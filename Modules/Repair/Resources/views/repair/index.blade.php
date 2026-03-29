@extends('layouts.app')
@section('title', __('repair::lang.repair'))

@section('content')
@include('repair::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('repair::lang.invoices')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @if(session()->has('success') && session()->has('msg'))
        <div class="alert alert-{{ session('success') ? 'success' : 'danger' }} alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            {{ session('msg') }}
        </div>
    @endif

    @component('components.filters', ['title' => __('report.filters'), 'closed' => false])
        @include('sell.partials.sell_list_filters', ['only' => ['sell_list_filter_location_id', 'sell_list_filter_customer_id', 'sell_list_filter_payment_status', 'sell_list_filter_date_range', 'created_by']])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('repair_status_id',  __('sale.status') . ':') !!}
                {!! Form::select('repair_status_id', $repair_status_dropdown['statuses'], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        @if(in_array('service_staff' ,$enabled_modules) && !$is_service_staff)
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('service_staff_id',  __('repair::lang.technician') . ':') !!}
                {!! Form::select('service_staff_id', $service_staffs, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        @endif
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('assigned_to_filter', __('repair::lang.assigned_to') . ':') !!}
                {!! Form::select('assigned_to_filter', $assigned_users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
    @endcomponent
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#pending_repair_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fas fa-exclamation-circle text-orange"></i>
                            @lang('repair::lang.pending')
                            @show_tooltip(__('repair::lang.common_pending_status_tooltip'))
                        </a>
                    </li>
                    <li>
                        <a href="#completed_repair_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fa fas fa-check-circle text-success"></i>
                            @lang('repair::lang.completed')
                            @show_tooltip(__('repair::lang.common_completed_status_tooltip'))
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="pending_repair_tab">

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped ajax_view" id="pending_repair_table">
                                <thead>
                                    <tr>
                                        <th>@lang('messages.action')</th>
                                        <th>@lang('receipt.date')</th>
                                        <th>
                                            @lang('repair::lang.delivery_date')
                                            @show_tooltip(__('repair::lang.repair_due_date_tooltip'))
                                        </th>
                                        <th>@lang('repair::lang.job_sheet_no')</th>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('sale.payment_status')</th>
                                        <th>@lang('repair::lang.Transection Status')</th>
                                        <th>@lang('sale.status')</th>
                                        <th>@lang('repair::lang.exit_permission')</th>
                                        <th>@lang('repair::lang.technician')</th>
                                        <th>@lang('lang_v1.added_by')</th>
                                        <th>@lang('sale.customer_name')</th>
                                        <th>@lang('contact.mobile')</th>
                                        <th>@lang('product.brand')</th>
                                        <th>@lang('repair::lang.device_model')</th>
                                        <th>@lang('repair::lang.Chassie_Number')</th>
                                        <th>@lang('sale.location')</th>
                                        <th>@lang('repair::lang.plate_number')</th>
                                        <th>@lang('repair::lang.spare_parts_end_user')</th>
                                        <th>@lang('repair::lang.purchasing_cost')</th>
                                        <th>@lang('repair::lang.labour_cost')</th>
                                        <th>@lang('repair::lang.total_expenses')</th>
                                        <th>@lang('repair::lang.net_profit')</th>
                                        <th>@lang('repair::lang.crm_followup')</th>
                                        <th>@lang('sale.total_amount')</th>
                                        <th>@lang('purchase.payment_due')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                        <td></td><td></td><td></td>
                                        <td><strong>@lang('sale.total'):</strong></td>
                                        <td><span class="display_currency" id="pending_repair_footer_total" data-currency_symbol="true"></span></td>
                                        <td><span class="display_currency" id="pending_repair_footer_total_remaining" data-currency_symbol="true"></span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="completed_repair_tab">

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped ajax_view" id="sell_table">
                                <thead>
                                    <tr>
                                        <th>@lang('messages.action')</th>
                                        <th>@lang('receipt.date')</th>
                                        <th>
                                            @lang('repair::lang.delivery_date')
                                            @show_tooltip(__('repair::lang.repair_due_date_tooltip'))
                                        </th>
                                        <th>@lang('repair::lang.job_sheet_no')</th>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('sale.payment_status')</th>
                                        <th>@lang('repair::lang.Transection Status')</th>
                                        <th>@lang('sale.status')</th>
                                        <th>@lang('repair::lang.exit_permission')</th>
                                        <th>@lang('repair::lang.technician')</th>
                                        <th>@lang('lang_v1.added_by')</th>
                                        <th>@lang('sale.customer_name')</th>
                                        <th>@lang('contact.mobile')</th>
                                        <th>@lang('product.brand')</th>
                                        <th>@lang('repair::lang.device_model')</th>
                                        <th>@lang('repair::lang.Chassie_Number')</th>
                                        <th>@lang('sale.location')</th>
                                        <th>@lang('repair::lang.plate_number')</th>
                                        <th>@lang('repair::lang.spare_parts_end_user')</th>
                                        <th>@lang('repair::lang.purchasing_cost')</th>
                                        <th>@lang('repair::lang.labour_cost')</th>
                                        <th>@lang('repair::lang.total_expenses')</th>
                                        <th>@lang('repair::lang.net_profit')</th>
                                        <th>@lang('repair::lang.crm_followup')</th>
                                        <th>@lang('sale.total_amount')</th>
                                        <th>@lang('purchase.payment_due')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                        <td></td><td></td><td></td>
                                        <td><strong>@lang('sale.total'):</strong></td>
                                        <td><span class="display_currency" id="footer_sale_total" data-currency_symbol="true"></span></td>
                                        <td><span class="display_currency" id="footer_total_remaining" data-currency_symbol="true"></span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade" id="edit_repair_status_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    {{-- Lightweight contact edit modal for Repair index (first/second/last name + mobile only) --}}
    <div class="modal fade" id="repair_edit_contact_modal" tabindex="-1" role="dialog" aria-labelledby="repairEditContactLabel"></div>

    <div class="modal fade" id="send_survey_modal" tabindex="-1" role="dialog" aria-labelledby="sendSurveyLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="sendSurveyLabel">@lang('survey::lang.sendperson')</h4>
                </div>
                <div class="modal-body">
                    <form id="send_survey_form">
                        <input type="hidden" id="survey_transaction_id">
                        <div class="form-group">
                            <label>@lang('contact.customer'):</label>
                            <p class="help-block" id="survey_contact_label"></p>
                        </div>
                        <div class="form-group">
                            <label for="survey_channel">@lang('lang_v1.send'):</label>
                            <select id="survey_channel" class="form-control" required>
                                <option value="sms">@lang('lang_v1.sms')</option>
                                <option value="whatsapp">@lang('lang_v1.whatsapp')</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="survey_category">@lang('survey::lang.category'):</label>
                            <select id="survey_category" class="form-control" required>
                                <option value="">@lang('messages.please_select')</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="survey_id">@lang('survey::lang.survey'):</label>
                            <select id="survey_id" class="form-control" required>
                                <option value="">@lang('messages.please_select')</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="button" class="btn btn-primary" id="send_survey_submit">
                        <i class="fa fa-paper-plane"></i> @lang('messages.send')
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Contact merge confirmation modal --}}
    <div class="modal fade" id="contact_merge_modal" tabindex="-1" role="dialog" aria-labelledby="contactMergeLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="contactMergeLabel">{{ __('contact.merge_contacts') }}</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <p>{{ __('contact.mobile_already_exists') }}</p>
                        <p><strong>{{ __('contact.mobile') }}:</strong> <span id="merge_mobile"></span></p>
                    </div>
                    <p>{{ __('contact.choose_merge_option') }}:</p>
                    <div class="radio">
                        <label>
                            <input type="radio" name="merge_option" value="keep_current" checked>
                            <strong>{{ __('contact.merge_other_into_current') }}</strong>
                            <br>
                            <small>{{ __('contact.current_contact') }}: <span id="current_contact_name"></span></small>
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="merge_option" value="keep_duplicate">
                            <strong>{{ __('contact.merge_current_into_other') }}</strong>
                            <br>
                            <small>{{ __('contact.other_contact') }}: <span id="duplicate_contact_name"></span></small>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="confirm_merge_contact">{{ __('contact.merge') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Set Due Date Modal -->
    <div class="modal fade" id="set_due_date_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">{{ __('treasury::lang.mark_as_due') }}</h4>
                </div>
                <div class="modal-body">
                    <form id="set_due_date_form">
                        <input type="hidden" id="set_due_transaction_id">
                        <div class="form-group">
                            <label for="set_due_date">{{ __('treasury::lang.due_date') }} <span class="text-danger">*</span></label>
                            <input type="date" id="set_due_date" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="button" id="submit_set_due_date" class="btn btn-primary">
                        <i class="fa fa-save"></i> {{ __('messages.save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="is_admin" value="{{ $is_admin ? 1 : 0 }}">
</section>
<!-- /.content -->
@stop
@section('javascript')

<script type="text/javascript">
$(document).ready( function(){
    //Date range as a button
    $('#sell_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            sell_table.ajax.reload();
            pending_repair_table.ajax.reload();
        }
    );
    $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#sell_list_filter_date_range').val('');
        sell_table.ajax.reload();
        pending_repair_table.ajax.reload();
    });

    var surveyCategoriesUrl = "{{ route('repair.survey.categories') }}";
    var surveySendUrl = "{{ route('repair.survey.send') }}";
    var surveysByCategoryUrl = "{{ route('repair.survey.category.surveys', ['category' => '__CATEGORY__']) }}";

    $(document).on('click', '.send-survey-action', function(e){
        e.preventDefault();
        var contactName = $(this).data('contact-name') || '';
        var contactMobile = $(this).data('contact-mobile') || '';
        var label = contactName;
        if (contactMobile) {
            label = label ? (label + ' (' + contactMobile + ')') : contactMobile;
        }

        $('#survey_transaction_id').val($(this).data('transaction-id'));
        $('#survey_contact_label').text(label || '@lang('contact.customer')');
        $('#survey_category').empty().append('<option value="">@lang('messages.please_select')</option>');
        $('#survey_id').empty().append('<option value="">@lang('messages.please_select')</option>');

        $.ajax({
            method: 'GET',
            url: surveyCategoriesUrl,
            dataType: 'json',
            success: function(result) {
                if (result.success && result.categories) {
                    result.categories.forEach(function(category) {
                        $('#survey_category').append('<option value="' + category.id + '">' + category.name + '</option>');
                    });
                }
                $('#send_survey_modal').modal('show');
            },
            error: function() {
                toastr.error(LANG.something_went_wrong || '@lang('messages.something_went_wrong')');
            }
        });
    });

    $(document).on('change', '#survey_category', function(){
        var categoryId = $(this).val();
        $('#survey_id').empty().append('<option value="">@lang('messages.please_select')</option>');
        if (!categoryId) {
            return;
        }

        var url = surveysByCategoryUrl.replace('__CATEGORY__', categoryId);
        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'json',
            success: function(result) {
                if (result.success && result.surveys) {
                    result.surveys.forEach(function(survey) {
                        $('#survey_id').append('<option value="' + survey.id + '">' + survey.title + '</option>');
                    });
                }
            },
            error: function() {
                toastr.error(LANG.something_went_wrong || '@lang('messages.something_went_wrong')');
            }
        });
    });

    $('#send_survey_submit').on('click', function(){
        var transactionId = $('#survey_transaction_id').val();
        var surveyId = $('#survey_id').val();
        var channel = $('#survey_channel').val();

        if (!transactionId || !surveyId || !channel) {
            toastr.error('@lang('messages.please_select')');
            return;
        }

        $.ajax({
            method: 'POST',
            url: surveySendUrl,
            dataType: 'json',
            data: {
                transaction_id: transactionId,
                survey_id: surveyId,
                channel: channel,
                _token: "{{ csrf_token() }}"
            },
            success: function(result){
                if (result.success) {
                    if (result.whatsapp_url) {
                        window.open(result.whatsapp_url, '_blank');
                    }
                    toastr.success(result.message || '@lang('messages.success')');
                    $('#send_survey_modal').modal('hide');
                } else {
                    toastr.error(result.message || '@lang('messages.something_went_wrong')');
                }
            },
            error: function(xhr){
                var errorMsg = '@lang('messages.something_went_wrong')';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
            }
        });
    });

    sell_table = $('#sell_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader:false,
        aaSorting: [[2, 'asc']],
        pageLength: 25,
        lengthMenu: [[25], [25]],

        "ajax": {
            "url": "/repair/repair",
            "data": function ( d ) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                d.is_direct_sale = 1;
                d.is_completed_status = 1;
                d.location_id = $('#sell_list_filter_location_id').val();
                d.customer_id = $('#sell_list_filter_customer_id').val();
                d.payment_status = $('#sell_list_filter_payment_status').val();
                d.created_by = $('#created_by').val();
                d.sub_type = 'repair';
                d.repair_status_id = $('#repair_status_id').val();
                d.assigned_to = $('#assigned_to_filter').val();
                @if(in_array('service_staff' ,$enabled_modules))
                    d.service_staff_id = $('#service_staff_id').val();
                @endif
            }
        },
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false},
            { data: 'transaction_date', name: 'transaction_date'  },
            { data: 'due_date', name: 'due_date'  , orderable: false, searchable: false},
            { data: 'job_sheet_no', name: 'rjs.job_sheet_no'},
            { data: 'invoice_no', name: 'invoice_no'},
            { data: 'payment_status', name: 'payment_status'},
            { data: 'transactions_status', name: 'transactions.status'},
            { data: 'repair_status', name: 'rs.name'},
            { data: 'exit_permission', name: 'Exit_permission', orderable: false, searchable: false},
            { data: 'technecian', name: 'technecian' , orderable: false, searchable: false},
            { data: 'added_by', name: 'added_by', orderable: false, searchable: false},
            { data: 'name', name: 'contacts.name'},
            { data: 'contact_mobile', name : 'contacts.mobile'},

            { data: 'brand', name: 'b.name'},
            { data: 'device_model', name: 'rdm.name'},
            { data: 'vin_number', name: 'contact_device.chassis_number', orderable: false },
            { data: 'business_location', name: 'bl.name'},
            { data: 'plate_number', name: 'contact_device.plate_number'},

            { data: 'total_spare_parts', name: 'total_spare_parts', orderable: false, searchable: false, visible: false},
            { data: 'purchasing_cost', name: 'purchasing_cost', orderable: false, searchable: false, visible: false},
            { data: 'labour_cost', name: 'labour_cost', orderable: false, searchable: false, visible: false},
            { data: 'total_expenses', name: 'total_expenses', orderable: false, searchable: false, visible: false},
            { data: 'net_profit', name: 'net_profit', orderable: false, searchable: false, visible: false},
            { data: 'crm_assigned_users', name: 'crm_assigned_users', orderable: false, searchable: false},
            { data: 'final_total', name: 'final_total', orderable: false, searchable: false},
            { data: 'total_remaining', name: 'total_remaining', orderable: false, searchable: false},
        ],
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel" aria-hidden="true"></i> Excel',
                className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25]
                },
                footer: true
            },
            {
                extend: 'csv',
                text: '<i class="fa fa-file-csv" aria-hidden="true"></i> CSV',
                className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25]
                },
                footer: true
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print" aria-hidden="true"></i> Print',
                className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25],
                    stripHtml: true
                },
                footer: true
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf" aria-hidden="true"></i> PDF',
                className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25]
                },
                footer: true
            }
        ],
        "fnDrawCallback": function (oSettings) {

            $('#footer_sale_total').text(sum_table_col($('#sell_table'), 'final-total'));

            $('#footer_total_remaining').text(sum_table_col($('#sell_table'), 'payment_due'));


            $('#footer_payment_status_count').html(__sum_status_html($('#sell_table'), 'payment-status-label'));

            $('#footer_repair_status_count').html(__sum_status_html($('#sell_table'), 'edit_repair_status'));

            __currency_convert_recursively($('#sell_table'));
        },
        createdRow: function( row, data, dataIndex ) {
            $( row ).find('td:eq(12)').attr('class', 'clickable_td');
            $( row ).find('td:eq(15)').attr('class', 'clickable_td edit_status_td');
        }
    });

    pending_repair_table = $('#pending_repair_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader:false,
        aaSorting: [[2, 'asc']],
        pageLength: 25,
        lengthMenu: [[25], [25]],

        "ajax": {
            "url": "/repair/repair",
            "data": function ( d ) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                d.is_completed_status = 0;
                d.is_direct_sale = 1;
                d.location_id = $('#sell_list_filter_location_id').val();
                d.customer_id = $('#sell_list_filter_customer_id').val();
                d.payment_status = $('#sell_list_filter_payment_status').val();
                d.created_by = $('#created_by').val();
                d.sub_type = 'repair';
                d.repair_status_id = $('#repair_status_id').val();
                d.assigned_to = $('#assigned_to_filter').val();
                @if(in_array('service_staff' ,$enabled_modules))
                    d.service_staff_id = $('#service_staff_id').val();
                @endif
            }
        },
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false},
            { data: 'transaction_date', name: 'transaction_date'  },
            { data: 'due_date', name: 'due_date', orderable: false, searchable: false},
            { data: 'job_sheet_no', name: 'rjs.job_sheet_no'},
            { data: 'invoice_no', name: 'invoice_no'},
            { data: 'payment_status', name: 'payment_status'},
            { data: 'transactions_status', name: 'transactions.status'},
            { data: 'repair_status', name: 'rs.name'},
            { data: 'exit_permission', name: 'Exit_permission', orderable: false, searchable: false},
            { data: 'technecian', name: 'technecian' },
            { data: 'added_by', name: 'added_by', orderable: false, searchable: false},
            { data: 'name', name: 'contacts.name'},
            { data: 'contact_mobile', name : 'contacts.mobile'},
            { data: 'brand', name: 'b.name'},
            { data: 'device_model', name: 'rdm.name'},
            { data: 'vin_number', name: 'contact_device.chassis_number'},
            { data: 'business_location', name: 'bl.name'},
            { data: 'plate_number', name: 'contact_device.plate_number'},
            { data: 'total_spare_parts', name: 'total_spare_parts', orderable: false, searchable: false, visible: false},
            { data: 'purchasing_cost', name: 'purchasing_cost', orderable: false, searchable: false, visible: false},
            { data: 'labour_cost', name: 'labour_cost', orderable: false, searchable: false, visible: false},
            { data: 'total_expenses', name: 'total_expenses', orderable: false, searchable: false, visible: false},
            { data: 'net_profit', name: 'net_profit', orderable: false, searchable: false, visible: false},
            { data: 'crm_assigned_users', name: 'crm_assigned_users', orderable: false, searchable: false},
            { data: 'final_total', name: 'final_total', orderable: false, searchable: false},
            { data: 'total_remaining', name: 'total_remaining', orderable: false, searchable: false},
        ],
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel" aria-hidden="true"></i> Excel',
                className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25]
                },
                footer: true
            },
            {
                extend: 'csv',
                text: '<i class="fa fa-file-csv" aria-hidden="true"></i> CSV',
                className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25]
                },
                footer: true
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print" aria-hidden="true"></i> Print',
                className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25],
                    stripHtml: true
                },
                footer: true
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf" aria-hidden="true"></i> PDF',
                className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25]
                },
                footer: true
            }
        ],
        "fnDrawCallback": function (oSettings) {

            $('#pending_repair_footer_total').text(sum_table_col($('#pending_repair_table'), 'final-total'));

            $('#pending_repair_footer_total_remaining').text(sum_table_col($('#pending_repair_table'), 'payment_due'));


            $('#pending_repair_footer_payment_status_count').html(__sum_status_html($('#pending_repair_table'), 'payment-status-label'));

            $('#footer_pending_repair_status_count').html(__sum_status_html($('#pending_repair_table'), 'edit_repair_status'));

            __currency_convert_recursively($('#pending_repair_table'));
        },
        createdRow: function( row, data, dataIndex ) {
            $( row ).find('td:eq(11)').attr('class', 'clickable_td');
            $( row ).find('td:eq(14)').attr('class', 'clickable_td edit_status_td');
        }
    });

    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #service_staff_id, #repair_status_id, #created_by, #assigned_to_filter',  function() {
        sell_table.ajax.reload();
        pending_repair_table.ajax.reload();
    });
    @can("repair_status.update")
        $(document).on('click', '.edit_repair_status', function(e){
            e.preventDefault();
            var url = $(this).data('href');
            $.ajax({
                method: 'GET',
                url: url,
                dataType: 'html',
                success: function(result) {
                    $('#edit_repair_status_modal').html(result).modal('show');
                }
            });
        });
    @endcan

    // Handle lightweight contact edit modal
    $(document).on('click', '.repair-edit-contact-basic', function(e){
        e.preventDefault();
        var url = $(this).data('href');
        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html',
            success: function(result) {
                $('#repair_edit_contact_modal').html(result).modal('show');
            }
        });
    });

    $(document).on('submit', 'form#repair_edit_contact_form', function(e){
        e.preventDefault();

        var data = $(this).serialize();

        $.ajax({
            method: $(this).attr("method"),
            url: $(this).attr("action"),
            dataType: "json",
            data: data,
            success: function(result){
                if(result.success == true){
                    $('#repair_edit_contact_modal').modal('hide');
                    toastr.success(result.msg);
                    sell_table.ajax.reload();
                    pending_repair_table.ajax.reload();
                } else if(result.duplicate_mobile == true){
                    // Show merge options modal
                    $('#merge_mobile').text(result.mobile);
                    $('#current_contact_name').text(result.current_contact_name);
                    $('#duplicate_contact_name').text(result.duplicate_contact_name);
                    $('#contact_merge_modal').data('current-contact-id', result.current_contact_id);
                    $('#contact_merge_modal').data('duplicate-contact-id', result.duplicate_contact_id);
                    $('#contact_merge_modal').modal('show');
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(xhr){
                var errorMsg = __('messages.something_went_wrong');
                if(xhr.responseJSON && xhr.responseJSON.msg){
                    errorMsg = xhr.responseJSON.msg;
                }
                toastr.error(errorMsg);
            }
        });
    });

    // Handle merge contact confirmation
    $('#confirm_merge_contact').on('click', function(){
        var mergeOption = $('input[name="merge_option"]:checked').val();
        var currentContactId = $('#contact_merge_modal').data('current-contact-id');
        var duplicateContactId = $('#contact_merge_modal').data('duplicate-contact-id');

        var keepContactId, mergeContactId;

        if(mergeOption == 'keep_current'){
            keepContactId = currentContactId;
            mergeContactId = duplicateContactId;
        } else {
            keepContactId = duplicateContactId;
            mergeContactId = currentContactId;
        }

        $.ajax({
            method: 'POST',
            url: '{{ route("repair.contacts.merge") }}',
            dataType: "json",
            data: {
                keep_contact_id: keepContactId,
                merge_contact_id: mergeContactId,
                _token: '{{ csrf_token() }}'
            },
            success: function(result){
                if(result.success == true){
                    $('#contact_merge_modal').modal('hide');
                    $('#repair_edit_contact_modal').modal('hide');
                    toastr.success(result.msg);
                    sell_table.ajax.reload();
                    pending_repair_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(xhr){
                var errorMsg = __('messages.something_went_wrong');
                if(xhr.responseJSON && xhr.responseJSON.msg){
                    errorMsg = xhr.responseJSON.msg;
                }
                toastr.error(errorMsg);
            }
        });
    });

    $('#edit_repair_status_modal').on('shown.bs.modal', function (e) {
        $('#send_sms').change(function() {
            if ($(this). is(":checked")) {
                $('div.sms_body').fadeIn();
            } else {
                $('div.sms_body').fadeOut();
            }
        });

        if ($('#repair_status_id_modal').length) {
            $("#sms_body").val($("#repair_status_id_modal :selected").data('sms_template'));
        }

        $('#repair_status_id_modal').on('change', function() {
            var sms_template = $(this).find(':selected').data('sms_template');
            $("#sms_body").val(sms_template);
        });
    });

    $(document).on('submit', 'form#update_repair_status_form', function(e){
        e.preventDefault();

        var data = $(this).serialize();
        var ladda = Ladda.create(document.querySelector('.ladda-button'));
        ladda.start();

        $.ajax({
            method: $(this).attr("method"),
            url: $(this).attr("action"),
            dataType: "json",
            data: data,
            success: function(result){
                ladda.stop();
                if(result.success == true){
                    $('#edit_repair_status_modal').modal('hide');
                    toastr.success(result.msg);
                    sell_table.ajax.reload();
                    pending_repair_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });

    // Two-stage delete for repair invoices (soft delete first, then hard delete with password)
    $(document).on('click', '.delete-repair-transaction', function(e) {
        e.preventDefault();

        var $link = $(this);
        var url = $link.data('href');
        var isDeleted = parseInt($link.data('is-deleted') || 0, 10) === 1;
        var isAdmin = $('#is_admin').val() == 1;

        if (!url) {
            return;
        }

        // First stage: soft delete via RepairController@destroy (no password)
        if (!isDeleted) {
            swal({
                title: "{{ __('repair::lang.soft_delete_confirm_title') }}",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then(function(confirmed) {
                if (confirmed) {
                    $.ajax({
                        method: 'DELETE',
                        url: url,
                        data: { _token: "{{ csrf_token() }}" },
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                toastr.success("{{ __('repair::lang.soft_delete_success') }}");
                                sell_table.ajax.reload();
                                pending_repair_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        } else {
            // Second stage: hard delete requires admin password
            if (!isAdmin) {
                toastr.error("{{ __('repair::lang.admin_only_error') }}");
                return;
            }

            swal({
                title: "{{ __('repair::lang.hard_delete_confirm_title') }}",
                text: "{{ __('repair::lang.hard_delete_confirm_text') }}",
                icon: "warning",
                buttons: true,
                dangerMode: true,
                content: {
                    element: 'input',
                    attributes: {
                        type: 'password',
                        placeholder: "{{ __('repair::lang.password_placeholder') }}",
                    }
                }
            }).then(function(password) {
                if (password) {
                    $.ajax({
                        method: 'DELETE',
                        url: url,
                        dataType: 'json',
                        data: {
                            force_delete: true,
                            password: password,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(result) {
                            if (result.success) {
                                toastr.success("{{ __('repair::lang.hard_delete_success') }}");
                                sell_table.ajax.reload();
                                pending_repair_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        }
    });

    $(document).on('click', '.collapsed-box-title', function(e){
        if (e.target.tagName == 'BUTTON' || e.target.tagName == 'I') {
            return false;
        }
        $(this).find('.box-tools button').click();
    });

    // Handle exit permission toggle
    $(document).on('click', '.toggle-exit-permission', function(e) {
        e.preventDefault();
        var $this = $(this);
        var id = $this.data('id');
        var currentStatus = $this.data('status') === 'true';

        // Confirm before changing status
        var confirmMessage = currentStatus ? "{{ __('repair::lang.remove_exit_permission') }}" : "{{ __('repair::lang.allow_exit_permission') }}";

        if (confirm(confirmMessage)) {
            $.ajax({
                url: '/update-vehicle-status',
                method: 'POST',
                data: {
                    id: id,
                    status: !currentStatus,
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        // Update the button status and text
                        $this.data('status', (!currentStatus).toString());

                        var newLabel = !currentStatus ?
                            "{{ __('repair::lang.exit_allowed') }}" :
                            "{{ __('repair::lang.exit_not_allowed') }}";

                        $this.find('.exit-permission-label').text(newLabel);

                        toastr.success(result.message);

                        // Reload the tables to reflect the changes
                        sell_table.ajax.reload();
                        pending_repair_table.ajax.reload();
                    } else {
                        toastr.error(result.message);
                    }
                }
            });
        }
    });

    // Handle add CRM follow-up button
    $(document).on('click', '.add-crm-followup', function(e) {
        e.preventDefault();
        var $this = $(this);
        var contactId = $this.data('contact-id');
        var contactName = $this.data('contact-name');
        var transactionId = $this.data('transaction-id');

        // Open CRM follow-up modal
        $.ajax({
            url: '{{ route("repair.add_crm_followup_modal") }}',
            method: 'GET',
            data: {
                contact_id: contactId,
                transaction_id: transactionId,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'html',
            success: function(result) {
                // Show modal
                var modalHtml = '<div class="modal fade" id="crm_followup_modal" tabindex="-1" role="dialog">' +
                    '<div class="modal-dialog modal-lg" role="document">' +
                    '<div class="modal-content">' +
                    result +
                    '</div></div></div>';

                $('#crm_followup_modal').remove();
                $('body').append(modalHtml);
                $('#crm_followup_modal').modal('show');
            }
        });
    });

    // Handle CRM follow-up form submission
    $(document).on('submit', '#crm_followup_form', function(e) {
        e.preventDefault();
        var $form = $(this);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                    $('#crm_followup_modal').modal('hide');
                    sell_table.ajax.reload();
                    pending_repair_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });

    // Handle exit permission toggle
    $(document).on('click', '.toggle-exit-permission', function(e) {
        e.preventDefault();
        var $this = $(this);
        var id = $this.data('id');
        var currentStatus = $this.data('status') === 'true';

        // Confirm before changing status
        var confirmMessage = currentStatus ? "{{ __('repair::lang.remove_exit_permission') }}" : "{{ __('repair::lang.allow_exit_permission') }}";

        if (confirm(confirmMessage)) {
            $.ajax({
                url: '/update-vehicle-status',
                method: 'POST',
                data: {
                    id: id,
                    status: !currentStatus,
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        // Update the button status and text
                        $this.data('status', (!currentStatus).toString());

                        var newLabel = !currentStatus ?
                            "{{ __('repair::lang.exit_allowed') }}" :
                            "{{ __('repair::lang.exit_not_allowed') }}";

                        $this.find('.exit-permission-label').text(newLabel);

                        toastr.success(result.message);

                        // Reload the tables to reflect the changes
                        sell_table.ajax.reload();
                        pending_repair_table.ajax.reload();
                    } else {
                        toastr.error(result.message || "{{ __('messages.something_went_wrong') }}");
                    }
                },
                error: function() {
                    toastr.error("{{ __('messages.something_went_wrong') }}");
                }
            });
        }
    });

});
</script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection




