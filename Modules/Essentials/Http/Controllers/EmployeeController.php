<?php

namespace Modules\Essentials\Http\Controllers;

use App\Media;
use App\Transaction;
use App\User;
use App\Utils\ModuleUtil;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsEmployeeBonus;
use Modules\Essentials\Entities\EssentialsEmployeeDeduction;
use Modules\Essentials\Entities\EssentialsEmployeeWarning;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsSalaryAdvance;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    private function _checkAccess()
    {
        $business_id = request()->session()->get('user.business_id');
        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')
            )
        ) {
            abort(403, 'Unauthorized action.');
        }
        return $business_id;
    }

    /**
     * Main index page with tabs
     */
    public function index(Request $request)
    {
        $business_id = $this->_checkAccess();

        $employees = User::forDropdown($business_id, false);
        $departments = \App\Category::forDropdown($business_id, 'hrm_department');
        $designations = \App\Category::forDropdown($business_id, 'hrm_designation');
        $locations = \App\BusinessLocation::forDropdown($business_id, true, false, true, true);

        $leave_types = \Modules\Essentials\Entities\EssentialsLeaveType::where(
            'business_id',
            $business_id,
        )->pluck('leave_type', 'id');

        $shifts = \Modules\Essentials\Entities\Shift::where('business_id', $business_id)->pluck(
            'name',
            'id',
        );

        return view('essentials::employees.index')->with(
            compact(
                'employees',
                'departments',
                'designations',
                'locations',
                'leave_types',
                'shifts',
            ),
        );
    }

    /**
     * AJAX employee search for select2 dropdowns (paginated)
     */
    public function searchEmployees(Request $request)
    {
        $business_id = $this->_checkAccess();
        $q = $request->input('q', '');
        $page = (int) $request->input('page', 1);
        $perPage = 20;

        $query = User::where('users.business_id', $business_id)
            ->where('users.user_type', 'user')
            ->select(
                'users.id',
                DB::raw(
                    "CONCAT(COALESCE(users.surname,''), ' ', COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,'')) as text",
                ),
            );

        if (!empty($request->input('location_id'))) {
            $query->where('users.location_id', $request->input('location_id'));
        }

        if (!empty($q)) {
            $query->whereRaw(
                "CONCAT(COALESCE(users.surname,''), ' ', COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,'')) LIKE ?",
                ["%{$q}%"],
            );
        }

        $total = $query->count();
        $results = $query
            ->orderBy('users.first_name')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($u) => ['id' => $u->id, 'text' => trim($u->text)]);

        return response()->json([
            'results' => $results,
            'more' => $page * $perPage < $total,
        ]);
    }

    /**
     * Standalone pages
     */
    public function warningsIndex()
    {
        $business_id = $this->_checkAccess();
        $employees = User::forDropdown($business_id, false);
        return view('essentials::warnings.index')->with(compact('employees'));
    }

    public function bonusesIndex()
    {
        $business_id = $this->_checkAccess();
        $employees = User::forDropdown($business_id, false);
        return view('essentials::bonuses.index')->with(compact('employees'));
    }

    public function deductionsIndex()
    {
        $business_id = $this->_checkAccess();
        $employees = User::forDropdown($business_id, false);
        return view('essentials::deductions.index')->with(compact('employees'));
    }

    public function paymentHistoryIndex()
    {
        $business_id = $this->_checkAccess();
        $employees = User::forDropdown($business_id, false);
        return view('essentials::payment_history.index')->with(compact('employees'));
    }

    public function advancesIndex()
    {
        $business_id = $this->_checkAccess();
        $employees = User::forDropdown($business_id, false);
        return view('essentials::advances.index')->with(compact('employees'));
    }

    /**
     * Tab 1: Employees DataTable (AJAX)
     */
    public function getEmployeesData(Request $request)
    {
        $business_id = $this->_checkAccess();

        $query = User::where('users.business_id', $business_id)
            ->where('users.user_type', 'user')
            ->leftJoin('categories as dept', 'dept.id', '=', 'users.essentials_department_id')
            ->leftJoin('categories as desig', 'desig.id', '=', 'users.essentials_designation_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'users.location_id')
            ->select([
                'users.id',
                DB::raw(
                    "CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as full_name",
                ),
                'users.email',
                'users.contact_number',
                'users.id_proof_name',
                'users.essentials_salary',
                'users.essentials_pay_period',
                'dept.name as department_name',
                'desig.name as designation_name',
                'bl.name as location_name',
            ]);

        if (!empty($request->department_id)) {
            $query->where('users.essentials_department_id', $request->department_id);
        }
        if (!empty($request->designation_id)) {
            $query->where('users.essentials_designation_id', $request->designation_id);
        }
        if (!empty($request->location_id)) {
            $query->where('users.location_id', $request->location_id);
        }

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $html =
                    '<div class="btn-group">
                    <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        ' .
                    __('messages.actions') .
                    ' <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                $html .=
                    '<li><a href="#" class="view-employee-modal" data-user-id="' .
                    $row->id .
                    '"><i class="fas fa-eye"></i> ' .
                    __('messages.view') .
                    '</a></li>';
                $html .=
                    '<li><a href="' .
                    action(
                        [\Modules\Essentials\Http\Controllers\EmployeeController::class, 'edit'],
                        [$row->id],
                    ) .
                    '"><i class="glyphicon glyphicon-edit"></i> ' .
                    __('messages.edit') .
                    '</a></li>';
                $html .= '<li class="divider"></li>';
                $html .=
                    '<li><a href="#" class="emp-action-modal" data-user-id="' .
                    $row->id .
                    '" data-user-name="' .
                    e($row->full_name) .
                    '" data-action-type="attendance"><i class="fas fa-check-square"></i> ' .
                    __('essentials::lang.attendance') .
                    '</a></li>';
                $html .=
                    '<li><a href="#" class="emp-action-modal" data-user-id="' .
                    $row->id .
                    '" data-user-name="' .
                    e($row->full_name) .
                    '" data-action-type="absence"><i class="fas fa-user-times"></i> ' .
                    __('essentials::lang.absent') .
                    '</a></li>';
                $html .=
                    '<li><a href="#" class="emp-action-modal" data-user-id="' .
                    $row->id .
                    '" data-user-name="' .
                    e($row->full_name) .
                    '" data-action-type="leave"><i class="fas fa-calendar-minus"></i> ' .
                    __('essentials::lang.leave') .
                    '</a></li>';
                $html .= '<li class="divider"></li>';
                $html .=
                    '<li><a href="#" class="emp-action-modal" data-user-id="' .
                    $row->id .
                    '" data-user-name="' .
                    e($row->full_name) .
                    '" data-action-type="warnings"><i class="fas fa-exclamation-triangle"></i> ' .
                    __('essentials::lang.warnings') .
                    '</a></li>';
                $html .=
                    '<li><a href="#" class="emp-action-modal" data-user-id="' .
                    $row->id .
                    '" data-user-name="' .
                    e($row->full_name) .
                    '" data-action-type="bonuses"><i class="fas fa-gift"></i> ' .
                    __('essentials::lang.bonuses') .
                    '</a></li>';
                $html .=
                    '<li><a href="#" class="emp-action-modal" data-user-id="' .
                    $row->id .
                    '" data-user-name="' .
                    e($row->full_name) .
                    '" data-action-type="deductions"><i class="fas fa-minus-circle"></i> ' .
                    __('essentials::lang.deductions') .
                    '</a></li>';
                $html .=
                    '<li><a href="#" class="emp-action-modal" data-user-id="' .
                    $row->id .
                    '" data-user-name="' .
                    e($row->full_name) .
                    '" data-action-type="payment"><i class="fas fa-money-bill"></i> ' .
                    __('essentials::lang.payment_history') .
                    '</a></li>';
                $html .=
                    '<li><a href="#" class="emp-action-modal" data-user-id="' .
                    $row->id .
                    '" data-user-name="' .
                    e($row->full_name) .
                    '" data-action-type="advances"><i class="fas fa-hand-holding-usd"></i> ' .
                    __('essentials::lang.salary_advance') .
                    '</a></li>';
                $html .= '</ul></div>';
                return $html;
            })
            ->addColumn('image', function ($row) {
                $img_src = $row->image_url;
                return '<img src="' .
                    $img_src .
                    '" alt="' .
                    e($row->full_name) .
                    '" class="tw-rounded-full" style="width:35px;height:35px;object-fit:cover;">';
            })
            ->editColumn('essentials_salary', function ($row) {
                return $row->essentials_salary
                    ? '<span class="display_currency" data-currency_symbol="true">' .
                            $row->essentials_salary .
                            '</span>'
                    : '-';
            })
            ->rawColumns(['action', 'image', 'essentials_salary'])
            ->filterColumn('full_name', function ($query, $keyword) {
                $query->whereRaw(
                    "CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) like ?",
                    ["%{$keyword}%"],
                );
            })
            ->make(true);
    }

    /**
     * Tab 2: Attendance History DataTable (AJAX)
     */
    public function getAttendanceData(Request $request)
    {
        $business_id = $this->_checkAccess();

        $query = EssentialsAttendance::where('essentials_attendances.business_id', $business_id)
            ->join('users as u', 'u.id', '=', 'essentials_attendances.user_id')
            ->leftJoin(
                'essentials_shifts as es',
                'es.id',
                '=',
                'essentials_attendances.essentials_shift_id',
            )
            ->select([
                'essentials_attendances.id',
                'essentials_attendances.clock_in_time',
                'essentials_attendances.clock_out_time',
                'essentials_attendances.ip_address',
                DB::raw('DATE(clock_in_time) as date'),
                DB::raw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as employee_name",
                ),
                'es.name as shift_name',
            ])
            ->groupBy('essentials_attendances.id');

        if (!empty($request->input('employee_id'))) {
            $query->where('essentials_attendances.user_id', $request->input('employee_id'));
        }
        if (!empty($request->input('department_id'))) {
            $query->where('u.essentials_department_id', $request->input('department_id'));
        }
        if (!empty($request->input('designation_id'))) {
            $query->where('u.essentials_designation_id', $request->input('designation_id'));
        }
        if (!empty($request->input('location_id'))) {
            $query->where('u.location_id', $request->input('location_id'));
        }
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $query
                ->whereDate('clock_in_time', '>=', $request->start_date)
                ->whereDate('clock_in_time', '<=', $request->end_date);
        }

        return DataTables::of($query)
            ->editColumn('date', '{{@format_date($date)}}')
            ->addColumn('clock_in', function ($row) {
                return $row->clock_in_time
                    ? \Carbon::parse($row->clock_in_time)->format('H:i')
                    : '-';
            })
            ->addColumn('clock_out', function ($row) {
                return $row->clock_out_time
                    ? \Carbon::parse($row->clock_out_time)->format('H:i')
                    : '-';
            })
            ->addColumn('work_duration', function ($row) {
                if (!$row->clock_in_time) {
                    return '-';
                }
                $in = \Carbon::parse($row->clock_in_time);
                $out = $row->clock_out_time ? \Carbon::parse($row->clock_out_time) : \Carbon::now();
                return $in->diffForHumans($out, true, true, 2);
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->whereRaw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?",
                    ["%{$keyword}%"],
                );
            })
            ->rawColumns([])
            ->make(true);
    }

    /**
     * Tab 3: Absence History DataTable (AJAX)
     * Shows days where employee had a shift but no attendance record
     */
    public function getAbsenceData(Request $request)
    {
        $business_id = $this->_checkAccess();

        $query = EssentialsLeave::where('essentials_leaves.business_id', $business_id)
            ->join('users as u', 'u.id', '=', 'essentials_leaves.user_id')
            ->leftJoin(
                'essentials_leave_types as lt',
                'lt.id',
                '=',
                'essentials_leaves.essentials_leave_type_id',
            )
            ->where('essentials_leaves.status', '!=', 'approved')
            ->select([
                'essentials_leaves.id',
                'essentials_leaves.start_date',
                'essentials_leaves.end_date',
                'essentials_leaves.status',
                'essentials_leaves.reason',
                DB::raw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as employee_name",
                ),
                'lt.leave_type',
            ]);

        if (!empty($request->input('employee_id'))) {
            $query->where('essentials_leaves.user_id', $request->input('employee_id'));
        }
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $query
                ->whereDate('essentials_leaves.start_date', '>=', $request->start_date)
                ->whereDate('essentials_leaves.end_date', '<=', $request->end_date);
        }

        return DataTables::of($query)
            ->editColumn('start_date', '{{@format_date($start_date)}}')
            ->editColumn('end_date', '{{@format_date($end_date)}}')
            ->editColumn('status', function ($row) {
                $colors = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'cancelled' => 'danger',
                    'rejected' => 'danger',
                ];
                $color = $colors[$row->status] ?? 'default';
                return '<span class="label label-' .
                    $color .
                    '">' .
                    __('essentials::lang.' . $row->status) .
                    '</span>';
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->whereRaw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?",
                    ["%{$keyword}%"],
                );
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    /**
     * Tab 4: Leave History DataTable (AJAX)
     */
    public function getLeaveData(Request $request)
    {
        $business_id = $this->_checkAccess();

        $query = EssentialsLeave::where('essentials_leaves.business_id', $business_id)
            ->join('users as u', 'u.id', '=', 'essentials_leaves.user_id')
            ->leftJoin(
                'essentials_leave_types as lt',
                'lt.id',
                '=',
                'essentials_leaves.essentials_leave_type_id',
            )
            ->select([
                'essentials_leaves.id',
                'essentials_leaves.ref_no',
                'essentials_leaves.start_date',
                'essentials_leaves.end_date',
                'essentials_leaves.status',
                'essentials_leaves.reason',
                DB::raw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as employee_name",
                ),
                'lt.leave_type',
            ]);

        if (!empty($request->input('employee_id'))) {
            $query->where('essentials_leaves.user_id', $request->input('employee_id'));
        }
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $query
                ->whereDate('essentials_leaves.start_date', '>=', $request->start_date)
                ->whereDate('essentials_leaves.end_date', '<=', $request->end_date);
        }

        return DataTables::of($query)
            ->editColumn('start_date', '{{@format_date($start_date)}}')
            ->editColumn('end_date', '{{@format_date($end_date)}}')
            ->editColumn('status', function ($row) {
                $colors = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'cancelled' => 'danger',
                    'rejected' => 'danger',
                ];
                $color = $colors[$row->status] ?? 'default';
                return '<span class="label label-' .
                    $color .
                    '">' .
                    __('essentials::lang.' . $row->status) .
                    '</span>';
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->whereRaw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?",
                    ["%{$keyword}%"],
                );
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    /**
     * Tab 5: Warnings DataTable (AJAX)
     */
    public function getWarningsData(Request $request)
    {
        $business_id = $this->_checkAccess();

        $query = EssentialsEmployeeWarning::where(
            'essentials_employee_warnings.business_id',
            $business_id,
        )
            ->join('users as u', 'u.id', '=', 'essentials_employee_warnings.user_id')
            ->join('users as ib', 'ib.id', '=', 'essentials_employee_warnings.issued_by')
            ->select([
                'essentials_employee_warnings.id',
                'essentials_employee_warnings.warning_type',
                'essentials_employee_warnings.warning_note',
                'essentials_employee_warnings.warning_date',
                DB::raw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as employee_name",
                ),
                DB::raw(
                    "CONCAT(COALESCE(ib.surname, ''), ' ', COALESCE(ib.first_name, ''), ' ', COALESCE(ib.last_name, '')) as issued_by_name",
                ),
            ]);

        if (!empty($request->input('employee_id'))) {
            $query->where('essentials_employee_warnings.user_id', $request->input('employee_id'));
        }

        return DataTables::of($query)
            ->editColumn('warning_date', '{{@format_date($warning_date)}}')
            ->editColumn('warning_type', function ($row) {
                $colors = ['verbal' => 'info', 'written' => 'warning', 'final' => 'danger'];
                $color = $colors[$row->warning_type] ?? 'default';
                return '<span class="label label-' .
                    $color .
                    '">' .
                    __('essentials::lang.warning_' . $row->warning_type) .
                    '</span>';
            })
            ->addColumn('action', function ($row) {
                return '<button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error delete-warning" data-href="' .
                    action(
                        [
                            \Modules\Essentials\Http\Controllers\EmployeeController::class,
                            'deleteWarning',
                        ],
                        [$row->id],
                    ) .
                    '"><i class="fa fa-trash"></i></button>';
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->whereRaw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?",
                    ["%{$keyword}%"],
                );
            })
            ->rawColumns(['warning_type', 'action'])
            ->make(true);
    }

    public function storeWarning(Request $request)
    {
        $business_id = $this->_checkAccess();

        try {
            EssentialsEmployeeWarning::create([
                'business_id' => $business_id,
                'user_id' => $request->input('user_id'),
                'issued_by' => auth()->user()->id,
                'warning_type' => $request->input('warning_type'),
                'warning_note' => $request->input('warning_note'),
                'warning_date' => $request->input('warning_date')
                    ? $this->moduleUtil->uf_date($request->input('warning_date'))
                    : now()->format('Y-m-d'),
            ]);

            return ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function deleteWarning($id)
    {
        $business_id = $this->_checkAccess();

        try {
            EssentialsEmployeeWarning::where('business_id', $business_id)
                ->where('id', $id)
                ->delete();
            return ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    /**
     * Tab 6: Bonuses DataTable (AJAX)
     */
    public function getBonusesData(Request $request)
    {
        $business_id = $this->_checkAccess();

        $query = EssentialsEmployeeBonus::where(
            'essentials_employee_bonuses.business_id',
            $business_id,
        )
            ->join('users as u', 'u.id', '=', 'essentials_employee_bonuses.user_id')
            ->select([
                'essentials_employee_bonuses.*',
                DB::raw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as employee_name",
                ),
            ]);

        if (!empty($request->input('employee_id'))) {
            $query->where('essentials_employee_bonuses.user_id', $request->input('employee_id'));
        }

        return DataTables::of($query)
            ->editColumn('amount', function ($row) {
                $display =
                    '<span class="display_currency" data-currency_symbol="false">' .
                    $row->amount .
                    '</span>';
                if ($row->amount_type == 'percent') {
                    $display .= ' %';
                }
                return $display;
            })
            ->editColumn('start_date', '{{@format_date($start_date)}}')
            ->editColumn('end_date', '{{$end_date ? @format_date($end_date) : "-"}}')
            ->editColumn('status', function ($row) {
                $colors = ['active' => 'success', 'applied' => 'info', 'cancelled' => 'danger'];
                $color = $colors[$row->status] ?? 'default';
                return '<span class="label label-' .
                    $color .
                    '">' .
                    __('essentials::lang.' . $row->status) .
                    '</span>';
            })
            ->editColumn('apply_on', function ($row) {
                return __('essentials::lang.' . $row->apply_on);
            })
            ->addColumn('action', function ($row) {
                $html = '';
                if ($row->status == 'active') {
                    $html .=
                        '<button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-warning cancel-bonus" data-href="' .
                        action(
                            [
                                \Modules\Essentials\Http\Controllers\EmployeeController::class,
                                'cancelBonus',
                            ],
                            [$row->id],
                        ) .
                        '"><i class="fa fa-ban"></i> ' .
                        __('essentials::lang.cancel') .
                        '</button> ';
                }
                $html .=
                    '<button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error delete-bonus" data-href="' .
                    action(
                        [
                            \Modules\Essentials\Http\Controllers\EmployeeController::class,
                            'deleteBonus',
                        ],
                        [$row->id],
                    ) .
                    '"><i class="fa fa-trash"></i></button>';
                return $html;
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->whereRaw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?",
                    ["%{$keyword}%"],
                );
            })
            ->rawColumns(['amount', 'status', 'action'])
            ->make(true);
    }

    public function storeBonus(Request $request)
    {
        $business_id = $this->_checkAccess();

        try {
            EssentialsEmployeeBonus::create([
                'business_id' => $business_id,
                'user_id' => $request->input('user_id'),
                'description' => $request->input('description'),
                'amount' => $this->moduleUtil->num_uf($request->input('amount')),
                'amount_type' => $request->input('amount_type', 'fixed'),
                'start_date' => $request->input('start_date')
                    ? $this->moduleUtil->uf_date($request->input('start_date'))
                    : null,
                'end_date' => $request->input('end_date')
                    ? $this->moduleUtil->uf_date($request->input('end_date'))
                    : null,
                'apply_on' => $request->input('apply_on', 'next_payroll'),
                'note' => $request->input('note'),
                'created_by' => auth()->user()->id,
            ]);

            return ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function cancelBonus($id)
    {
        $business_id = $this->_checkAccess();
        try {
            EssentialsEmployeeBonus::where('business_id', $business_id)
                ->where('id', $id)
                ->update(['status' => 'cancelled']);
            return ['success' => true, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function deleteBonus($id)
    {
        $business_id = $this->_checkAccess();
        try {
            EssentialsEmployeeBonus::where('business_id', $business_id)->where('id', $id)->delete();
            return ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    /**
     * Tab 7: Deductions DataTable (AJAX)
     */
    public function getDeductionsData(Request $request)
    {
        $business_id = $this->_checkAccess();

        $query = EssentialsEmployeeDeduction::where(
            'essentials_employee_deductions.business_id',
            $business_id,
        )
            ->join('users as u', 'u.id', '=', 'essentials_employee_deductions.user_id')
            ->select([
                'essentials_employee_deductions.*',
                DB::raw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as employee_name",
                ),
            ]);

        if (!empty($request->input('employee_id'))) {
            $query->where('essentials_employee_deductions.user_id', $request->input('employee_id'));
        }

        return DataTables::of($query)
            ->editColumn('amount', function ($row) {
                $display =
                    '<span class="display_currency" data-currency_symbol="false">' .
                    $row->amount .
                    '</span>';
                if ($row->amount_type == 'percent') {
                    $display .= ' %';
                }
                return $display;
            })
            ->editColumn('start_date', '{{@format_date($start_date)}}')
            ->editColumn('end_date', '{{$end_date ? @format_date($end_date) : "-"}}')
            ->editColumn('status', function ($row) {
                $colors = ['active' => 'success', 'applied' => 'info', 'cancelled' => 'danger'];
                $color = $colors[$row->status] ?? 'default';
                return '<span class="label label-' .
                    $color .
                    '">' .
                    __('essentials::lang.' . $row->status) .
                    '</span>';
            })
            ->editColumn('apply_on', function ($row) {
                return __('essentials::lang.' . $row->apply_on);
            })
            ->addColumn('action', function ($row) {
                $html = '';
                if ($row->status == 'active') {
                    $html .=
                        '<button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-warning cancel-deduction" data-href="' .
                        action(
                            [
                                \Modules\Essentials\Http\Controllers\EmployeeController::class,
                                'cancelDeduction',
                            ],
                            [$row->id],
                        ) .
                        '"><i class="fa fa-ban"></i> ' .
                        __('essentials::lang.cancel') .
                        '</button> ';
                }
                $html .=
                    '<button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error delete-deduction" data-href="' .
                    action(
                        [
                            \Modules\Essentials\Http\Controllers\EmployeeController::class,
                            'deleteDeduction',
                        ],
                        [$row->id],
                    ) .
                    '"><i class="fa fa-trash"></i></button>';
                return $html;
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->whereRaw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?",
                    ["%{$keyword}%"],
                );
            })
            ->rawColumns(['amount', 'status', 'action'])
            ->make(true);
    }

    public function storeDeduction(Request $request)
    {
        $business_id = $this->_checkAccess();

        try {
            EssentialsEmployeeDeduction::create([
                'business_id' => $business_id,
                'user_id' => $request->input('user_id'),
                'description' => $request->input('description'),
                'amount' => $this->moduleUtil->num_uf($request->input('amount')),
                'amount_type' => $request->input('amount_type', 'fixed'),
                'start_date' => $request->input('start_date')
                    ? $this->moduleUtil->uf_date($request->input('start_date'))
                    : null,
                'end_date' => $request->input('end_date')
                    ? $this->moduleUtil->uf_date($request->input('end_date'))
                    : null,
                'apply_on' => $request->input('apply_on', 'next_payroll'),
                'note' => $request->input('note'),
                'created_by' => auth()->user()->id,
            ]);

            return ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function cancelDeduction($id)
    {
        $business_id = $this->_checkAccess();
        try {
            EssentialsEmployeeDeduction::where('business_id', $business_id)
                ->where('id', $id)
                ->update(['status' => 'cancelled']);
            return ['success' => true, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function deleteDeduction($id)
    {
        $business_id = $this->_checkAccess();
        try {
            EssentialsEmployeeDeduction::where('business_id', $business_id)
                ->where('id', $id)
                ->delete();
            return ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    /**
     * Tab 8: Payment History DataTable (AJAX) - Payroll transactions
     */
    public function getPaymentHistoryData(Request $request)
    {
        $business_id = $this->_checkAccess();

        $query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'payroll')
            ->join('users as u', 'u.id', '=', 'transactions.expense_for')
            ->select([
                'transactions.id',
                'transactions.ref_no',
                'transactions.transaction_date',
                'transactions.final_total',
                'transactions.payment_status',
                DB::raw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as employee_name",
                ),
            ]);

        if (!empty($request->input('employee_id'))) {
            $query->where('transactions.expense_for', $request->input('employee_id'));
        }
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $query
                ->whereDate('transactions.transaction_date', '>=', $request->start_date)
                ->whereDate('transactions.transaction_date', '<=', $request->end_date);
        }

        return DataTables::of($query)
            ->editColumn('transaction_date', function ($row) {
                return \Carbon::parse($row->transaction_date)->format('F Y');
            })
            ->editColumn(
                'final_total',
                '<span class="display_currency" data-currency_symbol="true">{{$final_total}}</span>',
            )
            ->editColumn('payment_status', function ($row) {
                $colors = ['paid' => 'success', 'due' => 'danger', 'partial' => 'warning'];
                $color = $colors[$row->payment_status] ?? 'default';
                return '<span class="label label-' .
                    $color .
                    '">' .
                    __('lang_v1.' . $row->payment_status) .
                    '</span>';
            })
            ->addColumn('action', function ($row) {
                return '<a href="#" data-href="' .
                    action(
                        [\Modules\Essentials\Http\Controllers\PayrollController::class, 'show'],
                        [$row->id],
                    ) .
                    '" data-container=".view_modal" class="btn-modal tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info"><i class="fa fa-eye"></i> ' .
                    __('messages.view') .
                    '</a>';
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->whereRaw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?",
                    ["%{$keyword}%"],
                );
            })
            ->rawColumns(['final_total', 'payment_status', 'action'])
            ->make(true);
    }

    /**
     * Tab 9: Salary Advances (سلفة) DataTable (AJAX)
     */
    public function getAdvancesData(Request $request)
    {
        $business_id = $this->_checkAccess();

        $query = EssentialsSalaryAdvance::where(
            'essentials_salary_advances.business_id',
            $business_id,
        )
            ->join('users as u', 'u.id', '=', 'essentials_salary_advances.user_id')
            ->leftJoin('users as ab', 'ab.id', '=', 'essentials_salary_advances.approved_by')
            ->select([
                'essentials_salary_advances.*',
                DB::raw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as employee_name",
                ),
                DB::raw(
                    "CONCAT(COALESCE(ab.surname, ''), ' ', COALESCE(ab.first_name, ''), ' ', COALESCE(ab.last_name, '')) as approved_by_name",
                ),
            ]);

        if (!empty($request->input('employee_id'))) {
            $query->where('essentials_salary_advances.user_id', $request->input('employee_id'));
        }

        return DataTables::of($query)
            ->editColumn('amount', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">' .
                    $row->amount .
                    '</span>';
            })
            ->editColumn('request_date', '{{@format_date($request_date)}}')
            ->editColumn('status', function ($row) {
                $colors = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'deducted' => 'info',
                ];
                $color = $colors[$row->status] ?? 'default';
                return '<span class="label label-' .
                    $color .
                    '">' .
                    __('essentials::lang.' . $row->status) .
                    '</span>';
            })
            ->addColumn('action', function ($row) {
                $html = '';
                if ($row->status == 'pending') {
                    $html .=
                        '<button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-success approve-advance" data-id="' .
                        $row->id .
                        '"><i class="fa fa-check"></i> ' .
                        __('essentials::lang.approved') .
                        '</button> ';
                    $html .=
                        '<button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-danger reject-advance" data-id="' .
                        $row->id .
                        '"><i class="fa fa-times"></i> ' .
                        __('essentials::lang.rejected') .
                        '</button> ';
                }
                $html .=
                    '<button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-error delete-advance" data-href="' .
                    action(
                        [
                            \Modules\Essentials\Http\Controllers\EmployeeController::class,
                            'deleteAdvance',
                        ],
                        [$row->id],
                    ) .
                    '"><i class="fa fa-trash"></i></button>';
                return $html;
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->whereRaw(
                    "CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?",
                    ["%{$keyword}%"],
                );
            })
            ->rawColumns(['amount', 'status', 'action'])
            ->make(true);
    }

    public function storeAdvance(Request $request)
    {
        $business_id = $this->_checkAccess();

        try {
            $amount = $this->moduleUtil->num_uf($request->input('amount'));
            $user_id = $request->input('user_id');

            // Validate: advance cannot exceed employee's monthly salary
            $employee = User::where('business_id', $business_id)->find($user_id);
            if (!$employee) {
                return ['success' => false, 'msg' => __('messages.something_went_wrong')];
            }

            $monthly_salary = (float) ($employee->essentials_salary ?? 0);
            if ($monthly_salary > 0 && $amount > $monthly_salary) {
                return [
                    'success' => false,
                    'msg' => __('essentials::lang.advance_exceeds_salary', [
                        'salary' => number_format($monthly_salary, 2),
                    ]),
                ];
            }

            EssentialsSalaryAdvance::create([
                'business_id' => $business_id,
                'user_id' => $user_id,
                'amount' => $amount,
                'reason' => $request->input('reason'),
                'request_date' => $request->input('request_date')
                    ? $this->moduleUtil->uf_date($request->input('request_date'))
                    : now()->format('Y-m-d'),
                'deduct_from_payroll' => $request->input('deduct_from_payroll'),
                'note' => $request->input('note'),
                'created_by' => auth()->user()->id,
            ]);

            return ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function updateAdvanceStatus(Request $request, $id)
    {
        $business_id = $this->_checkAccess();

        try {
            $advance = EssentialsSalaryAdvance::where('business_id', $business_id)->findOrFail($id);
            $new_status = $request->input('status');

            if ($new_status == 'approved') {
                // Re-validate amount does not exceed monthly salary
                $employee = User::where('business_id', $business_id)->find($advance->user_id);
                $monthly_salary = (float) ($employee->essentials_salary ?? 0);
                if ($monthly_salary > 0 && (float) $advance->amount > $monthly_salary) {
                    return [
                        'success' => false,
                        'msg' => __('essentials::lang.advance_exceeds_salary', [
                            'salary' => number_format($monthly_salary, 2),
                        ]),
                    ];
                }

                $advance->status = 'approved';
                $advance->approved_by = auth()->user()->id;
                $advance->approved_date = now()->format('Y-m-d');
                $advance->save();

                // Auto-create a FINAL payroll for the advance payout amount;
                // the advance stays 'approved' so the regular monthly payroll
                // will pick it up as a deduction automatically.
                $this->_createPayrollForAdvance($advance, $business_id);
            } else {
                $advance->status = $new_status;
                $advance->save();
            }

            return ['success' => true, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage(),
            );
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    /**
     * Auto-create a FINAL payroll transaction when a salary advance is approved.
     *
     * The advance itself becomes a stand-alone final payroll whose total equals
     * the advance amount (representing the cash the employee receives immediately).
     *
     * The advance stays in 'approved' status so that the regular monthly payroll
     * creation will automatically pick it up as a deduction line via
     * getSalaryAdvancesForPayroll() and mark it 'deducted' when saved.
     */
    private function _createPayrollForAdvance($advance, $business_id)
    {
        $employee = User::find($advance->user_id);
        if (!$employee) {
            return;
        }

        // Determine the target month (YYYY-MM format)
        $deduct_month = $advance->deduct_from_payroll ?: now()->format('Y-m');
        $transaction_date = $deduct_month . '-01';

        DB::beginTransaction();

        try {
            $module_util = $this->moduleUtil;

            $salary = (float) ($employee->essentials_salary ?? 0);
            $pay_period = $employee->essentials_pay_period ?? 'month';

            // Duration calculation (matches what PayrollController::create() does)
            $duration = 1;
            $duration_unit = 'month';
            if ($pay_period == 'day') {
                $duration = \Carbon\Carbon::parse($transaction_date)->daysInMonth;
                $duration_unit = 'day';
            } elseif ($pay_period == 'week') {
                $duration = 4;
                $duration_unit = 'week';
            }

            // The advance payout IS the payroll total — no deductions on this record.
            // The deduction from the employee's regular monthly salary will appear
            // automatically when the regular payroll is generated for the same month.
            $advance_amount = (float) $advance->amount;

            $allowances_json = json_encode([
                'allowance_names' => [],
                'allowance_amounts' => [],
                'allowance_types' => [],
                'allowance_percents' => [],
            ]);
            $deductions_json = json_encode([
                'deduction_names' => [],
                'deduction_amounts' => [],
                'deduction_types' => [],
                'deduction_percents' => [],
            ]);

            // Generate reference number
            $ref_count = $module_util->setAndGetReferenceCount('payroll');
            $settings = session()->get('business.essentials_settings');
            $settings = !empty($settings) ? json_decode($settings, true) : [];
            $prefix = !empty($settings['payroll_ref_no_prefix'])
                ? $settings['payroll_ref_no_prefix']
                : '';
            $ref_no = $module_util->generateReferenceNumber('payroll', $ref_count, null, $prefix);

            // Create a dedicated payroll group for this advance payout
            $group_label =
                __('essentials::lang.salary_advance') .
                ' – ' .
                $employee->user_full_name .
                ' – ' .
                $deduct_month;

            $payroll_group = \Modules\Essentials\Entities\PayrollGroup::create([
                'business_id' => $business_id,
                'name' => $group_label,
                'status' => 'final', // immediately final — advance was paid
                'gross_total' => $advance_amount,
                'location_id' => $employee->location_id,
                'created_by' => auth()->user()->id,
            ]);

            $transaction = Transaction::create([
                'business_id' => $business_id,
                'type' => 'payroll',
                'status' => 'final', // final — matches the advance approval
                'payment_status' => 'due', // payment is still outstanding
                'transaction_date' => $transaction_date,
                'final_total' => $advance_amount, // full advance amount
                'total_before_tax' => $advance_amount,
                'expense_for' => $advance->user_id,
                'essentials_salary' => $salary,
                'essentials_pay_period' => $pay_period,
                'essentials_duration' => $duration,
                'essentials_duration_unit' => $duration_unit,
                'essentials_amount_per_unit_duration' => $advance_amount, // per-cycle amount = advance
                'essentials_allowances' => $allowances_json,
                'essentials_deductions' => $deductions_json,
                'ref_no' => $ref_no,
                'created_by' => auth()->user()->id,
                // Marker so PayrollController::create() can exclude this from
                // "already paid regular payroll" totals — the advance deduction
                // line in the regular payroll already accounts for it.
                'staff_note' => '__advance_payout__',
            ]);

            $payroll_group->payrollGroupTransactions()->sync([$transaction->id]);

            // NOTE: We do NOT mark the advance as 'deducted' here.
            // It stays 'approved' so that PayrollController::create() can include
            // it as a deduction line in the regular monthly payroll for this employee,
            // and PayrollController::store() will mark it 'deducted' at that point.

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('Auto payroll for advance failed: ' . $e->getMessage());
        }
    }

    public function deleteAdvance($id)
    {
        $business_id = $this->_checkAccess();
        try {
            EssentialsSalaryAdvance::where('business_id', $business_id)->where('id', $id)->delete();
            return ['success' => true, 'msg' => __('lang_v1.deleted_success')];
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function storeAttendance(Request $request)
    {
        $business_id = $this->_checkAccess();

        try {
            $clock_in = $request->input('clock_in_date') . ' ' . $request->input('clock_in_time');
            $clock_out_raw = null;
            if (
                !empty($request->input('clock_out_date')) &&
                !empty($request->input('clock_out_time'))
            ) {
                $clock_out_raw =
                    $request->input('clock_out_date') . ' ' . $request->input('clock_out_time');
            }

            EssentialsAttendance::create([
                'business_id' => $business_id,
                'user_id' => $request->input('user_id'),
                'clock_in_time' => $this->moduleUtil->uf_date($clock_in, true),
                'clock_out_time' => $clock_out_raw
                    ? $this->moduleUtil->uf_date($clock_out_raw, true)
                    : null,
                'ip_address' => $request->ip(),
                'essentials_shift_id' => $request->input('essentials_shift_id') ?: null,
            ]);

            return ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function storeLeave(Request $request)
    {
        $business_id = $this->_checkAccess();

        try {
            \Modules\Essentials\Entities\EssentialsLeave::create([
                'business_id' => $business_id,
                'user_id' => $request->input('user_id'),
                'essentials_leave_type_id' => $request->input('essentials_leave_type_id'),
                'start_date' => $this->moduleUtil->uf_date($request->input('start_date')),
                'end_date' => $this->moduleUtil->uf_date($request->input('end_date')),
                'reason' => $request->input('reason'),
                'status' => $request->input('status', 'pending'),
                'ref_no' => 'LEV-' . time(),
            ]);

            return ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    /**
     * Attendance Calendar View Data (AJAX)
     * Returns employees with attendance keyed by date for grid/calendar view
     */
    public function getAttendanceCalendar(Request $request)
    {
        $business_id = $this->_checkAccess();

        $month = $request->input('month', now()->format('Y-m'));
        $employee_id = $request->input('employee_id');
        $department_id = $request->input('department_id');
        $designation_id = $request->input('designation_id');
        $location_id = $request->input('location_id');

        try {
            $date = \Carbon::parse($month . '-01');
        } catch (\Exception $e) {
            $date = now()->startOfMonth();
        }

        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $query = User::where('users.business_id', $business_id)
            ->where('users.user_type', 'user')
            ->leftJoin('categories as dept', 'dept.id', '=', 'users.essentials_department_id')
            ->leftJoin('categories as desig', 'desig.id', '=', 'users.essentials_designation_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'users.location_id')
            ->select([
                'users.id',
                DB::raw(
                    "CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as full_name",
                ),
                'dept.name as department_name',
                'desig.name as designation_name',
                'bl.name as location_name',
            ]);

        if (!empty($employee_id)) {
            $query->where('users.id', $employee_id);
        }
        if (!empty($department_id)) {
            $query->where('users.essentials_department_id', $department_id);
        }
        if (!empty($designation_id)) {
            $query->where('users.essentials_designation_id', $designation_id);
        }
        if (!empty($location_id)) {
            $query->where('users.location_id', $location_id);
        }

        $users = $query->get();

        $attendances = EssentialsAttendance::where('business_id', $business_id)
            ->whereDate('clock_in_time', '>=', $start)
            ->whereDate('clock_in_time', '<=', $end)
            ->when(!empty($employee_id), function ($q) use ($employee_id) {
                $q->where('user_id', $employee_id);
            })
            ->select(['user_id', 'clock_in_time', 'clock_out_time'])
            ->get()
            ->groupBy(function ($row) {
                return $row->user_id . '_' . \Carbon::parse($row->clock_in_time)->format('Y-m-d');
            });

        $days = [];
        $cur = $start->copy();
        while ($cur->lte($end)) {
            $days[] = $cur->format('Y-m-d');
            $cur->addDay();
        }

        $rows = [];
        foreach ($users as $user) {
            $dayData = [];
            foreach ($days as $day) {
                $key = $user->id . '_' . $day;
                $rec = isset($attendances[$key]) ? $attendances[$key]->first() : null;
                if ($rec) {
                    $in = \Carbon::parse($rec->clock_in_time);
                    $out = $rec->clock_out_time ? \Carbon::parse($rec->clock_out_time) : null;
                    $hours = $out ? round($in->diffInMinutes($out) / 60, 1) : null;
                    $dayData[$day] = [
                        'in' => $in->format('H:i'),
                        'out' => $out ? $out->format('H:i') : '-',
                        'hours' => $hours !== null ? $hours : '-',
                    ];
                } else {
                    $dayData[$day] = null;
                }
            }
            $rows[] = [
                'id' => $user->id,
                'name' => trim($user->full_name),
                'department' => $user->department_name ?? '-',
                'designation' => $user->designation_name ?? '-',
                'location' => $user->location_name ?? '-',
                'days' => $dayData,
            ];
        }

        return response()->json([
            'success' => true,
            'days' => $days,
            'rows' => $rows,
        ]);
    }

    /**
     * Leaderboard: Employee of the month
     */
    public function leaderboard(Request $request)
    {
        $business_id = $this->_checkAccess();

        $month = $request->input('month', now()->format('Y-m'));
        try {
            $date = \Carbon::parse($month . '-01');
        } catch (\Exception $e) {
            $date = now()->startOfMonth();
        }

        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $users = User::where('users.business_id', $business_id)
            ->where('users.user_type', 'user')
            ->leftJoin('categories as dept', 'dept.id', '=', 'users.essentials_department_id')
            ->leftJoin('categories as desig', 'desig.id', '=', 'users.essentials_designation_id')
            ->select([
                'users.id',
                DB::raw(
                    "CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as full_name",
                ),
                'dept.name as department_name',
                'desig.name as designation_name',
            ])
            ->get();

        $attendances = EssentialsAttendance::where('business_id', $business_id)
            ->whereDate('clock_in_time', '>=', $start)
            ->whereDate('clock_in_time', '<=', $end)
            ->select(['user_id', 'clock_in_time', 'clock_out_time', 'essentials_shift_id'])
            ->get()
            ->groupBy('user_id');

        $leaderboard = [];
        foreach ($users as $user) {
            $records = $attendances->get($user->id, collect());
            $total_hours = 0;
            $days_present = 0;
            $perfect_days = 0;
            $overtime_hours = 0;
            $standard_day_hours = 8;

            foreach ($records as $rec) {
                if (!$rec->clock_in_time) {
                    continue;
                }
                $in = \Carbon::parse($rec->clock_in_time);
                $out = $rec->clock_out_time ? \Carbon::parse($rec->clock_out_time) : null;
                if (!$out) {
                    continue;
                }

                $hours = round($in->diffInMinutes($out) / 60, 2);
                $total_hours += $hours;
                $days_present++;

                if ($hours >= $standard_day_hours) {
                    $perfect_days++;
                }
                if ($hours > $standard_day_hours) {
                    $overtime_hours += round($hours - $standard_day_hours, 2);
                }
            }

            $score = $total_hours * 1 + $perfect_days * 5 + $overtime_hours * 2;

            $leaderboard[] = [
                'id' => $user->id,
                'full_name' => trim($user->full_name),
                'department_name' => $user->department_name ?? '-',
                'designation_name' => $user->designation_name ?? '-',
                'image_url' => $user->image_url,
                'total_hours' => round($total_hours, 1),
                'days_present' => $days_present,
                'perfect_days' => $perfect_days,
                'overtime_hours' => round($overtime_hours, 1),
                'score' => round($score, 1),
            ];
        }

        usort($leaderboard, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $selected_month = $month;

        return view('essentials::employees.leaderboard')->with(
            compact('leaderboard', 'selected_month'),
        );
    }

    /**
     * Create employee form
     */
    public function create()
    {
        $business_id = $this->_checkAccess();

        $departments = \App\Category::forDropdown($business_id, 'hrm_department');
        $designations = \App\Category::forDropdown($business_id, 'hrm_designation');
        $locations = \App\BusinessLocation::forDropdown($business_id, true, false, true, true);

        return view('essentials::employees.create')->with(
            compact('departments', 'designations', 'locations'),
        );
    }

    /**
     * Store new employee (non-login user)
     */
    public function store(Request $request)
    {
        $business_id = $this->_checkAccess();

        try {
            $input = $request->only([
                'surname',
                'first_name',
                'last_name',
                'email',
                'dob',
                'gender',
                'marital_status',
                'blood_group',
                'contact_number',
                'alt_number',
                'family_number',
                'fb_link',
                'twitter_link',
                'social_media_1',
                'social_media_2',
                'id_proof_name',
                'id_proof_number',
                'fingerprint_id',
                'permanent_address',
                'current_address',
                'guardian_name',
                'custom_field_1',
                'custom_field_2',
                'custom_field_3',
                'custom_field_4',
                'essentials_department_id',
                'essentials_designation_id',
                'location_id',
                'essentials_salary',
                'essentials_pay_period',
            ]);

            $input['business_id'] = $business_id;
            $input['user_type'] = 'user';
            $input['username'] = 'emp_' . time() . rand(100, 999);
            $input['password'] = bcrypt(\Illuminate\Support\Str::random(20));
            $input['allow_login'] = 0;

            if (!empty($input['dob'])) {
                $input['dob'] = $this->moduleUtil->uf_date($input['dob']);
            }
            if (!empty($input['essentials_salary'])) {
                $input['essentials_salary'] = $this->moduleUtil->num_uf(
                    $input['essentials_salary'],
                );
            }

            if ($request->has('bank_details')) {
                $input['bank_details'] = json_encode($request->input('bank_details'));
            }

            $user = User::create($input);

            if ($request->hasFile('user_image')) {
                Media::uploadMedia($business_id, $user, $request, 'user_image', true);
            }

            $output = ['success' => true, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()
            ->action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Edit employee
     */
    public function edit($id)
    {
        $business_id = $this->_checkAccess();

        $user = User::where('business_id', $business_id)
            ->with(['media', 'contactAccess'])
            ->findOrFail($id);

        $employee_documents = \App\Media::where('business_id', $business_id)
            ->where('model_id', $id)
            ->where('model_type', \App\User::class)
            ->where('model_media_type', 'employee_document')
            ->orderBy('created_at', 'desc')
            ->get();

        $bank_details = !empty($user->bank_details) ? json_decode($user->bank_details, true) : null;

        $departments = \App\Category::forDropdown($business_id, 'hrm_department');
        $designations = \App\Category::forDropdown($business_id, 'hrm_designation');
        $locations = \App\BusinessLocation::forDropdown($business_id, true, false, true, true);

        // Get contact access for selected contacts dropdown
        $contact_access = [];
        if ($user->selected_contacts && $user->contactAccess->count() > 0) {
            foreach ($user->contactAccess as $contact) {
                $contact_access[$contact->id] = $contact->name;
            }
        }

        return view('essentials::employees.edit')->with(
            compact(
                'user',
                'employee_documents',
                'bank_details',
                'departments',
                'designations',
                'locations',
                'contact_access',
            ),
        );
    }

    /**
     * Update employee
     */
    public function update(Request $request, $id)
    {
        $business_id = $this->_checkAccess();

        try {
            $user = User::where('business_id', $business_id)->findOrFail($id);

            $input = $request->only([
                'surname',
                'first_name',
                'last_name',
                'email',
                'dob',
                'gender',
                'marital_status',
                'blood_group',
                'contact_number',
                'alt_number',
                'family_number',
                'fb_link',
                'twitter_link',
                'social_media_1',
                'social_media_2',
                'permanent_address',
                'current_address',
                'guardian_name',
                'custom_field_1',
                'custom_field_2',
                'custom_field_3',
                'custom_field_4',
                'id_proof_name',
                'id_proof_number',
                'fingerprint_id',
                'essentials_department_id',
                'essentials_designation_id',
                'location_id',
                'essentials_salary',
                'essentials_pay_period',
                'cmmsn_percent',
                'max_sales_discount_percent',
                'selected_contacts',
            ]);

            // Handle status
            $input['status'] = $request->has('is_active') ? 'active' : 'inactive';

            // Handle service staff pin
            if ($request->has('is_enable_service_staff_pin')) {
                $input['is_enable_service_staff_pin'] = 1;
                if (!empty($request->input('service_staff_pin'))) {
                    $input['service_staff_pin'] = $request->input('service_staff_pin');
                }
            } else {
                $input['is_enable_service_staff_pin'] = 0;
                $input['service_staff_pin'] = null;
            }

            if (!empty($input['dob'])) {
                $input['dob'] = $this->moduleUtil->uf_date($input['dob']);
            }
            if (!empty($input['essentials_salary'])) {
                $input['essentials_salary'] = $this->moduleUtil->num_uf(
                    $input['essentials_salary'],
                );
            }

            if ($request->has('bank_details')) {
                $input['bank_details'] = json_encode($request->input('bank_details'));
            }

            $user->update($input);

            if ($request->hasFile('user_image')) {
                Media::uploadMedia($business_id, $user, $request, 'user_image', true);
            }

            // Handle allowed contacts
            if ($request->has('selected_contacts') && $request->input('selected_contacts') == 1) {
                $user->contactAccess()->sync($request->input('selected_contact_ids', []));
            } else {
                $user->contactAccess()->detach();
            }

            $output = [
                'success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()
            ->action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Show employee details in modal
     */
    public function show($id)
    {
        $business_id = $this->_checkAccess();

        $employee = User::where('users.business_id', $business_id)
            ->where('users.id', $id)
            ->leftJoin('categories as dept', 'dept.id', '=', 'users.essentials_department_id')
            ->leftJoin('categories as desig', 'desig.id', '=', 'users.essentials_designation_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'users.location_id')
            ->select([
                'users.*',
                'dept.name as department_name',
                'desig.name as designation_name',
                'bl.name as location_name',
            ])
            ->first();

        if (!$employee) {
            return '<div class="alert alert-danger">Employee not found.</div>';
        }

        return view('essentials::employees.partials.view_modal', compact('employee'));
    }

    /**
     * Get employee attendance data for modal
     */
    public function getEmployeeAttendanceModal($id)
    {
        $business_id = $this->_checkAccess();

        $attendances = \Modules\Essentials\Entities\EssentialsAttendance::where(
            'business_id',
            $business_id,
        )
            ->where('user_id', $id)
            ->orderBy('clock_in_time', 'desc')
            ->limit(50)
            ->get();

        return view(
            'essentials::employees.partials.attendance_modal',
            compact('attendances', 'id'),
        );
    }

    /**
     * Get employee absence data for modal
     */
    public function getEmployeeAbsenceModal($id)
    {
        $business_id = $this->_checkAccess();

        $absences = \Modules\Essentials\Entities\EssentialsLeave::where('business_id', $business_id)
            ->where('user_id', $id)
            ->where('status', 'approved')
            ->orderBy('start_date', 'desc')
            ->limit(50)
            ->get();

        return view('essentials::employees.partials.absence_modal', compact('absences', 'id'));
    }

    /**
     * Get employee leave data for modal
     */
    public function getEmployeeLeaveModal($id)
    {
        $business_id = $this->_checkAccess();

        $leaves = \Modules\Essentials\Entities\EssentialsLeave::where('business_id', $business_id)
            ->where('user_id', $id)
            ->orderBy('start_date', 'desc')
            ->limit(50)
            ->get();

        return view('essentials::employees.partials.leave_modal', compact('leaves', 'id'));
    }

    /**
     * Get employee warnings data for modal
     */
    public function getEmployeeWarningsModal($id)
    {
        $business_id = $this->_checkAccess();

        $warnings = \Modules\Essentials\Entities\EssentialsEmployeeWarning::where(
            'business_id',
            $business_id,
        )
            ->where('user_id', $id)
            ->orderBy('warning_date', 'desc')
            ->get();

        return view('essentials::employees.partials.warnings_modal', compact('warnings', 'id'));
    }

    /**
     * Get employee bonuses data for modal
     */
    public function getEmployeeBonusesModal($id)
    {
        $business_id = $this->_checkAccess();

        $bonuses = \Modules\Essentials\Entities\EssentialsEmployeeBonus::where(
            'business_id',
            $business_id,
        )
            ->where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('essentials::employees.partials.bonuses_modal', compact('bonuses', 'id'));
    }

    /**
     * Get employee deductions data for modal
     */
    public function getEmployeeDeductionsModal($id)
    {
        $business_id = $this->_checkAccess();

        $deductions = \Modules\Essentials\Entities\EssentialsEmployeeDeduction::where(
            'business_id',
            $business_id,
        )
            ->where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('essentials::employees.partials.deductions_modal', compact('deductions', 'id'));
    }

    /**
     * Get employee payment history for modal
     */
    public function getEmployeePaymentModal($id)
    {
        $business_id = $this->_checkAccess();

        $payments = \App\Transaction::where('business_id', $business_id)
            ->where('type', 'payroll')
            ->where('expense_for', $id)
            ->orderBy('transaction_date', 'desc')
            ->limit(50)
            ->get();

        return view('essentials::employees.partials.payment_modal', compact('payments', 'id'));
    }

    /**
     * Get employee advances data for modal
     */
    public function getEmployeeAdvancesModal($id)
    {
        $business_id = $this->_checkAccess();

        $advances = \Modules\Essentials\Entities\EssentialsSalaryAdvance::where(
            'business_id',
            $business_id,
        )
            ->where('user_id', $id)
            ->orderBy('request_date', 'desc')
            ->get();

        return view('essentials::employees.partials.advances_modal', compact('advances', 'id'));
    }

    /**
     * Store employee documents (multi-file with labels)
     */
    public function storeDocument(Request $request, $id)
    {
        $business_id = $this->_checkAccess();

        try {
            $user = User::where('business_id', $business_id)->findOrFail($id);

            if (!$request->hasFile('doc_files')) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }

            $files = $request->file('doc_files');
            $labels = $request->input('doc_labels', []);
            $saved = [];

            foreach ($files as $index => $file) {
                $new_file_name = time() . '_' . mt_rand() . '_' . $file->getClientOriginalName();
                if (
                    \Illuminate\Support\Facades\Storage::disk('public')->putFileAs(
                        'employee_docs',
                        $file,
                        $new_file_name,
                    )
                ) {
                    $label = !empty($labels[$index])
                        ? $labels[$index]
                        : $file->getClientOriginalName();
                    $media = \App\Media::create([
                        'business_id' => $business_id,
                        'file_name' => 'employee_docs/' . $new_file_name,
                        'description' => $label,
                        'uploaded_by' => auth()->id(),
                        'model_id' => $user->id,
                        'model_type' => \App\User::class,
                        'model_media_type' => 'employee_document',
                    ]);
                    $saved[] = [
                        'id' => $media->id,
                        'label' => $label,
                        'file_name' => $media->file_name,
                        'display_url' => $media->display_url,
                        'is_image' => in_array(strtolower($file->getClientOriginalExtension()), [
                            'jpg',
                            'jpeg',
                            'png',
                            'gif',
                            'bmp',
                            'webp',
                        ]),
                    ];
                }
            }

            return response()->json(['success' => true, 'docs' => $saved]);
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    /**
     * Delete a single employee document by media id
     */
    public function deleteDocument($employee_id, $doc_id)
    {
        $business_id = $this->_checkAccess();

        try {
            $media = \App\Media::where('business_id', $business_id)
                ->where('model_id', $employee_id)
                ->where('model_type', \App\User::class)
                ->where('model_media_type', 'employee_document')
                ->findOrFail($doc_id);

            if (strpos($media->file_name, '/') !== false) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($media->file_name);
            } else {
                $path = public_path('uploads/media/' . $media->file_name);
                if (file_exists($path)) {
                    unlink($path);
                }
            }

            $media->delete();

            return response()->json(['success' => true, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }
}
