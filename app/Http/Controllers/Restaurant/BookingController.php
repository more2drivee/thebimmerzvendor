<?php

namespace App\Http\Controllers\Restaurant;

use App\User;
use App\Contact;
use Carbon\Carbon;
use App\Utils\Util;
use App\CustomerGroup;
use App\Utils\SmsUtil;
use App\Models\FcmToken;
use App\BusinessLocation;
use App\Restaurant\Booking;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Utils\RestaurantUtil;

use App\Utils\TransactionUtil;
use App\Helpers\FirebaseHelper;
use Modules\Sms\Entities\SmsLog;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\NotificationService;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\NotificationController;

class BookingController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $commonUtil;

    protected $restUtil;

    public function __construct(Util $commonUtil, RestaurantUtil $restUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->restUtil = $restUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if (! auth()->user()->can('crud_all_bookings') && ! auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $user_id = request()->has('user_id') ? request()->user_id : null;
        if (! auth()->user()->hasPermissionTo('crud_all_bookings') && ! $this->restUtil->is_admin(auth()->user(), $business_id)) {
            $user_id = request()->session()->get('user.id');
        }
        if (request()->ajax()) {
            $filters = [
                'start_date' => request()->start,
                'end_date' => request()->end,
                'user_id' => $user_id,
                'location_id' => !empty(request()->location_id) ? request()->location_id : null,
                'business_id' => $business_id,
            ];

            $events = $this->restUtil->getBookingsForCalendar($filters);



            return $events;
        }

        $correspondents = User::forDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);

        // $customers = Contact::customersDropdown($business_id, false);

        $types = Contact::getContactTypes();
        $customer_groups = CustomerGroup::forDropdown($business_id);
        $customers = DB::table('contacts')

            ->where('is_default', 0)
            ->get(['id', 'name']);
        $services = DB::table('types_of_services')
            ->where(function ($q) {
                $q->whereNull('is_inspection_service')
                  ->orWhere('is_inspection_service', 0);
            })
            ->get(['id', 'name'])
            ->pluck('name', 'id'); // This will create an associative array with 'id' as the key and 'name' as the value.

      


        return view('restaurant.booking.index', compact('services', 'business_locations', 'customers', 'correspondents', 'types', 'customer_groups', 'customers'));
    }
    public function getModelsByCustomer($customerId)
    {
        $cars = DB::select(
            "SELECT
                contact_device.id,
                repair_device_models.name AS model_name,
                contact_device.plate_number,
                contact_device.color,
                contact_device.models_id AS model_id
            FROM
                contact_device
            LEFT JOIN
                repair_device_models ON contact_device.models_id = repair_device_models.id
            WHERE
                contact_device.contact_id = :customerId",
            ['customerId' => $customerId]
        );

        return response()->json($cars);
    }

    /**
     * Get inspection services only (for Buy & Sell Car Inspection modal)
     */
    public function getInspectionServices()
    {
        $services = DB::table('types_of_services')
            ->where('is_inspection_service', 1)
            ->pluck('name', 'id');

        return response()->json($services);
    }

    /**
     * Get all services for a location (for standard bookings)
     */
    public function getServicesByLocation($locationId)
    {
        $business_id = request()->session()->get('user.business_id');

        $query = DB::table('types_of_services')
         
            ->where(function ($q) {
                $q->whereNull('is_inspection_service')
                  ->orWhere('is_inspection_service', 0);
            })
            ->where(function ($q) use ($locationId) {
                $q->whereNull('location_price_group')
                  ->orWhereJsonContains('location_price_group->' . $locationId, 0);
            });

        $services = $query->pluck('name', 'id');

        return response()->json($services);
    }

    /**
     * Get vehicle compatibility information for filtering products
     *
     * @param int $vehicleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVehicleCompatibility($vehicleId)
    {
        try {
            // Get vehicle information with brand category
            $vehicle = DB::table('contact_device')
                ->leftJoin('repair_device_models', 'contact_device.models_id', '=', 'repair_device_models.id')
                ->leftJoin('categories', 'contact_device.device_id', '=', 'categories.id')
                ->where('contact_device.id', $vehicleId)
                ->select(
                    'contact_device.models_id as model_id',
                    'contact_device.device_id as brand_category_id',
                    'repair_device_models.name as model_name',
                    'categories.name as brand_name'
                )
                ->first();

            if (!$vehicle) {
                return response()->json(['error' => 'Vehicle not found'], 404);
            }

            return response()->json([
                'model_id' => $vehicle->model_id,
                'brand_category_id' => $vehicle->brand_category_id,
                'model_name' => $vehicle->model_name,
                'brand_name' => $vehicle->brand_name
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching vehicle compatibility'], 500);
        }
    }

    /**
     * Get estimators by identifier.
     * Accepts either a contact_id or a vehicle (device) id.
     * Maintains existing response format.
     */
    public function estimatorsByContact($identifier)
    {
        $id = (int) $identifier;

        // Detect if the provided id corresponds to a device (vehicle)
        $isDevice = DB::table('contact_device')->where('id', $id)->exists();

        $query = DB::table('job_estimator')
            ->whereIn('estimator_status', ['pending', 'sent', 'replied'])
            ->where('estimator_status', '!=', 'booked');

        if ($isDevice) {
            $query->where('device_id', $id);
        } else {
            $query->where('contact_id', $id);
        }

        $estimators = $query
            ->orderByDesc('id')
            ->get(['id', 'estimate_no']);

        return response()->json([
            'success' => true,
            'data' => $estimators,
        ]);
    }

    /**
     * Get estimator details to prepopulate booking form
     */
    public function estimatorDetails($id)
    {
        $estimator = DB::table('job_estimator')
            ->where('id', (int)$id)
            ->select('id', 'contact_id', 'device_id', 'location_id', 'service_type_id', 'estimate_no')
            ->first();

        if (!$estimator) {
            return response()->json(['success' => false, 'msg' => 'Not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $estimator]);
    }

    // Debugging output to check the fetched data




    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }


    // public function store(Request $request)
    // {
    //     if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     $validated = $request->validate([
    //         'contact_id' => 'required|integer',
    //         'model_id' => 'required|integer',
    //         'services' => 'required|integer',
    //         // 'car_type' => 'required|string',
    //         'location_id' => 'required|string|max:255',
    //         'booking_start' => 'required',
    //         'booking_end' => 'required',
    //         'booking_note' => 'nullable|string|max:255',
    //     ]);

    //     try {
    //         $business_id = session('user.business_id');  // Fetch business_id from session
    //         $user_id = session('user.id');  // Fetch user_id from session
    //         if ($request->ajax()) {
    //             $booking_start = $this->commonUtil->uf_date($validated['booking_start'], true);
    //             $booking_end = $this->commonUtil->uf_date($validated['booking_end'], true);
    //             $date_range = [$booking_start, $booking_end];

    //             // Check if booking is available for the required input
    //             $existing_booking = Booking::where('business_id', $business_id)
    //                 ->where('location_id', $validated['location_id'])
    //                 ->where('contact_id', $validated['contact_id'])
    //                 ->where(function ($query) use ($date_range) {
    //                     $query->whereBetween('booking_start', $date_range)
    //                         ->orWhereBetween('booking_end', $date_range);
    //                 })
    //                 ->first();

    //             if ($existing_booking) {
    //                 $time_range = $this->commonUtil->format_date($existing_booking->booking_start, true) . ' ~ ' .
    //                     $this->commonUtil->format_date($existing_booking->booking_end, true);

    //                 return [
    //                     'success' => 0,
    //                     'msg' => trans('restaurant.booking_not_available', [
    //                         'customer_name' => $existing_booking->customer->name,
    //                         'booking_time_range' => $time_range,
    //                     ]),
    //                 ];
    //             }

    //             // Fetch contact, device, and brand information using inner join
    //             $contactDeviceBrand = DB::table('contacts')
    //                 ->join('contact_device', 'contacts.id', '=', 'contact_device.contact_id')
    //                 ->join('repair_device_models', 'contact_device.models_id', '=', 'repair_device_models.id')
    //                 ->where('contacts.id', $validated['contact_id'])
    //                 ->where('contact_device.id', $validated['model_id'])
    //                 ->select('contacts.name as contact_name', 'repair_device_models.name as brand_name', 'contact_device.models_id')
    //                 ->first();

    //             if (!$contactDeviceBrand) {
    //                 return back()->withErrors('Invalid contact, device, or brand.');
    //             }

    //             // Create the booking name
    //             $booking_name = "{$contactDeviceBrand->contact_name} - {$contactDeviceBrand->brand_name} - {$validated['booking_start']}";
    //             $created_by = auth()->id();

    //             // Insert new booking, including business_id
    //             $booking = DB::insert('
    //                 insert into bookings (business_id, booking_name, contact_id, device_id, location_id,created_by,booking_start, booking_end, service_type_id, booking_status, booking_note)
    //                 values (?, ?, ?, ?, ?,? ,?, ?, ?, ?, ?)', [
    //                 $business_id,  // Insert business_id
    //                 $booking_name,

    //                 $validated['contact_id'],
    //                 $validated['model_id'],
    //                 $validated['location_id'],
    //                 $created_by,
    //                 $validated['booking_start'],
    //                 $validated['booking_end'],
    //                 $validated['services'],
    //                 'Booked',
    //                 $validated['booking_note'],
    //             ]);

    //             $output = [
    //                 'success' => 1,
    //                 'msg' => trans('lang_v1.added_success'),
    //             ];

    //             // Send notification if applicable
    //             if ($request->input('send_notification') == 1) {
    //                 $output['send_notification'] = 1;
    //                 // You may need to create a proper action for new bookings
    //                 $output['notification_url'] = action([NotificationController::class, 'getTemplate'], [
    //                     'transaction_id' => $booking->id,
    //                     'template_for' => 'new_booking'
    //                 ]);
    //             }

    //             return $output;
    //         }

    //         // If not an AJAX request, throw an error
    //         return response()->json(['error' => __('messages.something_went_wrong')], 400);
    //     } catch (\Exception $e) {
    //         Log::error("Error in store booking: {$e->getMessage()}", [
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //         ]);
    //         return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 500);
    //     }
    // }



    public function store_new_booking(Request $request)
    {
        // Ensure the 'is_callback' is present in the request and default to false if not checked
        $request->merge([
            'is_callback' => $request->has('is_callback') ? true : false,
        ]);

        try {
            // Validate the request data
            $validated = $request->validate([
                'contact_id' => 'required|integer',
                'buyer_contact_id' => 'nullable|integer',
                'model_id' => 'required|integer',
                'services' => 'required|integer',
                'location_id' => 'required|integer|max:255',
                'booking_start' => 'required|date',
                'booking_note' => 'nullable|string|max:255',
                'send_notification_value' => 'required|integer',
                // 'car_type' => 'nullable|string|max:255',
                'is_callback' => 'required|boolean', // Updated to validate as boolean
                'call_back_ref' => 'nullable|integer', // Changed to integer for job sheet ID
                'job_estimator_id' => 'nullable|integer',
            ]);

            // Fetch business_id from session (or another source)
            $business_id = session('user.business_id'); // Ensure this is set in your session
            if (!$business_id) {
                return response()->json([
                    'success' => 0,
                    'msg' => 'Business ID not found. Please log in again.',
                ], 400);
            }

            // Fetch contact name from the contacts table
            $contact = DB::table('contacts')->where('id', $validated['contact_id'])->first();
            // Fetch device name from the contact_device table
            $device = DB::table('contact_device')->where('id', $validated['model_id'])->first();
            $brand = $device
                ? DB::table('repair_device_models')->where('id', $device->models_id)->first()
                : null;

            // Check if the contact and device were found
            if (!$contact || !$device || !$brand) {
                return response()->json([
                    'success' => 0,
                    'msg' => 'Invalid contact or vehicle.',
                ], 422);
            }

            // Prevent duplicate bookings: check if a booking already exists for the same
            // contact, device, and location within the last 5 minutes
            $recentDuplicateBooking = DB::table('bookings')
                ->where('contact_id', $validated['contact_id'])
                ->where('device_id', $validated['model_id'])
                ->where('location_id', $validated['location_id'])
                ->where('created_at', '>=', Carbon::now()->subMinutes(5))
                ->first();

            if ($recentDuplicateBooking) {
                return response()->json([
                    'success' => 0,
                    'msg' => __('checkcar::lang.duplicate_booking_detected') ?? 'A similar booking was already created recently. Please check existing bookings.',
                ], 422);
            }

            // Create the booking name with contact name and device name
            $booking_name = $contact->name . ' - ' . $brand->name . ' - ' . $validated['booking_start'];
            $created_by = auth()->id(); // Get the ID of the currently authenticated user

            // Calculate booking_end as 1 hour after booking_start
            $booking_start = Carbon::parse($validated['booking_start']); // Parse booking_start
            $booking_end = $booking_start->copy()->addHour(); // Add 1 hour

            // If estimator selected, validate it matches the booking data
            if (!empty($validated['job_estimator_id'])) {
                $estimator = DB::table('job_estimator')
                    ->where('id', (int) $validated['job_estimator_id'])
                    ->first();

                if (!$estimator) {
                    return response()->json([
                        'success' => 0,
                        'msg' => 'Selected estimate not found.',
                    ], 404);
                }

                // Validate: contact, device, and service must match estimator
                if ((int) $estimator->contact_id !== (int) $validated['contact_id']) {
                    return response()->json([
                        'success' => 0,
                        'msg' => 'Estimate belongs to a different customer.',
                    ], 422);
                }
                if ((int) $estimator->device_id !== (int) $validated['model_id']) {
                    return response()->json([
                        'success' => 0,
                        'msg' => 'Estimate is for a different vehicle.',
                    ], 422);
                }
                // if (!is_null($estimator->service_type_id) && (int)$estimator->service_type_id !== (int)$validated['services']) {
                //     return back()->withErrors('Service type does not match the estimate.');
                // }
            }

            DB::insert(
                '
            INSERT INTO bookings (
                business_id,
                created_by,
                booking_name,
                contact_id,
                buyer_contact_id,
                device_id,
                location_id,
                booking_start,
                booking_end,
                service_type_id,
                booking_status,
                booking_note,
                is_callback,
                call_back_ref,
                job_estimator_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $business_id, // Add business_id
                    $created_by,  // Add created_by
                    $booking_name,
                    $validated['contact_id'],
                    $validated['buyer_contact_id'] ?? null,
                    $validated['model_id'],
                    $validated['location_id'],
                    $booking_start->toDateTimeString(), // Convert to string for database
                    $booking_end->toDateTimeString(),   // Convert to string for database
                    $validated['services'],
                    'waiting', // Corrected spelling from 'pendding'
                    $validated['booking_note'],
                    // $validated['car_type'],
                    $validated['is_callback'], // Storing is_callback instead of booking_type
                    $validated['call_back_ref'] ?? null, // Add callback reference
                    $validated['job_estimator_id'] ?? null, // Add job estimator id
                ]
            );

         
                $location_name = DB::table('business_locations')
                    ->where('id', $validated['location_id'])
                    ->value('name');

                // Send notifications to ALL users with FCM tokens (both Firebase and database), excluding the creator
                // $notificationResult = NotificationService::sendToAllUsers(
                //     'New Booking',
                //     "New booking for {$contact->name} at {$location_name} on {$booking_start->toDateTimeString()}",
                //     [
                //         'type' => 'booking_created',
                //         'booking_id' => DB::table('bookings')->latest('id')->first()->id ?? null,
                //         'contact_id' => (string) $contact->id,
                //         'location_id' => (string) $validated['location_id'],
                //         'booking_start' => $booking_start->toDateTimeString(),
                //         'url' => '/restaurant/booking',
                //     ],
                //     true,
                //     $created_by  // Exclude the creator from receiving notifications
                // );

                Log::info('Notification result', [
                    'success' => $notificationResult,
                    'method' => 'NotificationService::sendToAllUsers',
                ]);
    

            // If booking is linked to a job estimator, mark estimator as booked
            if (!empty($validated['job_estimator_id'])) {
                DB::table('job_estimator')
                    ->where('id', (int) $validated['job_estimator_id'])
                    ->update(['estimator_status' => 'booked']);
            }

            $output = [
                'success' => 1,
                'msg' => trans('lang_v1.added_success'),
            ];

            // Send SMS notification if requested
            if ($validated['send_notification_value'] == 1) {
                $data_contact = DB::table('contacts')
                    ->where('id', $validated['contact_id'])
                    ->select('id', 'mobile', 'name')
                    ->first();
                $location_name = DB::table('business_locations')
                    ->where('id', $validated['location_id'])
                    ->value('name');

                if ($data_contact && $data_contact->mobile) {
                    $message = 'اهلا ا/' . $data_contact->name . ' لقد تم الحجز بنجاح في يوم ' . $booking_start->toDateTimeString() . ' في ' . $location_name;
                    $smsSent = SmsUtil::sendEpusheg($data_contact->mobile, $message);
                    
                    // Log SMS with provider balance
                    SmsLog::create([
                        'contact_id' => $data_contact->id,
                        'transaction_id' => null,
                        'job_sheet_id' => null,
                        'mobile' => $data_contact->mobile,
                        'message_content' => $message,
                        'status' => (is_array($smsSent) && $smsSent['success']) ? 'sent' : 'failed',
                        'error_message' => (is_array($smsSent) && $smsSent['success']) ? null : 'Failed to send SMS',
                        'provider_balance' => is_array($smsSent) ? $smsSent['balance'] : SmsUtil::getLastNetBalance(),
                        'sent_at' => (is_array($smsSent) && $smsSent['success']) ? now() : null,
                    ]);
                }

                // Keep compatibility with existing JS handler
                $output['send_notification'] = 1;
                // $output['notification_url'] can be set here if you want to open the template modal
            }

            return response()->json($output);
        } catch (\Exception $e) {
            Log::error('Error creating booking', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }



    public function fetch_booking_data(Request $request)
    {



        // Validate the incoming request
        $validated = $request->validate([
            'booking_id' => 'required|integer',
        ]);

        // Get the selected booking_id from the request
        $booking_id = $validated['booking_id'];




        $bookings = DB::table('bookings')
            ->leftJoin('contacts', 'contacts.id', '=', 'bookings.contact_id')
            ->leftJoin('business_locations', 'business_locations.id', '=', 'bookings.location_id')
            ->leftJoin('types_of_services', 'types_of_services.id', '=', 'bookings.service_type_id')
            ->leftJoin('contact_device', 'contact_device.id', '=', 'bookings.device_id')
            ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->leftJoin('categories as brand', 'brand.id', '=', 'contact_device.device_id')
            ->select(
                'bookings.*',
                'contacts.id as contact_id', // Add contact_id
                'contacts.name as contact_name',
                'business_locations.id as location_id', // Add location_id
                'business_locations.name as location_name',
                'types_of_services.id as service_type_id', // Add service_type_id
                'types_of_services.name as type_name',
                'contact_device.id as device_id', // Add device_id
                'contact_device.chassis_number as car_chassis_number',
                'contact_device.color as car_color',
                'contact_device.plate_number as car_plate_number',
                'repair_device_models.id as device_model_id', // Add device_model_id
                'repair_device_models.name as device_name',
                'brand.name as brand_id', // Add device_model_id
                'brand.name as brand_name', // Add device_model_id
                'bookings.job_estimator_id as job_estimator_id',

            )
            ->where('bookings.id', '=', $booking_id)
            ->get();


        // Return the booking details as a JSON response
        return response()->json($bookings);
    }



    public function search(Request $request)
    {
        $searchTerm = $request->get('q'); // The search term from the Select2 input

        // Query the database using DB::table for the bookings (replace 'bookings' with your actual table name)
        $bookings = DB::table('bookings') // Replace 'bookings' with your actual table name
            ->where('name', 'like', '%' . $searchTerm . '%') // Searching by the 'name' column
            ->limit(10) // Optional: limit the number of results for better performance
            ->get();

        // Return the results as a JSON response
        return response()->json($bookings);
    }


    public function store_car_data(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|integer',
            'model_id' => 'required|integer',
            'color' => 'nullable|string|max:255',
            'chassis_number' => 'nullable|string|max:255',
            'plate_number' => 'nullable|string|max:255',
            'manufacturing_year' => 'required|integer|digits:4',
            'contact_id' => 'required|integer',
            'car_type' => 'required|string|in:ملاكي,اجرة,نقل ثقيل,نقل', // Validate car type
            'brand_origin_variant_id' => 'nullable|integer|exists:brand_origin_variants,id',
            'motor_cc' => 'nullable|integer',
        ]);

        try {
            // Validate that the selected model belongs to the selected brand
            $modelBelongsToBrand = DB::table('repair_device_models')
                ->where('id', $validated['model_id'])
                ->where('device_id', $validated['category_id'])
                ->exists();

            if (!$modelBelongsToBrand) {
                return response()->json([
                    'message' => __('checkcar::lang.model_does_not_belong_to_brand') ?? 'The selected model does not belong to the selected brand. Please select a valid model.',
                ], 422);
            }

            // Extract and store VIN codes if chassis number is provided
            // if (!empty($validated['chassis_number'])) {
            //     $chassisNumber = strtoupper(trim($validated['chassis_number']));

            //     // Check if the chassis number is a valid VIN (17 characters)
            //     if (strlen($chassisNumber) == 17 && preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $chassisNumber)) {
            //         // Extract manufacturer code (WMI) - first 3 characters
            //         $manufacturerCode = substr($chassisNumber, 0, 3);

            //         // Extract model code - characters 4-8
            //         $modelCode = substr($chassisNumber, 3, 5);

            //         // Reset any other brand that has this manufacturer code
            //         DB::table('categories')
            //             ->where('id', '!=', $validated['category_id'])
            //             ->where('vin_category_code', $manufacturerCode)
            //             ->update(['vin_category_code' => null]);

            //         // Reset any other model that has this model code
            //         DB::table('repair_device_models')
            //             ->where('id', '!=', $validated['model_id'])
            //             ->where('vin_model_code', $modelCode)
            //             ->update(['vin_model_code' => null]);

            //         // Update the category (brand) with the manufacturer code
            //         DB::table('categories')
            //             ->where('id', $validated['category_id'])
            //             ->update(['vin_category_code' => $manufacturerCode]);

            //         // Update the repair_device_model with the model code
            //         DB::table('repair_device_models')
            //             ->where('id', $validated['model_id'])
            //             ->update(['vin_model_code' => $modelCode]);

            //         Log::info("Updated VIN codes from chassis", [
            //             'chassis' => $chassisNumber,
            //             'brand_id' => $validated['category_id'],
            //             'model_id' => $validated['model_id'],
            //             'manufacturer_code' => $manufacturerCode,
            //             'model_code' => $modelCode
            //         ]);
            //     } else {
            //         Log::warning("Invalid chassis number format for VIN extraction", [
            //             'chassis' => $chassisNumber
            //         ]);
            //     }
            // }

            // Insert the car data as usual
            $contactDeviceId = DB::table('contact_device')->insertGetId([
                'device_id' => $validated['category_id'],
                'models_id' => $validated['model_id'],
                'color' => $validated['color'] ?? null,
                'chassis_number' => $validated['chassis_number'] ?? null,
                'plate_number' => $validated['plate_number'] ?? null,
                'manufacturing_year' => $validated['manufacturing_year'],
                'contact_id' => $validated['contact_id'],
                'car_type' => $validated['car_type'], // Insert car type
                'brand_origin_variant_id' => $validated['brand_origin_variant_id'] ?? null,
                'motor_cc' => $validated['motor_cc'] ?? null,
            ]);

            // Get model name for display
            $modelName = '';
            $model = DB::table('repair_device_models')->where('id', $validated['model_id'])->first();
            if ($model) {
                $modelName = $model->name;
            }

            return response()->json([
                'message' => 'Vehicle data inserted successfully.',
                'vin_codes_updated' => !empty($validated['chassis_number']) && strlen($validated['chassis_number']) == 17,
                'contact_device' => [
                    'id' => $contactDeviceId,
                    'model_name' => $modelName,
                    'plate_number' => $validated['plate_number'] ?? null,
                    'color' => $validated['color'] ?? null,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error in store_car_data: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['message' => 'Failed to insert vehicle data: ' . $e->getMessage()], 500);
        }
    }

    public function getCustomerVehicles($contactId)
    {
        // Run a raw SQL query to get vehicles associated with the contact ID
        $vehicles = DB::table('vehicles')
            ->where('contact_id', $contactId)
            ->get(['id', 'name']);  // Assuming 'id' and 'model_name' columns in vehicles table

        // Return vehicles as JSON response
        return response()->json($vehicles);
    }


    /**
     * Get all brands for dropdown
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBrands()
    {
        $business_id = request()->session()->get('user.business_id');

        // Fetch all brands (categories of type 'device')
        $brands = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('category_type', 'device')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($brands);
    }

    /**
     * Get models for a specific brand
     *
     * @param int $brandId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getModelsByBrand($brandId)
    {
        // Fetch the models related to the brand
        $models = DB::table('repair_device_models')
            ->where('device_id', $brandId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Fetch brand origin variants for this brand
        $variants = DB::table('brand_origin_variants')
            ->where('parent_id', $brandId)
            ->select('id', 'name', 'vin_category_code', 'country_of_origin')
            ->orderByRaw('COALESCE(country_of_origin, name) ASC')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'label' => $row->country_of_origin ?: $row->name,
                ];
            });

        return response()->json([
            'models' => $models,
            'variants' => $variants,
        ]);
    }

    /**
     * Get brand origins (countries) for a specific brand
     *
     * @param int $brandId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBrandOrigins($brandId)
    {
        // Fetch brand origin variants for this brand
        $variants = DB::table('brand_origin_variants')
            ->where('parent_id', $brandId)
            ->select('id', 'name', 'vin_category_code', 'country_of_origin')
            ->orderByRaw('COALESCE(country_of_origin, name) ASC')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'vin_category_code' => $row->vin_category_code,
                    'country_of_origin' => $row->country_of_origin,
                    'label' => $row->country_of_origin ? ($row->name . ' (' . $row->country_of_origin . ')') : $row->name,
                ];
            });

        return response()->json($variants);
    }

    public function contactDevices()
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }
        return view('restaurant.booking.contact_devices');
    }

    public function contactDevicesData()
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }
        $query = DB::table('contact_device')
            ->leftJoin('contacts', 'contacts.id', '=', 'contact_device.contact_id')
            ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->leftJoin('categories', 'categories.id', '=', 'contact_device.device_id')
            ->select(
                'contact_device.id',
                'contacts.name as contact_name',
                'categories.name as device_name',
                'repair_device_models.name as model_name',
                'contact_device.plate_number',
                'contact_device.color',
                'contact_device.manufacturing_year',
                'contact_device.car_type',
                'contact_device.chassis_number'
            );
        if (!empty(request()->contact_id)) {
            $query->where('contact_device.contact_id', request()->contact_id);
        }
        return DataTables::of($query)
            ->addColumn('actions', function ($row) {
                return '<span class="text-muted">No actions</span>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function getContactDeviceEditModal($id)
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $device = DB::table('contact_device')
            ->leftJoin('contacts', 'contacts.id', '=', 'contact_device.contact_id')
            ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->leftJoin('categories', 'categories.id', '=', 'contact_device.device_id')
            ->where('contact_device.id', (int)$id)
            ->select(
                'contact_device.*',
                'contacts.name as contact_name',
                'repair_device_models.name as model_name',
                'categories.name as brand_name'
            )
            ->first();
        $brands = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('category_type', 'device')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        return view('restaurant.booking.partials.edit_contact_device_modal', compact('device', 'brands'));
    }

    public function updateContactDevice(Request $request, $id)
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'device_id' => 'required|integer|exists:categories,id',
            'models_id' => 'required|integer|exists:repair_device_models,id',
            'color' => 'required|string|max:255',
            'plate_number' => 'required|string|max:255',
            'manufacturing_year' => 'required|string|max:4',
            'car_type' => 'nullable|string|max:255',
            'chassis_number' => 'nullable|string|max:255',
        ]);

        $modelBelongsToBrand = DB::table('repair_device_models')
            ->where('id', $validated['models_id'])
            ->where('device_id', $validated['device_id'])
            ->exists();

        if (!$modelBelongsToBrand) {
            return redirect()->back()->withErrors([
                'models_id' => __('checkcar::lang.model_does_not_belong_to_brand') ?? 'The selected model does not belong to the selected brand.',
            ]);
        }

        DB::table('contact_device')
            ->where('id', (int)$id)
            ->update([
                'device_id' => $validated['device_id'],
                'models_id' => $validated['models_id'],
                'color' => $validated['color'],
                'plate_number' => $validated['plate_number'],
                'manufacturing_year' => $validated['manufacturing_year'],
                'car_type' => $validated['car_type'] ?? null,
                'chassis_number' => $validated['chassis_number'] ?? null,
            ]);
        return redirect()->route('bookings.contact_devices')->with('success', __('lang_v1.success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $booking = DB::table('bookings')
                ->leftJoin('contacts', 'contacts.id', '=', 'bookings.contact_id')
                ->leftJoin('business_locations', 'business_locations.id', '=', 'bookings.location_id')
                ->leftJoin('types_of_services', 'types_of_services.id', '=', 'bookings.service_type_id')
                ->leftJoin('contact_device', 'contact_device.id', '=', 'bookings.device_id')
                ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
                ->select(
                    'bookings.*',
                    'contacts.name as contact_name',
                    'business_locations.name as location_name',
                    'types_of_services.name as type_name',
                    'contact_device.chassis_number as car_chassis_number',
                    'contact_device.color as car_color',
                    'contact_device.plate_number as car_plate_number',
                    'repair_device_models.name as device_name'
                )
                ->where('bookings.business_id', $business_id)
                ->where('bookings.id', $id)
                ->first();

            if (empty($booking)) {
                return response()->json(['error' => __('messages.not_found')], 404);
            }

            // Format dates for display
            $booking_start = $this->commonUtil->format_date($booking->booking_start, true);
            $booking_end = $this->commonUtil->format_date($booking->booking_end, true);

            // Define booking statuses
            $booking_statuses = [
                'waiting' => __('lang_v1.waiting'),
                'booked' => __('restaurant.booked'),
                'completed' => __('restaurant.completed'),
                'cancelled' => __('restaurant.cancelled'),
                'request' => __('Request'),
                'pickup_request' => __('Pickup Request'),
            ];

            // Return the view with the data
            return view('restaurant.booking.show', compact('booking', 'booking_start', 'booking_end', 'booking_statuses'));
        }

        return response()->json(['error' => __('messages.invalid_request')], 400);
    }

    public function edit($id)
    {
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Fetch booking with related data
        $booking = DB::table('bookings')
            ->leftJoin('contacts', 'contacts.id', '=', 'bookings.contact_id')
            ->leftJoin('business_locations', 'business_locations.id', '=', 'bookings.location_id')
            ->leftJoin('types_of_services', 'types_of_services.id', '=', 'bookings.service_type_id')
            ->leftJoin('contact_device', 'contact_device.id', '=', 'bookings.device_id')
            ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->select(
                'bookings.*',
                'contacts.id as contact_id',
                'contacts.name as contact_name',
                'business_locations.id as location_id',
                'business_locations.name as location_name',
                'types_of_services.id as service_type_id',
                'types_of_services.name as type_name',
                'contact_device.id as device_id',
                'contact_device.chassis_number as car_chassis_number',
                'contact_device.color as car_color',
                'contact_device.plate_number as car_plate_number',
                'repair_device_models.name as device_name',
                'bookings.booking_start as start_time',
                'bookings.booking_end as end_time'
            )
            ->where('bookings.business_id', $business_id)
            ->where('bookings.id', $id)
            ->first();


        if (!$booking) {
            return response()->json(['error' => 'Booking not found.'], 404);
        }

        // Fetch dropdown data
        $business_locations = BusinessLocation::forDropdown($business_id);
        $services = DB::table('types_of_services')->pluck('name', 'id');

        // Corrected models query
        $models = DB::table('contact_device')
            ->join('repair_device_models', 'contact_device.models_id', '=', 'repair_device_models.id')
            ->join('contacts', 'contact_device.contact_id', '=', 'contacts.id') // Join contacts table
            ->where('contact_device.contact_id', $booking->contact_id) // Filter by business_id in contacts

            ->pluck('repair_device_models.name', 'contact_device.id');

        return response()->json([
            'booking' => $booking,
            'business_locations' => $business_locations,
            'services' => $services,
            'models' => $models,

        ]);
    }

    public function update(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'id' => 'required|integer', // Ensure the booking ID is provided
            'contact_id' => 'required|integer',
            'car_model_id' => 'required|integer', // Correct field name
            'service_type_id' => 'required|integer', // Correct field name
            'location_id' => 'required|integer', // Correct validation rule
            'booking_start' => 'required|date',  // Validate booking start as a date

            'booking_note' => 'nullable|string|max:255',
            'send_notification' => 'nullable|boolean',
            'booking_status' => 'required|in:request,waiting,booked,completed,cancelled,pickup_request',
            'is_callback' => 'nullable|boolean', // Add validation for is_callback
            'call_back_ref' => 'nullable|string|max:255', // Add validation for callback reference
            'job_estimator_id' => 'nullable|integer',
        ]);

        // Fetch the booking instance
             $booking = Booking::findOrFail($validated['id']);
             $previous_status = $booking->booking_status;
             $FcmUser=FcmToken::where('user_id',$request->user_id)->first();
             $fcmtoken=$FcmUser->token;
             // Update the booking
             $booking->update([
            'contact_id' => $validated['contact_id'],
            'device_id' => $validated['car_model_id'], // Correct field name
            'service_type_id' => $validated['service_type_id'], // Correct field name
            'location_id' => $validated['location_id'],
            'booking_start' => $validated['booking_start'],
            'booking_end' =>  $booking->booking_end,
            'booking_note' => $validated['booking_note'],
            'booking_status' => $validated['booking_status'] ?? $booking->booking_status,
            'send_notification' => $validated['send_notification'] ?? false, // Handle notification
            'is_callback' => $validated['is_callback'] ?? $booking->is_callback, // Add the is_callback field update
            'call_back_ref' => $validated['call_back_ref'] ?? $booking->call_back_ref, // Add the call_back_ref field update
            'job_estimator_id' => $validated['job_estimator_id'] ?? $booking->job_estimator_id,
        ]);


              $new_status = $validated['booking_status'] ?? $booking->booking_status;
                $messageText = 'اهلا ا/' . ($data_contact->name ?? '') . 
                    ', تم تعديل حالة حجزك إلى: ' . ucfirst($new_status);

            $data = [
                'title'       => 'تحديث حالة الحجز',
                'description' => $messageText,
                'image'       => '', 
                'order_id'    => (string)$booking->id, 
                'type'        => 'booking_updates',
            ];

        FirebaseHelper::send_push_notif_to_device($fcmtoken, $data,  false );
        // Optionally send SMS notification after update
        try {
            if (!empty($validated['send_notification']) && $validated['send_notification']) {
                $data_contact = DB::table('contacts')
                    ->where('id', $validated['contact_id'])
                    ->select('id', 'mobile', 'name')
                    ->first();
                if (!empty($data_contact) && !empty($data_contact->mobile)) {
                    $location_name = DB::table('business_locations')
                        ->where('id', $validated['location_id'])
                        ->value('name');

                    $start_at = Carbon::parse($validated['booking_start'])->toDateTimeString();

                    // If previous status was 'request' and now it's 'booked', send confirmation message
                    $new_status = $validated['booking_status'] ?? $booking->booking_status;
                    if ($previous_status === 'request' && $new_status === 'booked') {
                        $message = 'اهلا ا/' . ($data_contact->name ?? '') . ' لقد تم تأكيد حجزك يوم ' . $start_at . ' في ' . ($location_name ?? '');
                    } else {
                        // Keep existing edit message for other cases
                        $message = 'اهلا ا/' . ($data_contact->name ?? '') . ' تم تعديل الحجز ليصبح في يوم ' . $start_at . ' في ' . ($location_name ?? '');
                    }

                    $smsSent = SmsUtil::sendEpusheg($data_contact->mobile, $message);
                    
                    // Log SMS with provider balance
                    SmsLog::create([
                        'contact_id' => $data_contact->id,
                        'transaction_id' => null,
                        'job_sheet_id' => null,
                        'mobile' => $data_contact->mobile,
                        'message_content' => $message,
                        'status' => (is_array($smsSent) && $smsSent['success']) ? 'sent' : 'failed',
                        'error_message' => (is_array($smsSent) && $smsSent['success']) ? null : 'Failed to send SMS',
                        'provider_balance' => is_array($smsSent) ? $smsSent['balance'] : SmsUtil::getLastNetBalance(),
                        'sent_at' => (is_array($smsSent) && $smsSent['success']) ? now() : null,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send booking update SMS', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Return response
        return back()->with('message', 'Booking updated successfully.');
    }

    public function update_status(Request $request, $id)
    {
        // Get the business_id from the session
        $business_id = $request->session()->get('user.business_id');

        // Validate the input
        $request->validate([
            'booking_status' => 'required|in:waiting,booked,completed,cancelled,pickup_request', // Ensure valid status
        ]);

        try {
            // Find the booking by ID and business ID
            $booking = Booking::where('business_id', $business_id)->where('id', $id)->first();

            // Check if the booking exists
            if (!$booking) {
                return response()->json(['error' => __('messages.not_found')], 404);
            }

            // Update the booking status
            $booking->booking_status = $request->input('booking_status');
            $booking->save();

            // Return success response
            return response()->json(['success' => __('messages.updated_successfully')]);
        } catch (\Exception $e) {
            // Return error response on failure
            return response()->json(['error' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
                return response()->json(['success' => false, 'msg' => 'Unauthorized action.'], 403);
            }

            DB::beginTransaction();

            $booking = Booking::where('business_id', $business_id)
                ->where('id', $id)
                ->first();

            if (!$booking) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ], 404);
            }

            $jobSheetIds = DB::table('repair_job_sheets')
                ->where('booking_id', $booking->id)
                ->pluck('id')
                ->values();

            $transactionIds = collect([]);
            $paymentIds = collect([]);

            if ($jobSheetIds->isNotEmpty()) {
                $transactionIds = DB::table('transactions')
                    ->whereIn('repair_job_sheet_id', $jobSheetIds)
                    ->pluck('id')
                    ->values();

                if ($transactionIds->isNotEmpty()) {
                    $paymentIds = DB::table('transaction_payments')
                        ->whereIn('transaction_id', $transactionIds)
                        ->pluck('id')
                        ->values();
                }
            }

            $inspectionIds = DB::table('checkcar_inspections')
                ->where('booking_id', $booking->id)
                ->pluck('id')
                ->values();

            if ($inspectionIds->isNotEmpty()) {
                DB::table('checkcar_inspection_items')
                    ->whereIn('inspection_id', $inspectionIds)
                    ->delete();

                DB::table('checkcar_inspection_documents')
                    ->whereIn('inspection_id', $inspectionIds)
                    ->delete();

                DB::table('checkcar_inspections')
                    ->whereIn('id', $inspectionIds)
                    ->delete();
            }

            if ($jobSheetIds->isNotEmpty()) {
                DB::table('product_joborder')
                    ->whereIn('job_order_id', $jobSheetIds)
                    ->delete();

                DB::table('maintenance_note')
                    ->whereIn('job_sheet_id', $jobSheetIds)
                    ->delete();

                DB::table('media')
                    ->where('model_type', '=', \Modules\Repair\Entities\JobSheet::class)
                    ->whereIn('model_id', $jobSheetIds)
                    ->delete();
            }

            DB::table('media')
                ->where('model_type', '=', \App\Restaurant\Booking::class)
                ->where('model_id', $booking->id)
                ->delete();

            if ($transactionIds->isNotEmpty()) {
                $transactionUtil = app(TransactionUtil::class);

                foreach ($transactionIds as $transactionId) {
                    $transaction = \App\Transaction::where('id', $transactionId)
                        ->where('business_id', $business_id)
                        ->first();

                    if (empty($transaction)) {
                        continue;
                    }

                    if (in_array($transaction->type, ['sell', 'sales_order'])) {
                        $output = $transactionUtil->deleteSale($business_id, $transaction->id);
                        if (empty($output['success'])) {
                            throw new \Exception($output['msg'] ?? 'Unable to delete transaction');
                        }
                    } else {
                        $transaction->delete();
                    }
                }

                DB::table('account_transactions')
                    ->whereIn('transaction_id', $transactionIds)
                    ->delete();

                if ($paymentIds->isNotEmpty()) {
                    DB::table('account_transactions')
                        ->whereIn('transaction_payment_id', $paymentIds)
                        ->delete();
                }
            }

            if ($jobSheetIds->isNotEmpty()) {
                DB::table('repair_job_sheets')
                    ->whereIn('id', $jobSheetIds)
                    ->delete();
            }

            $booking->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => trans('lang_v1.deleted_success'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    /**
     * Retrieves todays bookings
     *
     * @param  \App\Booking  $booking
     * @return \Illuminate\Http\Response
     */


    public function getTodaysBookings()
    {
        // Check user permissions
        if (!auth()->user()->can('crud_all_bookings') && !auth()->user()->can('crud_own_bookings')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');
            $today = Carbon::now()->format('Y-m-d');

            // Base query using DB::table and inner joins
            $query = DB::table('bookings')
                ->join('contacts', 'bookings.contact_id', '=', 'contacts.id') // Join for customer
                ->leftJoin('business_locations', 'bookings.location_id', '=', 'business_locations.id') // Join for location
                ->leftJoin('users as created_by_user', 'bookings.created_by', '=', 'created_by_user.id') // Join for created_by user
                ->leftJoin('types_of_services', 'bookings.service_type_id', '=', 'types_of_services.id') // Join for type of service
                ->where('bookings.business_id', $business_id)
                ->whereDate('bookings.booking_start', $today)
                ->select(
                    'bookings.id',
                    'bookings.booking_name',
                    'bookings.booking_start',
                    'bookings.is_callback',
                    // 'bookings.car_type',
                    'bookings.booking_status',

                    'types_of_services.name as service_type_name', // Add service type name
                    'contacts.name as customer_name',
                    'business_locations.name as location_name',
                    'created_by_user.first_name as created_by_name'
                );

            // Filter by location if provided
            if (!empty(request()->location_id)) {
                $query->where('bookings.location_id', request()->location_id);
            }

            // Apply permitted locations filter for non-admin users
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('bookings.location_id', $permitted_locations);
            }

            // Restrict bookings for non-admin users
            if (!auth()->user()->hasPermissionTo('crud_all_bookings') && !$this->commonUtil->is_admin(auth()->user(), $business_id)) {
                $query->where(function ($query) use ($user_id) {
                    $query->where('bookings.created_by', $user_id)
                        ->orWhere('bookings.correspondent_id', $user_id);
                });
            }

            $is_admin = $this->commonUtil->is_admin(auth()->user(), $business_id);

            return Datatables::of($query)

                ->editColumn('customer_name', function ($row) {
                    return $row->customer_name ?? '--';
                })
                ->editColumn('booking_name', function ($row) {
                    return $row->booking_name ?? '--';
                })
                // ->editColumn('car_type', function ($row) {
                //     return $row->car_type ?? '--';
                // })
                ->editColumn('created_by_name', function ($row) {
                    return $row->created_by_name ?? '--';
                })
                ->editColumn('service_type_name', function ($row) {
                    return $row->service_type_name ?? '--';
                })
                ->editColumn('location_name', function ($row) {
                    return $row->location_name ?? '--';
                })
                ->editColumn('booking_status', function ($row) {
                    return $row->booking_status ?? '--';
                })
                ->editColumn('is_callback', function ($row) {
                    return $row->is_callback == true ? 'callback' : '';
                })

                ->editColumn('booking_start', function ($row) {
                    return $row->booking_start ? $this->commonUtil->format_date($row->booking_start, true) : '--';
                })

                ->addColumn('action', function ($row) use ($is_admin) {

                    $editUrl = '/bookings/' . $row->id . '/edit'; // Direct URL for edit
                    $deleteUrl = '/bookings/' . $row->id; // Direct URL for delete


                    $html = '<div class="btn-group">
                                <button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                    ' . __('messages.action') . '

                                </button>';

                    $html .= '<ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    // Edit Button
                    if (auth()->user()->can('crud_all_bookings') || auth()->user()->can('crud_own_bookings')) {
                        $html .= '<li>
                        <a href="#" class="cursor-pointer edit-booking" data-id="' . $row->id . '">
                            <i class="fa fa-edit"></i> ' . __('messages.edit') . '
                        </a>
                    </li>';
                    }

                    // Delete Button
                    if ($is_admin) {
                        $html .= '<li>
                                    <a href="#" data-href="' . $deleteUrl . '" class="cursor-pointer delete-booking">
                                        <i class="fa fa-trash"></i> ' . __('messages.delete') . '
                                    </a>
                                </li>';
                    }

                    $html .= '</ul>
                            </div>';

                    return $html;
                })

                ->rawColumns(['action']) // Render HTML in the action column
                ->make(true);
        }
    }

    /**
     * Get job sheet reference numbers for callback functionality
     * Returns all job sheet numbers for a specific car/customer with dates
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobSheetReferences(Request $request)
    {
        try {
            $validated = $request->validate([
                'contact_id' => 'required|integer',
                'device_id' => 'required|integer', // car/device ID
            ]);

            $business_id = request()->session()->get('user.business_id');

            // Get all job sheets for this customer and car
            $jobSheets = DB::table('repair_job_sheets')
                ->leftJoin('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
                ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')
                ->leftJoin('repair_device_models', 'contact_device.models_id', '=', 'repair_device_models.id')
                ->leftJoin('categories as brand', 'brand.id', '=', 'contact_device.device_id')
                ->where('repair_job_sheets.business_id', $business_id)
                ->where('bookings.contact_id', $validated['contact_id'])
                ->where('bookings.device_id', $validated['device_id'])
                ->select(
                    'repair_job_sheets.id',
                    'repair_job_sheets.job_sheet_no',
                    'repair_job_sheets.created_at',
                    'repair_job_sheets.delivery_date',
                    'repair_device_models.name as model_name',
                    'brand.name as brand_name',
                    'contact_device.plate_number'
                )
                ->orderBy('repair_job_sheets.created_at', 'desc')
                ->get();

            // Format the data for dropdown
            $formattedJobSheets = $jobSheets->map(function ($jobSheet) {
                return [
                    'id' => $jobSheet->id, // Use job sheet ID instead of job sheet number
                    'text' => $jobSheet->job_sheet_no . ' - ' . 
                             $this->commonUtil->format_date($jobSheet->created_at) . 
                             ($jobSheet->delivery_date ? ' (Delivered: ' . $this->commonUtil->format_date($jobSheet->delivery_date) . ')' : ''),
                    'created_date' => $jobSheet->created_at,
                    'delivery_date' => $jobSheet->delivery_date,
                    'car_info' => ($jobSheet->brand_name ?? '') . ' ' . ($jobSheet->model_name ?? '') . 
                                 ($jobSheet->plate_number ? ' - ' . $jobSheet->plate_number : '')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedJobSheets
            ]);

        } catch (\Exception $e) {
            Log::error("Error in getJobSheetReferences: {$e->getMessage()}", [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching job sheet references'
            ], 500);
        }
    }

}