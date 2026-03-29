<?php

namespace App\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoreProductManagementController extends Controller
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

        // List Products Card
        if ($user->can('product.view')) {
            $cards[] = [
                'title' => __('lang_v1.list_products'),
                'description' => __('View and manage all products in your inventory'),
                'icon' => 'fas fa-boxes',
                'color' => 'primary',
                'route' => action([\App\Http\Controllers\ProductController::class, 'index']),
                'permission' => 'product.view'
            ];
        }

        // Add Product Card
        if ($user->can('product.create')) {
            $cards[] = [
                'title' => __('product.add_product'),
                'description' => __('Add new products to your inventory'),
                'icon' => 'fas fa-plus-circle',
                'color' => 'success',
                'route' => action([\App\Http\Controllers\ProductController::class, 'create']),
                'permission' => 'product.create'
            ];
        }

        // Update Product Price Card
        if ($user->can('product.create')) {
            $cards[] = [
                'title' => __('lang_v1.update_product_price'),
                'description' => __('Update selling prices for multiple products'),
                'icon' => 'fas fa-tags',
                'color' => 'warning',
                'route' => action([\App\Http\Controllers\SellingPriceGroupController::class, 'updateProductPrice']),
                'permission' => 'product.create'
            ];
        }

        // Print Labels Card
        if ($user->can('product.view')) {
            $cards[] = [
                'title' => __('barcode.print_labels'),
                'description' => __('Print barcode labels for products'),
                'icon' => 'fas fa-print',
                'color' => 'info',
                'route' => action([\App\Http\Controllers\LabelsController::class, 'show']),
                'permission' => 'product.view'
            ];
        }

        if ($user->can('product.view')) {
            $cards[] = [
                'title' => __('Under Processing Job Orders'),
                'description' => __('Manage spare parts lines for under processing repair job sheets'),
                'icon' => 'fas fa-tools',
                'color' => 'primary',
                'route' => route('product-management.underprocessing_joborders.index'),
                'permission' => 'product.view'
            ];
        }

        // Variations Card
        if ($user->can('product.create')) {
            $cards[] = [
                'title' => __('product.variations'),
                'description' => __('Manage product variations and templates'),
                'icon' => 'fas fa-layer-group',
                'color' => 'secondary',
                'route' => action([\App\Http\Controllers\VariationTemplateController::class, 'index']),
                'permission' => 'product.create'
            ];
        }

        // Import Products Card
        if ($user->can('product.create')) {
            $cards[] = [
                'title' => __('product.import_products'),
                'description' => __('Import products from CSV or Excel files'),
                'icon' => 'fas fa-file-import',
                'color' => 'dark',
                'route' => action([\App\Http\Controllers\ImportProductsController::class, 'index']),
                'permission' => 'product.create'
            ];
        }

        // Import Opening Stock Card
        if ($user->can('product.opening_stock')) {
            $cards[] = [
                'title' => __('lang_v1.import_opening_stock'),
                'description' => __('Import initial stock quantities'),
                'icon' => 'fas fa-box-open',
                'color' => 'light',
                'route' => action([\App\Http\Controllers\ImportOpeningStockController::class, 'index']),
                'permission' => 'product.opening_stock'
            ];
        }

        // Selling Price Group Card
        if ($user->can('product.create')) {
            $cards[] = [
                'title' => __('lang_v1.selling_price_group'),
                'description' => __('Manage different price groups for customers'),
                'icon' => 'fas fa-money-bill-wave',
                'color' => 'success',
                'route' => action([\App\Http\Controllers\SellingPriceGroupController::class, 'index']),
                'permission' => 'product.create'
            ];
        }

        return view('core_product_management.index', compact('cards'));
    }
}
