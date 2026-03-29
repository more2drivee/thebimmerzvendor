<?php

namespace Modules\Treasury\Services;

use Modules\Treasury\Repositories\TreasuryRepository;
use App\Utils\TransactionUtil;
use App\Utils\BusinessUtil;
use App\Business;
use App\BusinessLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;

/**
 * Treasury Transaction Service
 * 
 * Handles transaction-specific operations, viewing, and processing
 * Separates transaction logic from main Treasury service
 */
class TreasuryTransactionService
{
    protected TreasuryRepository $repository;
    protected TransactionUtil $transactionUtil;
    protected BusinessUtil $businessUtil;

    public function __construct(
        TreasuryRepository $repository, 
        TransactionUtil $transactionUtil,
        BusinessUtil $businessUtil
    ) {
        $this->repository = $repository;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Get treasury transactions for DataTable
     *
     * @param int $business_id
     * @param array $filters
     * @return \Yajra\DataTables\DataTables
     */
    public function getTreasuryTransactionsDataTable(int $business_id, array $filters = [])
    {
        $query = $this->repository->getTreasuryTransactionsQuery($business_id, $filters);

        return datatables()->of($query)
            ->editColumn('transaction_date', function ($row) {
                return $this->transactionUtil->format_date($row->transaction_date, true);
            })
            ->editColumn('invoice_no', function ($row) {
                return $this->formatInvoiceNumber($row);
            })
             
            ->editColumn('type', function ($row) {
                return ucfirst($row->type);
            })
            ->editColumn('sub_type', function ($row) {
                if (empty($row->sub_type)) {
                    return '';
                }

                switch (strtolower($row->sub_type)) {
                    case 'repair':
                        return __('treasury::lang.sub_type_repair');
                    default:
                        return ucfirst($row->sub_type);
                }
            })
            ->editColumn('final_total', function ($row) {
                return $this->transactionUtil->num_f($row->final_total, true);
            })
            ->editColumn('remaining_amount', function ($row) {
                return $this->transactionUtil->num_f($row->remaining_amount, true);
            })
            ->editColumn('payment_status', function ($row) {
                return ucfirst($row->payment_status);
            })
            ->addColumn('status', function ($row) {
                return $this->formatTransactionStatus($row->status);
            })
            ->addColumn('action', function ($row) {
                return $this->generateActionButtons($row);
            })
            ->rawColumns(['action', 'status', 'payment_status'])
            ->make(true);
    }

    /**
     * Get transaction details for viewing
     *
     * @param int $transaction_id
     * @param int $business_id
     * @return array
     * @throws \Exception
     */
    public function getTransactionDetails(int $transaction_id, int $business_id): array
    {
        if (!Auth::user()->can('treasury.view')) {
            throw new \Exception('Unauthorized action.');
        }

        $transaction = $this->repository->getTransactionById($transaction_id, $business_id);
        
        if (!$transaction) {
            throw new \Exception('Transaction not found.');
        }

        // Route to appropriate view based on transaction type
        if ($transaction->type === 'purchase') {
            return $this->getPurchaseTransactionDetails($transaction, $business_id);
        } elseif ($transaction->type === 'expense') {
            return $this->getExpenseTransactionDetails($transaction, $business_id);
        }

        // Handle other transaction types
        return $this->getGeneralTransactionDetails($transaction, $business_id);
    }

    /**
     * Delete treasury transaction
     *
     * @param int $transaction_id
     * @param int $business_id
     * @return array
     */
    public function deleteTransaction(int $transaction_id, int $business_id): array
    {
        if (!Auth::user()->can('treasury.delete')) {
            return [
                'success' => false,
                'msg' => 'Unauthorized action.'
            ];
        }

        try {
            $success = $this->repository->deleteTransaction($transaction_id, $business_id);
            
            if ($success) {
                return [
                    'success' => true,
                    'msg' => __('lang_v1.deleted_success')
                ];
            } else {
                return [
                    'success' => false,
                    'msg' => 'Transaction not found.'
                ];
            }
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            
            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
    }

    /**
     * Print treasury transaction
     *
     * @param int $transaction_id
     * @param int $business_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function printTransaction(int $transaction_id, int $business_id)
    {
        if (Gate::denies('treasury.view')) {
            throw new \Exception('Unauthorized action.');
        }

        // Get transaction with relationships
        $transaction = $this->repository->getTransactionById($transaction_id, $business_id);
        
        if (!$transaction) {
            throw new \Exception('Transaction not found.');
        }

        // Get business and location details
        $business = Business::find($business_id);
        $location = BusinessLocation::find($transaction->location_id);

        // Get invoice layout
        $invoice_layout_id = $location->invoice_layout_id;
        $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $invoice_layout_id);

        // Get receipt details
        $receipt_details = $this->transactionUtil->getReceiptDetails(
            $transaction_id, 
            $transaction->location_id,
            $invoice_layout,
            $business,
            $location,
            'browser'
        );

        // Prepare currency details
        $currency_details = [
            'symbol' => $business->currency_symbol,
            'thousand_separator' => $business->thousand_separator,
            'decimal_separator' => $business->decimal_separator,
        ];

        if (is_object($receipt_details)) {
            $receipt_details->currency = (object)$currency_details;
        } else if (is_array($receipt_details)) {
            $receipt_details['currency'] = $currency_details;
            $receipt_details = (object)$receipt_details;
        }

        $transaction->receipt_details = $receipt_details;

        // Generate PDF
        return $this->generateTransactionPDF($transaction);
    }

    /**
     * Format invoice number based on transaction type
     *
     * Always returns a non-null string to satisfy the return type hint,
     * even when both ref_no and invoice_no are missing.
     *
     * @param object $row
     * @return string
     */
    private function formatInvoiceNumber($row): string
    {
        $type = strtolower((string) ($row->type ?? ''));

        // For purchase/expense, prefer ref_no when available
        if (($type === 'purchase' || $type === 'expense') && !empty($row->ref_no)) {
            return (string) $row->ref_no;
        }

        if (!empty($row->invoice_no)) {
            return (string) $row->invoice_no;
        }

        // Fallback: some treasury-only rows may not have an invoice
        return '-';
    }

    /**
     * Format transaction status as localized HTML badge
     *
     * @param string $status
     * @return string
     */
    private function formatTransactionStatus(string $status): string
    {
        switch (strtolower($status)) {
            case 'final':
                // Closed -> red badge
                return '<span class="badge bg-danger treasury-status">' . e(__('treasury::lang.status_closed')) . '</span>';
            case 'under processing':
                // Open -> green badge
                return '<span class="badge bg-success treasury-status">' . e(__('treasury::lang.status_open')) . '</span>';
            default:
                return '';
        }
    }

    /**
     * Generate action buttons for transaction
     *
     * @param object $row
     * @return string
     */
    private function generateActionButtons($row): string
    {
        $html = '<div class="btn-group">
            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'. __("messages.actions") .'<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right" role="menu">';

        // View action
        if (Auth::user()->can('treasury.view')) {
            $html .= '<li><a href="#" data-href="' . action([\Modules\Treasury\Http\Controllers\TreasuryController::class, 'show'], [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye"></i> ' . __("messages.view") . '</a></li>';
        }

        // Edit contact action
            // $html .= '<li><a data-href="' . route('repair.contacts.edit_basic', [$row->contact_id]) . '" class="repair-edit-contact-basic"><i class="fas fa-user-edit"></i> ' . __('contact.edit_contact') . '</a></li>';
    

        // Share invoice action (only for sell transactions)
        if (strtolower($row->type) === 'sell' && (Auth::user()->can('sell.create') || Auth::user()->can('direct_sell.access'))) {
            $html .= '<li><a href="' . action([\App\Http\Controllers\SellPosController::class, 'showInvoiceUrl'], [$row->id]) . '" class="view_invoice_url tw-inline-flex tw-items-center tw-gap-1 tw-px-2 tw-py-1 tw-rounded tw-bg-indigo-600 hover:tw-bg-indigo-700 tw-text-white tw-text-xs"><i class="fas fa-eye"></i> ' . __('lang_v1.view_invoice_url') . '</a></li>';

            $html .= '<li><a href="#" data-href="' . action([\App\Http\Controllers\SellPosController::class, 'shareInvoiceLinks'], [$row->id]) . '" data-sms-url="' . action([\App\Http\Controllers\SellPosController::class, 'sendInvoiceSms'], [$row->id]) . '" class="share_invoice tw-inline-flex tw-items-center tw-gap-1 tw-px-2 tw-py-1 tw-rounded tw-text-gray-700 hover:tw-text-blue-600 tw-transition-colors tw-duration-200" aria-label="' . e(__('lang_v1.share_invoice')) . '" tabindex="0"><i class="fa fa-share-alt"></i> ' . __('lang_v1.share_invoice') . '</a></li>';
        }

        // Edit action
        $edit_url = $this->getEditUrlByTransactionType($row);
        if ($edit_url && $this->canEditTransactionType($row->type)) {
            $html .= '<li><a href="' . $edit_url . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
        }
        if((strtolower($row->type) === 'sell') && (auth()->user()->can('treasury.create') || auth()->user()->can('direct_sell.access'))){
            $html .= '<li><a href="'.route('treasury.transaction_overview', ['transaction_id' => $row->id]).'" target="_blank"><i class="fas fa-chart-pie"></i> '.__('treasury::lang.transaction_overview').'</a></li>';
        }

        // Add payment action
        if ($row->payment_status != 'paid' && Auth::user()->can('treasury.create')) {
            $html .= '<li><a href="' . action([\App\Http\Controllers\TransactionPaymentController::class, 'addPayment'], [$row->id]) . '" class="add_payment_modal" data-container="#payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __("purchase.add_payment") . '</a></li>';
        }

        // View payments action
        if (Auth::user()->can('treasury.view')) {
            $html .= '<li><a href="' . action([\App\Http\Controllers\TransactionPaymentController::class, 'show'], [$row->id]) . '" class="view_payment_modal" data-container="#view_payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __("purchase.view_payments") . '</a></li>';
        }

        // Print action
        if (Auth::user()->can('treasury.view')) {
            $html .= '<li>
                <a href="' . route('sell.printCleanInvoice', ['transaction_id' => $row->id]) . '" class="btn btn-info no-print" target="_blank">
                    <i class="fas fa-file-alt"></i> ' . __('messages.print') . '
                </a>
            </li>';
        }

        // Delete action
        if (Auth::user()->can('treasury.delete')) {
            $html .= '<li><a href="' . action([\Modules\Treasury\Http\Controllers\TreasuryController::class, 'destroy'], [$row->id]) . '" class="delete-treasury-transaction"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</a></li>';
        }

        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Get edit URL based on transaction type
     *
     * @param object $row
     * @return string|null
     */
    private function getEditUrlByTransactionType($row): ?string
    {
        switch (strtolower($row->type)) {
            case 'expense':
                return action([\App\Http\Controllers\ExpenseController::class, 'edit'], [$row->id]);
            case 'sell':
                return action([\App\Http\Controllers\SellController::class, 'edit'], [$row->id]);
            case 'purchase':
                return action([\App\Http\Controllers\PurchaseController::class, 'edit'], [$row->id]);
            default:
                return null;
        }
    }

    /**
     * Check if user can edit transaction type
     *
     * @param string $type
     * @return bool
     */
    private function canEditTransactionType(string $type): bool
    {
        switch (strtolower($type)) {
            case 'expense':
                return Auth::user()->can('expense.edit');
            case 'sell':
                return Auth::user()->can('sell.update');
            case 'purchase':
                return Auth::user()->can('purchase.update');
            default:
                return false;
        }
    }

    /**
     * Get purchase transaction details
     *
     * @param \App\Transaction $transaction
     * @param int $business_id
     * @return array
     */
    private function getPurchaseTransactionDetails($transaction, int $business_id): array
    {
        $purchase = \App\Transaction::where('business_id', $business_id)
            ->where('id', $transaction->id)
            ->with(
                'contact',
                'purchase_lines',
                'purchase_lines.product',
                'purchase_lines.product.unit',
                'purchase_lines.product.second_unit',
                'purchase_lines.variations',
                'purchase_lines.variations.product_variation',
                'purchase_lines.sub_unit',
                'location',
                'payment_lines',
                'tax'
            )
            ->firstOrFail();

        $taxes = \App\TaxRate::where('business_id', $business_id)->pluck('name', 'id');
        $payment_methods = $this->transactionUtil->payment_types(null, false, $business_id);

        return [
            'type' => 'purchase',
            'view' => 'purchase.show',
            'data' => compact('purchase', 'taxes', 'payment_methods')
        ];
    }

    /**
     * Get expense transaction details
     *
     * @param \App\Transaction $transaction
     * @param int $business_id
     * @return array
     */
    private function getExpenseTransactionDetails($transaction, int $business_id): array
    {
        $expense = \App\Transaction::where('business_id', $business_id)
            ->where('id', $transaction->id)
            ->with([
                'contact',
                'payment_lines',
                'location',
                'tax',
                'transaction_for'
            ])
            ->firstOrFail();

        // Load expense category
        if (!empty($expense->expense_category_id)) {
            $expense->expense_category = \App\ExpenseCategory::find($expense->expense_category_id);
        }

        $taxes = \App\TaxRate::where('business_id', $business_id)->pluck('name', 'id');
        $payment_methods = $this->transactionUtil->payment_types(null, false, $business_id);

        return [
            'type' => 'expense',
            'view' => 'expense.show_modal',
            'data' => compact('expense', 'taxes', 'payment_methods')
        ];
    }

    /**
     * Get general transaction details
     *
     * @param \App\Transaction $transaction
     * @param int $business_id
     * @return array
     */
    private function getGeneralTransactionDetails($transaction, int $business_id): array
    {
        // Fetch related repair job sheet if this is a repair transaction
        $repair = null;
        if ($transaction->sub_type === 'repair' && !empty($transaction->repair_job_sheet_id)) {
            $repair = $this->getRepairJobSheetDetails($transaction->repair_job_sheet_id);
        }

        // Format sell lines if needed
        if (!empty($transaction->sell_lines)) {
            foreach ($transaction->sell_lines as $key => $value) {
                if (!empty($value->sub_unit_id)) {
                    $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                    $transaction->sell_lines[$key] = $formated_sell_line;
                }
            }
        }

        // Get order taxes
        $order_taxes = $this->calculateOrderTaxes($transaction);

        return [
            'type' => 'general',
            'view' => 'treasury::show',
            'data' => compact('transaction', 'order_taxes', 'repair')
        ];
    }

    /**
     * Get repair job sheet details
     *
     * @param int $repair_job_sheet_id
     * @return object|null
     */
    private function getRepairJobSheetDetails(int $repair_job_sheet_id)
    {
        // Get basic repair job sheet with relationships
        $repair = \Modules\Repair\Entities\JobSheet::with([
            'customer',
            'Brand',
            'Device',
            'deviceModel',
            'status'
        ])->find($repair_job_sheet_id);

        if ($repair) {
            // Get additional vehicle information
            $vehicleInfo = DB::table('repair_job_sheets')
                ->leftJoin('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
                ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')
                ->leftJoin('repair_device_models AS model', 'model.id', '=', 'contact_device.models_id')
                ->leftJoin('categories as brand', 'brand.id', '=', 'contact_device.device_id')
                ->select(
                    'contact_device.color',
                    'contact_device.plate_number',
                    'contact_device.chassis_number',
                    'contact_device.manufacturing_year',
                    'contact_device.car_type',
                    'model.name AS model_name',
                    'brand.name AS brand_name'
                )
                ->where('repair_job_sheets.id', $repair_job_sheet_id)
                ->first();

            // Merge vehicle information
            if ($vehicleInfo) {
                foreach ($vehicleInfo as $key => $value) {
                    $repair->$key = $value;
                }
            }
        }

        return $repair;
    }

    /**
     * Calculate order taxes
     *
     * @param \App\Transaction $transaction
     * @return array
     */
    private function calculateOrderTaxes($transaction): array
    {
        $order_taxes = [];
        
        if (!empty($transaction->tax)) {
            if ($transaction->tax->is_tax_group) {
                $order_taxes = $this->transactionUtil->sumGroupTaxDetails(
                    $this->transactionUtil->groupTaxDetails($transaction->tax, $transaction->tax_amount)
                );
            } else {
                $order_taxes[$transaction->tax->name] = $transaction->tax_amount;
            }
        }

        return $order_taxes;
    }

    /**
     * Generate transaction PDF
     *
     * @param \App\Transaction $transaction
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    private function generateTransactionPDF($transaction)
    {
        try {
            $mpdf = new Mpdf([
                'tempDir' => public_path('uploads/temp'),
                'mode' => 'utf-8',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'autoVietnamese' => true,
                'autoArabic' => true,
                'margin_top' => 8,
                'margin_bottom' => 8,
            ]);

            $html = view('treasury::print', compact('transaction'))->render();

            $mpdf->useSubstitutions = true;
            $mpdf->SetTitle(__('treasury::lang.transaction') . ' | ' . $transaction->invoice_no);
            $mpdf->WriteHTML($html);

            return $mpdf->Output('treasury_transaction.pdf', 'I');

        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            throw $e;
        }
    }
}