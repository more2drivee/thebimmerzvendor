<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicEcomController extends ApiController
{
    /**
     * List e-commerce products without auth.
     */
    public function products(Request $request)
    {
        $validated = $request->validate([
            'business_id' => ['required', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:191'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $businessId = (int) $validated['business_id'];
        $perPage = $validated['per_page'] ?? 20;

        $query = Product::query()
            ->where('products.business_id', $businessId)
            ->where('products.virtual_product', 0)
            ->where('products.not_for_selling', 0)
            ->where('products.is_inactive', 0)
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.image',
                'products.brand_id',
                'products.category_id',
                'products.sub_category_id',
                'products.weight',
                'products.product_description',
            ]);

        if (Schema::hasColumn('products', 'shipping_details')) {
            $query->addSelect('products.shipping_details');
        }

        $query->selectRaw('(
                SELECT v.id
                FROM variations v
                WHERE v.product_id = products.id
                ORDER BY v.id ASC
                LIMIT 1
            ) as variation_id')
            ->selectRaw('(
                SELECT v.default_sell_price
                FROM variations v
                WHERE v.product_id = products.id
                ORDER BY v.id ASC
                LIMIT 1
            ) as default_sell_price');

      
       
            $query->where("products.is_ecom", 1);
    

        if (!empty($validated['category_id'])) {
            $query->where('products.category_id', $validated['category_id']);
        }

        if (!empty($validated['brand_id'])) {
            $query->where('products.brand_id', $validated['brand_id']);
        }

        if (!empty($validated['q'])) {
            $search = trim($validated['q']);
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        // if (!empty($locationId)) {
        //     $query->whereExists(function ($q) use ($locationId) {
        //         $q->select(DB::raw(1))
        //             ->from('product_locations')
        //             ->whereColumn('product_locations.product_id', 'products.id')
        //             ->where('product_locations.location_id', $locationId);
        //     });

        //     $query->selectRaw('
        //         COALESCE((
        //             SELECT SUM(vld.qty_available)
        //             FROM variation_location_details vld
        //             JOIN variations v ON v.id = vld.variation_id
        //             WHERE v.product_id = products.id
        //             AND vld.location_id = ?
        //         ), 0) as qty_available
        //     ', [$locationId]);
        // } else {
        //     $query->selectRaw('
        //         COALESCE((
        //             SELECT SUM(vld.qty_available)
        //             FROM variation_location_details vld
        //             JOIN variations v ON v.id = vld.variation_id
        //             WHERE v.product_id = products.id
        //         ), 0) as qty_available
        //     ');
        // }

        $products = $query->orderByDesc('products.id')->paginate($perPage);
        $products->appends($request->query());

        $products->setCollection($products->getCollection()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'variation_id' => $product->variation_id,
                'default_sell_price' => (float) ($product->default_sell_price ?? 0),
                'brand_id' => $product->brand_id,
                'category_id' => $product->category_id,
                'sub_category_id' => $product->sub_category_id,
                'description' => strip_tags($product->product_description),
        
                
                'image_url' => $product->image_url,
            ];
        }));

        return response()->json($products);
    }
  public function productsByCAtegoryId(Request $request, $category_id)
    {
      $validated = $request->validate([
    'business_id' => ['required', 'integer'],
    'brand_id' => ['nullable', 'integer'],
    'q' => ['nullable', 'string', 'max:191'],
    'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
]);

        $businessId = (int) $validated['business_id'];
        $perPage = $validated['per_page'] ?? 20;

        $query = Product::query()
            ->where('products.business_id', $businessId)
            ->where('products.virtual_product', 0)
          ->where('products.category_id', $category_id)
            ->where('products.not_for_selling', 0)
            ->where('products.is_inactive', 0)
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.image',
                'products.brand_id',
                'products.category_id',
                'products.sub_category_id',
                'products.weight',
                'products.product_description',
            ]);

        if (Schema::hasColumn('products', 'shipping_details')) {
            $query->addSelect('products.shipping_details');
        }

        $query->selectRaw('(
                SELECT v.id
                FROM variations v
                WHERE v.product_id = products.id
                ORDER BY v.id ASC
                LIMIT 1
            ) as variation_id')
            ->selectRaw('(
                SELECT v.default_sell_price
                FROM variations v
                WHERE v.product_id = products.id
                ORDER BY v.id ASC
                LIMIT 1
            ) as default_sell_price');

      
       
            $query->where("products.is_ecom", 1);
    

        if (!empty($validated['brand_id'])) {
            $query->where('products.brand_id', $validated['brand_id']);
        }

        if (!empty($validated['q'])) {
            $search = trim($validated['q']);
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        // if (!empty($locationId)) {
        //     $query->whereExists(function ($q) use ($locationId) {
        //         $q->select(DB::raw(1))
        //             ->from('product_locations')
        //             ->whereColumn('product_locations.product_id', 'products.id')
        //             ->where('product_locations.location_id', $locationId);
        //     });

        //     $query->selectRaw('
        //         COALESCE((
        //             SELECT SUM(vld.qty_available)
        //             FROM variation_location_details vld
        //             JOIN variations v ON v.id = vld.variation_id
        //             WHERE v.product_id = products.id
        //             AND vld.location_id = ?
        //         ), 0) as qty_available
        //     ', [$locationId]);
        // } else {
        //     $query->selectRaw('
        //         COALESCE((
        //             SELECT SUM(vld.qty_available)
        //             FROM variation_location_details vld
        //             JOIN variations v ON v.id = vld.variation_id
        //             WHERE v.product_id = products.id
        //         ), 0) as qty_available
        //     ');
        // }

        $products = $query->orderByDesc('products.id')->paginate($perPage);
        $products->appends($request->query());

        $products->setCollection($products->getCollection()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'variation_id' => $product->variation_id,
                'default_sell_price' => (float) ($product->default_sell_price ?? 0),
                'brand_id' => $product->brand_id,
                'category_id' => $product->category_id,
                'sub_category_id' => $product->sub_category_id,
                'description' => strip_tags($product->product_description),
        
                
                'image_url' => $product->image_url,
            ];
        }));

        return response()->json($products);
    }
public function productById(Request $request, $id)
{
    $validated = $request->validate([
        'business_id' => ['required', 'integer'],
    ]);

    $businessId = (int) $validated['business_id'];

    $product = Product::query()
        ->where('products.id', $id)
        ->where('products.business_id', $businessId)
        ->where('products.virtual_product', 0)
        ->where('products.not_for_selling', 0)
        ->where('products.is_inactive', 0)
        ->where('products.is_ecom', 1)
        ->select([
            'products.id',
            'products.name',
            'products.sku',
            'products.image',
            'products.brand_id',
            'products.category_id',
            'products.sub_category_id',
            'products.weight',
            'products.product_description',
        ])
        ->selectRaw('(
            SELECT v.id
            FROM variations v
            WHERE v.product_id = products.id
            ORDER BY v.id ASC
            LIMIT 1
        ) as variation_id')
        ->selectRaw('(
            SELECT v.default_sell_price
            FROM variations v
            WHERE v.product_id = products.id
            ORDER BY v.id ASC
            LIMIT 1
        ) as default_sell_price')
        ->first();

    if (!$product) {
        return response()->json([
            'success' => false,
            'message' => 'Product not found'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'variation_id' => $product->variation_id,
            'default_sell_price' => (float) ($product->default_sell_price ?? 0),
            'brand_id' => $product->brand_id,
            'category_id' => $product->category_id,
            'sub_category_id' => $product->sub_category_id,
            'weight' => $product->weight,
            'description' => strip_tags($product->product_description),
            'image_url' => $product->image_url,
        ]
    ]);
}
    /**
     * Create proforma transaction via API without stock deduction.
     */
    public function storeProforma(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'error' => [
                    'message' => 'Unauthorized action.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $request->validate([
            'sells' => ['required', 'array', 'min:1'],
            'sells.*.contact_id' => ['required', 'integer'],
            'sells.*.products' => ['required', 'array', 'min:1'],
            'sells.*.products.*.product_id' => ['required', 'integer'],
            'sells.*.products.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $business_id = Auth::user()->business_id;
        $first_location = \App\BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->first();

        if (!$first_location) {
            return response()->json([
                'error' => [
                    'message' => 'No active location found for this business.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $sells = collect($request->input('sells', []))
            ->map(function ($sell) use ($first_location, $business_id) {
                $sell['status'] = 'draft';
                $sell['sub_status'] = 'proforma';
                $sell['location_id'] = $first_location->id;

                // Save shipping_type to custom field
                $shipping_type = $sell['shipping_type'] ?? null;
                $sell['shipping_custom_field_1'] = $shipping_type;

                // Handle shipping charges based on shipping_type
                if ($shipping_type === 'delivery') {
                    // Apply shipping charges for delivery
                    if (!isset($sell['shipping_charges'])) {
                        $business = \App\Business::find($business_id);
                        $common_settings = $business->common_settings ?? [];
                        $sell['shipping_charges'] = $common_settings['default_shipping_charges'] ?? 0;
                    }
                } else {
                    // No shipping charges for pickup or other types
                    $sell['shipping_charges'] = 0;
                }

                return $sell;
            })
            ->values()
            ->all();

        $request->merge(['sells' => $sells]);

        return app(SellController::class)->store($request);
    }

  
}
