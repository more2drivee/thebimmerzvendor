@extends('layouts.app')
@section('title', __('survey::lang.survey') . ' ' . __('business.dashboard'))

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

    @include('survey::layouts.nav')
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('survey::lang.survey')
            <small>@lang('business.dashboard')</small>
        </h1>
    </section>

    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('survey::lang.survey_statistics')])
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
                                <h4>@lang('survey::lang.no_data_found')</h4>
                            </div>
                        </div>
                    @endforelse
                @endcomponent
            </div>
        </div>

        @component('components.widget', ['title' => __('survey::lang.recent_surveys')])
            <table class="table table-bordered table-striped ajax_view">
                <thead>
                    <tr>
                        <th>@lang('survey::lang.title')</th>
                        <th>@lang('survey::lang.description')</th>
                        <th>@lang('survey::lang.type')</th>
                        <th>@lang('survey::lang.created_at')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSurveys as $survey)
                    <tr>
                        <td>{{ $survey->title }}</td>
                        <td>{{ $survey->description }}</td>
                        <td>{{ $survey->type }}</td>
                        <td>{{ $survey->created_at }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center">{{ __('survey::lang.no_surveys_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        @endcomponent

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('survey::lang.survey_status')])
                    <div class="chart-wrapper" style="max-width: 80%; margin: 0 auto; height: 400px;">
                        <h2 style="text-align: center;">@lang('survey::lang.status_distribution')</h2>
                        <div class="chart-canvas-container">
                            <canvas id="statusChart"></canvas>
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
    </script>
@endsection
