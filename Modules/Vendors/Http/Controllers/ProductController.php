<?php

namespace Modules\Vendors\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Vendors\Services\VendorService;

class ProductController extends Controller
{
    protected $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    /**
     * Get all products with filters, search, and pagination
     * 
     * Query Parameters:
     * - search: string (search by name or SKU)
     * - name: string (filter by name)
     * - sku: string (filter by SKU)
     * - category_id: integer
     * - sub_category_id: integer
     * - brand_id: integer
     * - unit_id: integer
     * - business_id: integer
     * - is_inactive: 0|1
     * - not_for_selling: 0|1
     * - type: string (product type)
     * - tax: integer
     * - product_custom_field1-4: string
     * - per_page: integer (default: 15)
     * - paginate: false (to get all without pagination)
     */
    public function index(Request $request): JsonResponse
    {
        $products = $this->vendorService->getAllVendors($request->all());
        return response()->json($products);
    }

    /**
     * Search products by name or SKU
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'per_page']);
        $filters['paginate'] = $request->boolean('paginate', true);
        
        $products = $this->vendorService->getAllVendors($filters);
        return response()->json($products);
    }

    /**
     * Filter products by category
     */
    public function byCategory(Request $request, $categoryId): JsonResponse
    {
        $filters = [
            'category_id' => $categoryId,
            'per_page' => $request->input('per_page', 15),
        ];
        
        $products = $this->vendorService->getAllVendors($filters);
        return response()->json($products);
    }

    /**
     * Filter products by brand
     */
    public function byBrand(Request $request, $brandId): JsonResponse
    {
        $filters = [
            'brand_id' => $brandId,
            'per_page' => $request->input('per_page', 15),
        ];
        
        $products = $this->vendorService->getAllVendors($filters);
        return response()->json($products);
    }

    /**
     * Filter products by business
     */
    public function byBusiness(Request $request, $businessId): JsonResponse
    {
        $filters = [
            'business_id' => $businessId,
            'per_page' => $request->input('per_page', 15),
        ];
        
        $products = $this->vendorService->getAllVendors($filters);
        return response()->json($products);
    }

    /**
     * Get active products only
     */
    public function active(Request $request): JsonResponse
    {
        $filters = [
            'is_inactive' => 0,
            'per_page' => $request->input('per_page', 15),
        ];
        
        $products = $this->vendorService->getAllVendors($filters);
        return response()->json($products);
    }

    /**
     * Get inactive products only
     */
    public function inactive(Request $request): JsonResponse
    {
        $filters = [
            'is_inactive' => 1,
            'per_page' => $request->input('per_page', 15),
        ];
        
        $products = $this->vendorService->getAllVendors($filters);
        return response()->json($products);
    }

    /**
     * Get products for sale
     */
    public function forSale(Request $request): JsonResponse
    {
        $filters = [
            'not_for_selling' => 0,
            'per_page' => $request->input('per_page', 15),
        ];
        
        $products = $this->vendorService->getAllVendors($filters);
        return response()->json($products);
    }
}
