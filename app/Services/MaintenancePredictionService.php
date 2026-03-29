<?php

namespace App\Services;

use App\ServicePrediction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaintenancePredictionService
{
    const DEFAULT_INTERVAL_MONTHS = 6;
    const MAX_INTERVAL_MONTHS = 18;

    /**
     * Recalculate all predictions for a given business.
     */
    public function recalculateForBusiness(int $businessId): int
    {
        $count = 0;

        // Get all unique (contact_id, device_id, product_id) combos
        $serviceCombos = $this->getServiceCombinations($businessId);

        foreach ($serviceCombos as $combo) {
            try {
                $prediction = $this->calculatePrediction(
                    $businessId,
                    $combo->contact_id,
                    $combo->device_id,
                    $combo->product_id
                );
                if ($prediction) {
                    $count++;
                }
            } catch (\Exception $e) {
                Log::error('Prediction calculation failed', [
                    'business_id' => $businessId,
                    'contact_id' => $combo->contact_id,
                    'device_id' => $combo->device_id,
                    'product_id' => $combo->product_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // After per-product predictions, build predicted_services_json per (contact, device)
        $this->buildPredictedServicesJson($businessId);

        return $count;
    }

    /**
     * Recalculate predictions for all businesses.
     */
    public function recalculateAll(): int
    {
        $businessIds = DB::table('business')->pluck('id');
        $total = 0;

        foreach ($businessIds as $businessId) {
            $total += $this->recalculateForBusiness($businessId);
        }

        return $total;
    }

    /**
     * Get all unique (contact_id, device_id, product_id) combos from completed service transactions.
     */
    protected function getServiceCombinations(int $businessId)
    {
        // Path 1: via job sheet → booking → device
        $viaJobSheet = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->join('repair_job_sheets as rjs', 'rjs.id', '=', 't.repair_job_sheet_id')
            ->join('bookings as b', 'b.id', '=', 'rjs.booking_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.enable_stock', 0)
            ->whereNotNull('b.device_id')
            ->select(
                't.contact_id',
                'b.device_id',
                'tsl.product_id'
            )
            ->groupBy('t.contact_id', 'b.device_id', 'tsl.product_id');

        // Path 2: direct device link
        $viaDirect = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.enable_stock', 0)
            ->whereNotNull('t.repair_device_id')
            ->whereNull('t.repair_job_sheet_id')
            ->select(
                't.contact_id',
                't.repair_device_id as device_id',
                'tsl.product_id'
            )
            ->groupBy('t.contact_id', 't.repair_device_id', 'tsl.product_id');

        return $viaJobSheet->union($viaDirect)->get();
    }

    /**
     * Calculate and upsert a single per-product prediction.
     */
    public function calculatePrediction(
        int $businessId,
        int $contactId,
        ?int $deviceId,
        int $productId
    ): ?ServicePrediction {
        if (empty($deviceId)) {
            return null;
        }

        // Get product info including prediction rules
        $product = DB::table('products')
            ->where('id', $productId)
            ->select('id', 'name', 'category_id', 'product_custom_field3', 'product_custom_field4')
            ->first();

        if (!$product) {
            return null;
        }

        $ruleKmInterval = !empty($product->product_custom_field3) ? (int) $product->product_custom_field3 : null;
        $ruleTimeInterval = !empty($product->product_custom_field4) ? (int) $product->product_custom_field4 : null;

        // Get service history for this specific product
        $serviceRecords = $this->getProductServiceHistory($businessId, $contactId, $deviceId, $productId);

        if ($serviceRecords->isEmpty()) {
            return null;
        }

        $totalCount = $serviceRecords->count();
        $dates = $serviceRecords->pluck('service_date')->map(fn($d) => Carbon::parse($d))->values();
        $kmReadings = $serviceRecords->pluck('km')->filter()->values();
        $quantities = $serviceRecords->pluck('quantity')->filter()->values();

        // Calculate monthly intervals
        $monthlyIntervals = [];
        for ($i = 1; $i < $dates->count(); $i++) {
            $diff = $dates[$i - 1]->diffInMonths($dates[$i]);
            if ($diff > 0 && $diff <= self::MAX_INTERVAL_MONTHS) {
                $monthlyIntervals[] = $diff;
            }
        }

        // Calculate KM intervals
        $kmIntervals = [];
        for ($i = 1; $i < $kmReadings->count(); $i++) {
            $diff = abs($kmReadings[$i] - $kmReadings[$i - 1]);
            if ($diff > 0) {
                $kmIntervals[] = $diff;
            }
        }

        // Adaptive window
        $windowSize = $this->getWindowSize(count($monthlyIntervals));
        $windowedIntervals = array_slice($monthlyIntervals, -$windowSize);
        $windowedKmIntervals = array_slice($kmIntervals, -$windowSize);

        // Calculate averages from history
        $historyAvgMonths = !empty($windowedIntervals)
            ? (int) round(array_sum($windowedIntervals) / count($windowedIntervals))
            : null;

        $historyAvgKm = !empty($windowedKmIntervals)
            ? (int) round(array_sum($windowedKmIntervals) / count($windowedKmIntervals))
            : null;

        // Determine prediction source and final interval
        $predictionSource = 'history';
        $avgMonths = $historyAvgMonths ?? self::DEFAULT_INTERVAL_MONTHS;
        $avgKm = $historyAvgKm;

        if ($ruleTimeInterval && $historyAvgMonths) {
            // Hybrid: weighted average (60% history, 40% rule)
            $avgMonths = (int) round(($historyAvgMonths * 0.6) + ($ruleTimeInterval * 0.4));
            $predictionSource = 'hybrid';
        } elseif ($ruleTimeInterval && !$historyAvgMonths) {
            $avgMonths = $ruleTimeInterval;
            $predictionSource = 'rule';
        }

        if ($ruleKmInterval && $historyAvgKm) {
            $avgKm = (int) round(($historyAvgKm * 0.6) + ($ruleKmInterval * 0.4));
            if ($predictionSource === 'history') {
                $predictionSource = 'hybrid';
            }
        } elseif ($ruleKmInterval && !$historyAvgKm) {
            $avgKm = $ruleKmInterval;
            if ($predictionSource === 'history') {
                $predictionSource = 'rule';
            }
        }

        if ($avgMonths < 1) {
            $avgMonths = 1;
        }

        // Average quantity consumed per visit
        $avgQuantity = $quantities->isNotEmpty()
            ? round($quantities->avg(), 2)
            : null;

        // Predicted quantity for next visit (use recent window)
        $recentQuantities = $quantities->slice(-$windowSize)->values();
        $predictedQuantity = $recentQuantities->isNotEmpty()
            ? round($recentQuantities->avg(), 2)
            : $avgQuantity;

        // Last service info
        $lastDate = $dates->last();
        $lastKm = $kmReadings->isNotEmpty() ? $kmReadings->last() : null;

        // Next expected date: use whichever comes first (km-based or time-based)
        $nextExpectedDate = $lastDate->copy()->addMonths($avgMonths);
        $nextExpectedKm = ($lastKm && $avgKm) ? $lastKm + $avgKm : null;

        // Status classification
        $today = Carbon::today();
        $status = 'on_time';
        $overdueMonths = 0;

        if ($today->gt($nextExpectedDate->copy()->addMonth())) {
            $status = 'overdue';
            $overdueMonths = (int) $today->diffInMonths($nextExpectedDate);
        } elseif ($today->format('Y-m') === $nextExpectedDate->format('Y-m')) {
            $status = 'due';
        } elseif ($today->gt($nextExpectedDate)) {
            $status = 'overdue';
            $overdueMonths = (int) $today->diffInMonths($nextExpectedDate);
            if ($overdueMonths < 1) {
                $overdueMonths = 1;
            }
        }

        // Behavior trend
        $behaviorTrend = $this->calculateBehaviorTrend($monthlyIntervals);

        // Confidence score (0-100)
        $confidenceScore = $this->calculateConfidence($totalCount, $monthlyIntervals, $predictionSource, $ruleTimeInterval, $ruleKmInterval);

        // Upsert prediction keyed by (business, contact, device, product)
        $prediction = ServicePrediction::updateOrCreate(
            [
                'business_id' => $businessId,
                'contact_id' => $contactId,
                'device_id' => $deviceId,
                'service_product_id' => $productId,
            ],
            [
                'service_category_id' => $product->category_id,
                'total_services_count' => $totalCount,
                'avg_interval_months' => $avgMonths,
                'window_size_used' => count($windowedIntervals) ?: 1,
                'last_service_date' => $lastDate->toDateString(),
                'last_km' => $lastKm,
                'avg_km_interval' => $avgKm,
                'next_expected_date' => $nextExpectedDate->toDateString(),
                'next_expected_km' => $nextExpectedKm,
                'predicted_quantity' => $predictedQuantity,
                'avg_quantity' => $avgQuantity,
                'status' => $status,
                'overdue_months' => $overdueMonths,
                'behavior_trend' => $behaviorTrend,
                'confidence_score' => $confidenceScore,
                'rule_km_interval' => $ruleKmInterval,
                'rule_time_interval' => $ruleTimeInterval,
                'prediction_source' => $predictionSource,
            ]
        );

        return $prediction;
    }

    /**
     * Build predicted_services_json for each (contact, device) group.
     * This aggregates all per-product predictions into a summary of what services
     * are expected in the next 3 months.
     */
    protected function buildPredictedServicesJson(int $businessId): void
    {
        $groups = ServicePrediction::where('business_id', $businessId)
            ->select('contact_id', 'device_id')
            ->groupBy('contact_id', 'device_id')
            ->get();

        $threeMonthsFromNow = Carbon::today()->addMonths(3);

        foreach ($groups as $group) {
            $predictions = ServicePrediction::where('business_id', $businessId)
                ->where('contact_id', $group->contact_id)
                ->where('device_id', $group->device_id)
                ->whereNotNull('service_product_id')
                ->get();

            $predictedServices = [];
            foreach ($predictions as $pred) {
                if ($pred->next_expected_date && $pred->next_expected_date->lte($threeMonthsFromNow)) {
                    $productName = DB::table('products')->where('id', $pred->service_product_id)->value('name');
                    $predictedServices[] = [
                        'product_id' => $pred->service_product_id,
                        'product_name' => $productName,
                        'expected_date' => $pred->next_expected_date->format('Y-m'),
                        'predicted_qty' => $pred->predicted_quantity,
                        'status' => $pred->status,
                        'confidence' => $pred->confidence_score,
                    ];
                }
            }

            // Update all predictions in this group with the aggregated JSON
            if (!empty($predictedServices)) {
                $json = json_encode($predictedServices, JSON_UNESCAPED_UNICODE);
                ServicePrediction::where('business_id', $businessId)
                    ->where('contact_id', $group->contact_id)
                    ->where('device_id', $group->device_id)
                    ->update(['predicted_services_json' => $json]);
            }
        }
    }

    /**
     * Get service history for a specific product on a specific car.
     */
    protected function getProductServiceHistory(int $businessId, int $contactId, int $deviceId, int $productId)
    {
        // Path 1: via job sheet → booking → device
        $viaJobSheet = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->join('repair_job_sheets as rjs', 'rjs.id', '=', 't.repair_job_sheet_id')
            ->join('bookings as b', 'b.id', '=', 'rjs.booking_id')
            ->where('t.business_id', $businessId)
            ->where('t.contact_id', $contactId)
            ->where('b.device_id', $deviceId)
            ->where('tsl.product_id', $productId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.enable_stock', 0)
            ->select(
                DB::raw('DATE(t.transaction_date) as service_date'),
                DB::raw('COALESCE(rjs.km, t.repair_device_km) as km'),
                'tsl.quantity'
            );

        // Path 2: direct device link
        $viaDirect = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->where('t.business_id', $businessId)
            ->where('t.contact_id', $contactId)
            ->where('t.repair_device_id', $deviceId)
            ->where('tsl.product_id', $productId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('p.enable_stock', 0)
            ->whereNull('t.repair_job_sheet_id')
            ->select(
                DB::raw('DATE(t.transaction_date) as service_date'),
                't.repair_device_km as km',
                'tsl.quantity'
            );

        return $viaJobSheet->union($viaDirect)
            ->orderBy('service_date', 'asc')
            ->get();
    }

    /**
     * Calculate confidence score (0-100) based on data quality.
     */
    protected function calculateConfidence(int $totalCount, array $intervals, string $source, ?int $ruleTime, ?int $ruleKm): int
    {
        $score = 20; // base

        // More history = more confidence
        if ($totalCount >= 5) {
            $score += 30;
        } elseif ($totalCount >= 3) {
            $score += 20;
        } elseif ($totalCount >= 2) {
            $score += 10;
        }

        // Consistent intervals = more confidence
        if (count($intervals) >= 2) {
            $avg = array_sum($intervals) / count($intervals);
            $variance = 0;
            foreach ($intervals as $v) {
                $variance += pow($v - $avg, 2);
            }
            $variance /= count($intervals);
            $stdDev = sqrt($variance);
            $cv = $avg > 0 ? ($stdDev / $avg) : 1;

            if ($cv < 0.2) {
                $score += 25; // very consistent
            } elseif ($cv < 0.4) {
                $score += 15;
            } elseif ($cv < 0.6) {
                $score += 5;
            }
        }

        // Rules defined = more confidence
        if ($ruleTime) {
            $score += 10;
        }
        if ($ruleKm) {
            $score += 10;
        }

        // Hybrid source bonus
        if ($source === 'hybrid') {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Determine adaptive window size based on number of intervals.
     */
    protected function getWindowSize(int $intervalCount): int
    {
        if ($intervalCount <= 1) {
            return 1;
        } elseif ($intervalCount <= 5) {
            return min(3, $intervalCount);
        } elseif ($intervalCount <= 10) {
            return min(5, $intervalCount);
        } else {
            return 6;
        }
    }

    /**
     * Calculate behavior trend by comparing recent vs overall average.
     */
    protected function calculateBehaviorTrend(array $intervals): string
    {
        if (count($intervals) < 3) {
            return 'stable';
        }

        $overallAvg = array_sum($intervals) / count($intervals);
        $recentIntervals = array_slice($intervals, -3);
        $recentAvg = array_sum($recentIntervals) / count($recentIntervals);

        $diff = $recentAvg - $overallAvg;
        $threshold = $overallAvg * 0.2;

        if ($diff > $threshold) {
            return 'increasing';
        } elseif ($diff < -$threshold) {
            return 'decreasing';
        }

        return 'stable';
    }
}
