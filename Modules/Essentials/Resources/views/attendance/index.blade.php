@extends('layouts.app')
@section('title', __('essentials::lang.attendance'))

@section('content')
@include('essentials::layouts.nav_hrm')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('essentials::lang.attendance')
    </h1>
</section>
<!-- Main content -->
<section class="content">
    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    @if(!empty($notification['msg']))
                        {{$notification['msg']}}
                    @elseif(session('notification.msg'))
                        {{ session('notification.msg') }}
                    @endif
                </div>
            </div>  
        </div>     
    @endif
    @if($is_employee_allowed)
        <div class="row">
            <div class="col-md-12 text-center">
                <button 
                    type="button" 
                    class="btn btn-app bg-blue clock_in_btn
                        @if(!empty($clock_in))
                            hide
                        @endif
                    "
                    data-type="clock_in"
                    >
                    <i class="fas fa-arrow-circle-down"></i> @lang('essentials::lang.clock_in')
                </button>
            &nbsp;&nbsp;&nbsp;
                <button 
                    type="button" 
                    class="btn btn-app bg-yellow clock_out_btn
                        @if(empty($clock_in))
                            hide
                        @endif
                    "  
                    data-type="clock_out"
                    >
                    <i class="fas fa-hourglass-half fa-spin"></i> @lang('essentials::lang.clock_out')
                </button>
                @if(!empty($clock_in))
                    <br>
                    <small class="text-muted">@lang('essentials::lang.clocked_in_at'): {{@format_datetime($clock_in->clock_in_time)}}</small>
                @endif
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    @can('essentials.crud_all_attendance')
                        <li class="active">
                            <a href="#shifts_tab" data-toggle="tab" aria-expanded="true">
                                <i class="fas fa-user-clock" aria-hidden="true"></i>
                                @lang('essentials::lang.shifts')
                                @show_tooltip(__('essentials::lang.shift_datatable_tooltip'))
                            </a>
                        </li>
                    @endcan
                    <li @if(!auth()->user()->can('essentials.crud_all_attendance')) class="active" @endif>
                        <a href="#attendance_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-check-square" aria-hidden="true"></i> @lang( 'essentials::lang.all_attendance' )</a>
                    </li>
                    @can('essentials.crud_all_attendance')
                    <li>
                        <a href="#attendance_by_shift_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-user-check" aria-hidden="true"></i> @lang('essentials::lang.attendance_by_shift')</a>
                    </li>
                    <li>
                        <a href="#attendance_by_date_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-calendar" aria-hidden="true"></i> @lang('essentials::lang.attendance_by_date')</a>
                    </li>
                    <li>
                        <a href="#attendance_calendar_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-calendar-check" aria-hidden="true"></i> @lang('essentials::lang.attendance_calendar')</a>
                    </li>
                    <li>
                        <a href="#shift_calendar_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-calendar-week" aria-hidden="true"></i> @lang('essentials::lang.shift_calendar')</a>
                    </li>
                    <li>
                        <a href="#import_attendance_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-download" aria-hidden="true"></i> @lang('essentials::lang.import_attendance')</a>
                    </li>
                    @endcan
                </ul>
                <div class="tab-content">
                    @can('essentials.crud_all_attendance')
                        <div class="tab-pane active" id="shifts_tab">
                            <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right"
                                data-toggle="modal" data-target="#shift_modal">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg> @lang('messages.add')
                            </button>
                            <br>
                            <br>
                            <br>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="shift_table">
                                    <thead>
                                        <tr>
                                            <th>@lang( 'lang_v1.name' )</th>
                                            <th>@lang( 'essentials::lang.shift_type' )</th>
                                            <th>@lang( 'restaurant.start_time' )</th>
                                            <th>@lang( 'restaurant.end_time' )</th>
                                            <th>@lang( 'essentials::lang.holiday' )</th>
                                            <th>@lang( 'messages.action' )</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    @endcan
                    <div class="tab-pane @if(!auth()->user()->can('essentials.crud_all_attendance')) active @endif" id="attendance_tab">
                        <div class="row">
                            @can('essentials.crud_all_attendance')
                                <div class="col-md-2">
                                    <div class="form-group">
                                        {!! Form::label('att_location_id', __('essentials::lang.filter_by_location') . ':') !!}
                                        <select id="att_location_id" name="att_location_id" class="form-control select2" style="width:100%;">
                                            <option value="">-- @lang('lang_v1.all') --</option>
                                            @foreach($locations as $loc_id => $loc_name)
                                                <option value="{{ $loc_id }}">{{ $loc_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        {!! Form::label('att_shift_id', __('essentials::lang.shift') . ':') !!}
                                        <select id="att_shift_id" name="att_shift_id" class="form-control select2" style="width:100%;" disabled>
                                            <option value="">-- @lang('lang_v1.all') --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        {!! Form::label('employee_id', __('essentials::lang.employee') . ':') !!}
                                        {!! Form::select('employee_id', $employees, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                    </div>
                                </div>
                            @endcan
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('date_range', __('report.date_range') . ':') !!}
                                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                                </div>
                            </div>
                            @can('essentials.crud_all_attendance')
                            <div class="col-md-3 spacer">
                                <div class="pull-right" style="display:flex;gap:6px;flex-wrap:wrap;padding-top:24px;">
                                    <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal"
                                        data-href="{{action([\Modules\Essentials\Http\Controllers\AttendanceController::class, 'create'])}}" data-container="#attendance_modal">
                                        <i class="fas fa-plus"></i> @lang( 'essentials::lang.add_latest_attendance' )
                                    </button>
                                    <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-green-600 tw-to-teal-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full" id="bulk_attendance_btn">
                                        <i class="fas fa-users"></i> @lang('essentials::lang.bulk_add')
                                    </button>
                                    <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-orange-500 tw-to-yellow-400 tw-font-bold tw-text-white tw-border-none tw-rounded-full" id="attend_all_btn">
                                        <i class="fas fa-check-double"></i> @lang('essentials::lang.attend_all')
                                    </button>
                                </div>
                            </div>
                            @endcan
                        </div>
                        <div id="user_attendance_summary" class="hide">
                            <h3>
                                <strong>@lang('essentials::lang.total_work_hours'):</strong>
                                <span id="total_work_hours"></span>
                            </h3>
                        </div>
                        <br><br>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="attendance_table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Username / FP-ID</th>
                                        <th>@lang( 'lang_v1.date' )</th>
                                        <th>@lang('essentials::lang.shift')</th>
                                        <th>On Duty</th>
                                        <th>Off Duty</th>
                                        <th>@lang('essentials::lang.clock_in')</th>
                                        <th>@lang('essentials::lang.clock_out')</th>
                                        <th>@lang('essentials::lang.department')</th>
                                        <th>@lang('essentials::lang.designation')</th>
                                        @can('essentials.crud_all_attendance')
                                            <th>@lang( 'messages.action' )</th>
                                        @endcan
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    
                    <div class="tab-pane" id="attendance_by_shift_tab">
                        @include('essentials::attendance.attendance_by_shift')
                    </div>
                    <div class="tab-pane" id="attendance_by_date_tab">
                        @include('essentials::attendance.attendance_by_date')
                    </div>

                    {{-- Attendance Calendar Tab --}}
                    <div class="tab-pane" id="attendance_calendar_tab">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('cal_employee_id', __('essentials::lang.employee') . ':') !!}
                                    {!! Form::select('cal_employee_id', $employees, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]); !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('cal_month', __('essentials::lang.month_year') . ':') !!}
                                    <input type="month" name="cal_month" id="cal_month" class="form-control" value="{{ \Carbon::now()->format('Y-m') }}">
                                </div>
                            </div>
                            <div class="col-md-3" style="padding-top:25px;">
                                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="load_attendance_calendar">
                                    <i class="fas fa-search"></i> @lang('essentials::lang.load_calendar')
                                </button>
                            </div>
                        </div>
                        <div id="attendance_calendar_legend" class="hide" style="margin-bottom:10px;">
                            <span class="label label-success">@lang('essentials::lang.present')</span>
                            <span class="label label-danger">@lang('essentials::lang.absent')</span>
                            <span class="label label-warning">@lang('essentials::lang.leave')</span>
                            <span class="label label-info">@lang('essentials::lang.holiday')</span>
                            <span class="label label-default">@lang('essentials::lang.day_off')</span>
                            <span class="label" style="background:#c0392b;color:#fff;">@lang('essentials::lang.leave_deducted')</span>
                        </div>
                        <div id="attendance_calendar_container"></div>
                    </div>

                    {{-- Shift Calendar Tab --}}
                    <div class="tab-pane" id="shift_calendar_tab">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('shift_cal_employee_id', __('essentials::lang.employee') . ':') !!}
                                    {!! Form::select('shift_cal_employee_id', $employees, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]); !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('shift_cal_month', __('essentials::lang.month_year') . ':') !!}
                                    <input type="month" name="shift_cal_month" id="shift_cal_month" class="form-control" value="{{ \Carbon::now()->format('Y-m') }}">
                                </div>
                            </div>
                            <div class="col-md-3" style="padding-top:25px;">
                                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="load_shift_calendar">
                                    <i class="fas fa-search"></i> @lang('essentials::lang.load_calendar')
                                </button>
                            </div>
                        </div>
                        <div id="shift_calendar_container"></div>
                    </div>

                    @can('essentials.crud_all_attendance')
                        <div class="tab-pane" id="import_attendance_tab">
                            @include('essentials::attendance.import_attendance')
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    
</section>
<!-- /.content -->
<div class="modal fade" id="attendance_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel"></div>
<div class="modal fade" id="edit_attendance_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel"></div>
<div class="modal fade" id="user_shift_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel"></div>
<div class="modal fade" id="edit_shift_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel"></div>
<div class="modal fade" id="shift_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    @include('essentials::attendance.shift_modal')
</div>

{{-- Bulk Attendance Modal --}}
<div class="modal fade" id="bulk_attendance_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-users"></i> @lang('essentials::lang.bulk_add') @lang('essentials::lang.attendance')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('bulk_att_date', __('lang_v1.date') . ':*') !!}
                            {!! Form::text('bulk_att_date', \Carbon\Carbon::today()->format(session('business.date_format', 'd/m/Y')), ['class' => 'form-control', 'readonly', 'id' => 'bulk_att_date']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('bulk_clock_in', __('essentials::lang.clock_in') . ':') !!}
                            <input type="time" id="bulk_clock_in" class="form-control" value="08:00">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('bulk_clock_out', __('essentials::lang.clock_out') . ':') !!}
                            <input type="time" id="bulk_clock_out" class="form-control" value="17:00">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('bulk_employees', __('essentials::lang.employees') . ':*') !!}
                            <select id="bulk_employees" class="form-control select2" multiple="multiple" style="width:100%;">
                            </select>
                            <small class="text-muted">@lang('essentials::lang.select_employees_to_mark_attendance')</small>
                        </div>
                    </div>
                </div>
                <div id="bulk_att_status" class="hide"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="submit_bulk_attendance">
                    <i class="fas fa-save"></i> @lang('messages.save')
                </button>
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

{{-- Attend All Modal --}}
<div class="modal fade" id="attend_all_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-check-double"></i> @lang('essentials::lang.attend_all')</h4>
            </div>
            <div class="modal-body">
                <p class="text-muted">@lang('essentials::lang.attend_all_desc')</p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('attend_all_date', __('lang_v1.date') . ':*') !!}
                            {!! Form::text('attend_all_date', \Carbon\Carbon::today()->format(session('business.date_format', 'd/m/Y')), ['class' => 'form-control', 'readonly', 'id' => 'attend_all_date']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('attend_all_clock_in', __('essentials::lang.clock_in') . ':') !!}
                            <input type="time" id="attend_all_clock_in" class="form-control" value="08:00">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('attend_all_clock_out', __('essentials::lang.clock_out') . ':') !!}
                            <input type="time" id="attend_all_clock_out" class="form-control" value="17:00">
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <span id="attend_all_count_msg">@lang('essentials::lang.attend_all_confirm')</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-warning tw-text-white" id="submit_attend_all">
                    <i class="fas fa-check-double"></i> @lang('essentials::lang.attend_all')
                </button>
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('css')
    <style>
        /* Fix Select2 dropdown visibility */
        .select2-container--default .select2-results__option {
            color: #333;
            background-color: #fff;
            padding: 6px;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #0078d4;
            color: #fff;
        }
        .select2-dropdown {
            background-color: #fff;
            border: 1px solid #aaa;
        }
    </style>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            attendance_table = $('#attendance_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    "url": "{{action([\Modules\Essentials\Http\Controllers\AttendanceController::class, 'index'])}}",
                    "data" : function(d) {
                        if ($('#employee_id').length) {
                            d.employee_id = $('#employee_id').val();
                        }
                        if ($('#att_location_id').length) {
                            d.location_id = $('#att_location_id').val();
                        }
                        if ($('#att_shift_id').length) {
                            d.shift_id = $('#att_shift_id').val();
                        }
                        if($('#date_range').val()) {
                            var start = $('#date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                    }
                },
                columns: [
                    { data: 'user_fp',    name: 'u.username',  orderable: true, searchable: true },
                    { data: 'date',       name: 'clock_in_time' },
                    { data: 'shift_name', name: 'es.name', orderable: false, searchable: false },
                    { data: 'on_duty',    name: 'on_duty',  orderable: false, searchable: false },
                    { data: 'off_duty',   name: 'off_duty', orderable: false, searchable: false },
                    { data: 'clock_in',   name: 'clock_in', orderable: false, searchable: false },
                    { data: 'clock_out',  name: 'clock_out', orderable: false, searchable: false },
                    { data: 'dept_name',  name: 'dept_cat.name', orderable: false, searchable: false },
                    { data: 'desig_name', name: 'desig_cat.name', orderable: false, searchable: false },
                    @can('essentials.crud_all_attendance')
                        { data: 'action', name: 'action', orderable: false, searchable: false},
                    @endcan
                ],
            });

            $('#date_range').daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                }
            );
            $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#date_range').val('');
                attendance_table.ajax.reload();
            });

            // ── Location → Shift cascade for attendance tab ──────────────────
            $(document).on('change', '#att_location_id', function() {
                var loc_id = $(this).val();
                var $shiftSel = $('#att_shift_id');
                $shiftSel.empty().append('<option value="">-- @lang("lang_v1.all") --</option>');
                if (!loc_id) {
                    $shiftSel.prop('disabled', true).trigger('change.select2');
                    attendance_table.ajax.reload();
                    return;
                }
                $.ajax({
                    url: '{{ action([\Modules\Essentials\Http\Controllers\AttendanceController::class, "getShiftsByLocation"]) }}',
                    data: { location_id: loc_id },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success && res.shifts.length) {
                            $.each(res.shifts, function(i, s) {
                                $shiftSel.append(
                                    $('<option>').val(s.id).text(
                                        s.name + ' (' + (s.start_time ? s.start_time.substr(0,5) : '') + ' – ' + (s.end_time ? s.end_time.substr(0,5) : '') + ')'
                                    )
                                );
                            });
                            $shiftSel.prop('disabled', false);
                        } else {
                            $shiftSel.prop('disabled', true);
                        }
                        if ($.fn.select2) { $shiftSel.trigger('change.select2'); }
                        attendance_table.ajax.reload();
                    },
                });
            });

            $(document).on('change', '#att_shift_id', function() {
                attendance_table.ajax.reload();
            });

            $(document).on('change', '#employee_id, #date_range', function() {
                attendance_table.ajax.reload();
            });

            $(document).on('submit', 'form#attendance_form', function(e) {
                e.preventDefault();
                if($(this).valid()) {
                    $(this).find('button[type="submit"]').attr('disabled', true);
                    var data = $(this).serialize();
                    $.ajax({
                        method: $(this).attr('method'),
                        url: $(this).attr('action'),
                        dataType: 'json',
                        data: data,
                        success: function(result) {
                            if (result.success == true) {
                                $('div#attendance_modal').modal('hide');
                                $('div#edit_attendance_modal').modal('hide');
                                toastr.success(result.msg);
                                attendance_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });

            $(document).on( 'change', '#employee_id, #date_range', function() {
                get_attendance_summary();
            });

            @if(!auth()->user()->can('essentials.crud_all_attendance'))
                get_attendance_summary();
            @endif

            shift_table = $('#shift_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    "url": "{{action([\Modules\Essentials\Http\Controllers\ShiftController::class, 'index'])}}",
                },
                columnDefs: [
                    {
                        targets: 4,
                        orderable: false,
                        searchable: false,
                    },
                ],
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'type', name: 'type' },
                    { data: 'start_time', name: 'start_time'},
                    { data: 'end_time', name: 'end_time' },
                    { data: 'holidays', name: 'holidays'},
                    { data: 'action', name: 'action' },
                ],
            });

            $('#shift_modal, #edit_shift_modal').on('shown.bs.modal', function(e) {
                $('form#add_shift_form').validate();
                $('#shift_modal #start_time, #shift_modal #end_time, #edit_shift_modal #start_time, #edit_shift_modal #end_time').datetimepicker({
                    format: moment_time_format,
                    ignoreReadonly: true,
                });
                $('#shift_modal .select2, #edit_shift_modal .select2').select2();

                if ($('select#shift_type').val() == 'fixed_shift') {
                    $('div.time_div').show();
                } else if ($('select#shift_type').val() == 'flexible_shift') {
                    $('div.time_div').hide();
                }

                $('select#shift_type').change(function() {
                    var shift_type = $(this).val();
                    if (shift_type == 'fixed_shift') {
                        $('div.time_div').fadeIn();
                    } else if (shift_type == 'flexible_shift') {
                        $('div.time_div').fadeOut();
                    }
                });

                //toggle auto clockout
                if($('#is_allowed_auto_clockout').is(':checked')) {
                    $("div.enable_auto_clock_out_time").show();
                } else {
                    $("div.enable_auto_clock_out_time").hide(); 
                }

                $('#is_allowed_auto_clockout').on('change', function(){
                    if ($(this).is(':checked')) {
                        $("div.enable_auto_clock_out_time").show();
                    } else {
                       $("div.enable_auto_clock_out_time").hide(); 
                    }
                });
                
                $('#shift_modal #auto_clockout_time, #edit_shift_modal #auto_clockout_time').datetimepicker({
                    format: moment_time_format,
                    stepping: 30,
                    ignoreReadonly: true,
                });
            });
            $('#shift_modal, #edit_shift_modal').on('hidden.bs.modal', function(e) {
                $('#shift_modal #start_time').data("DateTimePicker").destroy();
                $('#shift_modal #end_time').data("DateTimePicker").destroy();
                $('#add_shift_form')[0].reset();
                $('#add_shift_form').find('button[type="submit"]').attr('disabled', false);

                $('#is_allowed_auto_clockout').attr('checked', false);
                $('#auto_clockout_time').data("DateTimePicker").destroy();
                $("div.enable_auto_clock_out_time").hide(); 
            });
            $('#user_shift_modal').on('shown.bs.modal', function(e) {
                $('#user_shift_modal').find('.date_picker').each( function(){
                    $(this).datetimepicker({
                        format: moment_date_format,
                        ignoreReadonly: true,
                    });
                });
            });

            @can('essentials.crud_all_attendance')
                get_attendance_by_shift();
                $('#attendance_by_shift_date_filter').datetimepicker({
                    format: moment_date_format,
                    ignoreReadonly: true,
                });
                var attendanceDateRangeSettings = dateRangeSettings;
                attendanceDateRangeSettings.startDate = moment().subtract(6, 'days');
                attendanceDateRangeSettings.endDate = moment();
                $('#attendance_by_date_filter').daterangepicker(
                    dateRangeSettings,
                    function (start, end) {
                        $('#attendance_by_date_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    }
                );
                get_attendance_by_date();
                $(document).on('change', '#attendance_by_date_filter', function(){
                    get_attendance_by_date();
                });
            @endcan

            $('a[href="#attendance_tab"]').click(function(){
                attendance_table.ajax.reload();
            });
            $('a[href="#attendance_by_shift_tab"]').click(function(){
                get_attendance_by_shift();
            });
            $('a[href="#attendance_by_date_tab"]').click(function(){
                get_attendance_by_date();
            });
        });

        $(document).on('click', 'button.delete-attendance', function() {
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    var href = $(this).data('href');
                    var data = $(this).serialize();
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        data: data,
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                attendance_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });
        $('#edit_attendance_modal').on('hidden.bs.modal', function(e) {
            $('#edit_attendance_modal #clock_in_time').data("DateTimePicker").destroy();
            $('#edit_attendance_modal #clock_out_time').data("DateTimePicker").destroy();
        });

        $('#attendance_modal').on('shown.bs.modal', function(e) {
            $('#attendance_modal .select2').select2();
        });
        $('#edit_attendance_modal').on('shown.bs.modal', function(e) {
            $('#edit_attendance_modal .select2').select2();
            $('#edit_attendance_modal #clock_in_time, #edit_attendance_modal #clock_out_time').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });

            validate_clockin_clock_out = {
                url: '/hrm/validate-clock-in-clock-out',
                type: 'post',
                data: {
                    user_ids: function() {
                        return $('#employees').val();
                    },
                    clock_in_time: function() {
                        return $('#clock_in_time').val();
                    },
                    clock_out_time: function() {
                        return $('#clock_out_time').val();
                    },
                    attendance_id: function() {
                        if($('form#attendance_form #attendance_id').length) {
                           return $('form#attendance_form #attendance_id').val();
                        } else {
                            return '';
                        }
                    },
                },
            };

            $('form#attendance_form').validate({
                rules: {
                    clock_in_time: {
                        remote: validate_clockin_clock_out,
                    },
                    clock_out_time: {
                        remote: validate_clockin_clock_out,
                    },
                },
                messages: {
                    clock_in_time: {
                        remote: "{{__('essentials::lang.clock_in_clock_out_validation_msg')}}",
                    },
                    clock_out_time: {
                        remote: "{{__('essentials::lang.clock_in_clock_out_validation_msg')}}",
                    },
                },
            });
        });

        function get_attendance_summary() {
            $('#user_attendance_summary').addClass('hide');
            var user_id = $('#employee_id').length ? $('#employee_id').val() : '';
            
            var start = $('#date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
            var end = $('#date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
            $.ajax({
                url: '{{action([\Modules\Essentials\Http\Controllers\AttendanceController::class, 'getUserAttendanceSummary'])}}?user_id=' + user_id + '&start_date=' + start + '&end_date=' + end ,
                dataType: 'html',
                success: function(response) {
                    $('#total_work_hours').html(response);
                    $('#user_attendance_summary').removeClass('hide');
                },
            });
        }

    //Set mindate for clockout time greater than clockin time
    $('#attendance_modal').on('dp.change', '#clock_in_time', function(){
        if ($('#clock_out_time').data("DateTimePicker")) {
            $('#clock_out_time').data("DateTimePicker").options({minDate: $(this).data("DateTimePicker").date()});
            $('#clock_out_time').data("DateTimePicker").clear();
        }
    });

    $(document).on('submit', 'form#add_shift_form', function(e) {
        e.preventDefault();
        $(this).find('button[type="submit"]').attr('disabled', true);
        var data = $(this).serialize();

        $.ajax({
            method: $(this).attr('method'),
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            success: function(result) {
                if (result.success == true) {
                    if ($('div#edit_shift_modal').hasClass('in')) {
                        $('div#edit_shift_modal').modal("hide");
                    } else if ($('div#shift_modal').hasClass('in')) {
                        $('div#shift_modal').modal('hide');    
                    }
                    toastr.success(result.msg);
                    shift_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    $(document).on('submit', 'form#add_user_shift_form', function(e) {
        e.preventDefault();
        $(this).find('button[type="submit"]').attr('disabled', true);
        var data = $(this).serialize();

        $.ajax({
            method: $(this).attr('method'),
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            success: function(result) {
                if (result.success == true) {
                    $('div#user_shift_modal').modal('hide');
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
                $('form#add_user_shift_form').find('button[type="submit"]').attr('disabled', false);
            },
        });
    });

    function get_attendance_by_shift() {
        data = {date: $('#attendance_by_shift_date_filter').val()};
        $.ajax({
            url: "{{action([\Modules\Essentials\Http\Controllers\AttendanceController::class, 'getAttendanceByShift'])}}",
            data: data,
            dataType: 'html',
            success: function(result) {
                $('table#attendance_by_shift_table tbody').html(result);
            },
        });
    }
    function get_attendance_by_date() {
        data = {
                start_date: $('#attendance_by_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD'),
                end_date: $('#attendance_by_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD')
            };
        $.ajax({
            url: "{{action([\Modules\Essentials\Http\Controllers\AttendanceController::class, 'getAttendanceByDate'])}}",
            data: data,
            dataType: 'html',
            success: function(result) {
                $('table#attendance_by_date_table tbody').html(result);
            },
        });
    }
    $(document).on('dp.change', '#attendance_by_shift_date_filter', function(){
        get_attendance_by_shift();
    });
    $(document).on('change', '#select_employee', function(e) {
        var user_id = $(this).val();
        var count = 0;
        $('table#employee_attendance_table tbody').find('tr').each( function(){
            if ($(this).data('user_id') == user_id) {
                count++;
            }
        });
        
        if (user_id && count == 0) {
            $.ajax({
                url: "/hrm/get-attendance-row/" + user_id,
                dataType: 'html',
                success: function(result) {
                    $('table#employee_attendance_table tbody').append(result);
                    var tr = $('table#employee_attendance_table tbody tr:last');

                    tr.find('.date_time_picker').each( function(){
                        $(this).datetimepicker({
                            format: moment_date_format + ' ' + moment_time_format,
                            ignoreReadonly: true,
                            maxDate: moment(),
                            widgetPositioning: {
                                horizontal: 'auto',
                                vertical: 'bottom'
                             }
                        });
                        $(this).val('');
                    });
                    $('#select_employee').val('').change();
                },
            });
        }
    });
    $(document).on('click', 'button.remove_attendance_row', function(e) {
        $(this).closest('tr').remove();
    });

    // Attendance Calendar
    $(document).on('click', '#load_attendance_calendar', function() {
        var user_id = $('#cal_employee_id').val();
        var month = $('#cal_month').val();
        if (!user_id || !month) {
            toastr.error('Please select employee and month');
            return;
        }
        var parts = month.split('-');
        var start_date = parts[0] + '-' + parts[1] + '-01';
        var last_day = new Date(parts[0], parts[1], 0).getDate();
        var end_date = parts[0] + '-' + parts[1] + '-' + (last_day < 10 ? '0' + last_day : last_day);

        $('#attendance_calendar_container').html('<p class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></p>');
        $.ajax({
            url: '/hrm/get-attendance-calendar',
            data: { user_id: user_id, start_date: start_date, end_date: end_date },
            dataType: 'json',
            success: function(data) {
                $('#attendance_calendar_legend').removeClass('hide');
                var html = '<div class="table-responsive"><table class="table table-bordered table-condensed" style="font-size:12px;">';
                html += '<thead><tr><th>@lang("lang_v1.date")</th><th>@lang("essentials::lang.day_off")</th><th>@lang("essentials::lang.status")</th><th>@lang("essentials::lang.clock_in_time")</th><th>@lang("essentials::lang.clock_out_time")</th><th>@lang("essentials::lang.hours")</th><th>@lang("essentials::lang.shift")</th></tr></thead><tbody>';
                
                var status_map = {
                    'present': {label: '@lang("essentials::lang.present")', cls: 'label-success', row: ''},
                    'absent': {label: '@lang("essentials::lang.absent")', cls: 'label-danger', row: 'danger'},
                    'leave': {label: '@lang("essentials::lang.leave")', cls: 'label-warning', row: 'warning'},
                    'leave_deducted': {label: '@lang("essentials::lang.leave_deducted")', cls: 'label-danger', row: 'danger'},
                    'holiday': {label: '@lang("essentials::lang.holiday")', cls: 'label-info', row: 'info'},
                    'day_off': {label: '@lang("essentials::lang.day_off")', cls: 'label-default', row: 'active'},
                    'no_shift': {label: '-', cls: 'label-default', row: ''}
                };

                $.each(data, function(i, d) {
                    var s = status_map[d.status] || status_map['no_shift'];
                    html += '<tr class="' + s.row + '">';
                    html += '<td>' + d.date + ' <small>(' + d.day + ')</small></td>';
                    html += '<td><span class="label ' + s.cls + '">' + s.label + '</span></td>';
                    html += '<td>' + s.label + '</td>';
                    html += '<td>' + (d.clock_in ? d.clock_in : '-') + '</td>';
                    html += '<td>' + (d.clock_out ? d.clock_out : '-') + '</td>';
                    html += '<td>' + (d.hours > 0 ? d.hours : '-') + '</td>';
                    html += '<td>' + (d.shift_name ? d.shift_name : '-') + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                $('#attendance_calendar_container').html(html);
            },
            error: function() {
                $('#attendance_calendar_container').html('<p class="text-danger">Error loading calendar data</p>');
            }
        });
    });

    // Shift Calendar
    $(document).on('click', '#load_shift_calendar', function() {
        var user_id = $('#shift_cal_employee_id').val();
        var month = $('#shift_cal_month').val();
        if (!user_id || !month) {
            toastr.error('Please select employee and month');
            return;
        }
        var parts = month.split('-');
        var start_date = parts[0] + '-' + parts[1] + '-01';
        var last_day = new Date(parts[0], parts[1], 0).getDate();
        var end_date = parts[0] + '-' + parts[1] + '-' + (last_day < 10 ? '0' + last_day : last_day);

        $('#shift_calendar_container').html('<p class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></p>');
        $.ajax({
            url: '/hrm/get-shift-calendar',
            data: { user_id: user_id, start_date: start_date, end_date: end_date },
            dataType: 'json',
            success: function(data) {
                var html = '<div class="table-responsive"><table class="table table-bordered table-condensed" style="font-size:12px;">';
                html += '<thead><tr><th>@lang("lang_v1.date")</th><th>@lang("essentials::lang.day_off")</th><th>@lang("essentials::lang.shift")</th><th>@lang("essentials::lang.shift_type")</th><th>@lang("essentials::lang.start_date")</th><th>@lang("essentials::lang.end_date")</th></tr></thead><tbody>';
                
                $.each(data, function(i, d) {
                    var rowClass = '';
                    if (d.is_holiday) rowClass = 'active';
                    else if (d.shift_name) rowClass = '';
                    else rowClass = 'warning';

                    html += '<tr class="' + rowClass + '">';
                    html += '<td>' + d.date + ' <small>(' + d.day + ')</small></td>';
                    html += '<td>' + (d.is_holiday ? '<span class="label label-default">@lang("essentials::lang.day_off")</span>' : (d.shift_name ? '<span class="label label-primary">@lang("essentials::lang.working")</span>' : '<span class="label label-warning">@lang("essentials::lang.no_shift")</span>')) + '</td>';
                    html += '<td>' + (d.shift_name ? d.shift_name : '-') + '</td>';
                    html += '<td>' + (d.shift_type ? d.shift_type : '-') + '</td>';
                    html += '<td>' + (d.start_time ? d.start_time : '-') + '</td>';
                    html += '<td>' + (d.end_time ? d.end_time : '-') + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                $('#shift_calendar_container').html(html);
            },
            error: function() {
                $('#shift_calendar_container').html('<p class="text-danger">Error loading shift calendar data</p>');
            }
        });
    });


    // ===== Bulk Attendance =====
    $('#bulk_attendance_btn').on('click', function() {
        $('#bulk_att_status').addClass('hide').html('');
        $('#bulk_att_date').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        $('#bulk_employees').empty();
        $('#bulk_attendance_modal').modal('show');
    });
    $('#bulk_attendance_modal').on('shown.bs.modal', function() {
        if (!$('#bulk_employees').data('select2')) {
            $('#bulk_employees').select2({
                dropdownParent: $('#bulk_attendance_modal'),
                ajax: {
                    url: '/hrm/employees/search',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term, page: params.page }; },
                    processResults: function(data) {
                        return { results: data.results, pagination: { more: data.more } };
                    },
                    cache: true
                },
                placeholder: '{{ __("lang_v1.please_select") }}',
                minimumInputLength: 0,
            });
        }
    });
    $(document).on('click', '#submit_bulk_attendance', function() {
        var employee_ids = $('#bulk_employees').val();
        var date = $('#bulk_att_date').val();
        var clock_in = $('#bulk_clock_in').val();
        var clock_out = $('#bulk_clock_out').val();
        if (!employee_ids || employee_ids.length === 0 || !date) {
            toastr.error('{{ __("lang_v1.please_select") }}');
            return;
        }
        $(this).attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            method: 'POST',
            url: '/hrm/attendance/bulk',
            data: { employee_ids: employee_ids, date: date, clock_in: clock_in, clock_out: clock_out, _token: $('meta[name="csrf-token"]').attr('content') },
            dataType: 'json',
            success: function(result) {
                $('#submit_bulk_attendance').attr('disabled', false).html('<i class="fas fa-save"></i> {{ __("messages.save") }}');
                if (result.success) {
                    toastr.success(result.msg);
                    $('#bulk_attendance_modal').modal('hide');
                    attendance_table.ajax.reload();
                } else { toastr.error(result.msg); }
            },
        });
    });

    // ===== Attend All =====
    $('#attend_all_btn').on('click', function() {
        $('#attend_all_date').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        $('#attend_all_modal').modal('show');
    });
    $(document).on('click', '#submit_attend_all', function() {
        var date = $('#attend_all_date').val();
        var clock_in = $('#attend_all_clock_in').val();
        var clock_out = $('#attend_all_clock_out').val();
        if (!date) { toastr.error('{{ __("lang_v1.please_select") }}'); return; }
        $(this).attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            method: 'POST',
            url: '/hrm/attendance/attend-all',
            data: { date: date, clock_in: clock_in, clock_out: clock_out, _token: $('meta[name="csrf-token"]').attr('content') },
            dataType: 'json',
            success: function(result) {
                $('#submit_attend_all').attr('disabled', false).html('<i class="fas fa-check-double"></i> {{ __("essentials::lang.attend_all") }}');
                if (result.success) {
                    toastr.success(result.msg);
                    $('#attend_all_modal').modal('hide');
                    attendance_table.ajax.reload();
                } else { toastr.error(result.msg); }
            },
        });
    });

</script>

{{-- ====================================================================
     JavaScript for attendance import (without shift)
==================================================================== --}}
<script type="text/javascript">
$(document).ready(function () {
    // initialize Select2 on attendance tab dropdowns and import template dropdowns
    if ($.fn.select2) {
        $('#att_location_id').select2({ width: '100%' });
        $('#att_shift_id').select2({ width: '100%' });
        $('#tpl_location_id, #tpl_department_id').select2({ width: '100%' });
    }

    // date range helper
    function updateDayCount() {
        var from = $('#tpl_date_from').val();
        var to   = $('#tpl_date_to').val();
        if (!from || !to) { $('#tpl_days_count').text(''); return 0; }
        var d1   = new Date(from), d2 = new Date(to);
        var diff = Math.round((d2 - d1) / 86400000) + 1;
        if (diff < 1) { $('#tpl_date_to').val(from); diff = 1; }
        if (diff > 31) {
            var clamped = new Date(d1);
            clamped.setDate(clamped.getDate() + 30);
            $('#tpl_date_to').val(clamped.toISOString().substring(0, 10));
            diff = 31;
        }
        $('#tpl_days_count').text(diff + ' day(s) selected');
        return diff;
    }
    $('#tpl_date_from, #tpl_date_to').on('change', updateDayCount);

    // Enable/disable download button based on location and clock_in time
    function updateDownloadButtonState() {
        var locId = $('#tpl_location_id').val();
        var ci    = $('#tpl_clock_in').val();
        var btn   = $('#download_template_btn');
        
        if (locId && ci) {
            btn.prop('disabled', false).removeAttr('title');
        } else {
            btn.prop('disabled', true).attr('title', 'Select a location and clock in time first');
        }
    }
    
    $(document).on('change', '#tpl_location_id, #tpl_clock_in', updateDownloadButtonState);
    updateDownloadButtonState(); // Initialize on page load

    // simple form validation
    $('#download_template_form').on('submit', function (e) {
        var locId = $('#tpl_location_id').val();
        var ci    = $('#tpl_clock_in').val();
        if (!locId) {
            e.preventDefault();
            alert('Location is required');
            return false;
        }
        if (!ci) {
            e.preventDefault();
            alert('Clock in time is required');
            return false;
        }
    });

    // init
    updateDayCount();
});
</script>

@endsection
