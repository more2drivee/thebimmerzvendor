<?php

namespace Modules\Treasury\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Transaction;
use App\Utils\NotificationUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class DueTransactionController extends Controller
{
    protected $transactionUtil;
    protected $notificationUtil;

    public function __construct(TransactionUtil $transactionUtil, NotificationUtil $notificationUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->notificationUtil = $notificationUtil;
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_locations = DB::table('business_locations')
            ->where('business_id', $business_id)
            ->get();

        return view('treasury::due-transactions.index')
            ->with(compact('business_locations'));
    }

    public function getDueTransactionsData(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $query = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
                ->select(
                    'transactions.id',
                    'transactions.invoice_no',
                    'transactions.ref_no',
                    'transactions.type',
                    'transactions.sub_type',
                    'transactions.transaction_date',
                    'transactions.general_due_date',
                    'transactions.final_total',
                    'transactions.payment_status',
                    'transactions.status',
                    'contacts.name as contact_name',
                    'contacts.mobile as contact_mobile',
                    'bl.name as location_name'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.is_due_transaction', 1)
                ->whereNull('transactions.deleted_at');

            $location_id = request()->get('location_id');
            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            $start_date = request()->get('start_date');
            $end_date = request()->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transactions.general_due_date)'), [$start_date, $end_date]);
            }

            $query->orderBy('transactions.general_due_date', 'asc');

            return datatables()->of($query)
                ->editColumn('invoice_no', function ($row) {
                    $display_no = !empty($row->invoice_no) ? $row->invoice_no : $row->ref_no;
                    return '<a href="' . route('treasury.transaction_overview', ['transaction_id' => $row->id]) . '" target="_blank">' . $display_no . '</a>';
                })
                ->editColumn('transaction_date', function ($row) {
                    return $this->transactionUtil->format_date($row->transaction_date);
                })
                ->editColumn('due_date', function ($row) {
                    if ($row->payment_status == 'due' && $row->final_total == 0) {
                        return '-';
                    }
                    if (!empty($row->general_due_date)) {
                        $due_date = \Carbon::parse($row->general_due_date);
                        $is_overdue = $due_date->isPast();
                        $color = $is_overdue ? '#dc3545' : '#28a745';
                        return '<span style="background-color: ' . $color . '; color: white; padding: 5px 10px; border-radius: 4px;">' .
                            $this->transactionUtil->format_date($row->general_due_date) . '</span>';
                    }
                    return '-';
                })
                ->editColumn('final_total', function ($row) {
                    return $this->transactionUtil->num_f($row->final_total, true);
                })
                ->editColumn('payment_status', function ($row) {
                    if ($row->payment_status == 'due' && $row->final_total == 0) {
                        return '';
                    }
                    $status = $row->payment_status ?? '';
                    $status_class = $status == 'partial' ? 'bg-primary' : ($status == 'due' ? 'bg-warning' : ($status == 'paid' ? 'bg-success' : ''));
                    return '<span class="label ' . $status_class . '">' . __('lang_v1.' . $row->payment_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">';
                    $html .= '<button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown">
                                ' . __('messages.actions') . ' <span class="caret"></span>
                              </button>';
                    $html .= '<ul class="dropdown-menu dropdown-menu-left">';
                    $raw_due_date = !empty($row->due_date) ? $row->due_date->format('Y-m-d') : '';
                    $html .= '<li><a href="#" class="postpone-due" data-id="' . $row->id . '" data-due-date="' . $raw_due_date . '">
                                <i class="fas fa-clock"></i> ' . __('treasury::lang.postpone_due') . '</a></li>';
                    if (!empty($row->contact_mobile)) {
                        $html .= '<li><a href="#" class="send-due-sms" data-id="' . $row->id . '">
                                    <i class="fas fa-sms"></i> ' . __('treasury::lang.send_sms') . '</a></li>';
                    }
                    $html .= '<li><a href="#" class="view-due-history" data-id="' . $row->id . '">
                                <i class="fas fa-history"></i> ' . __('treasury::lang.view_due_history') . '</a></li>';
                    $html .= '<li><a href="#" class="remove-due-flag" data-id="' . $row->id . '">
                                <i class="fas fa-times"></i> ' . __('treasury::lang.remove_due_flag') . '</a></li>';
                    $html .= '</ul></div>';
                    return $html;
                })
                ->rawColumns(['invoice_no', 'due_date', 'payment_status', 'action'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('Get due transactions data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function setTransactionAsDue(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'transaction_id' => 'required|integer',
                'due_date' => 'required|date'
            ]);

            $transaction = Transaction::where('business_id', $business_id)
                ->findOrFail($request->transaction_id);

            $old_due_date = $transaction->general_due_date;

            $transaction->is_due_transaction = 1;
            $transaction->general_due_date = $request->due_date;
            $transaction->save();

            DB::table('due_date_history')->insert([
                'transaction_id' => $transaction->id,
                'old_due_date' => $old_due_date,
                'new_due_date' => $request->due_date,
                'reason' => 'Marked as Due',
                'created_by' => auth()->user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('treasury::lang.transaction_marked_as_due')
            ]);
        } catch (\Exception $e) {
            Log::error('Set transaction as due error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function postponeDueTransaction(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'transaction_id' => 'required|integer',
                'new_due_date' => 'required|date'
            ]);

            $transaction = Transaction::where('business_id', $business_id)
                ->findOrFail($request->transaction_id);

            $old_due_date = $transaction->general_due_date;

            $transaction->general_due_date = $request->new_due_date;
            $transaction->save();

            DB::table('due_date_history')->insert([
                'transaction_id' => $transaction->id,
                'old_due_date' => $old_due_date,
                'new_due_date' => $request->new_due_date,
                'reason' => 'Postponed',
                'created_by' => auth()->user()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('treasury::lang.due_date_postponed')
            ]);
        } catch (\Exception $e) {
            Log::error('Postpone due transaction error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function sendDueTransactionSms(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'transaction_id' => 'required|integer'
            ]);

            $transaction = Transaction::where('business_id', $business_id)
                ->findOrFail($request->transaction_id);

            if (empty($transaction->contact_id)) {
                return response()->json([
                    'success' => false,
                    'msg' => __('treasury::lang.no_contact_associated')
                ], 400);
            }

            $contact = DB::table('contacts')
                ->where('id', $transaction->contact_id)
                ->first();

            if (empty($contact) || empty($contact->mobile)) {
                return response()->json([
                    'success' => false,
                    'msg' => __('treasury::lang.no_contact_mobile')
                ], 400);
            }

            $business = DB::table('business')
                ->where('id', $business_id)
                ->first();

            $message = __('treasury::lang.due_reminder_sms', [
                'invoice_no' => $transaction->invoice_no ?? $transaction->ref_no,
                'due_date' => $this->transactionUtil->format_date($transaction->general_due_date),
                'amount' => $this->transactionUtil->num_f($transaction->final_total, true),
                'business_name' => $business->name ?? ''
            ]);

            $response = $this->notificationUtil->sendSms($contact->mobile, $message, $business_id);

            if (isset($response['success']) && $response['success']) {
                return response()->json([
                    'success' => true,
                    'msg' => __('treasury::lang.sms_sent_successfully')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'msg' => $response['msg'] ?? __('messages.something_went_wrong')
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Send due transaction SMS error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function getDueDateHistory(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'transaction_id' => 'required|integer'
            ]);

            $transaction = Transaction::where('business_id', $business_id)
                ->findOrFail($request->transaction_id);

            $history = DB::table('due_date_history as ddh')
                ->leftJoin('users as u', 'ddh.created_by', '=', 'u.id')
                ->select(
                    'ddh.old_due_date',
                    'ddh.new_due_date',
                    'ddh.reason',
                    'ddh.created_at',
                    'u.first_name',
                    'u.last_name'
                )
                ->where('ddh.transaction_id', $request->transaction_id)
                ->orderBy('ddh.created_at', 'desc')
                ->get();

            $history_data = [];
            foreach ($history as $record) {
                $created_by_name = '';
                if (!empty($record->first_name) || !empty($record->last_name)) {
                    $created_by_name = trim($record->first_name . ' ' . $record->last_name);
                }

                $history_data[] = [
                    'old_due_date' => $record->old_due_date,
                    'new_due_date' => $record->new_due_date,
                    'reason' => $record->reason,
                    'created_by_name' => $created_by_name,
                    'created_at' => $record->created_at
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $history_data
            ]);
        } catch (\Exception $e) {
            Log::error('Get due date history error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function toggleDueTransaction(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        try {
            $request->validate([
                'transaction_id' => 'required|integer',
                'is_due' => 'required'
            ]);

            $transaction = Transaction::where('business_id', $business_id)
                ->findOrFail($request->transaction_id);

            $is_due_value = filter_var($request->is_due, FILTER_VALIDATE_BOOLEAN);
            $transaction->is_due_transaction = $is_due_value ? 1 : 0;

            if ($is_due_value && empty($transaction->general_due_date)) {
                $transaction->general_due_date = \Carbon::now()->addDays(7);
            }

            $transaction->save();

            return response()->json([
                'success' => true,
                'msg' => $is_due_value ? __('treasury::lang.transaction_marked_as_due') : __('treasury::lang.due_flag_removed')
            ]);
        } catch (\Exception $e) {
            Log::error('Toggle due transaction error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }
}
