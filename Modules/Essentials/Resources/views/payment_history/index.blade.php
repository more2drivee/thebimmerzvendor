@extends('layouts.app')
@section('title', __('essentials::lang.payment_history'))

@section('content')
@include('essentials::layouts.nav_hrm')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('essentials::lang.payment_history')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid', 'title' => __('essentials::lang.payment_history')])
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('pay_employee_filter', __('essentials::lang.employee') . ':') !!}
                            {!! Form::select('pay_employee_filter', $employees, request('employee_id'), ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('pay_date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('pay_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="emp_payment_table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>@lang('essentials::lang.employee')</th>
                                <th>@lang('purchase.ref_no')</th>
                                <th>@lang('essentials::lang.month_year')</th>
                                <th>@lang('sale.total_amount')</th>
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

<div class="modal fade view_modal" tabindex="-1" role="dialog"></div>
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
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

    var emp_payment_table = $('#emp_payment_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'getPaymentHistoryData'])}}",
            data: function(d) {
                d.employee_id = $('#pay_employee_filter').val();
                if ($('#pay_date_range').val()) {
                    d.start_date = $('#pay_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    d.end_date = $('#pay_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                }
            }
        },
        columns: [
            { data: 'employee_name', name: 'employee_name' },
            { data: 'ref_no', name: 'transactions.ref_no' },
            { data: 'transaction_date', name: 'transactions.transaction_date' },
            { data: 'final_total', name: 'transactions.final_total' },
            { data: 'payment_status', name: 'transactions.payment_status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
    });
    initDateRange('#pay_date_range', function() { emp_payment_table.ajax.reload(); });
    $(document).on('change', '#pay_employee_filter', function() { emp_payment_table.ajax.reload(); });

    var _preEmployeeId = '{{ request('employee_id') }}';
    if (_preEmployeeId) {
        emp_payment_table.ajax.reload();
    }
});
</script>
@endsection
