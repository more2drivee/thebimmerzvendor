<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Product;
use App\ProductCompatibility;
use App\SellingPriceGroup;
use App\Unit;
use App\Utils\ProductUtil;
use App\Variation;
use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Connector\Transformers\CommonResource;
use Modules\Connector\Transformers\ProductResource;
use Modules\Connector\Transformers\VariationResource;

/**
 * @group Product management
 * @authenticated
 *
 * APIs for managing products
 */
class ProductController extends ApiController
{
    protected $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        parent::__construct();
        $this->productUtil = $productUtil;
    }

    public function index()
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        // Get the filters from the request
        $filters = request()->only(['brand_id', 'category_id', 'location_id', 'sub_category_id', 'per_page', 'enable_stock', 'qty_available', 'is_external']);
        $filters['selling_price_group'] = request()->input('selling_price_group') == 1 ? true : false;
        // If user is not Admin, force their location_id; Admin can see all
        if (!auth()->user()->hasRole('Admin')) {
            $filters['location_id'] = $user->location_id;
        } else {
            $reqLoc = request()->input('location_id');
            if (!empty($reqLoc)) {
                $filters['location_id'] = (int) $reqLoc;
            }
        }
        // Get stock parameter
        $stock = request()->input('stock');
        if ($stock !== null) {
            $filters['stock'] = $stock;
        }

        // Get search parameters
        $search = request()->only(['sku', 'name']);


        // Handle sorting parameters
        $order_by = null;
        $order_direction = null;

        if (! empty(request()->input('order_by'))) {
            $order_by = in_array(request()->input('order_by'), ['product_name', 'newest']) ? request()->input('order_by') : null;
            $order_direction = in_array(request()->input('order_direction'), ['asc', 'desc']) ? request()->input('order_direction') : 'asc';
        }

        // Get optional device-related parameters
        $device_id = request()->input('device_id', null);
        $device_model_id = request()->input('device_model_id', null);
        $manufacturing_year = request()->input('manufacturing_year', null);

        // Pass parameters to __getProducts
        $products = $this->__getProducts(
            $business_id,
            $filters,
            $search,
            true,
            $order_by,
            $order_direction,
            $device_id,
            $device_model_id,
            $manufacturing_year
        );

        return ProductResource::collection($products);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->where(function ($query) use ($business_id) {
                return $query->where('business_id', $business_id);
            })],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'compatibility' => ['nullable', 'array'],
            'compatibility.*.brand_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'compatibility.*.model_id' => ['nullable', 'integer', 'exists:repair_device_models,id'],
            'compatibility.*.from_year' => ['nullable', 'integer'],
            'compatibility.*.to_year' => ['nullable', 'integer'],
            'compatibility.*.year' => ['nullable', 'integer'],
            'car_brand_id' => ['nullable', 'integer', 'exists:categories,id'],
            'car_model_id' => ['nullable', 'integer', 'exists:repair_device_models,id'],
            'car_year' => ['nullable', 'integer'],
            'car_year_from' => ['nullable', 'integer'],
            'car_year_to' => ['nullable', 'integer'],
        ]);

        $unitId = $validated['unit_id'] ?? Unit::where('business_id', $business_id)->value('id');
        if (!$unitId) {
            $unitId = Unit::whereNull('business_id')->value('id');
        }
        if (!$unitId) {
            return response()->json([
                'error' => [
                    'message' => 'Unit configuration is required before creating products.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $providedSku = $validated['sku'] ?? null;
        $initialSku = $providedSku ?: 'tmp-' . $business_id . '-' . Str::uuid();
        $price = (float) $validated['price'];

        DB::beginTransaction();
        try {
            $product = Product::create([
                'name' => $validated['name'],
                'business_id' => $business_id,
                'type' => 'single',
                'unit_id' => $unitId,
                'brand_id' => $validated['brand_id'] ?? null,
                'category_id' => $validated['category_id'],
                'sub_category_id' => $validated['sub_category_id'] ?? null,
                'tax' => null,
                'tax_type' => 'exclusive',
                'enable_stock' => 1,
                'alert_quantity' => 0,
                'sku' => $initialSku,
                'barcode_type' => 'C128',
                'created_by' => $user->id,
                'not_for_selling' => 0,
            ]);

            if (!$providedSku) {
                $sku_prefix = \App\Business::where('id', $business_id)->value('sku_prefix');
                $generatedSku = $sku_prefix . str_pad($product->id, 4, '0', STR_PAD_LEFT);
                $product->sku = $generatedSku;
                $product->save();
            }

            $product->refresh();

            // Attach product location based on authenticated user or first business location
            $locationId = $user->location_id;
            if (empty($locationId)) {
                $locationId = BusinessLocation::where('business_id', $business_id)
                    ->orderBy('id')
                    ->value('id');
            }
            if (!empty($locationId)) {
                $product->product_locations()->sync([(int) $locationId]);
            }

            // Create variation with selling price (no purchase price or profit percent for API)
            $this->productUtil->createSingleProductVariation(
                $product->id,
                $product->sku,
                0,              // purchase_price = 0
                0,              // dpp_inc_tax = 0
                0,              // profit_percent = 0
                $price,         // selling_price (from API)
                $price          // selling_price_inc_tax (same as selling_price)
            );

            $compatibilityPayload = [];
            if (!empty($validated['compatibility'])) {
                foreach ($validated['compatibility'] as $row) {
                    if (empty($row['brand_category_id']) && empty($row['model_id']) && empty($row['from_year']) && empty($row['to_year']) && empty($row['year'])) {
                        continue;
                    }
                    $fromYear = $row['from_year'] ?? $row['year'] ?? null;
                    $toYear = $row['to_year'] ?? $row['year'] ?? null;
                    $compatibilityPayload[] = [
                        'product_id' => $product->id,
                        'brand_category_id' => $row['brand_category_id'] ?? null,
                        'model_id' => $row['model_id'] ?? null,
                        'from_year' => $fromYear,
                        'to_year' => $toYear,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            } else {
                $brand = $validated['car_brand_id'] ?? null;
                $model = $validated['car_model_id'] ?? null;
                $fromYear = $validated['car_year_from'] ?? $validated['car_year'] ?? null;
                $toYear = $validated['car_year_to'] ?? $validated['car_year'] ?? null;
                if ($brand || $model || $fromYear || $toYear) {
                    $compatibilityPayload[] = [
                        'product_id' => $product->id,
                        'brand_category_id' => $brand,
                        'model_id' => $model,
                        'from_year' => $fromYear,
                        'to_year' => $toYear,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($compatibilityPayload)) {
                ProductCompatibility::insert($compatibilityPayload);
            }

            $product->load([
                'product_variations.variations.variation_location_details',
                'brand',
                'unit',
                'category',
                'sub_category',
                'product_tax',
                'product_locations',
                'compatibility',
            ]);

            $product->setAttribute('qty_available', 0);
            $product->setAttribute('default_sell_price', $price);

            DB::commit();

            return ProductResource::make($product)
                ->additional(['message' => 'Product created successfully.'])
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage(), ['exception' => $e]);

            return response()->json([
                'error' => [
                    'message' => 'Unable to create product.',
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    private function __getProducts(
        $business_id,
        $filters = [],
        $search = [],
        $pagination = false,
        $order_by = null,
        $order_direction = null,
        $device_id = null,
        $device_model_id = null,
        $manufacturing_year = null
    ) {


        $query = Product::where('business_id', $business_id)
            ->where('products.virtual_product', 0)
            ->where('products.is_client_flagged', 0)
            ->where('products.is_inactive', 0)
            ->select('products.*')
            ->selectRaw('(
            SELECT v.default_sell_price
            FROM variations v
            WHERE v.product_id = products.id
            ORDER BY v.id ASC LIMIT 1
        ) as default_sell_price');


        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', (int) $filters['brand_id']);
        }
        // $with = ['product_variations.variations.variation_location_details', 'brand', 'unit', 'category', 'sub_category', 'product_tax', 'product_variations.variations.media', 'product_locations', 'compatibility'];

        $with = [
            'product_variations' => function ($q) {
                $q->select('id', 'product_id'); // only these fields from product_variations
            },
            'product_variations.variations' => function ($q) {
                $q->select(
                    'id',
                    'product_id',
                    'product_variation_id',
                    'default_purchase_price'
                    // default_sell_price moved to product level
                );
            },
            'product_variations.variations.variation_location_details', // load full for now; you can limit if needed
            // 'product_variations.variations.media', // assume all media fields needed; can limit if required

            'brand:id,name',
            'unit:id,actual_name,allow_decimal',
            'category:id,name,category_type',
            'sub_category:id,name', // optionally restrict fields if needed
            'product_tax:id,name,amount', // restrict if required, otherwise full
            'product_locations' => function ($q) {

                $q->select('product_locations.product_id', 'product_locations.location_id');
            },
            // Load only required columns for compatibility to reduce payload
            'compatibility:id,product_id,brand_category_id,model_id,from_year,to_year',
            'compatibility.brandCategory:id,name',
            'compatibility.deviceModel:id,name'
        ];


        if (! empty($filters['category_id'])) {
            // $category_id = explode(',', $filters['category_id']);
            // $query->whereIn('category_id', $category_id);
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['sub_category_id'])) {
            $sub_category_id = explode(',', $filters['sub_category_id']);
            $query->whereIn('sub_category_id', $sub_category_id);
        }


        if (! empty($filters['selling_price_group']) && $filters['selling_price_group'] == true) {
            $with[] = 'product_variations.variations.group_prices';
        }
        if (! empty($filters['location_id'])) {
            $location_id = $filters['location_id'];
            $query->whereHas('product_locations', function ($q) use ($location_id) {
                $q->where('product_locations.location_id', $location_id);
            });

            // qty_available filtered by location
            $query->selectRaw('
                COALESCE((
                    SELECT SUM(vld.qty_available)
                    FROM variation_location_details vld
                    JOIN variations v ON vld.variation_id = v.id
                    WHERE v.product_id = products.id AND vld.location_id = ?
                ), 0) as qty_available', [$location_id]);

            $with['product_variations.variations.variation_location_details'] = function ($q) use ($location_id) {
                $q->where('variation_location_details.location_id', $location_id);
            };

            // Override the 'product_locations' relation loader when location_id filter is present
            $with['product_locations'] = function ($q) use ($location_id) {
                // Filter the relationship and explicitly select only known pivot keys.
                $q->where('product_locations.location_id', $location_id)
                    ->select('product_locations.product_id', 'product_locations.location_id');
            };
        } else {
            // qty_available across all locations
            $query->selectRaw('
                COALESCE((
                    SELECT SUM(vld.qty_available)
                    FROM variation_location_details vld
                    JOIN variations v ON vld.variation_id = v.id
                    WHERE v.product_id = products.id
                ), 0) as qty_available
            ');
        }

        if (! empty($filters['product_ids'])) {
            $query->whereIn('id', $filters['product_ids']);
        }

        if (! empty($search)) {
            $query->where(function ($query) use ($search) {

                if (! empty($search['name'])) {
                    $nameKeywords = array_filter(preg_split('/\s+/', trim($search['name'])));
                    $query->where(function ($nameQuery) use ($nameKeywords) {
                        $firstKeyword = true;
                        foreach ($nameKeywords as $keyword) {
                            $like = '%' . $keyword . '%';
                            $method = $firstKeyword ? 'where' : 'orWhere';
                            $nameQuery->{$method}(function ($subQuery) use ($like) {
                                $subQuery->where('products.name', 'like', $like)
                                    ->orWhere('products.sku', 'like', $like);
                            });
                            $firstKeyword = false;
                        }
                    });
                }

                // if (! empty($search['sku'])) {
                //     $skuLike = '%' . trim($search['sku']) . '%';
                //     $query->where(function ($skuQuery) use ($skuLike) {
                //         $skuQuery->where('products.sku', 'like', $skuLike)
                //                  ->orWhereHas('variations', function ($variationQuery) use ($skuLike) {
                //                      $variationQuery->where('variations.sub_sku', 'like', $skuLike);
                //                  });
                //     });
                // }

            });
        }

        // Filter by enable_stock ONLY if explicitly requested
        // - enable_stock=0 => products with enable_stock = 0
        // - enable_stock=1 => products with enable_stock = 1
        if (isset($filters['enable_stock'])) {
            if ((string)$filters['enable_stock'] === '0') {
                if (isset($filters['is_external']) && (string)$filters['is_external'] === '1') {
                    
                    $query->where('products.is_external', 1);
                }else{
                    $query->where('products.enable_stock', 0);
                    $query->where('products.is_external', 0);

                }
            } elseif ((string)$filters['enable_stock'] === '1') {
                $query->where('products.enable_stock', 1);
            }
        }

        // Filter external services: is_external=1 implies enable_stock=0 + is_external=1


        if (isset($filters['stock'])) {
            if ((string)$filters['stock'] === '0') {
                // Override any previous enable_stock filter for stock=0
                $query->where('products.enable_stock', 1);
                $query->havingRaw('qty_available = 0');
            } elseif ((string)$filters['stock'] === '1') {
                $query->havingRaw('qty_available >= 1');
            }
        } else {
            // Default behavior when stock parameter is NOT provided
            // Show both:
            //   - Non-stock-managed products (enable_stock = 0) e.g. labour/services
            //   - Stock-managed products (enable_stock = 1) that have qty_available >= 1
            if (!isset($filters['enable_stock'])) {
                $query->havingRaw('(products.enable_stock = 1 AND qty_available >= 1)');
            }
        }
        // } else if (isset($filters['qty_available']) && (string)$filters['qty_available'] === '0') {
        //     $query->havingRaw('qty_available = 0');
        // } elseif (!isset($filters['qty_available'])) {
        //     $query->havingRaw('qty_available > 1');
        // }

        //Order by
        if (! empty($order_by)) {
            if ($order_by == 'product_name') {
                $query->orderBy('products.name', $order_direction);
            }

            if ($order_by == 'newest') {
                $query->orderBy('products.id', $order_direction);
            }
        }

        $query->with($with);

        $perPage = ! empty($filters['per_page']) ? $filters['per_page'] : $this->perPage;

        // Disable pagination if any of the category, brand, or subcategory filters are applied
        // if (!empty($filters['category_id']) || !empty($filters['brand_id']) || !empty($filters['sub_category_id'])) {
        //     $perPage = -1;
        // }

        if ($pagination && $perPage != -1) {
            $products = $query->paginate($perPage);
            $products->appends(request()->query());
        } else {
            // Get all products - we'll limit after sorting by flag
            $products = $query->get();
        }

        $products->transform(function ($product) use ($device_id, $device_model_id, $manufacturing_year) {
            // Default
            $product->flag = false;


            // Ensure all three params are provided (not null/empty)
            if (
                $device_id !== null && $device_id !== '' &&
                $device_model_id !== null && $device_model_id !== '' &&
                $manufacturing_year !== null && $manufacturing_year !== ''
            ) {

                $did = (int) $device_id;
                $mid = (int) $device_model_id;
                $year = (int) $manufacturing_year;



                // Check if any compatibility record matches brand, model and year within optional bounds
                $product->flag = $product->compatibility->contains(function ($c) use ($did, $mid, $year, $product) {
                    $match = ((int) $c->brand_category_id === $did) &&
                        ((int) $c->model_id === $mid);

                    if (!$match) {

                        return false;
                    }

                    $from = $c->from_year; // may be null
                    $to = $c->to_year;     // may be null

                    $lowerOk = is_null($from) ? true : ($year >= (int) $from);
                    $upperOk = is_null($to) ? true : ($year <= (int) $to);




                    return $match && $lowerOk && $upperOk;
                });
            }

            return $product;
        });
        // Sort the products by 'flag', placing the ones with true flag first
        $products = $products->sortByDesc('flag');

        // Check if per_page is -1 either in filters or the default perPage property
        if ((isset($filters['per_page']) && (int)$filters['per_page'] === -1)) {
            // Only apply the 40 product limit if none of the category, brand, or sub-category filters are set
            if (
                empty($filters['category_id']) &&
                empty($filters['brand_id']) &&
                empty($filters['sub_category_id'])
            ) {
                // Apply a limit of 40 products
                $products = $products->take(40);
            }
        }
        return $products;
    }






    public function show($product_ids)
    {
        $user = Auth::user();

        // if (!$user->can('api.access')) {
        //     return $this->respondUnauthorized();
        // }

        $business_id = $user->business_id;
        $filters['selling_price_group'] = request()->input('selling_price_group') == 1 ? true : false;

        $filters['product_ids'] = explode(',', $product_ids);

        $products = $this->__getProducts($business_id, $filters);

        return ProductResource::collection($products);
    }

    /**
     * Function to query product
     *
     * @return Response
     */
    // private function __getProducts($business_id, $filters = [], $search = [], $pagination = false, $order_by = null, $order_direction = null)
    // {
    //     $query = Product::where('business_id', $business_id);

    //     $with = ['product_variations.variations.variation_location_details', 'brand', 'unit', 'category', 'sub_category', 'product_tax', 'product_variations.variations.media', 'product_locations'];

    //     if (! empty($filters['category_id'])) {
    //         $category_ids = explode(',', $filters['category_id']);
    //         $query->whereIn('category_id', $category_ids);
    //     }

    //     if (! empty($filters['sub_category_id'])) {
    //         $sub_category_id = explode(',', $filters['sub_category_id']);
    //         $query->whereIn('sub_category_id', $sub_category_id);
    //     }

    //     if (! empty($filters['brand_id'])) {
    //         $brand_ids = explode(',', $filters['brand_id']);
    //         $query->whereIn('brand_id', $brand_ids);
    //     }

    //     if (! empty($filters['selling_price_group']) && $filters['selling_price_group'] == true) {
    //         $with[] = 'product_variations.variations.group_prices';
    //     }
    //     if (! empty($filters['location_id'])) {
    //         $location_id = $filters['location_id'];
    //         $query->whereHas('product_locations', function ($q) use ($location_id) {
    //             $q->where('product_locations.location_id', $location_id);
    //         });

    //         $with['product_variations.variations.variation_location_details'] = function ($q) use ($location_id) {
    //             $q->where('location_id', $location_id);
    //         };

    //         $with['product_locations'] = function ($q) use ($location_id) {
    //             $q->where('product_locations.location_id', $location_id);
    //         };
    //     }

    //     if (! empty($filters['product_ids'])) {
    //         $query->whereIn('id', $filters['product_ids']);
    //     }

    //     if (! empty($search)) {
    //         $query->where(function ($query) use ($search) {
    //             if (! empty($search['name'])) {
    //                 $query->where('products.name', 'like', '%'.$search['name'].'%');
    //             }

    //             if (! empty($search['sku'])) {
    //                 $sku = $search['sku'];
    //                 $query->orWhere('sku', 'like', '%'.$sku.'%');
    //                 $query->orWhereHas('variations', function ($q) use ($sku) {
    //                     $q->where('variations.sub_sku', 'like', '%'.$sku.'%');
    //                 });
    //             }
    //         });
    //     }

    //     //Order by
    //     if (! empty($order_by)) {
    //         if ($order_by == 'product_name') {
    //             $query->orderBy('products.name', $order_direction);
    //         }

    //         if ($order_by == 'newest') {
    //             $query->orderBy('products.id', $order_direction);
    //         }
    //     }

    //     $query->with($with);

    //     $perPage = ! empty($filters['per_page']) ? $filters['per_page'] : $this->perPage;
    //     if ($pagination && $perPage != -1) {
    //         $products = $query->paginate($perPage);
    //         $products->appends(request()->query());
    //     } else {
    //         $products = $query->get();
    //     }

    //     return $products;
    // }


    public function listVariations($variation_ids = null)
    {
        $user = Auth::user();

        $business_id = $user->business_id;

        $query = Variation::join('products AS p', 'variations.product_id', '=', 'p.id')
            ->join('product_variations AS pv', 'variations.product_variation_id', '=', 'pv.id')
            ->leftjoin('units', 'p.unit_id', '=', 'units.id')
            ->leftjoin('tax_rates as tr', 'p.tax', '=', 'tr.id')
            ->leftjoin('brands', function ($join) {
                $join->on('p.brand_id', '=', 'brands.id')
                    ->whereNull('brands.deleted_at');
            })
            ->leftjoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftjoin('categories as sc', 'p.sub_category_id', '=', 'sc.id')
            ->where('p.business_id', $business_id)
            ->select(
                'variations.id',
                'variations.name as variation_name',
                'variations.sub_sku',
                'p.id as product_id',
                'p.name as product_name',
                'p.sku',
                'p.type as type',
                'p.business_id',
                'p.barcode_type',
                'p.expiry_period',
                'p.expiry_period_type',
                'p.enable_sr_no',
                'p.weight',
                'p.product_custom_field1',
                'p.product_custom_field2',
                'p.product_custom_field3',
                'p.product_custom_field4',
                'p.image as product_image',
                'p.product_description',
                'p.warranty_id',
                'p.brand_id',
                'brands.name as brand_name',
                'p.unit_id',
                'p.enable_stock',
                'p.not_for_selling',
                'units.short_name as unit_name',
                'units.allow_decimal as unit_allow_decimal',
                'p.category_id',
                'c.name as category',
                'p.sub_category_id',
                'sc.name as sub_category',
                'p.tax as tax_id',
                'p.tax_type',
                'tr.name as tax_name',
                'tr.amount as tax_amount',
                'variations.product_variation_id',
                'variations.default_purchase_price',
                'variations.dpp_inc_tax',
                'variations.profit_percent',
                'variations.default_sell_price',
                'variations.sell_price_inc_tax',
                'pv.id as product_variation_id',
                'pv.name as product_variation_name'
            );

        $with = [
            'variation_location_details',
            'media',
            'group_prices',
            'product',
            'product.product_locations',
        ];

        if (! empty(request()->input('category_id'))) {
            $query->where('category_id', request()->input('category_id'));
        }

        if (! empty(request()->input('sub_category_id'))) {
            $query->where('p.sub_category_id', request()->input('sub_category_id'));
        }

        if (! empty(request()->input('brand_id'))) {
            $query->where('p.brand_id', request()->input('brand_id'));
        }

        if (request()->has('not_for_selling')) {
            $not_for_selling = request()->input('not_for_selling') == 1 ? 1 : 0;
            $query->where('p.not_for_selling', $not_for_selling);
        }
        $filters['selling_price_group'] = request()->input('selling_price_group') == 1 ? true : false;

       
            $location_id = $user->location_id;
       
        if (! empty($location_id)) {
            $query->whereHas('product.product_locations', function ($q) use ($location_id) {
                $q->where('product_locations.location_id', $location_id);
            });

            $with['variation_location_details'] = function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            };

            $with['product.product_locations'] = function ($q) use ($location_id) {
                $q->where('product_locations.location_id', $location_id);
            };
        }

        $search = request()->only(['sku', 'name']);

        if (! empty($search)) {
            $query->where(function ($query) use ($search) {
                if (! empty($search['name'])) {
                    $query->where('p.name', 'like', '%' . $search['name'] . '%');
                }

                if (! empty($search['sku'])) {
                    $sku = $search['sku'];
                    $query->orWhere('p.sku', 'like', '%' . $sku . '%')
                        ->where('variations.sub_sku', 'like', '%' . $sku . '%');
                }
            });
        }

        //filter by variations ids
        if (! empty($variation_ids)) {
            $variation_ids = explode(',', $variation_ids);
            $query->whereIn('variations.id', $variation_ids);
        }

        //filter by product ids
        if (! empty(request()->input('product_id'))) {
            $product_ids = explode(',', request()->input('product_id'));
            $query->whereIn('p.id', $product_ids);
        }

        $query->with($with);

        $perPage = ! empty(request()->input('per_page')) ? request()->input('per_page') : $this->perPage;
        if ($perPage == -1) {
            $variations = $query->get();
        } else {
            //paginate
            $variations = $query->paginate($perPage);
            $variations->appends(request()->query());
        }

        return VariationResource::collection($variations);
    }

    /**
     * List Selling Price Group
     *
     * @response {
        "data": [
            {
                "id": 1,
                "name": "Retail",
                "description": null,
                "business_id": 1,
                "is_active": 1,
                "deleted_at": null,
                "created_at": "2020-10-21 04:30:06",
                "updated_at": "2020-11-16 18:23:15"
            },
            {
                "id": 2,
                "name": "Wholesale",
                "description": null,
                "business_id": 1,
                "is_active": 1,
                "deleted_at": null,
                "created_at": "2020-10-21 04:30:21",
                "updated_at": "2020-11-16 18:23:00"
            }
        ]
    }
     */
    public function getSellingPriceGroup()
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->get();

        return CommonResource::collection($price_groups);
    }
}
