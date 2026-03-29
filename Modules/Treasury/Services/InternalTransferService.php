<?php

namespace Modules\Treasury\Services;

use Modules\Treasury\Repositories\TreasuryRepository;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\Util;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Internal Transfer Service
 * 
 * Handles all internal transfer operations between payment methods
 * Separates internal transfer logic from main Treasury service
 */
class InternalTransferService
{
    protected TreasuryRepository $repository;
    protected Util $commonUtil;
    protected TransactionUtil $transactionUtil;

    public function __construct(TreasuryRepository $repository, Util $commonUtil, TransactionUtil $transactionUtil)
    {
        $this->repository = $repository;
        $this->commonUtil = $commonUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Validate that updating the transfer will not cause negative balance
     * Uses the existing transfer's source branch/payment method as the balance context
     *
     * @param array $transactions
     * @param array $input
     * @param int $business_id
     * @throws \Exception
     */
    private function validateUpdateAgainstExistingTransfer(array $transactions, array $input, int $business_id): void
    {
        // Determine which of the two is the outgoing (source) transaction for balance check
        $source_txn = $transactions['is_outgoing'] ? $transactions['main'] : $transactions['corresponding'];

        if (!$source_txn) {
            throw new \Exception('Invalid internal transfer structure.');
        }

        // Load payment method used on the source transaction
        $payment_method = DB::table('transaction_payments')
            ->where('transaction_id', $source_txn->id)
            ->value('method');

        if (!$payment_method) {
            throw new \Exception('Payment method not found for the internal transfer.');
        }

        // Check branch/location balance for the specific method
        $from_balance = $this->repository->getBranchPaymentMethodBalance(
            $business_id,
            $source_txn->location_id,
            $payment_method
        );

        if ($input['amount'] > $from_balance) {
            throw new \Exception(__('treasury::lang.insufficient_balance') . ' at the selected branch');
        }
    }

    /**
     * Submit internal transfer between payment methods
     *
     * @param array $input
     * @param int $business_id
     * @return array
     * @throws \Exception
     */
    public function submitInternalTransfer(array $input, int $business_id): array
    {
        try {
            // Handle both 'date' and 'transfer_date' field names
            if (isset($input['date']) && !isset($input['transfer_date'])) {
                $input['transfer_date'] = $input['date'];
            }

            // Check available balance for from_payment_method
            $this->validateTransferBalance($input, $business_id);

            DB::beginTransaction();

            // Get default contact (optional - use null if not found)
            $default_contact = $this->repository->getDefaultContact($business_id);

            // Create transfer transactions
            $this->createTransferTransactions($input, $business_id, $default_contact);

            DB::commit();

            return [
                'success' => true,
                'msg' => __('treasury::lang.internal_transfer_success')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Internal transfer submission error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ' Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update internal transfer
     *
     * @param array $input
     * @param int $transfer_id
     * @param int $business_id
     * @return array
     * @throws \Exception
     */
    public function updateInternalTransfer(array $input, int $transfer_id, int $business_id): array
    {
        try {
            DB::beginTransaction();

            // Find and validate transfer transactions
            $transactions = $this->findTransferTransactions($transfer_id, $business_id);

            // Validate that the update will not cause a negative balance
            $this->validateUpdateAgainstExistingTransfer($transactions, $input, $business_id);
            
            // Update transfer transactions
            $this->updateTransferTransactions($transactions, $input, $business_id);

            DB::commit();

            return [
                'success' => true,
                'msg' => __('treasury::lang.internal_transfer_updated_success')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Internal transfer update error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ' Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete internal transfer
     *
     * @param int $transfer_id
     * @param int $business_id
     * @return array
     * @throws \Exception
     */
    public function deleteInternalTransfer(int $transfer_id, int $business_id): array
    {
        try {
            DB::beginTransaction();

            // Find and validate transfer transactions
            $transactions = $this->findTransferTransactions($transfer_id, $business_id);
            
            // Validate that deletion will not cause negative balance
            $this->validateDeletionBalance($transactions, $business_id);
            
            // Delete transfer transactions and their payments
            $this->deleteTransferTransactions($transactions);

            DB::commit();

            return [
                'success' => true,
                'msg' => __('treasury::lang.internal_transfer_deleted_success')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Internal transfer deletion error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * Get internal transfers data for DataTable
     *
     * @param int $business_id
     * @param array $filters
     * @return array
     */
    public function getInternalTransfersData(int $business_id, array $filters = []): array
    {
        $transfers = $this->repository->getInternalTransfersQuery($business_id, $filters)
            ->orderBy('t.transaction_date', 'desc')
            ->get();

        $payment_methods = $this->transactionUtil->payment_types($business_id, true, true);

        $data = [];
        foreach ($transfers as $transfer) {
            // Parse transfer direction from notes - only show outgoing transfers to avoid duplicates
            if (strpos($transfer->notes, 'Internal transfer to ') !== 0) {
                continue;
            }

            $from_method = $payment_methods[$transfer->payment_method] ?? $transfer->payment_method;
            $to_method_name = str_replace(['Internal transfer to ', '. '], ['', ''], explode('.', $transfer->notes)[0]);

            $data[] = [
                'id' => $transfer->id,
                'transaction_date' => $this->transactionUtil->format_date($transfer->transaction_date, true),
                'from_method' => $from_method,
                'to_method' => $to_method_name,
                'amount' => '<span class="display_currency" data-currency_symbol="true">' . $transfer->amount . '</span>',
                'notes' => $transfer->notes,
                'created_by' => $transfer->first_name . ' ' . $transfer->last_name,
                'action' => $this->getActionButtons($transfer->id)
            ];
        }

        return $data;
    }

    /**
     * Get transfer details for viewing
     *
     * @param int $transfer_id
     * @param int $business_id
     * @return array
     */
    public function getTransferDetails(int $transfer_id, int $business_id): array
    {
        $transaction = $this->repository->getTransactionById($transfer_id, $business_id);
        
        if (!$transaction || $transaction->type !== 'internal_transfer') {
            throw new \Exception('Internal transfer not found.');
        }

        $payment_methods = $this->transactionUtil->payment_types($business_id, true, true);
        
        return $this->parseTransferDetails($transaction, $payment_methods);
    }

    /**
     * Get transfer details for editing
     *
     * @param int $transfer_id
     * @param int $business_id
     * @return array
     */
    public function getTransferForEdit(int $transfer_id, int $business_id): array
    {
        $transaction = $this->repository->getTransactionById($transfer_id, $business_id);
        
        if (!$transaction || $transaction->type !== 'internal_transfer') {
            throw new \Exception('Internal transfer not found.');
        }

        $payment_types = $this->transactionUtil->payment_types($business_id, true, true);
        
        return $this->parseTransferForEdit($transaction, $payment_types);
    }

    /**
     * Validate that deletion will not cause negative balance
     * For internal transfers, we need to check the source payment method/branch balance
     * If this was an outgoing transfer, deleting it will increase the source balance (safe)
     * If this was an incoming transfer, deleting it will decrease the destination balance (check for negative)
     *
     * @param array $transactions
     * @param int $business_id
     * @throws \Exception
     */
    private function validateDeletionBalance(array $transactions, int $business_id): void
    {
        if (!$transactions['main']) {
            return;
        }

        $transaction = $transactions['main'];
        $is_outgoing = $transactions['is_outgoing'];
        
        // Get payment method from the transaction
        $payment_method = null;
        if ($transaction->payment_lines && $transaction->payment_lines->isNotEmpty()) {
            $payment_method = $transaction->payment_lines->first()->method;
        }
        
        if (!$payment_method) {
            // Fallback to DB query if payment_lines not loaded
            $payment_method = DB::table('transaction_payments')
                ->where('transaction_id', $transaction->id)
                ->value('method');
        }
        
        if (!$payment_method) {
            throw new \Exception('Payment method not found for the internal transfer.');
        }

        // For outgoing transfers, deletion increases source balance (always safe)
        if ($is_outgoing) {
            return;
        }

        // For incoming transfers, deletion decreases destination balance (check for negative)
        $current_balance = $this->repository->getBranchPaymentMethodBalance(
            $business_id,
            $transaction->location_id,
            $payment_method
        );

        // Calculate what the balance would be after removing this incoming transfer
        $balance_after_deletion = $current_balance - $transaction->final_total;

        if ($balance_after_deletion < 0) {
            throw new \Exception(__('treasury::lang.cannot_delete_transfer_would_cause_negative_balance') . ' at branch: ' . ($transaction->location->name ?? 'Unknown'));
        }
    }

    /**
     * Validate transfer balance
     * Ensures that no balance can go negative under any circumstance
     *
     * @param array $input
     * @param int $business_id
     * @throws \Exception
     */
    private function validateTransferBalance(array $input, int $business_id): void
    {
        // Validate amount is positive
        if (empty($input['amount']) || $input['amount'] <= 0) {
            throw new \Exception(__('treasury::lang.amount_must_be_positive'));
        }

        $payment_types = $this->commonUtil->payment_types(null, false, $business_id);
        
        // Check if this is a branch transfer (has from_location_id and to_location_id)
        $is_branch_transfer = !empty($input['from_location_id']) && !empty($input['to_location_id']);
        
        if ($is_branch_transfer) {
            // For branch transfers, check balance at the specific branch for the single payment method
            $payment_method_id = $input['payment_method'];
            $from_method_name = $payment_types[$payment_method_id] ?? $payment_method_id;
            
            $from_balance = $this->repository->getBranchPaymentMethodBalance(
                $business_id, 
                $input['from_location_id'], 
                $payment_method_id
            );
        } else {
            // For payment method transfers, get balance for the from_payment_method
            $payment_method_id = $input['from_payment_method'];
            $from_method_name = $payment_types[$payment_method_id] ?? $payment_method_id;
            
            // If location is specified for payment method transfer, check branch balance
            if (!empty($input['location_id'])) {
                $from_balance = $this->repository->getBranchPaymentMethodBalance(
                    $business_id, 
                    $input['location_id'], 
                    $payment_method_id
                );
            } else {
                // Get general balance across all locations
                $balances = $this->repository->getPaymentMethodBalances($business_id, $payment_types);
                $from_balance = 0;
                
                foreach ($balances as $balance) {
                    if ($balance['id'] == $payment_method_id) {
                        $from_balance = $balance['balance'];
                        break;
                    }
                }
            }
        }

        // Ensure balance is not negative before transfer
        if ($from_balance < 0) {
            $location_text = $is_branch_transfer ? ' at the selected branch' : '';
            throw new \Exception('Current balance is already negative' . $location_text . '. Cannot process transfer.');
        }

        // Ensure transfer amount does not exceed available balance
        if ($input['amount'] > $from_balance) {
            $location_text = $is_branch_transfer ? ' at the selected branch' : '';
            throw new \Exception(__('treasury::lang.insufficient_balance') . $location_text);
        }
    }

    /**
     * Create transfer transactions (outgoing and incoming)
     *
     * @param array $input
     * @param int $business_id
     * @param \App\Contact|null $default_contact
     */
    private function createTransferTransactions(array $input, int $business_id, $default_contact): void
    {
        $user = Auth::user();
        $contact_id = $default_contact ? $default_contact->id : null;
        
        // Determine if this is a branch transfer or payment method transfer
        $is_branch_transfer = !empty($input['from_location_id']) && !empty($input['to_location_id']);
        
        if ($is_branch_transfer) {
            // Branch transfer: different locations, same payment method
            $from_location_id = $input['from_location_id'];
            $to_location_id = $input['to_location_id'];
            $payment_method_id = $input['payment_method']; // Single payment method
            
            $payment_types = $this->commonUtil->payment_types(null, false, $business_id);
            $method_name = $payment_types[$payment_method_id] ?? $payment_method_id;
            
            // Get location names for notes
            $locations = \App\BusinessLocation::where('business_id', $business_id)
                ->whereIn('id', [$from_location_id, $to_location_id])
                ->pluck('name', 'id');
            
            $from_location_name = $locations[$from_location_id] ?? 'Branch ' . $from_location_id;
            $to_location_name = $locations[$to_location_id] ?? 'Branch ' . $to_location_id;
            
            // Create outgoing transaction (from branch)
            $outgoing_transaction = $this->repository->createInternalTransfer([
                'business_id' => $business_id,
                'location_id' => $from_location_id,
                'contact_id' => $contact_id,
                'type' => 'internal_transfer',
                'sub_type' => 'internal_transfer',
                'status' => 'final',
                'payment_status' => 'paid',
                'transaction_date' => $this->commonUtil->uf_date($input['transfer_date']),
                'total_before_tax' => $input['amount'],
                'final_total' => $input['amount'],
                'created_by' => $user->id,
                'additional_notes' => 'Internal transfer to ' . $to_location_name . ' (' . $method_name . '). ' . ($input['notes'] ?? '')
            ]);
            
            // Create outgoing payment
            $this->repository->createTransactionPayment([
                'transaction_id' => $outgoing_transaction->id,
                'amount' => $input['amount'],
                'method' => $payment_method_id,
                'paid_on' => $this->commonUtil->uf_date($input['transfer_date']),
                'created_by' => $user->id,
                'note' => 'Branch transfer out to ' . $to_location_name
            ]);
            
            // Create incoming transaction (to branch)
            $incoming_transaction = $this->repository->createInternalTransfer([
                'business_id' => $business_id,
                'location_id' => $to_location_id,
                'contact_id' => $contact_id,
                'type' => 'internal_transfer',
                'sub_type' => 'internal_transfer',
                'status' => 'final',
                'payment_status' => 'paid',
                'transaction_date' => $this->commonUtil->uf_date($input['transfer_date']),
                'total_before_tax' => $input['amount'],
                'final_total' => $input['amount'],
                'created_by' => $user->id,
                'additional_notes' => 'Internal transfer from ' . $from_location_name . ' (' . $method_name . '). ' . ($input['notes'] ?? '')
            ]);
            
            // Create incoming payment
            $this->repository->createTransactionPayment([
                'transaction_id' => $incoming_transaction->id,
                'amount' => $input['amount'],
                'method' => $payment_method_id,
                'paid_on' => $this->commonUtil->uf_date($input['transfer_date']),
                'created_by' => $user->id,
                'note' => 'Branch transfer in from ' . $from_location_name
            ]);
        } else {
            // Payment method transfer: same location, different payment methods
            $location_id = !empty($input['location_id']) ? $input['location_id'] : ($user->location_id ?? null);
            
            $payment_types = $this->commonUtil->payment_types(null, false, $business_id);
            $from_method_name = $payment_types[$input['from_payment_method']] ?? $input['from_payment_method'];
            $to_method_name = $payment_types[$input['to_payment_method']] ?? $input['to_payment_method'];
            
            // Create outgoing transaction
            $outgoing_transaction = $this->repository->createInternalTransfer([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'contact_id' => $contact_id,
                'type' => 'internal_transfer',
                'sub_type' => 'internal_transfer',
                'status' => 'final',
                'payment_status' => 'paid',
                'transaction_date' => $this->commonUtil->uf_date($input['transfer_date']),
                'total_before_tax' => $input['amount'],
                'final_total' => $input['amount'],
                'created_by' => $user->id,
                'additional_notes' => 'Internal transfer to ' . $to_method_name . '. ' . ($input['notes'] ?? '')
            ]);
            
            // Create outgoing payment
            $this->repository->createTransactionPayment([
                'transaction_id' => $outgoing_transaction->id,
                'amount' => $input['amount'],
                'method' => $input['from_payment_method'],
                'paid_on' => $this->commonUtil->uf_date($input['transfer_date']),
                'created_by' => $user->id,
                'note' => 'Transfer out to ' . $to_method_name
            ]);
            
            // Create incoming transaction
            $incoming_transaction = $this->repository->createInternalTransfer([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'contact_id' => $contact_id,
                'type' => 'internal_transfer',
                'sub_type' => 'internal_transfer',
                'status' => 'final',
                'payment_status' => 'paid',
                'transaction_date' => $this->commonUtil->uf_date($input['transfer_date']),
                'total_before_tax' => $input['amount'],
                'final_total' => $input['amount'],
                'created_by' => $user->id,
                'additional_notes' => 'Internal transfer from ' . $from_method_name . '. ' . ($input['notes'] ?? '')
            ]);
            
            // Create incoming payment
            $this->repository->createTransactionPayment([
                'transaction_id' => $incoming_transaction->id,
                'amount' => $input['amount'],
                'method' => $input['to_payment_method'],
                'paid_on' => $this->commonUtil->uf_date($input['transfer_date']),
                'created_by' => $user->id,
                'note' => 'Transfer in from ' . $from_method_name
            ]);
        }
    }

    /**
     * Find transfer transactions (outgoing and incoming)
     *
     * @param int $transfer_id
     * @param int $business_id
     * @return array
     * @throws \Exception
     */
    private function findTransferTransactions(int $transfer_id, int $business_id): array
    {
        $transaction = Transaction::where('business_id', $business_id)
            ->withTrashed()
            ->with(['payment_lines' => function($q) {
                $q->withTrashed();
            }])
            ->find($transfer_id);

        if (!$transaction || $transaction->type !== 'internal_transfer') {
            throw new \Exception('Internal transfer not found.');
        }

        $is_outgoing = $transaction->additional_notes && strpos($transaction->additional_notes, 'Internal transfer to ') === 0;
        $is_incoming = $transaction->additional_notes && strpos($transaction->additional_notes, 'Internal transfer from ') === 0;

        if (!$is_outgoing && !$is_incoming) {
            throw new \Exception('Invalid internal transfer type.');
        }

        // Find corresponding transaction (including soft-deleted)
        $corresponding_transaction = null;
        if ($is_outgoing) {
            $corresponding_transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'internal_transfer')
                ->where('transaction_date', $transaction->transaction_date)
                ->where('final_total', $transaction->final_total)
                ->where('additional_notes', 'LIKE', 'Internal transfer from %')
                ->where('id', '!=', $transaction->id)
                ->withTrashed()
                ->first();
        } else {
            $corresponding_transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'internal_transfer')
                ->where('transaction_date', $transaction->transaction_date)
                ->where('final_total', $transaction->final_total)
                ->where('additional_notes', 'LIKE', 'Internal transfer to %')
                ->where('id', '!=', $transaction->id)
                ->withTrashed()
                ->first();
        }

        return [
            'main' => $transaction,
            'corresponding' => $corresponding_transaction,
            'is_outgoing' => $is_outgoing
        ];
    }

    /**
     * Update transfer transactions
     *
     * @param array $transactions
     * @param array $input
     * @param int $business_id
     */
    private function updateTransferTransactions(array $transactions, array $input, int $business_id): void
    {
        $payment_methods = $this->transactionUtil->payment_types($business_id, true, true);
        $from_method_name = $payment_methods[$input['from_payment_method']] ?? $input['from_payment_method'];
        $to_method_name = $payment_methods[$input['to_payment_method']] ?? $input['to_payment_method'];

        // Update main transaction
        if ($transactions['main']) {
            $is_outgoing = $transactions['is_outgoing'];
            $additional_notes = $is_outgoing 
                ? 'Internal transfer to ' . $to_method_name . '. ' . $input['notes']
                : 'Internal transfer from ' . $from_method_name . '. ' . $input['notes'];

            DB::table('transactions')
                ->where('id', $transactions['main']->id)
                ->update([
                    'transaction_date' => $this->commonUtil->uf_date($input['date']),
                    'total_before_tax' => $input['amount'],
                    'final_total' => $input['amount'],
                    'additional_notes' => $additional_notes
                ]);

            // Update payment
            $payment_method = $is_outgoing ? $input['from_payment_method'] : $input['to_payment_method'];
            $note = $is_outgoing ? 'Transfer out to ' . $to_method_name : 'Transfer in from ' . $from_method_name;

            DB::table('transaction_payments')
                ->where('transaction_id', $transactions['main']->id)
                ->update([
                    'amount' => $input['amount'],
                    'method' => $payment_method,
                    'paid_on' => $this->commonUtil->uf_date($input['date']),
                    'note' => $note
                ]);
        }

        // Update corresponding transaction
        if ($transactions['corresponding']) {
            $is_corresponding_outgoing = !$transactions['is_outgoing'];
            $additional_notes = $is_corresponding_outgoing 
                ? 'Internal transfer to ' . $to_method_name . '. ' . $input['notes']
                : 'Internal transfer from ' . $from_method_name . '. ' . $input['notes'];

            DB::table('transactions')
                ->where('id', $transactions['corresponding']->id)
                ->update([
                    'transaction_date' => $this->commonUtil->uf_date($input['date']),
                    'total_before_tax' => $input['amount'],
                    'final_total' => $input['amount'],
                    'additional_notes' => $additional_notes
                ]);

            // Update payment
            $payment_method = $is_corresponding_outgoing ? $input['from_payment_method'] : $input['to_payment_method'];
            $note = $is_corresponding_outgoing ? 'Transfer out to ' . $to_method_name : 'Transfer in from ' . $from_method_name;

            DB::table('transaction_payments')
                ->where('transaction_id', $transactions['corresponding']->id)
                ->update([
                    'amount' => $input['amount'],
                    'method' => $payment_method,
                    'paid_on' => $this->commonUtil->uf_date($input['date']),
                    'note' => $note
                ]);
        }
    }

    /**
     * Delete transfer transactions
     *
     * @param array $transactions
     */
    private function deleteTransferTransactions(array $transactions): void
    {
        if ($transactions['main']) {
            // Force-delete payments linked to the main transfer transaction
            TransactionPayment::where('transaction_id', $transactions['main']->id)
                ->get()
                ->each(function ($payment) {
                    $payment->forceDelete();
                });

            // Force-delete the main transfer transaction itself
            $mainTxn = Transaction::where('id', $transactions['main']->id)
                ->where('type', 'internal_transfer')
                ->withTrashed()
                ->first();
            if ($mainTxn) {
                $mainTxn->forceDelete();
            }
        }

        if ($transactions['corresponding']) {
            // Force-delete payments linked to the corresponding transfer transaction
            TransactionPayment::where('transaction_id', $transactions['corresponding']->id)
                ->get()
                ->each(function ($payment) {
                    $payment->forceDelete();
                });

            // Force-delete the corresponding transfer transaction itself
            $correspondingTxn = Transaction::where('id', $transactions['corresponding']->id)
                ->where('type', 'internal_transfer')
                ->withTrashed()
                ->first();
            if ($correspondingTxn) {
                $correspondingTxn->forceDelete();
            }
        }
    }

    /**
     * Parse transfer details for viewing
     *
     * @param \App\Transaction $transaction
     * @param array $payment_methods
     * @return array
     */
    private function parseTransferDetails($transaction, array $payment_methods): array
    {
        $payment_line = $transaction->payment_lines->first();
        $from_method = '';
        $to_method = '';
        $notes = '';

        $is_outgoing = $transaction->additional_notes && strpos($transaction->additional_notes, 'Internal transfer to ') === 0;
        $is_incoming = $transaction->additional_notes && strpos($transaction->additional_notes, 'Internal transfer from ') === 0;

        if ($is_outgoing && $payment_line) {
            $from_method = $payment_methods[$payment_line->method] ?? $payment_line->method;
            if (preg_match('/Internal transfer to ([^.]+)\.?\s*(.*)/', $transaction->additional_notes, $matches)) {
                $to_method = trim($matches[1]);
                $notes = trim($matches[2] ?? '');
            }
        } elseif ($is_incoming && $payment_line) {
            $to_method = $payment_methods[$payment_line->method] ?? $payment_line->method;
            if (preg_match('/Internal transfer from ([^.]+)\.?\s*(.*)/', $transaction->additional_notes, $matches)) {
                $from_method = trim($matches[1]);
                $notes = trim($matches[2] ?? '');
            }
        }

        return [
            'transaction' => $transaction,
            'payment_methods' => $payment_methods,
            'from_method' => $from_method,
            'to_method' => $to_method,
            'notes' => $notes
        ];
    }

    /**
     * Parse transfer details for editing
     *
     * @param \App\Transaction $transaction
     * @param array $payment_types
     * @return array
     */
    private function parseTransferForEdit($transaction, array $payment_types): array
    {
        $payment_line = $transaction->payment_lines->first();
        $from_method_key = '';
        $to_method_key = '';
        $notes = '';

        $is_outgoing = $transaction->additional_notes && strpos($transaction->additional_notes, 'Internal transfer to ') === 0;

        if ($is_outgoing && $payment_line) {
            $from_method_key = $payment_line->method;
            if (preg_match('/Internal transfer to ([^.]+)\.?\s*(.*)/', $transaction->additional_notes, $matches)) {
                $to_method_name = trim($matches[1]);
                $notes = trim($matches[2] ?? '');
                
                // Find the key for the to method
                foreach ($payment_types as $key => $value) {
                    if ($value === $to_method_name) {
                        $to_method_key = $key;
                        break;
                    }
                }
            }
        } else {
            // Handle incoming transfer
            if ($payment_line) {
                $to_method_key = $payment_line->method;
            }
            if (preg_match('/Internal transfer from ([^.]+)\.?\s*(.*)/', $transaction->additional_notes, $matches)) {
                $from_method_name = trim($matches[1]);
                $notes = trim($matches[2] ?? '');
                
                foreach ($payment_types as $key => $value) {
                    if ($value === $from_method_name) {
                        $from_method_key = $key;
                        break;
                    }
                }
            }
        }

        return [
            'transaction' => $transaction,
            'payment_types' => $payment_types,
            'from_method_key' => $from_method_key,
            'to_method_key' => $to_method_key,
            'notes' => $notes
        ];
    }

    /**
     * Generate action buttons for internal transfer
     *
     * @param int $transfer_id
     * @return string
     */
    private function getActionButtons(int $transfer_id): string
    {
        $html = '<div class="btn-group">';
        $html .= '<button type="button" class="btn btn-info btn-xs btn-modal dropdown-toggle" data-toggle="dropdown" aria-expanded="false">';
        $html .= __('messages.actions') . ' <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>';
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu dropdown-menu-right" role="menu">';

        if (Auth::user()->can('treasury.view')) {
            $html .= '<li><a href="' . route('treasury.internal.transfers.show', $transfer_id) . '" class="view-transfer"><i class="fas fa-eye"></i> ' . __('messages.view') . '</a></li>';
        }

        if (Auth::user()->can('treasury.edit')) {
            $html .= '<li><a href="' . route('treasury.internal.transfers.edit', $transfer_id) . '" class="edit-transfer"><i class="fas fa-edit"></i> ' . __('messages.edit') . '</a></li>';
        }

        if (Auth::user()->can('treasury.delete')) {
            $html .= '<li><a href="' . route('treasury.internal.transfers.destroy', $transfer_id) . '" class="delete-transfer" data-href="' . route('treasury.internal.transfers.destroy', $transfer_id) . '"><i class="fas fa-trash"></i> ' . __('messages.delete') . '</a></li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}