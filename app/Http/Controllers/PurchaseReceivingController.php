<?php

namespace App\Http\Controllers;

use App\Transaction;
use App\Models\PurchaseLine;
use App\VariationLocationDetails;
use App\BusinessLocation;
use App\Contact;
use App\Utils\TransactionUtil;
use App\Utils\ProductUtil;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseReceivingController extends Controller
{
    protected $transactionUtil;
    protected $productUtil;

    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->middleware('auth');
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $orderStatuses = [
            'pending' => __('purchase.pending'),
            'received' => __('purchase.received'),
        ];
        
        return view('purchase_receiving.index', compact('business_locations', 'suppliers', 'orderStatuses'));
    }

    public function getData(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');

        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('purchase_lines')
                    ->whereColumn('purchase_lines.transaction_id', 'transactions.id')
                    ->whereRaw('purchase_lines.quantity > COALESCE(purchase_lines.asked_qty, purchase_lines.quantity)');
            })
            ->where(function ($q) {
                $q->where('status', 'pending')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'received')
                         ->whereExists(function ($query) {
                             $query->select(DB::raw(1))
                                 ->from('purchase_lines')
                                 ->whereColumn('purchase_lines.transaction_id', 'transactions.id')
                                 ->whereRaw('COALESCE(purchase_lines.asked_qty, purchase_lines.quantity) > purchase_lines.quantity');
                         });
                  });
            })
            ->with(['contact', 'purchase_lines']);

        // Apply filters
        if (!empty($request->location_id)) {
            $query->where('location_id', $request->location_id);
        }

        if (!empty($request->supplier_id)) {
            $query->where('contact_id', $request->supplier_id);
        }

      
    

        if (!empty($request->date_range)) {
            $dates = explode(' ~ ', $request->date_range);
            if (count($dates) == 2) {
                $query->whereBetween('transaction_date', [$dates[0], $dates[1]]);
            }
        }

        if (!auth()->user()->can('purchase.view_all') && !auth()->user()->can('view_own_purchase')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!auth()->user()->can('purchase.view_all') && auth()->user()->can('view_own_purchase')) {
            $query->where('created_by', $user_id);
        }

        $purchases = $query->select([
            'id',
            'ref_no',
            'transaction_date',
            'final_total',
            'status',
            'contact_id',
            'total_before_tax',
            'tax_amount',
            'discount_amount',
            'shipping_charges',
            'additional_notes'
        ])->orderBy('transaction_date', 'desc');

        return DataTables::of($purchases)
            ->editColumn('ref_no', function ($row) {
                return '<a href="#" class="view-purchase-lines" data-id="' . $row->id . '">' . $row->ref_no . '</a>';
            })
            ->editColumn('transaction_date', function ($row) {
                return $this->transactionUtil->format_date($row->transaction_date, true);
            })
            ->addColumn('supplier', function ($row) {
                return $row->contact ? $row->contact->name : '';
            })
            ->addColumn('total_asked', function ($row) {
                $totalAsked = $row->purchase_lines->sum('asked_qty');
                return number_format($totalAsked, 2);
            })
            ->addColumn('total_received', function ($row) {
                if ($row->status == 'pending') {
                    return number_format(0, 2);
                }
                $totalReceived = $row->purchase_lines->sum('quantity');
                return number_format($totalReceived, 2);
            })
            ->addColumn('remaining', function ($row) {
                $totalAsked = $row->purchase_lines->sum('asked_qty');
                if ($row->status == 'pending') {
                    return number_format($totalAsked, 2);
                }
                $totalReceived = $row->purchase_lines->sum('quantity');
                $remaining = max(0, $totalAsked - $totalReceived);
                return number_format($remaining, 2);
            })
            ->addColumn('progress', function ($row) {
                $totalAsked = $row->purchase_lines->sum('asked_qty');
                $totalReceived = $row->purchase_lines->sum('quantity');
                
                if ($row->status == 'pending') {
                    $percentage = 0;
                } elseif ($totalAsked == 0) {
                    $percentage = 0;
                } else {
                    $percentage = min(100, ($totalReceived / $totalAsked) * 100);
                }
                
                $color = $percentage == 100 ? 'success' : ($percentage >= 50 ? 'info' : 'warning');
                
                return '<div class="progress" style="height: 20px; margin-bottom: 0;">
                    <div class="progress-bar progress-bar-' . $color . '" role="progressbar" 
                         style="width: ' . $percentage . '%" aria-valuenow="' . $percentage . '" 
                         aria-valuemin="0" aria-valuemax="100">
                        ' . number_format($percentage, 0) . '%
                    </div>
                </div>';
            })
            ->editColumn('final_total', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">' . $row->final_total . '</span>';
            })
            ->editColumn('status', function ($row) {
                $totalAsked = $row->purchase_lines->sum('asked_qty');
                $totalReceived = $row->purchase_lines->sum('received_qty');
                $remaining = max(0, $totalAsked - $totalReceived);
                
                if ($totalAsked < $totalReceived) {
                    $status = 'pending';
                    $color = 'bg-yellow-100 text-yellow-800';
                } elseif ($totalAsked == $totalReceived) {
                    $status = 'pending';
                    $color = 'bg-yellow-100 text-yellow-800';
                } elseif ($remaining == 0) {
                    $status = 'received';
                    $color = 'bg-green-100 text-green-800';
                } elseif ($totalReceived > 0) {
                    $status = 'partially received';
                    $color = 'bg-purple-100 text-purple-800';
                } else {
                    $status = 'pending';
                    $color = 'bg-yellow-100 text-yellow-800';
                }
                
                return '<span class="px-2 py-1 rounded-full text-xs font-semibold ' . $color . '">' . ucfirst($status) . '</span>';
            })
            ->addColumn('action', function ($row) {
                $remaining = max(0, $row->purchase_lines->sum('asked_qty') - $row->purchase_lines->sum('received_qty'));
                
                $html = '<button type="button" class="btn btn-info btn-xs view-purchase-lines" data-id="' . $row->id . '">
                    <i class="fas fa-eye"></i> ' . __('purchase.view_lines') . '
                </button>';
                
              
                return $html;
            })
            ->rawColumns(['ref_no', 'progress', 'final_total', 'status', 'action'])
            ->make(true);
    }

    public function getPurchaseLines($id)
    {
        $business_id = request()->session()->get('user.business_id');
        
        $purchase = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->where('id', $id)
            ->with(['purchase_lines' => function ($query) {
                $query->with(['product']);
            }])
            ->first();

        if (!$purchase) {
            return response()->json(['success' => false, 'message' => 'Purchase not found'], 404);
        }

        $lines = $purchase->purchase_lines->map(function ($line) {
            $askedQty = $line->asked_qty ?? $line->quantity;
            $receivedQty = $line->quantity; // Use current quantity as received qty
            $remaining = max(0, $askedQty - $receivedQty);

            return [
                'id' => $line->id,
                'product_name' => $line->product ? $line->product->name : '',
                'sku' => $line->product ? $line->product->sku : '',
                'variation_name' => '',
                'asked_qty' => number_format($askedQty, 2),
                'received_qty' => number_format($receivedQty, 2),
                'remaining_qty' => number_format($remaining, 2),
                'unit_price' => number_format($line->purchase_price_inc_tax, 2),
                'sub_unit_id' => $line->sub_unit_id,
                'lot_number' => $line->lot_number,
                'mfg_date' => $line->mfg_date,
                'exp_date' => $line->exp_date
            ];
        });

        return response()->json([
            'success' => true,
            'purchase' => [
                'id' => $purchase->id,
                'ref_no' => $purchase->ref_no,
                'transaction_date' => $purchase->transaction_date,
                'supplier' => $purchase->contact ? $purchase->contact->name : '',
                'supplier_id' => $purchase->contact_id,
                'asked_qty' => number_format($purchase->purchase_lines->sum('asked_qty'), 2),
                'total_received' => number_format($purchase->purchase_lines->sum('quantity'), 2),
                'total_remaining' => number_format(max(0, $purchase->purchase_lines->sum('asked_qty') - $purchase->purchase_lines->sum('quantity')), 2),
                'final_total' => number_format($purchase->final_total, 2)
            ],
            'lines' => $lines
        ]);
    }

    public function receiveRemaining(Request $request)
    {
        \Log::info('PurchaseReceiving: receiveRemaining called', [
            'purchase_id' => $request->input('purchase_id'),
            'lines' => $request->input('lines')
        ]);

        $business_id = request()->session()->get('user.business_id');
        $user_id = Auth::user()->id;

        $purchase_id = $request->input('purchase_id');
        $supplier_id = $request->input('supplier_id');
        $lines = $request->input('lines', []);

        if (empty($lines)) {
            return response()->json(['success' => false, 'message' => 'No lines to receive'], 400);
        }

        try {
            DB::beginTransaction();

            $purchase = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->where('id', $purchase_id)
                ->with(['purchase_lines'])
                ->first();

            if (!$purchase) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Purchase not found'], 404);
            }

            $before_status = $purchase->status;
            $hasRemaining = false;

            foreach ($lines as $lineData) {
                $line = $purchase->purchase_lines->find($lineData['line_id']);
                
                if (!$line) {
                    continue;
                }

                $askedQty = $line->asked_qty ?? $line->quantity;
                $originalReceived = $line->quantity; // Store original before modification
                $toReceive = (float) $lineData['to_receive'];

                // Treat to_receive as the TOTAL quantity to set (allows both increase and decrease)
                if ($toReceive < 0) {
                    $toReceive = 0;
                }

                // Calculate the difference (delta for stock adjustment)
                // If this is the first receive (pending -> received), add the full received quantity
                // Otherwise, adjust VLD by the difference (increase or decrease)
                if ($before_status === 'pending') {
                    // First receive: add the full received quantity to VLD
                    $qtyDiff = $toReceive;
                } else {
                    // Already received: adjust VLD by the difference
                    // If increasing, add the difference. If decreasing, subtract the difference.
                    $qtyDiff = $toReceive - $originalReceived;
                }

                // Update purchase line quantity to the new total
                $line->quantity = $toReceive;
                $line->save();

                \Log::info('PurchaseReceiving: Before VLD update', [
                    'line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'location_id' => $purchase->location_id,
                    'askedQty' => $askedQty,
                    'originalReceived' => $originalReceived,
                    'toReceive_input' => (float) $lineData['to_receive'],
                    'newQuantity' => $toReceive,
                    'qtyDiff' => $qtyDiff,
                    'will_update' => ($qtyDiff != 0)
                ]);

                // Get current VLD qty_available and update it directly
                $vld = VariationLocationDetails::where('product_id', $line->product_id)
                    ->where('variation_id', $line->variation_id)
                    ->where('location_id', $purchase->location_id)
                    ->first();

                $currentVldQty = $vld ? $vld->qty_available : 0;
                $newVldQty = $currentVldQty + $qtyDiff;

                \Log::info('PurchaseReceiving: VLD update', [
                    'currentVldQty' => $currentVldQty,
                    'qtyDiff' => $qtyDiff,
                    'newVldQty' => $newVldQty
                ]);

                if ($vld) {
                    $vld->qty_available = $newVldQty;
                    $vld->save();
                } else {
                    $vld = new VariationLocationDetails();
                    $vld->product_id = $line->product_id;
                    $vld->variation_id = $line->variation_id;
                    $vld->location_id = $purchase->location_id;
                    $vld->qty_available = $newVldQty;
                    $vld->save();
                }

                // Check if there are remaining items
                if ($line->quantity < $askedQty) {
                    $hasRemaining = true;
                }
            }

            $linesTotal = $purchase->purchase_lines->sum(function ($line) {
                return (float) $line->purchase_price_inc_tax * (float) $line->quantity;
            });
            $discountAmount = 0;
            if ($purchase->discount_type === 'fixed') {
                $discountAmount = (float) $purchase->discount_amount;
            } elseif ($purchase->discount_type === 'percentage') {
                $discountAmount = $linesTotal * ((float) $purchase->discount_amount / 100);
            }
            $purchase->final_total = max(0, $linesTotal - $discountAmount + (float) $purchase->shipping_charges);

            // Update purchase status to 'received' when any items are received
            $purchase->status = 'received';

            // Update supplier if provided
            if (!empty($supplier_id)) {
                $purchase->contact_id = $supplier_id;
            }

            $purchase->save();

            // Update payment status
            $this->transactionUtil->updatePaymentStatus($purchase->id, $purchase->final_total);

            // Log activity
            $this->transactionUtil->activityLog($purchase, 'updated');

            DB::commit();

            $this->syncRepairSparePartsForReceivedPurchase($purchase->fresh());

            return response()->json([
                'success' => true,
                'message' => 'Purchase received successfully',
                'new_status' => $purchase->status
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error receiving purchase: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to receive purchase'], 500);
        }
    }

    private function getLinkedSellTransactionForPurchase(Transaction $purchase): ?Transaction
    {
        if (!empty($purchase->repair_job_sheet_id)) {
            $sell = Transaction::where('type', 'sell')
                ->where('repair_job_sheet_id', $purchase->repair_job_sheet_id)
                ->orderByDesc('id')
                ->first();

            if (!empty($sell)) {
                return $sell;
            }
        }

        if (!empty($purchase->invoice_ref) && is_numeric($purchase->invoice_ref)) {
            return Transaction::where('id', (int) $purchase->invoice_ref)
                ->where('type', 'sell')
                ->first();
        }

        return null;
    }

    private function syncRepairSparePartsForReceivedPurchase(Transaction $purchase): void
    {
        try {
            if ($purchase->type !== 'purchase' || $purchase->status !== 'received') {
                return;
            }

            $sell_transaction = $this->getLinkedSellTransactionForPurchase($purchase);
            if (empty($sell_transaction) || $sell_transaction->status === 'final') {
                return;
            }

            $job_order_id = (int) ($sell_transaction->repair_job_sheet_id ?? $purchase->repair_job_sheet_id ?? 0);
            $contact_id = (int) ($sell_transaction->contact_id ?? 0);

            if ($job_order_id <= 0 || $contact_id <= 0) {
                return;
            }

            $job_products = DB::table('product_joborder')
                ->where('job_order_id', $job_order_id)
                ->select([
                    'product_id',
                    'quantity',
                    'price',
                    'delivered_status',
                    'out_for_deliver',
                    'client_approval',
                    'product_status',
                ])
                ->get();

            if ($job_products->isEmpty()) {
                return;
            }

            $payload_data = $job_products->map(function ($product) {
                return [
                    'product_id' => (int) $product->product_id,
                    'quantity' => (float) $product->quantity,
                    'price' => (float) ($product->price ?? 0),
                    'delivered_status' => (int) ($product->delivered_status ?? 0),
                    'out_for_deliver' => (int) ($product->out_for_deliver ?? 0),
                    'client_approval' => (int) ($product->client_approval ?? 0),
                    'product_status' => $product->product_status,
                ];
            })->values()->toArray();

            $sync_request = new Request([
                'job_order_id' => $job_order_id,
                'contact_id' => $contact_id,
                'data' => $payload_data,
            ]);

            app(\Modules\Connector\Http\Controllers\Api\SparePartsController::class)
                ->store_spareparts($sync_request);
        } catch (\Exception $e) {
            \Log::error('PurchaseReceiving spare-parts sync failed', [
                'purchase_id' => $purchase->id ?? null,
                'repair_job_sheet_id' => $purchase->repair_job_sheet_id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
