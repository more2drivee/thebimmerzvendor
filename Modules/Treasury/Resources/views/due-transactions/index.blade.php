@extends('layouts.app')
@section('title', __('treasury::lang.due_transactions'))

@section('content')
@include('treasury::layouts.nav')

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label for="location_id">{{ __('business.business_location') }}</label>
                <select id="location_id" class="form-control select2">
                    <option value="">{{ __('lang_v1.all') }}</option>
                    @foreach($business_locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="start_date">{{ __('treasury::lang.start_date') }}</label>
                <input type="date" id="start_date" class="form-control">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="end_date">{{ __('treasury::lang.end_date') }}</label>
                <input type="date" id="end_date" class="form-control">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" id="filter_due_transactions" class="btn btn-primary btn-block">
                    <i class="fa fa-filter"></i> {{ __('treasury::lang.filter') }}
                </button>
            </div>
        </div>
    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('treasury::lang.due_transactions') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="due_transactions_table">
                            <thead class="bg-light">
                                <tr>
                                    <th>{{ __('sale.invoice_no') }}</th>
                                    <th>{{ __('contact.contact') }}</th>
                                    <th>{{ __('treasury::lang.location') }}</th>
                                    <th>{{ __('treasury::lang.transaction_date') }}</th>
                                    <th>{{ __('treasury::lang.due_date') }}</th>
                                    <th>{{ __('sale.total_amount') }}</th>
                                    <th>{{ __('sale.payment_status') }}</th>
                                    <th>{{ __('messages.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Postpone Due Modal -->
<div class="modal fade" id="postpone_due_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">{{ __('treasury::lang.postpone_due_date') }}</h4>
            </div>
            <div class="modal-body">
                <form id="postpone_due_form">
                    <input type="hidden" id="postpone_transaction_id">
                    <div class="form-group">
                        <label for="new_due_date">{{ __('treasury::lang.new_due_date') }} <span class="text-danger">*</span></label>
                        <input type="date" id="new_due_date" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="button" id="submit_postpone_due" class="btn btn-primary">
                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Due Date History Modal -->
<div class="modal fade" id="due_date_history_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">{{ __('treasury::lang.due_date_history') }}</h4>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="due_date_history_table">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('treasury::lang.old_due_date') }}</th>
                                <th>{{ __('treasury::lang.new_due_date') }}</th>
                                <th>{{ __('treasury::lang.reason') }}</th>
                                <th>{{ __('treasury::lang.changed_by') }}</th>
                                <th>{{ __('treasury::lang.changed_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- History data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    var due_transactions_table = $('#due_transactions_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("treasury.due-transactions.data") }}',
            data: function(d) {
                d.location_id = $('#location_id').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            }
        },
        columns: [
            { data: 'invoice_no', name: 'invoice_no' },
            { data: 'contact_name', name: 'contact_name' },
            { data: 'location_name', name: 'location_name' },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'general_due_date', name: 'general_due_date' },
            { data: 'final_total', name: 'final_total' },
            { data: 'payment_status', name: 'payment_status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[4, 'asc']]
    });

    $('#filter_due_transactions').on('click', function() {
        due_transactions_table.ajax.reload();
    });

    $(document).on('click', '.postpone-due', function(e) {
        e.preventDefault();
        var transaction_id = $(this).data('id');
        var current_due_date = $(this).data('due-date');
        $('#postpone_transaction_id').val(transaction_id);
        if (current_due_date) {
            $('#new_due_date').val(current_due_date);
        } else {
            $('#new_due_date').val('');
        }
        $('#postpone_due_modal').modal('show');
    });

    $('#submit_postpone_due').on('click', function() {
        var transaction_id = $('#postpone_transaction_id').val();
        var new_due_date = $('#new_due_date').val();

        if (!new_due_date) {
            toastr.error("{{ __('messages.required_field') }}");
            return;
        }

        $.ajax({
            method: 'POST',
            url: '{{ route("treasury.due-transactions.postpone") }}',
            data: {
                _token: '{{ csrf_token() }}',
                transaction_id: transaction_id,
                new_due_date: new_due_date
            },
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                    $('#postpone_due_modal').modal('hide');
                    due_transactions_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(xhr) {
                toastr.error('{{ __("messages.something_went_wrong") }}');
            }
        });
    });

    $(document).on('click', '.send-due-sms', function(e) {
        e.preventDefault();
        var transaction_id = $(this).data('id');

        if (!confirm('{{ __("treasury::lang.confirm_send_sms") }}')) {
            return;
        }

        $.ajax({
            method: 'POST',
            url: '{{ route("treasury.due-transactions.send-sms") }}',
            data: {
                _token: '{{ csrf_token() }}',
                transaction_id: transaction_id
            },
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(xhr) {
                toastr.error('{{ __("messages.something_went_wrong") }}');
            }
        });
    });

    $(document).on('click', '.view-due-history', function(e) {
        e.preventDefault();
        var transaction_id = $(this).data('id');
        
        // Clear previous data
        $('#due_date_history_table tbody').html('');
        
        // Load due date history
        $.ajax({
            method: 'GET',
            url: '{{ route("treasury.due-transactions.history") }}',
            data: {
                transaction_id: transaction_id
            },
            success: function(result) {
                if (result.success) {
                    var historyHtml = '';
                    if (result.data.length > 0) {
                        result.data.forEach(function(history) {
                            historyHtml += '<tr>';
                            historyHtml += '<td>' + (history.old_due_date ? formatDate(history.old_due_date) : '-') + '</td>';
                            historyHtml += '<td>' + formatDate(history.new_due_date) + '</td>';
                            historyHtml += '<td>' + (history.reason || '-') + '</td>';
                            historyHtml += '<td>' + (history.created_by_name || '-') + '</td>';
                            historyHtml += '<td>' + formatDate(history.created_at) + '</td>';
                            historyHtml += '</tr>';
                        });
                    } else {
                        historyHtml = '<tr><td colspan="5" class="text-center">{{ __("messages.no_data_found") }}</td></tr>';
                    }
                    $('#due_date_history_table tbody').html(historyHtml);
                    $('#due_date_history_modal').modal('show');
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(xhr) {
                toastr.error('{{ __("messages.something_went_wrong") }}');
            }
        });
    });

    function formatDate(dateString) {
        if (!dateString) return '-';
        var date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    $(document).on('click', '.remove-due-flag', function(e) {
        e.preventDefault();
        var transaction_id = $(this).data('id');

        if (!confirm('{{ __("treasury::lang.confirm_remove_due_flag") }}')) {
            return;
        }

        $.ajax({
            method: 'POST',
            url: '{{ route("treasury.due-transactions.toggle") }}',
            data: {
                _token: '{{ csrf_token() }}',
                transaction_id: transaction_id,
                is_due: false
            },
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                    due_transactions_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(xhr) {
                toastr.error('{{ __("messages.something_went_wrong") }}');
            }
        });
    });
});
</script>
@endsection
