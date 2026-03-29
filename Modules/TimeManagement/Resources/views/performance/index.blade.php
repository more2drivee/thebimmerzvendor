@extends('layouts.app')
@section('title', __('timemanagement::lang.performance_page_title'))


@section('content')
@include('timemanagement::partials.nav')

<section class="content-header">
  <h1>@lang('timemanagement::lang.performance_title')</h1>
  <small>@lang('timemanagement::lang.performance_subtitle')</small>
</section>
<section class="content">
  <div class="row">

    <div class="col-md-12">
      <div class="box box-solid">
        <div class="box-body">
          @include('timemanagement::partials.filters', ['action' => route('timemanagement.performance')])
          <hr/>

          <style>
            .pf-kpi .kpi-card{border:1px solid #e5e7eb;border-radius:12px;padding:16px;background:#f9fafb;display:flex;align-items:center;justify-content:space-between;margin-bottom:15px}
            .pf-kpi .kpi-title{color:#6b7280;margin:0 0 6px 0;font-weight:600}
            .pf-kpi .kpi-value{font-size:28px;font-weight:700;color:#111827}
            .pf-section-title{font-size:16px;font-weight:700;color:#111827;margin:10px 0 14px}
          </style>


          <div class="row pf-kpi">
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.kpi_total_productive')</div>
                  <div class="kpi-value">{{ number_format($total_prod, 1) }}h</div>
                </div>
                <i class="fa fa-hourglass-start text-primary" aria-hidden="true"></i>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.kpi_total_scheduled')</div>
                  <div class="kpi-value">{{ number_format($total_sched, 1) }}h</div>
                </div>
                <i class="fa fa-calendar text-info" aria-hidden="true"></i>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.kpi_avg_efficiency')</div>
                  <div class="kpi-value">{{ number_format($avg_eff, 0) }}%</div>
                </div>
                <i class="fa fa-line-chart text-success" aria-hidden="true"></i>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.kpi_total_late')</div>
                  <div class="kpi-value">{{ $total_late }}</div>
                </div>
                <i class="fa fa-clock-o text-warning" aria-hidden="true"></i>
              </div>
            </div>
          </div>

          <!-- New Productivity Metrics KPIs -->
          <div class="row pf-kpi">
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.productivity_rate')</div>
                  <div class="kpi-value">{{ number_format($productivityRate ?? 0, 1) }}%</div>
                </div>
                <i class="fa fa-tachometer text-primary" aria-hidden="true"></i>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.utilization_rate')</div>
                  <div class="kpi-value">{{ number_format($utilizationRate ?? 0, 1) }}%</div>
                </div>
                <i class="fa fa-tasks text-info" aria-hidden="true"></i>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.first_time_fix_rate')</div>
                  <div class="kpi-value">{{ number_format($firstTimeFixRate ?? 0, 1) }}%</div>
                </div>
                <i class="fa fa-wrench text-success" aria-hidden="true"></i>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.comeback_ratio')</div>
                  <div class="kpi-value">{{ number_format($comebackRatio ?? 0, 1) }}%</div>
                </div>
                <i class="fa fa-refresh text-warning" aria-hidden="true"></i>
              </div>
            </div>
          </div>

          <div class="row pf-kpi">
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.avg_repair_time')</div>
                  <div class="kpi-value">{{ number_format($averageRepairTime ?? 0, 1) }}h</div>
                </div>
                <i class="fa fa-clock-o text-primary" aria-hidden="true"></i>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.attendance_rate')</div>
                  <div class="kpi-value">{{ number_format($attendanceRate ?? 0, 1) }}%</div>
                </div>
                <i class="fa fa-calendar-check-o text-info" aria-hidden="true"></i>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.job_quality_index')</div>
                  <div class="kpi-value">{{ number_format($jobQualityIndex ?? 0, 1) }}%</div>
                </div>
                <i class="fa fa-star text-success" aria-hidden="true"></i>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="kpi-card">
                <div>
                  <div class="kpi-title">@lang('timemanagement::lang.efficiency_rate')</div>
                  <div class="kpi-value">{{ number_format($avg_eff, 1) }}%</div>
                </div>
                <i class="fa fa-line-chart text-warning" aria-hidden="true"></i>
              </div>
            </div>
          </div>

          <div class="pf-section-title">@lang('timemanagement::lang.performance_dashboard')</div>

          <div class="table-responsive">
            <table class="table table-striped" id="performance-table">
              <thead>
                <tr>
                  <th>@lang('timemanagement::lang.table_technician')</th>
                  <th style="width:10%">@lang('timemanagement::lang.total_labor_hours_sold')</th>
                  <th style="width:10%">@lang('timemanagement::lang.actual_hours_worked_on_jobs')</th>
                  <th style="width:10%">@lang('timemanagement::lang.table_scheduled_hours')</th>
                  <th style="width:8%">@lang('timemanagement::lang.table_efficiency')</th>
                  <th style="width:8%">@lang('timemanagement::lang.performance_productivity_rate')</th>
                  <th style="width:8%">@lang('timemanagement::lang.performance_utilization_rate')</th>
                  <th style="width:8%">@lang('timemanagement::lang.performance_first_time_fix_rate')</th>
                  <th style="width:8%">@lang('timemanagement::lang.performance_comeback_ratio')</th>
                  <th style="width:8%">@lang('timemanagement::lang.performance_avg_repair_time')</th>
                  <th style="width:8%">@lang('timemanagement::lang.performance_job_quality_index')</th>
                  <!-- <th style="width:8%">Attendance Rate</th> -->
                  <!-- <th style="width:6%">@lang('timemanagement::lang.table_late_arrivals')</th> -->
                </tr>
              </thead>
              <tbody>
                <!-- DataTables will populate this -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
@section('javascript')

<script>
$(document).ready(function() {
    var table = $('#performance-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("timemanagement.performance") }}',
            data: function(d) {
                d.workshop_id = $('#workshop_id').val();
                d.location_id = $('#location_id').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            }
        },
        columns: [
            { data: 'user_name', name: 'user_name' },
            { data: 'allocated_hours', name: 'allocated_hours' },
            { data: 'productive_hours', name: 'productive_hours' },
            { data: 'scheduled_hours', name: 'scheduled_hours' },
            { data: 'efficiency', name: 'efficiency', orderable: false },
            { data: 'productivity_rate', name: 'productivity_rate' },
            { data: 'utilization_rate', name: 'utilization_rate' },
            { data: 'first_time_fix_rate', name: 'first_time_fix_rate' },
            { data: 'comeback_ratio', name: 'comeback_ratio' },
            { data: 'avg_repair_time', name: 'avg_repair_time' },
            // { data: 'attendance_rate', name: 'attendance_rate' },
            { data: 'job_quality_index', name: 'job_quality_index' },
            // { data: 'late_arrivals', name: 'late_arrivals' }
        ],
        pageLength: 10,
       
        order: [[0, 'asc']],
   
      
    });

    function reloadWithFilters() {
        var workshop_id = $('#workshop_id').val();
        var location_id = $('#location_id').val();
        var start_date = $('#start_date').val();
        var end_date = $('#end_date').val();

        var params = [];
        if (workshop_id) params.push('workshop_id=' + encodeURIComponent(workshop_id));
        if (location_id) params.push('location_id=' + encodeURIComponent(location_id));
        if (start_date) params.push('start_date=' + encodeURIComponent(start_date));
        if (end_date) params.push('end_date=' + encodeURIComponent(end_date));

        var queryString = params.length > 0 ? '?' + params.join('&') : '';
        window.location.href = '{{ route("timemanagement.performance") }}' + queryString;
    }

    $('#workshop_id, #location_id').change(function() {
        reloadWithFilters();
    });

    $('.filter-btn').click(function(e) {
        e.preventDefault();
        reloadWithFilters();
    });

    // Date range picker
    var dateRangeOptions = $.extend(true, {}, dateRangeSettings);
    var initialStart = $('#start_date').val() ? moment($('#start_date').val()) : moment();
    var initialEnd = $('#end_date').val() ? moment($('#end_date').val()) : moment();

    dateRangeOptions.startDate = initialStart;
    dateRangeOptions.endDate = initialEnd;

    $('#performance_date_filter').daterangepicker(dateRangeOptions, function(start, end) {
        $('#start_date').val(start.format('YYYY-MM-DD'));
        $('#end_date').val(end.format('YYYY-MM-DD'));
        $('#performance_date_filter span').remove();
        $('#performance_date_filter').append('<span>' + start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format) + '</span>');
        reloadWithFilters();
    });

    $('#performance_date_filter').on('cancel.daterangepicker', function() {
        $('#start_date').val('');
        $('#end_date').val('');
        $('#performance_date_filter span').remove();
        $('#performance_date_filter').append('<span>{{ __("messages.filter_by_date") }}</span>');
        reloadWithFilters();
    });

    if ($('#start_date').val() && $('#end_date').val()) {
        $('#performance_date_filter').append('<span>' + moment($('#start_date').val()).format(moment_date_format) + ' ~ ' + moment($('#end_date').val()).format(moment_date_format) + '</span>');
    } else {
        $('#performance_date_filter').append('<span>{{ __("messages.filter_by_date") }}</span>');
    }
});
</script>
@endsection
