<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Http\Controllers\BusinessController;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\RestaurantUtil;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Modules\Connector\Http\Controllers\Api\EndPointsController;


class DataJobSheetController extends Controller
{

    protected $perm;

    public function __construct(
        EndPointsController $perm,

    ) {
        $this->perm = $perm;
    }

    // public function test()
    // {
    //     $businessUtil = new BusinessUtil();
    //     $restaurantUtil = new RestaurantUtil();
    //     $moduleUtil = new ModuleUtil();

    //     $sendsms = new BusinessController($businessUtil, $restaurantUtil, $moduleUtil);
    //     $survey = DB::table('surveys')->where('title', 'Evaluation after completion')->first();
    //     $currentTime = now();

    //     DB::table('action')->insert([
    //         'user_id' => 10,
    //         'survey_id' => $survey->id,
    //         'timesend' => $currentTime,
    //         'type_form' => 'Know Person',
    //     ]);
    //     $action_id = DB::table('action')->select('id')->where('timesend', $currentTime)->where('user_id', 10)->first();
    //     // $user_name = DB::table('contacts')
    //     //     ->select('email', 'first_name', 'slug', 'mobile')
    //     //     ->where('id', 10)
    //     //     ->first();
    //     // $parts = explode('-', $user_name->slug);
        
    //     $url = 'https://erp.carserv.pro/';
    //     $visitUrl = $url . 'survey/' . 'gehad' . '/' . $action_id->id;
    //     DB::table('action')
    //         ->where('id', $action_id->id)
    //         ->update(['user_url' => $visitUrl]);

    //     $response = $sendsms->sendsurveyAsSms($visitUrl, '01140224342', 'gehad');
    //     return $response;
    // }

    public function data()
    {

        $user = Auth::user();
        $id_user = $user->id;
        $businessId = $user->business_id;
        $location_id = $user->location_id;
        // $date = Carbon::now()->toDateString();
        // $business_id = $user->business_id;
        // $job_sheet = DB::table('repair_job_sheets')
        //     ->whereDate('repair_job_sheets.due_date', '<', $date)
        //     ->where('repair_job_sheets.business_id', $business_id)
        //     ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
        //     ->where(function ($query) {
        //         $query->where(function ($q) {
        //             $q->where('transactions.status', '!=', 'final')
        //                 ->orWhereNull('transactions.status');
        //         })
        //             ->where(function ($q) {
        //                 $q->where('transactions.payment_status', '!=', 'paid')
        //                     ->orWhereNull('transactions.payment_status');
        //             });
        //     })
        //     ->join('business_locations', 'business_locations.id', '=', 'repair_job_sheets.location_id')
        //     ->select('repair_job_sheets.id', 'repair_job_sheets.job_sheet_no', 'repair_job_sheets.location_id')->get();
        // // dd($job_sheet);
        // $job_data = [];
        $status = DB::table('notifications')->where('user_id', $id_user)
            ->select('status', 'job_sheet_id', 'job_sheet_no', 'user_id')->get();

        $content = [];
        foreach ($status as $job) {
            $datacar = DB::table('repair_job_sheets')
                ->Join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
                ->Join('contact_device', 'contact_device.id', '=', 'bookings.device_id')
                ->where('bookings.location_id', $location_id)
                ->where('bookings.business_id', $businessId)

                ->where('repair_job_sheets.id', $job->job_sheet_id)
                ->select('contact_device.models_id', 'contact_device.device_id', 'contact_device.plate_number', 'contact_device.manufacturing_year')->first();

            $content[] = [
                'status' => $job->status,
                'job_sheet_id' => $job->job_sheet_id,
                'job_sheet_no' => $job->job_sheet_no,
                'user_id' => $job->user_id,
                'models_id' => $datacar->models_id,
                'device_id' => $datacar->device_id,
                'plate_number' => $datacar->plate_number,
                'manufacturing_year' => $datacar->manufacturing_year,
            ];
        }


        return response()->json(["data" => $content]);




    }

    public function updateStatus(Request $request)
    {
        DB::table('notifications')->where('user_id', $request->user_id)->where('job_sheet_id', $request->job_id)->update([
            'status' => 1
        ]);
        return response()->json(["data" => "success"]);
    }

    public function workers()
    {

        $user = Auth::user();

        $businessId = $user->business_id;
      
    

        $techStaffs = User::where('location_id', $user->location_id)
            ->where('allow_login', 0)
            ->where('user_type', 'user')
            ->select('id', 'surname', 'first_name', 'last_name', 'location_id')
            ->get();
   

        $locationId = $user->location_id;
        $techStaffsList = [];

        foreach ($techStaffs as $staff) {
            $fullName = trim(($staff->first_name ?? '') . ' ' . ($staff->surname ?? '') . ' ' . ($staff->last_name ?? ''));
            $techStaffsList[$staff->id] = [
                'id' => $staff->id,
                'name' => $fullName,
                'alljobsheet' => 0,
                'precentage' => 0.0,
                'jobsheetopen' => 0,
                'jobsheetclosed' => 0,
                'jobsheetdelay' => 0,
                'callback' => 0,
            ];
        }

        if (empty($techStaffsList)) {
            return response()->json(["data" => []]);
        }

        $alljobs = DB::table('repair_job_sheets')
            ->where('repair_job_sheets.location_id', $locationId)
            ->select('service_staff')
            ->get();

        foreach ($alljobs as $job) {
            $array = json_decode($job->service_staff, true);

            if (!is_array($array)) {
                continue;
            }

            foreach ($array as $i) {
                if (isset($techStaffsList[$i])) {
                    $techStaffsList[$i]['alljobsheet']++;
                }
            }
        }

        $datejobsheetopen = DB::table('repair_job_sheets')
            ->join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
            ->where('transactions.status', '=', 'under processing')
            ->where('bookings.location_id', $locationId)
            ->select('repair_job_sheets.service_staff')
            ->get();

        foreach ($datejobsheetopen as $job) {
            $array = json_decode($job->service_staff, true);

            if (!is_array($array)) {
                continue;
            }

            foreach ($array as $i) {
                if (isset($techStaffsList[$i])) {
                    $techStaffsList[$i]['jobsheetopen']++;
                }
            }
        }

        $jobsheetclosed = DB::table('transactions')
            ->where('transactions.status', '=', 'final')
            ->join('repair_job_sheets', 'repair_job_sheets.id', '=', 'transactions.repair_job_sheet_id')
            ->where('repair_job_sheets.location_id', $locationId)
            ->select('repair_job_sheets.service_staff')
            ->get();

        foreach ($jobsheetclosed as $job) {
            $array = json_decode($job->service_staff, true);

            if (!is_array($array)) {
                continue;
            }

            foreach ($array as $i) {
                if (isset($techStaffsList[$i])) {
                    $techStaffsList[$i]['jobsheetclosed']++;
                }
            }
        }

        $date = Carbon::now()->toDateString();
        $job_sheet_delay = DB::table('repair_job_sheets')
            ->join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
            ->whereDate('repair_job_sheets.due_date', '<', $date)
            ->where('bookings.location_id', $locationId)
            ->where('transactions.status', '=', 'under processing')
            ->select('repair_job_sheets.service_staff')
            ->get();

        foreach ($job_sheet_delay as $job) {
            $array = json_decode($job->service_staff, true);

            if (!is_array($array)) {
                continue;
            }

            foreach ($array as $i) {
                if (isset($techStaffsList[$i])) {
                    $techStaffsList[$i]['jobsheetdelay']++;
                }
            }
        }

        $callbackJobs = DB::table('bookings')
            ->join('repair_job_sheets', 'repair_job_sheets.id', '=', 'bookings.call_back_ref')
            ->where('bookings.is_callback', 1)
            ->whereNotNull('bookings.call_back_ref')
            ->where('repair_job_sheets.location_id', $locationId)
            ->select('repair_job_sheets.service_staff')
            ->get();

        foreach ($callbackJobs as $job) {
            $array = json_decode($job->service_staff, true);

            if (!is_array($array)) {
                continue;
            }

            foreach ($array as $i) {
                if (isset($techStaffsList[$i])) {
                    $techStaffsList[$i]['callback']++;
                }
            }
        }

        foreach ($techStaffsList as &$staffMetrics) {
            $totalJobs = $staffMetrics['alljobsheet'];
            $base = $totalJobs > 0 ? $totalJobs : 1;
            $staffMetrics['precentage'] = $staffMetrics['jobsheetdelay'] > 0 ? (float)(($staffMetrics['jobsheetdelay'] / $base) * 100.0) : 0.0;
        }
        unset($staffMetrics);

        return response()->json(["data" => array_values($techStaffsList)]);
    }

}
