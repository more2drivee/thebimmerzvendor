<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Connector\Utils\ModuleUtil;
use Modules\TimeManagement\Entities\WorkshopTechnicianAssignmentHistory;
use Modules\Repair\Entities\Workshop;
use Carbon\Carbon;

class AssignController extends Controller
{
    /**
     * Module utility instance
     */
    protected $moduleUtil;

    /**
     * Maximum technicians per workshop
     */
    const MAX_TECHNICIANS_PER_WORKSHOP = 100;

    /**
     * Constructor
     */

    /**
     * Get assignments for job sheets with workshops and technicians
     * Optimized version without RJS dependency and caching
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $business_id = $request->user()->business_id;
            $location_id = $request->user()->location_id;

            $request->validate([
                'job_sheet_id' => 'nullable|integer',
                'workshop_id' => 'nullable|integer',
                'technician_id' => 'nullable|integer',
                'date' => 'nullable|date',
                'status' => 'nullable|string|in:active,completed,all',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $date = $request->date ? Carbon::parse($request->date) : Carbon::today();
            $perPage = $request->per_page ?? 15;
            $status = $request->status ?? 'active';

            // Optimized query without RJS alias - direct table access with efficient joins
            $query = DB::table('repair_job_sheets')
                ->leftJoin('bookings', 'repair_job_sheets.booking_id', '=', 'bookings.id')
                ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')
                ->leftJoin('repair_device_models as rdm', 'contact_device.models_id', '=', 'rdm.id')
                ->leftJoin('categories as device_cat', 'contact_device.device_id', '=', 'device_cat.id')
                ->leftJoin('brands', 'rdm.brand_id', '=', 'brands.id')
                ->leftJoin('contacts', 'bookings.contact_id', '=', 'contacts.id')
                ->leftJoin('workshops as w', function($join) {
                    $join->on(DB::raw("JSON_CONTAINS(repair_job_sheets.workshops, CAST(w.id as JSON))"), '=', DB::raw('1'));
                })
                ->leftJoin('workshop_assignments as wtah', function($join) use ($date) {
                    $join->on('w.id', '=', 'wtah.workshop_id')
                         ->where('wtah.assignment_type', 'workshop')
                         ->whereDate('wtah.created_at', $date)
                         ->where('wtah.status', 'assigned');
                })
                ->leftJoin('users as u', 'wtah.user_id', '=', 'u.id')
                ->where('repair_job_sheets.business_id', $business_id);

            if ($location_id) {
                $query->where('repair_job_sheets.location_id', $location_id);
            }

            // Apply filters
            if ($request->job_sheet_id) {
                $query->where('repair_job_sheets.id', $request->job_sheet_id);
            }

            if ($request->workshop_id) {
                $query->where('w.id', $request->workshop_id);
            }

            if ($request->technician_id) {
                $query->where('u.id', $request->technician_id);
            }

            if ($status === 'active') {
                $query->whereIn('repair_job_sheets.status_id', function($subQuery) {
                    $subQuery->select('id')
                             ->from('repair_statuses')
                             ->where('is_completed_status', 0);
                });
            } elseif ($status === 'completed') {
                $query->whereIn('repair_job_sheets.status_id', function($subQuery) {
                    $subQuery->select('id')
                             ->from('repair_statuses')
                             ->where('is_completed_status', 1);
                });
            }

            // Real-time data retrieval with comprehensive device information
            $assignments = $query->select([
                'repair_job_sheets.id as job_sheet_id',
                'repair_job_sheets.job_sheet_no',
                'repair_job_sheets.workshops',
                'repair_job_sheets.created_at as job_created_at',
                'repair_job_sheets.updated_at as job_updated_at',
                // Real-time customer information
                'contacts.name as customer_name',
      
                'contacts.email as customer_email',
                // Real-time device information without caching
                'device_cat.name as device_name',
                'device_cat.id as device_id',
                'rdm.name as device_model',
                'rdm.id as device_model_id',
                'brands.name as device_brand',
                'brands.id as device_brand_id',
                // Real-time device details
                'contact_device.plate_number',
                'contact_device.chassis_number',
                'contact_device.color as device_color',
                'contact_device.manufacturing_year',
                'contact_device.car_type',
                // Workshop and technician information
                'w.id as workshop_id',
                'w.name as workshop_name',
                'u.id as technician_id',
                'u.first_name',
                'u.last_name',
                'wtah.created_at as technician_assigned_at'
            ])
            ->orderBy('repair_job_sheets.created_at', 'desc')
            ->paginate($perPage);

            // Process results with real-time device data
            $groupedAssignments = $assignments->getCollection()->groupBy('job_sheet_id')->map(function ($jobAssignments) {
                $firstAssignment = $jobAssignments->first();
                
                // Build comprehensive device information in real-time
                $deviceInfo = null;
                if ($firstAssignment->device_id) {
                    $deviceInfo = [
                        'id' => $firstAssignment->device_id,
                        'name' => $firstAssignment->device_name,
                        'model' => [
                            'id' => $firstAssignment->device_model_id,
                            'name' => $firstAssignment->device_model
                        ],
                        'brand' => [
                            'id' => $firstAssignment->device_brand_id,
                            'name' => $firstAssignment->device_brand
                        ],
                        'details' => [
                            'plate_number' => $firstAssignment->plate_number,
                            'chassis_number' => $firstAssignment->chassis_number,
                            'color' => $firstAssignment->device_color,
                            'manufacturing_year' => $firstAssignment->manufacturing_year,
                            'car_type' => $firstAssignment->car_type
                        ]
                    ];
                    
                    // Filter out null values for cleaner response
                    $deviceInfo = array_filter($deviceInfo, function($value) {
                        return !is_null($value) && $value !== '';
                    });
                    
                    if (isset($deviceInfo['details'])) {
                        $deviceInfo['details'] = array_filter($deviceInfo['details'], function($value) {
                            return !is_null($value) && $value !== '';
                        });
                    }
                }
                
                return [
                    'job_sheet_id' => $firstAssignment->job_sheet_id,
                    'job_sheet_no' => $firstAssignment->job_sheet_no,
                    'customer' => [
                        'name' => $firstAssignment->customer_name,
                  
                        'email' => $firstAssignment->customer_email
                    ],
                    'device' => $deviceInfo,
                    'timestamps' => [
                        'created_at' => $firstAssignment->job_created_at,
                        'updated_at' => $firstAssignment->job_updated_at
                    ],
                    'workshops' => $jobAssignments->where('workshop_id', '!=', null)->groupBy('workshop_id')->map(function ($workshopAssignments) {
                        $firstWorkshop = $workshopAssignments->first();
                        
                        return [
                            'workshop_id' => $firstWorkshop->workshop_id,
                            'workshop_name' => $firstWorkshop->workshop_name,
                            'technicians' => $workshopAssignments->where('technician_id', '!=', null)->map(function ($assignment) {
                                return [
                                    'technician_id' => $assignment->technician_id,
                                    'name' => trim($assignment->first_name . ' ' . $assignment->last_name),
                                    'assigned_at' => $assignment->technician_assigned_at
                                ];
                            })->values()
                        ];
                    })->values()
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $groupedAssignments,
                'pagination' => [
                    'current_page' => $assignments->currentPage(),
                    'per_page' => $assignments->perPage(),
                    'total' => $assignments->total(),
                    'last_page' => $assignments->lastPage()
                ],
                'meta' => [
                    'retrieved_at' => now()->toISOString(),
                    'real_time' => true,
                    'cached' => false
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('AssignController@index: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve assignments'], 500);
        }
    }

    /**
     * Assign workshops to a job sheet
     * Optimized version without caching for real-time processing
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function AssignWorkshop_toJobsheet(Request $request)
    {
        try {
            $business_id = $request->user()->business_id;
            $current_user = $request->user();

            $request->validate([
                'job_sheet_id' => 'required|integer',
                'workshop_assignments' => 'required|array|min:1',
                'workshop_assignments.*.workshop_id' => 'required|integer',
                'workshop_assignments.*.technician_ids' => 'nullable|array',
                'workshop_assignments.*.technician_ids.*' => 'integer',
                'notes' => 'nullable|string|max:500'
            ]);

            DB::beginTransaction();

            // Real-time validation - job sheet exists and belongs to business
            $jobSheet = DB::table('repair_job_sheets')
                ->where('business_id', $business_id)
                ->where('id', $request->job_sheet_id)
                ->select([
                    'id',
                    'job_sheet_no'
                ])
                ->first();

            if (!$jobSheet) {
                return response()->json(['error' => 'Job sheet not found'], 404);
            }

            // Extract workshop IDs from workshop_assignments
            $workshopIds = collect($request->workshop_assignments)->pluck('workshop_id')->unique()->toArray();

            // Real-time workshop validation
            $workshops = Workshop::query()
                ->where('business_id', $business_id)
                ->whereIn('id', $workshopIds)
                ->pluck('name', 'id');

            if ($workshops->count() !== count($workshopIds)) {
                return response()->json(['error' => 'One or more workshops not found'], 404);
            }

            $now = now();

            // Get existing assignments for this job sheet to avoid duplicates
            $existingAssignments = WorkshopTechnicianAssignmentHistory::where('job_sheet_id', $request->job_sheet_id)
                ->where('assignment_type', 'job_sheet')
                ->where('status', 'assigned')
                ->get()
                ->keyBy(function ($assignment) {
                    return $assignment->workshop_id . '_' . $assignment->user_id;
                });

            // Create new workshop-to-job sheet assignments with specific technicians only
            $assignmentData = [];
            $newAssignments = [];
            $skippedAssignments = [];

            foreach ($request->workshop_assignments as $assignment) {
                $workshopId = $assignment['workshop_id'];
                $technicianIds = $assignment['technician_ids'] ?? [];
                
                $technicianAssignments = [];
                $skippedTechnicians = [];

                // Assign only the specific technician IDs sent in the request
                foreach ($technicianIds as $techId) {
                    $assignmentKey = $workshopId . '_' . $techId;
                    
                    // Check if this specific workshop-user-job_sheet combination already exists
                    if ($existingAssignments->has($assignmentKey)) {
                        $skippedTechnicians[] = [
                            'technician_id' => $techId,
                            'reason' => 'Already assigned to this job sheet from this workshop'
                        ];
                        continue;
                    }

                    // Create new assignment
                    $newAssignment = WorkshopTechnicianAssignmentHistory::create([
                        'workshop_id' => $workshopId,
                        'job_sheet_id' => $request->job_sheet_id,
                        'user_id' => $techId,
                        'assigned_by' => $current_user->id,
                        'assignment_type' => 'job_sheet',
                        'status' => 'assigned',
                        'notes' => $request->notes ?? 'Technician assigned to job sheet via workshop assignment',
                        'created_at' => $now,
                        'metadata' => [
                            'api_assigned' => true,
                            'source' => 'workshop_to_job_sheet'
                        ]
                    ]);

                    $technicianAssignments[] = [
                        'assignment_id' => $newAssignment->id,
                        'technician_id' => $techId
                    ];
                    $newAssignments[] = $assignmentKey;
                }

                $assignmentData[] = [
                    'workshop_id' => $workshopId,
                    'workshop_name' => $workshops[$workshopId],
                    'technicians' => $technicianAssignments,
                    'skipped_technicians' => $skippedTechnicians
                ];
            }

            // Update job sheet with assigned workshops - real-time update
            DB::table('repair_job_sheets')
                ->where('id', $request->job_sheet_id)
                ->update([
                    'workshops' => json_encode($workshopIds),
                    'updated_at' => $now
                ]);

            // Calculate summary statistics
            $totalNewAssignments = collect($assignmentData)->sum(function ($workshop) {
                return count($workshop['technicians']);
            });
            
            $totalSkippedAssignments = collect($assignmentData)->sum(function ($workshop) {
                return count($workshop['skipped_technicians']);
            });

            DB::commit();

            // Return real-time response with comprehensive data
            return response()->json([
                'success' => true,
                'message' => $totalNewAssignments > 0 ? 'Workshops assigned successfully' : 'No new assignments made - all technicians already assigned',
                'data' => [
                    'job_sheet_id' => $request->job_sheet_id,
                    'job_sheet_no' => $jobSheet->job_sheet_no,
                    'assigned_workshops' => $assignmentData,
                    'summary' => [
                        'new_assignments' => $totalNewAssignments,
                        'skipped_assignments' => $totalSkippedAssignments,
                        'total_workshops' => count($workshopIds)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AssignController@store: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to assign workshops'], 500);
        }
    }

    /**
     * Assign technicians to a workshop
     * Optimized version without caching for real-time processing
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignTechnicians(Request $request)
    {
        try {
            $business_id = $request->user()->business_id;
            $current_user = $request->user();

            $request->validate([
                'workshop_id' => 'required|integer',
                'technician_ids' => 'required|array|min:1',
                'technician_ids.*' => 'integer',
                'notes' => 'nullable|string|max:500'
            ]);

            DB::beginTransaction();

            // Real-time workshop validation with current capacity
            $workshop = DB::table('workshops')
                ->leftJoin('workshop_assignments as wtah', function($join) {
                    $join->on('workshops.id', '=', 'wtah.workshop_id')
                         ->where('wtah.assignment_type', 'workshop')
                         ->whereDate('wtah.created_at', Carbon::today())
                         ->where('wtah.status', 'assigned');
                })
                ->where('workshops.business_id', $business_id)
                ->where('workshops.id', $request->workshop_id)
                ->select([
                    'workshops.*',
                    DB::raw('COUNT(wtah.id) as current_technicians')
                ])
                ->groupBy('workshops.id', 'workshops.name', 'workshops.business_id', 'workshops.business_location_id')
                ->first();

            if (!$workshop) {
                return response()->json(['error' => 'Workshop not found'], 404);
            }

            // Real-time technician validation
            // First get all requested technicians to ensure they're all included
            $allTechnicians = DB::table('users')
                ->whereIn('id', $request->technician_ids)
                ->whereNull('deleted_at')
                ->select(['id', 'first_name', 'last_name', 'email'])
                ->get();

            if ($allTechnicians->count() !== count($request->technician_ids)) {
                return response()->json(['error' => 'One or more technicians not found'], 404);
            }

            // Get current assignments for all requested technicians
            $currentAssignments = DB::table('workshop_assignments')
                ->where('assignment_type', 'workshop')
                ->whereDate('created_at', Carbon::today())
                ->where('status', 'assigned')
                ->whereIn('user_id', $request->technician_ids)
                ->select(['user_id', 'workshop_id'])
                ->get()
                ->groupBy('user_id');

            $validTechnicians = $allTechnicians->map(function ($technician) use ($currentAssignments, $request) {
                $assignments = $currentAssignments->get($technician->id, collect());
                $assignedWorkshopIds = $assignments->pluck('workshop_id')->toArray();
                $isAlreadyAssignedToTargetWorkshop = in_array($request->workshop_id, $assignedWorkshopIds);

                return (object) [
                    'id' => $technician->id,
                    'first_name' => $technician->first_name,
                    'last_name' => $technician->last_name,
                    'email' => $technician->email,
                    'assigned_workshop_ids' => $assignedWorkshopIds,
                    'is_already_assigned_to_target' => $isAlreadyAssignedToTargetWorkshop
                ];
            });

            // Filter out technicians already assigned to the target workshop
            $techniciansToAssign = $validTechnicians->reject(function ($technician) {
                return $technician->is_already_assigned_to_target;
            });

            // Check if any technicians are already assigned to the target workshop
            $alreadyAssignedTechnicians = $validTechnicians->filter(function ($technician) {
                return $technician->is_already_assigned_to_target;
            });

            if ($alreadyAssignedTechnicians->isNotEmpty()) {
                $alreadyAssignedNames = $alreadyAssignedTechnicians->map(function ($technician) {
                    return trim($technician->first_name . ' ' . $technician->last_name);
                })->implode(', ');

                return response()->json([
                    'error' => 'Some technicians are already assigned to this workshop',
                    'already_assigned' => $alreadyAssignedNames,
                    'already_assigned_count' => $alreadyAssignedTechnicians->count()
                ], 422);
            }

            // Check workshop capacity in real-time
            $currentTechnicianCount = (int) $workshop->current_technicians;
            $newTechniciansCount = $techniciansToAssign->count();
            
            if (($currentTechnicianCount + $newTechniciansCount) > self::MAX_TECHNICIANS_PER_WORKSHOP) {
                return response()->json([
                    'error' => 'Workshop capacity exceeded',
                    'current_capacity' => $currentTechnicianCount,
                    'max_capacity' => self::MAX_TECHNICIANS_PER_WORKSHOP,
                    'attempting_to_add' => $newTechniciansCount
                ], 422);
            }

            $today = Carbon::today();
            $assignedTechnicians = [];
            $technicianDetails = [];

            // Get workshop names for current assignments (for display purposes)
            $allAssignedWorkshopIds = $techniciansToAssign->flatMap(function ($technician) {
                return $technician->assigned_workshop_ids;
            })->unique()->values();

            $workshopNames = $allAssignedWorkshopIds->isEmpty() ? collect() : DB::table('workshops')
                ->whereIn('id', $allAssignedWorkshopIds)
                ->pluck('name', 'id');

            foreach ($techniciansToAssign as $technician) {
                // Create new assignment without unassigning from other workshops
                $assignment = WorkshopTechnicianAssignmentHistory::create([
                    'workshop_id' => $request->workshop_id,
                    'user_id' => $technician->id,
                    'assigned_by' => $current_user->id,
                    'assignment_type' => 'workshop',
                    'status' => 'assigned',
                    'notes' => $request->notes ?? 'Technician assigned to workshop',
                    'created_at' => now(),
                    'metadata' => [
                        'api_assigned' => true,
                        'assigned_at_timestamp' => now()->timestamp
                    ]
                ]);

                $assignedTechnicians[] = $technician->id;
                $technicianDetails[] = [
                    'id' => $technician->id,
                    'name' => trim($technician->first_name . ' ' . $technician->last_name),
                    'email' => $technician->email,
                    'assignment_id' => $assignment->id,
                    'other_workshops' => $workshopNames->only($technician->assigned_workshop_ids)->values()->toArray()
                ];
            }

            // Log activity with comprehensive real-time data
            $this->logActivity('technicians_assigned', [
                'workshop_id' => $request->workshop_id,
                'workshop_name' => $workshop->name,
                'technician_ids' => $assignedTechnicians,
                'technician_details' => $technicianDetails,
                'assigned_by' => $current_user->id,
                'assignment_count' => count($assignedTechnicians),
                'timestamp' => now()->toISOString()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Technicians assigned successfully',
                'data' => [
                    'workshop_id' => $request->workshop_id,
                    'workshop_name' => $workshop->name,
                    'assigned_technicians' => $technicianDetails,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AssignController@assignTechnicians: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to assign technicians'], 500);
        }
    }

    /**
     * Update workshop assignments for a job sheet
     * Optimized version without caching for real-time processing
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $business_id = $request->user()->business_id;
            $current_user = $request->user();

            $request->validate([
                'job_sheet_id' => 'required|integer',
                'workshop_ids' => 'required|array|min:1',
                'workshop_ids.*' => 'integer',
                'action' => 'required|string|in:add,replace',
                'notes' => 'nullable|string|max:500'
            ]);

            DB::beginTransaction();

            // Real-time job sheet validation with device and customer info
            $jobSheet = DB::table('repair_job_sheets')
                ->leftJoin('bookings', 'repair_job_sheets.booking_id', '=', 'bookings.id')
                ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')
                ->leftJoin('repair_device_models as rdm', 'contact_device.models_id', '=', 'rdm.id')
                ->leftJoin('categories as device_cat', 'contact_device.device_id', '=', 'device_cat.id')
                ->leftJoin('brands', 'rdm.brand_id', '=', 'brands.id')
                ->leftJoin('contacts', 'bookings.contact_id', '=', 'contacts.id')
                ->where('repair_job_sheets.business_id', $business_id)
                ->where('repair_job_sheets.id', $request->job_sheet_id)
                ->select([
                    'repair_job_sheets.*',
                    'contacts.name as customer_name',
               
                    'device_cat.name as device_name',
                    'rdm.name as device_model',
                    'brands.name as device_brand',
                    'contact_device.serial_no',
                    'contact_device.chassis_number',
                    'contact_device.plate_number'
                ])
                ->first();

            if (!$jobSheet) {
                return response()->json(['error' => 'Job sheet not found'], 404);
            }

            // Real-time workshop validation with capacity info
            $validWorkshops = DB::table('workshops')
                ->leftJoin('workshop_assignments as wtah', function($join) {
                    $join->on('workshops.id', '=', 'wtah.workshop_id')
                         ->where('wtah.assignment_type', 'workshop')
                         ->whereDate('wtah.created_at', Carbon::today())
                         ->where('wtah.status', 'assigned');
                })
                ->where('workshops.business_id', $business_id)
                ->whereIn('workshops.id', $request->workshop_ids)
                ->select([
                    'workshops.*',
                    DB::raw('COUNT(wtah.id) as current_technicians')
                ])
                ->groupBy('workshops.id', 'workshops.name', 'workshops.business_id', 'workshops.business_location_id')
                ->get();

            if ($validWorkshops->count() !== count($request->workshop_ids)) {
                return response()->json(['error' => 'One or more workshops not found'], 404);
            }

            $currentWorkshops = json_decode($jobSheet->workshops ?? '[]', true) ?: [];
            $newWorkshops = [];
            $workshopDetails = [];

            if ($request->action === 'replace') {
                // Remove all existing assignments for this job sheet
                WorkshopTechnicianAssignmentHistory::where('job_sheet_id', $request->job_sheet_id)
                    ->where('assignment_type', 'job_sheet')
                    ->where('status', 'assigned')
                    ->update([
                        'status' => 'unassigned',
                        'notes' => 'Replaced with new workshop assignments',
                        'updated_at' => now()
                    ]);

                $newWorkshops = $request->workshop_ids;
            } else { // add
                $newWorkshops = array_unique(array_merge($currentWorkshops, $request->workshop_ids));
            }

            // Create new assignment records with real-time metadata
            foreach ($validWorkshops as $workshop) {
                if (!in_array($workshop->id, $currentWorkshops) || $request->action === 'replace') {
                    WorkshopTechnicianAssignmentHistory::create([
                        'workshop_id' => $workshop->id,
                        'job_sheet_id' => $request->job_sheet_id,
                        'assigned_by' => $current_user->id,
                        'assignment_type' => 'job_sheet',
                        'status' => 'assigned',
                        'notes' => $request->notes ?? "Job sheet {$request->action}ed to workshop",
                        'created_at' => now(),
                        'metadata' => [
                            'api_assigned' => true,
                            'action' => $request->action,
                            'assigned_at_timestamp' => now()->timestamp,
                            'workshop_capacity' => $workshop->current_technicians,
                            'device_info' => [
                                'device_name' => $jobSheet->device_name,
                                'device_model' => $jobSheet->device_model,
                                'device_brand' => $jobSheet->device_brand,
                                'serial_no' => $jobSheet->serial_no
                            ]
                        ]
                    ]);

                    // Also create technician-level job sheet assignments for technicians currently assigned to this workshop
                    $activeTechs = WorkshopTechnicianAssignmentHistory::where('workshop_id', $workshop->id)
                        ->where('assignment_type', 'workshop')
                        ->where('status', 'assigned')
                        ->select('user_id')
                        ->distinct()
                        ->pluck('user_id');

                    foreach ($activeTechs as $techId) {
                        $existsTechJob = WorkshopTechnicianAssignmentHistory::where('assignment_type', 'job_sheet')
                            ->where('job_sheet_id', $request->job_sheet_id)
                            ->where('user_id', $techId)
                            ->where('status', 'assigned')
                            ->exists();

                        if (!$existsTechJob) {
                            WorkshopTechnicianAssignmentHistory::create([
                                'workshop_id' => $workshop->id,
                                'job_sheet_id' => $request->job_sheet_id,
                                'user_id' => $techId,
                                'assigned_by' => $current_user->id,
                                'assignment_type' => 'job_sheet',
                                'status' => 'assigned',
                                'notes' => $request->notes ?? 'Technician assigned to job sheet via workshop assignment',
                                'created_at' => now(),
                                'metadata' => [
                                    'api_assigned' => true,
                                    'source' => 'workshop_to_job_sheet'
                                ]
                            ]);
                        }
                    }
                }

                $workshopDetails[] = [
                    'id' => $workshop->id,
                    'name' => $workshop->name,
                    'description' => $workshop->description,
                    'current_technicians' => (int) $workshop->current_technicians,
                    'capacity' => $workshop->capacity,
                    'utilization_percentage' => $workshop->capacity > 0 ? 
                        round(($workshop->current_technicians / $workshop->capacity) * 100, 2) : 0
                ];
            }

            // Update job sheet with new workshop assignments
            DB::table('repair_job_sheets')
                ->where('id', $request->job_sheet_id)
                ->update([
                    'workshops' => json_encode($newWorkshops),
                    'updated_at' => now()
                ]);

            // Log activity with comprehensive real-time data
            $this->logActivity('workshop_assignment_updated', [
                'job_sheet_id' => $request->job_sheet_id,
                'job_sheet_no' => $jobSheet->job_sheet_no,
                'action' => $request->action,
                'previous_workshops' => $currentWorkshops,
                'new_workshops' => $newWorkshops,
                'workshop_details' => $workshopDetails,
                'updated_by' => $current_user->id,
                'device_info' => [
                    'device_name' => $jobSheet->device_name,
                    'device_model' => $jobSheet->device_model,
                    'device_brand' => $jobSheet->device_brand,
                    'serial_no' => $jobSheet->serial_no,
                    'chassis_number' => $jobSheet->chassis_number,
                    'plate_number' => $jobSheet->plate_number
                ],
          
                'timestamp' => now()->toISOString()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Workshop assignments updated successfully',
                'data' => [
                    'job_sheet_id' => $request->job_sheet_id,
                    'job_sheet_no' => $jobSheet->job_sheet_no,
                    'action' => $request->action,
                    'assigned_workshops' => $workshopDetails,
                    'device_info' => [
                        'device_name' => $jobSheet->device_name,
                        'device_model' => $jobSheet->device_model,
                        'device_brand' => $jobSheet->device_brand,
                        'serial_no' => $jobSheet->serial_no
                    ],
                    'customer_name' => $jobSheet->customer_name
                ],
                'meta' => [
                    'updated_at' => now()->toISOString(),
                    'real_time' => true,
                    'cached' => false
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AssignController@update: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update workshop assignments'], 500);
        }
    }

    /**
     * Remove assignments
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
       

            $business_id = $request->user()->business_id;
            $current_user = $request->user();

            $request->validate([
                'type' => 'required|string|in:workshop_from_job,technician_from_workshop',
                'job_sheet_id' => 'required_if:type,workshop_from_job|integer',
                'workshop_id' => 'required_if:type,technician_from_workshop|integer',
                'workshop_ids' => 'required_if:type,workshop_from_job|array|min:1',
                'workshop_ids.*' => 'integer',
                'technician_ids' => 'required_if:type,technician_from_workshop|array|min:1',
                'technician_ids.*' => 'integer',
                'notes' => 'nullable|string|max:500'
            ]);

            DB::beginTransaction();

            if ($request->type === 'workshop_from_job') {
                // Remove workshops from job sheet
                $jobSheet = DB::table('repair_job_sheets')
                    ->where('business_id', $business_id)
                    ->where('id', $request->job_sheet_id)
                    ->first();

                if (!$jobSheet) {
                    return response()->json(['error' => 'Job sheet not found'], 404);
                }

                // Unassign workshops
                $workshopUnassignedCount = WorkshopTechnicianAssignmentHistory::where('job_sheet_id', $request->job_sheet_id)
                    ->where('assignment_type', 'job_sheet')
                    ->whereIn('workshop_id', $request->workshop_ids)
                    ->where('status', 'assigned')
                    ->update([
                        'status' => 'unassigned',
                        'notes' => $request->notes ?? 'Workshop unassigned from job sheet',
                        'updated_at' => now()
                    ]);

                // Also unassign technician-level job sheet records tied to these workshops
                $techUnassignedCount = WorkshopTechnicianAssignmentHistory::where('job_sheet_id', $request->job_sheet_id)
                    ->where('assignment_type', 'job_sheet')
                    ->whereIn('workshop_id', $request->workshop_ids)
                    ->whereNotNull('user_id')
                    ->where('status', 'assigned')
                    ->update([
                        'status' => 'unassigned',
                        'notes' => $request->notes ?? 'Technician unassigned due to workshop removal from job sheet',
                        'updated_at' => now()
                    ]);

                // Update job sheet workshops
                $currentWorkshops = json_decode($jobSheet->workshops ?? '[]', true) ?: [];
                $remainingWorkshops = array_values(array_diff($currentWorkshops, $request->workshop_ids));

                DB::table('repair_job_sheets')
                    ->where('id', $request->job_sheet_id)
                    ->update([
                        'workshops' => json_encode($remainingWorkshops),
                        'updated_at' => now()
                    ]);

                $this->logActivity('workshops_unassigned', [
                    'job_sheet_id' => $request->job_sheet_id,
                    'workshop_ids' => $request->workshop_ids,
                    'unassigned_by' => $current_user->id
                ]);

            } else { // technician_from_workshop
                // Remove technicians from workshop
                $workshop = DB::table('workshops')
                    ->where('business_id', $business_id)
                    ->where('id', $request->workshop_id)
                    ->first();

                if (!$workshop) {
                    return response()->json(['error' => 'Workshop not found'], 404);
                }

                // Unassign technicians
                $unassignedCount = WorkshopTechnicianAssignmentHistory::where('workshop_id', $request->workshop_id)
                    ->where('assignment_type', 'workshop')
                    ->whereIn('user_id', $request->technician_ids)
                    ->whereDate('created_at', Carbon::today())
                    ->where('status', 'assigned')
                    ->update([
                        'status' => 'unassigned',
                        'notes' => $request->notes ?? 'Technician unassigned from workshop',
                        'updated_at' => now()
                    ]);

                $this->logActivity('technicians_unassigned', [
                    'workshop_id' => $request->workshop_id,
                    'technician_ids' => $request->technician_ids,
                    'unassigned_count' => $unassignedCount,
                    'unassigned_by' => $current_user->id,
                    'timestamp' => now()->toISOString()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assignments removed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AssignController@destroy: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to remove assignments'], 500);
        }
    }

   
    public function availableTechnicians(Request $request)
    {
        try {
            $location_id = auth()->user()->location_id;
            $workshop_id = $request->get('workshop_id');
            $date = $request->get('date', now()->toDateString());

            $latestAttendancePerUser = DB::table('essentials_attendances')
                ->select('user_id', DB::raw('MAX(id) as latest_attendance_id'))
                ->whereDate('clock_in_time', $date)
      
                ->whereNull('clock_out_time')
              
                ->groupBy('user_id');

            // Ensure a single active workshop assignment per user by joining latest active record
            $latestWorkshopAssignmentPerUser = DB::table('workshop_assignments')
                ->select('user_id', DB::raw('MAX(id) as latest_wtah_id'))
                ->where('assignment_type', 'workshop')
                ->where('status', 'assigned')
                ->groupBy('user_id');

            // Only include technicians with attendance on the given date
            $technicians = DB::table('users')
                ->joinSub($latestAttendancePerUser, 'latest_attendance', function ($join) {
                    $join->on('users.id', '=', 'latest_attendance.user_id');
                })
                ->join('essentials_attendances as attendance', 'attendance.id', '=', 'latest_attendance.latest_attendance_id')
                ->leftJoinSub($latestWorkshopAssignmentPerUser, 'latest_wtah', function ($join) {
                    $join->on('users.id', '=', 'latest_wtah.user_id');
                })
                ->leftJoin('workshop_assignments as current_wtah', 'current_wtah.id', '=', 'latest_wtah.latest_wtah_id')
                ->leftJoin('workshops as assigned_workshop', 'current_wtah.workshop_id', '=', 'assigned_workshop.id')
        
                ->where('users.allow_login', 0)
                ->where('users.user_type', 'user')
                ->whereNull('users.deleted_at')
                ->select([
                    'users.id',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                    'users.location_id',
                    'attendance.clock_in_time',
                    'attendance.clock_out_time',
                    'current_wtah.workshop_id as assigned_workshop_id',
                    'assigned_workshop.name as assigned_workshop_name',
                    'current_wtah.created_at as workshop_assigned_at'
                ])
                ->get();

            // Prefetch job sheet counts for technicians in one query to avoid N+1
            $userIds = $technicians->pluck('id')->unique()->values();
            $jobSheetCounts = $userIds->isEmpty() ? collect() : DB::table('workshop_assignments')
                ->whereIn('user_id', $userIds)
                ->where('assignment_type', 'job_sheet')
                ->where('status', 'assigned')
                ->select('user_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $availableTechnicians = [];
            $assignedTechnicians = [];
            $unavailableTechnicians = [];

            foreach ($technicians as $technician) {
                $isPresent = !is_null($technician->clock_in_time) && is_null($technician->clock_out_time);
                $isAssigned = !is_null($technician->assigned_workshop_id);

                $technicianData = [
                    'id' => $technician->id,
                    'name' => trim($technician->first_name . ' ' . $technician->last_name),
                    'email' => $technician->email,
                    'location_id' => $technician->location_id,
                    'attendance' => [
                        'clock_in_time' => $technician->clock_in_time,
                        'clock_out_time' => $technician->clock_out_time,
                        'is_present' => $isPresent
                    ],
                    'current_assignment' => [
                        'workshop_id' => $technician->assigned_workshop_id,
                        'workshop_name' => $technician->assigned_workshop_name,
                        'assigned_at' => $technician->workshop_assigned_at
                    ],
                    'available_for_assignment' => !$isAssigned && $isPresent
                ];

                // Add job sheet count if assigned to a workshop
                if ($isAssigned) {
                    $countRecord = $jobSheetCounts->get($technician->id);
                    $technicianData['current_assignment']['job_sheet_count'] = $countRecord ? (int)$countRecord->cnt : 0;
                }

                // Categorize
                if ($technicianData['available_for_assignment']) {
                    $availableTechnicians[] = $technicianData;
                } elseif ($isAssigned) {
                    $assignedTechnicians[] = $technicianData;
                } else {
                    $unavailableTechnicians[] = $technicianData;
                }
            }

            // Filter assigned technicians by specific workshop if requested
            if ($workshop_id) {
                $assignedTechnicians = array_values(array_filter($assignedTechnicians, function ($tech) use ($workshop_id) {
                    return $tech['current_assignment']['workshop_id'] == $workshop_id;
                }));
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'available' => array_values($availableTechnicians),
                    'assigned' => array_values($assignedTechnicians),
                    'unavailable' => array_values($unavailableTechnicians),
                ],
            
            ]);

        } catch (\Exception $e) {
            \Log::error('AssignController@availableTechnicians: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve available technicians'], 500);
        }
    }
    /**
     * Get available workshops for assignment based on jobsheet services
     * Enhanced to filter transaction lines for services and retrieve referenced workshops
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function Fetch_Workshops_jobsheet(Request $request)
    {
        try {
       
            $location_id = $request->user()->location_id;
            $business_id = $request->user()->business_id;
        

            $request->validate([
                'job_sheet_id' => 'required|integer',
              
          
            ]);

            $job_sheet_id = $request->job_sheet_id;
            // Validate job sheet exists and belongs to this business
        $jobSheet = DB::table('repair_job_sheets')
            ->where('business_id', $business_id)
            ->where('id', $job_sheet_id)
            ->first();
            
        if (!$jobSheet) {
            return response()->json(['workshops' => []], 404);
        }
        
    
            // Derive workshops from product_joborder products linked to this job sheet
            $productIds = DB::table('product_joborder')
                ->where('job_order_id', $job_sheet_id)
                ->pluck('product_id')
                ->unique();

            if ($productIds->isEmpty()) {
                $workshopsFromServices = collect();
            } else {
                // Use pivot table to derive workshops from service products
                $pivot = DB::table('product_workshop as pw')
                    ->join('products as p', 'p.id', '=', 'pw.product_id')
                    ->whereIn('pw.product_id', $productIds)
                    ->where('p.enable_stock', 0)
                    ->select('pw.workshop_id', 'p.id as product_id', 'p.name as product_name')
                    ->get();

                $byWorkshop = $pivot->groupBy('workshop_id');
                $workshopIds = $byWorkshop->keys()->map(function ($id) { return (int)$id; })->values();

                $workshops = DB::table('workshops')
                    ->whereIn('id', $workshopIds)
                    ->where('status', 'available')
                    ->where('business_location_id', $location_id)
                    ->select('id', 'name', 'business_location_id', 'status')
                    ->get()
                    ->keyBy('id');

                $workshopsFromServices = $byWorkshop->map(function ($items, $workshopId) use ($workshops) {
                    $w = $workshops->get((int)$workshopId);
                    if (!$w) {
                        return null;
                    }
                    $productNames = collect($items)->pluck('product_name')->unique();
                    $serviceCount = collect($items)->pluck('product_id')->unique()->count();
                    return (object) [
                        'id' => $w->id,
                        'name' => $w->name,
                        'services' => $productNames->implode(', '),
                        'service_count' => $serviceCount,
                        'business_location_id' => $w->business_location_id,
                        'status' => $w->status
                    ];
                })->filter()->values();
            }

            // If no workshops found, return early
            if ($workshopsFromServices->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'workshops' => [],
                ]);
            }

            // Collect workshop IDs once
            $workshopIds = $workshopsFromServices->pluck('id')->values();

            $technicianHistories = WorkshopTechnicianAssignmentHistory::with(['technician:id,first_name,last_name,email'])
                ->ofType('workshop')
                ->active()
                ->whereIn('workshop_id', $workshopIds)
                ->get()
                ->groupBy('workshop_id');

            // Include user_id so we can check technician-level job_sheet assignments
            $jobSheetHistories = WorkshopTechnicianAssignmentHistory::query()
                ->ofType('job_sheet')
                ->active()
                ->whereIn('workshop_id', $workshopIds)
                ->get(['workshop_id', 'job_sheet_id', 'user_id', 'created_at'])
                ->groupBy('workshop_id');

            // Fetch job sheet details for all assigned job sheets in one query
            $allJobSheetIds = $jobSheetHistories->flatten()->pluck('job_sheet_id')->filter()->unique();
            $jobSheetsInfo = $allJobSheetIds->isEmpty() ? collect() : DB::table('repair_job_sheets')
                ->whereIn('id', $allJobSheetIds)
                ->select('id', 'job_sheet_no')
                ->get()
                ->keyBy('id');

            // Process workshops with assignment data
            $availableWorkshops = $workshopsFromServices->map(function ($workshop) use ($technicianHistories, $jobSheetHistories, $jobSheetsInfo, $job_sheet_id) {
                $technicians = $technicianHistories->get($workshop->id, collect());
                // job sheet assignment records for this specific workshop
                $jobSheetRecordsForWorkshop = $jobSheetHistories->get($workshop->id, collect());
                $assignedTechnicians = $technicians->unique('user_id')->map(function ($history) {
                    $technician = $history->technician;
                    return [
                        'user_id' => $history->user_id,
                        'name' => $technician ? trim(($technician->first_name ?? '') . ' ' . ($technician->last_name ?? '')) : null,
                        'email' => $technician ? $technician->email : null,
                        'assigned_at' => $history->created_at,
                        // default; we'll override below if this technician has a job_sheet assignment for this job
                        'is_assigned' => false,
                    ];
                })->filter(function ($technician) {
                    return !empty($technician['user_id']);
                })->values();

                $jobSheets = $jobSheetHistories->get($workshop->id, collect());
                $assignedJobSheets = $jobSheets->unique('job_sheet_id')->map(function ($history) use ($jobSheetsInfo) {
                    $jobSheet = $jobSheetsInfo->get($history->job_sheet_id);
                    return [
                        'id' => $history->job_sheet_id,
                        'job_sheet_no' => $jobSheet ? $jobSheet->job_sheet_no : null,
                        'assigned_at' => $history->created_at,
                    ];
                })->filter(function ($jobSheet) {
                    return !empty($jobSheet['id']);
                })->values();

                // Determine if this workshop already has the current job sheet assigned
                $isWorkshopAssigned = $jobSheetRecordsForWorkshop->contains('job_sheet_id', $job_sheet_id);

                // Annotate technicians with whether they are assigned to this job sheet
                $assignedTechnicians = $assignedTechnicians->map(function ($tech) use ($jobSheetRecordsForWorkshop, $job_sheet_id) {
                    $tech['is_assigned'] = $jobSheetRecordsForWorkshop->contains(function ($rec) use ($tech, $job_sheet_id) {
                        return isset($rec->job_sheet_id) && isset($rec->user_id)
                            && $rec->job_sheet_id == $job_sheet_id
                            && $rec->user_id == $tech['user_id'];
                    });
                    return $tech;
                })->values();

                return [
                    'id' => $workshop->id,
                    'name' => $workshop->name,
                    'location_id' => $workshop->business_location_id,
                    'status' => $workshop->status,
                    'services' => $workshop->services,
                    'service_count' => $workshop->service_count,
                    'assigned_technicians' => $assignedTechnicians,
                    'technician_count' => $assignedTechnicians->count(),
                    'assigned_job_sheets' => $assignedJobSheets,
                    'job_sheet_count' => $assignedJobSheets->count(),
                    'is_available' => $workshop->status === 'available',
                    // true if this workshop already has the requested job sheet
                    'is_assigned' => $isWorkshopAssigned,
                    'has_services' => $workshop->service_count > 0,
                ];
            });

            return response()->json([
                'success' => true,
                'workshops' => $availableWorkshops->values(),
            ]);
        } catch (\Exception $e) {
            Log::error('AssignController@availableWorkshops: ' . $e->getMessage(), [
                'job_sheet_id' => $request->job_sheet_id ?? null,
                'business_id' => $business_id ?? null,
                'location_id' => $location_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to retrieve available workshops'], 500);
        }
    }


    /**
     * Get all workshops with their assigned technicians
     * Includes validation to prevent duplicate assignments
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWorkshopsWithTechnicians(Request $request)
    {
        try {
            $business_id = auth()->user()->business_id;
            $location_id = auth()->user()->location_id;
            $date = $request->get('date', Carbon::today()->toDateString());


    

            // Get all workshops with their current technician assignments
            $workshopsQuery = DB::table('workshops')
                ->leftJoin('workshop_assignments as wtah', function($join) use ($date) {
                    $join->on('workshops.id', '=', 'wtah.workshop_id')
                         ->where('wtah.assignment_type', 'workshop')
                         ->where('wtah.status', 'assigned');
                })
                ->leftJoin('users', 'wtah.user_id', '=', 'users.id')
                ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('workshops.business_id', $business_id);

            if ($location_id) {
                $workshopsQuery->where('workshops.business_location_id', $location_id);
            }

        
    

            $workshops = $workshopsQuery->select([
                'workshops.id as workshop_id',
                'workshops.name as workshop_name',
                'workshops.status as workshop_status',
                'workshops.business_location_id',
                'users.id as technician_id',
                'users.first_name',
                'users.last_name',
                'users.email as technician_email',
                DB::raw('wtah.created_at as assigned_at'),
                'wtah.notes as assignment_notes'
            ])->get();

            // Group workshops and their technicians
            $groupedWorkshops = $workshops->groupBy('workshop_id')->map(function ($workshopData) {
                $firstWorkshop = $workshopData->first();
                
                $technicians = $workshopData->filter(function ($item) {
                    return !is_null($item->technician_id);
                })->map(function ($item) {
                    return [
                        'id' => $item->technician_id,
                        'name' => trim($item->first_name . ' ' . $item->last_name),
                        'email' => $item->technician_email,
                        'assigned_at' => $item->assigned_at,
                        'assignment_notes' => $item->assignment_notes
                    ];
                })->values();

                return [
                    'id' => $firstWorkshop->workshop_id,
                    'name' => $firstWorkshop->workshop_name,
                    'status' => $firstWorkshop->workshop_status,
                    'location_id' => $firstWorkshop->business_location_id,
                    'technicians' => $technicians,
                    'technician_count' => $technicians->count(),
               
                ];
            });

            // Get job sheet assignments for workshops to check for duplicate workshop-jobsheet assignments
            $workshopJobAssignments = DB::table('workshop_assignments')
                ->join('repair_job_sheets', 'workshop_assignments.job_sheet_id', '=', 'repair_job_sheets.id')
                ->where('workshop_assignments.assignment_type', 'job_sheet')
                ->where('workshop_assignments.status', 'assigned')
                ->whereIn('workshop_assignments.workshop_id', $groupedWorkshops->keys())
                ->select([
                    'workshop_assignments.workshop_id',
                    'workshop_assignments.job_sheet_id',
                    'repair_job_sheets.job_sheet_no'
                ])
                ->get()
                ->groupBy('workshop_id');

            // Add job sheet assignment information to workshops
            $finalWorkshops = $groupedWorkshops->map(function ($workshop) use ($workshopJobAssignments) {
                $jobAssignments = $workshopJobAssignments->get($workshop['id'], collect());
                
                $workshop['job_assignments'] = $jobAssignments->map(function ($assignment) {
                    return [
                        'job_sheet_id' => $assignment->job_sheet_id,
                        'job_sheet_no' => $assignment->job_sheet_no
                    ];
                })->values();

                $workshop['assignment_validation']['assigned_job_sheet_ids'] = $jobAssignments->pluck('job_sheet_id')->toArray();
                $workshop['assignment_validation']['can_assign_to_job_sheets'] = true; // Workshops can be assigned to multiple job sheets

                return $workshop;
            });

            return response()->json([
                'success' => true,
                'data' => $finalWorkshops->values(),
               
               
            ]);

        } catch (\Exception $e) {
            Log::error('AssignController@getWorkshopsWithTechnicians: ' . $e->getMessage(), [
                'business_id' => $business_id ?? null,
                'location_id' => $location_id ?? null,
                'date' => $date ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to retrieve workshops with technicians'], 500);
        }
    }




    /**
     * Unassign technicians from workshop
     * This function detaches technicians from a workshop without removing them
     * by setting the unassigned_at timestamp
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unassignTechnicians(Request $request)
    {
        try {
            $business_id = $request->user()->business_id;
            $current_user = $request->user();

            // Validate request parameters
            $request->validate([
                'workshop_id' => 'required|integer|exists:workshops,id',
                'technician_ids' => 'required|array|min:1',
                'technician_ids.*' => 'integer|exists:users,id',
                'notes' => 'nullable|string|max:500',
                'date' => 'nullable|date'
            ]);

            $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

            DB::beginTransaction();

            // Verify workshop belongs to the business
            $workshop = DB::table('workshops')
                ->where('business_id', $business_id)
                ->where('id', $request->workshop_id)
                ->first();

            if (!$workshop) {
                return response()->json(['error' => 'Workshop not found or access denied'], 404);
            }

            // Verify technicians belong to the business
            $validTechnicians = DB::table('users')
                ->where('business_id', $business_id)
                ->whereIn('id', $request->technician_ids)
                ->pluck('id')
                ->toArray();

            if (count($validTechnicians) !== count($request->technician_ids)) {
                $invalidTechnicians = array_diff($request->technician_ids, $validTechnicians);
                return response()->json([
                    'error' => 'Some technicians not found or access denied',
                    'invalid_technician_ids' => $invalidTechnicians
                ], 400);
            }

            // Unassign technicians from workshop (do not restrict by assignment creation date)
            $unassignedCount = WorkshopTechnicianAssignmentHistory::where('workshop_id', $request->workshop_id)
                ->where('assignment_type', 'workshop')
                ->whereIn('user_id', $request->technician_ids)
                ->where('status', 'assigned')
                ->update([
                    'status' => 'unassigned',
                    'notes' => $request->notes ?? 'Technician unassigned from workshop via API',
                    'updated_at' => now()
                ]);



            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully unassigned {$unassignedCount} technician(s) from workshop",
                'data' => [
                    'workshop_id' => $request->workshop_id,
                    'unassigned_count' => $unassignedCount,
                    'date' => $date->toDateString()
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to unassign technicians'], 500);
        }
    }


    /**
     * Log activity with enhanced real-time data
     */
    private function logActivity($action, $data)
    {
        try {
            // Enhanced logging with real-time context
            $logData = array_merge($data, [
                'action' => $action,
                'logged_at' => now()->toISOString(),
                'real_time_processing' => true,
                'cache_disabled' => true
            ]);
            
            Log::info("Assignment Activity (Real-time): {$action}", $logData);
        } catch (\Exception $e) {
            // Silently fail logging to not interrupt main process
            Log::error('Failed to log assignment activity: ' . $e->getMessage());
        }
    }
}