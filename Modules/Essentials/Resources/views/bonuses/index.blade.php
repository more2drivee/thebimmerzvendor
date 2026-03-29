@extends('layouts.app')
@section('title', __('essentials::lang.bonuses'))

@section('content')
@include('essentials::layouts.nav_hrm')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('essentials::lang.bonuses')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid', 'title' => __('essentials::lang.bonuses')])
                @slot('tool')
                    <div class="box-tools">
                        <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right" data-toggle="modal" data-target="#add_bonus_modal">
                            <i class="fas fa-plus"></i> @lang('essentials::lang.add_bonus')
                        </button>
                    </div>
                @endslot
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('bonus_employee_filter', __('essentials::lang.employee') . ':') !!}
                            {!! Form::select('bonus_employee_filter', $employees, request('employee_id'), ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="emp_bonuses_table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>@lang('essentials::lang.employee')</th>
                                <th>@lang('essentials::lang.description')</th>
                                <th>@lang('essentials::lang.amount_type')</th>
                                <th>@lang('sale.amount')</th>
                                <th>@lang('essentials::lang.apply_on_payroll')</th>
                                <th>@lang('essentials::lang.start_date')</th>
                                <th>@lang('essentials::lang.end_date')</th>
                                <th>@lang('essentials::lang.status')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

{{-- Add Bonus Modal --}}
<div class="modal fade" id="add_bonus_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['id' => 'add_bonus_form', 'method' => 'POST']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('essentials::lang.add_bonus')</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('bonus_user_id', __('essentials::lang.employee') . ':*') !!}
                    {!! Form::select('user_id', $employees, request('employee_id'), ['class' => 'form-control select2', 'required', 'style' => 'width:100%', 'id' => 'bonus_user_id']); !!}
                </div>
                <div class="form-group">
                    {!! Form::label('bonus_description', __('essentials::lang.description') . ':*') !!}
                    {!! Form::text('description', null, ['class' => 'form-control', 'required', 'id' => 'bonus_description']); !!}
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('bonus_amount', __('sale.amount') . ':*') !!}
                            {!! Form::text('amount', null, ['class' => 'form-control input_number', 'required', 'id' => 'bonus_amount']); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('bonus_amount_type', __('essentials::lang.amount_type') . ':') !!}
                            {!! Form::select('amount_type', ['fixed' => __('lang_v1.fixed'), 'percent' => __('lang_v1.percentage')], 'fixed', ['class' => 'form-control', 'id' => 'bonus_amount_type']); !!}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {!! Form::label('bonus_apply_on', __('essentials::lang.apply_on_payroll') . ':') !!}
                    {!! Form::select('apply_on', ['next_payroll' => __('essentials::lang.next_payroll'), 'after_next' => __('essentials::lang.after_next'), 'every_payroll' => __('essentials::lang.every_payroll')], 'next_payroll', ['class' => 'form-control', 'id' => 'bonus_apply_on']); !!}
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
                    {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 2, 'id' => 'bonus_note']); !!}
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
    var emp_bonuses_table = $('#emp_bonuses_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'getBonusesData'])}}",
            data: function(d) { d.employee_id = $('#bonus_employee_filter').val(); }
        },
        columns: [
            { data: 'employee_name', name: 'employee_name' },
            { data: 'description', name: 'essentials_employee_bonuses.description' },
            { data: 'amount_type', name: 'essentials_employee_bonuses.amount_type' },
            { data: 'amount', name: 'essentials_employee_bonuses.amount' },
            { data: 'apply_on', name: 'essentials_employee_bonuses.apply_on' },
            { data: 'start_date', name: 'essentials_employee_bonuses.start_date' },
            { data: 'end_date', name: 'essentials_employee_bonuses.end_date' },
            { data: 'status', name: 'essentials_employee_bonuses.status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
    });
    $(document).on('change', '#bonus_employee_filter', function() { emp_bonuses_table.ajax.reload(); });

    var _preEmployeeId = '{{ request('employee_id') }}';
    if (_preEmployeeId) {
        emp_bonuses_table.ajax.reload();
        $('#add_bonus_modal').modal('show');
        $('#add_bonus_modal').on('shown.bs.modal.pre', function() {
            $('#bonus_user_id').val(_preEmployeeId).trigger('change');
            $(this).off('shown.bs.modal.pre');
        });
    }

    $('#add_bonus_modal').on('shown.bs.modal', function() {
        $('#bonus_start_date, #bonus_end_date').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        $('#add_bonus_modal .select2').select2();
    });

    $(document).on('submit', '#add_bonus_form', function(e) {
        e.preventDefault();
        $.ajax({
            method: 'POST',
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'storeBonus'])}}",
            data: $(this).serialize(),
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                    emp_bonuses_table.ajax.reload();
                    $('#add_bonus_form')[0].reset();
                    $('#add_bonus_modal').modal('hide');
                } else { toastr.error(result.msg); }
            },
        });
    });

    $(document).on('click', '.cancel-bonus', function() {
        var href = $(this).data('href');
        $.ajax({ method: 'POST', url: href, dataType: 'json', success: function(result) {
            if (result.success) { toastr.success(result.msg); emp_bonuses_table.ajax.reload(); }
            else { toastr.error(result.msg); }
        }});
    });
    $(document).on('click', '.delete-bonus', function() {
        var href = $(this).data('href');
        swal({ title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true }).then(function(willDelete) {
            if (willDelete) {
                $.ajax({ method: 'DELETE', url: href, dataType: 'json', success: function(result) {
                    if (result.success) { toastr.success(result.msg); emp_bonuses_table.ajax.reload(); }
                    else { toastr.error(result.msg); }
                }});
            }
        });
    });
});
</script>
@endsection
