<?php

namespace Modules\TimeManagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TechnicianMetricsCalculator
{
    private TechnicianDataService $dataService;
    private array $metricsCache = [];

    public function __construct(TechnicianDataService $dataService)
    {
        $this->dataService = $dataService;
    }

    /**
     * Calculate all metrics for technicians in one go
     */
    public function calculateAllMetrics(int $businessId, ?int $workshopId = null, ?int $locationId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = "all_metrics_{$businessId}_{$workshopId}_{$locationId}_{$startDate}_{$endDate}";
        
        if (isset($this->metricsCache[$cacheKey])) {
            return $this->metricsCache[$cacheKey];
        }

        // Fetch all required data once
        $technicians = $this->dataService->getTechnicians($businessId, $workshopId, $locationId);
        $jobSheets = $this->dataService->getJobSheetsWithTechnicians($businessId, $workshopId, $locationId, $startDate, $endDate);
        $shifts = $this->dataService->getTechnicianShifts($businessId, $startDate, $endDate);
        $timerData = $this->dataService->getTimerTrackingData($businessId, $workshopId, $locationId, $startDate, $endDate);
        $attendanceData = $this->dataService->getAttendanceData($businessId, $startDate, $endDate);
        $comebackJobIds = $this->dataService->getComebackJobSheetIds($businessId, $workshopId, $locationId, $startDate, $endDate);
        
        // Get standard hours data for all job sheets
        $allJobSheetIds = array_column($jobSheets, 'id');
        $standardHoursData = $this->dataService->getStandardHoursData($allJobSheetIds);

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfWeek();

        $metrics = [];

        foreach ($technicians as $techId => $technician) {
            $metrics[$techId] = [
                'efficiency_rate' => $this->calculateEfficiencyRate($techId, $timerData, $standardHoursData, $jobSheets),
                'productivity_rate' => $this->calculateProductivityRate($techId, $shifts, $standardHoursData, $jobSheets, $start, $end),
                'utilization_rate' => $this->calculateUtilizationRate($techId, $timerData, $attendanceData),
                'first_time_fix_rate' => $this->calculateFirstTimeFixRate($techId, $jobSheets, $comebackJobIds),
                'comeback_ratio' => $this->calculateComebackRatio($techId, $jobSheets, $comebackJobIds),
                'avg_repair_time' => $this->calculateAverageRepairTime($techId, $timerData),
                'attendance_rate' => $this->calculateAttendanceRate($techId, $shifts, $attendanceData, $start, $end),
                'job_quality_index' => 0 // Will be calculated after comeback_ratio
            ];

            // Calculate job quality index (100 - comeback_ratio)
            $metrics[$techId]['job_quality_index'] = round(100 - $metrics[$techId]['comeback_ratio'], 2);
        }

        $this->metricsCache[$cacheKey] = $metrics;
        return $metrics;
    }

    /**
     * Calculate efficiency rate for a technician
     * Efficiency Rate = (Total Standard Hours / Total Actual Hours Worked) × 100
     */
    private function calculateEfficiencyRate(int $techId, array $timerData, array $standardHoursData, array $jobSheets): float
    {
        $techTimerData = $timerData[$techId] ?? [];
        $actualHours = 0;

        foreach ($techTimerData as $timer) {
            $actualHours += $timer->total_seconds / 3600;
        }

        if ($actualHours <= 0) {
            return 0.0;
        }

        $standardHours = $this->calculateStandardHours($techId, $standardHoursData, $jobSheets);
        
        return round(($standardHours / $actualHours) * 100, 2);
    }

    /**
     * Calculate productivity rate for a technician
     * Productivity Rate = (Total Labor Hours Sold / Total Labor Hours Available) × 100
     */
    private function calculateProductivityRate(int $techId, array $shifts, array $standardHoursData, array $jobSheets, Carbon $start, Carbon $end): float
    {
        $techShifts = $shifts[$techId] ?? [];
        $scheduledHours = $this->dataService->calculateScheduledHours($techShifts, $start, $end);

        if ($scheduledHours <= 0) {
            return 0.0;
        }

        $standardHours = $this->calculateStandardHours($techId, $standardHoursData, $jobSheets);
        
        return round(($standardHours / $scheduledHours) * 100, 2);
    }

    /**
     * Calculate utilization rate for a technician
     * Utilization Rate = (Actual Hours Worked on Jobs / Total Attendance Hours) × 100
     */
    private function calculateUtilizationRate(int $techId, array $timerData, array $attendanceData): float
    {
        $techTimerData = $timerData[$techId] ?? [];
        $actualHours = 0;

        foreach ($techTimerData as $timer) {
            $actualHours += $timer->total_seconds / 3600;
        }

        $attendanceHours = $attendanceData[$techId]->total_attendance_hours ?? 0;

        if ($attendanceHours <= 0) {
            return 0.0;
        }

        return round(($actualHours / $attendanceHours) * 100, 2);
    }

    /**
     * Calculate first time fix rate for a technician
     */
    private function calculateFirstTimeFixRate(int $techId, array $jobSheets, array $comebackJobIds): float
    {
        $totalJobs = 0;
        $comebackJobs = 0;

        foreach ($jobSheets as $job) {
            if (in_array($techId, $job->technician_ids)) {
                $totalJobs++;
                if (isset($comebackJobIds[$job->id])) {
                    $comebackJobs++;
                }
            }
        }

        if ($totalJobs <= 0) {
            return 0.0;
        }

        return round((($totalJobs - $comebackJobs) / $totalJobs) * 100, 2);
    }

    /**
     * Calculate comeback ratio for a technician
     */
    private function calculateComebackRatio(int $techId, array $jobSheets, array $comebackJobIds): float
    {
        $totalJobs = 0;
        $comebackJobs = 0;

        foreach ($jobSheets as $job) {
            if (in_array($techId, $job->technician_ids)) {
                $totalJobs++;
                if (isset($comebackJobIds[$job->id])) {
                    $comebackJobs++;
                }
            }
        }

        if ($totalJobs <= 0) {
            return 0.0;
        }

        return round(($comebackJobs / $totalJobs) * 100, 2);
    }

    /**
     * Calculate average repair time for a technician
     */
    private function calculateAverageRepairTime(int $techId, array $timerData): float
    {
        $techTimerData = $timerData[$techId] ?? [];
        
        if (empty($techTimerData)) {
            return 0.0;
        }

        $totalHours = 0;
        $jobCount = 0;

        foreach ($techTimerData as $timer) {
            $totalHours += $timer->total_seconds / 3600;
            $jobCount++;
        }

        if ($jobCount <= 0) {
            return 0.0;
        }

        return round($totalHours / $jobCount, 2);
    }

    /**
     * Calculate attendance rate for a technician
     */
    private function calculateAttendanceRate(int $techId, array $shifts, array $attendanceData, Carbon $start, Carbon $end): float
    {
        $techShifts = $shifts[$techId] ?? [];
        
        if (empty($techShifts)) {
            return 0.0;
        }

        // Calculate total scheduled days
        $scheduledDays = 0;
        foreach ($techShifts as $shift) {
            $assignStart = $shift->start_date ? Carbon::parse($shift->start_date) : $start;
            $assignEnd = $shift->end_date ? Carbon::parse($shift->end_date) : $end;
            $rangeStart = $assignStart->gt($start) ? $assignStart : $start;
            $rangeEnd = $assignEnd->lt($end) ? $assignEnd : $end;

            if ($rangeEnd->gte($rangeStart)) {
                $scheduledDays += $rangeStart->diffInDays($rangeEnd) + 1;
            }
        }

        if ($scheduledDays <= 0) {
            return 0.0;
        }

        $attendedDays = $attendanceData[$techId]->days_attended ?? 0;
        
        return round(($attendedDays / $scheduledDays) * 100, 2);
    }

    /**
     * Calculate standard hours for a technician based on job sheets they worked on
     */
    private function calculateStandardHours(int $techId, array $standardHoursData, array $jobSheets): float
    {
        $totalStandardHours = 0;

        // Get job sheet IDs that this technician worked on
        $techJobSheetIds = [];
        foreach ($jobSheets as $job) {
            if (in_array($techId, $job->technician_ids)) {
                $techJobSheetIds[] = $job->id;
            }
        }

        // Sum up standard hours for those job sheets
        foreach ($standardHoursData as $record) {
            if (in_array($record->repair_job_sheet_id, $techJobSheetIds)) {
                $serviceHours = (float)($record->serviceHours ?? 0);
                $quantity = (float)($record->quantity ?? 1);
                $totalStandardHours += $serviceHours * $quantity;
            }
        }

        return $totalStandardHours;
    }

    /**
     * Get individual metric for a technician
     */
    public function getMetric(string $metricName, int $businessId, ?int $workshopId = null, ?int $locationId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $allMetrics = $this->calculateAllMetrics($businessId, $workshopId, $locationId, $startDate, $endDate);
        
        $result = [];
        foreach ($allMetrics as $techId => $metrics) {
            if (isset($metrics[$metricName])) {
                $result[$techId] = $metrics[$metricName];
            }
        }

        return $result;
    }

    /**
     * Clear metrics cache
     */
    public function clearCache(): void
    {
        $this->metricsCache = [];
        $this->dataService->clearCache();
    }
}
