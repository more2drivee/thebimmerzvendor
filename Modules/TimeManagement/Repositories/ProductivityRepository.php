<?php

namespace Modules\TimeManagement\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductivityRepository
{
    /**
     * Get efficiency rate for users within a date range.
     */
    public function getEfficiencyRate(int $business_id, $workshop_id = null, $location_id = null, $start_date = null, $end_date = null): float
    {
        $start = $start_date ? Carbon::parse($start_date)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $end_date ? Carbon::parse($end_date)->endOfDay() : Carbon::now()->endOfWeek();

        // Productive seconds
        $attendanceRepo = app(AttendanceRepository::class);
        $productive = $attendanceRepo->getProductiveHours($business_id, $workshop_id, $location_id, $start->toDateString(), $end->toDateString()) * 3600;

        // Scheduled seconds from shifts
        $shifts = DB::table('essentials_user_shifts as eus')
            ->join('essentials_shifts as s', 's.id', '=', 'eus.essentials_shift_id')
            ->select('s.start_time', 's.end_time', 'eus.user_id', 'eus.start_date', 'eus.end_date');

        if (!empty($workshop_id) || !empty($location_id)) {
            $shifts->whereExists(function ($sub) use ($business_id, $workshop_id, $location_id) {
                $sub->from('repair_job_sheets as rjs')
                    ->join('transactions as t', 't.repair_job_sheet_id', '=', 'rjs.id')
                    ->where('rjs.business_id', $business_id)
                    ->where('t.sub_type', 'repair')
                    ->where('t.status', 'under processing')
                    ->when($workshop_id, fn($w) => $w->where('rjs.workshop_id', $workshop_id))
                    ->when($location_id, fn($w) => $w->where('rjs.location_id', $location_id))
                    ->whereRaw("JSON_CONTAINS(COALESCE(rjs.service_staff, '[]'), JSON_QUOTE(CAST(eus.user_id as CHAR)))");
            });
        }

        $scheduled_seconds = 0;
        $days = $start->diffInDays($end) + 1;
        foreach ($shifts->get() as $s) {
            $startTime = Carbon::parse($s->start_time);
            $endTime = Carbon::parse($s->end_time);
            $daily = max(0, $endTime->diffInSeconds($startTime));
            // determine overlap days within assignment window
            $assignStart = $s->start_date ? Carbon::parse($s->start_date) : null;
            $assignEnd = $s->end_date ? Carbon::parse($s->end_date) : null;
            $rangeStart = $assignStart && $assignStart->gt($start) ? $assignStart : $start;
            $rangeEnd = $assignEnd && $assignEnd->lt($end) ? $assignEnd : $end;
            if ($rangeEnd->lt($rangeStart)) { continue; }
            $overlapDays = $rangeStart->diffInDays($rangeEnd) + 1;
            $scheduled_seconds += $daily * $overlapDays;
        }

        if ($scheduled_seconds <= 0) {
            return 0.0;
        }
        return round(($productive / $scheduled_seconds) * 100, 0);
    }

    /**
     * Get performance summary for users within a date range.
     */
    public function getPerformanceSummary(int $business_id, array $filters)
    {
        $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : Carbon::now()->startOfWeek();
        $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : Carbon::now()->endOfWeek();
        $workshop_id = $filters['workshop_id'] ?? null;
        $location_id = $filters['location_id'] ?? null;
        
        // Build base user query for technical staff with location filter
        $baseUserQuery = DB::table('users as u')
            ->whereNull('u.deleted_at')
            ->where('u.allow_login', 0)
            ->where('u.user_type', 'user');

        if ($location_id) {
            $baseUserQuery->where('u.location_id', $location_id);
        }

        // Get technical staff user IDs for filtering
        $techUserIds = $baseUserQuery->pluck('u.id')->toArray();
        
        if (empty($techUserIds)) {
            return collect([]);
        }

        // Get productive hours from timer tracking (actual work on job sheets)
        $productiveHoursQuery = DB::table('timer_tracking as tt')
            ->whereIn('tt.user_id', $techUserIds)
            ->whereBetween('tt.started_at', [$start, $end]);

        if ($workshop_id) {
            $productiveHoursQuery->join('job_sheets as js', 'js.id', '=', 'tt.job_sheet_id')
                ->where('js.workshop_id', $workshop_id);
        }

        $productiveHours = $productiveHoursQuery
            ->selectRaw('tt.user_id, SUM(TIMESTAMPDIFF(SECOND, tt.started_at, COALESCE(tt.completed_at, NOW())) - COALESCE(tt.total_paused_duration, 0)) as prod_sec')
            ->groupBy('tt.user_id')
            ->pluck('prod_sec', 'user_id')
            ->toArray();

        // Get user details for all technicians - don't filter by location here
        $usersData = DB::table('users as u')
            ->whereIn('u.id', $techUserIds)
            ->whereNull('u.deleted_at')
            ->where('u.allow_login', 0)
            ->where('u.user_type', 'user');

        if ($location_id) {
            $usersData->where('u.location_id', $location_id);
        }

        $usersData = $usersData
            ->select('u.id', DB::raw("TRIM(CONCAT_WS(' ', COALESCE(u.surname, ''), COALESCE(u.first_name, ''), COALESCE(u.last_name, ''))) as name"))
            ->orderBy('name')
            ->get();

    // Always get business working days and hours from logged-in user's business
    $user = auth()->user();
    $business_id = $user ? $user->business_id : $business_id;
    $common_settings_json = DB::table('business')->where('id', $business_id)->value('common_settings');
    $common_settings = json_decode($common_settings_json ?? '{}', true);
    $work_days = $common_settings['work_days'] ?? [];
    $work_hours = $common_settings['work_hours'] ?? [];

        // Build scheduled seconds map using business settings
        $schedMap = [];
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->copy()->addDay());
        foreach ($usersData as $user) {
            $totalSeconds = 0;
            foreach ($period as $date) {
                $weekday = strtolower($date->format('l'));
                if (!empty($work_days[$weekday])) {
                    $dayHours = $work_hours[$weekday] ?? [];
                    if (!empty($dayHours['start']) && !empty($dayHours['end'])) {
                        $startTime = Carbon::parse($dayHours['start']);
                        $endTime = Carbon::parse($dayHours['end']);
                        $dailySeconds = max(0, $endTime->diffInSeconds($startTime));
                        $totalSeconds += $dailySeconds;
                    } elseif (!empty($dayHours['total'])) {
                        $totalSeconds += $dayHours['total'] * 3600;
                    } else {
                        $totalSeconds += 8 * 3600; // fallback default
                    }
                }
            }
            $schedMap[$user->id] = $totalSeconds;
        }

        // Late arrivals calculation - keep as is (attendance based)
        $lateArrivals = DB::table('essentials_attendances as ea')
            ->join('essentials_user_shifts as eus', 'eus.user_id', '=', 'ea.user_id')
            ->join('essentials_shifts as s', 's.id', '=', 'eus.essentials_shift_id')
            ->whereIn('ea.user_id', $techUserIds)
            ->whereBetween('ea.clock_in_time', [$start, $end])
            ->where(function($w) {
                $w->whereNull('eus.start_date')
                  ->orWhereRaw('DATE(ea.clock_in_time) >= eus.start_date');
            })
            ->where(function($w) {
                $w->whereNull('eus.end_date')
                  ->orWhereRaw('DATE(ea.clock_in_time) <= eus.end_date');
            })
            ->whereRaw('TIME(ea.clock_in_time) > TIME(s.start_time)')
            ->selectRaw('ea.user_id, COUNT(DISTINCT DATE(ea.clock_in_time)) as late_count')
            ->groupBy('ea.user_id')
            ->pluck('late_count', 'user_id')
            ->toArray();

        // Build final result
        $result = [];
        foreach ($usersData as $user) {
            $productiveSeconds = (int) ($productiveHours[$user->id] ?? 0);
            $scheduledSeconds = (int) ($schedMap[$user->id] ?? 0);
            $lateCount = (int) ($lateArrivals[$user->id] ?? 0);
            $result[] = (object) [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'productive_hours' => round($productiveSeconds / 3600, 1),
                'scheduled_hours' => round($scheduledSeconds / 3600, 1),
                'late_arrivals' => $lateCount,
            ];
        }

        return collect($result);
    }

    /**
     * Get efficiency rate per technician within a date range.
     * Efficiency Rate = (Total Standard Hours-FR / Total Actual Hours Worked) × 100
     * Where:
     * - Total Standard Hours-FR = service_hours of products in transaction lines of job sheets
     * - Total Actual Hours Worked = timer tracking data
     * Returns array with technician_id => efficiency_rate_percentage
     */
    public function getEfficiencyRateByTechnician($workshop_id = null, $location_id = null, $start_date = null, $end_date = null): array
    {
        $start = $start_date ? Carbon::parse($start_date)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $end_date ? Carbon::parse($end_date)->endOfDay() : Carbon::now()->endOfWeek();

        // Get actual hours worked per technician from timer tracking
        $actualHours = DB::table('timer_tracking as tt')
            ->join('repair_job_sheets as rjs', 'tt.job_sheet_id', '=', 'rjs.id')
            ->whereBetween('tt.started_at', [$start, $end])
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->selectRaw('tt.user_id, SUM(TIMESTAMPDIFF(SECOND, tt.started_at, COALESCE(tt.completed_at, NOW())) - COALESCE(tt.total_paused_duration, 0)) / 3600 as actual_hours')
            ->groupBy('tt.user_id')
            ->pluck('actual_hours', 'user_id')
            ->toArray();


        // Get standard hours per technician from products in job sheets they worked on
        $standardHours = DB::table('transactions as t')
            ->join('repair_job_sheets as rjs', 't.repair_job_sheet_id', '=', 'rjs.id')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->where('t.type', 'sell')
            ->where('t.sub_type', 'repair')
            ->where('p.enable_stock', 0)
            // Remove product_custom_field1 filter to match productivity calculation
            ->whereNotNull('p.serviceHours')
            ->whereBetween('t.transaction_date', [$start, $end])
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->select('rjs.id as job_sheet_id', 'rjs.service_staff', 'p.serviceHours', 'tsl.quantity')
            ->get();

        $techStandardHours = [];
        foreach ($standardHours as $record) {
            $technicians = json_decode($record->service_staff ?? '[]', true) ?: [];
            if (empty($technicians)) {
                continue;
            }

            $serviceHours = (float) ($record->serviceHours ?? 0);
            $quantity = (float) ($record->quantity ?? 1);
            $totalHours = $serviceHours * ($quantity > 0 ? $quantity : 1);
            $distributedHours = $totalHours / count($technicians);
            
            foreach ($technicians as $techId) {
                $techStandardHours[$techId] = ($techStandardHours[$techId] ?? 0) + $distributedHours;
            }
        }

        $result = [];
        foreach ($actualHours as $techId => $actual) {
            $standard = $techStandardHours[$techId] ?? 0;
            if ($actual > 0) {
                $result[$techId] = round(($standard / $actual) * 100, 2);
            } else {
                $result[$techId] = 0.0;
            }
        }

        return $result;
    }

    /**
     * Get productivity rate per technician within a date range.
     * Productivity Rate = (Total Labor Hours Sold-FR / Total Labor Hours Available) × 100
     * Where:
     * - Total Labor Hours Sold-FR = service_hours of products in transaction lines of job sheets
     * - Total Labor Hours Available = scheduled hours from attendance
     * Returns array with technician_id => productivity_rate_percentage
     */

    public function getProductivityRateByTechnician(int $business_id, $workshop_id = null, $location_id = null, $start_date = null, $end_date = null): array
    {

        $start = $start_date ? Carbon::parse($start_date)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $end_date ? Carbon::parse($end_date)->endOfDay() : Carbon::now()->endOfWeek();

        // Identify job sheets and technicians who worked on them
        $jobSheets = DB::table('repair_job_sheets as rjs')
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->whereBetween('rjs.created_at', [$start, $end])
            ->whereNotNull('rjs.service_staff')
            ->select('rjs.id', 'rjs.service_staff')
            ->get();


        $jobSheetTechnicians = [];
        foreach ($jobSheets as $sheet) {
            $techIds = json_decode($sheet->service_staff ?? '[]', true) ?: [];
            if (empty($techIds)) {
                continue;
            }
            $jobSheetTechnicians[$sheet->id] = array_map('intval', $techIds);
        }

        $standardHoursByTech = [];

        if (!empty($jobSheetTechnicians)) {
            $rawTransactions = DB::table('transactions as t')
                ->select('t.id', 't.repair_job_sheet_id', 't.type', 't.sub_type', 't.status', 't.transaction_date')
                ->whereIn('t.repair_job_sheet_id', array_keys($jobSheetTechnicians))
                ->get();


            $standardRecords = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->where('t.type', 'sell')
                ->where('t.sub_type', 'repair')
                ->whereIn('t.repair_job_sheet_id', array_keys($jobSheetTechnicians))
                // ->whereBetween('t.transaction_date', [$start, $end])
                ->where('p.enable_stock', 0)
                ->whereNotNull('p.serviceHours')
                ->select(
                    't.repair_job_sheet_id',
                    't.id as transaction_id',
                    'p.serviceHours',
                    'tsl.quantity'
                )
                ->get();


            foreach ($standardRecords as $rec) {
                $techs = $jobSheetTechnicians[$rec->repair_job_sheet_id] ?? [];
                if (empty($techs)) {
                    continue;
                }
                $serviceHours = (float) ($rec->serviceHours ?? 0);
                $quantity = (float) ($rec->quantity ?? 1);
                $totalHours = $serviceHours * ($quantity > 0 ? $quantity : 1);
                
                // Distribute hours equally among all technicians assigned to this job sheet
                $distributedHours = count($techs) > 0 ? $totalHours / count($techs) : 0;
                
                foreach ($techs as $techId) {
                    $standardHoursByTech[$techId] = ($standardHoursByTech[$techId] ?? 0) + $distributedHours;
                }
            }
        }

        // Get scheduled hours per technician
        $scheduledHours = $this->getScheduledHoursByTechnician($business_id, $workshop_id, $location_id, $start, $end);


        $result = [];
        foreach ($scheduledHours as $techId => $scheduled) {
            // scheduledHours already returns hours, not seconds - remove incorrect division by 3600
            $scheduledHoursInHrs = $scheduled;
            $laborHoursSold = $standardHoursByTech[$techId] ?? 0.0;
            $result[$techId] = $scheduledHoursInHrs > 0
                ? round(($laborHoursSold / $scheduledHoursInHrs) * 100, 2)
                : 0.0;
        }


        return $result;
    }
    
    /**
     * Get utilization rate per technician within a date range.
     * Utilization Rate = (Actual Hours Worked on Jobs / Total Attendance Hours) × 100
     * Returns array with technician_id => utilization_rate_percentage
     */
    public function getUtilizationRateByTechnician($workshop_id = null, $location_id = null, $start_date = null, $end_date = null): array
    {
        $start = $start_date ? Carbon::parse($start_date)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $end_date ? Carbon::parse($end_date)->endOfDay() : Carbon::now()->endOfWeek();

        // Get actual hours worked on jobs per technician (from timer tracking)
        $actualHours = DB::table('timer_tracking as tt')
            ->join('repair_job_sheets as rjs', 'tt.job_sheet_id', '=', 'rjs.id')
            ->whereBetween('tt.started_at', [$start, $end])
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->selectRaw('tt.user_id, SUM(TIMESTAMPDIFF(SECOND, tt.started_at, COALESCE(tt.completed_at, NOW())) - COALESCE(tt.total_paused_duration, 0)) / 3600 as actual_hours')
            ->groupBy('tt.user_id')
            ->pluck('actual_hours', 'user_id')
            ->toArray();


        // Get total attendance hours per technician (clock-in to clock-out)
        $attendanceHours = DB::table('essentials_attendances as ea')
            ->whereBetween('ea.clock_in_time', [$start, $end])
            ->selectRaw("ea.user_id, SUM(GREATEST(0, TIMESTAMPDIFF(SECOND, ea.clock_in_time, CASE \n                WHEN ea.clock_out_time IS NULL THEN NOW() \n                WHEN ea.clock_out_time < ea.clock_in_time THEN DATE_ADD(ea.clock_out_time, INTERVAL 1 DAY) \n                ELSE ea.clock_out_time \n            END))) / 3600 as attendance_hours")
            ->groupBy('ea.user_id')
            ->pluck('attendance_hours', 'user_id')
            ->toArray();


        $result = [];
        foreach ($actualHours as $techId => $actual) {
            $attendance = max(0.0, $attendanceHours[$techId] ?? 0.0);
            if ($attendance > 0) {
                $result[$techId] = round(($actual / $attendance) * 100, 2);
            } else {
                $result[$techId] = 0.0;
            }
        }


        return $result;
    }

  

    /**
     * Get first time fix rate for each technician within a date range.
     * Uses bookings.is_callback and bookings.call_back_ref -> repair_job_sheets.id
     * Returns array with technician_id => first_time_fix_rate_percentage
     */
    public function getFirstTimeFixRateByTechnician($workshop_id = null, $location_id = null, $start_date = null, $end_date = null): array
    {
        // Pull relevant job sheets and service staff
        $jobSheets = DB::table('repair_job_sheets as rjs')
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->when($start_date, fn($q) => $q->where('rjs.created_at', '>=', $start_date))
            ->when($end_date, fn($q) => $q->where('rjs.created_at', '<=', $end_date))
            ->select('rjs.id', 'rjs.service_staff')
            ->get();

        if ($jobSheets->isEmpty()) {
            return [];
        }

        // Identify job sheets that had callbacks
        $comebackJobSheetIds = DB::table('repair_job_sheets as rjs')
            ->join('bookings as b', function ($join) {
                $join->on(DB::raw('CAST(b.call_back_ref as UNSIGNED)'), '=', 'rjs.id')
                    ->where('b.is_callback', '=', 1);
            })
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->when($start_date, fn($q) => $q->where('rjs.created_at', '>=', $start_date))
            ->when($end_date, fn($q) => $q->where('rjs.created_at', '<=', $end_date))
            ->pluck('rjs.id')
            ->toArray();

        $comebackSet = array_flip($comebackJobSheetIds);

        $techStats = [];
        foreach ($jobSheets as $job) {
            $technicians = json_decode($job->service_staff ?? '[]', true) ?: [];
            foreach ($technicians as $techId) {
                if (!isset($techStats[$techId])) {
                    $techStats[$techId] = ['total' => 0, 'comeback' => 0];
                }
                $techStats[$techId]['total']++;
                if (isset($comebackSet[$job->id])) {
                    $techStats[$techId]['comeback']++;
                }
            }
        }

        $result = [];
        foreach ($techStats as $techId => $s) {
            if ($s['total'] > 0) {
                $ftf = (($s['total'] - $s['comeback']) / $s['total']) * 100;
                $result[$techId] = round($ftf, 2);
            } else {
                $result[$techId] = 0.0;
            }
        }

        return $result;
    }

    /**
     * Get comeback ratio for each technician within a date range.
     * Returns array with technician_id => comeback_ratio_percentage
     */
    public function getComebackRatio(int $business_id, $workshop_id = null, $location_id = null, $start_date = null, $end_date = null): array
    {
        // Get all job sheets with their service staff (technicians)
        $jobSheets = DB::table('repair_job_sheets as rjs')
            ->where('rjs.business_id', $business_id)
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->when($start_date, fn($q) => $q->where('rjs.created_at', '>=', $start_date))
            ->when($end_date, fn($q) => $q->where('rjs.created_at', '<=', $end_date))
            ->select('rjs.id', 'rjs.service_staff')
            ->get();

        if ($jobSheets->isEmpty()) {
            return [];
        }

        // Get comeback job sheet IDs (jobs that had callbacks)
        $comebackJobSheets = DB::table('repair_job_sheets as rjs')
            ->join('bookings as b', function($join) {
                $join->on(DB::raw('CAST(b.call_back_ref as UNSIGNED)'), '=', 'rjs.id')
                     ->where('b.is_callback', '=', 1);
            })
            ->where('rjs.business_id', $business_id)
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->when($start_date, fn($q) => $q->where('rjs.created_at', '>=', $start_date))
            ->when($end_date, fn($q) => $q->where('rjs.created_at', '<=', $end_date))
            ->pluck('rjs.id')
            ->toArray();

        $comebackJobSheets = array_flip($comebackJobSheets); // For faster lookup

        $technicianStats = [];

        foreach ($jobSheets as $job) {
            $technicians = json_decode($job->service_staff ?? '[]', true) ?: [];

            foreach ($technicians as $technicianId) {
                if (!isset($technicianStats[$technicianId])) {
                    $technicianStats[$technicianId] = [
                        'total_jobs' => 0,
                        'comeback_jobs' => 0
                    ];
                }

                $technicianStats[$technicianId]['total_jobs']++;

                if (isset($comebackJobSheets[$job->id])) {
                    $technicianStats[$technicianId]['comeback_jobs']++;
                }
            }
        }

        $result = [];
        foreach ($technicianStats as $technicianId => $stats) {
            if ($stats['total_jobs'] > 0) {
                $ratio = ($stats['comeback_jobs'] / $stats['total_jobs']) * 100;
                $result[$technicianId] = round($ratio, 2);
            } else {
                $result[$technicianId] = 0.0;
            }
        }

        return $result;
    }

    /**
     * Get average repair time per technician within a date range.
     * Average Repair Time = (Total Timer Hours / Number of Job Sheets Worked On) per technician
     * Returns array with technician_id => average_repair_time_hours
     */
    public function getAverageRepairTimeByTechnician($workshop_id = null, $location_id = null, $start_date = null, $end_date = null): array
    {
        $start = $start_date ? Carbon::parse($start_date)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $end_date ? Carbon::parse($end_date)->endOfDay() : Carbon::now()->endOfWeek();

        // Get total repair hours per technician from timer tracking
        $repairHours = DB::table('timer_tracking as tt')
            ->join('repair_job_sheets as rjs', 'tt.job_sheet_id', '=', 'rjs.id')
            ->whereBetween('tt.started_at', [$start, $end])
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->selectRaw('tt.user_id, SUM(TIMESTAMPDIFF(SECOND, tt.started_at, COALESCE(tt.completed_at, NOW())) - COALESCE(tt.total_paused_duration, 0)) / 3600 as total_hours')
            ->groupBy('tt.user_id')
            ->pluck('total_hours', 'user_id')
            ->toArray();

        // Get number of job sheets each technician worked on
        $jobSheetCounts = DB::table('repair_job_sheets as rjs')
            ->whereBetween('rjs.created_at', [$start, $end])
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->select('rjs.id', 'rjs.service_staff')
            ->get();

        $techJobCounts = [];
        foreach ($jobSheetCounts as $job) {
            $technicians = json_decode($job->service_staff ?? '[]', true) ?: [];
            foreach ($technicians as $techId) {
                $techJobCounts[$techId] = ($techJobCounts[$techId] ?? 0) + 1;
            }
        }

        $result = [];
        foreach ($repairHours as $techId => $hours) {
            $jobCount = $techJobCounts[$techId] ?? 0;
            if ($jobCount > 0) {
                $result[$techId] = round($hours / $jobCount, 2);
            } else {
                $result[$techId] = 0.0;
            }
        }

        return $result;
    }

    /**
     * Get average repair time per job within a date range (overall).
     */
    public function getAverageRepairTime(int $business_id, $workshop_id = null, $location_id = null, $start_date = null, $end_date = null): float
    {
        $start = $start_date ? Carbon::parse($start_date)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $end_date ? Carbon::parse($end_date)->endOfDay() : Carbon::now()->endOfWeek();

        // Get total repair hours from timer tracking
        $totalRepairHours = DB::table('timer_tracking as tt')
            ->join('repair_job_sheets as rjs', 'tt.job_sheet_id', '=', 'rjs.id')
            ->where('tt.business_id', $business_id)
            ->whereBetween('tt.started_at', [$start, $end])
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, tt.started_at, COALESCE(tt.completed_at, NOW())) - COALESCE(tt.total_paused_duration, 0)) as total_seconds')
            ->first();

        $totalSeconds = $totalRepairHours->total_seconds ?? 0;

        // Get number of vehicles serviced (unique job sheets with completed transactions)
        $vehiclesServiced = DB::table('repair_job_sheets as rjs')
            ->join('transactions as t', 't.repair_job_sheet_id', '=', 'rjs.id')
            ->where('rjs.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.sub_type', 'repair')
            ->whereBetween('rjs.created_at', [$start, $end])
            ->when($workshop_id, fn($q) => $q->where('rjs.workshop_id', $workshop_id))
            ->when($location_id, fn($q) => $q->where('rjs.location_id', $location_id))
            ->distinct('rjs.id')
            ->count('rjs.id');

        if ($vehiclesServiced <= 0) {
            return 0.0;
        }

        return round(($totalSeconds / 3600) / $vehiclesServiced, 2);
    }

    /**
     * Get attendance rate per technician within a date range.
     * Attendance Rate = (Days Attended / Total Shift Days in Period) × 100
     * Returns array with technician_id => attendance_rate_percentage
     */
    public function getAttendanceRateByTechnician($workshop_id = null, $location_id = null, $start_date = null, $end_date = null): array
    {
        $start = $start_date ? Carbon::parse($start_date)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $end_date ? Carbon::parse($end_date)->endOfDay() : Carbon::now()->endOfWeek();

        // Get attendance days per technician
        $attendanceDays = DB::table('essentials_attendances as ea')
            ->join('users as u', 'u.id', '=', 'ea.user_id')
            ->join('model_has_roles as mhr', function ($j) {
                $j->on('mhr.model_id', '=', 'u.id')
                  ->where('mhr.model_type', '=', 'App\\User');
            })
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('r.is_service_staff', 1)
            ->whereBetween('ea.clock_in_time', [$start, $end])
            ->selectRaw('ea.user_id, COUNT(DISTINCT DATE(ea.clock_in_time)) as days_attended')
            ->groupBy('ea.user_id')
            ->pluck('days_attended', 'user_id')
            ->toArray();

        // Get total shift days per technician in the period (not just attended days)
        $shiftDays = DB::table('essentials_user_shifts as eus')
            ->join('essentials_shifts as s', 's.id', '=', 'eus.essentials_shift_id')
            ->join('users as u', 'u.id', '=', 'eus.user_id')
            ->join('model_has_roles as mhr', function ($j) {
                $j->on('mhr.model_id', '=', 'u.id')
                  ->where('mhr.model_type', '=', 'App\\User');
            })
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('r.is_service_staff', 1)
            ->select('eus.user_id', 'eus.start_date', 'eus.end_date')
            ->get();

        $scheduledDays = [];
        foreach ($shiftDays as $shift) {
            $shiftStart = $shift->start_date ? Carbon::parse($shift->start_date) : $start;
            $shiftEnd = $shift->end_date ? Carbon::parse($shift->end_date) : $end;
            
            // Calculate overlap with filter period
            $overlapStart = $shiftStart->gt($start) ? $shiftStart : $start;
            $overlapEnd = $shiftEnd->lt($end) ? $shiftEnd : $end;
            
            if ($overlapEnd->gte($overlapStart)) {
                $days = $overlapStart->diffInDays($overlapEnd) + 1;
                $scheduledDays[$shift->user_id] = ($scheduledDays[$shift->user_id] ?? 0) + $days;
            }
        }

        $result = [];
        foreach ($scheduledDays as $techId => $scheduled) {
            $attended = $attendanceDays[$techId] ?? 0;
            if ($scheduled > 0) {
                $result[$techId] = round(($attended / $scheduled) * 100, 2);
            } else {
                $result[$techId] = 0.0;
            }
        }

        return $result;
    }

    /**
     * Get job quality index within a date range.
     * Since comeback ratio is now per technician, this returns overall quality index based on average comeback ratio
     */
    public function getJobQualityIndex(int $business_id, $workshop_id = null, $location_id = null, $start_date = null, $end_date = null): float
    {
        $comebackRatios = $this->getComebackRatio($business_id, $workshop_id, $location_id, $start_date, $end_date);

        if (empty($comebackRatios)) {
            return 100.0; // Perfect quality if no data
        }

        $averageComebackRatio = array_sum($comebackRatios) / count($comebackRatios);
        return round(100 - $averageComebackRatio, 2);
    }

    /**
     * Get Job Quality Index per technician within a date range.
     * Returns array with technician_id => quality_index_percentage
     * Where quality_index = 100 - comeback_ratio
     */
    public function getJobQualityIndexByTechnician(int $business_id, $workshop_id = null, $location_id = null, $start_date = null, $end_date = null): array
    {
        $comebackRatios = $this->getComebackRatio($business_id, $workshop_id, $location_id, $start_date, $end_date);
        if (empty($comebackRatios)) {
            return [];
        }
        $result = [];
        foreach ($comebackRatios as $techId => $ratio) {
            $result[$techId] = round(100 - (float)$ratio, 2);
        }
        return $result;
    }

    /**
     * Get scheduled hours per technician within a date range.
     * Returns array with technician_id => scheduled_hours
     */
    private function getScheduledHoursByTechnician(int $business_id, $workshop_id = null, $location_id = null, $start_date, $end_date): array
    {
        // Get business common settings for work schedule
        $commonSettings = DB::table('business')->where('id', $business_id)->value('common_settings');
        $settings = json_decode($commonSettings ?? '{}', true);
        $workDays = $settings['work_days'] ?? [];
        $workHours = $settings['work_hours'] ?? [];

        $result = [];
        $current = $start_date->copy();
        while ($current->lte($end_date)) {
            $weekday = strtolower($current->format('l'));
            if (!empty($workDays[$weekday])) {
                $hours = $workHours[$weekday] ?? 8; // Default to 8 if not set
                // For all technicians, but since per technician, we need to get technicians
                // But to avoid, since it's the same for all, but we need per technician
                // Actually, since it's business-wide, but to match the structure, we need to get technicians who have worked in the period
                // For simplicity, get all service staff for the business
                $technicians = DB::table('users as u')
                    ->join('model_has_roles as mhr', function ($j) {
                        $j->on('mhr.model_id', '=', 'u.id')
                          ->where('mhr.model_type', '=', 'App\\User');
                    })
                    ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                    ->where('u.business_id', $business_id)
                    ->where('r.is_service_staff', 1)
                    ->pluck('u.id')
                    ->toArray();

                foreach ($technicians as $techId) {
                    $result[$techId] = ($result[$techId] ?? 0) + $hours;
                }
            }
            $current->addDay();
        }

        return $result;
    }

    /**
     * Helper method to parse date range with defaults
     */
    private function parseDateRange($startDate = null, $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfWeek();
        
        return [$start, $end];
    }

    /**
     * Helper method to get base timer tracking query with filters
     */
    private function getBaseTimerQuery($workshopId = null, $locationId = null, $startDate = null, $endDate = null)
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        return DB::table('timer_tracking as tt')
            ->join('repair_job_sheets as rjs', 'tt.job_sheet_id', '=', 'rjs.id')
            ->whereBetween('tt.started_at', [$start, $end])
            ->when($workshopId, fn($q) => $q->where('rjs.workshop_id', $workshopId))
            ->when($locationId, fn($q) => $q->where('rjs.location_id', $locationId));
    }

    /**
     * Helper method to get base job sheets query with filters
     */
    private function getBaseJobSheetsQuery(int $businessId, $workshopId = null, $locationId = null, $startDate = null, $endDate = null)
    {
        return DB::table('repair_job_sheets as rjs')
            ->where('rjs.business_id', $businessId)
            ->when($workshopId, fn($q) => $q->where('rjs.workshop_id', $workshopId))
            ->when($locationId, fn($q) => $q->where('rjs.location_id', $locationId))
            ->when($startDate, fn($q) => $q->where('rjs.created_at', '>=', $startDate));
     
    }

    /**
     * Helper method to get service staff technicians query
     */
    private function getServiceStaffQuery(int $businessId)
    {
        return DB::table('users as u')
            ->join('model_has_roles as mhr', function ($j) {
                $j->on('mhr.model_id', '=', 'u.id')
                  ->where('mhr.model_type', '=', 'App\\User');
            })
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('u.business_id', $businessId)
            ->where('r.is_service_staff', 1);
    }

    /**
     * Helper method to calculate technician stats from job sheets and comeback data
     */
    private function calculateTechnicianStats($jobSheets, $comebackJobSheets): array
    {
        $techStats = [];
        
        foreach ($jobSheets as $job) {
            $technicians = json_decode($job->service_staff ?? '[]', true) ?: [];
            foreach ($technicians as $techId) {
                if (!isset($techStats[$techId])) {
                    $techStats[$techId] = ['total' => 0, 'comeback' => 0];
                }
                $techStats[$techId]['total']++;
                if (isset($comebackJobSheets[$job->id])) {
                    $techStats[$techId]['comeback']++;
                }
            }
        }
        
        return $techStats;
    }
}