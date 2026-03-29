@extends('layouts.app')

@php
    $action_url = action([\Modules\Essentials\Http\Controllers\PayrollController::class, 'store']);
    $title = __( 'essentials::lang.add_payroll' );
    $subtitle = __( 'essentials::lang.add_payroll' );
    $submit_btn_text = __( 'messages.save' );
    $group_name = __('essentials::lang.payroll_for_month', ['date' => $month_name . ' ' . $year]);
    if ($action == 'edit') {
        $action_url = action([\Modules\Essentials\Http\Controllers\PayrollController::class, 'getUpdatePayrollGroup']);
        $title = __( 'essentials::lang.edit_payroll' );
        $subtitle = __( 'essentials::lang.edit_payroll' );
        $submit_btn_text = __( 'messages.update' );
    }
@endphp

@section('title', $title)

@section('content')
@include('essentials::layouts.nav_hrm')
<!-- Content Header (Page header) -->
<section class="content-header">
  <h1 class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">{{$subtitle}}</h1>
</section>

<!-- Main content -->
<section class="content">
{!! Form::open(['url' => $action_url, 'method' => 'post', 'id' => 'add_payroll_form' ]) !!}
    {!! Form::hidden('transaction_date', $transaction_date); !!}
    @if($action == 'edit')
        {!! Form::hidden('payroll_group_id', $payroll_group->id); !!}
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h3>
                                {!! $group_name !!}
                            </h3>
                            <small>
                                <b>@lang('business.location')</b> :
                                @if(!empty($location))
                                    {{$location->name}}
                                    {!! Form::hidden('location_id', $location->id); !!}
                                @else
                                    {{__('report.all_locations')}}
                                    {!! Form::hidden('location_id', ''); !!}
                                @endif
                            </small>
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('payroll_group_name', __( 'essentials::lang.payroll_group_name' ) . ':*') !!}
                            {!! Form::text('payroll_group_name', !empty($payroll_group) ? $payroll_group->name : strip_tags($group_name), ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.payroll_group_name' ), 'required']); !!}
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('payroll_group_status', __( 'sale.status' ) . ':*') !!}
                            @show_tooltip(__('essentials::lang.group_status_tooltip'))
                            {!! Form::select('payroll_group_status', ['draft' => __('sale.draft'), 'final' => __('sale.final')], !empty($payroll_group) ? $payroll_group->status : null, ['class' => 'form-control select2', 'required', 'style' => 'width: 100%;', 'placeholder' => __( 'messages.please_select' )]); !!}
                            <p class="help-block text-muted">@lang('essentials::lang.payroll_cant_be_deleted_if_final')</p>
                        </div>
                    </div><br><br>
                    <table class="table" id="payroll_table">
                        <tr>
                            <th>
                                @lang('essentials::lang.employee')
                            </th>
                            <th>
                                @lang('essentials::lang.salary')
                            </th>
                            <th>
                                @lang('essentials::lang.allowances')
                            </th>
                            <th>
                                @lang('essentials::lang.deductions')
                            </th>
                            <th>
                                @lang('essentials::lang.gross_amount')
                            </th>
                        </tr>
                        @foreach($payrolls as $employee => $payroll)
                            @php
                                if ($action != 'edit') {
                                    $amount_per_unit_duration = (double)$payroll['essentials_salary'];
                                    $pay_period = $payroll['essentials_pay_period'] ?? 'month';
                                    $total_work_duration = 1;
                                    $duration_unit = __('lang_v1.month');
                                    if ($pay_period == 'week') {
                                        // Calculate actual weeks in the month
                                        $total_work_duration = 4;
                                        $duration_unit = __('essentials::lang.week');
                                    } elseif ($pay_period == 'day') {
                                        // Use actual days worked from attendance
                                        $total_work_duration = $payroll['total_days_worked'] ?? \Carbon::parse($transaction_date)->daysInMonth;
                                        $duration_unit = __('lang_v1.day');
                                    }
                                    $total = $total_work_duration * $amount_per_unit_duration;
                                } else {
                                    $amount_per_unit_duration = $payroll['essentials_amount_per_unit_duration'];
                                    $total_work_duration = $payroll['essentials_duration'];
                                    $duration_unit = $payroll['essentials_duration_unit'];
                                    $total = $total_work_duration * $amount_per_unit_duration;
                                    $pay_period = 'month';
                                }

                                // Cycle info (display only — no restriction)
                                $already_paid          = (float)($payroll['already_paid_this_cycle'] ?? 0);
                                $prev_payrolls_cycle   = $payroll['previous_payrolls_this_cycle'] ?? collect();
                            @endphp

                            {{-- ══ Normal editable row — no blocking, full salary shown ══ --}}
                            <tr data-id="{{$employee}}">
                                <input type="hidden" name="payrolls[{{$employee}}][expense_for]" value="{{$employee}}">
                                @if($action != 'edit')
                                    <input type="hidden" name="payrolls[{{$employee}}][bonus_ids_to_apply]" value="{{ implode(',', $payroll['bonus_ids_to_apply'] ?? []) }}">
                                    <input type="hidden" name="payrolls[{{$employee}}][deduction_ids_to_apply]" value="{{ implode(',', $payroll['deduction_ids_to_apply'] ?? []) }}">
                                    <input type="hidden" name="payrolls[{{$employee}}][advance_ids_to_deduct]" value="{{ implode(',', $payroll['advance_ids_to_deduct'] ?? []) }}">
                                @endif
                                @if($action == 'edit')
                                    {!! Form::hidden('payrolls['.$employee.'][transaction_id]', $payroll['transaction_id']); !!}
                                @endif
                                <td>
                                    <strong class="text-primary" style="font-size:15px;">{{$payroll['name']}}</strong>

                                    {{-- Previous payrolls this cycle (info only, collapsible) --}}
                                    @if($action != 'edit' && $already_paid > 0)
                                    <div style="margin:6px 0 4px;">
                                        <a href="#prev_payrolls_{{$employee}}" data-toggle="collapse"
                                           style="font-size:12px; text-decoration:none;">
                                            <i class="fa fa-info-circle text-info"></i>
                                            <strong class="text-info">@lang('essentials::lang.already_paid_cycle'):</strong>
                                            <strong>{{ @num_format($already_paid) }}</strong>
                                            &nbsp;<i class="fa fa-chevron-down" style="font-size:10px;"></i>
                                        </a>
                                        <div id="prev_payrolls_{{$employee}}" class="collapse" style="margin-top:4px;">
                                            <table class="table table-condensed table-bordered" style="font-size:11px;margin-bottom:0;">
                                                <thead>
                                                    <tr class="active">
                                                        <th>@lang('messages.date')</th>
                                                        <th>@lang('lang_v1.type')</th>
                                                        <th>@lang('lang_v1.amount')</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($prev_payrolls_cycle as $pp)
                                                    <tr>
                                                        <td>{{ \Carbon::parse($pp->transaction_date)->format('d M') }}</td>
                                                        <td>
                                                            @if($pp->staff_note === '__advance_payout__')
                                                                <span class="label label-warning">@lang('essentials::lang.advance')</span>
                                                            @else
                                                                <span class="label label-success">@lang('essentials::lang.salary')</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ @num_format($pp->final_total) }}</td>
                                                    </tr>
                                                    @endforeach
                                                    <tr class="active">
                                                        <td colspan="2"><strong>@lang('lang_v1.total')</strong></td>
                                                        <td><strong>{{ @num_format($already_paid) }}</strong></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif

                                    <hr style="margin:5px 0;">

                                    {{-- Worker Summary Panel --}}
                                    <div class="panel panel-default" style="margin-bottom:5px;">
                                        <div class="panel-heading" style="padding:5px 10px; cursor:pointer;" data-toggle="collapse" data-target="#summary_{{$employee}}">
                                            <strong><i class="fas fa-chart-bar"></i> @lang('essentials::lang.worker_summary')</strong>
                                            <i class="fas fa-chevron-down pull-right"></i>
                                        </div>
                                        <div id="summary_{{$employee}}" class="panel-collapse collapse in">
                                            <div class="panel-body" style="padding:8px;">
                                                <div class="row">
                                                    <div class="col-xs-6">
                                                        <small class="text-muted">@lang('essentials::lang.expected_working_days')</small><br>
                                                        <strong>{{ $payroll['expected_working_days'] ?? 0 }} @lang('lang_v1.days')</strong>
                                                    </div>
                                                    <div class="col-xs-6">
                                                        <small class="text-muted">@lang('essentials::lang.actual_days_worked')</small><br>
                                                        <strong class="{{ ($payroll['total_days_worked'] ?? 0) < ($payroll['expected_working_days'] ?? 0) ? 'text-danger' : 'text-success' }}">
                                                            {{ $payroll['total_days_worked'] ?? 0 }} @lang('lang_v1.days')
                                                        </strong>
                                                    </div>
                                                </div>
                                                <div class="row" style="margin-top:5px;">
                                                    <div class="col-xs-6">
                                                        <small class="text-muted">@lang('essentials::lang.expected_hours')</small><br>
                                                        <strong>{{ $payroll['expected_shift_hours'] ?? 0 }} @lang('essentials::lang.hours')</strong>
                                                    </div>
                                                    <div class="col-xs-6">
                                                        <small class="text-muted">@lang('essentials::lang.actual_hours_worked')</small><br>
                                                        <strong>{{ $payroll['total_work_duration'] ?? 0 }} @lang('essentials::lang.hours')</strong>
                                                    </div>
                                                </div>
                                                <div class="row" style="margin-top:5px;">
                                                    <div class="col-xs-6">
                                                        <small class="text-muted">@lang('essentials::lang.holidays')</small><br>
                                                        <strong class="text-info">{{ $payroll['holidays_count'] ?? 0 }} @lang('lang_v1.days')</strong>
                                                    </div>
                                                    <div class="col-xs-6">
                                                        <small class="text-muted">@lang('essentials::lang.total_leave_days')</small><br>
                                                        <strong class="text-warning">{{ $payroll['total_leaves'] ?? 0 }} @lang('lang_v1.days')</strong>
                                                    </div>
                                                </div>
                                                <div class="row" style="margin-top:5px;">
                                                    <div class="col-xs-6">
                                                        <small class="text-muted">@lang('essentials::lang.pay_period')</small><br>
                                                        <strong>
                                                            @if($pay_period == 'day')
                                                                <span class="label label-info">@lang('lang_v1.day')</span>
                                                            @elseif($pay_period == 'week')
                                                                <span class="label label-primary">@lang('essentials::lang.week')</span>
                                                            @else
                                                                <span class="label label-success">@lang('lang_v1.month')</span>
                                                            @endif
                                                        </strong>
                                                    </div>
                                                    <div class="col-xs-6">
                                                        <small class="text-muted">@lang('essentials::lang.warnings')</small><br>
                                                        <strong class="{{ ($payroll['warnings_count'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                                            {{ $payroll['warnings_count'] ?? 0 }}
                                                        </strong>
                                                    </div>
                                                </div>
                                                @if(!empty($payroll['leaves_summary']) && $payroll['leaves_summary']['total_deducted_days'] > 0)
                                                <div class="row" style="margin-top:5px;">
                                                    <div class="col-xs-6">
                                                        <small class="text-muted">@lang('essentials::lang.deducted_leave_days')</small><br>
                                                        <strong class="text-danger">{{ $payroll['leaves_summary']['total_deducted_days'] }} @lang('lang_v1.days')</strong>
                                                    </div>
                                                    <div class="col-xs-6">
                                                        <small class="text-muted">@lang('essentials::lang.leave_deduction_total')</small><br>
                                                        <strong class="text-danger">{{ @num_format($payroll['leaves_summary']['total_deduction_amount']) }}</strong>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Attendance Calendar (collapsible) --}}
                                    @if($action != 'edit' && !empty($payroll['attendance_calendar']))
                                    <div class="panel panel-default" style="margin-bottom:5px;">
                                        <div class="panel-heading" style="padding:5px 10px; cursor:pointer;" data-toggle="collapse" data-target="#cal_{{$employee}}">
                                            <strong><i class="fas fa-calendar-alt"></i> @lang('essentials::lang.attendance_calendar')</strong>
                                            <i class="fas fa-chevron-down pull-right"></i>
                                        </div>
                                        <div id="cal_{{$employee}}" class="panel-collapse collapse">
                                            <div class="panel-body" style="padding:4px; overflow-x:auto;">
                                                <table class="table table-bordered table-condensed" style="font-size:11px; margin:0;">
                                                    <thead>
                                                        <tr>
                                                            <th>@lang('lang_v1.date')</th>
                                                            <th>@lang('essentials::lang.status')</th>
                                                            <th>@lang('essentials::lang.hours')</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($payroll['attendance_calendar'] as $cal)
                                                            <tr class="
                                                                @if($cal['status'] == 'present') bg-success
                                                                @elseif($cal['status'] == 'absent') bg-danger
                                                                @elseif($cal['status'] == 'leave') bg-warning
                                                                @elseif($cal['status'] == 'leave_deducted') bg-danger
                                                                @elseif($cal['status'] == 'holiday') bg-info
                                                                @elseif($cal['status'] == 'day_off') bg-default
                                                                @endif
                                                            ">
                                                                <td>{{ $cal['date'] }} <small>({{ $cal['day'] }})</small></td>
                                                                <td>
                                                                    @if($cal['status'] == 'present')
                                                                        <span class="label label-success">@lang('essentials::lang.present')</span>
                                                                    @elseif($cal['status'] == 'absent')
                                                                        <span class="label label-danger">@lang('essentials::lang.absent')</span>
                                                                    @elseif($cal['status'] == 'leave')
                                                                        <span class="label label-warning">@lang('essentials::lang.leave')</span>
                                                                    @elseif($cal['status'] == 'leave_deducted')
                                                                        <span class="label label-danger">@lang('essentials::lang.leave_deducted')</span>
                                                                    @elseif($cal['status'] == 'holiday')
                                                                        <span class="label label-info">@lang('essentials::lang.holiday')</span>
                                                                    @elseif($cal['status'] == 'day_off')
                                                                        <span class="label label-default">@lang('essentials::lang.day_off')</span>
                                                                    @else
                                                                        <span class="label label-default">-</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $cal['hours'] > 0 ? $cal['hours'] : '-' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </td>
                                <td>
                                    {!! Form::label('essentials_duration_'.$employee, __( 'essentials::lang.total_work_duration' ) . ':*') !!}
                                    {!! Form::text('payrolls['.$employee.'][essentials_duration]', $total_work_duration, ['class' => 'form-control input_number essentials_duration', 'placeholder' => __( 'essentials::lang.total_work_duration' ), 'required', 'data-id' => $employee, 'id' => 'essentials_duration_'.$employee]); !!}
                                    <br>

                                    {!! Form::label('essentials_duration_unit_'.$employee, __( 'essentials::lang.duration_unit' ) . ':') !!}
                                    {!! Form::text('payrolls['.$employee.'][essentials_duration_unit]', $duration_unit, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.duration_unit' ), 'data-id' => $employee, 'id' => 'essentials_duration_unit_'.$employee]); !!}

                                    <br>

                                    {!! Form::label('essentials_amount_per_unit_duration_'.$employee, __( 'essentials::lang.amount_per_unit_duartion' ) . ':*') !!}
                                    {!! Form::text('payrolls['.$employee.'][essentials_amount_per_unit_duration]', $amount_per_unit_duration, ['class' => 'form-control input_number essentials_amount_per_unit_duration', 'placeholder' => __( 'essentials::lang.amount_per_unit_duartion' ), 'required', 'data-id' => $employee, 'id' => 'essentials_amount_per_unit_duration_'.$employee]); !!}

                                    <br>
                                    {!! Form::label('total_'.$employee, __( 'sale.total' ) . ':') !!}
                                    {!! Form::text('payrolls['.$employee.'][total]', $total, ['class' => 'form-control input_number total', 'placeholder' => __( 'sale.total' ), 'data-id' => $employee, 'id' => 'total_'.$employee]); !!}
                                </td>
                                <td>
                                    @component('components.widget')
                                        <table class="table table-condenced allowance_table" id="allowance_table_{{$employee}}" data-id="{{$employee}}">
                                            <thead>
                                                <tr>
                                                    <th class="col-md-5">@lang('essentials::lang.description')</th>
                                                    <th class="col-md-3">@lang('essentials::lang.amount_type')</th>
                                                    <th class="col-md-3">@lang('sale.amount')</th>
                                                    <th class="col-md-1">&nbsp;</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $total_allowances = 0;
                                                @endphp
                                                @if(!empty($payroll['allowances']['allowance_names']))
                                                    @foreach($payroll['allowances']['allowance_names'] as $key => $value)
                                                        @include('essentials::payroll.allowance_and_deduction_row', ['add_button' => $loop->index == 0 ? true : false, 'type' => 'allowance', 'name' => $value, 'value' => $payroll['allowances']['allowance_amounts'][$key], 'amount_type' => $payroll['allowances']['allowance_types'][$key],
                                                        'percent' => $payroll['allowances']['allowance_percents'][$key] ])

                                                        @php
                                                            $total_allowances += $payroll['allowances']['allowance_amounts'][$key];
                                                        @endphp
                                                    @endforeach
                                                    {{-- Always render a blank add-button row so the user can append more --}}
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['add_button' => true, 'type' => 'allowance'])
                                                @else
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['add_button' => true, 'type' => 'allowance'])
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['type' => 'allowance'])
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['type' => 'allowance'])
                                                @endif
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="2">@lang('sale.total')</th>
                                                    <td><span id="total_allowances_{{$employee}}" class="display_currency" data-currency_symbol="true">{{$total_allowances}}</span></td>
                                                    <td>&nbsp;</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    @endcomponent
                                </td>
                                <td>
                                    @component('components.widget')
                                        <table class="table table-condenced deductions_table" id="deductions_table_{{$employee}}" data-id="{{$employee}}">
                                            <thead>
                                                <tr>
                                                    <th class="col-md-5">@lang('essentials::lang.description')</th>
                                                    <th class="col-md-3">@lang('essentials::lang.amount_type')</th>
                                                    <th class="col-md-3">@lang('sale.amount')</th>
                                                    <th class="col-md-1">&nbsp;</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $total_deductions = 0;
                                                @endphp
                                                @if(!empty($payroll['deductions']['deduction_names']))
                                                    @foreach($payroll['deductions']['deduction_names'] as $key => $value)
                                                        @include('essentials::payroll.allowance_and_deduction_row', ['add_button' => $loop->index == 0 ? true : false, 'type' => 'deduction', 'name' => $value, 'value' => $payroll['deductions']['deduction_amounts'][$key],
                                                        'amount_type' => $payroll['deductions']['deduction_types'][$key], 'percent' => $payroll['deductions']['deduction_percents'][$key]])

                                                        @php
                                                            $total_deductions += $payroll['deductions']['deduction_amounts'][$key];
                                                        @endphp
                                                    @endforeach
                                                    {{-- Always render a blank add-button row so the user can append more --}}
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['add_button' => true, 'type' => 'deduction'])
                                                @else
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['add_button' => true, 'type' => 'deduction'])
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['type' => 'deduction'])
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['type' => 'deduction'])
                                                @endif
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="2">@lang('sale.total')</th>
                                                    <td><span id="total_deductions_{{$employee}}" class="display_currency" data-currency_symbol="true">{{$total_deductions}}</span></td>
                                                    <td>&nbsp;</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    @endcomponent
                                </td>
                                <td>
                                    <strong>
                                        <span id="gross_amount_text_{{$employee}}">0</span>
                                    </strong>
                                    <br>
                                    {!! Form::hidden('payrolls['.$employee.'][final_total]', 0, ['id' => 'gross_amount_'.$employee, 'class' => 'gross_amount']); !!}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5">
                                    <div class="form-group">
                                        {!! Form::label('note_'.$employee, __( 'brand.note' ) . ':') !!}
                                        {!! Form::textarea('payrolls['.$employee.'][staff_note]', $payroll['staff_note'] ?? null, ['class' => 'form-control', 'placeholder' => __( 'sale.total' ), 'id' => 'note_'.$employee, 'rows' => 3]); !!}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 text-center">
            <div class="form-group m-8 mt-15">
                {!! Form::hidden('total_gross_amount', 0, ['id' => 'total_gross_amount']); !!}
                <label>
                    {!! Form::checkbox('notify_employee', 1, 0 ,
                    [ 'class' => 'input-icheck']); !!} {{ __( 'essentials::lang.notify_employee' ) }}
                </label>
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-lg" id="submit_user_button">
                    {{$submit_btn_text}}
                </button>
            </div>
        </div>
    </div>
{!! Form::close() !!}
@stop
@section('javascript')
@includeIf('essentials::payroll.form_script')
@endsection
