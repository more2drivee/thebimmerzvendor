<?php

namespace Modules\Repair\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Livewire;
use Illuminate\Support\Facades\Session;


session_start();
class InfoJobOrderController extends Controller
{

    // public function getStatus($jobOrderId)
    // {
    //     $statuses = DB::table('repair_statuses')
    //         ->select('name', 'id', 'color')
    //         ->where('status_category', 'status')
    //         ->get();

    //     $currentStatus = DB::table('repair_job_sheets')
    //         ->select('status_id')
    //         ->where('id', $jobOrderId)
    //         ->first();

    //     return response()->json([
    //         'statuses' => $statuses,
    //         'currentStatusId' => $currentStatus ? $currentStatus->status_id : null
    //     ]);
    // }

    public function check($id)
    {
        return view('repair::info_job_order.index', compact('id'));
    }

    public function testcheckphone($id, Request $request)
    {
        $info = DB::table('product_joborder')
            ->join('repair_job_sheets', 'repair_job_sheets.id', '=', 'product_joborder.job_order_id')
            ->join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->join('contacts', 'bookings.contact_id', '=', 'contacts.id')
            ->where('repair_job_sheets.id', $id)
            ->first();

            // dd($info->mobile);

        $last4 = substr($info->mobile, -4);
        // dd($request->phone);
        if ($last4 != $request->phone) {
            $url = url('check/phone') . '/' . $id;
            return redirect($url);
        }
        $check = "true" . $id;
        //dd($id);
        $_SESSION[$check] = $last4;
        //dd($_SESSION);

        $url = url('info/job/order') . '/' . $id;
        return redirect($url);
    }

    public function checkphone($id)
    {
        $check = "true" . $id;
        // dd($_SESSION);
        if (isset($_SESSION[$check])) {
            return $this->info($id);
        }

        $url = url('check/phone') . '/' . $id;
        //dd($_SESSION);

        return redirect($url);
        //dd($_SESSION);
    }


    public function info($id, $okay = '')
    {
        //if ($_SESSION[$id] == true) {
        $job_order = DB::table('product_joborder')->where('job_order_id', $id)->get();
        $date = DB::table('repair_job_sheets')->select('start_date', 'due_date')->where('id', $id)->first();

        $hours = Carbon::parse(Carbon::now()->toDateTimeString())->diffInHours(Carbon::parse($date->due_date));
        $minutes = Carbon::parse(Carbon::now()->toDateTimeString())->diffInMinutes(Carbon::parse($date->due_date));

        if (Carbon::parse($date->due_date)->isPast()) {
            $days = 0;
            $hours = 0;
            $minutes = 0;
            $seconds = 0;
        } else {
            $diff = Carbon::parse(now())->diff(Carbon::parse($date->due_date));
            $days = $diff->days;
            $hours = $diff->h;
            $minutes = $diff->m;
            $seconds = $diff->s;
        }

        return view('repair::info_job_order.show', compact('okay', 'days', 'hours', 'minutes', 'seconds',  'job_order', 'id'));
        //}
        //$url = url('check/phone') . '/' . $id;
        //return redirect($url);
    }

    public function saveData(Request $request)
    {
        if (isset($request->product_ids)) {
            foreach ($request->product_ids as $key => $value) {
                DB::table('product_joborder')->where('job_order_id', $request->job_order_id)
                    ->where('id', $key)
                    ->update(['client_approval' => 1]);
            }
        }
        //dd($request);
        // return $this->info($request->job_order_id, "تم حفظ البيانات");
        return $this->saveDataShow($request->job_order_id);
        //return redirect()->back();

        //$url = url('info/job/order') . '/' . $request->job_order_id;
        //return redirect($url);
        //dd($request);
    }

    public function saveDataShow($id)
    {
        $url = url('info/job/order') . '/' . $id;
        return redirect($url);
    }
}
