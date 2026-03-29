<?php

namespace Modules\Treasury\Services;

use Modules\Treasury\Repositories\TreasuryRepository;
use App\BusinessLocation;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Main Treasury Service
 * 
 * Central service for Treasury module operations
 * Coordinates between different specialized services
 */
class TreasuryService
{
    protected TreasuryRepository $repository;
    protected ModuleUtil $moduleUtil;
    protected TransactionUtil $transactionUtil;
    protected Util $commonUtil;
    protected TreasuryChartService $chartService;

    public function __construct(
        TreasuryRepository $repository,
        ModuleUtil $moduleUtil, 
        TransactionUtil $transactionUtil, 
        Util $commonUtil,
        TreasuryChartService $chartService
    ) {
        $this->repository = $repository;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->commonUtil = $commonUtil;
        $this->chartService = $chartService;
    }

  


    /**
     * Get payment method balances
     *
     * @param int $business_id
     * @return array
     */
    public function getPaymentMethodBalances(int $business_id): array
    {
        $payment_types = $this->commonUtil->payment_types(null, false, $business_id);
        return $this->repository->getPaymentMethodBalances($business_id, $payment_types);
    }

    /**
     * Get business locations for dropdown
     *
     * @param int $business_id
     * @return array
     */
    public function getBusinessLocations(int $business_id): array
    {
        return BusinessLocation::forDropdown($business_id, true, false, true, true)->toArray();
    }

    /**
     * Get chart data for dashboard
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return array
     */
    public function getDashboardChartData(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): array
    {
        return $this->chartService->getDashboardChartData($business_id, $location_id, $start_date, $end_date);
    }
        /**
     * Get payment method balances for specific branch
     *
     * @param int $business_id
     * @param int|null $location_id
     * @return array
     */
    public function getBranchPaymentMethodBalances(int $business_id, ?int $location_id = null): array
    {
        $payment_types = $this->commonUtil->payment_types($location_id, false, $business_id);
        return $this->repository->getBranchPaymentMethodBalances($business_id, $payment_types, $location_id);
    }


     /**
     * Get sales dashboard cards data with location filtering
     * Optimized to use repository method that fetches all data efficiently
     *
     * @param int $business_id
     * @param int|null $location_id
     * @param string|null $start_date
     * @param string|null $end_date
     * @return array
     */
    public function getSalesDashboardCards(int $business_id, ?int $location_id = null, ?string $start_date = null, ?string $end_date = null): array
    {
        // Set default date range if not provided (today)
        if (!$start_date) {
            $start_date = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
        }
        if (!$end_date) {
            $end_date = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
        }

        // Get all dashboard data in one optimized call to repository
        // This replaces multiple calls to transactionUtil and repository methods
        return $this->repository->getSalesDashboardData($business_id, $location_id, $start_date, $end_date);
    }

    /**
     * Get unfiltered financial totals (not affected by date filters)
     *
     * @param int $business_id
     * @param int|null $location_id
     * @return array
     */
    public function getUnfilteredFinancialTotals(int $business_id, ?int $location_id = null): array
    {
        // Get all-time totals using the same repository method without date filters
        // This ensures consistent logic between filtered and unfiltered data
        $allTimeData = $this->repository->getSalesDashboardData($business_id, $location_id);
        
        // Get cash in hand (payment method balances)
        $cashe_in_hand = $this->repository->calculateCashInHandTotal($business_id, $location_id);
        
        // Calculate totals for reference (all-time, including dues)
        $total_income = $allTimeData['total_sell'] - $allTimeData['total_sell_return'];
        $total_expense = $allTimeData['total_expense'];
        $total_purchase = $allTimeData['total_purchase'] - $allTimeData['total_purchase_return'];
        
        // Real balance based on paid amounts (income - outcome)
        // selling_paid, purchase_paid and expense_paid are computed in TreasuryRepository
        // Include paid part of under-processing sells in income side to match payment balances
        $paid_income = ($allTimeData['selling_paid'] ?? 0.0)
            + ($allTimeData['under_processing_sell'] ?? 0.0);
        $paid_purchase = $allTimeData['purchase_paid'] ?? 0.0;
        $paid_expense = $allTimeData['expense_paid'] ?? 0.0;

        $real_balance = $paid_income - $paid_purchase - $paid_expense;
        
        return [
            'total_income' => $total_income,
            'total_expense' => $total_expense,
            'total_purchase' => $total_purchase,
            'real_balance' => $real_balance,
            'cashe_in_hand' => $cashe_in_hand,
        ];
    }
}