<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Product;
use App\ProductVariation;
use App\Transaction;
use App\TransactionSellLine;
use App\Variation;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClientFlaggedProductApiController extends ApiController
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        parent::__construct();
        $this->transactionUtil = $transactionUtil;
    }

    public function getProducts(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $products = Product::where('business_id', $business_id)
            ->where('is_client_flagged', 1)
            ->select('id', 'name', 'sku')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                ];
            });

        return $this->respond([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function createProduct(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $business_id = $user->business_id;

        DB::beginTransaction();
        try {
            $unit_id = DB::table('units')->orderBy('id')->value('id');

            $sku = $request->input('sku') ?? 'CFP-' . Str::upper(Str::random(8));

            $existingProduct = Product::where('business_id', $business_id)
                ->where('sku', $sku)
                ->first();

            if ($existingProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'SKU already exists',
                ], 400);
            }

            $product = Product::create([
                'name' => $request->input('name'),
                'business_id' => $business_id,
                'unit_id' => $unit_id,
                'type' => 'single',
                'enable_stock' => 0,
                'virtual_product' => 1,
                'is_client_flagged' => 1,
                'tax_type' => 'exclusive',
                'alert_quantity' => 0,
                'sku' => $sku,
                'barcode_type' => 'C128',
                'created_by' => $user->id,
            ]);

            $productVariation = ProductVariation::create([
                'product_id' => $product->id,
                'name' => 'DUMMY',
                'is_dummy' => 1,
            ]);

            $variation = Variation::create([
                'name' => $product->name,
                'product_id' => $product->id,
                'product_variation_id' => $productVariation->id,
                'sub_sku' => null,
                'default_purchase_price' => 0,
                'dpp_inc_tax' => 0,
                'default_sell_price' => 0,
                'sell_price_inc_tax' => 0,
            ]);

            Log::info('Client flagged product created', [
                'product_id' => $product->id,
                'sku' => $sku,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $product->id,
                    'variation_id' => $variation->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create client flagged product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function sellProduct(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'product_id' => 'required|integer|min:1',
            'qty' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0',
            'job_order_id' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $business_id = $user->business_id;
        $location_id = $user->location_id ?? null;

        if (empty($location_id)) {
            return $this->respondUnauthorized('Location not assigned');
        }

        $product_id = $request->input('product_id');
        $qty = $request->input('qty');
        $price = $request->input('price');
        $job_order_id = $request->input('job_order_id');

        $product = Product::where('business_id', $business_id)
            ->where('is_client_flagged', 1)
            ->find($product_id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Client flagged product not found',
            ], 404);
        }

        $variation = $product->variations->first();

        DB::beginTransaction();
        try {
            $total_before_tax = $price * $qty;

            $invoice_no = $this->transactionUtil->getInvoiceNumber(
                $business_id,
                'final',
                $location_id,
                null,
                'sell'
            );

            $transaction = Transaction::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'type' => 'sell',
                'status' => 'final',
                'payment_status' => 'due',
                'contact_id' => null,
                'tax_id' => null,
                'tax_amount' => 0,
                'discount_type' => null,
                'discount_amount' => 0,
                'shipping_details' => null,
                'shipping_charges' => 0,
                'additional_notes' => 'Client flagged product: ' . $product->name,
                'final_total' => $total_before_tax,
                'total_before_tax' => $total_before_tax,
                'transaction_date' => now(),
                'created_by' => $user->id,
                'is_direct_sale' => 1,
                'invoice_no' => $invoice_no,
                'repair_job_sheet_id' => $job_order_id,
            ]);

            $transactionSellLine = TransactionSellLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'quantity' => $qty,
                'unit_price_before_discount' => $price,
                'unit_price' => $price,
                'unit_price_inc_tax' => $price,
                'line_total' => $total_before_tax,
            ]);

            if (!empty($job_order_id)) {
                DB::table('product_joborder')->insert([
                    'job_order_id' => $job_order_id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $price,
                    'purchase_price' => 0,
                    'delivered_status' => 1,
                    'out_for_deliver' => 1,
                    'client_approval' => 1,
                    'product_status' => 'black',
                    'created_at' => now(),
                ]);

                $jobSheetTransaction = Transaction::where('repair_job_sheet_id', $job_order_id)->first();
                if ($jobSheetTransaction) {
                    $this->transactionUtil->adjustMappingPurchaseSell(
                        null,
                        $jobSheetTransaction,
                        $business_id,
                        []
                    );
                }
            }

            Log::info('Client flagged product sold', [
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'job_order_id' => $job_order_id,
                'qty' => $qty,
                'price' => $price,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction' => [
                        'id' => $transaction->id,
                        'invoice_no' => $transaction->invoice_no,
                        'final_total' => $transaction->final_total,
                        'transaction_date' => $transaction->transaction_date,
                    ],
                    'transaction_sell_line' => [
                        'id' => $transactionSellLine->id,
                        'quantity' => $transactionSellLine->quantity,
                        'unit_price' => $transactionSellLine->unit_price,
                        'line_total' => $transactionSellLine->line_total,
                    ],
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                    ],
                    'variation_id' => $variation->id,
                    'job_order_id' => $job_order_id,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to sell client flagged product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
}
