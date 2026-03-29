<?php

namespace Modules\Treasury\Services;

use App\Transaction;
use App\TransactionPayment;
use App\Utils\Util;
use App\Utils\TransactionUtil;
use App\BusinessLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Opening Balance Service
 * 
 * Handles creation of opening balance transactions for treasury
 * Seeds payment method balances (cash, visa, etc.)
 */
class OpeningBalanceService
{
    protected Util $commonUtil;
    protected TransactionUtil $transactionUtil;

    public function __construct(Util $commonUtil, TransactionUtil $transactionUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Create opening balance transaction with payment
     *
     * @param array $input
     * @param int $business_id
     * @return array
     * @throws \Exception
     */
    public function createOpeningBalance(array $input, int $business_id): array
    {
        try {
            // Validate input
            $this->validateOpeningBalanceInput($input, $business_id);

            DB::beginTransaction();

            // Convert amount
            $amount = $this->commonUtil->num_uf($input['amount']);
            
            // Parse date
            $transaction_date = $this->commonUtil->uf_date($input['transaction_date'] ?? 'now', true);

            // Get location
            $location_id = $input['location_id'] ?? null;
            if (!$location_id) {
                $location = BusinessLocation::where('business_id', $business_id)->first();
                $location_id = $location ? $location->id : null;
            } else {
                $location = BusinessLocation::where('business_id', $business_id)->find($location_id);
            }

            // Generate reference number (for audit/tracking)
            $ob_ref_count = $this->transactionUtil->setAndGetReferenceCount('opening_balance', $business_id);
            $ref_no = $this->transactionUtil->generateReferenceNumber('opening_balance', $ob_ref_count, $business_id);

            // Generate invoice number using the configured invoice scheme
            // This mirrors sales invoices (prefix + year + sequential digits per location)
            $invoice_no = $this->transactionUtil->getInvoiceNumber($business_id, 'final', $location_id);

            // Create transaction
            $transaction_data = [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'type' => 'opening_balance',
                'sub_type' => 'treasury_opening',
                'status' => 'final',
                'payment_status' => 'paid',
                'ref_no' => $ref_no,
                'invoice_no' => $invoice_no,
                'transaction_date' => $transaction_date,
                'total_before_tax' => $amount,
                'final_total' => $amount,
                'additional_notes' => $input['notes'] ?? '',
                'created_by' => Auth::id(),
            ];

            $transaction = Transaction::create($transaction_data);

            Log::info('Opening balance transaction created', [
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'payment_method' => $input['payment_method'],
                'location_id' => $location_id
            ]);

            // Determine default account_id from location payment settings
            $account_id = null;
            if (!empty($location)) {
                $default_payment_accounts = !empty($location->default_payment_accounts) ? json_decode($location->default_payment_accounts, true) : [];
                if (!empty($default_payment_accounts[$input['payment_method']])) {
                    $account_id = $default_payment_accounts[$input['payment_method']]['account'] ?? null;
                }
            }

            // Create payment line
            $payment_data = [
                'transaction_id' => $transaction->id,
                'business_id' => $business_id,
                'amount' => $amount,
                'method' => $input['payment_method'],
                'paid_on' => $transaction_date,
                'is_return' => 0,
                'payment_type' => 'credit',
                'payment_ref_no' => $input['payment_ref_no'] ?? null,
                'account_id' => $account_id,
                'created_by' => Auth::id(),
            ];

            $payment = TransactionPayment::create($payment_data);

            // Dispatch events to record account & accounting mappings similar to sales payments
            try {
                event(new \App\Events\TransactionPaymentAdded($payment, [
                    'amount' => $amount,
                    'account_id' => $account_id,
                    'transaction_type' => 'opening_balance',
                ]));
            } catch (\Throwable $te) {
                \Log::warning('Opening balance: failed to dispatch TransactionPaymentAdded event', [
                    'error' => $te->getMessage(),
                    'payment_id' => $payment->id ?? null,
                ]);
            }

            Log::info('Opening balance payment created', [
                'transaction_id' => $transaction->id,
                'method' => $input['payment_method'],
                'amount' => $amount
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => __('treasury::lang.opening_balance_created_successfully'),
                'transaction_id' => $transaction->id,
                'ref_no' => $ref_no
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Opening balance creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate opening balance input
     *
     * @param array $input
     * @param int $business_id
     * @throws \Exception
     */
    private function validateOpeningBalanceInput(array $input, int $business_id): void
    {
        if (empty($input['amount'])) {
            throw new \Exception(__('treasury::lang.amount_required'));
        }

        if (empty($input['payment_method'])) {
            throw new \Exception(__('treasury::lang.payment_method_required'));
        }

        $amount = $this->commonUtil->num_uf($input['amount']);
        if ($amount <= 0) {
            throw new \Exception(__('treasury::lang.amount_must_be_positive'));
        }

        // Validate payment method exists
        $payment_types = $this->commonUtil->payment_types(null, false, $business_id);
        if (!isset($payment_types[$input['payment_method']])) {
            throw new \Exception(__('treasury::lang.invalid_payment_method'));
        }

        // If a location is provided, ensure the payment method is enabled for that location
        if (!empty($input['location_id'])) {
            $location = BusinessLocation::where('business_id', $business_id)->find($input['location_id']);
            if (!empty($location)) {
                $default_payment_accounts = !empty($location->default_payment_accounts) ? json_decode($location->default_payment_accounts, true) : [];
                $is_enabled = $default_payment_accounts[$input['payment_method']]['is_enabled'] ?? 0;
                if (!$is_enabled) {
                    throw new \Exception(__('treasury::lang.payment_method_not_enabled'));
                }
            }
        }
    }

    /**
     * Get opening balance transactions
     *
     * @param int $business_id
     * @param int|null $location_id
     * @return array
     */
    public function getOpeningBalances(int $business_id, ?int $location_id = null): array
    {
        // Get permitted locations for the current user
        $permitted_locations = auth()->user()->permitted_locations();

        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'opening_balance')
            ->where('sub_type', 'treasury_opening')
            ->with(['payment_lines', 'location']);

        // Apply permitted locations filter for non-admin users
        if ($permitted_locations != 'all') {
            $query->whereIn('location_id', $permitted_locations);
        }

        if ($location_id) {
            // Ensure the requested location is within permitted locations
            if ($permitted_locations != 'all' && !in_array($location_id, $permitted_locations)) {
                $query->whereRaw('1=0'); // Return no results if location not permitted
            } else {
                $query->where('location_id', $location_id);
            }
        }

        return $query->orderByDesc('transaction_date')->get()->toArray();
    }

    /**
     * Delete opening balance transaction
     *
     * @param int $transaction_id
     * @param int $business_id
     * @return array
     */
    public function deleteOpeningBalance(int $transaction_id, int $business_id): array
    {
        try {
            $transaction = Transaction::where('business_id', $business_id)
                ->where('id', $transaction_id)
                ->where('type', 'opening_balance')
                ->where('sub_type', 'treasury_opening')
                ->first();

            if (!$transaction) {
                throw new \Exception(__('messages.record_not_found'));
            }

            DB::beginTransaction();

            // Force-delete payment lines and dispatch deletion events for proper ledger cleanup
            $payments = TransactionPayment::where('transaction_id', $transaction_id)->get();
            foreach ($payments as $pay) {
                try {
                    event(new \App\Events\TransactionPaymentDeleted($pay));
                } catch (\Throwable $te) {
                    \Log::warning('Opening balance: failed to dispatch TransactionPaymentDeleted event', [
                        'error' => $te->getMessage(),
                        'payment_id' => $pay->id,
                    ]);
                }
                $pay->forceDelete();
            }

            // Force-delete transaction
            $transaction->forceDelete();

            DB::commit();

            Log::info('Opening balance transaction deleted', [
                'transaction_id' => $transaction_id
            ]);

            return [
                'success' => true,
                'message' => __('treasury::lang.opening_balance_deleted_successfully')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Opening balance deletion failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
