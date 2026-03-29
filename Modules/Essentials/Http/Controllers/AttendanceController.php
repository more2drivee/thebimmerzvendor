<?php

namespace Modules\Essentials\Http\Controllers;

use App\User;
use App\Utils\ModuleUtil;
use DB;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsUserShift;
use Modules\Essentials\Entities\Shift;
use Modules\Essentials\Utils\EssentialsUtil;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    protected $essentialsUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil, EssentialsUtil $essentialsUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->essentialsUtil = $essentialsUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')
            )
        ) {
            abort(403, 'Unauthorized action.');
        }
        $can_crud_all_attendance = auth()->user()->can('essentials.crud_all_attendance');
        $can_view_own_attendance = auth()->user()->can('essentials.view_own_attendance');

        if (!$can_crud_all_attendance && !$can_view_own_attendance) {
            abort(403, 'Unauthorized action.');
        }

        // Load shifts for import form
        $shifts = Shift::where('business_id', $business_id)->pluck('name', 'id');

        if (request()->ajax()) {
            $attendance = EssentialsAttendance::where(
                'essentials_attendances.business_id',
                $business_id,
            )
                ->join('users as u', 'u.id', '=', 'essentials_attendances.user_id')
                ->leftjoin(
                    'essentials_shifts as es',
                    'es.id',
                    '=',
                    'essentials_attendances.essentials_shift_id',
                )
                ->leftJoin('categories as dept_cat', function($join) {
                    $join->on('dept_cat.id', '=', 'u.essentials_department_id')
                         ->where('dept_cat.category_type', '=', 'hrm_department');
                })
                ->leftJoin('categories as desig_cat', function($join) {
                    $join->on('desig_cat.id', '=', 'u.essentials_designation_id')
                         ->where('desig_cat.category_type', '=', 'hrm_designation');
                })
                ->select([
                    'essentials_attendances.id',
                    'clock_in_time',
                    'clock_out_time',
                    'clock_in_note',
                    'clock_out_note',
                    'ip_address',
                    DB::raw('DATE(clock_in_time) as date'),
                    DB::raw(
                        "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user",
                    ),
                    DB::raw("COALESCE(NULLIF(u.fingerprint_id, ''), u.username) as user_fp"),
                    'es.name as shift_name',
                    DB::raw("CASE WHEN es.start_time IS NOT NULL THEN SUBSTRING(es.start_time, 1, 5) ELSE '' END as on_duty"),
                    DB::raw("CASE WHEN es.end_time IS NOT NULL THEN SUBSTRING(es.end_time, 1, 5) ELSE '' END as off_duty"),
                    'clock_in_location',
                    'clock_out_location',
                    DB::raw("COALESCE(dept_cat.name, '') as dept_name"),
                    DB::raw("COALESCE(desig_cat.name, '') as desig_name"),
                    'u.location_id as user_location_id',
                ])
                ->groupBy('essentials_attendances.id');

            $permitted_locations = auth()->user()->permitted_locations();

            if ($permitted_locations != 'all') {
                $permitted_locations_array = [];

                foreach ($permitted_locations as $loc_id) {
                    $permitted_locations_array[] = 'location.' . $loc_id;
                }
                $permission_ids = Permission::whereIn('name', $permitted_locations_array)->pluck(
                    'id',
                );

                $attendance
                    ->join('model_has_permissions as mhp', 'mhp.model_id', '=', 'u.id')
                    ->whereIn('mhp.permission_id', $permission_ids);
            }

            if (!empty(request()->input('employee_id'))) {
                $attendance->where(
                    'essentials_attendances.user_id',
                    request()->input('employee_id'),
                );
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $attendance
                    ->whereDate('clock_in_time', '>=', $start)
                    ->whereDate('clock_in_time', '<=', $end);
            }

            // Filter by location: match users whose location_id equals chosen location
            if (!empty(request()->input('location_id'))) {
                $attendance->where('u.location_id', request()->input('location_id'));
            }

            // Filter by shift — when a shift is explicitly selected use it directly;
            // when only a location is selected keep all shifts from that location
            if (!empty(request()->input('shift_id'))) {
                $attendance->where('essentials_attendances.essentials_shift_id', request()->input('shift_id'));
            }

            if (!$can_crud_all_attendance && $can_view_own_attendance) {
                $attendance->where('essentials_attendances.user_id', auth()->user()->id);
            }

            return Datatables::of($attendance)
                ->addColumn(
                    'action',
                    '@can("essentials.crud_all_attendance") <button data-href="{{action(\'\Modules\Essentials\Http\Controllers\AttendanceController@edit\', [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-container="#edit_attendance_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        <button class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error delete-attendance" data-href="{{action(\'\Modules\Essentials\Http\Controllers\AttendanceController@destroy\', [$id])}}"><i class="fa fa-trash"></i> @lang("messages.delete")</button> @endcan
                        ',
                )
                ->editColumn('work_duration', function ($row) {
                    $clock_in = \Carbon::parse($row->clock_in_time);
                    if (!empty($row->clock_out_time)) {
                        $clock_out = \Carbon::parse($row->clock_out_time);
                    } else {
                        $clock_out = \Carbon::now();
                    }

                    $html = $clock_in->diffForHumans($clock_out, true, true, 2);

                    return $html;
                })
                ->editColumn('clock_in', function ($row) {
                    $html = $this->moduleUtil->format_date($row->clock_in_time, true);
                    if (!empty($row->clock_in_location)) {
                        $html .= '<br>' . $row->clock_in_location . '<br>';
                    }

                    if (!empty($row->clock_in_note)) {
                        $html .= '<br>' . $row->clock_in_note . '<br>';
                    }

                    return $html;
                })
                ->editColumn('clock_out', function ($row) {
                    $html = $this->moduleUtil->format_date($row->clock_out_time, true);
                    if (!empty($row->clock_out_location)) {
                        $html .= '<br>' . $row->clock_out_location . '<br>';
                    }

                    if (!empty($row->clock_out_note)) {
                        $html .= '<br>' . $row->clock_out_note . '<br>';
                    }

                    return $html;
                })
                ->editColumn('date', '{{@format_date($date)}}')
                ->rawColumns(['action', 'clock_in', 'work_duration', 'clock_out'])
                ->filterColumn('user', function ($query, $keyword) {
                    $query->whereRaw(
                        "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?",
                        ["%{$keyword}%"],
                    );
                })
                ->make(true);
        }

        $settings = request()->session()->get('business.essentials_settings');
        $settings = !empty($settings) ? json_decode($settings, true) : [];

        $is_employee_allowed = auth()
            ->user()
            ->can('essentials.allow_users_for_attendance_from_web');
        $clock_in = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', auth()->user()->id)
            ->whereNull('clock_out_time')
            ->first();
        $employees = [];
        if ($can_crud_all_attendance) {
            $employees = User::forDropdown($business_id, false);
        }

        $days = $this->moduleUtil->getDays();

        // Full shift data (id, name, start_time, end_time) for template auto-fill
        $shifts_full = Shift::where('business_id', $business_id)
            ->select('id', 'name', 'start_time', 'end_time')
            ->get();

        // Locations and departments for import template filters
        // Only show ACTIVE locations that the user has access to
        $locations = \App\BusinessLocation::where('business_id', $business_id)
            ->Active()
            ->pluck('name', 'id')
            ->toArray();
        
        $permitted_locs = auth()->user()->permitted_locations();
        if ($permitted_locs != 'all' && is_array($permitted_locs)) {
            $locations = array_intersect_key($locations, array_flip($permitted_locs));
        }
        
        $departments = \App\Category::forDropdown($business_id, 'hrm_department');

        return view('essentials::attendance.index')->with(
            compact(
                'is_employee_allowed',
                'clock_in',
                'employees',
                'days',
                'shifts',
                'shifts_full',
                'settings',
                'locations',
                'departments',
            ),
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')
            ) &&
            !$is_admin
        ) {
            abort(403, 'Unauthorized action.');
        }

        $employees = User::forDropdown($business_id, false);

        return view('essentials::attendance.create')->with(compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription(
                    $business_id,
                    'essentials_module',
                ) ||
                $is_admin
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $attendance = $request->input('attendance');
            $ip_address = $this->moduleUtil->getUserIpAddr();
            if (!empty($attendance)) {
                foreach ($attendance as $user_id => $value) {
                    $data = [
                        'business_id' => $business_id,
                        'user_id' => $user_id,
                    ];

                    if (!empty($value['clock_in_time'])) {
                        $data['clock_in_time'] = $this->moduleUtil->uf_date(
                            $value['clock_in_time'],
                            true,
                        );
                    }
                    if (!empty($value['id'])) {
                        $data['id'] = $value['id'];
                    }
                    EssentialsAttendance::updateOrCreate($data, [
                        'clock_out_time' => !empty($value['clock_out_time'])
                            ? $this->moduleUtil->uf_date($value['clock_out_time'], true)
                            : null,
                        'ip_address' => !empty($value['ip_address'])
                            ? $value['ip_address']
                            : $ip_address,
                        'clock_in_note' => $value['clock_in_note'],
                        'clock_out_note' => $value['clock_out_note'],
                        'essentials_shift_id' => $value['essentials_shift_id'],
                    ]);
                }
            }

            $output = ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription(
                    $business_id,
                    'essentials_module',
                ) ||
                $is_admin
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        $attendance = EssentialsAttendance::where('business_id', $business_id)
            ->with(['employee'])
            ->find($id);

        return view('essentials::attendance.edit')->with(compact('attendance'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription(
                    $business_id,
                    'essentials_module',
                ) ||
                $is_admin
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only([
                'clock_in_time',
                'clock_out_time',
                'ip_address',
                'clock_in_note',
                'clock_out_note',
            ]);

            $input['clock_in_time'] = $this->moduleUtil->uf_date($input['clock_in_time'], true);
            $input['clock_out_time'] = !empty($input['clock_out_time'])
                ? $this->moduleUtil->uf_date($input['clock_out_time'], true)
                : null;

            $attendance = EssentialsAttendance::where('business_id', $business_id)
                ->where('id', $id)
                ->update($input);
            $output = ['success' => true, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                EssentialsAttendance::where('business_id', $business_id)
                    ->where('id', $id)
                    ->delete();

                $output = ['success' => true, 'msg' => __('lang_v1.deleted_success')];
            } catch (\Exception $e) {
                \Log::emergency(
                    'File:' .
                        $e->getFile() .
                        'Line:' .
                        $e->getLine() .
                        'Message:' .
                        $e->getMessage(),
                );

                $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
            }

            return $output;
        }
    }

    /**
     * Bulk add attendance for multiple employees
     */
    public function bulkStore(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        if (!auth()->user()->can('essentials.crud_all_attendance')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $employee_ids = $request->input('employee_ids', []);
            $date = $this->moduleUtil->uf_date($request->input('date'));
            $clock_in_time = $request->input('clock_in', '08:00');
            $clock_out_time = $request->input('clock_out');
            $ip = $this->moduleUtil->getUserIpAddr();
            $count = 0;
            foreach ($employee_ids as $user_id) {
                EssentialsAttendance::create([
                    'business_id' => $business_id,
                    'user_id' => $user_id,
                    'clock_in_time' => $date . ' ' . $clock_in_time . ':00',
                    'clock_out_time' => !empty($clock_out_time)
                        ? $date . ' ' . $clock_out_time . ':00'
                        : null,
                    'ip_address' => $ip,
                ]);
                $count++;
            }
            return ['success' => true, 'msg' => __('lang_v1.added_success') . ' (' . $count . ')'];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    /**
     * Mark attendance for ALL employees of this business
     */
    public function attendAll(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        if (!auth()->user()->can('essentials.crud_all_attendance')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $date = $this->moduleUtil->uf_date($request->input('date'));
            $clock_in_time = $request->input('clock_in', '08:00');
            $clock_out_time = $request->input('clock_out');
            $ip = $this->moduleUtil->getUserIpAddr();

            $employees = User::where('business_id', $business_id)
                ->where('user_type', 'user')
                ->pluck('id');

            $count = 0;
            foreach ($employees as $user_id) {
                // Skip if already has attendance for this date
                $exists = EssentialsAttendance::where('business_id', $business_id)
                    ->where('user_id', $user_id)
                    ->whereDate('clock_in_time', $date)
                    ->exists();
                if ($exists) {
                    continue;
                }

                EssentialsAttendance::create([
                    'business_id' => $business_id,
                    'user_id' => $user_id,
                    'clock_in_time' => $date . ' ' . $clock_in_time . ':00',
                    'clock_out_time' => !empty($clock_out_time)
                        ? $date . ' ' . $clock_out_time . ':00'
                        : null,
                    'ip_address' => $ip,
                ]);
                $count++;
            }
            return [
                'success' => true,
                'msg' =>
                    __('lang_v1.added_success') .
                    ' (' .
                    $count .
                    ' ' .
                    __('essentials::lang.employees') .
                    ')',
            ];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    /**
     * Clock in / Clock out the logged in user.
     *
     * @return Response
     */
    public function clockInClockOut(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        //Check if employees allowed to add their own attendance
        $settings = request()->session()->get('business.essentials_settings');
        $settings = !empty($settings) ? json_decode($settings, true) : [];
        if (!auth()->user()->can('essentials.allow_users_for_attendance_from_web')) {
            return ['success' => false, 'msg' => __('essentials::lang.not_allowed')];
        } elseif (
            !empty($settings['is_location_required']) &&
            $settings['is_location_required'] &&
            empty($request->input('clock_in_out_location'))
        ) {
            return ['success' => false, 'msg' => __('essentials::lang.you_must_enable_location')];
        }

        try {
            $type = $request->input('type');

            if ($type == 'clock_in') {
                $data = [
                    'business_id' => $business_id,
                    'user_id' => auth()->user()->id,
                    'clock_in_time' => \Carbon::now(),
                    'clock_in_note' => $request->input('clock_in_note'),
                    'ip_address' => $this->moduleUtil->getUserIpAddr(),
                    'clock_in_location' => $request->input('clock_in_out_location'),
                ];

                $output = $this->essentialsUtil->clockin($data, $settings);
            } elseif ($type == 'clock_out') {
                $data = [
                    'business_id' => $business_id,
                    'user_id' => auth()->user()->id,
                    'clock_out_time' => \Carbon::now(),
                    'clock_out_note' => $request->input('clock_out_note'),
                    'clock_out_location' => $request->input('clock_in_out_location'),
                ];

                $output = $this->essentialsUtil->clockout($data, $settings);
            }
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
                'type' => $type,
            ];
        }

        return $output;
    }

    /**
     * Function to get attendance summary of a user
     *
     * @return Response
     */
    public function getUserAttendanceSummary()
    {
        $business_id = request()->session()->get('user.business_id');

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        $user_id = $is_admin ? request()->input('user_id') : auth()->user()->id;

        if (empty($user_id)) {
            return '';
        }

        $start_date = !empty(request()->start_date) ? request()->start_date : null;
        $end_date = !empty(request()->end_date) ? request()->end_date : null;

        $total_work_duration = $this->essentialsUtil->getTotalWorkDuration(
            'hour',
            $user_id,
            $business_id,
            $start_date,
            $end_date,
        );

        return $total_work_duration;
    }

    /**
     * Function to validate clock in and clock out time
     *
     * @return string
     */
    public function validateClockInClockOut(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_ids = explode(',', $request->input('user_ids'));
        $clock_in_time = $request->input('clock_in_time');
        $clock_out_time = $request->input('clock_out_time');
        $attendance_id = $request->input('attendance_id');

        $is_valid = 'true';
        if (!empty($user_ids)) {
            //Check if clock in time falls under any existing attendance range
            $is_clock_in_exists = false;
            if (!empty($clock_in_time)) {
                $clock_in_time = $this->essentialsUtil->uf_date($clock_in_time, true);

                $is_clock_in_exists = EssentialsAttendance::where('business_id', $business_id)
                    ->where('id', '!=', $attendance_id)
                    ->whereIn('user_id', $user_ids)
                    ->where('clock_in_time', '<', $clock_in_time)
                    ->where('clock_out_time', '>', $clock_in_time)
                    ->exists();
            }

            //Check if clock out time falls under any existing attendance range
            $is_clock_out_exists = false;
            if (!empty($clock_out_time)) {
                $clock_out_time = $this->essentialsUtil->uf_date($clock_out_time, true);

                $is_clock_out_exists = EssentialsAttendance::where('business_id', $business_id)
                    ->where('id', '!=', $attendance_id)
                    ->whereIn('user_id', $user_ids)
                    ->where('clock_in_time', '<', $clock_out_time)
                    ->where('clock_out_time', '>', $clock_out_time)
                    ->exists();
            }

            if ($is_clock_in_exists || $is_clock_out_exists) {
                $is_valid = 'false';
            }
        }

        return $is_valid;
    }

    /**
     * Get attendance summary by shift
     */
    public function getAttendanceByShift()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription(
                    $business_id,
                    'essentials_module',
                ) ||
                $is_admin
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        $date = $this->moduleUtil->uf_date(request()->input('date'));

        $attendance_data = EssentialsAttendance::where('business_id', $business_id)
            ->whereDate('clock_in_time', $date)
            ->whereNotNull('essentials_shift_id')
            ->with(['shift', 'shift.user_shifts', 'shift.user_shifts.user', 'employee'])
            ->get();
        $attendance_by_shift = [];
        $date_obj = \Carbon::parse($date);
        foreach ($attendance_data as $data) {
            if (empty($attendance_by_shift[$data->essentials_shift_id])) {
                //Calculate total users in the shift
                $total_users = 0;
                $all_users = [];
                foreach ($data->shift->user_shifts as $user_shift) {
                    if (
                        !empty($user_shift->start_date) &&
                        !empty($user_shift->end_date) &&
                        $date_obj->between(
                            \Carbon::parse($user_shift->start_date),
                            \Carbon::parse($user_shift->end_date),
                        )
                    ) {
                        $total_users++;
                        $all_users[] = $user_shift->user->user_full_name;
                    }
                }
                $attendance_by_shift[$data->essentials_shift_id] = [
                    'present' => 1,
                    'shift' => $data->shift->name,
                    'total' => $total_users,
                    'present_users' => [$data->employee->user_full_name],
                    'all_users' => $all_users,
                ];
            } else {
                if (
                    !in_array(
                        $data->employee->user_full_name,
                        $attendance_by_shift[$data->essentials_shift_id]['present_users'],
                    )
                ) {
                    $attendance_by_shift[$data->essentials_shift_id]['present']++;
                    $attendance_by_shift[$data->essentials_shift_id]['present_users'][] =
                        $data->employee->user_full_name;
                }
            }
        }

        return view('essentials::attendance.attendance_by_shift_data')->with(
            compact('attendance_by_shift'),
        );
    }

    /**
     * Get attendance summary by date
     */
    public function getAttendanceByDate()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription(
                    $business_id,
                    'essentials_module',
                ) ||
                $is_admin
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        $start_date = request()->input('start_date');
        $end_date = request()->input('end_date');

        $attendance_data = EssentialsAttendance::where('business_id', $business_id)
            ->whereDate('clock_in_time', '>=', $start_date)
            ->whereDate('clock_in_time', '<=', $end_date)
            ->select(
                'essentials_attendances.*',
                DB::raw('COUNT(DISTINCT essentials_attendances.user_id) as total_present'),
                DB::raw('CAST(clock_in_time AS DATE) as clock_in_date'),
            )
            ->groupBy(DB::raw('CAST(clock_in_time AS DATE)'))
            ->get();

        $all_users = User::where('business_id', $business_id)->user()->count();

        $attendance_by_date = [];
        foreach ($attendance_data as $data) {
            $total_present = !empty($data->total_present) ? $data->total_present : 0;
            $attendance_by_date[] = [
                'present' => $total_present,
                'absent' => $all_users - $total_present,
                'date' => $data->clock_in_date,
            ];
        }

        return view('essentials::attendance.attendance_by_date_data')->with(
            compact('attendance_by_date'),
        );
    }

    /**
     * AJAX: Return shifts that have at least one user assigned from the given location.
     * Also returns all shifts when no location is provided.
     */
    public function getShiftsByLocation(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription(
                    $business_id,
                    'essentials_module',
                ) ||
                $is_admin
            )
        ) {
            return response()->json(['success' => false, 'shifts' => []]);
        }

        $location_id = $request->input('location_id');

        $query = Shift::where('essentials_shifts.business_id', $business_id)->select(
            'essentials_shifts.id',
            'essentials_shifts.name',
            'essentials_shifts.start_time',
            'essentials_shifts.end_time',
        );

        if (!empty($location_id)) {
            // Only return shifts that have at least one user from the chosen location
            $query
                ->join(
                    'essentials_user_shifts as eus',
                    'eus.essentials_shift_id',
                    '=',
                    'essentials_shifts.id',
                )
                ->join('users as u', 'u.id', '=', 'eus.user_id')
                ->where('u.location_id', $location_id)
                ->groupBy(
                    'essentials_shifts.id',
                    'essentials_shifts.name',
                    'essentials_shifts.start_time',
                    'essentials_shifts.end_time',
                );
        }

        $shifts = $query->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'start_time' => $s->start_time ? substr($s->start_time, 0, 5) : null,
                'end_time' => $s->end_time ? substr($s->end_time, 0, 5) : null,
                'label' =>
                    $s->name .
                    ($s->start_time && $s->end_time
                        ? ' (' .
                            substr($s->start_time, 0, 5) .
                            ' – ' .
                            substr($s->end_time, 0, 5) .
                            ')'
                        : ''),
            ];
        });

        return response()->json(['success' => true, 'shifts' => $shifts]);
    }

    /**
     * AJAX: Given a clock-in time (HH:MM) and optional location_id,
     * return all shifts whose window covers that time.
     */
    public function getShiftByTime(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription(
                    $business_id,
                    'essentials_module',
                ) ||
                $is_admin
            )
        ) {
            return response()->json(['success' => false, 'shifts' => []]);
        }

        $time = $request->input('time'); // "HH:MM"
        $location_id = $request->input('location_id');

        if (empty($time)) {
            return response()->json([
                'success' => false,
                'msg' => 'Time is required',
                'shifts' => [],
            ]);
        }

        // Pad to HH:MM if needed
        if (strlen($time) === 5) {
            $time_check = $time; // "08:00"
        } else {
            $time_check = substr($time, 0, 5);
        }

        $query = Shift::where('essentials_shifts.business_id', $business_id)
            ->whereNotNull('essentials_shifts.start_time')
            ->whereNotNull('essentials_shifts.end_time')
            ->select(
                'essentials_shifts.id',
                'essentials_shifts.name',
                'essentials_shifts.start_time',
                'essentials_shifts.end_time',
            );

        if (!empty($location_id)) {
            $query
                ->join(
                    'essentials_user_shifts as eus',
                    'eus.essentials_shift_id',
                    '=',
                    'essentials_shifts.id',
                )
                ->join('users as u', 'u.id', '=', 'eus.user_id')
                ->where('u.location_id', $location_id)
                ->groupBy(
                    'essentials_shifts.id',
                    'essentials_shifts.name',
                    'essentials_shifts.start_time',
                    'essentials_shifts.end_time',
                );
        }

        $all_shifts = $query->get();
        $matched = [];

        foreach ($all_shifts as $s) {
            $start = substr($s->start_time, 0, 5); // "HH:MM"
            $end = substr($s->end_time, 0, 5);

            $hits = false;
            if ($end >= $start) {
                // Normal (same-day) shift
                $hits = $time_check >= $start && $time_check <= $end;
            } else {
                // Overnight shift  e.g. 22:00 – 06:00
                $hits = $time_check >= $start || $time_check <= $end;
            }

            if ($hits) {
                $matched[] = [
                    'id' => $s->id,
                    'name' => $s->name,
                    'start_time' => $start,
                    'end_time' => $end,
                    'label' => $s->name . ' (' . $start . ' – ' . $end . ')',
                ];
            }
        }

        return response()->json(['success' => true, 'shifts' => $matched]);
    }

    /**
     * Generate attendance import template with selected shift users.
     * Priority: 1) Passed shift_id, 2) Auto-detect from clock_in_time, 3) Default shift from settings
     * If no shift found, generates normal template with all employees.
     */
    public function generateAttendanceTemplate(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription(
                    $business_id,
                    'essentials_module',
                ) ||
                $is_admin
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $shift_id = $request->input('shift_id');
            $location_id = $request->input('location_id');
            $dept_id = $request->input('department_id');

            // Get settings for defaults
            $settings = request()->session()->get('business.essentials_settings');
            $settings = !empty($settings) ? json_decode($settings, true) : [];

            // ── Resolve shift ─────────────────────────────────────────────────
            // Priority 1 – explicit shift_id from the form
            // Priority 2 – auto-detect from clock_in_time (+ optional location)
            // Priority 3 – default shift from settings
            $shift = null;
            $shift_auto_detected = false;

            if (!empty($shift_id)) {
                $shift = Shift::where('business_id', $business_id)->find($shift_id);
                if (empty($shift)) {
                    return back()->with('notification', [
                        'success' => 0,
                        'msg' => __('essentials::lang.shift') . ' ' . __('messages.not_found'),
                    ]);
                }
            } else {
                // Auto-detect from clock_in_time
                $raw_ci_for_detect = $request->input('clock_in_time');

                if (!empty($raw_ci_for_detect)) {
                    $time_check = substr($raw_ci_for_detect, 0, 5); // "HH:MM"

                    $sq = Shift::where('essentials_shifts.business_id', $business_id)
                        ->whereNotNull('essentials_shifts.start_time')
                        ->whereNotNull('essentials_shifts.end_time')
                        ->select('essentials_shifts.*');

                    if (!empty($location_id)) {
                        $sq->join(
                            'essentials_user_shifts as eus_d',
                            'eus_d.essentials_shift_id',
                            '=',
                            'essentials_shifts.id',
                        )
                            ->join('users as ud', 'ud.id', '=', 'eus_d.user_id')
                            ->where('ud.location_id', $location_id)
                            ->groupBy('essentials_shifts.id');
                    }

                    foreach ($sq->get() as $candidate) {
                        $s_start = substr($candidate->start_time, 0, 5);
                        $s_end = substr($candidate->end_time, 0, 5);

                        $hits =
                            $s_end >= $s_start
                                ? $time_check >= $s_start && $time_check <= $s_end // normal
                                : $time_check >= $s_start || $time_check <= $s_end; // overnight

                        if ($hits) {
                            $shift = $candidate;
                            $shift_id = $candidate->id;
                            $shift_auto_detected = true;
                            break;
                        }
                    }
                }

                // Priority 3 – fallback to default shift from settings
                if (empty($shift) && !empty($settings['default_import_shift_id'])) {
                    $shift = Shift::where('business_id', $business_id)->find($settings['default_import_shift_id']);
                    if (!empty($shift)) {
                        $shift_id = $shift->id;
                    }
                }

                // If still no shift, we'll generate a normal template with all employees
            }

            // ── Date range ───────────────────────────────────────────────────
            $date_from = $request->input('date_from')
                ? \Carbon::parse($request->input('date_from'))->format('Y-m-d')
                : \Carbon::today()->format('Y-m-d');

            $date_to = $request->input('date_to')
                ? \Carbon::parse($request->input('date_to'))->format('Y-m-d')
                : $date_from;

            // Clamp to 31 days maximum to avoid memory issues
            $from_carbon = \Carbon::parse($date_from);
            $to_carbon = \Carbon::parse($date_to);
            if ($to_carbon->diffInDays($from_carbon) > 31) {
                $to_carbon = $from_carbon->copy()->addDays(31);
                $date_to = $to_carbon->format('Y-m-d');
            }

            // ── Default clock times (fall back to shift times or settings) ────────────────
            $raw_clock_in = $request->input('clock_in_time') ?: ($shift->start_time ?? ($settings['default_clock_in_time'] ?? '08:00'));
            $raw_clock_out = $request->input('clock_out_time') ?: ($shift->end_time ?? ($settings['default_clock_out_time'] ?? '17:00'));
            $ci_hm = substr($raw_clock_in, 0, 5); // "HH:MM"
            $co_hm = substr($raw_clock_out, 0, 5);

            // ── Build user list ───────────────────────────────────────────────
            // Resolve department and designation names for reference columns
            $dept_names = \App\Category::where('category_type', 'hrm_department')->pluck(
                'name',
                'id',
            );
            $desig_names = \App\Category::where('category_type', 'hrm_designation')->pluck(
                'name',
                'id',
            );

            $users = [];
            
            if (!empty($shift_id)) {
                // Get users assigned to this shift
                $user_query = EssentialsUserShift::where('essentials_shift_id', $shift_id)
                    ->with(['user'])
                    ->get();

                foreach ($user_query as $us) {
                    if (empty($us->user)) {
                        continue;
                    }
                    $u = $us->user;

                    // Location filter
                    if (!empty($location_id) && (string) $u->location_id !== (string) $location_id) {
                        continue;
                    }
                    // Department filter
                    if (
                        !empty($dept_id) &&
                        (string) $u->essentials_department_id !== (string) $dept_id
                    ) {
                        continue;
                    }

                    $dept_name = !empty($u->essentials_department_id)
                        ? $dept_names[$u->essentials_department_id] ?? ''
                        : '';
                    $desig_name = !empty($u->essentials_designation_id)
                        ? $desig_names[$u->essentials_designation_id] ?? ''
                        : '';

                    // Use fingerprint_id if available, otherwise username
                    $identifier = !empty($u->fingerprint_id) ? $u->fingerprint_id : $u->username;

                    $users[] = [
                        'identifier' => $identifier,
                        'dept'       => $dept_name,
                        'desig'      => $desig_name,
                    ];
                }
            }
            
            // If no users from shift (or no shift), get all employees filtered by location/department
            if (empty($users)) {
                $all_users_query = User::where('business_id', $business_id)
                    ->where('user_type', 'user');

                if (!empty($location_id)) {
                    $all_users_query->where('location_id', $location_id);
                }
                if (!empty($dept_id)) {
                    $all_users_query->where('essentials_department_id', $dept_id);
                }

                $all_users = $all_users_query->get();
                foreach ($all_users as $u) {
                    $dept_name = !empty($u->essentials_department_id)
                        ? $dept_names[$u->essentials_department_id] ?? ''
                        : '';
                    $desig_name = !empty($u->essentials_designation_id)
                        ? $desig_names[$u->essentials_designation_id] ?? ''
                        : '';

                    // Use fingerprint_id if available, otherwise username
                    $identifier = !empty($u->fingerprint_id) ? $u->fingerprint_id : $u->username;

                    $users[] = [
                        'identifier' => $identifier,
                        'dept'       => $dept_name,
                        'desig'      => $desig_name,
                    ];
                }
            }

            // ── Build header ──────────────────────────────────────────────────
            $template_data = [];
            $template_data[] = [
                'Username / Fingerprint ID',
                'Date',
                'Shift (Timetable)',
                'On Duty (Shift Start)',
                'Off Duty (Shift End)',
                'Clock In Time',
                'Clock Out Time',
                'Department',
                'Designation',
            ];

            // ── One row per user per date ─────────────────────────────────────
            $shift_name      = !empty($shift) ? $shift->name : '';
            $shift_on_duty   = !empty($shift) ? substr($shift->start_time ?? '', 0, 5) : '';
            $shift_off_duty  = !empty($shift) ? substr($shift->end_time   ?? '', 0, 5) : '';
            $current = $from_carbon->copy();
            while ($current->lte($to_carbon)) {
                $date_str = $current->format('n/j/Y'); // M/D/YYYY – matches accepted import formats

                if (empty($users)) {
                    // Placeholder row so the file is never completely empty
                    $template_data[] = [
                        'sample_username',
                        $date_str,
                        $shift_name,
                        $shift_on_duty,
                        $shift_off_duty,
                        $ci_hm,
                        $co_hm,
                        '',
                        '',
                    ];
                    break; // one placeholder is enough
                }

                foreach ($users as $u) {
                    $template_data[] = [
                        $u['identifier'],
                        $date_str,
                        $shift_name,
                        $shift_on_duty,
                        $shift_off_duty,
                        $ci_hm,
                        $co_hm,
                        $u['dept'],
                        $u['desig'],
                    ];
                }

                $current->addDay();
            }

            // ── File name ─────────────────────────────────────────────────────
            $filename =
                'attendance_' .
                preg_replace('/\s+/', '_', $shift_name) .
                '_' .
                $date_from .
                ($date_to !== $date_from ? '_to_' . $date_to : '') .
                '.xlsx';

            return Excel::download(
                new class ($template_data) implements
                    \Maatwebsite\Excel\Concerns\FromArray,
                    \Maatwebsite\Excel\Concerns\WithStyles
                {
                    private $data;

                    public function __construct($data)
                    {
                        $this->data = $data;
                    }

                    public function array(): array
                    {
                        return $this->data;
                    }

                    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
                    {
                        $sheet->getStyle('1')->getFont()->setBold(true);
                        foreach (range('A', 'I') as $col) {
                            $sheet->getColumnDimension($col)->setAutoSize(true);
                        }
                        // Light-yellow background for the header row
                        $sheet
                            ->getStyle('1')
                            ->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setARGB('FFFFFF99');
                        return [];
                    }
                },
                $filename,
            );
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );

            return back()->with('notification', ['success' => 0, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Function to import attendance.
     *
     * @param  Request  $request
     * @return Response
     */
    public function importAttendance(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription(
                    $business_id,
                    'essentials_module',
                ) ||
                $is_admin
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $notAllowed = $this->moduleUtil->notAllowedInDemo();
            if (!empty($notAllowed)) {
                return $notAllowed;
            }

            // Fallback shift from the import form's shift selector
            $form_shift_id = $request->input('shift_id');

            ini_set('max_execution_time', 0);

            if ($request->hasFile('attendance')) {
                $file = $request->file('attendance');
                $parsed_array = Excel::toArray([], $file);

                // Remove header row
                $imported_data = array_splice($parsed_array[0], 1);

                $formated_data = [];
                $is_valid = true;
                $error_msg = '';

                DB::beginTransaction();
                $ip_address = $this->moduleUtil->getUserIpAddr();

                foreach ($imported_data as $key => $value) {
                    // Pad the row so all column indexes are safely accessible
                    $value = array_pad($value, 9, null);

                    // Skip completely empty rows (e.g. trailing blank lines)
                    if (empty(array_filter($value, fn($v) => $v !== null && $v !== ''))) {
                        continue;
                    }

                    $row_no = $key + 2;
                    $temp = [];

                    // ── Column 1: Username OR Fingerprint ID ──────────────────
                    $identifier = isset($value[0]) ? trim((string) $value[0]) : '';
                    if ($identifier === '') {
                        $is_valid = false;
                        $error_msg = 'Username / Fingerprint ID is required in row ' . $row_no;
                        break;
                    }

                    $user = User::where('business_id', $business_id)
                        ->where(function ($q) use ($identifier) {
                            $q->where('username', $identifier)->orWhere(
                                'fingerprint_id',
                                $identifier,
                            );
                        })
                        ->first();

                    if (empty($user)) {
                        $is_valid = false;
                        $error_msg = __('essentials::lang.user_not_found', [
                            'identifier' => $identifier,
                            'row' => $row_no,
                        ]);
                        break;
                    }
                    $temp['user_id'] = $user->id;

                    // ── Column 2: Date  (M/D/YYYY  or  YYYY-MM-DD  etc.) ─────
                    $date_raw = isset($value[1]) ? trim((string) $value[1]) : '';
                    if ($date_raw === '') {
                        $is_valid = false;
                        $error_msg = 'Date is required in row ' . $row_no;
                        break;
                    }

                    try {
                        // Handle numeric serial dates from Excel (e.g. 46023)
                        if (is_numeric($date_raw) && (int) $date_raw > 1000) {
                            $date_carbon = \Carbon\Carbon::createFromFormat(
                                'Y-m-d',
                                \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(
                                    (int) $date_raw,
                                )->format('Y-m-d'),
                            );
                        } else {
                            $date_carbon = \Carbon\Carbon::parse($date_raw);
                        }
                    } catch (\Exception $de) {
                        $is_valid = false;
                        $error_msg =
                            "Cannot parse date '{$date_raw}' in row {$row_no}. " .
                            'Use M/D/YYYY or YYYY-MM-DD format.';
                        break;
                    }
                    $date_str = $date_carbon->format('Y-m-d');

                    // ── Column 3: Shift / Timetable name (optional) ──────────
                    $shift_col = isset($value[2]) ? trim((string) $value[2]) : '';

                    // ── Column 4: On Duty  (reference, HH:MM – not imported) ─
                    // $value[3] – intentionally skipped

                    // ── Column 5: Off Duty (reference, HH:MM – not imported) ─
                    // $value[4] – intentionally skipped

                    // ── Column 6: Clock In  (HH:MM, optional) ────────────────
                    $clock_in_raw = isset($value[5]) ? trim((string) $value[5]) : '';

                    // ── Column 7: Clock Out (HH:MM, optional) ────────────────
                    $clock_out_raw = isset($value[6]) ? trim((string) $value[6]) : '';

                    // ── Column 8: Department (reference only – not imported) ──
                    // $value[7] – intentionally skipped

                    // ── Column 9: Designation (reference only – not imported) ─
                    // $value[8] – intentionally skipped

                    // Skip rows where both clock-in AND clock-out are absent
                    // (treated as absent / no attendance event for that day)
                    if ($clock_in_raw === '' && $clock_out_raw === '') {
                        continue;
                    }

                    // Build full datetime for clock-in
                    if ($clock_in_raw !== '') {
                        // Accept "HH:MM" and "HH:MM:SS"
                        $ci_time =
                            strlen($clock_in_raw) === 5 ? $clock_in_raw . ':00' : $clock_in_raw;
                        $temp['clock_in_time'] = $date_str . ' ' . $ci_time;
                    } else {
                        // Clock-in absent but clock-out present – use midnight as placeholder
                        $temp['clock_in_time'] = $date_str . ' 00:00:00';
                    }

                    // Build full datetime for clock-out
                    if ($clock_out_raw !== '') {
                        $co_time =
                            strlen($clock_out_raw) === 5 ? $clock_out_raw . ':00' : $clock_out_raw;

                        // Detect overnight shift: if clock-out (as HH:MM) < clock-in (as HH:MM)
                        // advance clock-out date by one day
                        $co_date_str = $date_str;
                        if (
                            $clock_in_raw !== '' &&
                            substr($co_time, 0, 5) < substr($clock_in_raw, 0, 5)
                        ) {
                            $co_date_str = $date_carbon->copy()->addDay()->format('Y-m-d');
                        }

                        $temp['clock_out_time'] = $co_date_str . ' ' . $co_time;
                    } else {
                        $temp['clock_out_time'] = null;
                    }

                    // ── Shift resolution ──────────────────────────────────────
                    if (!empty($shift_col)) {
                        // 1. Exact name match
                        $shift = Shift::where('business_id', $business_id)
                            ->where('name', $shift_col)
                            ->first();

                        if (!empty($shift)) {
                            $temp['essentials_shift_id'] = $shift->id;
                        } else {
                            // Shift name in file not found → warn but don't block import;
                            // fall through to auto-detection below
                            \Log::warning(
                                "Attendance import row {$row_no}: shift '{$shift_col}' not found; will try auto-detection.",
                            );
                        }
                    }

                    if (empty($temp['essentials_shift_id'])) {
                        // 2. Use the fallback shift selected on the upload form
                        if (!empty($form_shift_id)) {
                            $temp['essentials_shift_id'] = $form_shift_id;
                        } else {
                            // 3. Auto-detect from the user's active shift assignments
                            $clock_in_carbon = \Carbon\Carbon::parse($temp['clock_in_time']);

                            $user_shifts = EssentialsUserShift::where('user_id', $temp['user_id'])
                                ->with('shift')
                                ->where(function ($q) use ($date_str) {
                                    $q->whereNull('start_date')->orWhere(
                                        'start_date',
                                        '<=',
                                        $date_str,
                                    );
                                })
                                ->where(function ($q) use ($date_str) {
                                    $q->whereNull('end_date')->orWhere('end_date', '>=', $date_str);
                                })
                                ->get();

                            foreach ($user_shifts as $us) {
                                if (
                                    empty($us->shift) ||
                                    !$us->shift->start_time ||
                                    !$us->shift->end_time
                                ) {
                                    continue;
                                }

                                $s_start = \Carbon\Carbon::parse(
                                    $date_str . ' ' . $us->shift->start_time,
                                );
                                $s_end = \Carbon\Carbon::parse(
                                    $date_str . ' ' . $us->shift->end_time,
                                );

                                // Overnight shift
                                if ($s_end->lte($s_start)) {
                                    $s_end->addDay();
                                }

                                // ±2 h tolerance window
                                if (
                                    $clock_in_carbon->gte($s_start->copy()->subHours(2)) &&
                                    $clock_in_carbon->lte($s_end->copy()->addHours(2))
                                ) {
                                    $temp['essentials_shift_id'] = $us->essentials_shift_id;
                                    break;
                                }
                            }
                            // If still not found, leave essentials_shift_id unset (null)
                        }
                    }

                    $temp['ip_address'] = $ip_address;
                    $temp['business_id'] = $business_id;
                    $formated_data[] = $temp;
                }

                if (!$is_valid) {
                    throw new \Exception($error_msg);
                }

                if (!empty($formated_data)) {
                    EssentialsAttendance::insert($formated_data);
                }

                $output = [
                    'success' => 1,
                    'msg' =>
                        __('product.file_imported_successfully') .
                        ' (' .
                        count($formated_data) .
                        ' ' .
                        __('essentials::lang.attendance_records') .
                        ')',
                ];

                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );

            $output = ['success' => 0, 'msg' => $e->getMessage()];

            return redirect()->back()->with('notification', $output);
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Get attendance calendar data for AJAX
     */
    public function getAttendanceCalendar(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        $user_id =
            $is_admin || auth()->user()->can('essentials.crud_all_attendance')
                ? $request->input('user_id')
                : auth()->user()->id;

        if (empty($user_id)) {
            return response()->json([]);
        }

        $start_date = $request->input(
            'start_date',
            \Carbon::now()->startOfMonth()->format('Y-m-d'),
        );
        $end_date = $request->input('end_date', \Carbon::now()->endOfMonth()->format('Y-m-d'));

        $location_id = $request->input('location_id', null);

        $calendar = $this->essentialsUtil->getAttendanceCalendarData(
            $business_id,
            $user_id,
            $start_date,
            $end_date,
            $location_id,
        );

        return response()->json($calendar);
    }

    /**
     * Get shift calendar data for AJAX
     */
    public function getShiftCalendar(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        $user_id =
            $is_admin || auth()->user()->can('essentials.crud_all_attendance')
                ? $request->input('user_id')
                : auth()->user()->id;

        if (empty($user_id)) {
            return response()->json([]);
        }

        $start_date = $request->input(
            'start_date',
            \Carbon::now()->startOfMonth()->format('Y-m-d'),
        );
        $end_date = $request->input('end_date', \Carbon::now()->endOfMonth()->format('Y-m-d'));

        $calendar = $this->essentialsUtil->getShiftCalendarData(
            $business_id,
            $user_id,
            $start_date,
            $end_date,
        );

        return response()->json($calendar);
    }

    /**
     * Adds attendance row for an employee on add latest attendance form
     *
     * @param  int  $user_id
     * @return Response
     */
    public function getAttendanceRow($user_id)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription(
                    $business_id,
                    'essentials_module',
                ) ||
                $is_admin
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::where('business_id', $business_id)->findOrFail($user_id);

        $attendance = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->first();

        $shifts = Shift::join(
            'essentials_user_shifts as eus',
            'eus.essentials_shift_id',
            '=',
            'essentials_shifts.id',
        )
            ->where('essentials_shifts.business_id', $business_id)
            ->where('eus.user_id', $user_id)
            ->where('eus.start_date', '<=', \Carbon::now()->format('Y-m-d'))
            ->pluck('essentials_shifts.name', 'essentials_shifts.id');

        return view('essentials::attendance.attendance_row')->with(
            compact('attendance', 'shifts', 'user'),
        );
    }
}
