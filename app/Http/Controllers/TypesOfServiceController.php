<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\TypesOfService;
use App\Utils\Util;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TypesOfServiceController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $commonUtil;

    /**
     * Constructor
     *
     * @param  TaxUtil  $taxUtil
     * @return void
     */
    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('access_types_of_service')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $user = auth()->user();

            $types_q = TypesOfService::where('business_id', $business_id)
                        ->select('*');

            // Filter by user's location_id if not admin
            if (!$user->is_admin) {
                $user_location_id = $user->location_id;
                $types_q->where(function ($query) use ($user_location_id) {
                    $query->whereJsonContains('location_price_group->' . $user_location_id, 0)
                          ->orWhereNull('location_price_group')
                          ->orWhere('location_price_group', '');
                });
            }

            // Preload locations map for name lookup
            $locations_map = BusinessLocation::forDropdown($business_id);

            return Datatables::of($types_q)
                ->addColumn('locations', function ($row) use ($locations_map) {
                    $ids = [];
                    if (!empty($row->location_price_group) && is_array($row->location_price_group)) {
                        $ids = array_keys($row->location_price_group);
                    }
                    if (empty($ids)) {
                        return '-';
                    }
                    $names = [];
                    foreach ($ids as $id) {
                        if (isset($locations_map[$id])) {
                            $names[] = e($locations_map[$id]);
                        }
                    }
                    return !empty($names) ? implode(', ', $names) : '-';
                })
                ->addColumn('inspection_service', function ($row) {
                    return $row->is_inspection_service ? __('messages.yes') : __('messages.no');
                })
                ->addColumn('is_inspection_service', function ($row) {
                    return $row->is_inspection_service ? __('messages.yes') : __('messages.no');
                })
                ->addColumn(
                    'action',
                    '<button data-href="{{action(\'App\Http\Controllers\TypesOfServiceController@edit\', [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-container=".type_of_service_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                    <button data-href="{{action(\'App\Http\Controllers\TypesOfServiceController@destroy\', [$id])}}" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error delete_type_of_service"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>'
                )
                ->editColumn('packing_charge', function ($row) {
                    $html = '<span class="display_currency" data-currency_symbol="false">'.$row->packing_charge.'</span>';

                    if ($row->packing_charge_type == 'percent') {
                        $html .= '%';
                    }

                    return $html;
                })
                ->removeColumn('id')
                ->rawColumns(['locations', 'action', 'packing_charge'])
                ->make(true);
        }

        return view('types_of_service.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('access_types_of_service')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $locations = BusinessLocation::forDropdown($business_id);

        return view('types_of_service.create')
                ->with(compact('locations'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('access_types_of_service')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'description',
                'location_price_group', 'packing_charge_type',
                'packing_charge', 'is_inspection_service']);

            $input['business_id'] = $request->session()->get('user.business_id');
            $input['packing_charge'] = ! empty($input['packing_charge']) ? $this->commonUtil->num_uf($input['packing_charge']) : 0;
            $input['enable_custom_fields'] = ! empty($request->input('enable_custom_fields')) ? 1 : 0;

            $selected_locations = (array) $request->input('location_price_group', []);
            $selected_locations = array_values(array_unique(array_filter($selected_locations, function ($locationId) {
                return $locationId !== null && $locationId !== '';
            })));
            $input['location_price_group'] = ! empty($selected_locations)
                ? array_fill_keys($selected_locations, 0)
                : null;

            TypesOfService::create($input);

            $output = ['success' => true,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\TypesOfService  $typesOfService
     * @return \Illuminate\Http\Response
     */
    public function show(TypesOfService $typesOfService)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TypesOfService  $typesOfService
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('access_types_of_service')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $locations = BusinessLocation::forDropdown($business_id);

        $type_of_service = TypesOfService::where('business_id', $business_id)
                                            ->findOrFail($id);

        $raw_location_price_group = $type_of_service->location_price_group;
        if (is_string($raw_location_price_group)) {
            $decoded = json_decode($raw_location_price_group, true);
            $locations_array = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw_location_price_group)) {
            $locations_array = $raw_location_price_group;
        } else {
            $locations_array = [];
        }

        $selected_locations = array_keys($locations_array);

        return view('types_of_service.edit')
                ->with(compact('locations', 'type_of_service', 'selected_locations'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\TypesOfService  $typesOfService
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('access_types_of_service')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'description',
                'location_price_group', 'packing_charge_type',
                'packing_charge', ]);

            $business_id = $request->session()->get('user.business_id');
            $input['packing_charge'] = ! empty($input['packing_charge']) ? $this->commonUtil->num_uf($input['packing_charge']) : 0;
            $input['enable_custom_fields'] = ! empty($request->input('enable_custom_fields')) ? 1 : 0;
            $input['is_inspection_service'] = ! empty($request->input('is_inspection_service')) ? 1 : 0;

            $selected_locations = (array) $request->input('location_price_group', []);
            $selected_locations = array_values(array_unique(array_filter($selected_locations, function ($locationId) {
                return $locationId !== null && $locationId !== '';
            })));
            $input['location_price_group'] = ! empty($selected_locations)
                ? array_fill_keys($selected_locations, 0)
                : null;

            TypesOfService::where('business_id', $business_id)
                        ->where('id', $id)
                        ->update($input);

            $output = ['success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\TypesOfService  $typesOfService
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('access_types_of_service')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                TypesOfService::where('business_id', $business_id)
                        ->where('id', $id)
                        ->delete();

                $output = ['success' => true,
                    'msg' => __('lang_v1.deleted_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }
}
