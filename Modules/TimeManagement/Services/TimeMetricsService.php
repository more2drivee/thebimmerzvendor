<?php

namespace Modules\TimeManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Modules\TimeManagement\Repositories\{
    AttendanceRepository,
    ProductivityRepository,
    JobRepository
};
use Modules\TimeManagement\Services\TechnicianDataService;
use Modules\TimeManagement\Entities\WorkshopTechnicianAssignmentHistory;

class TimeMetricsService
{
    protected AttendanceRepository $attendanceRepo;
    protected ProductivityRepository $productivityRepo;
    protected JobRepository $jobRepo;
    protected TechnicianDataService $technicianDataService;

    /**
     * Constructor-based dependency injection
     * (better than repeatedly calling app() helper).
     */
    public function __construct(
        AttendanceRepository $attendanceRepo,
        ProductivityRepository $productivityRepo,
        JobRepository $jobRepo,
        TechnicianDataService $technicianDataService
    ) {
        $this->attendanceRepo = $attendanceRepo;
        $this->productivityRepo = $productivityRepo;
        $this->jobRepo = $jobRepo;
        $this->technicianDataService = $technicianDataService;
    }

    /** ────────────────────────────────
     * Attendance & Presence
     * ──────────────────────────────── */

    public function getPresentCount(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): int
    {
        return $this->attendanceRepo->getPresentCount($businessId, $workshopId, $locationId, $startDate, $endDate);
    }

    public function getPresentTodayCount(int $businessId, $workshopId = null): int
    {
        return $this->attendanceRepo->getPresentTodayCount($businessId, $workshopId);
    }

    public function getLateArrivalsCount(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): int
    {
        return $this->attendanceRepo->getLateArrivalsCount($businessId, $workshopId, $locationId, $startDate, $endDate);
    }

    public function getAttendances(int $businessId, array $filters)
    {
        return $this->attendanceRepo->getAttendances($businessId, $filters);
    }

    /** ────────────────────────────────
     * Productivity & Performance
     * ──────────────────────────────── */

    public function getProductiveHours(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): float
    {
        return $this->attendanceRepo->getProductiveHours($businessId, $workshopId, $locationId, $startDate, $endDate);
    }

    /**
     * Get productivity rates for each technician within a date range.
     * Returns array with technician_id => productivity_rate_percentage
     */
    public function getTechnicianProductivityRates(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): array
    {
        return $this->productivityRepo->getProductivityRateByTechnician($businessId, $workshopId, $locationId, $startDate, $endDate);
    }

    public function getEfficiencyRate(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): float
    {
        return $this->productivityRepo->getEfficiencyRate($businessId, $workshopId, $locationId, $startDate, $endDate);
    }

    /**
     * Get efficiency rates for each technician within a date range.
     * Returns array with technician_id => efficiency_rate_percentage
     */
    public function getTechnicianEfficiencyRates(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): array
    {
        return $this->productivityRepo->getEfficiencyRateByTechnician($workshopId, $locationId, $startDate, $endDate);
    }

    /**
     * Get utilization rates for each technician within a date range.
     * Returns array with technician_id => utilization_rate_percentage
     */
    public function getTechnicianUtilizationRates(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): array
    {
        return $this->productivityRepo->getUtilizationRateByTechnician($workshopId, $locationId, $startDate, $endDate);
    }


    /**
     * Get average comeback ratio across all technicians within a date range.
     */
    public function getComebackRatio(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): float
    {
        $comebackRatios = $this->productivityRepo->getComebackRatio($businessId, $workshopId, $locationId, $startDate, $endDate);

        if (empty($comebackRatios)) {
            return 0.0;
        }

        return round(array_sum($comebackRatios) / count($comebackRatios), 2);
    }

    /**
     * Get comeback ratios for each technician within a date range.
     * Returns array with technician_id => comeback_ratio_percentage
     */
    public function getTechnicianComebackRatios(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): array
    {
        return $this->productivityRepo->getComebackRatio($businessId, $workshopId, $locationId, $startDate, $endDate);
    }

    /**
     * Get first time fix rates for each technician within a date range.
     * Returns array with technician_id => first_time_fix_rate_percentage
     */
    public function getTechnicianFirstTimeFixRates(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): array
    {
        return $this->productivityRepo->getFirstTimeFixRateByTechnician($workshopId, $locationId, $startDate, $endDate);
    }

    public function getAverageRepairTime(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): float
    {
        return $this->productivityRepo->getAverageRepairTime($businessId, $workshopId, $locationId, $startDate, $endDate);
    }

    /**
     * Get average repair times for each technician within a date range.
     * Returns array with technician_id => average_repair_time_hours
     */
    public function getTechnicianAverageRepairTimes(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): array
    {
        return $this->productivityRepo->getAverageRepairTimeByTechnician($workshopId, $locationId, $startDate, $endDate);
    }

    /**
     * Get attendance rates for each technician within a date range.
     * Returns array with technician_id => attendance_rate_percentage
     */
    public function getTechnicianAttendanceRates(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): array
    {
        return $this->productivityRepo->getAttendanceRateByTechnician($workshopId, $locationId, $startDate, $endDate);
    }

    public function getJobQualityIndex(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): float
    {
        return $this->productivityRepo->getJobQualityIndex($businessId, $workshopId, $locationId, $startDate, $endDate);
    }

    /**
     * Get Job Quality Index per technician within a date range.
     * Returns array with technician_id => quality_index_percentage
     */
    public function getTechnicianJobQualityIndex(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null): array
    {
        return $this->productivityRepo->getJobQualityIndexByTechnician($businessId, $workshopId, $locationId, $startDate, $endDate);
    }

    public function getPerformanceSummary(int $businessId, array $filters): array
    {
        return $this->productivityRepo->getPerformanceSummary($businessId, $filters)->toArray();
    }

    /**
     * Get optimized performance summary that matches technician metrics calculations
     * This method calculates summary KPIs from the same data sources as getAllTechnicianMetrics
     * for consistency and performance optimization.
     */
    public function getOptimizedPerformanceSummary(int $businessId, array $filters): array
    {
        // Get all technician metrics in one optimized call (reuses data fetching)
        $technicianMetrics = $this->getAllTechnicianMetrics($businessId, $filters);
        
        if (empty($technicianMetrics) || empty($technicianMetrics['efficiency'])) {
            return [
                'total_technicians' => 0,
                'avg_efficiency_rate' => 0,
                'avg_productivity_rate' => 0,
                'avg_utilization_rate' => 0,
                'avg_attendance_rate' => 0,
                'avg_quality_index' => 0,
                'avg_comeback_ratio' => 0,
                'avg_first_time_fix' => 0,
                'avg_repair_time' => 0,
                'total_late_arrivals' => 0
            ];
        }

        $totalTechnicians = count($technicianMetrics['efficiency']);
        
        // Calculate averages from technician metrics
        $avgEfficiency = $totalTechnicians > 0 ? round(array_sum($technicianMetrics['efficiency']) / $totalTechnicians, 2) : 0;
        $avgProductivity = $totalTechnicians > 0 ? round(array_sum($technicianMetrics['productivity']) / $totalTechnicians, 2) : 0;
        $avgUtilization = $totalTechnicians > 0 ? round(array_sum($technicianMetrics['utilization']) / $totalTechnicians, 2) : 0;
        $avgAttendance = $totalTechnicians > 0 ? round(array_sum($technicianMetrics['attendance']) / $totalTechnicians, 2) : 0;
        $avgQualityIndex = $totalTechnicians > 0 ? round(array_sum($technicianMetrics['quality_index']) / $totalTechnicians, 2) : 0;
        $avgComebackRatio = $totalTechnicians > 0 ? round(array_sum($technicianMetrics['comeback_ratio']) / $totalTechnicians, 2) : 0;
        $avgFirstTimeFix = $totalTechnicians > 0 ? round(array_sum($technicianMetrics['first_time_fix']) / $totalTechnicians, 2) : 0;
        $avgRepairTime = $totalTechnicians > 0 ? round(array_sum($technicianMetrics['avg_repair_time']) / $totalTechnicians, 2) : 0;

        // Get late arrivals count (need to fetch this separately as it's not in technician metrics)
        $lateArrivals = $this->getLateArrivalsCount(
            $businessId, 
            $filters['workshop_id'] ?? null, 
            $filters['location_id'] ?? null, 
            $filters['start_date'] ?? null, 
            $filters['end_date'] ?? null
        );

        return [
            'total_technicians' => $totalTechnicians,
            'avg_efficiency_rate' => $avgEfficiency,
            'avg_productivity_rate' => $avgProductivity,
            'avg_utilization_rate' => $avgUtilization,
            'avg_attendance_rate' => $avgAttendance,
            'avg_quality_index' => $avgQualityIndex,
            'avg_comeback_ratio' => $avgComebackRatio,
            'avg_first_time_fix' => $avgFirstTimeFix,
            'avg_repair_time' => $avgRepairTime,
            'total_late_arrivals' => $lateArrivals,
            // Additional summary metrics
            'performance_grade' => $this->calculatePerformanceGrade($avgEfficiency, $avgProductivity, $avgQualityIndex),
            'top_performers' => $this->getTopPerformers($technicianMetrics),
            'areas_for_improvement' => $this->getAreasForImprovement($avgEfficiency, $avgProductivity, $avgUtilization, $avgQualityIndex)
        ];
    }

    /** ────────────────────────────────
     * Job & Timer Information
     * ──────────────────────────────── */

    public function getActiveJobs(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null)
    {
        return $this->jobRepo->getActiveJobs($businessId, $workshopId, $locationId, $startDate, $endDate);
    }

    public function getLiveTimers(int $businessId, array $filters)
    {
        return $this->jobRepo->getLiveTimers($businessId, $filters);
    }

    public function getWorkersStatus(int $businessId, array $filters)
    {
        return $this->jobRepo->getWorkersStatus($businessId, $filters);
    }

    /**
     * Get all technician metrics in one optimized call to reduce database queries
     * and improve performance by fetching technician data once and reusing it.
     */
    public function getAllTechnicianMetrics(int $businessId, array $filters): array
    {
        // Get centralized technician data once
        $technicians = $this->technicianDataService->getTechniciansWithFilters($businessId, $filters);
        $timerData = $this->technicianDataService->getTimerDataForTechnicians($businessId, $filters);
        $attendanceData = $this->technicianDataService->getAttendanceDataForTechnicians($businessId, $filters);
        $shiftData = $this->technicianDataService->getBusinessScheduleForTechnicians($businessId, $filters);
        $comebackData = $this->technicianDataService->getComebackDataForTechnicians($businessId, $filters);
        $standardHoursData = $this->technicianDataService->getStandardHoursDataForTechnicians($businessId, $filters);
        $standardHoursLookup = $this->buildStandardHoursLookup($standardHoursData);

        Log::info('TimeMetricsService: getAllTechnicianMetrics called', [
            'business_id' => $businessId,
            'filters' => $filters,
            'technicians_count' => $technicians->count(),
            'timer_data_count' => $timerData->count(),
            'attendance_data_count' => $attendanceData->count(),
            'shift_data_count' => $shiftData->count(),
            'comeback_data' => [
                'job_sheets_count' => $comebackData['job_sheets']->count(),
                'comeback_job_sheets_count' => count($comebackData['comeback_job_sheets'])
            ],
            'standard_hours_count' => $standardHoursData->count()
        ]);

        // Initialize result arrays
        $metrics = [
            'efficiency' => [],
            'productivity' => [],
            'utilization' => [],
            'first_time_fix' => [],
            'comeback_ratio' => [],
            'avg_repair_time' => [],
            'attendance' => [],
            'quality_index' => [],
            'allocated_hours' => [],
            'actual_hours' => []
        ];

        // Extract date range for workshop assignment filtering
        $startDate = $filters['start_date'] ?? now()->startOfWeek();
        $endDate = $filters['end_date'] ?? now()->endOfWeek();

        // Calculate metrics for each technician using the centralized data
        foreach ($technicians as $technician) {
            $techId = $technician->id;

            // Calculate efficiency (Standard Hours / Actual Hours * 100)
            $actualHours = $timerData->where('user_id', $techId)->sum('work_seconds') / 3600;
            $standardHours = $this->calculateStandardHoursForTechnician($techId, $timerData, $standardHoursLookup);
            $metrics['allocated_hours'][$techId] = round($standardHours, 2);
            $metrics['actual_hours'][$techId] = round($actualHours, 2);
            $metrics['efficiency'][$techId] = $actualHours > 0 ? round(($standardHours / $actualHours) * 100, 2) : 0;

            // Calculate productivity (Labor Hours Sold / Labor Hours Available * 100)
            $availableHours = $this->calculateAvailableHoursForTechnician($techId, $attendanceData, $filters);
            $metrics['productivity'][$techId] = $availableHours > 0 ? round(($standardHours / $availableHours) * 100, 2) : 0;

            // Calculate utilization (Actual Hours / Attendance Hours * 100)
            $attendanceHours = $attendanceData->where('user_id', $techId)->sum('attendance_seconds') / 3600;
            $metrics['utilization'][$techId] = $attendanceHours > 0 ? round(($actualHours / $attendanceHours) * 100, 2) : 0;

            // Calculate comeback ratio and first time fix
            $comebackStats = $this->calculateComebackStatsForTechnician($techId, $comebackData);
            $metrics['comeback_ratio'][$techId] = $comebackStats['comeback_ratio'];
            $metrics['first_time_fix'][$techId] = $comebackStats['first_time_fix'];

            // Calculate average repair time
            $jobCount = $timerData->where('user_id', $techId)->groupBy('job_sheet_id')->count();
            $metrics['avg_repair_time'][$techId] = $jobCount > 0 ? round($actualHours / $jobCount, 2) : 0;

            // Calculate attendance rate
            $metrics['attendance'][$techId] = $this->calculateAttendanceRateForTechnician($techId, $attendanceData, $shiftData, $filters);

            // Calculate quality index (100 - comeback_ratio)
            if($metrics['comeback_ratio'][$techId] > 0) {
                $metrics['quality_index'][$techId] = round(100 - $metrics['comeback_ratio'][$techId], 2);
            } else {
                $metrics['quality_index'][$techId] = 0;
            }
        }
            Log::debug('TimeMetricsService: Technician metrics calculated', [
                'technician_id' => $techId,
                'technician_name' => $technician->full_name,
                'actual_hours' => round($actualHours, 2),
                'standard_hours' => round($standardHours, 2),
                'available_hours' => round($availableHours, 2),
                'attendance_hours' => round($attendanceHours, 2),
                'job_count' => $jobCount,
                'metrics' => [
                    'efficiency' => $metrics['efficiency'][$techId],
                    'productivity' => $metrics['productivity'][$techId],
                    'utilization' => $metrics['utilization'][$techId],
                    'first_time_fix' => $metrics['first_time_fix'][$techId],
                    'comeback_ratio' => $metrics['comeback_ratio'][$techId],
                    'avg_repair_time' => $metrics['avg_repair_time'][$techId],
                    'attendance' => $metrics['attendance'][$techId],
                    'quality_index' => $metrics['quality_index'][$techId]
                ]
            ]);
        

        Log::info('TimeMetricsService: getAllTechnicianMetrics completed', [
            'total_technicians_processed' => count($technicians),
            'metrics_summary' => array_map(function($metricArray) {
                return count($metricArray) . ' technicians';
            }, $metrics)
        ]);

        return $metrics;
    }

    /**
     * Get all workshop assignments for a technician within date range (optimized for bulk lookups)
     * Returns array keyed by job_sheet_id for fast in-memory lookups
     */
    private function getTechnicianWorkshopAssignments(int $techId, $startDate, $endDate): array
    {
        $assignments = WorkshopTechnicianAssignmentHistory::where('user_id', $techId)
            ->where('assignment_type', 'job_sheet')
            ->where('status', 'assigned')
            ->whereNotNull('job_sheet_id')
            ->select('job_sheet_id', 'workshop_id', 'status', 'created_at', 'updated_at')
            ->get();

        // Group by job_sheet_id for fast lookups
        $assignmentMap = [];
        foreach ($assignments as $assignment) {
            $jobSheetId = $assignment->job_sheet_id;
            if (!isset($assignmentMap[$jobSheetId])) {
                $assignmentMap[$jobSheetId] = [];
            }
            $assignmentMap[$jobSheetId][] = $assignment;
        }

        return $assignmentMap;
    }

    /**
     * Check if technician was assigned to workshop for specific job sheet at given time
     * Uses pre-loaded assignments for performance (no DB queries in loop)
     */
    private function checkWorkshopAssignmentAtTime(array $jobSheetAssignments, $serviceTimestamp): ?int
    {
        if (empty($jobSheetAssignments)) {
            return null;
        }

        foreach ($jobSheetAssignments as $assignment) {
            if ($assignment->status === 'assigned') {
                return $assignment->workshop_id;
            }
        }

        return null;
    }

    /**
     * Calculate standard hours for a specific technician based on timer allocations
     * Uses `time_allocate` from timer_tracking entries, falling back to service hours when missing
     */
    private function calculateStandardHoursForTechnician(int $techId, $timerData, array $standardHoursLookup): float
    {
        $totalHours = 0.0;
        $processedJobs = 0;
        $fallbackJobs = 0;

        $timersByJob = $timerData->where('user_id', $techId)->groupBy('job_sheet_id');

        foreach ($timersByJob as $jobSheetId => $timers) {
            if (empty($jobSheetId)) {
                continue;
            }

            $allocatedHours = $timers->pluck('time_allocate')
                ->filter(function ($value) {
                    return $value !== null && $value !== '' && is_numeric($value);
                })
                ->map(function ($value) {
                    return (float) $value;
                });

            if ($allocatedHours->isNotEmpty()) {
                // Use the largest allocation provided for this job to avoid double counting multiple entries
                $totalHours += $allocatedHours->max();
            } else {
                $fallbackHours = $this->getFallbackStandardHoursFromLookup((int) $jobSheetId, $standardHoursLookup);
                if ($fallbackHours > 0) {
                    $totalHours += $fallbackHours;
                }
                $fallbackJobs++;
            }

            $processedJobs++;
        }

        Log::debug("Standard hours (timer allocation) for technician {$techId}", [
            'total_hours' => $totalHours,
            'jobs_processed' => $processedJobs,
            'jobs_using_fallback' => $fallbackJobs
        ]);

        return $totalHours;
    }

    /**
     * Build lookup array of per-technician standard hours derived from service products per job sheet.
     */
    private function buildStandardHoursLookup($standardHoursData): array
    {
        $lookup = [];

        foreach ($standardHoursData as $record) {
            $jobSheetId = $record->job_sheet_id ?? null;
            if (empty($jobSheetId)) {
                continue;
            }

            $serviceHours = (float) ($record->serviceHours ?? 0);
            if ($serviceHours <= 0) {
                continue;
            }

            $quantity = max(1.0, (float) ($record->quantity ?? 1));
            $technicians = json_decode($record->service_staff ?? '[]', true) ?: [];
            $technicians = array_filter(array_map('intval', $technicians));
            $techCount = max(1, count($technicians));

            $perTechHours = ($serviceHours * $quantity) / $techCount;

            if (!isset($lookup[$jobSheetId])) {
                $lookup[$jobSheetId] = [
                    'per_tech_hours' => 0.0,
                    'technician_count' => $techCount,
                ];
            }

            $lookup[$jobSheetId]['per_tech_hours'] += $perTechHours;
        }

        return $lookup;
    }

    /**
     * Retrieve fallback hours for a job sheet from the precomputed lookup table.
     */
    private function getFallbackStandardHoursFromLookup(int $jobSheetId, array $standardHoursLookup): float
    {
        if (!isset($standardHoursLookup[$jobSheetId])) {
            return 0.0;
        }

        return (float) ($standardHoursLookup[$jobSheetId]['per_tech_hours'] ?? 0.0);
    }

    /**
     * Calculate scheduled hours for a specific technician using business work schedule
     */
    private function calculateScheduledHoursForTechnician(int $techId, $scheduleData, array $filters): float
    {
        $startDate = $filters['start_date'] ?? now()->startOfWeek();
        $endDate = $filters['end_date'] ?? now()->endOfWeek();

        if (is_string($startDate)) $startDate = \Carbon\Carbon::parse($startDate);
        if (is_string($endDate)) $endDate = \Carbon\Carbon::parse($endDate);

        // Get business work schedule from common_settings
        $businessId = Auth::user()->business_id;
        $businessSettings = DB::table('business')->where('id', $businessId)->first();
        $commonSettings = json_decode($businessSettings->common_settings ?? '{}', true);
        
        $workDays = $commonSettings['work_days'] ?? [];
        $workHours = $commonSettings['work_hours'] ?? [];
        
        $totalHours = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $dayName = strtolower($currentDate->format('l')); // monday, tuesday, etc.
            
            // Check if technician attended this day
            $attended = $scheduleData->where('user_id', $techId)
                                   ->where('attendance_date', $currentDate->toDateString())
                                   ->isNotEmpty();
            
            if ($attended && !empty($workDays[$dayName])) {
                $dayHours = $workHours[$dayName]['total'] ?? 8; // Default 8 hours
                $totalHours += (float) $dayHours;
            }
            
            $currentDate->addDay();
        }
        
        return $totalHours;
    }

    /**
     * Calculate comeback statistics for a specific technician
     */
    private function calculateComebackStatsForTechnician(int $techId, array $comebackData): array
    {
        if (!isset($comebackData['job_sheets']) || $comebackData['job_sheets']->isEmpty()) {
            return ['comeback_ratio' => 0, 'first_time_fix' => 0];
        }

        $jobSheets = $comebackData['job_sheets'];
        $comebackJobSheets = $comebackData['comeback_job_sheets'] ?? [];

        $totalJobs = 0;
        $comebackJobs = 0;

        foreach ($jobSheets as $job) {
            $technicians = json_decode($job->service_staff ?? '[]', true) ?: [];
            if (in_array($techId, $technicians)) {
                $totalJobs++;
                if (isset($comebackJobSheets[$job->id])) {
                    $comebackJobs++;
                }
            }
        }

        if ($totalJobs === 0) {
            return ['comeback_ratio' => 0, 'first_time_fix' => 0];
        }

        $comebackRatio = ($comebackJobs / $totalJobs) * 100;
        $firstTimeFix = (($totalJobs - $comebackJobs) / $totalJobs) * 100;

        return [
            'comeback_ratio' => round($comebackRatio, 2),
            'first_time_fix' => round($firstTimeFix, 2)
        ];
    }

    /**
     * Calculate attendance rate for a specific technician
     */
    private function calculateAttendanceRateForTechnician(int $techId, $attendanceData, $shiftData, array $filters): float
    {
        $attendedDays = $attendanceData->where('user_id', $techId)->pluck('attendance_date')->unique()->count();
        $scheduledDays = $this->calculateScheduledDaysForTechnician($techId, $shiftData, $filters);
        
        return $scheduledDays > 0 ? round(($attendedDays / $scheduledDays) * 100, 2) : 0;
    }

    /**
     * Calculate available hours for a specific technician using business work schedule
     */
    private function calculateAvailableHoursForTechnician(int $techId, $attendanceData, array $filters): float
    {
        $startDate = $filters['start_date'] ?? now()->startOfWeek();
        $endDate = $filters['end_date'] ?? now()->endOfWeek();
        
        if (is_string($startDate)) $startDate = \Carbon\Carbon::parse($startDate);
        if (is_string($endDate)) $endDate = \Carbon\Carbon::parse($endDate);

        // Get business work schedule
        $businessId = Auth::user()->business_id;
        $businessSettings = DB::table('business')->where('id', $businessId)->first();
        $commonSettings = json_decode($businessSettings->common_settings ?? '{}', true);
        
        $workDays = $commonSettings['work_days'] ?? [];
        $workHours = $commonSettings['work_hours'] ?? [];
        
        // Calculate total available hours for ALL working days in the date range
        // (not just days the technician attended)
        $totalAvailableHours = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $dayName = strtolower($currentDate->format('l'));
            
            if (!empty($workDays[$dayName])) {
                $dayHours = $workHours[$dayName]['total'] ?? 0; // Default 8 hours
                $totalAvailableHours += (float) $dayHours;
            }
            
            $currentDate->addDay();
        }
        
        return $totalAvailableHours;
    }

    /**
     * Calculate scheduled days for a specific technician
     */
    private function calculateScheduledDaysForTechnician(int $techId, $shiftData, array $filters): int
    {
        // Count unique dates from shift data for this technician
        return $shiftData->where('user_id', $techId)
                        ->pluck('date')
                        ->unique()
                        ->count();
    }

    /**
     * Calculate overall performance grade based on key metrics
     */
    private function calculatePerformanceGrade(float $efficiency, float $productivity, float $quality): string
    {
        $overallScore = ($efficiency + $productivity + $quality) / 3;
        
        if ($overallScore >= 90) return 'A+';
        if ($overallScore >= 80) return 'A';
        if ($overallScore >= 70) return 'B';
        if ($overallScore >= 60) return 'C';
        if ($overallScore >= 50) return 'D';
        return 'F';
    }

    /**
     * Get top performing technicians across all metrics
     */
    private function getTopPerformers(array $technicianMetrics): array
    {
        if (empty($technicianMetrics['efficiency'])) {
            return [];
        }

        $topPerformers = [];
        
        // Get top 3 in efficiency
        arsort($technicianMetrics['efficiency']);
        $topEfficiency = array_slice($technicianMetrics['efficiency'], 0, 3, true);
        
        // Get top 3 in productivity
        arsort($technicianMetrics['productivity']);
        $topProductivity = array_slice($technicianMetrics['productivity'], 0, 3, true);
        
        // Get top 3 in quality
        arsort($technicianMetrics['quality_index']);
        $topQuality = array_slice($technicianMetrics['quality_index'], 0, 3, true);

        return [
            'efficiency' => $topEfficiency,
            'productivity' => $topProductivity,
            'quality' => $topQuality
        ];
    }

    /**
     * Identify areas that need improvement based on average scores
     */
    private function getAreasForImprovement(float $efficiency, float $productivity, float $utilization, float $quality): array
    {
        $improvements = [];
        
        if ($efficiency < 70) {
            $improvements[] = [
                'area' => 'Efficiency Rate',
                'current' => $efficiency,
                'target' => 80,
                'recommendation' => 'Focus on completing jobs within standard time estimates'
            ];
        }
        
        if ($productivity < 70) {
            $improvements[] = [
                'area' => 'Productivity Rate',
                'current' => $productivity,
                'target' => 80,
                'recommendation' => 'Increase billable hours relative to available work time'
            ];
        }
        
        if ($utilization < 70) {
            $improvements[] = [
                'area' => 'Utilization Rate',
                'current' => $utilization,
                'target' => 80,
                'recommendation' => 'Reduce idle time and maximize active work hours'
            ];
        }
        
        if ($quality < 80) {
            $improvements[] = [
                'area' => 'Quality Index',
                'current' => $quality,
                'target' => 90,
                'recommendation' => 'Reduce comeback jobs by improving first-time fix rates'
            ];
        }
        
        return $improvements;
    }
}
