<?php

namespace App\Http\Controllers\Restaurant;

use App\User;
use App\Contact;
use Carbon\Carbon;
use App\Utils\Util;
use App\CustomerGroup;
use App\BusinessLocation;
use App\Business;
use App\TypesOfService;
use App\Restaurant\JobEstimator;
use Illuminate\Http\Request;
use App\Utils\RestaurantUtil;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Modules\Repair\Entities\MaintenanceNote;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Utils\SmsUtil;
use App\Transaction;
use App\TransactionPayment;
use Modules\Sms\Entities\SmsLog;

class JobEstimatorController extends Controller
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

    private function createEstimatorAdvancePayment(int $contactId, float $amount, JobEstimator $estimator, int $locationId): void
    {
        $businessId = session('business.id');
        $userId = auth()->id();

        $paymentRef = $this->generateAdvancePaymentRef($businessId);

        // Create a draft transaction for the estimator
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

        // Create the advance payment linked to the draft transaction
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

        // \DB::table('contact_ledgers')->insert([
        //     'contact_id' => $contactId,
        //     'type' => 'debit',
        //     'transaction_payment_id' => $payment->id,
        //     'amount' => $amount,
        //     'created_at' => $paidOn,
        //     'updated_at' => $paidOn,
        //     'location_id' => $locationId,
        // ]);
    }

    private function generateAdvancePaymentRef(?int $businessId): string
    {
        $prefix = 'ADV';
        if (! empty($businessId)) {
            $prefix .= '-' . $businessId;
        }

        do {
            $reference = $prefix . '-' . strtoupper(Str::random(6));
        } while (TransactionPayment::where('payment_ref_no', $reference)->exists());

        return $reference;
    }

    /**
     * Generate a unique estimate number starting with ES.
     */
    private function generateEstimateNo(): string
    {
        $business_id = request()->session()->get('user.business_id');

        $ref_count = $this->commonUtil->setAndGetReferenceCount('job_estimator', $business_id);

        $estimate_prefix = 'ES';

        return $this->commonUtil->generateReferenceNumber('job_estimator', $ref_count, $business_id, $estimate_prefix);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    

        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->has('user_id') ? request()->user_id : null;
        $permitted_locations = auth()->user()->permitted_locations();

        if (request()->ajax()) {
            $estimators = JobEstimator::with(['customer', 'location', 'creator'])
                ->where('business_id', $business_id)
                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('created_by', $user_id);
                })
                ->when($permitted_locations != 'all', function ($query) use ($permitted_locations) {
                    return $query->whereIn('location_id', $permitted_locations);
                });

            return DataTables::of($estimators)
                ->addColumn('action', function ($row) {
                    $actions = [];

                    $actions[] = '<li>
                        <a href="#" class="cursor-pointer view-estimator" data-id="' . $row->id . '">
                            <i class="fa fa-eye"></i> ' . __('messages.view') . '
                        </a>
                    </li>';

                    if (auth()->user()->can('crud_all_job_estimators') || (auth()->user()->can('crud_own_job_estimators') && auth()->id() === $row->created_by && $row->estimator_status === 'pending')) {
                        $actions[] = '<li>
                            <a href="#" class="cursor-pointer edit-estimator" data-id="' . $row->id . '">
                                <i class="fa fa-edit"></i> ' . __('messages.edit') . '
                            </a>
                        </li>';
                    }

                    $actions[] = '<li>
                        <a href="' . action([\App\Http\Controllers\Restaurant\JobEstimatorController::class, 'printEstimate'], [$row->id]) . '" target="_blank">
                            <i class="fa fa-print"></i> ' . __('repair::lang.print_estimate') . '
                        </a>
                    </li>';

                    if ($row->customer && $row->customer->mobile) {
                        $actions[] = '<li>
                            <a href="#" class="cursor-pointer send-estimator-sms" data-id="' . $row->id . '" data-mobile="' . e($row->customer->mobile) . '" data-name="' . e($row->customer->name) . '">
                                <i class="fa fa-comment"></i> ' . __('restaurant.send_sms_to_customer') . '
                            </a>
                        </li>';
                    }

                    if (empty($actions)) {
                        return '';
                    }

                    $html = '<div class="btn-group">
                        <button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">'
                            . __('messages.action') .
                        '</button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">'
                            . implode('', $actions) .
                        '</ul>
                    </div>';

                    return $html;
                })
                ->addColumn('customer_name', function ($row) {
                    return $row->customer ? $row->customer->name : '';
                })
                ->addColumn('vehicle_info', function ($row) {
                    $device = DB::table('contact_device')->where('id', $row->device_id)->first();
                    if ($device) {
                        $model = DB::table('repair_device_models')->where('id', $device->models_id)->value('name');
                        return $model ?: '';
                    }
                    return '';
                })
                ->addColumn('location_name', function ($row) {
                    return $row->location ? $row->location->name : '';
                })
                ->addColumn('created_by_name', function ($row) {
                    return $row->creator ? $row->creator->first_name . ' ' . $row->creator->last_name : '';
                })
                ->addColumn('amount', function ($row) {
                    return $row->amount !== null ? $this->commonUtil->num_f($row->amount) : '';
                })
                ->addColumn('status_badge', function ($row) {
                    $status_colors = [
                        'pending' => 'warning',
                        'sent' => 'info',
                        'replied' => 'success',
                        'rejected' => 'danger',
                        'booked' => 'primary'
                    ];
                    $color = $status_colors[$row->estimator_status] ?? 'default';
                    return '<span class="label bg-' . $color . '">' . $row->status_label . '</span>';
                })
                ->filterColumn('customer_name', function ($query, $keyword) {
                    $query->whereHas('customer', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['action', 'status_badge'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $services = DB::table('types_of_services')->pluck('name', 'id');

        return view('restaurant.job_estimator.index', compact('business_locations', 'services'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('crud_all_job_estimators') && !auth()->user()->can('crud_own_job_estimators')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'contact_id' => 'required|integer',
            'device_id' => 'required|integer',
            'location_id' => 'required|integer',
            'service_type_id' => 'nullable|integer',
            'vehicle_details' => 'nullable|string|max:1000',
            'amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $business_id = session('user.business_id');
            $user_id = auth()->user()->id;

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

            // Generate estimate number if not provided
            $data['estimate_no'] = $this->generateEstimateNo();

            
            $estimator = JobEstimator::create($data);

            // Treat estimator amount as customer advance balance instead of creating a quotation
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
                    \Log::warning('Failed to record estimator advance payment', [
                        'estimator_id' => $estimator->id,
                        'contact_id' => $validated['contact_id'],
                        'error' => $txEx->getMessage(),
                    ]);
                }
            }

            // Create a maintenance note immediately linked to this estimator (no job sheet)
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
                \Log::warning('Failed to create maintenance note on estimator create', [
                    'estimator_id' => $estimator->id,
                    'error' => $ex->getMessage(),
                ]);
            }

            // Send SMS if requested
            if ($validated['send_notification_value'] == 1) {
                $contact = DB::table('contacts')->where('id', $validated['contact_id'])->select('id', 'mobile', 'name')->first();
                if ($contact && $contact->mobile) {
                    $location_name = DB::table('business_locations')->where('id', $validated['location_id'])->value('name');
                    $message = 'اهلا ا/' . $contact->name . ' تم ارسال مقايسة صيانة لمركبتك من ' . $location_name;
                    
                    $smsResult = SmsUtil::sendEpusheg($contact->mobile, $message);
                    $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;
                    
                    // Log SMS
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

            $output = [
                'success' => true,
                'msg' => trans('lang_v1.added_success'),
                'send_notification' => $validated['send_notification_value'] == 1,
            ];

            if ($validated['send_notification_value'] == 1) {
                $output['notification_url'] = action([NotificationController::class, 'getTemplate'], [
                    'transaction_id' => $estimator->id,
                    'template_for' => 'new_estimator'
                ]);
            }

            return $output;
        } catch (\Exception $e) {
            Log::error("Error in store job estimator: {$e->getMessage()}", [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $estimator = JobEstimator::with(['customer', 'location', 'creator', 'serviceType'])
                ->where('business_id', $business_id)
                ->where('id', $id)
                ->first();

            if (empty($estimator)) {
                return response()->json(['error' => __('messages.not_found')], 404);
            }

            // Get device model name
            $device_model = null;
            if ($estimator->device_id) {
                $device = DB::table('contact_device')->where('id', $estimator->device_id)->first();
                if ($device) {
                    $device_model = DB::table('repair_device_models')->where('id', $device->models_id)->first();
                }
            }

            // Fetch product_joborder lines for this estimator
            $lines = DB::table('product_joborder as pjo')
                ->leftJoin('products', 'products.id', '=', 'pjo.product_id')
                ->leftJoin('units', 'units.id', '=', 'products.unit_id')
                ->leftJoin('contacts as suppliers', 'suppliers.id', '=', 'pjo.supplier_id')
                ->where('pjo.job_estimator_id', $id)
                ->select([
                    'pjo.id as line_id',
                    'products.name as product_name',
                    'products.sku as sku',
                    'units.short_name as unit',
                    'pjo.quantity',
                    'pjo.price',
                    'pjo.purchase_price',
                    'pjo.supplier_id',
                    'suppliers.name as supplier_name',
                    'pjo.client_approval',
                    'pjo.Notes as notes',
                ])
                ->orderBy('pjo.id', 'desc')
                ->get();

            return view('restaurant.job_estimator.show', compact('estimator', 'device_model', 'lines'));
        }

        return response()->json(['error' => __('messages.invalid_request')], 400);
    }

    /**
     * Update client approval for a single product_joborder line.
     */
    public function updateLineApproval(Request $request, $id)
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'client_approval' => 'required|in:0,1',
        ]);

        $business_id = request()->session()->get('user.business_id');

        $line = DB::table('product_joborder')->where('id', (int)$id)->first();
        if (!$line) {
            return response()->json(['success' => false, 'msg' => __('messages.not_found')], 404);
        }

        // Ensure the line belongs to an estimator in the same business
        if ($line->job_estimator_id) {
            $estimator = JobEstimator::where('business_id', $business_id)
                ->where('id', $line->job_estimator_id)
                ->first();
            if (!$estimator) {
                return response()->json(['success' => false, 'msg' => __('messages.unauthorized_action')], 403);
            }

        }
        

        DB::table('product_joborder')
            ->where('id', $line->id)
            ->update(['client_approval' => (int)$validated['client_approval']]);

        // Mark estimator as replied when any line is updated
        if (!empty($estimator) && $estimator->estimator_status !== 'booked') {
            $estimator->update(['estimator_status' => 'replied']);
        }

        return response()->json([
            'success' => true,
            'msg' => __('lang_v1.updated_success'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('crud_all_job_estimators') && !auth()->user()->can('crud_own_job_estimators')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $estimator = JobEstimator::with('customer')
    
            ->where('id', $id)
            ->first();

        if (!$estimator) {
            return response()->json(['error' => 'Job estimator not found.'], 404);
        }

        // Fetch dropdown data
        $business_locations = BusinessLocation::forDropdown($business_id);
        $services = DB::table('types_of_services')->pluck('name', 'id');

        // Get customer devices
        $devices = DB::table('contact_device')
            ->join('repair_device_models', 'contact_device.models_id', '=', 'repair_device_models.id')
            ->where('contact_device.contact_id', $estimator->contact_id)
            ->pluck('repair_device_models.name', 'contact_device.id');

        return view('restaurant.job_estimator.edit', [
            'estimator' => $estimator,
            'business_locations' => $business_locations,
            'services' => $services,
            'devices' => $devices,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('crud_all_job_estimators') && !auth()->user()->can('crud_own_job_estimators')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'contact_id' => 'required|integer',
            'device_id' => 'required|integer',
            'location_id' => 'required|integer',
            'service_type_id' => 'nullable|integer',
            'vehicle_details' => 'nullable|string|max:1000',
            'amount' => 'nullable|numeric|min:0',
            'payment_image' => 'nullable|image|max:2048',
            'estimator_status' => 'required|string|in:pending,sent,replied,rejected,booked',
            'send_notification' => 'nullable|boolean',
        ]);

        try {
            $business_id = session('user.business_id');
            $estimator = JobEstimator::where('business_id', $business_id)
                ->where('id', $id)
                ->first();

            if (!$estimator) {
                return response()->json(['error' => 'Job estimator not found.'], 404);
            }

            // Capture previous status
            $previous_status = $estimator->estimator_status;

            $data = [
                'contact_id' => $validated['contact_id'],
                'device_id' => $validated['device_id'],
                'location_id' => $validated['location_id'],
                'service_type_id' => $validated['service_type_id'] ?? null,
                'vehicle_details' => $validated['vehicle_details'] ?? null,
                'amount' => $validated['amount'] ?? null,
                'estimator_status' => $validated['estimator_status'],
            ];

            $estimator->update($data);

            
                    
                // Ensure a maintenance note exists for purchase request flow linked to estimator
                try {
                    MaintenanceNote::updateOrCreate(
                        [
                            'job_estimator_id' => $estimator->id,
                            'category_status' => 'purchase_req',
                        ],
                        [
                            'job_sheet_id' => null,
                            'created_by' => auth()->id(),
                            'device_id' => $estimator->device_id,
                            'status' => 'awaiting_reply',
                            'content' => isset($validated['amount']) && $validated['amount'] !== null
                                ? __('restaurant.amount') . ': ' . $validated['amount']
                                : $estimator->vehicle_details,
                        ]
                    );
                } catch (\Throwable $ex) {
                    \Log::warning('Failed to create maintenance note for estimator approval', [
                        'estimator_id' => $estimator->id,
                        'error' => $ex->getMessage(),
                    ]);
                }
        
            

            // Send SMS if requested and status is sent
            if ($validated['send_notification'] && $validated['estimator_status'] === 'sent') {
                $contact = DB::table('contacts')->where('id', $validated['contact_id'])->select('id', 'mobile', 'name')->first();
                if ($contact && $contact->mobile) {
                    $location_name = DB::table('business_locations')->where('id', $validated['location_id'])->value('name');
                    $message = 'اهلا ا/' . $contact->name . ' تم ارسال مقايسة صيانة لمركبتك من ' . $location_name;
                    
                    $smsResult = SmsUtil::sendEpusheg($contact->mobile, $message);
                    $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;
                    
                    // Log SMS
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
                }
            }

            return response()->json([
                'success' => true,
                'msg' => trans('lang_v1.updated_success'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error in update job estimator: {$e->getMessage()}", [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('crud_all_job_estimators')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $estimator = JobEstimator::where('business_id', $business_id)
                ->where('id', $id)
                ->first();

            if (!$estimator) {
                return response()->json(['error' => 'Job estimator not found.'], 404);
            }

            // Check if estimator can be deleted (only pending status)
            if ($estimator->estimator_status == 'booked') {
                return response()->json(['error' => 'Cannot delete estimator that is in booked status.'], 400);
            }

            $estimator->delete();

            return response()->json([
                'success' => true,
                'msg' => trans('lang_v1.deleted_success'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error in delete job estimator: {$e->getMessage()}", [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Send estimator SMS to the associated customer.
     */
    public function sendSms(Request $request)
    {
        if (!auth()->user()->can('crud_all_job_estimators') && !auth()->user()->can('crud_own_job_estimators')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'estimator_id' => 'required|integer',
        ]);

        $estimator = JobEstimator::with(['customer', 'location'])
            ->find($validated['estimator_id']);

        if (!$estimator || !$estimator->customer || empty($estimator->customer->mobile)) {
            return response()->json([
                'success' => false,
                'msg' => __('restaurant.customer_mobile_missing'),
            ], 422);
        }

        try {
            $location_name = $estimator->location ? $estimator->location->name : __('restaurant.our_service_center');

            // Build verification link for estimator details via API
            $business = Business::first();
            $commonSettings = $business ? $business->common_settings : [];
            $customerAppDomain = rtrim($commonSettings['customer_app_domain'] ?? '', '/');

          

            $verification_url = $customerAppDomain . '/check/phone/estimator/' . $estimator->id;

            // Compose SMS with a direct link
            $message = 'اهلا ا/' . $estimator->customer->name . ' تم ارسال مقايسة صيانة لمركبتك من ' . $location_name . '، يمكنك الاطلاع عليها عبر الرابط: ' . $verification_url;

            SmsUtil::sendEpusheg($estimator->customer->mobile, $message);

            $estimator->sent_to_customer_at = now();
            $estimator->save();

            return response()->json([
                'success' => true,
                'msg' => __('restaurant.sms_sent_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send estimator SMS', [
                'estimator_id' => $estimator->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    /**
     * Prints the job estimator estimate
     *
     * @return Response
     */
    public function printEstimate($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $estimator = JobEstimator::with(['customer', 'location', 'creator', 'serviceType', 'device'])
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->firstOrFail();

        $job_order_lines = DB::table('product_joborder')
            ->leftJoin('products', 'product_joborder.product_id', '=', 'products.id')
            ->where('product_joborder.job_estimator_id', $id)
            ->select(
                'product_joborder.*',
                'products.name as product_name',
                'products.enable_stock'
            )
            ->get();

        // Get brand and model names
        $brand = '';
        $repair_model = '';
        if (!empty($estimator->device)) {
             $brand = optional($estimator->device->deviceCategory)->name ?? '';
             $repair_model = optional($estimator->device->deviceModel)->name ?? '';
        }

        $business_details = Business::find($business_id);
        
        return view('restaurant.job_estimator.receipts.estimate')
            ->with(compact('estimator', 'business_details', 'job_order_lines', 'brand', 'repair_model'));
    }
}
