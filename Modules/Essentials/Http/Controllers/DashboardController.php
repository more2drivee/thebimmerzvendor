<?php

namespace Modules\Essentials\Http\Controllers;

use App\Category;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsHoliday;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsUserSalesTarget;
use Modules\Essentials\Utils\EssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class DashboardController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    protected $essentialsUtil;

    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param  ModuleUtil  $moduleUtil
     * @return void
     */
    public function __construct(
        ModuleUtil $moduleUtil,
        EssentialsUtil $essentialsUtil,
        TransactionUtil $transactionUtil
    ) {
        $this->moduleUtil = $moduleUtil;
        $this->essentialsUtil = $essentialsUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function hrmDashboard()
    {
        $business_id = request()->session()->get('user.business_id');

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        $user_id = auth()->user()->id;

        // ── Total Employees ──
        $users = User::where('business_id', $business_id)
            ->user()
            ->get();
        $total_employees = $users->count();

        // ── Departments ──
        $departments = Category::where('business_id', $business_id)
            ->where('category_type', 'hrm_department')
            ->get();
        $users_by_dept = $users->groupBy('essentials_department_id');

        // Department chart data
        $dept_labels = [];
        $dept_counts = [];
        foreach ($departments as $dept) {
            $dept_labels[] = $dept->name;
            $dept_counts[] = isset($users_by_dept[$dept->id]) ? $users_by_dept[$dept->id]->count() : 0;
        }
        // Employees not assigned to any department
        $unassigned_count = isset($users_by_dept[null]) ? $users_by_dept[null]->count() : (isset($users_by_dept['']) ? $users_by_dept['']->count() : 0);
        if ($unassigned_count > 0) {
            $dept_labels[] = __('essentials::lang.unassigned') ?: 'Unassigned';
            $dept_counts[] = $unassigned_count;
        }

        $today = new \Carbon('today');
        $one_month_from_today = \Carbon::now()->addMonth();

        // ── All Leaves (for analytics) ──
        $all_leaves = EssentialsLeave::where('business_id', $business_id)
            ->with(['user', 'leave_type'])
            ->get();

        // Leave by status
        $leave_status_counts = $all_leaves->groupBy('status')->map->count();
        $leave_status_labels = $leave_status_counts->keys()->map(function ($s) {
            return ucfirst($s);
        })->values()->toArray();
        $leave_status_data = $leave_status_counts->values()->toArray();

        // Leave by type
        $leave_type_counts = $all_leaves->groupBy(function ($l) {
            return optional($l->leave_type)->leave_type ?? (__('essentials::lang.unknown') ?: 'Unknown');
        })->map->count();
        $leave_type_labels = $leave_type_counts->keys()->toArray();
        $leave_type_data = $leave_type_counts->values()->toArray();

        // Pending leave requests
        $pending_leaves = EssentialsLeave::where('business_id', $business_id)
            ->where('status', 'pending')
            ->with(['user', 'leave_type'])
            ->orderBy('start_date', 'desc')
            ->limit(10)
            ->get();
        $pending_leaves_count = EssentialsLeave::where('business_id', $business_id)
            ->where('status', 'pending')
            ->count();

        // Today's and upcoming leaves
        $approved_leaves = EssentialsLeave::where('business_id', $business_id)
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $today->format('Y-m-d'))
            ->whereDate('start_date', '<=', $one_month_from_today->format('Y-m-d'))
            ->with(['user', 'leave_type'])
            ->orderBy('start_date', 'asc')
            ->get();

        $todays_leaves = [];
        $upcoming_leaves = [];
        foreach ($approved_leaves as $leave) {
            $leave_start = \Carbon::parse($leave->start_date);
            $leave_end = \Carbon::parse($leave->end_date);
            if ($today->gte($leave_start) && $today->lte($leave_end)) {
                $todays_leaves[] = $leave;
            } elseif ($today->lt($leave_start) && $leave_start->lte($one_month_from_today)) {
                $upcoming_leaves[] = $leave;
            }
        }

        // ── Holidays ──
        $holidays_query = EssentialsHoliday::where('essentials_holidays.business_id', $business_id)
            ->whereDate('end_date', '>=', $today->format('Y-m-d'))
            ->whereDate('start_date', '<=', $one_month_from_today->format('Y-m-d'))
            ->orderBy('start_date', 'asc')
            ->with(['location']);

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $holidays_query->where(function ($query) use ($permitted_locations) {
                $query->whereIn('essentials_holidays.location_id', $permitted_locations)
                    ->orWhereNull('essentials_holidays.location_id');
            });
        }
        $holidays = $holidays_query->get();

        $todays_holidays = [];
        $upcoming_holidays = [];
        foreach ($holidays as $holiday) {
            $holiday_start = \Carbon::parse($holiday->start_date);
            $holiday_end = \Carbon::parse($holiday->end_date);
            if ($today->gte($holiday_start) && $today->lte($holiday_end)) {
                $todays_holidays[] = $holiday;
            } elseif ($today->lt($holiday_start) && $holiday_start->lte($one_month_from_today)) {
                $upcoming_holidays[] = $holiday;
            }
        }

        // ── Today's Attendance ──
        $todays_attendances = [];
        $present_today = 0;
        $absent_today = 0;
        if ($is_admin) {
            $todays_attendances = EssentialsAttendance::where('business_id', $business_id)
                ->whereDate('clock_in_time', \Carbon::now()->format('Y-m-d'))
                ->with(['employee'])
                ->orderBy('clock_in_time', 'asc')
                ->get();
            $present_today = $todays_attendances->pluck('user_id')->unique()->count();
            $absent_today = max(0, $total_employees - $present_today);
        }

        // ── Monthly Attendance Trend (last 6 months) ──
        $attendance_trend_labels = [];
        $attendance_trend_data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = \Carbon::now()->subMonths($i);
            $attendance_trend_labels[] = $month->format('M Y');
            $attendance_trend_data[] = EssentialsAttendance::where('business_id', $business_id)
                ->whereYear('clock_in_time', $month->year)
                ->whereMonth('clock_in_time', $month->month)
                ->count();
        }

        // ── Monthly Leave Trend (last 6 months) ──
        $leave_trend_labels = [];
        $leave_trend_data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = \Carbon::now()->subMonths($i);
            $leave_trend_labels[] = $month->format('M Y');
            $leave_trend_data[] = EssentialsLeave::where('business_id', $business_id)
                ->where('status', 'approved')
                ->whereYear('start_date', $month->year)
                ->whereMonth('start_date', $month->month)
                ->count();
        }

        // ── Birthdays ──
        $now = \Carbon::now()->addDays(1)->format('Y-m-d');
        $thirtyDaysFromNow = \Carbon::now()->addDays(30)->format('Y-m-d');
        $up_comming_births = User::where('business_id', $business_id)
            ->whereRaw("DATE_FORMAT(dob, '%m-%d') BETWEEN DATE_FORMAT('$now', '%m-%d') AND DATE_FORMAT('$thirtyDaysFromNow', '%m-%d')")
            ->orderBy('dob', 'asc')
            ->get();
        $today_births = User::where('business_id', $business_id)
            ->whereMonth('dob', \Carbon::now()->format('m'))
            ->whereDay('dob', \Carbon::now()->format('d'))
            ->get();

        return view('essentials::dashboard.hrm_dashboard')
            ->with(compact(
                'is_admin',
                'total_employees',
                'departments',
                'users_by_dept',
                'dept_labels',
                'dept_counts',
                'leave_status_labels',
                'leave_status_data',
                'leave_type_labels',
                'leave_type_data',
                'pending_leaves',
                'pending_leaves_count',
                'todays_leaves',
                'upcoming_leaves',
                'todays_holidays',
                'upcoming_holidays',
                'todays_attendances',
                'present_today',
                'absent_today',
                'attendance_trend_labels',
                'attendance_trend_data',
                'leave_trend_labels',
                'leave_trend_data',
                'up_comming_births',
                'today_births'
            ));
    }

    public function getUserSalesTargets()
    {
        $business_id = request()->session()->get('user.business_id');

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        $user_id = auth()->user()->id;

        if (!$is_admin) {
            abort(403, 'Unauthorized action.');
        }

        $this_month_start_date = \Carbon::today()->startOfMonth()->format('Y-m-d');
        $this_month_end_date = \Carbon::today()->endOfMonth()->format('Y-m-d');
        $last_month_start_date = \Carbon::parse('first day of last month')->format('Y-m-d');
        $last_month_end_date = \Carbon::parse('last day of last month')->format('Y-m-d');

        $settings = $this->essentialsUtil->getEssentialsSettings();

        $query = User::where('users.business_id', $business_id)
            ->join('transactions as t', 't.commission_agent', '=', 'users.id')
            ->where('t.type', 'sell')
            ->whereDate('transaction_date', '>=', $last_month_start_date)
            ->where('t.status', 'final');

        if (!empty($settings['calculate_sales_target_commission_without_tax']) && $settings['calculate_sales_target_commission_without_tax'] == 1) {
            $query->select(
                DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$last_month_start_date}' AND '{$last_month_end_date}', total_before_tax - shipping_charges - (SELECT SUM(item_tax*quantity) FROM transaction_sell_lines as tsl WHERE tsl.transaction_id=t.id), 0) ) as total_sales_last_month"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$this_month_start_date}' AND '{$this_month_end_date}', total_before_tax - shipping_charges - (SELECT SUM(item_tax*quantity) FROM transaction_sell_lines as tsl WHERE tsl.transaction_id=t.id), 0) ) as total_sales_this_month")
            );
        } else {
            $query->select(
                DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$last_month_start_date}' AND '{$last_month_end_date}', final_total, 0)) as total_sales_last_month"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$this_month_start_date}' AND '{$this_month_end_date}', final_total, 0)) as total_sales_this_month")
            );
        }

        $query->groupBy('users.id');

        return Datatables::of($query)
            ->editColumn('total_sales_this_month', function ($row) {
                return $this->transactionUtil->num_f($row->total_sales_this_month, true);
            })
            ->editColumn('total_sales_last_month', function ($row) {
                return $this->transactionUtil->num_f($row->total_sales_last_month, true);
            })
            ->make(false);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function essentialsDashboard()
    {
        return view('essentials::dashboard.essentials_dashboard');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('essentials::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return view('essentials::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        return view('essentials::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
