<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseDashboardController extends Controller
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

        $total_purchases = DB::table('transactions')
            ->where('type', 'purchase')
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->whereNull('deleted_at')
            ->count();

        $pending_purchases = DB::table('transactions')
            ->where('type', 'purchase')
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->count();

        $completed_purchases = DB::table('transactions')
            ->where('type', 'purchase')
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('status', 'received')
            ->whereNull('deleted_at')
            ->count();

        $due_purchases = DB::table('transactions')
            ->where('type', 'purchase')
            ->where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('payment_status', 'due')
            ->whereNull('deleted_at')
            ->count();

        $counters = [
            __('purchase.total_purchases') => ["data" => $total_purchases, "icon" => "fa fa-shopping-cart"],
            __('purchase.pending_purchases') => ["data" => $pending_purchases, "icon" => "fa fa-clock"],
            __('purchase.completed_purchases') => ["data" => $completed_purchases, "icon" => "fa fa-check-circle"],
            __('purchase.due_purchases') => ["data" => $due_purchases, "icon" => "fa fa-exclamation-circle"],
        ];

        $recent_purchases = DB::table('transactions')
            ->leftJoin('contacts', 'contacts.id', '=', 'transactions.contact_id')
            ->where('transactions.type', 'purchase')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.location_id', $location_id)
            ->whereNull('transactions.deleted_at')
            ->select(
                'transactions.id',
                'transactions.ref_no',
                'transactions.transaction_date',
                'transactions.final_total',
                'transactions.status',
                'transactions.payment_status',
                'contacts.name as supplier_name'
            )
            ->orderBy('transactions.transaction_date', 'desc')
            ->limit(10)
            ->get();

        $date = Carbon::now();
        $dayNumber = $date->day;
        $daysArray = range(1, $dayNumber);
        $currentMonth = $date->month;
        $labels = range(1, 31);

        $purchase_counts = [];
        foreach ($daysArray as $day) {
            $purchase_counts[] = [
                'x' => $day,
                'y' => DB::table('transactions')
                    ->where('type', 'purchase')
                    ->where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->whereDay('transaction_date', '=', $day)
                    ->whereMonth('transaction_date', '=', $currentMonth)
                    ->whereNull('deleted_at')
                    ->count()
            ];
        }

        $purchase_amounts = [];
        foreach ($daysArray as $day) {
            $purchase_amounts[] = [
                'x' => $day,
                'y' => DB::table('transactions')
                    ->where('type', 'purchase')
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
                __('purchase.pending'),
                __('purchase.completed'),
                __('purchase.ordered'),
                __('purchase.received')
            ],
            'data' => [
                DB::table('transactions')
                    ->where('type', 'purchase')
                    ->where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->where('status', 'pending')
                    ->whereNull('deleted_at')
                    ->count(),
                DB::table('transactions')
                    ->where('type', 'purchase')
                    ->where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->where('status', 'completed')
                    ->whereNull('deleted_at')
                    ->count(),
                DB::table('transactions')
                    ->where('type', 'purchase')
                    ->where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->where('status', 'ordered')
                    ->whereNull('deleted_at')
                    ->count(),
                DB::table('transactions')
                    ->where('type', 'purchase')
                    ->where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->where('status', 'received')
                    ->whereNull('deleted_at')
                    ->count()
            ],
            'colors' => ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6']
        ];

        return view('purchase.dashboard.index')
            ->with(compact('location_id', 'counters', 'recent_purchases', 'labels', 'purchase_counts', 'purchase_amounts', 'status_chart', 'locations'));
    }
}
