<?php

namespace Modules\TimeManagement\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRepository
{
    /**
     * Count distinct users present within a date range, optionally filtered by workshop/location.
     */
    public function getPresentCount(int $business_id, $workshop_id = null, $location_id = null, $start_date = null, $end_date = null): int
    {
        $start = $start_date ? Carbon::parse($start_date)->startOfDay() : Carbon::today()->startOfDay();
        $end = $end_date ? Carbon::parse($end_date)->endOfDay() : Carbon::today()->endOfDay();

        $q = DB::table('essentials_attendances as ea')
            ->join('users as u', 'u.id', '=', 'ea.user_id')
            ->whereBetween('ea.clock_in_time', [$start, $end]);

        if (!empty($workshop_id) || !empty($location_id)) {
            $q->whereExists(function ($sub) use ($business_id, $workshop_id, $location_id, $start, $end) {
                $sub->from('repair_job_sheets as rjs')
                    ->join('transactions as t', 't.repair_job_sheet_id', '=', 'rjs.id')
                    ->where('rjs.business_id', $business_id)
                    ->where('t.sub_type', 'repair')
                    ->where('t.status', 'under processing')
                    ->when($workshop_id, fn($w) => $w->where('rjs.workshop_id', $workshop_id))
                    ->when($location_id, fn($w) => $w->where('rjs.location_id', $location_id))
                    ->whereRaw("JSON_CONTAINS(COALESCE(rjs.service_staff, '[]'), JSON_QUOTE(CAST(u.id as CHAR)))")
                    ->where(function($w) use ($start, $end) {
                        $w->whereBetween('rjs.start_date', [$start, $end])
                          ->orWhereBetween('rjs.entry_date', [$start, $end]);
                    });
            });
        }
        return (int) $q->distinct('ea.user_id')->count('ea.user_id');
    }

    /**
     * Count distinct users present today, optionally filtered by workshop.
     */
    public function getPresentTodayCount(int $business_id, $workshop_id = null): int
    {
        $today = Carbon::today();
        $q = DB::table('essentials_attendances as ea')
            ->join('users as u', 'u.id', '=', 'ea.user_id')
            ->whereDate('ea.clock_in_time', $today);

        if (!empty($workshop_id)) {
            $q->whereExists(function ($sub) use ($business_id, $workshop_id) {
                $sub->from('repair_job_sheets as rjs')
                    ->join('transactions as t', 't.repair_job_sheet_id', '=', 'rjs.id')
                    ->whereColumn('rjs.business_id', DB::raw($business_id))
                    ->where('t.sub_type', 'repair')
                    ->where('t.status', 'under processing')
                    ->where('rjs.workshop_id', $workshop_id)
                    ->whereRaw("JSON_CONTAINS(COALESCE(rjs.service_staff, '[]'), JSON_QUOTE(CAST(u.id as CHAR)))");
            });
        }
        return (int) $q->distinct('ea.user_id')->count('ea.user_id');
    }

    /**
     * Count late arrivals within a date range, optionally filtered by workshop/location.
     */
    public function getLateArrivalsCount(int $business_id, $workshop_id = null, $location_id = null, $start_date = null, $end_date = null): int
    {
        $start = $start_date ? Carbon::parse($start_date)->startOfDay() : Carbon::today()->startOfDay();
        $end = $end_date ? Carbon::parse($end_date)->endOfDay() : Carbon::today()->endOfDay();

        // First clock-in per user per day within range
        $clockIns = DB::table('essentials_attendances as ea')
            ->select('ea.user_id', DB::raw('DATE(ea.clock_in_time) as d'), DB::raw('MIN(ea.clock_in_time) as first_in'))
            ->whereBetween('ea.clock_in_time', [$start, $end])
            ->groupBy('ea.user_id', DB::raw('DATE(ea.clock_in_time)'));

        $shifts = DB::table('essentials_user_shifts as eus')
            ->join('essentials_shifts as s', 's.id', '=', 'eus.essentials_shift_id')
            ->select('eus.user_id', 's.start_time', 'eus.start_date', 'eus.end_date');

        $q = DB::query()->fromSub($clockIns, 'ci')
            ->joinSub($shifts, 'sh', 'sh.user_id', '=', 'ci.user_id')
            ->whereRaw('TIME(ci.first_in) > TIME(sh.start_time)')
            ->where(function($w){
                // If shift has bounds, ensure the day falls within assignment window
                $w->whereNull('sh.start_date')->orWhereRaw('DATE(ci.d) >= sh.start_date');
            })
            ->where(function($w){
                $w->whereNull('sh.end_date')->orWhereRaw('DATE(ci.d) <= sh.end_date');
            });

        if (!empty($workshop_id) || !empty($location_id)) {
            $q->whereExists(function ($sub) use ($business_id, $workshop_id, $location_id) {
                $sub->from('repair_job_sheets as rjs')
                    ->join('transactions as t', 't.repair_job_sheet_id', '=', 'rjs.id')
                    ->where('rjs.business_id', $business_id)
                    ->where('t.sub_type', 'repair')
                    ->where('t.status', 'under processing')
                    ->when($workshop_id, fn($w) => $w->where('rjs.workshop_id', $workshop_id))
                    ->when($location_id, fn($w) => $w->where('rjs.location_id', $location_id))
                    ->whereRaw("JSON_CONTAINS(COALESCE(rjs.service_staff, '[]'), JSON_QUOTE(CAST(ci.user_id as CHAR)))");
            });
        }
        return (int) $q->count();
    }

    /**
     * Get attendances with filters.
     */
    public function getAttendances(int $business_id, array $filters)
    {
        $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : Carbon::now()->startOfMonth();
        $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : Carbon::now()->endOfMonth();

        $q = DB::table('essentials_attendances as ea')
            ->join('users as u', 'u.id', '=', 'ea.user_id')
            ->where('u.business_id', $business_id)
            ->whereBetween('ea.clock_in_time', [$start, $end])
            ->select([
                'u.id as user_id',
                DB::raw("TRIM(CONCAT_WS(' ', COALESCE(u.surname, ''), COALESCE(u.first_name, ''), COALESCE(u.last_name, ''))) as user_name"),
                'ea.clock_in_time',
                'ea.clock_out_time',
                'ea.clock_in_note',
                'ea.clock_out_note',
                'ea.ip_address'
            ])
            ->orderBy('ea.clock_in_time', 'desc');

        if (!empty($filters['workshop_id']) || !empty($filters['location_id'])) {
            $q->whereExists(function ($sub) use ($business_id, $filters) {
                $sub->from('repair_job_sheets as rjs')
                    ->where('rjs.business_id', $business_id)
                    ->when(!empty($filters['workshop_id']), fn($w) => $w->where('rjs.workshop_id', $filters['workshop_id']))
                    ->when(!empty($filters['location_id']), fn($w) => $w->where('rjs.location_id', $filters['location_id']))
                    ->whereRaw("JSON_CONTAINS(COALESCE(rjs.service_staff, '[]'), JSON_QUOTE(CAST(u.id as CHAR)))");
            });
        }

        return $q->get();
    }

    /**
     * Get productive hours for users within a date range.
     */
    public function getProductiveHours(int $business_id, $workshop_id = null, $location_id = null, $start_date = null, $end_date = null): float
    {
        $start = $start_date ? Carbon::parse($start_date)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $end_date ? Carbon::parse($end_date)->endOfDay() : Carbon::now()->endOfWeek();

        $q = DB::table('essentials_attendances as ea')
            ->whereBetween('ea.clock_in_time', [$start, $end]);

        if (!empty($workshop_id) || !empty($location_id)) {
            $q->whereExists(function ($sub) use ($business_id, $workshop_id, $location_id) {
                $sub->from('repair_job_sheets as rjs')
                    ->join('transactions as t', 't.repair_job_sheet_id', '=', 'rjs.id')
                    ->where('rjs.business_id', $business_id)
                    ->where('t.sub_type', 'repair')
                    ->where('t.status', 'under processing')
                    ->when($workshop_id, fn($w) => $w->where('rjs.workshop_id', $workshop_id))
                    ->when($location_id, fn($w) => $w->where('rjs.location_id', $location_id))
                    ->whereRaw("JSON_CONTAINS(COALESCE(rjs.service_staff, '[]'), JSON_QUOTE(CAST(ea.user_id as CHAR)))");
            });
        }

        $rows = $q->select('ea.clock_in_time', 'ea.clock_out_time')->get();
        $seconds = 0;
        foreach ($rows as $r) {
            $in = Carbon::parse($r->clock_in_time);
            $out = $r->clock_out_time ? Carbon::parse($r->clock_out_time) : Carbon::now();
            $seconds += max(0, $out->diffInSeconds($in));
        }
        return round($seconds / 3600, 1);
    }
}
