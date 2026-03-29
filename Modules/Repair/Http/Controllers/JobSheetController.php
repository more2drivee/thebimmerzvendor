<?php

namespace Modules\Repair\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\BusinessLocation;
use App\Contact;
use App\Business;

use App\ContactDevice;
use App\Media;
use App\Product;
use App\Category;
use App\Brands;
use App\Transaction;
use App\TransactionSellLine;
use App\CustomerGroup;
use FFMpeg\Media\Video;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use App\Utils\ContactUtil;
use App\Utils\ProductUtil;
use App\Restaurant\Booking;
use Illuminate\Http\Request;
use FFMpeg\Format\Video\X264;
use Illuminate\Http\Response;
use App\Utils\CashRegisterUtil;
use FFMpeg\Coordinate\Dimension;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Modules\Repair\Utils\RepairUtil;
use Modules\Repair\Entities\JobSheet;
use Modules\CheckCar\Entities\CarInspection;

use Spatie\Activitylog\Models\Activity;
use Modules\Repair\Entities\DeviceModel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Modules\Repair\Entities\RepairStatus;
use App\Utils\TransactionUtil;

class JobSheetController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $repairUtil;

    protected $commonUtil;

    protected $cashRegisterUtil;

    protected $moduleUtil;

    protected $contactUtil;
    protected $productUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(RepairUtil $repairUtil, Util $commonUtil, CashRegisterUtil $cashRegisterUtil, ModuleUtil $moduleUtil,
        ContactUtil $contactUtil, ProductUtil $productUtil,TransactionUtil $transactionUtil)
    {
        $this->repairUtil = $repairUtil;
        $this->commonUtil = $commonUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        
        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && (auth()->user()->can('job_sheet.view_assigned') || auth()->user()->can('job_sheet.view_all') || auth()->user()->can('job_sheet.create'))))) {
            abort(403, 'Unauthorized action.');
        }
 
        $is_user_admin = $this->commonUtil->is_admin(auth()->user(), $business_id);
        

       
        $job_sheets = JobSheet::where('repair_job_sheets.business_id', $business_id)
            ->leftJoin('contacts', 'repair_job_sheets.contact_id', '=', 'contacts.id')
            ->leftJoin('repair_statuses AS rs', 'repair_job_sheets.status_id', '=', 'rs.id')
            ->leftJoin('business_locations AS bl', 'repair_job_sheets.location_id', '=', 'bl.id')
            ->leftJoin('bookings', 'repair_job_sheets.booking_id', '=', 'bookings.id')
            ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')
            ->leftJoin('categories AS device', 'contact_device.device_id', '=', 'device.id')
            ->leftJoin('repair_device_models AS rdm', 'contact_device.models_id', '=', 'rdm.id')
            ->leftJoin('users AS created_by_user', 'repair_job_sheets.created_by', '=', 'created_by_user.id')
            ->with([
                'booking',
                'booking.serviceType',
                'invoices'
            ])
            ->where('repair_job_sheets.business_id', $business_id);


        if (request()->ajax()) {
            

            //if user is not admin get only assgined/created_by job sheet
            if (! auth()->user()->can('job_sheet.view_all')) {
                if (! $is_user_admin) {
                    $user_id = auth()->user()->id;
                    $job_sheets->where(function ($query) use ($user_id) {
                        $query->where('repair_job_sheets.service_staff', $user_id)
                            ->orWhere('repair_job_sheets.created_by', $user_id);
                    });
                }
            }

            //if location is not all get only assgined location job sheet
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $job_sheets->whereIn('repair_job_sheets.location_id', $permitted_locations);
            }

            //filter location
            if (! empty(request()->get('location_id'))) {
                $job_sheets->where('repair_job_sheets.location_id', request()->get('location_id'));
            }

            //filter by customer
            if (! empty(request()->contact_id)) {
                $job_sheets->where('repair_job_sheets.contact_id', request()->contact_id);
            }

            // filter by technician (service_staff JSON or legacy single ID)
            if (! empty(request()->technician)) {
                $technicianId = (int) request()->technician;
                $job_sheets->where(function ($q) use ($technicianId) {
                    // New JSON format: service_staff is a JSON array of user IDs
                    $q->where(function ($sub) use ($technicianId) {
                        $sub->whereRaw('JSON_VALID(repair_job_sheets.service_staff)')
                            ->whereRaw('JSON_CONTAINS(repair_job_sheets.service_staff, ?)', [json_encode($technicianId)]);
                    })
                    // Legacy plain ID format (non-JSON)
                    ->orWhere('repair_job_sheets.service_staff', (string) $technicianId);
                });
            }

            //filter by status
            if (! empty(request()->status_id)) {
                $job_sheets->where('repair_job_sheets.status_id', request()->status_id);
            }

            //filter out mark as completed status
            if (request()->get('is_completed_status') === '1') {
                $job_sheets->whereHas('invoices', function ($q) {
                    $q->where('status', 'final');
                });
            } else {
                $job_sheets->whereHas('invoices', function ($q) {
                    $q->where('status', 'under processing');
                });
            }
            $job_sheets->select(
                'repair_job_sheets.id',
                'repair_job_sheets.delivery_date',
                'repair_job_sheets.job_sheet_no',
                'repair_job_sheets.service_staff',
                'repair_job_sheets.created_at',
                'repair_job_sheets.estimated_cost',
                'repair_job_sheets.status_id',
                'repair_job_sheets.booking_id',
                'repair_job_sheets.created_by',
                'repair_job_sheets.contact_id',
                'repair_job_sheets.business_id',
                'repair_job_sheets.deleted_at',
                'contacts.name as contact_name',
                'contacts.mobile as contact_mobile',
                'rs.name as status_name',
                'rs.color as status_color',
                'bl.name as location_name',
                'device.name as device_name',
                'rdm.name as model_name',
                'contact_device.chassis_number as chassie_number',
                'contact_device.plate_number',
                'contacts.name as customer',
                'rs.name as status',
                'bl.name as location',
                'device.name as device',
                'rdm.name as device_model',
                DB::raw('CONCAT(COALESCE(created_by_user.first_name, ""), " ", COALESCE(created_by_user.last_name, "")) as added_by')
                )
                ->groupBy('repair_job_sheets.id')
                ->orderBy('repair_job_sheets.created_at', 'desc');
               
            return DataTables::of($job_sheets)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                                <button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-info tw-w-max dropdown-toggle" type="button"  data-toggle="dropdown" aria-expanded="false">
                                    '.__('messages.action').'
                                    <span class="caret"></span>
                                    <span class="sr-only">
                                    '.__('messages.action').'
                                    </span>
                                    </button>';

                    $html .= '<ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if (auth()->user()->can('job_sheet.view_assigned') || auth()->user()->can('job_sheet.view_all') || auth()->user()->can('job_sheet.create')) {
                        $html .= '<li>
                                <a href="'.action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'show'], [$row->id]).'" class="cursor-pointer"><i class="fa fa-eye"></i> '.__('messages.view').'
                                </a>
                                </li>';
                    }

             
                    if (auth()->user()->can('job_sheet.edit')) {
                        $html .= '<li>
                                    <a href="'.action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'edit'], [$row->id]).'" class="cursor-pointer edit_job_sheet"><i class="fa fa-edit"></i> '.__('messages.edit').'
                                    </a>
                                </li>';

                        $html .= '<li>
                                    <a href="'.action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'addParts'], [$row->id]).'" class="cursor-pointer">
                                        <i class="fas fa-toolbox"></i>
                                        '.__('repair::lang.add_parts').'
                                    </a>
                                </li>';

                        $html .= '<li>
                                    <a href="'.action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'getUploadDocs'], [$row->id]).'" class="cursor-pointer">
                                        <i class="fas fa-file-alt"></i>
                                        '.__('repair::lang.upload_docs').'
                                    </a>
                                </li>';
                        if (auth()->user()->can('customer.update') || auth()->user()->can('supplier.update')) {
                            $html .= '<li><a data-href="' . action([\App\Http\Controllers\ContactController::class, 'edit'], [$row->contact_id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-user-edit"></i> ' . __('contact.edit_contact') . '</a></li>';
                        }
                    }

                    $html .= '<li>
                                    <a href="'.action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'print'], [$row->id]).'" target="_blank"><i class="fa fa-print"></i> '.__('messages.print').'
                                    </a>
                            </li>';

                    if (auth()->user()->can('job_sheet.create') || auth()->user()->can('job_sheet.edit')) {
                        $html .= '<li>
                                    <a data-href="'.action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'editStatus'], [$row->id]).'" class="cursor-pointer edit_job_sheet_status">
                                        <i class="fa fa-edit"></i>
                                        '.__('repair::lang.change_status').'
                                    </a>
                                </li>';
                    }

                    if (auth()->user()->can('job_sheet.delete')) {
                        $is_deleted_flag = !empty($row->deleted_at) ? 1 : 0;
                        $html .= '<li>
                                    <a data-href="'.action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'destroy'], [$row->id]).'" data-is-deleted="'.$is_deleted_flag.'" class="cursor-pointer delete-job-sheet">
                                        <i class="fas fa-trash"></i>
                                        '.__('messages.delete').'
                                    </a>
                                </li>';
                    }

                    $html .= '</ul>
                            </div>';

                    return $html;
                })
                ->editColumn('delivery_date',
                    '
                        @if($delivery_date)
                            {{@format_datetime($delivery_date)}}
                        @endif
                    '
                )
                ->editColumn('created_at',
                    '
                    {{@format_datetime($created_at)}}
                    '
                )
                ->editColumn('service_type', function ($row) {
                    $service_type = $row->booking && $row->booking->serviceType ? $row->booking->serviceType->name : '';
                    return __($service_type);
                })
      
                ->addColumn('technecian', function ($row) {
                    // service_staff may be stored as JSON array or a single ID
                    $raw = $row->service_staff;

                    // Try to decode as JSON first (new format)
                    $decoded = null;
                    if (!is_null($raw) && $raw !== '') {
                        $decoded = json_decode($raw, true);
                    }

                    if (json_last_error() === JSON_ERROR_NONE && !is_null($decoded)) {
                        $service_staff_ids = $decoded;
                    } else {
                        // Fallback: treat raw value as a single ID
                        $service_staff_ids = $raw !== null && $raw !== '' ? [$raw] : [];
                    }

                    if (!is_array($service_staff_ids)) {
                        $service_staff_ids = [$service_staff_ids];
                    }

                    // Normalize to integer IDs and remove empties
                    $service_staff_ids = array_values(array_filter(array_map('intval', $service_staff_ids)));

                    if (!empty($service_staff_ids)) {
                        // Fetch full names "surname first_name last_name"
                        $names = DB::table('users')
                            ->whereIn('id', $service_staff_ids)
                            ->select(DB::raw("TRIM(CONCAT_WS(' ', COALESCE(surname, ''), COALESCE(first_name, ''), COALESCE(last_name, ''))) as full_name"))
                            ->pluck('full_name')
                            ->filter()
                            ->implode(', ');

                        return $names;
                    }

                    return '';
                })
                ->editColumn('estimated_cost', function ($row) {
                    $cost = '<span class="display_currency total-discount" data-currency_symbol="true" data-orig-value="'.$row->estimated_cost.'">'.$row->estimated_cost.'</span>';
                    
                    return $cost;
                })
                ->editColumn('repair_no', function ($row) {
                    $invoice_no = [];
                    if ($row->invoices->count() > 0) {
                        foreach ($row->invoices as $key => $invoice) {
                            $invoice_no[] = $invoice->invoice_no;
                        }
                    }

                    $add_invoice = '';
               

                    return implode(', ', $invoice_no).$add_invoice;
                })
                ->addColumn('is_deleted', function ($row) {
                    if (!empty($row->deleted_at)) {
                        return '<span class="label bg-red">' . __('lang_v1.yes') . '</span>';
                    } else {
                        return '<span class="label bg-green">' . __('lang_v1.no') . '</span>';
                    }
                })
                ->editColumn('status', function ($row) {
                    $status_name = $row->status_name;
                    $status_color = $row->status_color;
                    $html = '<a data-href="'.action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'editStatus'], [$row->id]).'" class="edit_job_sheet_status cursor-pointer" data-orig-value="'.$status_name.'" data-status-name="'.$status_name.'">
                                <span class="label " style="background-color:'.$status_color.';" >
                                    '.$status_name.'
                                </span>
                            </a>
                        ';
                    // Append deleted badge if this job sheet is soft deleted
                    if (!empty($row->deleted_at)) {
                        $html .= ' <small class="label bg-gray label-round no-print" title="'.e(__('lang_v1.deleted')).'"><i class="fas fa-trash"></i></small>';
                    }
                    return $html;
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'service_type', 'delivery_date', 'repair_no', 'status', 'is_deleted', 'estimated_cost', 'created_at'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $status_dropdown = RepairStatus::forDropdown($business_id);
        $service_staffs = $this->commonUtil->serviceStaffDropdown($business_id);

        $user_role_as_service_staff = auth()->user()->roles()
                            ->where('is_service_staff', 1)
                            ->get()
                            ->toArray();
        $is_user_service_staff = false;
        if (! empty($user_role_as_service_staff) && ! $is_user_admin) {
            $is_user_service_staff = true;
        }

        $repair_settings = $this->repairUtil->getRepairSettings($business_id);
        return view('repair::job_sheet.index')
        ->with(compact('business_locations', 'customers', 'status_dropdown', 'service_staffs', 'is_user_service_staff', 'repair_settings', 'is_user_admin'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
       
        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('job_sheet.create')))) {
            abort(403, 'Unauthorized action.');
        }
        
        $repair_statuses = RepairStatus::getRepairSatuses($business_id);
        $device_models = DeviceModel::forDropdown($business_id);
        $brands = Brands::forDropdown($business_id, false, true);
        $devices = Category::forDropdown($business_id, 'device');
        $repair_settings = $this->repairUtil->getRepairSettings($business_id);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $types = Contact::getContactTypes();
        $customer_groups = CustomerGroup::forDropdown($business_id);
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        $default_status = '';
        if (! empty($repair_settings['default_status'])) {
            $default_status = $repair_settings['default_status'];
        }
        
        //get service staff(technecians)
        $technecians = [];
        if ($this->commonUtil->isModuleEnabled('service_staff')) {
            $technecians = $this->commonUtil->serviceStaffDropdown($business_id);
        }
        // $bookings = collect(DB::select('select * from bookings'));
        $bookings = DB::table('bookings')
        ->where('booking_status','waiting')
        ->select('id', 'booking_name')->get();

        $workshops = DB::table('workshops')
        ->where('status', 'available')
        ->pluck('name', 'id');
    
        // Fetch all statuses in one query
        $note_list = RepairStatus::where('status_category', 'note')
        ->pluck('name', 'id');

     



        return view('repair::job_sheet.create')
        ->with(compact('bookings','workshops','repair_statuses','note_list', 'device_models', 'brands', 'devices', 'default_status', 'technecians', 'business_locations', 'types', 'customer_groups', 'walk_in_customer', 'repair_settings'));
    }

    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('job_sheet.edit')))) {
            abort(403, 'Unauthorized action.');
        }

        $job_sheet = JobSheet::where('business_id', $business_id)
            ->with(['booking', 'booking.serviceType'])
            ->findOrFail($id);

        $repair_statuses = RepairStatus::getRepairSatuses($business_id);
        $device_models = DeviceModel::forDropdown($business_id);
        $brands = Brands::forDropdown($business_id, false, true);
        $devices = Category::forDropdown($business_id, 'device');
        $repair_settings = $this->repairUtil->getRepairSettings($business_id);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $types = Contact::getContactTypes();
        $customer_groups = CustomerGroup::forDropdown($business_id);
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        $default_status = '';
        if (! empty($repair_settings['default_status'])) {
            $default_status = $repair_settings['default_status'];
        }

        $technecians = [];
        if ($this->commonUtil->isModuleEnabled('service_staff')) {
            $technecians = $this->commonUtil->serviceStaffDropdown($business_id);
        }

        $bookings = DB::table('bookings')
            ->join('contact_device', 'bookings.device_id', '=', 'contact_device.id')
            ->join('categories', 'contact_device.device_id', '=', 'categories.id')
            ->join('business_locations', 'bookings.location_id', '=', 'business_locations.id')
            ->join('contacts', 'bookings.contact_id', '=', 'contacts.id')
            ->leftJoin('types_of_services', 'bookings.service_type_id', '=', 'types_of_services.id')
            ->where('bookings.id', $job_sheet->booking_id)
            ->select(
                'bookings.id',
                'bookings.booking_name',
                'bookings.location_id',
                'bookings.contact_id',
                'bookings.booking_note',
                'bookings.service_type_id',
                'business_locations.name as location_name',
                'contacts.name as contact_name',
                'categories.name as device_name',
                'contact_device.device_id',
                'contact_device.models_id as device_model_id',
                'contact_device.chassis_number as car_chassis_number',
                'contact_device.plate_number as car_plate_number',
                'contact_device.color as car_color',
                'types_of_services.name as type_name'
            )
            ->get();

        $workshops = DB::table('workshops')
            ->where('status', 'available')
            ->pluck('name', 'id');

        $note_list = RepairStatus::where('status_category', 'note')
            ->pluck('name', 'id');

        return view('repair::job_sheet.edit')
            ->with(compact('job_sheet', 'bookings', 'workshops', 'repair_statuses', 'note_list', 'device_models', 'brands', 'devices', 'default_status', 'technecians', 'business_locations', 'types', 'customer_groups', 'walk_in_customer', 'repair_settings'));
    }

    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('job_sheet.edit')))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $job_sheet = JobSheet::where('business_id', $business_id)
                ->findOrFail($id);

            $filteredData = $request->only([
                'km',
                'car_condition',
                'entry_date',
                'start_date',
                'delivery_date',
                'due_date',
                'product_configuration',
                'defects',
                'product_condition',
                'service_staff',
                'comment_by_ss',
                'estimated_cost',
                'status_id',
                'workshop',
                'Note',
                'note_list',
                'send_notification'
            ]);

            $validator = Validator::make($filteredData, [
                'workshop' => 'nullable|integer',
                'note_list' => 'nullable|integer',
                'Note' => 'nullable|string',
                'km' => 'nullable|integer',
                'car_condition' => 'nullable|string',
                'entry_date' => 'nullable|date',
                'start_date' => 'nullable|date',
                'delivery_date' => 'nullable|date',
                'due_date' => 'nullable|date',
                'product_configuration' => 'nullable|string',
                'defects' => 'nullable|string',
                'product_condition' => 'nullable|string',
                'service_staff' => 'required|array',
                'service_staff.*' => 'integer',
                'comment_by_ss' => 'nullable|string',
                'estimated_cost' => 'nullable|numeric',
                'status_id' => 'nullable',
                'send_notification' => 'nullable|array',
            ], [
                'service_staff.required' => 'Please select at least one service staff.',
                'service_staff.*.integer' => 'Each service staff must be a valid integer.',
            ]);

            if ($validator->fails()) {
                Log::error('Validation errors in JobSheetController@update', [
                    'errors' => $validator->errors()->all(),
                    'input' => $request->all(),
                ]);

                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            if (isset($filteredData['service_staff']) && is_array($filteredData['service_staff'])) {
                $filteredData['service_staff'] = json_encode($filteredData['service_staff']);
            }

            if (!empty($filteredData['delivery_date'])) {
                $filteredData['delivery_date'] = $this->commonUtil->uf_date($filteredData['delivery_date'], true);
            }

            if (!empty($filteredData['entry_date'])) {
                $filteredData['entry_date'] = $this->commonUtil->uf_date($filteredData['entry_date'], true);
            }

            if (!empty($filteredData['start_date'])) {
                $filteredData['start_date'] = $this->commonUtil->uf_date($filteredData['start_date'], true);
            }

            if (!empty($filteredData['due_date'])) {
                $filteredData['due_date'] = $this->commonUtil->uf_date($filteredData['due_date'], true);
            }

            if (!empty($filteredData['estimated_cost'])) {
                $filteredData['estimated_cost'] = $this->commonUtil->num_uf($filteredData['estimated_cost']);
            }

            if (!empty($request->input('repair_checklist'))) {
                $checklist = $request->input('repair_checklist');
                $formatted_checklist = [];
                $id = 1;

                foreach ($checklist as $key => $value) {
                    if ($value === 'yes') {
                        $formatted_checklist[] = [
                            'id' => $id,
                            'title' => trim($key),
                        ];
                        $id++;
                    }
                }

                $filteredData['checklist'] = $formatted_checklist;
            }

            DB::beginTransaction();

            $job_sheet->update($filteredData);

            if (!empty($filteredData['Note']) && !empty($filteredData['note_list'])) {
                $note = [
                    'category_status' => 'note',
                    'job_sheet_id' => $job_sheet->id,
                    'content' => $filteredData['Note'],
                    'title' => $filteredData['note_list'],
                    'created_by' => auth()->user()->id,
                    'created_at' => now()
                ];

                DB::table('maintenance_note')->insert($note);
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    try {
                        $filePath = $this->handleFileCompression($file, $job_sheet->id, $business_id);

                        if (!$filePath) {
                            Log::error('File compression failed for image', [
                                'file_name' => $file->getClientOriginalName(),
                                'model_id' => $job_sheet->id,
                                'business_id' => $business_id,
                            ]);
                            continue;
                        }

                        Media::create([
                            'business_id' => $business_id,
                            'file_name' => $filePath,
                            'uploaded_by' => auth()->id(),
                            'model_id' => $job_sheet->id,
                            'model_type' => JobSheet::class,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error while processing image for job sheet', [
                            'error_message' => $e->getMessage(),
                            'file_name' => $file->getClientOriginalName(),
                            'model_id' => $job_sheet->id,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()
                ->action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'show'], [$job_sheet->id])
                ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in JobSheetController@update', [
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }


public function store(Request $request)
{
    $business_id = request()->session()->get('user.business_id');
    // Log::info('req',$request->all());
    // dd($request);
    // Check permissions
    if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('job_sheet.create')))) {
        abort(403, 'Unauthorized action.');
    }

    try {
        // Filter the request data to include only the required fields
        $filteredData = $request->only([
            'booking_id',
            'location_id',
            'contact_id',
            'device_id',
            'device_model_id',
            'km',
            'car_condition',
            'entry_date',
            'start_date',
            'delivery_date',
            'due_date',
            'product_configuration',
            'defects',
            'product_condition',
            'service_staff',
            'comment_by_ss',
            'estimated_cost',
            'status_id',
            'images',
            'workshop',
            'Note',
            'note_list',
            'category_status',
            'category_status',
        ]);

        // Validate the filtered data
        $validator = Validator::make($filteredData, [
            'booking_id' => 'required|integer',
            'workshop' => 'nullable|integer',
            'note_list' => 'nullable|integer',
            'Note' => 'nullable|string',
            'km' => 'nullable|integer',
            'car_condition' => 'nullable|string',
            'entry_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',  // Fixed
            'due_date' => 'nullable|date',  // Added separately
            'product_configuration' => 'nullable|string',
            'defects' => 'nullable|string',
            'product_condition' => 'nullable|string',
            'service_staff' => 'required|array',
            'service_staff.*' => 'integer',
            'comment_by_ss' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric',
            'status_id' => 'nullable',
            'images' => 'nullable|array',
            'images.*' => 'nullable|file',
        ], [
            'service_staff.required' => 'Please select at least one service staff.',
            'service_staff.*.integer' => 'Each service staff must be a valid integer.',
        ]);
        

        // Check if validation fails
        if ($validator->fails()) {
            Log::error('Validation errors in JobSheetController@store', [
                'errors' => $validator->errors()->all(),
                'input' => $request->all(),
            ]);

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Encode the service_staff array as JSON
        if (isset($filteredData['service_staff']) && is_array($filteredData['service_staff'])) {
            $filteredData['service_staff'] = json_encode($filteredData['service_staff']);
        }

        // Process delivery date and estimated cost
        if (!empty($filteredData['delivery_date'])) {
            $filteredData['delivery_date'] = $this->commonUtil->uf_date($filteredData['delivery_date'], true);
        }

        if (!empty($filteredData['entry_date'])) {
            $filteredData['entry_date'] = $this->commonUtil->uf_date($filteredData['entry_date'], true);
        }

        if (!empty($filteredData['start_date'])) {
            $filteredData['start_date'] = $this->commonUtil->uf_date($filteredData['start_date'], true);
        }
        if (!empty($filteredData['due_date'])) {
            $filteredData['due_date'] = $this->commonUtil->uf_date($filteredData['due_date'], true);
        }

        if (!empty($filteredData['estimated_cost'])) {
            $filteredData['estimated_cost'] = $this->commonUtil->num_uf($filteredData['estimated_cost']);
        }

        if (!empty($request->input('repair_checklist'))) {
            $checklist = $request->input('repair_checklist');
            $formatted_checklist = [];
            $id = 1;  // Initialize id to 1

            foreach ($checklist as $key => $value) {
                // Only save items with value 'yes', which translates to true
                if ($value === 'yes') {
                    $formatted_checklist[] = [
                        'id' => $id,       // Use a sequential id (1, 2, 3, ...)
                        'title' => trim($key), // Use the key as the title (like 'doors', 'lights', etc.)
                    ];
                    $id++;  // Increment the id for each valid item
                }
            }

            // Store only items with 'yes' (true)
            $filteredData['checklist'] = $formatted_checklist;
        }

        DB::beginTransaction();

        // Generate reference number
        $ref_count = $this->commonUtil->setAndGetReferenceCount('job_sheet', $business_id);
        $business = Business::find($business_id);
        $repair_settings = json_decode($business->repair_settings, true);

        $job_sheet_prefix = '';
        if (isset($repair_settings['job_sheet_prefix'])) {
            $job_sheet_prefix = $repair_settings['job_sheet_prefix'];
        }

        $filteredData['job_sheet_no'] = $this->commonUtil->generateReferenceNumber('job_sheet', $ref_count, null, $job_sheet_prefix);

        // Add created_by and business_id to the filtered data
        $filteredData['created_by'] = $request->user()->id;
        $filteredData['business_id'] = $business_id;

    
        // Check if a job sheet already exists for this booking
        // if (JobSheet::where('booking_id', $booking->id)->exists()) {
        //     return response()->json(['message' => 'Booking already has a job order.'], 409);
        // }
        // Create the job sheet
        $job_sheet = JobSheet::create($filteredData);
        if (!empty($filteredData['Note']) && !empty($filteredData['note_list'])) {
            // Ensure category_status is strictly "note"
            $note = [
                'category_status' => 'note',
                'job_sheet_id' => $job_sheet->id,
                'content' => $filteredData['Note'],  // Add note_list
                'title' => $filteredData['note_list'],  // Assuming "Note" is the title or content
                'created_by' => auth()->user()->id, // Fix: added parentheses
                'created_at' => now()
            ];
        
            // Insert new note
            DB::table('maintenance_note')->insert($note);
        }
        
     

        // Handle Media Upload (Ensure correct model_type and model_id)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                try {
                    // Compress and store the image
                    $filePath = $this->handleFileCompression($file, $job_sheet->id, $business_id);
                    
                    if (!$filePath) {
                        Log::error('File compression failed for image', [
                            'file_name' => $file->getClientOriginalName(),
                            'model_id' => $job_sheet->id,
                            'business_id' => $business_id,
                        ]);
                        continue; // Skip to the next file if compression failed
                    }
    
                    // Create a new media record and associate it with the job sheet
                    Media::create([
                        'business_id' => $business_id,
                        'file_name' => $filePath,
                        'uploaded_by' => auth()->id(),
                        'model_id' => $job_sheet->id,
                        'model_type' => JobSheet::class, // Explicitly set model_type
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error while processing image for job sheet', [
                        'error_message' => $e->getMessage(),
                        'file_name' => $file->getClientOriginalName(),
                        'model_id' => $job_sheet->id,
                    ]);
                }
            }
        }

        // Fetch the booking instance
        $booking = Booking::findOrFail($filteredData['booking_id']);

        if (!$booking || !$job_sheet) {
            return response()->json(['message' => 'Booking not found.'], 404);
        } else {

            // Update the booking
            $booking->update([
                'booking_status' => 'booked',
            ]);

            // If this booking was created with an estimator, move its pending product_joborder rows to this job sheet
            if (!empty($booking->job_estimator_id)) {
                DB::table('product_joborder')
                    ->where('job_estimator_id', $booking->job_estimator_id)
                    ->update([
                        'job_order_id' => $job_sheet->id,
                    ]);
            }
        }

        // After booking update, fetch the device details for repair transaction
        $device = DB::table('contact_device')
            ->where('id', $booking->device_id)
            ->first();

        DB::commit();

        // Try to reuse existing estimator advance transaction if this booking was created from an estimator
        $existing_transaction = null;
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
                $booking->location_id,
                $user_id = auth()->user()->id
            );
        }


        // Redirect based on submit type
        if (!empty($request->input('submit_type')) && $request->input('submit_type') == 'save_and_add_parts') {
            return redirect()
                ->action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'addParts'], [$job_sheet->id])
                ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);
        } elseif (!empty($request->input('submit_type')) && $request->input('submit_type') == 'save_and_upload_docs') {
            return redirect()
                ->action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'getUploadDocs'], [$job_sheet->id])
                ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);
        }

        return redirect()
            ->action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'show'], [$job_sheet->id])
            ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Error in JobSheetController@store', [
            'error_message' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString(),
        ]);

        return redirect()->back()
            ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
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
            '-crf', $quality,           // CRF to control the quality (lower = better quality)
            '-preset', 'slow',          // Use slow preset for better compression efficiency (change this to 'medium' if you want faster processing)
            '-profile:v', 'high',       // Set the profile to 'high' for better compression and quality
            '-level', '4.0',            // Set video level for compatibility (you can change based on the target device)
            '-b:v', '1500k',            // Set a bitrate to control the video size and quality
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

// Modified handleFileCompression to include validation
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
        } else// Example usage in handleFileCompression
        if (str_starts_with($mimeType, 'video')) {
            // Validate video before processing
            $this->validateVideo($file);
            
            // Set desired quality (e.g., 18 for higher quality, 23 for lower quality)
            $quality = 10; // You can modify this value based on your needs
            return $this->compressVideo($file, $path, $quality);
        }elseif (str_starts_with($mimeType, 'application/pdf') || 
                  str_starts_with($mimeType, 'application/msword') || 
                  str_starts_with($mimeType, 'application/vnd.ms-excel')) {
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

public function compressImage($imagePath, $savePath)
{
    // Load the image
    $image = imagecreatefromstring(file_get_contents($imagePath));

    // Check if the image was created successfully
    if (!$image) {
        throw new \Exception("Could not create image from file.");
    }

    // Set the desired quality (0-100)
    $quality = 30;

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



    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && (auth()->user()->can('job_sheet.view_assigned') || auth()->user()->can('job_sheet.view_all') || auth()->user()->can('job_sheet.create'))))) {
            abort(403, 'Unauthorized action.');
        }

       
        // Fetch the job sheet along with related details
        $query = JobSheet::with([ 
            'customer',
            'customer.business',
            'technician',
            'status',
            'Brand',
            'Device',
            'deviceModel',
            'businessLocation',
            'invoices',
            'media' => function($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])
        ->leftJoin('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
        ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')
        ->leftJoin('repair_device_models AS model', 'model.id', '=', 'contact_device.models_id')
        ->leftJoin('categories as brand', 'brand.id', '=', 'contact_device.device_id')
        ->leftJoin('types_of_services', 'bookings.service_type_id', '=', 'types_of_services.id')
        ->select(
            'repair_job_sheets.*',
            'contact_device.color',
            'contact_device.plate_number',
            'contact_device.chassis_number',
            'contact_device.manufacturing_year',
            'model.name AS model_name',
            'brand.name AS brand_name',
            'bookings.booking_note AS problem_reported_by_customer',
            'repair_job_sheets.due_date',
            'repair_job_sheets.delivery_date',
            'repair_job_sheets.entry_date',
            'types_of_services.name AS service_type'
        )
        ->where('repair_job_sheets.business_id', $business_id);

        // Restrict to assigned/created job sheets if the user is not an admin
        if (! ($this->commonUtil->is_admin(auth()->user(), $business_id) || auth()->user()->can('job_sheet.view_all'))) {
        $user_id = auth()->user()->id;
        $query->where(function ($q) use ($user_id) {
            $q->where('repair_job_sheets.service_staff', $user_id)
            ->orWhere('repair_job_sheets.created_by', $user_id);
        });
        }

        // Fetch the job sheet by ID
        $job_sheet = $query->findOrFail($id);

        // Set booking_note from booking
        $job_sheet->booking_note = $job_sheet->booking_note ?? optional($job_sheet->booking)->booking_note ?? null;

        // Set car_condition (if not set, use comment_by_ss as fallback)
        $job_sheet->car_condition = $job_sheet->car_condition ?? $job_sheet->comment_by_ss ?? null;

        // Load media attached to the linked booking (if any)
        $booking_media = collect();
        if (!empty($job_sheet->booking_id)) {
            $booking = Booking::with('media')->find($job_sheet->booking_id);
            if ($booking) {
                $booking_media = $booking->media;
            }
        }

        // Load related CheckCar inspection documents (buyer/seller) if an inspection exists for this job sheet
        $inspection_documents = collect();
        if (class_exists(CarInspection::class)) {
            $inspection = CarInspection::with('documents')
                ->where('job_sheet_id', $job_sheet->id)
                ->first();

            if ($inspection && $inspection->documents) {
                $inspection_documents = $inspection->documents->groupBy('party');
            }
        }

        // Fetch parts used in this job order along with estimated cost
        $parts = DB::table('product_joborder')
            ->join('products', 'product_joborder.product_id', '=', 'products.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->where('product_joborder.job_order_id', $job_sheet->id)

            ->select(
                'products.name AS product_name',
                'product_joborder.quantity',
                'units.short_name AS unit',
                'product_joborder.client_approval',
                DB::raw('(product_joborder.quantity * product_joborder.price) AS total_price'),
                DB::raw("CASE WHEN product_joborder.client_approval = 0 THEN 'لم يتم الموفقه' ELSE 'تمت الموافقه' END as approval_status")
            )
            ->get();

        // Calculate total estimated cost
        $job_sheet->estimated_cost = $parts->sum('total_price');


        // **Decode & Fetch Service Staff**
        $service_staff_ids = json_decode($job_sheet->service_staff, true) ?? [];
        $job_sheet->service_staff = empty($service_staff_ids) ? [] : DB::table('users')
        ->whereIn('id', $service_staff_ids)
        ->select('id', DB::raw("CONCAT(surname, ' ', first_name) AS technicans"))
        ->get();

        // dd($job_sheet);

        // $parts = $job_sheet->getPartsUsed();

        $business = Business::find($business_id);
        $repair_settings = json_decode($business->repair_settings, true);
        $jobsheet_settings = ! empty($business->repair_jobsheet_settings) ?
        json_decode($business->repair_jobsheet_settings, true) : [];

        $activities = Activity::forSubject($job_sheet)
           ->with(['causer', 'subject'])
           ->latest()
           ->get();

        return view('repair::job_sheet.show')
            ->with(compact('job_sheet', 'repair_settings', 'parts', 'activities', 'jobsheet_settings', 'booking_media', 'inspection_documents'));
    }

/**
 * Remove the specified resource from storage.
 *
 * @param  int  $id
 * @return Response
 */
public function destroy($id)
{
    $business_id = request()->session()->get('user.business_id');

    if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('job_sheet.delete')))) {
        abort(403, 'Unauthorized action.');
    }

    if (request()->ajax()) {
        try {
            // Decide whether this is a soft delete (first time) or a
            // hard delete (second time, requires admin password).
            $force_delete = (bool) request()->input('force_delete', false);

            // Include soft-deleted records so we can perform the second-stage delete
            $job_sheet = JobSheet::withTrashed()
                ->where('business_id', $business_id)
                ->findOrFail($id);

            if (! $force_delete) {
                // First stage: soft delete only if not already deleted.
                if (empty($job_sheet->deleted_at)) {
                    $job_sheet->delete();

                    // Find any repair sell transactions linked to this job sheet
                    $transactions = Transaction::where('business_id', $business_id)
                        ->where('repair_job_sheet_id', $job_sheet->id)
                        ->whereIn('type', ['sell', 'sales_order'])
                        ->get();

                    foreach ($transactions as $transaction) {
                        // Use existing deleteSale logic to correctly
                        // rollback stock, rewards, payments, etc.
                        $this->transactionUtil->deleteSale($business_id, $transaction->id);
                    }
                }

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ];
            } else {
                // Second stage: hard delete, only for admins and only if
                // the job sheet is already soft-deleted.

                $is_admin = $this->commonUtil->is_admin(auth()->user(), $business_id);
                if (! $is_admin && ! auth()->user()->can('superadmin')) {
                    abort(403, 'Unauthorized action.');
                }

                if (empty($job_sheet->deleted_at)) {
                    // Must be soft-deleted first before permanent removal.
                    return [
                        'success' => false,
                        'msg' => __('messages.something_went_wrong'),
                    ];
                }

                $password = (string) request()->input('password', '');
                if (empty($password) || ! Hash::check($password, auth()->user()->password)) {
                    return [
                        'success' => false,
                        'msg' => __('auth.failed'),
                    ];
                }

                DB::beginTransaction();

                try {
                    // Find any repair sell transactions linked to this job sheet
                    $transactions = Transaction::withTrashed()
                        ->where('business_id', $business_id)
                        ->where('repair_job_sheet_id', $job_sheet->id)
                        ->whereIn('type', ['sell', 'sales_order'])
                        ->get();

                    foreach ($transactions as $transaction) {
                        // Use existing deleteSale logic to correctly
                        // rollback stock, rewards, payments, etc.
                        $this->transactionUtil->deleteSale($business_id, $transaction->id);

                        // After the normal (soft) delete, hard delete the
                        // transaction record itself.
                        Transaction::withTrashed()
                            ->where('id', $transaction->id)
                            ->forceDelete();
                    }

                    // Finally, hard delete the job sheet itself.
                    $job_sheet->forceDelete();

                    DB::commit();

                    $output = [
                        'success' => true,
                        'msg' => __('lang_v1.success'),
                    ];
                } catch (\Exception $e) {
                    DB::rollBack();

                    Log::error('Failed to hard delete job sheet', [
                        'job_sheet_id' => $job_sheet->id,
                        'business_id' => $business_id,
                        'error' => $e->getMessage(),
                    ]);

                    $output = [
                        'success' => false,
                        'msg' => __('messages.something_went_wrong'),
                    ];
                }
            }

            return $output;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    return redirect()->action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'index']);
}

    private function updateJobsheetStatus($input, $jobsheet_id)
    {
        $job_sheet = JobSheet::where('business_id', $input['business_id'])->findOrFail($jobsheet_id);
        $job_sheet->status_id = $input['status_id'];
        $job_sheet->save();

        $status = RepairStatus::where('business_id', $input['business_id'])->findOrFail($input['status_id']);

        //send job sheet updates
        if (! empty($input['send_sms'])) {
            $sms_body = $input['sms_body'];
            $response = $this->repairUtil->sendJobSheetUpdateSmsNotification($sms_body, $job_sheet);
        }

        if (! empty($input['send_email'])) {
            $subject = $input['email_subject'];
            $body = $input['email_body'];
            $notification = [
                'subject' => $subject,
                'body' => $body,
            ];

            //Set email configuration
            $notificationUtil = new \App\Utils\NotificationUtil();
            $notificationUtil->configureEmail();

            if (! empty($subject) && ! empty($body)) {
                $this->repairUtil->sendJobSheetUpdateEmailNotification($notification, $job_sheet);
            }
        }

        activity()
            ->performedOn($job_sheet)
            ->withProperties(['update_note' => $input['update_note'], 'updated_status' => $status->name])
            ->log('status_changed');
    }

    public function updateStatus(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && (auth()->user()->can('job_sheet.create') || auth()->user()->can('job_sheet.edit'))))) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {
            try {
                $input = $request->only([
                    'status_id',
                    'update_note',
                ]);

                $input['business_id'] = $business_id;

                if (! empty($request->input('send_sms'))) {
                    $input['send_sms'] = true;
                    $input['sms_body'] = $request->input('sms_body');
                }
                if (! empty($request->input('send_email'))) {
                    $input['send_email'] = true;
                    $input['email_body'] = $request->input('email_body');
                    $input['email_subject'] = $request->input('email_subject');
                }
                $status_id = $request->input('status_id');

                $status = RepairStatus::find($status_id);

                if ($status->is_completed_status == 1) {
                    $input['job_sheet_id'] = $id;
                    $request->session()->put('repair_status_update_data', $input);

                    return ['success' => true];
                }

                $this->updateJobsheetStatus($input, $id);

                return ['success' => true,
                    'msg' => __('lang_v1.success'),
                ];
            } catch (\Exception $e) {
                return ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }
        }
    }

    public function recycleBin()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $job_sheets = JobSheet::onlyTrashed()
                ->where('repair_job_sheets.business_id', $business_id)
                ->leftJoin('contacts', 'repair_job_sheets.contact_id', '=', 'contacts.id')
                ->leftJoin('business_locations AS bl', 'repair_job_sheets.location_id', '=', 'bl.id')
                ->select([
                    'repair_job_sheets.id',
                    'repair_job_sheets.job_sheet_no',
                    'contacts.name as customer',
                    'bl.name as location',
                    'repair_job_sheets.deleted_at'
                ]);

            return Datatables::of($job_sheets)
                ->addColumn('action', function ($row) {
                    $html = '<button data-href="' . route('job-sheet.restore', [$row->id]) . '" class="btn btn-xs btn-success restore_job_sheet"><i class="fas fa-undo"></i> ' . __("messages.restore") . '</button>';
                    $html .= ' <button data-href="' . route('job-sheet.permanent_delete', [$row->id]) . '" class="btn btn-xs btn-danger delete_job_sheet_permanent"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</button>';
                    return $html;
                })
                ->editColumn('deleted_at', '{{@format_datetime($deleted_at)}}')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('repair::job_sheet.recycle_bin');
    }

    public function restore($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $job_sheet = JobSheet::onlyTrashed()
                ->where('business_id', $business_id)
                ->findOrFail($id);
            $job_sheet->restore();

            $output = ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }
        return $output;
    }

    public function permanentDelete($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            $job_sheet = JobSheet::onlyTrashed()
                ->where('business_id', $business_id)
                ->findOrFail($id);
            
            // Find related repair transactions (sell / sales order) linked to this job sheet
            $transactions = Transaction::withTrashed()
                ->where('business_id', $business_id)
                ->where('repair_job_sheet_id', $job_sheet->id)
                ->whereIn('type', ['sell', 'sales_order'])
                ->get();

            foreach ($transactions as $transaction) {
                // If this transaction has not yet been soft-deleted, run the
                // standard deleteSale flow so stock, payments, etc. are
                // handled exactly the same way as everywhere else.
                if (empty($transaction->deleted_at)) {
                    $this->transactionUtil->deleteSale($business_id, $transaction->id);
                }

                // At this point the transaction and its lines/payments are
                // soft-deleted. Now permanently remove the underlying rows
                // so the recycle bin hard delete fully cleans things up.

                // Delete related transaction sell lines
                TransactionSellLine::where('transaction_id', $transaction->id)->delete();

                // Delete related transaction payments
                DB::table('transaction_payments')
                    ->where('transaction_id', $transaction->id)
                    ->delete();

                // Delete related account transactions
                DB::table('account_transactions')
                    ->where('transaction_id', $transaction->id)
                    ->delete();

                // Finally force delete the transaction itself
                $transaction->forceDelete();
            }

            // Delete related media
            Media::where('model_id', $job_sheet->id)
                ->where('model_type', JobSheet::class)
                ->delete();

            // Finally, force delete the job sheet itself
            $job_sheet->forceDelete();

            DB::commit();
            $output = ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            DB::rollBack();
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }
        return $output;
    }

    public function deleteJobSheetImage(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && (auth()->user()->can('job_sheet.view_assigned') || auth()->user()->can('job_sheet.view_all') || auth()->user()->can('job_sheet.create'))))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                Media::deleteMedia($business_id, $id);

                $output = ['success' => true,
                    'msg' => __('lang_v1.success'),
                ];
            } catch (\Exception $e) {
                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    public function addParts($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && (auth()->user()->can('job_sheet.create') || auth()->user()->can('job_sheet.edit'))))) {
            abort(403, 'Unauthorized action.');
        }

        $status_update_data = request()->session()->get('repair_status_update_data');

        $job_sheet = JobSheet::where('business_id', $business_id)->findOrFail($id);

        $parts = $job_sheet->getPartsUsed();

        $status_dropdown = RepairStatus::forDropdown($business_id, true);
        $status_template_tags = $this->repairUtil->getRepairStatusTemplateTags();

        return view('repair::job_sheet.add_parts')
            ->with(compact('job_sheet', 'parts', 'status_update_data', 'status_dropdown', 'status_template_tags'));
    }

    public function saveParts(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && (auth()->user()->can('job_sheet.create') || auth()->user()->can('job_sheet.edit'))))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $parts = $request->input('parts');
            $job_sheet = JobSheet::where('business_id', $business_id)->findOrFail($id);
            $job_sheet->parts = ! empty($parts) ? $parts : null;
            $job_sheet->save();

            if (! empty($request->session()->get('repair_status_update_data')) && ! empty($request->input('status_id'))) {
                $input = $request->only([
                    'status_id',
                    'update_note',
                ]);

                $input['business_id'] = $business_id;

                if (! empty($request->input('send_sms'))) {
                    $input['send_sms'] = true;
                    $input['sms_body'] = $request->input('sms_body');
                }
                if (! empty($request->input('send_email'))) {
                    $input['send_email'] = true;
                    $input['email_body'] = $request->input('email_body');
                    $input['email_subject'] = $request->input('email_subject');
                }

                $this->updateJobsheetStatus($input, $job_sheet->id);

                $request->session()->forget('repair_status_update_data');
            }

            $output = ['success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
        }

        return redirect()
                ->action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'show'], [$job_sheet->id])
                ->with('status', ['success' => true,
                    'msg' => __('lang_v1.success'), ]);
    }

    public function jobsheetPartRow(Request $request)
    {
        if (request()->ajax()) {
            $variation_id = $request->input('variation_id');

            $business_id = $request->session()->get('user.business_id');
            $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id);

            $variation_name = $product->product_name.' - '.$product->sub_sku;
            $variation_id = $product->variation_id;
            $quantity = 1;
            $unit = $product->unit;

            return view('repair::job_sheet.partials.job_sheet_part_row')
            ->with(compact('variation_name', 'variation_id', 'quantity', 'unit'));
        }
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function print($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && (auth()->user()->can('job_sheet.view_assigned') || auth()->user()->can('job_sheet.view_all') || auth()->user()->can('job_sheet.create'))))) {
            abort(403, 'Unauthorized action.');
        }

        $query = JobSheet::with([
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
            ->where('repair_job_sheets.business_id', $business_id);

        if (! ($this->commonUtil->is_admin(auth()->user(), $business_id) || auth()->user()->can('job_sheet.view_all'))) {
            $user_id = auth()->user()->id;
            $query->where(function ($q) use ($user_id) {
                $q->where('repair_job_sheets.service_staff', $user_id)
                    ->orWhere('repair_job_sheets.created_by', $user_id);
            });
        }

        $job_sheet = $query->findOrFail($id);

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

        $job_sheet->parts = $parts;
        $job_sheet->estimated_cost = $parts->sum('total_price');

        $service_staff_ids = json_decode($job_sheet->service_staff, true) ?? [];
        $job_sheet->service_staff = empty($service_staff_ids) ? [] : DB::table('users')
            ->whereIn('id', $service_staff_ids)
            ->select('id', DB::raw("CONCAT(surname, ' ', first_name) AS technicans"))
            ->get();

        $mediaCollection = collect($job_sheet->media ?? []);

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

        $firstUntagged = $mediaCollection->first(function ($mediaItem) {
            return empty($mediaItem->description) || trim($mediaItem->description) === '';
        });

        $job_sheet->jobSheet_media = optional($firstUntagged)->display_url ?? null;

        $business = Business::find($business_id);
        $repair_settings = $business->repair_settings ? json_decode($business->repair_settings, true) : [];
        $jobsheet_settings = $business->repair_jobsheet_settings ? json_decode($business->repair_jobsheet_settings, true) : [];

        $html = view('repair::job_sheet.print_pdf_api')
            ->with(compact('job_sheet', 'repair_settings', 'parts', 'jobsheet_settings'))
            ->render();

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

        $mpdf->showImageErrors = true;
        $mpdf->useSubstitutions = true;
        $mpdf->SetTitle(__('repair::lang.job_sheet') . ' | ' . $job_sheet->job_sheet_no);
        $mpdf->WriteHTML($html);

        $fileName = $job_sheet->id . '.pdf';
        $filePath = storage_path('app/public/jobsheet_pdf/' . $fileName);

        if (!file_exists(storage_path('app/public/jobsheet_pdf'))) {
            mkdir(storage_path('app/public/jobsheet_pdf'), 0777, true);
        }

        $mpdf->Output($filePath, 'F');

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"'
        ]);
    }

    /**
     * Print label.
     *
     * @param  int  $id
     * @return Response
     */
    public function printLabel($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && (auth()->user()->can('job_sheet.view_assigned') || auth()->user()->can('job_sheet.view_all') || auth()->user()->can('job_sheet.create'))))) {
            abort(403, 'Unauthorized action.');
        }

        $query = JobSheet::with(
            'customer',
            'customer.business',
            'technician',
            'status',
            'Brand',
            'Device',
            'deviceModel',
            'businessLocation',
            'createdBy'
        )
            ->where('business_id', $business_id);

        //if user is not admin or didn't have permission `job_sheet.view_all` get only assgined/created_by job sheet
        if (! ($this->commonUtil->is_admin(auth()->user(), $business_id) || auth()->user()->can('job_sheet.view_all'))) {
            $user_id = auth()->user()->id;
            $query->where(function ($q) use ($user_id) {
                $q->where('repair_job_sheets.service_staff', $user_id)
                    ->orWhere('repair_job_sheets.created_by', $user_id);
            });
        }

        $job_sheet = $query->findOrFail($id);

        $business = Business::find($business_id);
        $repair_settings = json_decode($business->repair_settings, true);

        $jobsheet_settings = ! empty($business->repair_jobsheet_settings) ?
            json_decode($business->repair_jobsheet_settings, true) : [];

        $label_width = isset($jobsheet_settings['label_width']) ? $jobsheet_settings['label_width'] : 75;
        $label_height = isset($jobsheet_settings['label_height']) ? $jobsheet_settings['label_height'] : 50;

        $html = view('repair::job_sheet.print_label')
        ->with(compact('job_sheet', 'repair_settings', 'jobsheet_settings'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'format' => [$label_width, $label_height],
            'tempDir' => public_path('uploads/temp'),
            'mode' => 'utf-8',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'autoVietnamese' => true,
            'autoArabic' => true,
            'margin_top' => 4,
            'margin_left' => 4,
            'margin_right' => 4,
            'margin_bottom' => 4,
        ]);
        $mpdf->useSubstitutions = true;
        $mpdf->SetTitle(__('repair::lang.job_sheet_label').' | '.$job_sheet->job_sheet_no);
        $mpdf->WriteHTML($html);
        $mpdf->Output('job_sheet_label.pdf', 'I');
    }

    public function getUploadDocs($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && (auth()->user()->can('job_sheet.create') || auth()->user()->can('job_sheet.edit'))))) {
            abort(403, 'Unauthorized action.');
        }

        $job_sheet = JobSheet::with(['media'])
                        ->where('business_id', $business_id)
                        ->findOrFail($id);

        $booking_media = collect();
        if (!empty($job_sheet->booking_id)) {
            $booking = Booking::with('media')->find($job_sheet->booking_id);
            if ($booking) {
                $booking_media = $booking->media;
            }
        }

        // Load related CheckCar inspection documents (buyer/seller) if an inspection exists for this job sheet
        $inspection_documents = collect();
        if (class_exists(CarInspection::class)) {
            $inspection = CarInspection::with('documents')
                ->where('job_sheet_id', $job_sheet->id)
                ->first();

            if ($inspection && $inspection->documents) {
                $inspection_documents = $inspection->documents->groupBy('party');
            }
        }

        return view('repair::job_sheet.upload_doc', compact('job_sheet', 'booking_media', 'inspection_documents'));
    }

    public function postUploadDocs(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && (auth()->user()->can('job_sheet.create') || auth()->user()->can('job_sheet.edit'))))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $images = json_decode($request->input('images'), true);

            $job_sheet = JobSheet::where('business_id', $business_id)
                        ->findOrFail($request->input('job_sheet_id'));

            if (! empty($images) && ! empty($job_sheet)) {
                // Create Media records directly for JobSheet with proper model association
                foreach ($images as $filePath) {
                    if (!empty($filePath)) {
                        Media::create([
                            'business_id' => $business_id,
                            'file_name' => $filePath,
                            'uploaded_by' => auth()->id(),
                            'model_id' => $job_sheet->id,
                            'model_type' => JobSheet::class,
                        ]);
                    }
                }
            }

            $output = ['success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()
            ->action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'show'], [$job_sheet->id])
            ->with('status', ['success' => true,
                'msg' => __('lang_v1.success'), ]);
    }

    /**
     * Handle AJAX media upload from Dropzone and return stored filename.
     */
    public function postUploadMedia(Request $request)
    {
        try {
            $fileInput = $request->file('file');

            // Dropzone usually sends a single file per request. Support both single and array.
            if (is_array($fileInput)) {
                $file = $fileInput[0] ?? null;
            } else {
                $file = $fileInput;
            }

            if (empty($file)) {
                return [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            // Get job sheet ID from request or session
            $jobSheetId = $request->input('job_sheet_id');
            if (!$jobSheetId) {
                // Try to get from session or other means
                $jobSheetId = session('current_job_sheet_id');
            }

            if ($jobSheetId) {
                // Use JobSheet-specific upload method
                $file_name = \App\Media::uploadFileForJobSheet($file, $jobSheetId);
            } else {
                // Fallback to legacy method
                $file_name = \App\Media::uploadFile($file);
            }

            return [
                'success' => !empty($file_name),
                'file_name' => $file_name,
                'msg' => !empty($file_name) ? __('lang_v1.success') : __('messages.something_went_wrong'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    public function showMedia($id)
    {
        // Find the JobSheet with its media
        $jobSheet = JobSheet::with('media')->findOrFail($id);
        return view('repair::job_sheet.media', compact('jobSheet'));

    }
}
