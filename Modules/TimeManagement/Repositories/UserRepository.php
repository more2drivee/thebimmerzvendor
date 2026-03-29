<?php

namespace Modules\TimeManagement\Repositories;

use Illuminate\Support\Facades\DB;

class UserRepository
{
    /**
     * Get users by IDs with formatted names.
     */
    public function getUsersByIds(array $userIds)
    {
        if (empty($userIds)) {
            return collect();
        }

        return DB::table('users')
            ->whereIn('id', $userIds)
            ->select('id', DB::raw("TRIM(CONCAT_WS(' ', COALESCE(surname, ''), COALESCE(first_name, ''), COALESCE(last_name, ''))) as name"))
            ->get()
            ->keyBy('id');
    }

    /**
     * Get all users for a business with formatted names.
     */
    public function getUsersByBusiness(int $business_id)
    {
        return DB::table('users as u')
            ->where('u.business_id', $business_id)
            ->select('u.id', DB::raw("TRIM(CONCAT_WS(' ', COALESCE(u.surname, ''), COALESCE(u.first_name, ''), COALESCE(u.last_name, ''))) as name"))
            ->get()
            ->keyBy('id');
    }

    /**
     * Get service (tech) staff for a business with optional location filter.
     * Uses Spatie roles with roles.is_service_staff = 1
     */
    public function getServiceStaffByBusiness(int $business_id, $location_id = null)
    {
        $q = DB::table('users as u')
            ->join('model_has_roles as mhr', function ($j) {
                $j->on('mhr.model_id', '=', 'u.id')
                  ->where('mhr.model_type', '=', 'App\\User');
            })
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
     
            ->where('r.is_service_staff', 1)
            ->select('u.id', DB::raw("TRIM(CONCAT_WS(' ', COALESCE(u.surname, ''), COALESCE(u.first_name, ''), COALESCE(u.last_name, ''))) as name"))
            ->distinct();

        if (!empty($location_id)) {
            $q->where('u.location_id', $location_id);
        }

        return $q->get()->keyBy('id');
    }

    /**
     * Get user shifts within a date range.
     */
    public function getUserShifts(int $business_id, $workshop_id = null, $location_id = null, $start_date = null, $end_date = null)
    {
        $shifts = DB::table('essentials_user_shifts as eus')
            ->join('essentials_shifts as s', 's.id', '=', 'eus.essentials_shift_id')
            ->select('eus.user_id', 's.start_time', 's.end_time', 'eus.start_date', 'eus.end_date');

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

        return $shifts;
    }
}
