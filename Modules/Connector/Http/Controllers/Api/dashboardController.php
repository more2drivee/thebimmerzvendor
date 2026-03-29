<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
// use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Renderable;
use Modules\Connector\Http\Controllers\Api\EndPointsController;

class dashboardController extends Controller
{

    protected $perm;

    public function __construct(
        EndPointsController $perm,

    ) {
        $this->perm = $perm;
    }




    public function data()
    {

        $user = Auth::user();
        $business_id = $user->business_id;
        $location_id = $user->location_id;

        $booking_waiting = DB::table('bookings')
            ->where('booking_status', 'waiting')
            ->where('bookings.business_id', $business_id)
            ->where('bookings.location_id', $location_id)
            ->count();

        // Count job sheets that are callbacks and have waiting status
       // Count job sheets that are callbacks and have waiting status
       $is_callback = DB::table('bookings')
        
        ->where('is_callback', 1)
        ->where('booking_status', 'waiting')

        ->where('location_id', $location_id)

        ->where('business_id', $business_id)

        ->count();

        // Count job sheets with incomplete transactions or unpaid status
        $job_sheet_count = DB::table('repair_job_sheets')

            //->where('business_id', $business_id)
            ->Join('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
            ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->where('transactions.status', '=', 'under processing')

            ->where('repair_job_sheets.business_id', $business_id)
            ->where('bookings.location_id', $location_id)
            ->count();

        // Count product job orders awaiting delivery with client approval
        $job_order = DB::table('product_joborder')

            ->Join('transactions', 'transactions.repair_job_sheet_id', '=', 'product_joborder.job_order_id')
            // ->join('products', 'products.id', '=', 'product_joborder.product_id')
            ->join('repair_job_sheets', 'repair_job_sheets.id', '=', 'product_joborder.job_order_id')
            ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->join('workshops', 'workshops.id', '=', 'repair_job_sheets.workshop_id')
            ->where('transactions.status', '=', 'under processing')
            ->where('product_joborder.delivered_status', 0)
            ->where('product_joborder.client_approval', 1)
            ->where('bookings.location_id', $location_id)


            // ->where('product_joborder.business_id', $business_id)

            ->count();

        // Count overdue job sheets with incomplete transactions or unpaid status
        $date = Carbon::now();
        $job = DB::table('repair_job_sheets')
            ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')

            ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
            ->where('transactions.status', '=', 'under processing')
            ->where('repair_job_sheets.business_id', $business_id)

            ->whereDate('due_date', '<', $date)
            ->where('bookings.location_id', $location_id)


            ->whereDate('repair_job_sheets.due_date', '<', $date)

            ->count();

        // Return JSON response
        return response()->json([
            "data" => [
                'booking_waiting' => $booking_waiting,
                'job_sheet_callback' => $is_callback,
                'product_job_order' => $job_order,
                'job_sheet' => $job_sheet_count,
                'late_job_sheet' => $job,
            ]
        ]);
    }

    public function table()
    {
        $user = Auth::user();
        $location_id = $user->location_id;
        $data = DB::table('product_joborder')
            ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'product_joborder.job_order_id')
            ->leftjoin('products', 'products.id', '=', 'product_joborder.product_id')
            ->leftjoin('repair_job_sheets', 'repair_job_sheets.id', '=', 'product_joborder.job_order_id')
            ->leftJoin('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->leftjoin('workshops', 'workshops.id', '=', 'repair_job_sheets.workshop_id')
            ->where('transactions.status', '=', 'under processing')
            ->where('product_joborder.delivered_status', 0)
            ->where('product_joborder.client_approval', 1)
            ->where('bookings.location_id', $location_id)
            ->where('products.enable_stock', 1)
            
            ->select('product_joborder.id', 'products.name AS product_name', 'repair_job_sheets.job_sheet_no', 'repair_job_sheets.created_at', 'workshops.name AS workshop_name', 'product_joborder.out_for_deliver')
            ->get();


        // return response()->json($data);
        return response()->json(["data" => $data]);
    }

    public function updatestatus($id)
    {
        $user = Auth::user();
        DB::table('product_joborder')->where('id', $id)->update([
            'delivered_status' => 1,
        ]);

        return response()->json(["data" => "sucsess"]);
    }




    public function draw()
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        $location_id = $user->location_id;


        // $businessId = $user->business_id;
        // $coun_waiting = DB::table('bookings')
        //     ->where('booking_status', 'waiting')
        //     ->whereBetween(DB::raw('DATE(booking_start)'), [$start, $end])
        //     ->whereBetween(DB::raw('DATE(booking_end)'), [$start, $end])
        //     ->count();

        $allworkshops = DB::table('workshops')
            ->select(
                'id',
                'name',
                'status',
            )->get();

        $workshops = [];

        foreach ($allworkshops as $workshop) {
            $workshops[] = [
                'id' => $workshop->id,
                'name' => $workshop->name,
                'status' => $workshop->status,
                'job_count' => DB::table('repair_job_sheets')
                    ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
                    ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
                    
                    ->where('repair_job_sheets.workshop_id', '=', $workshop->id)
                    ->where('bookings.location_id', $location_id)
                    ->where('transactions.status', '=', 'under processing')
                    ->count()
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
            $statuses[] = [
                'id' => $status->id,
                'name' => $status->name,
                'color' => $status->color,
                'job_count' => DB::table('repair_job_sheets')
                    ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
                    ->where('repair_job_sheets.status_id', '=', $status->id)
                    ->where('bookings.location_id', $location_id)

                    ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
                    ->where('transactions.status', '=', 'under processing')

                    ->count()
            ];
        }




        // $job_orders = DB::


        $datenow = Carbon::now();
        $dayNumber = $datenow->day;
        $daysArray = range(1, $dayNumber);
        $currentMonth = $datenow->month;

        $bookingcounts = [];
        foreach ($daysArray as $day) {
            $bookingcounts[] = [
                'x' => $day,
                'y' => DB::table('bookings')
                    ->where('bookings.location_id', $location_id)
       
                    ->whereDay('booking_start', '=', $day)
                    ->whereMonth('booking_start', '=', $currentMonth)
                    ->count()
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

        // $permittedLocations = $user->permitted_locations($businessId);

        $serviceStaffRoles = Role::where('business_id', $businessId)
            ->where('is_service_staff', 1)
            ->pluck('name')
            ->toArray();

        $techStaffs = User::where('business_id', $businessId)
            ->where('location_id', $user->location_id)
            ->role($serviceStaffRoles)
            ->select('id', 'surname', 'first_name', 'last_name')
            ->get();


        // if ($permittedLocations !== 'all') {
        //     $techStaffs = $techStaffs->filter(function ($staff) use ($permittedLocations) {
        //         return $this->perm->hasLocationPermissions($staff, $permittedLocations);
        //     });
        // }

        $techStaffsList = $techStaffs->map(function ($staff) use ($location_id) {
            $fullName = trim(($staff->first_name ?? '') . ' ' . ($staff->surname ?? '') . ' ' . ($staff->last_name ?? ''));
            $count = [];
            $datejobsheet = DB::table('repair_job_sheets')
                ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')

                ->where('bookings.location_id', $location_id)

                ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
                ->where('transactions.status', '=', 'under processing')

                ->select('repair_job_sheets.service_staff')
                ->get();

            foreach ($datejobsheet as $job) {
                $array = json_decode($job->service_staff, true);

                if (is_array($array)) {
                    foreach ($array as $i) {
                        $count[$i] = ($count[$i] ?? 0) + 1;
                    }
                }
            }

            return [
                'id' => $staff->id,
                'name' => $fullName,
                'jobsheetcount' => $count[$staff->id] ?? 0
            ];
        });

        $workers = [];

        foreach ($techStaffsList as $key => $value) {
            $workers[] = $value;
        }


        return response()->json([
            "data" => [
                "workshop" => $workshops,
                "status" => $statuses,
                "joborders" => [
                    "booking" => $bookingcounts,
                    "jobsheet" => $jobsheetcounts,
                    "callback" => $callbackcounts,
                ],
                "workers" => $workers

            ]
        ]);
    }

    public function uploadImage(Request $request)
    {
       
        
        $business = DB::table('business')->first();
        
        if (!$business || empty($business->logo)) {
            return response()->json(['error' => 'Logo not found'], 404);
        }

        // Assuming the logo field contains the relative path from public/
        $logoPath = 'public/uploads/business_logos/' . $business->logo;
        return response()->json(['logo' => $logoPath]);
    }

    public function uploadImageWithDomain(Request $request)
    {
        $business = DB::table('business')->first();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $response = [];

        if (!empty($business->logo)) {
            $logoPath = 'public/uploads/business_logos/' . $business->logo;
            $response['logo'] = rtrim($request->getSchemeAndHttpHost(), '/') . '/' . ltrim($logoPath, '/');
        }

        if (!empty($business->repair_jobsheet_settings)) {
            $repair_jobsheet_settings = json_decode($business->repair_jobsheet_settings, true);
            if (!empty($repair_jobsheet_settings['jobsheet_image'])) {
                $jobsheetPath = 'public/uploads/business_logos/' . $repair_jobsheet_settings['jobsheet_image'];
                $response['jobsheet_canva'] = rtrim($request->getSchemeAndHttpHost(), '/') . '/' . ltrim($jobsheetPath, '/');
            }
        }

        return response()->json($response);
    }

    public function sidebar()
    {
        $user = Auth::user();
        $location_id = $user->location_id;
        $user_id = $user->id;
        $data = DB::table('users')->select('first_name', 'last_name', 'location_id')->where('id', $user_id)->first();
        $location = DB::table('business_locations')->select('name', 'id')->where('id', $data->location_id)->first();

        return response()->json([
            "data" => [
                "name" => $data->first_name . " " . $data->last_name,
                "location" => $location->name,
                "location_id" => $location->id,
            ]
        ]);
    }
}
