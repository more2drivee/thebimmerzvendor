@extends('layouts.app')

@section('title', __('timemanagement::lang.nav_time_statistics') ?? 'Time Reasons Statistics')

@section('content')
@include('timemanagement::partials.nav')

<section class="content-header">
    <h1>@lang('timemanagement::lang.nav_time_statistics')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-4">
            <div class="small-box bg-aqua">
                <div class="inner">
                    @php $total_hours = max(0, ($total_pause_seconds ?? 0) / 3600); @endphp
                    <h3>{{ number_format($total_hours, 2) }}<sup style="font-size: 16px;">h</sup></h3>
                    <p>@lang('timemanagement::lang.total_paused_hours_overall')</p>
                </div>
                <div class="icon"><i class="fas fa-pause-circle"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3>{{ $total_reasons_count ?? 0 }}</h3>
                    <p>@lang('timemanagement::lang.total_pause_events')</p>
                </div>
                <div class="icon"><i class="fas fa-stream"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3>{{ $total_technicians_count ?? 0 }}</h3>
                    <p>@lang('timemanagement::lang.total_technicians_with_pauses')</p>
                </div>
                <div class="icon"><i class="fas fa-user-cog"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('timemanagement::lang.chart_pause_by_reason')</h3>
                </div>
                <div class="box-body" style="max-width:520px; margin:0 auto;">
                    <canvas id="reasonsChart" height="160" style="max-height:260px; max-width:100%;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('timemanagement::lang.chart_pause_by_technician')</h3>
                </div>
                <div class="box-body" style="max-width:520px; margin:0 auto;">
                    <canvas id="techniciansChart" height="160" style="max-height:260px; max-width:100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('timemanagement::lang.time_statistics_filters')</h3>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('timemanagement.time_statistics') }}" class="form-inline">
                <div class="form-group">
                    <label for="start_date" class="control-label">@lang('timemanagement::lang.messages.start_date')</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $start_date }}">
                </div>
                <div class="form-group" style="margin-left:10px;">
                    <label for="end_date" class="control-label">@lang('timemanagement::lang.messages.end_date')</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $end_date }}">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-left:10px;">
                    <i class="fa fa-filter"></i> @lang('timemanagement::lang.messages.filter')
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('timemanagement::lang.time_statistics_reasons')</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('timemanagement::lang.stop_reason')</th>
                                <th>@lang('timemanagement::lang.occurrences')</th>
                                <th>@lang('timemanagement::lang.total_paused_hours')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reasons_stats as $row)
                                @php
                                    $hours = max(0, ($row->total_pause_seconds ?? 0) / 3600);
                                @endphp
                                <tr>
                                    <td>{{ $row->body }}</td>
                                    <td>{{ $row->occurrences }}</td>
                                    <td>{{ number_format($hours, 2) }} h</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">@lang('messages.no_data_found')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="text-center">
                        {{ $reasons_stats->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('timemanagement::lang.time_statistics_technicians')</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('timemanagement::lang.technician')</th>
                                <th>@lang('timemanagement::lang.reasons_count')</th>
                                <th>@lang('timemanagement::lang.total_paused_hours')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($technician_stats as $row)
                                @php
                                    $hours = max(0, ($row->total_pause_seconds ?? 0) / 3600);
                                @endphp
                                <tr>
                                    <td>{{ trim($row->first_name . ' ' . $row->last_name) }}</td>
                                    <td>{{ $row->reasons_count }}</td>
                                    <td>{{ number_format($hours, 2) }} h</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">@lang('messages.no_data_found')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('timemanagement::lang.time_statistics_finished_links')</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('timemanagement::lang.technician')</th>
                                <th>@lang('timemanagement::lang.completed_timer_job_sheet')</th>
                                <th>@lang('timemanagement::lang.resumed_timer_job_sheet')</th>
                                <th>@lang('timemanagement::lang.stop_reason')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($finish_links_stats as $row)
                                <tr>
                                    <td>{{ trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) }}</td>
                                    <td>{{ $row->completed_job_sheet_no ?? '-' }}</td>
                                    <td>{{ $row->resumed_job_sheet_no ?? '-' }}</td>
                                    <td>{{ $row->body ?? '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">@lang('messages.no_data_found')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="text-center">
                        {{ $finish_links_stats->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            const reasons = @json($reasons_chart_stats->map(function($r) {
                return [
                    'label' => $r->body,
                    'seconds' => (int)($r->total_pause_seconds ?? 0),
                ];
            }));

            const technicians = @json($technician_chart_stats->map(function($t) {
                return [
                    'label' => trim(($t->first_name ?? '') . ' ' . ($t->last_name ?? '')),
                    'seconds' => (int)($t->total_pause_seconds ?? 0),
                ];
            }));

            function secondsToHours(sec) {
                return (sec / 3600).toFixed(2);
            }

            if (reasons.length > 0 && document.getElementById('reasonsChart')) {
                const ctxR = document.getElementById('reasonsChart').getContext('2d');
                new Chart(ctxR, {
                    type: 'doughnut',
                    data: {
                        labels: reasons.map(r => r.label || '{{ __('timemanagement::lang.unknown_reason') }}'),
                        datasets: [{
                            data: reasons.map(r => r.seconds),
                            backgroundColor: ['#42a5f5','#66bb6a','#ffa726','#ab47bc','#ef5350','#26a69a','#5c6bc0'],
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        const sec = ctx.parsed || 0;
                                        return ctx.label + ': ' + secondsToHours(sec) + 'h';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            if (technicians.length > 0 && document.getElementById('techniciansChart')) {
                const ctxT = document.getElementById('techniciansChart').getContext('2d');
                new Chart(ctxT, {
                    type: 'bar',
                    data: {
                        labels: technicians.map(t => t.label || '{{ __('timemanagement::lang.technician') }}'),
                        datasets: [{
                            label: '{{ __('timemanagement::lang.total_paused_hours') }}',
                            data: technicians.map(t => (t.seconds / 3600).toFixed(2)),
                            backgroundColor: '#42a5f5',
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: '{{ __('timemanagement::lang.hours') }}'
                                }
                            }
                        }
                    }
                });
            }
        })();
    </script>
@endsection
