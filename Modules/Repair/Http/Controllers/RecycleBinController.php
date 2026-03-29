<?php

namespace Modules\Repair\Http\Controllers;

use App\Business;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\PurchaseLine;
use App\Utils\Util;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\Utils\ProductUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Modules\Repair\Entities\JobSheet;
use Yajra\DataTables\Facades\DataTables;

class RecycleBinController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $commonUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $productUtil;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Display a unified listing of deleted job sheets and repair transactions.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $type = request()->get('type', 'all'); // all, job_sheet, transaction
            
            if ($type === 'job_sheet') {
                return $this->getJobSheetsData($business_id);
            } elseif ($type === 'transaction') {
                return $this->getTransactionsData($business_id);
            } else {
                return $this->getCombinedData($business_id);
            }
        }

        return view('repair::recycle_bin.index');
    }

    /**
     * Get job sheets data for recycle bin
     */
    private function getJobSheetsData($business_id)
    {
        $job_sheets = JobSheet::onlyTrashed()
            ->where('repair_job_sheets.business_id', $business_id)
            ->leftJoin('contacts', 'repair_job_sheets.contact_id', '=', 'contacts.id')
            ->leftJoin('business_locations AS bl', 'repair_job_sheets.location_id', '=', 'bl.id')
            ->select([
                'repair_job_sheets.id',
                'repair_job_sheets.job_sheet_no',
                'contacts.name as customer',
                'bl.name as location',
                'repair_job_sheets.deleted_at',
                'repair_job_sheets.created_at',
                DB::raw("'job_sheet' as type"),
                DB::raw("'Job Sheet' as type_display"),
                DB::raw("NULL as transaction_date"),
                DB::raw("NULL as invoice_no"),
                DB::raw('NULL as supplier'),
                DB::raw('NULL as ref_no')
            ]);

        return Datatables::of($job_sheets)
            ->addColumn('action', function ($row) {
                $html = '<button data-href="' . route('recycle-bin.restore-job-sheet', [$row->id]) . '" class="btn btn-xs btn-success restore_item"><i class="fas fa-undo"></i> ' . __("messages.restore") . '</button>';
                $html .= ' <button data-href="' . route('recycle-bin.permanent-delete-job-sheet', [$row->id]) . '" class="btn btn-xs btn-danger delete_permanent"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</button>';
                return $html;
            })
            ->addColumn('transaction_type_display', function ($row) {
                // Job sheets don't have transaction types
                return '';
            })
            ->editColumn('deleted_at', '{{@format_datetime($deleted_at)}}')
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Get transactions data for recycle bin
     */
    private function getTransactionsData($business_id)
    {
        // Include all relevant transaction types used in Treasury module
        $transaction_types = [
            'sell',
            'purchase',
            'opening_balance',
            'sell_return',
            'purchase_return',
            'expense',
            'payroll',
            // keep opening_stock for forward compatibility, even if not widely used yet
            'opening_stock',
            // Internal transfers between payment methods/branches
            'internal_transfer',
        ];

        $sells = Transaction::onlyTrashed()
            ->where('transactions.business_id', $business_id)
            ->whereIn('transactions.type', $transaction_types)
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('business_locations AS bl', 'transactions.location_id', '=', 'bl.id')
            ->select([
                'transactions.id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'contacts.name as customer',
                'bl.name as location',
                'transactions.deleted_at',
                DB::raw("'transaction' as type"),
                // High-level item type label (row kind)
                DB::raw("'Transaction' as type_display"),
                DB::raw("NULL as job_sheet_no"),
                // Underlying transaction type & sub-type
                'transactions.type as transaction_type',
                'transactions.sub_type as transaction_sub_type',
                // Additional fields for different transaction types
                DB::raw('CASE WHEN transactions.type IN ("purchase", "purchase_return") THEN contacts.name ELSE NULL END as supplier'),
                'transactions.ref_no'
            ]);

        return Datatables::of($sells)
            ->addColumn('action', function ($row) {
                $html = '<button data-href="' . route('recycle-bin.restore-transaction', [$row->id]) . '" class="btn btn-xs btn-success restore_item"><i class="fas fa-undo"></i> ' . __("messages.restore") . '</button>';
                $html .= ' <button data-href="' . route('recycle-bin.permanent-delete-transaction', [$row->id]) . '" class="btn btn-xs btn-danger delete_permanent"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</button>';
                return $html;
            })
            ->addColumn('transaction_type_display', function ($row) {
                // Build a simple human-readable transaction type, e.g. "sell - repair"
                $base = $row->transaction_type ?? '';
                if (! empty($row->transaction_sub_type)) {
                    $base .= ' - ' . $row->transaction_sub_type;
                }

                return $base;
            })
            ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
            ->editColumn('deleted_at', '{{@format_datetime($deleted_at)}}')
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Get combined data for both job sheets and transactions
     */
    private function getCombinedData($business_id)
    {
        // Get job sheets
        $job_sheets = JobSheet::onlyTrashed()
            ->where('repair_job_sheets.business_id', $business_id)
            ->leftJoin('contacts', 'repair_job_sheets.contact_id', '=', 'contacts.id')
            ->leftJoin('business_locations AS bl', 'repair_job_sheets.location_id', '=', 'bl.id')
            ->select([
                // IMPORTANT: column order must exactly match the transaction SELECT below
                // id, job_sheet_no, invoice_no, transaction_date, customer, location,
                // deleted_at, created_at, type, type_display, transaction_type, transaction_sub_type, supplier, ref_no
                'repair_job_sheets.id',
                DB::raw('repair_job_sheets.job_sheet_no as job_sheet_no'),
                DB::raw('NULL as invoice_no'),
                DB::raw('NULL as transaction_date'),
                'contacts.name as customer',
                'bl.name as location',
                'repair_job_sheets.deleted_at',
                'repair_job_sheets.created_at',
                DB::raw("'job_sheet' as type"),
                DB::raw("'Job Sheet' as type_display"),
                DB::raw('NULL as transaction_type'),
                DB::raw('NULL as transaction_sub_type'),
                DB::raw('NULL as supplier'),
                DB::raw('NULL as ref_no')
            ]);

        // Get transactions
        $transactions = Transaction::onlyTrashed()
            ->where('transactions.business_id', $business_id)
            ->whereIn('transactions.type', [
                'sell',
                'purchase',
                'opening_balance',
                'sell_return',
                'purchase_return',
                'expense',
                'payroll',
                'opening_stock',
                // Internal transfers between payment methods/branches
                'internal_transfer',
            ])
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('business_locations AS bl', 'transactions.location_id', '=', 'bl.id')
            ->select([
                // Same column order as job_sheets SELECT above
                'transactions.id',
                DB::raw('NULL as job_sheet_no'),
                'transactions.invoice_no',
                'transactions.transaction_date',
                'contacts.name as customer',
                'bl.name as location',
                'transactions.deleted_at',
                DB::raw('NULL as created_at'),
                DB::raw("'transaction' as type"),
                DB::raw("'Transaction' as type_display"),
                'transactions.type as transaction_type',
                'transactions.sub_type as transaction_sub_type',
                // Additional fields for different transaction types
                DB::raw('CASE WHEN transactions.type IN ("purchase", "purchase_return") THEN contacts.name ELSE NULL END as supplier'),
                'transactions.ref_no'
            ]);

        // Combine both queries using union
        $combined = $job_sheets->union($transactions);

        return Datatables::of($combined)
            ->addColumn('action', function ($row) {
                if ($row->type === 'job_sheet') {
                    $html = '<button data-href="' . route('recycle-bin.restore-job-sheet', [$row->id]) . '" class="btn btn-xs btn-success restore_item"><i class="fas fa-undo"></i> ' . __("messages.restore") . '</button>';
                    $html .= ' <button data-href="' . route('recycle-bin.permanent-delete-job-sheet', [$row->id]) . '" class="btn btn-xs btn-danger delete_permanent"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</button>';
                } else {
                    $html = '<button data-href="' . route('recycle-bin.restore-transaction', [$row->id]) . '" class="btn btn-xs btn-success restore_item"><i class="fas fa-undo"></i> ' . __("messages.restore") . '</button>';
                    $html .= ' <button data-href="' . route('recycle-bin.permanent-delete-transaction', [$row->id]) . '" class="btn btn-xs btn-danger delete_permanent"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</button>';
                }
                return $html;
            })
            ->addColumn('transaction_type_display', function ($row) {
                if ($row->type === 'job_sheet') {
                    return '';
                }

                $base = $row->transaction_type ?? '';
                if (! empty($row->transaction_sub_type)) {
                    $base .= ' - ' . $row->transaction_sub_type;
                }

                return $base;
            })
            ->editColumn('transaction_date', function ($row) {
                return $row->transaction_date ? \Carbon\Carbon::parse($row->transaction_date)->format('Y-m-d H:i:s') : '';
            })
            ->editColumn('deleted_at', '{{@format_datetime($deleted_at)}}')
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Restore a job sheet
     */
    public function restoreJobSheet($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $job_sheet = JobSheet::onlyTrashed()
                ->where('business_id', $business_id)
                ->findOrFail($id);

            $job_sheet->restore();

            return [
                'success' => true,
                'msg' => __('repair::lang.job_sheet_restored')
            ];
        } catch (\Exception $e) {
            Log::error('Error restoring job sheet: ' . $e->getMessage());
            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
    }

    /**
     * Get restore preview data for transaction
     */
    public function getRestorePreview($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $transaction = Transaction::onlyTrashed()
                ->where('business_id', $business_id)
                ->findOrFail($id);

            // Get items based on transaction type
            $all_items = collect();

            if ($transaction->type === 'sell') {
                // Get transaction sell lines
                $sell_lines = TransactionSellLine::where('transaction_id', $transaction->id)
                    ->with(['product', 'variation'])
                    ->get();

                // Get product_joborder items if this is a repair transaction
                $job_order_items = [];
                if ($transaction->sub_type === 'repair' && !empty($transaction->repair_job_sheet_id)) {
                    $job_order_items = DB::table('product_joborder')
                        ->where('job_order_id', $transaction->repair_job_sheet_id)
                        ->leftJoin('products', 'product_joborder.product_id', '=', 'products.id')
                        ->select([
                            'product_joborder.id',
                            'product_joborder.product_id',
                            'product_joborder.quantity',
                            'products.name as product_name'
                        ])
                        ->get();
                }

                // Add transaction sell lines
                foreach ($sell_lines as $line) {
                    $all_items->push([
                        'source' => 'sell_line',
                        'product_id' => $line->product_id,
                        'variation_id' => $line->variation_id,
                        'quantity' => $line->quantity,
                        'product_name' => $line->product->name ?? 'Unknown',
                        'variation_name' => $line->variation->name ?? '',
                    ]);
                }

                // Add job order items
                foreach ($job_order_items as $item) {
                    $all_items->push([
                        'source' => 'job_order',
                        'product_id' => $item->product_id,
                        'variation_id' => null,
                        'quantity' => $item->quantity,
                        'product_name' => $item->product_name ?? 'Unknown',
                        'variation_name' => '',
                    ]);
                }
            } elseif ($transaction->type === 'purchase') {
                // Get purchase lines for purchase transactions
                $purchase_lines = PurchaseLine::where('transaction_id', $transaction->id)
                    ->with(['product', 'variations'])
                    ->get();

                // Add purchase lines
                foreach ($purchase_lines as $line) {
                    $all_items->push([
                        'source' => 'purchase_line',
                        'product_id' => $line->product_id,
                        'variation_id' => $line->variation_id,
                        'quantity' => $line->quantity,
                        'product_name' => $line->product->name ?? 'Unknown',
                        'variation_name' => $line->variations->name ?? '',
                    ]);
                }
            }

            // Check inventory availability for each item
            $lines_with_stock = [];
            $lines_without_stock = [];
            $total_lines = 0;
            $lines_with_stock_count = 0;
            $lines_without_stock_count = 0;

            foreach ($all_items as $item) {
                $total_lines++;
                $available_qty = 0;

                if ($item['variation_id']) {
                    // Check variation stock
                    $available_qty = DB::table('variation_location_details')
                        ->where('variation_id', $item['variation_id'])
                        ->where('location_id', $transaction->location_id)
                        ->value('qty_available') ?? 0;
                } elseif ($item['product_id']) {
                    // Check product stock by summing all variations for this product
                    $available_qty = DB::table('variation_location_details')
                        ->join('variations', 'variation_location_details.variation_id', '=', 'variations.id')
                        ->where('variations.product_id', $item['product_id'])
                        ->where('variation_location_details.location_id', $transaction->location_id)
                        ->sum('variation_location_details.qty_available') ?? 0;
                }

                $line_data = [
                    'source' => $item['source'],
                    'product_name' => $item['product_name'],
                    'variation_name' => $item['variation_name'],
                    'quantity' => $item['quantity'],
                    'available_qty' => $available_qty,
                    'can_restore' => $available_qty >= $item['quantity'],
                    'shortage' => max(0, $item['quantity'] - $available_qty)
                ];

                if ($line_data['can_restore']) {
                    $lines_with_stock[] = $line_data;
                    $lines_with_stock_count++;
                } else {
                    $lines_without_stock[] = $line_data;
                    $lines_without_stock_count++;
                }
            }

            // Get transaction payments
            $payments = TransactionPayment::withTrashed()
                ->where('transaction_id', $transaction->id)
                ->get();

            $payment_status = $transaction->payment_status;
            $total_paid = $payments->sum('amount');
            $has_payments = $payments->count() > 0;
            $all_payments_restorable = $payments->every(function($payment) {
                return $payment->trashed();
            });

            // Combine all items for display
            $all_items_display = [];
            foreach ($lines_with_stock as $line) {
                $line['can_restore'] = true;
                $all_items_display[] = $line;
            }
            foreach ($lines_without_stock as $line) {
                $line['can_restore'] = false;
                $all_items_display[] = $line;
            }

            $data = [
                'transaction' => [
                    'id' => $transaction->id,
                    'invoice_no' => $transaction->invoice_no,
                    'type' => $transaction->type,
                    'sub_type' => $transaction->sub_type,
                    'transaction_date' => $transaction->transaction_date,
                    'final_total' => $transaction->final_total,
                    'payment_status' => $payment_status,
                    'contact_id' => $transaction->contact_id,
                    'location_id' => $transaction->location_id,
                ],
                'inventory' => [
                    'total_lines' => $total_lines,
                    'lines_with_stock' => $lines_with_stock_count,
                    'lines_without_stock' => $lines_without_stock_count,
                    'lines_with_stock_data' => $lines_with_stock,
                    'lines_without_stock_data' => $lines_without_stock,
                    'all_items' => $all_items_display,
                    'can_restore_all' => $lines_without_stock_count === 0,
                ],
                'payments' => [
                    'has_payments' => $has_payments,
                    'total_paid' => $total_paid,
                    'payment_status' => $payment_status,
                    'all_payments_restorable' => $all_payments_restorable,
                    'payments_count' => $payments->count(),
                ]
            ];

            // Return HTML for modal
            $html = view('repair::recycle_bin.restore_preview', compact('data'))->render();

            return [
                'success' => true,
                'html' => $html,
                'data' => $data
            ];
        } catch (\Exception $e) {
            Log::error('Error getting restore preview: ' . $e->getMessage());
            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
    }

    /**
     * Restore a transaction with smart options
     */
    public function restoreTransactionWithOptions(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        $restore_options = $request->input('restore_options', []);
        $selected_items = $restore_options['selected_items'] ?? [];

        try {
            $transaction = Transaction::onlyTrashed()
                ->where('business_id', $business_id)
                ->findOrFail($id);

            DB::beginTransaction();

            // Group selected items by source type
            $selected_sell_lines = [];
            $selected_job_orders = [];
            $selected_purchase_lines = [];

            foreach ($selected_items as $item) {
                if ($item['source'] === 'sell_line') {
                    $selected_sell_lines[] = $item;
                } elseif ($item['source'] === 'job_order') {
                    $selected_job_orders[] = $item;
                } elseif ($item['source'] === 'purchase_line') {
                    $selected_purchase_lines[] = $item;
                }
            }

            // Create purchase orders for items with insufficient stock
            $purchase_transactions_created = [];
            if ($transaction->type === 'sell') {
                foreach ($selected_sell_lines as $item) {
                    $available_qty = 0;
                    if ($item['variation_id']) {
                        $available_qty = DB::table('variation_location_details')
                            ->where('variation_id', $item['variation_id'])
                            ->where('location_id', $transaction->location_id)
                            ->value('qty_available') ?? 0;
                    } elseif ($item['product_id']) {
                        $available_qty = DB::table('variation_location_details')
                            ->join('variations', 'variation_location_details.variation_id', '=', 'variations.id')
                            ->where('variations.product_id', $item['product_id'])
                            ->where('variation_location_details.location_id', $transaction->location_id)
                            ->sum('variation_location_details.qty_available') ?? 0;
                    }

                    $shortage = max(0, $item['quantity'] - $available_qty);

                    if ($shortage > 0) {
                        // Check product flags to skip purchase order creation
                        $product = DB::table('products')
                            ->where('id', $item['product_id'])
                            ->first();

                        // Skip purchase order if product has any of these flags
                        if ($product && ($product->enable_stock == 1 || $product->virtual_product == 1 || $product->client_flagged == 1)) {
                            continue;
                        }

                        // Search product_joborder for supplier and purchase price
                        $job_order_data = DB::table('product_joborder')
                            ->where('job_order_id', $transaction->repair_job_sheet_id)
                            ->where('product_id', $item['product_id'])
                            ->first();

                        if ($job_order_data) {
                            $purchase_price = $job_order_data->purchase_price ?? 0;
                            $supplier_id = $job_order_data->supplier_id ?? null;

                            // Create purchase order
                            $purchase_transaction = new Transaction();
                            $purchase_transaction->business_id = $business_id;
                            $purchase_transaction->location_id = $transaction->location_id;
                            $purchase_transaction->type = 'purchase';
                            $purchase_transaction->sub_type = null;
                            $purchase_transaction->status = 'received';
                            $purchase_transaction->contact_id = $supplier_id;
                            $purchase_transaction->transaction_date = now();
                            $purchase_transaction->total_before_tax = $purchase_price * $shortage;
                            $purchase_transaction->final_total = $purchase_price * $shortage;
                            $purchase_transaction->payment_status = 'paid';
                            $ref_count = $this->productUtil->setAndGetReferenceCount('purchase');
                            $purchase_transaction->invoice_no = $this->commonUtil->generateReferenceNumber('purchase', $ref_count);
                            $purchase_transaction->created_by = auth()->user()->id;
                            $purchase_transaction->repair_job_sheet_id = $transaction->repair_job_sheet_id;
                            $purchase_transaction->invoice_ref = $transaction->id;
                            $purchase_transaction->save();

                            // Create purchase line
                            $purchase_line = new PurchaseLine();
                            $purchase_line->transaction_id = $purchase_transaction->id;
                            $purchase_line->product_id = $item['product_id'];
                            $purchase_line->variation_id = $item['variation_id'];
                            $purchase_line->quantity = $shortage;
                            $purchase_line->asked_qty = $shortage;
                            $purchase_line->purchase_price = $purchase_price;
                            $purchase_line->purchase_price_inc_tax = $purchase_price;
                            $purchase_line->item_tax = 0;
                            $purchase_line->tax_id = null;
                            $purchase_line->quantity_sold = 0;
                            $purchase_line->quantity_adjusted = 0;
                            $purchase_line->quantity_returned = 0;
                            $purchase_line->lot_number = null;
                            $purchase_line->save();

                            // Update stock
                            $this->productUtil->updateProductQuantity(
                                $transaction->location_id,
                                $item['product_id'],
                                $item['variation_id'],
                                $shortage
                            );

                            $purchase_transactions_created[] = $purchase_transaction->id;
                        }
                    }
                }
            }

            // Restore the transaction
            $transaction->restore();

            // Recreate sell/purchase lines from product_joborder
            if ($transaction->type === 'sell') {
                // Get product_joborder items for this job sheet
                $job_order_items = DB::table('product_joborder')
                    ->where('job_order_id', $transaction->repair_job_sheet_id)
                    ->get();

                foreach ($job_order_items as $job_item) {
                    // Check if this item was selected for restore
                    $is_selected = false;
                    foreach ($selected_sell_lines as $selected) {
                        if ($selected['product_id'] == $job_item->product_id) {
                            $is_selected = true;
                            break;
                        }
                    }

                    if (!$is_selected) {
                        continue;
                    }

                    // Check if sell line already exists (in case of partial restore)
                    $existing_line = TransactionSellLine::where('transaction_id', $transaction->id)
                        ->where('product_id', $job_item->product_id)
                        ->first();

                    if (!$existing_line) {
                        // Create new sell line from product_joborder
                        $sell_line = new TransactionSellLine();
                        $sell_line->transaction_id = $transaction->id;
                        $sell_line->product_id = $job_item->product_id;
                        $sell_line->variation_id = $job_item->variation_id;
                        $sell_line->quantity = $job_item->quantity;
                        $sell_line->unit_price = $job_item->end_user_price ?? 0;
                        $sell_line->unit_price_inc_tax = $job_item->end_user_price ?? 0;
                        $sell_line->item_tax = 0;
                        $sell_line->tax_id = null;
                        $sell_line->discount_amount = 0;
                        $sell_line->discount_type = 'none';
                        $sell_line->quantity_sold = 0;
                        $sell_line->quantity_returned = 0;
                        $sell_line->quantity_adjusted = 0;
                        $sell_line->sub_unit_id = null;
                        $sell_line->save();

                        // Deduct stock for this item
                        if ($job_item->variation_id) {
                            $this->productUtil->decreaseProductQuantity(
                                $job_item->product_id,
                                $job_item->variation_id,
                                $transaction->location_id,
                                $job_item->quantity
                            );
                        }
                    }
                }
            } elseif ($transaction->type === 'purchase') {
                // Get product_joborder items for this job sheet
                $job_order_items = DB::table('product_joborder')
                    ->where('job_order_id', $transaction->repair_job_sheet_id)
                    ->get();

                foreach ($job_order_items as $job_item) {
                    // Check if this item was selected for restore
                    $is_selected = false;
                    foreach ($selected_purchase_lines as $selected) {
                        if ($selected['product_id'] == $job_item->product_id) {
                            $is_selected = true;
                            break;
                        }
                    }

                    if (!$is_selected) {
                        continue;
                    }

                    // Check if purchase line already exists
                    $existing_line = PurchaseLine::where('transaction_id', $transaction->id)
                        ->where('product_id', $job_item->product_id)
                        ->first();

                    if (!$existing_line) {
                        // Create new purchase line from product_joborder
                        $purchase_line = new PurchaseLine();
                        $purchase_line->transaction_id = $transaction->id;
                        $purchase_line->product_id = $job_item->product_id;
                        $purchase_line->variation_id = $job_item->variation_id;
                        $purchase_line->quantity = $job_item->quantity;
                        $purchase_line->asked_qty = $job_item->quantity;
                        $purchase_line->purchase_price = $job_item->purchase_price ?? 0;
                        $purchase_line->purchase_price_inc_tax = $job_item->purchase_price ?? 0;
                        $purchase_line->item_tax = 0;
                        $purchase_line->tax_id = null;
                        $purchase_line->quantity_sold = 0;
                        $purchase_line->quantity_adjusted = 0;
                        $purchase_line->quantity_returned = 0;
                        $purchase_line->lot_number = null;
                        $purchase_line->save();

                        // Add stock for this item
                        if ($job_item->variation_id) {
                            $this->productUtil->updateProductQuantity(
                                $transaction->location_id,
                                $job_item->product_id,
                                $job_item->variation_id,
                                $job_item->quantity
                            );
                        }
                    }
                }
            }

            // Handle payments based on options
            if (isset($restore_options['restore_payments']) && $restore_options['restore_payments']) {
                TransactionPayment::withTrashed()
                    ->where('transaction_id', $transaction->id)
                    ->get()
                    ->each(function ($payment) {
                        if ($payment->trashed()) {
                            $payment->restore();
                        }
                    });
            }

            DB::commit();

            $msg = __('repair::lang.transaction_restored_with_options');
            if (!empty($purchase_transactions_created)) {
                $msg .= ' ' . __('repair::lang.purchase_orders_created', ['count' => count($purchase_transactions_created)]);
            }

            return [
                'success' => true,
                'msg' => $msg
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error restoring transaction with options: ' . $e->getMessage());
            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
    }

    /**
     * Restore a transaction
     */
    public function restoreTransaction($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $transaction = Transaction::onlyTrashed()
                ->where('business_id', $business_id)
                ->findOrFail($id);

            $transaction->restore();

            // Restore associated transaction payments
            TransactionPayment::withTrashed()
                ->where('transaction_id', $transaction->id)
                ->get()
                ->each(function ($payment) {
                    if ($payment->trashed()) {
                        $payment->restore();
                    }
                });

            return [
                'success' => true,
                'msg' => __('repair::lang.transaction_restored')
            ];
        } catch (\Exception $e) {
            Log::error('Error restoring transaction: ' . $e->getMessage());
            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
    }

    /**
     * Permanently delete a job sheet
     */
    public function permanentDeleteJobSheet($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $job_sheet = JobSheet::onlyTrashed()
                ->where('business_id', $business_id)
                ->findOrFail($id);

            // Check for admin password confirmation if needed
            if (request()->has('password') && !empty(request()->input('password'))) {
                $password = request()->input('password');
                if (!Hash::check($password, auth()->user()->password)) {
                    return [
                        'success' => false,
                        'msg' => __('auth.failed')
                    ];
                }
            }

            $job_sheet->forceDelete();

            return [
                'success' => true,
                'msg' => __('repair::lang.job_sheet_permanently_deleted')
            ];
        } catch (\Exception $e) {
            Log::error('Error permanently deleting job sheet: ' . $e->getMessage());
            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
    }

    /**
     * Permanently delete a transaction
     */
    public function permanentDeleteTransaction($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!$this->commonUtil->is_admin(auth()->user(), $business_id)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $transaction = Transaction::onlyTrashed()
                ->where('business_id', $business_id)
                ->findOrFail($id);

            // Check for admin password confirmation if needed
            if (request()->has('password') && !empty(request()->input('password'))) {
                $password = request()->input('password');
                if (!Hash::check($password, auth()->user()->password)) {
                    return [
                        'success' => false,
                        'msg' => __('auth.failed')
                    ];
                }
            }

            DB::beginTransaction();

            // Permanently delete related transaction payments
            TransactionPayment::withTrashed()
                ->where('transaction_id', $transaction->id)
                ->forceDelete();

            // Delete related transaction sell lines
            TransactionSellLine::where('transaction_id', $transaction->id)->delete();

            // Permanently delete the transaction
            $transaction->forceDelete();

            DB::commit();

            return [
                'success' => true,
                'msg' => __('repair::lang.transaction_permanently_deleted')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error permanently deleting transaction: ' . $e->getMessage());
            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
    }
}
