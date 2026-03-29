@extends('layouts.app')
@section('title', __('account.accounts') . ' ' . __('business.dashboard'))

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

    @include('accounts.layouts.nav')
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('account.accounts')
            <small>@lang('business.dashboard')</small>
        </h1>
        <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
            @if (count($locations) > 1)
                {!! Form::select('accounts_location', $locations->pluck('name', 'id')->all(), $location_id ?? null, [
                    'class' => 'form-control select2',
                    'placeholder' => __('lang_v1.select_location'),
                    'id' => 'accounts_location',
                    'onchange' => "window.location.href = '/account/dashboard?location=' + this.value",
                ]) !!}
            @endif
        </div>
    </section>

    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('account.account_statistics')])
                    @forelse($counters as $key => $value)
                        <div class="col-md-3 col-sm-6 col-xs-12">
                            <div class="small-box">
                                <div class="inner">
                                    <p><i class="{{ $value['icon'] }}"></i> {{ $key }}</p>
                                    <h3>{{ number_format($value['data'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h4>{{ __('messages.no_data_found') }}</h4>
                            </div>
                        </div>
                    @endforelse
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                @component('components.widget', ['title' => __('account.transaction_type_distribution')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 400px;">
                        <h2 style="text-align: center;">@lang('account.transaction_type_distribution')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="transactionTypeChart"></canvas>
                        </div>
                    </div>
                @endcomponent
            </div>
            <div class="col-md-6">
                @component('components.widget', ['title' => __('account.account_balance_distribution')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 400px;">
                        <h2 style="text-align: center;">@lang('account.account_balance_distribution')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="balanceDistributionChart"></canvas>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('account.credit_debit_trend')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 400px;">
                        <h2 style="text-align: center;">@lang('account.monthly_credit_debit_trends')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                @component('components.widget', ['title' => __('account.daily_transaction_volume')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 400px;">
                        <h2 style="text-align: center;">@lang('account.daily_transaction_volume')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="dailyVolumeChart"></canvas>
                        </div>
                    </div>
                @endcomponent
            </div>
            <div class="col-md-6">
                @component('components.widget', ['title' => __('account.top_accounts')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 400px;">
                        <h2 style="text-align: center;">@lang('account.top_accounts_by_balance')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="topAccountsChart"></canvas>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        @component('components.widget', ['title' => __('account.recent_transactions')])
            <table class="table table-bordered table-striped ajax_view">
                <thead>
                    <tr>
                        <th>@lang('account.date')</th>
                        <th>@lang('account.account_name')</th>
                        <th>@lang('account.type')</th>
                        <th>@lang('account.sub_type')</th>
                        <th>@lang('account.amount')</th>
                        <th>@lang('account.created_by')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent_transactions as $transaction)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($transaction->operation_date)->format('Y-m-d H:i') }}</td>
                        <td>{{ $transaction->account_name }}</td>
                        <td>
                            @if($transaction->type == 'credit')
                                <span class="label label-success">{{ __('account.credit') }}</span>
                            @else
                                <span class="label label-danger">{{ __('account.debit') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($transaction->sub_type)
                                <span class="label label-info">{{ __('account.' . $transaction->sub_type) }}</span>
                            @else
                                <span class="label label-default">{{ __('account.other') }}</span>
                            @endif
                        </td>
                        <td>{{ number_format($transaction->amount, 2) }}</td>
                        <td>{{ $transaction->created_by }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">{{ __('messages.no_data_found') }}</td>
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

        // Transaction Type Distribution Chart
        (function () {
            const el = document.getElementById('transactionTypeChart');
            if (!el) return;
            const ctx = el.getContext('2d');
            const labels = @json($transaction_type_chart['labels']);
            const values = @json($transaction_type_chart['data']);
            const colors = @json($transaction_type_chart['colors']);

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

        // Balance Distribution Chart
        (function () {
            const el = document.getElementById('balanceDistributionChart');
            if (!el) return;
            const ctx = el.getContext('2d');
            const balanceData = @json($balance_distribution ?? []);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: balanceData.labels || [],
                    datasets: [{
                        label: '@lang("account.balance")',
                        data: balanceData.data || [],
                        backgroundColor: '#3b82f6',
                        borderColor: '#2563eb',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(15, 23, 42, 0.04)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        })();

        // Credit/Debit Trend Chart
        (function () {
            const el = document.getElementById('trendChart');
            if (!el) return;
            const ctx = el.getContext('2d');

            const labels = @json($labels);
            const creditAmounts = @json($credit_amounts);
            const debitAmounts = @json($debit_amounts);

            const datasets = [
                {
                    label: '@lang("account.credit_amounts")',
                    data: creditAmounts.map(d => d.y),
                    borderColor: '#10b981',
                    backgroundColor: createGradient(ctx, '#10b98166', '#10b98100'),
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    borderWidth: 2,
                    yAxisID: 'y'
                },
                {
                    label: '@lang("account.debit_amounts")',
                    data: debitAmounts.map(d => d.y),
                    borderColor: '#ef4444',
                    backgroundColor: createGradient(ctx, '#ef444466', '#ef444400'),
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
                            title: { display: true, text: '@lang("account.days")' }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            grid: { color: 'rgba(15, 23, 42, 0.04)' },
                            title: { display: true, text: '@lang("account.credit")' },
                            ticks: { precision: 0 }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: '@lang("account.debit")' }
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

        // Daily Transaction Volume Chart
        (function () {
            const el = document.getElementById('dailyVolumeChart');
            if (!el) return;
            const ctx = el.getContext('2d');
            const dailyData = @json($daily_transaction_volume ?? []);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dailyData.labels || [],
                    datasets: [{
                        label: '@lang("account.transaction_count")',
                        data: dailyData.data || [],
                        backgroundColor: '#8b5cf6',
                        borderColor: '#7c3aed',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(15, 23, 42, 0.04)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        })();

        // Top Accounts Chart
        (function () {
            const el = document.getElementById('topAccountsChart');
            if (!el) return;
            const ctx = el.getContext('2d');
            const topAccountsData = @json($top_accounts ?? []);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: topAccountsData.labels || [],
                    datasets: [{
                        label: '@lang("account.balance")',
                        data: topAccountsData.data || [],
                        backgroundColor: '#f59e0b',
                        borderColor: '#d97706',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: 'rgba(15, 23, 42, 0.04)' }
                        },
                        y: {
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        })();
    </script>
@endsection
