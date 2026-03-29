@extends('layouts.app')

@section('header')
    <section class="content-header">
        <h1>{{ __('lang_v1.loyalty_requests') }}</h1>
    </section>
@endsection

@section('content')
    <div class="content">
        <div class="box box-solid">
            <div class="box-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('lang_v1.status') }}</label>
                            <select id="filter_status" class="form-control select2">
                                <option value="">{{ __('messages.all') }}</option>
                                <option value="pending">{{ __('lang_v1.pending') }}</option>
                                <option value="approved">{{ __('lang_v1.approved') }}</option>
                                <option value="rejected">{{ __('lang_v1.rejected') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('contact.name') }}</label>
                            <select id="filter_contact" class="form-control select2" style="width: 100%;">
                                <option value="">{{ __('messages.all') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('messages.date') }}</label>
                            <div class="input-group">
                                <input type="text" id="filter_date" class="form-control datepicker">
                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="button" id="btn_filter" class="btn btn-primary">
                                    <i class="fa fa-filter"></i> {{ __('messages.filter') }}
                                </button>
                                <button type="button" id="btn_reset" class="btn btn-default">
                                    <i class="fa fa-refresh"></i> {{ __('messages.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="loyalty_requests_table"
                        width="100%">
                        <thead>
                            <tr>
                                <th>{{ __('messages.date') }}</th>
                                <th>{{ __('contact.name') }}</th>
                                <th>{{ __('lang_v1.points_requested') }}</th>
                        
                                <th>{{ __('sale.order_total') }}</th>
                                <th>{{ __('lang_v1.status') }}</th>
                                <th>{{ __('lang_v1.approved_by') }}</th>
                                <th>{{ __('messages.action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            var loyalty_requests_table = $('#loyalty_requests_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ action([\App\Http\Controllers\ContactController::class, 'getLoyaltyRequests']) }}",
                    data: function(d) {
                        d.status = $('#filter_status').val();
                        d.contact_id = $('#filter_contact').val();
                        d.date = $('#filter_date').val();
                    }
                },
                columns: [{
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'contact_name',
                        name: 'contact_name'
                    },
                    {
                        data: 'points_requested',
                        name: 'points_requested'
                    },
                
                    {
                        data: 'order_total',
                        name: 'order_total'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'approved_by',
                        name: 'approved_by'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        searchable: false,
                        orderable: false
                    },
                ],
                language: {
                    sProcessing: '{{ __("messages.loading") }}',
                    sLengthMenu: '{{ __("messages.show") }} _MENU_ {{ __("messages.entries") }}',
                    sZeroRecords: '{{ __("messages.no_data_found") }}',
                    sEmptyTable: '{{ __("messages.no_data_found") }}',
                    sInfo: '{{ __("messages.showing") }} _START_ {{ __("messages.to") }} _END_ {{ __("messages.of") }} _TOTAL_ {{ __("messages.entries") }}',
                    sInfoEmpty: '{{ __("messages.showing") }} 0 {{ __("messages.to") }} 0 {{ __("messages.of") }} 0 {{ __("messages.entries") }}',
                    sInfoFiltered: '({{ __("messages.filtered") }} {{ __("messages.from") }} _MAX_ {{ __("messages.total_entries") }})',
                    sInfoPostFix: '',
                    sSearch: '{{ __("messages.search") }}',
                    sUrl: '',
                    oPaginate: {
                        sFirst: '{{ __("messages.first") }}',
                        sPrevious: '{{ __("messages.previous") }}',
                        sNext: '{{ __("messages.next") }}',
                        sLast: '{{ __("messages.last") }}'
                    }
                }
            });

            // Load contacts for filter
            $.ajax({
                url: "{{ action([\App\Http\Controllers\ContactController::class, 'getCustomers']) }}",
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">{{ __("messages.all") }}</option>';
                    $.each(data, function(index, value) {
                        options += '<option value="' + value.id + '">' + value.text + '</option>';
                    });
                    $('#filter_contact').html(options);
                }
            });

            // Initialize datepicker
            $('.datepicker').datetimepicker({
                format: 'YYYY-MM-DD'
            });

            // Filter button
            $('#btn_filter').on('click', function() {
                loyalty_requests_table.ajax.reload();
            });

            // Reset button
            $('#btn_reset').on('click', function() {
                $('#filter_status').val('').trigger('change');
                $('#filter_contact').val('').trigger('change');
                $('#filter_date').val('');
                loyalty_requests_table.ajax.reload();
            });

            // Approve button
            $(document).on('click', '.btn-approve', function() {
                var id = $(this).data('id');
                var btn = $(this);
                swal({
                    title: '{{ __("lang_v1.confirm_approve_loyalty_request") }}',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: false,
                }).then((willApprove) => {
                    if (willApprove) {
                        $.ajax({
                            url: "/contacts/loyalty-requests/" + id + "/approve",
                            method: 'POST',
                            dataType: 'json',
                            success: function(result) {
                                if (result.success) {
                                    swal({
                                        title: result.msg,
                                        icon: 'success',
                                        timer: 2000,
                                        buttons: false
                                    });
                                    loyalty_requests_table.ajax.reload();
                                } else {
                                    swal({
                                        title: result.msg,
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function(xhr) {
                                swal({
                                    title: '{{ __("messages.something_went_wrong") }}',
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            });

            // Reject button
            $(document).on('click', '.btn-reject', function() {
                var id = $(this).data('id');
                swal({
                    title: '{{ __("lang_v1.enter_rejection_reason") }}',
                    text: '',
                    content: {
                        element: 'input',
                        attributes: {
                            placeholder: '{{ __("lang_v1.enter_rejection_reason") }}',
                            type: 'text',
                        },
                    },
                    buttons: {
                        cancel: true,
                        confirm: {
                            text: '{{ __("messages.submit") }}',
                            closeModal: false,
                        }
                    }
                }).then((reason) => {
                    if (reason) {
                        $.ajax({
                            url: "/contacts/loyalty-requests/" + id + "/reject",
                            method: 'POST',
                            data: { reason: reason },
                            dataType: 'json',
                            success: function(result) {
                                if (result.success) {
                                    swal({
                                        title: result.msg,
                                        icon: 'success',
                                        timer: 2000,
                                        buttons: false
                                    });
                                    loyalty_requests_table.ajax.reload();
                                } else {
                                    swal({
                                        title: result.msg,
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function(xhr) {
                                swal({
                                    title: '{{ __("messages.something_went_wrong") }}',
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
