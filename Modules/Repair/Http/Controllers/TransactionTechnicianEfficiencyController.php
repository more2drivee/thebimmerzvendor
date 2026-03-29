<?php

namespace Modules\Repair\Http\Controllers;

use App\Transaction;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Transaction Technician Efficiency Controller
 * 
 * Displays technician efficiency metrics for a specific transaction,
 * including allocated hours, worked hours, efficiency percentage, and cost breakdown.
 */
class TransactionTechnicianEfficiencyController extends Controller
{
    protected $contactUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $commonUtil;

    public function __construct(
        ContactUtil $contactUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ModuleUtil $moduleUtil,
        ProductUtil $productUtil,
        Util $commonUtil
    ) {
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display technician efficiency metrics for a transaction
     *
     * @param int $transaction_id
     * @return \Illuminate\Http\Response
     */
    public function show($transaction_id)
    {
        // Load transaction with relationships
        $transaction = Transaction::with([
            'contact',
            'sell_lines' => function($query) {
                $query->with(['product']);
            }
        ])->find($transaction_id);

        if (!$transaction) {
            abort(404);
        }

        // Initialize metrics
        $technician_metrics = [];
        $total_allocated_hours = 0;
        $total_worked_hours = 0;
        $total_labor_cost = 0;
        $total_labor_income = 0;
        $overall_efficiency = 0;

        // Get timer tracking data for this transaction's job sheet
        if ($transaction->repair_job_sheet_id) {
            $timer_data = DB::table('timer_tracking as tt')
                ->join('users as u', 'tt.user_id', '=', 'u.id')
                ->where('tt.job_sheet_id', $transaction->repair_job_sheet_id)
                ->select(
                    'tt.user_id',
                    'u.first_name',
                    'u.last_name',
                    'u.essentials_salary',
                    'u.essentials_pay_period',
                    DB::raw('SUM(COALESCE(tt.time_allocate, 0)) as total_hours_allocated'),
                    DB::raw('SUM(GREATEST(0, (TIMESTAMPDIFF(SECOND, tt.started_at, COALESCE(tt.completed_at, NOW())) - COALESCE(tt.total_paused_duration, 0)) / 3600)) as total_hours_worked')
                )
                ->groupBy('tt.user_id', 'u.first_name', 'u.last_name', 'u.essentials_salary', 'u.essentials_pay_period')
                ->get();

            // Aggregate stop-reason data per technician (pause duration + breakdown per body)
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

            foreach ($timer_data as $timer) {
                $worked_hours = (float) ($timer->total_hours_worked ?? 0);
                $allocated_hours = (float) ($timer->total_hours_allocated ?? 0);
                
                // Only include if there's actual data
                if ($worked_hours > 0 || $allocated_hours > 0) {
                    $total_allocated_hours += $allocated_hours;
                    $total_worked_hours += $worked_hours;

                    // Calculate hourly rate with sensible defaults
                    // Prefer salary-based rate; assume standard working hours if not explicitly stored
                    $hourly_rate = 0.0;
                    if (!empty($timer->essentials_salary) && !empty($timer->essentials_pay_period)) {
                        switch ($timer->essentials_pay_period) {
                            case 'month':
                                // Approximate to 22 working days * 8 hours
                                $hourly_rate = (float) $timer->essentials_salary / (22 * 8);
                                break;
                            case 'week':
                                // Approximate to 5 working days * 8 hours
                                $hourly_rate = (float) $timer->essentials_salary / (5 * 8);
                                break;
                            case 'day':
                                $hourly_rate = (float) $timer->essentials_salary / 8;
                                break;
                            default:
                                $hourly_rate = 0.0;
                        }
                    }

                    $technician_cost = $worked_hours * $hourly_rate;
                    $total_labor_cost += $technician_cost;

                    // Calculate efficiency: worked vs allocated
                    // Efficiency = (worked / allocated) * 100. Guard against divide-by-zero.
                    $efficiency = $allocated_hours > 0 ? ($worked_hours / $allocated_hours) * 100 : 0;

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

                    $technician_metrics[] = [
                        'user_id' => $timer->user_id,
                        'name' => trim($timer->first_name . ' ' . $timer->last_name),
                        'department' => 'Technician',
                        'allocated_hours' => $allocated_hours,
                        'worked_hours' => $worked_hours,
                        'hourly_rate' => $hourly_rate,
                        'total_cost' => $technician_cost,
                        'efficiency' => $efficiency,
                        'paused_hours' => $pauseSeconds > 0 ? ($pauseSeconds / 3600) : 0,
                        'reasons' => $reasonsList,
                    ];
                }
            }
        }

        // Calculate labor income from sell lines
        foreach ($transaction->sell_lines as $line) {
            if ($line->product && $line->product->enable_stock == 0) {
                // Labor/Service line
                $line_total = ($line->unit_price ?? 0) * ($line->quantity ?? 1);
                $line_discount = $line->line_discount_amount ?? 0;
                $total_labor_income += ($line_total - $line_discount);
            }
        }

        // Calculate overall efficiency
        if ($total_worked_hours > 0 && $total_allocated_hours > 0) {
            $overall_efficiency = ($total_worked_hours / $total_allocated_hours) * 100;
        }

        // Sort technicians by efficiency descending for clearer presentation
        usort($technician_metrics, function ($a, $b) {
            return $b['efficiency'] <=> $a['efficiency'];
        });

        // Calculate cost distribution by technician
        $cost_distribution = [];
        foreach ($technician_metrics as $tech) {
            $cost_distribution[] = [
                'name' => $tech['name'],
                'cost' => $tech['total_cost'],
                'percentage' => $total_labor_cost > 0 ? ($tech['total_cost'] / $total_labor_cost) * 100 : 0
            ];
        }

        // Localization labels
        $chartLabels = [
            'allocated_hours' => __('repair::lang.allocated_hours'),
            'worked_hours' => __('repair::lang.worked_hours'),
            'efficiency' => __('repair::lang.efficiency'),
            'labor_cost' => __('repair::lang.total_labor_cost'),
            // Use available key for labour income label
            'labor_income' => __('repair::lang.labour_income_chart'),
            'cost_distribution' => __('repair::lang.cost_distribution'),
            'time_comparison' => __('repair::lang.time_comparison'),
            'technician_efficiency' => __('repair::lang.technician_efficiency'),
            'technician_details' => __('repair::lang.technician_details'),
            'hourly_rate' => __('repair::lang.hourly_rate'),
            'total_cost' => __('repair::lang.total_cost'),
            'department' => __('repair::lang.department')
        ];

      
        return view('repair::repair.transaction_technician_efficiency', compact(
            'transaction',
            'technician_metrics',
            'total_allocated_hours',
            'total_worked_hours',
            'total_labor_cost',
            'total_labor_income',
            'overall_efficiency',
            'cost_distribution',
            'chartLabels'
        ));
    }

    public function updateTimer(Request $request, $transaction_id)
    {
        $transaction = Transaction::find($transaction_id);

        if (!$transaction || !$transaction->repair_job_sheet_id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong'),
            ], 404);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'field' => 'required|in:allocated,worked',
            'value' => 'required|numeric|min:0',
        ]);

        $jobSheetId = (int) $transaction->repair_job_sheet_id;
        $userId = (int) $validated['user_id'];
        $valueHours = (float) $validated['value'];

        DB::beginTransaction();
        try {
            if ($validated['field'] === 'allocated') {
                $error = $this->updateAllocatedTime($jobSheetId, $userId, $valueHours);
                if (!empty($error)) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => $error,
                    ], 422);
                }
            } else {
                $this->updateWorkedTime($jobSheetId, $userId, $valueHours);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('lang_v1.updated_success'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update technician timer', [
                'transaction_id' => $transaction_id,
                'job_sheet_id' => $jobSheetId,
                'user_id' => $userId,
                'field' => $validated['field'],
                'value' => $valueHours,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    protected function updateAllocatedTime(int $jobSheetId, int $userId, float $hours)
    {
        // 1) Determine maximum standard hours for this job sheet from related services (products.serviceHours)
        $maxServiceHours = (float) DB::table('product_joborder as pj')
            ->join('products as p', 'pj.product_id', '=', 'p.id')
            ->where('pj.job_order_id', $jobSheetId)
            ->where('p.enable_stock', 0) // services only
            ->sum(DB::raw('COALESCE(p.serviceHours, 0)'));

        if ($maxServiceHours > 0) {
            // Current total allocated across all technicians on this job
            $currentTotalAllocated = (float) DB::table('timer_tracking')
                ->where('job_sheet_id', $jobSheetId)
                ->sum('time_allocate');

            // Current allocated for this specific technician
            $currentUserAllocated = (float) DB::table('timer_tracking')
                ->where('job_sheet_id', $jobSheetId)
                ->where('user_id', $userId)
                ->sum('time_allocate');

            // New total if we change this technician to $hours
            $newTotalAllocated = $currentTotalAllocated - $currentUserAllocated + $hours;

            // Small epsilon to avoid float noise
            if ($newTotalAllocated - $maxServiceHours > 0.0001) {
                return __('repair::lang.allocated_time_exceeds_service_hours')
                    . ' (max ' . number_format($maxServiceHours, 2) . 'h)';
            }
        }

        $timers = DB::table('timer_tracking')
            ->where('job_sheet_id', $jobSheetId)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();

        $now = Carbon::now();
        $businessId = auth()->user()->business_id ?? null;

        if ($timers->isEmpty()) {
            if ($businessId === null) {
                return;
            }

            DB::table('timer_tracking')->insert([
                'business_id' => $businessId,
                'job_sheet_id' => $jobSheetId,
                'user_id' => $userId,
                'status' => 'completed',
                'started_at' => $now,
                'paused_at' => null,
                'resumed_at' => null,
                'completed_at' => $now,
                'total_paused_duration' => 0,
                'time_allocate' => $hours,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        $ids = $timers->pluck('id')->all();

        DB::table('timer_tracking')
            ->whereIn('id', $ids)
            ->update([
                'time_allocate' => 0,
                'updated_at' => $now,
            ]);

        $mainId = $timers->first()->id;

        DB::table('timer_tracking')
            ->where('id', $mainId)
            ->update([
                'time_allocate' => $hours,
                'updated_at' => $now,
            ]);
    }

    protected function updateWorkedTime(int $jobSheetId, int $userId, float $hours): void
    {
        $timers = DB::table('timer_tracking')
            ->where('job_sheet_id', $jobSheetId)
            ->where('user_id', $userId)
            ->orderBy('started_at')
            ->get();

        $now = Carbon::now();
        $targetSeconds = max(0, (int) round($hours * 3600));

        if ($timers->isEmpty()) {
            $businessId = auth()->user()->business_id ?? null;
            if ($businessId === null) {
                return;
            }

            $start = $now->copy()->subSeconds($targetSeconds);

            DB::table('timer_tracking')->insert([
                'business_id' => $businessId,
                'job_sheet_id' => $jobSheetId,
                'user_id' => $userId,
                'status' => 'completed',
                'started_at' => $start,
                'paused_at' => null,
                'resumed_at' => null,
                'completed_at' => $now,
                'total_paused_duration' => 0,
                'time_allocate' => null,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        $totalSeconds = 0;
        foreach ($timers as $timer) {
            if (empty($timer->started_at)) {
                continue;
            }

            $start = Carbon::parse($timer->started_at);
            $pausedDuration = (int) ($timer->total_paused_duration ?? 0);

            if ($timer->status === 'completed' && !empty($timer->completed_at)) {
                $end = Carbon::parse($timer->completed_at);
            } elseif ($timer->status === 'paused' && !empty($timer->paused_at)) {
                $end = Carbon::parse($timer->paused_at);
            } else {
                $end = $now;
            }

            $elapsed = $end->diffInSeconds($start) - $pausedDuration;
            $totalSeconds += max(0, (int) $elapsed);
        }

        if ($totalSeconds === $targetSeconds) {
            return;
        }

        $lastTimer = $timers->sortByDesc(function ($timer) {
            if (!empty($timer->completed_at)) {
                return $timer->completed_at;
            }

            if (!empty($timer->paused_at)) {
                return $timer->paused_at;
            }

            return $timer->started_at;
        })->first();

        if (!$lastTimer || empty($lastTimer->started_at)) {
            return;
        }

        $start = Carbon::parse($lastTimer->started_at);
        $pausedDuration = (int) ($lastTimer->total_paused_duration ?? 0);

        if ($lastTimer->status === 'completed' && !empty($lastTimer->completed_at)) {
            $end = Carbon::parse($lastTimer->completed_at);
        } elseif ($lastTimer->status === 'paused' && !empty($lastTimer->paused_at)) {
            $end = Carbon::parse($lastTimer->paused_at);
        } else {
            $end = $now;
        }

        $originalElapsed = max(0, $end->diffInSeconds($start) - $pausedDuration);

        $delta = $targetSeconds - $totalSeconds;
        $newElapsed = $originalElapsed + $delta;

        if ($newElapsed < 0) {
            $newElapsed = 0;
        }

        $newEnd = $start->copy()->addSeconds($newElapsed + $pausedDuration);

        DB::table('timer_tracking')
            ->where('id', $lastTimer->id)
            ->update([
                'status' => 'completed',
                'completed_at' => $newEnd,
                'paused_at' => null,
                'resumed_at' => null,
                'updated_at' => $now,
            ]);
    }
}
