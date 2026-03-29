<?php

namespace Modules\TimeManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TimeStatisticsController extends Controller
{
    /**
     * Show statistics for timer stop reasons and technicians.
     */
    public function index(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        // Default: last 7 days if no filter provided
        if (empty($start_date) || empty($end_date)) {
            $end_date = now()->toDateString();
            $start_date = now()->subDays(6)->toDateString();
        }

        $startDateTime = $start_date . ' 00:00:00';
        $endDateTime = $end_date . ' 23:59:59';

        // Base query: aggregate pause time per reason body
        $reasonsQuery = DB::table('timer_stop_reasons as tsr')
            ->join('timer_tracking as tt', 'tsr.timer_id', '=', 'tt.id')
            ->join('repair_job_sheets as js', 'tt.job_sheet_id', '=', 'js.id')
            ->join('transactions as t', 't.repair_job_sheet_id', '=', 'js.id')
            ->where('t.business_id', $business_id)
            ->whereNotNull('tsr.pause_start')
            ->whereNotNull('tsr.pause_end')
            ->whereBetween('tsr.created_at', [$startDateTime, $endDateTime])
            ->select(
                'tsr.body',
                DB::raw('COUNT(*) as occurrences'),
                DB::raw('SUM(GREATEST(TIMESTAMPDIFF(SECOND, tsr.pause_start, tsr.pause_end),0)) as total_pause_seconds')
            )
            ->groupBy('tsr.body')
            ->orderByDesc('total_pause_seconds');

        // Full collection for totals & charts
        $reasons_chart_stats = (clone $reasonsQuery)->get();
        // Paginated collection for table
        $reasons_stats = (clone $reasonsQuery)->paginate(10);

        // Base query: aggregate pause time per technician
        $techniciansQuery = DB::table('timer_stop_reasons as tsr')
            ->join('timer_tracking as tt', 'tsr.timer_id', '=', 'tt.id')
            ->join('users as u', 'tt.user_id', '=', 'u.id')
            ->join('repair_job_sheets as js', 'tt.job_sheet_id', '=', 'js.id')
            ->join('transactions as t', 't.repair_job_sheet_id', '=', 'js.id')
            ->where('t.business_id', $business_id)
            ->whereNotNull('tsr.pause_start')
            ->whereNotNull('tsr.pause_end')
            ->whereBetween('tsr.created_at', [$startDateTime, $endDateTime])
            ->select(
                'u.id as user_id',
                'u.first_name',
                'u.last_name',
                DB::raw('COUNT(*) as reasons_count'),
                DB::raw('SUM(GREATEST(TIMESTAMPDIFF(SECOND, tsr.pause_start, tsr.pause_end),0)) as total_pause_seconds')
            )
            ->groupBy('u.id', 'u.first_name', 'u.last_name')
            ->orderByDesc('total_pause_seconds');

        // Full collection for charts & aggregates
        $technician_chart_stats = (clone $techniciansQuery)->get();
        // Paginated collection for table
        $technician_stats = (clone $techniciansQuery)->paginate(10);

        // Finishtimer links: completed timer vs resumed timer
        $finish_links_stats = DB::table('timer_stop_reasons as tsr')
            ->join('timer_tracking as completed_tt', 'tsr.timer_id', '=', 'completed_tt.id')
            ->join('users as u', 'completed_tt.user_id', '=', 'u.id')
            ->join('repair_job_sheets as js', 'completed_tt.job_sheet_id', '=', 'js.id')
            ->join('transactions as t', 't.repair_job_sheet_id', '=', 'js.id')
            ->leftJoin('timer_tracking as resumed_tt', 'tsr.resumed_timer_id', '=', 'resumed_tt.id')
            ->leftJoin('repair_job_sheets as resumed_js', 'resumed_tt.job_sheet_id', '=', 'resumed_js.id')
      
            ->whereBetween('tsr.created_at', [$startDateTime, $endDateTime])
            ->select(
                'tsr.id as reason_id',
                'tsr.body',
                'u.first_name',
                'u.last_name',
                'completed_tt.id as completed_timer_id',
                'resumed_tt.id as resumed_timer_id',
                'js.job_sheet_no as completed_job_sheet_no',
                'resumed_js.job_sheet_no as resumed_job_sheet_no'
            )
            ->orderByDesc('tsr.created_at')
            ->paginate(10);

        $total_pause_seconds = $reasons_chart_stats->sum('total_pause_seconds');
        $total_reasons_count = $reasons_chart_stats->sum('occurrences');
        $total_technicians_count = $technician_chart_stats->count();

        return view('timemanagement::time_statistics.index', [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'reasons_stats' => $reasons_stats,
            'technician_stats' => $technician_stats,
            'reasons_chart_stats' => $reasons_chart_stats,
            'technician_chart_stats' => $technician_chart_stats,
            'total_pause_seconds' => $total_pause_seconds,
            'total_reasons_count' => $total_reasons_count,
            'total_technicians_count' => $total_technicians_count,
            'finish_links_stats' => $finish_links_stats,
        ]);
    }
}
