<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Http\Controllers\BusinessController;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\RestaurantUtil;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobsheetExitController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $user = Auth::user();
        $location_id = $user->location_id;
     
        $cars = DB::table('transactions')
            ->leftjoin('repair_job_sheets', 'repair_job_sheets.id', '=', 'transactions.repair_job_sheet_id')
            ->leftjoin('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->leftjoin('contacts', 'contacts.id', '=', 'bookings.contact_id')
            ->leftjoin('contact_device', 'contact_device.id', '=', 'bookings.device_id')
            ->leftjoin('categories', 'categories.id', '=', 'contact_device.device_id')
            ->leftjoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
           
            ->where('bookings.location_id', $location_id)
            ->where('Exit_permission', 'Exit allowed')
            // ->where('repair_job_sheets.start_date', '!=', '0000-00-00 00:00:00')
            ->select(
                'transactions.id',
                'repair_device_models.name AS model',
                'categories.name AS device',
                'contact_device.plate_number',
                'transactions.payment_status',
                'transactions.status',
                'repair_job_sheets.job_sheet_no',
                'contacts.name',
                'contact_device.color',
                'repair_job_sheets.start_date'
            )
            ->get();
        $data = [];
        foreach ($cars as $car) {
            $data[] = [
                'id' => $car->id,
                'date' => $car->start_date,
                'job_sheet_no' => $car->job_sheet_no,
                'contact' => $car->name,
                'device' => $car->device,
                'model' => $car->model,
                'color' => $car->color,
                'plate_number' => $car->plate_number,
                // 'status' => ($car->payment_status == 'paid' && $car->status == 'final') ? 1 : 0
            ];
        }
        return response()->json(["data" => $data]);
    }

    public function updateExitPermission($id)
    {
        $businessUtil = new BusinessUtil();
        $restaurantUtil = new RestaurantUtil();
        $moduleUtil = new ModuleUtil();
        $sendsms = new BusinessController($businessUtil, $restaurantUtil, $moduleUtil);

        // $survey = DB::table('surveys')->where('title', 'Evaluation after completion')->first();

        $currentTime = now();
        $dataContact = DB::table('transactions')
            ->join('contacts', 'contacts.id', '=', 'transactions.contact_id')
            ->select('contacts.id', 'contacts.slug', 'contacts.first_name', 'contacts.mobile')
            ->where('transactions.id', $id)->first();

        // DB::table('action')->insert([
        //     'user_id' => $dataContact->id,
        //     'survey_id' => $survey->id,
        //     'timesend' => $currentTime,
        //     'type_form' => 'Know Person',
        // ]);

        // $action_id = DB::table('action')
        //     ->select('id')
        //     ->where('timesend', $currentTime)
        //     ->where('user_id', $dataContact->id)
        //     ->where('survey_id', $survey->id)
        //     ->first();

        if (!empty($dataContact->slug)) {
            $parts = explode('-', $dataContact->slug);
        } else {
            $generatedSlug = Str::slug(Str::ascii($dataContact->first_name));
            $parts = !empty($generatedSlug) ? [$generatedSlug] : ['default-slug'];
        }

        // $url = 'https://erp.carserv.pro/';
        // $visitUrl = $url . 'survey/' . $parts[0] . '/' . $action_id->id;

        // DB::table('action')
        //     ->where('id', $action_id->id)
        //     ->update(['user_url' => $visitUrl]);

        // $response = $sendsms->sendsurveyAsSms($visitUrl, $dataContact->mobile, $dataContact->first_name);

        DB::table('transactions')->where('id', $id)->update([
            'Exit_permission' => 'Exited',
            // 'status' => 'final',
            // 'payment_status' => 'paid'
        ]);
        return response()->json(["data" => "success"]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('connector::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('connector::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('connector::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
