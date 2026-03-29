<?php

namespace App\Http\Controllers;


use App\Contact;
use App\Product;
use Carbon\Carbon;
use App\Transaction;
use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class POSDashboardController extends Controller
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
        
        // Statistics counters
        $counters = [
            __('pos.total_sales') => [
                'data' => Transaction::where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->where('status', 'final')
                    ->where('is_direct_sale', 0)
                    ->when($location_id, function($q) use ($location_id) {
                        return $q->where('location_id', $location_id);
                    })
                    ->sum('final_total'),
                'icon' => 'fa fa-shopping-cart'
            ],
            __('pos.total_orders') => [
                'data' => Transaction::where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->where('status', 'final')
                    ->where('is_direct_sale', 0)
                    ->when($location_id, function($q) use ($location_id) {
                        return $q->where('location_id', $location_id);
                    })
                    ->count(),
                'icon' => 'fa fa-list'
            ],
            __('pos.total_customers') => [
                'data' => Contact::where('business_id', $business_id)
                    ->where('type', 'customer')
                    ->count(),
                'icon' => 'fa fa-users'
            ],
            __('pos.total_products') => [
                'data' => Product::where('business_id', $business_id)
                    ->where('is_inactive', 0)
                    ->count(),
                'icon' => 'fa fa-box'
            ]
        ];

        // Recent sales
        $recent_sales = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->where('is_direct_sale', 0)
            ->when($location_id, function($q) use ($location_id) {
                return $q->where('location_id', $location_id);
            })
            ->with(['contact'])
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();

        $date = Carbon::now();
        $dayNumber = $date->day;
        $daysArray = range(1, $dayNumber);
        $currentMonth = $date->month;
        $labels = range(1, 31);

        // Sales by day
        $sales_by_day = [];
        foreach ($daysArray as $day) {
            $sales_by_day[] = [
                'x' => $day,
                'y' => Transaction::where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->where('status', 'final')
                    ->where('is_direct_sale', 0)
                    ->when($location_id, function($q) use ($location_id) {
                        return $q->where('location_id', $location_id);
                    })
                    ->whereDay('transaction_date', '=', $day)
                    ->whereMonth('transaction_date', '=', $currentMonth)
                    ->sum('final_total')
            ];
        }

        // Payment type distribution
        $payment_type_chart = [
            'labels' => [
                __('pos.cash'),
                __('pos.card'),
                __('pos.bank_transfer'),
                __('pos.other')
            ],
            'data' => [
                Transaction::where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->where('status', 'final')
                    ->where('is_direct_sale', 0)
                    ->when($location_id, function($q) use ($location_id) {
                        return $q->where('location_id', $location_id);
                    })
                    ->whereHas('payment_lines', function($q) {
                        $q->where('method', 'cash');
                    })
                    ->count(),
                Transaction::where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->where('status', 'final')
                    ->where('is_direct_sale', 0)
                    ->when($location_id, function($q) use ($location_id) {
                        return $q->where('location_id', $location_id);
                    })
                    ->whereHas('payment_lines', function($q) {
                        $q->where('method', 'card');
                    })
                    ->count(),
                Transaction::where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->where('status', 'final')
                    ->where('is_direct_sale', 0)
                    ->when($location_id, function($q) use ($location_id) {
                        return $q->where('location_id', $location_id);
                    })
                    ->whereHas('payment_lines', function($q) {
                        $q->where('method', 'bank_transfer');
                    })
                    ->count(),
                Transaction::where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->where('status', 'final')
                    ->where('is_direct_sale', 0)
                    ->when($location_id, function($q) use ($location_id) {
                        return $q->where('location_id', $location_id);
                    })
                    ->whereHas('payment_lines', function($q) {
                        $q->whereNotIn('method', ['cash', 'card', 'bank_transfer']);
                    })
                    ->count()
            ],
            'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#6b7280']
        ];

        // Top selling products
        $top_products = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            
            ->when($location_id, function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->groupBy('p.id', 'p.name')
            ->select(
                'p.name',
                DB::raw('SUM(tsl.quantity) as total_quantity'),
                DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_revenue')
            )
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get();

        $top_products_data = [
            'labels' => $top_products->pluck('name')->toArray(),
            'data' => $top_products->pluck('total_quantity')->toArray()
        ];

        // Daily order volume (last 7 days)
        $daily_volume = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->where('is_direct_sale', 0)
                ->when($location_id, function($q) use ($location_id) {
                    return $q->where('location_id', $location_id);
                })
                ->whereDate('transaction_date', $date->format('Y-m-d'))
                ->count();
            
            $daily_volume['labels'][] = $date->format('M d');
            $daily_volume['data'][] = $count;
        }

        // Top customers by revenue
        $top_customers = DB::table('transactions as t')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('t.is_direct_sale', 0)
            ->when($location_id, function($q) use ($location_id) {
                return $q->where('t.location_id', $location_id);
            })
            ->groupBy('c.id', 'c.name')
            ->select(
                'c.name',
                DB::raw('SUM(t.final_total) as total_spent')
            )
            ->orderBy('total_spent', 'desc')
            ->limit(5)
            ->get();

        $top_customers_data = [
            'labels' => $top_customers->pluck('name')->toArray(),
            'data' => $top_customers->pluck('total_spent')->toArray()
        ];

        return view('pos.dashboard.index')
            ->with(compact(
                'location_id',
                'counters',
                'recent_sales',
                'labels',
                'sales_by_day',
                'payment_type_chart',
                'top_products_data',
                'daily_volume',
                'top_customers_data',
                'locations'
            ));
    }
}
