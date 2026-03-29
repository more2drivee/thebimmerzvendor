@extends('layouts.app')
@section('title', __('treasury::lang.internal_transfers'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('treasury::lang.internal_transfers') }}
        <small>{{ __('treasury::lang.manage_your_data', ['data' => __('treasury::lang.internal_transfers')]) }}</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('treasury::lang.internal_transfers')])
        @slot('tool')
            <div class="box-tools">
                @if(auth()->user()->can('treasury.create'))
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#internal_transfer_modal">
                        <i class="fas fa-plus"></i> @lang('treasury::lang.add_internal_transfer')
                    </button>
                @endif
                <a class="btn btn-primary btn-sm" href="{{ route('treasury.index') }}">
                    <i class="fa fa-arrow-left"></i> @lang('treasury::lang.back_to_dashboard')
                </a>
            </div>
        @endslot

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="transfer_date_filter">@lang('messages.date'):</label>
                    <input type="text" class="form-control" id="transfer_date_filter" placeholder="@lang('messages.filter_by_date')">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="transfer_payment_method_filter">@lang('treasury::lang.payment_method'):</label>
                    <select class="form-control" id="transfer_payment_method_filter">
                        <option value="">@lang('messages.all')</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="transfer_amount_filter">@lang('treasury::lang.amount_range'):</label>
                    <select class="form-control" id="transfer_amount_filter">
                        <option value="">@lang('messages.all')</option>
                        <option value="0-100">0 - 100</option>
                        <option value="100-500">100 - 500</option>
                        <option value="500-1000">500 - 1000</option>
                        <option value="1000+">1000+</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-primary btn-sm" id="apply_transfer_filters">
                            <i class="fas fa-filter"></i> @lang('treasury::lang.filter')
                        </button>
                        <button type="button" class="btn btn-default btn-sm" id="clear_transfer_filters">
                            <i class="fas fa-times" style="font-size: 15px !important;"></i> @lang('messages.clear')
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="internal_transfers_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('treasury::lang.from_method')</th>
                        <th>@lang('treasury::lang.to_method')</th>
                        <th>@lang('sale.total_amount')</th>
                        <th>@lang('treasury::lang.notes')</th>
                        <th>@lang('treasury::lang.created_by')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

     <!-- Add Internal Transfer Modal -->
    @include('treasury::internal_transfers.create_modal') 

    <!-- View Modal -->
    <div class="modal fade" id="view_transfer_modal" tabindex="-1" role="dialog" aria-labelledby="viewTransferModalLabel"></div>

    <!-- Edit Modal -->
    <div class="modal fade" id="edit_transfer_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
</section>

@endsection

@section('javascript')
<script type="text/javascript">

    $(document).ready(function() {

        var internal_transfers_table;
        var PAYMENT_METHOD_LABELS = @json($payment_methods ?? []);

        function mapMethodLabel(m){
            return PAYMENT_METHOD_LABELS[m] || m;
        }

        // Initialize Internal Transfers Table
        function initializeInternalTransfersTable() {
            // Destroy existing table if it exists
            if ($.fn.DataTable.isDataTable('#internal_transfers_table')) {
                $('#internal_transfers_table').DataTable().destroy();
            }

            internal_transfers_table = $('#internal_transfers_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("treasury.internal.transfers.data") }}',
                    data: function(d) {
                        d.date_filter = $('#transfer_date_filter').val();
                        d.payment_method_filter = $('#transfer_payment_method_filter').val();
                        d.amount_filter = $('#transfer_amount_filter').val();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable Ajax Error:', error, thrown);
                        toastr.error('Error loading internal transfers data');
                    }
                },
                columns: [
                    { data: 'transaction_date', name: 'transaction_date' },
                    { data: 'from_method', name: 'from_method', render: function(d){ return mapMethodLabel(d); } },
                    { data: 'to_method', name: 'to_method', render: function(d){ return mapMethodLabel(d); } },
                    { data: 'amount', name: 'amount' },
                    { data: 'notes', name: 'notes' },
                    { data: 'created_by', name: 'created_by' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']], // Order by date descending
                pageLength: 25,
                responsive: true,
              
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#internal_transfers_table'));
                }
            });
        }

        function loadTransferFilters() {
            // Load payment methods for filter
            $.ajax({
                url: '{{ route("treasury.payment.method.balances") }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        var options = '<option value="">@lang("messages.all")</option>';
                        response.data.forEach(function(method) {
                            options += `<option value="${method.id}">${method.name}</option>`;
                        });
                        $('#transfer_payment_method_filter').html(options);
                    }
                },
                error: function() {
                    toastr.error('@lang("messages.something_went_wrong")');
                }
            });

            // Initialize date range picker for transfers filter
            $('#transfer_date_filter').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: moment_date_format
                }
            });

            $('#transfer_date_filter').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format(moment_date_format) + ' - ' + picker.endDate.format(moment_date_format));
            });

            $('#transfer_date_filter').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        }

        // Initialize table and filters on page load
        initializeInternalTransfersTable();
        loadTransferFilters();

        // Apply filters
        $('#apply_transfer_filters').click(function() {
            if (internal_transfers_table) {
                internal_transfers_table.ajax.reload();
            }
        });

        // Clear filters
        $('#clear_transfer_filters').click(function() {
            $('#transfer_date_filter').val('');
            $('#transfer_payment_method_filter').val('');
            $('#transfer_amount_filter').val('');
            if (internal_transfers_table) {
                internal_transfers_table.ajax.reload();
            }
        });

        // Handle view modal
        $(document).on('click', '.view-transfer', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            $.get(url, function(data) {
                $('#view_transfer_modal').html(data).modal('show');
            });
        });

        // Handle edit modal
        $(document).on('click', '.edit-transfer', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            $.get(url, function(data) {
                $('#edit_transfer_modal').html(data).modal('show');
            });
        });

        // Handle delete
        $(document).on('click', '.delete-transfer', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            swal({
                title: "{{ __('messages.sure') }}",
                text: "{{ __('messages.confirm_delete') }}",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url: url,
                        method: 'DELETE',
                        dataType: 'json',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                internal_transfers_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        // Ensure DataTable reloads after closing add/edit modals
        $('#internal_transfer_modal, #edit_transfer_modal').on('hidden.bs.modal', function () {
            if (typeof internal_transfers_table !== 'undefined' && internal_transfers_table) {
                internal_transfers_table.ajax.reload();
            }
        });

        // Load payment method balances when modal opens
        $('#internal_transfer_modal').on('show.bs.modal', function() {
            // Reset all forms
            $('#payment_transfer_form')[0].reset();
            if ($('#branch_transfer_form').length) {
                $('#branch_transfer_form')[0].reset();
            }
            
            // Reset to payment transfer by default
            $('input[name="transfer_type"][value="payment_transfer"]').prop('checked', true);
            $('#payment_transfer_tab').addClass('active').find('input').prop('checked', true);
            $('#branch_transfer_tab').removeClass('active').find('input').prop('checked', false);
            
            // Show payment transfer container, hide branch transfer
            $('#payment_transfer_form_container').show();
            $('#branch_transfer_form_container').hide();
            
            // Hide available balances for payment transfer mode until branch is selected
            $('#payment_method_cards_container').hide();
            
            // Hide branch balances
            $('#branch_balances_container').hide();
            
            // Don't load payment method balances initially for payment transfer
            switchTransferType('payment_transfer');
        });

        // Reset forms when modal closes
        $('#internal_transfer_modal').on('hidden.bs.modal', function() {
            $('#payment_transfer_form')[0].reset();
            if ($('#branch_transfer_form').length) {
                $('#branch_transfer_form')[0].reset();
            }
            $('#branch_balances_container').hide();
        });

        // Transfer type switching
        $('input[name="transfer_type"]').change(function() {
            var selectedType = $(this).val();
            switchTransferType(selectedType);
        });

        // Function to switch transfer type
        function switchTransferType(type) {
            if (type === 'payment_transfer') {
                $('#payment_transfer_form_container').show();
                $('#branch_transfer_form_container').hide();
                $('#payment_transfer_tab').addClass('active');
                $('#branch_transfer_tab').removeClass('active');
                
                // Hide available balances for payment transfers until branch is selected
                $('#payment_method_cards_container').hide();
                $('#branch_balances_container').hide();
                
                // Reset branch form
                if ($('#branch_transfer_form').length) {
                    $('#branch_transfer_form')[0].reset();
                }
            } else if (type === 'branch_transfer') {
                $('#payment_transfer_form_container').hide();
                $('#branch_transfer_form_container').show();
                $('#branch_transfer_tab').addClass('active');
                $('#payment_transfer_tab').removeClass('active');
                
                // Hide general available balances for branch transfers
                $('#payment_method_cards_container').hide();
                
                // Reset payment form
                if ($('#payment_transfer_form').length) {
                    $('#payment_transfer_form')[0].reset();
                }
                
                // Load payment methods for branch transfer
                loadBranchPaymentMethods();
            }
        }

        // Function to load payment methods for branch transfer
        function loadBranchPaymentMethods() {
            $.ajax({
                url: '{{ route("treasury.payment.method.balances") }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        var selectOptions = '<option value="">@lang("treasury::lang.select_payment_method")</option>';
                        response.data.forEach(function(method) {
                            selectOptions += `<option value="${method.id}">${method.name}</option>`;
                        });
                        $('#branch_payment_method').html(selectOptions);
                    }
                },
                error: function() {
                    toastr.error({!! json_encode(__('treasury::lang.failed_load_payment_methods')) !!});
                }
            });
        }

        // Handle branch selection for branch transfer
        $(document).on('change', '#from_location_id, #to_location_id', function() {
            var fromLocationId = $('#from_location_id').val();
            var toLocationId = $('#to_location_id').val();
            
            // Clear the branch balance display first
            $('#branch_balances_container').hide();
            
            // Check for same branch selection
            if (fromLocationId && toLocationId && fromLocationId === toLocationId) {
                toastr.warning({!! json_encode(__('treasury::lang.please_select_different_branches')) !!});
                $(this).val('');
                return;
            }
            
            // Load balances only if both branches are selected and different
            if (fromLocationId && toLocationId && fromLocationId !== toLocationId) {
                loadBranchBalancesForTransfer(fromLocationId, toLocationId);
            }
        });

        // Function to load branch balances for transfer
        function loadBranchBalancesForTransfer(fromLocationId, toLocationId) {
            // Load From Branch Balance
            if (fromLocationId) {
                $.ajax({
                    url: '{{ route("treasury.branch.payment.method.balances") }}',
                    method: 'GET',
                    data: { location_id: fromLocationId },
                    success: function(response) {
                        if (response.success) {
                            var fromBranchName = $('#from_location_id option:selected').text();
                            var fromBranchHtml = `
                                <div class="col-md-6 mb-3">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0">{{ __('treasury::lang.from_branch') }}: ${fromBranchName}</h6>
                                        </div>
                                        <div class="card-body">
                            `;
                            
                            response.data.forEach(function(method) {
                                var balanceFormatted = __currency_trans_from_en(method.balance, true);
                                var balanceClass = method.balance >= 0 ? 'text-success' : 'text-danger';
                                
                                fromBranchHtml += `
                                    <div class="row mb-2">
                                        <div class="col-8">${method.name}:</div>
                                        <div class="col-4 text-right">
                                            <span class="display_currency ${balanceClass}" data-currency_symbol="true">${method.balance}</span>
                                        </div>
                                    </div>
                                `;
                            });
                            
                            fromBranchHtml += `
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            updateBranchBalanceDisplay(fromBranchHtml, 'from');
                        }
                    },
                    error: function() {
                        console.log('Failed to load from branch balances');
                    }
                });
            }
            
            // Load To Branch Balance
            if (toLocationId && toLocationId !== fromLocationId) {
                $.ajax({
                    url: '{{ route("treasury.branch.payment.method.balances") }}',
                    method: 'GET',
                    data: { location_id: toLocationId },
                    success: function(response) {
                        if (response.success) {
                            var toBranchName = $('#to_location_id option:selected').text();
                            var toBranchHtml = `
                                <div class="col-md-6 mb-3">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">{{ __('treasury::lang.to_branch') }}: ${toBranchName}</h6>
                                        </div>
                                        <div class="card-body">
                            `;
                            
                            response.data.forEach(function(method) {
                                var balanceFormatted = __currency_trans_from_en(method.balance, true);
                                var balanceClass = method.balance >= 0 ? 'text-success' : 'text-danger';
                                
                                toBranchHtml += `
                                    <div class="row mb-2">
                                        <div class="col-8">${method.name}:</div>
                                        <div class="col-4 text-right">
                                            <span class="display_currency ${balanceClass}" data-currency_symbol="true">${method.balance}</span>
                                        </div>
                                    </div>
                                `;
                            });
                            
                            toBranchHtml += `
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            updateBranchBalanceDisplay(toBranchHtml, 'to');
                        }
                    },
                    error: function() {
                        console.log('Failed to load to branch balances');
                    }
                });
            }
        }
        
        // Function to update branch balance display
        function updateBranchBalanceDisplay(html, type) {
            var containerId = type === 'from' ? '#from_branch_balances' : '#to_branch_balances';
            
            // Create container if it doesn't exist
            if ($(containerId).length === 0) {
                var containerHtml = `
                    <div class="row mb-4" id="branch_balances_container">
                        <div class="col-md-12">
                            <h5>{{ __('treasury::lang.branch_balance') }}</h5>
                            <div class="row">
                                <div id="from_branch_balances"></div>
                                <div id="to_branch_balances"></div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Insert before the branch transfer form container
                $('#branch_transfer_form_container').before(containerHtml);
            }
            
            $(containerId).html(html);
            $('#branch_balances_container').show();
            __currency_convert_recursively($(containerId));
        }

        // Initialize date pickers for transfer dates
        $(document).on('focus', '#payment_transfer_date, #branch_transfer_date', function() {
            $(this).datepicker({
                autoclose: true,
                format: datepicker_date_format
            });
        });

        // Function to load payment method balances
        function loadPaymentMethodBalances() {
            $.ajax({
                url: '{{ route("treasury.get.payment.method.balances") }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Update payment method cards
                        var cardsHtml = '';
                        var selectOptions = '<option value="">@lang("treasury::lang.select_payment_method")</option>';

                        response.data.forEach(function(method) {
                            var balanceFormatted = __currency_trans_from_en(method.balance, true);
                            var balanceClass = method.balance >= 0 ? 'text-success' : 'text-danger';

                            // Create card
                            cardsHtml += `
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">${method.name}</h5>
                                            <p class="card-text">
                                                <span class="display_currency ${balanceClass}" data-currency_symbol="true">${method.balance}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            `;

                            // Add to select options with balance display
                            selectOptions += `<option value="${method.id}">${method.name} - ${balanceFormatted}</option>`;
                        });

                        $('#payment_method_cards').html(cardsHtml);
                        $('#from_payment_method, #to_payment_method').html(selectOptions);

                        // Convert currency display
                        __currency_convert_recursively($('#payment_method_cards'));
                    }
                },
                error: function() {
                    toastr.error({!! json_encode(__('treasury::lang.failed_load_payment_method_balances')) !!});
                }
            });
        }

        // Handle payment transfer location change to show branch-specific balances
        $(document).on('change', '#payment_transfer_location_id', function() {
            var locationId = $(this).val();
            
            if (locationId) {
                // Show available balances container when branch is selected
                $('#payment_method_cards_container').show();
                // Load branch-specific balances
                loadBranchSpecificBalances(locationId);
            } else {
                // Hide available balances when no branch is selected
                $('#payment_method_cards_container').hide();
                // Clear payment method selects
                $('#from_payment_method, #to_payment_method').html('<option value="">@lang("treasury::lang.select_payment_method")</option>');
            }
        });
        
        // Function to load branch-specific balances for payment method transfers
        function loadBranchSpecificBalances(locationId) {
            $.ajax({
                url: '{{ route("treasury.branch.payment.method.balances") }}',
                method: 'GET',
                data: { location_id: locationId },
                success: function(response) {
                    if (response.success) {
                        // Update payment method cards with branch-specific balances
                        var cardsHtml = '';
                        var selectOptions = '<option value="">@lang("treasury::lang.select_payment_method")</option>';
                        
                        response.data.forEach(function(method) {
                            var balanceFormatted = __currency_trans_from_en(method.balance, true);
                            var balanceClass = method.balance >= 0 ? 'text-success' : 'text-danger';
                            
                            // Create card with branch indicator
                            cardsHtml += `
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">${method.name}
                                            </h5>
                                            <p class="card-text">
                                                <span class="display_currency ${balanceClass}" data-currency_symbol="true">${method.balance}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            // Add to select options with balance display
                            selectOptions += `<option value="${method.id}">${method.name} - ${balanceFormatted}</option>`;
                        });
                        
                        $('#payment_method_cards').html(cardsHtml);
                        $('#from_payment_method, #to_payment_method').html(selectOptions);
                        
                        // Convert currency display
                        __currency_convert_recursively($('#payment_method_cards'));
                    }
                },
                error: function() {
                    toastr.error({!! json_encode(__('treasury::lang.failed_load_branch_balances')) !!});
                    // Fall back to general balances
                    loadPaymentMethodBalances();
                }
            });
        }

        // Handle transfer submission
        $('#submit_transfer').click(function() {
            var transferType = $('input[name="transfer_type"]:checked').val();
            
            if (transferType === 'payment_transfer') {
                submitPaymentTransfer();
            } else if (transferType === 'branch_transfer') {
                submitBranchTransfer();
            }
        });

        // Function to submit payment method transfer
        function submitPaymentTransfer() {
            var form = $('#payment_transfer_form');
            
            if (form[0].checkValidity()) {
                // Validate different payment methods
                var fromMethod = $('#from_payment_method').val();
                var toMethod = $('#to_payment_method').val();
                
                if (fromMethod === toMethod && fromMethod !== '') {
                    toastr.warning({!! json_encode(__('treasury::lang.please_select_different_payment_methods')) !!});
                    return;
                }
                
                var data = {
                    from_payment_method: fromMethod,
                    to_payment_method: toMethod,
                    amount: $('#payment_transfer_amount').val(),
                    date: $('#payment_transfer_date').val(),
                    notes: $('#payment_transfer_notes').val(),
                    location_id: $('#payment_transfer_location_id').val() || '' // Optional branch for payment transfer
                };

                submitTransferRequest(data);
            } else {
                form[0].reportValidity();
            }
        }

        // Function to submit branch transfer
        function submitBranchTransfer() {
            var form = $('#branch_transfer_form');
            
            if (form[0].checkValidity()) {
                // Validate different branches
                var fromBranch = $('#from_location_id').val();
                var toBranch = $('#to_location_id').val();
                
                if (!fromBranch || !toBranch) {
                    toastr.warning({!! json_encode(__('treasury::lang.please_select_both_branches')) !!});
                    return;
                }
                
                if (fromBranch === toBranch) {
                    toastr.warning({!! json_encode(__('treasury::lang.please_select_different_branches')) !!});
                    $('#to_location_id').val('');
                    return;
                }
                
                var paymentMethod = $('#branch_payment_method').val();
                if (!paymentMethod) {
                    toastr.warning({!! json_encode(__('treasury::lang.please_select_a_payment_method')) !!});
                    return;
                }
                
                var data = {
                    payment_method: paymentMethod, // Single payment method for branch transfer
                    amount: $('#branch_transfer_amount').val(),
                    date: $('#branch_transfer_date').val(),
                    notes: $('#branch_transfer_notes').val(),
                    from_location_id: fromBranch,
                    to_location_id: toBranch
                };

                submitTransferRequest(data);
            } else {
                form[0].reportValidity();
            }
        }

        // Function to submit transfer request
        function submitTransferRequest(data) {
            // Add CSRF token
            data._token = $('meta[name="csrf-token"]').attr('content');
            
            $.ajax({
                url: '{{ route("treasury.submit.internal.transfer") }}',
                method: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        $('#internal_transfer_modal').modal('hide');
                        // Reload payment method balances
                        loadPaymentMethodBalances();
                        // Reload table
                        if (internal_transfers_table) {
                            internal_transfers_table.ajax.reload();
                        }
                    } else {
                        toastr.error(response.msg);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        var errorMsg = '';
                        $.each(errors, function(key, value) {
                            errorMsg += value[0] + '\n';
                        });
                        toastr.error(errorMsg);
                    } else {
                        toastr.error('{{ __("messages.something_went_wrong") }}');
                    }
                }
            });
        }

        // Prevent selecting same payment method for from and to
        $(document).on('change', '#from_payment_method, #to_payment_method', function() {
            var fromVal = $('#from_payment_method').val();
            var toVal = $('#to_payment_method').val();
            
            if (fromVal === toVal && fromVal !== '') {
                toastr.warning({!! json_encode(__('treasury::lang.please_select_different_payment_methods')) !!});
                $(this).val('');
                $(this).focus();
            }
        });

        // Prevent selecting same branch for from and to (improved)
        $(document).on('change', '#from_location_id, #to_location_id', function() {
            var fromVal = $('#from_location_id').val();
            var toVal = $('#to_location_id').val();
            
            if (fromVal === toVal && fromVal !== '') {
                toastr.warning({!! json_encode(__('treasury::lang.please_select_different_branches')) !!});
                $(this).val('');
                $(this).focus();
                $('#branch_balances_container').hide();
            }
        });
        
        // Reset form properly when switching transfer types
        $(document).on('change', 'input[name="transfer_type"]', function() {
            var transferType = $(this).val();
            
            // Reset all forms completely
            $('#payment_transfer_form')[0].reset();
            if ($('#branch_transfer_form').length) {
                $('#branch_transfer_form')[0].reset();
            }
            
            // Set default dates
            $('#payment_transfer_date').val('{{ @format_date("now") }}');
            $('#branch_transfer_date').val('{{ @format_date("now") }}');
            
            // Hide balance displays
            $('#branch_balances_container').hide();
            $('#payment_method_cards_container').hide();
            
            // Clear payment method selects
            $('#from_payment_method, #to_payment_method').html('<option value="">@lang("treasury::lang.select_payment_method")</option>');
        });
    });
</script>
@endsection
