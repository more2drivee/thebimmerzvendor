<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProductsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $location_id = $request->query('location');
        $user = Auth::user();
        $business_id = $user->business_id;

        $isAdmin = $user->hasRole('Admin#' . $business_id) || $user->can('superadmin');
        $permitted_locations = $user->permitted_locations($business_id);

        if($location_id == Null){
            $location_id = $user->location_id;
        }

        if (!$isAdmin && $permitted_locations != 'all') {
            if (!in_array($location_id, $permitted_locations)) {
                $location_id = $permitted_locations[0] ?? $user->location_id;
            }
        }

        $locations = DB::table('business_locations')
            ->select('id', 'name')
            ->where('business_id', $business_id)
            ->when($permitted_locations != 'all', function($q) use ($permitted_locations) {
                return $q->whereIn('id', $permitted_locations);
            })
            ->get();
        
        // Statistics counters (filtered by location)
        $counters = [
            __('product.total_products') => [
                'data' => DB::table('products as p')
                    ->where('p.business_id', $business_id)
                    ->where('p.enable_stock', 1)
                    ->where('p.is_inactive', 0)
                    ->distinct('p.id')
                    ->count('p.id'),
                    'icon' => 'fa fa-box'
                ],
                __('product.total_services') => [
                    'data' => DB::table('products as p')
                    ->join('product_locations as pl', 'p.id', '=', 'pl.product_id')
                    ->where('p.business_id', $business_id)
                    ->where('p.virtual_product', 0)
                    ->where('p.enable_stock', 0)
                    ->where('p.is_client_flagged', 0)
                    ->where('p.is_inactive', 0)
                    ->where('pl.location_id', $location_id)
                    ->count('p.id'),
                'icon' => 'fa fa-wrench'
            ],
            __('product.total_brands') => [
                'data' => DB::table('brands')
                    ->where('business_id', $business_id)
                    ->count(),
                'icon' => 'fa fa-tag'
            ],
            __('product.total_categories') => [
                'data' => DB::table('categories')
                    ->where('business_id', $business_id)
                    ->where('category_type', 'product')
                    ->count(),
                'icon' => 'fa fa-folder'
            ],
            __('product.total_models') => [
                'data' => DB::table('product_compatibility')
                    ->distinct('model_id')
                    ->count('model_id'),
                'icon' => 'fa fa-cogs'
            ]
        ];

        // Products by category (filtered by location)
        $products_by_category = DB::table('categories as c')
            ->leftJoin('products as p', function($join) {
                $join->on('c.id', '=', 'p.category_id')
                     ->where('p.is_inactive', 0);
            })
            ->leftJoin('variations as v', 'p.id', '=', 'v.product_id')
            ->leftJoin('variation_location_details as vld', function($join) use ($location_id) {
                $join->on('v.id', '=', 'vld.variation_id')
                     ->where('vld.location_id', '=', $location_id);
            })
            ->where('c.business_id', $business_id)
            ->where('c.category_type', 'product')
            ->where(function($q) use ($location_id) {
                $q->whereNull('p.id')
                  ->orWhereNotNull('vld.id');
            })
            ->groupBy('c.id', 'c.name')
            ->select(
                'c.name',
                DB::raw('COUNT(DISTINCT p.id) as product_count')
            )
            ->orderBy('product_count', 'desc')
            ->limit(10)
            ->get();

        // Stock alerts by selected location
        $stock_alerts = [];
        $selected_location = $locations->firstWhere('id', $location_id);
        if ($selected_location) {
            $alert_count = DB::table('variations as v')
                ->join('products as p', 'v.product_id', '=', 'p.id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->leftJoin('variation_location_details as vld', function($join) use ($location_id) {
                    $join->on('v.id', '=', 'vld.variation_id')
                         ->where('vld.location_id', '=', $location_id);
                })
                ->where('p.business_id', $business_id)
                ->where('p.enable_stock', 1)
                ->where('p.is_inactive', 0)
                ->where('vld.qty_available', '<=', 0)
                ->count();
            
            $stock_alerts[] = [
                'location' => $selected_location->name,
                'count' => $alert_count
            ];
        }

        // Top selling products (last 30 days)
        $top_products = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.is_direct_sale', 0)
            ->where('p.is_inactive', 0)
            ->when($location_id, function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->whereDate('t.transaction_date', '>=', Carbon::now()->subDays(30))
            ->groupBy('p.id', 'p.name')
            ->select(
                'p.name',
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_revenue')
            )
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();

        // Products by category
        $products_by_category = DB::table('categories as c')
            ->leftJoin('products as p', function($join) {
                $join->on('c.id', '=', 'p.category_id')
                     ->where('p.is_inactive', 0);
            })
            ->where('c.business_id', $business_id)
            ->where('c.category_type', 'product')
            ->groupBy('c.id', 'c.name')
            ->select(
                'c.name',
                DB::raw('COUNT(p.id) as product_count')
            )
            ->orderBy('product_count', 'desc')
            ->limit(10)
            ->get();

        // Recent stock adjustments (last 7 days)
        $recent_adjustments = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'stock_adjustment')
            ->where('p.is_inactive', 0)
            ->when($location_id, function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->whereDate('t.transaction_date', '>=', Carbon::now()->subDays(7))
            ->select(
                't.transaction_date',
                'p.name as product_name',
                'tsl.quantity',
                't.invoice_no',
                't.additional_notes'
            )
            ->orderBy('t.transaction_date', 'desc')
            ->limit(10)
            ->get();

        // Low stock products
        $low_stock_products = DB::table('variation_location_details as vld')
            ->join('product_variations as pv', 'vld.product_variation_id', '=', 'pv.id')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('products as p', 'vld.product_id', '=', 'p.id')
            ->leftJoin('business_locations as l', 'vld.location_id', '=', 'l.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('p.business_id', $business_id)
            ->where('p.enable_stock', 1)
            ->where('p.is_inactive', 0)
            ->whereNull('v.deleted_at')
            ->whereNotNull('p.alert_quantity')
            ->whereRaw('vld.qty_available <= p.alert_quantity')
            ->when($location_id, function($q) use ($location_id) {
                return $q->where('vld.location_id', $location_id);
            })
            ->select(
                'p.name as product',
                'p.type',
                'p.sku',
                'p.alert_quantity',
                'pv.name as product_variation',
                'v.name as variation',
                'v.sub_sku',
                'l.name as location',
                'vld.qty_available as stock',
                'u.short_name as unit',
                'v.id as variation_id',
                'p.id as product_id'
            )
            ->orderBy('vld.qty_available', 'asc')
            ->limit(10)
            ->get();

        return view('products.dashboard.index', compact(
            'counters',
            'locations',
            'location_id',
            'stock_alerts',
            'top_products',
            'products_by_category',
            'recent_adjustments',
            'low_stock_products',
            'isAdmin'
        ));
    }
}
