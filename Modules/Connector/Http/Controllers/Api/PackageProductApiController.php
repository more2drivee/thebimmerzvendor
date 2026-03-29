<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackageProductApiController extends Controller
{
    /**
     * Get all service packages (without products)
     * Supports filters: search, device, model, km
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        
        $query = DB::table('service_package as sp')
            ->leftJoin('categories as c', 'sp.device_id', '=', 'c.id')
            ->leftJoin('repair_device_models as rdm', 'sp.repair_device_model_id', '=', 'rdm.id')
            ->select(
                'sp.id',
                'sp.name',
                'sp.km',
                'sp.device_id',
                'sp.repair_device_model_id',
                'sp.from',
                'sp.to',
       
                'c.name as device_name',
                'rdm.name as model_name'
            );

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('sp.name', 'like', "%$search%");
                  
        }
        
        // Apply device filter
        if ($request->filled('device') || $request->filled('device_id')) {
            $deviceId = $request->get('device') ?? $request->get('device_id');
            $query->where('sp.device_id', $deviceId);
        }

        // Apply model filter
        if ($request->filled('model') || $request->filled('model_id') || $request->filled('repair_device_model_id')) {
            $modelId = $request->get('model') ?? $request->get('model_id') ?? $request->get('repair_device_model_id');
            $query->where('sp.repair_device_model_id', $modelId);
        }

        // Apply km filter
        if ($request->filled('km')) {
            $km = $request->get('km');
            $query->where('sp.km', '<=', $km);
        }

        // Get packages (no products)
        $perPage = (int)($request->get('per_page') ?? 15);
        if ($perPage <= 0) {
            $perPage = 15;
        }
        $packagesPaginator = $query->paginate($perPage)->appends($request->query());
        $packages = collect($packagesPaginator->items());

        // Build response without products
        $result = $packages->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'km' => $package->km,
                'device_id' => $package->device_id,
                'device_name' => $package->device_name,
                'repair_device_model_id' => $package->repair_device_model_id,
                'model_name' => $package->model_name,
                'from' => $package->from,
                'to' => $package->to,
            ];
        })->values();

        return response()->json([
            'data' => $result,
            'meta' => [
                'current_page' => $packagesPaginator->currentPage(),
                'per_page' => $packagesPaginator->perPage(),
                'total' => $packagesPaginator->total(),
                'last_page' => $packagesPaginator->lastPage(),
            ],
        ]);
    }

    /**
     * Show a single package (without products)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $package = DB::table('service_package as sp')
            ->leftJoin('categories as c', 'sp.device_id', '=', 'c.id')
            ->leftJoin('repair_device_models as rdm', 'sp.repair_device_model_id', '=', 'rdm.id')
            ->where('sp.id', $id)
            ->select(
                'sp.id',
                'sp.name',
                'sp.km',
                'sp.device_id',
                'sp.repair_device_model_id',
                'sp.from',
                'sp.to',
                'c.name as device_name',
                'rdm.name as model_name'
            )
            ->first();

        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $package->id,
                'name' => $package->name,
                'km' => $package->km,
                'device_id' => $package->device_id,
                'device_name' => $package->device_name,
                'repair_device_model_id' => $package->repair_device_model_id,
                'model_name' => $package->model_name,
                'from' => $package->from,
                'to' => $package->to,
            ],
        ]);
    }

    /**
     * Get products associated with a specific package
     * Route: GET package-products/package/{package_id}
     *
     * @param int $package_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPackageWithProducts($package_id)
    {
        // Fetch package basic info
        $package = DB::table('service_package as sp')
            ->leftJoin('categories as c', 'sp.device_id', '=', 'c.id')
            ->leftJoin('repair_device_models as rdm', 'sp.repair_device_model_id', '=', 'rdm.id')
            ->where('sp.id', $package_id)
            ->select(
                'sp.id',
                'sp.name',
                'sp.km',
                'sp.device_id',
                'sp.repair_device_model_id',
                'sp.from',
                'sp.to',
                'c.name as device_name',
                'rdm.name as model_name'
            )
            ->first();

        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        // Fetch products for this package
        $locationId = optional(auth()->user())->location_id;
        $products = DB::table('package_product as pp')
            ->join('products as p', 'pp.product_id', '=', 'p.id')
            ->where('pp.package_id', $package_id)
            ->select(
                'pp.id as package_product_id',
                'pp.package_id',
                'pp.product_id',
                'p.name as product_name',
                'p.sku',
                'p.type as product_type',
                'p.unit_id',
                'pp.created_at',
                'pp.updated_at'
            )
            ->selectRaw(
                $locationId
                    ? 'COALESCE((SELECT SUM(vld.qty_available) FROM variation_location_details vld JOIN variations v2 ON vld.variation_id = v2.id WHERE v2.product_id = p.id AND vld.location_id = ?), 0) as qty_available'
                    : 'COALESCE((SELECT SUM(vld.qty_available) FROM variation_location_details vld JOIN variations v2 ON vld.variation_id = v2.id WHERE v2.product_id = p.id), 0) as qty_available',
                $locationId ? [$locationId] : []
            )
            ->selectRaw('(
                SELECT v.sell_price_inc_tax
                FROM variations v
                WHERE v.product_id = p.id
                ORDER BY v.id ASC LIMIT 1
            ) as price')
            ->get();

        $normalizedProducts = $products->map(function ($prod) {
            return [
                'id' => $prod->product_id,
                'name' => $prod->product_name,
                'sku' => $prod->sku,
                'price' => $prod->price !== null ? (float) $prod->price : null,
                'qty_available' => (float) ($prod->qty_available ?? 0),
            ];
        })->values();

        return response()->json([
            'data' => [
                'id' => $package->id,
                'name' => $package->name,
                'km' => $package->km,
                'device_id' => $package->device_id,
                'device_name' => $package->device_name,
                'repair_device_model_id' => $package->repair_device_model_id,
                'model_name' => $package->model_name,
                'from' => $package->from,
                'to' => $package->to,
                'products' => $normalizedProducts,
            ],
        ]);
    }
}