<?php

namespace Modules\Treasury\Http\Controllers;

use Modules\Treasury\Services\TreasuryService;
use Modules\Treasury\Services\TreasuryTransactionService;
use Modules\Treasury\Services\TreasuryChartService;
use Modules\Treasury\Services\InternalTransferService;
use Modules\Treasury\Services\TreasuryValidationService;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Utils\BusinessUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\TransactionPayment;
use App\Transaction;

/**
 * Treasury Controller
 * 
 * Refactored to use service layer architecture following clean code principles
 * All business logic has been moved to dedicated services
 */
class TreasuryController extends Controller
{
    protected TreasuryService $treasuryService;
    protected TreasuryTransactionService $transactionService;
    protected TreasuryChartService $chartService;
    protected InternalTransferService $internalTransferService;
    protected TreasuryValidationService $validationService;
    protected ModuleUtil $moduleUtil;
    protected TransactionUtil $transactionUtil;
    protected Util $commonUtil;
    protected BusinessUtil $businessUtil;

    public function __construct(
        TreasuryService $treasuryService,
        TreasuryTransactionService $transactionService,
        TreasuryChartService $chartService,
        InternalTransferService $internalTransferService,
        TreasuryValidationService $validationService,
        ModuleUtil $moduleUtil,
        TransactionUtil $transactionUtil,
        Util $commonUtil,
        BusinessUtil $businessUtil
    ) {
        $this->treasuryService = $treasuryService;
        $this->transactionService = $transactionService;
        $this->chartService = $chartService;
        $this->internalTransferService = $internalTransferService;
        $this->validationService = $validationService;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->commonUtil = $commonUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Get pending transaction payments for DataTable
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingPayments(Request $request)
    {
        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {

            $query = TransactionPayment::query()
                ->where('status', 'due')
                ->with(['transaction']);



            $payments = $query->orderByDesc('created_at')->get();

            $data = $payments->map(function ($p) {
                $documentUrl = null;
                if ($p->document) {
                    // Generate full URL to the stored document
                    $documentUrl = asset('storage/' . $p->document);
                }

                return [
                    'id' => $p->id,
                    'date' => optional($p->created_at)->format('Y-m-d H:i'),
                    'payment_ref_no' => $p->payment_ref_no ?? '',
                    'amount' => (float)($p->amount ?? 0),
                    'method' => $p->method ?? '',
                    'document' => $p->document ?? null,
                    'document_url' => $documentUrl,
                    'document_name' => $p->document_name ?? null,
                    'status' => $p->status ?? 'due',
                ];
            });

            return response()->json([
                'draw' => (int) ($request->get('draw') ?? 1),
                'recordsTotal' => $payments->count(),
                'recordsFiltered' => $payments->count(),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Pending payments fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Display treasury dashboard
     *
     * @return Renderable
     */
    public function index(): Renderable
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Get permitted locations for non-admin users
            $permitted_locations = auth()->user()->permitted_locations();
            
            // Determine initial location_id for summary (use first permitted location if not admin)
            $initial_location_id = null;
            if ($permitted_locations != 'all' && !empty($permitted_locations)) {
                $initial_location_id = $permitted_locations[0];
            }

            // Get comprehensive treasury summary filtered by permitted locations
            $summary = $this->treasuryService->getUnfilteredFinancialTotals($business_id, $initial_location_id);

            // Get business locations for branch filter (already filtered by permitted locations via forDropdown)
            $business_locations = $this->treasuryService->getBusinessLocations($business_id);

            $payment_methods = $this->commonUtil->payment_types(null, true, $business_id) ?? [];

            return view('treasury::dashboard.index', compact('business_locations', 'summary', 'payment_methods', 'initial_location_id'));
        } catch (\Exception $e) {
            Log::error('Treasury dashboard error: ' . $e->getMessage());
            abort(500, 'Error loading treasury dashboard.');
        }
    }

    /**
     * Get treasury transactions for datatable
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTreasuryTransactions()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Validate and prepare filters
            $filters = [
                'start_date' => request()->get('start_date'),
                'end_date' => request()->get('end_date'),
                'payment_status' => request()->get('payment_status'),
                'transaction_type' => request()->get('transaction_type'),
                'location_id' => request()->get('location_id')
            ];

            // Validate filters
            $validation_result = $this->validationService->validateTransactionFilters($filters);
            if (!$validation_result['success']) {
                return response()->json([
                    'success' => false,
                    'errors' => $validation_result['errors']
                ], 422);
            }

            return $this->transactionService->getTreasuryTransactionsDataTable($business_id, $filters);
        } catch (\Exception $e) {
            Log::error('Treasury transactions DataTable error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function paymentsIndex()
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }
        $payment_methods = $this->commonUtil->payment_types(null, false, $business_id);
        return view('treasury::payments.index', compact('payment_methods'));
    }

    public function getPaymentsData(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        $query = DB::table('transaction_payments')
            ->leftJoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->whereNull('t.deleted_at')
            ->whereNotIn('t.type', ['opening_stock', 'internal_transfer'])
            ->select(
                'transaction_payments.id',
                'transaction_payments.transaction_id',
                'transaction_payments.paid_on',
                'transaction_payments.payment_ref_no',
                'transaction_payments.method',
                'transaction_payments.amount',
                'transaction_payments.status',
                't.type as transaction_type',
                't.invoice_no',
                't.ref_no',
                'c.name as contact_name',
                'c.supplier_business_name as supplier_business_name'
            )
            ->groupBy('transaction_payments.id');

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        $location_id = $request->get('location_id');
        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween(DB::raw('date(transaction_payments.paid_on)'), [$start_date, $end_date]);
        }

        if (!empty($request->get('status'))) {
            $query->where('transaction_payments.status', $request->get('status'));
        }

        if (!empty($request->get('method'))) {
            $query->where('transaction_payments.method', $request->get('method'));
        }

        if (!empty($request->get('transaction_type'))) {
            $query->where('t.type', $request->get('transaction_type'));
        }

        if (!empty($request->get('amount_min'))) {
            $query->where('transaction_payments.amount', '>=', (float)$request->get('amount_min'));
        }
        if (!empty($request->get('amount_max'))) {
            $query->where('transaction_payments.amount', '<=', (float)$request->get('amount_max'));
        }

        return datatables()->of($query)
            ->editColumn('paid_on', function ($row) {
                return $this->transactionUtil->format_date($row->paid_on, true);
            })
            ->editColumn('invoice_no', function ($row) {
                $display_no = $row->invoice_no;
                if (in_array($row->transaction_type, ['purchase', 'expense'])) {
                    $display_no = $row->ref_no;
                }

                if (!empty($display_no) && !empty($row->transaction_id)) {
                    return '<a href="' . route('treasury.transaction_overview', ['transaction_id' => $row->transaction_id]) . '" target="_blank">' . $display_no . '</a>';
                }
                return $display_no ?? '';
            })
            ->editColumn('amount', function ($row) {
                return $this->transactionUtil->num_f($row->amount, true);
            })
            ->addColumn('contact', function ($row) {
                if (!empty($row->supplier_business_name)) {
                    return $row->supplier_business_name . '<br>' . $row->contact_name;
                }
                return $row->contact_name;
            })
            ->addColumn('row_class', function ($row) {
                $highlight = false;
                if (strtolower($row->status ?? '') === 'pending') {
                    $highlight = true;
                }
                if ((float)$row->amount >= 1000) {
                    $highlight = true;
                }
                return $highlight ? 'tw-bg-amber-50' : '';
            })
            ->rawColumns(['contact', 'invoice_no'])
            ->make(true);
    }

    public function paymentsReport(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        $query = DB::table('transaction_payments')
            ->leftJoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $business_id)
            ->whereNotIn('t.type', ['opening_stock', 'internal_transfer'])
            ->whereNull('t.deleted_at')
            ->select(
                'transaction_payments.id',
                'transaction_payments.transaction_id',
                'transaction_payments.paid_on',
                'transaction_payments.payment_ref_no',
                'transaction_payments.method',
                'transaction_payments.amount',
                'transaction_payments.status',
                't.type as transaction_type',
                't.invoice_no',
                't.ref_no',
                'c.name as contact_name',
                'c.supplier_business_name as supplier_business_name'
            );

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        $location_id = $request->get('location_id');
        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        
        // Apply date filtering to regular payments - support single date filters too
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween(DB::raw('date(transaction_payments.paid_on)'), [$start_date, $end_date]);
        } elseif (!empty($start_date)) {
            $query->whereDate('transaction_payments.paid_on', '>=', $start_date);
        } elseif (!empty($end_date)) {
            $query->whereDate('transaction_payments.paid_on', '<=', $end_date);
        }

        if (!empty($request->get('status'))) {
            $query->where('transaction_payments.status', $request->get('status'));
        }

        if (!empty($request->get('method'))) {
            $query->where('transaction_payments.method', $request->get('method'));
        }

        if (!empty($request->get('amount_min'))) {
            $query->where('transaction_payments.amount', '>=', (float)$request->get('amount_min'));
        }
        if (!empty($request->get('amount_max'))) {
            $query->where('transaction_payments.amount', '<=', (float)$request->get('amount_max'));
        }

        $payments = $query->orderByDesc('transaction_payments.paid_on')->get();

        // Process amounts and categorize as income/outcome
        $incomeTypes = ['sell', 'purchase_return', 'income'];
        $outcomeTypes = ['purchase', 'sell_return', 'expense', 'payroll'];

        $total_income = 0.0;
        $total_outcome = 0.0;

        foreach ($payments as $payment) {
            $income_amount = 0.0;
            $outcome_amount = 0.0;
            $type = $payment->transaction_type;

            if (in_array($type, $incomeTypes, true)) {
                $income_amount = (float)$payment->amount;
            } elseif (in_array($type, $outcomeTypes, true)) {
                $outcome_amount = (float)$payment->amount;
            }

            $payment->income_amount = $income_amount;
            $payment->outcome_amount = $outcome_amount;

            if ($type !== 'opening_stock') {
                $total_income += $income_amount;
                $total_outcome += $outcome_amount;
            }
        }

        // Use payments directly without opening balance
        $all_payments = $payments;

        // Internal transfers (payment method transfers and branch/location transfers)
        $internalTransfersQuery = DB::table('transactions as it')
            ->join('transaction_payments as itp', 'it.id', '=', 'itp.transaction_id')
            ->leftJoin('business_locations as bl', 'it.location_id', '=', 'bl.id')
            ->where('it.business_id', $business_id)
            ->where('it.type', 'internal_transfer')
            ->where('it.sub_type', 'internal_transfer')
            ->whereNull('it.deleted_at')
            // Only keep the outgoing side ("Internal transfer to ...") to avoid duplicates
            ->where('it.additional_notes', 'LIKE', 'Internal transfer to %')
            ->select(
                'it.id',
                'it.transaction_date',
                'it.final_total as amount',
                'it.additional_notes as notes',
                'itp.method as payment_method',
                'it.location_id',
                'bl.name as from_location_name'
            );

        if ($permitted_locations != 'all') {
            $internalTransfersQuery->whereIn('it.location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $internalTransfersQuery->where('it.location_id', $location_id);
        }

        if (!empty($start_date) && !empty($end_date)) {
            $internalTransfersQuery->whereBetween(DB::raw('date(it.transaction_date)'), [$start_date, $end_date]);
        } elseif (!empty($start_date)) {
            $internalTransfersQuery->whereDate('it.transaction_date', '>=', $start_date);
        } elseif (!empty($end_date)) {
            $internalTransfersQuery->whereDate('it.transaction_date', '<=', $end_date);
        }

        if (!empty($request->get('method'))) {
            $internalTransfersQuery->where('itp.method', $request->get('method'));
        }

        if (!empty($request->get('amount_min'))) {
            $internalTransfersQuery->where('it.final_total', '>=', (float) $request->get('amount_min'));
        }

        if (!empty($request->get('amount_max'))) {
            $internalTransfersQuery->where('it.final_total', '<=', (float) $request->get('amount_max'));
        }

        $internal_transfers = $internalTransfersQuery
            ->orderByDesc('it.transaction_date')
            ->get();

        // Opening balances (treasury opening balance transactions)
        $openingBalancesQuery = DB::table('transactions as ob')
            ->join('transaction_payments as obp', 'ob.id', '=', 'obp.transaction_id')
            ->leftJoin('business_locations as obl', 'ob.location_id', '=', 'obl.id')
            ->where('ob.business_id', $business_id)
            ->where('ob.type', 'opening_balance')
            ->where('ob.sub_type', 'treasury_opening')
            ->whereNull('ob.deleted_at')
            ->select(
                'ob.id',
                'ob.transaction_date',
                'ob.invoice_no',
                'ob.ref_no',
                'ob.final_total as amount',
                'ob.additional_notes as notes',
                'obp.method as payment_method',
                'obl.name as location_name'
            );

        if ($permitted_locations != 'all') {
            $openingBalancesQuery->whereIn('ob.location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $openingBalancesQuery->where('ob.location_id', $location_id);
        }

        if (!empty($start_date) && !empty($end_date)) {
            $openingBalancesQuery->whereBetween(DB::raw('date(ob.transaction_date)'), [$start_date, $end_date]);
        } elseif (!empty($start_date)) {
            $openingBalancesQuery->whereDate('ob.transaction_date', '>=', $start_date);
        } elseif (!empty($end_date)) {
            $openingBalancesQuery->whereDate('ob.transaction_date', '<=', $end_date);
        }

        if (!empty($request->get('method'))) {
            $openingBalancesQuery->where('obp.method', $request->get('method'));
        }

        if (!empty($request->get('amount_min'))) {
            $openingBalancesQuery->where('ob.final_total', '>=', (float) $request->get('amount_min'));
        }

        if (!empty($request->get('amount_max'))) {
            $openingBalancesQuery->where('ob.final_total', '<=', (float) $request->get('amount_max'));
        }

        $opening_balances = $openingBalancesQuery
            ->orderByDesc('ob.transaction_date')
            ->get();

        $filters = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'location_id' => $location_id,
            'status' => $request->get('status'),
            'method' => $request->get('method'),
            'amount_min' => $request->get('amount_min'),
            'amount_max' => $request->get('amount_max'),
        ];

        $payment_methods = $this->commonUtil->payment_types(null, false, $business_id);

        $business_details = $this->businessUtil->getDetails($business_id);

        return view('treasury::payments.report', compact('all_payments', 'filters', 'payment_methods', 'business_details', 'total_income', 'total_outcome', 'internal_transfers', 'opening_balances'));
    }

    /**
     * Get dashboard cards data with date filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardCards()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $filters = [
                'start_date' => request()->get('start_date'),
                'end_date' => request()->get('end_date'),
                'location_id' => request()->get('location_id')
            ];

            // Validate date range
            $validation_result = $this->validationService->validateDashboardDateRange($filters);
            if (!$validation_result['success']) {
                return response()->json([
                    'success' => false,
                    'errors' => $validation_result['errors']
                ], 422);
            }

            $validated_data = $validation_result['data'];
            $start_date = $validated_data['start_date'] ?? null;
            $end_date = $validated_data['end_date'] ?? null;
            $location_id = $validated_data['location_id'] ?? null;

            if ($start_date && $end_date) {
                $start_date = Carbon::parse($start_date)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($end_date)->endOfDay()->format('Y-m-d H:i:s');
            }

            $sales_cards = $this->treasuryService->getSalesDashboardCards($business_id, $location_id, $start_date, $end_date);

            return response()->json([
                'success' => true,
                'data' => $sales_cards
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard cards error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get chart data with date filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChartData()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $filters = [
                'start_date' => request()->get('start_date'),
                'end_date' => request()->get('end_date'),
                'location_id' => request()->get('location_id')
            ];

            // Validate filters
            $validation_result = $this->validationService->validateDashboardDateRange($filters);
            if (!$validation_result['success']) {
                return response()->json([
                    'success' => false,
                    'errors' => $validation_result['errors']
                ], 422);
            }

            $validated_data = $validation_result['data'];
            $start_date = $validated_data['start_date'] ?? null;
            $end_date = $validated_data['end_date'] ?? null;
            $location_id = $validated_data['location_id'] ?? null;

            if ($start_date && $end_date) {
                $start_date = Carbon::parse($start_date)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($end_date)->endOfDay()->format('Y-m-d H:i:s');
            }

            $chart_data = $this->treasuryService->getDashboardChartData($business_id, $location_id, $start_date, $end_date);

            return response()->json([
                'success' => true,
                'data' => $chart_data
            ]);
        } catch (\Exception $e) {
            Log::error('Chart data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get filtered financial totals (Total Income, Total Expense, Balance)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilteredTotals()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $filters = [
                'start_date' => request()->get('start_date'),
                'end_date' => request()->get('end_date'),
                'location_id' => request()->get('location_id')
            ];

            // Validate filters
            $validation_result = $this->validationService->validateDashboardDateRange($filters);
            if (!$validation_result['success']) {
                return response()->json([
                    'success' => false,
                    'errors' => $validation_result['errors']
                ], 422);
            }

            $validated_data = $validation_result['data'];
            $start_date = $validated_data['start_date'] ?? null;
            $end_date = $validated_data['end_date'] ?? null;
            $location_id = $validated_data['location_id'] ?? null;

            if ($start_date && $end_date) {
                $start_date = Carbon::parse($start_date)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($end_date)->endOfDay()->format('Y-m-d H:i:s');
            }

            // Get filtered totals
            $totals = $this->treasuryService->getFilteredFinancialTotals($business_id, $location_id, $start_date, $end_date);

            return response()->json([
                'success' => true,
                'data' => $totals
            ]);
        } catch (\Exception $e) {
            Log::error('Filtered totals error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get payment methods chart data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentMethodsChart()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $filters = [
                'start_date' => request()->get('start_date'),
                'end_date' => request()->get('end_date'),
                'location_id' => request()->get('location_id')
            ];

            $validation_result = $this->validationService->validateDashboardDateRange($filters);
            if (!$validation_result['success']) {
                return response()->json([
                    'success' => false,
                    'errors' => $validation_result['errors']
                ], 422);
            }

            $validated_data = $validation_result['data'];
            $start_date = $validated_data['start_date'] ?? null;
            $end_date = $validated_data['end_date'] ?? null;
            $location_id = $validated_data['location_id'] ?? null;

            if ($start_date && $end_date) {
                $start_date = Carbon::parse($start_date)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($end_date)->endOfDay()->format('Y-m-d H:i:s');
            }

            $top_transaction_types = $this->chartService->getTopTransactionTypes($business_id, $location_id, $start_date, $end_date);

            return response()->json([
                'success' => true,
                'data' => $top_transaction_types
            ]);
        } catch (\Exception $e) {
            Log::error('Payment methods chart error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get transaction type trend chart data with date filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionTypeTrendChart()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $filters = [
                'start_date' => request()->get('start_date'),
                'end_date' => request()->get('end_date'),
                'location_id' => request()->get('location_id')
            ];

            $validation_result = $this->validationService->validateDashboardDateRange($filters);
            if (!$validation_result['success']) {
                return response()->json([
                    'success' => false,
                    'errors' => $validation_result['errors']
                ], 422);
            }

            $validated_data = $validation_result['data'];
            $start_date = $validated_data['start_date'] ?? null;
            $end_date = $validated_data['end_date'] ?? null;
            $location_id = $validated_data['location_id'] ?? null;

            if ($start_date && $end_date) {
                $start_date = Carbon::parse($start_date)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($end_date)->endOfDay()->format('Y-m-d H:i:s');
            }

            $monthly_type_totals = $this->chartService->getMonthlyTransactionTypeTotals($business_id, $location_id, $start_date, $end_date);

            return response()->json([
                'success' => true,
                'data' => $monthly_type_totals
            ]);
        } catch (\Exception $e) {
            Log::error('Transaction type trend chart error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('treasury::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified transaction
     *
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');

        try {
            $transaction_details = $this->transactionService->getTransactionDetails($id, $business_id);

            if ($transaction_details['type'] === 'purchase') {
                return view($transaction_details['view'])->with($transaction_details['data']);
            } elseif ($transaction_details['type'] === 'expense') {
                return view($transaction_details['view'])->with($transaction_details['data']);
            } else {
                return view($transaction_details['view'])->with($transaction_details['data']);
            }
        } catch (\Exception $e) {
            Log::error('Transaction show error: ' . $e->getMessage());
            abort(500, 'Error loading transaction details.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('treasury::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified transaction from storage
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');

        try {
            $result = $this->transactionService->deleteTransaction($id, $business_id);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Transaction delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get all dashboard data in one consolidated request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllDashboardData()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $filters = [
                'start_date' => request()->get('start_date'),
                'end_date' => request()->get('end_date'),
                'location_id' => request()->get('location_id')
            ];

            // Validate date range
            $validation_result = $this->validationService->validateDashboardDateRange($filters);
            if (!$validation_result['success']) {
                return response()->json([
                    'success' => false,
                    'errors' => $validation_result['errors']
                ], 422);
            }

            $validated_data = $validation_result['data'];
            $start_date = $validated_data['start_date'] ?? null;
            $end_date = $validated_data['end_date'] ?? null;
            $location_id = $validated_data['location_id'] ?? null;

            if ($start_date && $end_date) {
                $start_date = Carbon::parse($start_date)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($end_date)->endOfDay()->format('Y-m-d H:i:s');
            }

            // Get all data in one go
            $sales_cards = $this->treasuryService->getSalesDashboardCards($business_id, $location_id, $start_date, $end_date);
            $filtered_totals = $this->treasuryService->getFilteredFinancialTotals($business_id, $location_id, $start_date, $end_date);
            $chart_data = $this->treasuryService->getDashboardChartData($business_id, $location_id, $start_date, $end_date);

            return response()->json([
                'success' => true,
                'data' => [
                    'sales_cards' => $sales_cards,
                    'filtered_totals' => $filtered_totals,
                    'chart_data' => $chart_data
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('All dashboard data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get unfiltered financial totals (not affected by date filters, only location)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnfilteredFinancialTotals()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $location_id = request()->get('location_id');

            $summary = $this->treasuryService->getUnfilteredFinancialTotals($business_id, $location_id);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            Log::error('Unfiltered financial totals error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get payment method balances
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentMethodBalances()
    {
        if (!auth()->user()->can('treasury.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $balances = $this->treasuryService->getPaymentMethodBalances($business_id);

            return response()->json([
                'success' => true,
                'data' => $balances
            ]);
        } catch (\Exception $e) {
            Log::error('Payment method balances error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get payment method balances for specific branch
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBranchPaymentMethodBalances()
    {
        if (!auth()->user()->can('treasury.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $location_id = request()->get('location_id');

            $balances = $this->treasuryService->getBranchPaymentMethodBalances($business_id, $location_id);

            return response()->json([
                'success' => true,
                'data' => $balances
            ]);
        } catch (\Exception $e) {
            Log::error('Branch payment method balances error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Submit internal transfer between payment methods
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitInternalTransfer(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            // Validate input
            $validated_data = $this->validationService->validateInternalTransferSubmission($request->all());

            // Submit transfer using service
            $result = $this->internalTransferService->submitInternalTransfer($validated_data, $business_id);

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Internal transfer submission error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Display internal transfers index page
     *
     * @return \Illuminate\Http\Response
     */
    public function internalTransfersIndex()
    {
        if (!auth()->user()->can('treasury.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $payment_methods = $this->transactionUtil->payment_types($business_id, true, true);

            return view('treasury::internal_transfers.index', compact('payment_methods'));
        } catch (\Exception $e) {
            Log::error('Internal transfers index error: ' . $e->getMessage());
            abort(500, 'Error loading internal transfers page.');
        }
    }

    /**
     * Get internal transfers data for DataTable
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInternalTransfersData(Request $request)
    {
        if (!auth()->user()->can('treasury.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            // Validate filters
            $filters = $request->only(['date_filter', 'payment_method_filter', 'amount_filter']);
            $validation_result = $this->validationService->validateInternalTransferFilters($filters);

            if (!$validation_result['success']) {
                return response()->json([
                    'success' => false,
                    'errors' => $validation_result['errors']
                ], 422);
            }

            $data = $this->internalTransferService->getInternalTransfersData($business_id, $validation_result['data']);

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => count($data),
                'recordsFiltered' => count($data),
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Internal transfers data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Show internal transfer details
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function showInternalTransfer($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $transfer_details = $this->internalTransferService->getTransferDetails($id, $business_id);

            return view('treasury::internal_transfers.show', $transfer_details);
        } catch (\Exception $e) {
            Log::error('Internal transfer show error: ' . $e->getMessage());
            abort(500, 'Error loading internal transfer: ' . $e->getMessage());
        }
    }

    /**
     * Show edit form for internal transfer
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function editInternalTransfer($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $transfer_data = $this->internalTransferService->getTransferForEdit($id, $business_id);

            return view('treasury::internal_transfers.edit', $transfer_data);
        } catch (\Exception $e) {
            Log::error('Internal transfer edit error: ' . $e->getMessage());
            abort(500, 'Error loading internal transfer edit form: ' . $e->getMessage());
        }
    }

    /**
     * Update internal transfer
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateInternalTransfer(Request $request, $id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            // Validate input
            $validated_data = $this->validationService->validateInternalTransferUpdate($request->all());

            // Update transfer using service
            $result = $this->internalTransferService->updateInternalTransfer($validated_data, $id, $business_id);

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Internal transfer update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Delete internal transfer
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyInternalTransfer($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $result = $this->internalTransferService->deleteInternalTransfer($id, $business_id);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Internal transfer delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Update payment status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePaymentStatus(Request $request)
    {
        if (!(Auth::user()->can('treasury.view') || Auth::user()->can('treasury.create'))) {
            return response()->json([
                'success' => false,
                'msg' => __('treasury::lang.unauthorized_action')
            ], 403);
        }

        try {
            $payment_id = $request->input('payment_id');
            $status = $request->input('status');
            $payment_method = $request->input('payment_method');
            $paid_on_input = $request->input('paid_on');
            $business_id = $request->session()->get('user.business_id');

            if (!$payment_id) {
                return response()->json([
                    'success' => false,
                    'msg' => __('treasury::lang.required_fields_missing')
                ], 422);
            }

            if (empty($status) && empty($payment_method)) {
                return response()->json([
                    'success' => false,
                    'msg' => __('treasury::lang.select_valid_option')
                ], 422);
            }

            $payment = TransactionPayment::find($payment_id);
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'msg' => __('treasury::lang.record_not_found')
                ], 404);
            }

            DB::beginTransaction();

            if (!empty($status)) {
                $payment->status = $status;

                // If status changed to paid and we have an advance payment with draft transaction
                if ($status === 'paid' && $payment->transaction_id && $payment->is_advance == 1) {
                    $transaction = Transaction::find($payment->transaction_id);
                    if ($transaction && $transaction->status === 'draft') {
                        // Mark the draft transaction as final (paid)
                        $transaction->status = 'final';
                        $transaction->payment_status = 'paid';
                        $transaction->save();
                    }
                }
            }

            if (!empty($payment_method)) {
                $payment_types = $this->commonUtil->payment_types(null, true, $business_id);
                if (!array_key_exists($payment_method, $payment_types)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'msg' => __('treasury::lang.select_valid_option')
                    ], 422);
                }

                $payment->method = $payment_method;

                // If this is paid advance payment, update transaction payment status but not balance
                // (balance was already updated when advance was created)
            }

            $parsedPaidOn = null;
            if (!empty($paid_on_input)) {
                try {
                    $parsedPaidOn = Carbon::createFromFormat('Y-m-d\\TH:i', $paid_on_input)->toDateTimeString();
                } catch (\Exception $e) {
                    try {
                        $parsedPaidOn = Carbon::parse($paid_on_input)->toDateTimeString();
                    } catch (\Exception $ex) {
                        $parsedPaidOn = now()->toDateTimeString();
                    }
                }
            }

            if ($status === 'paid') {
                $payment->paid_on = $parsedPaidOn ?? now()->toDateTimeString();
            } elseif (!empty($parsedPaidOn)) {
                $payment->paid_on = $parsedPaidOn;
            }

            $payment->save();

            // Update transaction payment status if needed
            if ($payment->transaction_id) {
                $payment_status = $this->transactionUtil->updatePaymentStatus($payment->transaction_id);
                $transaction = $payment->transaction;
                if ($transaction) {
                    $transaction->payment_status = $payment_status;
                    $transaction->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('treasury::lang.updated_successfully')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment status update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('treasury::lang.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Print treasury transaction
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function printTransaction($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            return $this->transactionService->printTransaction($id, $business_id);
        } catch (\Exception $e) {
            Log::error('Transaction print error: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];

            return redirect()->back()->with('status', $output);
        }
    }
}
