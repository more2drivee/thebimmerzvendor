@extends('layouts.app')
@section('title', __('lang_v1.loyalty_requests') . ' ' . __('business.dashboard'))

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

    .chart-wrapper h2{
        margin: 0 0 8px 0;
        font-size: 16px;
        text-align: center;
        color: #111827;
        font-weight: 600;
        flex-shrink: 0;
    }

    .chart-wrapper .chart-canvas-container {
        flex: 1;
        position: relative;
        height: calc(100% - 36px);
        overflow: hidden;
    }

    .chart-wrapper canvas{
        width: 100% !important;
        height: 100% !important;
        display: block;
        max-height: 100%;
    }

    .small-box {
        border-radius: 10px;
        background: var(--chart-bg);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        padding: 18px;
        margin-bottom: 18px;
        transition: transform .18s ease, box-shadow .18s ease;
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .small-box:hover {
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.09);
        transform: translateY(-4px);
    }

    .inner { text-align:center; }
    .inner p { margin:0 0 6px 0; color:var(--muted); font-size:15px; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px; }
    .inner i { color:var(--accent); font-size:18px; }
    .inner h3 { margin:0; font-size:22px; font-weight:700; color:#0f172a; }

    @media (max-width: 1200px){
        .chart-container {
            gap: 12px;
        }
        .chart-wrapper {
            max-width: 320px;
            height: 280px;
        }
    }

    @media (max-width: 900px){
        .chart-wrapper{
            max-width: 48%;
            height: 260px;
            min-width: 250px;
        }
        .chart-container {
            gap: 10px;
        }
    }

    @media (max-width: 600px){
        .chart-wrapper{
            max-width: 100%;
            height: 240px;
            margin-bottom: 15px;
        }
        .chart-container {
            flex-direction: column;
            align-items: center;
        }
        .inner h3{ font-size:20px }
        .inner p{ font-size:14px }
    }

    @media (max-width: 480px){
        .chart-wrapper {
            height: 220px;
            padding: 10px 12px 15px;
        }
        .chart-wrapper h2 {
            font-size: 14px;
            margin-bottom: 6px;
        }
    }
</style>

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @include('contact.layouts.nav')
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('lang_v1.loyalty_requests')
            <small>@lang('business.dashboard')</small>
        </h1>
    </section>

    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('lang_v1.loyalty_statistics')])
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
                                <h4>@lang('lang_v1.no_data_found')</h4>
                            </div>
                        </div>
                    @endforelse
                @endcomponent
            </div>
        </div>

        @component('components.widget', ['title' => __('lang_v1.recent_requests')])
            <table class="table table-bordered table-striped ajax_view">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('contact.name')</th>
                        <th>@lang('lang_v1.points_requested')</th>
                        <th>@lang('sale.order_total')</th>
                        <th>@lang('lang_v1.status')</th>
                        <th>@lang('lang_v1.approved_by')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent_requests as $request)
                    <tr>
                        <td>{{ $request->created_at }}</td>
                        <td>{{ $request->contact_name }}</td>
                        <td>{{ $request->points_requested }}</td>
                        <td>{{ number_format($request->order_total, 2) }}</td>
                        <td>
                            @if($request->status == 'pending')
                                <span class="label label-warning">{{ __('lang_v1.pending') }}</span>
                            @elseif($request->status == 'approved')
                                <span class="label label-success">{{ __('lang_v1.approved') }}</span>
                            @elseif($request->status == 'rejected')
                                <span class="label label-danger">{{ __('lang_v1.rejected') }}</span>
                            @else
                                <span class="label label-default">{{ $request->status }}</span>
                            @endif
                        </td>
                        <td>{{ $request->approved_by ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">{{ __('lang_v1.no_requests_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        @endcomponent

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('lang_v1.request_status')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 400px;">
                        <h2 style="text-align: center;">@lang('lang_v1.status_distribution')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('lang_v1.monthly_trends')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 400px;">
                        <h2 style="text-align: center;">@lang('lang_v1.monthly_request_trends')</h2>
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
            const labels = @json($status_chart['labels'] ?? []);
            const values = @json($status_chart['data'] ?? []);
            const colors = @json($status_chart['colors'] ?? []);

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

            const labels = @json($labels ?? []);
            const counts = @json($request_counts ?? []);
            const amounts = @json($point_amounts ?? []);

            const datasets = [
                {
                    label: '@lang("lang_v1.request_count")',
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
                    label: '@lang("lang_v1.points_awarded")',
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
                            title: { display: true, text: '@lang("business.days")' }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            grid: { color: 'rgba(15, 23, 42, 0.04)' },
                            title: { display: true, text: '@lang("lang_v1.count")' },
                            ticks: { precision: 0 }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: '@lang("lang_v1.points")' }
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
