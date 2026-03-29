@extends('layouts.app')
@section('title', __('essentials::lang.warnings'))

@section('content')
@include('essentials::layouts.nav_hrm')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('essentials::lang.warnings')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid', 'title' => __('essentials::lang.warnings')])
                @slot('tool')
                    <div class="box-tools">
                        <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right" data-toggle="modal" data-target="#add_warning_modal">
                            <i class="fas fa-plus"></i> @lang('essentials::lang.add_warning')
                        </button>
                    </div>
                @endslot
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('warn_employee_filter', __('essentials::lang.employee') . ':') !!}
                            {!! Form::select('warn_employee_filter', $employees, request('employee_id'), ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="emp_warnings_table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>@lang('essentials::lang.employee')</th>
                                <th>@lang('essentials::lang.warning_type')</th>
                                <th>@lang('essentials::lang.warning_note')</th>
                                <th>@lang('essentials::lang.warning_date')</th>
                                <th>@lang('essentials::lang.warning_issued_by')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

{{-- Add Warning Modal --}}
<div class="modal fade" id="add_warning_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['id' => 'add_warning_form', 'method' => 'POST']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('essentials::lang.add_warning')</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('warning_user_id', __('essentials::lang.employee') . ':*') !!}
                    {!! Form::select('user_id', $employees, request('employee_id'), ['class' => 'form-control select2', 'required', 'style' => 'width:100%', 'id' => 'warning_user_id']); !!}
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
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    var emp_warnings_table = $('#emp_warnings_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'getWarningsData'])}}",
            data: function(d) { d.employee_id = $('#warn_employee_filter').val(); }
        },
        columns: [
            { data: 'employee_name', name: 'employee_name' },
            { data: 'warning_type', name: 'essentials_employee_warnings.warning_type' },
            { data: 'warning_note', name: 'essentials_employee_warnings.warning_note' },
            { data: 'warning_date', name: 'essentials_employee_warnings.warning_date' },
            { data: 'issued_by_name', name: 'issued_by_name' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
    });
    $(document).on('change', '#warn_employee_filter', function() { emp_warnings_table.ajax.reload(); });

    var _preEmployeeId = '{{ request('employee_id') }}';
    if (_preEmployeeId) {
        emp_warnings_table.ajax.reload();
        $('#add_warning_modal').modal('show');
        $('#add_warning_modal').on('shown.bs.modal.pre', function() {
            $('#warning_user_id').val(_preEmployeeId).trigger('change');
            $(this).off('shown.bs.modal.pre');
        });
    }

    $('#add_warning_modal').on('shown.bs.modal', function() {
        $('#warning_date_picker').datetimepicker({ format: moment_date_format, ignoreReadonly: true });
        $('#add_warning_modal .select2').select2();
    });

    $(document).on('submit', '#add_warning_form', function(e) {
        e.preventDefault();
        $.ajax({
            method: 'POST',
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'storeWarning'])}}",
            data: $(this).serialize(),
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                    emp_warnings_table.ajax.reload();
                    $('#add_warning_form')[0].reset();
                    $('#add_warning_modal').modal('hide');
                } else { toastr.error(result.msg); }
            },
        });
    });

    $(document).on('click', '.delete-warning', function() {
        var href = $(this).data('href');
        swal({ title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true }).then(function(willDelete) {
            if (willDelete) {
                $.ajax({ method: 'DELETE', url: href, dataType: 'json', success: function(result) {
                    if (result.success) { toastr.success(result.msg); emp_warnings_table.ajax.reload(); }
                    else { toastr.error(result.msg); }
                }});
            }
        });
    });
});
</script>
@endsection
