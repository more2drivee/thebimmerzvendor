<?php

namespace Modules\TimeManagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TechnicianDataService
{
    /**
     * Get all technicians with their basic data for a given business and filters
     */
    public function getTechniciansWithFilters(int $businessId, array $filters = []): Collection
    {
        $workshopId = $filters['workshop_id'] ?? null;
        $locationId = $filters['location_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $query = DB::table('users as u')
            ->where('u.user_type', 'user')
            ->where('u.allow_login', 0)
            ->when($locationId, fn($q) => $q->where('u.location_id', $locationId))
            ->whereNull('u.deleted_at');  // Exclude deleted users


        return $query->select(
            'u.id',
            'u.first_name',
            'u.last_name',
            'u.surname',
            DB::raw("TRIM(CONCAT_WS(' ', COALESCE(u.surname, ''), COALESCE(u.first_name, ''), COALESCE(u.last_name, ''))) as full_name")
        )->get();  // Remove distinct() as it's not needed
    }

    /**
     * Get job sheets that technicians worked on within filters
     */
    public function getJobSheetsForTechnicians(int $businessId, array $filters = []): Collection
    {
        $workshopId = $filters['workshop_id'] ?? null;
        $locationId = $filters['location_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        return DB::table('repair_job_sheets as rjs')
            ->where('rjs.business_id', $businessId)
            ->when($workshopId, fn($q) => $q->where('rjs.workshop_id', $workshopId))
            ->when($locationId, fn($q) => $q->where('rjs.location_id', $locationId))
            ->when($startDate, fn($q) => $q->where('rjs.created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('rjs.created_at', '<=', $endDate))
            ->whereNotNull('rjs.service_staff')
            ->select('rjs.id', 'rjs.service_staff', 'rjs.created_at', 'rjs.workshop_id', 'rjs.location_id')
            ->get();
    }

    /**
     * Get timer tracking data for technicians within filters
     */
    public function getTimerDataForTechnicians(int $businessId, array $filters = []): Collection
    {
        $workshopId = $filters['workshop_id'] ?? null;
        $locationId = $filters['location_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfWeek();

        return DB::table('timer_tracking as tt')
            ->join('repair_job_sheets as rjs', 'tt.job_sheet_id', '=', 'rjs.id')
            ->join('users as u', 'u.id', '=', 'tt.user_id')
        
            ->whereBetween('tt.started_at', [$start, $end])
            ->when($workshopId, fn($q) => $q->where('rjs.workshop_id', $workshopId))
            ->when($locationId, fn($q) => $q->where('rjs.location_id', $locationId))
            ->select(
                'tt.user_id',
                'tt.job_sheet_id',
                'tt.started_at',
                'tt.completed_at',
                'tt.total_paused_duration',
                'tt.time_allocate',
                DB::raw('TIMESTAMPDIFF(SECOND, tt.started_at, COALESCE(tt.completed_at, NOW())) - COALESCE(tt.total_paused_duration, 0) as work_seconds')
            )
            ->get();
    }

    /**
     * Get attendance data for technicians within filters
     */
    public function getAttendanceDataForTechnicians(int $businessId, array $filters = []): Collection
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $locationId = $filters['location_id'] ?? null;

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfWeek();

        return DB::table('essentials_attendances as ea')
            
            ->join('users as u', 'u.id', '=', 'ea.user_id')
           
        
            ->when($locationId, fn($q) => $q->where('u.location_id', $locationId))
            ->whereBetween('ea.clock_in_time', [$start, $end])
            ->select(
                'ea.user_id',
                'ea.clock_in_time',
                'ea.clock_out_time',
                DB::raw('DATE(ea.clock_in_time) as attendance_date'),
                DB::raw('TIMESTAMPDIFF(SECOND, ea.clock_in_time, COALESCE(ea.clock_out_time, NOW())) as attendance_seconds')
            )
            ->get();
    }

    /**
     * Get shift data for technicians within filters
     */
    public function getBusinessScheduleForTechnicians(int $businessId, array $filters = []): Collection
    {
        $defaultSchedule = DB::table('business')
            ->where('id', $businessId)
            ->select('common_settings')
            ->first();

        if (empty($defaultSchedule)) {
            return collect();
        }

        $commonSettings = json_decode($defaultSchedule->common_settings ?? '{}', true);
        $workDays = $commonSettings['work_days'] ?? [];
        $workHours = $commonSettings['work_hours'] ?? [];

        if (empty($workDays) || empty($workHours)) {
            return collect();
        }

        $startInput = $filters['start_date'] ?? now()->startOfWeek();
        $endInput = $filters['end_date'] ?? now()->endOfWeek();

        $start = $startInput instanceof Carbon ? $startInput->copy()->startOfDay() : Carbon::parse($startInput)->startOfDay();
        $end = $endInput instanceof Carbon ? $endInput->copy()->endOfDay() : Carbon::parse($endInput)->endOfDay();

        $technicians = $this->getTechniciansWithFilters($businessId, $filters);

        $schedule = collect();

        foreach ($technicians as $technician) {
            $current = $start->copy();
            while ($current->lte($end)) {
                $weekday = strtolower($current->format('l'));
                if (!empty($workDays[$weekday]) && !empty($workHours[$weekday])) {
                    $hours = $workHours[$weekday];
                    $startTime = $hours['start'] ?? null;
                    $endTime = $hours['end'] ?? null;

                    $totalHours = $hours['total'] ?? null;
                    if ($totalHours === null && $startTime && $endTime) {
                        $startCarbon = Carbon::parse($startTime);
                        $endCarbon = Carbon::parse($endTime);
                        if ($endCarbon->lessThanOrEqualTo($startCarbon)) {
                            $endCarbon->addDay();
                        }
                        $totalHours = round($startCarbon->diffInMinutes($endCarbon) / 60, 4);
                    }

                    $schedule->push((object) [
                        'user_id' => $technician->id,
                        'date' => $current->toDateString(),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'total_hours' => $totalHours,
                    ]);
                }
                $current->addDay();
            }
        }

        return $schedule;
    }

    /**
     * Get comeback data for technicians within filters
     */
    public function getComebackDataForTechnicians(int $businessId, array $filters = []): array
    {
        $workshopId = $filters['workshop_id'] ?? null;
        $locationId = $filters['location_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        // Get job sheets
        $jobSheets = $this->getJobSheetsForTechnicians($businessId, $filters);
        
        if ($jobSheets->isEmpty()) {
            return [
                'job_sheets' => collect(),
                'comeback_job_sheets' => []
            ];
        }

        // Get comeback job sheet IDs
        $comebackJobSheets = DB::table('repair_job_sheets as rjs')
            ->join('bookings as b', function($join) {
                $join->on(DB::raw('CAST(b.call_back_ref as UNSIGNED)'), '=', 'rjs.id')
                     ->where('b.is_callback', '=', 1);
            })
       
            ->pluck('rjs.id')
            ->flip()
            ->toArray();

        return [
            'job_sheets' => $jobSheets,
            'comeback_job_sheets' => $comebackJobSheets
        ];
    }

    /**
     * Get standard hours data for technicians within filters
     */
    public function getStandardHoursDataForTechnicians(int $businessId, array $filters = []): Collection
    {
        $workshopId = $filters['workshop_id'] ?? null;
        $locationId = $filters['location_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfWeek();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfWeek();

        return DB::table('transactions as t')
            ->join('repair_job_sheets as rjs', 't.repair_job_sheet_id', '=', 'rjs.id')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->where('t.type', 'sell')
            ->where('t.sub_type', 'repair')
            ->where('p.enable_stock', 0)
            ->whereNotNull('p.serviceHours')
            ->whereBetween('t.transaction_date', [$start, $end])
            ->when($workshopId, fn($q) => $q->where('rjs.workshop_id', $workshopId))
            ->when($locationId, fn($q) => $q->where('rjs.location_id', $locationId))
            ->select(
                'rjs.id as job_sheet_id',
                'rjs.service_staff',
                'p.serviceHours',
                'tsl.quantity',
                't.transaction_date',
                't.created_at'
            )
            ->get();
    }
}
