<?php

namespace Modules\Repair\Http\Controllers;

use App\Barcode;
use App\Brands;
use App\Business;
use App\BusinessLocation;
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
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Warranty;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Repair\Entities\DeviceModel;
use Modules\Repair\Entities\RepairStatus;
use Modules\Repair\Utils\RepairUtil;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class transactionOverviewController extends Controller
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
     * Render transaction overview page for a repair transaction.
     *
     * @param int $transaction_id
     * @return \Illuminate\Http\Response
     */
    public function index($transaction_id)
    {
        // Load transaction with comprehensive relationships for profit calculations
        $transaction = \App\Transaction::with([
            'contact',
            'payment_lines',
            'return_parent',
            'sell_lines' => function($query) {
                $query->with([
                    'product' => function($q) {
                        $q->select('id', 'name', 'enable_stock', 'serviceHours');
                    },
                    'sell_line_purchase_lines' => function($q) {
                        $q->with(['purchase_line' => function($pl) {
                            $pl->select('id', 'purchase_price', 'purchase_price_inc_tax');
                        }]);
                    }
                ]);
            }
        ])->find($transaction_id);

        if (! $transaction) {
            abort(404);
        }

        // Preload job order lines (product_joborder) for this repair, if any
        $joborderLines = collect();
        if (!empty($transaction->repair_job_sheet_id)) {
            $joborderLines = DB::table('product_joborder')
                ->where('job_order_id', $transaction->repair_job_sheet_id)
                ->select('id', 'product_id', 'quantity', 'price', 'purchase_price')
                ->get()
                ->keyBy('product_id');
        }

        // Calculate comprehensive transaction metrics
        $invoice_total = $transaction->final_total ?? 0;
        $raw_discount = $transaction->discount_amount ?? 0;
        $transaction_discount_amount = 0;
        if (! empty($raw_discount)) {
            if (($transaction->discount_type ?? 'fixed') === 'percentage') {
                $total_before_tax = $transaction->total_before_tax ?? 0;
                $transaction_discount_amount = ($raw_discount / 100) * $total_before_tax;
            } else {
                $transaction_discount_amount = $raw_discount;
            }
        }
        // Normalize all line discounts to fixed values for accurate aggregation
        $line_discount_amount_fixed = 0;
        foreach ($transaction->sell_lines as $line) {
            $quantity = $line->quantity ?? 1;
            $joborderLine = $joborderLines->get($line->product_id ?? null);
            $unit_selling_price = $joborderLine && $joborderLine->price !== null
                ? (float) $joborderLine->price
                : (float) ($line->unit_price ?? 0);
            $line_total_before_discount = $unit_selling_price * $quantity;

            $fixed_line_discount = 0;
            if (!empty($line->line_discount_amount)) {
                $discount_type = $line->line_discount_type ?? 'fixed';
                $fixed_line_discount = $discount_type === 'percentage'
                    ? ($line_total_before_discount * ($line->line_discount_amount / 100))
                    : (float) $line->line_discount_amount * $quantity;
            }

            $line->fixed_line_discount = $fixed_line_discount;
            $line_discount_amount_fixed += $fixed_line_discount;
        }

        $discount_amount = $transaction_discount_amount + $line_discount_amount_fixed;

        // Gross total before discounts (used for component share percentages)
        $invoice_total_before_discount = ($invoice_total ?? 0) + $discount_amount;

        // Calculate spare parts and labor totals
        $spare_parts_total = 0;
        $spare_parts_total_before_discount = 0;
        $labor_total = 0;
        $labor_total_before_discount = 0;
        $total_purchase_cost = 0;
        $total_profit = 0;
        $labor_hours = 0;
        $technician_assignments = [];

        foreach($transaction->sell_lines as $line) {
            $quantity = $line->quantity ?? 1;

            // Prefer job order selling price (joborder_selling_price) when available
            $joborderLine = $joborderLines->get($line->product_id ?? null);
            $unit_selling_price = $joborderLine && $joborderLine->price !== null
                ? (float) $joborderLine->price
                : (float) ($line->unit_price ?? 0);

            $line_total = $unit_selling_price * $quantity;
            $line_discount = $line->fixed_line_discount ?? 0;
            $line_net = $line_total - $line_discount;

            // Get purchase price, preferring joborder_purchase_price when available
            $purchase_price = 0;
            if ($joborderLine && $joborderLine->purchase_price !== null) {
                $purchase_price = (float) $joborderLine->purchase_price * $quantity;
            } else {
                if($line->sell_line_purchase_lines && $line->sell_line_purchase_lines->count() > 0) {
                    foreach($line->sell_line_purchase_lines as $purchase_mapping) {
                        if($purchase_mapping->purchase_line) {
                            $purchase_price += ($purchase_mapping->purchase_line->purchase_price ?? 0) * $purchase_mapping->quantity;
                        }
                    }
                }
            }

            // Categorize by product type
            if($line->product && $line->product->enable_stock == 0) {
                // Labor/Service
                $labor_total += $line_net;
                $labor_total_before_discount += $line_total;
                if($line->product->serviceHours) {
                    $labor_hours += ($line->product->serviceHours * $quantity);
                }
            } else {
                // Spare Parts
                $spare_parts_total += $line_net;
                $spare_parts_total_before_discount += $line_total;
                $total_purchase_cost += $purchase_price;
            }

            $total_profit += ($line_net - $purchase_price);
        }

        // Calculate labor cost from timer tracking and user salary
        $labor_cost = 0;
        $actual_labor_hours = 0;
        $technician_details = [];
        
        if($transaction->repair_job_sheet_id) {
            // Get timer tracking data for this job sheet
            $timer_data = DB::table('timer_tracking as tt')
                ->join('users as u', 'tt.user_id', '=', 'u.id')
                ->where('tt.job_sheet_id', $transaction->repair_job_sheet_id)
                ->select(
                    'tt.user_id',
                    'u.first_name',
                    'u.last_name',
                    'u.essentials_salary',
                    'u.essentials_pay_period',
                    // Allocated hours (as entered)
                    DB::raw('SUM(COALESCE(tt.time_allocate, 0)) as total_hours_allocated'),
                    // Worked hours from elapsed time minus pauses
                    DB::raw('SUM(GREATEST((TIMESTAMPDIFF(SECOND, tt.started_at, COALESCE(tt.completed_at, NOW())) - COALESCE(tt.total_paused_duration, 0)) / 3600, 0)) as total_hours_worked')
                )
                ->groupBy('tt.user_id', 'u.first_name', 'u.last_name', 'u.essentials_salary', 'u.essentials_pay_period')
                ->get();

            // Load stop-reason summary per technician (total paused hours + per-reason breakdown)
            $stopReasonSummary = DB::table('timer_stop_reasons as tsr')
                ->join('timer_tracking as tt', 'tsr.timer_id', '=', 'tt.id')
                ->where('tt.job_sheet_id', $transaction->repair_job_sheet_id)
                ->select(
                    'tt.user_id',
                    DB::raw('SUM(CASE WHEN tsr.pause_start IS NOT NULL AND tsr.pause_end IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(SECOND, tsr.pause_start, tsr.pause_end),0) ELSE 0 END) as total_pause_seconds')
                )
                ->groupBy('tt.user_id')
                ->get()
                ->keyBy('user_id');

            // Group reasons per user & body with their own durations
            $reasonsPerUser = DB::table('timer_stop_reasons as tsr')
                ->join('timer_tracking as tt', 'tsr.timer_id', '=', 'tt.id')
                ->where('tt.job_sheet_id', $transaction->repair_job_sheet_id)
                ->whereNotNull('tsr.pause_start')
                ->whereNotNull('tsr.pause_end')
                ->select(
                    'tt.user_id',
                    'tsr.body',
                    DB::raw('SUM(GREATEST(TIMESTAMPDIFF(SECOND, tsr.pause_start, tsr.pause_end),0)) as reason_pause_seconds')
                )
                ->groupBy('tt.user_id', 'tsr.body')
                ->get()
                ->groupBy('user_id');

            foreach($timer_data as $timer) {
                $worked_hours = (float) ($timer->total_hours_worked ?? 0);
                $allocated_hours = (float) ($timer->total_hours_allocated ?? 0);
                $actual_labor_hours += $worked_hours;
                
                // Calculate hourly rate based on salary and pay period
                $hourly_rate = 0;
                if($timer->essentials_salary && $timer->essentials_pay_period) {
                    switch($timer->essentials_pay_period) {
                        case 'month':
                            $hourly_rate = $timer->essentials_salary / (30 * 8); // 30 days * 8 hours
                            break;
                        case 'week':
                            $hourly_rate = $timer->essentials_salary / (7 * 8); // 7 days * 8 hours
                            break;
                        case 'day':
                            $hourly_rate = $timer->essentials_salary / 8; // 8 hours per day
                            break;
                        default:
                            $hourly_rate = 100; // Default fallback rate
                    }
                }
                
                $technician_cost = $worked_hours * $hourly_rate;
                $labor_cost += $technician_cost;

                $pauseSeconds = 0;
                $reasonsList = [];
                if (isset($stopReasonSummary[$timer->user_id])) {
                    $summary = $stopReasonSummary[$timer->user_id];
                    $pauseSeconds = (int) ($summary->total_pause_seconds ?? 0);
                }

                if (isset($reasonsPerUser[$timer->user_id])) {
                    foreach ($reasonsPerUser[$timer->user_id] as $row) {
                        if (empty($row->body)) {
                            continue;
                        }
                        $hours = max(0, ($row->reason_pause_seconds ?? 0) / 3600);
                        $reasonsList[] = [
                            'body' => trim($row->body),
                            'hours' => $hours,
                        ];
                    }
                }

                $technician_details[] = [
                    'name' => trim($timer->first_name . ' ' . $timer->last_name),
                    'hours' => $worked_hours, // worked hours
                    'allocated_hours' => $allocated_hours,
                    'hourly_rate' => $hourly_rate,
                    'total_cost' => $technician_cost,
                    'paused_hours' => $pauseSeconds > 0 ? ($pauseSeconds / 3600) : 0,
                    'reasons' => $reasonsList,
                ];
            }
        }

        // Use actual labor hours if available, otherwise fall back to service hours
        if($actual_labor_hours > 0) {
            $labor_hours = $actual_labor_hours;
        }
        
        // Contact and device info for header
        $contact_name = $transaction->contact->name ?? '-';
        $device_info = null;
        if (!empty($transaction->repair_job_sheet_id)) {
            $device_info = DB::table('repair_job_sheets as rjs')
                ->leftJoin('bookings', 'rjs.booking_id', '=', 'bookings.id')
                ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')
                ->leftJoin('categories as device', 'contact_device.device_id', '=', 'device.id')
                ->leftJoin('repair_device_models as rdm', 'rdm.id', '=', 'contact_device.models_id')
                ->leftJoin('contacts as c', 'bookings.contact_id', '=', 'c.id')
                ->where('rjs.id', $transaction->repair_job_sheet_id)
                ->select(
                    'c.name as contact_name',
                    'device.name as device_name',
                    'rdm.name as device_model',
                    'contact_device.plate_number',
                    'contact_device.chassis_number as vin_number'
                )
                ->first();
        }

        // Payment summary
        $total_paid = (float) DB::table('transaction_payments')
            ->where('transaction_id', $transaction_id)
            ->sum('amount');
        $remaining_amount = max((float) ($transaction->final_total ?? 0) - $total_paid, 0);
        $payment_status = $transaction->payment_status ?? '';

        // Get expenses linked to this transaction
        $expenses = DB::table('transactions as exp')
            ->leftJoin('expense_categories as ec', 'exp.expense_category_id', '=', 'ec.id')
            ->leftJoin('contacts as c', 'exp.contact_id', '=', 'c.id')
            ->leftJoin('transaction_payments as tp', 'tp.transaction_id', '=', 'exp.id')
            ->where('exp.invoice_ref', $transaction_id)
            ->where('exp.type', 'expense')
            ->select(
                'exp.id',
                'exp.ref_no',
                'exp.transaction_date',
                'exp.final_total',
                'exp.additional_notes',
                'exp.payment_status',
                'ec.name as category_name',
                DB::raw("CASE WHEN c.supplier_business_name IS NOT NULL AND c.supplier_business_name <> '' THEN CONCAT(c.supplier_business_name, ' - ', c.name) ELSE c.name END as expense_contact_name"),
                DB::raw("COALESCE(SUM(CASE WHEN tp.is_return = 1 THEN -1 * tp.amount ELSE tp.amount END), 0) as total_paid")
            )
            ->groupBy(
                'exp.id',
                'exp.ref_no',
                'exp.transaction_date',
                'exp.final_total',
                'exp.additional_notes',
                'exp.payment_status',
                'ec.name',
                'c.supplier_business_name',
                'c.name'
            )
            ->get();

        $expenses_total = $expenses->sum('final_total');

        // Get related purchases linked to this repair (by invoice_no reference or by job sheet id)
        // Include both purchases and purchase returns, expose payment status & return flag
        $purchases = DB::table('transactions as p')
            ->leftJoin('contacts as s', 'p.contact_id', '=', 's.id')
            ->leftJoin('purchase_lines as pl', 'pl.transaction_id', '=', 'p.id')
            ->whereIn('p.type', ['purchase', 'purchase_return'])
            ->where(function($q) use ($transaction) {
                $q->when($transaction->invoice_no, function($qq) use ($transaction) {
                    $qq->where('p.invoice_no', $transaction->invoice_no);
                });
                if (!empty($transaction->repair_job_sheet_id)) {
                    $q->orWhere('p.repair_job_sheet_id', $transaction->repair_job_sheet_id);
                }
            })
            ->select(
                'p.id',
                'p.ref_no',
                'p.transaction_date',
                'p.final_total',
                'p.status',
                'p.payment_status',
                'p.type',
                // Count of purchase_return children for this purchase (for correct column display)
                DB::raw('(SELECT COUNT(*) FROM transactions pr WHERE pr.return_parent_id = p.id AND pr.type = "purchase_return") as purchase_return_count'),
                DB::raw("CASE WHEN s.supplier_business_name IS NOT NULL AND s.supplier_business_name <> '' THEN CONCAT(s.supplier_business_name, ' - ', s.name) ELSE s.name END as supplier_name"),
                DB::raw('COALESCE(SUM(pl.quantity), 0) as total_qty')
            )
            ->groupBy('p.id', 'p.ref_no', 'p.transaction_date', 'p.final_total', 'p.status', 'p.payment_status', 'p.type', 's.supplier_business_name', 's.name')
            ->orderByDesc('p.id')
            ->get();

        $purchases_total = $purchases->sum('final_total');

        // Sell return data
        $sell_return_amount = 0;
        $sell_return = null;
        if (!empty($transaction->return_parent)) {
            $sell_return = $transaction->return_parent;
            $sell_return_amount = (float) ($sell_return->final_total ?? 0);
        }

        // Calculate additional metrics
        $purchasing_cost = $total_purchase_cost;
        $labor_income = $labor_total;
        $total_expenses = $purchasing_cost + $labor_cost + $expenses_total;
        $net_profit = $invoice_total - $sell_return_amount - $total_expenses;

        // Calculate percentages
        $parts_profit_margin = $spare_parts_total > 0 ? (($spare_parts_total - $purchasing_cost) / $spare_parts_total) * 100 : 0;

        // Labor efficiency: (allocated hours / actual worked hours) * 100
        // If actual < allocated: efficiency > 100% (finished faster than expected)
        // If actual > allocated: efficiency < 100% (took longer than expected)
        $allocated_hours_total = 0;
        foreach($technician_details as $detail) {
            $allocated_hours_total += $detail['allocated_hours'];
        }
        $labor_efficiency = $actual_labor_hours > 0 && $allocated_hours_total > 0 ?
            ($allocated_hours_total / $actual_labor_hours) * 100 : 0;

        $overall_profit_margin = $invoice_total > 0 ? ($net_profit / $invoice_total) * 100 : 0;

        // Chart labels for localization
        $chartLabels = [
            'purchase_cost' => __('repair::lang.purchase_cost'),
            'selling_price' => __('repair::lang.selling_price'),
            'labour_income' => __('repair::lang.labour_income_chart'),
            'expenses' => __('repair::lang.expenses'),
            'discount' => __('repair::lang.discount'),
            'parts_profit' => __('repair::lang.parts_profit'),
            'labour_profit' => __('repair::lang.labour_profit'),
            'labor_costs' => __('repair::lang.labor_costs'),
            'other_expenses' => __('repair::lang.other_expenses'),
            'discounts' => __('repair::lang.discounts'),
            'spare_parts_sales' => __('repair::lang.spare_parts_sales'),
            'other_income' => __('repair::lang.other_income'),
            'purchasing_cost' => __('repair::lang.purchasing_cost'),
            'labour_cost' => __('repair::lang.labour_cost'),
            'income_breakdown' => __('repair::lang.income_breakdown'),
            'expense_breakdown' => __('repair::lang.expense_breakdown')
        ];

        // Get technician from job sheet if available
        $technician = '-';
        if($transaction->repair_job_sheet_id) {
            $job_sheet = \Modules\Repair\Entities\JobSheet::find($transaction->repair_job_sheet_id);
            if($job_sheet && $job_sheet->service_staff) {
                $staff_ids = json_decode($job_sheet->service_staff, true);
                if(is_array($staff_ids) && count($staff_ids) > 0) {
                    $staff = \App\User::whereIn('id', $staff_ids)->pluck('first_name')->toArray();
                    $technician = implode(', ', $staff);
                }
            }
        }

        // Pass all calculated data to the comprehensive overview blade
        // Effective total after sell return
        $effective_invoice_total = $invoice_total - $sell_return_amount;
        $remaining_amount = max($effective_invoice_total - $total_paid, 0);

        return view('repair::repair.transaction_overview', compact(
            'transaction',
            'invoice_total',
            'discount_amount',
            'spare_parts_total',
            'spare_parts_total_before_discount',
            'labor_total',
            'total_purchase_cost',
            'total_profit',
            'labor_hours',
            'purchasing_cost',
            'labor_income',
            'labor_cost',
            'total_expenses',
            'net_profit',
            'parts_profit_margin',
            'labor_efficiency',
            'overall_profit_margin',
            'invoice_total_before_discount',
            'chartLabels',
            'technician',
            'technician_details',
            'expenses',
            'expenses_total',
            'purchases',
            'purchases_total',
            'contact_name',
            'joborderLines',
            'device_info',
            'payment_status',
            'total_paid',
            'remaining_amount',
            'sell_return_amount',
            'sell_return'
        ));
    }

   
}

