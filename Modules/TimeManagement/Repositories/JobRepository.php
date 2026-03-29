<?php

namespace Modules\TimeManagement\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\TimeManagement\Entities\WorkshopTechnicianAssignmentHistory;

class JobRepository
{
    /**
     * Get active jobs with optional filters.
     */
    public function getActiveJobs(int $business_id, $workshop_id = null, $location_id = null, $start_date = null, $end_date = null)
    {
        $q = DB::table('repair_job_sheets as rjs')
            ->leftJoin('transactions as t', 't.repair_job_sheet_id', '=', 'rjs.id')
            ->leftJoin('workshops as w', 'w.id', '=', 'rjs.workshop_id')
            ->leftJoin('users as u', 'u.id', '=', 'rjs.created_by')
            ->leftJoin('repair_statuses as rs', 'rs.id', '=', 'rjs.status_id')
            ->leftJoin('bookings as b', 'b.id', '=', 'rjs.booking_id')
            ->leftJoin('contact_device as cd', 'cd.id', '=', 'b.device_id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'cd.device_id')
            ->leftJoin('repair_device_models as rdm', 'rdm.id', '=', 'cd.models_id')
            ->where('t.status', 'under processing')
            ->select([
                'rjs.id', 'rjs.job_sheet_no', 'rjs.entry_date', 'rjs.start_date', 'rjs.due_date', 'rjs.service_staff',
                'w.name as workshop_name', 'w.id as workshop_id',
                'rs.name as status_name', 'rs.color as status_color',
                'b.id as booking_id',
                'cd.id as contact_device_id',
                'cat.name as device_name',
                'rjs.workshops',
                'rdm.name as device_model_name',
                'cd.plate_number as device_plate_number',
                'cd.chassis_number as device_chassis_number',
                'cd.color as device_color',
                'cd.manufacturing_year as device_year',
                'cd.car_type as device_car_type',
                DB::raw("TRIM(CONCAT_WS(' ', COALESCE(u.surname, ''), COALESCE(u.first_name, ''), COALESCE(u.last_name, ''))) as created_by")
            ])
            ->orderByDesc('rjs.created_at');

        if (!empty($workshop_id)) {
            $q->where('rjs.workshop_id', $workshop_id);
        }

        $rows = $q->get();

        // Expand service staff names
        if ($rows->isEmpty()) return [];

        $userIds = $rows->flatMap(function ($r) {
            $arr = json_decode($r->service_staff, true) ?: [];
            return array_filter($arr);
        })->unique()->values();

        $users = DB::table('users')->whereIn('id', $userIds)
            ->select('id', DB::raw("TRIM(CONCAT_WS(' ', COALESCE(surname, ''), COALESCE(first_name, ''), COALESCE(last_name, ''))) as name"))
            ->get()->keyBy('id');

        foreach ($rows as $r) {
            $ids = json_decode($r->service_staff, true) ?: [];
            $r->technicians = collect($ids)->map(fn($id) => $users[$id]->name ?? null)->filter()->values()->all();
            $r->device = (object) [
                'name' => $r->device_name ?? null,
                'model' => $r->device_model_name ?? null,
                'plate_number' => $r->device_plate_number ?? null,
                'chassis_number' => $r->device_chassis_number ?? null,
                'color' => $r->device_color ?? null,
                'manufacturing_year' => $r->device_year ?? null,
                'car_type' => $r->device_car_type ?? null,
            ];
        }

        return $rows;
    }

    /**
     * Get live timers for active jobs with service groups.
     */
    public function getLiveTimers(int $business_id, array $filters)
    {
        $workshop_id = $filters['workshop_id'] ?? null;
        $location_id = $filters['location_id'] ?? null;
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;

        $rows = $this->getActiveJobs($business_id, $workshop_id, $location_id, $start_date, $end_date);
        $now = Carbon::now();
        $jobIds = $rows->pluck('id')->toArray();

        if (empty($jobIds)) {
            return $rows;
        }

        // 1) Load services linked to job sheets via product_joborder
        $jobProducts = DB::table('product_joborder')
            ->whereIn('job_order_id', $jobIds)
            ->select('job_order_id', 'product_id')
            ->get();

        $productIds = $jobProducts->pluck('product_id')->unique()->values();

        $products = $productIds->isEmpty() ? collect() : DB::table('products')
            ->whereIn('id', $productIds)
            ->select('id', 'name', 'enable_stock', 'serviceHours')
            ->get()
            ->keyBy('id');

        // Load pivot mappings of product -> workshops
        $productWorkshops = $productIds->isEmpty() ? collect() : DB::table('product_workshop')
            ->whereIn('product_id', $productIds)
            ->select('product_id', 'workshop_id')
            ->get()
            ->groupBy('product_id');

        // Build services grouped by job, expanding multiple workshops per service via pivot
        $servicesByJob = $jobProducts
            ->groupBy('job_order_id')
            ->map(function ($rows) use ($products, $productWorkshops) {
                return collect($rows)->flatMap(function ($row) use ($products, $productWorkshops) {
                    $p = $products->get($row->product_id);
                    if (!$p) { return []; }
                    $isService = (int)($p->enable_stock ?? 1) === 0;
                    if (!$isService) { return []; }

                    // Get assigned workshops strictly from pivot mapping
                    $assignedWorkshopIds = $productWorkshops->get($p->id, collect())->pluck('workshop_id')->unique()->values()->all();
                    if (empty($assignedWorkshopIds)) { return []; }

                    return collect($assignedWorkshopIds)->map(function ($wid) use ($p) {
                        return (object) [
                            'service_id' => $p->id,
                            'service_name' => $p->name,
                            'service_hours' => $p->serviceHours,
                            'workshop_id' => (int) $wid,
                        ];
                    })->all();
                })->values();
            });

        $candidateWorkshopIds = $servicesByJob->flatten(1)->pluck('workshop_id')->unique()->values();

        // 2) Load job-sheet specific workshop-user assignments
        $assignmentsRaw = $candidateWorkshopIds->isEmpty() ? collect() : WorkshopTechnicianAssignmentHistory::query()
            ->active()
            ->ofType('job_sheet')
            ->whereIn('job_sheet_id', $jobIds)
            ->whereIn('workshop_id', $candidateWorkshopIds)
            ->select('job_sheet_id', 'workshop_id', 'user_id')
            ->get();

        $assignments = $assignmentsRaw->groupBy(function ($a) {
            return $a->job_sheet_id . '|' . $a->workshop_id;
        });

        $assignedWorkshopsByJob = $assignmentsRaw
            ->groupBy('job_sheet_id')
            ->map(function ($rows) {
                return $rows->pluck('workshop_id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->unique()
                    ->values();
            });

        $servicesByJob = $servicesByJob->map(function ($services, $jobId) use ($assignedWorkshopsByJob) {
            $assigned = $assignedWorkshopsByJob->get($jobId, collect());
            if (!$assigned instanceof \Illuminate\Support\Collection) {
                $assigned = collect($assigned);
            }

            if ($assigned->isEmpty()) {
                return collect();
            }

            return collect($services)->filter(function ($svc) use ($assigned) {
                return $assigned->contains((int) $svc->workshop_id);
            })->values();
        });

        $workshopIds = $servicesByJob->flatten(1)->pluck('workshop_id')->unique()->values();
        $workshops = $workshopIds->isEmpty() ? collect() : DB::table('workshops')
            ->whereIn('id', $workshopIds)
            ->select('id', 'name')
            ->get()
            ->keyBy('id');

        // 3) Collect all user IDs (service_staff + assignment users) to batch load names
        $allUserIds = collect();
        foreach ($rows as $job) {
            $serviceStaff = json_decode($job->service_staff ?? '[]', true) ?: [];
            $allUserIds = $allUserIds->merge($serviceStaff);
        }
        $assignmentUserIds = $assignments->flatten(1)->pluck('user_id');
        $allUserIds = $allUserIds->merge($assignmentUserIds)->unique()->filter()->values();

        $users = $allUserIds->isEmpty() ? collect() : DB::table('users')
            ->whereIn('id', $allUserIds)
            ->select('id', DB::raw("TRIM(CONCAT_WS(' ', COALESCE(surname, ''), COALESCE(first_name, ''), COALESCE(last_name, ''))) as full_name"))
            ->get()
            ->keyBy('id');

        // 4) Load all timers for these jobs, grouped by job-user
        $timerData = DB::table('timer_tracking')
            ->where('business_id', $business_id)
            ->whereIn('job_sheet_id', $jobIds)
            ->orderBy('started_at', 'desc')
            ->get()
            ->groupBy(function ($timer) {
                return $timer->job_sheet_id . '-' . $timer->user_id;
            });

        // 5) Process each job sheet
        foreach ($rows as $job) {
            // Build service-based timer grouping
            $jobServices = $servicesByJob->get($job->id, collect());
            $serviceGroups = [];

            foreach ($jobServices as $svc) {
                $assignmentKey = $job->id . '|' . $svc->workshop_id;
                $assignedUsers = $assignments->get($assignmentKey, collect())->pluck('user_id')->unique()->values();

                // Build timers for users assigned to this workshop for the job
                $timersForService = [];
                $technicianNames = [];

                if ($assignedUsers->isEmpty()) {
                    continue;
                }

                foreach ($assignedUsers as $uid) {
                    $uid = (int) $uid;
                    $userName = $users->get($uid)->full_name ?? ('User #' . $uid);
                    $technicianNames[] = $userName;

                    $timerKey = $job->id . '-' . $uid;
                    $userTimers = $timerData->get($timerKey, collect());

                    $timersForService[] = $this->processWorkerTimerData($uid, $userName, $userTimers, $now);
                }

                $serviceGroups[] = (object) [
                    'service_id' => $svc->service_id,
                    'service_name' => $svc->service_name,
                    'service_hours' => $svc->service_hours,
                    'workshop_id' => $svc->workshop_id,
                    'workshop_name' => ($workshops->get($svc->workshop_id)->name ?? null),
                    'technicians' => $technicianNames,
                    'timers' => $timersForService,
                ];
            }

            // Set service groups
            $job->service_groups = $serviceGroups;

            // Calculate job-level totals from service groups
            $jobSum = 0;
            $active = 0; $paused = 0; $completed = 0;
            foreach ($serviceGroups as $group) {
                foreach ($group->timers as $timer) {
                    $jobSum += $timer->elapsed_seconds;
                    if ($timer->timer_status === 'active') { $active++; }
                    elseif ($timer->timer_status === 'paused') { $paused++; }
                    elseif ($timer->timer_status === 'completed') { $completed++; }
                }
            }

            $job->elapsed_seconds = $jobSum;
            $job->active_workers = $active;
            $job->paused_workers = $paused;
            $job->completed_workers = $completed;
            $totalWorkers = collect($serviceGroups)->sum(function($g) { return count($g->timers); });
            $job->total_workers = $totalWorkers;
            $job->has_active = $active > 0;
            $job->has_paused = $paused > 0;
            $job->all_completed = $totalWorkers > 0 && ($active + $paused) === 0;

            // Keep backward compatibility with workers array (flatten all timers)
            $job->workers = collect($serviceGroups)->flatMap(function($g) { return $g->timers; })->values();
            $job->technicians = collect($serviceGroups)->flatMap(function($g) { return $g->technicians; })->unique()->values()->all();
        }

        return $rows;
    }

    /**
     * Process timer data for individual worker
     */
    private function processWorkerTimerData(int $userId, string $userName, $userTimers, Carbon $now)
    {
        $totalElapsedSeconds = 0;
        $currentTimerStatus = null;
        $currentTimerId = null;
        $startedAt = null;
        $lastActionAt = null;
        $pausedAt = null;

        // Process all timer records for this worker
        foreach ($userTimers as $timer) {
            if (!$timer->started_at) continue;

            $timerStarted = Carbon::parse($timer->started_at);
            $pausedDuration = (int) ($timer->total_paused_duration ?? 0);

            // Calculate elapsed time based on timer status
            if ($timer->status === 'completed' && $timer->completed_at) {
                $timerEnded = Carbon::parse($timer->completed_at);
                $elapsed = $timerEnded->diffInSeconds($timerStarted) - $pausedDuration;
                $currentTimerStatus = 'completed';
                $currentTimerId = $timer->id;
                $lastActionAt = $timer->completed_at;
            } elseif ($timer->status === 'paused' && $timer->paused_at) {
                $timerPaused = Carbon::parse($timer->paused_at);
                $elapsed = $timerPaused->diffInSeconds($timerStarted) - $pausedDuration;
                $currentTimerStatus = 'paused';
                $currentTimerId = $timer->id;
                $pausedAt = $timer->paused_at;
                $lastActionAt = $timer->paused_at;
            } else { // active timer
                $elapsed = $now->diffInSeconds($timerStarted) - $pausedDuration;
                $currentTimerStatus = 'active';
                $currentTimerId = $timer->id;
                $lastActionAt = $timer->started_at;
            }

            $totalElapsedSeconds += max(0, (int) $elapsed);
            
            // Keep track of the earliest start time
            if (!$startedAt || $timerStarted->lt(Carbon::parse($startedAt))) {
                $startedAt = $timer->started_at;
            }
        }

        // If no active or paused timer found, check if there were any completed timers
        if (!$currentTimerStatus && !$userTimers->isEmpty()) {
            $currentTimerStatus = 'completed';
            $latestTimer = $userTimers->sortByDesc('completed_at')->first();
            if ($latestTimer) {
                $currentTimerId = $latestTimer->id;
            }
        }

        return (object) [
            'user_id' => $userId,
            'user_name' => $userName,
            'timer_id' => $currentTimerId,
            'timer_status' => $currentTimerStatus,
            'elapsed_seconds' => max(0, $totalElapsedSeconds),
            'started_at' => $startedAt,
            'paused_at' => $pausedAt,
            'last_action_at' => $lastActionAt,
        ];
    }

    /**
     * Get workers status (assigned, present, away).
     */
    public function getWorkersStatus(int $business_id, array $filters)
    {
        $workshop_id = $filters['workshop_id'] ?? null;
        $location_id = $filters['location_id'] ?? null;
        $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : Carbon::today()->startOfDay();
        $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : Carbon::today()->endOfDay();

        // Only service (tech) staff per roles.is_service_staff = 1
        $usersQ = DB::table('users as u')
           ->where('u.allow_login', 0)
           ->where('u.user_type', 'user')
           ->whereNull('u.deleted_at')

            ->select('u.id', DB::raw("TRIM(CONCAT_WS(' ', COALESCE(u.surname, ''), COALESCE(u.first_name, ''), COALESCE(u.last_name, ''))) as name"))
            ->distinct();

        // if (!empty($location_id)) {
        //     $usersQ->where('u.location_id', $location_id);
        // }

        $users = $usersQ->get()->keyBy('id');

        // Present today/range
        $presentIds = DB::table('essentials_attendances')
            ->whereDate('clock_in_time', Carbon::today())
            ->pluck('user_id')->unique();

        // Assigned on active jobs
        $activeJobs = $this->getActiveJobs($business_id, $workshop_id, $location_id, $start->toDateString(), $end->toDateString());
        $assignedIds = collect();
        foreach ($activeJobs as $j) {
            $ids = json_decode($j->service_staff, true) ?: [];
            $assignedIds = $assignedIds->merge($ids);
        }
        $assignedIds = $assignedIds->unique();

        $rows = [];
        foreach ($users as $id => $u) {
            $assigned = $assignedIds->contains($id);
            $present = $presentIds->contains($id);
            $status = $assigned ? 'Active' : ($present ? 'Clocked-in' : 'Away');
            $rows[] = (object) [
                'user_id' => $id,
                'user_name' => $u->name,
                'status' => $status,
                'assigned' => $assigned,
                'present' => $present,
            ];
        }
        return collect($rows);
    }
}
