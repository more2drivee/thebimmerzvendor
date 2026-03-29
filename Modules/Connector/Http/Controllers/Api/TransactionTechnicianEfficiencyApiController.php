<?php

namespace Modules\Connector\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Transaction;

class TransactionTechnicianEfficiencyApiController extends Controller
{
    public function show($job_sheet_id)
    {
        $transaction = Transaction::with([
            'contact',
            'sell_lines' => function ($query) {
                $query->with(['product']);
            }
        ])->where('repair_job_sheet_id', $job_sheet_id)
          ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found for the provided job sheet'], 404);
        }

        $technician_metrics = [];
        $total_allocated_hours = 0.0;
        $total_worked_hours = 0.0;
        $total_labor_cost = 0.0;
        $total_labor_income = 0.0;
        $overall_efficiency = 0.0;

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

            foreach ($timer_data as $timer) {
                $worked_hours = (float) ($timer->total_hours_worked ?? 0);
                $allocated_hours = (float) ($timer->total_hours_allocated ?? 0);

                if ($worked_hours > 0 || $allocated_hours > 0) {
                    $total_allocated_hours += $allocated_hours;
                    $total_worked_hours += $worked_hours;

                    $hourly_rate = 0.0;
                    if (!empty($timer->essentials_salary) && !empty($timer->essentials_pay_period)) {
                        switch ($timer->essentials_pay_period) {
                            case 'month':
                                $hourly_rate = (float) $timer->essentials_salary / (22 * 8);
                                break;
                            case 'week':
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
                    $efficiency = $allocated_hours > 0 ? ($worked_hours / $allocated_hours) * 100 : 0;

                    $technician_metrics[] = [
                        'name' => trim(($timer->first_name ?? '') . ' ' . ($timer->last_name ?? '')),
                        'department' => 'Technician',
                        'allocated_hours' => round($allocated_hours, 2),
                        'worked_hours' => round($worked_hours, 2),
                        'hourly_rate' => round($hourly_rate, 2),
                        'total_cost' => round($technician_cost, 2),
                        'efficiency' => round($efficiency, 2)
                    ];
                }
            }
        }

        foreach ($transaction->sell_lines as $line) {
            if ($line->product && $line->product->enable_stock == 0) {
                $line_total = ($line->unit_price ?? 0) * ($line->quantity ?? 1);
                $line_discount = $line->line_discount_amount ?? 0;
                $total_labor_income += ($line_total - $line_discount);
            }
        }

        if ($total_worked_hours > 0 && $total_allocated_hours > 0) {
            $overall_efficiency = ($total_worked_hours / $total_allocated_hours) * 100;
        }

        usort($technician_metrics, function ($a, $b) {
            return $b['efficiency'] <=> $a['efficiency'];
        });

        $cost_distribution = [];
        foreach ($technician_metrics as $tech) {
            $cost_distribution[] = [
                'name' => $tech['name'],
                'cost' => $tech['total_cost'],
                'percentage' => $total_labor_cost > 0 ? round(($tech['total_cost'] / $total_labor_cost) * 100, 2) : 0
            ];
        }

        $chartLabels = [
            'allocated_hours' => __('repair::lang.allocated_hours'),
            'worked_hours' => __('repair::lang.worked_hours'),
            'efficiency' => __('repair::lang.efficiency'),
            'labor_cost' => __('repair::lang.total_labor_cost'),
            'labor_income' => __('repair::lang.labour_income_chart'),
            'cost_distribution' => __('repair::lang.cost_distribution'),
            'time_comparison' => __('repair::lang.time_comparison'),
            'technician_efficiency' => __('repair::lang.technician_efficiency'),
            'technician_details' => __('repair::lang.technician_details'),
            'hourly_rate' => __('repair::lang.hourly_rate'),
            'total_cost' => __('repair::lang.total_cost'),
            'department' => __('repair::lang.department')
        ];

        return response()->json([
            'transaction_id' => $transaction->id,
            'repair_job_sheet_id' => $transaction->repair_job_sheet_id,
            'technician_metrics' => $technician_metrics,
            'totals' => [
                'allocated_hours' => round($total_allocated_hours, 2),
                'worked_hours' => round($total_worked_hours, 2),
                'labor_cost' => round($total_labor_cost, 2),
                'labor_income' => round($total_labor_income, 2),
                'overall_efficiency' => round($overall_efficiency, 2)
            ],
            'cost_distribution' => $cost_distribution,
            'labels' => $chartLabels
        ]);
    }
}