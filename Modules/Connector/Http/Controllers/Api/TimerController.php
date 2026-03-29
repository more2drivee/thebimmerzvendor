<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Connector\Transformers\CommonResource;
use Modules\TimeManagement\Services\TimeMetricsService;
use Modules\TimeManagement\Services\TechnicianDataService;
use Modules\TimeManagement\Entities\WorkshopTechnicianAssignmentHistory;
use Carbon\Carbon;

/**
 * @group Timer management
 * @authenticated
 *
 * APIs for managing job timers and time tracking with unified data structure
 */
class TimerController extends ApiController
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
            
            return $this->respond(['data' => $response]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
        }
    }

    /**
     * Get list of pre-defined phrases (pre_phrases)
     *
     * @queryParam type string optional Filter by phrase type (e.g. stop_reason)
     * @queryParam only_active boolean optional Only active phrases (default: true)
     */
    public function getPrePhrases(Request $request)
    {
        try {
            $location_id = Auth::user()->location_id;

            $query = DB::table('timer_pre_phrases')
                ->where(function ($q) use ($location_id) {
                    $q->whereNull('location_id')
                      ->orWhere('location_id', $location_id);
                });

            if ($request->boolean('only_active', true)) {
                $query->where('is_active', true);
            }

            if ($type = $request->input('type')) {
                $query->where('type', $type);
            }

            if ($reasonType = $request->input('reason_type')) {
                $query->where('reason_type', $reasonType);
            }

            $phrases = $query
                ->orderByDesc('id')
                ->get();

            return $this->respond(['data' => $phrases]);
        } catch (\Exception $e) {
            return $this->otherExceptions($e);
        }
    }



    /**
     * Save a stop reason for a timer
     *
     * This marks the beginning of a pause interval linked to a timer.
     * Behavior depends on the phrase's reason_type:
     * - record_reason: Save reason with pause_start = now
     * - finishtimer: Complete the paused timer (mark as completed)
     * - ignore: Save reason without pause_start or pause_end
     *
     * @bodyParam timer_id integer required The timer_tracking ID
     * @bodyParam phrase_id integer optional The timer_pre_phrases ID (determines behavior)
     * @bodyParam body string optional Custom body/description for the stop reason
     */
    public function saveTimerStopReason(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'timer_id'  => 'required|integer|exists:timer_tracking,id',
                'phrase_id' => 'nullable|integer|exists:timer_pre_phrases,id',
                'body'      => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->respondWithError($validator->errors()->first());
            }

            $data = $validator->validated();
            $now = Carbon::now();

            // Determine reason_type from phrase if provided
            $reasonType = 'record_reason'; // default
            $phraseId = $data['phrase_id'] ?? null;
            $body = $data['body'] ?? null;

            if ($phraseId) {
                $phrase = DB::table('timer_pre_phrases')->where('id', $phraseId)->first();
                if ($phrase && $phrase->reason_type) {
                    $reasonType = $phrase->reason_type;
                }
                // Use phrase body if no custom body provided
                if (!$body && $phrase && $phrase->body) {
                    $body = $phrase->body;
                }
            }

            // Build base payload
            $insertData = [
                'timer_id'         => $data['timer_id'],
                'resumed_timer_id' => null, // will be filled with the technician's currently active timer (if any)
                'phrase_id'        => $phraseId,
                'location_id'      => Auth::user()->location_id ?? null,
                'reason_type'      => $reasonType,
                'body'             => $body,
                'is_active'        => true,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];

            // Detect the technician and their latest ACTIVE timer and treat it as the resumed timer
            $currentTimerForResumed = DB::table('timer_tracking')
                ->where('id', $data['timer_id'])
                ->first();

            $technicianId = Auth::user()->id ?? null;
            if ($currentTimerForResumed && isset($currentTimerForResumed->user_id)) {
                $technicianId = $currentTimerForResumed->user_id;
            }

            if ($technicianId) {
                $activeTimer = DB::table('timer_tracking')
                    ->where('user_id', $technicianId)
                    ->where('status', 'active')
                    ->orderByDesc('started_at')
                    ->first();

                if ($activeTimer) {
                    $insertData['resumed_timer_id'] = $activeTimer->id;
                }
            }

            // Handle based on reason_type
            switch ($reasonType) {
                case 'record_reason':
                    // If there is already an open record_reason for this timer (pause_start set, pause_end null)
                    // then close it instead of creating a new row.
                    $openReason = DB::table('timer_stop_reasons')
                        ->where('timer_id', $data['timer_id'])
                        ->where('reason_type', 'record_reason')
                        ->whereNotNull('pause_start')
                        ->whereNull('pause_end')
                        ->orderByDesc('id')
                        ->first();

                    if ($openReason) {
                        DB::table('timer_stop_reasons')
                            ->where('id', $openReason->id)
                            ->update([
                                'pause_end'  => $now,
                                // allow overriding body if a new one was sent
                                'body'       => $body ?? $openReason->body,
                                'updated_at' => $now,
                            ]);

                        $updatedReason = DB::table('timer_stop_reasons')->where('id', $openReason->id)->first();

                        return $this->respond(['data' => $updatedReason]);
                    }

                    // No open record: create a new one and mark pause_start
                    $insertData['pause_start'] = $now;
                    $insertData['pause_end'] = null;
                    break;

                case 'finishtimer':
                    // Complete the PAUSED timer for this technician and, if possible, keep timer_id as the completed one
                    // resumed_timer_id (if any) was already detected above as another ACTIVE timer for this technician.
                    $insertData['pause_start'] = null;
                    $insertData['pause_end'] = null;

                    // Determine technician from the timer we're finishing (initially passed timer_id)
                    $currentTimer = DB::table('timer_tracking')
                        ->where('id', $data['timer_id'])
                        ->first();

                    $user_id = $currentTimer->user_id ?? (Auth::user()->id ?? null);

                    $pausedTimer = null;
                    $timerToCompleteId = null;

                    if ($user_id) {
                        // Find a paused timer for this user (the one being finished)
                        $pausedTimer = DB::table('timer_tracking')
                            ->where('user_id', $user_id)
                            ->where('status', 'paused')
                            ->orderByDesc('paused_at')
                            ->first();
                    }

                    // Decide which timer to complete
                    if ($pausedTimer) {
                        $timerToCompleteId = $pausedTimer->id;
                    } elseif ($currentTimer) {
                        $timerToCompleteId = $currentTimer->id;
                    } else {
                        $timerToCompleteId = $data['timer_id'];
                    }

                    // Complete the chosen timer
                    DB::table('timer_tracking')
                        ->where('id', $timerToCompleteId)
                        ->update([
                            'status' => 'completed',
                            'completed_at' => $now,
                            'updated_at' => $now,
                        ]);

                    // Update insertData to reference the completed timer
                    $insertData['timer_id'] = $timerToCompleteId;

                    $completedTimer = DB::table('timer_tracking')->where('id', $timerToCompleteId)->first();
                    break;

                case 'ignore':
                    // Just record the reason, no pause times
                    $insertData['pause_start'] = null;
                    $insertData['pause_end'] = null;
                    break;

                default:
                    $insertData['pause_start'] = $now;
                    $insertData['pause_end'] = null;
            }

            $reasonId = DB::table('timer_stop_reasons')->insertGetId($insertData);

            $reason = DB::table('timer_stop_reasons')->where('id', $reasonId)->first();

            // Include timer status in response for finishtimer case
            $response = ['data' => $reason];
            if ($reasonType === 'finishtimer' && isset($completedTimer)) {
                $response['timer'] = $completedTimer;
                $response['message'] = 'Timer completed successfully';
            }

            return $this->respond($response);
        } catch (\Exception $e) {
            return $this->otherExceptions($e);
        }
    }

    public function updateTimerStopReason(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pause_start' => 'nullable|date',
            'pause_end'   => 'nullable|date',
            'body'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->respondWithError($validator->errors()->first());
        }

        $business_id = Auth::user()->business_id;

        $reason = DB::table('timer_stop_reasons')
            ->where('id', $id)
            ->where('business_id', $business_id)
            ->first();

        if (! $reason) {
            return $this->setStatusCode(Response::HTTP_NOT_FOUND)
                ->respondWithError('Stop reason not found');
        }

        $data = $validator->validated();
        $data['updated_at'] = now();

        DB::table('timer_stop_reasons')
            ->where('id', $id)
            ->update($data);

        $updated = DB::table('timer_stop_reasons')->where('id', $id)->first();

        return $this->respond(['data' => $updated]);
    }

    /**
     * Mark the end of a stop reason (set pause_end)
     *
     * @urlParam id integer required The timer_stop_reasons ID
     */
    public function endTimerStopReason($id)
    {
        try {
       

            $reason = DB::table('timer_stop_reasons')
                ->where('id', $id)
                ->first();

            if (! $reason) {
                return $this->setStatusCode(Response::HTTP_NOT_FOUND)
                    ->respondWithError('Stop reason not found');
            }

            $now = Carbon::now();

            DB::table('timer_stop_reasons')
                ->where('id', $id)
                ->update([
                    'pause_end'  => $now,
                    'updated_at' => $now,
                ]);

            $updated = DB::table('timer_stop_reasons')->where('id', $id)->first();

            return $this->respond(['data' => $updated]);
        } catch (\Exception $e) {
            return $this->otherExceptions($e);
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

        // Maintain mutable pools per job-user to allocate timers across multiple workshops
        $timerPools = $timerData->map(function ($collection) {
            return $collection->values();
        });

        $allTimerIds = $timerData->flatten(1)->pluck('id')->unique()->values();
        $reasonsByTimer = $allTimerIds->isEmpty() ? collect() : DB::table('timer_stop_reasons')
            ->whereIn('timer_id', $allTimerIds)
            ->orderBy('created_at')
            ->get()
            ->groupBy('timer_id');

        // 5) Process each job sheet
        $enrichedJobs = [];
        foreach ($jobSheets as $job) {
            // Build service-based timer grouping FIRST and create missing timers as paused
            $jobServices = $servicesByJob->get($job->id, collect());
            $serviceGroups = [];

            foreach ($jobServices as $svc) {
                $assignmentKey = $job->id . '|' . $svc->workshop_id;
                $assignedUsers = $assignments->get($assignmentKey, collect())->pluck('user_id')->values();

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
                    $assignmentPool = $timerPools->get($timerKey, collect())->values();

                    // Ensure there is a timer available for this specific assignment
                    if ($assignmentPool->isEmpty()) {
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
                        $existingTimers = $timerData->get($timerKey, collect());
                        $existingTimers->push($newTimer);
                        $timerData->put($timerKey, $existingTimers);

                        $assignmentPool->push($newTimer);
                    }

                    $timerRecord = $assignmentPool->shift();
                    $timerPools->put($timerKey, $assignmentPool);

                    if ($timerRecord) {
                        $timersForService[] = $this->processWorkerTimerData($uid, $userName, collect([$timerRecord]), $now, $reasonsByTimer);
                    }
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
            $enrichedJob = $this->processJobSheetWithTimers($job, $users, $timerData, $now, $reasonsByTimer);
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
    private function processJobSheetWithTimers($job, $users, $timerData, Carbon $now, $reasonsByTimer): array
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
            $workerData = $this->processWorkerTimerData($userId, $userName, $userTimers, $now, $reasonsByTimer);
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
            'workshop_name' => $job->workshop_name ?? 'Workshop',
            'status_name' => $job->status_name,
            'status_color' => $job->status_color ?? '#6c757d',
          
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
    private function processWorkerTimerData(int $userId, string $userName, $userTimers, Carbon $now, $reasonsByTimer): array
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
                // Only set current timer info if not already determined by a more recent timer
                if ($currentTimerId === null) {
                    $currentTimerStatus = 'completed';
                    $currentTimerId = $timer->id;
                    $lastActionAt = $timer->completed_at;
                    $TimeAllocate = isset($timer->time_allocate) ? $timer->time_allocate : null;
                }
            } elseif ($timer->status === 'paused' && $timer->paused_at) {
                $timerPaused = Carbon::parse($timer->paused_at);
                $elapsed = $timerPaused->diffInSeconds($timerStarted) - $pausedDuration;
                if ($currentTimerId === null) {
                    $currentTimerStatus = 'paused';
                    $currentTimerId = $timer->id;
                    $pausedAt = $timer->paused_at;
                    $lastActionAt = $timer->paused_at;
                    $TimeAllocate = isset($timer->time_allocate) ? $timer->time_allocate : null;
                }
            } else { // active timer
                $elapsed = $now->diffInSeconds($timerStarted) - $pausedDuration;
                if ($currentTimerId === null) {
                    $currentTimerStatus = 'active';
                    $currentTimerId = $timer->id;
                    $lastActionAt = $timer->started_at;
                    $TimeAllocate = isset($timer->time_allocate) ? $timer->time_allocate : null;
                }
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

        $reasons = [];
        if ($currentTimerId && $reasonsByTimer) {
            $timerReasons = $reasonsByTimer->get($currentTimerId, collect());
            if ($timerReasons) {
                $reasons = $timerReasons->values()->toArray();
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
            'time_allocate' => $TimeAllocate,
            'reasons' => $reasons
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
                return $this->setStatusCode(422)->respondWithError($validator->errors()->first());
            }

            $business_id = Auth::user()->business_id;
            $job_sheet_id = $request->input('job_sheet_id');
            $user_id = (int) $request->input('user_id');

            if (!$job_sheet_id || !$user_id) {
                return $this->setStatusCode(422)->respondWithError('job_sheet_id and user_id are required');
            }

            // Verify job sheet belongs to user's business
            $jobSheet = DB::table('repair_job_sheets')
                ->where('id', $job_sheet_id)
                ->where('business_id', $business_id)
                ->first();

            if (!$jobSheet) {
                return $this->setStatusCode(404)->respondWithError('Job sheet not found');
            }

            // Check if user is assigned to this job sheet
            $serviceStaff = json_decode($jobSheet->service_staff ?? '[]', true) ?: [];
            if (!in_array($user_id, $serviceStaff)) {
                return $this->setStatusCode(403)->respondWithError('User is not assigned to this job sheet');
            }

            // Guard: require active attendance today
            if (!$this->userHasActiveAttendanceToday($user_id, $business_id)) {
                return $this->setStatusCode(403)->respondWithError('Technician must clock in today before starting timer');
            }

            $now = Carbon::now();
            $responseData = null;

            DB::transaction(function () use (&$responseData, $business_id, $job_sheet_id, $user_id, $now) {
                // Auto-pause other active timers for this technician before starting/resuming
                $this->pauseOtherActiveTimers((int) $user_id, $job_sheet_id, $now);

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

            return $this->respond([
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
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
    public function pauseTimer(Request $request, $timer_id)
    {
        try {
       

            $business_id = Auth::user()->business_id;

            DB::table('timer_tracking')
                ->where('id', $timer_id)
                ->where('business_id', $business_id)
                ->update([
                    'status' => 'paused',
                    'paused_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

            return $this->respond([
                'data' => [
                    'success' => true,
                    'message' => 'Timer paused successfully',
                    'timer_id' => (int) $timer_id,
                    'another_timer_stopped' => true,

                ]
            ]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
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
    public function resumeTimer(Request $request, $timer_id)
    {
        try {
           

            $business_id = Auth::user()->business_id;
            $authUserId  = Auth::user()->id;

            $timer = DB::table('timer_tracking')
                ->where('id', $timer_id)
                ->first();

            if (!$timer) {
                return $this->setStatusCode(404)->respondWithError('Timer not found');
            }

            // Use the timer's technician as the effective user for pause/resume logic
            $user_id = (int) $timer->user_id;

            // Guard: require active attendance today for the timer's user
            if (!$this->userHasActiveAttendanceToday($user_id, $business_id)) {
                return $this->setStatusCode(403)->respondWithError('Technician must clock in today before resuming timer');
            }

            // Track if we paused other timers and which ones
            $anotherTimerStopped = false;
            $stoppedTimerIds = [];

            if ($timer && $timer->status === 'paused' && $timer->paused_at) {
                $now = Carbon::now();
                $pausedSeconds = Carbon::parse($timer->paused_at)->diffInSeconds($now);

                DB::transaction(function () use ($user_id, $timer, $timer_id, $pausedSeconds, $now, &$anotherTimerStopped, &$stoppedTimerIds) {
                    // Pause other active timers for this user (excluding the one being resumed)
                    $localStoppedIds = [];
                    $pausedCount = $this->pauseOtherActiveTimersAndCount((int) $user_id, (int) $timer->job_sheet_id, $now, (int) $timer_id, $localStoppedIds);
                    $anotherTimerStopped = $pausedCount > 0;
                    if ($pausedCount > 0) {
                        $stoppedTimerIds = $localStoppedIds;
                    }

                    // Resume the current timer
                    DB::table('timer_tracking')
                        ->where('id', $timer_id)
                        ->update([
                            'status' => 'active',
                            'resumed_at' => $now,
                            'total_paused_duration' => DB::raw('total_paused_duration + ' . (int) $pausedSeconds),
                            'updated_at' => $now,
                        ]);

                    DB::table('timer_stop_reasons')
                        ->where('timer_id', $timer_id)
                        ->where('reason_type', 'record_reason')
                        ->whereNotNull('pause_start')
                        ->whereNull('pause_end')
                        ->update([
                            'pause_end' => $now,
                            'updated_at' => $now,
                        ]);
                });
            } elseif ($timer && $timer->status === 'completed' && $timer->completed_at) {
                // Allow resuming a completed timer: treat the time since completion as paused
                $now = Carbon::now();
                $pausedSeconds = Carbon::parse($timer->completed_at)->diffInSeconds($now);

                DB::transaction(function () use ($user_id, $timer, $timer_id, $pausedSeconds, $now, &$anotherTimerStopped) {
                    // Pause other active timers for this user (excluding the one being resumed)
                    $pausedCount = $this->pauseOtherActiveTimersAndCount((int) $user_id, (int) $timer->job_sheet_id, $now, (int) $timer_id);
                    $anotherTimerStopped = $pausedCount > 0;

                    DB::table('timer_tracking')
                        ->where('id', $timer_id)
                        ->update([
                            'status' => 'active',
                            'resumed_at' => $now,
                            'completed_at' => null,
                            'total_paused_duration' => DB::raw('total_paused_duration + ' . (int) $pausedSeconds),
                            'updated_at' => $now,
                        ]);

                    DB::table('timer_stop_reasons')
                        ->where('timer_id', $timer_id)
                        ->where('reason_type', 'record_reason')
                        ->whereNotNull('pause_start')
                        ->whereNull('pause_end')
                        ->update([
                            'pause_end' => $now,
                            'updated_at' => $now,
                        ]);
                });
            }

            return $this->respond([
                'data' => [
                    'success' => true,
                    'message' => 'Timer resumed successfully',
                    'another_timer_stopped' => $anotherTimerStopped,
                    'stopped_timer_ids' => $stoppedTimerIds,
                ]
            ]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
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
    public function completeTimer(Request $request, $timer_id)
    {
        try {
            if (!$this->moduleUtil->hasThePermissionInSubscription(Auth::user()->business_id, 'essentials_module')) {
                return $this->respondUnauthorized();
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

            return $this->respond([
                'data' => [
                    'success' => true,
                    'message' => 'Timer completed successfully'
                ]
            ]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
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
                return $this->setStatusCode(422)->respondWithError($validator->errors()->first());
            }

            $business_id = Auth::user()->business_id;
            $job_sheet_id = (int) $request->input('job_sheet_id');

            $job = DB::table('repair_job_sheets')
                ->where('business_id', $business_id)
                ->where('id', $job_sheet_id)
                ->select('service_staff')
                ->first();

            if (!$job) {
                return $this->setStatusCode(404)->respondWithError('Job sheet not found');
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

                    $this->pauseOtherActiveTimers( (int) $user_id, $job_sheet_id, $now);

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

            return $this->respond([
                'data' => [
                    'success' => true,
                    'message' => 'All timers started successfully',
                    'skipped_user_ids' => $skippedUserIds
                ]
            ]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
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
                return $this->respond([
                    'data' => [
                        'success' => true,
                        'message' => 'No active timers to pause'
                    ]
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

            return $this->respond([
                'data' => [
                    'success' => true,
                    'message' => 'All timers paused successfully'
                ]
            ]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
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
                return $this->respond([
                    'data' => [
                        'success' => true,
                        'message' => 'No active timers to complete'
                    ]
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

            return $this->respond([
                'data' => [
                    'success' => true,
                    'message' => 'All timers completed successfully'
                ]
            ]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
        }
    }

    /**
     * Unassign technician from job sheet and delete related timers
     *
     * This endpoint will:
     * - Mark workshop assignment history records for this user + job sheet as unassigned
     *   (assignment_type = 'job_sheet')
     * - Delete timer_tracking rows for this user on this job sheet
     *
     * If a specific timer_id is provided, only that timer will be deleted (for this
     * user + job sheet). If omitted, all timers for the user on the job sheet
     * will be deleted.
     *
     * @bodyParam job_sheet_id int required Job sheet ID Example: 5
     * @bodyParam user_id int required Technician (user) ID Example: 3
     * @bodyParam timer_id int required Specific timer ID to delete for this user & job sheet Example: 10
     *
     * @response {
     *   "data": {
     *     "success": true,
     *     "message": "Technician unassigned and timers deleted successfully",
     *     "timers_deleted": 2,
     *     "assignments_updated": 1
     *   }
     * }
     */
    public function unassignTechnicianAndDeleteTimers(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'job_sheet_id' => 'required|integer|exists:repair_job_sheets,id',
                'user_id' => 'required|integer|exists:users,id',
                'timer_id' => 'required|integer|exists:timer_tracking,id',
            ]);

            if ($validator->fails()) {
                return $this->setStatusCode(422)->respondWithError($validator->errors()->first());
            }

            $jobSheetId = (int) $request->input('job_sheet_id');
            $userId = (int) $request->input('user_id');
            $timerId = (int) $request->input('timer_id');

            // Ensure the job sheet belongs to the same location as the authenticated user
            $authUser = Auth::user();
            $jobSheet = DB::table('repair_job_sheets')
                ->where('id', $jobSheetId)
                ->where('location_id', $authUser->location_id)
                ->first();

            if (!$jobSheet) {
                return $this->setStatusCode(404)->respondWithError('Job sheet not found for this location');
            }

            $assignmentsUpdated = 0;
            $timersDeleted = 0;

            DB::transaction(function () use ($jobSheetId, $userId, $timerId, &$assignmentsUpdated, &$timersDeleted) {
                // Mark technician-job_sheet assignments as unassigned
                $assignmentsUpdated = WorkshopTechnicianAssignmentHistory::where('job_sheet_id', $jobSheetId)
                    ->where('user_id', $userId)
                    ->where('assignment_type', 'job_sheet')
                    ->where('status', 'assigned')
                    ->update([
                        'status' => 'unassigned',
                        'notes' => 'Technician unassigned via timer API',
                        'updated_at' => Carbon::now(),
                    ]);

                // Delete the specific timer for this user on this job sheet
                $timersDeleted = DB::table('timer_tracking')
                    ->where('job_sheet_id', $jobSheetId)
                    ->where('user_id', $userId)
                    ->where('id', $timerId)
                    ->delete();
            });

            return $this->respond([
                'data' => [
                    'success' => true,
                    'message' => 'Technician unassigned and timers deleted successfully',
                    'timers_deleted' => (int) $timersDeleted,
                    'assignments_updated' => (int) $assignmentsUpdated,
                ],
            ]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
        }
    }


    public function updateTimeAllocation(Request $request)
    {
        try {
            $business_id = Auth::user()->business_id;

            // Inputs
            $ids = $request->input('timer_ids', $request->input('ids'));
            $updatesInput = $request->input('updates', $request->input('timers'));
            // Prefer new name; fallback to legacy 'time_allocate' only (remove service_percent)
            $singleValue = $request->input('time_allocated', $request->input('time_allocate'));

            // 1) Bulk Update: accept array of {timer_id|id, time_allocated}
            if (is_array($updatesInput) && !empty($updatesInput)) {
                $validator = Validator::make(['updates' => $updatesInput], [
                    'updates' => ['array', 'min:1'],
                    'updates.*.timer_id' => ['required_without:updates.*.id', 'integer', 'min:1'],
                    'updates.*.id' => ['nullable', 'integer', 'min:1'],
                    'updates.*.time_allocated' => ['nullable', 'numeric', 'min:0']
                ]);
                if ($validator->fails()) {
                    return $this->setStatusCode(422)->respondWithError('Invalid bulk update payload');
                }

                // Normalize and deduplicate (last value wins)
                $pairs = [];
                foreach ($updatesInput as $u) {
                    $tid = isset($u['timer_id']) ? $u['timer_id'] : (isset($u['id']) ? $u['id'] : null);
                    if ($tid === null) { continue; }
                    $pairs[(int) $tid] = isset($u['time_allocated']) ? $u['time_allocated'] : null;
                }
                if (empty($pairs)) {
                    return $this->setStatusCode(422)->respondWithError('No valid timer updates provided');
                }

                $idsToUpdate = array_keys($pairs);

                // Filter to timers belonging to this business
                $validIds = DB::table('timer_tracking')
                    ->where('business_id', $business_id)
                    ->whereIn('id', $idsToUpdate)
                    ->pluck('id')
                    ->all();

                if (empty($validIds)) {
                    return $this->setStatusCode(404)->respondWithError('No matching timers found for update');
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
                    return $this->setStatusCode(422)->respondWithError('Invalid timer IDs');
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
                return $this->setStatusCode(422)->respondWithError('Invalid time allocation value');
            }

            $singleId = $request->input('timer_id', $request->input('id'));
            $idValidator = Validator::make(['timer_id' => $singleId], [
                'timer_id' => ['required', 'integer', 'min:1']
            ]);
            if ($idValidator->fails()) {
                return $this->setStatusCode(422)->respondWithError('Invalid or missing timer_id');
            }

            $now = Carbon::now();
            DB::table('timer_tracking')
         
                ->where('id', $singleId)
                ->update([
                    'time_allocate' => $singleValue,
                    'updated_at' => $now,
                ]);

            return $this->respond([
                'data' => [
                    'success' => true,
                    'timer_id' => (int) $singleId,
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

            return $this->respond([
                'data' => $result,
                'meta' => [
                    'current_page' => $timers->currentPage(),
                    'total' => $timers->total(),
                    'per_page' => $timers->perPage(),
                    'last_page' => $timers->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->otherExceptions($e);
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

    private function pauseOtherActiveTimers(int $user_id, int $job_sheet_id, Carbon $timestamp): void
    {
        // Backward-compatible wrapper: we don't care about IDs here
        $this->pauseOtherActiveTimersAndCount($user_id, $job_sheet_id, $timestamp);
    }

    /**
     * Pause other active timers for a user and return the count of paused timers
     * Optionally exclude a specific timer from being paused
     */
    private function pauseOtherActiveTimersAndCount(int $user_id, int $job_sheet_id, Carbon $timestamp, ?int $excludeTimerId = null, ?array &$stoppedTimerIds = null): int
    {
        // Pause ALL other active timers for this user (regardless of job sheet)
        $query = DB::table('timer_tracking')
            ->where('user_id', $user_id)
            ->where('status', 'active');

        // Exclude the timer being resumed (if provided)
        if ($excludeTimerId) {
            $query->where('id', '!=', $excludeTimerId);
        }

        // If caller wants the IDs of timers being paused, collect them before update
        if (is_array($stoppedTimerIds)) {
            $idsQuery = clone $query;
            $stoppedTimerIds = $idsQuery->pluck('id')->map(function ($id) {
                return (int) $id;
            })->all();
        }

        $pausedCount = $query->update([
            'status' => 'paused',
            'paused_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return $pausedCount;
    }
}


