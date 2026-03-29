<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\ProductVariation;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\User;
use App\Variation;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ClientFlaggedProductController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    public function index()
    {
        return view('client_flagged_products.index');
    }

    public function datatable(Request $request)
    {
        $business_id = session('user.business_id');

        $query = DB::table('products as p')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftJoin('business_locations as bl', 'p.location_id', '=', 'bl.id')
            ->leftJoin('users as u', 'p.created_by', '=', 'u.id')
            ->where('p.business_id', $business_id)
            ->where('p.is_client_flagged', 1)
            ->select(
                'p.id',
                'p.name',
                'p.sku',
                'p.enable_stock',
                'p.virtual_product',
                'p.created_at',
                'c.name as category_name',
                'b.name as brand_name',
                'bl.name as location_name',
                'u.name as created_by_name'
            );

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                return [
                    'edit_id' => $row->id,
                    'delete_id' => $row->id,
                    'quick_sell_url' => route('client_flagged_products.quick_sell.form', ['id' => $row->id]),
                ];
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? date('Y-m-d H:i', strtotime($row->created_at)) : '-';
            })
            ->toJson();
    }

    public function create()
    {
        $business_id = session('user.business_id');
        $categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('category_type', 'product')
            ->select('id', 'name')
            ->get();

        $brands = DB::table('brands')
            ->where('business_id', $business_id)
            ->select('id', 'name')
            ->get();

        $locations = BusinessLocation::orderBy('name')->get(['id', 'name']);

        $html = view('client_flagged_products.partials.form', [
            'product' => null,
            'categories' => $categories,
            'brands' => $brands,
            'locations' => $locations,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function store(Request $request)
    {
        $business_id = session('user.business_id');
        $user = Auth::user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:products,sku,NULL,id,business_id,' . $business_id,
            'category_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'location_id' => 'nullable|integer|exists:business_locations,id',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $unit_id = DB::table('units')->orderBy('id')->value('id');

            $sku = $data['sku'] ?? 'CFP-' . Str::upper(Str::random(8));

            $product = Product::create([
                'name' => $data['name'],
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
                'category_id' => $data['category_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'description' => $data['description'] ?? null,
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

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('client_flagged_products.created_successfully'),
                'data' => [
                    'id' => $product->id,
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
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    public function edit($id)
    {
        $business_id = session('user.business_id');

        $product = Product::where('business_id', $business_id)
            ->where('is_client_flagged', 1)
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'msg' => __('client_flagged_products.not_found'),
            ]);
        }

        $categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('category_type', 'product')
            ->select('id', 'name')
            ->get();

        $brands = DB::table('brands')
            ->where('business_id', $business_id)
            ->select('id', 'name')
            ->get();

        $locations = BusinessLocation::orderBy('name')->get(['id', 'name']);

        $html = view('client_flagged_products.partials.form', [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'locations' => $locations,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function update(Request $request, $id)
    {
        $business_id = session('user.business_id');
        $user = Auth::user();

        $product = Product::where('business_id', $business_id)
            ->where('is_client_flagged', 1)
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'msg' => __('client_flagged_products.not_found'),
            ]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:products,sku,' . $id . ',id,business_id,' . $business_id,
            'category_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'location_id' => 'nullable|integer|exists:business_locations,id',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $product->update([
                'name' => $data['name'],
                'sku' => $data['sku'] ?? $product->sku,
                'category_id' => $data['category_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'description' => $data['description'] ?? null,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('client_flagged_products.updated_successfully'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update client flagged product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $business_id = session('user.business_id');

        $product = Product::where('business_id', $business_id)
            ->where('is_client_flagged', 1)
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'msg' => __('client_flagged_products.not_found'),
            ]);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'msg' => __('client_flagged_products.deleted_successfully'),
        ]);
    }

    public function quickSellForm($id = null)
    {
        $user = Auth::user();
        $business_id = session('user.business_id');
        $location_id = $user->location_id ?? null;

        if (empty($location_id)) {
            abort(403, __('messages.unauthorized_action'));
        }

        $location = BusinessLocation::find($location_id);

        $products = Product::where('business_id', $business_id)
            ->where('is_client_flagged', 1)
            ->select('id', 'name', 'sku')
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id');

        $product = null;
        if (!empty($id)) {
            $product = Product::where('business_id', $business_id)
                ->where('is_client_flagged', 1)
                ->findOrFail($id);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'] ?? [];
        $business_locations = $business_locations['locations'] ?? [];

        $walk_in_customer = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->where('is_default', 1)
            ->first();

        if (!$walk_in_customer) {
            $walk_in_customer = Contact::where('business_id', $business_id)
                ->where('type', 'customer')
                ->first();
        }

        $walk_in_customer = $walk_in_customer ? [
            'id' => $walk_in_customer->id,
            'name' => $walk_in_customer->name,
            'balance' => $walk_in_customer->balance ?? 0,
            'shipping_address' => $walk_in_customer->shipping_address ?? '',
        ] : ['id' => null, 'name' => '', 'balance' => 0, 'shipping_address' => ''];

        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $payment_types = [
            'cash' => __('lang_v1.cash'),
            'card' => __('lang_v1.card'),
            'bank_transfer' => __('lang_v1.bank_transfer'),
        ];

        $statuses = [
            'final' => __('sale.final'),
            'draft' => __('lang_v1.draft'),
        ];

        $default_datetime = now()->format('Y-m-d H:i');

        return view('client_flagged_products.quick_sell', compact(
            'product',
            'products',
            'location',
            'business_locations',
            'bl_attributes',
            'walk_in_customer',
            'taxes',
            'payment_types',
            'statuses',
            'default_datetime'
        ));
    }

    public function quickSellStore(Request $request, $id = null)
    {
        $user = Auth::user();
        $business_id = session('user.business_id');
        $location_id = $user->location_id ?? null;

        if (empty($location_id)) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.unauthorized_action'),
            ], 403);
        }

        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'status' => 'required|in:final,draft',
            'payment_type' => 'nullable|string',
            'job_order_id' => 'nullable|integer',
        ]);

        $product = Product::where('business_id', $business_id)
            ->where('is_client_flagged', 1)
            ->findOrFail($data['product_id']);

        $variation = $product->variations()->first();

        DB::beginTransaction();
        try {
            $total_before_tax = $data['price'] * $data['quantity'];

            $invoice_no = $this->transactionUtil->getInvoiceNumber(
                $business_id,
                $data['status'],
                $location_id,
                null,
                'sell'
            );

            $transaction = Transaction::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'type' => 'sell',
                'status' => $data['status'],
                'payment_status' => 'due',
                'contact_id' => $data['contact_id'] ?? null,
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
                'repair_job_sheet_id' => $data['job_order_id'] ?? null,
            ]);

            $transactionSellLine = TransactionSellLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'quantity' => $data['quantity'],
                'unit_price_before_discount' => $data['price'],
                'unit_price' => $data['price'],
                'unit_price_inc_tax' => $data['price'],
                'line_total' => $total_before_tax,
            ]);

            if (!empty($data['job_order_id'])) {
                DB::table('product_joborder')->insert([
                    'job_order_id' => $data['job_order_id'],
                    'product_id' => $product->id,
                    'quantity' => $data['quantity'],
                    'price' => $data['price'],
                    'purchase_price' => 0,
                    'delivered_status' => 1,
                    'out_for_deliver' => 1,
                    'client_approval' => 1,
                    'product_status' => 'black',
                    'created_at' => now(),
                ]);

                $jobSheetTransaction = Transaction::where('repair_job_sheet_id', $data['job_order_id'])->first();
                if ($jobSheetTransaction) {
                    $this->transactionUtil->adjustMappingPurchaseSell(
                        null,
                        $jobSheetTransaction,
                        $business_id,
                        []
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('client_flagged_products.sold_successfully'),
                'data' => [
                    'transaction_id' => $transaction->id,
                    'invoice_no' => $transaction->invoice_no,
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
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }
}
