<?php

namespace App\Utils;

use App\Product;
use App\Variation;
use App\Transaction;
use App\PurchaseLine;
use App\TransactionSellLine;
use App\VariationLocationDetails;
use App\TransactionSellLinesPurchaseLines;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * StockSyncUtil - Centralized utility for handling stock synchronization
 * 
 * This utility ensures atomic and consistent stock operations across:
 * - variation_location_details (stock levels)
 * - transaction_sell_lines (sold quantities)
 * - purchase_lines (quantity_sold tracking)
 * - transaction_sell_lines_purchase_lines (mapping)
 * - transaction totals
 * 
 * All operations are wrapped in database transactions to prevent inconsistencies.
 */
class StockSyncUtil
{
    protected $productUtil;
    protected $transactionUtil;

    public function __construct(ProductUtil $productUtil = null, TransactionUtil $transactionUtil = null)
    {
        $this->productUtil = $productUtil ?? app(ProductUtil::class);
        $this->transactionUtil = $transactionUtil ?? app(TransactionUtil::class);
    }

    /**
     * Adjust sell line quantity with full synchronization
     * 
     * This method handles:
     * 1. Updates the sell line quantity
     * 2. Adjusts variation_location_details
     * 3. Updates purchase_lines quantity_sold
     * 4. Maintains transaction_sell_lines_purchase_lines mapping
     * 5. Recalculates transaction totals
     * 
     * @param int $sellLineId The transaction_sell_lines.id
     * @param float $newQuantity The new quantity to set
     * @param bool $recalculateTotal Whether to recalculate transaction total
     * @return array Result with success status and details
     */
    public function adjustSellLineQuantity(int $sellLineId, float $newQuantity, bool $recalculateTotal = true): array
    {
        return DB::transaction(function () use ($sellLineId, $newQuantity, $recalculateTotal) {
            // Lock the sell line for update
            $sellLine = TransactionSellLine::lockForUpdate()->find($sellLineId);
            
            if (!$sellLine) {
                throw new Exception("Sell line with ID {$sellLineId} not found");
            }

            $transaction = Transaction::lockForUpdate()->find($sellLine->transaction_id);
            if (!$transaction) {
                throw new Exception("Transaction for sell line {$sellLineId} not found");
            }

            $product = Product::find($sellLine->product_id);
            if (!$product) {
                throw new Exception("Product for sell line {$sellLineId} not found");
            }

            $oldQuantity = (float) $sellLine->quantity;
            $quantityDifference = $newQuantity - $oldQuantity;

            if ($quantityDifference == 0) {
                return [
                    'success' => true,
                    'message' => 'No quantity change needed',
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                ];
            }

            $locationId = $transaction->location_id;
            $variationId = $sellLine->variation_id;
            $productId = $sellLine->product_id;

            // Only adjust stock if product has stock enabled
            if ($product->enable_stock == 1) {
                // Step 1: Adjust variation_location_details
                // Decreasing sell qty = increasing stock, Increasing sell qty = decreasing stock
                $stockDelta = -$quantityDifference; // Negative because selling decreases stock
                $this->safeAdjustVariationLocationDetails(
                    $productId,
                    $variationId,
                    $locationId,
                    $stockDelta
                );

                // Step 2: Adjust purchase line mappings
                $this->adjustPurchaseSellMapping($sellLine, $quantityDifference, $transaction);
            }

            // Step 3: Update the sell line quantity
            $sellLine->quantity = $newQuantity;
            $sellLine->save();

            // Step 4: Recalculate transaction totals if requested
            if ($recalculateTotal) {
                $this->recalculateTransactionTotals($transaction);
            }

            Log::info('StockSyncUtil: Sell line quantity adjusted', [
                'sell_line_id' => $sellLineId,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'difference' => $quantityDifference,
                'location_id' => $locationId,
            ]);

            return [
                'success' => true,
                'message' => 'Sell line quantity adjusted successfully',
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'difference' => $quantityDifference,
                'transaction_id' => $transaction->id,
            ];
        });
    }

    /**
     * Adjust purchase line quantity with full synchronization
     * 
     * This method handles:
     * 1. Updates the purchase line quantity
     * 2. Adjusts variation_location_details
     * 3. Validates against quantity_sold to prevent over-reduction
     * 4. Recalculates transaction totals
     * 
     * @param int $purchaseLineId The purchase_lines.id
     * @param float $newQuantity The new quantity to set
     * @param bool $recalculateTotal Whether to recalculate transaction total
     * @return array Result with success status and details
     */
    public function adjustPurchaseLineQuantity(int $purchaseLineId, float $newQuantity, bool $recalculateTotal = true): array
    {
        return DB::transaction(function () use ($purchaseLineId, $newQuantity, $recalculateTotal) {
            // Lock the purchase line for update
            $purchaseLine = PurchaseLine::lockForUpdate()->find($purchaseLineId);
            
            if (!$purchaseLine) {
                throw new Exception("Purchase line with ID {$purchaseLineId} not found");
            }

            $transaction = Transaction::lockForUpdate()->find($purchaseLine->transaction_id);
            if (!$transaction) {
                throw new Exception("Transaction for purchase line {$purchaseLineId} not found");
            }

            // Only allow adjustment for received purchases
            if ($transaction->status !== 'received') {
                throw new Exception("Cannot adjust quantity for non-received purchase (status: {$transaction->status})");
            }

            $product = Product::find($purchaseLine->product_id);
            if (!$product) {
                throw new Exception("Product for purchase line {$purchaseLineId} not found");
            }

            $oldQuantity = (float) $purchaseLine->quantity;
            $quantityDifference = $newQuantity - $oldQuantity;

            if ($quantityDifference == 0) {
                return [
                    'success' => true,
                    'message' => 'No quantity change needed',
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                ];
            }

            // Validate: Cannot reduce below already sold/adjusted/returned quantity
            $usedQuantity = (float) $purchaseLine->quantity_sold 
                          + (float) $purchaseLine->quantity_adjusted 
                          + (float) $purchaseLine->quantity_returned
                          + (float) $purchaseLine->mfg_quantity_used;

            if ($newQuantity < $usedQuantity) {
                throw new Exception(
                    "Cannot reduce purchase quantity below used quantity. " .
                    "New: {$newQuantity}, Already used: {$usedQuantity} " .
                    "(sold: {$purchaseLine->quantity_sold}, adjusted: {$purchaseLine->quantity_adjusted}, " .
                    "returned: {$purchaseLine->quantity_returned}, mfg_used: {$purchaseLine->mfg_quantity_used})"
                );
            }

            $locationId = $transaction->location_id;
            $variationId = $purchaseLine->variation_id;
            $productId = $purchaseLine->product_id;

            // Only adjust stock if product has stock enabled
            if ($product->enable_stock == 1) {
                // Adjust variation_location_details
                // Increasing purchase qty = increasing stock, Decreasing purchase qty = decreasing stock
                $this->safeAdjustVariationLocationDetails(
                    $productId,
                    $variationId,
                    $locationId,
                    $quantityDifference
                );
            }

            // Update the purchase line quantity
            $purchaseLine->quantity = $newQuantity;
            $purchaseLine->save();

            // Recalculate transaction totals if requested
            if ($recalculateTotal) {
                $this->recalculatePurchaseTransactionTotals($transaction);
            }

            Log::info('StockSyncUtil: Purchase line quantity adjusted', [
                'purchase_line_id' => $purchaseLineId,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'difference' => $quantityDifference,
                'location_id' => $locationId,
            ]);

            return [
                'success' => true,
                'message' => 'Purchase line quantity adjusted successfully',
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'difference' => $quantityDifference,
                'transaction_id' => $transaction->id,
            ];
        });
    }

    /**
     * Create or update a sell line with full stock synchronization
     * 
     * @param Transaction $transaction The parent transaction
     * @param array $productData Product data including product_id, variation_id, quantity, unit_price, etc.
     * @param int|null $existingSellLineId If updating an existing sell line
     * @return TransactionSellLine The created/updated sell line
     */
    public function createOrUpdateSellLine(Transaction $transaction, array $productData, ?int $existingSellLineId = null): TransactionSellLine
    {
        return DB::transaction(function () use ($transaction, $productData, $existingSellLineId) {
            $product = Product::find($productData['product_id']);
            if (!$product) {
                throw new Exception("Product with ID {$productData['product_id']} not found");
            }

            $variation = Variation::find($productData['variation_id']);
            if (!$variation) {
                throw new Exception("Variation with ID {$productData['variation_id']} not found");
            }

            $newQuantity = (float) ($productData['quantity'] ?? 0);
            $locationId = $transaction->location_id;

            if ($existingSellLineId) {
                // Update existing sell line
                $sellLine = TransactionSellLine::lockForUpdate()->find($existingSellLineId);
                if (!$sellLine) {
                    throw new Exception("Sell line with ID {$existingSellLineId} not found");
                }

                $oldQuantity = (float) $sellLine->quantity;
                $quantityDifference = $newQuantity - $oldQuantity;

                // Adjust stock if needed
                if ($product->enable_stock == 1 && $quantityDifference != 0) {
                    // Adjust variation_location_details
                    $this->safeAdjustVariationLocationDetails(
                        $productData['product_id'],
                        $productData['variation_id'],
                        $locationId,
                        -$quantityDifference // Negative because selling decreases stock
                    );

                    // Adjust purchase sell mapping
                    $this->adjustPurchaseSellMapping($sellLine, $quantityDifference, $transaction);
                }

                // Update sell line fields
                $sellLine->fill($this->prepareSellLineData($productData));
                $sellLine->save();

            } else {
                // Create new sell line
                $sellLineData = $this->prepareSellLineData($productData);
                $sellLineData['transaction_id'] = $transaction->id;
                
                $sellLine = TransactionSellLine::create($sellLineData);

                // Adjust stock for new line
                if ($product->enable_stock == 1 && $newQuantity > 0) {
                    // Decrease stock
                    $this->safeAdjustVariationLocationDetails(
                        $productData['product_id'],
                        $productData['variation_id'],
                        $locationId,
                        -$newQuantity
                    );

                    // Create purchase sell mapping if transaction is final
                    if (in_array($transaction->status, ['final', 'under processing'])) {
                        $this->createPurchaseSellMapping($sellLine, $transaction);
                    }
                }
            }

            return $sellLine;
        });
    }

    /**
     * Delete a sell line with full stock synchronization
     * 
     * @param int $sellLineId The sell line ID to delete
     * @param bool $adjustStock Whether to restore stock
     * @return array Result with success status
     */
    public function deleteSellLine(int $sellLineId, bool $adjustStock = true): array
    {
        return DB::transaction(function () use ($sellLineId, $adjustStock) {
            $sellLine = TransactionSellLine::lockForUpdate()->find($sellLineId);
            
            if (!$sellLine) {
                return [
                    'success' => false,
                    'message' => "Sell line with ID {$sellLineId} not found",
                ];
            }

            $transaction = Transaction::find($sellLine->transaction_id);
            $product = Product::find($sellLine->product_id);
            $quantity = (float) $sellLine->quantity;
            $locationId = $transaction ? $transaction->location_id : null;

            if ($adjustStock && $product && $product->enable_stock == 1 && $locationId) {
                // Restore stock
                $this->safeAdjustVariationLocationDetails(
                    $sellLine->product_id,
                    $sellLine->variation_id,
                    $locationId,
                    $quantity // Positive to restore stock
                );

                // Remove purchase sell mappings and restore quantity_sold
                $this->removePurchaseSellMappings($sellLineId);
            }

            // Delete the sell line
            $sellLine->delete();

            Log::info('StockSyncUtil: Sell line deleted', [
                'sell_line_id' => $sellLineId,
                'quantity_restored' => $quantity,
                'location_id' => $locationId,
            ]);

            return [
                'success' => true,
                'message' => 'Sell line deleted successfully',
                'quantity_restored' => $quantity,
            ];
        });
    }

    /**
     * Safely adjust variation_location_details with locking and duplicate handling
     * 
     * @param int $productId
     * @param int $variationId
     * @param int $locationId
     * @param float $delta Positive to increase, negative to decrease
     * @return float The updated qty_available
     */
    public function safeAdjustVariationLocationDetails(int $productId, int $variationId, int $locationId, float $delta): float
    {
        return DB::transaction(function () use ($productId, $variationId, $locationId, $delta) {
            // Lock and get all rows for this combination
            $rows = VariationLocationDetails::where('variation_id', $variationId)
                ->where('product_id', $productId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->get();

            $variation = Variation::find($variationId);
            $productVariationId = $variation ? $variation->product_variation_id : null;

            if ($rows->isEmpty()) {
                // Create new record
                $vld = new VariationLocationDetails();
                $vld->variation_id = $variationId;
                $vld->product_id = $productId;
                $vld->location_id = $locationId;
                $vld->product_variation_id = $productVariationId;
                $vld->qty_available = max(0, $delta); // Don't allow negative for new records
                $vld->save();

                return (float) $vld->qty_available;
            }

            // Use first row, merge duplicates if any
            $vld = $rows->first();
            
            if ($rows->count() > 1) {
                $totalQty = $rows->sum('qty_available');
                // Delete duplicates
                VariationLocationDetails::whereIn('id', $rows->pluck('id')->slice(1)->all())->delete();
                $vld->qty_available = $totalQty;
            }

            // Ensure product_variation_id is set
            if ($productVariationId && empty($vld->product_variation_id)) {
                $vld->product_variation_id = $productVariationId;
            }

            // Apply delta — never allow stock to go negative
            $newQty = ($vld->qty_available ?? 0) + $delta;
            $vld->qty_available = max(0, $newQty);
            $vld->save();

            Log::debug('StockSyncUtil: VLD adjusted', [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'location_id' => $locationId,
                'delta' => $delta,
                'new_qty' => $vld->qty_available,
            ]);

            return (float) $vld->qty_available;
        });
    }

    /**
     * Get current stock for a variation at a location
     * 
     * @param int $variationId
     * @param int $locationId
     * @return float
     */
    public function getStockQuantity(int $variationId, int $locationId): float
    {
        $vld = VariationLocationDetails::where('variation_id', $variationId)
            ->where('location_id', $locationId)
            ->first();

        return $vld ? (float) $vld->qty_available : 0.0;
    }

    /**
     * Validate stock availability before selling
     * 
     * @param int $productId
     * @param int $variationId
     * @param int $locationId
     * @param float $requiredQuantity
     * @param float $existingQuantityInTransaction Quantity already in the transaction (for edits)
     * @return array Validation result
     */
    public function validateStockAvailability(
        int $productId,
        int $variationId,
        int $locationId,
        float $requiredQuantity,
        float $existingQuantityInTransaction = 0
    ): array {
        $product = Product::find($productId);
        
        if (!$product) {
            return [
                'valid' => false,
                'message' => "Product with ID {$productId} not found",
            ];
        }

        // If stock is not enabled, always valid
        if ($product->enable_stock != 1) {
            return [
                'valid' => true,
                'message' => 'Stock not enabled for this product',
                'available' => null,
            ];
        }

        $currentStock = $this->getStockQuantity($variationId, $locationId);
        $effectiveAvailable = $currentStock + $existingQuantityInTransaction;

        if ($requiredQuantity > $effectiveAvailable) {
            return [
                'valid' => false,
                'message' => "Insufficient stock. Required: {$requiredQuantity}, Available: {$effectiveAvailable}",
                'available' => $effectiveAvailable,
                'shortage' => $requiredQuantity - $effectiveAvailable,
            ];
        }

        return [
            'valid' => true,
            'message' => 'Stock available',
            'available' => $effectiveAvailable,
        ];
    }

    /**
     * Synchronize all stock data for a transaction
     * 
     * This is a repair/sync function that ensures all related data is consistent.
     * Use with caution - it will recalculate everything.
     * 
     * @param int $transactionId
     * @return array Sync results
     */
    public function syncTransactionStock(int $transactionId): array
    {
        return DB::transaction(function () use ($transactionId) {
            $transaction = Transaction::with('sell_lines')->lockForUpdate()->find($transactionId);
            
            if (!$transaction) {
                throw new Exception("Transaction with ID {$transactionId} not found");
            }

            $results = [
                'transaction_id' => $transactionId,
                'type' => $transaction->type,
                'lines_synced' => 0,
                'stock_adjustments' => [],
            ];

            if ($transaction->type === 'sell') {
                foreach ($transaction->sell_lines as $sellLine) {
                    $product = Product::find($sellLine->product_id);
                    if (!$product || $product->enable_stock != 1) {
                        continue;
                    }

                    // Verify VLD exists
                    $vld = VariationLocationDetails::where('variation_id', $sellLine->variation_id)
                        ->where('location_id', $transaction->location_id)
                        ->first();

                    if (!$vld) {
                        // Create VLD record
                        $this->safeAdjustVariationLocationDetails(
                            $sellLine->product_id,
                            $sellLine->variation_id,
                            $transaction->location_id,
                            0
                        );
                    }

                    // Verify purchase sell mapping exists for final transactions
                    if (in_array($transaction->status, ['final', 'under processing'])) {
                        $mappingQty = TransactionSellLinesPurchaseLines::where('sell_line_id', $sellLine->id)
                            ->sum('quantity');

                        if ($mappingQty != $sellLine->quantity) {
                            $results['stock_adjustments'][] = [
                                'sell_line_id' => $sellLine->id,
                                'issue' => 'mapping_quantity_mismatch',
                                'expected' => $sellLine->quantity,
                                'found' => $mappingQty,
                            ];
                        }
                    }

                    $results['lines_synced']++;
                }

                // Recalculate totals
                $this->recalculateTransactionTotals($transaction);
            }

            return $results;
        });
    }

    /**
     * Adjust purchase sell mapping when sell line quantity changes
     */
    protected function adjustPurchaseSellMapping(TransactionSellLine $sellLine, float $quantityDifference, Transaction $transaction): void
    {
        if ($quantityDifference > 0) {
            // Quantity increased - need to allocate more from purchase lines
            $this->allocateAdditionalPurchase($sellLine, $quantityDifference, $transaction);
        } else {
            // Quantity decreased - need to release back to purchase lines
            $this->releasePurchaseAllocation($sellLine, abs($quantityDifference));
        }
    }

    /**
     * Allocate additional quantity from purchase lines
     */
    protected function allocateAdditionalPurchase(TransactionSellLine $sellLine, float $quantity, Transaction $transaction): void
    {
        $product = Product::find($sellLine->product_id);
        if (!$product || $product->enable_stock != 1) {
            return;
        }

        $business = [
            'id' => $transaction->business_id,
            'location_id' => $transaction->location_id,
            'accounting_method' => session()->get('business.accounting_method', 'fifo'),
            'pos_settings' => ['allow_overselling' => false],
        ];

        // Create a temporary sell line object for mapping
        $tempLine = new \stdClass();
        $tempLine->id = $sellLine->id;
        $tempLine->product_id = $sellLine->product_id;
        $tempLine->variation_id = $sellLine->variation_id;
        $tempLine->quantity = $quantity;

        try {
            $this->transactionUtil->mapPurchaseSell($business, [$tempLine], 'purchase', false);
        } catch (\App\Exceptions\PurchaseSellMismatch $e) {
            // No received purchase lines to map against — create mapping with purchase_line_id=0
            // This is safe: stock was already clamped to 0 in safeAdjustVariationLocationDetails
            TransactionSellLinesPurchaseLines::create([
                'sell_line_id' => $sellLine->id,
                'purchase_line_id' => 0,
                'quantity' => $quantity,
            ]);
            Log::info('StockSyncUtil: Created zero-purchase mapping (no received purchase lines)', [
                'sell_line_id' => $sellLine->id,
                'quantity' => $quantity,
            ]);
        } catch (Exception $e) {
            Log::warning('StockSyncUtil: Could not allocate purchase for additional quantity', [
                'sell_line_id' => $sellLine->id,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Release purchase allocation when sell quantity decreases
     */
    protected function releasePurchaseAllocation(TransactionSellLine $sellLine, float $quantityToRelease): void
    {
        $mappings = TransactionSellLinesPurchaseLines::where('sell_line_id', $sellLine->id)
            ->orderBy('id', 'desc') // Release from most recent first
            ->get();

        $remaining = $quantityToRelease;

        foreach ($mappings as $mapping) {
            if ($remaining <= 0) {
                break;
            }

            $releaseQty = min($remaining, $mapping->quantity);

            // Update purchase line quantity_sold
            if ($mapping->purchase_line_id > 0) {
                PurchaseLine::where('id', $mapping->purchase_line_id)
                    ->decrement('quantity_sold', $releaseQty);
            }

            if ($releaseQty >= $mapping->quantity) {
                // Delete the mapping entirely
                $mapping->delete();
            } else {
                // Reduce the mapping quantity
                $mapping->quantity -= $releaseQty;
                $mapping->save();
            }

            $remaining -= $releaseQty;
        }
    }

    /**
     * Create purchase sell mapping for a new sell line
     */
    protected function createPurchaseSellMapping(TransactionSellLine $sellLine, Transaction $transaction): void
    {
        $product = Product::find($sellLine->product_id);
        if (!$product || $product->enable_stock != 1) {
            return;
        }

        $business = [
            'id' => $transaction->business_id,
            'location_id' => $transaction->location_id,
            'accounting_method' => session()->get('business.accounting_method', 'fifo'),
            'pos_settings' => ['allow_overselling' => false],
        ];

        try {
            $this->transactionUtil->mapPurchaseSell($business, [$sellLine], 'purchase', false);
        } catch (\App\Exceptions\PurchaseSellMismatch $e) {
            // No received purchase lines to map against — create mapping with purchase_line_id=0
            TransactionSellLinesPurchaseLines::create([
                'sell_line_id' => $sellLine->id,
                'purchase_line_id' => 0,
                'quantity' => (float) $sellLine->quantity,
            ]);
            Log::info('StockSyncUtil: Created zero-purchase mapping (no received purchase lines)', [
                'sell_line_id' => $sellLine->id,
                'quantity' => $sellLine->quantity,
            ]);
        } catch (Exception $e) {
            Log::warning('StockSyncUtil: Could not create purchase mapping', [
                'sell_line_id' => $sellLine->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove all purchase sell mappings for a sell line
     */
    protected function removePurchaseSellMappings(int $sellLineId): void
    {
        $mappings = TransactionSellLinesPurchaseLines::where('sell_line_id', $sellLineId)->get();

        foreach ($mappings as $mapping) {
            // Restore quantity_sold to purchase line
            if ($mapping->purchase_line_id > 0) {
                PurchaseLine::where('id', $mapping->purchase_line_id)
                    ->decrement('quantity_sold', $mapping->quantity);
            }
            $mapping->delete();
        }
    }

    /**
     * Prepare sell line data array from product data
     */
    protected function prepareSellLineData(array $productData): array
    {
        return [
            'product_id' => $productData['product_id'],
            'variation_id' => $productData['variation_id'],
            'quantity' => $productData['quantity'] ?? 0,
            'unit_price_before_discount' => $productData['unit_price_before_discount'] ?? $productData['unit_price'] ?? 0,
            'unit_price' => $productData['unit_price'] ?? 0,
            'line_discount_type' => $productData['line_discount_type'] ?? null,
            'line_discount_amount' => $productData['line_discount_amount'] ?? 0,
            'item_tax' => $productData['item_tax'] ?? 0,
            'tax_id' => $productData['tax_id'] ?? null,
            'unit_price_inc_tax' => $productData['unit_price_inc_tax'] ?? $productData['unit_price'] ?? 0,
            'sell_line_note' => $productData['sell_line_note'] ?? '',
            'sub_unit_id' => $productData['sub_unit_id'] ?? null,
            'res_service_staff_id' => $productData['res_service_staff_id'] ?? null,
        ];
    }

    /**
     * Recalculate transaction totals for sell transactions
     */
    public function recalculateTransactionTotals(Transaction $transaction): void
    {
        $sellLines = TransactionSellLine::where('transaction_id', $transaction->id)
            ->whereNull('parent_sell_line_id') // Exclude modifiers/combos
            ->get();

        $totalBeforeTax = 0;
        $totalTax = 0;

        foreach ($sellLines as $line) {
            $lineTotal = $line->unit_price * $line->quantity;
            $lineTax = $line->item_tax * $line->quantity;
            
            $totalBeforeTax += $lineTotal;
            $totalTax += $lineTax;
        }

        // Apply discount
        $discountAmount = 0;
        if ($transaction->discount_type === 'fixed') {
            $discountAmount = (float) $transaction->discount_amount;
        } elseif ($transaction->discount_type === 'percentage') {
            $discountAmount = ($totalBeforeTax * (float) $transaction->discount_amount) / 100;
        }

        // Calculate final total
        $finalTotal = $totalBeforeTax + $totalTax - $discountAmount;
        
        // Add shipping charges
        $finalTotal += (float) ($transaction->shipping_charges ?? 0);

        // Add packing charges
        if (!empty($transaction->packing_charge)) {
            if ($transaction->packing_charge_type === 'fixed') {
                $finalTotal += (float) $transaction->packing_charge;
            } elseif ($transaction->packing_charge_type === 'percent') {
                $finalTotal += ($totalBeforeTax * (float) $transaction->packing_charge) / 100;
            }
        }

        // Round off
        $finalTotal += (float) ($transaction->round_off_amount ?? 0);

        $transaction->total_before_tax = $totalBeforeTax;
        $transaction->tax_amount = $totalTax;
        $transaction->final_total = $finalTotal;
        $transaction->save();

        // Update payment status
        $this->transactionUtil->updatePaymentStatus($transaction->id, $finalTotal);
    }

    /**
     * Recalculate transaction totals for purchase transactions
     */
    public function recalculatePurchaseTransactionTotals(Transaction $transaction): void
    {
        $purchaseLines = PurchaseLine::where('transaction_id', $transaction->id)->get();

        $totalBeforeTax = 0;
        $totalTax = 0;

        foreach ($purchaseLines as $line) {
            $lineTotal = $line->purchase_price * $line->quantity;
            $lineTax = $line->item_tax * $line->quantity;
            
            $totalBeforeTax += $lineTotal;
            $totalTax += $lineTax;
        }

        // Apply discount
        $discountAmount = 0;
        if ($transaction->discount_type === 'fixed') {
            $discountAmount = (float) $transaction->discount_amount;
        } elseif ($transaction->discount_type === 'percentage') {
            $discountAmount = ($totalBeforeTax * (float) $transaction->discount_amount) / 100;
        }

        // Calculate final total
        $finalTotal = $totalBeforeTax + $totalTax - $discountAmount;
        
        // Add shipping charges
        $finalTotal += (float) ($transaction->shipping_charges ?? 0);

        $transaction->total_before_tax = $totalBeforeTax;
        $transaction->tax_amount = $totalTax;
        $transaction->final_total = $finalTotal;
        $transaction->save();

        // Update payment status
        $this->transactionUtil->updatePaymentStatus($transaction->id, $finalTotal);
    }

    /**
     * Bulk update sell lines with full synchronization
     * 
     * @param int $transactionId
     * @param array $products Array of product data with optional transaction_sell_lines_id for updates
     * @return array Results
     */
    public function bulkUpdateSellLines(int $transactionId, array $products): array
    {
        return DB::transaction(function () use ($transactionId, $products) {
            $transaction = Transaction::lockForUpdate()->find($transactionId);
            
            if (!$transaction) {
                throw new Exception("Transaction with ID {$transactionId} not found");
            }

            $results = [
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'errors' => [],
            ];

            $processedIds = [];

            foreach ($products as $productData) {
                try {
                    $existingId = $productData['transaction_sell_lines_id'] ?? null;
                    
                    $sellLine = $this->createOrUpdateSellLine(
                        $transaction,
                        $productData,
                        $existingId
                    );

                    $processedIds[] = $sellLine->id;

                    if ($existingId) {
                        $results['updated']++;
                    } else {
                        $results['created']++;
                    }
                } catch (Exception $e) {
                    $results['errors'][] = [
                        'product_id' => $productData['product_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Delete sell lines not in the update
            $linesToDelete = TransactionSellLine::where('transaction_id', $transactionId)
                ->whereNotIn('id', $processedIds)
                ->whereNull('parent_sell_line_id') // Don't delete modifiers/combos directly
                ->pluck('id');

            foreach ($linesToDelete as $lineId) {
                $this->deleteSellLine($lineId, true);
                $results['deleted']++;
            }

            // Recalculate totals once at the end
            $this->recalculateTransactionTotals($transaction->fresh());

            return $results;
        });
    }

    /**
     * Process full sell lines for a transaction — unified method that handles:
     * 1. Creating/updating/deleting sell lines (delegates to TransactionUtil for complex logic)
     * 2. Stock adjustment (decrease/increase based on status transitions)
     * 3. Purchase-sell mapping (FIFO/LIFO allocation)
     *
     * This replaces the 3 separate calls:
     * - TransactionUtil::createOrUpdateSellLines
     * - ProductUtil::decreaseProductQuantity / adjustProductStockForInvoice
     * - TransactionUtil::mapPurchaseSell / adjustMappingPurchaseSell
     *
     * @param Transaction $transaction The transaction object
     * @param array $products The products array from input
     * @param int $locationId The business location ID
     * @param string $statusBefore The status before the update (null for new transactions)
     * @param array $businessData Business data for purchase-sell mapping ['id', 'accounting_method', 'location_id', 'pos_settings']
     * @param bool $isNew Whether this is a new transaction (store) or update
     * @param array $extraLineParameters Extra parameters for sell lines
     * @param bool $ufData Whether to apply num_uf formatting
     * @return array Result with deleted_lines and sync info
     */
    public function processFullSellLines(
        Transaction $transaction,
        array $products,
        int $locationId,
        ?string $statusBefore,
        array $businessData,
        bool $isNew = true,
        array $extraLineParameters = [],
        bool $ufData = true
    ): array {
        $result = [
            'deleted_lines' => [],
            'stock_adjusted' => false,
            'mapping_adjusted' => false,
        ];

        // Step 1: Create/update/delete sell lines (delegates to TransactionUtil for
        // complex logic: modifiers, combos, warranties, sub-units, etc.)
        if ($isNew) {
            $this->transactionUtil->createOrUpdateSellLines(
                $transaction, $products, $locationId, false, $statusBefore, $extraLineParameters, $ufData
            );
        } else {
            $deleted_lines = $this->transactionUtil->createOrUpdateSellLines(
                $transaction, $products, $locationId, true, $statusBefore, $extraLineParameters, $ufData
            );
            $result['deleted_lines'] = $deleted_lines;
        }

        // Step 2: Adjust stock based on status transition
        $this->adjustStockForInvoice($statusBefore, $transaction, $products, $locationId, $ufData);
        $result['stock_adjusted'] = true;

        // Step 3: Adjust purchase-sell mapping
        $this->adjustMappingPurchaseSell(
            $statusBefore,
            $transaction,
            $businessData,
            $result['deleted_lines']
        );
        $result['mapping_adjusted'] = true;

        return $result;
    }

    /**
     * Adjust product stock for invoice based on status transitions.
     * Replaces ProductUtil::adjustProductStockForInvoice with StockSyncUtil's
     * safe locking mechanism.
     *
     * Handles:
     * - final/under_processing → draft: restore stock (increase)
     * - draft → final/under_processing: decrease stock
     * - final ↔ under_processing: only adjust for NEW products
     * - new transaction with status=final: decrease stock
     *
     * @param string|null $statusBefore Status before the update
     * @param Transaction $transaction The transaction (with updated status)
     * @param array $products Products array from input
     * @param int $locationId Business location ID
     * @param bool $ufData Whether to apply num_uf formatting
     */
    public function adjustStockForInvoice(
        ?string $statusBefore,
        Transaction $transaction,
        array $products,
        int $locationId,
        bool $ufData = true
    ): void {
        $currentStatus = $transaction->status;

        // New transaction with final/under processing status — decrease stock
        if (is_null($statusBefore) && $this->isStockAffectingStatus($currentStatus)) {
            foreach ($products as $product) {
                $qty = $ufData ? $this->productUtil->num_uf($product['quantity']) : (float) $product['quantity'];

                if (!empty($product['base_unit_multiplier'])) {
                    $qty = $qty * $product['base_unit_multiplier'];
                }

                if (!empty($product['enable_stock'])) {
                    $this->safeAdjustVariationLocationDetails(
                        (int) $product['product_id'],
                        (int) $product['variation_id'],
                        $locationId,
                        -$qty
                    );
                }

                // Handle combo products
                if (isset($product['product_type']) && $product['product_type'] == 'combo' && !empty($product['combo'])) {
                    foreach ($product['combo'] as $combo) {
                        $combo_product = Product::find($combo['product_id']);
                        if ($combo_product && $combo_product->enable_stock == 1) {
                            $combo_qty = $ufData ? $this->productUtil->num_uf($combo['quantity']) : (float) $combo['quantity'];
                            $this->safeAdjustVariationLocationDetails(
                                (int) $combo['product_id'],
                                (int) $combo['variation_id'],
                                $locationId,
                                -$combo_qty
                            );
                        }
                    }
                }
            }
            return;
        }

        // final/under_processing → draft: restore stock
        if ($this->isStockAffectingStatus($statusBefore) && $currentStatus == 'draft') {
            foreach ($products as $product) {
                if (!empty($product['transaction_sell_lines_id'])) {
                    $qty = $ufData ? $this->productUtil->num_uf($product['quantity']) : (float) $product['quantity'];

                    if (!empty($product['enable_stock'])) {
                        $this->safeAdjustVariationLocationDetails(
                            (int) $product['product_id'],
                            (int) $product['variation_id'],
                            $locationId,
                            $qty // positive = restore stock
                        );
                    }

                    // Restore combo stock
                    if (isset($product['product_type']) && $product['product_type'] == 'combo' && !empty($product['combo'])) {
                        foreach ($product['combo'] as $combo) {
                            $combo_product = Product::find($combo['product_id']);
                            if ($combo_product && $combo_product->enable_stock == 1) {
                                $combo_qty = $ufData ? $this->productUtil->num_uf($combo['quantity']) : (float) $combo['quantity'];
                                $this->safeAdjustVariationLocationDetails(
                                    (int) $combo['product_id'],
                                    (int) $combo['variation_id'],
                                    $locationId,
                                    $combo_qty
                                );
                            }
                        }
                    }
                }
            }
        }
        // draft → final/under_processing: decrease stock
        elseif ($statusBefore == 'draft' && $this->isStockAffectingStatus($currentStatus)) {
            foreach ($products as $product) {
                $qty = $ufData ? $this->productUtil->num_uf($product['quantity']) : (float) $product['quantity'];

                if (!empty($product['base_unit_multiplier'])) {
                    $qty = $qty * $product['base_unit_multiplier'];
                }

                if (!empty($product['enable_stock'])) {
                    $this->safeAdjustVariationLocationDetails(
                        (int) $product['product_id'],
                        (int) $product['variation_id'],
                        $locationId,
                        -$qty
                    );
                }

                // Handle combo products
                if (isset($product['product_type']) && $product['product_type'] == 'combo' && !empty($product['combo'])) {
                    foreach ($product['combo'] as $combo) {
                        $combo_product = Product::find($combo['product_id']);
                        if ($combo_product && $combo_product->enable_stock == 1) {
                            $combo_qty = $ufData ? $this->productUtil->num_uf($combo['quantity']) : (float) $combo['quantity'];
                            $this->safeAdjustVariationLocationDetails(
                                (int) $combo['product_id'],
                                (int) $combo['variation_id'],
                                $locationId,
                                -$combo_qty
                            );
                        }
                    }
                }
            }
        }
        // final/under_processing ↔ final/under_processing: only adjust NEW products
        elseif ($this->isStockAffectingStatus($statusBefore) && $this->isStockAffectingStatus($currentStatus)) {
            foreach ($products as $product) {
                if (empty($product['transaction_sell_lines_id'])) {
                    $qty = $ufData ? $this->productUtil->num_uf($product['quantity']) : (float) $product['quantity'];

                    if (!empty($product['base_unit_multiplier'])) {
                        $qty = $qty * $product['base_unit_multiplier'];
                    }

                    if (!empty($product['enable_stock'])) {
                        $this->safeAdjustVariationLocationDetails(
                            (int) $product['product_id'],
                            (int) $product['variation_id'],
                            $locationId,
                            -$qty
                        );
                    }

                    // Handle combo products
                    if (isset($product['product_type']) && $product['product_type'] == 'combo' && !empty($product['combo'])) {
                        foreach ($product['combo'] as $combo) {
                            $combo_product = Product::find($combo['product_id']);
                            if ($combo_product && $combo_product->enable_stock == 1) {
                                $combo_qty = $ufData ? $this->productUtil->num_uf($combo['quantity']) : (float) $combo['quantity'];
                                $this->safeAdjustVariationLocationDetails(
                                    (int) $combo['product_id'],
                                    (int) $combo['variation_id'],
                                    $locationId,
                                    -$combo_qty
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Adjust purchase-sell mapping based on status transitions.
     * Replaces TransactionUtil::adjustMappingPurchaseSell with consolidated logic.
     *
     * @param string|null $statusBefore Status before the update
     * @param Transaction $transaction The transaction (with updated status)
     * @param array $businessData Business data ['id', 'accounting_method', 'location_id', 'pos_settings']
     * @param array $deletedLineIds Deleted sell line IDs
     */
    public function adjustMappingPurchaseSell(
        ?string $statusBefore,
        Transaction $transaction,
        array $businessData,
        array $deletedLineIds = []
    ): void {
        $currentStatus = $transaction->status;

        // New transaction with final/under_processing — create mappings
        if (is_null($statusBefore) && $this->isStockAffectingStatus($currentStatus)) {
            try {
                $this->transactionUtil->mapPurchaseSell($businessData, $transaction->sell_lines, 'purchase');
            } catch (\App\Exceptions\PurchaseSellMismatch $e) {
                // Create zero-purchase mappings for lines that couldn't be mapped
                $this->createZeroPurchaseMappingsForTransaction($transaction);
                Log::info('StockSyncUtil: Created zero-purchase mappings for new transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
            return;
        }

        // final/under_processing → draft: remove all mappings, restore quantity_sold
        if ($this->isStockAffectingStatus($statusBefore) && $currentStatus == 'draft') {
            $sell_purchases = Transaction::join('transaction_sell_lines AS SL', 'transactions.id', '=', 'SL.transaction_id')
                ->join('transaction_sell_lines_purchase_lines as TSP', 'SL.id', '=', 'TSP.sell_line_id')
                ->where('transactions.id', $transaction->id)
                ->select('TSP.purchase_line_id', 'TSP.quantity', 'TSP.id')
                ->get()
                ->toArray();

            // Include deleted sell lines
            if (!empty($deletedLineIds)) {
                $deletedIds = array_column($deletedLineIds, 'id');
                $deleted_sell_purchases = TransactionSellLinesPurchaseLines::whereIn('sell_line_id', $deletedIds)
                    ->select('purchase_line_id', 'quantity', 'id')
                    ->get()
                    ->toArray();
                $sell_purchases = array_merge($sell_purchases, $deleted_sell_purchases);
            }

            $sell_purchase_ids = [];
            if (!empty($sell_purchases)) {
                foreach ($sell_purchases as $row) {
                    if ($row['purchase_line_id'] > 0) {
                        PurchaseLine::where('id', $row['purchase_line_id'])
                            ->decrement('quantity_sold', $row['quantity']);
                    }
                    $sell_purchase_ids[] = $row['id'];
                }
                TransactionSellLinesPurchaseLines::whereIn('id', $sell_purchase_ids)->delete();
            }
        }
        // draft → final/under_processing: create mappings for all sell lines
        elseif ($statusBefore == 'draft' && $this->isStockAffectingStatus($currentStatus)) {
            try {
                $this->transactionUtil->mapPurchaseSell($businessData, $transaction->sell_lines, 'purchase');
            } catch (\App\Exceptions\PurchaseSellMismatch $e) {
                $this->createZeroPurchaseMappingsForTransaction($transaction);
                Log::info('StockSyncUtil: Created zero-purchase mappings (draft→final)', [
                    'transaction_id' => $transaction->id,
                ]);
            }
        }
        // final/under_processing ↔ final/under_processing: handle edits
        elseif ($this->isStockAffectingStatus($statusBefore) && $this->isStockAffectingStatus($currentStatus)) {
            // Handle deleted lines
            if (!empty($deletedLineIds)) {
                $deletedIds = array_column($deletedLineIds, 'id');
                $deleted_sell_purchases = TransactionSellLinesPurchaseLines::whereIn('sell_line_id', $deletedIds)
                    ->select('sell_line_id', 'quantity')
                    ->get();
                if (!empty($deleted_sell_purchases)) {
                    foreach ($deleted_sell_purchases as $value) {
                        $this->mapDecrementPurchaseQuantity($value->sell_line_id, $value->quantity);
                    }
                }
            }

            // Check for updated/new sell lines
            $sell_purchases = Transaction::join('transaction_sell_lines AS SL', 'transactions.id', '=', 'SL.transaction_id')
                ->leftjoin('transaction_sell_lines_purchase_lines as TSP', 'SL.id', '=', 'TSP.sell_line_id')
                ->where('transactions.id', $transaction->id)
                ->select(
                    'TSP.id as slpl_id',
                    'TSP.purchase_line_id',
                    'TSP.quantity AS tsp_quantity',
                    'TSP.id as tsp_id',
                    'SL.*'
                )
                ->get();

            $new_sell_lines = [];
            $processed_sell_lines = [];

            foreach ($sell_purchases as $line) {
                if (empty($line->slpl_id)) {
                    $new_sell_lines[] = $line;
                } else {
                    if (in_array($line->slpl_id, $processed_sell_lines)) {
                        continue;
                    }
                    $processed_sell_lines[] = $line->slpl_id;

                    $total_sold_entry = TransactionSellLinesPurchaseLines::where('sell_line_id', $line->id)
                        ->select(DB::raw('SUM(quantity) AS quantity'))
                        ->first();

                    if ($total_sold_entry->quantity != $line->quantity) {
                        if ($line->quantity > $total_sold_entry->quantity) {
                            $line_temp = clone $line;
                            $line_temp->quantity = $line_temp->quantity - $total_sold_entry->quantity;
                            $new_sell_lines[] = $line_temp;
                        } elseif ($line->quantity < $total_sold_entry->quantity) {
                            $decrement_qty = $total_sold_entry->quantity - $line->quantity;
                            $this->mapDecrementPurchaseQuantity($line->id, $decrement_qty);
                        }
                    }
                }
            }

            // Add mapping for new sell lines and incremented quantities
            if (!empty($new_sell_lines)) {
                try {
                    $this->transactionUtil->mapPurchaseSell($businessData, $new_sell_lines);
                } catch (\App\Exceptions\PurchaseSellMismatch $e) {
                    // Create zero-purchase mappings for unmapped lines
                    foreach ($new_sell_lines as $line) {
                        $existingMapping = TransactionSellLinesPurchaseLines::where('sell_line_id', $line->id)->sum('quantity');
                        $unmapped = (float) $line->quantity - $existingMapping;
                        if ($unmapped > 0) {
                            TransactionSellLinesPurchaseLines::create([
                                'sell_line_id' => $line->id,
                                'purchase_line_id' => 0,
                                'quantity' => $unmapped,
                            ]);
                        }
                    }
                    Log::info('StockSyncUtil: Created zero-purchase mappings for edit', [
                        'transaction_id' => $transaction->id,
                    ]);
                }
            }
        }
    }

    /**
     * Decrement purchase quantity from transaction_sell_lines_purchase_lines
     * and purchase_lines.quantity_sold
     *
     * @param int $sellLineId
     * @param float $decrementQty
     */
    protected function mapDecrementPurchaseQuantity(int $sellLineId, float $decrementQty): void
    {
        $sell_purchase_lines = TransactionSellLinesPurchaseLines::where('sell_line_id', $sellLineId)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($sell_purchase_lines as $row) {
            if ($decrementQty <= 0) {
                break;
            }

            if ($row->quantity > $decrementQty) {
                if ($row->purchase_line_id > 0) {
                    PurchaseLine::where('id', $row->purchase_line_id)
                        ->decrement('quantity_sold', $decrementQty);
                }
                $row->quantity = $row->quantity - $decrementQty;
                $row->save();
                $decrementQty = 0;
            } else {
                if ($row->purchase_line_id > 0) {
                    PurchaseLine::where('id', $row->purchase_line_id)
                        ->decrement('quantity_sold', $row->quantity);
                }
                $decrementQty -= $row->quantity;
                $row->delete();
            }
        }
    }

    /**
     * Create zero-purchase mappings for sell lines that have no mapping
     */
    protected function createZeroPurchaseMappingsForTransaction(Transaction $transaction): void
    {
        $sellLines = TransactionSellLine::where('transaction_id', $transaction->id)
            ->whereNull('parent_sell_line_id')
            ->get();

        foreach ($sellLines as $sellLine) {
            $existingMapping = TransactionSellLinesPurchaseLines::where('sell_line_id', $sellLine->id)->sum('quantity');
            $unmapped = (float) $sellLine->quantity - $existingMapping;
            if ($unmapped > 0) {
                TransactionSellLinesPurchaseLines::create([
                    'sell_line_id' => $sellLine->id,
                    'purchase_line_id' => 0,
                    'quantity' => $unmapped,
                ]);
            }
        }
    }

    /**
     * Check if a status affects stock
     */
    protected function isStockAffectingStatus(?string $status): bool
    {
        return in_array($status, ['final', 'under processing'], true);
    }
}
