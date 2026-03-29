<div class="modal-dialog modal-lg" role="document" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                <i class="fas fa-info-circle"></i>
                {{ __('repair::lang.restore_preview') }}
            </h4>
        </div>
        <div class="modal-body">
            <!-- Transaction Info -->
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('repair::lang.transaction_details') }}</h3>
                </div>
                <div class="box-body">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <th width="30%">{{ __('sale.invoice_no') }}</th>
                            <td>{{ $data['transaction']['invoice_no'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('account.transaction_type') }}</th>
                            <td>
                                {{ $data['transaction']['type'] ?? '' }}
                                @if(!empty($data['transaction']['sub_type']))
                                    - {{ $data['transaction']['sub_type'] }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('receipt.date') }}</th>
                            <td>{{ $data['transaction']['transaction_date'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('repair::lang.total') }}</th>
                            <td>{{ $data['transaction']['final_total'] ?? '0' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('sale.payment_status') }}</th>
                            <td>
                                <span class="label @if($data['payments']['payment_status'] == 'paid') label-success @elseif($data['payments']['payment_status'] == 'partial') label-warning @else label-danger @endif">
                                    {{ $data['payments']['payment_status'] }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Inventory Check -->
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-boxes"></i>
                        {{ __('repair::lang.inventory_check') }}
                    </h3>
                </div>
                <div class="box-body">
                    <div class="alert @if($data['inventory']['can_restore_all']) alert-success @else alert-warning @endif">
                        @if($data['inventory']['can_restore_all'])
                            <i class="fas fa-check-circle"></i>
                            {{ __('repair::lang.all_items_available') }}
                        @else
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ __('repair::lang.some_items_not_available') }}
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>{{ __('repair::lang.items_with_stock') }}</th>
                                    <td>{{ $data['inventory']['lines_with_stock'] }} / {{ $data['inventory']['total_lines'] }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>{{ __('repair::lang.items_without_stock') }}</th>
                                    <td>{{ $data['inventory']['lines_without_stock'] }} / {{ $data['inventory']['total_lines'] }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h4>{{ __('product_name') }} - {{ __('repair::lang.select_items_to_restore') }}</h4>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="select_all_items" checked>
                                </th>
                                <th>{{ __('product.product_name') }}</th>
                   
                                <th>{{ __('repair::lang.required_qty') }}</th>
                                <th>{{ __('repair::lang.available_qty') }}</th>
                                <th>{{ __('repair::lang.shortage') }}</th>
                                <th>{{ __('repair::lang.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['inventory']['all_items'] as $item)
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-checkbox" 
                                           data-source="{{ $item['source'] }}"
                                           data-product-id="{{ $item['product_id'] }}"
                                           data-variation-id="{{ $item['variation_id'] }}"
                                           data-quantity="{{ $item['quantity'] }}"
                                           checked>
                                </td>
                                <td>{{ $item['product_name'] }}</td>
                     
                                <td>{{ $item['quantity'] }}</td>
                                <td>{{ $item['available_qty'] }}</td>
                                <td>
                                    @if($item['shortage'] > 0)
                                        <strong>{{ $item['shortage'] }}</strong>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td>
                                    @if($item['can_restore'])
                                        <span class="label label-success">{{ __('repair::lang.in_stock') }}</span>
                                    @else
                                        <span class="label label-warning">{{ __('repair::lang.insufficient_stock') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payments Info -->
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-money-bill"></i>
                        {{ __('repair::lang.payment_info') }}
                    </h3>
                </div>
                <div class="box-body">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <th width="50%">{{ __('repair::lang.has_payments') }}</th>
                            <td>
                                @if($data['payments']['has_payments'])
                                    <span>{{ __('lang_v1.yes') }}</span>
                                    ({{ $data['payments']['payments_count'] }} {{ __('repair::lang.payments') }})
                                @else
                                    <span>{{ __('lang_v1.no') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('repair::lang.total_paid') }}</th>
                            <td>{{ $data['payments']['total_paid'] ?? '0' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('repair::lang.payment_status') }}</th>
                            <td>
                                <span class="label @if($data['payments']['payment_status'] == 'paid') label-success @elseif($data['payments']['payment_status'] == 'partial') label-warning @else label-danger @endif">
                                    {{ $data['payments']['payment_status'] }}
                                </span>
                            </td>
                        </tr>
                        @if($data['payments']['has_payments'])
                        <tr>
                            <th>{{ __('repair::lang.restore_payments') }}</th>
                            <td>
                                @if($data['payments']['all_payments_restorable'])
                                    <span><i class="fas fa-check-circle"></i> {{ __('repair::lang.all_payments_can_be_restored') }}</span>
                                @else
                                    <span><i class="fas fa-exclamation-triangle"></i> {{ __('repair::lang.some_payments_already_exist') }}</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Restore Options -->
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-cogs"></i>
                        {{ __('repair::lang.restore_options') }}
                    </h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="restore_payments" value="1" checked>
                            {{ __('repair::lang.restore_transaction_payments') }}
                        </label>
                        <p class="help-block">{{ __('repair::lang.restore_payments_help') }}</p>
                    </div>

                    @if(!$data['inventory']['can_restore_all'])
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        {{ __('repair::lang.restore_with_shortage_warning') }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">
                <i class="fas fa-times"></i> {{ __('messages.cancel') }}
            </button>
            <button type="button" class="btn btn-success" id="confirm_restore">
                <i class="fas fa-undo"></i> {{ __('repair::lang.restore_transaction') }}
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Select all / deselect all items
        $('#select_all_items').on('change', function() {
            $('.item-checkbox').prop('checked', $(this).prop('checked'));
        });

        // Update select all checkbox when individual items change
        $('.item-checkbox').on('change', function() {
            if ($('.item-checkbox:checked').length === $('.item-checkbox').length) {
                $('#select_all_items').prop('checked', true);
            } else {
                $('#select_all_items').prop('checked', false);
            }
        });

        $('#confirm_restore').on('click', function() {
            var restorePayments = $('#restore_payments').is(':checked') ? 1 : 0;
            var transactionId = '{{ $data["transaction"]["id"] }}';

            // Collect selected items
            var selectedItems = [];
            $('.item-checkbox:checked').each(function() {
                selectedItems.push({
                    source: $(this).data('source'),
                    product_id: $(this).data('product-id'),
                    variation_id: $(this).data('variation-id'),
                    quantity: $(this).data('quantity')
                });
            });

            if (selectedItems.length === 0) {
                toastr.error("{{__('repair::lang.select_at_least_one_item')}}");
                return;
            }

            $.ajax({
                method: 'POST',
                url: '/repair/recycle-bin/restore-transaction-with-options/' + transactionId,
                data: {
                    restore_options: {
                        restore_payments: restorePayments,
                        selected_items: selectedItems
                    }
                },
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        $('.view_modal').modal('hide');
                        $('#unified_recycle_bin_table').DataTable().ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function(xhr) {
                    var errorMsg = xhr.responseJSON ? xhr.responseJSON.msg : "{{__('messages.something_went_wrong')}}";
                    toastr.error(errorMsg);
                }
            });
        });
    });
</script>
