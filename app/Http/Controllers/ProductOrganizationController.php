<?php

namespace App\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductOrganizationController extends Controller
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
        $cards[] = [
            'title' => __('lang_v1.warranties'),
            'description' => __('product_extensions.warranties_description'),
            'icon' => 'fas fa-shield-alt',
            'color' => 'primary',
            'route' => action([\App\Http\Controllers\WarrantyController::class, 'index']),
        ];

        // Service Packages Card
        $cards[] = [
            'title' => __('product_extensions.service_packages_title'),
            'description' => __('product_extensions.service_packages_description'),
            'icon' => 'fas fa-boxes',
            'color' => 'success',
            'route' => action([\App\Http\Controllers\ServicePackageController::class, 'index']),
        ];

        // Stock Adjustments Card
        $cards[] = [
            'title' => __('stock_adjustment.list'),
            'description' => __('product_extensions.stock_adjustments_description'),
            'icon' => 'fas fa-clipboard-list',
            'color' => 'warning',
            'route' => action([\App\Http\Controllers\StockAdjustmentController::class, 'index']),
        ];

        // Exit Product Card
        $cards[] = [
            'title' => __('product.Exit Product'),
            'description' => __('product_extensions.exit_permissions_description'),
            'icon' => 'fas fa-door-open',
            'color' => 'info',
            'route' => action([\App\Http\Controllers\ProductController::class, 'viewexitpermission']),
        ];

        // Inventory Delivery Returns Card
        $cards[] = [
            'title' => __('inventory_delivery_returns.card_title'),
            'description' => __('inventory_delivery_returns.card_description'),
            'icon' => 'fas fa-undo',
            'color' => 'danger',
            'route' => action([\App\Http\Controllers\InventoryDeliveryReturnController::class, 'index']),
        ];

        return view('product_organization.index', compact('cards'));
    }
}
