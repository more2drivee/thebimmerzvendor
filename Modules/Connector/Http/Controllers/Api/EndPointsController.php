<?php

namespace Modules\Connector\Http\Controllers\Api;


use App\User;
use App\Business;
use App\Utils\Util;
use App\BusinessLocation;

use App\Utils\ModuleUtil;

use App\Utils\ContactUtil;
use App\Utils\ProductUtil;
use App\Utils\QwenService;
use Illuminate\Http\Request;
use App\Utils\CashRegisterUtil;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Modules\Repair\Utils\RepairUtil;
use Modules\Repair\Entities\RepairStatus;
use Modules\Connector\Transformers\FuelResource;
use Modules\Connector\Transformers\StatusResource;

use Modules\Connector\Transformers\ServicesResource;
use Modules\Connector\Transformers\WorkShopResource;
use Modules\Connector\Transformers\ServiceStaffResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;


class EndPointsController extends Controller
{

    protected $repairUtil;
    protected $commonUtil;
    protected $cashRegisterUtil;
    protected $moduleUtil;
    protected $contactUtil;
    protected $productUtil;
    protected $qwenService;


    /**
     * Constructor
     *
     * @param RepairUtil $repairUtil
     * @param Util $commonUtil
     * @param CashRegisterUtil $cashRegisterUtil
     * @param ModuleUtil $moduleUtil
     * @param ContactUtil $contactUtil
     * @param ProductUtil $productUtil
     */ 
    public function __construct(
        RepairUtil $repairUtil,
        Util $commonUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        QwenService $qwenService
    ) {
        $this->repairUtil = $repairUtil;
        $this->commonUtil = $commonUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->qwenService = $qwenService;
    }    

 
    

    /**
     * Get all service staff members.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_all_service_staff()
    {
        try {
            $user = Auth::user();
            $businessId = $user->business_id;

            $techStaffs = User::where('business_id', $businessId)
            ->where('location_id', $user->location_id)
            ->where('allow_login', 0)
            ->where('user_type', "user")
            ->whereNull('deleted_at') // Exclude deleted users

            ->get();
         
            // Format the response
            $techStaffsList = $techStaffs->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'name' => trim(($staff->surname ?? '') . ' ' . ($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')),
                ];
            });

            return response()->json(['data' => $techStaffsList]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch service staff.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Check if a user has permissions for specific locations.
     *
     * @param User $staff
     * @param array $permittedLocations
     * @return bool
     */
    public function hasLocationPermissions(User $staff, array $permittedLocations): bool
    {
        return DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('model_has_permissions.model_type', User::class)
            ->where('model_has_permissions.model_id', $staff->id)
            ->whereIn('permissions.name', array_map(fn($location) => "location.$location", $permittedLocations))
            ->exists();
    }

    /**
     * Get all workshops.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_all_workshops()
    {
        try {
            $user = Auth::user()->location_id;
    
            $query = DB::table('workshops')
            ->where('business_location_id', $user)
            ->where('status', 'available');

         
            $workshops = $query->get();

            return WorkShopResource::collection($workshops);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch workshops.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the default repair checklist.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_all_checklist()
    {
        try {
            $user = Auth::user();
            $businessId = $user->business_id;

            $repairSettings = Business::where('id', $businessId)->value('repair_settings');
            $repairSettings = !empty($repairSettings) ? json_decode($repairSettings, true) : [];

            $checklistString = $repairSettings['default_repair_checklist'] ?? '';

            if (empty($checklistString)) {
                return response()->json(['data' => []]);
            }

            $checklistItems = explode('|', $checklistString);
            $formattedChecklist = array_values(array_filter(array_map('trim', $checklistItems)));

            return response()->json([
                'data' => array_map(fn($item, $index) => ['id' => $index + 1, 'title' => $item], $formattedChecklist, array_keys($formattedChecklist)),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch checklist.', 'message' => $e->getMessage()], 500);
        }
    }


    public function get_all_status()
    {
        try {
            $user = Auth::user();
            $businessId = $user->business_id;
            $status_list = [];
            $note_list = [];

            $repairStatuses = RepairStatus::where('business_id', $businessId)
                ->orderBy('sort_order', 'asc')
                ->get();

            foreach ($repairStatuses as $status) {
                if ($status->status_category == 'status') {
                    $status_list[] = $status;
                } elseif ($status->status_category == 'note') {
                    $note_list[] = $status;
                }
            }

            return response()->json([
                'status_list' => StatusResource::collection($status_list),
                'note_list' => StatusResource::collection($note_list),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch repair statuses.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Assign a technician to a workshop for today.
     * Requires an attendance record today.
     */
    public function assignTechnicianToWorkshop(Request $request)
    {
        $data = $request->validate([
            'workshop_id' => 'required|integer',
            'user_id' => 'required|integer',
            'notes' => 'nullable|string'
        ]);

        if (!Schema::hasTable('workshop_technician_attendance')) {
            return response()->json(['message' => 'Assignment table not migrated yet'], 409);
        }

        $today = Carbon::today();
        $attendance = DB::table('essentials_attendances')
            ->where('user_id', $data['user_id'])
            ->whereDate('clock_in_time', $today)
            ->orderBy('clock_in_time', 'desc')
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'Technician has no attendance today'], 422);
        }

        // Check if technician is already assigned to this workshop today
        $existingAssignment = DB::table('workshop_technician_attendance')
            ->where('user_id', $data['user_id'])
            ->where('workshop_id', $data['workshop_id'])
            ->whereDate('joined_at', $today)
            ->whereNull('left_at')
            ->first();

        if ($existingAssignment) {
            return response()->json(['message' => 'Technician is already assigned to this workshop today'], 422);
        }

        $id = DB::table('workshop_technician_attendance')->insertGetId([
            'workshop_id' => $data['workshop_id'],
            'attendance_id' => $attendance->id,
            'user_id' => $data['user_id'],
            'joined_at' => Carbon::now(),
            'notes' => $data['notes'] ?? null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $row = DB::table('workshop_technician_attendance as wta')
            ->leftJoin('workshops as w', 'w.id', '=', 'wta.workshop_id')
            ->select('wta.*', 'w.name as workshop_name')
            ->where('wta.id', $id)
            ->first();

        return response()->json(['message' => 'Technician assigned', 'data' => $row], 201);
    }

    /**
     * List technician assignments for a given date (default today).
     */
    public function technicianAssignments(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'date' => 'nullable|date'
        ]);

        if (!Schema::hasTable('workshop_technician_attendance')) {
            return response()->json(['data' => []]);
        }

        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();
        $rows = DB::table('workshop_technician_attendance as wta')
            ->leftJoin('workshops as w', 'w.id', '=', 'wta.workshop_id')
            ->where('wta.user_id', $request->user_id)
            ->whereDate('wta.joined_at', $date)
            ->select('wta.*', 'w.name as workshop_name')
            ->orderBy('wta.joined_at')
            ->get();

        return response()->json(['data' => $rows]);
    }

    /**
     * Get fuel status options.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fuel_status()
    {
        try {
            $fuelStatuses = DB::table('fuel_status')->get();
            return FuelResource::collection($fuelStatuses);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch fuel statuses.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get types of services.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function types_of_services()
    {
        try {
            // Exclude inspection services
            $typesOfServices = DB::table('types_of_services')
                ->where(function ($q) {
                    $q->whereNull('is_inspection_service')
                      ->orWhere('is_inspection_service', 0);
                })
                ->get();
            return ServicesResource::collection($typesOfServices);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch types of services.', 'message' => $e->getMessage()], 500);
        }
    }
    
}
