<?php

namespace Modules\TimeManagement\Http\Controllers;

use Carbon\Carbon;
use App\BusinessLocation;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\TimeManagement\Services\TimeMetricsService;
use Modules\TimeManagement\Services\TechnicianDataService;
use Modules\TimeManagement\Entities\WorkshopTechnicianAssignmentHistory;

class TimeControlController extends Controller
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
    public function __construct(ModuleUtil $moduleUtil, TimeMetricsService $timeMetricsService, TechnicianDataService $technicianDataService)
    {
        $this->moduleUtil = $moduleUtil;
        $this->timeMetricsService = $timeMetricsService;
        $this->technicianDataService = $technicianDataService;
    }

    /**
     * Get Unified Job Timers Data (Optimized Unified Endpoint)
     *
     * This unified endpoint combines job sheets, timers, and technician data into a single optimized response.
     * It replaces multiple separate endpoints with a single, efficient data structure that organizes
     * information by job sheet (not individual timers), matching the web interface structure.
     *
     * Key Features:
     * - Job-centric data organization (each job contains its timers and technicians)
     * - Optimized database queries with minimal N+1 query issues
     * - Real-time elapsed time calculations for active/paused timers
     * - Comprehensive device information and technician assignments
     * - Pagination and filtering capabilities
     * - Statistics summary (active count, total time, unique technicians)
     * - Backward compatibility with existing integrations
     *
     * Database Optimization Strategy:
     * 1. Single query for active job sheets with joins for related data
     * 2. Batch loading of user names to avoid N+1 queries
     * 3. Efficient timer data grouping by job_sheet_id and user_id
     * 4. In-memory processing for elapsed time calculations
     * 5. Minimal database round trips through strategic data loading
     *
     * Response Structure:
     * - Each job sheet contains complete timer and technician information
     * - Workers array shows individual technician timer states
     * - Device information includes all relevant vehicle/equipment details
     * - Statistics provide real-time dashboard metrics
     * - Pagination supports efficient data loading for large datasets
     *
     * @queryParam workshop_id int optional Workshop ID filter Example: 1
     * @queryParam location_id int optional Location ID filter Example: 1
     * @queryParam start_date string optional Start date filter (Y-m-d format) Example: 2024-01-01
     * @queryParam end_date string optional End date filter (Y-m-d format) Example: 2024-01-31
     * @queryParam per_page int optional Items per page (default: 10) Example: 15
     * @queryParam page int optional Page number (default: 1) Example: 1
     * @queryParam status string optional Filter by job status (active, completed, etc.) Example: active
     * @queryParam technician_id int optional Filter by specific technician Example: 5
     * 
     * @response {
     *   "data": {
     *     "job_sheets": [
     *       {
     *         "id": 123,
     *         "job_sheet_no": "JS-001",
     *         "workshop_id": 1,
     *         "workshop_name": "Main Workshop",
     *         "location_id": 1,
     *         "status_name": "In Progress",
     *         "status_color": "#28a745",
     *         "created_at": "2024-01-15T10:30:00Z",
     *         "updated_at": "2024-01-15T14:20:00Z",
     *         "device": {
     *           "id": 456,
     *           "name": "Toyota",
     *           "model": "Camry",
     *           "plate_number": "ABC-123",
     *           "chassis_number": "1HGBH41JXMN109186",
     *           "color": "Silver",
     *           "manufacturing_year": "2020",
     *           "car_type": "Sedan"
     *         },
     *         "service_staff": [1, 2, 3],
     *         "technicians": ["John Doe", "Jane Smith", "Mike Johnson"],
     *         "timer_summary": {
     *           "total_elapsed_seconds": 7200,
     *           "active_workers": 2,
     *           "paused_workers": 1,
     *           "completed_workers": 0,
     *           "total_workers": 3,
     *           "has_active_timers": true,
     *           "all_timers_completed": false
     *         },
     *         "workers": [
     *           {
     *             "user_id": 1,
     *             "user_name": "John Doe",
     *             "timer_id": 789,
     *             "timer_status": "active",
     *             "elapsed_seconds": 3600,
     *             "started_at": "2024-01-15T13:00:00Z",
     *             "last_action_at": "2024-01-15T13:00:00Z"
     *           },
     *           {
     *             "user_id": 2,
     *             "user_name": "Jane Smith",
     *             "timer_id": 790,
     *             "timer_status": "paused",
     *             "elapsed_seconds": 2400,
     *             "started_at": "2024-01-15T12:30:00Z",
     *             "paused_at": "2024-01-15T14:00:00Z",
     *             "last_action_at": "2024-01-15T14:00:00Z"
     *           },
     *           {
     *             "user_id": 3,
     *             "user_name": "Mike Johnson",
     *             "timer_id": null,
     *             "timer_status": null,
     *             "elapsed_seconds": 0,
     *             "started_at": null,
     *             "last_action_at": null
     *           }
     *         ]
     *       }
     *     ],
     *     "statistics": {
     *       "active_job_sheets": 5,
     *       "total_active_timers": 8,
     *       "total_elapsed_seconds": 28800,
     *       "total_elapsed_formatted": "8h 00m",
     *       "unique_technicians_active": 6,
     *       "workshops_with_activity": 3,
     *       "average_job_duration": 5760
     *     },
     *     "pagination": {
     *       "current_page": 1,
     *       "per_page": 10,
     *       "total_items": 25,
     *       "total_pages": 3,
     *       "has_more_pages": true,
     *       "next_page": 2,
     *       "prev_page": null
     *     },
     *     "filters_applied": {
     *       "workshop_id": 1,
     *       "location_id": null,
     *       "start_date": "2024-01-01",
     *       "end_date": "2024-01-31",
     *       "status": "active",
     *       "technician_id": null
     *     }
     *   }
     * }
     */
    public function index(Request $request)
    {
        try {
       

            $business_id = Auth::user()->business_id;
            
            // Extract and validate filters from request
            $filters = $this->extractAndValidateFilters($request);
            
            // Get paginated job sheets with optimized queries
            $paginationData = $this->getPaginatedJobSheets($business_id, $filters, $request);
            
            // Build unified response structure
            $response = $this->buildUnifiedResponse($paginationData, $filters);

            $timersRaw = $response['job_sheets'];

            return view('timemanagement::time_control.index', [
                'timers' => $this->prepareTimersForView($timersRaw),
                'stats' => $this->buildTimerStats($timersRaw),
                'pagination' => $response['pagination'],
                'filters' => $response['filters_applied'],
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Failed to load time control data'], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $business_id = Auth::user()->business_id;
            $filters = $this->extractAndValidateFilters($request);

            $paginationData = $this->getPaginatedJobSheets($business_id, $filters, $request);
            $response = $this->buildUnifiedResponse($paginationData, $filters);

            return response()->json([
                'timers' => $response['job_sheets'],
                'stats' => $this->buildTimerStats($response['job_sheets']),
                'pagination' => [
                    'page' => $paginationData['pagination']['current_page'],
                    'per_page' => $paginationData['pagination']['per_page'],
                    'total' => $paginationData['pagination']['total_items'],
                    'has_more' => $paginationData['pagination']['has_more_pages'],
                    'next_page' => $paginationData['pagination']['next_page'],
                    'prev_page' => $paginationData['pagination']['prev_page'],
                ],
                'filters' => $response['filters_applied'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Unable to load timers'], 500);
        }
    }

    /**
     * Extract and validate filters from the request
     * 
     * This method centralizes filter processing and validation to ensure
     * consistent behavior across all timer-related endpoints.
     *
     * @param Request $request
     * @return array Validated filters array
     */
    private function extractAndValidateFilters(Request $request): array
    {
        $filters = $request->only([
            'workshop_id', 
            'location_id', 
            'start_date', 
            'end_date', 
            'status', 
            'technician_id'
        ]);

        // Validate date formats if provided
        if (!empty($filters['start_date'])) {
            try {
                Carbon::parse($filters['start_date']);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid start_date format. Use Y-m-d format.');
            }
        }

        if (!empty($filters['end_date'])) {
            try {
                Carbon::parse($filters['end_date']);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid end_date format. Use Y-m-d format.');
            }
        }

        // Validate numeric filters
        if (!empty($filters['workshop_id']) && !is_numeric($filters['workshop_id'])) {
            throw new \InvalidArgumentException('workshop_id must be a valid integer.');
        }

        if (!empty($filters['location_id']) && !is_numeric($filters['location_id'])) {
            throw new \InvalidArgumentException('location_id must be a valid integer.');
        }

        if (!empty($filters['technician_id']) && !is_numeric($filters['technician_id'])) {
            throw new \InvalidArgumentException('technician_id must be a valid integer.');
        }

        return $filters;
    }

    /**
     * Get paginated job sheets with optimized database queries
     * 
     * This method implements the core database optimization strategy:
     * 1. Single optimized query for job sheets with necessary joins
     * 2. Batch loading of related data to minimize database round trips
     * 3. Efficient pagination handling
     * 4. Strategic use of indexes and query optimization
     *
     * @param int $business_id
     * @param array $filters
     * @param Request $request
     * @return array Contains job_sheets, pagination info, and statistics
     */
    private function getPaginatedJobSheets(int $business_id, array $filters, Request $request): array
    {
        $perPage = max(1, min(50, (int) $request->input('per_page', 10))); // Limit to reasonable range
        $page = max(1, (int) $request->input('page', 1));
        
        // Build optimized query for active job sheets with device information
        $query = DB::table('repair_job_sheets as rjs')
            ->leftJoin('bookings', 'bookings.id', '=', 'rjs.booking_id')
            ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')
            ->leftJoin('contacts', 'contacts.id', '=', 'bookings.contact_id')
            ->leftJoin('repair_statuses as rs', 'rjs.status_id', '=', 'rs.id')
            ->leftJoin('repair_device_models as rdm', 'rdm.id', '=', 'contact_device.models_id')
            ->leftJoin('categories as cat', 'contact_device.device_id', '=', 'cat.id')
            ->leftJoin('business_locations as bl', 'rjs.location_id', '=', 'bl.id')
            ->leftJoin('workshops', 'rjs.workshop_id', '=', 'workshops.id')
            ->leftJoin('transactions', 'rjs.id', '=', 'transactions.repair_job_sheet_id')
            ->where('rjs.location_id', auth()->user()->location_id )
            ->where('transactions.sub_type', 'repair')
            ->where('transactions.status', 'under processing')
            ->select([
                'rjs.id',
                'rjs.job_sheet_no',
                'rjs.workshop_id',
                'rjs.location_id',
                'rjs.service_staff',
                'rjs.created_at',
                'rjs.updated_at',
                'rjs.status_id',
                'rs.name as status_name',
                'rs.color as status_color',
                'workshops.name as workshop_name',
                // Device information from categories table (main device type)
                'cat.id as device_id',
                'cat.name as device_name',
                // Device model information
                'rdm.id as device_model_id', 
                'rdm.name as device_model',
                // Device details from contact_device
                'contact_device.plate_number',
                'contact_device.chassis_number',
                'contact_device.color as device_color',
                'contact_device.manufacturing_year',
                'contact_device.car_type'
            ]);

        // Apply filters to the query
        $this->applyFiltersToQuery($query, $filters);

        // Get total count for pagination (before applying limit/offset)
        $totalCount = $query->count();
        
        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $jobSheets = $query->orderBy('rjs.updated_at', 'desc')
                          ->limit($perPage)
                          ->offset($offset)
                          ->get();

        // Load timer data and user information efficiently
        $enrichedJobSheets = $this->enrichJobSheetsWithTimerData($business_id, $jobSheets);

        return [
            'job_sheets' => $enrichedJobSheets,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalCount,
                'total_pages' => ceil($totalCount / $perPage),
                'has_more_pages' => ($page * $perPage) < $totalCount,
                'next_page' => (($page * $perPage) < $totalCount) ? $page + 1 : null,
                'prev_page' => $page > 1 ? $page - 1 : null
            ],
            'statistics' => $this->calculateStatistics($business_id, $filters),
        ];
    }

    /**
     * Apply filters to the job sheets query
     * 
     * This method handles all filtering logic in a centralized, maintainable way.
     * Each filter is applied conditionally to avoid unnecessary query complexity.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $filters
     */
    private function applyFiltersToQuery($query, array $filters): void
    {
        if (!empty($filters['workshop_id'])) {
            $query->where('rjs.workshop_id', $filters['workshop_id']);
        }

        if (!empty($filters['location_id'])) {
            $query->where('rjs.location_id', $filters['location_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('rjs.created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('rjs.created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['status'])) {
            $query->where('rs.name', 'like', '%' . $filters['status'] . '%');
        }

        if (!empty($filters['technician_id'])) {
            // Filter jobs where the technician is assigned
            $query->whereRaw('JSON_CONTAINS(rjs.service_staff, ?)', ['"' . $filters['technician_id'] . '"']);
        }
    }

    /**
     * Enrich job sheets with timer data and technician information
     * 
     * This method implements the core data enrichment strategy:
     * 1. Batch load all user names to avoid N+1 queries
     * 2. Load timer data grouped by job and user for efficient processing
     * 3. Calculate elapsed times and timer states in memory
     * 4. Structure data according to the unified response format
     *
     * @param int $business_id
     * @param \Illuminate\Support\Collection $jobSheets
     * @return array Enriched job sheets with timer and technician data
     */
    private function enrichJobSheetsWithTimerData(int $business_id, $jobSheets): array
    {
        if ($jobSheets->isEmpty()) {
            return [];
        }

        $now = Carbon::now();
        $jobIds = $jobSheets->pluck('id')->toArray();

        // 1) Load services linked to job sheets via product_joborder
        $jobProducts = DB::table('product_joborder')
            ->whereIn('job_order_id', $jobIds)
            ->select('job_order_id', 'product_id')
            ->get();

        $productIds = $jobProducts->pluck('product_id')->unique()->values();

        $products = $productIds->isEmpty() ? collect() : DB::table('products')
            ->whereIn('id', $productIds)
            ->select('id', 'name', 'enable_stock', 'serviceHours')
            ->get()
            ->keyBy('id'); 
        // Load pivot mappings of product -> workshops
        $productWorkshops = $productIds->isEmpty() ? collect() : DB::table('product_workshop')
            ->whereIn('product_id', $productIds)
            ->select('product_id', 'workshop_id')
            ->get()
            ->groupBy('product_id');

        // Build services grouped by job, expanding multiple workshops per service via pivot
        $servicesByJob = $jobProducts
            ->groupBy('job_order_id')
            ->map(function ($rows) use ($products, $productWorkshops) {
                return collect($rows)->flatMap(function ($row) use ($products, $productWorkshops) {
                    $p = $products->get($row->product_id);
                    if (!$p) { return []; }
                    $isService = (int)($p->enable_stock ?? 1) === 0;
                    if (!$isService) { return []; }

                    // Get assigned workshops strictly from pivot mapping
                    $assignedWorkshopIds = $productWorkshops->get($p->id, collect())->pluck('workshop_id')->unique()->values()->all();
                    if (empty($assignedWorkshopIds)) { return []; }

                    return collect($assignedWorkshopIds)->map(function ($wid) use ($p) {
                        return (object) [
                            'service_id' => $p->id,
                            'service_name' => $p->name,
                            'service_hours' => $p->serviceHours,
                            'workshop_id' => (int) $wid,
                        ];
                    })->all();
                })->values();
            });

        $candidateWorkshopIds = $servicesByJob->flatten(1)->pluck('workshop_id')->unique()->values();

        // 2) Load job-sheet specific workshop-user assignments
        $assignmentsRaw = $candidateWorkshopIds->isEmpty() ? collect() : WorkshopTechnicianAssignmentHistory::query()
            ->active()
            ->ofType('job_sheet')
            ->whereIn('job_sheet_id', $jobIds)
            ->whereIn('workshop_id', $candidateWorkshopIds)
            ->select('job_sheet_id', 'workshop_id', 'user_id')
            ->get();

        $assignments = $assignmentsRaw->groupBy(function ($a) {
            return $a->job_sheet_id . '|' . $a->workshop_id;
        });

        $assignedWorkshopsByJob = $assignmentsRaw
            ->groupBy('job_sheet_id')
            ->map(function ($rows) {
                return $rows->pluck('workshop_id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->unique()
                    ->values();
            });

        $servicesByJob = $servicesByJob->map(function ($services, $jobId) use ($assignedWorkshopsByJob) {
            $assigned = $assignedWorkshopsByJob->get($jobId, collect());
            if (!$assigned instanceof \Illuminate\Support\Collection) {
                $assigned = collect($assigned);
            }

            if ($assigned->isEmpty()) {
                return collect();
            }

            return collect($services)->filter(function ($svc) use ($assigned) {
                return $assigned->contains((int) $svc->workshop_id);
            })->values();
        });

        $workshopIds = $servicesByJob->flatten(1)->pluck('workshop_id')->unique()->values();
        $workshops = $workshopIds->isEmpty() ? collect() : DB::table('workshops')
            ->whereIn('id', $workshopIds)
            ->select('id', 'name')
            ->get()
            ->keyBy('id');

        // 3) Collect all user IDs (service_staff + assignment users) to batch load names
        $allUserIds = collect();
        foreach ($jobSheets as $job) {
            $serviceStaff = json_decode($job->service_staff ?? '[]', true) ?: [];
            $allUserIds = $allUserIds->merge($serviceStaff);
        }
        $assignmentUserIds = $assignments->flatten(1)->pluck('user_id');
        $allUserIds = $allUserIds->merge($assignmentUserIds)->unique()->filter()->values();

        $users = $allUserIds->isEmpty() ? collect() : DB::table('users')
            ->whereIn('id', $allUserIds)
            ->select('id', DB::raw("TRIM(CONCAT_WS(' ', COALESCE(surname, ''), COALESCE(first_name, ''), COALESCE(last_name, ''))) as full_name"))
            ->get()
            ->keyBy('id');

        // 4) Load all timers for these jobs, grouped by job-user
        $timerData = DB::table('timer_tracking')
            ->where('business_id', $business_id)
            ->whereIn('job_sheet_id', $jobIds)
            ->orderBy('started_at', 'desc')
            ->get()
            ->groupBy(function ($timer) {
                return $timer->job_sheet_id . '-' . $timer->user_id;
            });

        // 5) Process each job sheet
        $enrichedJobs = [];
        foreach ($jobSheets as $job) {
            // Build service-based timer grouping FIRST and create missing timers as paused
            $jobServices = $servicesByJob->get($job->id, collect());
            $serviceGroups = [];

            foreach ($jobServices as $svc) {
                $assignmentKey = $job->id . '|' . $svc->workshop_id;
                $assignedUsers = $assignments->get($assignmentKey, collect())->pluck('user_id')->unique()->values();

                // Build timers for users assigned to this workshop for the job
                $timersForService = [];
                $technicianNames = [];

                if ($assignedUsers->isEmpty()) {
                    continue;
                }

                foreach ($assignedUsers as $uid) {
                    $uid = (int) $uid;
                    $userName = $users->get($uid)->full_name ?? ('User #' . $uid);
                    $technicianNames[] = $userName;

                    $timerKey = $job->id . '-' . $uid;
                    $userTimers = $timerData->get($timerKey, collect());

                    // If no timer exists for this job-user, create a paused timer without playing
                    if ($userTimers->isEmpty()) {
                        $newId = DB::table('timer_tracking')->insertGetId([
                            'business_id' => $business_id,
                            'job_sheet_id' => $job->id,
                            'user_id' => $uid,
                            'status' => 'paused',
                            'started_at' => $now,
                            'paused_at' => $now,
                            'resumed_at' => null,
                            'completed_at' => null,
                            'total_paused_duration' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        $newTimer = (object) [
                            'id' => $newId,
                            'business_id' => $business_id,
                            'job_sheet_id' => $job->id,
                            'user_id' => $uid,
                            'status' => 'paused',
                            'started_at' => $now->toDateTimeString(),
                            'paused_at' => $now->toDateTimeString(),
                            'resumed_at' => null,
                            'completed_at' => null,
                            'total_paused_duration' => 0,
                        ];

                        // Update in-memory timer data so workers reflect the new timer ID
                        $timerData->put($timerKey, collect([$newTimer]));
                        $userTimers = $timerData->get($timerKey, collect());
                    }

                    $timersForService[] = $this->processWorkerTimerData($uid, $userName, $userTimers, $now);
                }

                $serviceGroups[] = [
                    'service_id' => $svc->service_id,
                    'service_name' => $svc->service_name,
                    'service_hours' => $svc->service_hours,
                    'workshop_id' => $svc->workshop_id,
                    'workshop_name' => ($workshops->get($svc->workshop_id)->name ?? null),
                    'technicians' => $technicianNames,
                    'timers' => $timersForService,
                ];
            }

            // Now keep existing structure for backward compatibility with updated timer data
            $enrichedJob = $this->processJobSheetWithTimers($job, $users, $timerData, $now);
            $enrichedJob['service_groups'] = $serviceGroups;
            $enrichedJobs[] = $enrichedJob;
        }

        return $enrichedJobs;
    }

    /**
     * Process individual job sheet with timer data
     * 
     * This method handles the complex logic of:
     * 1. Building technician assignments and names
     * 2. Processing timer states and elapsed time calculations
     * 3. Structuring worker data with current timer status
     * 4. Creating timer summary statistics for the job
     *
     * @param object $job Raw job sheet data from database
     * @param \Illuminate\Support\Collection $users User data indexed by ID
     * @param \Illuminate\Support\Collection $timerData Timer data grouped by job-user key
     * @param Carbon $now Current timestamp for elapsed time calculations
     * @return array Fully processed job sheet with timer data
     */
    private function processJobSheetWithTimers($job, $users, $timerData, Carbon $now): array
    {
        $serviceStaff = json_decode($job->service_staff ?? '[]', true) ?: [];
        $technicians = [];
        $workers = [];
        
        // Initialize timer summary counters
        $totalElapsedSeconds = 0;
        $activeWorkers = 0;
        $pausedWorkers = 0;
        $completedWorkers = 0;

        // Process each assigned technician
        foreach ($serviceStaff as $userId) {
            $userId = (int) $userId;
            $userName = $users->get($userId)->full_name ?? "User #{$userId}";
            $technicians[] = $userName;

            // Get timer data for this user on this job
            $timerKey = $job->id . '-' . $userId;
            $userTimers = $timerData->get($timerKey, collect());
            
            // Process timer state and calculate elapsed time
            $workerData = $this->processWorkerTimerData($userId, $userName, $userTimers, $now);
            $workers[] = $workerData;

            // Update summary counters
            $totalElapsedSeconds += $workerData['elapsed_seconds'];
            
            switch ($workerData['timer_status']) {
                case 'active':
                    $activeWorkers++;
                    break;
                case 'paused':
                    $pausedWorkers++;
                    break;
                case 'completed':
                    $completedWorkers++;
                    break;
            }
        }

        // Build device information object
        $device = null;
        if ($job->device_id) {
            $device = [
                'id' => $job->device_id,
                'name' => $job->device_name,
                'model' => $job->device_model,
                'plate_number' => $job->plate_number,
                'chassis_number' => $job->chassis_number,
                'color' => $job->device_color,
                'manufacturing_year' => $job->manufacturing_year,
                'car_type' => $job->car_type
            ];
            
            // Remove null values to keep response clean
            $device = array_filter($device, function($value) {
                return $value !== null && $value !== '';
            });
        }

        // Build the complete job sheet response
        return [
            'id' => $job->id,
            'job_sheet_no' => $job->job_sheet_no,
            'workshop_id' => $job->workshop_id,
            'workshop_name' => $job->workshop_name ?? 'Workshop',
            'status_name' => $job->status_name,
            'status_color' => $job->status_color ?? '#6c757d',
            'service_staff' => $job->service_staff,
            'technicians' => $technicians,
            'elapsed_seconds' => $totalElapsedSeconds,
            'device' => $device,
            'workers' => $workers
        ];
    }

    /**
     * Process timer data for individual worker
     * 
     * This method handles the complex timer state logic:
     * 1. Processes multiple timer records for a single worker on a job
     * 2. Determines current timer status (active, paused, completed, or none)
     * 3. Calculates accurate elapsed time considering paused durations
     * 4. Identifies the most relevant timer ID for actions
     *
     * @param int $userId
     * @param string $userName
     * @param \Illuminate\Support\Collection $userTimers
     * @param Carbon $now
     * @return array Worker data with timer information
     */
    private function processWorkerTimerData(int $userId, string $userName, $userTimers, Carbon $now): array
    {
        $totalElapsedSeconds = 0;
        $currentTimerStatus = null;
        $currentTimerId = null;
        $startedAt = null;
        $lastActionAt = null;
        $pausedAt = null;
        $TimeAllocate = null;

        // Process all timer records for this worker (there might be multiple)
        foreach ($userTimers as $timer) {
            if (!$timer->started_at) continue;

            $timerStarted = Carbon::parse($timer->started_at);
            $pausedDuration = (int) ($timer->total_paused_duration ?? 0);

            // Calculate elapsed time based on timer status
            if ($timer->status === 'completed' && $timer->completed_at) {
                $timerEnded = Carbon::parse($timer->completed_at);
                $elapsed = $timerEnded->diffInSeconds($timerStarted) - $pausedDuration;
                $currentTimerStatus = 'completed';
                $currentTimerId = $timer->id;
                $lastActionAt = $timer->completed_at;
                $TimeAllocate = isset($timer->time_allocate) ? $timer->time_allocate : null;
            } elseif ($timer->status === 'paused' && $timer->paused_at) {
                $timerPaused = Carbon::parse($timer->paused_at);
                $elapsed = $timerPaused->diffInSeconds($timerStarted) - $pausedDuration;
                $currentTimerStatus = 'paused';
                $currentTimerId = $timer->id;
                $pausedAt = $timer->paused_at;
                $lastActionAt = $timer->paused_at;
                $TimeAllocate = isset($timer->time_allocate) ? $timer->time_allocate : null;
            } else { // active timer
                $elapsed = $now->diffInSeconds($timerStarted) - $pausedDuration;
                $currentTimerStatus = 'active';
                $currentTimerId = $timer->id;
                $lastActionAt = $timer->started_at;
                $TimeAllocate = isset($timer->time_allocate) ? $timer->time_allocate : null;
            }

            $totalElapsedSeconds += max(0, (int) $elapsed);
            
            // Keep track of the earliest start time
            if (!$startedAt || $timerStarted->lt(Carbon::parse($startedAt))) {
                $startedAt = $timer->started_at;
            }
        }

        // If no active or paused timer found, check if there were any completed timers
        if (!$currentTimerStatus && !$userTimers->isEmpty()) {
            $currentTimerStatus = 'completed';
            // Get the most recent completed timer ID
            $latestTimer = $userTimers->sortByDesc('completed_at')->first();
            if ($latestTimer) {
                $currentTimerId = $latestTimer->id;
                $TimeAllocate = isset($latestTimer->time_allocate) ? $latestTimer->time_allocate : null;
            }
        }

        return [
            'user_id' => $userId,
            'user_name' => $userName,
            'timer_id' => $currentTimerId,
            'timer_status' => $currentTimerStatus,
            'elapsed_seconds' => max(0, $totalElapsedSeconds),
            'started_at' => $startedAt,
            'paused_at' => $pausedAt,
            'last_action_at' => $lastActionAt,
            'time_allocate' => $TimeAllocate
        ];
    }

    /**
     * Calculate comprehensive statistics for the dashboard
     * 
     * This method provides real-time statistics that match the web interface:
     * 1. Active job sheets and timer counts
     * 2. Total elapsed time across all active work
     * 3. Unique technician counts and workshop activity
     * 4. Performance metrics and averages
     *
     * @param int $business_id
     * @param array $filters
     * @return array Statistics summary
     */
    private function calculateStatistics(int $business_id, array $filters): array
    {
        // Get all active jobs (not just current page) for accurate statistics
        $allActiveJobs = $this->timeMetricsService->getLiveTimers($business_id, $filters);
        
        $activeJobSheets = count($allActiveJobs ?? []);
        $totalElapsedSeconds = 0;
        $uniqueTechnicians = collect();
        $activeTimerCount = 0;
        $workshopsWithActivity = collect();

        foreach (($allActiveJobs ?? []) as $job) {
            $totalElapsedSeconds += ($job->elapsed_seconds ?? 0);
            $workshopsWithActivity->push($job->workshop_id);
            
            // Count active timers and collect unique technicians
            $serviceStaff = json_decode($job->service_staff ?? '[]', true) ?: [];
            foreach ($serviceStaff as $techId) {
                $uniqueTechnicians->push($techId);
            }
            
            // Count active timers from workers
            if (!empty($job->workers)) {
                foreach ($job->workers as $worker) {
                    if (($worker->timer_status ?? null) === 'active') {
                        $activeTimerCount++;
                    }
                }
            }
        }

        $uniqueTechniciansCount = $uniqueTechnicians->unique()->count();
        $workshopsWithActivityCount = $workshopsWithActivity->unique()->count();
        $averageJobDuration = $activeJobSheets > 0 ? round($totalElapsedSeconds / $activeJobSheets) : 0;

        return [
            'active_job_sheets' => $activeJobSheets,
            'total_active_timers' => $activeTimerCount,
            'total_elapsed_seconds' => $totalElapsedSeconds,
            'total_elapsed_formatted' => $this->formatElapsedTime($totalElapsedSeconds),
            'unique_technicians_active' => $uniqueTechniciansCount,
            'workshops_with_activity' => $workshopsWithActivityCount,
            'average_job_duration' => $averageJobDuration
        ];
    }

    /**
     * Build the final unified response structure
     * 
     * This method assembles all processed data into the final response format
     * that matches the web interface expectations and provides comprehensive
     * information for client applications.
     *
     * @param array $paginationData
     * @param array $filters
     * @return array Complete unified response
     */
    private function buildUnifiedResponse(array $paginationData, array $filters): array
    {
        return [
            'job_sheets' => $paginationData['job_sheets'],
            'statistics' => $paginationData['statistics'],
            'pagination' => $paginationData['pagination'],
            'filters_applied' => array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            })
        ];
    }

    private function prepareTimersForView(array $jobSheets): array
    {
        return array_map(function ($job) {
            return json_decode(json_encode($job));
        }, $jobSheets);
    }

    private function buildTimerStats(array $jobSheets): array
    {
        $totalSeconds = 0;
        $technicians = [];

        foreach ($jobSheets as $job) {
            $totalSeconds += (int) ($job['elapsed_seconds'] ?? 0);

            if (!empty($job['workers']) && is_array($job['workers'])) {
                foreach ($job['workers'] as $worker) {
                    if (isset($worker['user_id'])) {
                        $technicians[] = (int) $worker['user_id'];
                    }
                }
            } elseif (!empty($job['service_staff'])) {
                $serviceStaff = $job['service_staff'];
                if (is_string($serviceStaff)) {
                    $decoded = json_decode($serviceStaff, true);
                    $serviceStaff = is_array($decoded) ? $decoded : [];
                }

                if (is_array($serviceStaff)) {
                    foreach ($serviceStaff as $techId) {
                        $technicians[] = (int) $techId;
                    }
                }
            }
        }

        return [
            'active_count' => count($jobSheets),
            'total_seconds' => $totalSeconds,
            'unique_techs' => count(array_unique($technicians)),
        ];
    }

    /**
     * Format elapsed time in human-readable format
     * 
     * @param int $seconds
     * @return string Formatted time (e.g., "2h 30m")
     */
    private function formatElapsedTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf('%dh %02dm', $hours, $minutes);
    }

    /**
     * Check if user has an active attendance today (clocked in and not clocked out)
     */
    private function userHasActiveAttendanceToday(int $userId, int $businessId): bool
    {
        $today = Carbon::today();
        return DB::table('essentials_attendances')
            ->where('user_id', $userId)
            ->where('business_id', $businessId)
            ->whereDate('clock_in_time', $today)
            ->whereNull('clock_out_time')
            ->exists();
    }

    /**
     * Start Timer (Individual)
     *
     * @bodyParam job_sheet_id int required Job sheet ID Example: 5
     * @bodyParam user_id int required User ID Example: 1
     * @response {
     *   "data": {
     *     "success": true,
     *     "timer_id": 1,
     *     "message": "Timer started successfully"
     *   }
     * }
     */
    public function startTimer(Request $request)
    {
        try {
      

            $validator = Validator::make($request->all(), [
                'job_sheet_id' => 'required|integer|exists:repair_job_sheets,id',
                'user_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $business_id = Auth::user()->business_id;
            $job_sheet_id = $request->input('job_sheet_id');
            $user_id = (int) $request->input('user_id');

            if (!$job_sheet_id || !$user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'job_sheet_id and user_id are required'
                ], 422);
            }

            // Verify job sheet belongs to user's business
            $jobSheet = DB::table('repair_job_sheets')
                ->where('id', $job_sheet_id)
                ->where('business_id', $business_id)
                ->first();

            if (!$jobSheet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job sheet not found'
                ], 404);
            }

            // Check if user is assigned to this job sheet
            $serviceStaff = json_decode($jobSheet->service_staff ?? '[]', true) ?: [];
            if (!in_array($user_id, $serviceStaff)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not assigned to this job sheet'
                ], 403);
            }

            // Guard: require active attendance today
            if (!$this->userHasActiveAttendanceToday($user_id, $business_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Technician must clock in today before starting timer'
                ], 403);
            }

            $now = Carbon::now();
            $responseData = null;

            DB::transaction(function () use (&$responseData, $business_id, $job_sheet_id, $user_id, $now) {
                $this->pauseOtherActiveTimers($business_id, $user_id, $job_sheet_id, $now);

                $latestTimer = DB::table('timer_tracking')
                    ->where('job_sheet_id', $job_sheet_id)
                    ->where('user_id', $user_id)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($latestTimer && $latestTimer->status === 'active') {
                    $responseData = [
                        'success' => true,
                        'timer_id' => $latestTimer->id,
                        'message' => 'Timer started successfully'
                    ];
                    return;
                }

                if ($latestTimer && $latestTimer->status === 'paused') {
                    $pausedSeconds = $latestTimer->paused_at ? Carbon::parse($latestTimer->paused_at)->diffInSeconds($now) : 0;

                    DB::table('timer_tracking')
                        ->where('id', $latestTimer->id)
                        ->update([
                            'status' => 'active',
                            'resumed_at' => $now,
                            'total_paused_duration' => DB::raw('total_paused_duration + ' . (int) $pausedSeconds),
                            'updated_at' => $now,
                        ]);

                    $responseData = [
                        'success' => true,
                        'timer_id' => $latestTimer->id,
                        'message' => 'Timer started successfully'
                    ];
                    return;
                }

                $id = DB::table('timer_tracking')->insertGetId([
                    'business_id' => $business_id,
                    'job_sheet_id' => $job_sheet_id,
                    'user_id' => $user_id,
                    'status' => 'active',
                    'started_at' => $now,
                    'total_paused_duration' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $responseData = [
                    'success' => true,
                    'timer_id' => $id,
                    'message' => 'Timer started successfully'
                ];
            });

            return response()->json($responseData);

        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to start timer'
            ], 500);
        }
    }

    /**
     * Pause Timer
     *
     * @urlParam timer_id required Timer ID Example: 1
     * @response {
     *   "data": {
     *     "success": true,
     *     "message": "Timer paused successfully"
     *   }
     * }
     */
    public function pauseTimer(Request $request, $timer_id = null)
    {
        try {
            $timer_id = $timer_id ?? $request->input('timer_id');

            if (empty($timer_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Timer ID is required'
                ], 422);
            }

            $business_id = Auth::user()->business_id;

            DB::table('timer_tracking')
                ->where('id', $timer_id)
                ->where('business_id', $business_id)
                ->update([
                    'status' => 'paused',
                    'paused_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Timer paused successfully'
            ]);

        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to pause timer'
            ], 500);
        }
    }

    /**
     * Resume Timer
     *
     * @urlParam timer_id required Timer ID Example: 1
     * @response {
     *   "data": {
     *     "success": true,
     *     "message": "Timer resumed successfully"
     *   }
     * }
     */
    public function resumeTimer(Request $request, $timer_id = null)
    {
        try {
            if (!$this->moduleUtil->hasThePermissionInSubscription(Auth::user()->business_id, 'essentials_module')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $timer_id = $timer_id ?? $request->input('timer_id');

            if (empty($timer_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Timer ID is required'
                ], 422);
            }

            $business_id = Auth::user()->business_id;
            $user_id = Auth::user()->id;

            $timer = DB::table('timer_tracking')
                ->where('id', $timer_id)
                ->first();

            if (!$timer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Timer not found'
                ], 404);
            }

            // Guard: require active attendance today for the timer's user
            if (!$this->userHasActiveAttendanceToday((int) $timer->user_id, $business_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Technician must clock in today before resuming timer'
                ], 403);
            }

            // Pause any other active timers for this user
            DB::table('timer_tracking')
                ->where('user_id', $timer->user_id)
                ->where('id', '!=', $timer_id)
                ->where('status', 'active')
                ->update([
                    'status' => 'paused',
                    'paused_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);


            if ($timer && $timer->status === 'paused' && $timer->paused_at) {
                $now = Carbon::now();
                $pausedSeconds = Carbon::parse($timer->paused_at)->diffInSeconds($now);

                DB::transaction(function () use ($business_id, $user_id, $timer, $timer_id, $pausedSeconds, $now) {
                    // Pause other active timers for this user within the same business
                    $this->pauseOtherActiveTimers($business_id, (int) $user_id, (int) $timer->job_sheet_id, $now);

                    // Resume the current timer
                    DB::table('timer_tracking')
                        ->where('id', $timer_id)
                        ->update([
                            'status' => 'active',
                            'resumed_at' => $now,
                            'total_paused_duration' => DB::raw('total_paused_duration + ' . (int) $pausedSeconds),
                            'updated_at' => $now,
                        ]);
                });
            }

            return response()->json([
                'success' => true,
                'message' => 'Timer resumed successfully'
            ]);

        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to resume timer'
            ], 500);
        }
    }

    /**
     * Complete Timer
     *
     * @urlParam timer_id required Timer ID Example: 1
     * @response {
     *   "data": {
     *     "success": true,
     *     "message": "Timer completed successfully"
     *   }
     * }
     */
    public function completeTimer(Request $request, $timer_id = null)
    {
        try {
            if (!$this->moduleUtil->hasThePermissionInSubscription(Auth::user()->business_id, 'essentials_module')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $timer_id = $timer_id ?? $request->input('timer_id');

            if (empty($timer_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Timer ID is required'
                ], 422);
            }

            $business_id = Auth::user()->business_id;

            DB::table('timer_tracking')
                ->where('id', $timer_id)
                ->where('business_id', $business_id)
                ->update([
                    'status' => 'completed',
                    'completed_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Timer completed successfully'
            ]);

        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to complete timer'
            ], 500);
        }
    }

    /**
     * Play All Timers (Bulk Operation)
     *
     * Start or resume timers for all assigned technicians on a job sheet.
     *
     * @bodyParam job_sheet_id int required Job sheet ID Example: 5
     * @response {
     *   "data": {
     *     "success": true,
     *     "message": "All timers started successfully"
     *   }
     * }
     */
    public function playAll(Request $request)
    {
        try {
            if (!$this->moduleUtil->hasThePermissionInSubscription(Auth::user()->business_id, 'essentials_module')) {
                return $this->respondUnauthorized();
            }

            $validator = Validator::make($request->all(), [
                'job_sheet_id' => 'required|integer|exists:repair_job_sheets,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $business_id = Auth::user()->business_id;
            $job_sheet_id = (int) $request->input('job_sheet_id');

            $job = DB::table('repair_job_sheets')
                ->where('business_id', $business_id)
                ->where('id', $job_sheet_id)
                ->select('service_staff')
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job sheet not found'
                ], 404);
            }

            $staffIds = collect(json_decode($job->service_staff, true) ?: [])
                ->filter()
                ->unique()
                ->values();

            $skippedUserIds = [];

            DB::transaction(function () use ($business_id, $job_sheet_id, $staffIds, &$skippedUserIds) {
                $now = Carbon::now();

                foreach ($staffIds as $user_id) {
                    // Skip users who have not clocked in today
                    if (!$this->userHasActiveAttendanceToday((int) $user_id, $business_id)) {
                        $skippedUserIds[] = (int) $user_id;
                        continue;
                    }

                    $this->pauseOtherActiveTimers($business_id, (int) $user_id, $job_sheet_id, $now);

                    $latestTimer = DB::table('timer_tracking')
                        ->where('business_id', $business_id)
                        ->where('job_sheet_id', $job_sheet_id)
                        ->where('user_id', $user_id)
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->first();

                    if ($latestTimer && $latestTimer->status === 'active') {
                        continue;
                    }

                    if ($latestTimer && $latestTimer->status === 'paused') {
                        $pausedSeconds = $latestTimer->paused_at ? Carbon::parse($latestTimer->paused_at)->diffInSeconds($now) : 0;
                        DB::table('timer_tracking')
                            ->where('id', $latestTimer->id)
                            ->update([
                                'status' => 'active',
                                'resumed_at' => $now,
                                'total_paused_duration' => DB::raw('total_paused_duration + ' . (int) $pausedSeconds),
                                'updated_at' => $now,
                            ]);
                        continue;
                    }

                    DB::table('timer_tracking')->insert([
                        'business_id' => $business_id,
                        'job_sheet_id' => $job_sheet_id,
                        'user_id' => $user_id,
                        'status' => 'active',
                        'started_at' => $now,
                        'total_paused_duration' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'All timers started successfully',
                'skipped_user_ids' => $skippedUserIds
            ]);

        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to start all timers'
            ], 500);
        }
    }

    /**
     * Pause All Timers (Bulk Operation)
     *
     * Pause all active timers on a job sheet.
     *
     * @bodyParam job_sheet_id int required Job sheet ID Example: 5
     * @response {
     *   "data": {
     *     "success": true,
     *     "message": "All timers paused successfully"
     *   }
     * }
     */
    public function pauseAll(Request $request)
    {
        try {
            if (!$this->moduleUtil->hasThePermissionInSubscription(Auth::user()->business_id, 'essentials_module')) {
                return $this->respondUnauthorized();
            }

            $validator = Validator::make($request->all(), [
                'job_sheet_id' => 'required|integer|exists:repair_job_sheets,id'
            ]);

            if ($validator->fails()) {
                return $this->setStatusCode(422)->respondWithError($validator->errors()->first());
            }

            $business_id = Auth::user()->business_id;
            $job_sheet_id = (int) $request->input('job_sheet_id');

            $activeTimers = DB::table('timer_tracking')
                ->where('business_id', $business_id)
                ->where('job_sheet_id', $job_sheet_id)
                ->where('status', 'active')
                ->pluck('id');

            if ($activeTimers->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No active timers to pause'
                ]);
            }

            $now = Carbon::now();

            DB::table('timer_tracking')
                ->whereIn('id', $activeTimers)
                ->update([
                    'status' => 'paused',
                    'paused_at' => $now,
                    'updated_at' => $now,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'All timers paused successfully'
            ]);

        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to pause all timers'
            ], 500);
        }
    }

    /**
     * Complete All Timers (Bulk Operation)
     *
     * Complete all active/paused timers on a job sheet.
     *
     * @bodyParam job_sheet_id int required Job sheet ID Example: 5
     * @response {
     *   "data": {
     *     "success": true,
     *     "message": "All timers completed successfully"
     *   }
     * }
     */
    public function completeAll(Request $request)
    {
        try {
            if (!$this->moduleUtil->hasThePermissionInSubscription(Auth::user()->business_id, 'essentials_module')) {
                return $this->respondUnauthorized();
            }

            $validator = Validator::make($request->all(), [
                'job_sheet_id' => 'required|integer|exists:repair_job_sheets,id'
            ]);

            if ($validator->fails()) {
                return $this->setStatusCode(422)->respondWithError($validator->errors()->first());
            }

            $business_id = Auth::user()->business_id;
            $job_sheet_id = (int) $request->input('job_sheet_id');

            $timers = DB::table('timer_tracking')
                ->where('business_id', $business_id)
                ->where('job_sheet_id', $job_sheet_id)
                ->whereIn('status', ['active', 'paused'])
                ->get();

            if ($timers->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No active timers to complete'
                ]);
            }

            $now = Carbon::now();

            foreach ($timers as $timer) {
                $updates = [
                    'status' => 'completed',
                    'completed_at' => $now,
                    'updated_at' => $now,
                ];

                if ($timer->status === 'paused' && $timer->paused_at) {
                    $updates['total_paused_duration'] = ($timer->total_paused_duration ?? 0) + Carbon::parse($timer->paused_at)->diffInSeconds($now);
                }

                DB::table('timer_tracking')
                    ->where('id', $timer->id)
                    ->update($updates);
            }

            return response()->json([
                'success' => true,
                'message' => 'All timers completed successfully'
            ]);

        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to complete all timers'
            ], 500);
        }
    }


    public function updateTimeAllocation(Request $request, $timer_id = null)
    {
        try {
            $business_id = Auth::user()->business_id;

            // Inputs
            $ids = $request->input('timer_ids', $request->input('ids'));
            $updatesInput = $request->input('updates', $request->input('timers'));
            // Prefer new name; fallback to legacy 'time_allocate' only (remove service_percent)
            $singleValue = $request->input('time_allocated', $request->input('time_allocate'));
            $timer_id = $timer_id ?? $request->input('timer_id', $request->input('id'));

            // 1) Bulk Update: accept array of {timer_id|id, time_allocated}
            if (is_array($updatesInput) && !empty($updatesInput)) {
                $validator = Validator::make(['updates' => $updatesInput], [
                    'updates' => ['array', 'min:1'],
                    'updates.*.timer_id' => ['required_without:updates.*.id', 'integer', 'min:1'],
                    'updates.*.id' => ['nullable', 'integer', 'min:1'],
                    'updates.*.time_allocated' => ['nullable', 'numeric', 'min:0']
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid bulk update payload'
                    ], 422);
                }

                // Normalize and deduplicate (last value wins)
                $pairs = [];
                foreach ($updatesInput as $u) {
                    $tid = isset($u['timer_id']) ? $u['timer_id'] : (isset($u['id']) ? $u['id'] : null);
                    if ($tid === null) { continue; }
                    $pairs[(int) $tid] = isset($u['time_allocated']) ? $u['time_allocated'] : null;
                }
                if (empty($pairs)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No valid timer updates provided'
                    ], 422);
                }

                $idsToUpdate = array_keys($pairs);

                // Filter to timers belonging to this business
                $validIds = DB::table('timer_tracking')
                    ->where('business_id', $business_id)
                    ->whereIn('id', $idsToUpdate)
                    ->pluck('id')
                    ->all();

                if (empty($validIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No matching timers found for update'
                    ], 404);
                }

                // Build CASE SQL to update in a single statement
                $caseSql = 'CASE id ';
                $bindings = [];
                foreach ($validIds as $id) {
                    $caseSql .= 'WHEN ? THEN ? ';
                    $bindings[] = $id;
                    $bindings[] = $pairs[$id];
                }
                $caseSql .= 'END';

                $now = Carbon::now();
                $inPlaceholders = implode(',', array_fill(0, count($validIds), '?'));
                $sql = "UPDATE timer_tracking SET time_allocate = $caseSql, updated_at = ? WHERE business_id = ? AND id IN ($inPlaceholders)";
                $bindings[] = $now;
                $bindings[] = $business_id;
                $bindings = array_merge($bindings, $validIds);

                DB::beginTransaction();
                try {
                    DB::statement($sql, $bindings);
                    DB::commit();
                } catch (\Exception $ex) {
                    DB::rollBack();
                    return $this->otherExceptions($ex);
                }

                $updated = [];
                foreach ($validIds as $id) {
                    $updated[] = [
                        'timer_id' => (int) $id,
                        'time_allocated' => $pairs[$id]
                    ];
                }

                return $this->respond([
                    'data' => [
                        'success' => true,
                        'updated' => $updated,
                        'count' => count($validIds)
                    ]
                ]);
            }

            // 2) Retrieval: given array of timer IDs, return their allocated time
            if (is_array($ids) && !empty($ids)) {
                $validator = Validator::make(['timer_ids' => $ids], [
                    'timer_ids' => ['array', 'min:1'],
                    'timer_ids.*' => ['integer', 'min:1']
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid timer IDs'
                    ], 422);
                }

                $rows = DB::table('timer_tracking')
                    ->where('business_id', $business_id)
                    ->whereIn('id', $ids)
                    ->select('id', 'time_allocate')
                    ->get();

                $timers = $rows->map(function ($row) {
                    return [
                        'timer_id' => (int) $row->id,
                        'time_allocated' => $row->time_allocate
                    ];
                });

                return $this->respond([
                    'data' => [
                        'success' => true,
                        'timers' => $timers
                    ]
                ]);
            }

            // 3) Single update (fallback)
            $validator = Validator::make(['time_allocated' => $singleValue], [
                'time_allocated' => ['nullable', 'numeric', 'min:0']
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid time allocation value'
                ], 422);
            }

            $timer = DB::table('timer_tracking')
                ->where('id', $timer_id)
                ->where('business_id', $business_id)
                ->first();

            if (!$timer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Timer not found'
                ], 404);
            }

            $now = Carbon::now();
            DB::table('timer_tracking')
                ->where('id', $timer_id)
                ->where('business_id', $business_id)
                ->update([
                    'time_allocate' => $singleValue,
                    'updated_at' => $now,
                ]);

            return $this->respond([
                'data' => [
                    'success' => true,
                    'timer_id' => (int) $timer_id,
                    'time_allocated' => $singleValue,
                ]
            ]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
        }
    }

    /**
     * Get Timer History
     *
     * @queryParam user_id int optional Filter by user ID Example: 1
     * @queryParam job_sheet_id int optional Filter by job sheet ID Example: 1
     * @queryParam start_date string optional Start date filter (Y-m-d format) Example: 2024-01-01
     * @queryParam end_date string optional End date filter (Y-m-d format) Example: 2024-01-31
     * @queryParam per_page int optional Items per page (default: 10) Example: 15
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "user_id": 1,
     *       "user_name": "John Doe",
     *       "job_sheet_id": 5,
     *       "job_sheet_number": "JS-001",
     *       "started_at": "2024-01-15 09:00:00",
     *       "completed_at": "2024-01-15 11:30:00",
     *       "total_duration": "02:30:00",
     *       "total_seconds": 9000,
     *       "total_paused_duration": 300,
     *       "customer_name": "Jane Smith"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "total": 25,
     *     "per_page": 10
     *   }
     * }
     */
    public function history(Request $request)
    {
        try {
            if (!$this->moduleUtil->hasThePermissionInSubscription(Auth::user()->business_id, 'essentials_module')) {
                return $this->respondUnauthorized();
            }

            $business_id = Auth::user()->business_id;
            $perPage = $request->get('per_page', 10);

            $query = DB::table('timer_tracking as tt')
                ->join('repair_job_sheets as rjs', 'tt.job_sheet_id', '=', 'rjs.id')
                ->join('users as u', 'tt.user_id', '=', 'u.id')
                ->leftJoin('contacts as c', 'rjs.contact_id', '=', 'c.id')
                ->where('u.business_id', $business_id)
                ->whereNotNull('tt.completed_at') // Only completed timers
                ->when($request->user_id, fn($q) => $q->where('tt.user_id', $request->user_id))
                ->when($request->job_sheet_id, fn($q) => $q->where('tt.job_sheet_id', $request->job_sheet_id))
                ->when($request->start_date, fn($q) => $q->whereDate('tt.started_at', '>=', $request->start_date))
                ->when($request->end_date, fn($q) => $q->whereDate('tt.completed_at', '<=', $request->end_date))
                ->select(
                    'tt.id',
                    'tt.user_id',
                    'tt.job_sheet_id',
                    'rjs.job_sheet_no',
                    'tt.started_at',
                    'tt.completed_at',
                    'tt.total_paused_duration',
                    'c.name as customer_name',
                    DB::raw("CONCAT_WS(' ', u.first_name, u.last_name) as user_name"),
                    DB::raw('TIMESTAMPDIFF(SECOND, tt.started_at, tt.completed_at) - COALESCE(tt.total_paused_duration, 0) as total_seconds')
                )
                ->orderBy('tt.completed_at', 'desc');

            $timers = $query->paginate($perPage);

            $result = [];
            foreach ($timers->items() as $timer) {
                $result[] = [
                    'id' => $timer->id,
                    'user_id' => $timer->user_id,
                    'user_name' => $timer->user_name,
                    'job_sheet_id' => $timer->job_sheet_id,
                    'job_sheet_number' => $timer->job_sheet_no,
                    'started_at' => $timer->started_at,
                    'completed_at' => $timer->completed_at,
                    'total_duration' => $this->formatDuration($timer->total_seconds),
                    'total_seconds' => $timer->total_seconds,
                    'total_paused_duration' => $timer->total_paused_duration ?? 0,
                    'customer_name' => $timer->customer_name
                ];
            }

            return response()->json([
                'data' => $result,
                'meta' => [
                    'current_page' => $timers->currentPage(),
                    'total' => $timers->total(),
                    'per_page' => $timers->perPage(),
                    'last_page' => $timers->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch timer history'
            ], 500);
        }
    }

    /**
     * Format duration in seconds to HH:MM:SS format
     */
    private function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    private function pauseOtherActiveTimers(int $business_id, int $user_id, int $job_sheet_id, Carbon $timestamp): void
    {
        $timerIds = DB::table('timer_tracking')
            ->where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('status', 'active')
            ->where('job_sheet_id', '!=', $job_sheet_id)
            ->pluck('id');

        if ($timerIds->isEmpty()) {
            return;
        }

        DB::table('timer_tracking')
            ->whereIn('id', $timerIds)
            ->update([
                'status' => 'paused',
                'paused_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
    }
}
