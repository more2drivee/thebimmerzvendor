<?php

namespace Modules\Crm\Http\Controllers;

use App\ServicePrediction;
use App\Services\MaintenancePredictionService;
use App\Services\MaintenanceReminderService;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Yajra\DataTables\Facades\DataTables;

class ServicePredictionController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            return $this->getPredictionsDataTable($business_id);
        }

        // Stats
        $stats = ServicePrediction::where('business_id', $business_id)
            ->select(
                DB::raw("SUM(CASE WHEN status = 'on_time' THEN 1 ELSE 0 END) as on_time_count"),
                DB::raw("SUM(CASE WHEN status = 'due' THEN 1 ELSE 0 END) as due_count"),
                DB::raw("SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count"),
                DB::raw("SUM(CASE WHEN status = 'overdue' AND overdue_months >= 3 THEN 1 ELSE 0 END) as high_risk_count"),
                DB::raw("COUNT(*) as total"),
                DB::raw("ROUND(AVG(confidence_score), 0) as avg_confidence")
            )
            ->first();

        // Services used for filter dropdown
        $services_list = DB::table('service_predictions')
            ->join('products', 'products.id', '=', 'service_predictions.service_product_id')
            ->where('service_predictions.business_id', $business_id)
            ->whereNotNull('service_predictions.service_product_id')
            ->select('products.id', 'products.name')
            ->distinct()
            ->pluck('products.name', 'products.id');

        return view('crm::service_prediction.index', compact('stats', 'services_list'));
    }

    protected function getPredictionsDataTable($business_id)
    {
        $predictions = ServicePrediction::where('service_predictions.business_id', $business_id)
            ->whereRaw('service_predictions.id = (
                SELECT id FROM service_predictions as sp2 
                WHERE sp2.contact_id = service_predictions.contact_id 
                AND sp2.business_id = ? 
                ORDER BY sp2.next_expected_date ASC 
                LIMIT 1
            )', [$business_id])
            ->leftJoin('contacts', 'contacts.id', '=', 'service_predictions.contact_id')
            ->leftJoin('contact_device', 'contact_device.id', '=', 'service_predictions.device_id')
            ->leftJoin('categories as brand', 'brand.id', '=', 'contact_device.device_id')
            ->leftJoin('repair_device_models as model', 'model.id', '=', 'contact_device.models_id')
            ->leftJoin('products as svc_product', 'svc_product.id', '=', 'service_predictions.service_product_id')
            ->select([
                'service_predictions.id',
                'contacts.name as customer_name',
                'contacts.mobile as customer_mobile',
                'brand.name as car_brand',
                'model.name as car_model',
                'contact_device.plate_number',
                'svc_product.name as service_name',
                'service_predictions.last_service_date',
                'service_predictions.avg_interval_months',
                'service_predictions.next_expected_date',
                'service_predictions.predicted_quantity',
                'service_predictions.avg_quantity',
                'service_predictions.status',
                'service_predictions.overdue_months',
                'service_predictions.behavior_trend',
                'service_predictions.total_services_count',
                'service_predictions.prediction_source',
                'service_predictions.contact_id',
                'service_predictions.device_id',
            ]);

        // Filters
        if (!empty(request()->get('status_filter'))) {
            $predictions->where('service_predictions.status', request()->get('status_filter'));
        }
        if (!empty(request()->get('service_filter'))) {
            $predictions->where('service_predictions.service_product_id', request()->get('service_filter'));
        }

        return DataTables::of($predictions)
            ->addColumn('car_info', function ($row) {
                $parts = array_filter([$row->car_brand, $row->car_model]);
                $car = implode(' ', $parts);
                if ($row->plate_number) {
                    $car .= " ({$row->plate_number})";
                }
                return $car ?: '-';
            })
            ->addColumn('status_badge', function ($row) {
                $badges = [
                    'on_time' => '<span class="badge bg-success">في الموعد</span>',
                    'due' => '<span class="badge bg-warning">مستحق</span>',
                    'overdue' => '<span class="badge bg-danger">متأخر</span>',
                ];
                $badge = $badges[$row->status] ?? '';
                if ($row->status === 'overdue' && $row->overdue_months > 0) {
                    $badge .= " <small>({$row->overdue_months} شهر)</small>";
                }
                return $badge;
            })
            ->addColumn('trend_badge', function ($row) {
                $trends = [
                    'stable' => '<span class="badge bg-info">مستقر</span>',
                    'increasing' => '<span class="badge bg-warning">يتباعد</span>',
                    'decreasing' => '<span class="badge bg-success">يتقارب</span>',
                ];
                return $trends[$row->behavior_trend] ?? '-';
            })
            ->addColumn('predicted_qty_display', function ($row) {
                return $row->predicted_quantity ? number_format($row->predicted_quantity, 1) : '-';
            })
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">';
                $html .= '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info send_reminder_btn" data-id="' . $row->id . '"><i class="fas fa-bell"></i> تذكير</button>';
                $html .= '<a href="' . action([\Modules\Crm\Http\Controllers\ServicePredictionController::class, 'customerHistory'], ['contact_id' => $row->contact_id, 'device_id' => $row->device_id]) . '" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary"><i class="fas fa-history"></i> السجل</a>';
                $html .= '</div>';
                return $html;
            })
            ->editColumn('last_service_date', function ($row) {
                return $row->last_service_date ? Carbon::parse($row->last_service_date)->format('Y-m-d') : '-';
            })
            ->editColumn('next_expected_date', function ($row) {
                return $row->next_expected_date ? Carbon::parse($row->next_expected_date)->format('Y-m') : '-';
            })
            ->rawColumns(['status_badge', 'trend_badge', 'action'])
            ->make(true);
    }

    public function customerHistory($contact_id, $device_id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $service_filter = request()->get('service_filter');

        // Get service history from transactions
        $historyQuery = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'p.category_id')
            ->leftJoin('repair_job_sheets as rjs', 'rjs.id', '=', 't.repair_job_sheet_id')
            ->leftJoin('bookings as b', 'b.id', '=', 'rjs.booking_id')
            ->where('t.business_id', $business_id)
            ->where('t.contact_id', $contact_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.enable_stock', 0)
            ->where(function ($q) use ($device_id) {
                $q->where('b.device_id', $device_id)
                    ->orWhere('t.repair_device_id', $device_id);
            });

        if (!empty($service_filter)) {
            $historyQuery->where('tsl.product_id', $service_filter);
        }

        $history = $historyQuery->select(
                't.transaction_date',
                't.invoice_no',
                'p.id as product_id',
                'p.name as product_name',
                'cat.name as category_name',
                'tsl.unit_price',
                'tsl.quantity',
                DB::raw('COALESCE(rjs.km, t.repair_device_km) as km'),
                'rjs.job_sheet_no'
            )
            ->orderBy('t.transaction_date', 'desc')
            ->get();

        // Get customer and car info
        $contact = DB::table('contacts')->where('id', $contact_id)->first();
        $device = DB::table('contact_device')
            ->leftJoin('categories as brand', 'brand.id', '=', 'contact_device.device_id')
            ->leftJoin('repair_device_models as model', 'model.id', '=', 'contact_device.models_id')
            ->where('contact_device.id', $device_id)
            ->select('contact_device.*', 'brand.name as brand_name', 'model.name as model_name')
            ->first();

        // Get predictions for this combo with product info
        $predictions = ServicePrediction::where('business_id', $business_id)
            ->where('contact_id', $contact_id)
            ->where('device_id', $device_id)
            ->with(['serviceProduct', 'serviceCategory'])
            ->get();

        // Services used on this car (for filter dropdown)
        $services_used = $history->pluck('product_name', 'product_id')->unique();

        // All cars for this contact (for car switcher)
        $contact_cars = DB::table('contact_device')
            ->leftJoin('categories as brand', 'brand.id', '=', 'contact_device.device_id')
            ->leftJoin('repair_device_models as model', 'model.id', '=', 'contact_device.models_id')
            ->where('contact_device.contact_id', $contact_id)
            ->select('contact_device.id', 'brand.name as brand_name', 'model.name as model_name', 'contact_device.plate_number')
            ->get();

        // Chart data: service frequency over time
        $chart_data = $history->groupBy(function ($item) {
            return Carbon::parse($item->transaction_date)->format('Y-m');
        })->map(function ($group, $month) {
            return [
                'month' => $month,
                'count' => $group->count(),
                'total_qty' => $group->sum('quantity'),
                'total_amount' => $group->sum(function ($item) {
                    return $item->unit_price * $item->quantity;
                }),
            ];
        })->sortKeys()->values();

        return view('crm::service_prediction.history', compact(
            'history', 'contact', 'device', 'predictions',
            'services_used', 'contact_cars', 'chart_data',
            'contact_id', 'device_id', 'service_filter'
        ));
    }

    public function sendReminder(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $prediction = ServicePrediction::where('business_id', $business_id)
                ->findOrFail($request->prediction_id);

            $reminderService = new MaintenanceReminderService();
            $result = $reminderService->processForBusiness($business_id);

            return response()->json([
                'success' => true,
                'msg' => 'تم إرسال التذكير بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'حدث خطأ: ' . $e->getMessage(),
            ]);
        }
    }

    public function recalculate()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm_module'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $service = new MaintenancePredictionService();
            $count = $service->recalculateForBusiness($business_id);

            return response()->json([
                'success' => true,
                'msg' => "تم إعادة حساب {$count} توقع بنجاح",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'حدث خطأ: ' . $e->getMessage(),
            ]);
        }
    }
}
