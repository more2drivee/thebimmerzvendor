<?php

namespace Modules\Crm\Http\Controllers;

use App\BusinessLocation;
use App\Restaurant\Booking;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ContactBookingController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->bookingStatuses = [
            'waiting' => [
                'label' => __('lang_v1.waiting'),
                'class' => 'bg-yellow',
            ],
            'booked' => [
                'label' => __('restaurant.booked'),
                'class' => 'bg-light-blue',
            ],
            'completed' => [
                'label' => __('restaurant.completed'),
                'class' => 'bg-green',
            ],
            'cancelled' => [
                'label' => __('restaurant.cancelled'),
                'class' => 'bg-red',
            ],
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');

        if (request()->ajax()) {
            $start_date = request()->start;
            $end_date = request()->end;
            $query = Booking::where('bookings.business_id', $business_id)
                ->where('bookings.created_by', $user_id)
                ->leftjoin('business_locations as bl', 'bl.id', '=', 'bookings.location_id')
                ->select('bookings.*', 'bl.name as location_name');

            if (! empty(request()->location_id)) {
                $query->where('bookings.location_id', request()->location_id);
            }

            return Datatables::of($query)
                ->editColumn('booking_start', function ($row) {
                    return $this->commonUtil->format_date($row->booking_start, true);
                })
                ->editColumn('booking_end', function ($row) {
                    return $this->commonUtil->format_date($row->booking_end, true);
                })
                ->editColumn('booking_status', function ($row) {
                    $statuses = $this->bookingStatuses;

                    return '<span class="label ' . $statuses[$row->booking_status]['class'] . '" >' . $statuses[$row->booking_status]['label'] . '</span>';
                })
                ->rawColumns(['booking_status'])
                ->removeColumn('id')
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false, false, true, false);

        return view('crm::booking.index', compact('business_locations'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('crm::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */

    public function contactBooking()
    {
        $services = DB::table('types_of_services')->select('name', 'id')->get();
        $business_locations = DB::table('business_locations')->select('name', 'id')->get();
        $default_location = $business_locations->first();
        return view('crm::booking.add_booking', compact('business_locations', 'default_location', 'services'));
    }

    public function store(Request $request)
    {
        try {
            if ($request->ajax()) {
                $business_id = request()->session()->get('user.business_id');
                $user_id = request()->session()->get('user.id');

                $input = $request->input();
                $booking_start = $this->commonUtil->uf_date($input['booking_start'], true);
                $booking_end = $this->commonUtil->uf_date($input['booking_end'], true);
                $date_range = [$booking_start, $booking_end];

                //Check if booking is available for the required input
                $query = Booking::where('business_id', $business_id)
                    ->where('location_id', $input['location_id'])
                    ->where('contact_id', $input['contact_id'])
                    ->where(function ($q) use ($date_range) {
                        $q->whereBetween('booking_start', $date_range)
                            ->orWhereBetween('booking_end', $date_range);
                    });

                $existing_booking = $query->first();
                if (empty($existing_booking)) {
                Log::info($input['booking_start']);

                $input['business_id'] = $business_id;
                $input['created_by'] = $user_id;
                $input['booking_start'] = $booking_start;
                $input['booking_end'] = $booking_end;
                $booking = Booking::createBooking($input);
            
                $output = [
                    'success' => 1,
                    'msg' => trans('lang_v1.added_success'),
                ];
                } else {
                    $time_range = $this->commonUtil->format_date($existing_booking->booking_start, true) . ' ~ ' .
                        $this->commonUtil->format_date($existing_booking->booking_end, true);

                    $output = [
                        'success' => 0,
                        'msg' => trans(
                            'restaurant.booking_not_available',
                            [
                                'customer_name' => $existing_booking->customer->name,
                                'booking_time_range' => $time_range,
                            ]
                        ),
                    ];
                }
                } else {
                    exit(__('messages.something_went_wrong'));
            }
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function storebooking(Request $request)
    {
        // dd($request);
        // $user = Auth::user();
        // $user_id = $user->id;
        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');

        $input = $request->input();
        $device_id = DB::table('contact_device')->insertGetId([
            'device_id' => $input['gehad_category_id'],
            'models_id' => $input['gehad_model_id'],
            'color' => $input['color'],
            'chassis_number' => $input['chassis_number'],
            'plate_number' => $input['plate_number'],
            'manufacturing_year' => $input['manufacturing_year'],
            'car_type' => $input['car_type'],
            'contact_id' => $input['contact_id'],
        ]);

        DB::table('bookings')->insert([
            "booking_start" => $input['booking_start'],
            "location_id" => $input['location_id'],
            "business_id" => $business_id,
            "contact_id" => $input['contact_id'],
            "device_id" => $device_id,
            'booking_note' => $input['booking_note'],
            'created_by' => $user_id,
            'updated_at' => now(),
            'booking_status' => isset($input['booking_status']) ? $input['booking_status'] : 'booked',
            'service_type_id' => $input['service_id'],
        ]);

        return view('crm::booking.thanks');
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return view('crm::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        return view('crm::edit');
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
