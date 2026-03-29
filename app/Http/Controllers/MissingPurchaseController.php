<?php

namespace App\Http\Controllers;

use App\Transaction;
use App\PurchaseLine;
use App\Product;
use App\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Events\PurchaseCreatedOrModified;

class MissingPurchaseController extends Controller
{
    protected $productUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param ProductUtil $productUtil
     * @param TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display missing products that need to be purchased
     */
    public function index()
    {
        $business_id = auth()->user()->business_id;
        
        // Get consolidated missing products
        $missingProducts = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftJoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
            ->leftJoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.repair_job_sheet_id', '!=', null)
            ->where('p.enable_stock', 1)
            ->where(function($q) {
                $q->whereNull('tspl.sell_line_id')
                  ->orWhereNull('pl.id');
            })
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.sku',
                'u.short_name as unit',
                DB::raw('SUM(tsl.quantity) as total_required_qty'),
                DB::raw('COUNT(DISTINCT t.id) as num_transactions'),
                DB::raw("GROUP_CONCAT(DISTINCT t.id SEPARATOR ',') as transaction_ids")
            )
            ->groupBy('p.id', 'p.name', 'p.sku', 'u.short_name')
            ->orderBy('total_required_qty', 'DESC')
            ->get();
        
        // Get suppliers for dropdown
        $suppliers = Contact::where('business_id', $business_id)
            ->where('type', 'supplier')
            ->get();
        
        // Get default supplier (first one or create a default)
        $defaultSupplier = $suppliers->first();
        if (!$defaultSupplier) {
            // Create a default supplier if none exists
            $defaultSupplier = Contact::create([
                'business_id' => $business_id,
                'type' => 'supplier',
                'name' => 'Default Supplier',
                'created_by' => auth()->user()->id,
            ]);
            $suppliers = Contact::where('business_id', $business_id)
                ->where('type', 'supplier')
                ->get();
        }
        
        return view('missing_purchase.index', compact('missingProducts', 'suppliers', 'defaultSupplier'));
    }

    /**
     * Create purchase transaction for consolidated missing products
     */
    public function createPurchase(Request $request)
    {
        $business_id = auth()->user()->business_id;
        $location_id = auth()->user()->location_id;
        $user_id = auth()->user()->id;
        
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        $supplier_id = $request->input('supplier_id');
        
        // If supplier_id is not provided, use default supplier
        if (empty($supplier_id)) {
            $supplier = Contact::where('business_id', $business_id)
                ->where('type', 'supplier')
                ->first();
                
            if (!$supplier) {
                // Create a default supplier if none exists
                $supplier = Contact::create([
                    'business_id' => $business_id,
                    'type' => 'supplier',
                    'name' => 'Default Supplier',
                    'created_by' => $user_id,
                ]);
            }
            $supplier_id = $supplier->id;
        }
        
        // If no products selected, process ALL missing products
        if (empty($validated['products'])) {
            $validated['products'] = $this->getAllMissingProducts($business_id);
        }
        
        DB::beginTransaction();
        
        try {
            $createdPurchases = [];
            
            // Add purchase lines with chunking (max 50 lines per transaction)
            $productChunks = array_chunk($validated['products'], 50);
            
            foreach ($productChunks as $chunkIndex => $chunk) {
                $chunkTotalBeforeTax = 0;
                $chunkTaxAmount = 0;
                
                // Create new purchase for each chunk
                $chunkPurchase = new Transaction();
                $chunkPurchase->business_id = $business_id;
                $chunkPurchase->location_id = $location_id;
                $chunkPurchase->type = 'purchase';
                $chunkPurchase->status = 'received';
                $chunkPurchase->payment_status = 'due';
                $chunkPurchase->contact_id = $supplier_id;
                $chunkPurchase->transaction_date = now();
                $chunkPurchase->created_by = $user_id;
                
                // Generate reference number
                $ref_count = $this->productUtil->setAndGetReferenceCount('purchase');
                $chunkPurchase->ref_no = $this->productUtil->generateReferenceNumber('purchase', $ref_count);
                
                // Save the transaction first to get an ID
                $chunkPurchase->save();
                
                // Add purchase lines for this chunk
                foreach ($chunk as $product) {
                    $lineTotal = $product['quantity'] * $product['unit_price'];
                    $chunkTotalBeforeTax += $lineTotal;
                    
                    // Get the first variation for this product
                    $variation = DB::table('variations')
                        ->where('product_id', $product['product_id'])
                        ->first();
                    
                    $purchaseLine = new PurchaseLine();
                    $purchaseLine->product_id = $product['product_id'];
                    $purchaseLine->variation_id = $variation ? $variation->id : null;
                    $purchaseLine->quantity = $product['quantity'];
                    $purchaseLine->purchase_price = $product['unit_price'];
                    $purchaseLine->purchase_price_inc_tax = $product['unit_price'];
                    $purchaseLine->pp_without_discount = $product['unit_price'];
                    
                    $chunkPurchase->purchase_lines()->save($purchaseLine);

                    // Update product stock quantity using safe method for VLD
                    if ($variation) {
                        $this->productUtil->safeAdjustQtyAvailable(
                            $product['product_id'],
                            $variation->id,
                            $location_id,
                            $product['quantity'],
                            $variation->product_variation_id
                        );
                    }
                }
                
                $chunkPurchase->total_before_tax = $chunkTotalBeforeTax;
                $chunkPurchase->tax_amount = $chunkTaxAmount;
                $chunkPurchase->discount_amount = 0;
                $chunkPurchase->discount_type = 'fixed';
                $chunkPurchase->final_total = $chunkTotalBeforeTax + $chunkTaxAmount;
                $chunkPurchase->save();
                
                $createdPurchases[] = $chunkPurchase;
            }
            
            // Link to missing sell lines for all created purchases
            foreach ($createdPurchases as $purchase) {
                $this->linkPurchaseToMissingLines($purchase, $validated['products']);
            }
            
            // Log activity and dispatch event for each purchase
            foreach ($createdPurchases as $purchase) {
                $this->transactionUtil->activityLog($purchase, 'added');
                PurchaseCreatedOrModified::dispatch($purchase);
            }
            
            DB::commit();
            
            $refNumbers = collect($createdPurchases)->pluck('ref_no')->implode(', ');
            return response()->json([
                'success' => true,
                'message' => count($createdPurchases) . ' purchase transaction(s) created successfully',
                'transactions' => $createdPurchases,
                'ref_numbers' => $refNumbers
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Link purchase lines to missing sell lines
     */
    private function linkPurchaseToMissingLines($purchase, $products)
    {
        foreach ($products as $product) {
            // Get all missing sell lines for this product
            $missingSellLines = DB::table('transaction_sell_lines as tsl')
                ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                ->leftJoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
                ->leftJoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
                ->where('tsl.product_id', $product['product_id'])
                ->where('t.type', 'sell')
                ->where('t.repair_job_sheet_id', '!=', null)
                ->where(function($q) {
                    $q->whereNull('tspl.sell_line_id')
                      ->orWhereNull('pl.id');
                })
                ->select('tsl.id', 'tsl.quantity')
                ->get();
            
            $remainingQty = $product['quantity'];
            
            foreach ($missingSellLines as $sellLine) {
                if ($remainingQty <= 0) break;
                
                $linkQty = min($remainingQty, $sellLine->quantity);
                
                // Get the purchase line for this product
                $purchaseLine = $purchase->purchase_lines()
                    ->where('product_id', $product['product_id'])
                    ->first();
                
                if ($purchaseLine) {
                    DB::table('transaction_sell_lines_purchase_lines')->insert([
                        'sell_line_id' => $sellLine->id,
                        'purchase_line_id' => $purchaseLine->id,
                        'quantity' => $linkQty,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $remainingQty -= $linkQty;
                }
            }
        }
    }

    /**
     * Get all missing products for purchase creation
     */
    private function getAllMissingProducts($business_id)
    {
        $missingProducts = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftJoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
            ->leftJoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.repair_job_sheet_id', '!=', null)
            ->where('p.enable_stock', 1)
            ->where(function($q) {
                $q->whereNull('tspl.sell_line_id')
                  ->orWhereNull('pl.id');
            })
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.sku',
                'u.short_name as unit',
                DB::raw('SUM(tsl.quantity) as total_required_qty'),
                DB::raw('COUNT(DISTINCT t.id) as num_transactions'),
                DB::raw("GROUP_CONCAT(DISTINCT t.id SEPARATOR ',') as transaction_ids")
            )
            ->groupBy('p.id', 'p.name', 'p.sku', 'u.short_name')
            ->orderBy('total_required_qty', 'DESC')
            ->get();
        
        $products = [];
        foreach ($missingProducts as $product) {
            $products[] = [
                'product_id' => $product->product_id,
                'quantity' => $product->total_required_qty,
                'unit_price' => 0, // Default price, user can update later
            ];
        }
        
        return $products;
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNo($business_id)
    {
        $lastInvoice = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastInvoice ? intval(substr($lastInvoice->invoice_no, -5)) + 1 : 1;
        return 'PUR-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}
