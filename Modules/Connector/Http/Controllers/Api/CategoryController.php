<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Category;
use App\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Connector\Transformers\CommonResource;

/**
 * @group Taxonomy management
 * @authenticated
 *
 * APIs for managing taxonomies
 */
class CategoryController extends ApiController
{
    /**
     * List taxonomy
     *
     * @queryParam type Type of taxonomy (product, device, hrm_department)
     *
     * @response {
            "data": [
                {
                    "id": 1,
                    "name": "Men's",
                    "business_id": 1,
                    "short_code": null,
                    "parent_id": 0,
                    "created_by": 1,
                    "category_type": "product",
                    "description": null,
                    "slug": null,
                    "woocommerce_cat_id": null,
                    "deleted_at": null,
                    "created_at": "2018-01-03 21:06:34",
                    "updated_at": "2018-01-03 21:06:34",
                    "sub_categories": [
                        {
                            "id": 4,
                            "name": "Jeans",
                            "business_id": 1,
                            "short_code": null,
                            "parent_id": 1,
                            "created_by": 1,
                            "category_type": "product",
                            "description": null,
                            "slug": null,
                            "woocommerce_cat_id": null,
                            "deleted_at": null,
                            "created_at": "2018-01-03 21:07:34",
                            "updated_at": "2018-01-03 21:07:34"
                        },
                        {
                            "id": 5,
                            "name": "Shirts",
                            "business_id": 1,
                            "short_code": null,
                            "parent_id": 1,
                            "created_by": 1,
                            "category_type": "product",
                            "description": null,
                            "slug": null,
                            "woocommerce_cat_id": null,
                            "deleted_at": null,
                            "created_at": "2018-01-03 21:08:18",
                            "updated_at": "2018-01-03 21:08:18"
                        }
                    ]
                }
            ]
        }
     */
    
    public function index()
    {
        $user = Auth::user();

        $location_id = $user->location_id;

        $name = request()->input('name');
        $type = request()->input('type');
        $is_ecom = request()->input('is_ecom');

        $query = Category::onlyParent()
                        ->with('sub_categories');

        if (! empty($name)) {
            $query->where('name', 'like', '%'.$name.'%');
        }

        if (! empty($type)) {
            $query->where('category_type', $type);

            // For product type categories, only return those with associated products

        }

        if (! empty($is_ecom)) {
            $query->where('is_ecom', $is_ecom);
        }

        $categories = $query->paginate(10);

        return CommonResource::collection($categories);
    }

    // public function index()
    // {
    //     $user = Auth::user();

    //     $business_id = $user->business_id;

    //     $query = Category::where('business_id', $business_id)
    //                     ->onlyParent()
    //                     ->with('sub_categories');

    //     if (! empty(request()->input('type'))) {
    //         $query->where('category_type', request()->input('type'));
    //     }

    //     $categories = $query->get();

    //     return CommonResource::collection($categories);
    // }
    /**
     * Get the specified taxonomy
     *
     * @urlParam taxonomy required comma separated ids of product categories Example: 1

     * @response {
            "data": [
                {
                    "id": 1,
                    "name": "Men's",
                    "business_id": 1,
                    "short_code": null,
                    "parent_id": 0,
                    "created_by": 1,
                    "category_type": "product",
                    "description": null,
                    "slug": null,
                    "woocommerce_cat_id": null,
                    "deleted_at": null,
                    "created_at": "2018-01-03 21:06:34",
                    "updated_at": "2018-01-03 21:06:34",
                    "sub_categories": [
                        {
                            "id": 4,
                            "name": "Jeans",
                            "business_id": 1,
                            "short_code": null,
                            "parent_id": 1,
                            "created_by": 1,
                            "category_type": "product",
                            "description": null,
                            "slug": null,
                            "woocommerce_cat_id": null,
                            "deleted_at": null,
                            "created_at": "2018-01-03 21:07:34",
                            "updated_at": "2018-01-03 21:07:34"
                        },
                        {
                            "id": 5,
                            "name": "Shirts",
                            "business_id": 1,
                            "short_code": null,
                            "parent_id": 1,
                            "created_by": 1,
                            "category_type": "product",
                            "description": null,
                            "slug": null,
                            "woocommerce_cat_id": null,
                            "deleted_at": null,
                            "created_at": "2018-01-03 21:08:18",
                            "updated_at": "2018-01-03 21:08:18"
                        }
                    ]
                }
            ]
        }
     */
    public function show($category_ids)
    {
        $user = Auth::user();

        $business_id = $user->business_id;
        $category_ids = explode(',', $category_ids);

        $categories = Category::where('business_id', $business_id)
                        ->whereIn('id', $category_ids)
                        ->with('sub_categories')
                        ->get();

        return CommonResource::collection($categories);
    }
    /**
     * Get subcategories by category ID
     *
     * @urlParam category_id required ID of the parent category Example: 1
     * @queryParam name Search subcategories by name
     *
     * @response {
     *     "data": [
     *         {
     *             "id": 4,
     *             "name": "Jeans",
     *             "business_id": 1,
     *             "short_code": null,
     *             "parent_id": 1,
     *             "created_by": 1,
     *             "category_type": "product",
     *             "description": null,
     *             "slug": null,
     *             "woocommerce_cat_id": null,
     *             "deleted_at": null,
     *             "created_at": "2018-01-03 21:07:34",
     *             "updated_at": "2018-01-03 21:07:34"
     *         },
     *         {
     *             "id": 5,
     *             "name": "Shirts",
     *             "business_id": 1,
     *             "short_code": null,
     *             "parent_id": 1,
     *             "created_by": 1,
     *             "category_type": "product",
     *             "description": null,
     *             "slug": null,
     *             "woocommerce_cat_id": null,
     *             "deleted_at": null,
     *             "created_at": "2018-01-03 21:08:18",
     *             "updated_at": "2018-01-03 21:08:18"
     *         }
     *     ]
     * }
     */
    public function getSubcategories($category_id)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        // Validate that the parent category exists and belongs to the user's business
        $parent_category = Category::where('id', $category_id)

                                 ->first();

        if (!$parent_category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        // Get subcategories for the given category ID with pagination
        $query = Category::where('parent_id', $category_id);

        // Add name search functionality
        $name = request()->input('name');
        if (! empty($name)) {
            $query->where('name', 'like', '%'.$name.'%');
        }

        $subcategories = $query->paginate(10);

        return CommonResource::collection($subcategories);
    }
}
