<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Bundle;
use App\GenericSparePart;
use App\Product;
use App\ProductVariation;
use App\Variation;
use App\Transaction;
use App\TransactionSellLine;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BundleApiController extends ApiController
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        parent::__construct();
        $this->transactionUtil = $transactionUtil;
    }
    public function getBundles(Request $request)
    {
        $user = Auth::user();
        $location_id = $user->location_id ?? null;

        if (empty($location_id)) {
            return $this->respondUnauthorized('Location not assigned');
        }

        $bundles = Bundle::where('location_id', $location_id)
            ->where('has_parts_left', 1)
            ->select('id', 'reference_no', 'price', 'description')
            ->orderBy('reference_no')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => $bundles,
        ]);
    }

    public function getGenericSpareParts(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;
        
        $query = GenericSparePart::where('business_id', $business_id)
            ->select('id', 'name', 'type');

        // Filter by type if provided
        if ($request->has('type') && !empty($request->input('type'))) {
            $query->where('type', $request->input('type'));
        }

        // Search by name if provided
        if ($request->has('name') && !empty($request->input('name'))) {
            $searchTerm = $request->input('name');
            // Prioritize exact matches first, then partial matches
            $query->orderByRaw("CASE WHEN name = ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END", [$searchTerm, $searchTerm . '%'])
                  ->where('name', 'LIKE', '%' . $searchTerm . '%');
        } else {
            $query->orderBy('name');
        }

        // Pagination
        $perPage = $request->input('per_page', 100);
        $page = $request->input('page', 1);

        $spareParts = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->respond([
            'success' => true,
            'data' => $spareParts->items(),
            'pagination' => [
                'current_page' => $spareParts->currentPage(),
                'per_page' => $spareParts->perPage(),
                'total' => $spareParts->total(),
                'last_page' => $spareParts->lastPage(),
                'from' => $spareParts->firstItem(),
                'to' => $spareParts->lastItem(),
            ],
        ]);
    }

    public function createGenericSparePart(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:virtual,client_flagged',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $business_id = $user->business_id;

        $data = [
            'name' => trim($request->input('name')),
            'description' => $request->input('description'),
            'type' => $request->input('type'),
            'business_id' => $business_id,
            'created_by' => $user->id,
        ];

        $genericSparePart = GenericSparePart::create($data);

 

        return $this->respond([
            'success' => true,
            'data' => [
                'id' => $genericSparePart->id,
                'name' => $genericSparePart->name,
                'description' => $genericSparePart->description,
                'type' => $genericSparePart->type,
            ],
        ]);
    }

    public function createVirtualProduct(Request $request)
    {
        $type = $request->input('type', 'virtual');
        
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'bundle_id' => $type === 'virtual' ? 'required|integer|min:1' : 'nullable|integer|min:1',
            'name' => 'required|string|max:255',
            'qty' => 'nullable|numeric|min:0.01',
            'price' => 'nullable|numeric|min:0',
            'job_order_id' => 'required|integer|min:1',
            'type' => 'required|in:virtual,client_flagged',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $qty = $request->input('qty', 1);
        $price = $request->input('price', 0);
        $user = Auth::user();
        $business_id = $user->business_id;
        $location_id = $user->location_id ?? null;

        if (empty($location_id)) {
            return $this->respondUnauthorized('Location not assigned');
        }

        $bundle_id = $request->input('bundle_id');
        $name = trim($request->input('name'));

        $job_order_id = $request->input('job_order_id');

        $bundle = null;
        if ($type === 'virtual' && !empty($bundle_id)) {
            $bundle = Bundle::find($bundle_id);
            if (!$bundle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bundle not found',
                ], 404);
            }
        }

        DB::beginTransaction();
        try {
            $skuPrefix = $type === 'client_flagged' ? 'CFP-' : 'BND-';
            $skuSuffix = $type === 'client_flagged' ? Str::upper(Str::random(6)) : $bundle_id . '-' . Str::upper(Str::random(6));
            $sku = $skuPrefix . $skuSuffix;

            $unit = DB::table('units')->where('short_name', 'Pc(s)')->first();
            if (!$unit) {
                $unit_id = DB::table('units')->insertGetId([
                    'actual_name' => 'Piece(s)',
                    'short_name' => 'Pc(s)',
                    'allow_decimal' => 1,
                    'business_id' => $business_id,
                    'created_by' => $user->id,
                ]);
            } else {
                $unit_id = $unit->id;
            }

            $product = Product::create([
                'name' => $name,
                'business_id' => $business_id,
                'unit_id' => $unit_id,
                'type' => 'single',
                'enable_stock' => 0,
                'virtual_product' => $type === 'client_flagged' ? 1 : 0,
                'is_client_flagged' => $type === 'client_flagged' ? 1 : 0,
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
                'name' => $name,
                'product_id' => $product->id,
                'product_variation_id' => $productVariation->id,
                'sub_sku' => null,
                'default_purchase_price' => $price,
                'dpp_inc_tax' => $price,
                'default_sell_price' => $price,
                'sell_price_inc_tax' => $price,
            ]);

            $total_before_tax = $price * $qty;

            $transaction = Transaction::where('repair_job_sheet_id', $job_order_id)->first();
            if (!$transaction) {
                throw new \Exception('Job order transaction not found');
            }

            $transactionSellLine = TransactionSellLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'bundle_id' => $type === 'virtual' ? $bundle_id : null,
                'quantity' => $qty,
                'unit_price_before_discount' => $price,
                'unit_price' => $price,
                'unit_price_inc_tax' => $price,
                'line_total' => $total_before_tax,
            ]);

            $transaction->final_total += $total_before_tax;
            $transaction->total_before_tax += $total_before_tax;
            $transaction->save();

            DB::table('product_joborder')->insert([
                'job_order_id' => $job_order_id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'price' => $price,
                'purchase_price' => $price,
                'delivered_status' => 1,
                'out_for_deliver' => 1,
                'client_approval' => 1,
                'product_status' => 'black',
                'created_at' => now(),
            ]);

            $this->transactionUtil->adjustMappingPurchaseSell(
                null,
                $transaction,
                $business_id,
                []
            );

        

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
                        'type' => $type,
                    ],
                    'variation_id' => $variation->id,
                    'bundle' => $bundle ? [
                        'id' => $bundle->id,
                        'reference_no' => $bundle->reference_no,
                    ] : null,
                    'job_order_id' => $job_order_id,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create bundle selling', [
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
