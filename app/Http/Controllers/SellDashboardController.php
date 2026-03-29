<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellDashboardController extends Controller
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
            ->where('business_id', $business_id);

        if (!$isAdmin && $permitted_locations != 'all') {
            $locations->whereIn('id', $permitted_locations);
        }

        $locations = $locations->get();

        $total_sales = DB::table('transactions')
            ->whereIn('type', ['sell', 'sell_return'])
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->whereNull('deleted_at')
            ->count();

        $pending_sales = DB::table('transactions')
            ->where('type', 'sell')
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->count();

        $completed_sales = DB::table('transactions')
            ->where('type', 'sell')
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->count();

        $due_sales = DB::table('transactions')
            ->where('type', 'sell')
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('payment_status', 'due')
            ->whereNull('deleted_at')
            ->count();

        $counters = [
            __('sale.total_sales') => ["data" => $total_sales, "icon" => "fa fa-shopping-cart"],
            __('sale.pending_sales') => ["data" => $pending_sales, "icon" => "fa fa-clock"],
            __('sale.completed_sales') => ["data" => $completed_sales, "icon" => "fa fa-check-circle"],
            __('sale.due_sales') => ["data" => $due_sales, "icon" => "fa fa-exclamation-circle"],
        ];

        $recent_sales = DB::table('transactions')
            ->leftJoin('contacts', 'contacts.id', '=', 'transactions.contact_id')
            ->where('transactions.type', 'sell')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.location_id', $location_id)
            ->whereNull('transactions.deleted_at')
            ->select(
                'transactions.id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.final_total',
                'transactions.status',
                'transactions.payment_status',
                'contacts.name as customer_name'
            )
            ->orderBy('transactions.transaction_date', 'desc')
            ->limit(10)
            ->get();

        $date = Carbon::now();
        $dayNumber = $date->day;
        $daysArray = range(1, $dayNumber);
        $currentMonth = $date->month;
        $labels = range(1, 31);

        $sell_counts = [];
        foreach ($daysArray as $day) {
            $sell_counts[] = [
                'x' => $day,
                'y' => DB::table('transactions')
                    ->where('type', 'sell')
                    ->where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->whereDay('transaction_date', '=', $day)
                    ->whereMonth('transaction_date', '=', $currentMonth)
                    ->whereNull('deleted_at')
                    ->count()
            ];
        }

        $sell_amounts = [];
        foreach ($daysArray as $day) {
            $sell_amounts[] = [
                'x' => $day,
                'y' => DB::table('transactions')
                    ->where('type', 'sell')
                    ->where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->whereDay('transaction_date', '=', $day)
                    ->whereMonth('transaction_date', '=', $currentMonth)
                    ->whereNull('deleted_at')
                    ->sum('final_total')
            ];
        }

        $status_chart = [
            'labels' => [
                __('sale.draft'),
                __('sale.under_processing'),
                __('sale.final')
            ],
            'data' => [
                DB::table('transactions')
                    ->where('type', 'sell')
                    ->where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->where('status', 'draft')
                    ->whereNull('deleted_at')
                    ->count(),
                DB::table('transactions')
                    ->where('type', 'sell')
                    ->where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->where('status', 'under processing')
                    ->whereNull('deleted_at')
                    ->count(),
                DB::table('transactions')
                    ->where('type', 'sell')
                    ->where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->where('status', 'final')
                    ->whereNull('deleted_at')
                    ->count()
            ],
            'colors' => ['#8b5cf6', '#f97316', '#10b981']
        ];

        return view('sell.dashboard.index')
            ->with(compact('location_id', 'counters', 'recent_sales', 'labels', 'sell_counts', 'sell_amounts', 'status_chart', 'locations'));
    }
}
