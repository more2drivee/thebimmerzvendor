<?php

namespace Modules\Repair\Http\Controllers;

use App\Barcode;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\CustomerGroup;
use App\Media;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\SmsUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Warranty;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Repair\Entities\DeviceModel;
use Modules\Repair\Entities\RepairStatus;
use Modules\Repair\Entities\JobSheet;
use Modules\Repair\Utils\RepairUtil;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RepairController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $contactUtil;

    protected $businessUtil;

    protected $transactionUtil;

    protected $productUtil;

    protected $moduleUtil;

    protected $repairUtil;

    protected $commonUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ContactUtil $contactUtil, BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, RepairUtil $repairUtil, Util $commonUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->repairUtil = $repairUtil;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && (auth()->user()->can('repair.view') || auth()->user()->can('repair.view_own'))))) {
            abort(403, 'Unauthorized action.');
        }

        $is_admin = $this->commonUtil->is_admin(auth()->user(), $business_id);

        if (request()->ajax()) {
            $sells = Transaction::where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', '!=', 'draft')
                ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('business_locations AS bl', 'transactions.location_id', '=', 'bl.id')
                ->leftJoin('repair_job_sheets AS rjs', 'transactions.repair_job_sheet_id', '=', 'rjs.id')
                ->leftJoin('repair_statuses AS rs', 'rjs.status_id', '=', 'rs.id')
                ->leftJoin('bookings', 'rjs.booking_id', '=', 'bookings.id')
                ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')
                ->leftJoin('categories AS device', 'contact_device.device_id', '=', 'device.id')
                ->leftJoin('categories AS b', 'transactions.repair_brand_id', '=', 'b.id')
                ->leftJoin('repair_device_models AS rdm', 'transactions.repair_model_id', '=', 'rdm.id')
                ->leftJoin('users AS created_by_user', 'transactions.created_by', '=', 'created_by_user.id')
                ->leftJoin('warranties AS rw', 'transactions.repair_warranty_id', '=', 'rw.id')
                ->leftJoin('transactions AS SR', 'transactions.id', '=', 'SR.return_parent_id')
                ->with([
                    'payment_lines',
                    'job_sheet',
                    'job_sheet.booking.serviceType',
                    'return_parent'
                ])
                ->where('transactions.business_id', $business_id)
                ->where('transactions.sub_type', 'repair')
                ->select(
                    'transactions.id',
                    'transactions.Exit_permission',
                    'transactions.transaction_date',
                    'transactions.is_direct_sale',
                    'transactions.invoice_no',
                    'transactions.status as transactions_status',
                    'transactions.contact_id',
                    'transactions.payment_status',
                    'transactions.final_total',
                    'transactions.tax_amount',
                    'transactions.discount_amount',
                    'transactions.discount_type',
                    'transactions.total_before_tax',
                    'transactions.repair_status_id',
                    'transactions.repair_serial_no',
                    'transactions.repair_completed_on',
                    'transactions.repair_warranty_id',
                    'transactions.repair_due_date as due_date',
                    'transactions.is_due_transaction',
                    'transactions.repair_updates_notif',
                    'transactions.location_id',
                    'transactions.repair_job_sheet_id',
                    'transactions.res_waiter_id',
                    'transactions.created_by',
                    'transactions.business_id',
                    'transactions.deleted_at',
                    'rjs.job_sheet_no',
                    'rjs.service_staff',
                    'rs.name as repair_status',
                    'rs.color as status_color',
                    'contacts.name',
                    'contacts.mobile as contact_mobile',
                    'b.name as brand',
                    'rdm.name as device_model',
                    'contact_device.chassis_number as vin_number',
                    'contact_device.chassis_number',
                    'contact_device.plate_number',
                     'bl.name as business_location',
                     'rw.name as warranty_name',
                     DB::raw('CONCAT(COALESCE(created_by_user.first_name, ""), " ", COALESCE(created_by_user.last_name, "")) as added_by'),
                     DB::raw('(SELECT COALESCE(SUM(tsl.unit_price * tsl.quantity - COALESCE(tsl.line_discount_amount, 0)), 0)
                        FROM transaction_sell_lines tsl
                        JOIN products p ON tsl.product_id = p.id
                        WHERE tsl.transaction_id = transactions.id AND p.enable_stock = 1) as total_spare_parts'),
                     DB::raw('(SELECT COALESCE(SUM(pl.purchase_price * slpl.quantity), 0)
                        FROM transaction_sell_lines tsl
                        JOIN products p ON tsl.product_id = p.id
                        LEFT JOIN transaction_sell_lines_purchase_lines slpl ON slpl.sell_line_id = tsl.id
                        LEFT JOIN purchase_lines pl ON slpl.purchase_line_id = pl.id
                        WHERE tsl.transaction_id = transactions.id AND p.enable_stock = 1) as purchasing_cost'),
                     DB::raw('(SELECT COALESCE(SUM(pl.purchase_price * slpl.quantity), 0)
                        FROM transaction_sell_lines tsl
                        JOIN products p ON tsl.product_id = p.id
                        LEFT JOIN transaction_sell_lines_purchase_lines slpl ON slpl.sell_line_id = tsl.id
                        LEFT JOIN purchase_lines pl ON slpl.purchase_line_id = pl.id
                        WHERE tsl.transaction_id = transactions.id AND p.enable_stock = 0) as labour_cost'),
                     DB::raw('(SELECT COALESCE(SUM(exp.final_total), 0)
                        FROM transactions exp
                        WHERE exp.invoice_ref = transactions.id AND exp.type = "expense") as total_expenses'),
                     DB::raw('transactions.final_total -
                        (SELECT COALESCE(SUM(pl.purchase_price * slpl.quantity), 0)
                        FROM transaction_sell_lines tsl
                        LEFT JOIN transaction_sell_lines_purchase_lines slpl ON slpl.sell_line_id = tsl.id
                        LEFT JOIN purchase_lines pl ON slpl.purchase_line_id = pl.id
                        WHERE tsl.transaction_id = transactions.id) -
                        (SELECT COALESCE(SUM(exp.final_total), 0)
                        FROM transactions exp
                        WHERE exp.invoice_ref = transactions.id AND exp.type = "expense") as net_profit'),
                     DB::raw('(SELECT GROUP_CONCAT(DISTINCT CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) SEPARATOR ", ")
                        FROM user_contact_access uca
                        JOIN users u ON uca.user_id = u.id
                        WHERE uca.contact_id = contacts.id) as crm_assigned_users'),
                     DB::raw('(SELECT SUM(IF(TP.is_return = 1,-1*TP.amount,TP.amount)) FROM transaction_payments AS TP WHERE TP.transaction_id=transactions.id) as total_paid'),
                     DB::raw('COUNT(SR.id) as return_exists'),
                     DB::raw('COALESCE(SR.final_total, 0) as amount_return'),
                     DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE TP2.transaction_id=SR.id) as return_paid'),
                     'SR.id as return_transaction_id'
                );

                $permitted_locations = auth()->user()->permitted_locations();
                if ($permitted_locations != 'all') {
                    $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (! auth()->user()->can('repair.view') && auth()->user()->can('repair.view_own')) {
                $sells->where(function ($q) {
                    $q->where('transactions.created_by', auth()->user()->id)
                    ->orWhere('transactions.res_waiter_id', auth()->user()->id);
                });
            }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (! empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            if (! empty(request()->input('payment_status'))) {
                $sells->where('transactions.payment_status', request()->input('payment_status'));
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (! empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (! empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;

                $sells->whereDate('transactions.transaction_date', '>=', $start)
                ->whereDate('transactions.transaction_date', '<=', $end);
            }

            // Filter by technician assigned on the job sheet (service_staff JSON or legacy single ID)
            $serviceStaffFilter = request()->input('service_staff_id') ?: request()->input('technician');
            if (! empty($serviceStaffFilter)) {
                $technicianId = (int) $serviceStaffFilter;
                $sells->where(function ($q) use ($technicianId) {
                    // New JSON format: rjs.service_staff is a JSON array of user IDs
                    $q->where(function ($sub) use ($technicianId) {
                        $sub->whereRaw('JSON_VALID(rjs.service_staff)')
                            ->whereRaw('JSON_CONTAINS(rjs.service_staff, ?)', [json_encode($technicianId)]);
                    })
                    // Legacy plain ID format (non-JSON)
                    ->orWhere('rjs.service_staff', (string) $technicianId);
                });
            }

            if (! empty(request()->repair_status_id)) {
                $sells->where('repair_job_sheets.status_id', request()->repair_status_id);
            }

            // Filter by assigned user
            if (! empty(request()->input('assigned_to'))) {
                $assigned_to_id = request()->input('assigned_to');
                $sells->whereExists(function ($query) use ($assigned_to_id) {
                    $query->select(DB::raw(1))
                        ->from('user_contact_access')
                        ->whereRaw('user_contact_access.contact_id = contacts.id')
                        ->where('user_contact_access.user_id', $assigned_to_id);
                });
            }

            //filter out mark as completed status
            // $sells->where('rs.is_completed_status', request()->get('is_completed_status'));
                // Check if we need to filter by completed or pending repairs
                // Check if we need to filter by completed or pending repairs
                if (request()->get('is_completed_status')) {
                    $sells->where('transactions.status', 'final'); // Completed repairs
                } else {
                    $sells->where('transactions.status', 'under processing'); // Pending repairs
        }

        $sells->groupBy('transactions.id');

            // Order by created_at descending (newest first)
        $sells->orderBy('transactions.created_at', 'desc');

                $datatable = Datatables::of($sells)
                ->addColumn(
                        'action',
                        function ($row) use ($is_admin, $business_id) {
                            $html = '<div class="btn-group">
                                        <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max dropdown-toggle"
                                            data-toggle="dropdown" aria-expanded="false">'.
                                            __('messages.actions').
                                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                            </span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                            if (auth()->user()->can('repair.view') || auth()->user()->can('direct_sell.access')) {
                                $html .= '<li><a href="#" data-href="'.action([\Modules\Repair\Http\Controllers\RepairController::class, 'show'], [$row->id]).'" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i> '.__('messages.view').'</a></li>';
                            }

                            if (auth()->user()->can('repair.update')) {
                                $html .= '<li><a target="_blank" href="'.action([\App\Http\Controllers\SellController::class, 'edit'], [$row->id]).'"><i class="fas fa-edit"></i> '.__('messages.edit').'</a></li>';
                            }

                            if (auth()->user()->can('repair.delete')) {
                                $is_deleted_flag = !empty($row->deleted_at) ? 1 : 0;
                                $html .= '<li><a href="#" data-href="'.action([\Modules\Repair\Http\Controllers\RepairController::class, 'destroy'], [$row->id]).'" data-is-deleted="'.$is_deleted_flag.'" class="delete-repair-transaction"><i class="fa fa-trash"></i> '.__('messages.delete').'</a></li>';
                            }

                            if (auth()->user()->can('repair.view') || auth()->user()->can('direct_sell.access')) {
                              $html .= '
                                                        <li>
                                                                <a href="' . route('sell.printCleanInvoice', ['transaction_id' => $row->id]) . '" class="btn btn-info no-print" target="_blank">
                                                                        <i class="fas fa-file-alt"></i> ' . __('messages.print') . '
                                                                </a>
                                                        </li>';
                                                            // Transaction overview button (opens new tab)
                                                        }
                            if(auth()->user()->can('repair.update') || auth()->user()->can('direct_sell.access')){
                                $html .= '<li><a href="'.route('treasury.transaction_overview', ['transaction_id' => $row->id]).'" target="_blank"><i class="fas fa-chart-pie"></i> '.__('repair::lang.transaction_overview').'</a></li>';
                                $html .= '<li><a href="'.route('repair.transaction.technician_efficiency', ['transaction' => $row->id]).'" target="_blank"><i class="fas fa-chart-line"></i> '.__('repair::lang.technician_efficiency').'</a></li>';
                            }
                            $html .= '<li class="divider"></li>';

                            if (auth()->user()->can('repair.create')) {
                                $html .= '<li><a href="'.action([\App\Http\Controllers\SellReturnController::class, 'add'], [$row->id]).'"><i class="fas fa-undo"></i> '.__('lang_v1.sell_return').'</a></li>';
                            }
                      

                            if (auth()->user()->can('repair_status.update')) {
                                $html .= '<li><a data-href="'.action([\Modules\Repair\Http\Controllers\RepairController::class, 'editRepairStatus'], [$row->id]).'" class="edit_repair_status"><i class="fa fa-edit"></i> '.__('repair::lang.change_status').'</a></li>';
                            }

                            if (auth()->user()->can('customer.update') || auth()->user()->can('supplier.update')) {
                                $html .= '<li><a data-href="' . route('repair.contacts.edit_basic', [$row->contact_id]) . '" class="repair-edit-contact-basic"><i class="fas fa-user-edit"></i> ' . __('contact.edit_contact') . '</a></li>';
                            }

                            if ($row->payment_status != 'paid' && (auth()->user()->can('repair.create') || auth()->user()->can('direct_sell.access'))) {
                                $html .= '<li><a href="'.action([\App\Http\Controllers\TransactionPaymentController::class, 'addPayment'], [$row->id]).'" class="add_payment_modal"><i class="fas fa-money-bill-alt"></i> '.__('purchase.add_payment').'</a></li>';
                            }

                            $html .= '<li><a href="'.action([\App\Http\Controllers\TransactionPaymentController::class, 'show'], [$row->id]).'" class="view_payment_modal"><i class="fas fa-money-bill-alt"></i> '.__('purchase.view_payments').'</a></li>';

                            if (auth()->user()->can('repair.update') || auth()->user()->can('direct_sell.access')) {
                                $html .= '<li><a href="#" class="send-survey-action" data-transaction-id="'.$row->id.'" data-contact-id="'.$row->contact_id.'" data-contact-name="'.e($row->name).'" data-contact-mobile="'.e($row->contact_mobile).'"><i class="fas fa-comment-dots"></i> '.__('survey::lang.sendperson').'</a></li>';
                            }

                                $html .= '<li><a href="' . action([\App\Http\Controllers\SellPosController::class, 'showInvoiceUrl'], [$row->id]) . '" class="view_invoice_url tw-inline-flex tw-items-center tw-gap-1 tw-px-2 tw-py-1 tw-rounded tw-bg-indigo-600 hover:tw-bg-indigo-700 tw-text-white tw-text-xs"><i class="fas fa-eye"></i> ' . __('lang_v1.view_invoice_url') . '</a></li>';

                                $html .= '<li><a href="#" data-href="' . action([\App\Http\Controllers\SellPosController::class, 'shareInvoiceLinks'], [$row->id]) . '" data-sms-url="' . action([\App\Http\Controllers\SellPosController::class, 'sendInvoiceSms'], [$row->id]) . '" class="share_invoice tw-inline-flex tw-items-center tw-gap-1 tw-px-2 tw-py-1 tw-rounded tw-bg-blue-600 hover:tw-bg-blue-700 tw-text-white tw-text-xs tw-transition-colors tw-duration-200" aria-label="' . e(__('lang_v1.share_invoice')) . '" tabindex="0"><i class="fa fa-share-alt"></i> ' . __('lang_v1.share_invoice') . '</a></li>';
                      


                            // Add Exit Permission toggle button
                            if (auth()->user()->can('repair.update')) {
                                $exit_permission = $row->Exit_permission ?? 'Exit not allowed';
                                $is_checked = ($exit_permission == 'Exit allowed' || $exit_permission == 'Exited') ? true : false;

                                $html .= '<li class="divider"></li>';
                                $html .= '<li>
                                    <a href="javascript:void(0);" class="toggle-exit-permission" data-id="'.$row->id.'" data-status="'.($is_checked ? 'true' : 'false').'">
                                        <i class="fa fa-sign-out-alt"></i>
                                        <span class="exit-permission-label">'.($is_checked ? __('repair::lang.exit_allowed') : __('repair::lang.exit_not_allowed')).'</span>
                                    </a>
                                </li>';
                            }

                            $html .= '</ul></div>';

                            return $html;
                        }
                    )
                    ->removeColumn('id')
                    ->editColumn('final_total', function ($row) {
                        $html = '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="'.$row->final_total.'">'.$row->final_total.'</span>';
                        if (!empty($row->return_exists)) {
                            $return_amount = (float) $row->amount_return;
                            $net_total = $row->final_total - $return_amount;
                            $html .= '<br><small class="text-danger"><i class="fas fa-undo"></i> '.__('repair::lang.sell_return').': <span class="display_currency" data-currency_symbol="true">-'.$return_amount.'</span></small>';
                            $html .= '<br><small class="text-success"><strong>'.__('repair::lang.net_after_return').': <span class="display_currency" data-currency_symbol="true">'.$net_total.'</span></strong></small>';
                        }
                        return $html;
                    })
                    ->editColumn(
                        'tax_amount',
                        '<span class="display_currency total-tax" data-currency_symbol="true" data-orig-value="{{$tax_amount}}">{{$tax_amount}}</span>'
                    )
                    ->editColumn(
                        'total_before_tax',
                        '<span class="display_currency total_before_tax" data-currency_symbol="true" data-orig-value="{{$total_before_tax}}">{{$total_before_tax}}</span>'
                    )
                    ->editColumn(
                        'discount_amount',
                        function ($row) {
                            $discount = ! empty($row->discount_amount) ? $row->discount_amount : 0;

                            if (! empty($discount) && $row->discount_type == 'percentage') {
                                $discount = $row->total_before_tax * ($discount / 100);
                            }

                            return '<span class="display_currency total-discount" data-currency_symbol="true" data-orig-value="'.$discount.'">'.$discount.'</span>';
                        }
                    )
                    ->editColumn('due_date', '
                            @if(!empty($due_date))
                                {{@format_datetime($due_date)}}
                            @endif
                    ')
                    ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                    ->editColumn(
                        'payment_status',
                        function ($row) {
                            if ($row->payment_status == 'due' && $row->final_total == 0) {
                                return '';
                            }
                            $status = $row->payment_status ?? '';
                            
                            // Show 'postponed' instead of 'due' when is_due_transaction flag is set
                            if ($status == 'due' && $row->is_due_transaction == 1) {
                                $status = 'postponed';
                            }
                            
                            $status_class = $status == 'partial' ? 'bg-primary' : ($status == 'due' ? 'bg-warning' : ($status == 'paid' ? 'bg-success' : ($status == 'postponed' ? 'bg-info' : '')));
                            return '<a href="' . action([\App\Http\Controllers\TransactionPaymentController::class, 'show'], [$row->id]) . '"
                                class="view_payment_modal payment-status-label no-print"
                                data-orig-value="' . ($row->payment_status ?? ' ') . '"
                                data-status-name="' . ($status ? __('treasury::lang.' . $status) : ' ') . '">
                                <span class="label ' . $status_class . '">' . ($status ? __('treasury::lang.' . $status) : ' ') . '
                                </span>
                            </a>
                            <span class="print_section">' . ($status ? __('treasury::lang.' . $status) : ' ') . '</span>';
                        }
                    )

                    ->addColumn('total_remaining', function ($row) {
                        $sell_return_amount = !empty($row->return_exists) ? (float)$row->amount_return : 0;
                        $effective_total = $row->final_total - $sell_return_amount;
                        $total_remaining = $effective_total - $row->total_paid;
                        $total_remaining_html = '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="'.$total_remaining.'">'.$total_remaining.'</span>';

                        return $total_remaining_html;
                    })
                    ->editColumn('total_spare_parts', function ($row) {
                        return '<span class="display_currency" data-currency_symbol="true" data-orig-value="'.$row->total_spare_parts.'">'.$row->total_spare_parts.'</span>';
                    })
                    ->editColumn('purchasing_cost', function ($row) {
                        return '<span class="display_currency" data-currency_symbol="true" data-orig-value="'.$row->purchasing_cost.'">'.$row->purchasing_cost.'</span>';
                    })
                    ->editColumn('labour_cost', function ($row) {
                        return '<span class="display_currency" data-currency_symbol="true" data-orig-value="'.$row->labour_cost.'">'.$row->labour_cost.'</span>';
                    })
                    ->editColumn('total_expenses', function ($row) {
                        return '<span class="display_currency" data-currency_symbol="true" data-orig-value="'.$row->total_expenses.'">'.$row->total_expenses.'</span>';
                    })
                    ->editColumn('net_profit', function ($row) {
                        return '<span class="display_currency" data-currency_symbol="true" data-orig-value="'.$row->net_profit.'">'.$row->net_profit.'</span>';
                    })
                    ->editColumn('crm_assigned_users', function ($row) {
                        if (!empty($row->crm_assigned_users)) {
                            return '<span class="label bg-info">'.e($row->crm_assigned_users).'</span>';
                        } else {
                            return '<span class="label bg-gray">'.__('repair::lang.no_followup').'</span>';
                        }
                    })
                    ->editColumn('invoice_no', function ($row) {
                        $invoice_no = $row->invoice_no;

                        if (! empty($row->return_exists)) {
                            $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="'.__('lang_v1.some_qty_returned_from_sell').'"><i class="fas fa-undo"></i></small>';
                        }

                        // Append deleted badge if this transaction is soft deleted
                        if (!empty($row->deleted_at)) {
                            $invoice_no .= ' &nbsp;<small class="label bg-gray label-round no-print" title="'.e(__('lang_v1.deleted')).'"><i class="fas fa-trash"></i></small>';
                        }

                        return $invoice_no;
                    })
                    ->editColumn(
                        'repair_status',
                        function ($row) {
                            $html = '<a data-href="'.action([\Modules\Repair\Http\Controllers\RepairController::class, 'editRepairStatus'], [$row->id]).'" class="edit_repair_status" data-orig-value="'.$row->repair_status.'" data-status-name="'.$row->repair_status.'">
                                    <span class="label " style="background-color:'.$row->status_color.';" >
                                        '.$row->repair_status.'
                                    </span>
                                </a>
                            ';

                            if ($row->repair_updates_notif) {
                                $tooltip = __('repair::lang.sms_sent');
                                $html .= '<br><i class="fas fa-check-double text-success"
                                    data-toggle="tooltip" title="'.$tooltip.'"></i>';
                            }

                            return $html;
                        }
                    )
                    ->addColumn('technecian', function ($row) {
                        // service_staff is already selected from rjs.service_staff join
                        $raw = $row->service_staff ?? null;
                        
                        if (empty($raw)) {
                            return '';
                        }
                        
                        // Try to decode as JSON first (new format - array of IDs)
                        $decoded = json_decode($raw, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $service_staff_ids = $decoded;
                        } else {
                            // Fallback: treat raw value as a single ID
                            $service_staff_ids = [$raw];
                        }
                        
                        // Normalize to integer IDs and remove empties
                        $service_staff_ids = array_values(array_filter(array_map('intval', $service_staff_ids)));

                        if (!empty($service_staff_ids)) {
                            $technician = DB::table('users')
                                ->whereIn('id', $service_staff_ids)
                                ->select(DB::raw("TRIM(CONCAT_WS(' ', COALESCE(surname, ''), COALESCE(first_name, ''), COALESCE(last_name, ''))) as full_name"))
                                ->pluck('full_name')
                                ->filter()
                                ->implode(', ');
                        } else {
                            $technician = '';
                        }

                        return $technician;
                    })
                    ->addColumn('exit_permission', function ($row) {
                        $exit_permission = $row->Exit_permission ?? 'Exit not allowed';

                        $color = match ($exit_permission) {
                            'Exit allowed' => '#28a745', // Green
                            'Exited' => '#007bff',       // Blue
                            default => '#dc3545'         // Red (Exit not allowed)
                        };

                        return '<span style="background-color: ' . $color . '; color: #fff; padding: 5px 10px; border-radius: 6px; font-size: 14px; min-width: 80px; text-align: center; display: inline-block;">' .
                            __('repair::lang.' . strtolower(str_replace(' ', '_', $exit_permission))) .
                        '</span>';
                    })
                    ->addColumn('return_due', function ($row) {
                        $return_due_html = '';
                        if (! empty($row->return_exists)) {
                            $return_due = $row->amount_return - $row->return_paid;
                            $return_due_html .= '<a href="'.action([\App\Http\Controllers\TransactionPaymentController::class, 'show'], [$row->return_transaction_id]).'" class="view_purchase_return_payment_modal"><span class="display_currency sell_return_due" data-currency_symbol="true" data-orig-value="'.$return_due.'">'.$return_due.'</span></a>';
                        }

                        return $return_due_html;
                    })
                    ->editColumn('transactions_status', function ($row) {
                        $status = strtolower($row->transactions_status);

                        $translations = [
                            'under processing' => 'قيد العمل',
                            'final' => 'منتهي'
                        ];

                        $color = match (true) {
                            str_contains($status, 'under processing') => '#f39c12', // Orange
                            str_contains($status, 'final') => '#dc3545', // Red
                            default => '#6c757d' // Gray (default)
                        };

                        return !empty($row->transactions_status)
                            ? '<span style="background-color: ' . $color . '; color: #fff; padding: 5px 10px; border-radius: 6px; font-size: 14px; min-width: 80px; text-align: center;">'
                                . ($translations[$status] ?? e($row->transactions_status)) .
                            '</span>'
                            : ' ';
                    })

                    ->editColumn('job_sheet_no', function ($row) {
                        $html = $row->job_sheet_no;
                        if (!empty($row->job_sheet_id)
                            && (auth()->user()->can('job_sheet.view_assigned')
                            || auth()->user()->can('job_sheet.view_all')
                            || auth()->user()->can('job_sheet.create')))
                        {
                            $html = '<a href="'.action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'show'], [$row->job_sheet_id]).'" class="cursor-pointer" target="_blank">
                                        '.$row->job_sheet_no.'
                                    </a>';
                        }

                        return $html;
                    })
                    ->setRowAttr([
                        'data-href' => function ($row) {
                            if (auth()->user()->can('sell.view')) {
                                return  action([\Modules\Repair\Http\Controllers\RepairController::class, 'show'], [$row->id]);
                            } else {
                                return '';
                            }
                        }, ]);

                $rawColumns = ['action', 'invoice_no', 'payment_status', 'repair_status', 'exit_permission', 'total_remaining', 'return_due', 'transactions_status', 'final_total', 'due_date', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'repair_status', 'warranty_name', 'return_due', 'job_sheet_no', 'exit_permission', 'total_spare_parts', 'purchasing_cost', 'labour_cost', 'total_expenses', 'net_profit', 'crm_assigned_users'];

                return $datatable->rawColumns($rawColumns)
                        ->make(true);
            }

            $business_locations = BusinessLocation::forDropdown($business_id, false);
            $customers = Contact::customersDropdown($business_id, false);
            $service_staffs = $this->transactionUtil->serviceStaffDropdown($business_id);
            $repair_status_dropdown = RepairStatus::forDropdown($business_id);
            $sales_representative = User::forDropdown($business_id, false, false, true);
            $user_role_as_service_staff = auth()->user()->roles()
                                        ->where('is_service_staff', 1)
                                        ->get()
                                        ->toArray();
            $is_service_staff = false;
            if (! empty($user_role_as_service_staff) && ! $is_admin) {
                $is_service_staff = true;
            }

            $assigned_users = User::forDropdown($business_id, false);

            return view('repair::repair.index')->with(compact('business_locations', 'customers', 'service_staffs', 'repair_status_dropdown', 'sales_representative', 'is_service_staff', 'is_admin', 'assigned_users'));
    }

    /**
     * Two-stage delete for repair transactions listed in this controller.
     *
     * First stage (soft delete):
     *   - Called without force_delete flag.
     *   - Uses TransactionUtil->deleteSale() to perform the normal delete
     *     flow (stock reversal, payments handling, etc.) which ends with a
     *     soft delete of the transaction.
     *
     * Second stage (hard delete):
     *   - Called with force_delete=true and a valid admin/superadmin password.
     *   - Only allowed if the transaction is already soft-deleted.
     *   - Permanently removes the transaction record.
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.delete')))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $force_delete = (bool) request()->input('force_delete', false);

                // Include soft-deleted repair transactions so we can perform
                // the second-stage delete when needed.
                $transaction = Transaction::withTrashed()
                    ->where('business_id', $business_id)
                    ->where('id', $id)
                    ->where('sub_type', 'repair')
                    ->firstOrFail();

                if (! $force_delete) {
                    // First stage: perform the normal sale deletion flow only
                    // if this transaction is not already soft-deleted.
                    if (empty($transaction->deleted_at)) {
                        $output = $this->transactionUtil->deleteSale($business_id, $transaction->id);

                        // If the repair sale was deleted successfully, also
                        // soft-delete the linked job sheet (if any).
                        if (! empty($output['success']) && ! empty($transaction->repair_job_sheet_id)) {
                            $job_sheet = JobSheet::where('business_id', $business_id)
                                ->where('id', $transaction->repair_job_sheet_id)
                                ->first();

                            if ($job_sheet && empty($job_sheet->deleted_at)) {
                                $job_sheet->delete();
                            }
                        }

                        // Ensure a consistent response structure.
                        if (! empty($output['success'])) {
                            return [
                                'success' => true,
                                'msg' => $output['msg'] ?? __('lang_v1.success'),
                            ];
                        }

                        return [
                            'success' => false,
                            'msg' => $output['msg'] ?? __('messages.something_went_wrong'),
                        ];
                    }

                    // If transaction is already soft-deleted, still ensure
                    // the linked job sheet (if any) is also soft-deleted.
                    if (! empty($transaction->repair_job_sheet_id)) {
                        $job_sheet = JobSheet::where('business_id', $business_id)
                            ->where('id', $transaction->repair_job_sheet_id)
                            ->first();

                        if ($job_sheet && empty($job_sheet->deleted_at)) {
                            $job_sheet->delete();
                        }
                    }

                    return [
                        'success' => true,
                        'msg' => __('lang_v1.success'),
                    ];
                }

                // Second stage: hard delete, only for admins and only if
                // already soft-deleted.

                $is_admin = $this->commonUtil->is_admin(auth()->user(), $business_id);
                if (! $is_admin && ! auth()->user()->can('superadmin')) {
                    abort(403, 'Unauthorized action.');
                }

                if (empty($transaction->deleted_at)) {
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
                    $transaction->forceDelete();

                    DB::commit();

                    return [
                        'success' => true,
                        'msg' => __('lang_v1.success'),
                    ];
                } catch (\Exception $e) {
                    DB::rollBack();

                    Log::error('Failed to hard delete repair transaction', [
                        'transaction_id' => $transaction->id,
                        'business_id' => $business_id,
                        'error' => $e->getMessage(),
                    ]);

                    return [
                        'success' => false,
                        'msg' => __('messages.something_went_wrong'),
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error in RepairController@destroy', [
                    'transaction_id' => $id,
                    'business_id' => $business_id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.create')))) {
            abort(403, 'Unauthorized action.');
        }

        //Check if subscribed or not, then check for users quota
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\HomeController::class, 'index']));
        } elseif (! $this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action([\App\Http\Controllers\SellPosController::class, 'index']));
        }

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $default_location = null;
        if (count($business_locations) == 1) {
            foreach ($business_locations as $id => $name) {
                $default_location = $id;
            }
        }

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id);
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }

        $customer_groups = CustomerGroup::forDropdown($business_id);

        $default_datetime = $this->businessUtil->format_date('now', true);

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $repair_statuses = RepairStatus::getRepairSatuses($business_id);
        $warranties = Warranty::forDropdown($business_id);

        $brands = Brands::forDropdown($business_id);

        $service_staff = [];
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staff = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $checklist = Business::where('id', $business_id)->value('repair_checklist');
        $checklist = ! empty($checklist) ? json_decode($checklist, true) : [];

        $repair_settings = $this->repairUtil->getRepairSettings($business_id);

        return view('repair::repair.create')
                ->with(compact(
                    'business_details',
                    'taxes',
                    'walk_in_customer',
                    'business_locations',
                    'bl_attributes',
                    'default_location',
                    'commission_agent',
                    'customer_groups',
                    'default_datetime',
                    'pos_settings',
                    'repair_statuses',
                    'types',
                    'brands',
                    'service_staff',
                    'checklist',
                    'warranties',
                    'repair_settings'
            ));
    }

    /**
     * Show the specified resource.
     *
     * @return Response
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.view')))) {
            abort(403, 'Unauthorized action.');
        }

        $taxes = TaxRate::where('business_id', $business_id)
                            ->pluck('name', 'id');

                        $sell = Transaction::where('transactions.business_id', $business_id)
                    ->where('transactions.id', $id)
                    ->leftJoin(
                        'repair_job_sheets AS repair_job_sheets',
                        'transactions.repair_job_sheet_id',
                        '=',
                        'repair_job_sheets.id'
                    )
                    ->leftJoin('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')

                    ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')

                    ->leftJoin(
                        'repair_statuses AS rs',
                        'transactions.repair_status_id',
                        '=',
                        'rs.id'
                    )
                    ->leftJoin(
                        'users AS service_staff',
                        'transactions.res_waiter_id',
                        '=',
                        'service_staff.id'
                    )

                    ->leftJoin(
                        'warranties as rw',
                        'rw.id',
                        '=',
                        'transactions.repair_warranty_id'
                    )
                    ->leftJoin(
                        'repair_device_models as rdm',
                        'rdm.id',
                        '=',
                        'transactions.repair_model_id'
                    )
                    ->leftJoin(
                        'categories AS b',
                        'transactions.repair_brand_id',
                        '=',
                        'b.id'
                    )
                    ->with(['contact', 'return_parent', 'sell_lines' => function ($q) {
                        $q->whereNull('parent_sell_line_id');
                    }, 'sell_lines.product', 'sell_lines.product.unit', 'sell_lines.variations', 'sell_lines.variations.product_variation', 'payment_lines', 'sell_lines.modifiers', 'sell_lines.lot_details', 'tax', 'sell_lines.sub_unit', 'media'])
                    ->select(
                        'transactions.*',
                        'rs.name as repair_status',
                        'rs.color as repair_status_color',
                        DB::raw('CONCAT( COALESCE(service_staff.first_name, ""), " ", COALESCE(service_staff.last_name, "") ) as service_staff'),
                        // 'service_staff.address',
                        'b.name as manufacturer',
                        'rw.name as warranty_name',
                        'rw.duration',
                        'rw.duration_type',
                        'rdm.name as repair_model',
                        'b.name as brand',
                        'contact_device.id AS contact_device_id',
                        'contact_device.manufacturing_year',
                        'contact_device.chassis_number AS chassis_number',
                        'contact_device.plate_number',
                        'contact_device.color',
                        'contact_device.car_type',
                        'repair_job_sheets.job_sheet_no',
                        'repair_job_sheets.km as km',

                    )
                    ->first();



        foreach ($sell->sell_lines as $key => $value) {
            if (! empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }
        }

        $payment_types = $this->transactionUtil->payment_types();

        $warranty_expires_in = $this->repairUtil->repairWarrantyExpiresIn($sell);

        $order_taxes = [];
        if (! empty($sell->tax)) {
            if ($sell->tax->is_tax_group) {
                $order_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($sell->tax, $sell->tax_amount));
            } else {
                $order_taxes[$sell->tax->name] = $sell->tax_amount;
            }
        }

        $activities = Activity::forSubject($sell)
           ->with(['causer', 'subject'])
           ->latest()
           ->get();

        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = ! empty($common_settings['enable_product_warranty']) ? true : false;

        $checklists = [];
        if (! empty($sell->repair_model_id)) {
            $device_model = DeviceModel::where('business_id', $business_id)
                            ->find($sell->repair_model_id);

            if (! empty($device_model) && ! empty($device_model->repair_checklist)) {
                $checklists = explode('|', $device_model->repair_checklist);
            }
        }

        //merge default checklist
        $repair_settings = $this->repairUtil->getRepairSettings($business_id);
        if (! empty($repair_settings['default_repair_checklist'])) {
            $checklists = array_merge(explode('|', $repair_settings['default_repair_checklist']), $checklists);
        }

        return view('repair::repair.show')
            ->with(compact('taxes', 'sell', 'payment_types', 'order_taxes', 'activities', 'warranty_expires_in', 'is_warranty_enabled', 'checklists'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.update')))) {
            abort(403, 'Unauthorized action.');
        }

        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (! $this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days]), ]);
        }

        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', ['success' => 0,
                'msg' => __('lang_v1.return_exist'), ]);
        }

        $business_id = request()->session()->get('user.business_id');

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $transaction = Transaction::where('business_id', $business_id)
                            ->where('type', 'sell')
                            ->where('sub_type', 'repair')
                            ->with('return_parent')
                            ->findorfail($id);

        $location_id = $transaction->location_id;
        $location_printer_type = BusinessLocation::find($location_id)->receipt_printer_type;

        $sell_details = TransactionSellLine::join(
                            'products AS p',
                            'transaction_sell_lines.product_id',
                            '=',
                            'p.id'
                        )
                        ->join(
                            'variations AS variations',
                            'transaction_sell_lines.variation_id',
                            '=',
                            'variations.id'
                        )
                        ->join(
                            'product_variations AS pv',
                            'variations.product_variation_id',
                            '=',
                            'pv.id'
                        )
                        ->leftjoin('variation_location_details AS vld', function ($join) use ($location_id) {
                            $join->on('variations.id', '=', 'vld.variation_id')
                                ->where('vld.location_id', '=', $location_id);
                        })
                        ->leftjoin('units', 'units.id', '=', 'p.unit_id')
                        ->where('transaction_sell_lines.transaction_id', $id)
                        ->select(
                            DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':',variations.name, ')'), p.name) AS product_name"),
                            'p.id as product_id',
                            'p.enable_stock',
                            'p.name as product_actual_name',
                            'pv.name as product_variation_name',
                            'pv.is_dummy as is_dummy',
                            'variations.name as variation_name',
                            'variations.sub_sku',
                            'p.barcode_type',
                            'p.enable_sr_no',
                            'variations.id as variation_id',
                            'units.short_name as unit',
                            'units.allow_decimal as unit_allow_decimal',
                            'transaction_sell_lines.tax_id as tax_id',
                            'transaction_sell_lines.item_tax as item_tax',
                            'transaction_sell_lines.unit_price as default_sell_price',
                            'transaction_sell_lines.unit_price_inc_tax as sell_price_inc_tax',
                            'transaction_sell_lines.unit_price_before_discount as unit_price_before_discount',
                            'transaction_sell_lines.id as transaction_sell_lines_id',
                            'transaction_sell_lines.quantity as quantity_ordered',
                            'transaction_sell_lines.sell_line_note as sell_line_note',
                            'transaction_sell_lines.lot_no_line_id',
                            'transaction_sell_lines.line_discount_type',
                            'transaction_sell_lines.line_discount_amount',
                            'units.id as unit_id',
                            'transaction_sell_lines.sub_unit_id',
                            DB::raw('vld.qty_available + transaction_sell_lines.quantity AS qty_available')
                        )
                        ->get();
        if (! empty($sell_details)) {
            foreach ($sell_details as $key => $value) {
                if ($transaction->status != 'final') {
                    $actual_qty_avlbl = $value->qty_available - $value->quantity_ordered;
                    $sell_details[$key]->qty_available = $actual_qty_avlbl;
                    $value->qty_available = $actual_qty_avlbl;
                }

                $sell_details[$key]->formatted_qty_available = $this->transactionUtil->num_f($value->qty_available, false, null, true);
                $lot_numbers = [];
                if (request()->session()->get('business.enable_lot_number') == 1) {
                    $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($value->variation_id, $business_id, $location_id);
                    foreach ($lot_number_obj as $lot_number) {
                        //If lot number is selected added ordered quantity to lot quantity available
                        if ($value->lot_no_line_id == $lot_number->purchase_line_id) {
                            $lot_number->qty_available += $value->quantity_ordered;
                        }

                        $lot_number->qty_formated = $this->transactionUtil->num_f($lot_number->qty_available);
                        $lot_numbers[] = $lot_number;
                    }
                }
                $sell_details[$key]->lot_numbers = $lot_numbers;

                if (! empty($value->sub_unit_id)) {
                    $value = $this->productUtil->changeSellLineUnit($business_id, $value);
                    $sell_details[$key] = $value;
                }

                $sell_details[$key]->formatted_qty_available = $this->transactionUtil->num_f($value->qty_available, false, null, true);
            }
        }

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id);
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $transaction->transaction_date = $this->transactionUtil->format_date($transaction->transaction_date, true);

        $transaction->repair_completed_on = ! empty($transaction->repair_completed_on) ? $this->transactionUtil->format_date($transaction->repair_completed_on, true) : null;

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $repair_statuses = RepairStatus::getRepairSatuses($business_id);

        $warranties = Warranty::forDropdown($business_id);

        $brands = Brands::forDropdown($business_id);

        $waiters = [];
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $checklist = Business::where('id', $business_id)->value('repair_checklist');
        $checklist = ! empty($checklist) ? json_decode($checklist, true) : [];

        $redeem_details = [];
        if (request()->session()->get('business.enable_rp') == 1) {
            $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $transaction->contact_id);

            $redeem_details['points'] += $transaction->rp_redeemed;
            $redeem_details['points'] -= $transaction->rp_earned;
        }

        return view('repair::repair.edit')
            ->with(compact(
                'business_details',
                'taxes',
                'sell_details',
                'transaction',
                'commission_agent',
                'types',
                'customer_groups',
                'price_groups',
                'pos_settings',
                'repair_statuses',
                'brands',
                'waiters',
                'checklist',
                'warranties',
                'redeem_details'
            ));
    }

    public function editRepairStatus($repair_id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair_status.update')))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $transaction = Transaction::where('business_id', $business_id)->findOrFail($repair_id);

            $repair_status_dropdown = RepairStatus::forDropdown($business_id, true);
            $status_template_tags = $this->repairUtil->getRepairStatusTemplateTags();

            return view('repair::repair.partials.edit_repair_status_modal')
                ->with(compact('transaction', 'repair_status_dropdown', 'status_template_tags'));
        }
    }

    public function updateRepairStatus(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair_status.update')))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['repair_id', 'repair_status_id_modal', 'update_note']);

                $transaction = Transaction::where('business_id', $business_id)->findOrFail($input['repair_id']);
                $transaction->repair_status_id = $input['repair_status_id_modal'];
                $transaction->save();

                $status = RepairStatus::where('business_id', $business_id)->findOrFail($input['repair_status_id_modal']);

                //Send repair updates
                if (! empty($request->input('send_sms'))) {
                    $sms_body = $request->input('sms_body');
                    $response = $this->repairUtil->sendRepairUpdateNotification($sms_body, $transaction);
                }

                //update if notification is sent or not
                if (! empty($response) && $response->getStatusCode() == 200) {
                    $transaction->repair_updates_notif = 1;
                } else {
                    $transaction->repair_updates_notif = 0;
                }
                $transaction->save();

                activity()
                ->performedOn($transaction)
                ->withProperties(['update_note' => $input['update_note'], 'updated_status' => $status->name])
                ->log('status_changed');

                $output = ['success' => true,
                    'msg' => __('lang_v1.updated_success'),
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

    public function getSurveyCategories()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.view')))) {
            abort(403, 'Unauthorized action.');
        }

        if (! request()->ajax()) {
            return response()->json(['success' => false, 'message' => __('messages.something_went_wrong')], 400);
        }

        $categories = DB::table('survey_categories')
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['success' => true, 'categories' => $categories]);
    }

    public function getSurveysByCategory($category_id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.view')))) {
            abort(403, 'Unauthorized action.');
        }

        if (! request()->ajax()) {
            return response()->json(['success' => false, 'message' => __('messages.something_went_wrong')], 400);
        }

        $surveys = DB::table('surveys')
            ->where('survey_category_id', $category_id)
            ->orderBy('title')
            ->get(['id', 'title']);

        return response()->json(['success' => true, 'surveys' => $surveys]);
    }

    public function sendSurvey(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.update')))) {
            abort(403, 'Unauthorized action.');
        }

        if (! $request->ajax()) {
            return response()->json(['success' => false, 'message' => __('messages.something_went_wrong')], 400);
        }

        $data = $request->validate([
            'transaction_id' => ['required', 'integer'],
            'survey_id' => ['required', 'integer'],
            'channel' => ['required', Rule::in(['sms', 'whatsapp'])],
        ]);

        $transaction = Transaction::where('business_id', $business_id)->findOrFail($data['transaction_id']);
        $contact = Contact::where('business_id', $business_id)->find($transaction->contact_id);

        if (! $contact) {
            return response()->json(['success' => false, 'message' => __('messages.something_went_wrong')], 404);
        }

        $action_id = DB::table('action')->insertGetId([
            'user_id' => $contact->id,
            'survey_id' => $data['survey_id'],
            'timesend' => now()->toTimeString(),
            'type_form' => 'Repair',
            'seen' => 0,
            'fill' => 0,
            'user_url' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slug = $contact->slug;
        if (empty($slug)) {
            $generatedSlug = Str::slug(Str::ascii($contact->first_name ?? ''));
            $slug = ! empty($generatedSlug) ? $generatedSlug : 'default-slug';
        }
        $parts = explode('-', $slug);
        $baseUrl = rtrim($request->getSchemeAndHttpHost() ?: config('app.url'), '/');
        $visitUrl = $baseUrl . '/survey/' . $parts[0] . '/' . $action_id;

        DB::table('action')->where('id', $action_id)->update(['user_url' => $visitUrl]);

        $message = 'اهلا ا/' . ($contact->first_name ?? '') . ' الرجاء ملئ هذا الاستطلاع لتقييم خدماتنا ' . $visitUrl;

        if ($data['channel'] === 'sms') {
            if (empty($contact->mobile)) {
                return response()->json(['success' => false, 'message' => __('lang_v1.mobile_number_not_found')], 422);
            }

            $smsResult = SmsUtil::sendEpusheg($contact->mobile, $message);
            $smsSent = is_array($smsResult) ? ($smsResult['success'] ?? false) : $smsResult;

            if (! $smsSent) {
                return response()->json(['success' => false, 'message' => __('messages.something_went_wrong')], 500);
            }

            return response()->json(['success' => true, 'message' => __('lang_v1.sms_sent')]);
        }

        $normalized = preg_replace('/\D+/', '', (string) $contact->mobile);
        if (Str::startsWith($normalized, '00')) {
            $normalized = substr($normalized, 2);
        }
        if (! Str::startsWith($normalized, '20') && Str::startsWith($normalized, '0')) {
            $normalized = '20' . substr($normalized, 1);
        }

        $whatsappUrl = ! empty($normalized)
            ? 'https://wa.me/' . rawurlencode($normalized) . '?text=' . rawurlencode($message)
            : 'https://wa.me/?text=' . rawurlencode($message);

        return response()->json([
            'success' => true,
            'message' => __('lang_v1.share_invoice'),
            'whatsapp_url' => $whatsappUrl,
        ]);
    }

    public function deleteMedia($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.update')))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            Media::deleteMedia($business_id, $id);

            $output = ['success' => true,
                'msg' => __('lang_v1.deleted_success'),
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
     * Prints barcode for the repair
     *
     * @return Response
     */
    public function printLabel($transaction_id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $transaction = Transaction::where('business_id', $business_id)
                                    ->with(['contact'])
                                    ->findorfail($transaction_id);

            $repair_settings = $this->repairUtil->getRepairSettings($business_id);

            //barcode types
            $default_barcode_type = $this->moduleUtil->barcode_default();

            $barcode_type = ! empty($repair_settings['barcode_type']) ? $repair_settings['barcode_type'] : $default_barcode_type;

            $barcode_details = Barcode::find($repair_settings['barcode_id']);

            $business_name = request()->session()->get('business.name');

            $product_details = [];
            $total_qty = 0;
            $product_details[] = ['details' => $transaction, 'qty' => 1];
            $total_qty = 1;

            $page_height = null;
            if ($barcode_details->is_continuous) {
                $rows = ceil($total_qty / $barcode_details->stickers_in_one_row) + 0.4;
                $barcode_details->paper_height = $barcode_details->top_margin + ($rows * $barcode_details->height) + ($rows * $barcode_details->row_distance);
            }

            return view('repair::repair.partials.preview_label')
                ->with(compact('product_details', 'business_name', 'barcode_details', 'page_height', 'barcode_type'));
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['html' => '',
                'success' => false,
                'msg' => __('lang_v1.barcode_label_error'),
            ];
        }

        return $output;
    }

    /**
     * Prints the customer copy
     *
     * @return Response
     */
    public function printCustomerCopy(Request $request, $transaction_id)
    {
        if (request()->ajax()) {
            try {
                $output = [
                    'success' => 0,
                    'msg' => trans('messages.something_went_wrong'),
                ];

                $business_id = $request->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                                ->where('id', $transaction_id)
                                ->with(['location'])
                                ->first();

                if (empty($transaction)) {
                    return $output;
                }

                $receipt = $this->_receiptContent($business_id, $transaction->location_id, $transaction_id);

                if (! empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = [
                    'success' => 0,
                    'msg' => trans('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    protected function _receiptContent($business_id, $location_id, $transaction_id)
    {
        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_details->invoice_layout_id);

        $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, 'browser');

        $currency_details = [
            'symbol' => $business_details->currency_symbol,
            'thousand_separator' => $business_details->thousand_separator,
            'decimal_separator' => $business_details->decimal_separator,
        ];

        $receipt_details->currency = $currency_details;

        $output['html_content'] = view('repair::repair.receipts.classic', compact('receipt_details'))->render();

        return $output;
    }

    /**
     * Show modal for adding CRM follow-up
     */
    public function getCrmFollowupModal()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('crm.access_all_schedule') || auth()->user()->can('crm.access_own_schedule'))) {
            abort(403, 'Unauthorized action.');
        }

        $contact_id = request()->get('contact_id');
        $transaction_id = request()->get('transaction_id');

        $contact = Contact::find($contact_id);
        $users = User::forDropdown($business_id, false);
        $followup_categories = Category::forDropdown($business_id, 'followup_category');

        return view('repair::repair.partials.crm_followup_modal')
            ->with(compact('contact', 'users', 'followup_categories', 'transaction_id'));
    }

    /**
     * Store CRM follow-up
     */
    public function storeCrmFollowup(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('crm.access_all_schedule') || auth()->user()->can('crm.access_own_schedule'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $input = $request->only(['contact_id', 'title', 'description', 'start_datetime', 'end_datetime', 'user_id', 'followup_category_id', 'notify_via', 'notify_type', 'allow_notification']);

            $input['notify_via'] = [
                'sms' => ! empty($input['notify_via']['sms']) ? 1 : 0,
                'mail' => ! empty($input['notify_via']['mail']) ? 1 : 0,
            ];

            $input['notify_type'] = ! empty($input['notify_type']) ? $input['notify_type'] : 'hour';
            $input['schedule_type'] = ! empty($input['schedule_type']) ? $input['schedule_type'] : 'email';
            $input['allow_notification'] = ! empty($input['allow_notification']) ? 1 : 0;
            $input['business_id'] = $business_id;
            $input['created_by'] = auth()->user()->id;
            $input['followup_category_id'] = $input['followup_category_id'] ?? null;

            $assigned_user = $input['user_id'];
            unset($input['user_id']);

            $schedule = \Modules\Crm\Entities\Schedule::create($input);
            $schedule->users()->sync($assigned_user);

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('repair::lang.crm_followup_added'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function recycleBin()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $sells = Transaction::onlyTrashed()
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('business_locations AS bl', 'transactions.location_id', '=', 'bl.id')
                ->select([
                    'transactions.id',
                    'transaction_date',
                    'invoice_no',
                    'contacts.name as customer',
                    'bl.name as location',
                    'transactions.deleted_at'
                ]);

            return Datatables::of($sells)
                ->addColumn('action', function ($row) {
                    $html = '<button data-href="' . route('repair.restore', [$row->id]) . '" class="btn btn-xs btn-success restore_repair"><i class="fas fa-undo"></i> ' . __("messages.restore") . '</button>';
                    $html .= ' <button data-href="' . route('repair.permanent_delete', [$row->id]) . '" class="btn btn-xs btn-danger delete_repair_permanent"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</button>';
                    return $html;
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('repair::repair.recycle_bin');
    }

    public function restore($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $transaction = Transaction::onlyTrashed()
                ->where('business_id', $business_id)
                ->findOrFail($id);
            $transaction->restore();

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
            $transaction = Transaction::onlyTrashed()
                ->where('business_id', $business_id)
                ->findOrFail($id);

            // Delete related records
            TransactionSellLine::where('transaction_id', $transaction->id)->delete();
            DB::table('transaction_payments')->where('transaction_id', $transaction->id)->delete();
            DB::table('account_transactions')->where('transaction_id', $transaction->id)->delete();
            
            $transaction->forceDelete();

            DB::commit();
            $output = ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            DB::rollBack();
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }
        return $output;
    }
}

