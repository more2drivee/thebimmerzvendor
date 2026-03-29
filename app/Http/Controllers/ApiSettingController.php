<?php

namespace App\Http\Controllers;

use App\ApiSetting;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class ApiSettingController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ModuleUtil $moduleUtil
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $api_settings = ApiSetting::select(['id', 'token', 'domain', 'base_url', 'created_at']);

            return Datatables::of($api_settings)
                ->addColumn(
                    'action',
                    '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">
                            <li><a href="{{action(\'App\Http\Controllers\ApiSettingController@edit\', [$id])}}" class="edit_api_setting_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a></li>
                            <li><a href="{{action(\'App\Http\Controllers\ApiSettingController@show\', [$id])}}" class="view_api_setting_button"><i class="fa fa-eye"></i> @lang("messages.view")</a></li>
                            <li><a href="#" data-href="{{action(\'App\Http\Controllers\ApiSettingController@destroy\', [$id])}}" class="delete_api_setting_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</a></li>
                        </ul>
                    </div>'
                )
                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('api_settings.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('api_settings.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'domain' => 'required',
            'base_url' => 'required',
        ]);

        try {
            $input = $request->only(['token', 'domain', 'base_url']);

            $api_setting = ApiSetting::create($input);

            $output = ['success' => true,
                'data' => $api_setting,
                'msg' => __('messages.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('api-settings')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $api_setting = ApiSetting::findOrFail($id);
        
        return view('api_settings.show', compact('api_setting'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $api_setting = ApiSetting::findOrFail($id);
        
        return view('api_settings.edit', compact('api_setting'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'token' => 'required',
            'domain' => 'required',
            'base_url' => 'required',
        ]);

        try {
            $input = $request->only(['token', 'domain', 'base_url']);

            $api_setting = ApiSetting::findOrFail($id);
            $api_setting->update($input);

            $output = ['success' => true,
                'msg' => __('messages.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('api-settings')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            ApiSetting::destroy($id);

            $output = ['success' => true,
                'msg' => __('messages.deleted_success'),
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

