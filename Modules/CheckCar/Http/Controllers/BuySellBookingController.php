<?php

namespace Modules\CheckCar\Http\Controllers;

use App\Restaurant\Booking;
use App\Transaction;
use App\ProductJobOrder;
use App\Media;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Utils\SmsUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\CheckCar\Entities\CheckCarServiceSetting;
use Modules\CheckCar\Entities\CarInspection;
use Modules\CheckCar\Entities\CheckCarInspectionItem;
use Modules\CheckCar\Entities\CheckCarElement;
use Modules\CheckCar\Entities\CheckCarElementOption;
use Modules\Repair\Entities\JobSheet;
use Modules\Sms\Entities\SmsLog;

class BuySellBookingController extends Controller
{
    /** @var Util */
    protected $commonUtil;

    /** @var TransactionUtil */
    protected $transactionUtil;

    public function __construct(Util $commonUtil, TransactionUtil $transactionUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Return unified contact (buyer/seller + optional seller vehicle) modal HTML.
     */
    public function createContactModal(Request $request)
    {
        return view('restaurant.booking.create_contact_modal');
    }

    /**
     * Create buyer and/or seller contacts and, for seller with vehicle data, a contact_device.
     *
     * Expects optionally:
     * - buyer_first_name, buyer_name, buyer_mobile, buyer_email
     * - seller_first_name, seller_name, seller_mobile, seller_email
     * - Optional seller vehicle fields: seller_chassis_number, seller_car_type, seller_category_id,
     *   seller_model_id, seller_manufacturing_year, seller_brand_origin_variant_id,
     *   seller_color, seller_plate_number
     */
    public function storeContact(Request $request)
    {
        // Get business and user up-front so we can scope unique rules
        $business_id = $request->session()->get('user.business_id');
        $user_id     = $request->session()->get('user.id');

        $validated = $request->validate([
            'buyer_first_name'  => 'nullable|string|max:255',
            'buyer_last_name'   => 'nullable|string|max:255',
            // Enforce uniqueness per business when a buyer is created
            'buyer_mobile'      => 'nullable|string|max:255|unique:contacts,mobile,NULL,id,business_id,' . $business_id,
            'buyer_national_id' => 'nullable|string|max:255|unique:contacts,custom_field1,NULL,id,business_id,' . $business_id,
            'seller_first_name' => 'nullable|string|max:255',
            'seller_last_name'  => 'nullable|string|max:255',
            // Enforce uniqueness per business when a new seller contact is created
            'seller_mobile'     => 'nullable|string|max:255|unique:contacts,mobile,NULL,id,business_id,' . $business_id,
            'seller_national_id'=> 'nullable|string|max:255|unique:contacts,custom_field1,NULL,id,business_id,' . $business_id,
            'seller_license_number' => 'nullable|string|max:255',
            'seller_license_expiry' => 'nullable|date',
            // When adding only a vehicle we can receive an existing seller_contact_id
            'seller_contact_id'            => 'nullable|integer|exists:contacts,id',
            // Seller vehicle fields are optional but at least one of buyer/seller/vehicle
            // must be provided overall.
            'seller_chassis_number'          => 'nullable|string|max:255',
            'seller_car_type'                => 'nullable|string|max:255',
            'seller_category_id'             => 'nullable|integer|exists:categories,id',
            'seller_model_id'                => 'nullable|integer|exists:repair_device_models,id',
            'seller_manufacturing_year'      => 'nullable|integer',
            'seller_brand_origin_variant_id' => 'nullable|integer|exists:brand_origin_variants,id',
            'seller_color'                   => 'nullable|string|max:255',
            'seller_plate_number'            => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            $now = Carbon::now();

            $buyer = null;
            $seller = null;

            $existingSellerId = $validated['seller_contact_id'] ?? null;

            // Vehicle data present?
            $hasVehicleData = !empty($validated['seller_category_id'])
                || !empty($validated['seller_model_id'])
                || !empty($validated['seller_chassis_number'])
                || !empty($validated['seller_plate_number']);

            // Consider buyer "present" if any of its core fields are filled
            $hasBuyer = !empty($validated['buyer_first_name'])
                || !empty($validated['buyer_last_name'])
                || !empty($validated['buyer_mobile'])
                || !empty($validated['buyer_national_id']);

            // Seller can be either a new contact (name/mobile filled) or an existing contact id
            $hasSeller = $existingSellerId
                || !empty($validated['seller_first_name'])
                || !empty($validated['seller_last_name'])
                || !empty($validated['seller_mobile'])
                || !empty($validated['seller_national_id'])
                || !empty($validated['seller_license_number'])
                || !empty($validated['seller_license_expiry']);

            // Require at least one of buyer / seller / vehicle data
            if (!$hasBuyer && !$hasSeller && !$hasVehicleData) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.something_went_wrong'),
                ], 422);
            }

            // Create buyer contact if provided
            if ($hasBuyer) {
                $buyerFullName = trim($validated['buyer_first_name'].' '.$validated['buyer_last_name']);
                $buyerId = DB::table('contacts')->insertGetId([
                    'business_id'   => $business_id,
                    'created_by'    => $user_id,
                    'first_name'    => $validated['buyer_first_name'],
                    'last_name'     => $validated['buyer_last_name'],
                    'name'          => $buyerFullName,
                    'mobile'        => $validated['buyer_mobile'],
                    'custom_field1' => $validated['buyer_national_id'],
                    'type'          => 'customer',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);

                $buyer = [
                    'id'   => $buyerId,
                    'name' => $buyerFullName,
                ];
            }

            // Create seller contact (and optional device) if provided
            if ($hasSeller || $hasVehicleData) {
                $sellerId = $existingSellerId;
                $sellerFullName = null;

                // If no existing seller id, create a new contact from the provided seller_* fields
                if (!$sellerId) {
                    $sellerFullName = trim($validated['seller_first_name'].' '.$validated['seller_last_name']);
                    $sellerId = DB::table('contacts')->insertGetId([
                        'business_id'   => $business_id,
                        'created_by'    => $user_id,
                        'first_name'    => $validated['seller_first_name'],
                        'last_name'     => $validated['seller_last_name'],
                        'name'          => $sellerFullName,
                        'mobile'        => $validated['seller_mobile'],
                        'custom_field1' => $validated['seller_national_id'],
                        'custom_field2' => $validated['seller_license_number'] ?? null,
                        'custom_field3' => $validated['seller_license_expiry'] ?? null,
                        'type'          => 'customer',
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                } else {
                    // Existing seller: fetch name for response payload
                    $sellerContact = DB::table('contacts')->where('id', $sellerId)->first();
                    $sellerFullName = $sellerContact ? $sellerContact->name : null;
                }

                $seller = [
                    'id'   => $sellerId,
                    'name' => $sellerFullName,
                ];

                $contactDevice = null;
                if ($hasVehicleData) {
                    // Validate that the selected model belongs to the selected brand
                    if (!empty($validated['seller_model_id']) && !empty($validated['seller_category_id'])) {
                        $modelBelongsToBrand = DB::table('repair_device_models')
                            ->where('id', $validated['seller_model_id'])
                            ->where('device_id', $validated['seller_category_id'])
                            ->exists();

                        if (!$modelBelongsToBrand) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => __('checkcar::lang.model_does_not_belong_to_brand') ?? 'The selected model does not belong to the selected brand. Please select a valid model.',
                            ], 422);
                        }
                    }

                    $contactDeviceId = DB::table('contact_device')->insertGetId([
                        'device_id'              => $validated['seller_category_id'] ?? null,
                        'models_id'              => $validated['seller_model_id'] ?? null,
                        'color'                  => $validated['seller_color'] ?? null,
                        'chassis_number'         => $validated['seller_chassis_number'] ?? null,
                        'plate_number'           => $validated['seller_plate_number'] ?? null,
                        'manufacturing_year'     => $validated['seller_manufacturing_year'] ?? null,
                        'contact_id'             => $sellerId,
                        'car_type'               => $validated['seller_car_type'] ?? null,
                        'brand_origin_variant_id'=> $validated['seller_brand_origin_variant_id'] ?? null,
                    ]);

                    // Build display name for the new device
                    $modelName = '';
                    if (!empty($validated['seller_model_id'])) {
                        $model = DB::table('repair_device_models')->where('id', $validated['seller_model_id'])->first();
                        $modelName = $model ? $model->name : '';
                    }

                    $contactDevice = [
                        'id'           => $contactDeviceId,
                        'model_name'   => $modelName,
                        'plate_number' => $validated['seller_plate_number'] ?? null,
                        'color'        => $validated['seller_color'] ?? null,
                    ];
                }

                $seller['contact_device'] = $contactDevice;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('messages.success') ?? 'Contacts created successfully',
                'buyer'   => $buyer,
                'seller'  => $seller,
                'contact_device' => $seller ? ($seller['contact_device'] ?? null) : null,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error in BuySellBookingController@storeContact', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    /**
     * Handle Buy & Sell Car Inspection booking + job sheet + transaction + product/joborder.
     */
    public function store(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            if (empty($business_id) || empty($user_id)) {
                abort(403, 'Unauthorized');
            }

            // Base validation, similar to BookingController@store_new_booking
            // `services` is no longer selected in the UI; allow it to be null and
            // fall back to the configured CheckCar service type in the controller.
            $validated = $request->validate([
                'contact_id' => 'required|integer',
                'buyer_contact_id' => 'nullable|integer',
                'model_id' => 'required|integer',
                'services' => 'nullable|integer',
                'location_id' => 'required|integer',
                'booking_start' => 'required|date',
                'booking_note' => 'nullable|string|max:255',
                'service_price' => 'nullable|numeric|min:0',
                'verification_required' => 'nullable|boolean',
                'transaction_contact_type' => 'nullable|in:seller,buyer',
            ]);

            // Fetch contact/device/brand
            $contact = DB::table('contacts')->where('id', $validated['contact_id'])->first();
            $device = DB::table('contact_device')->where('id', $validated['model_id'])->first();
            $brand  = $device
                ? DB::table('repair_device_models')->where('id', $device->models_id)->first()
                : null;

            if (!$contact || !$device || !$brand) {
                $message = __('messages.something_went_wrong');

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'msg'     => $message,
                    ], 422);
                }

                return back()->withErrors($message);
            }

            // Prevent duplicate bookings: check if a booking already exists for the same
            // seller, buyer, device, and location within the last 5 minutes
            $recentDuplicateBooking = DB::table('bookings')
                ->where('contact_id', $validated['contact_id'])
                ->where('device_id', $validated['model_id'])
                ->where('location_id', $validated['location_id'])
                ->where('created_at', '>=', Carbon::now()->subMinutes(5))
                ->first();

            if ($recentDuplicateBooking) {
                $message = __('checkcar::lang.duplicate_booking_detected') ?? 'A similar booking was already created recently. Please check existing bookings.';

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'msg'     => $message,
                    ], 422);
                }

                return back()->withErrors($message);
            }

            $booking_start = Carbon::parse($validated['booking_start']);
            $booking_end   = $booking_start->copy()->addHour();

            $booking_name = $contact->name . ' - ' . $brand->name . ' - ' . $booking_start->toDateTimeString();

            // Resolve service type: use request value if present, otherwise
            // fall back to the configured CheckCar service type for this business.
            $service_type_id = $validated['services'] ?? null;
            if ($service_type_id === null) {
                $serviceSetting = DB::table('types_of_services')
                    ->where('is_inspection_service', 1)
                    ->first();

                if ($serviceSetting && !empty($serviceSetting->id)) {
                    $service_type_id = $serviceSetting->id;
                }
            }

            DB::beginTransaction();

            // 1) Create booking row (replicates restaurant booking store_new_booking)
            $booking_id = DB::table('bookings')->insertGetId([
                'business_id'      => $business_id,
                'created_by'       => $user_id,
                'booking_name'     => $booking_name,
                'contact_id'       => $validated['contact_id'],
                'buyer_contact_id' => $validated['buyer_contact_id'] ?? null,
                'device_id'        => $validated['model_id'],
                'location_id'      => $validated['location_id'],
                'booking_start'    => $booking_start->toDateTimeString(),
                'booking_end'      => $booking_end->toDateTimeString(),
                'service_type_id'  => $service_type_id,
                'booking_status'   => 'booked',
                'booking_note'     => $validated['booking_note'] ?? null,
                'is_callback'      => 0,
                'call_back_ref'    => null,
                'job_estimator_id' => null,
                'created_at'       => Carbon::now(),
                'updated_at'       => Carbon::now(),
            ]);

            // Reload as Eloquent model for relations if needed
            $booking = Booking::find($booking_id);

            // 2) Create Job Sheet (similar to JobSheetController@store)
            $status = DB::table('repair_statuses')->first();

            // Determine which contact to use for transaction/jobsheet based on user selection
            // Default is 'seller' (contact_id), but if 'buyer' is selected, use buyer_contact_id
            $transaction_contact_type = $validated['transaction_contact_type'] ?? 'seller';
            $transaction_contact_id = $validated['contact_id']; // Default to seller
            
            if ($transaction_contact_type === 'buyer' && !empty($validated['buyer_contact_id'])) {
                $transaction_contact_id = $validated['buyer_contact_id'];
            }

            $jobSheetData = [
                'booking_id'   => $booking->id,
                'contact_id'   => $transaction_contact_id,
                'location_id'  => $booking->location_id,
                'entry_date'   => $booking->booking_start,
                'status_id'    => $status ? $status->id : null,
                'created_by'   => $user_id,
                'business_id'  => $business_id,
            ];

            // Generate job sheet reference
            $ref_count = $this->commonUtil->setAndGetReferenceCount('job_sheet', $business_id);
            $business  = DB::table('business')->where('id', $business_id)->first();
            $repair_settings = $business && $business->repair_settings
                ? json_decode($business->repair_settings, true)
                : [];
            $job_sheet_prefix = $repair_settings['job_sheet_prefix'] ?? '';

            $jobSheetData['job_sheet_no'] = $this->commonUtil->generateReferenceNumber('job_sheet', $ref_count, null, $job_sheet_prefix);

            $job_sheet = JobSheet::create($jobSheetData);

            // Attach default job sheet image if none is provided (jobsheet_def.png)
            // File is at public/uploads/uploads/jobsheet_def.png
            // Store without leading path so display_url generates correct URL
            Media::create([
                'business_id' => $business_id,
                'file_name'   => 'jobsheet_def.png',
                'uploaded_by' => $user_id,
                'model_id'    => $job_sheet->id,
                'model_type'  => JobSheet::class,
            ]);

            // Resolve the base price that will be used everywhere (transaction totals
            // + product_joborder + sell line). Prefer explicit service_price; if not
            // provided, fall back to the default variation sell price; finally 0.0.
            $basePrice = null;

            // Look up the configured CheckCar service product once
            $setting = CheckCarServiceSetting::forBusiness($business_id)
                ->where('checkcar_service_settings.type', 'service')
                ->with('product')
                ->first();
            $serviceProduct = $setting && $setting->product ? $setting->product : null;

            if (!empty($validated['service_price'])) {
                $basePrice = (float)$validated['service_price'];
            } elseif ($serviceProduct) {
                $variation = DB::table('variations')
                    ->where('product_id', $serviceProduct->id)
                    ->orderBy('id')
                    ->first();
                $basePrice = $variation ? (float)$variation->default_sell_price : 0.0;
            } else {
                $basePrice = 0.0;
            }

            // 3) Create repair transaction (sell) using resolved base price
            // Use the selected transaction contact (seller or buyer based on radio selection)
            $transaction_input = [
                'location_id'        => $booking->location_id,
                'status'             => 'under processing',
                'type'               => 'sell',
                'total_before_tax'   => $basePrice,
                'tax'                => 0,
                'final_total'        => $basePrice,
                'payment_status'     => 'due',
                'contact_id'         => $transaction_contact_id,
                'transaction_date'   => Carbon::now(),
                'discount_amount'    => 0,
                'sub_type'           => 'repair',
                'repair_brand_id'    => $device->device_id ?? null,
                'repair_status_id'   => $job_sheet->status_id ?? null,
                'repair_model_id'    => $device->models_id ?? null,
                'repair_job_sheet_id'=> $job_sheet->id,
            ];

            $transaction = $this->transactionUtil->createSellTransaction(
                $business_id,
                $transaction_input,
                ['total_before_tax' => $basePrice, 'tax' => 0],
                $user_id
            );

            // Ensure payment_status is persisted as 'due' on the created transaction
            // without modifying shared TransactionUtil logic.
            if ($transaction instanceof Transaction) {
                $transaction->payment_status = 'due';
                $transaction->save();
            }

            // 4) Attach selected CheckCar product as product_joborder + sell line (type = service)
            if ($serviceProduct) {
                // Create product_joborder row
                ProductJobOrder::create([
                    'job_order_id'      => $job_sheet->id,
                    'product_id'        => $serviceProduct->id,
                    'quantity'          => 1,
                    'price'             => $basePrice,
                    'delivered_status'  => 1,
                    'out_for_deliver'   => 1,
                    'client_approval'   => 1,
                    'product_status'    => 'black',
                ]);

                // Create a sell line directly for this service
                $variation = DB::table('variations')
                    ->where('product_id', $serviceProduct->id)
                    ->orderBy('id')
                    ->first();

                if ($variation) {
                    $products = [[
                        'product_id'           => $serviceProduct->id,
                        'variation_id'         => $variation->id,
                        'quantity'             => 1,
                        'unit_price'           => $basePrice,
                        'unit_price_inc_tax'   => $basePrice,
                        'line_discount_type'   => null,
                        'line_discount_amount' => 0,
                        'item_tax'             => 0,
                        'tax_id'               => null,
                        'sell_line_note'       => 'CheckCar Buy & Sell Inspection',
                        'sub_unit_id'          => null,
                        'discount_id'          => null,
                        'res_service_staff_id' => null,
                    ]];

                    $this->transactionUtil->createOrUpdateSellLines($transaction, $products, $booking->location_id, false, null, [], false);
                }
            }

            // 5) Create CheckCar inspection linked to this booking
            // Use buyer_contact_id from booking if present, otherwise fall back to main contact
            $buyerContact = null;
            if (!empty($booking->buyer_contact_id)) {
                $buyerContact = DB::table('contacts')->where('id', $booking->buyer_contact_id)->first();
            }

          

            $inspectionData = [
                'booking_id'         => $booking->id,
                'job_sheet_id'       => $job_sheet->id,
                'buyer_contact_id'   => $buyerContact->id ?? null,
                'seller_contact_id'  => $contact->id,
                'contact_device_id'  => $device->id,
                'verification_required' => $validated['verification_required'] ?? true,
                'inspection_team'    => [],
                'status'             => 'draft',
                'created_by'         => $user_id,
                'location_id'        => $booking->location_id,
            ];

            $inspection = CarInspection::create($inspectionData);

            if ($request->has('send_notification') && $request->boolean('send_notification')) {
                $location_name = DB::table('business_locations')
                    ->where('id', $validated['location_id'])
                    ->value('name');

                // Seller (main contact)
                $seller_contact = DB::table('contacts')
                    ->where('id', $contact->id)
                    ->select('id', 'mobile', 'name')
                    ->first();

                if ($seller_contact && $seller_contact->mobile) {
                    $message = 'اهلا ا/' . $seller_contact->name . ' لقد تم الحجز بنجاح في يوم ' . $booking_start->toDateTimeString() . ' في ' . $location_name;
                    $smsSent = SmsUtil::sendEpusheg($seller_contact->mobile, $message);

                    SmsLog::create([
                        // sms_message_id is omitted so the DB default is used
                        'contact_id'      => $seller_contact->id,
                        'transaction_id'  => null,
                        'job_sheet_id'    => $job_sheet->id,
                        'mobile'          => $seller_contact->mobile,
                        'message_content' => $message,
                        'status'          => $smsSent ? 'sent' : 'failed',
                        'error_message'   => $smsSent ? null : 'Failed to send SMS',
                        'provider_balance'=> SmsUtil::getLastNetBalance(),
                        'sent_at'         => $smsSent ? now() : null,
                    ]);
                }

                // Buyer (if present)
                if (!empty($booking->buyer_contact_id)) {
                    $buyer_sms_contact = DB::table('contacts')
                        ->where('id', $booking->buyer_contact_id)
                        ->select('id', 'mobile', 'name')
                        ->first();

                    if ($buyer_sms_contact && $buyer_sms_contact->mobile) {
                        $message = 'اهلا ا/' . $buyer_sms_contact->name . ' لقد تم الحجز بنجاح في يوم ' . $booking_start->toDateTimeString() . ' في ' . $location_name;
                        $smsSent = SmsUtil::sendEpusheg($buyer_sms_contact->mobile, $message);

                        SmsLog::create([
                            // sms_message_id is omitted so the DB default is used
                            'contact_id'      => $buyer_sms_contact->id,
                            'transaction_id'  => null,
                            'job_sheet_id'    => $job_sheet->id,
                            'mobile'          => $buyer_sms_contact->mobile,
                            'message_content' => $message,
                            'status'          => $smsSent ? 'sent' : 'failed',
                            'error_message'   => $smsSent ? null : 'Failed to send SMS',
                            'provider_balance'=> SmsUtil::getLastNetBalance(),
                            'sent_at'         => $smsSent ? now() : null,
                        ]);
                    }
                }
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'msg' => __('checkcar::lang.saved_successfully'),
                    'inspection_id' => $inspection->id,
                ]);
            }

            return redirect()->route('booking.index')->with('success', __('checkcar::lang.saved_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error in BuySellBookingController@store', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ], 500);
            }

            return back()->withErrors(__('messages.something_went_wrong'));
        }
    }
}
