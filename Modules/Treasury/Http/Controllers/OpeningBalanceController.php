<?php

namespace Modules\Treasury\Http\Controllers;

use Modules\Treasury\Services\OpeningBalanceService;
use Modules\Treasury\Repositories\TreasuryRepository;
use App\Utils\Util;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Opening Balance Controller
 * 
 * Handles treasury opening balance transactions
 */
class OpeningBalanceController extends Controller
{
    protected OpeningBalanceService $openingBalanceService;
    protected TreasuryRepository $treasuryRepository;
    protected Util $commonUtil;

    public function __construct(
        OpeningBalanceService $openingBalanceService,
        TreasuryRepository $treasuryRepository,
        Util $commonUtil
    ) {
        $this->openingBalanceService = $openingBalanceService;
        $this->treasuryRepository = $treasuryRepository;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display opening balance management page
     *
     * @return Renderable
     */
    public function index(): Renderable
    {
        if (!Auth::user()->can('treasury.view') && !Auth::user()->can('treasury.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        try {
            return view('treasury::opening_balance.index', compact('business_id'));
        } catch (\Exception $e) {
            Log::error('Opening balance index error: ' . $e->getMessage());
            abort(500, 'Error loading opening balance page.');
        }
    }

    /**
     * Get data for opening balance modal (payment methods and current balances)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        if (!Auth::user()->can('treasury.view') && !Auth::user()->can('treasury.create')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->get('location_id');

            // Get payment methods; filter by selected location if provided so only enabled methods show
            if ($location_id) {
                $location = \App\BusinessLocation::where('business_id', $business_id)->find($location_id);
                $payment_methods = $this->commonUtil->payment_types($location, false);
            } else {
                $payment_methods = $this->commonUtil->payment_types(null, false, $business_id);
            }

            // Get current balances
            if ($location_id) {
                $current_balances = $this->treasuryRepository->getBranchPaymentMethodBalances(
                    $business_id,
                    $payment_methods,
                    $location_id
                );
            } else {
                $current_balances = $this->treasuryRepository->getPaymentMethodBalances(
                    $business_id,
                    $payment_methods
                );
            }

            return response()->json([
                'success' => true,
                'payment_methods' => $payment_methods,
                'current_balances' => $current_balances
            ]);

        } catch (\Exception $e) {
            Log::error('Opening balance data fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Store opening balance transaction
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('treasury.create')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            // Validate input
            $request->validate([
                'payment_method' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'transaction_date' => 'required',
                'notes' => 'nullable|string',
                'payment_ref_no' => 'nullable|string',
                'location_id' => 'nullable|integer'
            ]);

            $input = $request->only([
                'payment_method',
                'amount',
                'transaction_date',
                'notes',
                'payment_ref_no',
                'location_id'
            ]);

            $result = $this->openingBalanceService->createOpeningBalance($input, $business_id);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Opening balance store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get opening balance transactions for datatable
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactions(Request $request)
    {
        if (!Auth::user()->can('treasury.view') && !Auth::user()->can('treasury.create')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->get('location_id');

            $transactions = $this->openingBalanceService->getOpeningBalances($business_id, $location_id);

            $payment_types = $this->commonUtil->payment_types(null, false, $business_id);

            $data = array_map(function ($transaction) use ($payment_types) {
                $payment_method = '-';
                if (!empty($transaction['payment_lines'][0]['method'])) {
                    $method_key = $transaction['payment_lines'][0]['method'];
                    $payment_method = $payment_types[$method_key] ?? $method_key;
                }

                $location_name = $transaction['location']['name'] ?? '-';

                return [
                    'id' => $transaction['id'],
                    'invoice_no' => $transaction['invoice_no'] ?? $transaction['ref_no'],
                    'transaction_date' => $this->commonUtil->format_date($transaction['transaction_date'], true),
                    'location' => $location_name,
                    'payment_method' => $payment_method,
                    'amount' => '<span class="display_currency" data-orig-value="' . $transaction['final_total'] . '" data-currency_symbol="true">' . $transaction['final_total'] . '</span>',
                    'notes' => e($transaction['additional_notes'] ?? ''),
                    'actions' => ''
                ];
            }, $transactions);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Opening balance transactions fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Delete opening balance transaction
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        if (!Auth::user()->can('treasury.create')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $result = $this->openingBalanceService->deleteOpeningBalance($id, $business_id);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Opening balance delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment method name from transaction
     *
     * @param array $transaction
     * @return string
     */
    private function getPaymentMethodName(array $transaction): string
    {
        if (isset($transaction['payment_lines']) && count($transaction['payment_lines']) > 0) {
            $payment_types = $this->commonUtil->payment_types();
            $method = $transaction['payment_lines'][0]['method'];
            return $payment_types[$method] ?? $method;
        }
        return '-';
    }
}
