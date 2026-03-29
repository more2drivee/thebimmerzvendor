<?php

namespace Modules\Repair\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Repair\Utils\RepairUtil;

class DashboardController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $repairUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(RepairUtil $repairUtil)
    {
        $this->repairUtil = $repairUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $location_id = $request->query('location');
        $user = Auth::user();
        $business_id = $user->business_id;

        $isAdmin = $user->hasRole('Admin#' . $business_id) || $user->can('superadmin');
        $permitted_locations = $user->permitted_locations($business_id);

        if($location_id == Null){
            $location_id = $user->location_id;
        }

        if (!$isAdmin && $permitted_locations != 'all') {
            if (!in_array($location_id, $permitted_locations)) {
                $location_id = $permitted_locations[0] ?? $user->location_id;
            }
        }

        $locations = DB::table('business_locations')
            ->select('id', 'name')
            ->where('business_id', $business_id)
            ->when($permitted_locations != 'all', function($q) use ($permitted_locations) {
                return $q->whereIn('id', $permitted_locations);
            })
            ->get();

        $booking_waiting = DB::table('bookings')
            ->where('booking_status', 'waiting')
            ->where('bookings.business_id', $business_id)
            ->where('bookings.location_id', $location_id)
            ->count();

        $is_callback = DB::table('repair_job_sheets')
            ->join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->where('bookings.is_callback', 1)
            ->where('bookings.booking_status', 'waiting')
            ->where('bookings.location_id', $location_id)
            ->where('bookings.business_id', $business_id)
            ->whereNull('repair_job_sheets.deleted_at')
            ->count();

        $job_sheet_count = DB::table('repair_job_sheets')
            ->Join('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
            ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->where('transactions.status', '=', 'under processing')
            ->where('repair_job_sheets.business_id', $business_id)
            ->where('bookings.location_id', $location_id)
            ->whereNull('repair_job_sheets.deleted_at')
            ->count();

        $job_order = DB::table('product_joborder')
            ->Join('transactions', 'transactions.repair_job_sheet_id', '=', 'product_joborder.job_order_id')
            ->join('products', 'products.id', '=', 'product_joborder.product_id')
            ->join('repair_job_sheets', 'repair_job_sheets.id', '=', 'product_joborder.job_order_id')
            ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->join('workshops', 'workshops.id', '=', 'repair_job_sheets.workshop_id')
            ->where('transactions.status', '=', 'under processing')
            ->where('product_joborder.delivered_status', 0)
            ->where('product_joborder.client_approval', 1)
            ->where('bookings.location_id', $location_id)
            ->whereNull('repair_job_sheets.deleted_at')
            ->count();

        $date = Carbon::now();
        $job = DB::table('repair_job_sheets')
            ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
            ->where('transactions.status', '=', 'under processing')
            ->where('repair_job_sheets.business_id', $business_id)
            ->whereDate('due_date', '<', $date)
            ->where('bookings.location_id', $location_id)
            ->whereDate('repair_job_sheets.due_date', '<', $date)
            ->whereNull('repair_job_sheets.deleted_at')
            ->count();

        $counters = [
            __('repair::lang.cars_waiting') => ["data" => $booking_waiting, "icon" => "fas fa-car"],
            __('repair::lang.count_of_job_orders') => ["data" => $job_sheet_count, "icon" => "fa-solid fa-gear"],
            __('repair::lang.spare_parts_requests') => ["data" => $job_order, "icon" => "fa-solid fa-wrench"],
            __('repair::lang.count_of_callback') => ["data" => $is_callback, "icon" => "fa-solid fa-arrow-rotate-left"],
            __('repair::lang.late_job_orders') => ["data" => $job, "icon" => "fa-solid fa-clock"],
        ];

        $table = DB::table('product_joborder')
            ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'product_joborder.job_order_id')
            ->join('products', 'products.id', '=', 'product_joborder.product_id')
            ->join('repair_job_sheets', 'repair_job_sheets.id', '=', 'product_joborder.job_order_id')
            ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->join('workshops', 'workshops.id', '=', 'repair_job_sheets.workshop_id')
            ->where('transactions.status', '=', 'under processing')
            ->where('product_joborder.delivered_status', 0)
            ->where('product_joborder.client_approval', 1)
            ->where('bookings.location_id', $location_id)
            ->whereNull('repair_job_sheets.deleted_at')
            ->select('product_joborder.id', 'products.name AS product_name', 'repair_job_sheets.job_sheet_no', 'repair_job_sheets.created_at', 'workshops.name AS workshop_name', 'product_joborder.out_for_deliver')
            ->get();

        $circle = [
            'left_chart' => [],
            'right_chart' => [],
        ];
        $allworkshops = DB::table('workshops')
            ->select(
                'id',
                'name',
                'status',
            )->get();
        $colors = ['blue', 'green', 'red', 'orange', 'purple', 'yellow', 'cyan', 'pink'];
        foreach ($allworkshops as $workshop) {
            $randomColor = $colors[array_rand($colors)];
            $circle['right_chart'][] = [
                'label' => $workshop->name,
                'value' => DB::table('repair_job_sheets')
                    ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
                    ->where('repair_job_sheets.workshop_id', '=', $workshop->id)
                    ->where('bookings.location_id', $location_id)
                    ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
                    ->where('transactions.status', '=', 'under processing')
                    ->whereNull('repair_job_sheets.deleted_at')
                    ->count(),
                'color' => $randomColor
            ];
        }

        $allstatus = DB::table('repair_statuses')
            ->where('status_category', 'status')
            ->select(
                'id',
                'name',
                'color',
            )->get();

        $statuses = [];

        foreach ($allstatus as $status) {
            $circle['left_chart'][] = [
                'label' => $status->name,
                'value' => DB::table('repair_job_sheets')
                    ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
                    ->where('repair_job_sheets.status_id', '=', $status->id)
                    ->where('bookings.location_id', $location_id)

                    ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
                    ->where('transactions.status', '=', 'under processing')
                    ->whereNull('repair_job_sheets.deleted_at')

                    ->count(),
                'color' => $status->color,
            ];
        }

        $datenow = Carbon::now();
        $dayNumber = $datenow->day;
        $daysArray = range(1, $dayNumber);
        $currentMonth = $datenow->month;
        $labels = range(1, 31);

        $bookingcounts = [];
        foreach ($daysArray as $day) {
            $query = DB::table('bookings')
                ->whereDay('booking_start', '=', $day)
                ->whereMonth('booking_start', '=', $currentMonth);

            if (!$isAdmin && $permitted_locations != 'all') {
                $query->whereIn('location_id', $permitted_locations);
            }

            $bookingcounts[] = [
                'x' => $day,
                'y' => $query->count()
            ];
        }

        $jobsheetcounts = [];
        foreach ($daysArray as $day) {
            $jobsheetcounts[] = [
                'x' => $day,
                'y' => DB::table('repair_job_sheets')
                    ->where('repair_job_sheets.location_id', $location_id)
                    ->whereDay('created_at', '=', $day)
                    ->whereMonth('created_at', '=', $currentMonth)
                    ->whereNull('repair_job_sheets.deleted_at')
                    ->count()
            ];
        }

        $callbackcounts = [];
        foreach ($daysArray as $day) {
            $callbackcounts[] = [
                'x' => $day,
                'y' => DB::table('bookings')
                    ->where('bookings.location_id', $location_id)

                    ->where('is_callback', '=', 1)
                    ->whereDay('booking_start', '=', $day)
                    ->whereMonth('booking_start', '=', $currentMonth)
                    ->count()
            ];
        }

        $business_id = request()->session()->get('user.business_id');
        $job_sheets_by_status = $this->repairUtil->getRepairByStatus($business_id);
        $job_sheets_by_service_staff = $this->repairUtil->getRepairByServiceStaff($business_id);
        $trending_brand_chart = $this->repairUtil->getTrendingRepairBrands($business_id);
        $trending_devices_chart = $this->repairUtil->getTrendingDevices($business_id);
        $trending_dm_chart = $this->repairUtil->getTrendingDeviceModels($business_id);

        return view('repair::dashboard.index')
            ->with(compact('location_id', 'locations', 'circle', 'table', 'labels', 'bookingcounts', 'jobsheetcounts', 'callbackcounts', 'counters', 'job_sheets_by_status', 'job_sheets_by_service_staff', 'trending_devices_chart', 'trending_dm_chart', 'trending_brand_chart'));
    }

    public function editjobStatus($id)
    {
        DB::table('product_joborder')->where('id', $id)->update([
            'out_for_deliver' => 1,
            'delivered_status' => 1
        ]);

        return redirect('repair/dashboard');
    }

    // public function index()
    // {
    //     $user = Auth::user();
    //     $business_id = $user->business_id;
    //     $location_id = $user->location_id;
    //     $data = [
    //         'left_chart' => [],
    //         'right_chart' => [],
    //     ];
    //     $allworkshops = DB::table('workshops')
    //         ->select(
    //             'id',
    //             'name',
    //             'status',
    //         )->get();
    //     $colors = ['blue', 'green', 'red', 'orange', 'purple', 'yellow', 'cyan', 'pink'];
    //     foreach ($allworkshops as $workshop) {
    //         $randomColor = $colors[array_rand($colors)];
    //         $data['right_chart'][] = [
    //             'label' => $workshop->name,
    //             'value' => DB::table('repair_job_sheets')
    //                 ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
    //                 ->where('repair_job_sheets.workshop_id', '=', $workshop->id)
    //                 ->where('bookings.location_id', $location_id)
    //                 ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
    //                 ->where('transactions.status', '=', 'under processing')
    //                 ->count(),
    //             'color' => $randomColor
    //         ];
    //     }

    //     $allstatus = DB::table('repair_statuses')
    //         ->where('status_category', 'status')
    //         ->select(
    //             'id',
    //             'name',
    //             'color',
    //         )->get();

    //     $statuses = [];

    //     foreach ($allstatus as $status) {
    //         $data['left_chart'][] = [
    //             'label' => $status->name,
    //             'value' => DB::table('repair_job_sheets')
    //                 ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
    //                 ->where('repair_job_sheets.status_id', '=', $status->id)
    //                 ->where('bookings.location_id', $location_id)

    //                 ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
    //                 ->where('transactions.status', '=', 'under processing')

    //                 ->count(),
    //             'color' => $status->color,
    //         ];
    //     }


    //     return view('repair::dashboard.index', compact('data'));
    // }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('repair::create');
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
        return view('repair::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        return view('repair::edit');
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
