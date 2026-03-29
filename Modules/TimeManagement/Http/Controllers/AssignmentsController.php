<?php

namespace Modules\TimeManagement\Http\Controllers;

use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\TimeManagement\Services\TimeMetricsService;
use Modules\TimeManagement\Entities\WorkshopTechnicianAssignmentHistory;
use Carbon\Carbon;

class AssignmentsController extends Controller
{
    public function index(Request $request, TimeMetricsService $metrics)
    {
        $business_id = $request->session()->get('user.business_id');
        $filters = $request->only(['workshop_id', 'location_id', 'start_date', 'end_date']);

        $workshops = DB::table('workshops')
            ->where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');
        $locations = BusinessLocation::forDropdown($business_id, false, false, false, true);

        // Get all workers and filter by attendance for today
        $allWorkers = $metrics->getWorkersStatus($business_id, $filters)->values();
        
        // Filter workers to only include those who are present today (attended)
        $workers = $allWorkers->filter(function ($worker) {
            return $worker->present === true;
        });

        $today = Carbon::today();
        $workerIds = $workers->pluck('user_id')->toArray();

        // Fetch current workshop assignment for each technician
        $currentAssignments = WorkshopTechnicianAssignmentHistory::getCurrentAssignments($workerIds, $today);

        // Attach current workshop to each worker
        $workers = $workers->map(function ($worker) use ($currentAssignments) {
            $worker->current_workshop = $currentAssignments[$worker->user_id] ?? null;
            return $worker;
        });

        // Get workshops with their assigned technicians for attendance actions
        $workshopTechnicians = $this->getWorkshopTechnicians($business_id, $today);

        $jobs = collect($metrics->getActiveJobs(
            $business_id,
            $filters['workshop_id'] ?? null,
            $filters['location_id'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        ));

        return view('timemanagement::assignments.index', compact('workshops', 'locations', 'workers', 'workshopTechnicians', 'jobs'))
            ->with($filters);
    }
    public function assign(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $data = $request->validate([
            'job_sheet_id' => ['required', 'integer'],
            'workshop_ids' => ['required', 'array', 'min:1'],
            'workshop_ids.*' => ['integer'],
        ]);

        $job = DB::table('repair_job_sheets')
            ->where('business_id', $business_id)
            ->where('id', $data['job_sheet_id'])
            ->first();

        if (!$job) {
            return response()->json(['status' => 'error', 'message' => __('messages.something_went_wrong')], 404);
        }

        // Validate that all workshop IDs exist and belong to this business
        $validWorkshopIds = DB::table('workshops')
            ->where('business_id', $business_id)
            ->whereIn('id', $data['workshop_ids'])
            ->pluck('id')
            ->all();

        if (empty($validWorkshopIds)) {
            return response()->json(['status' => 'error', 'message' => __('messages.something_went_wrong')], 404);
        }

        $current_user = $request->user();

        // Fetch existing assigned job sheet workshop-user combinations to avoid duplicates
        $existingAssignments = WorkshopTechnicianAssignmentHistory::where('job_sheet_id', $data['job_sheet_id'])
            ->where('assignment_type', 'job_sheet')
            ->where('status', 'assigned')
            ->get()
            ->keyBy(function ($assignment) {
                return ($assignment->workshop_id ?? 'w') . '_' . ($assignment->user_id ?? 'u');
            });

        // Create workshop-to-job sheet assignments and technician-level job sheet assignments
        foreach ($validWorkshopIds as $workshopId) {
            // Create or ensure workshop-level job sheet assignment (without user)
            $existsWorkshopLevel = WorkshopTechnicianAssignmentHistory::where('job_sheet_id', $data['job_sheet_id'])
                ->where('assignment_type', 'job_sheet')
                ->where('status', 'assigned')
                ->where('workshop_id', $workshopId)
                ->whereNull('user_id')
                ->exists();

            if (!$existsWorkshopLevel) {
                WorkshopTechnicianAssignmentHistory::create([
                    'workshop_id' => $workshopId,
                    'job_sheet_id' => $data['job_sheet_id'],
                    'assigned_by' => $current_user->id,
                    'assignment_type' => 'job_sheet',
                    'status' => 'assigned',
                    'notes' => $request->notes ?? 'Workshop assigned to job sheet',
                    'created_at' => now(),
                    'metadata' => ['source' => 'job_sheet_assignment']
                ]);
            }

            // Create technician-level job sheet assignments for technicians currently assigned to this workshop
            $activeTechnicians = WorkshopTechnicianAssignmentHistory::where('workshop_id', $workshopId)
                ->where('assignment_type', 'workshop')
                ->where('status', 'assigned')
                ->select('user_id')
                ->distinct()
                ->pluck('user_id');

            foreach ($activeTechnicians as $techId) {
                $key = $workshopId . '_' . $techId;
                if ($existingAssignments->has($key)) {
                    continue;
                }

                WorkshopTechnicianAssignmentHistory::create([
                    'workshop_id' => $workshopId,
                    'job_sheet_id' => $data['job_sheet_id'],
                    'user_id' => $techId,
                    'assigned_by' => $current_user->id,
                    'assignment_type' => 'job_sheet',
                    'status' => 'assigned',
                    'notes' => $request->notes ?? 'Technician assigned to job sheet via workshop assignment',
                    'created_at' => now(),
                    'metadata' => ['source' => 'workshop_to_job_sheet']
                ]);
            }
        }

        // Update job sheet with assigned workshops (merge with existing)
        $currentWorkshops = json_decode($job->workshops ?? '[]', true) ?: [];
        $mergedWorkshops = array_values(array_unique(array_merge($currentWorkshops, $validWorkshopIds)));
        DB::table('repair_job_sheets')
            ->where('id', $job->id)
            ->update([
                'workshops' => json_encode($mergedWorkshops),
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => 'success',
            'message' => __('lang_v1.success'),
        ]);
    }

    public function unassign(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $data = $request->validate([
            'job_sheet_id' => ['required', 'integer'],
            'workshop_ids' => ['required', 'array', 'min:1'],
            'workshop_ids.*' => ['integer'],
        ]);

        $job = DB::table('repair_job_sheets')
            ->where('business_id', $business_id)
            ->where('id', $data['job_sheet_id'])
            ->first();

        if (!$job) {
            return response()->json(['status' => 'error', 'message' => __('messages.something_went_wrong')], 404);
        }

        $current = json_decode($job->workshops ?? '[]', true) ?: [];
        $toRemove = $data['workshop_ids'];
        $remaining = array_values(array_diff($current, $toRemove));

        $current_user = $request->user();

        // Unassign workshops from job sheet in the assignment history table
        foreach ($toRemove as $workshopId) {
            WorkshopTechnicianAssignmentHistory::where('workshop_id', $workshopId)
                ->where('job_sheet_id', $data['job_sheet_id'])
                ->where('assignment_type', 'job_sheet')
                ->where('status', 'assigned')
                ->update([
                    'status' => 'unassigned',
                    'notes' => $request->notes ?? 'Workshop unassigned from job sheet',
                    'updated_at' => now()
                ]);

            // Also unassign technician-level job sheet records tied to these workshops
            WorkshopTechnicianAssignmentHistory::where('workshop_id', $workshopId)
                ->where('job_sheet_id', $data['job_sheet_id'])
                ->where('assignment_type', 'job_sheet')
                ->whereNotNull('user_id')
                ->where('status', 'assigned')
                ->update([
                    'status' => 'unassigned',
                    'notes' => $request->notes ?? 'Technician unassigned due to workshop removal from job sheet',
                    'updated_at' => now()
                ]);
        }

        // Update job sheet with remaining workshops
        DB::table('repair_job_sheets')
            ->where('id', $job->id)
            ->update([
                'workshops' => json_encode($remaining),
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => 'success',
            'message' => __('lang_v1.success'),
        ]);
    }

    public function list(Request $request, TimeMetricsService $metrics)
    {
        $business_id = $request->session()->get('user.business_id');
        $filters = $request->only(['workshop_id', 'location_id', 'start_date', 'end_date']);

        // Get all workers and filter by attendance for today
        $allWorkers = $metrics->getWorkersStatus($business_id, $filters)->values();
        
        // Filter workers to only include those who are present today (attended)
        $workers = $allWorkers->filter(function ($worker) {
            return $worker->present === true;
        });
        
        $today = Carbon::today();
        $workerIds = $workers->pluck('user_id')->toArray();

        $currentAssignments = WorkshopTechnicianAssignmentHistory::getCurrentAssignments($workerIds, $today);

        $workers = $workers->map(function ($worker) use ($currentAssignments) {
            $worker->current_workshop = $currentAssignments[$worker->user_id] ?? null;
            return $worker;
        });

        $jobs = collect($metrics->getActiveJobs(
            $business_id,
            $filters['workshop_id'] ?? null,
            $filters['location_id'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        ));

        $perPage = (int) $request->input('per_page', 10);
        $wPage = max(1, (int) $request->input('worker_page', 1));
        $jPage = max(1, (int) $request->input('job_page', 1));

        $wTotal = $workers->count();
        $jTotal = $jobs->count();

        $workersPage = $workers->slice(($wPage - 1) * $perPage, $perPage)->values();
        $jobsPage = $jobs->slice(($jPage - 1) * $perPage, $perPage)->values();

        return response()->json([
            'workers' => $workersPage,
            'jobs' => $jobsPage,
            'pagination' => [
                'workers' => [
                    'page' => $wPage,
                    'per_page' => $perPage,
                    'total' => $wTotal,
                    'has_more' => ($wPage * $perPage) < $wTotal,
                ],
                'jobs' => [
                    'page' => $jPage,
                    'per_page' => $perPage,
                    'total' => $jTotal,
                    'has_more' => ($jPage * $perPage) < $jTotal,
                ],
            ],
        ]);
    }

    public function assignWorkshop(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'workshop_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string']
        ]);

        $user_id = (int) $request->user_id;
        $workshop_id = (int) $request->workshop_id;
        $today = Carbon::today();
        $current_user = $request->user();

        // Check if technician is already assigned to this workshop today
        $existingAssignment = WorkshopTechnicianAssignmentHistory::where('user_id', $user_id)
            ->where('workshop_id', $workshop_id)
            ->where('assignment_type', 'workshop')
            ->where('status', 'assigned')
            ->whereDate('created_at', $today)
            ->first();

        if ($existingAssignment) {
            return response()->json(['status' => 'error', 'message' => 'Technician is already assigned to this workshop'], 422);
        }

        // Create new assignment (do not forcibly close other workshops)
        WorkshopTechnicianAssignmentHistory::create([
            'workshop_id' => $workshop_id,
            'user_id' => $user_id,
            'assigned_by' => $current_user->id,
            'assignment_type' => 'workshop',
            'status' => 'assigned',
            'notes' => $request->notes,
            'created_at' => Carbon::now(),
            'metadata' => []
        ]);

        return response()->json(['status' => 'success', 'message' => 'Technician workshop updated']);
    }

    /**
     * List a technician's workshop assignments for a given date (default today).
     */
    public function technicianWorkshopAssignments(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'date' => ['nullable', 'date']
        ]);

        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        $rows = WorkshopTechnicianAssignmentHistory::with('workshop')
            ->where('user_id', $request->user_id)
            ->whereDate('created_at', $date)
            ->orderBy('created_at')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'workshop_id' => $assignment->workshop_id,
                    'workshop_name' => $assignment->workshop->name ?? null,
                    'assigned_at' => $assignment->created_at,
                    'unassigned_at' => $assignment->status === 'unassigned' ? $assignment->updated_at : null,
                    'notes' => $assignment->notes,
                    'assigned_by' => $assignment->assignedBy->name ?? null,
                    'status' => $assignment->status,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    /**
     * Unassign a technician from their current workshop
     */
    public function unassignWorkshop(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string']
        ]);

        $user_id = (int) $request->user_id;
        $today = Carbon::today();
        $current_user = $request->user();

        // Find current active assignment
        $currentAssignment = WorkshopTechnicianAssignmentHistory::where('user_id', $user_id)
            ->where('assignment_type', 'workshop')
            ->where('status', 'assigned')
            ->whereDate('created_at', $today)
            ->first();

        if (!$currentAssignment) {
            return response()->json(['status' => 'error', 'message' => 'Technician is not currently assigned to any workshop'], 404);
        }

        // Close the assignment
        $currentAssignment->update([
            'status' => 'unassigned',
            'notes' => $request->notes ?: 'Unassigned from workshop',
            'updated_at' => now()
        ]);

        return response()->json(['status' => 'success', 'message' => 'Technician unassigned from workshop']);
    }

    /**
     * Get assignment history for a technician
     */
    public function assignmentHistory(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date']
        ]);

        $query = WorkshopTechnicianAssignmentHistory::with(['workshop', 'assignedBy'])
            ->forTechnician($request->user_id);

        if ($request->start_date && $request->end_date) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        $history = $query->orderBy('created_at', 'desc')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'workshop' => ['name' => $item->workshop->name ?? null],
                'assigned_by' => ['name' => $item->assignedBy->name ?? null],
                'notes' => $item->notes,
                'status' => $item->status,
                'assigned_at' => $item->created_at,
                'unassigned_at' => $item->status === 'unassigned' ? $item->updated_at : null,
            ];
        });

        return response()->json(['data' => $history]);
    }

    /**
     * Get workshops based on job sheet services (products with enable_stock = 0)
     */
    public function getWorkshopsByJobSheet(Request $request, $job_sheet_id)
    {
        $business_id = $request->session()->get('user.business_id');
        
        // Validate job sheet exists and belongs to this business
        $jobSheet = DB::table('repair_job_sheets')
            ->where('business_id', $business_id)
            ->where('id', $job_sheet_id)
            ->first();
            
        if (!$jobSheet) {
            return response()->json(['workshops' => []], 404);
        }
        
        // Get workshops linked to service products in transaction lines for this job sheet (via pivot)
        $workshops = DB::table('workshops as w')
            ->join('product_workshop as pw', 'pw.workshop_id', '=', 'w.id')
            ->join('products as p', 'p.id', '=', 'pw.product_id')
            ->join('transaction_sell_lines as tsl', 'tsl.product_id', '=', 'p.id')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->where('t.repair_job_sheet_id', $job_sheet_id)
            ->where('p.enable_stock', 0) // Service products only
            ->select('w.id', 'w.name')
            ->distinct()
            ->orderBy('w.name')
            ->get();
            
        // Format for response
        $workshopsArray = [];
        foreach ($workshops as $workshop) {
            $workshopsArray[$workshop->id] = $workshop->name;
        }
        
        return response()->json(['workshops' => $workshopsArray]);
    }

    private function getWorkshopTechnicians($business_id, $date)
    {
        // Get all workshops for the business
        $workshops = DB::table('workshops')
            ->where('business_id', $business_id)
            ->orderBy('name')
            ->get();

        // Get current technician assignments for today
        $currentAssignments = WorkshopTechnicianAssignmentHistory::with(['technician', 'workshop'])
            ->where('assignment_type', 'workshop')
            ->where('status', 'assigned')
            ->whereDate('created_at', $date)
            ->get()
            ->groupBy('workshop_id');

        // Enhance workshops with their assigned technicians
        return $workshops->map(function ($workshop) use ($currentAssignments) {
            $workshop->assigned_technicians = $currentAssignments->get($workshop->id, collect())->pluck('technician')->values();
            $workshop->technician_count = $workshop->assigned_technicians->count();
            $workshop->status = $workshop->technician_count > 0 ? 'Active' : 'Inactive';
            return $workshop;
        });
    }
}