<?php

namespace Modules\TimeManagement\Http\Controllers;

use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Modules\TimeManagement\Services\TimeMetricsService;
use Modules\TimeManagement\Services\TechnicianDataService;

class PerformanceController extends Controller
{
    protected TechnicianDataService $technicianDataService;
    
    public function __construct(TechnicianDataService $technicianDataService)
    {
        $this->technicianDataService = $technicianDataService;
    }
    
    public function index(Request $request, TimeMetricsService $metrics)
    {
        $business_id = $request->session()->get('user.business_id');

        $workshops = DB::table('workshops')
            ->where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');
        $locations = BusinessLocation::forDropdown($business_id, false, false, false, true);

        if ($request->ajax()) {
            $filters = $request->only(['workshop_id', 'location_id', 'start_date', 'end_date']);

            // Get all technician metrics in one optimized call (this fetches all data once)
            $technicianMetrics = $metrics->getAllTechnicianMetrics($business_id, $filters);
            
            // Get basic summary for DataTable display (legacy format for compatibility)
            $summary = $metrics->getPerformanceSummary($business_id, $filters);

            // Build scheduled hours per technician from business work schedule within date range
            $scheduleData = $this->technicianDataService->getBusinessScheduleForTechnicians($business_id, $filters);
            $userScheduledHours = $scheduleData
                ->groupBy('user_id')
                ->map(function ($items) {
                    $hours = 0.0;
                    foreach ($items as $i) {
                        $hours += (float) ($i->total_hours ?? 0);
                    }
                    return $hours;
                })
                ->toArray();

            return DataTables::of($summary)
                ->addColumn('user_name', function ($row) {
                    return $row->user_name ?? 'N/A';
                })
                ->addColumn('productive_hours', function ($row) {
                    return number_format($row->productive_hours ?? 0, 1) . 'h';
                })
                ->addColumn('allocated_hours', function ($row) use ($technicianMetrics) {
                    $val = $technicianMetrics['allocated_hours'][$row->user_id] ?? 0;
                    return number_format($val, 2) . 'h';
                })
                // Use business schedule-based calculation for scheduled hours within filtered period
                ->addColumn('scheduled_hours', function ($row) use ($userScheduledHours) {
                    $val = $userScheduledHours[$row->user_id] ?? 0;
                    return number_format($val, 1) . 'h';
                })
                ->addColumn('efficiency', function ($row) use ($technicianMetrics) {
                    $eff = (int) ($technicianMetrics['efficiency'][$row->user_id] ?? 0);
                    $bar = $eff >= 95 ? 'progress-bar-success' : ($eff >= 85 ? 'progress-bar-info' : 'progress-bar-warning');
                    return '
                        <div class="clearfix">
                            <span class="pull-left"><strong>' . $eff . '%</strong></span>
                        </div>
                        <div class="progress" style="margin-bottom:0;height:8px;">
                            <div class="progress-bar ' . $bar . '" role="progressbar" aria-valuenow="' . $eff . '" aria-valuemin="0" aria-valuemax="150" style="width: ' . min($eff, 100) . '%"></div>
                        </div>
                    ';
                })
                ->addColumn('productivity_rate', function ($row) use ($technicianMetrics) {
                    $val = $technicianMetrics['productivity'][$row->user_id] ?? 0;
                    return number_format($val, 1) . '%';
                })
                ->addColumn('utilization_rate', function ($row) use ($technicianMetrics) {
                    $val = $technicianMetrics['utilization'][$row->user_id] ?? 0;
                    return number_format($val, 1) . '%';
                })
                ->addColumn('first_time_fix_rate', function ($row) use ($technicianMetrics) {
                    $val = $technicianMetrics['first_time_fix'][$row->user_id] ?? 0;
                    return number_format($val, 1) . '%';
                })
                ->addColumn('comeback_ratio', function ($row) use ($technicianMetrics) {
                    $val = $technicianMetrics['comeback_ratio'][$row->user_id] ?? 0;
                    return number_format($val, 1) . '%';
                })
                ->addColumn('avg_repair_time', function ($row) use ($technicianMetrics) {
                    $val = $technicianMetrics['avg_repair_time'][$row->user_id] ?? 0;
                    return number_format($val, 1) . 'h';
                })
                ->addColumn('attendance_rate', function ($row) use ($technicianMetrics) {
                    $val = $technicianMetrics['attendance'][$row->user_id] ?? 0;
                    return number_format($val, 1) . '%';
                })
                ->addColumn('job_quality_index', function ($row) use ($technicianMetrics) {
                    $val = $technicianMetrics['quality_index'][$row->user_id] ?? 0;
                    return number_format($val, 1) . '%';
                })
                ->addColumn('late_arrivals', function ($row) {
                    return $row->late_arrivals ?? 0;
                })
                ->rawColumns(['efficiency', 'user_name', 'productive_hours', 'allocated_hours', 'scheduled_hours', 'productivity_rate', 'utilization_rate', 'first_time_fix_rate', 'comeback_ratio', 'avg_repair_time', 'attendance_rate', 'job_quality_index', 'late_arrivals'])
                ->make(true);
        }

        // Get optimized performance summary that matches technician metrics calculations
        $filters = $request->only(['workshop_id', 'location_id', 'start_date', 'end_date']);
        if (empty($filters['start_date']) && empty($filters['end_date'])) {
            $today = now()->toDateString();
            $filters['start_date'] = $today;
            $filters['end_date'] = $today;
        }
        $optimizedSummary = $metrics->getOptimizedPerformanceSummary($business_id, $filters);
        
        // Extract KPI values from optimized summary
        $total_technicians = $optimizedSummary['total_technicians'];
        $avg_eff = $optimizedSummary['avg_efficiency_rate'];
        $productivityRate = $optimizedSummary['avg_productivity_rate'];
        $utilizationRate = $optimizedSummary['avg_utilization_rate'];
        $attendanceRate = $optimizedSummary['avg_attendance_rate'];
        $jobQualityIndex = $optimizedSummary['avg_quality_index'];
        $comebackRatio = $optimizedSummary['avg_comeback_ratio'];
        $firstTimeFixRate = $optimizedSummary['avg_first_time_fix'];
        $averageRepairTime = $optimizedSummary['avg_repair_time'];
        $total_late = $optimizedSummary['total_late_arrivals'];
        
        // Additional summary data
        $performanceGrade = $optimizedSummary['performance_grade'];
        $topPerformers = $optimizedSummary['top_performers'];
        $areasForImprovement = $optimizedSummary['areas_for_improvement'];
        
        // For backward compatibility, calculate total productive and scheduled hours
        $legacySummary = collect($metrics->getPerformanceSummary($business_id, $filters));
        $total_prod = $legacySummary->sum('productive_hours');
        // Replace scheduled hours total with business schedule-based calculation within filtered period
        $scheduleData = $this->technicianDataService->getBusinessScheduleForTechnicians($business_id, $filters);
        $total_sched = (float) $scheduleData->sum('total_hours');

        // Pass current filter values to view
        $workshop_id = $filters['workshop_id'] ?? '';
        $location_id = $filters['location_id'] ?? '';
        $start_date = $filters['start_date'] ?? '';
        $end_date = $filters['end_date'] ?? '';

        return view('timemanagement::performance.index', compact(
            'workshops', 'locations', 'total_prod', 'total_sched', 'avg_eff', 'total_late',
            'productivityRate', 'utilizationRate', 'firstTimeFixRate', 'comebackRatio',
            'averageRepairTime', 'attendanceRate', 'jobQualityIndex',
            'workshop_id', 'location_id', 'start_date', 'end_date',
            // New optimized summary data
            'total_technicians', 'performanceGrade', 'topPerformers', 'areasForImprovement'
        ));
    }
}
