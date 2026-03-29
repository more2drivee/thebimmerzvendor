@extends('layouts.app')
@section('title', __('product.products') . ' ' . __('business.dashboard'))

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

    @include('products.layouts.nav')
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('product.products')
            <small>@lang('business.dashboard')</small>
        </h1>
        <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
            @if (count($locations) > 1)
                {!! Form::select('products_location', $locations->pluck('name', 'id')->all(), $location_id ?? null, [
                    'class' => 'form-control select2',
                    'placeholder' => __('lang_v1.select_location'),
                    'id' => 'products_location',
                    'onchange' => "window.location.href = '/inventory/dashboard?location=' + this.value",
                ]) !!}
            @endif
        </div>
    </section>

    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('product.product_statistics')])
                    @forelse($counters as $key => $value)
                        <div class="col-md-3 col-sm-6 col-xs-12">
                            <div class="small-box">
                                <div class="inner">
                                    <p><i class="{{ $value['icon'] }}"></i> {{ $key }}</p>
                                    <h3>{{ number_format($value['data']) }}</h3>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h4>@lang('messages.no_data_found')</h4>
                            </div>
                        </div>
                    @endforelse
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                @component('components.widget', ['title' => __('product.stock_alerts_by_location')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 350px;">
                        <h2 style="text-align: center;">@lang('product.stock_alerts_by_location')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="stockAlertsChart"></canvas>
                        </div>
                    </div>
                @endcomponent
            </div>
            <div class="col-md-6">
                @component('components.widget', ['title' => __('product.products_by_category')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 350px;">
                        <h2 style="text-align: center;">@lang('product.products_by_category')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        @component('components.widget', ['title' => __('product.top_selling_products')])
            <table class="table table-bordered table-striped ajax_view">
                <thead>
                    <tr>
                        <th>@lang('product.product_name')</th>
                        <th>@lang('sale.quantity')</th>
                        <th>@lang('sale.total_revenue')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($top_products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ number_format($product->total_quantity) }}</td>
                        <td>{{ number_format($product->total_revenue, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center">{{ __('messages.no_data_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        @endcomponent

        @component('components.widget', ['title' => __('product.low_stock_products')])
            <table class="table table-bordered table-striped ajax_view">
                <thead>
                    <tr>
                        <th>@lang('product.product_name')</th>
                        <th>@lang('product.sku')</th>
                        <th>@lang('product.current_stock')</th>
                        <th>@lang('product.alert_quantity')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($low_stock_products as $product)
                    <tr>
                        <td>{{ $product->product }}</td>
                        <td>{{ $product->sku }} </td>
                        <td class="text-danger font-bold">{{ number_format($product->stock) }}</td>
                        <td>{{ number_format($product->alert_quantity) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center">{{ __('messages.no_data_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        @endcomponent
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
            const el = document.getElementById('stockAlertsChart');
            if (!el) return;
            const ctx = el.getContext('2d');
            const stockAlertsData = @json($stock_alerts);

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: stockAlertsData.map(item => item.location),
                    datasets: [{
                        data: stockAlertsData.map(item => item.count),
                        backgroundColor: ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#6b7280'],
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
            const el = document.getElementById('categoryChart');
            if (!el) return;
            const ctx = el.getContext('2d');
            const categoryData = @json($products_by_category);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: categoryData.map(item => item.name),
                    datasets: [{
                        label: '@lang("product.product_count")',
                        data: categoryData.map(item => item.product_count),
                        backgroundColor: createGradient(ctx, '#3b82f666', '#3b82f600'),
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 2,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const v = ctx.parsed.y;
                                    return ctx.dataset.label + ': ' + (v !== undefined ? v : 0);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: 'rgba(15, 23, 42, 0.04)' }
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
