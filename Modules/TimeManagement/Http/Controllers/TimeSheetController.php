<?php

namespace Modules\TimeManagement\Http\Controllers;

use App\User;
use Carbon\Carbon;
use App\BusinessLocation;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Modules\Repair\Entities\JobSheet;
use Modules\Essentials\Utils\EssentialsUtil;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\TimeManagement\Services\TimeMetricsService;

class TimeSheetController extends Controller
{
    protected $moduleUtil;
    protected $essentialsUtil;

    public function __construct(ModuleUtil $moduleUtil, EssentialsUtil $essentialsUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->essentialsUtil = $essentialsUtil;
    }

   /**
     * Display all technicians with their attendance status and job assignments
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));

        // Get all service staff (technicians)
        $technicians = $this->getTechnicians($businessId, $user);

        if ($technicians->isEmpty()) {
            return view('timemanagement::technician_attendance.index', [
                'technicians' => [],
                'date' => $date,
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

        // Build data for view
        $result = [];
        foreach ($technicians as $technician) {
            $attendance = $attendanceRecords->get($technician->id);
            $absence = $absenceRecords->get($technician->id);
            $jobs = $jobAssignments->get($technician->id, collect());

            $attendanceStatus = $this->determineAttendanceStatus($attendance, $absence);

            $attendanceDetails = $this->formatAttendanceDetails($attendance, $absence);

            $result[] = [
                'user_id' => $technician->id,
                'name' => trim($technician->first_name . ' ' . $technician->last_name),
                'email' => $technician->email,
              
                'location_id' => $technician->location_id,
                'attendance_status' => $attendanceStatus,
                'attendance_details' => $attendanceDetails,
                'clock_in_time' => $attendanceDetails['clock_in_time'] ?? null,
                'clock_out_time' => $attendanceDetails['clock_out_time'] ?? null,
                'worked_hours' => $attendance ? $this->calculateWorkedHours($attendance) : null,
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

        return view('timemanagement::technician_attendance.index', [
            'technicians' => $result,
            'date' => $date,
            'present_count' => $presentCount,
            'absent_count' => $col->where('attendance_status', 'absent')->count(),
            'on_leave_count' => $col->where('attendance_status', 'on_leave')->count(),
        ]);
    }

    /**
     * Show form to clock in a technician
     *
     * @return \Illuminate\View\View
     */
    public function showClockInForm()
    {
        $user = Auth::user();
        $technicians = $this->getTechnicians($user->business_id, $user);
        return view('timemanagement::technician_attendance.clock_in', [
            'technicians' => $technicians,
        ]);
    }

    /**
     * Handle clock in for a technician
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
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

        try {
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
                return redirect()->back()->with('error', 'Technician already clocked in today');
            }

            // Create attendance record
            EssentialsAttendance::create([
                'user_id' => $data['user_id'],
                'business_id' => $businessId,
                'clock_in_time' => $clockInTime,
                'clock_in_note' => $data['note'] ?? null,
                'ip_address' => $data['ip_address'] ?? $request->ip(),
            ]);

            return redirect()->route('timemanagement.index')->with('success', 'Successfully clocked in');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Show form to clock out a technician
     *
     * @return \Illuminate\View\View
     */
    public function showClockOutForm()
    {
        $user = Auth::user();
        $technicians = $this->getTechnicians($user->business_id, $user);
        return view('timemanagement::technician_attendance.clock_out', [
            'technicians' => $technicians,
        ]);
    }

    /**
     * Handle clock out for a technician
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
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

        try {
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
                return redirect()->back()->with('error', 'No active clock-in record found for today');
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

            return redirect()->route('timemanagement.index')->with('success', 'Successfully clocked out');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Display attendance history with filtering
     *
     * @param Request $request
     * @return \Illuminate\View\View
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

        return view('timemanagement::technician_attendance.history', [
            'attendances' => $results,
            'filters' => $data,
            'technicians' => $this->getTechnicians($businessId, $user),
        ]);
    }

    // Include the private helper methods from the original controller
    private function getTechnicians($businessId, $user)
    {
        $query = DB::table('users')
            ->where('location_id', $user->location_id)
            ->where('allow_login', 0)
            ->where('user_type', "user")
            ->whereNull('deleted_at')
            ->select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
              
                'users.location_id',
            ]);

        return $query->get();
    }

    private function getAttendanceRecords($businessId, $technicianIds, $date)
    {
        return EssentialsAttendance::where('business_id', $businessId)
            ->whereIn('user_id', $technicianIds)
            ->whereDate('clock_in_time', $date)
            ->get()
            ->keyBy('user_id');
    }

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

    private function calculateWorkedHours($attendance)
    {
        if (!$attendance || !$attendance->clock_in_time) {
            return null;
        }

        $clockIn = Carbon::parse($attendance->clock_in_time);
        $clockOut = $attendance->clock_out_time ? Carbon::parse($attendance->clock_out_time) : Carbon::now();

        $hours = $clockIn->diffInHours($clockOut);
        $minutes = $clockIn->diffInMinutes($clockOut) % 60;

        return [
            'hours' => $hours,
            'minutes' => $minutes,
            'total_minutes' => $clockIn->diffInMinutes($clockOut)
        ];
    }

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

    /**
     * Bulk clock in for multiple technicians.
     * Mirrors Connector API logic adapted for view submission.
     */
    public function bulkClockIn(Request $request)
    {
        $data = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'clock_in_time' => 'nullable|date',
            'note' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:45',
        ]);

        $user = Auth::user();
        $businessId = $user->business_id;
        $clockInTime = !empty($data['clock_in_time']) ? Carbon::parse($data['clock_in_time']) : Carbon::now();
        $date = $clockInTime->format('Y-m-d');

        $success = [];
        $errors = [];

        foreach ($data['user_ids'] as $technicianId) {
            try {
                $this->validateTechnician($technicianId, $businessId);

                $existingAttendance = EssentialsAttendance::where('user_id', $technicianId)
                    ->where('business_id', $businessId)
                    ->whereDate('clock_in_time', $date)
                    ->first();

                if ($existingAttendance) {
                    $errors[] = "User {$technicianId} already clocked in";
                    continue;
                }

                EssentialsAttendance::create([
                    'user_id' => $technicianId,
                    'business_id' => $businessId,
                    'clock_in_time' => $clockInTime,
                    'clock_in_note' => $data['note'] ?? null,
                    'ip_address' => $data['ip_address'] ?? $request->ip(),
                ]);

                $success[] = $technicianId;
            } catch (ValidationException $e) {
                $errors[] = "User {$technicianId} validation failed";
            } catch (\Throwable $e) {
                Log::error('Bulk clock-in failed', [
                    'user_id' => $technicianId,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = "User {$technicianId} error: {$e->getMessage()}";
            }
        }

        $message = count($success) . ' technicians clocked in';
        if (!empty($errors)) {
            $message .= '. ' . count($errors) . ' failed';
        }

        return redirect()->route('timemanagement.index')
            ->with('success', $message)
            ->with('bulk_success_ids', $success)
            ->with('bulk_errors', $errors);
    }

    /**
     * Bulk clock out for multiple technicians.
     * Also pauses any active timers for technicians.
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
        $clockOutTime = !empty($data['clock_out_time']) ? Carbon::parse($data['clock_out_time']) : Carbon::now();
        $date = $clockOutTime->format('Y-m-d');

        $success = [];
        $errors = [];

        foreach ($data['user_ids'] as $technicianId) {
            try {
                $this->validateTechnician($technicianId, $businessId);

                $attendance = EssentialsAttendance::where('user_id', $technicianId)
                    ->where('business_id', $businessId)
                    ->whereDate('clock_in_time', $date)
                    ->whereNull('clock_out_time')
                    ->first();

                if (!$attendance) {
                    $errors[] = "User {$technicianId} has no active clock-in";
                    continue;
                }

                $attendance->update([
                    'clock_out_time' => $clockOutTime,
                    'clock_out_note' => $data['note'] ?? null,
                ]);

                // Pause all active timers for this technician across the business
                DB::table('timer_tracking')
                    ->where('user_id', $technicianId)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'paused',
                        'paused_at' => $clockOutTime,
                        'updated_at' => $clockOutTime,
                    ]);

                $success[] = $technicianId;
            } catch (ValidationException $e) {
                $errors[] = "User {$technicianId} validation failed";
            } catch (\Throwable $e) {
                Log::error('Bulk clock-out failed', [
                    'user_id' => $technicianId,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = "User {$technicianId} error: {$e->getMessage()}";
            }
        }

        $message = count($success) . ' technicians clocked out';
        if (!empty($errors)) {
            $message .= '. ' . count($errors) . ' failed';
        }

        return redirect()->route('timemanagement.index')
            ->with('success', $message)
            ->with('bulk_success_ids', $success)
            ->with('bulk_errors', $errors);
    }
}
