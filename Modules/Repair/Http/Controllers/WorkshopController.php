<?php

namespace Modules\Repair\Http\Controllers;


use App\BusinessLocation;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Repair\Utils\RepairUtil;
use Yajra\DataTables\Facades\DataTables;



    class WorkshopController extends Controller
{

        /**
     * All Utils instance.
     */
    protected $moduleUtil;

    protected $repairUtil;

    /**
     * Constructor
     */
    public function __construct(ModuleUtil $moduleUtil, RepairUtil $repairUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->repairUtil = $repairUtil;
    }

    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        // Check for permissions
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module'))) {
            abort(403, 'Unauthorized action.');
        }
    
        // Select the necessary columns including 'id', 'name' (from workshops), 'status', and 'location' (from business_locations)
        $workshops = DB::table('workshops')
            ->leftJoin('business_locations AS location', 'workshops.business_location_id', '=', 'location.id')
            ->where('workshops.business_id', $business_id) // Explicitly reference 'workshops.business_id'
            ->select('workshops.id', 'workshops.name', 'location.name as location', 'workshops.status') // Added 'name'
            ->get();
    
        if ($request->ajax()) {
            return Datatables::of($workshops)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                                <button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                    '.__('messages.action').'
                                    <span class="sr-only">' . __('messages.action') . '</span>
                                </button>';
    
                    $html .= '<ul class="dropdown-menu dropdown-menu-left" role="menu">
                                <li>
                                    <a data-href="'.action([\Modules\Repair\Http\Controllers\WorkshopController::class, 'edit'], [$row->id]).'" class="cursor-pointer edit_workshop">
                                        <i class="fa fa-edit"></i> '.__('messages.edit').'
                                    </a>
                                </li>
                                <li>
                                    <a data-href="'.action([\Modules\Repair\Http\Controllers\WorkshopController::class, 'destroy'], [$row->id]).'" id="delete_a_workshop" class="cursor-pointer">
                                        <i class="fas fa-trash"></i> '.__('messages.delete').'
                                    </a>
                                </li>
                            </ul>';
    
                    $html .= '</div>';
                    return $html;
                })
                ->editColumn('status', function ($row) {
                    return $row->status;  // You can modify this if you need custom formatting
                })
                ->editColumn('location', function ($row) {
                    return $row->location;  // You can modify this if you need custom formatting
                })
                ->editColumn('name', function ($row) {
                    return $row->name;  // Ensure the name of the workshop is returned
                })
                ->removeColumn('id')  // Optionally remove 'id' from datatable, it's used for actions
                ->rawColumns(['action', 'status'])  // Ensure raw HTML for action and status columns
                ->make(true);
        }
    }
    



    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        $request->validate([
            'name' => 'required|string|max:255',
            'business_location_id' => 'required|integer',
            'status' => 'required|string|max:50',
        ]);

        DB::table('workshops')->insert([
            'name' => $request->name,
            'business_location_id' => $request->business_location_id,
            'status' => $request->status,
            'business_id' => $business_id
        ]);

        return response()->json(['message' => 'Workshop created successfully'], 201);
    }

    public function show($id)
    {
        $workshop = DB::select("SELECT * FROM workshops WHERE id = ?", [$id]);

        if (empty($workshop)) {
            return response()->json(['message' => 'Workshop not found'], 404);
        }

        return response()->json($workshop[0]);
    }
    public function create()
    {
     
        // Return a view or response as needed
        return view('repair::workshop.create');  // Assuming you have a create view
    }
    

    /**
     * Show the form for editing the specified workshop.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        
        // Fetch the workshop details
        $workshop = DB::table('workshops')
            ->where('id', $id)
            ->where('business_id', $business_id)
            ->first();

        if (empty($workshop)) {
            return response()->json(['message' => 'Workshop not found'], 404);
        }

        return response()->json($workshop);
    }

    public function update(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');
        
        $workshop = DB::table('workshops')
            ->where('id', $id)
            ->where('business_id', $business_id)
            ->first();

        if (empty($workshop)) {
            return response()->json(['message' => 'Workshop not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'business_location_id' => 'required|integer',
            'status' => 'required|string|max:50',
        ]);

        DB::table('workshops')
            ->where('id', $id)
            ->update([
                'name' => $request->name,
                'business_location_id' => $request->business_location_id,
                'status' => $request->status
            ]);

        return response()->json(['message' => 'Workshop updated successfully']);
    }

    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');
        
        $workshop = DB::table('workshops')
            ->where('id', $id)
            ->where('business_id', $business_id)
            ->first();

        if (empty($workshop)) {
            return response()->json(['message' => 'Workshop not found'], 404);
        }

        DB::table('workshops')->where('id', $id)->delete();

        return response()->json(['message' => 'Workshop deleted successfully']);
    }

}
