<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\ServicePrediction;
use App\Services\MaintenancePredictionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ServicePredictionController extends Controller
{
    /**
     * List predictions with filters.
     * GET /connector/api/service-predictions
     *
     * Filters: status, contact_id, device_id, service_category_id, per_page
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $query = ServicePrediction::where('business_id', $business_id)
            ->with([
                'contact:id,name,mobile',
                'device:id,device_id,models_id,plate_number,chassis_number,color',
                'device.deviceCategory:id,name',
                'device.deviceModel:id,name',
                'serviceCategory:id,name',
            ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by contact
        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }

        // Filter by device (car)
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        // Filter by service category
        if ($request->filled('service_category_id')) {
            $query->where('service_category_id', $request->service_category_id);
        }

        // Order by urgency: overdue first, then due, then on_time
        $query->orderByRaw("FIELD(status, 'overdue', 'due', 'on_time')")
              ->orderBy('overdue_months', 'desc')
              ->orderBy('next_expected_date', 'asc');

        $perPage = $request->input('per_page', 25);
        if ($perPage == -1) {
            $predictions = $query->get();
        } else {
            $predictions = $query->paginate($perPage);
        }

        return response()->json([
            'success' => true,
            'data' => $predictions,
        ]);
    }

    /**
     * Get predictions for a specific customer.
     * GET /connector/api/service-predictions/customer/{contact_id}
     */
    public function forCustomer($contactId)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $predictions = ServicePrediction::where('business_id', $business_id)
            ->where('contact_id', $contactId)
            ->with([
                'device:id,device_id,models_id,plate_number,chassis_number,color',
                'device.deviceCategory:id,name',
                'device.deviceModel:id,name',
                'serviceCategory:id,name',
            ])
            ->orderByRaw("FIELD(status, 'overdue', 'due', 'on_time')")
            ->orderBy('next_expected_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $predictions,
        ]);
    }

    /**
     * Dashboard summary stats.
     * GET /connector/api/service-predictions/dashboard-stats
     */
    public function dashboardStats()
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $stats = ServicePrediction::where('business_id', $business_id)
            ->select(
                DB::raw("SUM(CASE WHEN status = 'on_time' THEN 1 ELSE 0 END) as on_time_count"),
                DB::raw("SUM(CASE WHEN status = 'due' THEN 1 ELSE 0 END) as due_count"),
                DB::raw("SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count"),
                DB::raw("SUM(CASE WHEN status = 'overdue' AND overdue_months >= 3 THEN 1 ELSE 0 END) as high_risk_count"),
                DB::raw("COUNT(*) as total")
            )
            ->first();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Manually trigger recalculation for the current business.
     * POST /connector/api/service-predictions/recalculate
     */
    public function recalculate()
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $service = new MaintenancePredictionService();
        $count = $service->recalculateForBusiness($business_id);

        return response()->json([
            'success' => true,
            'message' => "Recalculated {$count} predictions.",
            'count' => $count,
        ]);
    }
}
