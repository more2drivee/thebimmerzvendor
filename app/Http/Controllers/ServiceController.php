<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\ProductVariation;
use App\VariationLocationDetails;
use App\Unit;
use App\Utils\ProductUtil;
use App\Utils\BusinessUtil;
use Yajra\DataTables\Facades\DataTables;
use Modules\Repair\Entities\Workshop;
use Modules\Repair\Entities\FlatRateService;
use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\BusinessLocation;

class ServiceController extends Controller
{
    protected $productUtil;
    protected $businessUtil;
    protected $moduleUtil;

    public function __construct(ProductUtil $productUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $workshops = Workshop::where('business_id', $business_id);
        if (!auth()->user()->hasRole('Admin')) {
            $permitted_locations = auth()->user()->permitted_locations($business_id);
            if ($permitted_locations != 'all') {
                $workshops->whereIn('business_location_id', (array) $permitted_locations);
            }
        }
        $workshops = $workshops->get();
        
        $flat_rates = FlatRateService::where('business_id', $business_id);
        if (!auth()->user()->hasRole('Admin')) {
            $permitted_locations = auth()->user()->permitted_locations($business_id);
            if ($permitted_locations != 'all') {
                $flat_rates->whereIn('business_location_id', (array) $permitted_locations);
            }
        }
        $flat_rates = $flat_rates->get();

        $business_locations = BusinessLocation::forDropdown($business_id, true, false, true, true);

        if (request()->ajax()) {
            return $this->getServicesDataTable();
        }

        return view('services.index', compact('workshops', 'flat_rates', 'business_locations'));
    }

    /**
     * Services datatable (supports multi-location filter)
     */
    public function getServicesDataTable()
    {
        $business_id = request()->session()->get('user.business_id');

        $current_user = auth()->user();
        $isAdmin = $current_user->hasRole('Admin#' . $business_id) || $current_user->can('superadmin');
        $permitted_locations = $current_user->permitted_locations($business_id);

        $services = Product::leftJoin('product_workshop as pw', 'pw.product_id', '=', 'products.id')
            ->leftJoin('workshops as w', 'w.id', '=', 'pw.workshop_id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
            ->leftJoin('variations', 'variations.product_id', '=', 'products.id')
            ->leftJoin('product_locations as pl', 'pl.product_id', '=', 'products.id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'pl.location_id')
            ->where('products.business_id', $business_id)
            ->where('products.enable_stock', 0)
            ->where('products.virtual_product', 0)
            ->where('products.is_client_flagged', 0)
            ->where('products.is_client_flagged', 0)
            ->where('products.is_labour', 0)
            ->where(function($query) use ($isAdmin, $permitted_locations) {
                if (!$isAdmin && $permitted_locations != 'all') {
                    $query->whereIn('pl.location_id', $permitted_locations)
                          ->orWhereNull('pl.location_id');
                }
            })
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'variations.default_sell_price as selling_price',
                'products.serviceHours',
                'products.product_custom_field1',
                'products.product_custom_field2',
                'brands.name as brand_name',
                'units.short_name as unit_name',
                'c1.name as category_name',
                'products.created_at',
                \DB::raw("GROUP_CONCAT(DISTINCT w.name SEPARATOR ', ') as workshop_names"),
                \DB::raw("GROUP_CONCAT(DISTINCT w.id SEPARATOR ',') as workshop_ids_csv"),
                \DB::raw("GROUP_CONCAT(DISTINCT bl.name SEPARATOR ', ') as location_names")
            ]);

        $location_ids_param = request()->get('location_ids');
        if (!empty($location_ids_param)) {
            $services->whereIn('pl.location_id', (array) $location_ids_param);
        } else {
            $location_id = request()->get('location_id');
            if (!empty($location_id)) {
                $services->where('pl.location_id', $location_id);
            }
        }

        $services->groupBy('products.id');

        return DataTables::of($services)
            ->filterColumn('selling_price', function ($query, $keyword) {
                $query->where('variations.default_sell_price', 'like', "%{$keyword}%");
            })
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">';
                $html .= '<button type="button" class="btn btn-info btn-xs btn-modal edit-service" data-container=".services_modal" data-href="' . action([\App\Http\Controllers\ServiceController::class, 'show'], [$row->id]) . '" data-update-url="' . action([\App\Http\Controllers\ServiceController::class, 'update'], [$row->id]) . '" data-name="' . e($row->name) . '" data-price="' . e($row->selling_price) . '" data-workshop-ids="' . e($row->workshop_ids_csv ?? '') . '" data-price-type="' . e($row->product_custom_field1 ?? 'manual') . '" data-flat-rate="' . e($row->product_custom_field2 ?? '') . '" data-service-hours="' . e($row->serviceHours ?? '') . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</button>';
                $html .= '<a href="' . action([\App\Http\Controllers\ServiceController::class, 'getServiceOverview'], [$row->id]) . '" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-eye-open"></i> ' . __("Overview") . '</a>';
                $html .= '<button type="button" class="btn btn-danger btn-xs delete-service" data-href="' . action([\App\Http\Controllers\ServiceController::class, 'destroy'], [$row->id]) . '"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</button>';
                $html .= '</div>';
                return $html;
            })
            ->editColumn('selling_price', function ($row) {
                return $this->productUtil->num_f($row->selling_price, false, null, true);
            })
            ->editColumn('serviceHours', function ($row) {
                return $row->serviceHours ? $row->serviceHours . ' hrs' : '-';
            })
            ->editColumn('workshop_names', function ($row) {
                return !empty($row->workshop_names) ? $row->workshop_names : '-';
            })
            ->editColumn('location_names', function ($row) {
                return !empty($row->location_names) ? $row->location_names : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $workshops = Workshop::where('business_id', $business_id);
        if (!auth()->user()->hasRole('Admin')) {
            $permitted_locations = auth()->user()->permitted_locations($business_id);
            if ($permitted_locations != 'all') {
                $workshops->whereIn('business_location_id', (array) $permitted_locations);
            }
        }
        $workshops = $workshops->get();
        
        $flat_rates = FlatRateService::where('business_id', $business_id)
            ->where('is_active', true);
        if (!auth()->user()->hasRole('Admin')) {
            $permitted_locations = auth()->user()->permitted_locations($business_id);
            if ($permitted_locations != 'all') {
                $flat_rates->whereIn('business_location_id', (array) $permitted_locations);
            }
        }
        $flat_rates = $flat_rates->get();

        $business_locations = BusinessLocation::forDropdown($business_id, true, false, true, true);

        return view('services.create', compact('workshops', 'flat_rates', 'business_locations'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'workshop_ids' => 'nullable|array',
                'workshop_ids.*' => 'integer|exists:workshops,id',
                'price_type' => 'required|in:manual,per_hour',
                'flat_rate_id' => 'nullable|exists:flat_rate_services,id',
                'service_hours' => 'nullable|numeric|min:0',
                'product_locations' => 'nullable|array',
                'product_locations.*' => 'integer|exists:business_locations,id',
                'prediction_km_interval' => 'nullable|integer|min:0',
                'prediction_time_interval' => 'nullable|integer|min:0',
                'is_external' => 'nullable|boolean',
            ]);

            \DB::beginTransaction();

            $default_unit = DB::table('units')
                ->where('business_id', $business_id)
                ->where('short_name', 'Pc(s)')
                ->orWhere('short_name', 'Each')
                ->first();

            if (!$default_unit) {
                $default_unit_id = DB::table('units')->insertGetId([
                    'business_id' => $business_id,
                    'actual_name' => 'Pieces',
                    'short_name' => 'Pc(s)',
                    'allow_decimal' => 0,
                    'created_by' => $user_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $default_unit_id = $default_unit->id;
            }

            $workshop_ids = $request->input('workshop_ids', []);

            // Create service with temporary SKU
            $tempSku = 'tmp-'.$business_id.'-'.Str::uuid();
            $service = Product::create([
                'name' => $request->name,
                'business_id' => $business_id,
                'type' => 'single',
                'unit_id' => $default_unit_id,
                'sku' => $tempSku,
                'enable_stock' => 0,
                'alert_quantity' => 0,
                'selling_price' => $request->price,
                'serviceHours' => $request->service_hours,
                'created_by' => $user_id,
                'tax_type' => 'inclusive',
                'tax' => null,
                'product_custom_field1' => $request->price_type,
                'product_custom_field2' => $request->flat_rate_id,
                'product_custom_field3' => $request->prediction_km_interval,
                'product_custom_field4' => $request->prediction_time_interval,
                'is_external' => !empty($request->is_external) ? 1 : 0,
            ]);

            // Generate proper SKU based on product ID
            $sku = $this->productUtil->generateProductSku($service->id);
            $service->sku = $sku;
            $service->save();

            // Ensure price is properly cast to float
            $price = (float) $request->price;

            $this->productUtil->createSingleProductVariation(
                $service,
                $sku,
                0,
                0,
                0,
                $price,
                $price
            );

            if (!empty($workshop_ids)) {
                $now = now();
                $rows = collect($workshop_ids)->unique()->map(function ($wid) use ($service, $now) {
                    return [
                        'product_id' => $service->id,
                        'workshop_id' => (int) $wid,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->values()->all();
                \DB::table('product_workshop')->insert($rows);
            }

            $product_locations = $request->input('product_locations', []);
            if (!empty($product_locations)) {
                $service->product_locations()->sync($product_locations);
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Service created successfully')
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $service = Product::where('business_id', $business_id)
            ->where('enable_stock', 0)
            ->where('virtual_product', 0)
            ->where('is_client_flagged', 0)
            ->with(['variations', 'brand', 'category', 'unit', 'product_locations'])
            ->findOrFail($id);

        $business_locations = BusinessLocation::forDropdown($business_id, true, false, true, true);
        $assigned_location_ids = $service->product_locations->pluck('id')->toArray();

        $workshops = Workshop::where('business_id', $business_id);
        if (!empty($assigned_location_ids)) {
            $workshops->whereIn('business_location_id', $assigned_location_ids);
        } elseif (!auth()->user()->hasRole('Admin')) {
            $permitted_locations = auth()->user()->permitted_locations($business_id);
            if ($permitted_locations != 'all') {
                $workshops->whereIn('business_location_id', (array) $permitted_locations);
            }
        }
        $workshops = $workshops->get();
        
        $flat_rates = FlatRateService::where('business_id', $business_id)
            ->where('is_active', true);
        if (!empty($assigned_location_ids)) {
            $flat_rates->whereIn('business_location_id', $assigned_location_ids);
        } elseif (!auth()->user()->hasRole('Admin')) {
            $permitted_locations = auth()->user()->permitted_locations($business_id);
            if ($permitted_locations != 'all') {
                $flat_rates->whereIn('business_location_id', (array) $permitted_locations);
            }
        }
        $flat_rates = $flat_rates->get();

        $assigned_workshop_ids = \DB::table('product_workshop')
            ->where('product_id', $service->id)
            ->pluck('workshop_id')
            ->toArray();

        return view('services.edit', compact('service', 'workshops', 'flat_rates', 'assigned_workshop_ids', 'business_locations', 'assigned_location_ids'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'workshop_ids' => 'nullable|array',
                'workshop_ids.*' => 'integer|exists:workshops,id',
                'price_type' => 'required|in:manual,per_hour',
                'flat_rate_id' => 'nullable|exists:flat_rate_services,id',
                'service_hours' => 'nullable|numeric|min:0',
                'product_locations' => 'nullable|array',
                'product_locations.*' => 'integer|exists:business_locations,id',
                'prediction_km_interval' => 'nullable|integer|min:0',
                'prediction_time_interval' => 'nullable|integer|min:0',
                'is_external' => 'nullable|boolean',
            ]);

            \DB::beginTransaction();

            $service = Product::where('business_id', $business_id)
                ->where('enable_stock', 0)
                ->where('virtual_product', 0)
                ->where('is_client_flagged', 0)
                ->findOrFail($id);

            $price = $request->price;
            if ($request->price_type === 'per_hour'
                && $request->filled('flat_rate_id')
                && $request->filled('service_hours')
            ) {
                $flatRate = FlatRateService::where('business_id', $business_id)
                    ->find($request->flat_rate_id);

                if ($flatRate) {
                    $price = round((float) $flatRate->price_per_hour * (float) $request->service_hours, 2);
                }
            }

            $workshop_ids = $request->input('workshop_ids', []);

            $service->update([
                'name' => $request->name,
                'selling_price' => $price,
                'serviceHours' => $request->service_hours,
                'product_custom_field1' => $request->price_type,
                'product_custom_field2' => $request->flat_rate_id,
                'product_custom_field3' => $request->prediction_km_interval,
                'product_custom_field4' => $request->prediction_time_interval,
                'is_external' => !empty($request->is_external) ? 1 : 0,
            ]);

            \DB::table('variations')
                ->where('product_id', $service->id)
                ->update([
                    'default_sell_price' => $price,
                    'sell_price_inc_tax' => $price,
                    'updated_at' => now()
                ]);

            \DB::table('product_workshop')->where('product_id', $service->id)->delete();
            if (!empty($workshop_ids)) {
                $now = now();
                $rows = collect($workshop_ids)->unique()->map(function ($wid) use ($service, $now) {
                    return [
                        'product_id' => $service->id,
                        'workshop_id' => (int) $wid,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->values()->all();
                \DB::table('product_workshop')->insert($rows);
            }

            $product_locations = $request->input('product_locations', []);
            $service->product_locations()->sync($product_locations);

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Service updated successfully')
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $service = Product::where('business_id', $business_id)
                ->where('enable_stock', 0)
                ->where('virtual_product', 0)
                ->where('is_client_flagged', 0)
                ->findOrFail($id);

            $is_used = DB::table('transaction_sell_lines')
                ->where('product_id', $service->id)
                ->exists();

            if ($is_used) {
                return response()->json([
                    'success' => false,
                    'message' => __('This service cannot be deleted as it is used in transactions')
                ], 400);
            }

            DB::beginTransaction();

            DB::table('variations')->where('product_id', $service->id)->delete();
            
            $service->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Service deleted successfully')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getFlatRateDetails($id)
    {
        $business_id = request()->session()->get('user.business_id');
        
        $flat_rate = FlatRateService::where('business_id', $business_id)
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'price_per_hour' => $flat_rate->price_per_hour
        ]);
    }

    public function optionsByLocations(Request $request)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_ids = (array) $request->input('location_ids', []);

        $workshopsQuery = Workshop::where('business_id', $business_id);
        if (!empty($location_ids)) {
            $workshopsQuery->whereIn('business_location_id', $location_ids);
        }
        if (!auth()->user()->hasRole('Admin')) {
            $permitted_locations = auth()->user()->permitted_locations($business_id);
            if ($permitted_locations != 'all') {
                $workshopsQuery->whereIn('business_location_id', (array) $permitted_locations);
            }
        }
        $workshops = $workshopsQuery->select('id', 'name')->get();

        $flatRatesQuery = FlatRateService::where('business_id', $business_id)
            ->where('is_active', true);
        if (!empty($location_ids)) {
            $flatRatesQuery->whereIn('business_location_id', $location_ids);
        }
        if (!auth()->user()->hasRole('Admin')) {
            $permitted_locations = auth()->user()->permitted_locations($business_id);
            if ($permitted_locations != 'all') {
                $flatRatesQuery->whereIn('business_location_id', (array) $permitted_locations);
            }
        }
        $flat_rates = $flatRatesQuery->select('id', 'name', 'price_per_hour')->get();

        return response()->json([
            'workshops' => $workshops,
            'flat_rates' => $flat_rates,
        ]);
    }

    public function getServiceOverview($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $current_user = auth()->user();
        $isAdmin = $current_user->hasRole('Admin#' . $business_id) || $current_user->can('superadmin');
        $permitted_locations = $current_user->permitted_locations($business_id);
        $location_id = request()->get('location');

        $locations = BusinessLocation::where('business_id', $business_id);
        if (!$isAdmin && $permitted_locations != 'all') {
            $locations->whereIn('id', (array) $permitted_locations);
        }
        $locations = $locations->select('id', 'name')->get();

        $service = Product::where('business_id', $business_id)
            ->where('enable_stock', 0)
            ->where('virtual_product', 0)
            ->where('is_client_flagged', 0)
            ->with(['variations', 'brand', 'category', 'unit'])
            ->findOrFail($id);

        // Get joborders that use this service
        $joborders = DB::table('product_joborder as pjo')
            ->join('repair_job_sheets as rjs', 'pjo.job_order_id', '=', 'rjs.id')
            ->join('contacts as c', 'rjs.contact_id', '=', 'c.id')
            ->join('business_locations as bl', 'rjs.location_id', '=', 'bl.id')
            ->where('pjo.product_id', $id)
            ->when(!empty($location_id), function ($query) use ($location_id) {
                $query->where('rjs.location_id', $location_id);
            })
            ->when(!$isAdmin && $permitted_locations != 'all', function ($query) use ($permitted_locations) {
                $query->whereIn('rjs.location_id', (array) $permitted_locations);
            })
            ->select([
                'rjs.id',
                'rjs.job_sheet_no',
                'rjs.created_at',
                'c.name as contact_name',
                'c.mobile',
                'bl.name as location_name',
                'pjo.quantity',
                'pjo.price',
                'pjo.purchase_price',
                DB::raw('CASE WHEN rjs.status_id IS NOT NULL THEN (
                    SELECT name FROM repair_statuses WHERE id = rjs.status_id
                ) ELSE NULL END as status_name')
            ])
            ->orderByDesc('rjs.created_at')
            ->get();

        // Get sales that include this service
        $sales = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('tsl.product_id', $id)
            ->when(!empty($location_id), function ($query) use ($location_id) {
                $query->where('t.location_id', $location_id);
            })
            ->when(!$isAdmin && $permitted_locations != 'all', function ($query) use ($permitted_locations) {
                $query->whereIn('t.location_id', (array) $permitted_locations);
            })
            ->select([
                't.id',
                't.invoice_no',
                't.transaction_date',
                't.final_total',
                't.payment_status',
                'c.name as contact_name',
                'bl.name as location_name',
                'tsl.quantity',
                'tsl.unit_price',
                'tsl.line_discount_amount',
                DB::raw('(tsl.unit_price * tsl.quantity - COALESCE(tsl.line_discount_amount, 0)) as line_total')
            ])
            ->orderByDesc('t.transaction_date')
            ->get();

        // Calculate summary statistics
        $joborder_count = $joborders->count();
        $sale_count = $sales->count();
        $total_joborder_quantity = $joborders->sum('quantity');
        $total_sale_quantity = $sales->sum('quantity');
        $total_joborder_revenue = $joborders->sum(function($item) {
            return ($item->price ?? 0) * ($item->quantity ?? 0);
        });
        $total_sale_revenue = $sales->sum('line_total');

        $summary_quantity = $total_sale_quantity > 0 ? $total_sale_quantity : $total_joborder_quantity;
        $summary_revenue = $total_sale_revenue > 0 ? $total_sale_revenue : $total_joborder_revenue;

        return view('services.overview', compact(
            'service',
            'joborders',
            'sales',
            'joborder_count',
            'sale_count',
            'total_joborder_quantity',
            'total_sale_quantity',
            'total_joborder_revenue',
            'total_sale_revenue',
            'summary_quantity',
            'summary_revenue',
            'locations',
            'location_id'
        ));
    }
}