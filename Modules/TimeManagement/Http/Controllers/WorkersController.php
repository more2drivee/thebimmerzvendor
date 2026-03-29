<?php

namespace Modules\TimeManagement\Http\Controllers;

use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\TimeManagement\Services\TimeMetricsService;

class WorkersController extends Controller
{
    public function index(Request $request, TimeMetricsService $metrics)
    {
        $business_id = $request->session()->get('user.business_id');
        $filters = $request->only(['workshop_id', 'location_id', 'start_date', 'end_date']);

        $workshops = DB::table('workshops')
            ->where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');
        $locations = BusinessLocation::forDropdown($business_id, false, false, false, true);

        $workers = $metrics->getWorkersStatus($business_id, $filters);

        // Paginator
        $items = collect($workers);
        $perPage = (int) $request->input('per_page', 10);
        $page = max(1, (int) $request->input('page', 1));
        $total = $items->count();
        $workersPage = new LengthAwarePaginator(
            $items->slice(($page - 1) * $perPage, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('timemanagement::workers.index', compact('workshops', 'locations', 'workers', 'workersPage'))
            ->with($filters);
    }

    public function profile($user, Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_id = (int) $user;

        // Basic user
        $u = DB::table('users')->where('business_id', $business_id)->where('id', $user_id)
            ->select('id', DB::raw("TRIM(CONCAT_WS(' ', COALESCE(surname,''), COALESCE(first_name,''), COALESCE(last_name,''))) as name"))
            ->first();
        if (!$u) {
            abort(404);
        }

        // Present today
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $present = DB::table('essentials_attendances')
            ->where('user_id', $user_id)
            ->whereBetween('clock_in_time', [$todayStart, $todayEnd])
            ->exists();

        // Today's hours
        $todaySec = (int) DB::table('essentials_attendances')
            ->where('user_id', $user_id)
            ->whereBetween('clock_in_time', [$todayStart, $todayEnd])
            ->select(DB::raw('SUM(TIMESTAMPDIFF(SECOND, clock_in_time, COALESCE(clock_out_time, NOW()))) as sec'))
            ->value('sec');
        $todayHours = round(($todaySec ?? 0) / 3600, 1);

        // Active assignment zone (latest active job)
        $activeJob = DB::table('repair_job_sheets as rjs')
            ->leftJoin('transactions as t', 't.repair_job_sheet_id', '=', 'rjs.id')
            ->leftJoin('workshops as w', 'w.id', '=', 'rjs.workshop_id')
            ->where('rjs.business_id', $business_id)
            ->where('t.sub_type', 'repair')
            ->where('t.status', 'under processing')
            ->whereRaw("JSON_CONTAINS(COALESCE(rjs.service_staff, '[]'), JSON_QUOTE(CAST(? as CHAR)))", [$user_id])
            ->orderByDesc('rjs.created_at')
            ->select('rjs.id', 'rjs.workshop_id', 'w.name as workshop_name')
            ->first();

        // Jobs completed stats
        $done = DB::table('repair_job_sheets as rjs')
            ->where('rjs.business_id', $business_id)
            ->whereNotNull('rjs.delivery_date')
            ->whereRaw("JSON_CONTAINS(COALESCE(rjs.service_staff, '[]'), JSON_QUOTE(CAST(? as CHAR)))", [$user_id]);

        $totalCompleted = (int) $done->count();

        $avgSec = (int) DB::table('repair_job_sheets as r')
            ->where('r.business_id', $business_id)
            ->whereNotNull('r.delivery_date')
            ->whereRaw("JSON_CONTAINS(COALESCE(r.service_staff, '[]'), JSON_QUOTE(CAST(? as CHAR)))", [$user_id])
            ->select(DB::raw("AVG(TIMESTAMPDIFF(SECOND, COALESCE(r.start_date, r.entry_date), r.delivery_date)) as avg_sec"))
            ->value('avg_sec');
        $avgJobHours = $avgSec ? round($avgSec / 3600, 1) : 0.0;

        // Specialty from most frequent device category on completed jobs
        $topDevice = DB::table('repair_job_sheets as r')
            ->leftJoin('categories as c', 'c.id', '=', 'r.device_id')
            ->where('r.business_id', $business_id)
            ->whereNotNull('r.delivery_date')
            ->whereRaw("JSON_CONTAINS(COALESCE(r.service_staff, '[]'), JSON_QUOTE(CAST(? as CHAR)))", [$user_id])
            ->groupBy('r.device_id', 'c.name')
            ->orderByRaw('COUNT(*) DESC')
            ->select('c.name', DB::raw('COUNT(*) as cnt'))
            ->first();
        $specialty = $topDevice->name ?? null; // e.g., "Brake Systems"

        // Rating placeholder (requires feedback source)
        $rating = null;

        return view('timemanagement::workers.profile', [
            'user' => $u,
            'present' => (bool) $present,
            'zone_label' => $activeJob ? ('L-' . ($activeJob->workshop_id ?? '—')) : null,
            'zone_name' => $activeJob->workshop_name ?? null,
            'rating' => $rating,
            'today_hours' => $todayHours,
            'avg_job_hours' => $avgJobHours,
            'total_jobs_completed' => $totalCompleted,
            'specialty' => $specialty,
        ]);
    }

    public function jobs($user, Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_id = (int) $user;

        $filters = $request->only(['workshop_id', 'location_id', 'start_date', 'end_date']);
        $q = DB::table('repair_job_sheets as rjs')
            ->leftJoin('transactions as t', 't.repair_job_sheet_id', '=', 'rjs.id')
            ->leftJoin('repair_statuses as rs', 'rs.id', '=', 'rjs.status_id')
            ->leftJoin('workshops as w', 'w.id', '=', 'rjs.workshop_id')
            ->where('rjs.business_id', $business_id)
            ->whereRaw("JSON_CONTAINS(COALESCE(rjs.service_staff, '[]'), JSON_QUOTE(CAST(? as CHAR)))", [$user_id])
            ->select([
                'rjs.id','rjs.job_sheet_no','rjs.entry_date','rjs.start_date','rjs.due_date','rjs.delivery_date',
                'rs.name as status_name','rs.color as status_color',
                'w.name as workshop_name','w.id as workshop_id',
            ])
            ->orderByDesc('rjs.created_at');

        if (!empty($filters['workshop_id'])) { $q->where('rjs.workshop_id', $filters['workshop_id']); }
        if (!empty($filters['location_id'])) { $q->where('rjs.location_id', $filters['location_id']); }
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $q->whereBetween('rjs.entry_date', [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59',
            ]);
        }

        $perPage = (int) $request->input('per_page', 10);
        $page = max(1, (int) $request->input('page', 1));
        $total = (clone $q)->count();
        $rows = $q->forPage($page, $perPage)->get();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($rows, $total, $perPage, $page, [
            'path' => $request->url(), 'query' => $request->query()
        ]);

        return view('timemanagement::workers.jobs', [
            'rows' => $rows,
            'paginator' => $paginator,
            'user_id' => $user_id,
        ] + $filters);
    }
}
