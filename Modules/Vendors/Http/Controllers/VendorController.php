<?php

namespace Modules\Vendors\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Vendors\Http\Requests\StoreProductByVendorRequest;
use Modules\Vendors\Http\Requests\UpdateProductByVendorRequest;
use Modules\Vendors\Services\VendorService;

class VendorController extends Controller
{
    protected $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    public function GetAllProducts(Request $request): JsonResponse
    {
        $vendors = $this->vendorService->GetAllproducts($request->all());
        return response()->json($vendors);
    }
    public function GetAllVendorProductsById(Request $request, $vendorId): JsonResponse
    {
        // Get all query params except vendorId
        $filters = $request->except(['vendorId']);
        $vendors = $this->vendorService->GetAllVendorProductsById($vendorId, $filters);
        return response()->json($vendors);
    }
    public function getAllWarranties(Request $request): JsonResponse
    {
        $warranties = $this->vendorService->getAllWarranties();
        return response()->json($warranties);
    }

    public function store(StoreProductByVendorRequest $request): JsonResponse
    {
        
        $productByVendor = $this->vendorService->createProductByVendor($request->validated());
        return response()->json($productByVendor, 201);
    }
    public function storeNewProduct(Request $request): JsonResponse
    {
        
        $productByVendor = $this->vendorService->storeNewProductByVendor($request->all());
        return response()->json($productByVendor, 201);
    }

    public function show($id): JsonResponse
    {
        $vendor = $this->vendorService->getVendorById($id);
        return response()->json($vendor);
    }

    public function update(UpdateProductByVendorRequest $request, $id): JsonResponse
    {
        $productByVendor = $this->vendorService->updateProductByVendor($id, $request->validated());
        return response()->json($productByVendor);
    }

    public function destroy($id): JsonResponse
    {
        $this->vendorService->deleteProductByVendor($id);
        return response()->json(['message' => 'Deleted successfully'], 204);
    }
    
        public function getProductCompatibility($id)
        {
            return $this->vendorService->getProductCompatibility($id);
        }
        
        public function getCarModelsByBrand(Request $request)
        {
            $brandId = $request->get('brand_id');
            return $this->vendorService->getCarModelsByBrand($brandId);
        }
        
        public function getBrands()
        {
            return $this->vendorService->getBrands();
        }
        
        public function getYears()
        {
            return $this->vendorService->getYears();
        }
        
        public function getCountries()
        {
            return $this->vendorService->getCountries();
        }
        
        public function getCategories()
        {
            return $this->vendorService->getCategories();
        }
        
        public function getUnits()
        {
            return $this->vendorService->getUnits();
        }

        public function getSubcategories($id)
        {
            return $this->vendorService->getSubcategories($id);
        }

        public function productsByCAtegoryId(Request $request, $category_id): JsonResponse
        {
            $validated = $request->validate([
                'business_id' => ['required', 'integer'],
                'brand_id' => ['nullable', 'integer'],
                'q' => ['nullable', 'string', 'max:191'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
                'car_brand_id' => ['nullable', 'integer'],
                'car_year' => ['nullable', 'integer'],
            ]);

            $products = $this->vendorService->getProductsByCategoryId($category_id, $validated);
            return response()->json($products);
        }
}
