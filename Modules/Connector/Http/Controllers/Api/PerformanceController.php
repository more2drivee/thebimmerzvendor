<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\Connector\Transformers\CommonResource;
use Modules\TimeManagement\Services\TimeMetricsService;
use Modules\TimeManagement\Services\TechnicianDataService;

/**
 * @group Performance management
 * @authenticated
 *
 * APIs for managing technician performance metrics
 */
class PerformanceController extends ApiController
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;
    protected $timeMetricsService;
    protected $technicianDataService;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(
        ModuleUtil $moduleUtil,
        TimeMetricsService $timeMetricsService,
        TechnicianDataService $technicianDataService
    ) {
        $this->moduleUtil = $moduleUtil;
        $this->timeMetricsService = $timeMetricsService;
        $this->technicianDataService = $technicianDataService;
    }

    /**
     * Get Performance Summary
     *
     * @queryParam workshop_id int optional Workshop ID filter Example: 1
     * @queryParam location_id int optional Location ID filter Example: 1
     * @queryParam start_date string optional Start date filter (Y-m-d format) Example: 2024-01-01
     * @queryParam end_date string optional End date filter (Y-m-d format) Example: 2024-01-31
     * @response {
     *   "data": [
     *     {
     *       "user_id": 1,
     *       "user_name": "John Doe",
     *       "productive_hours": "40.5h",
     *       "scheduled_hours": "45.0h",
     *       "efficiency": "85%",
     *       "productivity_rate": "90.2%",
     *       "utilization_rate": "88.7%",
     *       "first_time_fix_rate": "92.3%",
     *       "comeback_ratio": "7.7%",
     *       "avg_repair_time": "2.5h",
     *       "attendance_rate": "95.0%",
     *       "job_quality_index": "92.3%",
     *       "late_arrivals": 2
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        try {
          

            $business_id = Auth::user()->business_id;
            $filters = $request->only(['workshop_id', 'location_id', 'start_date', 'end_date']);

            // Get all technician metrics in one optimized call (this fetches all data once)
            $technicianMetrics = $this->timeMetricsService->getAllTechnicianMetrics($business_id, $filters);
            
            // Get basic summary for user details (legacy format for compatibility)
            $summary = $this->timeMetricsService->getPerformanceSummary($business_id, $filters);

            // Use business schedule-based calculation for scheduled hours within filtered period
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

            // Format data to match TimeManagement controller output
            $result = [];
            foreach ($summary as $row) {
                if (is_object($row)) {
                    $row = (array) $row;
                }

                if (!is_array($row) || !isset($row['user_id'])) {
                    continue;
                }

                $userId = $row['user_id'];
                $efficiency = (int) ($technicianMetrics['efficiency'][$userId] ?? 0);
                
                $result[] = [
                    'user_id' => $userId,
                    'user_name' => $row['user_name'] ?? 'N/A',
                    'productive_hours' => number_format($row['productive_hours'] ?? 0, 1) . 'h',
                    // Replace scheduled hours with business schedule-based calculation within filtered period
                    'scheduled_hours' => number_format($userScheduledHours[$userId] ?? 0, 1) . 'h',
                    // Include allocated hours consistent with TimeManagement controller
                    'allocated_hours' => number_format($technicianMetrics['allocated_hours'][$userId] ?? 0, 2) . 'h',
                    'efficiency' => $efficiency . '%',
                    'productivity_rate' => number_format($technicianMetrics['productivity'][$userId] ?? 0, 1) . '%',
                    'utilization_rate' => number_format($technicianMetrics['utilization'][$userId] ?? 0, 1) . '%',
                    'first_time_fix_rate' => number_format($technicianMetrics['first_time_fix'][$userId] ?? 0, 1) . '%',
                    'comeback_ratio' => number_format($technicianMetrics['comeback_ratio'][$userId] ?? 0, 1) . '%',
                    'avg_repair_time' => number_format($technicianMetrics['avg_repair_time'][$userId] ?? 0, 1) . 'h',
                    'attendance_rate' => number_format($technicianMetrics['attendance'][$userId] ?? 0, 1) . '%',
                    'job_quality_index' => number_format($technicianMetrics['quality_index'][$userId] ?? 0, 1) . '%',
                    'late_arrivals' => $row['late_arrivals'] ?? 0
                ];
            }

            return $this->respond(['data' => $result]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
        }
    }

    /**
     * Get Technician Performance Details
     *
     * @urlParam user_id required Technician user ID Example: 1
     * @queryParam workshop_id int optional Workshop ID filter Example: 1
     * @queryParam location_id int optional Location ID filter Example: 1
     * @queryParam start_date string optional Start date filter (Y-m-d format) Example: 2024-01-01
     * @queryParam end_date string optional End date filter (Y-m-d format) Example: 2024-01-31
     * @response {
     *   "data": {
     *     "user_id": 1,
     *     "user_name": "John Doe",
     *     "productive_hours": "40.5h",
     *     "scheduled_hours": "45.0h",
     *     "efficiency": "85%",
     *     "productivity_rate": "90.2%",
     *     "utilization_rate": "88.7%",
     *     "first_time_fix_rate": "92.3%",
     *     "comeback_ratio": "7.7%",
     *     "avg_repair_time": "2.5h",
     *     "attendance_rate": "95.0%",
     *     "job_quality_index": "92.3%",
     *     "late_arrivals": 2
     *   }
     * }
     */
    public function show(Request $request, $user_id)
    {
        try {
         
            $business_id = Auth::user()->business_id;
            $filters = $request->only(['workshop_id', 'location_id', 'start_date', 'end_date']);

            // Get technician details
            $technicians = $this->technicianDataService->getTechniciansWithFilters($business_id, $filters);
            $technician = $technicians->where('id', $user_id)->first();

            if (!$technician) {
                return $this->setStatusCode(404)->respondWithError('Technician not found');
            }

            // Get all metrics for this technician
            $technicianMetrics = $this->timeMetricsService->getAllTechnicianMetrics($business_id, $filters);
            
            // Get basic summary for this specific technician
            $summary = $this->timeMetricsService->getPerformanceSummary($business_id, $filters);
            $technicianSummary = collect($summary)->where('user_id', $user_id)->first();

            if (!$technicianSummary) {
                return $this->setStatusCode(404)->respondWithError('Technician performance data not found');
            }

            if (is_object($technicianSummary)) {
                $technicianSummary = (array) $technicianSummary;
            }

            // Use business schedule-based calculation for scheduled hours within filtered period
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

            $efficiency = (int) ($technicianMetrics['efficiency'][$user_id] ?? 0);

            $result = [
                'user_id' => (int)$user_id,
                'user_name' => $technicianSummary['user_name'] ?? $technician->full_name,
                'productive_hours' => number_format($technicianSummary['productive_hours'] ?? 0, 1) . 'h',
                // Replace scheduled hours with business schedule-based calculation within filtered period
                'scheduled_hours' => number_format($userScheduledHours[$user_id] ?? 0, 1) . 'h',
                // Include allocated hours consistent with TimeManagement controller
                'allocated_hours' => number_format($technicianMetrics['allocated_hours'][$user_id] ?? 0, 2) . 'h',
                'efficiency' => $efficiency . '%',
                'productivity_rate' => number_format($technicianMetrics['productivity'][$user_id] ?? 0, 1) . '%',
                'utilization_rate' => number_format($technicianMetrics['utilization'][$user_id] ?? 0, 1) . '%',
                'first_time_fix_rate' => number_format($technicianMetrics['first_time_fix'][$user_id] ?? 0, 1) . '%',
                'comeback_ratio' => number_format($technicianMetrics['comeback_ratio'][$user_id] ?? 0, 1) . '%',
                'avg_repair_time' => number_format($technicianMetrics['avg_repair_time'][$user_id] ?? 0, 1) . 'h',
                'attendance_rate' => number_format($technicianMetrics['attendance'][$user_id] ?? 0, 1) . '%',
                'job_quality_index' => number_format($technicianMetrics['quality_index'][$user_id] ?? 0, 1) . '%',
                'late_arrivals' => $technicianSummary['late_arrivals'] ?? 0
            ];

            return $this->respond(['data' => $result]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
        }
    }

    /**
     * Get Individual Technician Performance Data
     *
     * @queryParam workshop_id int optional Workshop ID filter Example: 1
     * @queryParam location_id int optional Location ID filter Example: 1
     * @queryParam start_date string optional Start date filter (Y-m-d format) Example: 2024-01-01
     * @queryParam end_date string optional End date filter (Y-m-d format) Example: 2024-01-31
     * @response {
     *   "data": [
     *     {
     *       "user_id": 1,
     *       "user_name": "John Doe",
     *       "productive_hours": "40.5h",
     *       "scheduled_hours": "45.0h",
     *       "efficiency": "85%",
     *       "productivity_rate": "90.2%",
     *       "utilization_rate": "88.7%",
     *       "first_time_fix_rate": "92.3%",
     *       "comeback_ratio": "7.7%",
     *       "avg_repair_time": "2.5h",
     *       "attendance_rate": "95.0%",
     *       "job_quality_index": "92.3%",
     *       "late_arrivals": 2
     *     }
     *   ]
     * }
     */
    public function dashboard(Request $request)
    {
        try {
            if (!$this->moduleUtil->hasThePermissionInSubscription(Auth::user()->business_id, 'essentials_module')) {
                return $this->respondUnauthorized();
            }

            $business_id = Auth::user()->business_id;
            $filters = $request->only(['workshop_id', 'location_id', 'start_date', 'end_date']);

            // Get all technician metrics in one optimized call (this fetches all data once)
            $technicianMetrics = $this->timeMetricsService->getAllTechnicianMetrics($business_id, $filters);
            
            // Get basic summary for user details (legacy format for compatibility)
            $summary = $this->timeMetricsService->getPerformanceSummary($business_id, $filters);

            // Format data to match TimeManagement controller output - focus on individual technicians only
            $result = [];
            foreach ($summary as $row) {
                if (is_object($row)) {
                    $row = (array) $row;
                }

                if (!is_array($row) || !isset($row['user_id'])) {
                    continue;
                }

                $userId = $row['user_id'];
                $efficiency = (int) ($technicianMetrics['efficiency'][$userId] ?? 0);
                
                $result[] = [
                    'user_id' => $userId,
                    'user_name' => $row['user_name'] ?? 'N/A',
                    'productive_hours' => number_format($row['productive_hours'] ?? 0, 1) . 'h',
                    // Replace scheduled hours with business schedule-based calculation within filtered period
                    'scheduled_hours' => number_format($userScheduledHours[$userId] ?? 0, 1) . 'h',
                    // Include allocated hours consistent with TimeManagement controller
                    'allocated_hours' => number_format($technicianMetrics['allocated_hours'][$userId] ?? 0, 2) . 'h',
                    'efficiency' => $efficiency . '%',
                    'productivity_rate' => number_format($technicianMetrics['productivity'][$userId] ?? 0, 1) . '%',
                    'utilization_rate' => number_format($technicianMetrics['utilization'][$userId] ?? 0, 1) . '%',
                    'first_time_fix_rate' => number_format($technicianMetrics['first_time_fix'][$userId] ?? 0, 1) . '%',
                    'comeback_ratio' => number_format($technicianMetrics['comeback_ratio'][$userId] ?? 0, 1) . '%',
                    'avg_repair_time' => number_format($technicianMetrics['avg_repair_time'][$userId] ?? 0, 1) . 'h',
                    'attendance_rate' => number_format($technicianMetrics['attendance'][$userId] ?? 0, 1) . '%',
                    'job_quality_index' => number_format($technicianMetrics['quality_index'][$userId] ?? 0, 1) . '%',
                    'late_arrivals' => $row['late_arrivals'] ?? 0
                ];
            }

            return $this->respond(['data' => $result]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
        }
    }
}
