<?php

namespace Modules\Crm\Http\Controllers;

use App\Category;
use App\Contact;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Crm\Entities\CrmContact;
use Modules\Crm\Utils\CrmUtil;
use Yajra\DataTables\Facades\DataTables;
use Modules\Repair\Entities\JobSheet;
use App\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LeadController extends Controller
{
    use AuthorizesRequests;

    protected $commonUtil;

    protected $moduleUtil;

    protected $crmUtil;

    /**
     * Constructor
     *
     * @param  Util  $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, CrmUtil $crmUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
        $this->crmUtil = $crmUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $can_access_all_leads = auth()->user()->can('crm.access_all_leads');
        $can_access_own_leads = auth()->user()->can('crm.access_own_leads');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module')) || ! ($can_access_all_leads || $can_access_own_leads)) {
            abort(403, 'Unauthorized action.');
        }

        $life_stages = Category::forDropdown($business_id, 'life_stage');

        if (is_null(request()->get('lead_view'))) {
            $lead_view = 'list_view';
        } else {
            $lead_view = request()->get('lead_view');
        }

        if (request()->ajax()) {
            $leads = $this->crmUtil->getLeadsListQuery($business_id);

            if (! $can_access_all_leads && $can_access_own_leads) {
                $leads->OnlyOwnLeads();
            }

            if (! empty(request()->get('source'))) {
                $leads->where('crm_source', request()->get('source'));
            }

            if (! empty(request()->get('life_stage'))) {
                $leads->where('crm_life_stage', request()->get('life_stage'));
            }

            if (! empty(request()->get('user_id'))) {
                $user_id = request()->get('user_id');
                $leads->where(function ($query) use ($user_id) {
                    $query->whereHas('leadUsers', function ($q) use ($user_id) {
                        $q->where('user_id', $user_id);
                    });
                });
            }

            if ($lead_view == 'list_view') {
                return Datatables::of($leads)
                    ->addColumn('address', '{{implode(", ", array_filter([$address_line_1, $address_line_2, $city, $state, $country, $zip_code]))}}')
                    ->addColumn('action', function ($row) {
                        $html = '<div class="btn-group">
                                    <button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max dropdown-toggle" type="button"  data-toggle="dropdown" aria-expanded="false">
                                        '.__('messages.action').'
                                        <span class="caret"></span>
                                        <span class="sr-only">'
                                           .__('messages.action').'
                                        </span>
                                    </button>
                                      <ul class="dropdown-menu dropdown-menu-left" role="menu">
                                       <li>
                                            <a href="'.action([\Modules\Crm\Http\Controllers\LeadController::class, 'show'], ['lead' => $row->id]).'" class="cursor-pointer view_lead">
                                                <i class="fa fa-eye"></i>
                                                '.__('messages.view').'
                                            </a>
                                        </li>
                                        <li>
                                            <a data-href="'.action([\Modules\Crm\Http\Controllers\LeadController::class, 'edit'], ['lead' => $row->id]).'"class="cursor-pointer edit_lead">
                                                <i class="fa fa-edit"></i>
                                                '.__('messages.edit').'
                                            </a>
                                        </li>
                                        <li>
                                            <a data-href="'.action([\Modules\Crm\Http\Controllers\LeadController::class, 'convertToCustomer'], ['id' => $row->id]).'" class="cursor-pointer convert_to_customer">
                                                <i class="fas fa-redo"></i>
                                                '.__('crm::lang.convert_to_customer').'
                                            </a>
                                        </li>
                                        <li>
                                            <a data-href="'.action([\Modules\Crm\Http\Controllers\LeadController::class, 'destroy'], ['lead' => $row->id]).'" class="cursor-pointer delete_a_lead">
                                                <i class="fas fa-trash"></i>
                                                '.__('messages.delete').'
                                            </a>
                                        </li>';

                        $html .= '</ul>
                                </div>';

                        return $html;
                    })
                    ->addColumn('last_follow_up', function ($row) {
                        $html = '';

                        if (! empty($row->last_follow_up)) {
                            $html .= $this->commonUtil->format_date($row->last_follow_up, true);
                            $html .= '<br><a href="#" class="view-followup-modal" data-lead_id="'.$row->id.'" title="'.__('crm::lang.view_follow_up').'" data-toggle="tooltip">
                                <i class="fas fa-external-link-alt"></i>
                            </a><br>';
                        }

                        $infos = json_decode($row->last_follow_up_additional_info, true);

                        if (! empty($infos)) {
                            foreach ($infos as $key => $value) {
                                $html .= $key.' : '.$value.'<br>';
                            }
                        }

                        return $html;
                    })
                    ->orderColumn('last_follow_up', function ($query, $order) {
                        $query->orderBy('last_follow_up', $order);
                    })
                    ->addColumn('upcoming_follow_up', function ($row) {
                        $html = '';

                        if (! empty($row->upcoming_follow_up)) {
                            $html .= $this->commonUtil->format_date($row->upcoming_follow_up, true);
                            $html .= '<br><a href="#" class="view-followup-modal" data-lead_id="'.$row->id.'" title="'.__('crm::lang.view_follow_up').'" data-toggle="tooltip">
                                <i class="fas fa-external-link-alt"></i>
                            </a><br>';
                        }

                        $html .= '<a href="'.action([\Modules\Crm\Http\Controllers\ScheduleController::class, 'create'], ['schedule_for' => 'lead', 'contact_id' => $row->id]).'" class="follow-up-btn tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary">
                            <i class="fas fa-plus"></i>'.
                            __('crm::lang.add_schedule').'
                        </a><br>';

                        $infos = json_decode($row->upcoming_follow_up_additional_info, true);

                        if (! empty($infos)) {
                            foreach ($infos as $key => $value) {
                                $html .= $key.' : '.$value.'<br>';
                            }
                        }

                        return $html;
                    })
                    ->orderColumn('upcoming_follow_up', function ($query, $order) {
                        $query->orderBy('upcoming_follow_up', $order);
                    })
                    ->editColumn('created_at', '
                        {{@format_date($created_at)}}
                    ')
                    ->editColumn('crm_source', function ($row) {
                        return $row->Source?->name;
                    })
                    ->editColumn('name', '@if(!empty($supplier_business_name)) {{$supplier_business_name}},<br>@endif {{$name}}')
                    ->addColumn('jobsheets', function ($row) {
                        $html = '';
                        // Get jobsheets related to this contact/lead
                        $jobsheets = JobSheet::where('contact_id', $row->id)
                            ->with('status') // Include the status relationship
                            ->select('id', 'job_sheet_no', 'status_id')
                            ->get();

                      if ($jobsheets->count() > 0) {
                            foreach ($jobsheets as $jobsheet) {
                                $url = action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'show'], ['job_sheet' => $jobsheet->id]);

                                // Get status color and name
                                $status_color = !empty($jobsheet->status->color) ? $jobsheet->status->color : '#777';
                                $status_name = !empty($jobsheet->status->name) ? $jobsheet->status->name : '';

                                // Use flex to align number and status side by side
                                $html .= '<div class="d-flex align-items-center gap-2 mb-1">';
                                $html .= '<a href="' . $url . '" class="cursor-pointer">' . $jobsheet->job_sheet_no . '</a>';
                                if (!empty($status_name)) {
                                    $html .= '<span class="label" style="background-color:' . $status_color . '">' . $status_name . '</span>';
                                }
                                $html .= '</div>';
                            }
                        }


                        return $html;
                    })
                    ->addColumn('contact_devices', function ($row) {
                        return $this->getContactDevicesButton($row->id);
                    })
                    ->addColumn('transactions', function ($row) {
                        $html = '';

                        // Get all jobsheets with their invoices for this contact
                        $jobsheets = JobSheet::where('contact_id', $row->id)
                            ->with(['invoices' => function($query) {
                                $query->where('type', 'sell')
                                    //   ->where('status', 'final')
                                      ->select('id', 'invoice_no', 'repair_job_sheet_id');
                            }])
                            ->get();

                        if ($jobsheets->count() > 0) {
                            foreach ($jobsheets as $jobsheet) {
                                if ($jobsheet->invoices->count() > 0) {
                                    foreach ($jobsheet->invoices as $transaction) {
                                        // Create a button that will open a modal with transaction details
                                        $html .= '<a href="#"
                                            class="btn-modal cursor-pointer"
                                            data-href="' . url('/repair/repair/' . $transaction->id) . '"
                                            data-container=".transaction_modal">' .
                                            $transaction->invoice_no .
                                        '</a><br>';
                                    }
                                }
                            }
                        }

                        return $html;
                    })
                    ->addColumn('estimators', function ($row) {
                        $html = '';
                        
                        // Get estimators related to this contact/lead
                        $estimators = \App\Restaurant\JobEstimator::where('contact_id', $row->id)
                            ->select('id', 'estimate_no', 'estimator_status')
                            ->get();

                        if ($estimators->count() > 0) {
                            foreach ($estimators as $estimator) {
                                $url = action([\App\Http\Controllers\Restaurant\JobEstimatorController::class, 'show'], $estimator->id);

                                // Get status color and name
                                $status_colors = [
                                    'pending' => '#f39c12',
                                    'sent' => '#3498db',
                                    'approved' => '#27ae60',
                                    'rejected' => '#e74c3c',
                                    'converted_to_order' => '#9b59b6'
                                ];
                                $status_color = $status_colors[$estimator->estimator_status] ?? '#777';
                                $status_name = ucfirst(str_replace('_', ' ', $estimator->estimator_status));

                                // Use flex to align number and status side by side
                                $html .= '<div class="d-flex align-items-center gap-2 mb-1">';
                                $display_no = !empty($estimator->estimate_no) ? $estimator->estimate_no : ('Estimator #' . $estimator->id);
                                $html .= '<a href="#" class="btn-modal cursor-pointer" data-href="' . $url . '" data-container=".estimator_modal">' . $display_no . '</a>';
                                if (!empty($status_name)) {
                                    $html .= '<span class="label" style="background-color:' . $status_color . '">' . $status_name . '</span>';
                                }
                                $html .= '</div>';
                            }
                        }

                        return $html;
                    })
                    ->removeColumn('id')
                    ->filterColumn('address', function ($query, $keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->where('address_line_1', 'like', "%{$keyword}%")
                            ->orWhere('address_line_2', 'like', "%{$keyword}%")
                            ->orWhere('city', 'like', "%{$keyword}%")
                            ->orWhere('state', 'like', "%{$keyword}%")
                            ->orWhere('country', 'like', "%{$keyword}%")
                            ->orWhere('zip_code', 'like', "%{$keyword}%")
                            ->orWhereRaw("CONCAT(COALESCE(address_line_1, ''), ', ', COALESCE(address_line_2, ''), ', ', COALESCE(city, ''), ', ', COALESCE(state, ''), ', ', COALESCE(country, '') ) like ?", ["%{$keyword}%"]);
                        });
                    })
                    ->rawColumns(['action', 'crm_source', 'last_follow_up', 'upcoming_follow_up', 'created_at', 'name', 'contact_devices', 'jobsheets', 'transactions', 'estimators'])
                    ->make(true);
            } elseif ($lead_view == 'kanban') {
                $leads = $leads->get()->groupBy('crm_life_stage');
                //sort leads based on life stage
                $crm_leads = [];
                $board_draggable_to = [];
                foreach ($life_stages as $key => $value) {
                    $board_draggable_to[] = strval($key);
                    if (! isset($leads[$key])) {
                        $crm_leads[strval($key)] = [];
                    } else {
                        $crm_leads[strval($key)] = $leads[$key];
                    }
                }

                $leads_html = [];
                foreach ($crm_leads as $key => $leads) {
                    //get all the leads for particular board(life stage)
                    $cards = [];
                    foreach ($leads as $lead) {
                        $edit = action([\Modules\Crm\Http\Controllers\LeadController::class, 'edit'], ['lead' => $lead->id]);

                        $delete = action([\Modules\Crm\Http\Controllers\LeadController::class, 'destroy'], ['lead' => $lead->id]);

                        $view = action([\Modules\Crm\Http\Controllers\LeadController::class, 'show'], ['lead' => $lead->id]);

                        //if member then get their avatar
                        if ($lead->leadUsers->count() > 0) {
                            $assigned_to = [];
                            foreach ($lead->leadUsers as $member) {
                                if (isset($member->media->display_url)) {
                                    $assigned_to[$member->user_full_name] = $member->media->display_url;
                                } else {
                                    $assigned_to[$member->user_full_name] = 'https://ui-avatars.com/api/?name='.$member->first_name;
                                }
                            }
                        }

                        $cards[] = [
                            'id' => $lead->id,
                            'title' => $lead->full_name_with_business,
                            'viewUrl' => $view,
                            'editUrl' => $edit,
                            'editUrlClass' => 'edit_lead',
                            'deleteUrl' => $delete,
                            'deleteUrlClass' => 'delete_a_lead',
                            'assigned_to' => $assigned_to,
                            'hasDescription' => false,
                            'tags' => [$lead->Source->name ?? ''],
                            'dragTo' => $board_draggable_to,
                        ];
                    }

                    //get all the card & board title for particular board(life stage)
                    $leads_html[] = [
                        'id' => strval($key),
                        'title' => $life_stages[$key],
                        'cards' => $cards,
                    ];
                }

                $output = [
                    'success' => true,
                    'leads_html' => $leads_html,
                    'msg' => __('lang_v1.success'),
                ];

                return $output;
            }
        }

        $sources = Category::forDropdown($business_id, 'source');

        $users = User::forDropdown($business_id, false, false, false, true);

        return view('crm::lead.index')
            ->with(compact('sources', 'life_stages', 'lead_view', 'users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $users = User::forDropdown($business_id, false, false, false, true);
        $sources = Category::forDropdown($business_id, 'source');
        $life_stages = Category::forDropdown($business_id, 'life_stage');

        $types['lead'] = __('crm::lang.lead');
        $types['customer'] = __('contact.customer');
        $store_action = action([\Modules\Crm\Http\Controllers\LeadController::class, 'store']);

        $module_form_parts = $this->moduleUtil->getModuleData('contact_form_part');

        return view('contact.create')
            ->with(compact('types', 'store_action', 'sources', 'life_stages', 'users', 'module_form_parts'));
    }

    /**
     * Fetch single job estimator details with related product_joborder lines for CRM (web guard).
     * GET /crm/estimator/{id}/details
     */
    public function getEstimatorDetails($id)
    {
        try {
        
            $id = (int) $id;
            if (! $id) {
                return response()->json(['success' => false, 'message' => 'Missing estimator id'], 400);
            }

            // Estimator + related info
            $estimator = DB::table('job_estimator')
                ->leftJoin('contacts', 'contacts.id', '=', 'job_estimator.contact_id')
                ->leftJoin('business_locations', 'business_locations.id', '=', 'job_estimator.location_id')
                ->leftJoin('contact_device', 'contact_device.id', '=', 'job_estimator.device_id')
                ->leftJoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
                ->leftJoin('categories', 'categories.id', '=', 'contact_device.device_id')
                ->leftJoin('types_of_services', 'types_of_services.id', '=', 'job_estimator.service_type_id')
       
                ->where('job_estimator.id', $id)
                ->select([
                    'job_estimator.id',
                    'job_estimator.estimate_no',
                    'job_estimator.contact_id',
                    'contacts.name as customer_name',
                    'job_estimator.device_id',
                    'repair_device_models.name AS model',
                    'categories.name AS brand',
                    'job_estimator.location_id',
                    'business_locations.name as location_name',
                    'job_estimator.created_by',
                    'job_estimator.service_type_id',
                    'types_of_services.name AS service_type',
                    'job_estimator.estimator_status',
                    'contact_device.color',
                    'contact_device.chassis_number',
                    'contact_device.plate_number',
                    'contact_device.manufacturing_year',
                    'contact_device.car_type',
                    'job_estimator.payment_details',
                    'job_estimator.expected_delivery_date',
                    'job_estimator.sent_to_customer_at',
                    'job_estimator.approved_at',
                ])
                ->first();

            if (! $estimator) {
                return response()->json(['success' => false, 'message' => 'Estimator not found'], 404);
            }

            // Derived display helpers
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
                ->leftJoin('units', 'units.id', '=', 'products.unit_id')
                ->leftJoin('contacts as suppliers', 'suppliers.id', '=', 'pjo.supplier_id')
                ->where('pjo.job_estimator_id', $id)
                ->select([
                    'pjo.id as line_id',
                    'pjo.product_id',
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

            

            return response()->json([
                'success' => true,
                'estimator' => $estimator,
                'vehicle_display' => $vehicle_display,
                'vin_display' => $vin_display,
                'lines' => $lines,
            ]);
        } catch (\Throwable $e) {
            Log::error('CRM getEstimatorDetails failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }
        // log::info('sssssss',[$request->all()]);

        try {
            $input = $request->only(['type', 'prefix', 'first_name', 'middle_name', 'last_name', 'tax_number', 'mobile', 'landline', 'alternate_number', 'city', 'state', 'country', 'landmark', 'contact_id', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'custom_field5', 'custom_field6', 'custom_field7', 'custom_field8', 'custom_field9', 'custom_field10', 'email', 'crm_source', 'crm_life_stage', 'dob', 'address_line_1', 'address_line_2', 'zip_code', 'supplier_business_name', 'shipping_custom_field_details', 'chassis_number', 'car_type', 'gehad_category_id', 'gehad_model_id', 'manufacturing_year', 'color', 'plate_number']);

            $input['name'] = implode(' ', [$input['prefix'], $input['first_name'], $input['middle_name'], $input['last_name']]);

            if (! empty($request->input('is_export'))) {
                $input['is_export'] = true;
                $input['export_custom_field_1'] = $request->input('export_custom_field_1');
                $input['export_custom_field_2'] = $request->input('export_custom_field_2');
                $input['export_custom_field_3'] = $request->input('export_custom_field_3');
                $input['export_custom_field_4'] = $request->input('export_custom_field_4');
                $input['export_custom_field_5'] = $request->input('export_custom_field_5');
                $input['export_custom_field_6'] = $request->input('export_custom_field_6');
            }

            if (! empty($input['dob'])) {
                $input['dob'] = $this->commonUtil->uf_date($input['dob']);
            }

            $input['business_id'] = $business_id;
            $input['created_by'] = $request->session()->get('user.id');

            $assigned_to = $request->input('user_id');

            // Create either lead or customer based on type
            if ($input['type'] === 'customer') {
                // Create a regular customer
                $contact = Contact::create($input);
                
                // Update user contact access
                if (! empty($assigned_to)) {
                    $contact->userHavingAccess()->sync($assigned_to);
                }
            } else {
                // Create a lead (default behavior)
                $contact = CrmContact::createNewLead($input, $assigned_to);
            }

            if (! empty($contact)) {
                $this->moduleUtil->getModuleData('after_contact_saved', ['contact' => $contact, 'input' => $request->input()]);
            }

            // Save device information if present
            if (isset($input['gehad_category_id'], $input['gehad_model_id'], $input['manufacturing_year'], $input['color'], $input['plate_number'])) {
                // Insert only if all required fields exist
                \DB::table('contact_device')->insert([
                    'device_id' => $input['gehad_category_id'],
                    'models_id' => $input['gehad_model_id'],
                    'color' => $input['color'],
                    'chassis_number' => $input['chassis_number'],
                    'plate_number' => $input['plate_number'],
                    'manufacturing_year' => $input['manufacturing_year'],
                    'car_type' => $input['car_type'],
                    'contact_id' => $contact->id,
                ]);
            } else {
                Log::error('Device data is missing or incomplete:', ['device' => $input]);
            }

            $output = ['success' => true,
                'msg' => __('contact.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $can_access_all_leads = auth()->user()->can('crm.access_all_leads');
        $can_access_own_leads = auth()->user()->can('crm.access_own_leads');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module')) || ! ($can_access_all_leads || $can_access_own_leads)) {
            abort(403, 'Unauthorized action.');
        }

        $query = CrmContact::with('leadUsers', 'Source', 'lifeStage')
                    ->where('business_id', $business_id);

        if (! $can_access_all_leads && $can_access_own_leads) {
            $query->OnlyOwnLeads();
        }
        $contact = $query->findOrFail($id);

        $leads = CrmContact::leadsDropdown($business_id, false);

        $contact_view_tabs = $this->moduleUtil->getModuleData('get_contact_view_tabs');

        return view('crm::lead.show')
            ->with(compact('contact', 'leads', 'contact_view_tabs'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $can_access_all_leads = auth()->user()->can('crm.access_all_leads');
        $can_access_own_leads = auth()->user()->can('crm.access_own_leads');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module')) || ! ($can_access_all_leads || $can_access_own_leads)) {
            abort(403, 'Unauthorized action.');
        }

        $query = CrmContact::with('leadUsers')
                    ->where('business_id', $business_id);

        if (! $can_access_all_leads && $can_access_own_leads) {
            $query->OnlyOwnLeads();
        }
        $contact = $query->findOrFail($id);

        $users = User::forDropdown($business_id, false);
        $sources = Category::forDropdown($business_id, 'source');
        $life_stages = Category::forDropdown($business_id, 'life_stage');

        $types['customer'] = __('contact.customer');
        $types['lead'] = __('crm::lang.lead');
        $update_action = action([\Modules\Crm\Http\Controllers\LeadController::class, 'update'], ['lead' => $id]);

        return view('contact.edit')
            ->with(compact('contact', 'types', 'update_action', 'sources', 'life_stages', 'users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        $can_access_all_leads = auth()->user()->can('crm.access_all_leads');
        $can_access_own_leads = auth()->user()->can('crm.access_own_leads');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module')) || ! ($can_access_all_leads || $can_access_own_leads)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['type', 'prefix', 'first_name', 'middle_name', 'last_name', 'tax_number', 'mobile', 'landline', 'alternate_number', 'city', 'state', 'country', 'landmark', 'contact_id', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'custom_field5', 'custom_field6', 'custom_field7', 'custom_field8', 'custom_field9', 'custom_field10', 'email', 'crm_source', 'crm_life_stage', 'dob', 'address_line_1', 'address_line_2', 'zip_code', 'supplier_business_name', 'shipping_custom_field_details', 'export_custom_field_1', 'export_custom_field_2', 'export_custom_field_3', 'export_custom_field_4', 'export_custom_field_5', 'export_custom_field_6']);

            $input['name'] = implode(' ', [$input['prefix'], $input['first_name'], $input['middle_name'], $input['last_name']]);

            $input['is_export'] = ! empty($request->input('is_export')) ? 1 : 0;

            if (! $input['is_export']) {
                unset($input['export_custom_field_1'], $input['export_custom_field_2'], $input['export_custom_field_3'], $input['export_custom_field_4'], $input['export_custom_field_5'], $input['export_custom_field_6']);
            }

            if (! empty($input['dob'])) {
                $input['dob'] = $this->commonUtil->uf_date($input['dob']);
            }

            $assigned_to = $request->input('user_id');

            // Update either lead or customer based on type
            if ($input['type'] === 'customer') {
                // Update a regular customer
                $contact = Contact::find($id);
                if (!$contact) {
                    throw new \Exception('Contact not found');
                }
                
                $contact->update($input);
                
                // Update user contact access
                if (! empty($assigned_to)) {
                    $contact->userHavingAccess()->sync($assigned_to);
                }
            } else {
                // Update a lead (default behavior)
                $contact = CrmContact::updateLead($id, $input, $assigned_to);
            }

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
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
        $can_access_all_leads = auth()->user()->can('crm.access_all_leads');
        $can_access_own_leads = auth()->user()->can('crm.access_own_leads');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module')) || ! ($can_access_all_leads || $can_access_own_leads)) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $query = CrmContact::where('business_id', $business_id);

                if (! $can_access_all_leads && $can_access_own_leads) {
                    $query->OnlyOwnLeads();
                }
                $contact = $query->findOrFail($id);

                $contact->forceDelete();

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    public function convertToCustomer($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $contact = CrmContact::where('business_id', $business_id)->findOrFail($id);

                $contact->type = 'customer';
                $contact->converted_by = auth()->user()->id;
                $contact->converted_on = \Carbon::now();
                $contact->save();

                $customer = Contact::find($contact->id);

                $this->commonUtil->activityLog($customer, 'converted', null, ['update_note' => __('crm::lang.converted_from_leads')]);

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    public function postLifeStage($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $contact = CrmContact::where('business_id', $business_id)->findOrFail($id);

                $contact->crm_life_stage = request()->input('crm_life_stage');
                $contact->save();

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Get contact devices for a specific contact
     *
     * @param  int  $contact_id
     * @return Response
     */
    public function getContactDevices($contact_id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                // Verify the contact exists and belongs to the business
                $contact = CrmContact::where('business_id', $business_id)->findOrFail($contact_id);

                // Get all contact devices with their related models and brands
                $contact_devices = DB::table('contact_device')
                    ->where('contact_device.contact_id', $contact_id)
                    ->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
                    ->join('categories', 'categories.id', '=', 'contact_device.device_id')
                    ->select(
                        'contact_device.id',
                        'contact_device.device_id',
                        'contact_device.models_id',
                        'contact_device.color',
                        'contact_device.chassis_number',
                        'contact_device.plate_number',
                        'contact_device.manufacturing_year',
                        'contact_device.car_type',
                        'repair_device_models.name as model_name',
                        'categories.name as brand_name'
                    )
                    ->get();

                // Get brands and models for dropdowns
                $brands = Category::where('business_id', $business_id)
                    ->where('category_type', 'device')
                    ->select('id', 'name')
                    ->get();

                return view('crm::lead.partials.contact_devices_modal')
                    ->with(compact('contact', 'contact_devices', 'brands'));
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }
        }
    }

    /**
     * Store a new contact device
     *
     * @param  Request  $request
     * @return Response
     */
    public function storeContactDevice(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $validator = Validator::make($request->all(), [
                    'contact_id' => 'required|exists:contacts,id',
                    'device_id' => 'required|exists:categories,id',
                    'models_id' => 'required|exists:repair_device_models,id',
                    'color' => 'required|string|max:255',
                    'plate_number' => 'required|string|max:255',
                    'manufacturing_year' => 'required|string|max:4',
                    'car_type' => 'nullable|string|max:255',
                    'chassis_number' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'msg' => $validator->errors()->first(),
                    ]);
                }

                // Verify the contact exists and belongs to the business
                $contact = CrmContact::where('business_id', $business_id)->findOrFail($request->contact_id);

                // Insert the new contact device
                $device_id = DB::table('contact_device')->insertGetId([
                    'device_id' => $request->device_id,
                    'models_id' => $request->models_id,
                    'color' => $request->color,
                    'chassis_number' => $request->chassis_number,
                    'plate_number' => $request->plate_number,
                    'manufacturing_year' => $request->manufacturing_year,
                    'car_type' => $request->car_type,
                    'contact_id' => $request->contact_id,
                ]);

                return response()->json([
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ]);
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }
        }
    }

    /**
     * Update a contact device
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function updateContactDevice(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $validator = Validator::make($request->all(), [
                    'device_id' => 'required|exists:categories,id',
                    'models_id' => 'required|exists:repair_device_models,id',
                    'color' => 'required|string|max:255',
                    'plate_number' => 'required|string|max:255',
                    'manufacturing_year' => 'required|string|max:4',
                    'car_type' => 'nullable|string|max:255',
                    'chassis_number' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'msg' => $validator->errors()->first(),
                    ]);
                }

                // Update the contact device
                DB::table('contact_device')
                    ->where('id', $id)
                    ->update([
                        'device_id' => $request->device_id,
                        'models_id' => $request->models_id,
                        'color' => $request->color,
                        'chassis_number' => $request->chassis_number,
                        'plate_number' => $request->plate_number,
                        'manufacturing_year' => $request->manufacturing_year,
                        'car_type' => $request->car_type,
                    ]);

                return response()->json([
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ]);
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }
        }
    }

    /**
     * Get a specific contact device for editing
     *
     * @param  int  $id
     * @return Response
     */
    public function getContactDevice($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                // Get the contact device with related data
                $contact_device = DB::table('contact_device')
                    ->where('contact_device.id', $id)
                    ->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
                    ->join('categories', 'categories.id', '=', 'contact_device.device_id')
                    ->select(
                        'contact_device.*',
                        'repair_device_models.name as model_name',
                        'categories.name as brand_name'
                    )
                    ->first();

                if (!$contact_device) {
                    return response()->json([
                        'success' => false,
                        'msg' => __('messages.not_found'),
                    ]);
                }

                // Get brands and models for dropdowns
                $brands = Category::where('business_id', $business_id)
                    ->where('category_type', 'device')
                    ->select('id', 'name')
                    ->get();

                return view('crm::lead.partials.edit_contact_device_modal')
                    ->with(compact('contact_device', 'brands'));
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }
        }
    }

    /**
     * Delete a contact device
     *
     * @param  int  $id
     * @return Response
     */
    public function destroyContactDevice($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                // Delete the contact device
                DB::table('contact_device')
                    ->where('id', $id)
                    ->delete();

                return response()->json([
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ]);
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }
        }
    }

    /**
     * Get models for a specific brand
     *
     * @param  int  $brand_id
     * @return Response
     */
    public function getModelsForBrand($brand_id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $models = DB::table('repair_device_models')
                    ->where('device_id', $brand_id)
                    ->select('id', 'name')
                    ->get();

                return response()->json([
                    'success' => true,
                    'models' => $models,
                ]);
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }
        }
    }

    /**
     * Get contact devices button for a specific contact
     *
     * @param  int  $contact_id
     * @return string
     */
    private function getContactDevicesButton($contact_id)
    {
        // Count the number of contact devices for this contact
        $count = DB::table('contact_device')
            ->where('contact_id', $contact_id)
            ->count();

        $html = '<div style="text-align: center; max-width: 140px; overflow: hidden;">
                    <button type="button"
                        class="tw-dw-btn tw-dw-btn-sm tw-dw-btn-primary contact_devices_btn"
                        data-contact_id="' . $contact_id . '">
                        ' . __('crm::lang.contact_devices') . '
                    </button>
                    <div style="margin-top: 5px; font-weight: bold;">
                        ' . $count . '
                    </div>
                </div>';

        return $html;
    }

    /**
     * Show follow-up log for a lead (for modal view)
     */
    public function showFollowupLog($lead_id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        // Fetch all follow-ups for this lead/contact
        $followups = \Modules\Crm\Entities\Schedule::with(['users', 'createdBy'])
            ->where('business_id', $business_id)
            ->where('contact_id', $lead_id)
            ->orderBy('start_datetime', 'desc')
            ->get();

        return view('crm::lead.followup_log_modal', compact('lead_id', 'followups'));
    }
}
