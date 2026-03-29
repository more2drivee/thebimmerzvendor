<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccountsDashboardController extends Controller
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

        $total_accounts = Account::where('business_id', $business_id)
            ->where('is_closed', 0)
            ->count();

        $total_balance = DB::table('account_transactions as at')
            ->join('accounts as a', 'at.account_id', '=', 'a.id')
            ->where('a.business_id', $business_id)
            ->where('a.is_closed', 0)
            ->select(DB::raw('SUM(IF(at.type="credit", at.amount, -1*at.amount)) as balance'))
            ->value('balance');

        $total_deposits = DB::table('account_transactions as at')
            ->join('accounts as a', 'at.account_id', '=', 'a.id')
            ->where('a.business_id', $business_id)
            ->where('a.is_closed', 0)
            ->where('at.type', 'credit')
            ->where('at.sub_type', 'deposit')
            ->sum('at.amount');

        $total_transfers = DB::table('account_transactions as at')
            ->join('accounts as a', 'at.account_id', '=', 'a.id')
            ->where('a.business_id', $business_id)
            ->where('a.is_closed', 0)
            ->where('at.sub_type', 'fund_transfer')
            ->count();

        $counters = [
            __('account.total_accounts') => ["data" => $total_accounts, "icon" => "fa fa-university"],
            __('account.total_balance') => ["data" => $total_balance, "icon" => "fa fa-money"],
            __('account.total_deposits') => ["data" => $total_deposits, "icon" => "fa fa-arrow-down"],
            __('account.total_transfers') => ["data" => $total_transfers, "icon" => "fa fa-exchange"],
        ];

        $recent_transactions = DB::table('account_transactions as at')
            ->join('accounts as a', 'at.account_id', '=', 'a.id')
            ->leftJoin('users as u', 'at.created_by', '=', 'u.id')
            ->where('a.business_id', $business_id)
            ->where('a.is_closed', 0)
            ->select(
                'at.id',
                'at.operation_date',
                'at.type',
                'at.sub_type',
                'at.amount',
                'a.name as account_name',
                DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as created_by")
            )
            ->orderBy('at.operation_date', 'desc')
            ->limit(10)
            ->get();

        $date = Carbon::now();
        $dayNumber = $date->day;
        $daysArray = range(1, $dayNumber);
        $currentMonth = $date->month;
        $labels = range(1, 31);

        // Credit amounts by day
        $credit_amounts = [];
        foreach ($daysArray as $day) {
            $credit_amounts[] = [
                'x' => $day,
                'y' => DB::table('account_transactions as at')
                    ->join('accounts as a', 'at.account_id', '=', 'a.id')
                    ->where('a.business_id', $business_id)
                    ->where('a.is_closed', 0)
                    ->where('at.type', 'credit')
                    ->whereDay('at.operation_date', '=', $day)
                    ->whereMonth('at.operation_date', '=', $currentMonth)
                    ->sum('at.amount')
            ];
        }

        // Debit amounts by day
        $debit_amounts = [];
        foreach ($daysArray as $day) {
            $debit_amounts[] = [
                'x' => $day,
                'y' => DB::table('account_transactions as at')
                    ->join('accounts as a', 'at.account_id', '=', 'a.id')
                    ->where('a.business_id', $business_id)
                    ->where('a.is_closed', 0)
                    ->where('at.type', 'debit')
                    ->whereDay('at.operation_date', '=', $day)
                    ->whereMonth('at.operation_date', '=', $currentMonth)
                    ->sum('at.amount')
            ];
        }

        // Transaction type distribution
        $transaction_type_chart = [
            'labels' => [
                __('account.deposit'),
                __('account.fund_transfer'),
                __('account.opening_balance'),
                __('account.other')
            ],
            'data' => [
                DB::table('account_transactions as at')
                    ->join('accounts as a', 'at.account_id', '=', 'a.id')
                    ->where('a.business_id', $business_id)
                    ->where('a.is_closed', 0)
                    ->where('at.sub_type', 'deposit')
                    ->count(),
                DB::table('account_transactions as at')
                    ->join('accounts as a', 'at.account_id', '=', 'a.id')
                    ->where('a.business_id', $business_id)
                    ->where('a.is_closed', 0)
                    ->where('at.sub_type', 'fund_transfer')
                    ->count(),
                DB::table('account_transactions as at')
                    ->join('accounts as a', 'at.account_id', '=', 'a.id')
                    ->where('a.business_id', $business_id)
                    ->where('a.is_closed', 0)
                    ->where('at.sub_type', 'opening_balance')
                    ->count(),
                DB::table('account_transactions as at')
                    ->join('accounts as a', 'at.account_id', '=', 'a.id')
                    ->where('a.business_id', $business_id)
                    ->where('a.is_closed', 0)
                    ->whereNull('at.sub_type')
                    ->count()
            ],
            'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#6b7280']
        ];

        // Balance distribution by account type
        $balance_distribution = DB::table('accounts as a')
            ->leftJoin('account_transactions as at', 'a.id', '=', 'at.account_id')
            ->where('a.business_id', $business_id)
            ->where('a.is_closed', 0)
            ->groupBy('a.name')
            ->select(
                'a.name as account_name',
                DB::raw('SUM(CASE WHEN at.type = "credit" THEN at.amount ELSE -1*at.amount END) as balance')
            )
            ->orderBy('balance', 'desc')
            ->limit(10)
            ->get();

        $balance_distribution_data = [
            'labels' => $balance_distribution->pluck('account_name')->toArray(),
            'data' => $balance_distribution->pluck('balance')->toArray()
        ];

        // Daily transaction volume (last 7 days)
        $daily_volume = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = DB::table('account_transactions as at')
                ->join('accounts as a', 'at.account_id', '=', 'a.id')
                ->where('a.business_id', $business_id)
                ->where('a.is_closed', 0)
                ->whereDate('at.operation_date', $date->format('Y-m-d'))
                ->count();
            
            $daily_volume['labels'][] = $date->format('M d');
            $daily_volume['data'][] = $count;
        }

        // Top accounts by balance
        $top_accounts = DB::table('accounts as a')
            ->leftJoin('account_transactions as at', 'a.id', '=', 'at.account_id')
            ->where('a.business_id', $business_id)
            ->where('a.is_closed', 0)
            ->groupBy('a.id', 'a.name')
            ->select(
                'a.name',
                DB::raw('SUM(CASE WHEN at.type = "credit" THEN at.amount ELSE -1*at.amount END) as balance')
            )
            ->orderBy('balance', 'desc')
            ->limit(5)
            ->get();

        $top_accounts_data = [
            'labels' => $top_accounts->pluck('name')->toArray(),
            'data' => $top_accounts->pluck('balance')->toArray()
        ];

        return view('accounts.dashboard.index')
            ->with(compact(
                'location_id', 
                'counters', 
                'recent_transactions', 
                'labels', 
                'credit_amounts', 
                'debit_amounts', 
                'transaction_type_chart',
                'balance_distribution_data',
                'daily_volume',
                'top_accounts_data',
                'locations'
            ));
    }
}
