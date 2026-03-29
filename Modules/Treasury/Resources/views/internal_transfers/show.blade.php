<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">
                <i class="fas fa-exchange-alt"></i> @lang('treasury::lang.internal_transfer') - @lang('messages.view')
            </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('messages.date'):</label>
                        <p class="form-control-static">{{ @format_date($transaction->transaction_date) }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('sale.total_amount'):</label>
                        <p class="form-control-static">
                            <span class="display_currency" data-currency_symbol="true">{{ $transaction->final_total }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('treasury::lang.from_method'):</label>
                        <p class="form-control-static">{{ $from_method }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('treasury::lang.to_method'):</label>
                        <p class="form-control-static">{{ $to_method }}</p>
                    </div>
                </div>
            </div>

            @if(!empty($notes))
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>@lang('treasury::lang.notes'):</label>
                        <p class="form-control-static">{{ $notes }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('treasury::lang.created_by'):</label>
                        <p class="form-control-static">
                            {{ $transaction->sales_person ? $transaction->sales_person->first_name . ' ' . $transaction->sales_person->last_name : 'N/A' }}
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('messages.created_at'):</label>
                        <p class="form-control-static">{{ @format_datetime($transaction->created_at) }}</p>
                    </div>
                </div>
            </div>

            @if($transaction->updated_at != $transaction->created_at)
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>@lang('messages.updated_at'):</label>
                        <p class="form-control-static">{{ @format_datetime($transaction->updated_at) }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <div class="modal-footer">
            @if(auth()->user()->can('treasury.edit'))
                <button type="button" class="btn btn-primary edit-transfer-btn" data-href="{{ route('treasury.internal.transfers.edit', $transaction->id) }}">
                    <i class="fas fa-edit"></i> @lang('messages.edit')
                </button>
            @endif
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Convert currency display
        __currency_convert_recursively($('#view_transfer_modal'));

        // Handle edit button click
        $('.edit-transfer-btn').click(function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            $('#view_transfer_modal').modal('hide');
            
            $.get(url, function(data) {
                $('#edit_transfer_modal').html(data).modal('show');
            });
        });
    });
</script>
