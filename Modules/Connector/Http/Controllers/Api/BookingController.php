<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Category;
use App\Http\Controllers\Restaurant\BookingController as RestaurantBookingController;
use App\Media;
use App\User;
use App\Contact;
use App\Business;
use App\BusinessLocation;
use App\Transaction;
use App\TransactionPayment;
use App\Restaurant\JobEstimator;
use App\Utils\SmsUtil;
use Modules\Sms\Entities\SmsLog;
use Modules\Repair\Entities\MaintenanceNote;

use App\Restaurant\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Connector\Transformers\BookingResource;
use Modules\Repair\Entities\ContactDevice;
use App\TransactionSellLine;
use App\VariationLocationDetails;
use App\Utils\StockSyncUtil;


/**
 * @group Brand management
 * @authenticated
 *
 * APIs for managing brands
 */

class BookingController extends ApiController
{

    protected $perm;
    private $commonUtil;

    public function __construct(
        RestaurantBookingController $perm,
        \App\Utils\Util $commonUtil
    ) {
        $this->perm = $perm;
        $this->commonUtil = $commonUtil;
    }

    private function generateEstimateNo(): string
    {
        $business_id = Auth::user()->business_id;
        $ref_count = DB::table('reference_counts')
            ->where('ref_type', 'job_estimator')
            ->where('business_id', $business_id)
            ->value('ref_count') ?? 0;
        
        $ref_count += 1;
        
        DB::table('reference_counts')
            ->updateOrInsert(
                ['ref_type' => 'job_estimator', 'business_id' => $business_id],
                ['ref_count' => $ref_count]
            );
        
        $estimate_prefix = 'ES';
        return $estimate_prefix . '-' . str_pad($ref_count, 6, '0', STR_PAD_LEFT);
    }

    private function createEstimatorAdvancePayment(int $contactId, float $amount, JobEstimator $estimator, int $locationId): void
    {
        $businessId = Auth::user()->business_id;
        $userId = Auth::id();

        $paymentRef = 'ADV-' . $businessId . '-' . strtoupper(Str::random(6));

        $draftTransaction = Transaction::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'contact_id' => $contactId,
            'type' => 'sell',
            'status' => 'draft',
            'payment_status' => 'due',
            'transaction_date' => now(),
            'created_by' => $userId,
            'total_before_tax' => $amount,
            'tax_amount' => 0,
            'final_total' => $amount,
            'ref_no' => $paymentRef,
        ]);

        $payment = TransactionPayment::create([
            'transaction_id' => $draftTransaction->id,
            'amount' => $amount,
            'method' => 'advance',
            'payment_for' => $contactId,
            'is_advance' => 1,
            'payment_ref_no' => $paymentRef,
            'note' => 'Estimator #' . ($estimator->estimate_no ?? $estimator->id),
            'paid_on' => null,
            'created_by' => $userId,
            'business_id' => $businessId,
            'payment_type' => null,
            'status' => 'due',
        ]);

        Contact::where('id', $contactId)->increment('balance', $amount);
    }



    public function index()
    {

        $user = Auth::user();
        $business_id = $user->business_id;


        // Start the bookings query
        $bookings = DB::table('bookings')
            ->leftJoin('contacts', 'contacts.id', '=', 'bookings.contact_id')
            ->leftJoin('business_locations', 'business_locations.id', '=', 'bookings.location_id')
            ->leftJoin('types_of_services', 'types_of_services.id', '=', 'bookings.service_type_id')
            ->leftJoin('contact_device', 'contact_device.id', '=', 'bookings.device_id')
            ->leftJoin('categories', 'categories.id', '=', 'contact_device.device_id')
            ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->select(
                'bookings.*',
                'contacts.name as contact_name',
                'business_locations.name as location_name',
                'types_of_services.name as services_type',
                'contact_device.chassis_number as car_chassis_number',
                'contact_device.color as car_color',
                'contact_device.car_type as car_type',
                'contact_device.plate_number as car_plate_number',
                'contact_device.manufacturing_year as manufacturing_year',
                'contact_device.motor_cc as motor_cc',
                'repair_device_models.name as car_model',
                'repair_device_models.id as car_model_id',
                'categories.name as car_brand'
            )

            ->where('bookings.location_id', $user->location_id)
            ->where('bookings.booking_status', 'waiting');

      

        // Execute the query
        $bookings = $bookings->get();

        return BookingResource::collection($bookings);
    }


    // Create a new booking
    public function store(Request $request)
    {
        $request->validate([
            'booking_name' => 'required|string',
            'contact_id' => 'required|integer',
            'location_id' => 'required|integer',
            'booking_start' => 'required|date',
            'booking_end' => 'required|date',
            'device_id' => 'required|integer',
        ]);

        $booking = Booking::create($request->all());
        return response()->json($booking, 201);
    }

    // Get a single booking
    public function show($id)
    {
        $booking = Booking::findOrFail($id);
        return response()->json($booking);
    }

    // Update a booking
    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update($request->all());
        return response()->json($booking);
    }

    // Delete a booking
    public function destroy($id)
    {
        Booking::destroy($id);
        return response()->json(null, 204);
    }

   public function customerBooking(Request $request)
{
    $validated = $request->validate([
        'device_id'        => 'required|integer|exists:contact_device,id',
        'location_id'      => 'required|integer',
        'booking_start'    => 'required|date',
        'booking_note'     => 'nullable|string|max:1000',
        'service_id'       => 'required|integer',
        'send_notification'=> 'nullable|boolean',
    ]);

    $user = Auth::user();
    $admins = $this->commonUtil->get_admins($user->business_id??1);
    $admin_ids = $admins->pluck('id')->toArray();
    Log::info('Admin IDs for notification', ['admin_ids' => $admin_ids]);       
    $user_id = $user->crm_contact_id;
    $business_id = $user->business_id;
    $send_notification = $validated['send_notification'] ?? false;

    $idempotency_key = md5($user_id.'-'.$validated['device_id'].'-'.$validated['booking_start']);
    $existingBooking = DB::table('bookings')
        ->where('contact_id', $user_id)
        ->where('device_id', $validated['device_id'])
        ->where('booking_start', $validated['booking_start'])
        ->where('created_at', '>=', now()->subMinutes(5))
        ->first();

    if ($existingBooking) {
        return response()->json([
            'success' => 1,
            'msg'     => trans('lang_v1.added_success'),
            'data'    => 'booking successfully',
            'message' => 'Duplicate request detected, booking already created',
            'send_notification' => $send_notification,
            'idempotency_key'   => $idempotency_key,
        ], 200);
    }

    $contact = DB::table('contacts')
        ->where('id', $user_id)
        ->select('name','mobile')
        ->first();

    if (!$contact) {
        return response()->json(['error' => 'Contact not found'], 404);
    }

    $device = DB::table('contact_device')
        ->join('repair_device_models','repair_device_models.id','=','contact_device.models_id')
        ->where('contact_device.id',$validated['device_id'])
        ->select('repair_device_models.name')
        ->first();

    if (!$device) {
        return response()->json(['error' => 'Device not found'], 404);
    }

    $booking_name = $contact->name.' - '.$device->name.' - '.$validated['booking_start'];

    $booking = Booking::create([
        'booking_start'   => $validated['booking_start'],
        'location_id'     => $validated['location_id'],
        'booking_name'    => $booking_name,
        'business_id'     => $business_id,
        'contact_id'      => $user_id,
        'device_id'       => $validated['device_id'],
        'booking_note'    => $validated['booking_note'] ?? null,
        'booking_status'  => 'request',
        'service_type_id' => $validated['service_id'],
    ]);

    $booking_id = $booking->id;

    $output = [
        'success' => 1,
        'msg'     => trans('lang_v1.added_success'),
        'data'    => 'booking successfully',
    ];

 
    if ($send_notification && !empty($contact->mobile)) {
        $location_name = DB::table('business_locations')
            ->where('id',$validated['location_id'])
            ->value('name');

        $booking_start_str = Carbon::parse($validated['booking_start'])->toDateTimeString();
        $message = 'اهلا '.$contact->name.' لقد تم الحجز بنجاح في يوم '.$booking_start_str.' في '.$location_name;

        try {
            $smsSent = SmsUtil::sendEpusheg($contact->mobile,$message);

            SmsLog::create([
                'contact_id'       => $user_id,
                'mobile'           => $contact->mobile,
                'message_content'  => $message,
                'status'           => (is_array($smsSent) && $smsSent['success']) ? 'sent' : 'failed',
                'error_message'    => (is_array($smsSent) && $smsSent['success']) ? null : 'Failed to send SMS',
                'provider_balance' => is_array($smsSent) ? $smsSent['balance'] : SmsUtil::getLastNetBalance(),
                'sent_at'          => (is_array($smsSent) && $smsSent['success']) ? now() : null,
            ]);

            $output['send_notification'] = 1;
        } catch (\Exception $e) {
            Log::error('SMS failed', ['e'=>$e->getMessage()]);
        }
    }

    $location_name = $location_name ?? '';

    $booking_details = [
        'booking_id'      => $booking_id,
        'booking_name'    => $booking_name,
        'contact_name'    => $contact->name,
        'contact_mobile'  => $contact->mobile,
        'device_name'     => $device->name,
        'booking_start'   => $validated['booking_start'],
        'location_name'   => $location_name,
        'location_id'     => $validated['location_id'],
        'service_id'      => $validated['service_id'],
        'note'            => $validated['booking_note'] ?? null,
        'booking_status'  => 'request',
        'created_at'      => now()->toDateTimeString(),
        'updated_at'      => now()->toDateTimeString(),
        'send_notification'=> $send_notification ? 1 : 0,
    ];

  
    $flattenedBooking = [];
    foreach ($booking_details as $k => $v) {
        $flattenedBooking[$k] = is_scalar($v) ? $v : json_encode($v);
    }

  $fcmData = [
    'message' => [
        'notification' => [
            'title' => 'Booking Added: ' . $booking_name,
            'title_ar' => 'تمت إضافة حجز: ' . $booking_name,
            'body'  => 'Booking by ' . $contact->name . ' for ' . $device->name,
            'body_ar' => 'حجز بواسطة ' . $contact->name . ' للجهاز ' . $device->name,
        ],
        'data' => array_map('strval', $flattenedBooking),
    ],
];
   
    $response = response()->json($output, 200);


    if ( !empty($admin_ids)) {
        dispatch(new \App\Jobs\SendNotifications(
            $admin_ids,
            $flattenedBooking,
            'App\Notifications\BookingNotification',
            $fcmData
        ));
    }

    return $response;
}



    public function customerPickupRequest(Request $request)
    {
        $user = Auth::user();
        $contactId = $user->crm_contact_id;
        $business_id = $user->business_id;

        if (empty($contactId)) {
            return response()->json([
                'success' => false,
                'message' => 'Contact profile not found for authenticated user.',
            ], 404);
        }

        $validated = $request->validate([
            'device_id'     => 'required|integer',
            'location_id'   => 'required|integer',
            'service_id'    => 'required|integer',
            'booking_start' => 'required|date',
            'pickup_latitude'      => 'required|numeric',
            'pickup_longitude'     => 'required|numeric',
            'booking_note'  => 'nullable|string',
        ]);

        // Fetch contact name
        $contact = DB::table('contacts')
            ->where('id', $contactId)
            ->select('name')
            ->first();

        if (! $contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found.',
            ], 404);
        }

        // Fetch device/model name for booking title
        $device = DB::table('contact_device')
            ->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->where('contact_device.id', $validated['device_id'])
            ->select('repair_device_models.name')
            ->first();

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found for this customer.',
            ], 404);
        }

        $booking_name = $contact->name . ' - ' . $device->name . ' - ' . $validated['booking_start'];

        // Combine pickup coordinates + optional note into booking_note text
        $noteParts = [];
        $noteParts[] = 'Pickup location: ' . $validated['latitude'] . ',' . $validated['longitude'];
        if (!empty($validated['booking_note'])) {
            $noteParts[] = $validated['booking_note'];
        }
        $bookingNote = implode(' | ', $noteParts);

        DB::table('bookings')->insert([
            'booking_start'     => $validated['booking_start'],
            'location_id'       => $validated['location_id'],
            'booking_name'      => $booking_name,
            'business_id'       => $business_id,
            'contact_id'        => $contactId,
            'device_id'         => $validated['device_id'],
            'booking_note'      => $bookingNote,
            'pickup_latitude'   => $validated['pickup_latitude'],
            'pickup_longitude'  => $validated['pickup_longitude'],
            'updated_at'        => now(),
            'booking_status'    => 'pickup_request',
            'service_type_id'   => $validated['service_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pickup booking created successfully.',
        ]);
    }

    public function getService(Request $request)
    {
        $user = Auth::user();
        $location_id = $request->input('location_id');
        if (!$location_id) {
            $location_id = $user->location_id;
        }

        // location_price_group is stored as text JSON like {"1":0,"2":0}
        // Match services that include the current user's location id as a key
        $needle = '"' . (string) $location_id . '":';
        $services = DB::table('types_of_services')
            ->select('name', 'id')
            ->where('location_price_group', 'like', '%' . $needle . '%')
            ->where(function ($q) {
                $q->whereNull('is_inspection_service')
                  ->orWhere('is_inspection_service', 0);
            })
            ->get();

        return response()->json($services);
    }

    public function getBrand()
    {
        $user = Auth::user();
        $business_id = $user->business_id;
        $categories = Category::forDropdown($business_id, 'device');

        $categoriesArray = $categories->toArray();
        $brands = array_map(function ($id, $name) {
            return [
                'id' => $id,
                'name' => ucfirst($name)
            ];
        }, array_keys($categoriesArray), $categoriesArray);

        return response()->json($brands);
    }

    // public function getModels($id)
    // {
    //            // Fetch the models related to the brand
    //     $models = DB::table('repair_device_models')
    //         ->where('device_id', $id)
    //         ->select('id', 'name')
    //         ->orderBy('name')
    //         ->get();

      

    //     return response()->json(
    //         $models,
        
    //     );
    // }

    public function getModels($id)
    {
        return $this->perm->getModelsByBrand($id);
    }

    public function customerAddCar(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->crm_contact_id;
        // $business_id = request()->session()->get('user.business_id');
        // $user_id = request()->session()->get('user.id');

        $requiredFields = [
            'brand_id',
            'model_id',
            'color',
            'plate_number',
            'manufacturing_year',
            'car_type',
        ];

        $input = $request->input();
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                return response()->json("Missing required field: $field");
            }
        }

        $modelBelongsToBrand = DB::table('repair_device_models')
            ->where('id', $input['model_id'])
            ->where('device_id', $input['brand_id'])
            ->exists();

        if (!$modelBelongsToBrand) {
            return response()->json([
                'message' => 'The selected model does not belong to the selected brand.',
            ], 422);
        }

        $device_id = DB::table('contact_device')->insertGetId([
            'device_id' => $input['brand_id'],
            'models_id' => $input['model_id'],
            'color' => $input['color'],
            'chassis_number' => $input['chassis_number'] ?? null,
            'plate_number' => $input['plate_number'],
            'manufacturing_year' => $input['manufacturing_year'],
            'car_type' => $input['car_type'],
            'contact_id' => $user_id,
            'brand_origin_variant_id' => $input['brand_origin_variant_id'] ?? null,
            'motor_cc' => $input['motor_cc'] ?? null,
        ]);
        return response()->json(["data" => "Adding Car successfully"]);
    }

    public function getBranch()
    {
        $bransh = DB::table('business_locations')->where('is_active', 1)->select('name', 'id')->get();
        return response()->json($bransh);
    }

    public function getInfo()
    {
        $user = Auth::user();
        $user_id = $user->id;

        $data = DB::table('users')
            ->join('contacts', 'contacts.id', '=', 'users.crm_contact_id')

            ->join('contact_device', 'contact_device.contact_id', '=', 'users.crm_contact_id')
            ->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->join('categories', 'categories.id', '=', 'contact_device.device_id')
            ->where('users.id', $user_id)
            ->select(
                'contacts.name',
                'contact_device.id',
                'users.first_name',
                'users.last_name',
                'repair_device_models.name AS model',
                'categories.name AS device'
            )->latest('contact_device.id')
            ->first();

        return response()->json($data);
    }

    public function getInfoCustomer()
    {
        $user = Auth::user();
        $contactId = $user->crm_contact_id;

        if (empty($contactId)) {
            return response()->json([
                'success' => false,
                'message' => 'Contact profile not found for authenticated user.'
            ], 404);
        }

        $cars = DB::table('contact_device')
            ->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->join('categories', 'categories.id', '=', 'contact_device.device_id')
            ->where('contact_device.contact_id', $contactId)
            ->select(
                'contact_device.id',
                'repair_device_models.name AS model',
                'categories.name AS device',
                DB::raw('CASE WHEN categories.logo IS NOT NULL THEN CONCAT("' . asset('storage/') . '", "/", categories.logo) ELSE NULL END AS car_logo'),
                'contact_device.color',
                'contact_device.plate_number',
                'contact_device.manufacturing_year',
                'contact_device.chassis_number',
                'contact_device.car_type',
                'contact_device.motor_cc',
            )->get();

        $contact = DB::table('contacts')
            ->where('id', $contactId)
            ->select('id', 'name', 'mobile')
            ->first();

        if (! $contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found.'
            ], 404);
        }

        return response()->json(["data" => [
            "id" => $contact->id,
            "name" => $contact->name,
            "mobile" => $contact->mobile ?? $user->username,
            "cars" => $cars
        ]]);
    }

    public function getBookingCustomer()
    {
        $user = Auth::user();
        $user_id = $user->crm_contact_id;

        $bookings = DB::table('bookings')
            ->leftJoin('repair_job_sheets', 'repair_job_sheets.booking_id', '=', 'bookings.id')
            ->leftJoin('contact_device', 'contact_device.id', '=', 'bookings.device_id')
            ->leftJoin('categories', 'categories.id', '=', 'contact_device.device_id')
            ->leftJoin('types_of_services', 'types_of_services.id', '=', 'bookings.service_type_id')
            ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->leftJoin('business_locations', 'business_locations.id', '=', 'repair_job_sheets.location_id')
            ->where('bookings.contact_id', $user_id)
            ->select(
                'repair_job_sheets.job_sheet_no',
                'bookings.booking_status',
                'contact_device.color',
                'contact_device.plate_number',
                'categories.name AS brand',
                'repair_device_models.name AS model',
                'types_of_services.name AS service',
                'bookings.booking_note',
                'bookings.id',
                'bookings.booking_start',
                'business_locations.name AS location',
            )
            ->orderBy('bookings.booking_start', 'desc') // Order by booking_start (ascending)
            ->get();
        return response()->json($bookings);
    }

    public function getJoborderCustomer()
    {
        $user = Auth::user();
        $user_id = $user->crm_contact_id;

        $joborder = DB::table('repair_job_sheets')
            ->leftJoin('workshops', 'workshops.id', '=', 'repair_job_sheets.workshop_id')
            ->leftJoin('business_locations', 'business_locations.id', '=', 'repair_job_sheets.location_id')
            ->leftJoin('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->leftJoin('contact_device', 'contact_device.id', '=', 'bookings.device_id')
            ->leftJoin('categories', 'categories.id', '=', 'contact_device.device_id')
            ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->where('repair_job_sheets.contact_id', $user_id)
            ->select(
                'repair_device_models.name AS model',
                'repair_job_sheets.job_sheet_no',
                'repair_job_sheets.id',
                'categories.name AS brand',
                'contact_device.color',
                'contact_device.plate_number',
                'contact_device.manufacturing_year',
                'workshops.name AS workshop',
                'business_locations.name AS location'
            )
            ->get();
        return response()->json($joborder);
    }

    // List job estimators ordered by status and date
    public function getJobEstimators(Request $request)
    {
        $request->validate([
            'customerId' => 'required|integer',
        ]);

        $contact_id = $request->input('customerId');
  
        $status = $request->query('status');

        $estimators = DB::table('job_estimator')
            ->leftJoin('contacts', 'contacts.id', '=', 'job_estimator.contact_id')
            ->leftJoin('business_locations', 'business_locations.id', '=', 'job_estimator.location_id')
            ->leftJoin('contact_device', 'contact_device.id', '=', 'job_estimator.device_id')
            ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->leftJoin('categories', 'categories.id', '=', 'contact_device.device_id')
            ->select(
                'job_estimator.id',
                'job_estimator.estimate_no',
                'job_estimator.contact_id',
                'contacts.name as customer_name',
                'job_estimator.device_id',
                'repair_device_models.name AS model',
                'categories.name AS brand',
                'job_estimator.business_id',
                'job_estimator.location_id',
                'business_locations.name as location_name',
                'job_estimator.created_by',
                'job_estimator.service_type_id',
                'job_estimator.estimator_status',
                 'contact_device.color',
                'contact_device.plate_number',
                'contact_device.manufacturing_year',
                
                'job_estimator.vehicle_details',
           
               
                'job_estimator.send_sms',
          
                'job_estimator.sent_to_customer_at',
                'job_estimator.approved_at',
                'job_estimator.created_at',
                'job_estimator.updated_at'
            )
            ->where('job_estimator.contact_id', $contact_id)
            ->where('job_estimator.estimator_status', 'pending');


        // Optional status filter
        if (!empty($status)) {
            $estimators->where('job_estimator.estimator_status', $status);
        }

        // Order by status and date (expected_delivery_date desc, nulls last), then created_at desc
        $estimators = $estimators
            ->orderBy('job_estimator.estimator_status')
            ->orderBy('job_estimator.created_at', 'DESC')
            ->get();

        return response()->json($estimators);
    }
    

    public function createJobEstimator(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|integer',
            'device_id' => 'required|integer',
            'location_id' => 'required|integer',
            'service_type_id' => 'nullable|integer',
            'vehicle_details' => 'nullable|string|max:1000',
            'amount' => 'nullable|numeric|min:0',
            'send_notification_value' => 'nullable|in:0,1',
        ]);

        try {
            $business_id = Auth::user()->business_id;
            $user_id = Auth::user()->id;

            $data = [
                'contact_id' => $validated['contact_id'],
                'device_id' => $validated['device_id'],
                'business_id' => $business_id,
                'location_id' => $validated['location_id'],
                'created_by' => $user_id,
                'service_type_id' => $validated['service_type_id'] ?? null,
                'vehicle_details' => $validated['vehicle_details'] ?? null,
                'amount' => $validated['amount'] ?? null,
                'send_sms' => isset($validated['send_notification_value']) && $validated['send_notification_value'] == 1,
                'estimator_status' => 'pending',
            ];

            $data['estimate_no'] = $this->generateEstimateNo();

            $estimator = JobEstimator::create($data);

            $amount = isset($validated['amount']) ? (float) $validated['amount'] : 0.0;
            if ($amount > 0) {
                try {
                    $this->createEstimatorAdvancePayment(
                        $validated['contact_id'],
                        $amount,
                        $estimator,
                        $validated['location_id']
                    );
                } catch (\Throwable $txEx) {
                    Log::warning('Failed to record estimator advance payment', [
                        'estimator_id' => $estimator->id,
                        'contact_id' => $validated['contact_id'],
                        'error' => $txEx->getMessage(),
                    ]);
                }
            }

            try {
                MaintenanceNote::updateOrCreate(
                    [
                        'job_estimator_id' => $estimator->id,
                        'category_status' => 'purchase_req',
                    ],
                    [
                        'job_sheet_id' => null,
                        'created_by' => $user_id,
                        'device_id' => $validated['device_id'],
                        'status' => 'awaiting_reply',
                        'content' => $validated['vehicle_details'] ?? null,
                    ]
                );
            } catch (\Throwable $ex) {
                Log::warning('Failed to create maintenance note on estimator create', [
                    'estimator_id' => $estimator->id,
                    'error' => $ex->getMessage(),
                ]);
            }

            if ($validated['send_notification_value'] == 1) {
                $contact = DB::table('contacts')->where('id', $validated['contact_id'])->select('id', 'mobile', 'name')->first();
                if ($contact && $contact->mobile) {
                    $location_name = DB::table('business_locations')->where('id', $validated['location_id'])->value('name');
                    $message = 'اهلا ا/' . $contact->name . ' تم ارسال مقايسة صيانة لمركبتك من ' . $location_name;
                    
                    $smsResult = SmsUtil::sendEpusheg($contact->mobile, $message);
                    $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;
                    
                    SmsLog::create([
                        'contact_id' => $contact->id,
                        'transaction_id' => null,
                        'job_sheet_id' => null,
                        'mobile' => $contact->mobile,
                        'message_content' => $message,
                        'status' => $smsSent ? 'sent' : 'failed',
                        'error_message' => $smsSent ? null : 'Failed to send SMS',
                        'provider_balance' => is_array($smsResult) ? $smsResult['balance'] : SmsUtil::getLastNetBalance(),
                        'sent_at' => $smsSent ? now() : null,
                    ]);
                    
                    $estimator->sent_to_customer_at = now();
                    $estimator->save();
                }
            }

            return response()->json([
                'success' => true,
                'id' => $estimator->id,
                'estimate_no' => $estimator->estimate_no,
                'send_notification' => $validated['send_notification_value'] == 1,
            ], 201);
        } catch (\Exception $e) {
            Log::error("Error in create job estimator: {$e->getMessage()}", [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'msg' => 'Something went wrong'], 500);
        }
    }
    

    // Fetch single job estimator by id with related product_joborder lines (modal-like payload)
    public function getJobEstimatorDetails(Request $request)
    {
        $id = (int) $request->query('id');
        if (! $id) {
            return response()->json(['success' => false, 'message' => 'Missing required query param: id'], 400);
        }

        // Get 4-digit phone number from request and fetch contact
        $phoneLastFourDigits = $request->query('phone');
        if (! $phoneLastFourDigits) {
            return response()->json(['success' => false, 'message' => 'Missing required query param: phone (last 4 digits)'], 400);
        }

        // Find contact by matching last 4 digits of mobile number
        $contact = DB::table('contacts')
            ->whereRaw('SUBSTRING(mobile, -4) = ?', [$phoneLastFourDigits])
            ->first();

        if (! $contact) {
            return response()->json(['success' => false, 'message' => 'No contact found matching the phone number'], 404);
        }

        $contactId = $contact->id;

        $estimator = DB::table('job_estimator')
            ->leftJoin('contacts', 'contacts.id', '=', 'job_estimator.contact_id')
            ->leftJoin('business_locations', 'business_locations.id', '=', 'job_estimator.location_id')
            ->leftJoin('contact_device', 'contact_device.id', '=', 'job_estimator.device_id')
            ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->leftJoin('categories', 'categories.id', '=', 'contact_device.device_id')
            ->leftJoin('types_of_services', 'types_of_services.id', '=', 'job_estimator.service_type_id')
        
            ->where('job_estimator.id', $id)
            ->where('job_estimator.contact_id', $contactId)
            ->select([
                'job_estimator.id',
                'job_estimator.estimate_no',
                'job_estimator.contact_id',
                'contacts.name as customer_name',
                'job_estimator.device_id',
                'repair_device_models.name AS model',
                'categories.name AS brand',
                'job_estimator.business_id',
                'job_estimator.location_id',
                'business_locations.name as location_name',
                'job_estimator.created_by',
                'job_estimator.service_type_id',
                'types_of_services.name as service_name',
                'job_estimator.estimator_status',
                'contact_device.color',
                'contact_device.plate_number',
                'contact_device.manufacturing_year',
                'contact_device.chassis_number',
                'job_estimator.vehicle_details',
         
            
                'job_estimator.send_sms',
           
                'job_estimator.sent_to_customer_at',
                'job_estimator.approved_at',
                'job_estimator.created_at',
                'job_estimator.updated_at',
                'job_estimator.amount'
            ])
            ->first();

        if (! $estimator) {
            return response()->json(['success' => false, 'message' => 'Estimator not found'], 404);
        }

        // Derived display helpers similar to modal in MaintenanceNoteController
        $vehicleParts = array_filter([
            $estimator->brand ?? null,
            isset($estimator->model) && $estimator->model ? '(' . $estimator->model . ')' : null,
            $estimator->manufacturing_year ?? null,
        ]);
        $vehicle_display = !empty($vehicleParts) ? trim(implode(' ', $vehicleParts)) : __('repair::lang.not_applicable');
        $vin_display = $estimator->chassis_number ?: __('repair::lang.not_applicable');

        // Lines for this estimator
        $lines = DB::table('product_joborder as pjo')
            ->leftJoin('products', 'products.id', '=', 'pjo.product_id')
            ->leftJoin('variations', 'variations.product_id', '=', 'products.id')
            ->leftJoin('contacts as suppliers', 'suppliers.id', '=', 'pjo.supplier_id')
            ->leftJoin('purchase_lines', function ($join) {
                $join->on('purchase_lines.product_id', '=', 'pjo.product_id');
            })
            ->where('pjo.job_estimator_id', $id)
            ->select([
                'pjo.id as line_id',
                'pjo.product_id',
                'pjo.quantity',
                'pjo.supplier_id',
                'pjo.client_approval',
                'pjo.product_status',
                'products.name as part_name',
                'products.sku as part_sku',
                DB::raw('COALESCE(NULLIF(pjo.price, 0), NULLIF(MAX(variations.sell_price_inc_tax), 0), 0) as end_user_price'),
                'pjo.Notes as Notes',
            ])
            ->groupBy('pjo.id', 'pjo.product_id', 'pjo.quantity', 'pjo.supplier_id', 'pjo.client_approval', 'products.name', 'products.sku', 'pjo.purchase_price', 'pjo.price', 'suppliers.supplier_business_name', 'suppliers.name', 'pjo.Notes')
            ->get();

        return response()->json([
            'success' => true,
            'estimator' => $estimator,
            'meta' => [
                'vehicle_display' => $vehicle_display,
                'vin_display' => $vin_display,
                'plate_number' => $estimator->plate_number,
                'color' => $estimator->color,
                'car_type' => $estimator->car_type ?? null,
            ],
            'lines' => $lines,
        ]);
    }

    public function datajoborder($id)
    {
        $dataofcar = DB::table('repair_job_sheets')
            ->leftJoin('workshops', 'workshops.id', '=', 'repair_job_sheets.workshop_id')
            ->leftJoin('business_locations', 'business_locations.id', '=', 'repair_job_sheets.location_id')
            ->leftJoin('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->leftJoin('contact_device', 'contact_device.id', '=', 'bookings.device_id')
            ->leftJoin('types_of_services', 'types_of_services.id', '=', 'bookings.service_type_id')
            ->leftJoin('categories', 'categories.id', '=', 'contact_device.device_id')
            ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->where('repair_job_sheets.id', $id)
            ->select(
                'repair_device_models.name AS model',
                'repair_job_sheets.job_sheet_no',
                'repair_job_sheets.id',
                'repair_job_sheets.status_id',
                'categories.name AS brand',
                'contact_device.color',
                'contact_device.plate_number',
                'types_of_services.name AS service',
                'contact_device.manufacturing_year',
                'workshops.name AS workshop',
                'business_locations.name AS location'
            )
            ->first();
        $job_order = DB::table('product_joborder')->where('job_order_id', $id)->get();
        $date = DB::table('repair_job_sheets')->select('start_date', 'due_date')->where('id', $id)->first();

        $hours = Carbon::parse(Carbon::now()->toDateTimeString())->diffInHours(Carbon::parse($date->due_date));
        $minutes = Carbon::parse(Carbon::now()->toDateTimeString())->diffInMinutes(Carbon::parse($date->due_date));

        if (Carbon::parse($date->due_date)->isPast()) {
            $days = 0;
            $hours = 0;
            $minutes = 0;
            $seconds = 0;
        } else {
            $diff = Carbon::parse(now())->diff(Carbon::parse($date->due_date));
            $days = $diff->days;
            $hours = $diff->h;
            $minutes = $diff->m;
            $seconds = $diff->s;
        }
        return response()->json(
            [
                "days" =>  $days,
                "hours" => $hours,
                "minutes" => $minutes,
                "seconds" => $seconds,
                "job_order" => $job_order,
                "status_id" => $dataofcar->status_id,
                "dataofcar" => $dataofcar
            ]
        );
    }

    public function status()
    {
        $status = DB::table('repair_statuses')->select('name', 'id','icon','color','sort_order')->where('status_category', 'status')->get();
        return response()->json(["status" => $status]);
    }

    public function getProductName($id)
    {
        $productName = DB::table('products')->select('name', 'id')->where('id', $id)->first();
        return response()->json($productName);
    }

    public function saveData(Request $request)
    {
        // Check if product_ids parameter exists
        if (!$request->query('product_ids')) {
            return response()->json(['error' => 'Missing product_ids parameter'], 400);
        }
        $user = Auth::user();

        // Check that either job_order_id or estimator_id is provided, but not both
        $jobOrderId = $request->query('job_order_id');
        $job_estimator_id  = $request->query('estimator_id');

        if (!$jobOrderId && !$job_estimator_id ) {
            return response()->json(['error' => 'Either job_order_id or estimator_id must be provided'], 400);
        }

        if ($jobOrderId && $job_estimator_id ) {
            return response()->json(['error' => 'Cannot provide both job_order_id and estimator_id'], 400);
        }

        $productIds = $request->query('product_ids');

        $used_estimator = false;
        foreach ($productIds as $productId) {
            $productId = (int) $productId;

            // Build query based on which ID type is provided
            $query = DB::table('product_joborder')
                ->where('product_id', $productId);

            if ($jobOrderId) {
                $jobOrderId = (int) $jobOrderId;
                $query->where('job_order_id', $jobOrderId);
                $idType = 'job_order_id';
                $idValue = $jobOrderId;
            } else {
                $job_estimator_id  = (int) $job_estimator_id ;
                $query->where('job_estimator_id', $job_estimator_id );
                $idType = 'job_estimator_id';
                $idValue = $job_estimator_id ;
                $used_estimator = true;
            }

            // Check if row exists
            $exists = $query->exists();

            if (!$exists) {
                return response()->json(['error' => "No matching row for {$idType}: {$idValue} and product_id: {$productId}"]);
            }

            // Perform the update
            $updated = $query->update(['client_approval' => 1]);

            if ($updated === 0) {
                return response()->json(['warning' => "No rows updated for {$idType}: {$idValue} and product_id: {$productId}"]);
            }
        }

        // If updates were for an estimator, mark its status as replied
        if ($used_estimator && isset($job_estimator_id) && $job_estimator_id) {
            DB::table('job_estimator')
                ->where('id', (int)$job_estimator_id)
                ->update(['estimator_status' => 'replied']);
        }

        // Sync sell lines for the job order using StockSyncUtil (same as SparePartsController)
        if ($jobOrderId) {
            try {
                $transaction = Transaction::where('repair_job_sheet_id', (int) $jobOrderId)->first();
                if ($transaction) {
                    DB::transaction(function () use ($jobOrderId, $transaction) {
                        Transaction::where('id', $transaction->id)->lockForUpdate()->first();
                        $this->syncSellLinesForJobOrder((int) $jobOrderId, $transaction, $transaction->status, $transaction->contact_id);
                    });
                }
            } catch (\Exception $e) {
                Log::error('Error syncing sell lines after client approval', [
                    'job_order_id' => $jobOrderId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $adminsAndCashers = $this->commonUtil->get_adminsAndCashers($user->business_id);
        $admin_ids = $adminsAndCashers->pluck('id')->toArray();
        Log::info('Dispatching notifications for client approval', [
            'admin_ids' => $admin_ids,
            'job_order_id' => $jobOrderId ?? null,
            'job_estimator_id' => $job_estimator_id ?? null,
        ]);
        $fcmData = [
            'message' => [
                'notification' => [
                    'title' => 'Client Approval: ' . $jobOrderId,
                    'title_ar' => 'موافقة العميل: ' . $jobOrderId,
                ],
                'data' => [
                    'body' => 'A client has approved products for Job Order ID: ' . $jobOrderId,
                    'body_ar' => 'قام العميل بالموافقة على منتجات أمر الشغل رقم: ' . $jobOrderId,
                ],
            ],
        ];

        if (!empty($admin_ids)) {
            dispatch(new \App\Jobs\SendNotifications(
                $admin_ids,
                $fcmData,
                'approveJopOrder',
                $fcmData
            ));
        }

        return response()->json(['message' => 'Products updated successfully']);
    }

    /**
     * Sync sell lines for a job order using StockSyncUtil directly.
     * Same logic as SparePartsController::syncSellLinesForJobOrder
     */
    private function syncSellLinesForJobOrder(int $job_order_id, Transaction $transaction, string $status, int $contact_id)
    {
        $stockSyncUtil = app(StockSyncUtil::class);
        $locationId = $transaction->location_id;

        $transaction->status = $status;
        $transaction->contact_id = $contact_id;
        $transaction->sub_status = 'repair';
        $transaction->save();

        $spareParts = DB::table('product_joborder')
            ->join('products', 'products.id', '=', 'product_joborder.product_id')
            ->join('variations', 'variations.product_id', '=', 'products.id')
            ->where('product_joborder.job_order_id', $job_order_id)
            ->groupBy(
                'product_joborder.id',
                'product_joborder.product_id',
                'product_joborder.job_order_id',
                'product_joborder.quantity',
                'product_joborder.price',
                'product_joborder.delivered_status',
                'product_joborder.out_for_deliver',
                'product_joborder.client_approval',
                'product_joborder.product_status',
                'products.name',
                'products.enable_stock'
            )
            ->select(
                'product_joborder.id',
                'product_joborder.product_id',
                'product_joborder.job_order_id',
                'product_joborder.quantity',
                'product_joborder.price',
                'product_joborder.delivered_status',
                'product_joborder.out_for_deliver',
                'product_joborder.client_approval',
                'product_joborder.product_status',
                'products.name as product_name',
                'products.enable_stock',
                DB::raw('MIN(variations.id) as variation_id')
            )
            ->get();

        $existingSellLines = TransactionSellLine::where('transaction_id', $transaction->id)
            ->get()
            ->keyBy('product_id');

        $variationIds = $spareParts->pluck('variation_id')->filter()->unique()->toArray();
        $stockMap = [];
        if (!empty($variationIds)) {
            $stockMap = VariationLocationDetails::whereIn('variation_id', $variationIds)
                ->where('location_id', $locationId)
                ->pluck('qty_available', 'variation_id')
                ->toArray();
        }

        $processedProductIds = [];

        foreach ($spareParts as $sparePart) {
            $productId = (int) $sparePart->product_id;
            $variationId = (int) $sparePart->variation_id;
            $requestedQty = (float) $sparePart->quantity;
            $price = (float) $sparePart->price;
            $enableStock = (int) $sparePart->enable_stock;

            if (!$sparePart->client_approval) {
                if ($existingSellLines->has($productId)) {
                    $stockSyncUtil->deleteSellLine($existingSellLines->get($productId)->id, true);
                }
                continue;
            }

            $processedProductIds[] = $productId;

            $existingLine = $existingSellLines->get($productId);
            if ($existingLine) {
                $variationId = (int) $existingLine->variation_id;
            }

            if ($enableStock === 1) {
                $currentStock = (float) ($stockMap[$variationId] ?? 0.0);
                $alreadyInTransaction = $existingLine ? (float) $existingLine->quantity : 0.0;
                $totalAvailable = $currentStock + $alreadyInTransaction;
                $qtyToSell = min($requestedQty, $totalAvailable);
                $qtyToSell = max(0.0, $qtyToSell);
            } else {
                $qtyToSell = $requestedQty;
            }

            $productData = [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $qtyToSell,
                'unit_price' => $price,
                'unit_price_inc_tax' => $price,
                'unit_price_before_discount' => $price,
                'line_discount_type' => 'fixed',
                'line_discount_amount' => 0,
                'item_tax' => 0,
                'tax_id' => null,
            ];

            if ($existingLine) {
                if ($qtyToSell > 0) {
                    $stockSyncUtil->createOrUpdateSellLine($transaction, $productData, $existingLine->id);
                } else {
                    $stockSyncUtil->deleteSellLine($existingLine->id, true);
                }
            } else {
                if ($qtyToSell > 0) {
                    $stockSyncUtil->createOrUpdateSellLine($transaction, $productData, null);
                }
            }
        }

        foreach ($existingSellLines as $prodId => $sellLine) {
            if (!in_array((int) $prodId, $processedProductIds, true)) {
                $stockSyncUtil->deleteSellLine($sellLine->id, true);
            }
        }

        $stockSyncUtil->recalculateTransactionTotals($transaction->fresh());
    }

    public function testcheckphone(Request $request)
    {
        // Validate required parameters
        if (!$request->has('id') || !$request->has('phone')) {
            return response()->json([
                'error' => 'Missing required parameters: id and phone'
            ], 400);
        }

        $jobSheetId = $request->id;
        $phoneLastFourDigits = $request->phone;

        // Get contact information for phone verification
        $contactInfo = DB::table('repair_job_sheets')
          
            ->join('contacts', 'contacts.id', '=', 'repair_job_sheets.contact_id')
            ->where('repair_job_sheets.id', $jobSheetId)
            ->select('contacts.mobile', 'contacts.id as contact_id')
            ->first();

     
        // Check if job sheet exists
        if (!$contactInfo) {
            return response()->json([
                'error' => 'Job sheet not found'
            ], 404);
        }

        // Verify phone number (last 4 digits)
        $contactLastFourDigits = substr($contactInfo->mobile, -4);
    
        
        if ($contactLastFourDigits != $phoneLastFourDigits) {
         
            
            return response()->json([
                'error' => 'No matching number of phone'
            ], 401);
        }
        
      

        // Find user associated with this contact for authentication
        $user = User::where('crm_contact_id', $contactInfo->contact_id)->first();
        $authToken = null;
        
        if ($user) {
            $authToken = $user->createToken('auth_token')->accessToken;
          
        } 
        

        // Get comprehensive job sheet and booking information
        $jobSheetDetails = DB::table('repair_job_sheets')
            ->join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->join('contact_device', 'contact_device.id', '=', 'bookings.device_id')
            ->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->join('types_of_services', 'types_of_services.id', '=', 'bookings.service_type_id')
            ->join('contacts', 'contacts.id', '=', 'bookings.contact_id')
            ->join('categories', 'categories.id', '=', 'contact_device.device_id')
            ->where('repair_job_sheets.id', $jobSheetId)
            ->select(
                // Customer information
                'contacts.name AS name',
                'contacts.mobile  AS mobile ',

                // Vehicle information
                'contact_device.color AS color',
                'contact_device.plate_number',
                'contact_device.plate_number AS number',
                'contact_device.chassis_number as chassisNumber',
                'contact_device.manufacturing_year as year',
                'repair_device_models.name AS model',
                'categories.name AS catname',

                // Service information
                'types_of_services.name AS service',
                'repair_job_sheets.status_id AS status',
                'repair_job_sheets.entry_date',
                'repair_job_sheets.due_date',
       

                // NEW: Additional requested fields
                'bookings.booking_start',
                'repair_job_sheets.job_sheet_no'
            )
            ->first();

        // Get job sheet dates for countdown calculation
        $jobSheetDates = DB::table('repair_job_sheets')
            ->select('start_date', 'due_date')
            ->where('id', $jobSheetId)
            ->first();

        // Get job order products with SKU and product category information
        $jobOrderProducts = DB::table('product_joborder')
            ->leftJoin('products', 'products.id', '=', 'product_joborder.product_id')
            ->leftJoin('categories as product_categories', 'product_categories.id', '=', 'products.category_id')
            ->where('product_joborder.job_order_id', $jobSheetId)
            ->select(
                'product_joborder.*',
                'products.name as product_name',
                'products.sku', // Product SKU
                DB::raw('product_categories.id as product_category_id'),
                DB::raw('product_categories.name as product_category_name')
            )
            ->get();

        // Calculate time remaining until due date
        $timeRemaining = $this->calculateTimeRemaining($jobSheetDates->due_date);

        $taggedImages = Media::where('model_id', $jobSheetId)
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->get()
            ->map(function ($mediaItem) {
                return [
                    'id' => $mediaItem->id,
                    'url' => $mediaItem->display_url,
                    'tag' => $mediaItem->description,
                ];
            })
            ->values();

        // Prepare structured response
        $response = [
            'success' => 'matching number of phone',

            // Vehicle and customer information
            'dataofcar' => $jobSheetDetails,

            // NEW: Additional booking and job sheet information
            'booking_start' => $jobSheetDetails->booking_start,
            'job_sheet_no' => $jobSheetDetails->job_sheet_no,

            // Time countdown
            'days' => $timeRemaining['days'],
            'hours' => $timeRemaining['hours'],
            'minutes' => $timeRemaining['minutes'],
            'seconds' => $timeRemaining['seconds'],

            // Job order products with SKU
            'job_order' => $jobOrderProducts,

            'tagged_images' => $taggedImages,

            // Status and token
            'status_id' => $jobSheetDetails->status,
            'token' => $authToken,
        ];

        return response()->json($response);
    }

    public function testcheckphoneEstimator(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'phone' => 'required|string',
        ]);

        $estimatorId = $request->id;
        $phoneLastFourDigits = $request->phone;
        
        // Get comprehensive job estimator and related information
        $estimatorDetails = DB::table('job_estimator')
            ->join('contacts', 'contacts.id', '=', 'job_estimator.contact_id')
            ->join('contact_device', 'contact_device.id', '=', 'job_estimator.device_id')
            ->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->leftJoin('types_of_services', 'types_of_services.id', '=', 'job_estimator.service_type_id')
            ->join('categories', 'categories.id', '=', 'contact_device.device_id')
            ->where('job_estimator.id', $estimatorId)
            ->select(
                // Customer information
                'contacts.name AS name',
                'contacts.mobile AS mobile',

                // Vehicle information
                'contact_device.color AS color',
                'contact_device.plate_number',
                'contact_device.plate_number AS number',
                'contact_device.chassis_number as chassisNumber',
                'contact_device.manufacturing_year as year',
                'repair_device_models.name AS model',
                'categories.name AS catname',

                // Service information
                'types_of_services.name AS service',
                'job_estimator.estimator_status AS status',
                'job_estimator.created_at as entry_date',
          
                // Additional estimator fields
                'job_estimator.id',
                'job_estimator.estimate_no',
                'job_estimator.sent_to_customer_at',
                'job_estimator.approved_at',
                'job_estimator.amount',
            )
            ->first();


          // Verify phone number (last 4 digits)
        $contactLastFourDigits = substr($estimatorDetails->mobile, -4);
        if ($contactLastFourDigits != $phoneLastFourDigits) {
            return response()->json([
                'error' => 'No matching number of phone'
            ], 401);
        }
       
      

        // Find user associated with this contact for authentication
        $contact = DB::table('contacts')->where('mobile', $estimatorDetails->mobile)->first();
        $user = null;
        $authToken = null;
        
        if ($contact) {
            $user = User::where('crm_contact_id', $contact->id)->first();
            if ($user) {
                $authToken = $user->createToken('auth_token')->accessToken;
               
            } 
        }
        // Prepare structured response
        $response = [
            'success' => 'matching number of phone',

            // Vehicle and customer information
            'dataofcar' => $estimatorDetails,

            // Additional estimator information
            'estimate_no' => $estimatorDetails->estimate_no,
            'sent_to_customer_at' => $estimatorDetails->sent_to_customer_at,
            'approved_at' => $estimatorDetails->approved_at,

         
          

            // Status and token
            'status_id' => $estimatorDetails->status,
            'token' => $authToken,
        ];

        return response()->json($response);
    }

    /**
     * Calculate time remaining until due date
     *
     * @param string $dueDate
     * @return array
     */
    private function calculateTimeRemaining($dueDate)
    {
        if (Carbon::parse($dueDate)->isPast()) {
            return [
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0
            ];
        }

        $diff = Carbon::parse(now())->diff(Carbon::parse($dueDate));

        return [
            'days' => $diff->days,
            'hours' => $diff->h,
            'minutes' => $diff->m,
            'seconds' => $diff->s
        ];
    }

    /**
     * List job estimators with filtering, validation, and robust error handling.
     *
     * REST: GET connector/api/job-estimators
     * Query params:
     * - status: pending|sent|approved|rejected|converted_to_order (optional)
     * - location_id: integer (optional)
     * - expected_delivery_date_from: date (optional)
     * - expected_delivery_date_to: date, >= from (optional)
     * - per_page: integer [1..100] (optional, default 15)
     *
     * Returns paginated results ordered by status ASC, expected_delivery_date DESC (NULLs last), then created_at DESC.
     */


    public function storeContact(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->id;

        // Basic validation
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'mobile' => 'required|string',
        ]);

        $mobile = trim((string) $request->mobile);

        // Prevent duplicate mobiles (global uniqueness, excluding soft-deleted)
        $exists = DB::table('contacts')
            ->where('mobile', $mobile)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الهاتف موجود بالفعل لدي عميل اخر.',
                'errors' => [
                    'mobile' => ['رقم الهاتف موجود بالفعل لدي عميل اخر.']
                ],
            ], 422);
        }

        $conatact_id = DB::table('contacts')->insertGetId([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "mobile"   =>  $mobile,
            "name" => $request->first_name . ' ' . $request->last_name,
            "business_id" => 1,
            "created_by" => $user_id,
            "type" => "customer",
            "contact_type" => "individual",
        ]);

        return response()->json([
            'success' => true,
            'contact' => $conatact_id
        ], 201);
    }

    public function getcars($id)
    {
        $car = DB::table('contacts')
            ->join('contact_device', 'contact_device.contact_id', '=', 'contacts.id')
            ->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->join('categories', 'categories.id', '=', 'contact_device.device_id')
            ->where('contacts.id', $id)
            ->select(
                'contact_device.id',
                'repair_device_models.name AS model',
                'categories.name AS device',
                'contact_device.color',
                'contact_device.plate_number',
                'contact_device.manufacturing_year',
                'contact_device.chassis_number',
                'contact_device.car_type',
                'contact_device.motor_cc',
            )->get();

        return response()->json($car);
    }

    public function storeCar(Request $request)
    {
     
        $request->validate([
            'brand_id' => 'required|integer|exists:categories,id',
            'model_id' => 'required|integer|exists:repair_device_models,id',
            'color' => 'nullable|string|max:255',
            'plate_number' => 'nullable|string|max:255|unique:contact_device,plate_number',
            'manufacturing_year' => 'nullable|integer|min:1900|max:2100',
            'car_type' => 'nullable|string|max:255',
            'contact_id' => 'required|integer|exists:contacts,id',
            'chassis_number' => 'nullable|string|max:255',
            'brand_origin_variant_id' => 'nullable|integer|exists:brand_origin_variants,id',
        ]);

        $modelBelongsToBrand = DB::table('repair_device_models')
            ->where('id', $request->model_id)
            ->where('device_id', $request->brand_id)
            ->exists();

        if (!$modelBelongsToBrand) {
            return response()->json([
                'message' => __('checkcar::lang.model_does_not_belong_to_brand') ?? 'The selected model does not belong to the selected brand. Please select a valid model.',
            ], 422);
        }

        $input = $request->only([
            'brand_id',
            'model_id',
            'color',
            'plate_number',
            'manufacturing_year',
            'car_type',
            'contact_id',
            'chassis_number',
            'brand_origin_variant_id',
            'motor_cc'
        ]);


        $contactDevice = ContactDevice::create([
            'device_id' => $input['brand_id'],
            'models_id' => $input['model_id'],
            'color' => $input['color'] ?? null,
            'chassis_number' => $input['chassis_number'] ?? null,
            'plate_number' => $input['plate_number'],
            'manufacturing_year' => $input['manufacturing_year'],
            'car_type' => $input['car_type'],
            'contact_id' => $input['contact_id'],
            'brand_origin_variant_id' => $input['brand_origin_variant_id'] ?? null,
            'motor_cc' => $input['motor_cc'] ?? null,
        ]);
        return response()->json(["data" => "Adding Car successfully", "car" => $contactDevice->id]);
    }

    public function getAllContacts()
    {
        $contacts = DB::table('contacts')->select('name', 'id', 'mobile')->get();
        return response()->json($contacts);
    }

    public function storeBooking(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|integer|exists:contacts,id',
            'device_id' => 'required|integer|exists:contact_device,id',
            'location_id' => 'required|integer',
            'booking_start' => 'required|date',
            'booking_note' => 'nullable|string|max:1000',
            'service_id' => 'required|integer',
            'send_notification' => 'nullable|boolean',
            'is_callback' => 'nullable|boolean',
            'call_back_ref' => 'nullable|integer',
        ]);

        $user = Auth::user();
        $business_id = $user->business_id;

        $idempotency_key = md5($validated['contact_id'] . '-' . $validated['device_id'] . '-' . $validated['booking_start']);

        $existingBooking = DB::table('bookings')
            ->where('contact_id', $validated['contact_id'])
            ->where('device_id', $validated['device_id'])
            ->where('location_id', $validated['location_id'])
            ->where('booking_start', $validated['booking_start'])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->first();

        $send_notification = $validated['send_notification'] ?? false;
        if ($existingBooking) {
            return response()->json([
                'success' => 1,
                'msg' => trans('lang_v1.added_success'),
                'data' => 'booking successfully',
                'message' => 'Duplicate request detected, booking already created',
                'send_notification' => $validated['send_notification'],
                'idempotency_key' => $idempotency_key,
            ], 200);
        }
        $contact = DB::table('contacts')
            ->where('id', $validated['contact_id'])
            ->select(
                'contacts.name',
                'contacts.mobile',
            )
            ->first();

        if (!$contact) {
            return response()->json(['error' => 'Contact not found'], 404);
        }

        $device = DB::table('contact_device')
            ->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->where('contact_device.id', $validated['device_id'])
            ->select('repair_device_models.name')
            ->first();

        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        $booking_name = $contact->name . ' - ' . $device->name . ' - ' . $validated['booking_start'];

        DB::table('bookings')->insert([
            "booking_start" => $validated['booking_start'],
            "location_id" => $validated['location_id'],
            "booking_name" => $booking_name,
            "business_id" => $business_id,
            "contact_id" => $validated['contact_id'],
            "device_id" => $validated['device_id'],
            'booking_note' => $validated['booking_note'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
            'booking_status' => 'waiting',
            'service_type_id' => $validated['service_id'],
            'is_callback' => $validated['is_callback'] ?? false,
            'call_back_ref' => $validated['call_back_ref'] ?? null,
        ]);

        $output = [
            'success' => 1,
            'msg' => trans('lang_v1.added_success'),
            'data' => 'booking successfully',
        ];

        if ($send_notification && !empty($contact->mobile)) {
            $location_name = DB::table('business_locations')
                ->where('id', $validated['location_id'])
                ->value('name');

            $booking_start = Carbon::parse($validated['booking_start']);
            $message = 'اهلا ا/' . $contact->name . ' لقد تم الحجز بنجاح في يوم ' . $booking_start->toDateTimeString() . ' في ' . $location_name;

            try {
                $smsSent = SmsUtil::sendEpusheg($contact->mobile, $message);

                SmsLog::create([
                    'contact_id' => $validated['contact_id'],
                    'transaction_id' => null,
                    'job_sheet_id' => null,
                    'mobile' => $contact->mobile,
                    'message_content' => $message,
                    'status' => (is_array($smsSent) && $smsSent['success']) ? 'sent' : 'failed',
                    'error_message' => (is_array($smsSent) && $smsSent['success']) ? null : 'Failed to send SMS',
                    'provider_balance' => is_array($smsSent) ? $smsSent['balance'] : SmsUtil::getLastNetBalance(),
                    'sent_at' => (is_array($smsSent) && $smsSent['success']) ? now() : null,
                ]);

                $output['send_notification'] = 1;
            } catch (\Exception $e) {
                Log::error('SMS sending failed for booking: ' . $e->getMessage());
            }
        }

        return response()->json($output);
    }

    /**
     * Get all job sheets for a specific contact device (car)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobSheetsByContactDevice(Request $request)
    {
        try {
           

            $validated = $request->validate([
                'contact_device_id' => 'required|integer',
            ]);

    
            // Get all job sheets for this specific contact device (car)
            $jobSheets = DB::table('repair_job_sheets')

            ->leftjoin('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->where('bookings.device_id', $validated['contact_device_id'])
                ->select(
                    'repair_job_sheets.id as job_sheet_id',
                    'repair_job_sheets.job_sheet_no',
                    'repair_job_sheets.created_at',
                )
                ->orderBy('repair_job_sheets.created_at', 'desc')
                ->get();

            // Format the response
            $formattedJobSheets = $jobSheets->map(function ($jobSheet) {
                return [
                    'job_sheet_id' => $jobSheet->job_sheet_id,
                    'job_sheet_no' => $jobSheet->job_sheet_no,
                    'created_at' => $jobSheet->created_at,
                ];
            });


            return response()->json([
                'success' => true,
                'data' => $formattedJobSheets,
                'total_count' => $formattedJobSheets->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('getJobSheetsByContactDevice error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching job sheets: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * PATCH /contact-devices/{id}
     * Update a contact_device record by ID. Scope by authenticated user's business.
     * Accepts existing contact_device fields only (no schema changes).
     */
    public function updateContactDevice(Request $request, $id)
    {

        $validated = $request->validate([
            'color' => 'nullable|string|max:255',
            'plate_number' => 'nullable|string|max:255',
            'manufacturing_year' => 'nullable|integer|min:1900|max:2100',
            'chassis_number' => 'nullable|string|max:255',
            'car_type' => 'nullable|string|max:255',
            'models_id' => 'nullable|integer|exists:repair_device_models,id',
            'device_id' => 'nullable|integer|exists:categories,id',
            'brand_origin_variant_id' => 'nullable|integer|exists:brand_origin_variants,id',
            'motor_cc' => 'nullable|string|max:80',
        ]);

        // Ensure at least one updatable field is present
        $updatableKeys = ['color','plate_number','manufacturing_year','chassis_number','car_type','models_id','device_id','brand_origin_variant_id', 'motor_cc'];
        $hasAnyField = false;
        foreach ($updatableKeys as $key) {
            if ($request->has($key)) { $hasAnyField = true; break; }
        }
        if (!$hasAnyField) {
            return response()->json(['message' => 'No update fields provided'], 422);
        }

        $user = Auth::user();

        $device = DB::table('contact_device')->where('id', (int) $id)->first();
        if (!$device) {
            return response()->json(['message' => 'Contact device not found'], 404);
        }

        // Scope by business via owning contact
        $contact = DB::table('contacts')->select('business_id')->where('id', $device->contact_id)->first();
        if (!$contact || (int)$contact->business_id !== (int)$user->business_id) {
            return response()->json(['message' => 'Contact device not found'], 404);
        }

        $update = [];
        foreach ($updatableKeys as $key) {
            if ($request->has($key)) {
                $update[$key] = $request->input($key);
            }
        }

        if (empty($update)) {
            return response()->json(['message' => 'No valid fields to update'], 422);
        }

        DB::table('contact_device')->where('id', (int) $id)->update($update);

        $updated = DB::table('contact_device')->where('id', (int) $id)->first();

        return response()->json([
            'message' => 'Contact device updated successfully',
            'data' => [
                'id' => $updated->id,
                'color' => $updated->color,
                'plate_number' => $updated->plate_number,
                'manufacturing_year' => $updated->manufacturing_year,
                'chassis_number' => $updated->chassis_number,
                'car_type' => $updated->car_type,
                'models_id' => $updated->models_id,
                'device_id' => $updated->device_id,
                'brand_origin_variant_id' => $updated->brand_origin_variant_id ?? null,
                'motor_cc' => $updated->motor_cc,
            ],
        ], 200);
    }

  
}
