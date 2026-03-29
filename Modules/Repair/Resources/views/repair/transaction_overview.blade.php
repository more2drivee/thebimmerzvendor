@extends('layouts.app')
@section('title', __('repair::lang.repair'))

@section('content')
@include('treasury::layouts.nav')

<style>
/* Responsive Transaction Overview Styles */
.transaction-overview {
    padding: 20px;
    font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
    max-width: 100%;
    overflow-x: hidden;
}

.overview-title {
    margin-bottom: 20px;
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.metric-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
}

.metric-header {
    font-size: 13px;
    color: #7a8aa3;
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.metric-value {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.metric-percentage {
    font-size: 11px;
    color: #9aa0a6;
}

.charts-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.chart-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
}

.chart-title {
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    font-size: 1.1rem;
    font-weight: 600;
}

.chart-container {
    height: 300px;
    position: relative;
}

.table-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    margin-bottom: 20px;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.data-table th,
.data-table td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid #f1f3f5;
    white-space: nowrap;
}

.data-table th {
    background: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #e9ecef;
}

.insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.insight-item {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.insight-label {
    color: #6c757d;
    margin-right: 8px;
}

.insight-value {
    font-weight: 600;
    margin-left: 8px;
}

.info-note {
    margin-top: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 12px;
    color: #6c757d;
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .transaction-overview {
        padding: 15px;
    }
    
    .overview-title {
        font-size: 1.2rem;
        margin-bottom: 15px;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .metric-card {
        padding: 15px;
    }
    
    .metric-value {
        font-size: 1.2rem;
    }
    
    .charts-container {
        gap: 15px;
    }
    
    .chart-card {
        padding: 15px;
    }
    
    .chart-container {
        height: 250px;
    }
    
    .table-container {
        padding: 15px;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px 6px;
        font-size: 14px;
    }
    
    .insights-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .insight-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .insight-value {
        margin-left: 0;
        margin-top: 4px;
    }
}

/* Tablet Responsive Styles */
@media (min-width: 769px) and (max-width: 1024px) {
    .metrics-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .charts-container {
        grid-template-columns: 1fr 1fr;
    }
}

/* Desktop Styles */
@media (min-width: 1025px) {
    .metrics-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .charts-container {
        grid-template-columns: 1fr 1fr 1fr;
    }
}

/* Badge Styles */
.badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    color: white;
}

.badge-labor { background: #34a853; }
.badge-parts { background: #4285f4; }
.badge-expense { background: #ea4335; }
.badge-invoice { background: #4285f4; }
.badge-salary { background: #34a853; }
.badge-hours { background: #e3f2fd; color: #1976d2; }

/* Color Classes */
.text-success { color: #28a745; }
.text-danger { color: #ea4335; }
.text-warning { color: #ffc107; }
.text-primary { color: #4285f4; }
.text-muted { color: #6c757d; }

/* Icon spacing */
.fas { margin-right: 8px; }
</style>

<div class="transaction-overview">
    <h3 class="overview-title">{{ __('repair::lang.transaction_overview') }} - #{{ $transaction->invoice_no ?? $transaction->id }}
        @if(!empty($sell_return))
            <small class="label bg-red" style="font-size:12px;"><i class="fas fa-undo"></i> {{ __('repair::lang.has_sell_return') }}</small>
        @endif
    </h3>
     <!-- Metrics Cards -->
    <div class="table-container tw-mb-4">
        <h5 class="tw-text-base tw-font-semibold tw-text-gray-700 tw-mb-3">{{ __('repair::lang.contact_summary_title') }}</h5>
        <div class="insights-grid">
            <div>
                <div class="insight-item">
                    <i class="fas fa-user text-primary"></i>
                    <span class="insight-label">{{ __('repair::lang.contact_name') }}:</span>
                    <strong class="insight-value">{{ $contact_name }}</strong>
                </div>
                <div class="insight-item">
                    <i class="fas fa-mobile-alt text-success"></i>
                    <span class="insight-label">{{ __('repair::lang.vehicle_label_compact') }}:</span>
                    <strong class="insight-value">
                        {{ $device_info->device_name ?? '-' }} {{ !empty($device_info->device_model) ? '(' . $device_info->device_model . ')' : '' }}
                    </strong>
                </div>
                <div class="insight-item">
                    <i class="fas fa-id-card text-primary"></i>
                    <span class="insight-label">{{ __('repair::lang.plate_short') }}:</span>
                    <strong class="insight-value">{{ $device_info->plate_number ?? '-' }}</strong>
                </div>
                <div class="insight-item">
                    <i class="fas fa-barcode text-primary"></i>
                    <span class="insight-label">{{ __('repair::lang.vin_short') }}:</span>
                    <strong class="insight-value">{{ $device_info->vin_number ?? '-' }}</strong>
                </div>
            </div>
            <div>
                <h6 class="tw-text-sm tw-font-semibold tw-text-gray-600 tw-mb-2">{{ __('repair::lang.payment_summary_title') }}</h6>
                <div class="insight-item">
                    <i class="fas fa-info-circle"></i>
                    <span class="insight-label">{{ __('sale.payment_status') }}:</span>
                    @php
                        $ps = strtolower($payment_status ?? 'due');
                        $ps_color = match($ps){
                            'paid' => '#34a853',
                            'partial' => '#fbbc05',
                            'due' => '#ea4335',
                            default => '#6c757d'
                        };
                    @endphp
                    <span class="badge" style="background: {{ $ps_color }};">{{ __('lang_v1.' . $ps) }}</span>
                </div>
                <div class="insight-item">
                    <span class="insight-label">{{ __('sale.total_amount') }}:</span>
                    <strong class="insight-value">EGP {{ number_format($transaction->final_total ?? 0, 2) }}</strong>
                </div>
                @if(!empty($sell_return))
                <div class="insight-item">
                    <span class="insight-label"><i class="fas fa-undo text-danger"></i> {{ __('repair::lang.sell_return') }}:</span>
                    <strong class="insight-value text-danger">- EGP {{ number_format($sell_return_amount, 2) }}</strong>
                </div>
                <div class="insight-item">
                    <span class="insight-label">{{ __('repair::lang.net_after_return') }}:</span>
                    <strong class="insight-value">EGP {{ number_format(($transaction->final_total ?? 0) - $sell_return_amount, 2) }}</strong>
                </div>
                @endif
                <div class="insight-item">
                    <span class="insight-label">{{ __('purchase.total_paid') }}:</span>
                    <strong class="insight-value text-success">EGP {{ number_format($total_paid, 2) }}</strong>
                </div>
                <div class="insight-item">
                    <span class="insight-label">{{ __('repair::lang.remaining_amount_label') }}:</span>
                    <strong class="insight-value text-danger">EGP {{ number_format($remaining_amount, 2) }}</strong>
                </div>
                <a href="{{ action([\App\Http\Controllers\TransactionPaymentController::class, 'addPayment'], [$transaction->id]) }}"
                   class="btn btn-primary btn-sm add_payment_modal"
                   data-container="#payment_modal">
                    <i class="fas fa-money-bill-alt"></i> {{ __('purchase.add_payment') }}
                </a>
            </div>
        </div>
    </div>


    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-receipt text-primary"></i>
                {{ __('repair::lang.invoice_total') }}
            </div>
            <div class="metric-value">EGP {{ number_format($invoice_total, 2) }}</div>
            <div class="metric-percentage">100.0% {{ __('repair::lang.of_total') }}</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-percentage text-danger"></i>
                {{ __('repair::lang.discount_amount') }}
            </div>
            <div class="metric-value">EGP {{ number_format($discount_amount, 2) }}</div>
            <div class="metric-percentage">{{ $invoice_total_before_discount > 0 ? number_format(($discount_amount / $invoice_total_before_discount) * 100, 1) : 0 }}% {{ __('repair::lang.of_total') }}</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-cogs text-primary"></i>
                {{ __('repair::lang.spare_parts_end_user') }}
            </div>
            <div class="metric-value">EGP {{ number_format($spare_parts_total_before_discount, 2) }}</div>
            <div class="metric-percentage">{{ $invoice_total_before_discount > 0 ? number_format(($spare_parts_total_before_discount / $invoice_total_before_discount) * 100, 1) : 0 }}% {{ __('repair::lang.of_total') }}</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-shopping-cart text-danger"></i>
                {{ __('repair::lang.purchasing_cost') }}
            </div>
            <div class="metric-value">EGP {{ number_format($purchasing_cost, 2) }}</div>
            <div class="metric-percentage">{{ $invoice_total > 0 ? number_format(($purchasing_cost / $invoice_total) * 100, 1) : 0 }}% {{ __('repair::lang.of_total') }}</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-wrench text-success"></i>
                {{ __('repair::lang.labour_income') }}
            </div>
            <div class="metric-value">EGP {{ number_format($labor_income, 2) }}</div>
            <div class="metric-percentage">{{ $invoice_total > 0 ? number_format(($labor_income / $invoice_total) * 100, 1) : 0 }}% {{ __('repair::lang.of_total') }}</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-clock text-danger"></i>
                {{ __('repair::lang.labour_cost') }}
            </div>
            <div class="metric-value">EGP {{ number_format($labor_cost, 2) }}</div>
            <div class="metric-percentage">{{ $invoice_total > 0 ? number_format(($labor_cost / $invoice_total) * 100, 1) : 0 }}% {{ __('repair::lang.of_total') }}</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-dollar-sign text-warning"></i>
                {{ __('repair::lang.total_expenses') }}
            </div>
            <div class="metric-value">EGP {{ number_format($expenses_total, 2) }}</div>
            <div class="metric-percentage">{{ $invoice_total > 0 ? number_format(($expenses_total / $invoice_total) * 100, 1) : 0 }}% {{ __('repair::lang.of_total') }}</div>
        </div>

        @if($sell_return_amount > 0)
        <div class="metric-card" style="border-left: 3px solid #ea4335;">
            <div class="metric-header">
                <i class="fas fa-undo text-danger"></i>
                {{ __('repair::lang.sell_return') }}
            </div>
            <div class="metric-value text-danger">EGP {{ number_format($sell_return_amount, 2) }}</div>
            <div class="metric-percentage">{{ $invoice_total > 0 ? number_format(($sell_return_amount / $invoice_total) * 100, 1) : 0 }}% {{ __('repair::lang.of_total') }}</div>
        </div>
        @endif

        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-chart-line text-success"></i>
                {{ __('repair::lang.net_profit') }}
            </div>
            <div class="metric-value {{ $net_profit >= 0 ? 'text-success' : 'text-danger' }}">EGP {{ number_format($net_profit, 2) }}</div>
            <div class="metric-percentage">{{ number_format($overall_profit_margin, 1) }}% {{ __('repair::lang.of_total') }}</div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-container">
        <div class="chart-card">
            <h4 class="chart-title">
                <i class="fas fa-chart-bar text-primary"></i>
                {{ __('repair::lang.invoice_components_comparison') }}
            </h4>
            <div class="chart-container">
                <canvas id="componentsChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h4 class="chart-title">
                <i class="fas fa-chart-pie text-success"></i>
                {{ __('repair::lang.income_breakdown') }}
            </h4>
            <div class="chart-container">
                <canvas id="incomeChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h4 class="chart-title">
                <i class="fas fa-chart-pie text-danger"></i>
                {{ __('repair::lang.expense_breakdown') }}
            </h4>
            <div class="chart-container">
                <canvas id="expenseChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Breakdown Table -->
    <div class="table-container">
        <h4 class="chart-title">
            <i class="fas fa-table text-warning"></i>
            {{ __('repair::lang.detailed_breakdown') }}
        </h4>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('repair::lang.category') }}</th>
                        <th>{{ __('repair::lang.item') }}</th>
                        <th>{{ __('repair::lang.qty') }}</th>
                        <th>{{ __('repair::lang.purchase') }}</th>
                        <th>{{ __('repair::lang.selling') }}</th>
                        <th>{{ __('repair::lang.profit') }}</th>
                        <th>{{ __('repair::lang.discount') }}</th>
                        <th>{{ __('repair::lang.technician') }}</th>
                        <th>{{ __('repair::lang.hours') }}</th>
                        <th>{{ __('repair::lang.rate') }}</th>
                        <th>{{ __('repair::lang.net_result') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaction->sell_lines as $line)
                        @php
                            $quantity = $line->quantity ?? 1;

                            // Prefer job order prices (joborder_selling_price / joborder_purchase_price) when available
                            $joborderLine = isset($joborderLines) ? $joborderLines->get($line->product_id ?? null) : null;

                            $unit_price = $joborderLine && $joborderLine->price !== null
                                ? (float) $joborderLine->price
                                : (float) ($line->unit_price ?? 0);

                            $line_discount = $line->fixed_line_discount ?? ($line->line_discount_amount ?? 0);

                            // Get purchase price, preferring joborder_purchase_price when available
                            $total_purchase_cost = 0;
                            $unit_purchase_price = 0;
                            if($joborderLine && $joborderLine->purchase_price !== null) {
                                $unit_purchase_price = (float) $joborderLine->purchase_price;
                                $total_purchase_cost = $unit_purchase_price * $quantity;
                            } elseif($line->sell_line_purchase_lines && $line->sell_line_purchase_lines->count() > 0) {
                                foreach($line->sell_line_purchase_lines as $purchase_mapping) {
                                    if($purchase_mapping->purchase_line) {
                                        $total_purchase_cost += ($purchase_mapping->purchase_line->purchase_price ?? 0) * $purchase_mapping->quantity;
                                    }
                                }
                                // Calculate unit purchase price
                                $unit_purchase_price = $quantity > 0 ? $total_purchase_cost / $quantity : 0;
                            }

                            $line_total = $unit_price * $quantity;
                            $profit = $line_total - $total_purchase_cost - $line_discount;
                            $net_result = $line_total - $line_discount;

                            // Determine category and technician info
                            $category = ($line->product && $line->product->enable_stock == 0) ? __('repair::lang.labour') : __('repair::lang.spare_parts');
                            
                            // Get actual hours and rate from technician details if available
                            $hours = 0;
                            $rate = 0;
                            $line_technician = $technician;
                            
                            if($category == __('repair::lang.labour') && !empty($technician_details)) {
                                // For labor items, use actual timer tracking data
                                $total_tech_hours = collect($technician_details)->sum('hours');
                                $hours = $total_tech_hours > 0 ? $total_tech_hours : (($line->product && $line->product->serviceHours) ? $line->product->serviceHours * $quantity : 0);
                                
                                // Calculate rate based on actual hourly rates from technician details
                                $total_cost = collect($technician_details)->sum('total_cost');
                                $rate = $hours > 0 ? $total_cost / $hours : 0;
                                $line_technician = collect($technician_details)->pluck('name')->implode(', ');
                            } else if($line->product && $line->product->serviceHours) {
                                // Fallback to service hours for labor items without timer data
                                $hours = $line->product->serviceHours * $quantity;
                                $rate = $hours > 0 ? $unit_price / $hours : 0;
                            }
                        @endphp
                        <tr>
                            <td>
                                <span class="badge {{ $category == __('repair::lang.labour') ? 'badge-labor' : 'badge-parts' }}">
                                    {{ $category }}
                                </span>
                            </td>
                            <td><strong>{{ $line->product->name ?? $line->product_name ?? '-' }}</strong></td>
                            <td style="text-align:center;">{{ $quantity }}</td>
                            <td><strong>EGP {{ number_format($unit_purchase_price, 2) }}</strong></td>
                            <td><strong>EGP {{ number_format($unit_price, 2) }}</strong></td>
                            <td class="{{ $profit < 0 ? 'text-danger' : 'text-success' }}"><strong>EGP {{ number_format($profit, 2) }}</strong></td>
                            <td class="text-danger"><strong>EGP {{ number_format($line_discount, 2) }}</strong></td>
                            <td>{{ $line_technician }}</td>
                            <td style="text-align:center;">{{ $hours > 0 ? number_format($hours, 2) : '-' }}</td>
                            <td>{{ $rate > 0 ? 'EGP ' . number_format($rate, 2) : '-' }}</td>
                            <td class="text-success"><strong>EGP {{ number_format($net_result, 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Related Expenses Table -->
    @if($expenses->count() > 0)
    <div class="table-container">
        <h4 class="chart-title">
            <i class="fas fa-receipt text-danger"></i>
            {{ __('repair::lang.related_expenses') }} ({{ __('repair::lang.linked_via_invoice_ref') }})
        </h4>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('repair::lang.contact') }}</th>
                        <th>{{ __('repair::lang.expense_ref') }}</th>
                        <th>{{ __('repair::lang.date') }}</th>
                        <th>{{ __('repair::lang.category') }}</th>
                        <th>{{ __('repair::lang.notes') }}</th>
                        <th>{{ __('sale.payment_status') }}</th>
                        <th>{{ __('purchase.total_paid') }}</th>
                        <th>{{ __('repair::lang.amount') }}</th>
                        <th>{{ __('purchase.add_payment') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_contact_name ?? '-' }}</td>
                            <td>
                                <a href="#"
                                   class="btn-modal"
                                   data-href="{{ action([\Modules\Treasury\Http\Controllers\TreasuryController::class, 'show'], [$expense->id]) }}"
                                   data-container=".view_modal">
                                    <strong>{{ $expense->ref_no }}</strong>
                                </a>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($expense->transaction_date)->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge badge-expense">
                                    {{ $expense->category_name ?? __('repair::lang.general') }}
                                </span>
                            </td>
                            <td>{{ $expense->additional_notes ?? '-' }}</td>
                            <td>
                                @php
                                    $exp_status = strtolower($expense->payment_status ?? 'due');
                                    $exp_status_color = match($exp_status) {
                                        'paid' => '#34a853',
                                        'partial' => '#fbbc05',
                                        'due' => '#ea4335',
                                        default => '#6c757d'
                                    };
                                @endphp
                                <span class="badge" style="background: {{ $exp_status_color }};">{{ __('lang_v1.' . $exp_status) }}</span>
                            </td>
                            <td class="text-success"><strong>EGP {{ number_format($expense->total_paid ?? 0, 2) }}</strong></td>
                            <td class="text-danger"><strong>EGP {{ number_format($expense->final_total, 2) }}</strong></td>
                            <td>
                                <a href="{{ action([\App\Http\Controllers\TransactionPaymentController::class, 'addPayment'], [$expense->id]) }}"
                                   class="btn btn-xs btn-primary add_payment_modal"
                                   data-container="#payment_modal">
                                   <i class="fas fa-money-bill-alt"></i> {{ __('purchase.add_payment') }}
                                </a>
                            </td>
                           
                        </tr>
                    @endforeach
                    <tr style="border-top:2px solid #e9ecef; background:#f8f9fa;">
                        <td colspan="7" style="text-align:right;"><strong>{{ __('repair::lang.total_expenses') }}:</strong></td>
                        <td class="text-danger" style="font-size:16px;"><strong>EGP {{ number_format($expenses_total, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(isset($purchases) && $purchases->count() > 0)
    <div class="table-container">
        <h4 class="chart-title">
            <i class="fas fa-shopping-bag text-primary"></i>
            {{ __('repair::lang.related_purchases') }}
        </h4>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('repair::lang.supplier') }}</th>
                        <th>{{ __('repair::lang.ref_no_short') }}</th>
                        <th>{{ __('repair::lang.qty') }}</th>
                        <th>{{ __('repair::lang.date') }}</th>
                        <th>{{ __('repair::lang.status') }}</th>
                        <th>{{ __('repair::lang.payment_status') }}</th>
                        <th>{{ __('repair::lang.purchase_return') }}</th>
                        <th>{{ __('repair::lang.amount') }}</th>
                        <th>{{ __('purchase.add_payment') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchases as $p)
                        <tr>
                            <td><strong>{{ $p->supplier_name ?? '-' }}</strong></td>
                            <td>
                                <a href="#"
                                   class="btn-modal"
                                   data-href="{{ action([\Modules\Treasury\Http\Controllers\TreasuryController::class, 'show'], [$p->id]) }}"
                                   data-container=".view_modal">
                                    {{ $p->ref_no ?? '-' }}
                                </a>
                            </td>
                            <td style="text-align:center;">{{ number_format($p->total_qty, 0) }}</td>
                            <td>{{ \Carbon\Carbon::parse($p->transaction_date)->format('Y-m-d') }}</td>
                            <td>
                                @php
                                    $status = strtolower($p->status ?? 'pending');
                                    $status_label = __('lang_v1.' . $status);
                                @endphp
                                <span class="badge" style="background:#6c757d;">{{ $status_label }}</span>
                            </td>
                            <td>
                                @php
                                    $ps = strtolower($p->payment_status ?? 'due');
                                    $ps_label = __('lang_v1.' . $ps);
                                    $ps_color = match($ps){
                                        'paid' => '#34a853',
                                        'partial' => '#fbbc05',
                                        'due' => '#ea4335',
                                        default => '#6c757d'
                                    };
                                @endphp
                                <span class="badge" style="background: {{ $ps_color }};">{{ $ps_label }}</span>
                            </td>
                            <td>
                                @php
                                    // Consider this a purchase return if the row itself is a purchase_return
                                    // OR if the purchase has at least one related purchase_return child
                                    $purchase_return_count = (int) ($p->purchase_return_count ?? 0);
                                    $is_return = (($p->type ?? 'purchase') === 'purchase_return') || $purchase_return_count > 0;
                                @endphp
                                <span class="badge" style="background: {{ $is_return ? '#34a853' : '#6c757d' }};">{{ $is_return ? __('repair::lang.yes') : __('repair::lang.no') }}</span>
                            </td>
                            <td class="text-primary"><strong>EGP {{ number_format($p->final_total, 2) }}</strong></td>
                            <td>
                                <a href="{{ action([\App\Http\Controllers\TransactionPaymentController::class, 'addPayment'], [$p->id]) }}"
                                   class="btn btn-xs btn-primary add_payment_modal"
                                   data-container="#payment_modal">
                                   <i class="fas fa-money-bill-alt"></i> {{ __('purchase.add_payment') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    <tr style="border-top:2px solid #e9ecef; background:#f8f9fa;">
                        <td colspan="8" style="text-align:right;"><strong>{{ __('repair::lang.total_purchases_label') }}</strong></td>
                        <td class="text-primary" style="font-size:16px;"><strong>EGP {{ number_format($purchases_total, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Sell Return Details -->
    @if(!empty($sell_return))
    <div class="table-container" style="border-left: 3px solid #ea4335;">
        <h4 class="chart-title">
            <i class="fas fa-undo text-danger"></i>
            {{ __('repair::lang.sell_return_details') }}
        </h4>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('repair::lang.return_invoice') }}</th>
                        <th>{{ __('repair::lang.date') }}</th>
                        <th>{{ __('sale.payment_status') }}</th>
                        <th>{{ __('repair::lang.return_amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>{{ $sell_return->invoice_no ?? '-' }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($sell_return->transaction_date)->format('Y-m-d') }}</td>
                        <td>
                            @php
                                $sr_status = strtolower($sell_return->payment_status ?? 'due');
                                $sr_color = match($sr_status){
                                    'paid' => '#34a853',
                                    'partial' => '#fbbc05',
                                    'due' => '#ea4335',
                                    default => '#6c757d'
                                };
                            @endphp
                            <span class="badge" style="background: {{ $sr_color }};">{{ __('lang_v1.' . $sr_status) }}</span>
                        </td>
                        <td class="text-danger" style="font-size:16px;"><strong>EGP {{ number_format($sell_return_amount, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="info-note">
            <i class="fas fa-info-circle"></i>
            {{ __('repair::lang.sell_return_stock_note') }}
        </div>
    </div>
    @endif

    <!-- Technician Labor Details (From Timer Tracking & User Salary) -->
    @if(!empty($technician_details))
    <div class="table-container">
        <h4 class="chart-title">
            <i class="fas fa-users text-success"></i>
            {{ __('repair::lang.technician_labor_details') }} ({{ __('repair::lang.from_timer_tracking') }})
        </h4>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('repair::lang.technician') }}</th>
                        <th>{{ __('repair::lang.hours_worked') }}</th>
                        <th>{{ __('repair::lang.allocated_hours') }}</th>
                        <th>{{ __('repair::lang.hourly_rate') }}</th>
                        <th>{{ __('repair::lang.paused_hours') ?? 'Paused' }}</th>
                        <th>{{ __('repair::lang.last_timer_reason') ?? 'Last Status / Reason' }}</th>
                        <th>{{ __('repair::lang.total_cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($technician_details as $tech)
                        <tr>
                            <td><strong>{{ $tech['name'] }}</strong></td>
                            <td style="text-align:center;">
                                <span class="badge badge-hours">
                                    {{ number_format($tech['hours'], 2) }}h
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge badge-hours" style="background:#eef6ff; color:#1976d2;">
                                    {{ number_format($tech['allocated_hours'] ?? 0, 2) }}h
                                </span>
                            </td>
                            <td>
                                <span class="text-success"><strong>EGP {{ number_format($tech['hourly_rate'], 2) }}/h</strong></span>
                            </td>
                            <td style="text-align:center;">
                                @php $paused = $tech['paused_hours'] ?? 0; @endphp
                                @if($paused > 0)
                                    <span class="badge badge-hours" style="background:#fff3cd; color:#856404;">
                                        {{ number_format($paused, 2) }}h
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td style="max-width:260px; white-space:normal;">
                                @if(!empty($tech['reasons']))
                                    @foreach($tech['reasons'] as $reason)
                                        <span class="badge badge-hours" style="display:inline-block; margin:2px; background:#e3f2fd; color:#0d47a1; white-space:normal;">
                                            {{ $reason['body'] }}
                                            @if(isset($reason['hours']) && $reason['hours'] > 0)
                                                ({{ number_format($reason['hours'], 2) }}h)
                                            @endif
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-danger"><strong>EGP {{ number_format($tech['total_cost'], 2) }}</strong></td>
                        </tr>
                    @endforeach
                    <tr style="border-top:2px solid #e9ecef; background:#f8f9fa;">
                        <td colspan="4" style="text-align:right;"><strong>{{ __('repair::lang.total_labor_cost') }}:</strong></td>
                        <td class="text-danger" style="font-size:16px;"><strong>EGP {{ number_format($labor_cost, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="info-note">
            <i class="fas fa-info-circle"></i>
            {{ __('repair::lang.labor_cost_calculation_note') }}: {{ __('repair::lang.hours_from_timer_tracking_rates_from_user_salary') }}
        </div>
    </div>
    @endif

    <!-- Key Insights -->
    <div class="table-container">
        <h4 class="chart-title">
            <i class="fas fa-lightbulb text-warning"></i>
            {{ __('repair::lang.key_insights') }}
        </h4>
        <div class="insights-grid">
            <div>
                <div class="insight-item">
                    <i class="fas fa-cogs text-primary"></i>
                    <span class="insight-label">{{ __('repair::lang.parts_profit_margin') }}:</span>
                    <strong class="insight-value" style="color:{{ $parts_profit_margin >= 30 ? '#28a745' : ($parts_profit_margin >= 15 ? '#ffc107' : '#dc3545') }};">
                        {{ number_format($parts_profit_margin, 1) }}%
                    </strong>
                </div>
                <div class="insight-item">
                    <i class="fas fa-wrench text-success"></i>
                    <span class="insight-label">{{ __('repair::lang.labour_efficiency') }}:</span>
                    <strong class="insight-value" style="color:{{ $labor_efficiency >= 0 ? '#28a745' : '#dc3545' }};">
                        {{ number_format($labor_efficiency, 1) }}%
                    </strong>
                </div>
                <div class="insight-item">
                    <i class="fas fa-chart-line text-success"></i>
                    <span class="insight-label">{{ __('repair::lang.overall_profit_margin') }}:</span>
                    <strong class="insight-value" style="color:{{ $overall_profit_margin < 0 ? '#dc3545' : ($overall_profit_margin >= 10 ? '#28a745' : ($overall_profit_margin >= 5 ? '#ffc107' : '#dc3545')) }};">
                        {{ number_format($overall_profit_margin, 1) }}%
                    </strong>
                </div>
            </div>
            <div>
                <div class="insight-item">
                    <i class="fas fa-clock text-danger"></i>
                    <span class="insight-label">{{ __('repair::lang.total_labor_hours') }}:</span>
                    <strong class="insight-value">{{ number_format($labor_hours, 1) }}h</strong>
                </div>
                <div class="insight-item">
                    <i class="fas fa-percentage text-danger"></i>
                    <span class="insight-label">{{ __('repair::lang.discount_rate') }}:</span>
                    <strong class="insight-value">{{ $invoice_total > 0 ? number_format(($discount_amount / $invoice_total) * 100, 1) : 0 }}%</strong>
                </div>
                <div class="insight-item">
                    <i class="fas fa-balance-scale text-primary"></i>
                    <span class="insight-label">{{ __('repair::lang.cost_ratio') }}:</span>
                    <strong class="insight-value">{{ $invoice_total > 0 ? number_format(($total_expenses / $invoice_total) * 100, 1) : 0 }}%</strong>
                </div>
            </div>
        </div>
        
    </div>
    
   


<!-- Payment Modals (same as Treasury) -->
<div class="modal fade payment_modal" id="payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
<div class="modal fade" id="edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
@endsection
@section('javascript')
    @parent
    <script>
        // Opt-in: after successful add payment, auto-reload this overview page
        // so that all payment_status and totals reflect latest data.
        window.transaction_overview_auto_reload = true;
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script>
        if (typeof jQuery !== 'undefined' && typeof $.ajaxSetup === 'function') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart labels for localization
            const chartLabels = {!! json_encode($chartLabels) !!};
            // Components Comparison Chart
            const componentsCtx = document.getElementById('componentsChart').getContext('2d');
            new Chart(componentsCtx, {
                type: 'bar',
                data: {
                    labels: [chartLabels.purchase_cost, chartLabels.spare_parts_sales, chartLabels.labour_income, chartLabels.labor_costs, chartLabels.expenses, chartLabels.discount],
                    datasets: [{
                        data: [
                            {{ $purchasing_cost }},
                            {{ $spare_parts_total }},
                            {{ $labor_income }},
                            {{ $labor_cost }},
                            {{ $expenses_total }},
                            {{ $discount_amount }}
                        ],
                        backgroundColor: [
                            '#ea4335',
                            '#4285f4',
                            '#34a853',
                            '#e91e63',
                            '#fbbc04',
                            '#ff6384'
                        ],
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 0
                    },
                    hover: {
                        animationDuration: 0
                    },
                    responsiveAnimationDuration: 0,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'EGP ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Income Breakdown Pie Chart
            const incomeCtx = document.getElementById('incomeChart').getContext('2d');
            new Chart(incomeCtx, {
                type: 'doughnut',
                data: {
                    labels: [chartLabels.spare_parts_sales, chartLabels.labour_income],
                    datasets: [{
                        data: [
                            {{ $spare_parts_total }},
                            {{ $labor_income }}
                        ],
                        backgroundColor: [
                            '#3498db',  // Blue for spare parts sales
                            '#2ecc71'   // Green for labor income
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 0
                    },
                    hover: {
                        animationDuration: 0
                    },
                    responsiveAnimationDuration: 0,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            // Expense Breakdown Pie Chart
            const expenseCtx = document.getElementById('expenseChart').getContext('2d');
            new Chart(expenseCtx, {
                type: 'doughnut',
                data: {
                    labels: [chartLabels.purchasing_cost, chartLabels.labour_cost, chartLabels.expenses, chartLabels.discounts],
                    datasets: [{
                        data: [
                            {{ $purchasing_cost }},
                            {{ $labor_cost }},
                            {{ $expenses_total }},
                            {{ $discount_amount }}
                        ],
                        backgroundColor: [
                    '#9b59b6',  // Purple for purchasing cost
                    '#e74c3c',  // Red for labor cost
                    '#f39c12',  // Orange for other expenses
                    '#2ecc71'   // Green for discounts
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 0
                    },
                    hover: {
                        animationDuration: 0
                    },
                    responsiveAnimationDuration: 0,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
