@extends('layouts.app')
@section('title', __('bundles.title'))

@section('content')
<style>
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
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .metric-card {
        background: #fff;
        border-radius: 8px;
        padding: 16px 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        border: 1px solid #f0f0f0;
    }

    .metric-header {
        font-size: 13px;
        color: #7a8aa3;
        display: flex;
        align-items: center;
        margin-bottom: 6px;
    }

    .metric-value {
        font-size: 1.3rem;
        font-weight: 700;
    }

    .charts-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
        font-size: 1.05rem;
        font-weight: 600;
    }

    .chart-container {
        height: 260px;
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

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
    }

    .data-table th,
    .data-table td {
        padding: 10px 8px;
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
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 20px;
    }

    .insight-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }

    .insight-label {
        color: #6c757d;
        margin-right: 6px;
    }

    .insight-value {
        font-weight: 600;
        margin-left: 4px;
    }

    @media (max-width: 768px) {
        .transaction-overview {
            padding: 15px;
        }

        .data-table {
            min-width: 600px;
        }
    }
</style>
<div class="transaction-overview">
    <h3 class="overview-title">{{ __('bundles.overview.title') }} - #{{ $bundle->reference_no }}</h3>

    <div class="table-container tw-mb-4">
        <h5 class="tw-text-base tw-font-semibold tw-text-gray-700 tw-mb-3">{{ __('bundles.overview.bundle_summary') }}</h5>
        <div class="insights-grid">
            <div>
                <div class="insight-item">
                    <span class="insight-label">{{ __('bundles.fields.device') }}:</span>
                    <strong class="insight-value">{{ $bundle->device->name ?? '-' }}</strong>
                </div>
                <div class="insight-item">
                    <span class="insight-label">{{ __('bundles.fields.model') }}:</span>
                    <strong class="insight-value">{{ $bundle->repairDeviceModel->name ?? '-' }}</strong>
                </div>
                <div class="insight-item">
                    <span class="insight-label">{{ __('bundles.fields.location') }}:</span>
                    <strong class="insight-value">{{ $bundle->location->name ?? '-' }}</strong>
                </div>
            </div>
            <div>
                <div class="insight-item">
                    <span class="insight-label">{{ __('bundles.overview.total_transactions') }}:</span>
                    <strong class="insight-value">{{ $total_transactions }}</strong>
                </div>
                <div class="insight-item">
                    <span class="insight-label">{{ __('bundles.overview.total_qty_sold') }}:</span>
                    <strong class="insight-value">{{ number_format($total_qty, 2) }}</strong>
                </div>
                <div class="insight-item">
                    <span class="insight-label">{{ __('bundles.overview.unique_customers') }}:</span>
                    <strong class="insight-value">{{ $unique_customers }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-receipt text-primary"></i>
                {{ __('bundles.overview.total_sales') }}
            </div>
            <div class="metric-value">EGP {{ number_format($total_sales, 2) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-shopping-cart text-danger"></i>
                {{ __('bundles.overview.total_purchase_cost') }}
            </div>
            <div class="metric-value">EGP {{ number_format($total_purchase_cost, 2) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-chart-line text-success"></i>
                {{ __('bundles.overview.net_profit') }}
            </div>
            <div class="metric-value {{ $net_profit >= 0 ? 'text-success' : 'text-danger' }}">EGP {{ number_format($net_profit, 2) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-header">
                <i class="fas fa-percentage text-warning"></i>
                {{ __('bundles.overview.profit_margin') }}
            </div>
            <div class="metric-value">{{ number_format($profit_margin, 1) }}%</div>
        </div>
    </div>

    <div class="charts-container">
        <div class="chart-card">
            <h4 class="chart-title">
                <i class="fas fa-chart-bar text-primary"></i>
                {{ __('bundles.overview.sales_vs_cost_chart') }}
            </h4>
            <div class="chart-container">
                <canvas id="bundleSalesChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h4 class="chart-title">
                <i class="fas fa-chart-pie text-success"></i>
                {{ __('bundles.overview.sold_products_chart') }}
            </h4>
            <div class="chart-container">
                <canvas id="bundleProductsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h4 class="chart-title">
            <i class="fas fa-table text-warning"></i>
            {{ __('bundles.overview.transactions_table') }}
        </h4>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('bundles.overview.transaction_date') }}</th>
                        <th>{{ __('bundles.overview.invoice_no') }}</th>
                        <th>{{ __('bundles.overview.customer') }}</th>
                        <th>Product</th>
                        <th>{{ __('bundles.overview.qty') }}</th>
                        <th>{{ __('bundles.overview.selling_total') }}</th>
                        <th>{{ __('sale.payment_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->transaction_date)->format('Y-m-d') }}</td>
                            <td>{{ $row->invoice_no ?? $row->transaction_id }}</td>
                            <td>{{ $row->customer_name ?? '-' }}</td>
                            <td>{{ $row->product_name ?? '-' }}</td>
                            <td>{{ number_format($row->quantity, 2) }}</td>
                            <td>EGP {{ number_format($row->selling_total, 2) }}</td>
                            <td>
                                @if($row->payment_status == 'paid')
                                    <span class="badge bg-success">{{ __('sale.paid') }}</span>
                                @elseif($row->payment_status == 'partial')
                                    <span class="badge bg-warning">{{ __('sale.partial') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('sale.due') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('javascript')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const salesCtx = document.getElementById('bundleSalesChart').getContext('2d');
            new Chart(salesCtx, {
                type: 'bar',
                data: {
                    labels: ['{{ __('bundles.overview.total_sales') }}', '{{ __('bundles.overview.total_purchase_cost') }}'],
                    datasets: [{
                        data: [
                            {{ $total_sales }},
                            {{ $total_purchase_cost }}
                        ],
                        backgroundColor: ['#4285f4', '#ea4335'],
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) { return 'EGP ' + value.toLocaleString(); }
                            }
                        }
                    }
                }
            });

            const productsCtx = document.getElementById('bundleProductsChart').getContext('2d');
            const productLabels = {!! json_encode(array_keys($sold_products)) !!};
            const productData = {!! json_encode(array_values($sold_products)) !!};
            new Chart(productsCtx, {
                type: 'bar',
                data: {
                    labels: productLabels,
                    datasets: [{
                        data: productData,
                        backgroundColor: '#2ecc71',
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) { return value.toLocaleString(); }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
