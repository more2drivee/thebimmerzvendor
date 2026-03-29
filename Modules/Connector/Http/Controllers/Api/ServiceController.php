<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\BusinessLocation;
use App\Business;
use App\Product;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Repair\Entities\FlatRateService;
use Modules\Repair\Entities\Workshop;

class ServiceController extends ApiController
{
    protected $productUtil;
    protected $businessUtil;
    protected $moduleUtil;

    public function __construct(ProductUtil $productUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        parent::__construct();
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    // GET /api/services
    public function index(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $query = Product::leftJoin('product_workshop as pw', 'pw.product_id', '=', 'products.id')
            ->leftJoin('workshops as w', 'w.id', '=', 'pw.workshop_id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
            ->leftJoin('variations', 'variations.product_id', '=', 'products.id')
            ->leftJoin('product_locations as pl', 'pl.product_id', '=', 'products.id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'pl.location_id')
            ->where('products.business_id', $business_id)
            ->where('products.enable_stock', 0)
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'variations.default_sell_price as selling_price',
                'products.serviceHours',
                'products.product_custom_field1 as price_type',
                'products.product_custom_field2 as flat_rate_id',
                'brands.name as brand_name',
                'units.short_name as unit_name',
                'c1.name as category_name',
                'products.created_at',
                DB::raw("GROUP_CONCAT(DISTINCT w.name SEPARATOR ', ') as workshop_names"),
                DB::raw("GROUP_CONCAT(DISTINCT w.id SEPARATOR ',') as workshop_ids_csv"),
                DB::raw("GROUP_CONCAT(DISTINCT bl.name SEPARATOR ', ') as location_names")
            ])
            ->groupBy('products.id');

        // Optional filters
        if ($request->filled('location_ids')) {
            $query->whereIn('pl.location_id', (array) $request->input('location_ids'));
        } elseif ($request->filled('location_id')) {
            $query->where('pl.location_id', $request->input('location_id'));
        }
        if ($request->filled('name')) {
            $name = '%' . trim($request->input('name')) . '%';
            $query->where('products.name', 'like', $name);
        }

        $perPage = (int) ($request->input('per_page', $this->perPage));
        if ($perPage == -1) {
            $services = $query->get();
        } else {
            $services = $query->paginate($perPage);
            $services->appends($request->query());
        }

        return response()->json(['data' => $services]);
    }

    // POST /api/services
    public function store(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;
        $user_id = $user->id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'workshop_ids' => 'nullable|array',
            'workshop_ids.*' => 'integer|exists:workshops,id',
            'price_type' => 'required|in:manual,per_hour',
            'flat_rate_id' => 'nullable|exists:flat_rate_services,id',
            'service_hours' => 'nullable|numeric|min:0',
            'product_locations' => 'nullable|array',
            'product_locations.*' => 'integer|exists:business_locations,id',
            'is_external' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            // Ensure we have a default unit
            $default_unit = DB::table('units')
                ->where('business_id', $business_id)
                ->where('short_name', 'جنية')
                ->orWhere('short_name', 'Each')
                ->first();

            if (!$default_unit) {
                $default_unit_id = DB::table('units')->insertGetId([
                    'business_id' => $business_id,
                    'actual_name' => 'جنية',
                    'short_name' => 'جنية',
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
                'name' => $validated['name'],
                'business_id' => $business_id,
                'type' => 'single',
                'unit_id' => $default_unit_id,
                'sku' => $tempSku,
                'enable_stock' => 0,
                'alert_quantity' => 0,
                'selling_price' => $validated['price'],
                'serviceHours' => $validated['service_hours'] ?? null,
                'created_by' => $user_id,
                'tax_type' => 'inclusive',
                'tax' => null,
                'product_custom_field1' => $validated['price_type'],
                'product_custom_field2' => $validated['flat_rate_id'] ?? null,
                'is_external' => $validated['is_external'] ?? 0,
            ]);

            // Generate proper SKU based on product ID without session
            $sku_prefix = Business::where('id', $business_id)->value('sku_prefix');
            $sku = ($sku_prefix ?? '') . str_pad($service->id, 4, '0', STR_PAD_LEFT);
            $service->sku = $sku;
            $service->save();

            // Ensure price is float
            $price = (float) $validated['price'];

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
                DB::table('product_workshop')->insert($rows);
            }

            $product_locations = $request->input('product_locations', []);
            if (!empty($product_locations)) {
                $service->product_locations()->sync($product_locations);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $service->fresh(['variations']),
                'message' => 'Service created successfully'
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // GET /api/services/{id}
    public function show($id)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $service = Product::where('business_id', $business_id)
            ->where('enable_stock', 0)
            ->with(['variations', 'brand', 'category', 'unit', 'product_locations'])
            ->findOrFail($id);

        // Gather assigned workshops and location ids
        $assigned_workshop_ids = DB::table('product_workshop')
            ->where('product_id', $service->id)
            ->pluck('workshop_id')
            ->toArray();
        $assigned_location_ids = $service->product_locations->pluck('id')->toArray();

        return response()->json([
            'data' => [
                'service' => $service,
                'assigned_workshop_ids' => $assigned_workshop_ids,
                'assigned_location_ids' => $assigned_location_ids,
            ]
        ]);
    }

    // PUT /api/services/{id}
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'workshop_ids' => 'nullable|array',
            'workshop_ids.*' => 'integer|exists:workshops,id',
            'price_type' => 'required|in:manual,per_hour',
            'flat_rate_id' => 'nullable|exists:flat_rate_services,id',
            'service_hours' => 'nullable|numeric|min:0',
            'product_locations' => 'nullable|array',
            'product_locations.*' => 'integer|exists:business_locations,id',
        ]);

        DB::beginTransaction();
        try {
            $service = Product::where('business_id', $business_id)
                ->where('enable_stock', 0)
                ->findOrFail($id);

            $price = $validated['price'];
            if ($validated['price_type'] === 'per_hour'
                && !empty($validated['flat_rate_id'])
                && isset($validated['service_hours'])
            ) {
                $flatRate = FlatRateService::where('business_id', $business_id)
                    ->find($validated['flat_rate_id']);
                if ($flatRate) {
                    $price = round((float) $flatRate->price_per_hour * (float) $validated['service_hours'], 2);
                }
            }

            $workshop_ids = $request->input('workshop_ids', []);

            $service->update([
                'name' => $validated['name'],
                'selling_price' => $price,
                'serviceHours' => $validated['service_hours'] ?? null,
                'product_custom_field1' => $validated['price_type'],
                'product_custom_field2' => $validated['flat_rate_id'] ?? null,
            ]);

            DB::table('variations')
                ->where('product_id', $service->id)
                ->update([
                    'default_sell_price' => $price,
                    'sell_price_inc_tax' => $price,
                    'updated_at' => now()
                ]);

            DB::table('product_workshop')->where('product_id', $service->id)->delete();
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
                DB::table('product_workshop')->insert($rows);
            }

            $product_locations = $request->input('product_locations', []);
            $service->product_locations()->sync($product_locations);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service updated successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // DELETE /api/services/{id}
    public function destroy($id)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        try {
            $service = Product::where('business_id', $business_id)
                ->where('enable_stock', 0)
                ->findOrFail($id);

            $is_used = DB::table('transaction_sell_lines')
                ->where('product_id', $service->id)
                ->exists();

            if ($is_used) {
                return response()->json([
                    'success' => false,
                    'message' => 'This service cannot be deleted as it is used in transactions'
                ], Response::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            DB::table('variations')->where('product_id', $service->id)->delete();
            $service->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service deleted successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // GET /api/services/flat-rate/{id}
    public function getFlatRateDetails($id)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $flat_rate = FlatRateService::where('business_id', $business_id)
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'price_per_hour' => $flat_rate->price_per_hour
        ]);
    }


    public function listFlatRates(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $query = FlatRateService::where('business_id', $business_id)
            ->where('is_active', true);

        $requestedLocationIds = collect((array) $request->input('location_ids', []));
        if ($request->filled('location_id')) {
            $requestedLocationIds->push($request->input('location_id'));
        }


        $filteredLocationIds = $requestedLocationIds
            ->filter(function ($id) {
                return !is_null($id) && $id !== '';
            })
            ->unique()
            ->values()
            ->all();

        if (!empty($filteredLocationIds)) {
            $query->whereIn('business_location_id', $filteredLocationIds);
        }

        $flatRates = $query
            ->select('id', 'name', 'price_per_hour', 'business_location_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $flatRates
        ]);
    }

    // POST /api/services/options-by-locations
    public function optionsByLocations(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;
        $location_ids = (array) $request->input('location_ids', []);

        $workshopsQuery = Workshop::where('business_id', $business_id);
        if (!empty($location_ids)) {
            $workshopsQuery->whereIn('business_location_id', $location_ids);
        }
        $workshops = $workshopsQuery->select('id', 'name')->get();

        $flatRatesQuery = FlatRateService::where('business_id', $business_id)
            ->where('is_active', true);
        if (!empty($location_ids)) {
            $flatRatesQuery->whereIn('business_location_id', $location_ids);
        }
        $flat_rates = $flatRatesQuery->select('id', 'name', 'price_per_hour')->get();

        // Also return locations dropdown for client apps if needed
        $business_locations = BusinessLocation::forDropdown($business_id, true, false, true, true);

        return response()->json([
            'workshops' => $workshops,
            'flat_rates' => $flat_rates,
            'business_locations' => $business_locations,
        ]);
    }
}
