<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Repair\Entities\JobSheet;
use Spatie\Permission\Models\Role;

class TechnicianAttendanceController extends ApiController
{
    /**
     * Get all technicians with their attendance status and job assignments
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        
        // Get all service staff (technicians)
        $technicians = $this->getTechnicians($businessId, $user);
        
        if ($technicians->isEmpty()) {
            return response()->json([
                'data' => [],
                'message' => 'No technicians found'
            ]);
        }
        
        $technicianIds = $technicians->pluck('id')->toArray();
        
        // Get attendance records for the date
        $attendanceRecords = $this->getAttendanceRecords($businessId, $technicianIds, $date);
        
        // Get absence records for the date
        $absenceRecords = $this->getAbsenceRecords($businessId, $technicianIds, $date);
        
        // Get job assignments for the date
        $jobAssignments = $this->getJobAssignments($businessId, $technicianIds, $date);
        
        // Build response data
        $result = [];
        foreach ($technicians as $technician) {
            $attendance = $attendanceRecords->get($technician->id);
            $absence = $absenceRecords->get($technician->id);
            $jobs = $jobAssignments->get($technician->id, collect());
            
            $attendanceStatus = $this->determineAttendanceStatus($attendance, $absence);
            
            $result[] = [
                'user_id' => $technician->id,
                'name' => trim($technician->first_name . ' ' . $technician->last_name),
                'email' => $technician->email,
                
                'role' => $technician->role_name,
                'location_id' => $technician->location_id,
                'attendance_status' => $attendanceStatus,
                'attendance_details' => $this->formatAttendanceDetails($attendance, $absence),
                'job_assignments' => $jobs->map(function ($job) {
                    return [
                        'job_sheet_id' => $job->id,
                        'job_sheet_no' => $job->job_sheet_no,
                        'customer_name' => $job->customer_name,
                        'status' => $job->status_name,
                        'assigned_at' => $job->created_at,
                    ];
                })->toArray(),
                'job_count' => $jobs->count(),
                'date' => $date,
            ];
        }
        
        $col = collect($result);
        $presentCount = $col->filter(function ($r) {
            return in_array($r['attendance_status'], ['present', 'completed']);
        })->count();

        return response()->json([
            'data' => $result,
            'meta' => [
                'total_technicians' => count($result),
                'date' => $date,
                'present_count' => $presentCount,
                'absent_count' => $col->where('attendance_status', 'absent')->count(),
                'on_leave_count' => $col->where('attendance_status', 'on_leave')->count(),
            ]
        ]);
    }
    
    /**
     * Clock in a technician
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clockIn(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'clock_in_time' => 'nullable|date',
            'note' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:45',
        ]);
        
        $user = Auth::user();
        $businessId = $user->business_id;
        
        // Validate technician belongs to business
        $this->validateTechnician($data['user_id'], $businessId);
        
        $clockInTime = $data['clock_in_time'] ?? Carbon::now();
        $date = Carbon::parse($clockInTime)->format('Y-m-d');
        
        // Check if already clocked in today
        $existingAttendance = EssentialsAttendance::where('user_id', $data['user_id'])
            ->where('business_id', $businessId)
            ->whereDate('clock_in_time', $date)
            ->first();
            
        if ($existingAttendance) {
            return response()->json([
                'error' => 'Technician already clocked in today',
                'existing_record' => [
                    'id' => $existingAttendance->id,
                    'clock_in_time' => $existingAttendance->clock_in_time,
                    'clock_out_time' => $existingAttendance->clock_out_time,
                ]
            ], 400);
        }
        
        // Create attendance record
        $attendance = EssentialsAttendance::create([
            'user_id' => $data['user_id'],
            'business_id' => $businessId,
            'clock_in_time' => $clockInTime,
            'clock_in_note' => $data['note'] ?? null,
            'ip_address' => $data['ip_address'] ?? $request->ip(),
        ]);
        
        return response()->json([
            'message' => 'Successfully clocked in',
            'data' => [
                'id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'clock_in_time' => $attendance->clock_in_time,
                'clock_in_note' => $attendance->clock_in_note,
                'ip_address' => $attendance->ip_address,
            ]
        ], 201);
    }
    
    /**
     * Clock out a technician
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clockOut(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'clock_out_time' => 'nullable|date',
            'note' => 'nullable|string|max:255',
        ]);
        
        $user = Auth::user();
        $businessId = $user->business_id;
        
        // Validate technician belongs to business
        $this->validateTechnician($data['user_id'], $businessId);
        
        $clockOutTime = $data['clock_out_time'] ?? Carbon::now();
        $date = Carbon::parse($clockOutTime)->format('Y-m-d');
        
        // Find today's attendance record
        $attendance = EssentialsAttendance::where('user_id', $data['user_id'])
            ->where('business_id', $businessId)
            ->whereDate('clock_in_time', $date)
            ->whereNull('clock_out_time')
            ->first();
            
        if (!$attendance) {
            return response()->json([
                'error' => 'No active clock-in record found for today'
            ], 400);
        }
        
        // Update with clock out time
        $attendance->update([
            'clock_out_time' => $clockOutTime,
            'clock_out_note' => $data['note'] ?? null,
        ]);

        // Pause all active timers for this technician across the business
        $pauseTime = Carbon::parse($clockOutTime);
        DB::table('timer_tracking')
     
            ->where('user_id', $data['user_id'])
            ->where('status', 'active')
            ->update([
                'status' => 'paused',
                'paused_at' => $pauseTime,
                'updated_at' => $pauseTime,
            ]);
        
        // Calculate worked hours
        $workedHours = Carbon::parse($attendance->clock_in_time)
            ->diffInHours(Carbon::parse($attendance->clock_out_time));
        
        return response()->json([
            'message' => 'Successfully clocked out',
            'data' => [
                'id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'clock_in_time' => $attendance->clock_in_time,
                'clock_out_time' => $attendance->clock_out_time,
                'clock_out_note' => $attendance->clock_out_note,
                'worked_hours' => $workedHours,
            ]
        ]);
    }
    
    /**
     * Bulk clock in multiple technicians
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkClockIn(Request $request)
    {
        $data = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'clock_in_time' => 'nullable|date',
            'note' => 'nullable|string|max:255',
        ]);
        
        $user = Auth::user();
        $businessId = $user->business_id;
        $clockInTime = $data['clock_in_time'] ?? Carbon::now();
        $date = Carbon::parse($clockInTime)->format('Y-m-d');
        
        $results = [];
        $errors = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($data['user_ids'] as $userId) {
                try {
                    // Validate technician
                    $this->validateTechnician($userId, $businessId);
                    
                    // Check if already clocked in
                    $existing = EssentialsAttendance::where('user_id', $userId)
                        ->where('business_id', $businessId)
                        ->whereDate('clock_in_time', $date)
                        ->exists();
                        
                    if ($existing) {
                        $errors[] = [
                            'user_id' => $userId,
                            'error' => 'Already clocked in today'
                        ];
                        continue;
                    }
                    
                    // Create attendance record
                    $attendance = EssentialsAttendance::create([
                        'user_id' => $userId,
                        'business_id' => $businessId,
                        'clock_in_time' => $clockInTime,
                        'clock_in_note' => $data['note'] ?? 'Bulk clock in',
                        'ip_address' => $request->ip(),
                    ]);
                    
                    $results[] = [
                        'user_id' => $userId,
                        'attendance_id' => $attendance->id,
                        'clock_in_time' => $attendance->clock_in_time,
                    ];
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'user_id' => $userId,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Bulk clock in completed',
                'successful_count' => count($results),
                'error_count' => count($errors),
                'results' => $results,
                'errors' => $errors,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Bulk clock in failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk clock out multiple technicians
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkClockOut(Request $request)
    {
        $data = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'clock_out_time' => 'nullable|date',
            'note' => 'nullable|string|max:255',
        ]);
        
        $user = Auth::user();
        $businessId = $user->business_id;
        $clockOutTime = $data['clock_out_time'] ?? Carbon::now();
        $date = Carbon::parse($clockOutTime)->format('Y-m-d');
        
        $results = [];
        $errors = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($data['user_ids'] as $userId) {
                try {
                    // Validate technician
                    $this->validateTechnician($userId, $businessId);
                    
                    // Find active attendance record
                    $attendance = EssentialsAttendance::where('user_id', $userId)
                        ->where('business_id', $businessId)
                        ->whereDate('clock_in_time', $date)
                        ->whereNull('clock_out_time')
                        ->first();
                        
                    if (!$attendance) {
                        $errors[] = [
                            'user_id' => $userId,
                            'error' => 'No active clock-in record found'
                        ];
                        continue;
                    }
                    
                    // Update with clock out
                    $attendance->update([
                        'clock_out_time' => $clockOutTime,
                        'clock_out_note' => $data['note'] ?? 'Bulk clock out',
                    ]);
                    
                    $results[] = [
                        'user_id' => $userId,
                        'attendance_id' => $attendance->id,
                        'clock_out_time' => $attendance->clock_out_time,
                    ];
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'user_id' => $userId,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Bulk clock out completed',
                'successful_count' => count($results),
                'error_count' => count($errors),
                'results' => $results,
                'errors' => $errors,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Bulk clock out failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get attendance history with filtering
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        
        $user = Auth::user();
        $businessId = $user->business_id;
        $perPage = $data['per_page'] ?? 15;
        
        $query = EssentialsAttendance::query()
            ->join('users', 'users.id', '=', 'essentials_attendances.user_id')
            ->where('essentials_attendances.business_id', $businessId);
            
        // Apply technician role filter
        $query->whereExists(function ($subQuery) use ($businessId) {
            $subQuery->select(DB::raw(1))
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereColumn('model_has_roles.model_id', 'users.id')
                ->where('model_has_roles.model_type', User::class)
                ->where('roles.business_id', $businessId)
                ->where('roles.is_service_staff', 1);
        });
        
        // Apply location filter if needed
        $this->applyLocationFilter($query, $user, $businessId);
        
        // Apply filters
        if (!empty($data['user_id'])) {
            $this->validateTechnician($data['user_id'], $businessId);
            $query->where('essentials_attendances.user_id', $data['user_id']);
        }
        
        if (!empty($data['from_date'])) {
            $query->whereDate('essentials_attendances.clock_in_time', '>=', $data['from_date']);
        }
        
        if (!empty($data['to_date'])) {
            $query->whereDate('essentials_attendances.clock_in_time', '<=', $data['to_date']);
        }
        
        $query->select([
            'essentials_attendances.*',
            DB::raw("TRIM(CONCAT_WS(' ', users.first_name, users.last_name)) as technician_name"),
            'users.email',
            'users.mobile'
        ])->orderBy('essentials_attendances.clock_in_time', 'desc');
        
        $results = $query->paginate($perPage);
        
        $results->getCollection()->transform(function ($record) {
            $clockIn = Carbon::parse($record->clock_in_time);
            $clockOut = $record->clock_out_time ? Carbon::parse($record->clock_out_time) : null;
            
            return [
                'id' => $record->id,
                'user_id' => $record->user_id,
                'technician_name' => $record->technician_name,
                'email' => $record->email,
                'mobile' => $record->mobile,
                'date' => $clockIn->format('Y-m-d'),
                'clock_in_time' => $record->clock_in_time,
                'clock_out_time' => $record->clock_out_time,
                'clock_in_note' => $record->clock_in_note,
                'clock_out_note' => $record->clock_out_note,
                'worked_hours' => $clockOut ? $clockIn->diffInHours($clockOut) : null,
                'worked_minutes' => $clockOut ? $clockIn->diffInMinutes($clockOut) : null,
                'status' => $clockOut ? 'completed' : 'active',
                'ip_address' => $record->ip_address,
                'created_at' => $record->created_at,
            ];
        });
        
        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
            ],
            'links' => [
                'first' => $results->url(1),
                'last' => $results->url($results->lastPage()),
                'prev' => $results->previousPageUrl(),
                'next' => $results->nextPageUrl(),
            ]
        ]);
    }
    
    /**
     * Get technicians for the business
     */
    private function getTechnicians($businessId, $user)
    {
        $query = DB::table('users')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('users.location_id', $user->location_id)
            ->where('users.allow_login', 0)
            ->where('users.user_type', "user")
            ->whereNull('users.deleted_at')
          
            ->select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.location_id',
                'roles.name as role_name'
            ]);
            
        // $this->applyLocationFilter($query, $user, $businessId);
        
        return $query->get();
    }
    
    /**
     * Get attendance records for technicians on a specific date
     */
    private function getAttendanceRecords($businessId, $technicianIds, $date)
    {
        return EssentialsAttendance::where('business_id', $businessId)
            ->whereIn('user_id', $technicianIds)
            ->whereDate('clock_in_time', $date)
            ->get()
            ->keyBy('user_id');
    }
    
    /**
     * Get absence records for technicians on a specific date
     */
    private function getAbsenceRecords($businessId, $technicianIds, $date)
    {
        return EssentialsLeave::where('business_id', $businessId)
            ->whereIn('user_id', $technicianIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get()
            ->keyBy('user_id');
    }
    
    /**
     * Get job assignments for technicians on a specific date
     */
    private function getJobAssignments($businessId, $technicianIds, $date)
    {
        return JobSheet::join('contacts', 'contacts.id', '=', 'repair_job_sheets.contact_id')
            ->join('repair_statuses', 'repair_statuses.id', '=', 'repair_job_sheets.status_id')
            ->where('repair_job_sheets.business_id', $businessId)
            ->whereIn('repair_job_sheets.service_staff', $technicianIds)
            ->whereDate('repair_job_sheets.created_at', $date)
            ->select([
                'repair_job_sheets.id',
                'repair_job_sheets.job_sheet_no',
                'repair_job_sheets.service_staff',
                'repair_job_sheets.created_at',
                DB::raw("TRIM(CONCAT_WS(' ', contacts.first_name, contacts.last_name)) as customer_name"),
                'repair_statuses.name as status_name'
            ])
            ->get()
            ->groupBy('service_staff');
    }
    
    /**
     * Determine attendance status based on attendance and absence records
     */
    private function determineAttendanceStatus($attendance, $absence)
    {
        if ($attendance) {
            return $attendance->clock_out_time ? 'completed' : 'present';
        }
        
        if ($absence) {
            return 'on_leave';
        }
        
        return 'absent';
    }
    
    /**
     * Format attendance details for response
     */
    private function formatAttendanceDetails($attendance, $absence)
    {
        if ($attendance) {
            return [
                'type' => 'attendance',
                'clock_in_time' => $attendance->clock_in_time,
                'clock_out_time' => $attendance->clock_out_time,
                'clock_in_note' => $attendance->clock_in_note,
                'clock_out_note' => $attendance->clock_out_note,
            ];
        }
        
        if ($absence) {
            return [
                'type' => 'absence',
                'start_date' => $absence->start_date,
                'end_date' => $absence->end_date,
                'reason' => $absence->reason,
                'status' => $absence->status,
            ];
        }
        
        return null;
    }
    
    /**
     * Validate that user is a technician in the business
     */
    private function validateTechnician($userId, $businessId)
    {
        $exists = DB::table('users')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('users.id', $userId)
            ->where('users.business_id', $businessId)
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.is_service_staff', 1)
            ->exists();
            
        if (!$exists) {
            throw ValidationException::withMessages([
                'user_id' => 'Invalid technician for this business'
            ]);
        }
    }
    
    /**
     * Apply location filter to query based on user permissions
     */
    private function applyLocationFilter($query, $user, $businessId)
    {
        if (method_exists($user, 'permitted_locations')) {
            $permitted = $user->permitted_locations($businessId);
            if ($permitted !== 'all') {
                $permitted = array_filter((array) $permitted);
                if (!empty($permitted)) {
                    $query->whereIn('users.location_id', $permitted);
                }
            }
        } elseif (!empty($user->location_id)) {
            $query->where('users.location_id', $user->location_id);
        }
        
        return $query;
    }
}
