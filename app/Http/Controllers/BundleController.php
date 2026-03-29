<?php

namespace App\Http\Controllers;

use App\Bundle;
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

class BundleController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    public function index()
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $isAdmin = $user->hasRole('Admin#' . $business_id) || $user->can('superadmin');
        $permitted_locations = $user->permitted_locations($business_id);

        $locations = DB::table('business_locations')
            ->select('id', 'name')
            ->where('business_id', $business_id)
            ->when(!$isAdmin && $permitted_locations != 'all', function($q) use ($permitted_locations) {
                return $q->whereIn('id', $permitted_locations);
            })
            ->get();

        return view('bundles.index', compact('isAdmin', 'locations'));
    }

    public function datatable(Request $request)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $isAdmin = $user->hasRole('Admin#' . $business_id) || $user->can('superadmin');
        $permitted_locations = $user->permitted_locations($business_id);

        $query = DB::table('bundles as b')
            ->leftJoin('categories as c', 'b.device_id', '=', 'c.id')
            ->leftJoin('repair_device_models as rdm', 'b.repair_device_model_id', '=', 'rdm.id')
            ->leftJoin('business_locations as bl', 'b.location_id', '=', 'bl.id')
            ->select(
                'b.id',
                'b.reference_no',
                'b.manufacturing_year',
                'b.side_type',
                'b.price',
                'b.has_parts_left',
                'b.description',
                'b.notes',
                'c.name as device_name',
                'rdm.name as repair_device_model_name',
                'bl.name as location_name'
            );

        if (!$isAdmin && $permitted_locations != 'all') {
            $query->whereIn('b.location_id', $permitted_locations);
        }

        if ($request->filled('device_id')) {
            $query->where('b.device_id', (int) $request->get('device_id'));
        }

        if ($request->filled('repair_device_model_id')) {
            $query->where('b.repair_device_model_id', (int) $request->get('repair_device_model_id'));
        }

        if ($request->filled('side_type')) {
            $query->where('b.side_type', $request->get('side_type'));
        }

        if ($request->filled('location_id')) {
            $query->where('b.location_id', (int) $request->get('location_id'));
        }

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                return [
                    'edit_id' => $row->id,
                    'delete_id' => $row->id,
                    'quick_sell_url' => route('bundles.quick_sell.form', ['id' => $row->id]),
                    'overview_url' => route('bundles.overview', ['id' => $row->id]),
                ];
            })
            ->editColumn('has_parts_left', function ($row) {
                return (bool) $row->has_parts_left;
            })
            ->toJson();
    }

    public function create()
    {
        $devices = DB::table('categories')->where('category_type', 'device')->select('id', 'name')->get();
        // For new bundle, keep models empty until a brand is chosen
        $repairDeviceModels = collect();
        $locations = BusinessLocation::orderBy('name')->get(['id', 'name']);

        $html = view('bundles.partials.form', [
            'bundle' => null,
            'devices' => $devices,
            'repairDeviceModels' => $repairDeviceModels,
            'locations' => $locations,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        $data['reference_no'] = $this->generateReferenceNo(
            $data['manufacturing_year'] ?? null,
            $data['device_id'] ?? null,
            $data['repair_device_model_id'] ?? null
        );
        $data['created_by'] = Auth::id();

        Bundle::create($data);

        return response()->json([
            'success' => true,
            'msg' => __('bundles.created_successfully'),
        ]);
    }

    public function edit($id)
    {
        $bundle = Bundle::find($id);
        if (! $bundle) {
            return response()->json([
                'success' => false,
                'msg' => __('bundles.not_found'),
            ]);
        }

        $devices = DB::table('categories')->where('category_type', 'device')->select('id', 'name')->get();
        // When editing, load only models for the selected brand (if any)
        $repairDeviceModels = DB::table('repair_device_models')
            ->when($bundle->device_id, function ($q) use ($bundle) {
                return $q->where('device_id', $bundle->device_id);
            })
            ->select('id', 'name')
            ->get();
        $locations = BusinessLocation::orderBy('name')->get(['id', 'name']);

        $html = view('bundles.partials.form', [
            'bundle' => $bundle,
            'devices' => $devices,
            'repairDeviceModels' => $repairDeviceModels,
            'locations' => $locations,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function update(Request $request, $id)
    {
        $bundle = Bundle::find($id);
        if (! $bundle) {
            return response()->json([
                'success' => false,
                'msg' => __('bundles.not_found'),
            ]);
        }

        $data = $this->validateRequest($request, $id);
        $data['updated_by'] = Auth::id();

        // Regenerate reference number if key fields changed
        $shouldRegenerateRef = false;
        if (isset($data['manufacturing_year']) && $data['manufacturing_year'] != $bundle->manufacturing_year) {
            $shouldRegenerateRef = true;
        }
        if (isset($data['device_id']) && $data['device_id'] != $bundle->device_id) {
            $shouldRegenerateRef = true;
        }
        if (isset($data['repair_device_model_id']) && $data['repair_device_model_id'] != $bundle->repair_device_model_id) {
            $shouldRegenerateRef = true;
        }

        if ($shouldRegenerateRef) {
            $data['reference_no'] = $this->generateReferenceNo(
                $data['manufacturing_year'] ?? $bundle->manufacturing_year,
                $data['device_id'] ?? $bundle->device_id,
                $data['repair_device_model_id'] ?? $bundle->repair_device_model_id
            );
        }

        $bundle->update($data);

        return response()->json([
            'success' => true,
            'msg' => __('bundles.updated_successfully'),
        ]);
    }

    public function destroy($id)
    {
        $bundle = Bundle::find($id);
        if (! $bundle) {
            return response()->json([
                'success' => false,
                'msg' => __('bundles.not_found'),
            ]);
        }

        $bundle->delete();

        return response()->json([
            'success' => true,
            'msg' => __('bundles.deleted_successfully'),
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

        // Get all available (active) bundles for dropdown
        $bundles = Bundle::where('location_id', $location_id)
            ->where('has_parts_left', 1)
            ->select('id', 'reference_no', 'price')
            ->orderBy('reference_no')
            ->get()
            ->pluck('reference_no', 'id');

        // If specific bundle ID provided, load it
        $bundle = null;
        if (!empty($id)) {
            $bundle = Bundle::with(['device', 'location'])->findOrFail($id);
        }

        // Prepare data like SellPosController@create
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
     
        ];

        $default_datetime = now()->format('Y-m-d H:i');

        return view('bundles.quick_sell', compact(
            'bundle',
            'bundles',
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
        $location_id = $user->location_id ?? null;

        if (empty($location_id)) {
            return redirect()->back()->with(['status' => [
                'success' => 0,
                'msg' => __('messages.unauthorized_action'),
            ]]);
        }

        $contact_id = $request->input('contact_id');
        if (empty($contact_id)) {
            return redirect()->back()->with(['status' => [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]]);
        }

        $lines = $request->input('lines', []);
        if (empty($lines)) {
            return redirect()->back()->with(['status' => [
                'success' => 0,
                'msg' => __('messages.no_products_added'),
            ]]);
        }

        $business_id = $request->session()->get('user.business_id');

        DB::beginTransaction();
        try {
            // Group lines by bundle_id to create separate transactions
            $linesByBundle = [];
            foreach ($lines as $line) {
                $bundleId = (int) ($line['bundle_id'] ?? 0);
                if (empty($bundleId)) {
                    continue;
                }
                if (!isset($linesByBundle[$bundleId])) {
                    $linesByBundle[$bundleId] = [];
                }
                $linesByBundle[$bundleId][] = $line;
            }

            if (empty($linesByBundle)) {
                DB::rollBack();
                return redirect()->back()->with(['status' => [
                    'success' => 0,
                    'msg' => __('messages.no_products_added'),
                ]]);
            }

            // Ensure we have a unit (use Piece or first unit)
            $unit_id = DB::table('units')->orderBy('id')->value('id');

            // Create a transaction for each bundle
            foreach ($linesByBundle as $bundleId => $bundleLines) {
                $total_before_tax = 0;
                $products = [];

                foreach ($bundleLines as $line) {
                    $name = trim($line['name'] ?? '');
                    $price = (float) ($line['price'] ?? 0);
                    $qty = (float) ($line['qty'] ?? 0);

                    if ($name === '' || $price <= 0 || $qty <= 0) {
                        continue;
                    }

                    // Create basic single product with required fields
                    $product = Product::create([
                        'name' => $name,
                        'business_id' => $business_id,
                        'unit_id' => $unit_id,
                        'type' => 'single',
                        'enable_stock' => 0,
                        'virtual_product' => 1,
                        'tax_type' => 'exclusive',
                        'alert_quantity' => 0,
                        'sku' => 'BND-' . $bundleId . '-' . Str::upper(Str::random(6)),
                        'barcode_type' => 'C128',
                        'created_by' => $user->id,
                    ]);

                    // Create dummy product_variation for this single product
                    $productVariation = ProductVariation::create([
                        'product_id' => $product->id,
                        'name' => 'DUMMY',
                        'is_dummy' => 1,
                    ]);

                    // Create variation row linked to product_variation
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

                    $products[] = [
                        'product' => $product,
                        'variation_id' => $variation->id,
                        'name' => $name,
                        'price' => $price,
                        'qty' => $qty,
                    ];

                    $total_before_tax += $price * $qty;
                }

                if (empty($products)) {
                    continue;
                }

                // Generate invoice number using standard util (same as normal sells)
                $status = 'final';
                $invoice_no = $this->transactionUtil->getInvoiceNumber(
                    $business_id,
                    $status,
                    $location_id,
                    null,
                    'sell'
                );

                // Create transaction for this bundle
                $transaction = Transaction::create([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'type' => 'sell',
                    'status' => 'final',
                    'payment_status' => 'due',
                    'contact_id' => $contact_id,
                    'tax_id' => $request->input('tax_rate_id'),
                    'tax_amount' => 0,
                    'discount_type' => $request->input('discount_type'),
                    'discount_amount' => $request->input('discount_amount', 0),
                    'shipping_details' => null,
                    'shipping_charges' => 0,
                    'additional_notes' => $request->input('sale_note'),
                    'final_total' => $total_before_tax,
                    'total_before_tax' => $total_before_tax,
                    'transaction_date' => now(),
                    'created_by' => $user->id,
                    'is_direct_sale' => 1,
                    'invoice_no' => $invoice_no,
                ]);

                // Add all products to this transaction
                foreach ($products as $p) {
                    TransactionSellLine::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $p['product']->id,
                        'variation_id' => $p['variation_id'],
                        'bundle_id' => $bundleId,
                        'quantity' => $p['qty'],
                        'unit_price_before_discount' => $p['price'],
                        'unit_price' => $p['price'],
                        'unit_price_inc_tax' => $p['price'],
                        'line_total' => $p['price'] * $p['qty'],
                    ]);
                }

                // Handle payment if provided using TransactionUtil
                $payment_amount = (float) ($request->input('payment_amount') ?? 0);
                $payment_method = $request->input('payment_method', 'cash');
                $payment_note = $request->input('payment_note', '');

                if ($payment_amount > 0) {
                    // Format payment data for TransactionUtil
                    $payments = [
                        [
                            'method' => $payment_method,
                            'amount' => $payment_amount,
                            'note' => $payment_note,
                        ]
                    ];

                    // Use TransactionUtil to create payment lines (same as SellPosController)
                    $this->transactionUtil->createOrUpdatePaymentLines(
                        $transaction,
                        $payments,
                        $business_id,
                        $user->id
                    );

                    // Update payment status
                    $payment_status = $this->transactionUtil->updatePaymentStatus(
                        $transaction->id,
                        $transaction->final_total
                    );

                    Log::info('Bundle quick sell payment recorded', [
                        'transaction_id' => $transaction->id,
                        'amount' => $payment_amount,
                        'method' => $payment_method,
                        'payment_status' => $payment_status,
                    ]);
                }

                Log::info('Bundle quick sell transaction created', [
                    'bundle_id' => $bundleId,
                    'transaction_id' => $transaction->id,
                    'product_count' => count($products),
                    'total' => $total_before_tax,
                    'payment_amount' => $payment_amount,
                ]);
            }

            DB::commit();

            return redirect()->route('sells.bundles')->with(['status' => [
                'success' => 1,
                'msg' => __('lang_v1.added_success'),
            ]]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bundle quick sell failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with(['status' => [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]]);
        }
    }

    public function editBundleSell($id)
    {
        $user = Auth::user();
        $business_id = session('user.business_id');
        $location_id = $user->location_id ?? null;

        if (empty($location_id)) {
            abort(403, __('messages.unauthorized_action'));
        }

        // Load the transaction
        $transaction = Transaction::with(['contact', 'sell_lines', 'sell_lines.product'])
            ->where('id', $id)
            ->where('type', 'sell')
            ->where('is_direct_sale', 1)
            ->firstOrFail();

        // Get bundle ID from first sell line
        $bundle_id = $transaction->sell_lines->first()->bundle_id ?? null;
        $bundle = null;
        if ($bundle_id) {
            $bundle = Bundle::with(['device', 'location'])->find($bundle_id);
        }

        $location = BusinessLocation::find($location_id);

        // Get all available (active) bundles for dropdown
        $bundles = Bundle::where('location_id', $location_id)
            ->where('has_parts_left', 1)
            ->select('id', 'reference_no', 'price')
            ->orderBy('reference_no')
            ->get()
            ->pluck('reference_no', 'id');

        // Prepare data like BundleController@quickSellForm
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'] ?? [];
        $business_locations = $business_locations['locations'] ?? [];

        $walk_in_customer = $transaction->contact ? [
            'id' => $transaction->contact->id,
            'name' => $transaction->contact->name,
            'balance' => $transaction->contact->balance ?? 0,
            'shipping_address' => $transaction->contact->shipping_address ?? '',
        ] : ['id' => null, 'name' => '', 'balance' => 0, 'shipping_address' => ''];

        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $payment_types = [
            'cash' => __('lang_v1.cash'),
            'card' => __('lang_v1.card'),
            'bank_transfer' => __('lang_v1.bank_transfer'),
        ];

        $statuses = [
            'final' => __('sale.final'),
         
        ];

        $default_datetime = \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i');

        return view('bundles.quick_sell_edit', compact(
            'transaction',
            'bundle',
            'bundles',
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

    public function updateBundleSell(Request $request, $id)
    {
        $user = Auth::user();
        $location_id = $user->location_id ?? null;

        if (empty($location_id)) {
            return redirect()->back()->with(['status' => [
                'success' => 0,
                'msg' => __('messages.unauthorized_action'),
            ]]);
        }

        // Load the transaction
        $transaction = Transaction::with(['contact', 'sell_lines', 'sell_lines.product'])
            ->where('id', $id)
            ->where('type', 'sell')
            ->where('is_direct_sale', 1)
            ->firstOrFail();

        $contact_id = $request->input('contact_id');
        if (empty($contact_id)) {
            return redirect()->back()->with(['status' => [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]]);
        }

        $lines = $request->input('lines', []);
        if (empty($lines)) {
            return redirect()->back()->with(['status' => [
                'success' => 0,
                'msg' => __('messages.no_products_added'),
            ]]);
        }

        $business_id = $request->session()->get('user.business_id');

        DB::beginTransaction();
        try {
            // Delete existing sell lines
            TransactionSellLine::where('transaction_id', $transaction->id)->delete();

            // Delete existing products that were created for this transaction
            foreach ($transaction->sell_lines as $line) {
                if ($line->product && $line->product->virtual_product == 1) {
                    $line->product->delete();
                }
            }

            // Group lines by bundle_id to handle multiple bundles
            $linesByBundle = [];
            foreach ($lines as $line) {
                $bundleId = (int) ($line['bundle_id'] ?? 0);
                if (empty($bundleId)) {
                    continue;
                }
                if (!isset($linesByBundle[$bundleId])) {
                    $linesByBundle[$bundleId] = [];
                }
                $linesByBundle[$bundleId][] = $line;
            }

            if (empty($linesByBundle)) {
                DB::rollBack();
                return redirect()->back()->with(['status' => [
                    'success' => 0,
                    'msg' => __('messages.no_products_added'),
                ]]);
            }

            // Ensure we have a unit (use Piece or first unit)
            $unit_id = DB::table('units')->orderBy('id')->value('id');

            $total_before_tax = 0;
            $products = [];

            // Process all lines (for now, we'll use the first bundle from the transaction)
            foreach ($linesByBundle as $bundleId => $bundleLines) {
                foreach ($bundleLines as $line) {
                    $name = trim($line['name'] ?? '');
                    $price = (float) ($line['price'] ?? 0);
                    $qty = (float) ($line['qty'] ?? 0);

                    if ($name === '' || $price <= 0 || $qty <= 0) {
                        continue;
                    }

                    // Create basic single product with required fields
                    $product = Product::create([
                        'name' => $name,
                        'business_id' => $business_id,
                        'unit_id' => $unit_id,
                        'type' => 'single',
                        'enable_stock' => 0,
                        'virtual_product' => 1,
                        'tax_type' => 'exclusive',
                        'alert_quantity' => 0,
                        'sku' => 'BND-' . $bundleId . '-' . Str::upper(Str::random(6)),
                        'barcode_type' => 'C128',
                        'created_by' => $user->id,
                    ]);

                    // Create dummy product_variation for this single product
                    $productVariation = ProductVariation::create([
                        'product_id' => $product->id,
                        'name' => 'DUMMY',
                        'is_dummy' => 1,
                    ]);

                    // Create variation row linked to product_variation
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

                    $products[] = [
                        'product' => $product,
                        'variation_id' => $variation->id,
                        'name' => $name,
                        'price' => $price,
                        'qty' => $qty,
                        'bundle_id' => $bundleId,
                    ];

                    $total_before_tax += $price * $qty;
                }
            }

            if (empty($products)) {
                DB::rollBack();
                return redirect()->back()->with(['status' => [
                    'success' => 0,
                    'msg' => __('messages.no_products_added'),
                ]]);
            }

            // Update transaction
            $transaction->update([
                'contact_id' => $contact_id,
                'tax_id' => $request->input('tax_rate_id'),
                'tax_amount' => 0,
                'discount_type' => $request->input('discount_type'),
                'discount_amount' => $request->input('discount_amount', 0),
                'additional_notes' => $request->input('sale_note'),
                'final_total' => $total_before_tax,
                'total_before_tax' => $total_before_tax,
                'transaction_date' => now(),
                'updated_by' => $user->id,
            ]);

            // Add all products to this transaction
            foreach ($products as $p) {
                TransactionSellLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $p['product']->id,
                    'variation_id' => $p['variation_id'],
                    'bundle_id' => $p['bundle_id'],
                    'quantity' => $p['qty'],
                    'unit_price_before_discount' => $p['price'],
                    'unit_price' => $p['price'],
                    'unit_price_inc_tax' => $p['price'],
                    'line_total' => $p['price'] * $p['qty'],
                ]);
            }

            DB::commit();

            return redirect()->route('sells.bundles')->with(['status' => [
                'success' => 1,
                'msg' => __('lang_v1.updated_success'),
            ]]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bundle sell update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with(['status' => [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]]);
        }
    }

    public function overview($id)
    {
        $bundle = Bundle::with(['device', 'repairDeviceModel', 'location'])->find($id);

        if (! $bundle) {
            abort(404);
        }

        $location_id = $bundle->location_id;

        $lines = TransactionSellLine::query()
            ->join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->where('transaction_sell_lines.bundle_id', $bundle->id)
            ->where('t.location_id', $location_id)
            ->where('t.type', 'sell')
            ->select(
                'transaction_sell_lines.id',
                'transaction_sell_lines.transaction_id',
                'transaction_sell_lines.quantity',
                'transaction_sell_lines.unit_price_inc_tax',
                't.invoice_no',
                't.transaction_date',
                't.payment_status',
                'c.name as customer_name',
                'p.name as product_name',
                DB::raw('transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax as selling_total')
            )
            ->orderByDesc('t.transaction_date')
            ->get();

        $bundle_cost = (float) $bundle->price; // cost of the scrap car / bundle

        $total_sales = (float) $lines->sum('selling_total');
        $total_purchase_cost = $bundle_cost;
        $net_profit = $total_sales - $bundle_cost;
        $profit_margin = $bundle_cost > 0 ? ($net_profit / $bundle_cost) * 100 : 0;
        $total_qty = (float) $lines->sum('quantity');
        $total_transactions = $lines->pluck('transaction_id')->unique()->count();
        $unique_customers = $lines->pluck('customer_name')->filter()->unique()->count();

        $sold_products = $lines
            ->groupBy('product_name')
            ->map(function ($group) {
                return (float) $group->sum('quantity');
            })
            ->toArray();

        return view('bundles.overview', compact(
            'bundle',
            'lines',
            'total_sales',
            'total_purchase_cost',
            'net_profit',
            'profit_margin',
            'total_qty',
            'total_transactions',
            'unique_customers',
            'sold_products'
        ));
    }

    /**
     * Get bundles for AJAX dropdown search
     */
    public function getBundlesAjax(Request $request)
    {
        $user = Auth::user();
        $location_id = $user->location_id ?? null;
        $search = $request->input('q', '');
        $page = $request->input('page', 1);
        $perPage = 10;

        $query = Bundle::where('location_id', $location_id)
            ->where('has_parts_left', 1)
            ->select('id', 'reference_no', 'price');

        if (!empty($search)) {
            $query->where('reference_no', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%');
        }

        $bundles = $query->paginate($perPage, ['*'], 'page', $page);

        $results = $bundles->map(function ($bundle) {
            return [
                'id' => $bundle->id,
                'text' => $bundle->reference_no,
                'price' => $bundle->price,
            ];
        })->toArray();

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $bundles->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get devices/brands for AJAX dropdown search
     */
    public function getDevicesAjax(Request $request)
    {
        $search = $request->input('q', '');
        $page = $request->input('page', 1);
        $perPage = 10;

        $query = DB::table('categories')
            ->where('category_type', 'device')
            ->select('id', 'name');

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $devices = $query->paginate($perPage, ['*'], 'page', $page);

        $results = $devices->map(function ($device) {
            return [
                'id' => $device->id,
                'text' => $device->name,
            ];
        })->toArray();

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $devices->hasMorePages(),
            ],
        ]);
    }

    protected function validateRequest(Request $request, ?int $bundleId = null): array
    {
        $sideTypes = ['front_half', 'rear_half', 'left_quarter', 'right_quarter', 'full_body', 'other'];

        return $request->validate([
            'device_id' => 'required|integer',
            'repair_device_model_id' => 'nullable|integer',
            'manufacturing_year' => 'nullable|integer|min:1900|max:2100',
            'side_type' => 'required|in:' . implode(',', $sideTypes),
            'price' => 'nullable|numeric|min:0',
            'has_parts_left' => 'sometimes|boolean',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'location_id' => 'required|integer',
        ]);
    }

    protected function generateReferenceNo($manufacturing_year = null, $device_id = null, $model_id = null): string
    {
        // Get manufacturing year from parameter or current year
        $year = $manufacturing_year ?? date('Y');
        
        // Get device/brand info
        $brand_code = 'M000'; // Default model code
        if ($device_id) {
            $device = DB::table('categories')->where('id', $device_id)->first();
            if ($device) {
                // Take first 3 characters of device name, pad with zeros if needed
                $brand_code = 'M' . str_pad(substr($device->name, 0, 3), 3, '0', STR_PAD_RIGHT);
            }
        }
        
        // Get model info for more specific code
        if ($model_id) {
            $model = DB::table('repair_device_models')->where('id', $model_id)->first();
            if ($model) {
                // Use model ID or create abbreviation from model name
                $brand_code = 'M' . str_pad($model_id, 3, '0', STR_PAD_LEFT);
            }
        }
        
        // Generate serial number (random 3-digit for now, could be sequential)
        $serial = mt_rand(100, 999);
        
        // Create reference: BND_2016M180R-234
        return "BND_{$year}{$brand_code}R-{$serial}";
    }
}
