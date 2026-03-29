<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\User;
use App\Media;
use App\Business;
use App\ProductJobOrder;
use App\Transaction;
use Carbon\Carbon;
use FFMpeg\FFMpeg;
use App\Utils\Util;
use FFMpeg\Media\Video;
use App\Utils\ModuleUtil;
use App\Utils\ContactUtil;
use App\Utils\ProductUtil;
use App\Restaurant\Booking;
use Illuminate\Http\Request;
use FFMpeg\Format\Video\X264;
use App\Utils\TransactionUtil;
use App\Utils\CashRegisterUtil;
use PDF;
use Mpdf\Mpdf; // Import mPDF
use Illuminate\Support\Facades\File;

use FFMpeg\Coordinate\Dimension;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Modules\Repair\Utils\RepairUtil;
use Modules\Repair\Entities\JobSheet;
use Illuminate\Support\Facades\Validator;
use Modules\Repair\Entities\RepairStatus;
use Modules\Connector\Transformers\JobSheetResource;
use Modules\Connector\Transformers\ServiceStaffResource;
use Modules\Connector\Transformers\ShowJobSheetResource;
use App\Utils\SmsUtil;
use Modules\Sms\Entities\SmsLog;


class JobSheetController extends Controller
{

    protected $repairUtil;

    protected $commonUtil;

    protected $cashRegisterUtil;

    protected $moduleUtil;
    protected $transactionUtil;

    protected $contactUtil;
    protected $productUtil;



    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(
        RepairUtil $repairUtil,
        Util $commonUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        TransactionUtil $transactionUtil,
    ) {



        $this->repairUtil = $repairUtil;
        $this->commonUtil = $commonUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function index()
    {
        $user = Auth::user();

        // Fully eager-loaded query (no SQL joins)
        $job_sheets = JobSheet::with([
                'invoices',
                'status',
                'workshop',
                'contact',
                'booking',
                'booking.contact',
                'booking.device',
                'booking.device.deviceModel',
                'booking.device.category',
                'booking.serviceType',
            ])
            ->where('location_id', $user->location_id)
            ->whereHas('invoices', function ($q) {
                $q->where('sub_type', 'repair')
                  ->where('status', 'under processing');
            })
            ->get();

        // Process service staff assignment (IDs stored as JSON on job_sheets)
        $userIds = collect($job_sheets)
            ->pluck('service_staff')
            ->filter()
            ->flatMap(function ($staff) {
                $decoded = json_decode($staff, true);
                return is_array($decoded) ? array_filter($decoded) : [];
            })
            ->unique()
            ->values();

        $serviceStaff = collect();
        if ($userIds->isNotEmpty()) {
            $serviceStaff = DB::table('users')
                ->whereIn('id', $userIds)
                ->select('id', DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, '')) AS name"))
                ->get()
                ->keyBy('id');
        }

        foreach ($job_sheets as $job_sheet) {
            $staffIds = json_decode($job_sheet->service_staff, true);
            $staffIds = is_array($staffIds) ? array_filter($staffIds) : [];

            $job_sheet->service_staff = collect($staffIds)
                ->map(fn($id) => $serviceStaff[$id] ?? null)
                ->filter()
                ->values();
        }

        return JobSheetResource::collection($job_sheets);
    }

    public function sendSmsToUsers($number)
    {
        try {
            $mobile_number = $number;
            $sms_body = 'لقد تم تأكيد الحجز بنجاح';

            $smsResult = SmsUtil::sendEpusheg($mobile_number, $sms_body);
            $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;
            
            if ($smsSent) {
                // Log SMS
                SmsLog::create([
                    'contact_id' => null,
                    'transaction_id' => null,
                    'job_sheet_id' => null,
                    'mobile' => $mobile_number,
                    'message_content' => $sms_body,
                    'status' => 'sent',
                    'error_message' => null,
                    'provider_balance' => is_array($smsResult) ? $smsResult['balance'] : SmsUtil::getLastNetBalance(),
                    'sent_at' => now(),
                ]);
                
                return response()->json($sms_body);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending SMS',
            ], 500);
        }
    }

    private function storeBase64JobSheetMedia(string $base64, int $jobSheetId): ?string
    {
        $mime = null;
        $data = $base64;

        if (preg_match('/^data:(.*?);base64,(.*)$/', $base64, $matches)) {
            $mime = $matches[1];
            $data = $matches[2];
        }

        $binary = base64_decode($data, true);
        if ($binary === false) {
            return null;
        }

        $extension = $this->guessExtensionFromMimeForJobSheet($mime);
        $fileName = time() . '_' . uniqid() . '_media.' . $extension;
        $filePath = "job_sheets/{$jobSheetId}/{$fileName}";

        \Illuminate\Support\Facades\Storage::disk('public')->put($filePath, $binary);

        return $filePath;
    }

    private function guessExtensionFromMimeForJobSheet(?string $mime): string
    {
        $mime = strtolower((string) $mime);

        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            return 'jpg';
        }
        if ($mime === 'image/png') {
            return 'png';
        }
        if ($mime === 'image/webp') {
            return 'webp';
        }
        if ($mime === 'image/gif') {
            return 'gif';
        }

        if ($mime === 'video/mp4') {
            return 'mp4';
        }
        if ($mime === 'video/quicktime' || $mime === 'video/mov') {
            return 'mov';
        }
        if ($mime === 'video/x-msvideo' || $mime === 'video/avi') {
            return 'avi';
        }
        if ($mime === 'video/x-matroska') {
            return 'mkv';
        }
        if ($mime === 'video/webm') {
            return 'webm';
        }

        return 'bin';
    }



    public function store(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;
        // Convert "null" strings to actual null values
        foreach (['booking_id','workshop_id', 'km', 'fuel_id', 'checklist', 'entry_date', 'start_date', 'due_date', 'delivery_date', 'status_id', 'car_condition'] as $field) {
            if ($request->has($field) && $request->input($field) === "null") {
                $request->merge([$field => null]);
            }
        }

        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'booking_id'    => 'required|integer|exists:bookings,id',
                // 'km'            => 'nullable|integer',
                'workshop_id'       => 'nullable|integer',
                'fuel_id'       => 'nullable|integer',
                'car_condition' => 'nullable|string',
                'checklist'     => 'nullable|array',
                'entry_date'    => 'nullable|date_format:Y-m-d H:i:s',
                'start_date'    => 'nullable|date_format:Y-m-d H:i:s',
                'due_date'      => 'nullable|date_format:Y-m-d H:i:s',
                'delivery_date' => 'nullable|date_format:Y-m-d H:i:s',
                'status_id'     => 'nullable|integer',
                'image'         => 'nullable|file', // Adjust max size if needed
                // Tagged images validation (multiple images with tags)
                'tag_imges'                  => 'nullable|array',
                'tag_imges.*.file'           => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,avi,mkv,webm',
                'tag_imges.*.base64'         => 'nullable|string',
                'tag_imges.*.file_name'      => 'nullable|string',
                'tag_imges.*.tag'            => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // Fetch booking details
            $booking = Booking::find($request->booking_id);
            if (!$booking) {
                return response()->json(['message' => 'Booking not found.'], 404);
            }

            // Check if a job sheet already exists for this booking
            if (JobSheet::where('booking_id', $booking->id)->exists()) {
                return response()->json(['message' => 'Booking already has a job order.'], 409);
            }

            DB::beginTransaction();

            try {
                $mobile = DB::table('contacts')->where('id', $booking->contact_id)->select('mobile')->first();
                // $sms = $this->sendSmsToUsers($mobile->mobile);

                // Update the booking status
                $booking->update(['booking_status' => 'booked']);
                $status = DB::table('repair_statuses')->first();

                // Prepare job sheet data
                $jobSheetData = $request->only([
                    'booking_id',
                    'km',
                    'fuel_id',
                    'car_condition',
                    'checklist',
                    'entry_date',
                    'start_date',
                    'due_date',
                    'delivery_date',
                    'status_id'
                ]);

                $jobSheetData['contact_id']  = $booking->contact_id;
                $jobSheetData['location_id'] = $booking->location_id;
                $jobSheetData['entry_date']  = $booking->booking_start;
                $jobSheetData['status_id']   = $status->id;
                $jobSheetData['created_by']  = auth()->id();
                $jobSheetData['business_id'] = $business_id;

                // Process checklist if IDs are provided
                if (!empty($request->checklist) && is_array($request->checklist)) {
                    $jobSheetData['checklist'] = $this->getChecklistObjects($request->checklist);
                }

                // Generate reference number
                $ref_count = $this->commonUtil->setAndGetReferenceCount('job_sheet', $business_id);
                $business = Business::find($business_id);
                $repair_settings = json_decode($business->repair_settings, true);
                $job_sheet_prefix = $repair_settings['job_sheet_prefix'] ?? '';

                $jobSheetData['job_sheet_no'] = $this->commonUtil->generateReferenceNumber('job_sheet', $ref_count, null, $job_sheet_prefix);

                // Fix date fields
                $dateFields = ['entry_date', 'start_date', 'due_date', 'delivery_date'];
                foreach ($dateFields as $field) {
                    if (!empty($jobSheetData[$field])) {
                        try {
                            $jobSheetData[$field] = Carbon::createFromFormat('Y-m-d H:i:s', $jobSheetData[$field])->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                        return response()->json([
                            'message' => "Invalid format for $field. Expected: Y-m-d H:i:s",
                            'error' => $e->getMessage()
                        ], 400);
                        }
                    }
                }

                // Remove fields with null values
                $jobSheetData = array_filter($jobSheetData, function ($value) {
                    return !is_null($value);
                });

                // Create job sheet
                $job_sheet = JobSheet::create($jobSheetData);

                // If booking has an estimator, link its product_joborder rows to this new job sheet
                if (!empty($booking->job_estimator_id)) {
                    DB::table('product_joborder')
                        ->where('job_estimator_id', $booking->job_estimator_id)
                        ->update([
                            'job_order_id' => $job_sheet->id,
                        ]);
                }

                // Handle Image Upload - Handle file uploads and base64 uploads separately
                if ($request->hasFile('image')) {
                    // Handle file upload
                    $file = $request->file('image');
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = "job_sheets/{$job_sheet->id}/{$fileName}";
                    \Illuminate\Support\Facades\Storage::disk('public')->putFileAs("job_sheets/{$job_sheet->id}", $file, $fileName);

                    Media::create([
                        'business_id' => $business_id,
                        'file_name' => $filePath,
                        'uploaded_by' => auth()->id(),
                        'model_id' => $job_sheet->id,
                        'model_type' => get_class($job_sheet),
                    ]);
                } elseif ($request->has('image') && !empty($request->image['base64'])) {
                    // Handle base64 upload separately
                    $image = $request->image;
                    if (!empty($image['base64']) && !empty($image['file_name'])) {
                        $base64String = $image['base64'];

                        // Handle base64 data URL format (data:image/jpeg;base64,...)
                        if (strpos($base64String, ',') !== false) {
                            $base64Array = explode(',', $base64String);
                            $base64String = $base64Array[1] ?? $base64Array[0];
                        }

                        if (Media::is_base64($base64String)) {
                            $uploadedFile = Media::uploadBase64Image($base64String);

                            // Create Media record with primary tag
                            Media::create([
                                'business_id' => $business_id,
                                'file_name' => $uploadedFile,
                                'uploaded_by' => auth()->id(),
                                'model_id' => $job_sheet->id,
                                'model_type' => get_class($job_sheet),
                            ]);
                        }
                    }
                }

                // Handle multiple tagged images upload
                $this->handleTaggedImagesUpload($request, $job_sheet, $business_id);

                // After creating the job sheet, fetch the device details
                $device = DB::table('contact_device')
                    ->where('id', $booking->device_id)
                    ->first();

                $existing_transaction = null;

                // If booking is linked to a job estimator, try to reuse its advance transaction
                if (!empty($booking->job_estimator_id)) {
                    $estimator = DB::table('job_estimator')
                        ->where('id', (int) $booking->job_estimator_id)
                        ->select('id', 'estimate_no')
                        ->first();

                    if ($estimator) {
                        $notes = [];
                        if (!empty($estimator->estimate_no)) {
                            $notes[] = 'Estimator #' . $estimator->estimate_no;
                        }
                        $notes[] = 'Estimator #' . $estimator->id;

                        $existing_transaction_id = DB::table('transaction_payments as tp')
                            ->join('transactions as t', 'tp.transaction_id', '=', 't.id')
                            ->where('tp.is_advance', 1)
                            ->where('tp.method', 'advance')
                            ->where('tp.payment_for', $booking->contact_id)
                            ->where('t.location_id', $booking->location_id)
                            ->where('t.type', 'sell')
                            ->whereIn('tp.note', $notes)
                            ->orderByDesc('tp.id')
                            ->value('t.id');

                        if (!empty($existing_transaction_id)) {
                            $existing_transaction = Transaction::find($existing_transaction_id);
                        }
                    }
                }

                if ($existing_transaction) {
                    // Reuse existing estimator transaction as the repair transaction
                    $existing_transaction->status = 'under processing';
                    $existing_transaction->sub_type = 'repair';
                    $existing_transaction->repair_brand_id = $device->device_id ?? null;
                    $existing_transaction->repair_status_id = $job_sheet->status_id ?? null;
                    $existing_transaction->repair_model_id = $device->models_id ?? null;
                    $existing_transaction->repair_job_sheet_id = $job_sheet->id;
                    $existing_transaction->location_id = $booking->location_id;
                    $existing_transaction->contact_id = $booking->contact_id;
                    $existing_transaction->save();
                } else {
                    $input = [
                        'location_id' => $booking->location_id,
                        'status' => 'under processing',
                        'type' => 'sell',
                        'total_before_tax' => 0,
                        'tax' => 0,
                        'final_total' => 0,
                        'contact_id' => $booking->contact_id,
                        'transaction_date' => Carbon::now(),
                        'discount_amount' => 0,
                        'sub_type' => 'repair',
                        'repair_brand_id'     => $device->device_id ?? null,
                        'repair_status_id'    => $job_sheet->status_id ?? null,
                        'repair_model_id'     => $device->models_id ?? null,
                        'repair_job_sheet_id' => $job_sheet->id
                    ];

                    $transaction = $this->transactionUtil->createSellTransaction(
                        $business_id,
                        $input,
                        ['total_before_tax' => 0, 'tax' => 0],
                        // $booking->location_id,
                        $user->id
                    );
                }

                DB::commit();

                return response()->json([
                    'message' => 'Job Sheet created successfully',
                    'data'    => $job_sheet
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error in creating job sheet', ['exception' => $e]);
            return response()->json([
                'message' => 'An error occurred',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function compressVideo($file, $path, $quality = 20) // Default CRF value is set to 20
    {
        try {
            // Get the original file path
            $inputFile = $file->getRealPath();

            // Define the output path
            $outputPath = storage_path('app/public/' . $path);

            // Ensure the directory exists
            $directory = dirname($outputPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Specify FFmpeg and FFProbe binaries explicitly
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => 'C:/Users/more2drive/ffmpeg-7.1-full_build/bin/ffmpeg.exe',
                'ffprobe.binaries' => 'C:/Users/more2drive/ffmpeg-7.1-full_build/bin/ffprobe.exe',
            ]);

            // Open the video file
            $video = $ffmpeg->open($inputFile);

            // Resize the video (optional - you can adjust or remove this based on your needs)
            $video->filters()->resize(new Dimension(1280, 720)); // Increase resolution to 720p for better quality

            // Create the X264 format instance with the correct codecs:
            $format = new X264('libmp3lame', 'libx264');

            // Adjust quality by setting CRF based on the quality parameter
            $format->setAdditionalParameters([
                '-crf',
                $quality,           // CRF to control the quality (lower = better quality)
                '-preset',
                'slow',          // Use slow preset for better compression efficiency (change this to 'medium' if you want faster processing)
                '-profile:v',
                'high',       // Set the profile to 'high' for better compression and quality
                '-level',
                '4.0',            // Set video level for compatibility (you can change based on the target device)
                '-b:v',
                '1500k',            // Set a bitrate to control the video size and quality
            ]);

            // Save the compressed video to the output path
            $video->save($format, $outputPath);

            // Get the final size after processing
            $finalSize = filesize($outputPath);

            Log::info('Video file processed', [
                'final_size' => $finalSize,
                'file_name' => $file->getClientOriginalName()
            ]);

            return $path;
        } catch (\Exception $e) {
            Log::error('Error processing video file', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName()
            ]);

            return null;
        }
    }

    private function getChecklistObjects(array $ids)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        // Fetch repair settings
        $repairSettings = Business::where('id', $businessId)->value('repair_settings');
        $repairSettings = !empty($repairSettings) ? json_decode($repairSettings, true) : [];

        $checklistString = $repairSettings['default_repair_checklist'] ?? '';

        if (empty($checklistString)) {
            return [];
        }

        // Convert string to array and filter out empty values
        $checklistItems = array_values(array_filter(array_map('trim', explode('|', $checklistString))));

        // Convert list into array of objects with indexed IDs
        $checklistData = array_map(fn($item, $index) => ['id' => $index + 1, 'title' => $item], $checklistItems, array_keys($checklistItems));

        // Convert list to associative array for quick lookup
        $checklistMap = collect($checklistData)->keyBy('id');

        // ✅ Sanitize IDs: Remove nulls, quotes, and convert to integers
        $validIds = collect($ids)
            ->map(fn($id) => is_numeric($id) ? (int) $id : null) // Convert to integer, else null
            ->filter(fn($id) => !is_null($id) && isset($checklistMap[$id])) // Remove nulls & invalid IDs
            ->toArray();

        // Match given IDs with their full object
        return array_values(array_intersect_key($checklistMap->toArray(), array_flip($validIds)));
    }



    // Helper function to validate video files
    public function validateVideo($file)
    {
        // List of allowed video MIME types
        $allowedTypes = [
            'video/mp4',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-flv',
            'video/webm'
        ];

        // Maximum file size (50MB for initial upload)
        $maxUploadSize = 50 * 1024 * 1024;

        // Check file size
        if ($file->getSize() > $maxUploadSize) {
            throw new \Exception('File size exceeds maximum limit of 50MB');
        }

        // Check file type
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid video format. Allowed formats: MP4, MOV, AVI, FLV, WEBM');
        }

        return true;
    }


    public function storeDocument($file, $path)
    {
        // Get the file's original name and store it at the specified path
        $outputPath = storage_path('app/public/' . $path);

        // Ensure the directory exists
        $directory = dirname($outputPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);  // Create the directory if it doesn't exist
        }

        // Move the document file to the designated path
        $file->move($directory, basename($outputPath));

        return $outputPath;
    }
    // Modified handleFileCompression to include validation


      public function show($id)
    {
        $user = Auth::user();
        $business_id = $user->business_id;


        // Fetch the job sheet with eager loaded relationships (no joins)
        $job_sheet = JobSheet::with([
                'media' => function($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'booking:id,device_id,contact_id,buyer_contact_id,service_type_id,location_id,booking_start,booking_note',
                'booking.media' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'booking.contact:id,name,mobile',
                'booking.device:id,device_id,models_id,car_type,chassis_number,plate_number,color,manufacturing_year,brand_origin_variant_id,motor_cc',
                'booking.device.brandOriginVariant:id,name,country_of_origin',
                'booking.device.deviceModel:id,name',
                'booking.device.category:id,name',
                'status:id,name,color,is_completed_status',
                'workshop:id,name',
                'location:id,name',
                'fuelStatus:id,name',
                'contact:id,name',
            ])
            ->find($id);

        // **Early return if job sheet is not found**
        if (!$job_sheet) {
            return response()->json(['message' => 'Job Sheet not found'], 404);
        }
        // return response()->json(['message' => $job_sheet->job_id], 404);


        // Ensure checklist is always an array
        $job_sheet->checklist = $job_sheet->checklist ?? [];

        // **Fetch Maintenance Notes**
        $contact_device_id = $job_sheet->booking->device_id ?? null;
        // Eager load maintenance notes and related entities, then map to the same shape
        $maintenance_notes = \Modules\Repair\Entities\MaintenanceNote::with([
                'creator:id,first_name,last_name,surname',
                'repairStatus:id,name,color',
                'jobSheet:id,job_sheet_no',
            ])
            ->where('device_id', $contact_device_id)
            ->get();

        // Preserve the original fields returned by the previous join-based query
        $maintenance_notes->transform(function ($note) {
            $full_name = trim(implode(' ', array_filter([
                $note->creator->surname ?? '',
                $note->creator->first_name ?? '',
                $note->creator->last_name ?? '',
            ])));

            // Build a plain object with all original note fields + computed fields
            $base = $note->toArray();
            $base['user_name'] = $full_name;
            $base['name'] = $note->repairStatus->name ?? null;
            $base['status_color'] = $note->repairStatus->color ?? null;
            $base['job_sheet_no'] = $note->jobSheet->job_sheet_no ?? null;
            return (object) $base;
        });

        // **Separate Notes & Chat**
        $notes = [];
        $chat = [];

        foreach ($maintenance_notes as $note) {
            $note->icon = 'f015'; // Default icon
            if ($note->category_status === 'note') {
                if ($note->job_sheet_id  ==  $id) {
                    $job_sheet->currnt_note = $note;
                } else {

                    $notes[] = $note;
                }
            } elseif ($note->category_status === 'comment' && $note->job_sheet_id  ==  $id) {
                $chat[] = $note;
            }
        }
        // Media from eager-loaded relation (latest first from with-order)
        $mediaCollection = collect($job_sheet->media);

        // Untagged job sheet media (no tag text)
        $job_sheet->jobSheet_media_list = $mediaCollection
            ->filter(function ($mediaItem) {
                return empty($mediaItem->description);
            })
            ->values()
            ->map(function ($mediaItem) {
                return [
                    'id' => $mediaItem->id,
                    'url' => $mediaItem->display_url,
                ];
            });

        $job_sheet->jobSheet_media = optional($mediaCollection
            ->first(function ($mediaItem) {
                return empty($mediaItem->description);
            }))
            ->display_url;

        // Tagged images: only media that has tag text in description
        $job_sheet->tagged_images = $mediaCollection
            ->filter(function ($mediaItem) {
                return !empty($mediaItem->description);
            })
            ->values()
            ->map(function ($mediaItem) {
                return [
                    'id' => $mediaItem->id,
                    'url' => $mediaItem->display_url,
                    'tag' => $mediaItem->description,
                ];
            });

        $bookingMediaCollection = collect(optional($job_sheet->booking)->media);
        $job_sheet->booking_images = $bookingMediaCollection
            ->values()
            ->map(function ($mediaItem) {
                return [
                    'id' => $mediaItem->id,
                    'url' => $mediaItem->display_url,
                    'file_name' => $mediaItem->file_name,
                    'created_at' => $mediaItem->created_at,
                ];
            });

        // Assign notes and chat
        $job_sheet->notes = $notes;
        $job_sheet->chat = $chat;

        // **Decode & Fetch Service Staff**
        $service_staff_ids = json_decode($job_sheet->service_staff, true) ?? [];
        $job_sheet->obd_id  = json_decode($job_sheet->obd_id, true) ?? [];

        $job_sheet->service_staff = empty($service_staff_ids) ? [] : DB::table('users')
            ->whereIn('id', $service_staff_ids)
            ->select('id',                 DB::raw("TRIM(CONCAT_WS(' ', COALESCE(surname, ''), COALESCE(first_name, ''), COALESCE(last_name, ''))) as name"))
            ->get();

        $spareParts = DB::table('product_joborder')
            ->leftJoin('products', 'products.id', '=', 'product_joborder.product_id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('categories as sub_cat', 'products.sub_category_id', '=', 'sub_cat.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('variations', 'variations.product_id', '=', 'products.id')
            ->leftJoin('variation_location_details as vld', function($join) use ($job_sheet) {
                $join->on('variations.id', '=', 'vld.variation_id')
                     ->where('vld.location_id', '=', $job_sheet->location_id);
            })
            ->where('product_joborder.job_order_id', $id)
            ->groupBy(
                'product_joborder.id',
                'product_joborder.job_order_id',
                'product_joborder.product_id',
                'products.name',
                'products.sku',
                'brands.name',
                'categories.name',
                'sub_cat.name',
                'products.enable_stock',
                'products.virtual_product',
                'products.is_client_flagged',
                'product_joborder.delivered_status',
                'product_joborder.client_approval',
                'product_joborder.price',
                'product_joborder.quantity',
                'product_joborder.out_for_deliver',
                'product_joborder.inventory_delivery',
                'product_joborder.product_status',
                'units.id',
                'units.actual_name',
                'units.allow_decimal'
            )
            ->select([
                "product_joborder.id",
                "product_joborder.job_order_id",
                "product_joborder.product_id",
                "products.name as product_name",
                "products.sku as sku",
                "brands.name as brand_name",
                "categories.name as category_name",
              
                "sub_cat.name as sub_category_name",
         
                "products.enable_stock",
                "products.virtual_product",
                "products.is_client_flagged",
                "product_joborder.delivered_status",
                "product_joborder.client_approval",
                "product_joborder.price",
                "product_joborder.quantity",
                "product_joborder.out_for_deliver",
                "product_joborder.product_status",
                "product_joborder.inventory_delivery",
                "units.id as unit_id",
                "units.actual_name as unit_name",
                "units.allow_decimal",
                DB::raw(
                    'COALESCE((SELECT SUM(tsl.quantity) FROM transaction_sell_lines tsl '
                    . 'JOIN variations v ON tsl.variation_id = v.id '
                    . 'WHERE v.product_id = product_joborder.product_id '
                    . 'AND tsl.transaction_id = (SELECT id FROM transactions WHERE repair_job_sheet_id = product_joborder.job_order_id LIMIT 1)), 0) '
                    . '+ COALESCE(SUM(vld.qty_available), 0) as qty_available'
                )
            ])
            ->get();

        $compatibilityByProduct = [];
        if ($spareParts->isNotEmpty()) {
            $productIds = $spareParts->pluck('product_id')->all();

            $compatRows = DB::table('product_compatibility as pc')
                ->leftJoin('repair_device_models as dm', 'pc.model_id', '=', 'dm.id')
                ->leftJoin('categories as bc', 'pc.brand_category_id', '=', 'bc.id')
                ->whereIn('pc.product_id', $productIds)
                ->select(
                    'pc.product_id',
                    'pc.from_year',
                    'pc.to_year',
                    'pc.motor_cc',
                    'dm.name as model_name',
                    'bc.name as brand_name'
                )
                ->orderBy('bc.name')
                ->orderBy('dm.name')
                ->orderBy('pc.from_year')
                ->get();

            foreach ($compatRows as $row) {
                $brand = $row->brand_name ?? null;
                $model = $row->model_name ?? null;
                $motorCc = $row->motor_cc ?? null;

                $yearLabel = null;
                if (!empty($row->from_year) && !empty($row->to_year)) {
                    $yearLabel = $row->from_year == $row->to_year ? (string) $row->from_year : $row->from_year . '-' . $row->to_year;
                } elseif (!empty($row->from_year)) {
                    $yearLabel = $row->from_year . '+';
                } elseif (!empty($row->to_year)) {
                    $yearLabel = $row->to_year . '-';
                }

                $parts = array_filter([$brand, $model, $motorCc, $yearLabel], function ($v) {
                    return $v !== null && $v !== '';
                });

                $compatibilityByProduct[$row->product_id][] = [
                    'brand' => $brand,
                    'model' => $model,
                    'motor_cc' => $motorCc,
                    'from_year' => $row->from_year,
                    'to_year' => $row->to_year,
                    'label' => !empty($parts) ? implode(' ', $parts) : null
                ];
            }

            $spareParts = $spareParts->map(function ($row) use ($compatibilityByProduct) {
                $row->compatibility = $compatibilityByProduct[$row->product_id] ?? [];
                return $row;
            });
        }
                    

        $job_sheet->spareParts = $spareParts;

        // Attach all job sheets for this contact device (id, job_sheet_no, date)
        $deviceLookupId = $job_sheet->contact_device_id ?? ($job_sheet->booking->device_id ?? null);

        $job_sheet->device_job_sheets = $this->getJobSheetsForDevice(
            $deviceLookupId,
            $business_id,
            $job_sheet->id
        );

        // Return the resource
        return new ShowJobSheetResource($job_sheet);
    }

    /**
     * Get all job sheets for the provided contact device.
     * Returns: id, job_sheet_no, date
     */
    private function getJobSheetsForDevice($contactDeviceId, $businessId, $excludeJobSheetId = null)
    {
        if (empty($contactDeviceId)) {
            return collect([]);
        }
        $vin = DB::table('contact_device')
            ->where('id', $contactDeviceId)
            ->value('chassis_number');

        return JobSheet::with('booking:id,device_id')
            ->whereHas('booking', function($query) use ($contactDeviceId, $vin) {
                $query->where(function ($q) use ($contactDeviceId, $vin) {
                    $q->where('device_id', $contactDeviceId);
                    if (!empty($vin)) {
                        $q->orWhereIn('device_id', function ($sub) use ($vin) {
                            $sub->from('contact_device')
                                ->select('id')
                                ->where('chassis_number', $vin);
                        });
                    }
                });
            })
            ->when($excludeJobSheetId, function ($query) use ($excludeJobSheetId) {
                $query->where('id', '!=', $excludeJobSheetId);
            })
            ->select([
                'id',
                'job_sheet_no',
                'booking_id',
                'km',
                DB::raw('COALESCE(entry_date, start_date, created_at) as date'),
                // Flag if a maintenance note exists (category_status = note)
                DB::raw("CASE WHEN EXISTS (SELECT 1 FROM maintenance_note mn WHERE mn.job_sheet_id = repair_job_sheets.id AND mn.category_status = 'note') THEN 1 ELSE 0 END as maintenance_note"),
                // Retrieve maintenance note status color if available
                DB::raw("(
                    SELECT rs.color
                    FROM maintenance_note mn
                    JOIN repair_statuses rs ON rs.id = mn.title
                    WHERE mn.job_sheet_id = repair_job_sheets.id
                        AND mn.category_status = 'note'
                    ORDER BY mn.id DESC
                    LIMIT 1
                ) as maintenance_note_status_color"),
            ])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function($sheet) {
                return [
                    'id' => $sheet->id,
                    'job_sheet_no' => $sheet->job_sheet_no,
                    'date' => $sheet->date,
                    'km' => $sheet->km,
                    'maintenance_note' => (int) ($sheet->maintenance_note ?? 0),
                    'maintenance_note_status_color' => $sheet->maintenance_note_status_color,
                ];
            });
    }

    public function update(Request $request, $id)
    {


        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'location_id' => 'nullable|integer',
                'contact_id' => 'nullable|integer',
                'status_id' => 'nullable|integer',
                'fuel_id' => 'nullable|integer',
                'workshop_id' => 'nullable|integer',
                'estimated_cost' => 'nullable|numeric',
                
                // 'car_type' => 'nullable|string',
                'car_condition' => 'nullable|string',
                'start_date' => 'nullable|date',
                'delivery_date' => 'nullable|date',
                'due_date' => 'nullable|date|after_or_equal:start_date',
                'service_type_id' => 'nullable|integer',
                // 'km' => 'nullable|integer',
                'workshop_id' => 'nullable|integer',
                'service_staff' => 'nullable|array',
                'service_staff.*' => 'integer',

                // Checklist validation
                'checklist' => 'nullable|array',
                'checklist.*' => 'integer',

                // Checklist validation
                'obd_id' => 'nullable|array',
                'obd_id.*' => 'integer',

                // Maintenance note validation
                'maintenance_note' => 'nullable|array',
                'maintenance_note.*.title' => 'nullable|integer',
                'maintenance_note.*.content' => 'nullable|string',
                'maintenance_note.*.device_id' => 'nullable|integer',
                'maintenance_note.*.category_status' => 'nullable|string|in:note,comment',

                // Image validation
                'image.file_name' => 'required_with:image|string',
                'image.file_type' => 'required_with:image|string',
                'image.base64' => 'nullable|string',
                // Tagged images validation (multiple images with tags)
                'tag_imges'                  => 'nullable|array',
                'tag_imges.*.file'           => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,avi,mkv,webm',
                'tag_imges.*.base64'         => 'nullable|string',
                'tag_imges.*.file_name'      => 'nullable|string',
                'tag_imges.*.tag'            => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = Auth::user();
            $business_id = $user->business_id;

            // Find Job Sheet
            $job_sheet = JobSheet::where('business_id', $business_id)->find($id);
            if (!$job_sheet) {
                return response()->json(['message' => 'Job Sheet not found.'], 404);
            }

            // Update service_type_id if provided
            if (!empty($request->service_type_id)) {
                $booking = Booking::find($job_sheet->booking_id);
                if ($booking) {
                    $booking->update(['service_type_id' => $request->service_type_id]);
                }
            }

            // Remove null values from request data
            $updateData = collect($request->all())->filter(fn($value) => !is_null($value))->toArray();

            // Process checklist if IDs are provided
            if (!empty($updateData['checklist']) && is_array($updateData['checklist'])) {
                $updateData['checklist'] = $this->getChecklistObjects($updateData['checklist']);
            }


            // Update Job Sheet
            $job_sheet->update($updateData);

            // Handle maintenance notes
            if (!empty($request->maintenance_note)) {
                $this->storeMaintenanceNote($request->maintenance_note, $job_sheet, $user);
            }

            // Handle Image Upload - Handle file uploads and base64 uploads separately
            \Log::info('[JobSheet Update] Image handling started', [
                'hasFile' => $request->hasFile('image'),
                'hasImage' => $request->has('image'),
                'imageData' => $request->image,
            ]);

            if ($request->hasFile('image')) {
                \Log::info('[JobSheet Update] File upload detected');
                // Handle file upload
                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = "job_sheets/{$job_sheet->id}/{$fileName}";
                \Illuminate\Support\Facades\Storage::disk('public')->putFileAs("job_sheets/{$job_sheet->id}", $file, $fileName);

                $media = Media::create([
                    'business_id' => $business_id,
                    'file_name' => $filePath,
                    'uploaded_by' => auth()->id(),
                    'model_id' => $job_sheet->id,
                    'model_type' => get_class($job_sheet),
                ]);
                \Log::info('[JobSheet Update] File upload Media created', ['id' => $media->id]);
            } elseif ($request->has('image') && !empty($request->image['base64'])) {
                \Log::info('[JobSheet Update] Base64 upload detected');
                // Handle base64 upload separately
                $image = $request->image;
                if (!empty($image['base64']) && !empty($image['file_name'])) {
                    $base64String = $image['base64'];

                    // Handle base64 data URL format (data:image/jpeg;base64,...)
                    if (strpos($base64String, ',') !== false) {
                        $base64Array = explode(',', $base64String);
                        $base64String = $base64Array[1] ?? $base64Array[0];
                    }

                    if (Media::is_base64($base64String)) {
                        $uploadedFile = Media::uploadBase64Image($base64String, "job_sheets/{$job_sheet->id}");
                        \Log::info('[JobSheet Update] Base64 uploaded to', ['file' => $uploadedFile]);

                        // Create Media record with primary tag
                        $media = Media::create([
                            'business_id' => $business_id,
                            'file_name' => $uploadedFile,
                            'uploaded_by' => auth()->id(),
                            'model_id' => $job_sheet->id,
                            'model_type' => get_class($job_sheet),
                        ]);
                        \Log::info('[JobSheet Update] Base64 Media created', ['id' => $media->id]);
                    }
                }
            } elseif ($request->has('image') && !empty($request->image['url']) && empty($request->image['base64'])) {
                \Log::info('[JobSheet Update] URL reference detected');
                // Handle existing image URL reference (no base64 = existing image)
                $image = $request->image;
                if (!empty($image['url']) && !empty($image['file_name'])) {
                    $media = Media::create([
                        'business_id' => $business_id,
                        'file_name' => $image['url'],
                        'uploaded_by' => auth()->id(),
                        'model_id' => $job_sheet->id,
                        'model_type' => get_class($job_sheet),
                    ]);
                    \Log::info('[JobSheet Update] URL Media created', ['id' => $media->id, 'url' => $image['url']]);
                } else {
                    \Log::warning('[JobSheet Update] URL case missing url or file_name', ['image' => $image]);
                }
            } else {
                \Log::warning('[JobSheet Update] No image case matched', [
                    'hasImage' => $request->has('image'),
                    'imageKeys' => $request->has('image') ? array_keys(is_array($request->image) ? $request->image : ($request->image->toArray() ?? [])) : [],
                ]);
            }

            // Handle multiple tagged images upload
            $this->handleTaggedImagesUpload($request, $job_sheet, $business_id);

            return response()->json(['message' => 'Job Sheet updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function handleFileCompression($file, $jobSheetId, $businessId)
    {
        // Generate a unique file name
        $fileName = time() . '_' . $file->getClientOriginalName();
        $path = "job_sheets/{$jobSheetId}/{$fileName}";

        // Get file MIME type
        $mimeType = $file->getMimeType();

        try {
            if (str_starts_with($mimeType, 'image')) {
                return $this->compressImage($file, $path);
            } else // Example usage in handleFileCompression
                if (str_starts_with($mimeType, 'video')) {
                    // Validate video before processing
                    $this->validateVideo($file);

                    // Set desired quality (e.g., 18 for higher quality, 23 for lower quality)
                    $quality = 10; // You can modify this value based on your needs
                    return $this->compressVideo($file, $path, $quality);
                } elseif (
                    str_starts_with($mimeType, 'application/pdf') ||
                    str_starts_with($mimeType, 'application/msword') ||
                    str_starts_with($mimeType, 'application/vnd.ms-excel')
                ) {
                    return $this->storeDocument($file, $path);
                }
        } catch (\Exception $e) {
            Log::error('File processing error', [
                'error' => $e->getMessage(),
                'file_type' => $mimeType,
                'file_name' => $file->getClientOriginalName()
            ]);
            return null;
        }

        return null;
    }


    public function compressImage($imagePath, $savePath)
    {
        // Load the image
        $image = imagecreatefromstring(file_get_contents($imagePath));

        // Check if the image was created successfully
        if (!$image) {
            throw new \Exception("Could not create image from file.");
        }

        // Set the desired quality (0-100)
        $quality = 100;

        // Define full storage path
        $fullPath = storage_path('app/public/' . $savePath);

        // Ensure the directory exists
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Save the compressed image as JPEG
        if (imagejpeg($image, $fullPath, $quality)) {
            // Free up memory
            imagedestroy($image);
            return $savePath; // Return the relative path
        } else {
            throw new \Exception("Failed to save compressed image.");
        }
    }


    /**
     * Handle multiple tagged images upload for job sheet
     * Supports both file uploads and base64 encoded images
     */
    private function handleTaggedImagesUpload(Request $request, $job_sheet, $business_id)
    {
        if (!$request->has('tag_imges') || !is_array($request->input('tag_imges'))) {
            return;
        }

        $images = $request->input('tag_imges');
        $files = $request->file('tag_imges') ?? [];

        foreach ($images as $index => $imageData) {
            $tagText = $imageData['tag'] ?? null;
            $uploadedFile = null;

            // Check if there's a file upload for this index
            if (isset($files[$index]['file']) && $files[$index]['file'] instanceof \Illuminate\Http\UploadedFile) {
                $file = $files[$index]['file'];
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = "job_sheets/{$job_sheet->id}/{$fileName}";
                
                \Illuminate\Support\Facades\Storage::disk('public')->putFileAs("job_sheets/{$job_sheet->id}", $file, $fileName);
                $uploadedFile = $filePath;
            }
            // Check for base64 encoded image
            elseif (!empty($imageData['base64'])) {
                $base64String = $imageData['base64'];

                $uploadedFile = $this->storeBase64JobSheetMedia($base64String, $job_sheet->id);
            }

            // Create Media record with tag if file was uploaded
            if ($uploadedFile) {
                Media::create([
                    'business_id' => $business_id,
                    'file_name' => $uploadedFile,
                    'uploaded_by' => auth()->id(),
                    'model_id' => $job_sheet->id,
                    'model_type' => get_class($job_sheet),
                    'description' => $tagText,
                ]);
            }
        }
    }

    private function storeMaintenanceNote($maintenanceNotes, $job_sheet, $user)
    {
        foreach ($maintenanceNotes as &$note) {
            // Skip if all values except category_status are null
            if (empty(array_filter($note, function ($value, $key) {
                return $key !== 'category_status' && !is_null($value);
            }, ARRAY_FILTER_USE_BOTH))) {
                continue;
            }

            // Set common fields
            $note['job_sheet_id'] = $job_sheet->id;
            $note['created_by'] = $user->id;
            $note['created_at'] = now();

            // Handle based on category_status
            if ($note['category_status'] === 'note') {
                // For notes: check if existing note exists and update it, otherwise create new
                $existingNote = DB::table('maintenance_note')
                    ->where('job_sheet_id', $job_sheet->id)
                    ->where('category_status', 'note')
                    ->first();

                if ($existingNote) {
                    // Update existing note
                    DB::table('maintenance_note')
                        ->where('job_sheet_id', $job_sheet->id)
                        ->where('category_status', 'note')
                        ->update($note);
                } else {
                    // Insert new note
                    DB::table('maintenance_note')->insert($note);
                }
            } elseif ($note['category_status'] === 'comment') {
                // For comments: always insert as new record
                DB::table('maintenance_note')->insert($note);
            }
        }
    }

    public function destroy($id)
    {
        $jobSheet = JobSheet::findOrFail($id);
        $jobSheet->delete();

        return response()->json(['message' => 'Job Sheet deleted successfully']);
    }

    /**
     * Get all image tags for the authenticated user's business
     */
    public function getImageTags()
    {
        $business_id = auth()->user()->business_id;

        $tags = Media::where('business_id', $business_id)
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->distinct()
            ->orderBy('description')
            ->pluck('description')
            ->values();

        return response()->json(['data' => $tags], 200);
    }

    /**
     * Update the image tag for a specific media item
     */
    public function updateMediaTag(Request $request, $mediaId)
    {
        $validator = Validator::make($request->all(), [
            'tag' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $business_id = auth()->user()->business_id;

        $media = Media::where('business_id', $business_id)->find($mediaId);

        if (!$media) {
            return response()->json(['message' => 'Media not found.'], 404);
        }

        $media->update(['description' => $request->tag]);

        return response()->json([
            'message' => 'Media tag updated successfully.',
            'data' => [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'url' => $media->display_url,
                'tag' => $media->description,
            ],
        ], 200);
    }

    /**
     * Delete a media item from a job sheet
     */
    public function deleteMedia($mediaId)
    {
        $business_id = auth()->user()->business_id;

        $media = Media::where('business_id', $business_id)->find($mediaId);

        if (!$media) {
            return response()->json(['message' => 'Media not found.'], 404);
        }

        Media::deleteMedia($business_id, $mediaId);

        return response()->json(['message' => 'Media deleted successfully.'], 200);
    }

    /**
     * Delete a tagged image from a job sheet
     */
    public function deleteTaggedImage($job_sheet_id, $media_id)
    {
        $user = auth()->user();
        $business_id = $user->business_id;

        $job_sheet = JobSheet::where('business_id', $business_id)
            ->where('location_id', $user->location_id)
            ->find($job_sheet_id);

        if (!$job_sheet) {
            return response()->json(['message' => 'Job Sheet not found.'], 404);
        }

        $media = Media::where('business_id', $business_id)
            ->where('id', $media_id)
            ->where('model_id', $job_sheet->id)
            ->where('model_type', get_class($job_sheet))
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->first();

        if (!$media) {
            return response()->json(['message' => 'Tagged image not found.'], 404);
        }

        Media::deleteMedia($business_id, $media->id);

        return response()->json(['message' => 'Tagged image deleted successfully.'], 200);
    }

public function apiPrint($id)
{
    // Retrieve business_id from authenticated user
    $business_id = auth()->user()->business_id;

    // Fetch the job sheet along with related details
    $job_sheet = JobSheet::with([
        'customer:id,name,mobile,business_id',
        'customer.business:id,name,logo',
        'technician:id,surname,first_name,last_name',
        'status:id,name,color',
        'Brand:id,name',
        'Device:id,name',
        'deviceModel:id,name',
        'businessLocation:id,name',
        'invoices',
        'media',
        'booking:id,device_id,contact_id,service_type_id,booking_note',
        'booking.device:id,device_id,models_id,color,plate_number,chassis_number,manufacturing_year',
        'booking.device.deviceModel:id,name',
        'booking.device.category:id,name',
        'booking.contact:id,name,mobile',
        'booking.serviceType:id,name'
    ])
        ->where('repair_job_sheets.business_id', $business_id)
        ->findOrFail($id);

    // Compute top-level fields used by the Blade template
    $device  = optional(optional($job_sheet->booking)->device);
    $contact = optional(optional($job_sheet->booking)->contact);

    $job_sheet->plate_number        = $job_sheet->plate_number ?? ($device->plate_number ?? null);
    $job_sheet->manufacturing_year  = $job_sheet->manufacturing_year ?? ($device->manufacturing_year ?? null);
    $job_sheet->chassis_number      = $job_sheet->chassis_number ?? ($device->chassis_number ?? null);
    $job_sheet->color               = $job_sheet->color ?? ($device->color ?? null);
    $job_sheet->brand_name          = optional($job_sheet->Brand)->name ?? optional($device->category)->name ?? null;
    $job_sheet->model_name          = optional($job_sheet->deviceModel)->name ?? optional($device->deviceModel)->name ?? null;
    $job_sheet->customer_name       = $job_sheet->customer_name ?? ($contact->name ?? optional($job_sheet->customer)->name ?? null);
    $job_sheet->customer_phone      = $job_sheet->customer_phone ?? ($contact->mobile ?? optional($job_sheet->customer)->mobile ?? null);
    $job_sheet->service_type        = $job_sheet->service_type ?? optional(optional($job_sheet->booking)->serviceType)->name ?? null;
    $job_sheet->problem_reported_by_customer = $job_sheet->problem_reported_by_customer ?? (optional($job_sheet->booking)->booking_note ?? null);
    $job_sheet->booking_note         = $job_sheet->booking_note ?? optional($job_sheet->booking)->booking_note ?? null;
    $job_sheet->logo                = $job_sheet->logo ?? optional(optional($job_sheet->customer)->business)->logo ?? null;
    $job_sheet->car_condition       = $job_sheet->car_condition ?? $job_sheet->comment_by_ss ?? null;

    // Fetch parts used in this job order along with estimated cost using eager loading
    $job_sheet->load([
        'productJobOrders' => function($query) {
            $query->where(function($q) {
                $q->where('delivered_status', 1)
                  ->orWhere('out_for_deliver', 1);
            })->select(['id', 'job_order_id', 'product_id', 'quantity', 'price', 'delivered_status', 'out_for_deliver']);
        },
        'productJobOrders.product:id,name,unit_id,sku',
        'productJobOrders.product.unit:id,short_name'
    ]);

    // Map parts with calculated total price
    $parts = $job_sheet->productJobOrders->map(function($jobOrder) {
        return (object) [
            'product_sku'  => optional($jobOrder->product)->sku ?? null,
            'product_name' => optional($jobOrder->product)->name ?? null,
            'quantity'     => $jobOrder->quantity,
            'price'        => $jobOrder->price,
            'unit'         => optional(optional($jobOrder->product)->unit)->short_name ?? null,
            'total_price'  => ($jobOrder->quantity ?? 0) * ($jobOrder->price ?? 0),
        ];
    });

    // Calculate total estimated cost
    $job_sheet->parts = $parts;
    $job_sheet->estimated_cost = $parts->sum('total_price');

    // **Decode & Fetch Service Staff**
    $service_staff_ids = json_decode($job_sheet->service_staff, true) ?? [];
    $job_sheet->service_staff = empty($service_staff_ids) ? [] : DB::table('users')
        ->whereIn('id', $service_staff_ids)
        ->select('id', DB::raw("CONCAT(surname, ' ', first_name) AS technicans"))
        ->get();

    // Prepare media - find first untagged media (same logic as show method)
    $mediaCollection = collect($job_sheet->media ?? []);
    
    // Untagged job sheet media list
    $job_sheet->media_list = $mediaCollection
        ->filter(function ($m) {
            return empty($m->description) || trim($m->description) === '';
        })
        ->values()
        ->map(function ($mediaItem) {
            return [
                'id' => $mediaItem->id,
                'url' => $mediaItem->display_url,
            ];
        });
    
    // Find first untagged media (no description)
    $firstUntagged = $mediaCollection->first(function ($mediaItem) {
        return empty($mediaItem->description) || trim($mediaItem->description) === '';
    });

    // Set jobSheet_media exactly like in show() method - use display_url
    $job_sheet->jobSheet_media = optional($firstUntagged)->display_url ?? null;

    // Fetch business details
    $business = Business::find($business_id);
    
    // Decode JSON settings safely
    $repair_settings = $business->repair_settings ? json_decode($business->repair_settings, true) : [];
    $jobsheet_settings = $business->repair_jobsheet_settings ? json_decode($business->repair_jobsheet_settings, true) : [];

    // Render the view as HTML
    $html = view('repair::job_sheet.print_pdf_api')
        ->with(compact('job_sheet', 'repair_settings', 'parts', 'jobsheet_settings'))
        ->render();

    // Initialize mPDF with custom settings
    $mpdf = new \Mpdf\Mpdf([
        'tempDir'         => storage_path('app/public/jobsheet_pdf'),
        'mode'            => 'utf-8',
        'autoScriptToLang' => true,
        'autoLangToFont'  => true,
        'autoVietnamese'  => true,
        'autoArabic'      => true,
        'margin_top'      => 8,
        'margin_bottom'   => 8,
    ]);

    // Enable remote image fetching
    $mpdf->showImageErrors = true; // Show errors if images fail to load
    $mpdf->useSubstitutions = true;
    $mpdf->SetTitle(__('repair::lang.job_sheet') . ' | ' . $job_sheet->job_sheet_no);
    $mpdf->WriteHTML($html);

    // Generate PDF file path
    $fileName = $job_sheet->id . '.pdf';
    $filePath = storage_path('app/public/jobsheet_pdf/' . $fileName);

    // Ensure directory exists
    if (!file_exists(storage_path('app/public/jobsheet_pdf'))) {
        mkdir(storage_path('app/public/jobsheet_pdf'), 0777, true);
    }

    // Save PDF to storage
    $mpdf->Output($filePath, 'F');

    // Generate public URL for the file
    $fileUrl = asset('storage/jobsheet_pdf/' . $fileName);

    // Return the file URL in JSON response
    return response()->json([
        'success' => true,
        'message' => 'PDF generated successfully.',
        'download_url' => $fileUrl
    ], 200);
}
}
