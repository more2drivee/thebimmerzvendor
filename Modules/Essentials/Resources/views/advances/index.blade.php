@extends('layouts.app')
@section('title', __('essentials::lang.salary_advance'))

@section('content')
@include('essentials::layouts.nav_hrm')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('essentials::lang.salary_advance')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid', 'title' => __('essentials::lang.salary_advance')])
                @slot('tool')
                    <div class="box-tools">
                        <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right" data-toggle="modal" data-target="#add_advance_modal">
                            <i class="fas fa-plus"></i> @lang('essentials::lang.add_advance')
                        </button>
                    </div>
                @endslot
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('adv_employee_filter', __('essentials::lang.employee') . ':') !!}
                            {!! Form::select('adv_employee_filter', $employees, request('employee_id'), ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="emp_advances_table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>@lang('essentials::lang.employee')</th>
                                <th>@lang('sale.amount')</th>
                                <th>@lang('essentials::lang.reason')</th>
                                <th>@lang('essentials::lang.request_date')</th>
                                <th>@lang('essentials::lang.deduct_from_month')</th>
                                <th>@lang('essentials::lang.status')</th>
                                <th>@lang('essentials::lang.approved_by')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

{{-- Add Salary Advance Modal --}}
<div class="modal fade" id="add_advance_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open([
                'id'     => 'add_advance_form',
                'method' => 'POST',
                'url'    => action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'storeAdvance']),
            ]) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('essentials::lang.add_advance')</h4>
            </div>
            <div class="modal-body">

                {{-- Employee --}}
                <div class="form-group">
                    {!! Form::label('adv_user_id', __('essentials::lang.employee') . ':*') !!}
                    {!! Form::select('user_id', $employees, request('employee_id'), [
                        'class'       => 'form-control select2',
                        'required'    => 'required',
                        'style'       => 'width:100%',
                        'id'          => 'adv_user_id',
                        'placeholder' => __('messages.please_select'),
                    ]); !!}
                </div>

                {{-- Salary info banner (hidden until employee selected) --}}
                <div id="adv_salary_info" class="alert alert-info" style="display:none; padding:8px 14px; margin-bottom:12px;">
                    <i class="fas fa-info-circle"></i>
                    <span id="adv_salary_info_text"></span>
                </div>

                {{-- Amount --}}
                <div class="form-group">
                    {!! Form::label('adv_amount', __('sale.amount') . ':*') !!}
                    {!! Form::text('amount', null, [
                        'class'       => 'form-control input_number',
                        'required'    => 'required',
                        'id'          => 'adv_amount',
                        'placeholder' => '0.00',
                    ]); !!}
                    <span id="adv_max_note"     class="help-block text-muted"  style="display:none;"></span>
                    <span id="adv_amount_error" class="help-block text-danger" style="display:none;"></span>
                </div>

                {{-- Reason --}}
                <div class="form-group">
                    {!! Form::label('adv_reason', __('essentials::lang.reason') . ':') !!}
                    {!! Form::textarea('reason', null, ['class' => 'form-control', 'rows' => 2, 'id' => 'adv_reason']); !!}
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('adv_request_date', __('essentials::lang.request_date') . ':') !!}
                            {!! Form::text('request_date', @format_date('now'), ['class' => 'form-control', 'readonly' => 'readonly', 'id' => 'adv_request_date']); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('adv_deduct_from', __('essentials::lang.deduct_from_month') . ':') !!}
                            <input type="month" name="deduct_from_payroll" id="adv_deduct_from" class="form-control" value="{{ now()->format('Y-m') }}">
                            <span class="help-block text-muted" style="font-size:11px;">
                                <i class="fas fa-info-circle"></i>
                                @lang('essentials::lang.deduct_from_month'): @lang('essentials::lang.salary_advance') @lang('essentials::lang.deducted').
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    {!! Form::label('adv_note', __('essentials::lang.note') . ':') !!}
                    {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 2, 'id' => 'adv_note']); !!}
                </div>

                {{-- Live summary box --}}
                <div id="adv_summary_box" class="well well-sm" style="display:none; background:#f9f9f9; border-left:4px solid #5b6af0; padding:10px 14px;">
                    <strong>
                        <i class="fas fa-calculator"></i>
                        @lang('essentials::lang.salary_advance') @lang('essentials::lang.summary'):
                    </strong>
                    <table class="table table-condensed" style="margin:6px 0 0; background:transparent;">
                        <tr>
                            <td>@lang('essentials::lang.salary'):</td>
                            <td><strong id="sum_salary">—</strong></td>
                        </tr>
                        <tr>
                            <td>@lang('essentials::lang.salary_advance'):</td>
                            <td><strong id="sum_advance" class="text-warning">—</strong></td>
                        </tr>
                        <tr>
                            <td>@lang('essentials::lang.deductions') (@lang('essentials::lang.deduct_from_month')):</td>
                            <td><strong id="sum_deduction" class="text-danger">—</strong></td>
                        </tr>
                        <tr class="success">
                            <td><strong>@lang('essentials::lang.gross_amount') (@lang('essentials::lang.deduct_from_month')):</strong></td>
                            <td><strong id="sum_net" class="text-success">—</strong></td>
                        </tr>
                    </table>
                    <small class="text-muted">
                        <i class="fas fa-check-circle text-success"></i>
                        @lang('essentials::lang.advance_payroll_created')
                    </small>
                </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="adv_submit_btn">
                    <i class="fas fa-save"></i> @lang('messages.save')
                </button>
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">
                    @lang('messages.close')
                </button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function () {

    /* ── Translations (rendered server-side; no quote conflicts in JS) ── */
    var ADV = {
        employeeSalary:       "{{ addslashes(__('essentials::lang.employee_salary')) }}",
        maxAdvanceNote:       "{{ addslashes(__('essentials::lang.max_advance_note', ['salary' => ':salary'])) }}",
        advanceExceedsSalary: "{{ addslashes(__('essentials::lang.advance_exceeds_salary', ['salary' => ':salary'])) }}",
        notAvailable:         "{{ addslashes(__('lang_v1.not_available') ?? 'N/A') }}",
        somethingWrong:       "{{ addslashes(__('messages.something_went_wrong')) }}",
        saving:               "{{ addslashes(__('messages.saving') ?? 'Saving') }}",
        save:                 "{{ addslashes(__('messages.save')) }}",
        amountGtZero:         "{{ addslashes(__('lang_v1.amount_must_be_greater_than_zero') ?? 'Amount must be greater than zero') }}"
    };

    /* ── DataTable ── */
    var emp_advances_table = $('#emp_advances_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  "{{ action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'getAdvancesData']) }}",
            type: 'GET',
            data: function (d) { d.employee_id = $('#adv_employee_filter').val(); }
        },
        columns: [
            { data: 'employee_name',       name: 'employee_name' },
            { data: 'amount',              name: 'essentials_salary_advances.amount' },
            { data: 'reason',              name: 'essentials_salary_advances.reason' },
            { data: 'request_date',        name: 'essentials_salary_advances.request_date' },
            { data: 'deduct_from_payroll', name: 'essentials_salary_advances.deduct_from_payroll' },
            { data: 'status',              name: 'essentials_salary_advances.status' },
            { data: 'approved_by_name',    name: 'approved_by_name' },
            { data: 'action',              name: 'action', orderable: false, searchable: false },
        ],
    });

    $(document).on('change', '#adv_employee_filter', function () {
        emp_advances_table.ajax.reload();
    });

    /* ── Pre-fill from URL param ── */
    var _preEmployeeId = '{{ request('employee_id') }}';
    if (_preEmployeeId) {
        emp_advances_table.ajax.reload();
        $('#add_advance_modal').modal('show');
        $('#add_advance_modal').one('shown.bs.modal', function () {
            $('#adv_user_id').val(_preEmployeeId).trigger('change');
        });
    }

    /* ── Modal open ── */
    $('#add_advance_modal').on('shown.bs.modal', function () {
        $('#adv_request_date').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        $('#add_advance_modal .select2').select2({ dropdownParent: $('#add_advance_modal') });
    });

    /* ── Modal close — reset state ── */
    $('#add_advance_modal').on('hidden.bs.modal', function () {
        _currentSalary    = 0;
        _currentPayPeriod = 'month';
        $('#adv_salary_info').hide();
        $('#adv_max_note').hide().text('');
        $('#adv_amount_error').hide().text('');
        $('#adv_summary_box').hide();
        $('#adv_submit_btn').prop('disabled', false);
        $('#add_advance_form')[0].reset();
    });

    /* ── Salary state ── */
    var _currentSalary    = 0;
    var _currentPayPeriod = 'month';

    /* Fetch salary when employee is selected */
    $(document).on('change', '#adv_user_id', function () {
        var userId = $(this).val();

        _currentSalary    = 0;
        _currentPayPeriod = 'month';
        $('#adv_salary_info').hide();
        $('#adv_max_note').hide().text('');
        $('#adv_amount_error').hide().text('');
        $('#adv_summary_box').hide();

        if (!userId) { return; }

        $.ajax({
            url:      '/hrm/payroll/employee-salary/' + userId,
            method:   'GET',
            dataType: 'json',
            success: function (res) {
                _currentSalary    = parseFloat(res.salary)    || 0;
                _currentPayPeriod = res.pay_period            || 'month';

                if (_currentSalary > 0) {
                    var fmt = _numFmt(_currentSalary);

                    $('#adv_salary_info_text').html(
                        '<strong>' + ADV.employeeSalary + ':</strong> ' + fmt +
                        ' &nbsp;|&nbsp; ' +
                        '<strong>' + ADV.maxAdvanceNote.replace(':salary', fmt) + '</strong>'
                    );
                    $('#adv_salary_info').show();
                    $('#adv_max_note').text(ADV.maxAdvanceNote.replace(':salary', fmt)).show();
                } else {
                    $('#adv_salary_info_text').html(
                        '<i class="fas fa-exclamation-triangle text-warning"></i> ' +
                        ADV.employeeSalary + ': <strong>' + ADV.notAvailable + '</strong>'
                    );
                    $('#adv_salary_info').show();
                }

                _validateAmount();
                _updateSummary();
            },
            error: function () {
                $('#adv_salary_info').hide();
            }
        });
    });

    /* Revalidate on amount change */
    $(document).on('input change keyup', '#adv_amount', function () {
        _validateAmount();
        _updateSummary();
    });

    /* ── Helpers ── */
    function _numFmt(num) {
        return parseFloat(num).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function _rawAmount() {
        var raw = ($('#adv_amount').val() || '0').replace(/,/g, '');
        return parseFloat(raw) || 0;
    }

    function _validateAmount() {
        var amount = _rawAmount();
        var $err   = $('#adv_amount_error');
        var $btn   = $('#adv_submit_btn');

        $err.hide().text('');
        $btn.prop('disabled', false);

        if (_currentSalary > 0 && amount > _currentSalary) {
            $err.text(ADV.advanceExceedsSalary.replace(':salary', _numFmt(_currentSalary))).show();
            $btn.prop('disabled', true);
            return false;
        }

        if (amount <= 0 && $('#adv_amount').val().trim() !== '') {
            $err.text(ADV.amountGtZero).show();
            $btn.prop('disabled', true);
            return false;
        }

        return true;
    }

    function _updateSummary() {
        var amount = _rawAmount();

        if (_currentSalary <= 0 || amount <= 0 || amount > _currentSalary) {
            $('#adv_summary_box').hide();
            return;
        }

        var net = _currentSalary - amount;
        $('#sum_salary').text(_numFmt(_currentSalary));
        $('#sum_advance').text(_numFmt(amount));
        $('#sum_deduction').text('- ' + _numFmt(amount));
        $('#sum_net').text(_numFmt(net));
        $('#adv_summary_box').show();
    }

    /* ── Form submit (AJAX) ── */
    $(document).on('submit', '#add_advance_form', function (e) {
        e.preventDefault();

        if (!_validateAmount()) { return; }

        var $btn = $('#adv_submit_btn');
        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> ' + ADV.saving + '...');

        $.ajax({
            method:   'POST',
            url:      "{{ action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'storeAdvance']) }}",
            data:     $(this).serialize(),
            dataType: 'json',
            success: function (result) {
                if (result.success) {
                    toastr.success(result.msg);
                    emp_advances_table.ajax.reload();
                    $('#add_advance_modal').modal('hide');
                } else {
                    toastr.error(result.msg);
                    $btn.prop('disabled', false)
                        .html('<i class="fas fa-save"></i> ' + ADV.save);
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.msg)
                    ? xhr.responseJSON.msg
                    : ADV.somethingWrong;
                toastr.error(msg);
                $btn.prop('disabled', false)
                    .html('<i class="fas fa-save"></i> ' + ADV.save);
            }
        });
    });

    /* ── Approve ── */
    $(document).on('click', '.approve-advance', function () {
        var id   = $(this).data('id');
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            method:   'POST',
            url:      '/hrm/employees/advances/' + id + '/status',
            data:     { status: 'approved', _token: $('meta[name="csrf-token"]').attr('content') },
            dataType: 'json',
            success: function (result) {
                if (result.success) {
                    toastr.success(result.msg);
                    emp_advances_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.msg)
                    ? xhr.responseJSON.msg : ADV.somethingWrong;
                toastr.error(msg);
                $btn.prop('disabled', false);
            }
        });
    });

    /* ── Reject ── */
    $(document).on('click', '.reject-advance', function () {
        var id   = $(this).data('id');
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            method:   'POST',
            url:      '/hrm/employees/advances/' + id + '/status',
            data:     { status: 'rejected', _token: $('meta[name="csrf-token"]').attr('content') },
            dataType: 'json',
            success: function (result) {
                if (result.success) {
                    toastr.success(result.msg);
                    emp_advances_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                    $btn.prop('disabled', false);
                }
            },
            error: function () {
                toastr.error(ADV.somethingWrong);
                $btn.prop('disabled', false);
            }
        });
    });

    /* ── Delete ── */
    $(document).on('click', '.delete-advance', function () {
        var href = $(this).data('href');
        swal({
            title:      LANG.sure,
            icon:       'warning',
            buttons:    true,
            dangerMode: true,
        }).then(function (willDelete) {
            if (!willDelete) { return; }
            $.ajax({
                method:   'DELETE',
                url:      href,
                data:     { _token: $('meta[name="csrf-token"]').attr('content') },
                dataType: 'json',
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        emp_advances_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });
    });

});
</script>
@endsection
