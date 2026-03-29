<?php

namespace App\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductExtensionsController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        $user = auth()->user();

        $cards = [];

        // Warranties Card
        if ($user->can('product.view') || $user->can('product.create')) {
            $cards[] = [
                'title' => __('lang_v1.warranties'),
                'description' => __('product_extensions.warranties_description'),
                'icon' => 'fas fa-shield-alt',
                'color' => 'primary',
                'route' => action([\App\Http\Controllers\WarrantyController::class, 'index']),
                'permission' => 'product.view'
            ];
        }

        // Service Packages Card
        if ($user->can('product.view') || $user->can('product.create')) {
            $cards[] = [
                'title' => __('product_extensions.service_packages_title'),
                'description' => __('product_extensions.service_packages_description'),
                'icon' => 'fas fa-boxes',
                'color' => 'success',
                'route' => action([\App\Http\Controllers\ServicePackageController::class, 'index']),
                'permission' => 'product.view'
            ];
        }

        // Stock Adjustments Card
        if ($user->can('purchase.view') || $user->can('purchase.create') || $user->can('view_own_purchase')) {
            $cards[] = [
                'title' => __('stock_adjustment.list'),
                'description' => __('product_extensions.stock_adjustments_description'),
                'icon' => 'fas fa-clipboard-list',
                'color' => 'warning',
                'route' => action([\App\Http\Controllers\StockAdjustmentController::class, 'index']),
                'permission' => 'purchase.view'
            ];
        }

        if ($user->can('brand.view')) {
            $cards[] = [
                'title' => __('product.Exit Product'),
                'description' => __('product_extensions.exit_permissions_description'),
                'icon' => 'fas fa-door-open',
                'color' => 'info',
                'route' => action([\App\Http\Controllers\ProductController::class, 'viewexitpermission']),
                'permission' => 'brand.view'
            ];
        }

        return view('product_extensions.index', compact('cards'));
    }
}
