@extends('layouts.app')
@section('title', __('repair::lang.repair') . ' ' . __('business.dashboard'))

<style>
    :root{
        --chart-bg: #ffffff;
        --muted: #6b7280;
        --accent: #2563eb; /* blue-600 */
        --card-radius: 10px;
    }

    /* Chart layout */
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
        height: 300px; /* Fixed height instead of min-height */
        position: relative;
    }

    .chart-wrapper h2{
        margin: 0 0 8px 0;
        font-size: 16px;
        text-align: center;
        color: #111827;
        font-weight: 600;
        flex-shrink: 0; /* Prevent title from shrinking */
    }

    /* Chart canvas container */
    .chart-wrapper .chart-canvas-container {
        flex: 1;
        position: relative;
        height: calc(100% - 36px); /* Fixed height calculation */
        overflow: hidden;
    }

    /* Ensure canvas fills available area and remains responsive */
    .chart-wrapper canvas{
        width: 100% !important;
        height: 100% !important; /* Fill the container completely */
        display: block;
        max-height: 100%;
    }

    /* Small stat boxes refinement */
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

    /* Responsive tweaks */
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

    @include('repair::layouts.nav')
    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('repair::lang.repair')
            <small>@lang('business.dashboard')</small>
        </h1>
        <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
            @if (count($locations) > 1)
                {!! Form::select('Repair_location', $locations->pluck('name', 'id')->all(), $location_id ?? null, [
                    'class' => 'form-control select2',
                    'placeholder' => __('lang_v1.select_location'),
                    'id' => 'Repair_location',
                    'onchange' => "window.location.href = '/repair/dashboard?location=' + this.value",
                ]) !!}
            @endif
        </div>
    </section>
    <!-- Main content -->
    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('repair::lang.job_sheets_by_status')])
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
                                <h4>@lang('repair::lang.no_report_found')</h4>
                            </div>
                        </div>
                    @endforelse
                @endcomponent
            </div>
        </div>

        @component('components.widget', ['title' => __('repair::lang.follow_work')])
            <table class="table table-bordered table-striped ajax_view">
                <thead>
                    <tr>
                        <th>@lang('repair::lang.date')</th>
                        <th>@lang('repair::lang.spare_parts_request')</th>
                        <th>@lang('repair::lang.job_sheet_number')</th>
                        <th>@lang('repair::lang.workshop')</th>
                        <th>@lang('repair::lang.status')</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($table as $data) {  ?>
                    <tr>
                        <td><?= $data->created_at ?></td>
                        <td><?= $data->product_name ?></td>
                        <td><?= $data->job_sheet_no ?></td>
                        <td><?= $data->workshop_name ?></td>
                        <td>
                            <?php if($data->out_for_deliver == 1) { ?>
                            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max"
                                onclick="window.location.href='{{ route('editjobStatus', $data->id) }}'">
                                @lang('repair::lang.confirm_receipt')
                            </button>
                            <?php } else { ?>
                            <p style="color: #666;">@lang('repair::lang.waiting_for_warehouse')</p>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php  } ?>
                </tbody>
            </table>
        @endcomponent

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('repair::lang.follow_up_work_monthly')])
                    <div class="chart-container">
                        <div class="chart-wrapper">
                            <h2 style="text-align: center;">@lang('repair::lang.follow_joborder')</h2>
                            <div class="chart-canvas-container">
                                <canvas id="leftChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <h2 style="text-align: center;">@lang('repair::lang.follow_workshop')</h2>
                            <div class="chart-canvas-container">
                                <canvas id="rightChart"></canvas>
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('repair::lang.follow_up_work_monthly')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 400px;">
                        <h2 style="text-align: center;">@lang('repair::lang.monthly_trends')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="myChart"></canvas>
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
        /* Modern Chart.js defaults */
        Chart.defaults.font.family = "Inter, 'Helvetica Neue', Arial, sans-serif";
        Chart.defaults.font.size = 13;
        Chart.defaults.color = '#374151';

        // Utility to create a vertical gradient for a chart
        function createGradient(ctx, colorStart, colorEnd) {
            const g = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height || 300);
            g.addColorStop(0, colorStart);
            g.addColorStop(1, colorEnd);
            return g;
        }

        // Left Chart (Doughnut)
        (function () {
            const el = document.getElementById('leftChart');
            if (!el) return;
            const ctx = el.getContext('2d');
            const labels = @json(array_column($circle['left_chart'], 'label'));
            const values = @json(array_column($circle['left_chart'], 'value'));
            const colors = @json(array_column($circle['left_chart'], 'color'));

            const data = {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 12
                }]
            };

            new Chart(ctx, {
                type: 'doughnut',
                data: data,
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

        // Right Chart (Doughnut)
        (function () {
            const el = document.getElementById('rightChart');
            if (!el) return;
            const ctx = el.getContext('2d');
            const labels = @json(array_column($circle['right_chart'], 'label'));
            const values = @json(array_column($circle['right_chart'], 'value'));
            const colors = @json(array_column($circle['right_chart'], 'color'));

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '50%',
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
                            mode: 'nearest', 
                            intersect: false 
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

        // Line Chart (Monthly)
        (function () {
            const el = document.getElementById('myChart');
            if (!el) return;
            const ctx = el.getContext('2d');

            const labels = @json($labels);
            const booking = @json($bookingcounts);
            const jobs = @json($jobsheetcounts);
            const callback = @json($callbackcounts);

            const palette = {
                booking: '#2563eb', // blue-600
                jobs: '#10b981', // green-500
                callback: '#ef4444' // red-500
            };

            const datasets = [
                { label: '@lang("repair::lang.booking")', data: booking, color: palette.booking },
                { label: '@lang("repair::lang.job_orders")', data: jobs, color: palette.jobs },
                { label: '@lang("repair::lang.callback")', data: callback, color: palette.callback }
            ];

            const chartDatasets = datasets.map(d => ({
                label: d.label,
                data: d.data,
                borderColor: d.color,
                backgroundColor: createGradient(ctx, d.color + '66', d.color + '00'),
                tension: 0.35,
                fill: true,
                pointRadius: 3,
                pointHoverRadius: 6,
                borderWidth: 2
            }));

            new Chart(ctx, {
                type: 'line',
                data: { labels: labels, datasets: chartDatasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 2.5,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: {
                            grid: { display: false },
                            title: { display: true, text: '@lang("repair::lang.days")' }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(15, 23, 42, 0.04)' },
                            title: { display: true, text: '@lang("repair::lang.count")' },
                            ticks: { precision: 0 }
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
