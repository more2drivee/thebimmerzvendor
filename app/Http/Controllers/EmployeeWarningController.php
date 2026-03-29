<?php

namespace App\Http\Controllers;

use App\User;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmployeeWarningController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if ($request->ajax()) {
            $warnings = DB::table('employee_warnings')
                ->join('users as employee', 'employee.id', '=', 'employee_warnings.user_id')
                ->join('users as issuer', 'issuer.id', '=', 'employee_warnings.issued_by')
                ->where('employee_warnings.business_id', $business_id)
                ->select([
                    'employee_warnings.id',
                    DB::raw("CONCAT(COALESCE(employee.surname, ''), ' ', COALESCE(employee.first_name, ''), ' ', COALESCE(employee.last_name, '')) as employee_name"),
                    'employee_warnings.warning_type',
                    'employee_warnings.reason',
                    'employee_warnings.warning_date',
                    DB::raw("CONCAT(COALESCE(issuer.surname, ''), ' ', COALESCE(issuer.first_name, ''), ' ', COALESCE(issuer.last_name, '')) as issued_by_name"),
                    'employee_warnings.created_at',
                ]);

            if (!empty($request->input('employee_id'))) {
                $warnings->where('employee_warnings.user_id', $request->input('employee_id'));
            }

            return DataTables::of($warnings)
                ->editColumn('warning_type', function ($row) {
                    $types = [
                        'verbal' => '<span class="label label-warning">' . __('essentials::lang.warning_verbal') . '</span>',
                        'written' => '<span class="label label-info">' . __('essentials::lang.warning_written') . '</span>',
                        'final' => '<span class="label label-danger">' . __('essentials::lang.warning_final') . '</span>',
                    ];
                    return $types[$row->warning_type] ?? $row->warning_type;
                })
                ->editColumn('warning_date', function ($row) {
                    return \Carbon::parse($row->warning_date)->format('Y-m-d');
                })
                ->rawColumns(['warning_type'])
                ->make(true);
        }

        $employees = User::forDropdown($business_id, false);

        return view('employee_warnings.index')->with(compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        try {
            $warning_data = [
                'business_id' => $business_id,
                'user_id' => $request->input('user_id'),
                'issued_by' => request()->session()->get('user.id'),
                'warning_type' => $request->input('warning_type'),
                'reason' => $request->input('reason'),
                'warning_date' => $request->input('warning_date') ?? date('Y-m-d'),
            ];

            DB::table('employee_warnings')->insert($warning_data);

            $output = ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Get warnings for a specific employee (API endpoint)
     */
    public function getEmployeeWarnings($employee_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $warnings = DB::table('employee_warnings')
            ->where('business_id', $business_id)
            ->where('user_id', $employee_id)
            ->orderBy('warning_date', 'desc')
            ->get();

        return response()->json($warnings);
    }

    /**
     * Show the form for creating a new warning.
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $employees = User::forDropdown($business_id, false);

        return view('employee_warnings.create')->with(compact('employees'));
    }
}
