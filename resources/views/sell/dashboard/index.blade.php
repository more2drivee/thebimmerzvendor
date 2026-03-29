@extends('layouts.app')
@section('title', __('sale.sells') . ' ' . __('business.dashboard'))

<style>
    :root{
        --chart-bg: #ffffff;
        --muted: #6b7280;
        --accent: #2563eb;
        --card-radius: 10px;
    }

    .chart-container {
        display: flex;
        gap: 18px;
        justify-content: center;
        flex-wrap: wrap;
        width: 100%;
        align-items: stretch;
    }

    .chart-wrapper {
        flex: 1 1 260px;
        max-width: 360px;
        background: var(--chart-bg);
        border-radius: var(--card-radius);
        padding: 12px 16px 18px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        display: flex;
        flex-direction: column;
        align-items: stretch;
        height: 300px;
        position: relative;
    }

    .chart-canvas-container {
        flex: 1;
        min-height: 0;
        position: relative;
    }

    .small-box {
        background: #fff;
        border-radius: var(--card-radius);
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-left: 4px solid var(--accent);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .small-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .small-box .inner {
        padding: 10px;
    }

    .small-box .inner p {
        font-size: 14px;
        color: var(--muted);
        margin: 0 0 8px 0;
        font-weight: 500;
    }

    .small-box .inner h3 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        color: var(--accent);
    }
</style>

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @include('sell.layouts.nav')
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('sale.sells')
            <small>@lang('business.dashboard')</small>
        </h1>
        <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
            @if (count($locations) > 1)
                {!! Form::select('sell_location', $locations->pluck('name', 'id')->all(), $location_id ?? null, [
                    'class' => 'form-control select2',
                    'placeholder' => __('lang_v1.select_location'),
                    'id' => 'sell_location',
                    'onchange' => "window.location.href = '/sells/dashboard?location=' + this.value",
                ]) !!}
            @endif
        </div>
    </section>

    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('sale.sell_statistics')])
                    @forelse($counters as $key => $value)
                        <div class="col-md-3 col-sm-6 col-xs-12">
                            <div class="small-box">
                                <div class="inner">
                                    <p><i class="{{ $value['icon'] }}"></i> {{ $key }}</p>
                                    <h3>{{ $value['data'] }}</h3>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h4>@lang('sale.no_data_found')</h4>
                            </div>
                        </div>
                    @endforelse
                @endcomponent
            </div>
        </div>

        @component('components.widget', ['title' => __('sale.recent_sales')])
            <table class="table table-bordered table-striped ajax_view">
                <thead>
                    <tr>
                        <th>@lang('sale.date')</th>
                        <th>@lang('sale.invoice_no')</th>
                        <th>@lang('sale.customer')</th>
                        <th>@lang('sale.total')</th>
                        <th>@lang('sale.status')</th>
                        <th>@lang('sale.payment_status')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent_sales as $sale)
                    <tr>
                        <td>{{ $sale->transaction_date }}</td>
                        <td>{{ $sale->invoice_no }}</td>
                        <td>{{ $sale->customer_name }}</td>
                        <td>{{ number_format($sale->final_total, 2) }}</td>
                        <td>
                         
                            @if($sale->status == 'draft')
                                <span class="label label-default">{{ __('sale.draft') }}</span>
                            @elseif($sale->status == 'under processing')
                                <span class="label label-warning">{{ __('sale.under_processing') }}</span>
                            @elseif($sale->status == 'final')
                                <span class="label label-success">{{ __('sale.final') }}</span>
                            @else
                                <span class="label label-default">{{ $sale->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($sale->payment_status == 'paid')
                                <span class="label label-success">{{ __('sale.paid') }}</span>
                            @elseif($sale->payment_status == 'due')
                                <span class="label label-danger">{{ __('sale.due') }}</span>
                            @elseif($sale->payment_status == 'partial')
                                <span class="label label-warning">{{ __('sale.partial') }}</span>
                            @else
                                <span class="label label-default">{{ $sale->payment_status }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">{{ __('sale.no_sales_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        @endcomponent

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('sale.sell_status')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 400px;">
                        <h2 style="text-align: center;">@lang('sale.status_distribution')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('sale.monthly_trends')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 400px;">
                        <h2 style="text-align: center;">@lang('sale.monthly_sell_trends')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
    </section>
@stop

@section('javascript')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="text/javascript">
        Chart.defaults.font.family = "Inter, 'Helvetica Neue', Arial, sans-serif";
        Chart.defaults.font.size = 13;
        Chart.defaults.color = '#374151';

        function createGradient(ctx, colorStart, colorEnd) {
            const g = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height || 300);
            g.addColorStop(0, colorStart);
            g.addColorStop(1, colorEnd);
            return g;
        }

        (function () {
            const el = document.getElementById('statusChart');
            if (!el) return;
            const ctx = el.getContext('2d');
            const labels = @json($status_chart['labels']);
            const values = @json($status_chart['data']);
            const colors = @json($status_chart['colors']);

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 12
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '55%',
                    aspectRatio: 1,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 12,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const v = ctx.parsed || 0;
                                    return ctx.label + ': ' + v;
                                }
                            }
                        }
                    },
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    }
                }
            });
        })();

        (function () {
            const el = document.getElementById('trendChart');
            if (!el) return;
            const ctx = el.getContext('2d');

            const labels = @json($labels);
            const counts = @json($sell_counts);
            const amounts = @json($sell_amounts);

            const datasets = [
                {
                    label: '@lang("sale.sell_count")',
                    data: counts,
                    borderColor: '#2563eb',
                    backgroundColor: createGradient(ctx, '#2563eb66', '#2563eb00'),
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    borderWidth: 2,
                    yAxisID: 'y'
                },
                {
                    label: '@lang("sale.sell_amount")',
                    data: amounts,
                    borderColor: '#10b981',
                    backgroundColor: createGradient(ctx, '#10b98166', '#10b98100'),
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    borderWidth: 2,
                    yAxisID: 'y1'
                }
            ];

            new Chart(ctx, {
                type: 'line',
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 2.5,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: {
                            grid: { display: false },
                            title: { display: true, text: '@lang("sale.days")' }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            grid: { color: 'rgba(15, 23, 42, 0.04)' },
                            title: { display: true, text: '@lang("sale.count")' },
                            ticks: { precision: 0 }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: '@lang("sale.amount")' }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10,
                                padding: 15
                            }
                        },
                        tooltip: {
                            mode: 'nearest',
                            intersect: false,
                            callbacks: {
                                label: function (ctx) {
                                    const v = ctx.parsed.y;
                                    return ctx.dataset.label + ': ' + (v !== undefined ? v : 0);
                                }
                            }
                        }
                    },
                    layout: {
                        padding: {
                            top: 15,
                            bottom: 15,
                            left: 10,
                            right: 10
                        }
                    }
                }
            });
        })();
    </script>
@endsection
