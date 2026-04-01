<?php

namespace Modules\Vendors\Services;

use App\Category;
use App\Product;
use App\Warranty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Connector\Transformers\CommonResource;
use Modules\Vendors\Entities\VendorsProduct;

class VendorService
{
    public function GetAllproducts(array $filters = [])
    {
        $query = Product::query();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['sku'])) {
            $query->where('sku', 'like', '%' . $filters['sku'] . '%');
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['sub_category_id'])) {
            $query->where('sub_category_id', $filters['sub_category_id']);
        }

        if (isset($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (isset($filters['unit_id'])) {
            $query->where('unit_id', $filters['unit_id']);
        }

        if (isset($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        if (isset($filters['is_inactive'])) {
            $query->where('is_inactive', $filters['is_inactive']);
        }

        if (isset($filters['not_for_selling'])) {
            $query->where('not_for_selling', $filters['not_for_selling']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['tax'])) {
            $query->where('tax', $filters['tax']);
        }

        if (isset($filters['product_custom_field1'])) {
            $query->where('product_custom_field1', 'like', '%' . $filters['product_custom_field1'] . '%');
        }
        if (isset($filters['product_custom_field2'])) {
            $query->where('product_custom_field2', 'like', '%' . $filters['product_custom_field2'] . '%');
        }
        if (isset($filters['product_custom_field3'])) {
            $query->where('product_custom_field3', 'like', '%' . $filters['product_custom_field3'] . '%');
        }
        if (isset($filters['product_custom_field4'])) {
            $query->where('product_custom_field4', 'like', '%' . $filters['product_custom_field4'] . '%');
        }

        if (isset($filters['car_brand_id']) || isset($filters['car_model_id']) || isset($filters['model_id']) || isset($filters['car_year']) || isset($filters['year'])) {
            $query->whereExists(function ($q) use ($filters) {
                $q->select(DB::raw(1))
                  ->from('product_compatibility')
                  ->whereColumn('product_compatibility.product_id', 'products.id');
                
                if (isset($filters['car_brand_id'])) {
                    $q->where('product_compatibility.brand_category_id', $filters['car_brand_id']);
                }
                
                if (isset($filters['car_model_id']) || isset($filters['model_id'])) {
                    $modelId = $filters['car_model_id'] ?? $filters['model_id'];
                    $q->where('product_compatibility.model_id', $modelId);
                }
                
                if (isset($filters['car_year']) || isset($filters['year'])) {
                    $year = $filters['car_year'] ?? $filters['year'];
                    $q->where('product_compatibility.from_year', '<=', $year)
                      ->where('product_compatibility.to_year', '>=', $year);
                }
            });
        }

        $withRelations = ['brand', 'unit', 'category', 'warranty'];
        if (isset($filters['with']) && is_array($filters['with'])) {
            $withRelations = array_merge($withRelations, $filters['with']);
        }
        $query->with($withRelations);

        $perPage = $filters['per_page'] ?? 15;
        if (isset($filters['paginate']) && $filters['paginate'] === false) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    public function getVendorById($id)
    {
        return VendorsProduct::with(['product', 'warranty'])->where('product_id', $id)->firstOrFail();
    }
    public function GetAllVendorProductsById($vendorId, array $filters = [])
    {
        
        $query = VendorsProduct::with(['product.brand', 'product.category', 'product.unit', 'warranty', 'country'])
            ->where('Vendor_id', $vendorId);
        
        $hasProductFilters = isset($filters['search']) || isset($filters['name']) || isset($filters['sku']) ||
            isset($filters['category_id']) || isset($filters['sub_category_id']) || isset($filters['brand_id']) ||
            isset($filters['unit_id']) || isset($filters['business_id']) || isset($filters['is_inactive']) ||
            isset($filters['not_for_selling']) || isset($filters['type']) || isset($filters['tax']) ||
            isset($filters['product_custom_field1']) || isset($filters['product_custom_field2']) ||
            isset($filters['product_custom_field3']) || isset($filters['product_custom_field4']) ||
            isset($filters['car_brand_id']) || isset($filters['car_model_id']) || isset($filters['model_id']) ||
            isset($filters['car_year']) || isset($filters['year']);
        
        if ($hasProductFilters) {
            $query->whereHas('product', function ($q) use ($filters) {
                if (isset($filters['search'])) {
                    $search = $filters['search'];
                    $q->where(function ($subQ) use ($search) {
                        $subQ->where('name', 'like', '%' . $search . '%')
                             ->orWhere('sku', 'like', '%' . $search . '%');
                    });
                }
                
                if (isset($filters['name'])) {
                    $q->where('name', 'like', '%' . $filters['name'] . '%');
                }
                
                if (isset($filters['sku'])) {
                    $q->where('sku', 'like', '%' . $filters['sku'] . '%');
                }
                
                if (isset($filters['category_id'])) {
                    $q->where('category_id', $filters['category_id']);
                }
                
                if (isset($filters['sub_category_id'])) {
                    $q->where('sub_category_id', $filters['sub_category_id']);
                }
                
                if (isset($filters['brand_id'])) {
                    $q->where('brand_id', $filters['brand_id']);
                }
                
                if (isset($filters['unit_id'])) {
                    $q->where('unit_id', $filters['unit_id']);
                }
                
                if (isset($filters['business_id'])) {
                    $q->where('business_id', $filters['business_id']);
                }
                
                if (isset($filters['is_inactive'])) {
                    $q->where('is_inactive', $filters['is_inactive']);
                }
                
                if (isset($filters['not_for_selling'])) {
                    $q->where('not_for_selling', $filters['not_for_selling']);
                }
                
                if (isset($filters['type'])) {
                    $q->where('type', $filters['type']);
                }
                
                if (isset($filters['tax'])) {
                    $q->where('tax', $filters['tax']);
                }
                
                if (isset($filters['product_custom_field1'])) {
                    $q->where('product_custom_field1', 'like', '%' . $filters['product_custom_field1'] . '%');
                }
                if (isset($filters['product_custom_field2'])) {
                    $q->where('product_custom_field2', 'like', '%' . $filters['product_custom_field2'] . '%');
                }
                if (isset($filters['product_custom_field3'])) {
                    $q->where('product_custom_field3', 'like', '%' . $filters['product_custom_field3'] . '%');
                }
                if (isset($filters['product_custom_field4'])) {
                    $q->where('product_custom_field4', 'like', '%' . $filters['product_custom_field4'] . '%');
                }
                
                if (isset($filters['car_brand_id']) || isset($filters['car_model_id']) || isset($filters['model_id']) ||
                    isset($filters['car_year']) || isset($filters['year'])) {
                    $q->whereExists(function ($subQuery) use ($filters) {
                        $subQuery->select(DB::raw(1))
                            ->from('product_compatibility')
                            ->whereColumn('product_compatibility.product_id', 'products.id');
                        
                        if (isset($filters['car_brand_id'])) {
                            $subQuery->where('product_compatibility.brand_category_id', $filters['car_brand_id']);
                        }
                        
                        if (isset($filters['car_model_id']) || isset($filters['model_id'])) {
                            $modelId = $filters['car_model_id'] ?? $filters['model_id'];
                            $subQuery->where('product_compatibility.model_id', $modelId);
                        }
                        
                        if (isset($filters['car_year']) || isset($filters['year'])) {
                            $year = $filters['car_year'] ?? $filters['year'];
                            $subQuery->where('product_compatibility.from_year', '<=', $year)
                                ->where('product_compatibility.to_year', '>=', $year);
                        }
                    });
                }
            });
        }
        
        $vendorProducts = $query->get();
        
        $products = $vendorProducts->map(function ($vendorProduct) {
            $product = $vendorProduct->product;
            
            if (!$product) {
                return null;
            }
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'image_url' => $product->image_url,
                'type' => $product->type,
                'unit_id' => $product->unit_id,
                'brand_id' => $product->brand_id,
                'category_id' => $product->category_id,
                'sub_category_id' => $product->sub_category_id,
                'tax' => $product->tax,
                'enable_stock' => $product->enable_stock,
                'alert_quantity' => $product->alert_quantity,
                'product_description' => $product->product_description,
                'product_condition' => $product->product_condition,
                'is_inactive' => $product->is_inactive,
                // Relations
                'brand' => $product->brand,
                'category' => $product->category,
                'unit' => $product->unit,
                // Vendor product data
                'vendor_product_price' => $vendorProduct->Product_price,
                'vendor_shipping_info' => $vendorProduct->shipping_information,
                'vendor_return_policy' => $vendorProduct->Return_policy,
                'country_id' => $vendorProduct->country_id,
                'country' => $vendorProduct->country,
                // Warranty info
                'warranty' => $vendorProduct->warranty,
                'warranty_id' => $vendorProduct->warranty_id,
            ];
        })->filter();
        
        return $products->values()->all();
    }

    public function getAllWarranties($businessId = null)
    {
        $query = Warranty::query();
        
        if ($businessId) {
            $query->where('business_id', $businessId);
        }
        
        return $query->get();
    }
    
public function createProductByVendor(array $data)
    {
        $productId = $data['product_id'] ?? null;
        
        $productByVendor = VendorsProduct::create($data);
        
        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $productData = [];
                if (isset($data['product_specifications'])) {
                    $productData['product_specifications'] = $data['product_specifications'];
                }
                if (isset($data['key_features'])) {
                    $productData['key_features'] = $data['key_features'];
                }
                    $productData['product_condition'] = "InReview";
                if (!empty($productData)) {
                    $product->update($productData);
                }
            }
        }
        
        return $productByVendor;
    }
public function storeNewProductByVendor(array $data)
    {
        $productFields = ['name', 'sku', 'brand_id', 'category_id', 'sub_category_id', 'unit_id', 
                          'type', 'tax_type', 'tax', 'price', 'enable_stock', 'alert_quantity',
                          'product_description', 'product_specifications', 'key_features', 'manufacturing_year'];
        
        $productData = [];
        foreach ($productFields as $field) {
            if (isset($data[$field])) {
                $productData[$field] = $data[$field];
            }
        }
        
        $productData['product_condition'] = 'UnderReview';
        $productData['business_id'] = $data['business_id'] ?? 1;
        $productData['created_by'] = $data['created_by'] ?? 1;
        
        // Handle product images - save to storage
        $imagePaths = [];
        if (isset($data['image']) && is_array($data['image'])) {
            foreach ($data['image'] as $index => $imageFile) {
                if ($imageFile && is_object($imageFile) && method_exists($imageFile, 'getClientOriginalName')) {
                    // Generate unique filename
                    $fileName = time() . '_' . $index . '_' . $imageFile->getClientOriginalName();
                    // Store in public/products folder
                    $filePath = $imageFile->storeAs('products', $fileName, 'public');
                    if ($filePath) {
                        $imagePaths[] = $filePath;
                    }
                }
            }
        }
        
        if (!empty($imagePaths)) {
            $productData['image'] = $imagePaths[0];
        }
        
        $product = Product::create($productData);
        
        if (count($imagePaths) > 0) {
            foreach ($imagePaths as $imagePath) {
                \App\Media::create([
                    'business_id' => $productData['business_id'],
                    'file_name' => $imagePath,
                    'uploaded_by' => $productData['created_by'],
                    'model_id' => $product->id,
                    'model_type' => 'App\Product',
                ]);
            }
        }
        
        $vendorData = [
            'product_id' => $product->id,
            'Vendor_id' => $data['Vendor_id'] ?? $data['vendor_id'] ?? null,
            'Product_price' => $data['price'] ?? 0,
            'warranty_id' => $data['warranty_id'] ?? null,
            'country_id' => $data['country_id'] ?? null,
        ];
        
        if (isset($data['shipping_information'])) {
            $vendorData['shipping_information'] = $data['shipping_information'];
        }
        if (isset($data['Return_policy'])) {
            $vendorData['Return_policy'] = $data['Return_policy'];
        }
        
        $productByVendor = VendorsProduct::create($vendorData);
        
        return $productByVendor->load('product');
    }

    public function updateProductByVendor($id, array $data)
    {
        $productByVendor = VendorsProduct::where('product_id', $id)->firstOrFail();
        $productId = $data['product_id'] ?? $productByVendor->product_id;
        
        $productByVendor->update($data);
        
        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $productData = [];
                if (isset($data['product_specifications'])) {
                    $productData['product_specifications'] = $data['product_specifications'];
                }
                if (isset($data['key_features'])) {
                    $productData['key_features'] = $data['key_features'];
                }
                if (isset($data['product_condition'])) {
                    $productData['product_condition'] = $data['product_condition'];
                }
                if (!empty($productData)) {
                    $product->update($productData);
                }
            }
        }
        
        return $productByVendor;
    }

    public function deleteProductByVendor($id)
    {
        $productByVendor = VendorsProduct::where('product_id', $id)->firstOrFail();
        return $productByVendor->delete();
    }
        public function getProductCompatibility($id)
    {
        try {
            $product = Product::findOrFail($id);

            $compatibilities = \App\ProductCompatibility::where('product_id', $id)
                ->with(['deviceModel', 'brandCategory'])
                ->get()
                ->map(function ($compat) {
                    return [
                        'id' => $compat->id,
                        'brand_category_id' => $compat->brand_category_id,
                        'brand_category_name' => $compat->brandCategory ? $compat->brandCategory->name : '',
                        'model_id' => $compat->model_id,
                        'model_name' => $compat->deviceModel ? $compat->deviceModel->name : '',
                        'from_year' => $compat->from_year,
                        'to_year' => $compat->to_year,
                    ];
                });

            return response()->json(['success' => true, 'data' => $compatibilities]);
        } catch (\Exception $e) {
            Log::error('Error getting product compatibility: ' . $e->getMessage());
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }
    
    public function getCarModelsByBrand($brandId)
    {
          try {
            if (empty($brandId)) {
                return response()->json([]);
            }
            
            $models = DB::table('repair_device_models')
                ->where('device_id', $brandId)
                ->orderBy('name')
                ->select('id', 'name')
                ->get();
            
            return response()->json($models);
        } catch (\Exception $e) {
            Log::error('Error getting car models by brand: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
    
    public function getBrands()
    {
        try {
            $brands = DB::table('categories')
                ->where('parent_id', 0)
                ->where('category_type', 'device')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json($brands);
        } catch (\Exception $e) {
            Log::error('Error fetching brands: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch brands'], 500);
        }
    }
    
    public function getYears()
    {
        $currentYear = date('Y');
        $years = range(1990, $currentYear + 1);
        
        return response()->json([
            'years' => array_values($years),
            'min_year' => 1990,
            'max_year' => $currentYear + 1
        ]);
    }
    
    public function getCountries()
    {
        try {
            $countries = DB::table('Country_of_Origin')
                ->select('id', 'name', 'created_at', 'updated_at')
                ->orderBy('name')
                ->get();

            return response()->json($countries);
        } catch (\Exception $e) {
            Log::error('Error fetching countries: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch countries'], 500);
        }
    }
    
    public function getCategories()
    {
        try {
            $categories = DB::table('categories')
                ->where('category_type', 'product')
                ->select('id', 'name', 'parent_id', 'short_code', 'category_type')
                ->orderBy('name')
                ->get();

            return response()->json($categories);
        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch categories'], 500);
        }
    }
    
    public function getUnits()
    {
        try {
            $units = DB::table('units')
                ->select('id', 'actual_name', 'short_name', 'base_unit_id', 'base_unit_multiplier')
                ->orderBy('actual_name')
                ->get();

            return response()->json($units);
        } catch (\Exception $e) {
            Log::error('Error fetching units: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch units'], 500);
        }
    }
    

          public function getSubcategories($category_id)
    {

        $parent_category = Category::where('id', $category_id) ->first();

        if (!$parent_category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $query = Category::where('parent_id', $category_id);

        $name = request()->input('name');
        if (! empty($name)) {
            $query->where('name', 'like', '%'.$name.'%');
        }

        $subcategories = $query->paginate(10);

        return CommonResource::collection($subcategories);
    }

    public function getProductsByCategoryId($category_id, array $validated)
    {
        $businessId = (int) $validated['business_id'];
        $perPage = $validated['per_page'] ?? 20;

        $query = Product::query()
            ->with(['category', 'sub_category', 'sub_sub_category', 'sub_sub_sub_category'])
            ->where('products.business_id', $businessId)
            ->where('products.virtual_product', 0)
            ->where(function ($q) use ($category_id) {
                $q->where('products.category_id', $category_id)
                    ->orWhere('products.sub_category_id', $category_id)
                    ->orWhere('products.sub_sub_category_id', $category_id)
                    ->orWhere('products.sub_sub_sub_category_id', $category_id);
            })
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
            ]);

        if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'shipping_details')) {
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

        if (!empty($validated['brand_id'])) {
            $query->where('products.brand_id', $validated['brand_id']);
        }

        if (!empty($validated['car_brand_id']) || !empty($validated['car_year'])) {
            $query->whereExists(function ($q) use ($validated) {
                $q->select(DB::raw(1))
                    ->from('product_compatibility')
                    ->whereColumn('product_compatibility.product_id', 'products.id');

                if (!empty($validated['car_brand_id'])) {
                    $q->where('product_compatibility.model_id', $validated['car_brand_id']);
                }

                if (!empty($validated['car_year'])) {
                    $q->where('product_compatibility.from_year', '<=', $validated['car_year'])
                        ->where('product_compatibility.to_year', '>=', $validated['car_year']);
                }
            });
        }

        if (!empty($validated['q'])) {
            $search = trim($validated['q']);
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        $products = $query->orderByDesc('products.id')->paginate($perPage);

        $products->setCollection($products->getCollection()->map(function ($product) use ($category_id) {
            $matchedLevel = null;
            if ($product->category_id == $category_id) {
                $matchedLevel = 'category';
            } elseif ($product->sub_category_id == $category_id) {
                $matchedLevel = 'sub_category';
            } elseif ($product->sub_sub_category_id == $category_id) {
                $matchedLevel = 'sub_sub_category';
            } elseif ($product->sub_sub_sub_category_id == $category_id) {
                $matchedLevel = 'sub_sub_sub_category';
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'variation_id' => $product->variation_id,
                'default_sell_price' => (float) ($product->default_sell_price ?? 0),
                'brand_id' => $product->brand_id,
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name,
                'sub_category_id' => $product->sub_category_id,
                'sub_category_name' => $product->sub_category?->name,
                'sub_sub_category_id' => $product->sub_sub_category_id,
                'sub_sub_category_name' => $product->sub_sub_category?->name,
                'sub_sub_sub_category_id' => $product->sub_sub_sub_category_id,
                'sub_sub_sub_category_name' => $product->sub_sub_sub_category?->name,
                'matched_category_level' => $matchedLevel,
                'description' => strip_tags($product->product_description),
                'image_url' => $product->image_url,
            ];
        }));

        return $products;
    }
}
