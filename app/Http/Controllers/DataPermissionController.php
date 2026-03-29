<?php

namespace App\Http\Controllers;

use App\DataPermission;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class DataPermissionController extends Controller
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
            $data_permissions = DataPermission::select(['id', 'permission_key', 'permission_name', 'is_active', 'created_at']);

            return Datatables::of($data_permissions)
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
                            <li><a href="{{action(\'App\Http\Controllers\DataPermissionController@edit\', [$id])}}" class="edit_data_permission_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a></li>
                            <li><a href="{{action(\'App\Http\Controllers\DataPermissionController@show\', [$id])}}" class="view_data_permission_button"><i class="fa fa-eye"></i> @lang("messages.view")</a></li>
                            <li><a data-href="{{action(\'App\Http\Controllers\DataPermissionController@destroy\', [$id])}}" class="delete_data_permission_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</a></li>
                        </ul>
                    </div>'
                )
                ->editColumn('is_active', '@if($is_active) <span class="label bg-green">@lang("messages.active")</span> @else <span class="label bg-gray">@lang("messages.inactive")</span> @endif')
                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }

        return view('data_permissions.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('data_permissions.create');
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
            'permission_key' => 'required|unique:data_permissions,permission_key',
            'permission_name' => 'required',
        ]);

        try {
            $input = $request->only(['permission_key', 'permission_name']);
            $input['is_active'] = !empty($request->input('is_active')) ? 1 : 0;

            $data_permission = DataPermission::create($input);

            $output = ['success' => true,
                'data' => $data_permission,
                'msg' => __('messages.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('data-permissions')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data_permission = DataPermission::findOrFail($id);
        
        return view('data_permissions.show', compact('data_permission'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data_permission = DataPermission::findOrFail($id);
        
        return view('data_permissions.edit', compact('data_permission'));
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
            'permission_key' => 'required|unique:data_permissions,permission_key,'.$id,
            'permission_name' => 'required',
        ]);

        try {
            $input = $request->only(['permission_key', 'permission_name']);
            $input['is_active'] = !empty($request->input('is_active')) ? 1 : 0;

            $data_permission = DataPermission::findOrFail($id);
            $data_permission->update($input);

            $output = ['success' => true,
                'msg' => __('messages.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('data-permissions')->with('status', $output);
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
            DataPermission::destroy($id);

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
