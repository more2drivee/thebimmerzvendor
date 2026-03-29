<?php

namespace Modules\Treasury\Services;

use Modules\Treasury\Repositories\TreasuryRepository;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Treasury Chart Service
 * 
 * Handles all chart data generation for the Treasury module
 * Separates chart logic from main business logic
 */
class TreasuryChartService
{
    protected TreasuryRepository $repository;
    protected Util $commonUtil;

    public function __construct(TreasuryRepository $repository, Util $commonUtil)
    {
        $this->repository = $repository;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Get monthly income data for chart
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return array
     */
    public function getMonthlyIncomeData(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): array
    {
        // Income types: sell, positive opening_balance
        $income_types = ['sell', 'opening_balance'];
        $income_data = $this->repository->getMonthlyTransactionPayments($business_id, $income_types, $location_id, $start_date, $end_date);

        // Subtract sell returns
        $return_types = ['sell_return'];
        $return_data = $this->repository->getMonthlyTransactionPayments($business_id, $return_types, $location_id, $start_date, $end_date);

        $monthly_income = [];
        for ($i = 1; $i <= 12; $i++) {
            $income = $income_data[$i]['total'] ?? 0;
            $returns = $return_data[$i]['total'] ?? 0;
            $monthly_income[$i] = ['total' => (float)($income - $returns)];
        }

        return $monthly_income;
    }

    /**
     * Get monthly expense data for chart
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return array
     */
    public function getMonthlyExpenseData(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): array
    {
        // Expense types: expense, purchase, payroll, negative opening_balance
        $expense_types = ['expense', 'purchase', 'payroll', 'opening_balance'];
        $expense_data = $this->repository->getMonthlyTransactionPayments($business_id, $expense_types, $location_id, $start_date, $end_date);

        // Subtract purchase returns
        $return_types = ['purchase_return'];
        $return_data = $this->repository->getMonthlyTransactionPayments($business_id, $return_types, $location_id, $start_date, $end_date);

        $monthly_expense = [];
        for ($i = 1; $i <= 12; $i++) {
            $expense = $expense_data[$i]['total'] ?? 0;
            $returns = $return_data[$i]['total'] ?? 0;
            $monthly_expense[$i] = ['total' => (float)($expense - $returns)];
        }

        return $monthly_expense;
    }

    /**
     * Get top transaction types distribution
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return array
     */
    public function getTopTransactionTypes(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): array
    {
        $transaction_types = ['expense', 'sell', 'purchase', 'opening_balance', 'sell_return', 'purchase_return', 'payroll'];

        $colors = [
            'sell' => '#36A2EB',
            'purchase' => '#FF6384',
            'expense' => '#FFCE56',
            'opening_balance' => '#4BC0C0',
            'sell_return' => '#9966FF',
            'purchase_return' => '#FF9F40',
            'payroll' => '#8B5CF6',
        ];

        // Single aggregated query with status rules:
        // - non-purchase types must be in status 'final'
        // - purchase type must be in status 'received'
        $query = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.deleted_at')
            ->select('transactions.type', DB::raw('SUM(transaction_payments.amount) as total'))
            ->whereIn('transactions.type', $transaction_types)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('transactions.status', 'final')
                        ->where('transactions.type', '!=', 'purchase');
                })->orWhere(function ($sub) {
                    $sub->where('transactions.status', 'received')
                        ->where('transactions.type', 'purchase');
                });
            });

        if ($location_id) {
            $query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }

        $results = $query->groupBy('transactions.type')->get();

        $type_totals = [];
        foreach ($results as $row) {
            $total = (float) ($row->total ?? 0);
            if ($total > 0) {
                $type_totals[$row->type] = $total;
            }
        }

        // Sort by total amount (descending)
        arsort($type_totals);

        // Take top 6 transaction types
        $top_types = array_slice($type_totals, 0, 6, true);

        return $this->formatChartData($top_types, $colors);
    }

    /**
     * Get payment methods distribution by transaction type
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return array
     */
    public function getPaymentMethodsDistributionByTransactionType(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): array
    {
        $enabled_payment_methods = $this->commonUtil->payment_types(null, false, $business_id);
        
        $payment_methods_by_type = $this->repository->getPaymentMethodsDistribution(
            $business_id, 
            $enabled_payment_methods, 
            $location_id, 
            $start_date, 
            $end_date
        );

        return $this->processPaymentMethodsData($payment_methods_by_type, $enabled_payment_methods);
    }

    /**
     * Get monthly transaction type totals for trend chart
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return array
     */
    public function getMonthlyTransactionTypeTotals(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): array
    {
        $transaction_types = ['expense', 'sell', 'purchase', 'opening_balance', 'sell_return', 'purchase_return', 'payroll'];
        $monthly_type_totals = [];
        $year = $start_date && $end_date ? Carbon::parse($start_date)->year : Carbon::now()->year;

        // Aggregated query across all types and months
        $query = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.deleted_at')
            ->select(
                'transactions.type',
                DB::raw('MONTH(transactions.transaction_date) as month'),
                DB::raw('SUM(transaction_payments.amount) as total')
            )
            // Apply status rules: final for non-purchase, received for purchase
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('transactions.status', 'final')
                        ->where('transactions.type', '!=', 'purchase');
                })->orWhere(function ($sub) {
                    $sub->where('transactions.status', 'received')
                        ->where('transactions.type', 'purchase');
                });
            })
            ->whereIn('transactions.type', $transaction_types);

        if ($location_id) {
            $query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        } else {
            $query->whereYear('transactions.transaction_date', $year);
        }

        $results = $query->groupBy('transactions.type', DB::raw('MONTH(transactions.transaction_date)'))
            ->get();

        // Initialize all months to 0 for each type
        foreach ($transaction_types as $type) {
            $monthly_type_totals[$type] = array_fill(0, 12, 0.0);
        }

        foreach ($results as $row) {
            $type = $row->type;
            $monthIndex = ((int) $row->month) - 1; // 0-based index
            $monthly_type_totals[$type][$monthIndex] = (float) ($row->total ?? 0);
        }

        return $monthly_type_totals;
    }

    /**
     * Get chart data for dashboard
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return array
     */
    public function getDashboardChartData(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): array
    {
        $monthly_income = $this->getMonthlyIncomeData($business_id, $location_id, $start_date, $end_date);
        $monthly_expense = $this->getMonthlyExpenseData($business_id, $location_id, $start_date, $end_date);

        // Calculate totals
        $total_income = array_sum(array_column($monthly_income, 'total'));
        $total_expense = array_sum(array_column($monthly_expense, 'total'));

        $payment_methods_by_transaction_type = $this->getPaymentMethodsDistributionByTransactionType(
            $business_id, 
            $location_id, 
            $start_date, 
            $end_date
        );

        return [
            'monthly_income' => $monthly_income,
            'monthly_expense' => $monthly_expense,
            'total_income' => $total_income,
            'total_expense' => $total_expense,
            'payment_methods_by_transaction_type' => $payment_methods_by_transaction_type
        ];
    }

    /**
     * Format chart data with labels, data, and colors
     *
     * @param array $data
     * @param array $colors
     * @return array
     */
    private function formatChartData(array $data, array $colors): array
    {
        $labels = [];
        $chart_data = [];
        $chart_colors = [];

        foreach ($data as $key => $value) {
            $labels[] = ucfirst(str_replace('_', ' ', $key));
            $chart_data[] = $value;
            $chart_colors[] = $colors[$key] ?? '#999999';
        }

        return [
            'labels' => $labels,
            'data' => $chart_data,
            'colors' => $chart_colors
        ];
    }

    /**
     * Process payment methods data for chart
     *
     * @param \Illuminate\Database\Eloquent\Collection $payment_methods_by_type
     * @param array $enabled_payment_methods
     * @return array
     */
    private function processPaymentMethodsData($payment_methods_by_type, array $enabled_payment_methods): array
    {
        // Group data by payment method and calculate totals
        $payment_method_totals = [];
        $payment_method_details = [];

        foreach ($payment_methods_by_type as $item) {
            $method = $item->method;
            $type = $item->transaction_type;
            $amount = (float) $item->total_amount;

            if (!isset($payment_method_totals[$method])) {
                $payment_method_totals[$method] = 0;
                $payment_method_details[$method] = [];
            }

            $payment_method_totals[$method] += $amount;
            $payment_method_details[$method][$type] = $amount;
        }

        // Format the data for the chart
        $labels = [];
        $data = [];
        $colors = [
            'cash' => '#36A2EB',
            'card' => '#FF6384',
            'cheque' => '#FFCE56',
            'bank_transfer' => '#4BC0C0',
            'custom_pay_1' => '#9966FF',
            'custom_pay_2' => '#FF9F40',
            'custom_pay_3' => '#FF6384',
            'custom_pay_4' => '#8B5CF6',
            'other' => '#C9CBCF'
        ];

        $chart_colors = [];
        $breakdown_details = [];

        foreach ($payment_method_totals as $method => $total_amount) {
            $method_name = $enabled_payment_methods[$method] ?? ucfirst(str_replace('_', ' ', $method));

            $labels[] = $method_name;
            $data[] = $total_amount;
            $chart_colors[] = $colors[$method] ?? '#C9CBCF';

            // Calculate breakdown with percentages
            $breakdown = [];
            $method_details = $payment_method_details[$method] ?? [];

            foreach ($method_details as $type => $amount) {
                $percentage = $total_amount > 0 ? round(($amount / $total_amount) * 100, 1) : 0;
                $breakdown[] = [
                    'type' => ucfirst(str_replace('_', ' ', $type)),
                    'amount' => $amount,
                    'percentage' => $percentage,
                    'formatted_amount' => number_format($amount, 2)
                ];
            }

            // Sort breakdown by amount (descending)
            usort($breakdown, function($a, $b) {
                return $b['amount'] <=> $a['amount'];
            });

            $breakdown_details[] = [
                'method' => $method_name,
                'total' => $total_amount,
                'formatted_total' => number_format($total_amount, 2),
                'breakdown' => $breakdown
            ];
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $chart_colors,
            'details' => $breakdown_details
        ];
    }
}