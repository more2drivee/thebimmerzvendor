<?php

namespace Modules\Essentials\Utils;

use App\Transaction;
use App\Utils\Util;
use DB;
use Illuminate\Support\Facades\View;
use Modules\Essentials\Entities\EssentialsAllowanceAndDeduction;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsEmployeeBonus;
use Modules\Essentials\Entities\EssentialsEmployeeDeduction;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsSalaryAdvance;
use Modules\Essentials\Entities\EssentialsUserShift;
use Modules\Essentials\Entities\Shift;

class EssentialsUtil extends Util
{
    /**
     * Function to calculate total work duration of a user for a period of time
     *
     * @param  string  $unit
     * @param  int  $user_id
     * @param  int  $business_id
     * @param  int  $start_date = null
     * @param  int  $end_date = null
     */
    public function getTotalWorkDuration(
        $unit,
        $user_id,
        $business_id,
        $start_date = null,
        $end_date = null
    ) {
        $total_work_duration = 0;
        if ($unit == 'hour') {
            $query = EssentialsAttendance::where('business_id', $business_id)
                                        ->where('user_id', $user_id)
                                        ->whereNotNull('clock_out_time');

            if (! empty($start_date) && ! empty($end_date)) {
                $query->whereDate('clock_in_time', '>=', $start_date)
                            ->whereDate('clock_in_time', '<=', $end_date);
            }

            $minutes_sum = $query->select(DB::raw('SUM(TIMESTAMPDIFF(MINUTE, clock_in_time, clock_out_time)) as total_minutes'))->first();
            $total_work_duration = ! empty($minutes_sum->total_minutes) ? $minutes_sum->total_minutes / 60 : 0;
        }

        return number_format($total_work_duration, 2);
    }

    /**
     * Parses month and year from date
     *
     * @param  string  $month_year
     */
    public function getDateFromMonthYear($month_year)
    {
        $month_year_arr = explode('/', $month_year);
        $month = $month_year_arr[0];
        $year = $month_year_arr[1];

        $transaction_date = $year.'-'.$month.'-01';

        return $transaction_date;
    }

    /**
     * Retrieves all allowances and deductions of an employeee
     *
     * @param  int  $business_id
     * @param  int  $user_id
     * @param  string  $start_date = null
     * @param  string  $end_date = null
     */
    public function getEmployeeAllowancesAndDeductions($business_id, $user_id, $start_date = null, $end_date = null)
    {
        $query = EssentialsAllowanceAndDeduction::join('essentials_user_allowance_and_deductions as euad', 'euad.allowance_deduction_id', '=', 'essentials_allowances_and_deductions.id')
                ->where('business_id', $business_id)
                ->where('euad.user_id', $user_id);

        //Filter if applicable one
        if (! empty($start_date) && ! empty($end_date)) {
            $query->where(function ($q) use ($start_date, $end_date) {
                $q->whereNull('applicable_date')
                    ->orWhereBetween('applicable_date', [$start_date, $end_date]);
            });
        }
        $allowances_and_deductions = $query->get();

        return $allowances_and_deductions;
    }

    /**
     * Validates user clock in and returns available shift id
     */
    public function checkUserShift($user_id, $settings, $clock_in_time = null)
    {
        $shift_id = null;
        $shift_date = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time) : \Carbon::now();
        $shift_datetime = $shift_date->format('Y-m-d');
        $day_string = strtolower($shift_date->format('l'));
        $grace_before_checkin = ! empty($settings['grace_before_checkin']) ? (int) $settings['grace_before_checkin'] : 0;
        $grace_after_checkin = ! empty($settings['grace_after_checkin']) ? (int) $settings['grace_after_checkin'] : 0;
        $clock_in_start = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time)->subMinutes($grace_before_checkin) : \Carbon::now()->subMinutes($grace_before_checkin);
        $clock_in_end = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time)->addMinutes($grace_after_checkin) : \Carbon::now()->addMinutes($grace_after_checkin);

        \Log::info('DIAG HRM checkUserShift', [
            'user_id' => $user_id,
            'clock_in_time' => $clock_in_time ?? 'now',
            'shift_datetime' => $shift_datetime,
            'day_string' => $day_string,
            'grace_before' => $grace_before_checkin,
            'grace_after' => $grace_after_checkin,
            'clock_in_start' => $clock_in_start->toDateTimeString(),
            'clock_in_end' => $clock_in_end->toDateTimeString(),
        ]);

        $user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=', 'essentials_user_shifts.essentials_shift_id')
                    ->where('user_id', $user_id)
                    ->where('start_date', '<=', $shift_datetime)
                    ->where(function ($q) use ($shift_datetime) {
                        $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $shift_datetime);
                    })
                    ->select('essentials_user_shifts.*', 's.holidays', 's.start_time', 's.end_time', 's.type')
                    ->get();

        foreach ($user_shifts as $shift) {
            $holidays = json_decode($shift->holidays, true);
            //check if holiday
            if (is_array($holidays) && in_array($day_string, $holidays)) {
                \Log::info('DIAG HRM shift_skipped_holiday', [
                    'shift_id' => $shift->essentials_shift_id,
                    'day_string' => $day_string,
                    'holidays' => $holidays,
                ]);
                continue;
            }

            // Parse shift start and end times with date
            $parsed_shift_start = ! empty($shift->start_time) ? \Carbon::parse($shift_datetime . ' ' . $shift->start_time) : null;
            $parsed_shift_end = ! empty($shift->end_time) ? \Carbon::parse($shift_datetime . ' ' . $shift->end_time) : null;
            $current_time = $shift_date;

            // Check if current clock-in time is within shift window (with grace periods)
            $is_between = $parsed_shift_start && $parsed_shift_end
                && $current_time->between($parsed_shift_start->subMinutes($grace_before_checkin), $parsed_shift_end->addMinutes($grace_after_checkin));
            $is_flexible = $shift->type == 'flexible_shift';

            \Log::info('DIAG HRM shift_check', [
                'shift_id' => $shift->essentials_shift_id,
                'shift_type' => $shift->type,
                'shift_start_time_db' => $shift->start_time,
                'shift_end_time_db' => $shift->end_time,
                'parsed_shift_start' => $parsed_shift_start ? $parsed_shift_start->toDateTimeString() : null,
                'parsed_shift_end' => $parsed_shift_end ? $parsed_shift_end->toDateTimeString() : null,
                'current_time' => $current_time->toDateTimeString(),
                'grace_before' => $grace_before_checkin,
                'grace_after' => $grace_after_checkin,
                'is_between' => $is_between,
                'is_flexible' => $is_flexible,
                'will_match' => $is_between || $is_flexible,
            ]);

            //Check allocated shift time
            if ($is_between || $is_flexible) {
                \Log::info('DIAG HRM shift_matched', [
                    'shift_id' => $shift->essentials_shift_id,
                    'reason' => $is_flexible ? 'flexible_shift' : 'time_between',
                ]);
                return $shift->essentials_shift_id;
            }
        }

        \Log::info('DIAG HRM no_shift_matched', [
            'user_id' => $user_id,
            'shifts_checked' => $user_shifts->count(),
        ]);

        return $shift_id;
    }

    /**
     * Validates user clock out
     */
    public function canClockOut($clock_in, $settings, $clock_out_time = null)
    {
        $shift = Shift::find($clock_in->essentials_shift_id);
        if (empty($shift->end_time)) {
            return true;
        }

        $grace_before_checkout = ! empty($settings['grace_before_checkout']) ? (int) $settings['grace_before_checkout'] : 0;
        $grace_after_checkout = ! empty($settings['grace_after_checkout']) ? (int) $settings['grace_after_checkout'] : 0;

        // Current clock-out time
        $clock_out_current = empty($clock_out_time) ? \Carbon::now() : \Carbon::parse($clock_out_time);

        // Parse shift start and end times with date
        $shift_datetime = $clock_out_current->format('Y-m-d');
        $parsed_shift_start = ! empty($shift->start_time) ? \Carbon::parse($shift_datetime . ' ' . $shift->start_time) : null;
        $parsed_shift_end = ! empty($shift->end_time) ? \Carbon::parse($shift_datetime . ' ' . $shift->end_time) : null;

        // Check if current clock-out time is within shift window (with grace periods)
        // Allow clock out anytime after shift start + grace_before, and before shift end + grace_after
        $can_clock_out = $parsed_shift_start && $parsed_shift_end
            && $clock_out_current->between($parsed_shift_start->subMinutes($grace_before_checkout), $parsed_shift_end->addMinutes($grace_after_checkout));

        if ($can_clock_out || $shift->type == 'flexible_shift') {
            return true;
        } else {
            return false;
        }
    }

    public function clockin($data, $essentials_settings)
    {
        //Check user can clockin
        $clock_in_time = is_object($data['clock_in_time']) ? $data['clock_in_time']->toDateTimeString() : $data['clock_in_time'];

        $shift = $this->checkUserShift($data['user_id'], $essentials_settings, $clock_in_time);

        if (empty($shift)) {
            $available_shifts = $this->getAllAvailableShiftsForGivenUser($data['business_id'], $data['user_id']);

            $available_shifts_html = view('essentials::attendance.avail_shifts')
                                        ->with(compact('available_shifts'))
                                        ->render();

            $output = ['success' => false,
                'msg' => __('essentials::lang.shift_not_allocated'),
                'type' => 'clock_in',
                'shift_details' => $available_shifts_html,
            ];

            return $output;
        }

        $data['essentials_shift_id'] = $shift;

        //Check if already clocked in
        $count = EssentialsAttendance::where('business_id', $data['business_id'])
                                ->where('user_id', $data['user_id'])
                                ->whereNull('clock_out_time')
                                ->count();

        //Check if already completed a shift today
        $completed_today = EssentialsAttendance::where('business_id', $data['business_id'])
                                ->where('user_id', $data['user_id'])
                                ->whereNotNull('clock_out_time')
                                ->whereDate('clock_in_time', '=', \Carbon::parse($clock_in_time)->format('Y-m-d'))
                                ->count();

        if ($count > 0) {
            $output = ['success' => false,
                'msg' => __('essentials::lang.already_clocked_in'),
                'type' => 'clock_in',
            ];
            return $output;
        }

        if ($completed_today > 0) {
            $output = ['success' => false,
                'msg' => __('essentials::lang.already_completed_shift_today'),
                'type' => 'clock_in',
            ];
            return $output;
        }

        EssentialsAttendance::create($data);

        $shift_info = Shift::getGivenShiftInfo($data['business_id'], $shift);
        $current_shift_html = view('essentials::attendance.current_shift')
                                ->with(compact('shift_info'))
                                ->render();

        $output = ['success' => true,
            'msg' => __('essentials::lang.clock_in_success'),
            'type' => 'clock_in',
            'current_shift' => $current_shift_html,
        ];

        return $output;
    }

    public function clockout($data, $essentials_settings)
    {

        //Get clock in
        $clock_in = EssentialsAttendance::where('business_id', $data['business_id'])
                                ->where('user_id', $data['user_id'])
                                ->whereNull('clock_out_time')
                                ->first();
        $clock_out_time = is_object($data['clock_out_time']) ? $data['clock_out_time']->toDateTimeString() : $data['clock_out_time'];

        if (! empty($clock_in)) {
            $can_clockout = $this->canClockOut($clock_in, $essentials_settings, $clock_out_time);
            if (! $can_clockout) {
                $output = ['success' => false,
                    'msg' => __('essentials::lang.shift_not_over'),
                    'type' => 'clock_out',
                ];

                return $output;
            }

            $clock_in->clock_out_time = $data['clock_out_time'];
            $clock_in->clock_out_note = $data['clock_out_note'];
            $clock_in->clock_out_location = $data['clock_out_location'] ?? '';
            $clock_in->save();

            $output = ['success' => true,
                'msg' => __('essentials::lang.clock_out_success'),
                'type' => 'clock_out',
            ];
        } else {
            $output = ['success' => false,
                'msg' => __('essentials::lang.not_clocked_in'),
                'type' => 'clock_out',
            ];
        }

        return $output;
    }

    public function getAllAvailableShiftsForGivenUser($business_id, $user_id)
    {
        $available_user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=',
                                    'essentials_user_shifts.essentials_shift_id')
                                    ->where('user_id', $user_id)
                                    ->where('s.business_id', $business_id)
                                    ->whereDate('start_date', '<=', \Carbon::today())
                                    ->whereDate('end_date', '>=', \Carbon::today())
                                    ->select('essentials_user_shifts.start_date', 'essentials_user_shifts.end_date',
                                        's.name', 's.type', 's.start_time', 's.end_time', 's.holidays')
                                    ->get();

        return $available_user_shifts;
    }

    /**
     * get total leaves of and employee for given date
     *
     * @param  int  $business_id
     * @param  int  $employee_id
     * @param  string  $start_date
     * @param  string  $end_date
     */
    public function getTotalLeavesForGivenDateOfAnEmployee($business_id, $employee_id, $start_date, $end_date)
    {
        $leaves = EssentialsLeave::where('business_id', $business_id)
                        ->where('user_id', $employee_id)
                        ->whereDate('start_date', '>=', $start_date)
                        ->whereDate('end_date', '<=', $end_date)
                        ->get();

        $total_leaves = 0;
        foreach ($leaves as $key => $leave) {
            $start_date = \Carbon::parse($leave->start_date);
            $end_date = \Carbon::parse($leave->end_date);

            $diff = $start_date->diffInDays($end_date);
            $diff += 1;
            $total_leaves += $diff;
        }

        return $total_leaves;
    }

    public function getTotalDaysWorkedForGivenDateOfAnEmployee($business_id, $employee_id, $start_date, $end_date)
    {
        $attendances = EssentialsAttendance::where('business_id', $business_id)
                        ->where('user_id', $employee_id)
                        ->whereNotNull('clock_out_time')
                        ->whereDate('clock_in_time', '>=', $start_date)
                        ->whereDate('clock_in_time', '<=', $end_date)
                        ->get()
                        ->groupBy(function ($attendance, $key) {
                            return \Carbon::parse($attendance->clock_in_time)->format('Y-m-d');
                        });

        return count($attendances);
    }

    public function getPayrollQuery($business_id)
    {
        $payrolls = Transaction::where('transactions.business_id', $business_id)
                    ->where('type', 'payroll')
                    ->join('users as u', 'u.id', '=', 'transactions.expense_for')
                    ->leftJoin('categories as dept', 'u.essentials_department_id', '=', 'dept.id')
                    ->leftJoin('categories as dsgn', 'u.essentials_designation_id', '=', 'dsgn.id')
                    ->leftJoin('essentials_payroll_group_transactions as epgt', 'transactions.id', '=', 'epgt.transaction_id')
                    ->leftJoin('essentials_payroll_groups as epg', 'epgt.payroll_group_id', '=', 'epg.id')
                    ->select([
                        'transactions.id',
                        DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user"),
                        'final_total',
                        'transaction_date',
                        'ref_no',
                        'transactions.payment_status',
                        'dept.name as department',
                        'dsgn.name as designation',
                        'epgt.payroll_group_id',
                    ]);

        return $payrolls;
    }

    public function getEssentialsSettings()
    {
        $settings = request()->session()->get('business.essentials_settings');
        $settings = ! empty($settings) ? json_decode($settings, true) : [];

        return $settings;
    }

    /**
     * Get expected working days for an employee based on assigned shifts (excluding shift holidays and system holidays)
     */
    public function getExpectedWorkingDays($business_id, $employee_id, $start_date, $end_date, $location_id = null)
    {
        $start = \Carbon::parse($start_date);
        $end = \Carbon::parse($end_date);

        // Get user shifts for the period
        $user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=', 'essentials_user_shifts.essentials_shift_id')
            ->where('user_id', $employee_id)
            ->where('start_date', '<=', $end->format('Y-m-d'))
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start->format('Y-m-d'));
            })
            ->select('essentials_user_shifts.*', 's.holidays', 's.start_time', 's.end_time', 's.type')
            ->get();

        // Get system holidays
        $system_holidays = $this->getSystemHolidayDates($business_id, $start_date, $end->format('Y-m-d'), $location_id);

        $working_days = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            $day_string = strtolower($current->format('l'));
            $date_str = $current->format('Y-m-d');

            // Skip system holidays
            if (in_array($date_str, $system_holidays)) {
                $current->addDay();
                continue;
            }

            // Check if any shift covers this day
            foreach ($user_shifts as $shift) {
                $shift_start = \Carbon::parse($shift->start_date);
                $shift_end = ! empty($shift->end_date) ? \Carbon::parse($shift->end_date) : $end;

                if ($current->between($shift_start, $shift_end)) {
                    $holidays = is_array($shift->holidays) ? $shift->holidays : json_decode($shift->holidays, true);
                    if (! is_array($holidays) || ! in_array($day_string, $holidays)) {
                        $working_days++;
                        break;
                    }
                }
            }

            $current->addDay();
        }

        return $working_days;
    }

    /**
     * Get expected shift hours for an employee in a period
     */
    public function getExpectedShiftHours($business_id, $employee_id, $start_date, $end_date, $location_id = null)
    {
        $start = \Carbon::parse($start_date);
        $end = \Carbon::parse($end_date);

        $user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=', 'essentials_user_shifts.essentials_shift_id')
            ->where('user_id', $employee_id)
            ->where('start_date', '<=', $end->format('Y-m-d'))
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start->format('Y-m-d'));
            })
            ->select('essentials_user_shifts.*', 's.holidays', 's.start_time', 's.end_time', 's.type')
            ->get();

        $system_holidays = $this->getSystemHolidayDates($business_id, $start_date, $end->format('Y-m-d'), $location_id);

        $total_hours = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            $day_string = strtolower($current->format('l'));
            $date_str = $current->format('Y-m-d');

            if (in_array($date_str, $system_holidays)) {
                $current->addDay();
                continue;
            }

            foreach ($user_shifts as $shift) {
                $shift_start = \Carbon::parse($shift->start_date);
                $shift_end = ! empty($shift->end_date) ? \Carbon::parse($shift->end_date) : $end;

                if ($current->between($shift_start, $shift_end)) {
                    $holidays = is_array($shift->holidays) ? $shift->holidays : json_decode($shift->holidays, true);
                    if (! is_array($holidays) || ! in_array($day_string, $holidays)) {
                        if (! empty($shift->start_time) && ! empty($shift->end_time)) {
                            $s = \Carbon::parse($shift->start_time);
                            $e = \Carbon::parse($shift->end_time);
                            if ($e->lt($s)) {
                                $e->addDay();
                            }
                            $total_hours += $s->diffInMinutes($e) / 60;
                        } else {
                            $total_hours += 8; // default 8 hours for flexible shifts
                        }
                        break;
                    }
                }
            }

            $current->addDay();
        }

        return number_format($total_hours, 2);
    }

    /**
     * Get system holiday dates as array of Y-m-d strings
     */
    public function getSystemHolidayDates($business_id, $start_date, $end_date, $location_id = null)
    {
        $query = \DB::table('essentials_holidays')
            ->where('business_id', $business_id)
            ->where(function ($q) use ($start_date, $end_date) {
                $q->whereBetween('start_date', [$start_date, $end_date])
                    ->orWhereBetween('end_date', [$start_date, $end_date])
                    ->orWhere(function ($q2) use ($start_date, $end_date) {
                        $q2->where('start_date', '<=', $start_date)
                            ->where('end_date', '>=', $end_date);
                    });
            });

        if (! empty($location_id)) {
            $query->where(function ($q) use ($location_id) {
                $q->where('location_id', $location_id)
                    ->orWhereNull('location_id');
            });
        }

        $holidays = $query->get();

        $dates = [];
        foreach ($holidays as $holiday) {
            $hs = \Carbon::parse($holiday->start_date);
            $he = \Carbon::parse($holiday->end_date);
            while ($hs->lte($he)) {
                $dates[] = $hs->format('Y-m-d');
                $hs->addDay();
            }
        }

        return array_unique($dates);
    }

    /**
     * Get holidays count in a period
     */
    public function getHolidaysCount($business_id, $start_date, $end_date, $location_id = null)
    {
        return count($this->getSystemHolidayDates($business_id, $start_date, $end_date, $location_id));
    }

    /**
     * Get deducted leaves summary for an employee in a period
     * Returns ['total_deducted_days' => x, 'total_deduction_amount' => y, 'leaves' => [...]]
     */
    public function getDeductedLeavesSummary($business_id, $employee_id, $start_date, $end_date)
    {
        $leaves = EssentialsLeave::where('business_id', $business_id)
            ->where('user_id', $employee_id)
            ->where('status', 'approved')
            ->where(function ($q) use ($start_date, $end_date) {
                $q->whereBetween('start_date', [$start_date, $end_date])
                    ->orWhereBetween('end_date', [$start_date, $end_date])
                    ->orWhere(function ($q2) use ($start_date, $end_date) {
                        $q2->where('start_date', '<=', $start_date)
                            ->where('end_date', '>=', $end_date);
                    });
            })
            ->get();

        $total_deducted_days = 0;
        $total_deduction_amount = 0;
        $leave_details = [];

        foreach ($leaves as $leave) {
            $ls = \Carbon::parse($leave->start_date);
            $le = \Carbon::parse($leave->end_date);
            $days = $ls->diffInDays($le) + 1;

            $is_deducted = ($leave->deduct_from_salary == 'yes' && $leave->deduction_amount > 0);

            $leave_details[] = [
                'start_date' => $leave->start_date,
                'end_date' => $leave->end_date,
                'days' => $days,
                'deducted' => $is_deducted,
                'deduction_amount' => $is_deducted ? $leave->deduction_amount : 0,
                'leave_type_id' => $leave->essentials_leave_type_id,
            ];

            if ($is_deducted) {
                $total_deducted_days += $days;
                $total_deduction_amount += $leave->deduction_amount;
            }
        }

        return [
            'total_leave_days' => array_sum(array_column($leave_details, 'days')),
            'total_deducted_days' => $total_deducted_days,
            'total_deduction_amount' => $total_deduction_amount,
            'leaves' => $leave_details,
        ];
    }

    /**
     * Get attendance calendar data for an employee in a period
     * Returns array of dates with status (present/absent/leave/holiday)
     */
    public function getAttendanceCalendarData($business_id, $employee_id, $start_date, $end_date, $location_id = null)
    {
        $start = \Carbon::parse($start_date);
        $end = \Carbon::parse($end_date);

        // Get attendance records
        $attendances = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $employee_id)
            ->whereDate('clock_in_time', '>=', $start_date)
            ->whereDate('clock_in_time', '<=', $end_date)
            ->get()
            ->groupBy(function ($att) {
                return \Carbon::parse($att->clock_in_time)->format('Y-m-d');
            });

        // Get leaves
        $leaves = EssentialsLeave::where('business_id', $business_id)
            ->where('user_id', $employee_id)
            ->where('status', 'approved')
            ->where(function ($q) use ($start_date, $end_date) {
                $q->whereBetween('start_date', [$start_date, $end_date])
                    ->orWhereBetween('end_date', [$start_date, $end_date]);
            })
            ->get();

        $leave_dates = [];
        foreach ($leaves as $leave) {
            $ls = \Carbon::parse($leave->start_date);
            $le = \Carbon::parse($leave->end_date);
            while ($ls->lte($le)) {
                $leave_dates[$ls->format('Y-m-d')] = [
                    'deducted' => ($leave->deduct_from_salary == 'yes'),
                    'leave_type_id' => $leave->essentials_leave_type_id,
                ];
                $ls->addDay();
            }
        }

        // Get holidays & shifts
        $system_holidays = $this->getSystemHolidayDates($business_id, $start_date, $end->format('Y-m-d'), $location_id);

        $user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=', 'essentials_user_shifts.essentials_shift_id')
            ->where('user_id', $employee_id)
            ->where('start_date', '<=', $end->format('Y-m-d'))
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start->format('Y-m-d'));
            })
            ->select('essentials_user_shifts.*', 's.holidays', 's.start_time', 's.end_time', 's.name as shift_name')
            ->get();

        $calendar = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $date_str = $current->format('Y-m-d');
            $day_string = strtolower($current->format('l'));

            $entry = [
                'date' => $date_str,
                'day' => $current->format('D'),
                'status' => 'no_shift',
                'hours' => 0,
                'clock_in' => null,
                'clock_out' => null,
                'shift_name' => null,
            ];

            if (in_array($date_str, $system_holidays)) {
                $entry['status'] = 'holiday';
            } elseif (isset($leave_dates[$date_str])) {
                $entry['status'] = $leave_dates[$date_str]['deducted'] ? 'leave_deducted' : 'leave';
            } elseif (isset($attendances[$date_str])) {
                $att = $attendances[$date_str]->first();
                $entry['status'] = 'present';
                $entry['clock_in'] = $att->clock_in_time;
                $entry['clock_out'] = $att->clock_out_time;
                if ($att->clock_in_time && $att->clock_out_time) {
                    $entry['hours'] = round(\Carbon::parse($att->clock_in_time)->diffInMinutes(\Carbon::parse($att->clock_out_time)) / 60, 2);
                }
            } else {
                // Check if this was a working day (has shift, not shift holiday)
                $is_working_day = false;
                foreach ($user_shifts as $shift) {
                    $shift_start = \Carbon::parse($shift->start_date);
                    $shift_end = ! empty($shift->end_date) ? \Carbon::parse($shift->end_date) : $end;
                    if ($current->between($shift_start, $shift_end)) {
                        $holidays = is_array($shift->holidays) ? $shift->holidays : json_decode($shift->holidays, true);
                        if (! is_array($holidays) || ! in_array($day_string, $holidays)) {
                            $is_working_day = true;
                            $entry['shift_name'] = $shift->shift_name;
                            break;
                        } else {
                            $entry['status'] = 'day_off';
                        }
                    }
                }
                if ($is_working_day && $current->lt(\Carbon::now())) {
                    $entry['status'] = 'absent';
                }
            }

            // Attach shift name for present days
            if ($entry['status'] == 'present' && empty($entry['shift_name'])) {
                foreach ($user_shifts as $shift) {
                    $shift_start = \Carbon::parse($shift->start_date);
                    $shift_end = ! empty($shift->end_date) ? \Carbon::parse($shift->end_date) : $end;
                    if ($current->between($shift_start, $shift_end)) {
                        $entry['shift_name'] = $shift->shift_name;
                        break;
                    }
                }
            }

            $calendar[] = $entry;
            $current->addDay();
        }

        return $calendar;
    }

    /**
     * Get active employee bonuses for payroll period
     * Fetches bonuses with status=active and matching apply_on logic
     */
    public function getEmployeeBonusesForPayroll($business_id, $employee_id, $start_date, $end_date)
    {
        $bonuses = EssentialsEmployeeBonus::where('business_id', $business_id)
            ->where('user_id', $employee_id)
            ->where('status', 'active')
            ->where(function ($q) use ($start_date, $end_date) {
                $q->whereNull('start_date')
                    ->orWhere(function ($q2) use ($start_date, $end_date) {
                        $q2->where('start_date', '<=', $end_date)
                            ->where(function ($q3) use ($start_date) {
                                $q3->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $start_date);
                            });
                    });
            })
            ->get();

        return $bonuses;
    }

    /**
     * Get active employee deductions for payroll period
     * Fetches deductions with status=active and matching apply_on logic
     */
    public function getEmployeeDeductionsForPayroll($business_id, $employee_id, $start_date, $end_date)
    {
        $deductions = EssentialsEmployeeDeduction::where('business_id', $business_id)
            ->where('user_id', $employee_id)
            ->where('status', 'active')
            ->where(function ($q) use ($start_date, $end_date) {
                $q->whereNull('start_date')
                    ->orWhere(function ($q2) use ($start_date, $end_date) {
                        $q2->where('start_date', '<=', $end_date)
                            ->where(function ($q3) use ($start_date) {
                                $q3->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $start_date);
                            });
                    });
            })
            ->get();

        return $deductions;
    }

    /**
     * Get approved salary advances that should be deducted in this payroll period.
     * $month_year format: YYYY-MM (matches HTML input[type=month] stored value)
     */
    public function getSalaryAdvancesForPayroll($business_id, $employee_id, $month_year)
    {
        $advances = EssentialsSalaryAdvance::where('business_id', $business_id)
            ->where('user_id', $employee_id)
            ->where('status', 'approved')
            ->where(function ($q) use ($month_year) {
                $q->where('deduct_from_payroll', $month_year)
                  ->orWhereNull('deduct_from_payroll');
            })
            ->get();

        return $advances;
    }

    /**
     * Get employee warnings count for a period (used for payroll summary)
     */
    public function getEmployeeWarningsCount($business_id, $employee_id, $start_date, $end_date)
    {
        return \Modules\Essentials\Entities\EssentialsEmployeeWarning::where('business_id', $business_id)
            ->where('user_id', $employee_id)
            ->whereDate('warning_date', '>=', $start_date)
            ->whereDate('warning_date', '<=', $end_date)
            ->count();
    }

    /**
     * Get shift calendar data for an employee
     */
    public function getShiftCalendarData($business_id, $employee_id, $start_date, $end_date)
    {
        $start = \Carbon::parse($start_date);
        $end = \Carbon::parse($end_date);

        $user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=', 'essentials_user_shifts.essentials_shift_id')
            ->where('user_id', $employee_id)
            ->where('start_date', '<=', $end->format('Y-m-d'))
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start->format('Y-m-d'));
            })
            ->select('essentials_user_shifts.*', 's.holidays', 's.start_time', 's.end_time', 's.name as shift_name', 's.type as shift_type')
            ->get();

        $calendar = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $date_str = $current->format('Y-m-d');
            $day_string = strtolower($current->format('l'));

            $entry = [
                'date' => $date_str,
                'day' => $current->format('D'),
                'shift_name' => null,
                'start_time' => null,
                'end_time' => null,
                'is_holiday' => false,
                'shift_type' => null,
            ];

            foreach ($user_shifts as $shift) {
                $shift_start = \Carbon::parse($shift->start_date);
                $shift_end = ! empty($shift->end_date) ? \Carbon::parse($shift->end_date) : $end;
                if ($current->between($shift_start, $shift_end)) {
                    $holidays = is_array($shift->holidays) ? $shift->holidays : json_decode($shift->holidays, true);
                    $entry['shift_name'] = $shift->shift_name;
                    $entry['start_time'] = $shift->start_time;
                    $entry['end_time'] = $shift->end_time;
                    $entry['shift_type'] = $shift->shift_type;
                    $entry['is_holiday'] = (is_array($holidays) && in_array($day_string, $holidays));
                    break;
                }
            }

            $calendar[] = $entry;
            $current->addDay();
        }

        return $calendar;
    }
}
