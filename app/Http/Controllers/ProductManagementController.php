<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductManagementController extends Controller
{
    /**
     * Display the product management cards view.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Pass permission checks to the view
        $permissions = [
            'canViewProducts' => auth()->user()->can('product.view'),
            'canCreateProducts' => auth()->user()->can('product.create'),
            'canViewBrands' => auth()->user()->can('brand.view') || auth()->user()->can('brand.create'),
            'canViewUnits' => auth()->user()->can('unit.view') || auth()->user()->can('unit.create'),
            'canViewCategories' => auth()->user()->can('category.view') || auth()->user()->can('category.create'),
            'canViewServices' => auth()->user()->can('product.view') || auth()->user()->can('product.create'),
        ];

        return view('core_product_management.index', compact('permissions'));
    }
}
