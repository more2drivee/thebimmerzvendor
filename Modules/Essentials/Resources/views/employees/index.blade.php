@extends('layouts.app')
@section('title', __('essentials::lang.employees'))

@section('content')
@include('essentials::layouts.nav_hrm')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('essentials::lang.employees')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#employees_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fas fa-users" aria-hidden="true"></i> @lang('essentials::lang.employees')
                        </a>
                    </li>
                    <li>
                        <a href="#attendance_history_tab" data-toggle="tab">
                            <i class="fas fa-check-square" aria-hidden="true"></i> @lang('essentials::lang.attendance')
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Tab 1: Employees List --}}
                    <div class="tab-pane active" id="employees_tab">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('emp_department_filter', __('essentials::lang.department') . ':') !!}
                                    {!! Form::select('emp_department_filter', $departments, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'emp_department_filter']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('emp_designation_filter', __('essentials::lang.designation') . ':') !!}
                                    {!! Form::select('emp_designation_filter', $designations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'emp_designation_filter']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('emp_location_filter', __('essentials::lang.location_site') . ':') !!}
                                    {!! Form::select('emp_location_filter', $locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'emp_location_filter']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <a href="{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'create'])}}" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full">
                                        <i class="fas fa-plus"></i> @lang('essentials::lang.add_employee')
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="employees_table" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>@lang('essentials::lang.image')</th>
                                        <th>@lang('essentials::lang.employee')</th>
                                        <th>@lang('lang_v1.email')</th>
                                        <th>@lang('lang_v1.mobile_number')</th>
                                        <th>@lang('essentials::lang.department')</th>
                                        <th>@lang('essentials::lang.designation')</th>
                                        <th>@lang('essentials::lang.location_site')</th>
                                        <th>@lang('essentials::lang.salary')</th>
                                        <th>@lang('messages.action')</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>

                    {{-- Tab 2: Attendance History --}}
                    <div class="tab-pane" id="attendance_history_tab">
                        {{-- Filters Row --}}
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    {!! Form::label('att_employee_filter', __('essentials::lang.employee') . ':') !!}
                                    {!! Form::select('att_employee_filter', $employees, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'att_employee_filter']); !!}
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    {!! Form::label('att_dept_filter', __('essentials::lang.department') . ':') !!}
                                    {!! Form::select('att_dept_filter', $departments, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'att_dept_filter']); !!}
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    {!! Form::label('att_desig_filter', __('essentials::lang.designation') . ':') !!}
                                    {!! Form::select('att_desig_filter', $designations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'att_desig_filter']); !!}
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    {!! Form::label('att_location_filter', __('essentials::lang.location_site') . ':') !!}
                                    {!! Form::select('att_location_filter', $locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'att_location_filter']); !!}
                                </div>
                            </div>
                            {{-- Date range (list view only) --}}
                            <div class="col-md-2" id="att_date_range_wrap">
                                <div class="form-group">
                                    {!! Form::label('att_date_range', __('report.date_range') . ':') !!}
                                    {!! Form::text('att_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly', 'id' => 'att_date_range']); !!}
                                </div>
                            </div>
                            {{-- Month picker (calendar view only) --}}
                            <div class="col-md-3" id="att_month_wrap" style="display:none;">
                                <div class="form-group">
                                    {!! Form::label('att_calendar_month', __('essentials::lang.month_year') . ':') !!}
                                    <input type="month" id="att_calendar_month" class="form-control" value="{{ now()->format('Y-m') }}">
                                </div>
                            </div>
                            {{-- Toggle Buttons --}}
                            <div class="col-md-6 text-right" style="padding-top:25px;">
                                <div class="btn-group" role="group">
                                    <button type="button" id="att_view_list" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-l-full tw-rounded-r-none" style="border-right:1px solid rgba(255,255,255,0.3);">
                                        <i class="fas fa-list"></i> @lang('report.list')
                                    </button>
                                    <button type="button" id="att_view_calendar" class="tw-dw-btn tw-bg-white tw-font-bold tw-text-indigo-600 tw-border tw-border-indigo-500 tw-rounded-r-full tw-rounded-l-none">
                                        <i class="fas fa-table"></i> @lang('lang_v1.calendar')
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- List / DataTable View --}}
                        <div id="att_list_view">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="emp_attendance_table" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th>@lang('lang_v1.date')</th>
                                            <th>@lang('essentials::lang.employee')</th>
                                            <th>@lang('essentials::lang.clock_in')</th>
                                            <th>@lang('essentials::lang.clock_out')</th>
                                            <th>@lang('essentials::lang.work_duration')</th>
                                            <th>@lang('essentials::lang.ip_address')</th>
                                            <th>@lang('essentials::lang.shift')</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        {{-- Calendar / Grid View --}}
                        <div id="att_calendar_view" style="display:none;">
                            <div class="text-center" id="att_calendar_loading" style="padding:30px;">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                            </div>
                            <div class="table-responsive" id="att_calendar_table_wrap" style="display:none;">
                                <table class="table table-bordered" id="att_calendar_table" style="width:100%; font-size:12px;">
                                    <thead id="att_calendar_thead"></thead>
                                    <tbody id="att_calendar_tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</section>

{{-- Employee View Modal --}}
<div class="modal fade" id="employee_view_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('essentials::lang.employee') @lang('messages.details')</h4>
            </div>
            <div class="modal-body" id="employee_view_content">
                <div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

{{-- Employee Action Modal --}}
<div class="modal fade" id="employee_action_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="employee_action_modal_title"></h4>
            </div>
            <div class="modal-body" id="employee_action_content">
                <div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade view_modal" tabindex="-1" role="dialog"></div>

{{-- Add Attendance Modal --}}
<div class="modal fade" id="add_attendance_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['id' => 'add_attendance_form', 'method' => 'POST']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('essentials::lang.add_attendance')</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="att_modal_user_id_hidden">
                <div class="form-group">
                    {!! Form::label('att_modal_user_id', __('essentials::lang.employee') . ':*') !!}
                    {!! Form::select('user_id_disabled', $employees, null, ['class' => 'form-control select2', 'disabled', 'style' => 'width:100%', 'id' => 'att_modal_user_id']); !!}
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('att_clock_in_date', __('essentials::lang.clock_in') . ' ' . __('lang_v1.date') . ':*') !!}
                            {!! Form::text('clock_in_date', @format_date('now'), ['class' => 'form-control', 'required', 'readonly', 'id' => 'att_clock_in_date']); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('att_clock_in_time', __('essentials::lang.clock_in') . ' ' . __('essentials::lang.time') . ':*') !!}
                            <input type="time" name="clock_in_time" id="att_clock_in_time" class="form-control" required value="08:00">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('att_clock_out_date', __('essentials::lang.clock_out') . ' ' . __('lang_v1.date') . ':') !!}
                            {!! Form::text('clock_out_date', null, ['class' => 'form-control', 'readonly', 'id' => 'att_clock_out_date']); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('att_clock_out_time', __('essentials::lang.clock_out') . ' ' . __('essentials::lang.time') . ':') !!}
                            <input type="time" name="clock_out_time" id="att_clock_out_time" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('att_shift_id', __('essentials::lang.shift') . ':') !!}
                    {!! Form::select('essentials_shift_id', $shifts, null, ['class' => 'form-control', 'id' => 'att_shift_id', 'placeholder' => __('lang_v1.none')]); !!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

{{-- Add Leave Modal --}}
<div class="modal fade" id="add_leave_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['id' => 'add_leave_form', 'method' => 'POST']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="add_leave_modal_title">@lang('essentials::lang.add_leave')</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="leave_modal_user_id_hidden">
                <div class="form-group">
                    {!! Form::label('leave_modal_user_id', __('essentials::lang.employee') . ':*') !!}
                    {!! Form::select('user_id_disabled', $employees, null, ['class' => 'form-control select2', 'disabled', 'style' => 'width:100%', 'id' => 'leave_modal_user_id']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('leave_modal_type_id', __('essentials::lang.leave_type') . ':*') !!}
                    {!! Form::select('essentials_leave_type_id', $leave_types, null, ['class' => 'form-control', 'required', 'id' => 'leave_modal_type_id', 'placeholder' => __('messages.please_select')]); !!}
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('leave_modal_start_date', __('essentials::lang.start_date') . ':*') !!}
                            {!! Form::text('start_date', null, ['class' => 'form-control', 'required', 'readonly', 'id' => 'leave_modal_start_date']); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('leave_modal_end_date', __('essentials::lang.end_date') . ':*') !!}
                            {!! Form::text('end_date', null, ['class' => 'form-control', 'required', 'readonly', 'id' => 'leave_modal_end_date']); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('leave_modal_status', __('essentials::lang.status') . ':') !!}
                    {!! Form::select('status', ['pending' => __('essentials::lang.pending'), 'approved' => __('essentials::lang.approved'), 'rejected' => __('essentials::lang.rejected')], 'pending', ['class' => 'form-control', 'id' => 'leave_modal_status']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('leave_modal_reason', __('essentials::lang.reason') . ':') !!}
                    {!! Form::textarea('reason', null, ['class' => 'form-control', 'rows' => 2, 'id' => 'leave_modal_reason']); !!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

{{-- Add Warning Modal (nested from action modal) --}}
<div class="modal fade" id="add_warning_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['id' => 'add_warning_form', 'method' => 'POST']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('essentials::lang.add_warning')</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="warning_user_id_hidden">
                <div class="form-group">
                    {!! Form::label('warning_user_id', __('essentials::lang.employee') . ':*') !!}
                    {!! Form::select('user_id_disabled', $employees, null, ['class' => 'form-control select2', 'disabled', 'style' => 'width:100%', 'id' => 'warning_user_id']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('warning_type', __('essentials::lang.warning_type') . ':*') !!}
                    {!! Form::select('warning_type', ['verbal' => __('essentials::lang.warning_verbal'), 'written' => __('essentials::lang.warning_written'), 'final' => __('essentials::lang.warning_final')], null, ['class' => 'form-control', 'required']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('warning_date', __('essentials::lang.warning_date') . ':') !!}
                    {!! Form::text('warning_date', @format_date('now'), ['class' => 'form-control', 'readonly', 'id' => 'warning_date_picker']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('warning_note', __('essentials::lang.warning_note') . ':') !!}
                    {!! Form::textarea('warning_note', null, ['class' => 'form-control', 'rows' => 3]); !!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

{{-- Add Bonus Modal (nested from action modal) --}}
<div class="modal fade" id="add_bonus_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['id' => 'add_bonus_form', 'method' => 'POST']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('essentials::lang.add_bonus')</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="bonus_user_id_hidden">
                <div class="form-group">
                    {!! Form::label('bonus_user_id', __('essentials::lang.employee') . ':*') !!}
                    {!! Form::select('user_id_disabled', $employees, null, ['class' => 'form-control select2', 'disabled', 'style' => 'width:100%', 'id' => 'bonus_user_id']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('bonus_description', __('essentials::lang.description') . ':*') !!}
                    {!! Form::text('description', null, ['class' => 'form-control', 'required']); !!}
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('bonus_amount', __('sale.amount') . ':*') !!}
                            {!! Form::text('amount', null, ['class' => 'form-control input_number', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('bonus_amount_type', __('essentials::lang.amount_type') . ':') !!}
                            {!! Form::select('amount_type', ['fixed' => __('lang_v1.fixed'), 'percent' => __('lang_v1.percentage')], 'fixed', ['class' => 'form-control']); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('bonus_apply_on', __('essentials::lang.apply_on_payroll') . ':') !!}
                    {!! Form::select('apply_on', ['next_payroll' => __('essentials::lang.next_payroll'), 'after_next' => __('essentials::lang.after_next'), 'every_payroll' => __('essentials::lang.every_payroll')], 'next_payroll', ['class' => 'form-control']); !!}
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('bonus_start_date', __('essentials::lang.start_date') . ':') !!}
                            {!! Form::text('start_date', null, ['class' => 'form-control', 'readonly', 'id' => 'bonus_start_date']); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('bonus_end_date', __('essentials::lang.end_date') . ':') !!}
                            {!! Form::text('end_date', null, ['class' => 'form-control', 'readonly', 'id' => 'bonus_end_date']); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('bonus_note', __('essentials::lang.note') . ':') !!}
                    {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 2]); !!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

{{-- Add Deduction Modal (nested from action modal) --}}
<div class="modal fade" id="add_deduction_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['id' => 'add_deduction_form', 'method' => 'POST']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('essentials::lang.add_deduction')</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="ded_user_id_hidden">
                <div class="form-group">
                    {!! Form::label('ded_user_id', __('essentials::lang.employee') . ':*') !!}
                    {!! Form::select('user_id_disabled', $employees, null, ['class' => 'form-control select2', 'disabled', 'style' => 'width:100%', 'id' => 'ded_user_id']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('ded_description', __('essentials::lang.description') . ':*') !!}
                    {!! Form::text('description', null, ['class' => 'form-control', 'required']); !!}
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('ded_amount', __('sale.amount') . ':*') !!}
                            {!! Form::text('amount', null, ['class' => 'form-control input_number', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('ded_amount_type', __('essentials::lang.amount_type') . ':') !!}
                            {!! Form::select('amount_type', ['fixed' => __('lang_v1.fixed'), 'percent' => __('lang_v1.percentage')], 'fixed', ['class' => 'form-control']); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('ded_apply_on', __('essentials::lang.apply_on_payroll') . ':') !!}
                    {!! Form::select('apply_on', ['next_payroll' => __('essentials::lang.next_payroll'), 'after_next' => __('essentials::lang.after_next'), 'every_payroll' => __('essentials::lang.every_payroll')], 'next_payroll', ['class' => 'form-control']); !!}
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('ded_start_date', __('essentials::lang.start_date') . ':') !!}
                            {!! Form::text('start_date', null, ['class' => 'form-control', 'readonly', 'id' => 'ded_start_date']); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('ded_end_date', __('essentials::lang.end_date') . ':') !!}
                            {!! Form::text('end_date', null, ['class' => 'form-control', 'readonly', 'id' => 'ded_end_date']); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('ded_note', __('essentials::lang.note') . ':') !!}
                    {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 2]); !!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

{{-- Add Advance Modal (nested from action modal) --}}
<div class="modal fade" id="add_advance_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['id' => 'add_advance_form', 'method' => 'POST']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('essentials::lang.add_advance')</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="adv_user_id_hidden">
                <div class="form-group">
                    {!! Form::label('adv_user_id', __('essentials::lang.employee') . ':*') !!}
                    {!! Form::select('user_id_disabled', $employees, null, ['class' => 'form-control select2', 'disabled', 'style' => 'width:100%', 'id' => 'adv_user_id']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('adv_amount', __('sale.amount') . ':*') !!}
                    {!! Form::text('amount', null, ['class' => 'form-control input_number', 'required']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('adv_reason', __('essentials::lang.reason') . ':') !!}
                    {!! Form::textarea('reason', null, ['class' => 'form-control', 'rows' => 2]); !!}
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('adv_request_date', __('essentials::lang.request_date') . ':') !!}
                            {!! Form::text('request_date', @format_date('now'), ['class' => 'form-control', 'readonly', 'id' => 'adv_request_date']); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('adv_deduct_from', __('essentials::lang.deduct_from_month') . ':') !!}
                            <input type="month" name="deduct_from_payroll" id="adv_deduct_from" class="form-control" value="{{ now()->format('Y-m') }}">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('adv_note', __('essentials::lang.note') . ':') !!}
                    {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 2]); !!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    // ===== Hash-based scroll (from navbar links) =====
    var hash = window.location.hash;
    if (hash) {
        var target = $(hash);
        if (target.length) {
            $('html, body').animate({ scrollTop: target.offset().top - 70 }, 400);
        } else {
            // fallback: try Bootstrap tab (Employees/Attendance)
            var tabLink = $('a[href="' + hash + '"]');
            if (tabLink.length) tabLink.tab('show');
        }
    }

    // ===== Employee Action Dropdown: navigate to section =====
    $(document).on('click', '.emp-action-link', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        var tab = $(this).data('tab');

        if (tab === 'attendance_history_tab') {
            $('a[href="#attendance_history_tab"]').tab('show');
            $('#att_employee_filter').val(userId).trigger('change');
        }
    });

    // ===== View Employee Modal =====
    $(document).on('click', '.view-employee-modal', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        $('#employee_view_content').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        $('#employee_view_modal').modal('show');
        
        $.ajax({
            url: '/hrm/employees/' + userId,
            method: 'GET',
            dataType: 'html',
            success: function(result) {
                $('#employee_view_content').html(result);
            },
            error: function() {
                $('#employee_view_content').html('<div class="alert alert-danger">Failed to load employee data.</div>');
            }
        });
    });

    // ===== Employee Action Modal =====
    $(document).on('click', '.emp-action-modal', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        var actionType = $(this).data('action-type');
        
        var titleMap = {
            'attendance': '@lang("essentials::lang.attendance")',
            'absence': '@lang("essentials::lang.absent")',
            'leave': '@lang("essentials::lang.leave")',
            'warnings': '@lang("essentials::lang.warnings")',
            'bonuses': '@lang("essentials::lang.bonuses")',
            'deductions': '@lang("essentials::lang.deductions")',
            'payment': '@lang("essentials::lang.payment_history")',
            'advances': '@lang("essentials::lang.salary_advance")'
        };
        
        _actionModalUserId   = userId;
        _actionModalType     = actionType;
        _actionModalUserName = userName;
        $('#employee_action_modal_title').text(titleMap[actionType] + ' - ' + userName);
        $('#employee_action_content').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        $('#employee_action_modal').modal('show');
        
        $.ajax({
            url: '/hrm/employees/' + userId + '/modal/' + actionType,
            method: 'GET',
            dataType: 'html',
            success: function(result) {
                $('#employee_action_content').html(result);
            },
            error: function() {
                $('#employee_action_content').html('<div class="alert alert-danger">Failed to load data.</div>');
            }
        });
    });

    // ===== Nested-modal helper: track what the action modal was showing =====
    var _actionModalUserId   = null;
    var _actionModalType     = null;
    var _actionModalUserName = null;

    function openAddModal(addModalId, prepFn) {
        var parentOpen = $('#employee_action_modal').hasClass('in');
        if (parentOpen) {
            $('#employee_action_modal').modal('hide');
            $('#employee_action_modal').one('hidden.bs.modal', function() {
                prepFn();
                $(addModalId).modal('show');
                $(addModalId).one('hidden.bs.modal', function() {
                    if (_actionModalUserId && _actionModalType) {
                        $('#employee_action_modal_title').text((window._actionTitleMap[_actionModalType] || '') + ' - ' + (_actionModalUserName || ''));
                        $('#employee_action_content').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
                        $('#employee_action_modal').modal('show');
                        $.ajax({
                            url: '/hrm/employees/' + _actionModalUserId + '/modal/' + _actionModalType,
                            method: 'GET', dataType: 'html',
                            success: function(r) { $('#employee_action_content').html(r); }
                        });
                    }
                });
            });
        } else {
            prepFn();
            $(addModalId).modal('show');
        }
    }

    window._actionTitleMap = {
        'attendance': '@lang("essentials::lang.attendance")',
        'absence':    '@lang("essentials::lang.absent")',
        'leave':      '@lang("essentials::lang.leave")',
        'warnings':   '@lang("essentials::lang.warnings")',
        'bonuses':    '@lang("essentials::lang.bonuses")',
        'deductions': '@lang("essentials::lang.deductions")',
        'payment':    '@lang("essentials::lang.payment_history")',
        'advances':   '@lang("essentials::lang.salary_advance")'
    };

    // ===== Add Leave from Modal =====
    $(document).on('click', '.add-leave-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        openAddModal('#add_leave_modal', function() {
            $('#leave_modal_user_id').val(userId).trigger('change');
            $('#leave_modal_user_id_hidden').val(userId);
        });
    });

    // ===== Add Attendance from Modal =====
    $(document).on('click', '.add-attendance-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        openAddModal('#add_attendance_modal', function() {
            $('#att_modal_user_id').val(userId).trigger('change');
            $('#att_modal_user_id_hidden').val(userId);
        });
    });

    // ===== Add Warning from Action Modal =====
    $(document).on('click', '.add-warning-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        openAddModal('#add_warning_modal', function() {
            $('#warning_user_id').val(userId).trigger('change');
            $('#warning_user_id_hidden').val(userId);
        });
    });
    $('#add_warning_modal').on('shown.bs.modal', function() {
        $('#warning_date_picker').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        $('#add_warning_modal .select2').select2();
    });
    $(document).on('submit', '#add_warning_form', function(e) {
        e.preventDefault();
        $.ajax({
            method: 'POST',
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'storeWarning'])}}",
            data: $(this).serialize(), dataType: 'json',
            success: function(result) {
                if (result.success) { toastr.success(result.msg); $('#add_warning_modal').modal('hide'); $('#add_warning_form')[0].reset(); }
                else { toastr.error(result.msg); }
            },
        });
    });

    // ===== Add Bonus from Action Modal =====
    $(document).on('click', '.add-bonus-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        openAddModal('#add_bonus_modal', function() {
            $('#bonus_user_id').val(userId).trigger('change');
            $('#bonus_user_id_hidden').val(userId);
        });
    });
    $('#add_bonus_modal').on('shown.bs.modal', function() {
        $('#bonus_start_date, #bonus_end_date').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        $('#add_bonus_modal .select2').select2();
    });
    $(document).on('submit', '#add_bonus_form', function(e) {
        e.preventDefault();
        $.ajax({
            method: 'POST',
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'storeBonus'])}}",
            data: $(this).serialize(), dataType: 'json',
            success: function(result) {
                if (result.success) { toastr.success(result.msg); $('#add_bonus_modal').modal('hide'); $('#add_bonus_form')[0].reset(); }
                else { toastr.error(result.msg); }
            },
        });
    });

    // ===== Add Deduction from Action Modal =====
    $(document).on('click', '.add-deduction-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        openAddModal('#add_deduction_modal', function() {
            $('#ded_user_id').val(userId).trigger('change');
            $('#ded_user_id_hidden').val(userId);
        });
    });
    $('#add_deduction_modal').on('shown.bs.modal', function() {
        $('#ded_start_date, #ded_end_date').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        $('#add_deduction_modal .select2').select2();
    });
    $(document).on('submit', '#add_deduction_form', function(e) {
        e.preventDefault();
        $.ajax({
            method: 'POST',
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'storeDeduction'])}}",
            data: $(this).serialize(), dataType: 'json',
            success: function(result) {
                if (result.success) { toastr.success(result.msg); $('#add_deduction_modal').modal('hide'); $('#add_deduction_form')[0].reset(); }
                else { toastr.error(result.msg); }
            },
        });
    });

    // ===== Add Advance from Action Modal =====
    $(document).on('click', '.add-advance-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        openAddModal('#add_advance_modal', function() {
            $('#adv_user_id').val(userId).trigger('change');
            $('#adv_user_id_hidden').val(userId);
        });
    });
    $('#add_advance_modal').on('shown.bs.modal', function() {
        $('#adv_request_date').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        $('#add_advance_modal .select2').select2();
    });
    $(document).on('submit', '#add_advance_form', function(e) {
        e.preventDefault();
        $.ajax({
            method: 'POST',
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'storeAdvance'])}}",
            data: $(this).serialize(), dataType: 'json',
            success: function(result) {
                if (result.success) { toastr.success(result.msg); $('#add_advance_modal').modal('hide'); $('#add_advance_form')[0].reset(); }
                else { toastr.error(result.msg); }
            },
        });
    });

    // ===== Tab 1: Employees DataTable =====
    var employees_table = $('#employees_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'getEmployeesData'])}}",
            data: function(d) {
                d.department_id  = $('#emp_department_filter').val();
                d.designation_id = $('#emp_designation_filter').val();
                d.location_id    = $('#emp_location_filter').val();
            }
        },
        columns: [
            { data: 'image', name: 'image', orderable: false, searchable: false },
            { data: 'full_name', name: 'full_name' },
            { data: 'email', name: 'users.email' },
            { data: 'contact_number', name: 'users.contact_number' },
            { data: 'department_name', name: 'dept.name' },
            { data: 'designation_name', name: 'desig.name' },
            { data: 'location_name', name: 'bl.name' },
            { data: 'essentials_salary', name: 'users.essentials_salary' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
    });

    $('#emp_department_filter, #emp_designation_filter, #emp_location_filter').on('change', function() {
        employees_table.ajax.reload();
    });

    // ===== Tab 2: Attendance DataTable + Calendar Toggle =====
    var emp_attendance_table;
    var att_current_view = 'list';
    var att_lang_employee  = "{{ __('essentials::lang.employee') }}";
    var att_lang_clock_in  = "{{ __('essentials::lang.clock_in') }}";
    var att_lang_clock_out = "{{ __('essentials::lang.clock_out') }}";

    function loadAttendanceCalendar() {
        var month  = $('#att_calendar_month').val();
        var emp_id = $('#att_employee_filter').val();
        var dept_id = $('#att_dept_filter').val();
        var desig_id = $('#att_desig_filter').val();
        var loc_id = $('#att_location_filter').val();
        $('#att_calendar_loading').show();
        $('#att_calendar_table_wrap').hide();
        $.ajax({
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'getAttendanceCalendar'])}}",
            method: 'GET',
            data: { month: month, employee_id: emp_id, department_id: dept_id, designation_id: desig_id, location_id: loc_id },
            dataType: 'json',
            success: function(res) {
                if (!res.success) return;
                var days = res.days;
                var rows = res.rows;

                // Build header: #, Employee, Department, Designation, Location, then per-day group (date label + 3 sub-cols)
                var thead = '<tr style="background:#3c5fa3; color:#fff; white-space:nowrap;">';
                thead += '<th rowspan="2" style="vertical-align:middle; text-align:center; background:#3c5fa3; color:#fff;">#</th>';
                thead += '<th rowspan="2" style="vertical-align:middle; background:#3c5fa3; color:#fff;">' + att_lang_employee + '</th>';
                thead += '<th rowspan="2" style="vertical-align:middle; background:#3c5fa3; color:#fff;">{{ __('essentials::lang.department') }}</th>';
                thead += '<th rowspan="2" style="vertical-align:middle; background:#3c5fa3; color:#fff;">{{ __('essentials::lang.designation') }}</th>';
                thead += '<th rowspan="2" style="vertical-align:middle; background:#3c5fa3; color:#fff;">{{ __('essentials::lang.location_site') }}</th>';
                $.each(days, function(i, day) {
                    var d  = moment(day);
                    var isWeekend = (d.day() === 5 || d.day() === 6);
                    var bg = isWeekend ? '#1e3a8a' : '#3c5fa3';
                    thead += '<th colspan="3" style="text-align:center; background:' + bg + '; color:#fff; border-left:2px solid #fff; padding:4px 2px;">'
                           + d.format('ddd') + '<br>' + d.format('MM/DD')
                           + '</th>';
                });
                thead += '</tr>';
                // Sub-header row
                thead += '<tr style="background:#4a72c4; color:#fff; font-size:10px;">';
                $.each(days, function(i, day) {
                    var d  = moment(day);
                    var isWeekend = (d.day() === 5 || d.day() === 6);
                    var bg = isWeekend ? '#1e3a8a' : '#4a72c4';
                    thead += '<th style="text-align:center; background:' + bg + '; color:#fff; padding:2px; border-left:2px solid #fff;">' + att_lang_clock_in + '</th>';
                    thead += '<th style="text-align:center; background:' + bg + '; color:#fff; padding:2px;">' + att_lang_clock_out + '</th>';
                    thead += '<th style="text-align:center; background:' + bg + '; color:#fff; padding:2px;">Hrs</th>';
                });
                thead += '</tr>';
                $('#att_calendar_thead').html(thead);

                // Build body rows
                var tbody = '';
                $.each(rows, function(idx, row) {
                    tbody += '<tr>';
                    tbody += '<td style="text-align:center; font-weight:bold;">' + (idx + 1) + '</td>';
                    tbody += '<td style="white-space:nowrap; font-weight:600;">' + row.name + '</td>';
                    tbody += '<td style="white-space:nowrap;">' + (row.department || '-') + '</td>';
                    tbody += '<td style="white-space:nowrap;">' + (row.designation || '-') + '</td>';
                    tbody += '<td style="white-space:nowrap;">' + (row.location || '-') + '</td>';
                    $.each(days, function(i, day) {
                        var d  = moment(day);
                        var isWeekend = (d.day() === 5 || d.day() === 6);
                        var bg = isWeekend ? '#f0f4ff' : '';
                        var cell = row.days[day];
                        if (cell) {
                            tbody += '<td style="text-align:center; background:' + bg + '; color:#1a7a2e; padding:2px; border-left:2px solid #ddd;">' + cell['in'] + '</td>';
                            tbody += '<td style="text-align:center; background:' + bg + '; color:#c0392b; padding:2px;">' + cell['out'] + '</td>';
                            tbody += '<td style="text-align:center; background:' + bg + '; color:#555; padding:2px; font-size:11px;">' + cell['hours'] + '</td>';
                        } else {
                            var dashBg = isWeekend ? '#f0f4ff' : '#fafafa';
                            tbody += '<td style="text-align:center; color:#ccc; background:' + dashBg + '; border-left:2px solid #ddd;">-</td>';
                            tbody += '<td style="text-align:center; color:#ccc; background:' + dashBg + ';">-</td>';
                            tbody += '<td style="text-align:center; color:#ccc; background:' + dashBg + ';">-</td>';
                        }
                    });
                    tbody += '</tr>';
                });
                $('#att_calendar_tbody').html(tbody);
                $('#att_calendar_loading').hide();
                $('#att_calendar_table_wrap').show();
            },
            error: function() {
                $('#att_calendar_loading').hide();
                toastr.error('Failed to load calendar data.');
            }
        });
    }

    function switchAttView(view) {
        att_current_view = view;
        if (view === 'list') {
            $('#att_list_view').show();
            $('#att_calendar_view').hide();
            $('#att_date_range_wrap').show();
            $('#att_month_wrap').hide();
            // Active/inactive button styling
            $('#att_view_list').removeClass('tw-bg-white tw-text-indigo-600 tw-border tw-border-indigo-500')
                               .addClass('tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-text-white tw-border-none');
            $('#att_view_calendar').removeClass('tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-text-white tw-border-none')
                                   .addClass('tw-bg-white tw-text-indigo-600 tw-border tw-border-indigo-500');
        } else {
            $('#att_list_view').hide();
            $('#att_calendar_view').show();
            $('#att_date_range_wrap').hide();
            $('#att_month_wrap').show();
            $('#att_view_calendar').removeClass('tw-bg-white tw-text-indigo-600 tw-border tw-border-indigo-500')
                                   .addClass('tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-text-white tw-border-none');
            $('#att_view_list').removeClass('tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-text-white tw-border-none')
                               .addClass('tw-bg-white tw-text-indigo-600 tw-border tw-border-indigo-500');
            loadAttendanceCalendar();
        }
    }

    $('#att_view_list').on('click', function() { switchAttView('list'); });
    $('#att_view_calendar').on('click', function() { switchAttView('calendar'); });

    $('a[href="#attendance_history_tab"]').on('shown.bs.tab', function() {
        if (!emp_attendance_table) {
            emp_attendance_table = $('#emp_attendance_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'getAttendanceData'])}}",
                    data: function(d) {
                        d.employee_id = $('#att_employee_filter').val();
                        d.department_id = $('#att_dept_filter').val();
                        d.designation_id = $('#att_desig_filter').val();
                        d.location_id = $('#att_location_filter').val();
                        if ($('#att_date_range').val()) {
                            d.start_date = $('#att_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            d.end_date = $('#att_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        }
                    }
                },
                columns: [
                    { data: 'date', name: 'clock_in_time' },
                    { data: 'employee_name', name: 'employee_name' },
                    { data: 'clock_in', name: 'clock_in', orderable: false, searchable: false },
                    { data: 'clock_out', name: 'clock_out', orderable: false, searchable: false },
                    { data: 'work_duration', name: 'work_duration', orderable: false, searchable: false },
                    { data: 'ip_address', name: 'ip_address' },
                    { data: 'shift_name', name: 'es.name' },
                ],
            });
            initDateRange('#att_date_range', function() { emp_attendance_table.ajax.reload(); });
        } else {
            if (att_current_view === 'list') {
                emp_attendance_table.ajax.reload();
            } else {
                loadAttendanceCalendar();
            }
        }
    });
    $(document).on('change', '#att_employee_filter', function() {
        if (att_current_view === 'list') {
            if (emp_attendance_table) emp_attendance_table.ajax.reload();
        } else {
            loadAttendanceCalendar();
        }
    });
    $(document).on('change', '#att_dept_filter, #att_desig_filter, #att_location_filter', function() {
        if (att_current_view === 'list') {
            if (emp_attendance_table) emp_attendance_table.ajax.reload();
        } else {
            loadAttendanceCalendar();
        }
    });
    $(document).on('change', '#att_calendar_month', function() {
        if (att_current_view === 'calendar') loadAttendanceCalendar();
    });

    // ===== Date Range Helper =====
    function initDateRange(selector, callback) {
        $(selector).daterangepicker(dateRangeSettings, function(start, end) {
            $(selector).val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        });
        $(selector).on('cancel.daterangepicker', function() {
            $(selector).val('');
            callback();
        });
        $(selector).on('apply.daterangepicker', function() { callback(); });
    }

    // ===== Attendance modal date pickers =====
    $('#add_attendance_modal').on('shown.bs.modal', function() {
        if (!$('#att_clock_in_date').data('daterangepicker')) {
            $('#att_clock_in_date').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        }
        if (!$('#att_clock_out_date').data('daterangepicker')) {
            $('#att_clock_out_date').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        }
        $('#add_attendance_modal .select2').select2();
    });

    // ===== Leave modal date pickers =====
    $('#add_leave_modal').on('shown.bs.modal', function() {
        if (!$('#leave_modal_start_date').data('daterangepicker')) {
            $('#leave_modal_start_date').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        }
        if (!$('#leave_modal_end_date').data('daterangepicker')) {
            $('#leave_modal_end_date').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        }
        $('#add_leave_modal .select2').select2();
    });

    // ===== Attendance Form Submit =====
    $(document).on('submit', '#add_attendance_form', function(e) {
        e.preventDefault();
        var data = $(this).serialize();
        $.ajax({
            method: 'POST',
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'storeAttendance'])}}",
            data: data,
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                    if (emp_attendance_table) emp_attendance_table.ajax.reload();
                    $('#add_attendance_form')[0].reset();
                    $('#add_attendance_modal').modal('hide');
                } else { toastr.error(result.msg); }
            },
        });
    });

    // ===== Leave Form Submit =====
    $(document).on('submit', '#add_leave_form', function(e) {
        e.preventDefault();
        var data = $(this).serialize();
        $.ajax({
            method: 'POST',
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'storeLeave'])}}",
            data: data,
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                    $('#add_leave_form')[0].reset();
                    $('#add_leave_modal').modal('hide');
                } else { toastr.error(result.msg); }
            },
        });
    });
});
</script>
@endsection
