<?php

namespace Modules\TimeManagement\Http\Controllers;

use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\TimeManagement\Services\TimeMetricsService;

class DashboardController extends Controller
{
    public function index(Request $request, TimeMetricsService $metrics)
    {
        $business_id = $request->session()->get('user.business_id');
        $workshop_id = $request->get('workshop_id');
        $location_id = $request->get('location_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        // Filters lists
        $workshops = DB::table('workshops')
            ->where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');

        $locations = BusinessLocation::forDropdown($business_id, false, false, false, true);

        // KPIs (date/location aware)
        $present_today = $metrics->getPresentCount($business_id, $workshop_id, $location_id, $start_date, $end_date);
        $late_arrivals = $metrics->getLateArrivalsCount($business_id, $workshop_id, $location_id, $start_date, $end_date);
        $productive_hours = $metrics->getProductiveHours($business_id, $workshop_id, $location_id, $start_date, $end_date);
        $efficiency_rate = $metrics->getEfficiencyRate($business_id, $workshop_id, $location_id, $start_date, $end_date);

        // Active jobs
        $active_jobs = $metrics->getActiveJobs($business_id, $workshop_id, $location_id, $start_date, $end_date);
        return view('timemanagement::dashboard.index', compact(
            'workshops', 'locations',
            'present_today', 'late_arrivals', 'productive_hours', 'efficiency_rate',
            'active_jobs', 'workshop_id', 'location_id', 'start_date', 'end_date'
        ));
    }
}
