@extends('layouts.app')

@section('title', __('treasury::lang.opening_balance'))

@section('content')
      @include('treasury::layouts.nav')

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-left-primary shadow">
         
                <div class="card-body">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#opening_balance_modal">
                        <i class="fas fa-plus"></i> @lang('treasury::lang.add_opening_balance')
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">@lang('treasury::lang.opening_balance') @lang('messages.list')</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="opening_balance_table">
                            <thead class="bg-light">
                                <tr>
                                    <th>@lang('treasury::lang.reference_no')</th>
                                    <th>@lang('treasury::lang.transaction_date')</th>
                                    <th>@lang('treasury::lang.branch')</th>
                                    <th>@lang('treasury::lang.payment_method')</th>
                                    <th>@lang('treasury::lang.amount')</th>
                                    <th>@lang('treasury::lang.notes')</th>
                                    <th>@lang('messages.actions')</th>
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

<!-- Opening Balance Modal -->
@include('treasury::opening_balance.create_modal')


@endsection
@section('javascript')
<script>
    $(document).ready(function() {
        // CSRF setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Initialize DataTable
        var openingBalanceTable = $('#opening_balance_table').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '{{ route("treasury.opening-balance.transactions") }}',
                dataSrc: function(json) {
                    return json.data || [];
                }
            },
            columns: [
                { data: 'invoice_no' },
                { data: 'transaction_date' },
                { data: 'location' },
                { data: 'payment_method' },
                { data: 'amount' },
                { data: 'notes' },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        var id = row.id;
                        return '<button class="btn btn-danger btn-sm btn-delete-opening-balance" data-id="' + id + '"><i class="fas fa-trash"></i></button>';
                    }
                }
            ],
            order: [[1, 'desc']]
        });

        // Delete action
        $(document).on('click', '.btn-delete-opening-balance', function() {
            var id = $(this).data('id');
            if (!id) return;
            if (!confirm('{{ __("messages.are_you_sure") }}')) return;

            $.ajax({
                url: '{{ url("treasury/opening-balance") }}/' + id,
                method: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        if (window.toastr) toastr.success(response.message || '{{ __("messages.success") }}');
                        openingBalanceTable.ajax.reload();
                    } else {
                        if (window.toastr) toastr.error(response.message || '{{ __("messages.something_went_wrong") }}');
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ __("messages.something_went_wrong") }}';
                    if (window.toastr) toastr.error(msg);
                }
            });
        });

        // Modal events
        $('#opening_balance_modal').on('shown.bs.modal', function () {
            loadOpeningBalanceModalData();
        });

        $('#opening_balance_location_id').on('change', function() {
            loadOpeningBalanceModalData();
        });

        // Submit opening balance
        $('#submit_opening_balance').on('click', function() {
            var form = $('#opening_balance_form');
            if (form[0].checkValidity()) {
                var data = form.serialize();
                $.post('{{ route("treasury.opening-balance.store") }}', data)
                    .done(function(response) {
                        if (response.success) {
                            if (window.toastr) toastr.success(response.message || '{{ __("messages.success") }}');
                            $('#opening_balance_modal').modal('hide');
                            openingBalanceTable.ajax.reload();
                        } else {
                            if (window.toastr) toastr.error(response.message || '{{ __("messages.something_went_wrong") }}');
                        }
                    })
                    .fail(function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ __("messages.something_went_wrong") }}';
                        if (window.toastr) toastr.error(msg);
                    });
            } else {
                form[0].reportValidity();
            }
        });

        function loadOpeningBalanceModalData() {
            var locationId = $('#opening_balance_location_id').val() || '';
            $.ajax({
                url: '{{ route("treasury.opening-balance.data") }}',
                method: 'GET',
                data: { location_id: locationId },
                success: function(response) {
                    if (!response.success) return;

                    // Populate payment methods
                    var methods = response.payment_methods || {};
                    var $pmSelect = $('#opening_balance_payment_method');
                    $pmSelect.empty().append('<option value="">{{ __("treasury::lang.select_payment_method") }}</option>');
                    Object.keys(methods).forEach(function(key) {
                        var label = methods[key];
                        $pmSelect.append('<option value="' + key + '">' + label + '</option>');
                    });

                    // Populate balances
                    var balances = response.current_balances || {};
                    var $container = $('#current_balances_container');
                    $container.empty();

                    var items = Array.isArray(balances) ? balances : [];

                    if (!items.length) {
                        $container.append('<div class="col-md-12"><div class="alert alert-warning">{{ __("treasury::lang.no_balances_available") }}</div></div>');
                    } else {
                        items.forEach(function(item) {
                            var methodName = item.name || (methods[item.id] || item.id);
                            var amountFormatted = (typeof __currency_trans_from_en === 'function') ? __currency_trans_from_en(Number(item.balance || 0), true) : (Number(item.balance || 0)).toLocaleString();
                            var card = '<div class="col-md-4 mb-3">\
                                <div class="card">\
                                    <div class="card-body">\
                                        <h6 class="card-title">' + methodName + '</h6>\
                                        <p class="card-text"><span class="display_currency" data-currency_symbol="true">' + amountFormatted + '</span></p>\
                                    </div>\
                                </div>\
                            </div>';
                            $container.append(card);
                        });
                    }
                },
                error: function() {
                    if (window.toastr) toastr.error('{{ __("messages.something_went_wrong") }}');
                }
            });
        }
    });
</script>
@endsection
