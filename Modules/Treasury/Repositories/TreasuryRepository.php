<?php

namespace Modules\Treasury\Repositories;

use App\BusinessLocation;
use App\Transaction;
use App\TransactionPayment;
use App\Contact;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Treasury Repository
 * 
 * Handles all database operations related to treasury transactions
 * Following the Repository Pattern to abstract database layer
 */
class TreasuryRepository
{
    /**
     * Get treasury transactions with filtering
     *
     * @param int $business_id
     * @param array $filters
     * @return \Illuminate\Database\Query\Builder
     */
    public function getTreasuryTransactionsQuery(int $business_id, array $filters = [])
    {
        $query = Transaction::whereIn('transactions.type', ['expense', 'sell', 'purchase', 'opening_balance', 'sell_return', 'purchase_return', 'payroll'])
            ->whereNotIn('transactions.status', ['draft', 'quotation', 'pending'])
            ->whereIn('transactions.payment_status', ['due', 'partial'])
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('business_locations', 'transactions.location_id', '=', 'business_locations.id')
            ->orderBy('transactions.transaction_date', 'asc')
            ->select(
                'transactions.id',
                'transactions.transaction_date',
                'transactions.type',
                'transactions.sub_type',
                'transactions.invoice_no',
                'transactions.ref_no',
                'transactions.payment_status',
                'transactions.status',
                'transactions.final_total',
                'transactions.tax_amount',
                'transactions.discount_amount',
                'transactions.discount_type',
                'transactions.total_before_tax',
                'transactions.created_at',
                'transactions.created_by',
                'business_locations.name as location_name',
                DB::raw("CASE 
                    WHEN transactions.sub_type = 'repair' THEN 
                        (SELECT CONCAT(COALESCE(c.name, '')) 
                         FROM contacts c 
                         INNER JOIN repair_job_sheets rjs ON rjs.contact_id = c.id 
                         WHERE rjs.id = transactions.repair_job_sheet_id)
                    ELSE 
                        CONCAT(COALESCE(contacts.name, ''))
                END as contact_name"),
                DB::raw("(SELECT COALESCE(SUM(amount), 0) FROM transaction_payments WHERE transaction_id = transactions.id) as total_paid"),
                DB::raw("(transactions.final_total - (SELECT COALESCE(SUM(amount), 0) FROM transaction_payments WHERE transaction_id = transactions.id)) as remaining_amount")
            );

        // Apply location filter if provided
        if (!empty($filters['location_id'])) {
            $query->where('transactions.location_id', $filters['location_id']);
        }

        // Apply transaction type filter if provided
        if (!empty($filters['transaction_type'])) {
            $query->where('transactions.type', $filters['transaction_type']);
        }

        // Apply date range filter if provided
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('transactions.transaction_date', [$filters['start_date'], $filters['end_date']]);
        }

        return $query;
    }

    /**
     * Get transaction by ID with relationships
     *
     * @param int $transaction_id
     * @param int $business_id
     * @return Transaction|null
     */
    public function getTransactionById(int $transaction_id, int $business_id): ?Transaction
    {
        return Transaction::where('transactions.business_id', $business_id)
            ->with([
                'contact',
                'payment_lines',
                'location',
                'sell_lines' => function ($q) {
                    $q->whereNull('parent_sell_line_id');
                },
                'sell_lines.product',
                'sell_lines.product.unit',
                'sell_lines.variations',
                'sell_lines.variations.product_variation',
                'tax'
            ])
            ->find($transaction_id);
    }

    /**
     * Calculate total income for business
     *
     * @param int $business_id
     * @param int|null $location_id
     * @return float
     */
    public function calculateTotalIncome(int $business_id, ?int $location_id = null): float
    {
        $query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final');

        if ($location_id) {
            $query->where('transactions.location_id', $location_id);
        }

        $total_income = $query->sum('transactions.final_total');

        // Subtract sell returns (final only)
        $sell_returns_query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell_return')
            ->where('transactions.status', 'final');

        if ($location_id) {
            $sell_returns_query->where('transactions.location_id', $location_id);
        }

        $sell_returns = $sell_returns_query->sum('transactions.final_total');

        return $total_income - $sell_returns;
    }

    public function calculateAdvanceIncome(int $business_id, ?int $location_id = null): float
    {
            $query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.payment_status', 'partial')
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'under processing');

        if ($location_id) {
            $query->where('transactions.location_id', $location_id);
        }

        // Calculate the sum of paid amounts for these transactions
        $paidTotal = $query->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->sum('transaction_payments.amount');

        return (float) $paidTotal;  
    }



    /**
     * Calculate total under-processing sells for business (unfiltered by date)
     *
     * @param int $business_id
     * @param int|null $location_id
     * @return float
     */
    public function calculateCashInHandTotal(int $business_id, ?int $location_id = null): float
    {
        /** @var Util $util */
        $util = app(Util::class);

        if ($location_id) {
            $location = BusinessLocation::where('business_id', $business_id)->find($location_id);
            $payment_methods = $util->payment_types($location, false) ?? [];
            $balances = $this->getBranchPaymentMethodBalances($business_id, $payment_methods, $location_id);
        } else {
            $payment_methods = $util->payment_types(null, false, $business_id) ?? [];
            $balances = $this->getPaymentMethodBalances($business_id, $payment_methods);
        }

        $total = 0.0;
        foreach ($balances as $balance) {
            $total += (float) ($balance['balance'] ?? 0);
        }

            

        return $total;
    }

    /**
     * Calculate total expense for business
     *
     * @param int $business_id
     * @param int|null $location_id
     * @return float
     */
    public function calculateTotalExpense(int $business_id, ?int $location_id = null): float
    {
        $query = Transaction::where('transactions.business_id', $business_id)
            ->whereIn('transactions.type', ['expense', 'payroll'])
            ->where('transactions.status', 'final');


        if ($location_id) {
            $query->where('transactions.location_id', $location_id);
        }

        return (float) $query->sum('transactions.final_total');
    }

    /**
     * Calculate total purchases (optionally filtered by date range) and net off purchase returns
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float
     */
    public function calculateTotalPurchase(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        // Sum purchases
        $purchase_query = Transaction::where('transactions.type', 'purchase')
            ->where('transactions.status', 'received');
            

        if ($location_id) {
            $purchase_query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $purchase_query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }

        $total_purchases = $purchase_query->sum('transactions.final_total');

        // Subtract purchase returns
        $purchase_returns_query = Transaction::where('transactions.type', 'purchase_return')
            ->where('transactions.payment_status', 'paid');

        if ($location_id) {
            $purchase_returns_query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $purchase_returns_query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }

        $purchase_returns = $purchase_returns_query->sum('transactions.final_total');

        return (float) ($total_purchases - $purchase_returns);
    }

    /**
     * Calculate filtered total income for business with date range
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float
     */
    public function calculateFilteredTotalIncome(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        $query = Transaction::where('transactions.type', 'sell')
            ->where('transactions.status', 'final');
                
       

        if ($location_id) {
            $query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }

        $total_income = $query->sum('transactions.final_total');

        // Subtract sell returns
        $sell_returns_query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell_return');

        if ($location_id) {
            $sell_returns_query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $sell_returns_query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }

        $sell_returns = $sell_returns_query->sum('transactions.final_total');

        return $total_income - $sell_returns;
    }

    /**
     * Calculate filtered total expense for business with date range
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float
     */
    public function calculateFilteredTotalExpense(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        $query = Transaction::where('transactions.business_id', $business_id)
            ->whereIn('transactions.type', ['expense', 'payroll'])
            ->where('transactions.status', 'final');
                  
       
        if ($location_id) {
            $query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }
        return (float) $query->sum('transactions.final_total');
    }

    /**
     * Get monthly transaction payments data
     *
     * @param int $business_id
     * @param array $transaction_types
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return array
     */
    public function getMonthlyTransactionPayments(
        int $business_id, 
        array $transaction_types, 
        ?int $location_id = null, 
        ?string $start_date = null, 
        ?string $end_date = null
    ): array {
        $monthly_data = [];
        $year = $start_date && $end_date ? Carbon::parse($start_date)->year : Carbon::now()->year;

        for ($i = 1; $i <= 12; $i++) {
            $query = DB::table('transaction_payments')
                ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->whereNull('transactions.deleted_at')
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
                $query->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                      ->whereMonth('transactions.transaction_date', $i);
            } else {
                $query->whereYear('transactions.transaction_date', $year)
                      ->whereMonth('transactions.transaction_date', $i);
            }

            $total = $query->sum('transaction_payments.amount');
            $monthly_data[$i] = ['total' => (float)$total];
        }

        return $monthly_data;
    }

    /**
     * Get payment methods distribution
     *
     * @param int $business_id
     * @param array $payment_methods
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return \Illuminate\Support\Collection
     */
    public function getPaymentMethodsDistribution(
        int $business_id, 
        array $payment_methods, 
        ?int $location_id = null, 
        ?string $start_date = null, 
        ?string $end_date = null
    ): \Illuminate\Support\Collection {
        $query = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.deleted_at')
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
            ->whereIn('transactions.type', ['expense', 'sell', 'purchase', 'opening_balance', 'sell_return', 'purchase_return', 'payroll'])
            ->whereIn('transaction_payments.method', array_keys($payment_methods));

        if ($location_id) {
            $query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }

        return $query->select(
                'transaction_payments.method',
                'transactions.type as transaction_type',
                DB::raw('SUM(transaction_payments.amount) as total_amount')
            )
            ->groupBy('transaction_payments.method', 'transactions.type')
            ->get();
    }

    /**
     * Get sales due total
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float
     */
    public function getTotalSalesDue(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        $query = Transaction::where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereIn('transactions.payment_status', ['due', 'partial']);

        if ($location_id) {
            $query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }

        $due = $query->selectRaw('
            COALESCE(SUM(transactions.final_total - COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = transactions.id), 0)), 0) as sales_due
        ')->value('sales_due');

        return (float) ($due ?? 0);
    }

    /**
     * Get purchase due total
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float
     */
    public function getTotalPurchaseDue(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        $query = Transaction::where('transactions.type', 'purchase')
            ->where('transactions.status', 'received')
            ->whereIn('transactions.payment_status', ['due', 'partial']);

        if ($location_id) {
            $query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }

        $due = $query->selectRaw('
            COALESCE(SUM(transactions.final_total - COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = transactions.id), 0)), 0) as purchase_due
        ')->value('purchase_due');

        return (float) ($due ?? 0);
    }

    /**
     * Get expense due calculation
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float
     */
    public function getExpenseDue(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        $query = Transaction::where('transactions.type', 'expense')
            ->where('transactions.status', 'final')
            ->whereIn('transactions.payment_status', ['due', 'partial']);

        if ($location_id) {
            $query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }

        $due = $query->selectRaw('
            COALESCE(SUM(transactions.final_total - COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = transactions.id), 0)), 0) as expense_due
        ')->value('expense_due');

        return (float) ($due ?? 0);
    }

    /**
     * Get payment method balances
     *
     * @param int $business_id
     * @param array $payment_methods
     * @return array
     */
    public function getPaymentMethodBalances(int $business_id, array $payment_methods): array
    {
        $balances = [];

        foreach (array_keys($payment_methods) as $method_key) {
            // Income types (increase balance): sell, opening_balance, purchase_return (final only)
            $income_amount = DB::table('transaction_payments')
                ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->whereNull('transactions.deleted_at')
                ->whereNull('transaction_payments.deleted_at')
            
                ->where('transaction_payments.method', $method_key)
                ->whereIn('transactions.type', ['sell', 'opening_balance', 'purchase_return'])
       
                ->sum('transaction_payments.amount');

            // Expense types (decrease balance): expense, payroll, sell_return (final) and purchase (received)
            $expense_amount = DB::table('transaction_payments')
                ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->whereNull('transactions.deleted_at')
                ->whereNull('transaction_payments.deleted_at')
            
                ->where('transaction_payments.method', $method_key)
                ->where(function ($q) {
                    $q->whereIn('transactions.type', ['expense', 'payroll', 'sell_return'])
                
                      ->orWhere(function ($sub) {
                          $sub->where('transactions.type', 'purchase')
                              ->where('transactions.status', 'received');
                      });
                })
                ->sum('transaction_payments.amount');

            // Internal transfers
            $incoming_transfers = DB::table('transaction_payments')
                ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->whereNull('transactions.deleted_at')
                ->whereNull('transaction_payments.deleted_at')
         
                ->where('transaction_payments.method', $method_key)
                ->where('transactions.type', 'internal_transfer')
       
                ->where('transactions.additional_notes', 'LIKE', 'Internal transfer from %')
                ->sum('transaction_payments.amount');

            $outgoing_transfers = DB::table('transaction_payments')
                ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->whereNull('transactions.deleted_at')
                ->whereNull('transaction_payments.deleted_at')

                ->where('transaction_payments.method', $method_key)
                ->where('transactions.type', 'internal_transfer')
        
                ->where('transactions.additional_notes', 'LIKE', 'Internal transfer to %')
                ->sum('transaction_payments.amount');

            $internal_transfer_net = $incoming_transfers - $outgoing_transfers;
            $balance = $income_amount - $expense_amount + $internal_transfer_net;

            $balances[] = [
                'id' => $method_key,
                'name' => $payment_methods[$method_key],
                'balance' => $balance
            ];
        }

        return $balances;
    }

    /**
     * Get default contact for business
     *
     * @param int $business_id
     * @return Contact|null
     */
    public function getDefaultContact(int $business_id): ?Contact
    {
        return Contact::where('business_id', $business_id)
            ->where('is_default', 1)
            ->first();
    }

    /**
     * Create internal transfer transaction
     *
     * @param array $data
     * @return Transaction
     */
    public function createInternalTransfer(array $data): Transaction
    {
        return Transaction::create($data);
    }

    /**
     * Create transaction payment
     *
     * @param array $data
     * @return TransactionPayment
     */
    public function createTransactionPayment(array $data): TransactionPayment
    {
        return TransactionPayment::create($data);
    }

    /**
     * Delete transaction
     *
     * @param int $transaction_id
     * @param int $business_id
     * @return bool
     */
    public function deleteTransaction(int $transaction_id, int $business_id): bool
    {
        $transaction = Transaction::where('business_id', $business_id)->find($transaction_id);
        return $transaction ? $transaction->delete() : false;
    }

    /**
     * Get internal transfers query
     *
     * @param int $business_id
     * @param array $filters
     * @return \Illuminate\Database\Query\Builder
     */
    public function getInternalTransfersQuery(int $business_id, array $filters = [])
    {
        $query = DB::table('transactions as t')
            ->join('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->join('users as u', 't.created_by', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'internal_transfer')
            ->where('t.sub_type', 'internal_transfer')
            ->whereNull('t.deleted_at')
            ->whereNull('tp.deleted_at')
            ->select([
                't.id',
                't.transaction_date',
                't.final_total as amount',
                't.additional_notes as notes',
                't.created_at',
                't.updated_at',
                'tp.method as payment_method',
                'u.first_name',
                'u.last_name'
            ]);

        // Apply date filter if provided
        if (!empty($filters['date_filter'])) {
            $date_filter = $filters['date_filter'];
            if (strpos($date_filter, ' to ') !== false) {
                $dates = explode(' to ', $date_filter);
                $start_date = Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $end_date = Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                $query->whereBetween('t.transaction_date', [$start_date, $end_date]);
            }
        }

        // Apply payment method filter if provided
        if (!empty($filters['payment_method_filter'])) {
            $query->where('tp.method', $filters['payment_method_filter']);
        }

        // Apply amount filter if provided
        if (!empty($filters['amount_filter'])) {
            $amount_filter = $filters['amount_filter'];
            if (strpos($amount_filter, '-') !== false) {
                $amounts = explode('-', $amount_filter);
                $min_amount = (float) trim($amounts[0]);
                $max_amount = (float) trim($amounts[1]);
                $query->whereBetween('t.final_total', [$min_amount, $max_amount]);
            }
        }

        return $query;
    }

    /**
     * Get payment method balances for specific branch/location
     *
     * @param int $business_id
     * @param array $payment_methods
     * @param int|null $location_id
     * @return array
     */
    public function getBranchPaymentMethodBalances(int $business_id, array $payment_methods, ?int $location_id = null): array
    {
        $balances = [];

        foreach (array_keys($payment_methods) as $method_key) {
            // Income types (increase balance): sell, opening_balance, purchase_return
            $income_query = DB::table('transaction_payments')
                ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->whereNull('transactions.deleted_at')
                ->whereNull('transaction_payments.deleted_at')
                ->where('transaction_payments.method', $method_key)
                ->whereIn('transactions.type', ['sell', 'opening_balance', 'purchase_return'])
                ->when($location_id, function ($query, $location_id) {
                    $query->where('transactions.location_id', $location_id);
                });

            $income_amount = $income_query->sum('transaction_payments.amount');

            // Expense types (decrease balance): expense, payroll, sell_return (final) and purchase (received)
            $expense_query = DB::table('transaction_payments')
                ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->whereNull('transactions.deleted_at')
                ->whereNull('transaction_payments.deleted_at')
                ->where('transaction_payments.method', $method_key)
                ->whereIn('transactions.type', ['expense', 'payroll', 'sell_return','purchase'])
                ->when($location_id, function ($query, $location_id) {
                    $query->where('transactions.location_id', $location_id);
                });

            $expense_amount = $expense_query->sum('transaction_payments.amount');

            // Internal transfers for this branch
            $incoming_transfers_query = DB::table('transaction_payments')
                ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->whereNull('transactions.deleted_at')
                ->whereNull('transaction_payments.deleted_at')
                ->where('transaction_payments.method', $method_key)
                ->where('transactions.type', 'internal_transfer')
                ->where('transactions.additional_notes', 'LIKE', 'Internal transfer from %')
                ->when($location_id, function ($query, $location_id) {
                    $query->where('transactions.location_id', $location_id);
                });

            $incoming_transfers = $incoming_transfers_query->sum('transaction_payments.amount');

            $outgoing_transfers_query = DB::table('transaction_payments')
                ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->whereNull('transactions.deleted_at')
                ->whereNull('transaction_payments.deleted_at')
                ->where('transaction_payments.method', $method_key)
                ->where('transactions.type', 'internal_transfer')
                ->where('transactions.additional_notes', 'LIKE', 'Internal transfer to %')
                ->when($location_id, function ($query, $location_id) {
                    $query->where('transactions.location_id', $location_id);
                });

            $outgoing_transfers = $outgoing_transfers_query->sum('transaction_payments.amount');

            $internal_transfer_net = $incoming_transfers - $outgoing_transfers;
            $balance = $income_amount - $expense_amount + $internal_transfer_net;

            $balances[] = [
                'id' => $method_key,
                'name' => $payment_methods[$method_key],
                'balance' => $balance,
                'location_id' => $location_id
            ];
        }

        return $balances;
    }

    /**
     * Get single payment method balance for specific branch/location
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string $payment_method_key
     * @return float
     */
    public function getBranchPaymentMethodBalance(int $business_id, ?int $location_id, string $payment_method_key): float
    {
        // Income types (increase balance): sell, opening_balance, purchase_return
        $income_query = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_payments.deleted_at')
            ->where('transaction_payments.method', $payment_method_key)
            ->where('transactions.status', 'final')
            ->whereIn('transactions.type', ['sell', 'opening_balance', 'purchase_return']);

        if ($location_id) {
            $income_query->where('transactions.location_id', $location_id);
        }

        $income_amount = $income_query->sum('transaction_payments.amount');

        // Expense types (decrease balance): expense, payroll, sell_return (final) and purchase (received)
        $expense_query = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_payments.deleted_at')
            ->where('transaction_payments.method', $payment_method_key)
            ->where(function ($q) {
                $q->whereIn('transactions.type', ['expense', 'payroll', 'sell_return'])
                    ->where('transactions.status', 'final')
                  ->orWhere(function ($sub) {
                      $sub->where('transactions.type', 'purchase')
                          ->where('transactions.status', 'received');
                  });
            });

        if ($location_id) {
            $expense_query->where('transactions.location_id', $location_id);
        }

        $expense_amount = $expense_query->sum('transaction_payments.amount');

        // Internal transfers for this branch
        $incoming_transfers_query = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_payments.deleted_at')
            ->where('transaction_payments.method', $payment_method_key)
            ->where('transactions.type', 'internal_transfer')
            ->where('transactions.status', 'final')
            ->where('transactions.additional_notes', 'LIKE', 'Internal transfer from %');

        if ($location_id) {
            $incoming_transfers_query->where('transactions.location_id', $location_id);
        }

        $incoming_transfers = $incoming_transfers_query->sum('transaction_payments.amount');

        $outgoing_transfers_query = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_payments.deleted_at')
            ->where('transaction_payments.method', $payment_method_key)
            ->where('transactions.type', 'internal_transfer')
            ->where('transactions.status', 'final')
            ->where('transactions.additional_notes', 'LIKE', 'Internal transfer to %');

        if ($location_id) {
            $outgoing_transfers_query->where('transactions.location_id', $location_id);
        }

        $outgoing_transfers = $outgoing_transfers_query->sum('transaction_payments.amount');

        return $income_amount - $expense_amount + ($incoming_transfers - $outgoing_transfers);
    }

    private function calculateGrossProfit(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null, string $stock_filter = 'all'): float
    {
        // Calculate gross profit using query builder and PHP calculations
        $query = DB::table('transaction_sell_lines')
            ->leftJoin('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->leftJoin('transaction_sell_lines_purchase_lines as TSPL', 'transaction_sell_lines.id', '=', 'TSPL.sell_line_id')
            ->leftJoin('purchase_lines as PL', 'TSPL.purchase_line_id', '=', 'PL.id')
            ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->whereNull('sale.deleted_at')
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final');

        if ($location_id) {
            $query->where('sale.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('sale.transaction_date', [$start_date, $end_date]);
        }

    if ($stock_filter === 'exclude_stock') {
        $query->where('P.enable_stock', 0);
    }

    // Fetch sell lines with necessary fields
    $sellLines = $query->select(
        'transaction_sell_lines.*',
        'P.enable_stock',
        'TSPL.quantity as tspl_quantity',
        'TSPL.qty_returned as tspl_qty_returned',
        'PL.purchase_price_inc_tax'
    )->get();

    $totalProfit = 0;

    foreach ($sellLines as $line) {
        $quantity = $line->quantity - $line->quantity_returned;

        if ($stock_filter === 'exclude_stock') {
            // For non-stock products, entire selling price is profit
            $totalProfit += $quantity * $line->unit_price_inc_tax;
        } else {
            // Check for parent sell line's purchase lines (subquery equivalent)
            $parentProfit = DB::table('transaction_sell_lines as tsl')
                ->join('transaction_sell_lines_purchase_lines as tspl2', 'tsl.id', '=', 'tspl2.sell_line_id')
                ->join('purchase_lines as pl2', 'tspl2.purchase_line_id', '=', 'pl2.id')
                ->where('tsl.parent_sell_line_id', $line->id)
                ->sum(DB::raw('(tspl2.quantity - tspl2.qty_returned) * (tsl.unit_price_inc_tax - pl2.purchase_price_inc_tax)'));

            if ($parentProfit != 0) {
                // Use the parent profit if available
                $totalProfit += $parentProfit;
            } else {
                // Fallback based on enable_stock
                if ($line->enable_stock == 1) {
                    $totalProfit += $quantity * $line->unit_price_inc_tax;
                } else {
                    // Calculate using purchase line data
                    $purchaseQuantity = ($line->tspl_quantity ?? 0) - ($line->tspl_qty_returned ?? 0);
                    $totalProfit += $purchaseQuantity * ($line->unit_price_inc_tax - ($line->purchase_price_inc_tax ?? 0));
                }
            }
        }
    }

        return $totalProfit;
    }

    /**
     * Calculate total expenses for business
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float
     */
    private function calculateTotalExpenses(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        $expenses_query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'expense')
            ->where('transactions.payment_status', 'paid');

        if ($location_id) {
            $expenses_query->where('transactions.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $expenses_query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }

        return $expenses_query->sum('transactions.final_total');
    }

    /**
     * Calculate total profit for business (service products only - enable_stock = 0, virtual_product = 1)
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float
     */
    public function calculateTotalProfit(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        // Calculate profit for service products only (enable_stock = 0, virtual_product = 0)
        $query = DB::table('transaction_sell_lines')
            ->leftJoin('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->whereNull('sale.deleted_at')
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->whereNull('transaction_sell_lines.bundle_id')
        
            ->where('P.enable_stock', 0);

        if ($location_id) {
            $query->where('sale.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('sale.transaction_date', [$start_date, $end_date]);
        }

        return (float) $query->selectRaw(
            'COALESCE(SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax), 0) as total'
        )->value('total');
    }

    /**
     * Calculate virtual products profit (enable_stock = 0, virtual_product = 1)
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float
     */
    public function calculateVirtualProductsProfit(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        $query = DB::table('transaction_sell_lines')
            ->leftJoin('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->whereNull('sale.deleted_at')
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->whereNotNull('transaction_sell_lines.bundle_id');


        if ($location_id) {
            $query->where('sale.location_id', $location_id);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('sale.transaction_date', [$start_date, $end_date]);
        }

        return (float) $query->selectRaw(
            'COALESCE(SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax), 0) as total'
        )->value('total');
    }

    /**
     * Calculate spare parts selling price (stock-enabled products only)
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float
     */
    public function calculateSparePartsSellingPrice(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as sale', 'tsl.transaction_id', '=', 'sale.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->whereNull('sale.deleted_at')
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->where('p.enable_stock', 1);

        if ($location_id) {
            $query->where('sale.location_id', $location_id);
        }
        if ($start_date && $end_date) {
            $query->whereBetween('sale.transaction_date', [$start_date, $end_date]);
        }

        return (float) $query->sum(DB::raw('COALESCE((tsl.quantity - tsl.quantity_returned) * tsl.unit_price_inc_tax, 0)'));
    }

    /**
     * Calculate spare parts buying price (stock-enabled products only)
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return float
     */
    public function calculateSparePartsBuyingPrice(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as sale', 'tsl.transaction_id', '=', 'sale.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
            ->leftJoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
            ->whereNull('sale.deleted_at')
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->where('p.enable_stock', 1);

        if ($location_id) {
            $query->where('sale.location_id', $location_id);
        }
        if ($start_date && $end_date) {
            $query->whereBetween('sale.transaction_date', [$start_date, $end_date]);
        }

        return (float) $query->sum(DB::raw('COALESCE((tspl.quantity - tspl.qty_returned) * pl.purchase_price_inc_tax, 0)'));
    }

    public function calculateTotalProfitInvoice(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): float
    {
        $sellingPrice = $this->calculateSparePartsSellingPrice($business_id, $location_id, $start_date, $end_date);
        $buyingPrice = $this->calculateSparePartsBuyingPrice($business_id, $location_id, $start_date, $end_date);
        return (float) ($sellingPrice - $buyingPrice);
    }

    /**
     * Get all sales dashboard card data in one optimized call
     * Retrieves purchase totals, sell totals, returns, expenses, and dues
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string $start_date
     * @param string $end_date
     * @return array
     */

        public function getSalesDashboardData(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): array
    {
        // Build base query with location and optional date filters
        $baseQuery = function($query) use ($business_id, $location_id, $start_date, $end_date) {
            $query->where('business_id', $business_id);
            if ($start_date && $end_date) {
                $query->whereBetween('transaction_date', [$start_date, $end_date]);
            }
            if ($location_id) {
                $query->where('location_id', $location_id);
            }
            return $query;
        };

        // Execute all aggregation queries in parallel (eager loading pattern)
        // 1. Sell totals with invoice due
        $sellDataQuery = $baseQuery(Transaction::query())
            ->where('type', 'sell')
            ->where('status', 'final')
            ->selectRaw('
                SUM(final_total) as total_sell_inc_tax,
                SUM(CASE WHEN payment_status != "paid" THEN 
                    final_total - COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = transactions.id), 0)
                ELSE 0 END) as invoice_due
            ');

        // 1b. Under-processing sells
        $underProcessingSellDataQuery = Transaction::query()
            ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'under processing')
            ->whereIn('transactions.payment_status', ['partial', 'paid']);
        
        if ($start_date && $end_date) {
            $underProcessingSellDataQuery->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }
        if ($location_id) {
            $underProcessingSellDataQuery->where('transactions.location_id', $location_id);
        }
        
        $underProcessingSellDataQuery->selectRaw('COALESCE(SUM(transaction_payments.amount), 0) as total_under_processing_sell');

        // 2. Purchase totals with purchase due
        $purchaseDataQuery = $baseQuery(Transaction::query())
            ->where('type', 'purchase')
            ->where('status', 'received')
            ->selectRaw('
                SUM(final_total) as total_purchase_inc_tax,
                SUM(CASE WHEN payment_status != "paid" THEN 
                    final_total - COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = transactions.id), 0)
                ELSE 0 END) as purchase_due
            ');

        // 3. Returns (sell_return and purchase_return) - ALL statuses for accurate totals
        $returnsDataQuery = $baseQuery(Transaction::query())
            ->whereIn('type', ['sell_return', 'purchase_return'])
            ->selectRaw('
                SUM(CASE WHEN type = "sell_return" THEN final_total ELSE 0 END) as total_sell_return_inc_tax,
                SUM(CASE WHEN type = "purchase_return" THEN final_total ELSE 0 END) as total_purchase_return_inc_tax,
                SUM(CASE WHEN type = "sell_return" THEN COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = transactions.id), 0) ELSE 0 END) as sell_return_paid,
                SUM(CASE WHEN type = "sell_return" THEN final_total - COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = transactions.id), 0) ELSE 0 END) as sell_return_due
            ');

        // 4. Expense totals with expense due
        $expenseDataQuery = $baseQuery(Transaction::query())
            ->where('type', 'expense')
            ->where('status', 'final')
            ->selectRaw('
                SUM(final_total) as total_expense,
                SUM(CASE WHEN payment_status != "paid" THEN 
                    final_total - COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = transactions.id), 0)
                ELSE 0 END) as expense_due
            ');

        // 4b. Payroll totals with payroll due
        $payrollDataQuery = $baseQuery(Transaction::query())
            ->where('type', 'payroll')
            ->where('status', 'final')
            ->selectRaw('
                SUM(final_total) as total_payroll,
                SUM(CASE WHEN payment_status != "paid" THEN 
                    final_total - COALESCE((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = transactions.id), 0)
                ELSE 0 END) as payroll_due
            ');

        // 5. Ledger discount (only for sell transactions) - normalize to fixed amounts
        $ledgerDiscountQuery = DB::table('transaction_sell_lines')
            ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereNull('transactions.deleted_at')
            ->where('transactions.business_id', $business_id);
        
        if ($start_date && $end_date) {
            $ledgerDiscountQuery->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }
        if ($location_id) {
            $ledgerDiscountQuery->where('transactions.location_id', $location_id);
        }

        // Calculate normalized discount: convert percentage to fixed, multiply fixed by quantity
        $ledgerDiscount = $ledgerDiscountQuery->selectRaw('
            SUM(
                CASE 
                    WHEN line_discount_type = "percentage" AND line_discount_amount IS NOT NULL AND line_discount_amount > 0
                        THEN (unit_price_before_discount * quantity * line_discount_amount / 100)
                    WHEN line_discount_type = "fixed" AND line_discount_amount IS NOT NULL AND line_discount_amount > 0
                        THEN (line_discount_amount * quantity)
                    ELSE 0
                END
            ) as total_discount
        ')->value('total_discount') ?? 0;

        // Execute all queries at once (eager loading)
        $sellData = $sellDataQuery->first();
        $underProcessingSellData = $underProcessingSellDataQuery->first();
        $purchaseData = $purchaseDataQuery->first();
        $returnsData = $returnsDataQuery->first();
        $expenseData = $expenseDataQuery->first();
        $payrollData = $payrollDataQuery->first();

        // 6. Calculate total profit invoice (reuse existing method)
        $total_profit_invoice = $this->calculateTotalProfitInvoice($business_id, $location_id, $start_date, $end_date);
        $total_profit = $this->calculateTotalProfit($business_id, $location_id, $start_date, $end_date);
        $virtual_products_profit = $this->calculateVirtualProductsProfit($business_id, $location_id, $start_date, $end_date);
        
        // 7. Calculate spare parts selling and buying prices
        $spare_parts_selling_price = $this->calculateSparePartsSellingPrice($business_id, $location_id, $start_date, $end_date);
        $spare_parts_buying_price = $this->calculateSparePartsBuyingPrice($business_id, $location_id, $start_date, $end_date);

        // 8. Calculate 21 cards paid amount (all paid transactions)
        $cardsPaymentQuery = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $business_id)
            ->whereNull('transactions.deleted_at');
        
        if ($location_id) {
            $cardsPaymentQuery->where('transactions.location_id', $location_id);
        }
        if ($start_date && $end_date) {
            $cardsPaymentQuery->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }
        
        $cards_paid_amount = (float) $cardsPaymentQuery->sum('transaction_payments.amount');

        // 9. Calculate job sheet related expenses
        $jobSheetExpensesQuery = DB::table('transactions as exp')
            ->where('exp.type', 'expense')
            ->where('exp.business_id', $business_id)
            ->whereNull('exp.deleted_at')
            ->whereNotNull('exp.invoice_ref');
        
        if ($location_id) {
            $jobSheetExpensesQuery->where('exp.location_id', $location_id);
        }
        if ($start_date && $end_date) {
            $jobSheetExpensesQuery->whereBetween('exp.transaction_date', [$start_date, $end_date]);
        }
        
        $job_sheet_expenses = (float) $jobSheetExpensesQuery->sum('exp.final_total');

        // 10. Calculate spare parts profit paid and due amounts (eager loading with single query)
        // Build base filter for spare parts queries
        $sparePartsBaseFilter = function($query) use ($business_id, $location_id, $start_date, $end_date) {
            $query->join('transactions as sale', 'tsl.transaction_id', '=', 'sale.id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')
                ->whereNull('sale.deleted_at')
                ->where('sale.type', 'sell')
                ->where('sale.status', 'final')
                ->where('p.enable_stock', 1)
                ->where('sale.business_id', $business_id);
            
            if ($location_id) {
                $query->where('sale.location_id', $location_id);
            }
            if ($start_date && $end_date) {
                $query->whereBetween('sale.transaction_date', [$start_date, $end_date]);
            }
            return $query;
        };

        // Spare parts profit paid queries
        $sparePartsSellingPaidQuery = $sparePartsBaseFilter(DB::table('transaction_sell_lines as tsl'))
            ->where('sale.payment_status', 'paid')
            ->selectRaw('COALESCE(SUM((tsl.quantity - tsl.quantity_returned) * tsl.unit_price_inc_tax), 0) as total');

        $sparePartsBuyingPaidQuery = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as sale', 'tsl.transaction_id', '=', 'sale.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
            ->leftJoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
            ->whereNull('sale.deleted_at')
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->where('p.enable_stock', 1)
            ->where('sale.payment_status', 'paid')
            ->where('sale.business_id', $business_id);
        
        if ($location_id) {
            $sparePartsBuyingPaidQuery->where('sale.location_id', $location_id);
        }
        if ($start_date && $end_date) {
            $sparePartsBuyingPaidQuery->whereBetween('sale.transaction_date', [$start_date, $end_date]);
        }
        $sparePartsBuyingPaidQuery->selectRaw('COALESCE(SUM((tspl.quantity - tspl.qty_returned) * pl.purchase_price_inc_tax), 0) as total');

        // Spare parts profit due queries
        $sparePartsSellingDueQuery = $sparePartsBaseFilter(DB::table('transaction_sell_lines as tsl'))
            ->whereIn('sale.payment_status', ['due', 'partial'])
            ->selectRaw('COALESCE(SUM((tsl.quantity - tsl.quantity_returned) * tsl.unit_price_inc_tax), 0) as total');

        $sparePartsBuyingDueQuery = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as sale', 'tsl.transaction_id', '=', 'sale.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
            ->leftJoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
            ->whereNull('sale.deleted_at')
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final')
            ->where('p.enable_stock', 1)
            ->whereIn('sale.payment_status', ['due', 'partial'])
            ->where('sale.business_id', $business_id);
        
        if ($location_id) {
            $sparePartsBuyingDueQuery->where('sale.location_id', $location_id);
        }
        if ($start_date && $end_date) {
            $sparePartsBuyingDueQuery->whereBetween('sale.transaction_date', [$start_date, $end_date]);
        }
        $sparePartsBuyingDueQuery->selectRaw('COALESCE(SUM((tspl.quantity - tspl.qty_returned) * pl.purchase_price_inc_tax), 0) as total');

        // Execute all spare parts queries at once (eager loading)
        $sparePartsSellingPaid = (float) ($sparePartsSellingPaidQuery->first()->total ?? 0);
        $sparePartsBuyingPaid = (float) ($sparePartsBuyingPaidQuery->first()->total ?? 0);
        $sparePartsSellingDue = (float) ($sparePartsSellingDueQuery->first()->total ?? 0);
        $sparePartsBuyingDue = (float) ($sparePartsBuyingDueQuery->first()->total ?? 0);

        $spare_parts_profit_paid = (float) ($sparePartsSellingPaid - $sparePartsBuyingPaid);
        $spare_parts_profit_due = (float) ($sparePartsSellingDue - $sparePartsBuyingDue);

        // Return consolidated data
        return [
            'total_sell' => (float) ($sellData->total_sell_inc_tax ?? 0),
            'under_processing_sell' => (float) ($underProcessingSellData->total_under_processing_sell ?? 0),
            'total_sell_return' => (float) ($returnsData->total_sell_return_inc_tax ?? 0),
            'total_purchase' => (float) ($purchaseData->total_purchase_inc_tax ?? 0),
            'total_purchase_return' => (float) ($returnsData->total_purchase_return_inc_tax ?? 0),
            'total_expense' => (float) ($expenseData->total_expense ?? 0),
            'invoice_due' => (float) ($sellData->invoice_due ?? 0),
            // 'purchase_due' => (float) ($purchaseData->purchase_due ?? 0),
            'purchase_due' => (float) ($purchaseData->purchase_due ?? 0),
            'expense_due' => (float) ($expenseData->expense_due ?? 0),
            // computed paid amounts
            'selling_paid' => (float) ( ($sellData->total_sell_inc_tax ?? 0) - ($sellData->invoice_due ?? 0) - ($returnsData->sell_return_paid ?? 0) ),
            'sell_return_paid' => (float) ($returnsData->sell_return_paid ?? 0),
            'sell_return_due' => (float) ($returnsData->sell_return_due ?? 0),
            'purchase_paid' => (float) ( ($purchaseData->total_purchase_inc_tax ?? 0) - ($purchaseData->purchase_due ?? 0) ),
            'expense_paid' => (float) ( ($expenseData->total_expense ?? 0) - ($expenseData->expense_due ?? 0) ),
            'total_payroll' => (float) ($payrollData->total_payroll ?? 0),
            'payroll_due' => (float) ($payrollData->payroll_due ?? 0),
            'payroll_paid' => (float) ( ($payrollData->total_payroll ?? 0) - ($payrollData->payroll_due ?? 0) ),
            'total_profit_invoice' => (float) $total_profit_invoice,
            'total_profit' => (float) $total_profit,
            'virtual_products_profit' => (float) $virtual_products_profit,
            'spare_parts_selling_price' => (float) $spare_parts_selling_price,
            'spare_parts_buying_price' => (float) $spare_parts_buying_price,
            'cards_paid_amount' => (float) $cards_paid_amount,
            'job_sheet_expenses' => (float) $job_sheet_expenses,
            'spare_parts_profit_paid' => (float) $spare_parts_profit_paid,
            'spare_parts_profit_due' => (float) $spare_parts_profit_due
        ];
    }
}