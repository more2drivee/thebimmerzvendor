<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Business;
use App\TaxRate;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class PurchaseController extends ApiController
{
    /** @var ProductUtil */
    protected $productUtil;
    /** @var TransactionUtil */
    protected $transactionUtil;
    /** @var BusinessUtil */
    protected $businessUtil;
    /** @var ModuleUtil */
    protected $moduleUtil;

    protected $dummyPaymentLine;

    public function __construct(
        ProductUtil $productUtil,
        TransactionUtil $transactionUtil,
        BusinessUtil $businessUtil,
        ModuleUtil $moduleUtil
    ) {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;

        $this->dummyPaymentLine = [
            'method' => 'cash',
            'amount' => 0,
            'note' => '',
            'card_transaction_number' => '',
            'card_number' => '',
            'card_type' => '',
            'card_holder_name' => '',
            'card_month' => '',
            'card_year' => '',
            'card_security' => '',
            'cheque_number' => '',
            'bank_account_number' => '',
            'is_return' => 0,
            'transaction_no' => '',
        ];

        parent::__construct();
    }

    /**
     * Create a purchase from a jobsheet (API)
     * Expected payload:
     * {
     *   "jobsheet_id": 123,
     *   "ref_no": "PO-001",
     *   "transaction_date": "2025-09-29",
     *   "location_id": 1,
     *   "exchange_rate": 1,
     *   "products": [
     *     {"product_id": 1, "quantity": 2, "purchase_price": 10},
     *     {"product_id": 5, "quantity": 1}
     *   ]
     * }
     */
    public function storeFromJobsheet(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        if (!Gate::allows('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        // subscription check
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return response()->json($this->moduleUtil->expiredResponse(), 402);
        }

        $request->validate([
            'jobsheet_id' => 'required|integer',
            'transaction_date' => 'required',
            'location_id' => 'required|integer',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer',
            'products.*.quantity' => 'required|numeric|min:0.0001',
            'products.*.purchase_price' => 'sometimes|numeric',
            'ref_no' => 'sometimes|string',
        ]);

        try {
            $user_id = $user->id;

            $transaction_data = $request->only([
                'ref_no', 'transaction_date', 'location_id', 'exchange_rate', 'additional_notes'
            ]);

            $transaction_data['status'] = 'received';
            $transaction_data['exchange_rate'] = $transaction_data['exchange_rate'] ?? 1;

            // Update business exchange rate setting (matches web behavior)
            Business::update_business($business_id, ['p_exchange_rate' => ($transaction_data['exchange_rate'])]);

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
            $exchange_rate = (float)($transaction_data['exchange_rate'] ?? 1);

            // For simplicity, compute totals from products input
            $total_before_tax = 0;
            foreach ($request->input('products') as $p) {
                $price = isset($p['purchase_price']) ? $this->productUtil->num_uf($p['purchase_price'], $currency_details) * $exchange_rate : 0;
                $qty = $p['quantity'];
                $total_before_tax += ($price * $qty);
            }

            $transaction_data['total_before_tax'] = $total_before_tax;
            $transaction_data['discount_type'] = 'fixed';
            $transaction_data['discount_amount'] = 0;
            $transaction_data['tax_amount'] = 0;
            $transaction_data['shipping_charges'] = 0;
            $transaction_data['final_total'] = $transaction_data['total_before_tax'];

            $transaction_data['business_id'] = $business_id;
            $transaction_data['created_by'] = $user_id;
            $transaction_data['type'] = 'purchase';
            $transaction_data['payment_status'] = 'due';
            $transaction_data['transaction_date'] = $this->productUtil->uf_date($transaction_data['transaction_date'], true);

            // store jobsheet id in a custom field to avoid schema changes
            $transaction_data['custom_field_1'] = $request->input('jobsheet_id');

            DB::beginTransaction();

            // Reference number
            $ref_count = $this->productUtil->setAndGetReferenceCount($transaction_data['type']);
            if (empty($transaction_data['ref_no'])) {
                $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumber($transaction_data['type'], $ref_count);
            }

            // Create transaction
            $transaction = Transaction::create($transaction_data);

            // Build purchases array expected by productUtil
            $products = $request->input('products');
            $purchases = [];
            foreach ($products as $p) {
                $purchases[] = [
                    'product_id' => $p['product_id'],
                    'quantity' => $p['quantity'],
                    'purchase_price' => $p['purchase_price'] ?? 0,
                    // minimal required keys - other keys will be handled by utility
                ];
            }

            $this->productUtil->createOrUpdatePurchaseLines($transaction, $purchases, $currency_details, false);

            // Payment status
            $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            // Adjust stock overselling if present
            $this->productUtil->adjustStockOverSelling($transaction);

            // Activity log
            $this->transactionUtil->activityLog($transaction, 'added');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $transaction->id,
                    'ref_no' => $transaction->ref_no,
                    'status' => $transaction->status,
                ],
                'msg' => __('purchase.purchase_add_success'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    /**
     * Return all purchased products for a given jobsheet id
     * Route: GET connector/api/jobsheet/{jobsheet_id}/purchased-products
     */
    public function getPurchasedProductsByJobsheet($jobsheet_id)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        if (!Gate::allows('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }

        // Query purchase lines joining transactions and products; we stored jobsheet id in custom_field_1
        $products = DB::table('purchase_lines')
            ->join('transactions', 'purchase_lines.transaction_id', '=', 'transactions.id')
            ->join('products', 'purchase_lines.product_id', '=', 'products.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'purchase')
            ->where('transactions.repair_job_sheet_id', $jobsheet_id)
            ->select('products.id as product_id', 'products.name as product_name', 'purchase_lines.quantity', 'purchase_lines.purchase_price', 'transactions.id as transaction_id', 'transactions.ref_no')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ], 200);
    }

    /**
     * Create a draft purchase (API)
     * Route: POST connector/api/purchase-drafts
     */
    public function storeDraft(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        if (!Gate::allows('purchase.create')){
            abort(403, 'Unauthorized action.');
        }

        // subscription check
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return response()->json($this->moduleUtil->expiredResponse(), 402);
        }

        // Accept same payload as web PurchaseController@store, but we will force status=draft
        $transaction_data = $request->only([
            'ref_no', 'contact_id', 'transaction_date', 'total_before_tax', 'location_id',
            'discount_type', 'discount_amount', 'tax_id', 'tax_amount', 'shipping_details',
            'shipping_charges', 'final_total', 'additional_notes', 'exchange_rate',
            'pay_term_number', 'pay_term_type', 'purchase_order_ids'
        ]);

        // Defaults
        $transaction_data['status'] = 'draft';
        $transaction_data['exchange_rate'] = $transaction_data['exchange_rate'] ?? 1;

        // Validate
        $request->validate([
            'contact_id' => 'required|integer',
            'transaction_date' => 'required',
            'total_before_tax' => 'required',
            'location_id' => 'required|integer',
            'final_total' => 'required',
            'document' => 'sometimes|file|max:'.(config('constants.document_size_limit') / 1000),
            'purchases' => 'required|array|min:1',
        ]);

        try {
            $user_id = $user->id;

            // Update business exchange rate setting (matches web behavior)
            Business::update_business($business_id, ['p_exchange_rate' => ($transaction_data['exchange_rate'])]);

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
            $exchange_rate = (float)($transaction_data['exchange_rate'] ?? 1);

            // Unformat and scale numbers
            $transaction_data['total_before_tax'] = $this->productUtil->num_uf($transaction_data['total_before_tax'], $currency_details) * $exchange_rate;

            if (($transaction_data['discount_type'] ?? null) === 'fixed') {
                $transaction_data['discount_amount'] = $this->productUtil->num_uf($transaction_data['discount_amount'] ?? 0, $currency_details) * $exchange_rate;
            } elseif (($transaction_data['discount_type'] ?? null) === 'percentage') {
                $transaction_data['discount_amount'] = $this->productUtil->num_uf($transaction_data['discount_amount'] ?? 0, $currency_details);
            } else {
                $transaction_data['discount_amount'] = 0;
            }

            $transaction_data['tax_amount'] = $this->productUtil->num_uf($transaction_data['tax_amount'] ?? 0, $currency_details) * $exchange_rate;
            $transaction_data['shipping_charges'] = $this->productUtil->num_uf($transaction_data['shipping_charges'] ?? 0, $currency_details) * $exchange_rate;
            $transaction_data['final_total'] = $this->productUtil->num_uf($transaction_data['final_total'], $currency_details) * $exchange_rate;

            $transaction_data['business_id'] = $business_id;
            $transaction_data['created_by'] = $user_id;
            $transaction_data['type'] = 'purchase';
            $transaction_data['payment_status'] = 'due';
            $transaction_data['transaction_date'] = $this->productUtil->uf_date($transaction_data['transaction_date'], true);

            // upload document
            $transaction_data['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            // custom fields
            $transaction_data['custom_field_1'] = $request->input('custom_field_1', null);
            $transaction_data['custom_field_2'] = $request->input('custom_field_2', null);
            $transaction_data['custom_field_3'] = $request->input('custom_field_3', null);
            $transaction_data['custom_field_4'] = $request->input('custom_field_4', null);

            $transaction_data['shipping_custom_field_1'] = $request->input('shipping_custom_field_1', null);
            $transaction_data['shipping_custom_field_2'] = $request->input('shipping_custom_field_2', null);
            $transaction_data['shipping_custom_field_3'] = $request->input('shipping_custom_field_3', null);
            $transaction_data['shipping_custom_field_4'] = $request->input('shipping_custom_field_4', null);
            $transaction_data['shipping_custom_field_5'] = $request->input('shipping_custom_field_5', null);

            for ($i = 1; $i <= 4; $i++) {
                if ($request->input("additional_expense_value_{$i}") !== null && $request->input("additional_expense_value_{$i}") !== '') {
                    $transaction_data["additional_expense_key_{$i}"] = $request->input("additional_expense_key_{$i}");
                    $transaction_data["additional_expense_value_{$i}"] = $this->productUtil->num_uf($request->input("additional_expense_value_{$i}"), $currency_details) * $exchange_rate;
                }
            }

            DB::beginTransaction();

            // Reference number
            $ref_count = $this->productUtil->setAndGetReferenceCount($transaction_data['type']);
            if (empty($transaction_data['ref_no'])) {
                $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumber($transaction_data['type'], $ref_count);
            }

            // Create transaction
            $transaction = Transaction::create($transaction_data);

            // Purchase lines
            $purchases = $request->input('purchases');
            $this->productUtil->createOrUpdatePurchaseLines($transaction, $purchases, $currency_details, $request->input('enable_product_editing', false));

            // Payments (optional for draft)
            if ($request->filled('payment')) {
                $this->transactionUtil->createOrUpdatePaymentLines($transaction, $request->input('payment'));
            }

            // Payment status
            $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            // PO status if any
            if (!empty($transaction->purchase_order_ids)) {
                $this->transactionUtil->updatePurchaseOrderStatus($transaction->purchase_order_ids);
            }

            // Adjust stock overselling if present
            $this->productUtil->adjustStockOverSelling($transaction);

            // Activity log
            $this->transactionUtil->activityLog($transaction, 'added');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $transaction->id,
                    'ref_no' => $transaction->ref_no,
                    'status' => $transaction->status,
                ],
                'msg' => __('purchase.purchase_add_success'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }
}
