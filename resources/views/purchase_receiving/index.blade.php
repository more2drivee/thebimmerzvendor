@extends('layouts.app')

@section('title', __('purchase.receive_purchases'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('purchase.receive_purchases')
            <small></small>
        </h1>
    </section>

    <!-- Main content -->
    <section class="no-print">
        <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#purchase-receiving-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                        <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="{{ route('purchase_receiving.index') }}"><i class="fa fa-truck-loading"></i> {{ __('purchase.receive_purchases') }}</a>
                </div>
                <div class="collapse navbar-collapse" id="purchase-receiving-navbar">
                    <ul class="nav navbar-nav d-block" style="position: relative !important;">
                        <li class="active">
                            <a href="{{ route('purchase_receiving.index') }}">
                                <i class="fa fa-list"></i> {{ __('purchase.all_purchases') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </section>
    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_receiving_filter_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('purchase_receiving_filter_location_id', $business_locations, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_receiving_filter_supplier_id', __('purchase.supplier') . ':') !!}
                    {!! Form::select('purchase_receiving_filter_supplier_id', $suppliers, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_receiving_filter_status', __('purchase.purchase_status') . ':') !!}
                    {!! Form::select('purchase_receiving_filter_status', $orderStatuses, 'pending', [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_receiving_filter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('purchase_receiving_filter_date_range', null, [
                        'placeholder' => __('lang_v1.select_a_date_range'),
                        'class' => 'form-control',
                        'readonly',
                    ]) !!}
                </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-primary', 'title' => __('purchase.receive_purchases')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="purchase_receiving_table">
                    <thead>
                        <tr>
                            <th>{{ __('purchase.ref_no') }}</th>
                            <th>{{ __('purchase.date') }}</th>
                            <th>{{ __('contact.supplier') }}</th>
                            <th>{{ __('purchase.asked_qty') }}</th>
                            <th>{{ __('purchase.total_received') }}</th>
                            <th>{{ __('purchase.remaining') }}</th>
                            <th>{{ __('purchase.progress') }}</th>
                     
                            <th>{{ __('lang_v1.status') }}</th>
                            <th>{{ __('lang_v1.action') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent

<!-- View Purchase Lines Modal -->
<div class="modal fade" id="view_lines_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{{ __('purchase.purchase_lines') }}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="purchase_details"></div>
                <hr>
                <form id="view_purchase_form">
                    <input type="hidden" id="view_purchase_id" name="purchase_id">
                    <div class="form-group">
                        <label>{{ __('contact.supplier') }}:</label>
                        <select class="form-control select2" id="view_supplier_id" name="supplier_id" style="width: 100%;">
                            <option value="">{{ __('lang_v1.select_supplier') }}</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('product.product_name') }}</th>
                                    <th>{{ __('product.sku') }}</th>
                                    <th>{{ __('purchase.asked_qty') }}</th>
                                    <th>{{ __('purchase.purchase_quantity') }}</th>
                                    <th>{{ __('purchase.remaining_qty') }}</th>
                                </tr>
                            </thead>
                            <tbody id="purchase_lines_body"></tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
                <button type="button" class="btn btn-primary" id="update_received_qty">
                    <i class="fas fa-save"></i> {{ __('purchase.update_received') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Receive Purchase Modal -->
<div class="modal fade" id="receive_purchase_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{{ __('purchase.receive_remaining') }}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="receive_purchase_details"></div>
                <hr>
                <form id="receive_purchase_form">
                    <input type="hidden" id="receive_purchase_id" name="purchase_id">
                    <div class="form-group">
                        <label>{{ __('contact.supplier') }}:</label>
                        <select class="form-control select2" id="receive_supplier_id" name="supplier_id" style="width: 100%;">
                            <option value="">{{ __('lang_v1.select_supplier') }}</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('product.product_name') }}</th>
                                    <th>{{ __('product.sku') }}</th>
                                    <th>{{ __('purchase.asked_qty') }}</th>
                                    <th>{{ __('purchase.received_qty') }}</th>
                                    <th>{{ __('purchase.remaining_qty') }}</th>
                                    <th>{{ __('purchase.qty_to_receive') }}</th>
                                   
                                </tr>
                            </thead>
                            <tbody id="receive_lines_body"></tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="button" class="btn btn-info" id="receive_all_remaining">
                    <i class="fas fa-layer-group"></i> {{ __('purchase.receive_all_remaining') }}
                </button>
                <button type="button" class="btn btn-success" id="confirm_receive">
                    <i class="fas fa-check"></i> {{ __('purchase.confirm_receive') }}
                </button>
            </div>
        </div>
    </div>
</div>

    <section id="receipt_section" class="print_section"></section>

    <!-- /.content -->
@endsection

@section('javascript')
    <script>
$(document).ready(function() {
    var purchase_receiving_table = $('#purchase_receiving_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('purchase_receiving.data') }}",
            data: function(d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.location_id = $('#purchase_receiving_filter_location_id').val();
                d.supplier_id = $('#purchase_receiving_filter_supplier_id').val();
                d.status = $('#purchase_receiving_filter_status').val();
                d.date_range = $('#purchase_receiving_filter_date_range').val();
            }
        },
        columns: [
            { data: 'ref_no', name: 'ref_no' },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'supplier', name: 'supplier' },
            { data: 'total_asked', name: 'total_asked' },
            { data: 'total_received', name: 'total_received' },
            { data: 'remaining', name: 'remaining' },
            { data: 'progress', name: 'progress' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        language: {
            url: "{{ asset('js/datatables/' . app()->getLocale() . '.json') }}",
            search: "_INPUT_",
            searchPlaceholder: "{{ __('lang_v1.search') }}"
        },
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-5'i><'col-sm-7'p>>"
    });

    // Filter change handlers
    $('#purchase_receiving_filter_location_id, #purchase_receiving_filter_supplier_id, #purchase_receiving_filter_status').on('change', function() {
        purchase_receiving_table.ajax.reload();
    });

    //Date range as a button
    $('#purchase_receiving_filter_date_range').daterangepicker(
        dateRangeSettings,
        function(start, end) {
            $('#purchase_receiving_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            purchase_receiving_table.ajax.reload();
        }
    );
    $('#purchase_receiving_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#purchase_receiving_filter_date_range').val('');
        purchase_receiving_table.ajax.reload();
    });

    // View purchase lines
    $(document).on('click', '.view-purchase-lines', function(e) {
        e.preventDefault();
        var purchase_id = $(this).data('id');
        
        // Get suppliers from filter dropdown
        var suppliers = [];
        $('#purchase_receiving_filter_supplier_id option').each(function() {
            if ($(this).val()) {
                suppliers.push({
                    id: $(this).val(),
                    name: $(this).text()
                });
            }
        });
        
        $.ajax({
            url: "/purchase-receiving/" + purchase_id + "/lines",
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    var purchase = response.purchase;
                    var lines = response.lines;
                    
                    $('#view_purchase_id').val(purchase_id);
                    
                    // Populate supplier dropdown
                    var supplierOptions = '<option value="">{{ __('lang_v1.select_supplier') }}</option>';
                    suppliers.forEach(function(supplier) {
                        var selected = (purchase.supplier_id && supplier.id == purchase.supplier_id) ? 'selected' : '';
                        supplierOptions += '<option value="' + supplier.id + '" ' + selected + '>' + supplier.name + '</option>';
                    });
                    $('#view_supplier_id').html(supplierOptions).select2({
                        width: '100%',
                        placeholder: '{{ __('lang_v1.select_supplier') }}',
                        allowClear: true,
                        dropdownParent: $('#view_lines_modal')
                    });
                    
                    var detailsHtml = '<div class="row">' +
                        '<div class="col-md-6"><strong>{{ __('purchase.ref_no') }}:</strong> ' + purchase.ref_no + '</div>' +
                        '<div class="col-md-6"><strong>{{ __('purchase.date') }}:</strong> ' + purchase.transaction_date + '</div>' +
                        '<div class="col-md-6"><strong>{{ __('contact.supplier') }}:</strong> ' + purchase.supplier + '</div>' +
                        '<div class="col-md-4"><strong>{{ __('purchase.asked_qty') }}:</strong> ' + purchase.asked_qty + '</div>' +
                        '<div class="col-md-4"><strong>{{ __('purchase.total_received') }}:</strong> ' + purchase.total_received + '</div>' +
                        '<div class="col-md-4"><strong>{{ __('purchase.total_remaining') }}:</strong> ' + purchase.total_remaining + '</div>' +
                    '</div>';
                    
                    $('#purchase_details').html(detailsHtml);
                    
                    var linesHtml = '';
                    lines.forEach(function(line) {
                        var askedQty = parseFloat(line.asked_qty);
                        var receivedQty = parseFloat(line.received_qty);
                        var remaining = askedQty - receivedQty;
                        
                        linesHtml += '<tr>' +
                            '<td>' + line.product_name + '</td>' +
                            '<td>' + line.sku + '</td>' +
                            '<td>' + line.asked_qty + '</td>' +
                            '<td><input type="number" class="form-control input-sm view-received-qty" data-line-id="' + line.id + '" data-asked-qty="' + askedQty + '" min="0" max="' + askedQty + '" step="0.01" value="' + receivedQty + '"></td>' +
                            '<td class="view-remaining-qty">' + remaining.toFixed(2) + '</td>' +
                        '</tr>';
                    });
                    
                    $('#purchase_lines_body').html(linesHtml);
                    $('#view_lines_modal').modal('show');
                } else {
                    toastr.error(response.message || 'Failed to load purchase lines');
                }
            },
            error: function() {
                toastr.error('Failed to load purchase lines');
            }
        });
    });

    // Update received qty when changed in view modal
    $(document).on('input change', '.view-received-qty', function() {
        var $input = $(this);
        var lineId = $input.data('line-id');
        var askedQty = parseFloat($input.data('asked-qty'));
        var receivedQty = parseFloat($input.val()) || 0;
        
        // Validate
        if (receivedQty < 0) {
            receivedQty = 0;
            $input.val(0);
        }
        
        if (receivedQty > askedQty) {
            receivedQty = askedQty;
            $input.val(askedQty);
        }
        
        // Update remaining
        var remaining = askedQty - receivedQty;
        $input.closest('tr').find('.view-remaining-qty').text(remaining.toFixed(2));
    });

    // Update received qty button
    $('#update_received_qty').on('click', function() {
        var purchase_id = $('#view_purchase_id').val();
        var supplier_id = $('#view_supplier_id').val();
        var lines = [];
        var hasError = false;

        // Validation: supplier must be selected
        if (!supplier_id || supplier_id === '') {
            toastr.error('Please select a supplier');
            hasError = true;
            return false;
        }

        $('.view-received-qty').each(function() {
            var line_id = $(this).data('line-id');
            var received_qty = $(this).val();
            var asked_qty = $(this).data('asked-qty');
            
            lines.push({
                line_id: line_id,
                to_receive: received_qty,
                asked_qty: asked_qty
            });
        });
        
        if (lines.length === 0) {
            toastr.error('No lines to update');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: "{{ route('purchase_receiving.receive') }}",
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                purchase_id: purchase_id,
                supplier_id: supplier_id,
                lines: lines
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#view_lines_modal').modal('hide');
                    purchase_receiving_table.ajax.reload();
                } else {
                    toastr.error(response.message || 'Failed to update received quantity');
                }
            },
            error: function() {
                toastr.error('Failed to update received quantity');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> {{ __('purchase.update_received') }}');
            }
        });
    });

    // Receive purchase
    $(document).on('click', '.receive-purchase', function(e) {
        e.preventDefault();
        var purchase_id = $(this).data('id');
        
        // Get suppliers from filter dropdown
        var suppliers = [];
        $('#purchase_receiving_filter_supplier_id option').each(function() {
            if ($(this).val()) {
                suppliers.push({
                    id: $(this).val(),
                    name: $(this).text()
                });
            }
        });
        
        $.ajax({
            url: "/purchase-receiving/" + purchase_id + "/lines",
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    var purchase = response.purchase;
                    var lines = response.lines;
                    
                    $('#receive_purchase_id').val(purchase_id);
                    
                    // Populate supplier dropdown
                    var supplierOptions = '<option value="">{{ __('lang_v1.select_supplier') }}</option>';
                    suppliers.forEach(function(supplier) {
                        var selected = (purchase.supplier_id && supplier.id == purchase.supplier_id) ? 'selected' : '';
                        supplierOptions += '<option value="' + supplier.id + '" ' + selected + '>' + supplier.name + '</option>';
                    });
                    $('#receive_supplier_id').html(supplierOptions).select2({
                        width: '100%',
                        placeholder: '{{ __('lang_v1.select_supplier') }}',
                        allowClear: true,
                        dropdownParent: $('#receive_purchase_modal')
                    });
                    
                    var detailsHtml = '<div class="row">' +
                        '<div class="col-md-6"><strong>{{ __('purchase.ref_no') }}:</strong> ' + purchase.ref_no + '</div>' +
                        '<div class="col-md-6"><strong>{{ __('purchase.date') }}:</strong> ' + purchase.transaction_date + '</div>' +
                        '<div class="col-md-6"><strong>{{ __('contact.supplier') }}:</strong> ' + purchase.supplier + '</div>' +
                        '<div class="col-md-4"><strong>{{ __('purchase.asked_qty') }}:</strong> ' + purchase.asked_qty + '</div>' +
                        '<div class="col-md-4"><strong>{{ __('purchase.total_received') }}:</strong> ' + purchase.total_received + '</div>' +
                        '<div class="col-md-4"><strong>{{ __('purchase.total_remaining') }}:</strong> ' + purchase.total_remaining + '</div>' +
                    '</div>';
                    
                    $('#receive_purchase_details').html(detailsHtml);
                    
                    var linesHtml = '';
                    lines.forEach(function(line) {
                        var askedQty = parseFloat(line.asked_qty);
                        var receivedQty = parseFloat(line.received_qty);
                        var remaining = askedQty - receivedQty;
                        linesHtml += '<tr>' +
                            '<td>' + line.product_name + '</td>' +
                            '<td>' + line.sku + '</td>' +
                            '<td>' + line.asked_qty + '</td>' +
                            '<td>' + line.received_qty + '</td>' +
                            '<td>' + line.remaining_qty + '</td>' +
                            '<td><input type="number" class="form-control input-sm to-receive" data-line-id="' + line.id + '" data-asked-qty="' + askedQty + '" data-current-received="' + receivedQty + '" min="0" max="' + askedQty + '" step="0.01" value="' + receivedQty + '"></td>' +
                        
                        '</tr>';
                    });
                    
                    $('#receive_lines_body').html(linesHtml);
                    $('#receive_purchase_modal').modal('show');
                } else {
                    toastr.error(response.message || 'Failed to load purchase lines');
                }
            },
            error: function() {
                toastr.error('Failed to load purchase lines');
            }
        });
    });

    // Receive all remaining
    $('#receive_all_remaining').on('click', function() {
        $('.to-receive').each(function() {
            var $input = $(this);
            var askedQty = parseFloat($input.data('asked-qty'));
            $input.val(askedQty);
        });
        toastr.info('All remaining quantities filled');
    });

    // Confirm receive
    $('#confirm_receive').on('click', function() {
        var purchase_id = $('#receive_purchase_id').val();
        var supplier_id = $('#receive_supplier_id').val();
        var lines = [];
        var hasError = false;

        // Validation: supplier must be selected
        if (!supplier_id || supplier_id === '') {
            toastr.error('Please select a supplier');
            hasError = true;
            return false;
        }

        $('.to-receive').each(function() {
            var $input = $(this);
            var line_id = $input.data('line-id');
            var askedQty = parseFloat($input.data('asked-qty'));
            var currentReceived = parseFloat($input.data('current-received'));
            var to_receive = parseFloat($input.val());

            // Validation: cannot be zero or negative
            if (isNaN(to_receive) || to_receive <= 0) {
                toastr.error('Received quantity must be greater than zero');
                hasError = true;
                return false;
            }

            // Validation: cannot exceed asked quantity
            if (to_receive > askedQty) {
                toastr.error('Received quantity cannot exceed required quantity (' + askedQty + ')');
                hasError = true;
                return false;
            }

            lines.push({
                line_id: line_id,
                to_receive: to_receive
            });
        });

        if (hasError) {
            return;
        }

        if (lines.length === 0) {
            toastr.error('No lines to receive');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: "{{ route('purchase_receiving.receive') }}",
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                purchase_id: purchase_id,
                supplier_id: supplier_id,
                lines: lines
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#receive_purchase_modal').modal('hide');
                    purchase_receiving_table.ajax.reload();
                } else {
                    toastr.error(response.message || 'Failed to receive purchase');
                }
            },
            error: function() {
                toastr.error('Failed to receive purchase');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i> {{ __('purchase.confirm_receive') }}');
            }
        });
    });
});
</script>
@endsection