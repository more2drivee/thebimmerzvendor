<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Product;
use App\Transaction;
use App\TransactionSellLine;
use App\Utils\ModuleUtil;
use App\Utils\ContactUtil;
use App\Utils\ProductUtil;
use App\Utils\BusinessUtil;
use App\Utils\TransactionUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\NotificationUtil;
use App\Utils\StockSyncUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\VariationLocationDetails;
use App\Utils\Util;
use App\PurchaseLine;
use Carbon\Carbon;
use App\ExpenseCategory;
use Modules\Repair\Entities\JobSheet;

class SparePartsController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $contactUtil;
    protected $productUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $cashRegisterUtil;
    protected $moduleUtil;
    protected $notificationUtil;
    protected $commonUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        NotificationUtil $notificationUtil,
        Util $commonUtil
    ) {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;
        $this->commonUtil = $commonUtil;
    }


    public function complete_jobsheet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_order_id' => 'required|integer|min:1',
            'contact_id' => 'required|integer|min:1',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $job_order_id = $request->input('job_order_id');
        $contact_id = $request->input('contact_id');
        $data = $request->input('data');

        $transaction = Transaction::where('repair_job_sheet_id', $job_order_id)->first();
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        try {
            DB::transaction(function () use ($job_order_id, $contact_id, $data, $transaction) {
                // Lock the transaction row to serialize concurrent calls
                // for the same job order, preventing VLD race conditions
                Transaction::where('id', $transaction->id)->lockForUpdate()->first();

                if (is_array($data)) {
                    $this->processProductData($job_order_id, $data);
                    $this->checkStockAndCreatePurchaseRequisition($data, $job_order_id);
                }

                $this->syncSellLinesForJobOrder($job_order_id, $transaction, 'final', $contact_id);

                // Complete any active or paused timers for this job sheet
                $now = Carbon::now();
                DB::table('timer_tracking')
                    ->where('job_sheet_id', $job_order_id)
                    ->whereIn('status', ['active', 'paused'])
                    ->update([
                        'status' => 'completed',
                        'completed_at' => $now,
                        'updated_at' => $now,
                    ]);

                $repairStatusId = \Modules\Repair\Entities\RepairStatus::firstOrCreate([
                    'name' => 'تم الانتهاء الاعمال'
                ])->id;

                $jobSheet = JobSheet::find($job_order_id);
                if ($jobSheet) {
                    $jobSheet->status_id = $repairStatusId;
                    $jobSheet->save();
                }
            });

            return response()->json(['message' => 'Joborder Completed Successfully']);
        } catch (\Exception $e) {
            Log::error('Error completing jobsheet', [
                'error' => $e->getMessage(),
                'job_order_id' => $job_order_id,
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function store_spareparts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_order_id' => 'required|integer|min:1',
            'contact_id'   => 'required|integer|min:1',
            'data'         => 'nullable|array',
            'data.*.product_id'       => 'required|integer|min:1',
            'data.*.delivered_status' => 'nullable|boolean',
            'data.*.out_for_deliver'  => 'nullable|boolean',
            'data.*.client_approval'  => 'required|boolean',
            'data.*.quantity'         => 'required|numeric|min:0.0001',
            'data.*.price'            => 'required|numeric|min:0',
            'data.*.product_status'   => 'nullable|string|in:black,red,orange'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $job_order_id = $request->input('job_order_id');
        $contact_id = $request->input('contact_id');
        $data = $request->input('data', []);

        $transaction = Transaction::where('repair_job_sheet_id', $job_order_id)->first();
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        try {
            $spareParts = null;
            Log::error('DIAG SpareParts store_spareparts hit', [
                'job_order_id' => $job_order_id,
                'contact_id' => $contact_id,
                'items_count' => is_array($data) ? count($data) : 0,
            ]);
            DB::transaction(function () use ($job_order_id, $data, $contact_id, $transaction, &$spareParts) {
                // Lock the transaction row to serialize concurrent store_spareparts calls
                // for the same job order, preventing VLD race conditions
                Transaction::where('id', $transaction->id)->lockForUpdate()->first();

                $this->processProductData($job_order_id, $data);
                $this->checkStockAndCreatePurchaseRequisition($data, $job_order_id);
                $spareParts = $this->syncSellLinesForJobOrder($job_order_id, $transaction, 'under processing', $contact_id);
            });

            return response()->json([
                'message' => 'Products processed successfully',
                'data' => $spareParts,
                'payment_status' => $transaction->fresh()->payment_status ?? 'due',
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing spare parts', [
                'error' => $e->getMessage(),
                'job_order_id' => $job_order_id,
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function processProductData($job_order_id, $productDataList)
    {
        $existingRows = DB::table('product_joborder')
            ->where('job_order_id', $job_order_id)
            ->get()
            ->keyBy('product_id');
        $productIds = array_column($productDataList, 'product_id');

        $toUpdate = [];
        $toInsert = [];
        $allowedStatuses = ['black', 'red', 'orange'];

        foreach ($productDataList as $productData) {
            $statusProvided = array_key_exists('product_status', $productData) &&
                in_array($productData['product_status'], $allowedStatuses, true);
            $data = [
                'quantity' => $productData['quantity'],
                'price' => $productData['price'],
                'delivered_status' => $productData['delivered_status'] ?? 0,
                'out_for_deliver' => $productData['out_for_deliver'] ?? 0,
                'client_approval' => $productData['client_approval'],
            ];

            $is111 = (int) ($productData['delivered_status'] ?? 0) === 1
                && (int) ($productData['out_for_deliver'] ?? 0) === 1
                && (int) $productData['client_approval'] === 1;

            if ($is111) {
                $data['inventory_delivery'] = 0;
            }

            if ($statusProvided) {
                $data['product_status'] = $productData['product_status'];
            }

            if (isset($existingRows[$productData['product_id']])) {
                $toUpdate[] = [
                    'id' => $existingRows[$productData['product_id']]->id,
                    'data' => $data
                ];
            } else {
                $insertData = $data;
                if (!$statusProvided) {
                    $insertData['product_status'] = 'black';
                }
                $toInsert[] = array_merge([
                    'job_order_id' => $job_order_id,
                    'product_id' => $productData['product_id'],
                ], $insertData);
            }
        }

        if (!empty($toInsert)) {
            DB::table('product_joborder')->insert($toInsert);
        }

        foreach ($toUpdate as $update) {
            DB::table('product_joborder')
                ->where('id', $update['id'])
                ->update($update['data']);
        }

        $allExistingProductIds = array_keys($existingRows->all());
        $toDeleteProductIds = array_diff($allExistingProductIds, $productIds);
        if (!empty($toDeleteProductIds)) {
            DB::table('product_joborder')
                ->where('job_order_id', $job_order_id)
                ->whereIn('product_id', $toDeleteProductIds)
                ->delete();
        }
    }

    /**
     * Sync sell lines for a job order using StockSyncUtil directly.
     *
     * Rules:
     * - Only client_approval=1 products get sell lines
     * - Stock NEVER goes negative: sell only min(requested, available)
     * - Shortage is handled by purchase requisition (checkStockAndCreatePurchaseRequisition)
     * - Existing sell lines are updated/deleted as needed
     * - Transaction totals and payment status are recalculated
     *
     * @return \Illuminate\Support\Collection spare parts data for response
     */
    private function syncSellLinesForJobOrder(int $job_order_id, Transaction $transaction, string $status, int $contact_id)
    {
        $stockSyncUtil = app(StockSyncUtil::class);
        $locationId = $transaction->location_id;

        // Update transaction status and contact
        $transaction->status = $status;
        $transaction->contact_id = $contact_id;
        $transaction->sub_status = 'repair';
        $transaction->save();

        // Fetch all spare parts with product + variation info in one query
        $spareParts = DB::table('product_joborder')
            ->join('products', 'products.id', '=', 'product_joborder.product_id')
            ->join('variations', 'variations.product_id', '=', 'products.id')
            ->where('product_joborder.job_order_id', $job_order_id)
            ->groupBy(
                'product_joborder.id',
                'product_joborder.product_id',
                'product_joborder.job_order_id',
                'product_joborder.quantity',
                'product_joborder.price',
                'product_joborder.delivered_status',
                'product_joborder.out_for_deliver',
                'product_joborder.client_approval',
                'product_joborder.product_status',
                'products.name',
                'products.enable_stock'
            )
            ->select(
                'product_joborder.id',
                'product_joborder.product_id',
                'product_joborder.job_order_id',
                'product_joborder.quantity',
                'product_joborder.price',
                'product_joborder.delivered_status',
                'product_joborder.out_for_deliver',
                'product_joborder.client_approval',
                'product_joborder.product_status',
                'products.name as product_name',
                'products.enable_stock',
                DB::raw('MIN(variations.id) as variation_id')
            )
            ->get();

        // Batch-fetch existing sell lines keyed by product_id
        $existingSellLines = TransactionSellLine::where('transaction_id', $transaction->id)
            ->get()
            ->keyBy('product_id');

        // Batch-fetch stock for all variations at this location
        $variationIds = $spareParts->pluck('variation_id')->filter()->unique()->toArray();
        $stockMap = [];
        if (!empty($variationIds)) {
            $stockMap = VariationLocationDetails::whereIn('variation_id', $variationIds)
                ->where('location_id', $locationId)
                ->pluck('qty_available', 'variation_id')
                ->toArray();
        }

        // Track which product_ids we process (to delete orphaned sell lines)
        $processedProductIds = [];

        foreach ($spareParts as $sparePart) {
            $productId = (int) $sparePart->product_id;
            $variationId = (int) $sparePart->variation_id;
            $requestedQty = (float) $sparePart->quantity;
            $price = (float) $sparePart->price;
            $enableStock = (int) $sparePart->enable_stock;

            // Only approved products get sell lines
            if (!$sparePart->client_approval) {
                // If there's an existing sell line for a now-unapproved product, delete it
                if ($existingSellLines->has($productId)) {
                    $stockSyncUtil->deleteSellLine($existingSellLines->get($productId)->id, true);
                }
                continue;
            }

            $processedProductIds[] = $productId;

            // Use existing sell line's variation_id for consistency
            $existingLine = $existingSellLines->get($productId);
            if ($existingLine) {
                $variationId = (int) $existingLine->variation_id;
            }

            // Calculate how much we can sell (stock never goes negative)
            if ($enableStock === 1) {
                $currentStock = (float) ($stockMap[$variationId] ?? 0.0);
                $alreadyInTransaction = $existingLine ? (float) $existingLine->quantity : 0.0;
                // Available = current VLD stock + what's already committed in this sell line
                $totalAvailable = $currentStock + $alreadyInTransaction;
                $qtyToSell = min($requestedQty, $totalAvailable);
                $qtyToSell = max(0.0, $qtyToSell);
            } else {
                // Non-stock products (labour etc.) — sell full qty
                $qtyToSell = $requestedQty;
            }

            $productData = [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $qtyToSell,
                'unit_price' => $price,
                'unit_price_inc_tax' => $price,
                'unit_price_before_discount' => $price,
                'line_discount_type' => 'fixed',
                'line_discount_amount' => 0,
                'item_tax' => 0,
                'tax_id' => null,
            ];

            if ($existingLine) {
                if ($qtyToSell > 0) {
                    $stockSyncUtil->createOrUpdateSellLine($transaction, $productData, $existingLine->id);
                } else {
                    $stockSyncUtil->deleteSellLine($existingLine->id, true);
                }
            } else {
                if ($qtyToSell > 0) {
                    $stockSyncUtil->createOrUpdateSellLine($transaction, $productData, null);
                }
            }
        }

        // Delete sell lines for products no longer in the job order
        foreach ($existingSellLines as $prodId => $sellLine) {
            if (!in_array((int) $prodId, $processedProductIds, true)) {
                $stockSyncUtil->deleteSellLine($sellLine->id, true);
            }
        }

        // Recalculate transaction totals
        $stockSyncUtil->recalculateTransactionTotals($transaction->fresh());

        // Sync expenses for external labor products
        $this->syncExternalLaborExpenses($transaction, $spareParts);

        // Re-query fresh data after all processing so response reflects actual DB state
        // Aggregate sell line quantities from the current transaction so qty_available mirrors job sheet view
        $tslSubquery = DB::table('transaction_sell_lines as tsl')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('tsl.transaction_id', $transaction->id)
            ->select('v.product_id', DB::raw('SUM(tsl.quantity) as qty_in_transaction'))
            ->groupBy('v.product_id');

        $freshParts = DB::table('product_joborder')
            ->join('products', 'products.id', '=', 'product_joborder.product_id')
            ->join('variations', 'variations.product_id', '=', 'products.id')
            ->leftJoin('variation_location_details as vld', function ($join) use ($locationId) {
                $join->on('vld.variation_id', '=', 'variations.id')
                     ->where('vld.location_id', '=', $locationId);
            })
            ->leftJoinSub($tslSubquery, 'tsl_sum', function ($join) {
                $join->on('tsl_sum.product_id', '=', 'product_joborder.product_id');
            })
            ->where('product_joborder.job_order_id', $job_order_id)
            ->groupBy(
                'product_joborder.id',
                'product_joborder.product_id',
                'product_joborder.job_order_id',
                'product_joborder.quantity',
                'product_joborder.price',
                'product_joborder.delivered_status',
                'product_joborder.out_for_deliver',
                'product_joborder.client_approval',
                'product_joborder.product_status',
                'products.name',
                'products.enable_stock',
                'tsl_sum.qty_in_transaction'
            )
            ->select(
                'product_joborder.id',
                'product_joborder.product_id',
                'product_joborder.job_order_id',
                'product_joborder.quantity',
                'product_joborder.price',
                'product_joborder.delivered_status',
                'product_joborder.out_for_deliver',
                'product_joborder.client_approval',
                'product_joborder.product_status',
                'products.name as product_name',
                'products.enable_stock',
                DB::raw('MIN(variations.id) as variation_id'),
                DB::raw('COALESCE(SUM(vld.qty_available), 0) + COALESCE(tsl_sum.qty_in_transaction, 0) as qty_available')
            )
            ->get();

        return $freshParts;
    }

 
    public function getSparePartsByJobsheet($job_order_id)
    {
        try {
            if (!is_numeric($job_order_id) || $job_order_id < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid job order ID'
                ], 400);
            }

            $spareParts = DB::table('product_joborder')
                ->leftJoin('products', 'products.id', '=', 'product_joborder.product_id')
                ->where('product_joborder.job_order_id', $job_order_id)
                ->select(
                    'product_joborder.*',
                    'products.name as product_name',
                    'products.sku as product_sku'
                )
                ->get();

            if ($spareParts->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No spare parts found for this job order',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Spare parts retrieved successfully',
                'data' => $spareParts
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving spare parts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving spare parts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function patchDeliveredStatus($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivered_status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $record = DB::table('product_joborder')->where('id', $id)->first();

            if (!$record) {
                return response()->json(['message' => 'Spare Part not found'], 404);
            }

            $newValue = (int) $request->boolean('delivered_status');
            if ((int) $record->delivered_status === $newValue) {
                return response()->json([
                    'message' => 'Delivered status is already up to date',
                    'data' => $record,
                ], 200);
            }

            DB::table('product_joborder')
                ->where('id', $id)
                ->update(['delivered_status' => $newValue]);

            $updated = DB::table('product_joborder')->where('id', $id)->first();

            return response()->json([
                'message' => 'Delivered status updated successfully',
                'data' => $updated,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating delivered status: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while updating delivered status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $record = DB::table('product_joborder')->where('id', $id)->first();

            if (!$record) {
                return response()->json(['message' => 'Spare part not found'], 404);
            }

            $job_order_id = $record->job_order_id;

            $transaction = Transaction::where('repair_job_sheet_id', $job_order_id)->first();
            if (!$transaction) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            DB::transaction(function () use ($id, $job_order_id, $transaction) {
                Transaction::where('id', $transaction->id)->lockForUpdate()->first();

                DB::table('product_joborder')->where('id', $id)->delete();

                $this->syncSellLinesForJobOrder($job_order_id, $transaction, $transaction->status, $transaction->contact_id);
            });

            return response()->json(['message' => 'Spare part deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting spare part', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    public function markInventoryDelivery($id, Request $request)
    {
        try {
            $user = auth()->user();

            $record = DB::table('product_joborder as pjo')
                ->join('repair_job_sheets as rjs', 'rjs.id', '=', 'pjo.job_order_id')
                ->where('pjo.id', (int) $id)
                ->where('rjs.location_id', (int) $user->location_id)
                ->select('pjo.*')
                ->first();

            if (!$record) {
                return response()->json(['message' => 'Spare Part not found'], 404);
            }

            if ((int) $record->delivered_status !== 1 || (int) $record->out_for_deliver !== 1 || (int) $record->client_approval !== 1) {
                return response()->json([
                    'message' => 'Spare Part must be in delivered/out_for_deliver/client_approval state (111) before inventory delivery can be marked',
                    'data' => $record,
                ], 409);
            }

            DB::transaction(function () use ($id, $record) {
                // Update product_joborder flags: 111 -> 011 and mark inventory_delivery
                DB::table('product_joborder')
                    ->where('id', (int) $id)
                    ->update([
                        'inventory_delivery' => 1,
                        'delivered_status' => 0,
                    ]);

                $job_order_id = (int) $record->job_order_id;

                $transaction = Transaction::where('repair_job_sheet_id', $job_order_id)->first();
                if (!$transaction) {
                    throw new \Exception('Related transaction not found for this job sheet');
                }

                $stockSyncUtil = app(StockSyncUtil::class);

                // Find sell lines for this product and delete them (restores stock)
                $sellLines = TransactionSellLine::where('transaction_id', $transaction->id)
                    ->where('product_id', (int) $record->product_id)
                    ->get();

                if ($sellLines->isEmpty()) {
                    throw new \Exception('No transaction sell line found for this product on the related transaction');
                }

                // Also find children (modifiers/combo) of these sell lines
                $parentIds = $sellLines->pluck('id')->toArray();
                $childLines = TransactionSellLine::where('transaction_id', $transaction->id)
                    ->whereIn('parent_sell_line_id', $parentIds)
                    ->get();

                // Delete children first, then parents
                foreach ($childLines as $child) {
                    $stockSyncUtil->deleteSellLine($child->id, true);
                }
                foreach ($sellLines as $sellLine) {
                    $stockSyncUtil->deleteSellLine($sellLine->id, true);
                }

                // Recalculate transaction totals
                $stockSyncUtil->recalculateTransactionTotals($transaction->fresh());
            });

            $updated = DB::table('product_joborder as pjo')
                ->join('repair_job_sheets as rjs', 'rjs.id', '=', 'pjo.job_order_id')
                ->where('pjo.id', (int) $id)
                ->where('rjs.location_id', (int) $user->location_id)
                ->select('pjo.*')
                ->first();

            return response()->json([
                'message' => 'Inventory delivery marked successfully',
                'data' => $updated,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error marking inventory delivery: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while marking inventory delivery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check stock availability and create purchase requisition for out-of-stock products
     */
    public function checkStockAndCreatePurchaseRequisition($productDataList, $job_order_id)
    {
        try {
            $transaction = Transaction::where('repair_job_sheet_id', $job_order_id)->first();
            Log::error('DIAG SpareParts checkStock entry', [
                'job_order_id' => $job_order_id,
                'has_transaction' => (bool) $transaction,
                'items_count' => is_array($productDataList) ? count($productDataList) : 0,
            ]);
            
            if (!$transaction || !$transaction->location_id) {
                return;
            }

            $business_id = $transaction->business_id;
            $location_id = $transaction->location_id;
            $productIds = array_column($productDataList, 'product_id');
            
            // Validate that all products exist in product_joborder for this job order
            $validProductIds = DB::table('product_joborder')
                ->where('job_order_id', $job_order_id)
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->toArray();
            
            // Filter productDataList to only include products that exist in product_joborder
            $filteredProductDataList = array_filter($productDataList, function ($item) use ($validProductIds) {
                return in_array($item['product_id'], $validProductIds, true);
            });
            
            if (empty($filteredProductDataList)) {
                return;
            }
            
            $productIds = array_column($filteredProductDataList, 'product_id');
            
            $products = Product::whereIn('id', $productIds)
        
                ->get()
                ->keyBy('id');

            if ($products->isEmpty()) {
                return;
            }

            $variations = DB::table('variations')
                ->whereIn('product_id', $products->keys()->toArray())
                ->get()
                ->groupBy('product_id');

            $variationIds = $variations->flatten()->pluck('id')->toArray();
            $stockDetails = VariationLocationDetails::whereIn('variation_id', $variationIds)
                ->where('location_id', $location_id)
                ->get()
                ->keyBy('variation_id');

            // Get transaction sell lines to calculate qty already in transaction
            $transactionSellLines = DB::table('transaction_sell_lines')
                ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
                ->where('transaction_sell_lines.transaction_id', $transaction->id)
                ->select('variations.product_id', DB::raw('SUM(transaction_sell_lines.quantity) as qty_in_transaction'))
                ->groupBy('variations.product_id')
                ->get()
                ->keyBy('product_id');

            // Fetch existing purchase lines for this job order (ALL statuses including received)
            // to know how much qty is already on order and avoid creating duplicate purchases
            $existingPurchaseQty = DB::table('purchase_lines')
                ->join('transactions', 'transactions.id', '=', 'purchase_lines.transaction_id')
                ->where('transactions.type', 'purchase')
                ->where('transactions.location_id', $location_id)
                ->where('transactions.repair_job_sheet_id', $job_order_id)
                ->whereNull('transactions.deleted_at')
                ->select(
                    'purchase_lines.variation_id',
                    'transactions.status',
                    DB::raw('SUM(COALESCE(purchase_lines.asked_qty, purchase_lines.quantity)) as total_asked'),
                    DB::raw('SUM(CASE WHEN transactions.status = \'received\' THEN purchase_lines.quantity ELSE 0 END) as total_received')
                )
                ->groupBy('purchase_lines.variation_id', 'transactions.status')
                ->get();

            // Build maps for on-order quantities:
            // - adjustableOnOrderByVariation: qty in pending/ordered/partial purchases (we can update these lines)
            // - lockedOnOrderByVariation: qty implied by received transactions (do not edit here)
            $adjustableOnOrderByVariation = [];
            $lockedOnOrderByVariation = [];
            foreach ($existingPurchaseQty as $row) {
                $varId = (int)$row->variation_id;
                $status = (string) $row->status;
                $totalAsked = (float) $row->total_asked;
                $totalReceived = (float) $row->total_received;

                if (!isset($adjustableOnOrderByVariation[$varId])) {
                    $adjustableOnOrderByVariation[$varId] = 0.0;
                }
                if (!isset($lockedOnOrderByVariation[$varId])) {
                    $lockedOnOrderByVariation[$varId] = 0.0;
                }

                if ($row->status === 'received') {
                    // Received transactions are treated as locked reference qty.
                    // Remaining asked-received should reduce what we ask from adjustable purchases.
                    $lockedOnOrderByVariation[$varId] += max(0.0, $totalAsked - $totalReceived);
                } else {
                    // Pending/ordered/partial: this is editable on-order qty.
                    $adjustableOnOrderByVariation[$varId] += $totalAsked;
                }
            }

            Log::error('DIAG SpareParts requisition baseline', [
                'job_order_id' => $job_order_id,
                'transaction_id' => $transaction->id,
                'location_id' => $location_id,
                'adjustable_on_order_by_variation' => $adjustableOnOrderByVariation,
                'locked_on_order_by_variation' => $lockedOnOrderByVariation,
            ]);

            $productsForPurchase = [];

            foreach ($filteredProductDataList as $productData) {
                if (!isset($productData['client_approval']) || !$productData['client_approval']) {
                    continue;
                }

                if (!$products->has($productData['product_id'])) {
                    continue;
                }

                // If product is non-stock (enable_stock = 0), skip shortage handling entirely
                $prod = $products->get($productData['product_id']);
                if (isset($prod->enable_stock) && (int)$prod->enable_stock === 0) {
                    continue;
                }

                $productVariations = $variations->get($productData['product_id']);
                if (!$productVariations || $productVariations->isEmpty()) {
                    continue;
                }

                $variation = $productVariations->first();
                $stock = $stockDetails->get($variation->id);
                $stockQty = $stock ? (float)$stock->qty_available : 0.0;
                
                // Get qty already in transaction for this product
                $qtyInTransaction = $transactionSellLines->has($productData['product_id']) 
                    ? (float)$transactionSellLines->get($productData['product_id'])->qty_in_transaction 
                    : 0.0;
                
                // Total available = stock + already in transaction
                $totalQtyAvailable = $stockQty + $qtyInTransaction;
                
                $requestedQty = isset($productData['quantity']) ? (float)$productData['quantity'] : 0.0;
                $shortageQty = max(0.0, $requestedQty - $totalQtyAvailable);

                Log::error('DIAG SpareParts shortage evaluation', [
                    'job_order_id' => $job_order_id,
                    'product_id' => (int) $productData['product_id'],
                    'variation_id' => (int) $variation->id,
                    'requested_qty' => $requestedQty,
                    'stock_qty' => $stockQty,
                    'qty_in_transaction' => $qtyInTransaction,
                    'total_qty_available' => $totalQtyAvailable,
                    'shortage_qty' => $shortageQty,
                ]);

                if ($shortageQty > 0) {
                    // Exact-match target for editable purchases:
                    // target_adjustable = shortage - locked_on_order_from_received
                    // This allows both increasing and decreasing editable purchase lines.
                    $lockedOnOrder = $lockedOnOrderByVariation[(int)$variation->id] ?? 0.0;
                    $currentAdjustableOnOrder = $adjustableOnOrderByVariation[(int)$variation->id] ?? 0.0;
                    $targetAdjustableQty = max(0.0, $shortageQty - $lockedOnOrder);

                    Log::error('DIAG SpareParts shortage after on-order deduction', [
                        'job_order_id' => $job_order_id,
                        'product_id' => (int) $productData['product_id'],
                        'variation_id' => (int) $variation->id,
                        'shortage_qty' => $shortageQty,
                        'locked_on_order' => $lockedOnOrder,
                        'current_adjustable_on_order' => $currentAdjustableOnOrder,
                        'target_adjustable_qty' => $targetAdjustableQty,
                    ]);

                    if ($targetAdjustableQty <= 0) {
                        continue;
                    }

                    DB::table('product_joborder')
                        ->where('job_order_id', $job_order_id)
                        ->where('product_id', $productData['product_id'])
                        ->update([
                            'delivered_status' => 0,
                            'out_for_deliver' => 0,
                        ]);

                    $productsForPurchase[] = [
                        'product_id' => $productData['product_id'],
                        'variation_id' => $variation->id,
                        'quantity' => $targetAdjustableQty,
                        'price' => isset($productData['price']) ? (float)$productData['price'] : 0,
                    ];
                }
            }

            Log::error('DIAG SpareParts requisition payload before sync', [
                'job_order_id' => $job_order_id,
                'products_for_purchase' => $productsForPurchase,
                'preserve_variation_ids' => [],
            ]);

            $this->syncPurchaseRequisition($productsForPurchase, $business_id, $location_id, $job_order_id, []);
            
        } catch (\Exception $e) {
            Log::error('Error checking stock for purchase requisition', [
                'error' => $e->getMessage(),
                'job_order_id' => $job_order_id
            ]);
        }
    }

    private function syncPurchaseRequisition($products, $business_id, $location_id, $job_order_id, $preserveVariationIds = [])
    {
        try {
            $jobsheet = JobSheet::find($job_order_id);
            $jobsheet_no = $jobsheet ? $jobsheet->job_sheet_no : null;

            DB::beginTransaction();

            $repairTransactionId = Transaction::where('repair_job_sheet_id', $job_order_id)
                ->orderByDesc('id')
                ->value('id');

            $desiredByVariation = [];
            foreach ($products as $p) {
                $desiredByVariation[(int)$p['variation_id']] = $p;
            }
            $desiredVariationIds = array_keys($desiredByVariation);
            $preserveVariationIds = array_values(array_unique(array_map('intval', $preserveVariationIds)));

            Log::error('DIAG SpareParts syncPurchaseRequisition start', [
                'job_order_id' => $job_order_id,
                'desired_variation_ids' => $desiredVariationIds,
                'preserve_variation_ids' => $preserveVariationIds,
                'products_count' => count($products),
            ]);

            $productIds = !empty($products)
                ? array_values(array_unique(array_column($products, 'product_id')))
                : [];

            $jobOrderData = !empty($productIds)
                ? DB::table('product_joborder')
                    ->where('job_order_id', $job_order_id)
                    ->whereIn('product_id', $productIds)
                    ->get()
                    ->keyBy('product_id')
                : collect();

            $supplierMap = $jobOrderData->pluck('supplier_id', 'product_id');
            $purchasePriceMap = $jobOrderData->pluck('purchase_price', 'product_id');

            $existingPurchases = Transaction::where('type', 'purchase')
                ->where('location_id', $location_id)
                ->where('repair_job_sheet_id', $job_order_id)
                ->whereIn('status', ['pending', 'ordered', 'partial'])
                ->get();

            // ── STEP 1: Clean up purchase lines that are no longer needed ──
            foreach ($existingPurchases as $tr) {
                $existingLines = PurchaseLine::where('transaction_id', $tr->id)->get();
                $deletedLineIds = [];

                foreach ($existingLines as $line) {
                    $varId = (int)$line->variation_id;

                    if (in_array($varId, $desiredVariationIds, true)) {
                        Log::error('DIAG SpareParts line preserved (desired)', [
                            'job_order_id' => $job_order_id,
                            'transaction_id' => $tr->id,
                            'purchase_line_id' => $line->id,
                            'variation_id' => $varId,
                        ]);
                        continue;
                    }

                    if (in_array($varId, $preserveVariationIds, true)) {
                        Log::error('DIAG SpareParts line preserved (covered by existing order)', [
                            'job_order_id' => $job_order_id,
                            'transaction_id' => $tr->id,
                            'purchase_line_id' => $line->id,
                            'variation_id' => $varId,
                        ]);
                        continue;
                    }

                    if ((float)$line->quantity_sold > 0) {
                        Log::error('DIAG SpareParts line preserved (already received)', [
                            'job_order_id' => $job_order_id,
                            'transaction_id' => $tr->id,
                            'purchase_line_id' => $line->id,
                            'variation_id' => $varId,
                            'quantity_sold' => (float) $line->quantity_sold,
                        ]);
                        continue;
                    }

                    DB::table('purchase_lines')->where('id', $line->id)->delete();
                    $deletedLineIds[] = (int) $line->id;
                }

                $remainingCount = PurchaseLine::where('transaction_id', $tr->id)->count();
                Log::error('DIAG SpareParts transaction cleanup result', [
                    'job_order_id' => $job_order_id,
                    'transaction_id' => $tr->id,
                    'deleted_line_ids' => $deletedLineIds,
                    'remaining_lines_count' => $remainingCount,
                ]);

                if ($remainingCount === 0) {
                    Log::error('DIAG SpareParts force deleting empty purchase transaction', [
                        'job_order_id' => $job_order_id,
                        'transaction_id' => $tr->id,
                    ]);
                    $this->forceDeletePurchaseTransaction($tr);
                } else {
                    $this->recalcPurchaseTotal($tr);
                }
            }

            // ── STEP 2: Add or update desired purchase lines ──
            if (!empty($products)) {
                $groups = [];
                foreach ($products as $p) {
                    $supplierId = $supplierMap[$p['product_id']] ?? null;
                    $key = $supplierId === null ? 'null' : (string)$supplierId;
                    if (!isset($groups[$key])) {
                        $groups[$key] = ['supplier_id' => $supplierId, 'items' => []];
                    }
                    $groups[$key]['items'][] = $p + ['supplier_id' => $supplierId];
                }

                $existingPurchases = Transaction::where('type', 'purchase')
                    ->where('location_id', $location_id)
                    ->where('repair_job_sheet_id', $job_order_id)
                    ->whereIn('status', ['pending', 'ordered', 'partial'])
                    ->get();

                $existingBySupplier = [];
                foreach ($existingPurchases as $tr) {
                    $k = $tr->contact_id === null ? 'null' : (string)$tr->contact_id;
                    $existingBySupplier[$k] = $tr;
                }

                $notes = 'Auto-generated for spare parts - JobOrder ID: ' . $job_order_id;
                if ($jobsheet_no) {
                    $notes .= ', JobSheet No: ' . $jobsheet_no;
                }

                foreach ($groups as $key => $group) {
                    $supplierId = $group['supplier_id'];
                    $desiredItems = $group['items'];

                    if (isset($existingBySupplier[$key])) {
                        $tr = $existingBySupplier[$key];
                        if ($tr->invoice_ref !== $repairTransactionId) {
                            $tr->invoice_ref = $repairTransactionId;
                            $tr->save();
                        }

                        $existingLines = PurchaseLine::where('transaction_id', $tr->id)
                            ->get()->keyBy('variation_id');

                        foreach ($desiredItems as $p) {
                            $price = $this->resolvePurchasePrice($p, $purchasePriceMap);
                            $line = $existingLines->get($p['variation_id']);
                            if ($line) {
                                if ((float)$line->quantity_sold <= 0) {
                                    // $p['quantity'] is exact target adjustable qty for this variation.
                                    $line->quantity = (float) $p['quantity'];
                                    $line->asked_qty = (float) $p['quantity'];
                                    $line->pp_without_discount = $price;
                                    $line->discount_percent = 0;
                                    $line->purchase_price = $price;
                                    $line->purchase_price_inc_tax = $price;
                                    $line->item_tax = 0;
                                    $line->tax_id = null;
                                    $line->save();

                                    Log::error('DIAG SpareParts line updated to exact target', [
                                        'job_order_id' => $job_order_id,
                                        'transaction_id' => $tr->id,
                                        'purchase_line_id' => $line->id,
                                        'variation_id' => (int) $p['variation_id'],
                                        'target_quantity' => (float) $p['quantity'],
                                    ]);
                                }
                            } else {
                                $tr->purchase_lines()->create([
                                    'variation_id' => $p['variation_id'],
                                    'product_id' => $p['product_id'],
                                    'quantity' => $p['quantity'],
                                    'asked_qty' => $p['quantity'],
                                    'pp_without_discount' => $price,
                                    'discount_percent' => 0,
                                    'purchase_price' => $price,
                                    'purchase_price_inc_tax' => $price,
                                    'item_tax' => 0,
                                    'tax_id' => null,
                                    'secondary_unit_quantity' => 0,
                                ]);
                            }
                        }
                        $this->recalcPurchaseTotal($tr);
                    } else {
                        $transaction_data = [
                            'business_id' => $business_id,
                            'location_id' => $location_id,
                            'type' => 'purchase',
                            'status' => 'pending',
                            'payment_status' => 'due',
                            'created_by' => auth()->user()->id ?? 1,
                            'transaction_date' => Carbon::now()->toDateTimeString(),
                            'repair_job_sheet_id' => $job_order_id,
                            'additional_notes' => $notes,
                            'contact_id' => $supplierId,
                            'invoice_ref' => $repairTransactionId,
                        ];

                        $ref_count = $this->commonUtil->setAndGetReferenceCount('purchase', $business_id);
                        $transaction_data['ref_no'] = $this->commonUtil->generateReferenceNumber('purchase', $ref_count, $business_id);

                        $tr = Transaction::create($transaction_data);

                        $purchase_lines = [];
                        foreach ($desiredItems as $product) {
                            $price = $this->resolvePurchasePrice($product, $purchasePriceMap);
                            $purchase_lines[] = [
                                'variation_id' => $product['variation_id'],
                                'product_id' => $product['product_id'],
                                'quantity' => $product['quantity'],
                                'asked_qty' => $product['quantity'],
                                'pp_without_discount' => $price,
                                'discount_percent' => 0,
                                'purchase_price' => $price,
                                'purchase_price_inc_tax' => $price,
                                'item_tax' => 0,
                                'tax_id' => null,
                                'secondary_unit_quantity' => 0,
                            ];
                        }

                        $tr->purchase_lines()->createMany($purchase_lines);
                        $this->recalcPurchaseTotal($tr);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing purchase requisition', [
                'error' => $e->getMessage(),
                'job_order_id' => $job_order_id
            ]);
        }
    }

    private function resolvePurchasePrice(array $product, $purchasePriceMap): float
    {
        if (isset($purchasePriceMap[$product['product_id']]) && $purchasePriceMap[$product['product_id']]) {
            return (float)$purchasePriceMap[$product['product_id']];
        }
        return isset($product['price']) ? (float)$product['price'] : 0.0;
    }

    private function recalcPurchaseTotal(Transaction $tr): void
    {
        $final_total = PurchaseLine::where('transaction_id', $tr->id)
            ->selectRaw('SUM(purchase_price_inc_tax * quantity) as total')
            ->value('total') ?? 0;
        $tr->final_total = (float)$final_total;
        $tr->save();
    }

    private function forceDeletePurchaseTransaction(Transaction $tr): void
    {
        DB::table('transaction_payments')->where('transaction_id', $tr->id)->delete();
        DB::table('purchase_lines')->where('transaction_id', $tr->id)->delete();
        DB::table('transactions')->where('id', $tr->id)->delete();
    }

    // ══════════════════════════════════════════════════════════════════════
    // EXTERNAL LABOR EXPENSE SYNC — remove this block to disable feature
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Sync unpaid expenses for external labor products in the job order.
     *
     * For each approved sell line whose product has is_external=1:
     *   - Create or update an unpaid expense transaction linked to the job order
     * For removed/unapproved external products:
     *   - Delete the corresponding expense transaction
     *
     * Each expense is linked via: type='expense', repair_job_sheet_id, invoice_ref=sell_transaction_id,
     * and a unique ref_no pattern "EXT-{transaction_id}-{product_id}" to match expenses to products.
     */
    private function syncExternalLaborExpenses(Transaction $transaction, $spareParts): void
    {
        try {
            $businessId = $transaction->business_id;
            $locationId = $transaction->location_id;
            $jobSheetId = $transaction->repair_job_sheet_id;

            // Build invoice_no for expense: show sell invoice_no and/or job_sheet_no for easy tracking
            $invoiceNoParts = [];
            if (!empty($transaction->invoice_no)) {
                $invoiceNoParts[] = '#' . $transaction->invoice_no;
            }
            if ($jobSheetId) {
                $jobSheet = JobSheet::find($jobSheetId);
                if ($jobSheet && !empty($jobSheet->job_sheet_no)) {
                    $invoiceNoParts[] = 'JS: ' . $jobSheet->job_sheet_no;
                }
            }
            $expenseInvoiceNo = implode(' - ', $invoiceNoParts) ?: null;

            // Collect spare parts keyed by product_id
            $sparePartsByProductId = [];
            foreach ($spareParts as $sp) {
                $sparePartsByProductId[(int) $sp->product_id] = $sp;
            }

            // Batch-fetch is_external flag for all products in one query
            $productIds = array_keys($sparePartsByProductId);
            if (empty($productIds)) {
                $this->deleteExternalExpensesForTransaction($transaction->id, $jobSheetId, $businessId);
                return;
            }

            $externalProductIds = DB::table('products')
                ->whereIn('id', $productIds)
                ->where('is_external', 1)
                ->pluck('id')
                ->toArray();

            if (empty($externalProductIds)) {
                $this->deleteExternalExpensesForTransaction($transaction->id, $jobSheetId, $businessId);
                return;
            }

            // Build desired external expenses: product_id => {qty, price, name, total}
            $desiredExpenses = [];
            foreach ($externalProductIds as $extProductId) {
                $sp = $sparePartsByProductId[$extProductId] ?? null;
                if (!$sp || !$sp->client_approval) {
                    continue;
                }
                $qty = (float) $sp->quantity;
                $price = (float) $sp->price;
                if ($qty <= 0) {
                    continue;
                }
                $desiredExpenses[$extProductId] = [
                    'product_id' => $extProductId,
                    'product_name' => $sp->product_name ?? ('Product #' . $extProductId),
                    'total' => round($qty * $price, 4),
                    'qty' => $qty,
                    'price' => $price,
                ];
            }

            $categoryId = $this->getOrCreateExternalLaborCategory($businessId);

            // Fetch existing external expenses for this job order
            $existingExpenses = Transaction::where('business_id', $businessId)
                ->where('type', 'expense')
                ->where('repair_job_sheet_id', $jobSheetId)
                ->where('invoice_ref', $transaction->id)
                ->where('ref_no', 'like', 'EXT-' . $transaction->id . '-%')
                ->get()
                ->keyBy(function ($exp) {
                    $parts = explode('-', $exp->ref_no);
                    return (int) end($parts);
                });

            // Upsert: create or update expenses for desired external products
            foreach ($desiredExpenses as $productId => $data) {
                $refNo = 'EXT-' . $transaction->id . '-' . $productId;
                $notes = 'External Labor: ' . $data['product_name'] . ' (Qty: ' . $data['qty'] . ' x ' . $data['price'] . ')';

                if ($existingExpenses->has($productId)) {
                    $exp = $existingExpenses->get($productId);
                    $exp->update([
                        'final_total' => $data['total'],
                        'total_before_tax' => $data['total'],
                        'additional_notes' => $notes,
                        'expense_category_id' => $categoryId,
                    ]);
                } else {
                    Transaction::create([
                        'business_id' => $businessId,
                        'location_id' => $locationId,
                        'type' => 'expense',
                        'status' => 'final',
                        'payment_status' => 'due',
                        'invoice_no' => $expenseInvoiceNo,
                        'ref_no' => $refNo,
                        'transaction_date' => Carbon::now()->toDateTimeString(),
                        'final_total' => $data['total'],
                        'total_before_tax' => $data['total'],
                        'expense_category_id' => $categoryId,
                        'repair_job_sheet_id' => $jobSheetId,
                        'invoice_ref' => $transaction->id,
                        'additional_notes' => $notes,
                        'created_by' => auth()->user()->id ?? 1,
                        'contact_id' => $transaction->contact_id,
                    ]);
                }
            }

            // Delete expenses for products no longer in desired list
            $desiredProductIds = array_keys($desiredExpenses);
            foreach ($existingExpenses as $productId => $exp) {
                if (!in_array($productId, $desiredProductIds, true)) {
                    DB::table('transaction_payments')->where('transaction_id', $exp->id)->delete();
                    $exp->forceDelete();
                }
            }

        } catch (\Exception $e) {
            Log::error('Error syncing external labor expenses', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);
        }
    }

    /**
     * Delete all external labor expenses for a given sell transaction.
     */
    private function deleteExternalExpensesForTransaction(int $transactionId, ?int $jobSheetId, int $businessId): void
    {
        $query = Transaction::where('business_id', $businessId)
            ->where('type', 'expense')
            ->where('invoice_ref', $transactionId)
            ->where('ref_no', 'like', 'EXT-' . $transactionId . '-%');

        if ($jobSheetId) {
            $query->where('repair_job_sheet_id', $jobSheetId);
        }

        $expenseIds = $query->pluck('id')->toArray();
        if (!empty($expenseIds)) {
            DB::table('transaction_payments')->whereIn('transaction_id', $expenseIds)->delete();
            Transaction::whereIn('id', $expenseIds)->forceDelete();
        }
    }

    /**
     * Get or create the "External Labor" expense category for the business.
     */
    private function getOrCreateExternalLaborCategory(int $businessId): int
    {
        $category = ExpenseCategory::where('business_id', $businessId)
            ->where('name', 'External Labor')
            ->whereNull('parent_id')
            ->first();

        if (!$category) {
            $category = ExpenseCategory::create([
                'name' => 'External Labor',
                'business_id' => $businessId,
            ]);
        }

        return $category->id;
    }

    // ══════════════════════════════════════════════════════════════════════
    // END EXTERNAL LABOR EXPENSE SYNC
    // ══════════════════════════════════════════════════════════════════════
}