<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">
                <i class="fas fa-exchange-alt"></i> @lang('treasury::lang.internal_transfer') - @lang('messages.edit')
            </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <form id="edit_internal_transfer_form">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_from_payment_method">@lang('treasury::lang.from_payment_method') <span class="text-danger">*</span></label>
                            <select class="form-control" name="from_payment_method" id="edit_from_payment_method" required>
                                <option value="">@lang('messages.please_select')</option>
                                @foreach($payment_types as $key => $value)
                                    <option value="{{ $key }}" {{ $key == $from_method_key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_to_payment_method">@lang('treasury::lang.to_payment_method') <span class="text-danger">*</span></label>
                            <select class="form-control" name="to_payment_method" id="edit_to_payment_method" required>
                                <option value="">@lang('messages.please_select')</option>
                                @foreach($payment_types as $key => $value)
                                    <option value="{{ $key }}" {{ $key == $to_method_key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_transfer_amount">@lang('treasury::lang.transfer_amount') <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="edit_transfer_amount"
                                   value="{{ number_format($transaction->final_total, 2, '.', '') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_transfer_date">@lang('treasury::lang.transfer_date') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="date" id="edit_transfer_date"
                                   value="{{ @format_date($transaction->transaction_date) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="edit_transfer_notes">@lang('treasury::lang.notes')</label>
                            <textarea class="form-control" name="notes" id="edit_transfer_notes" rows="3">{{ $notes }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> @lang('messages.update')
                </button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Initialize date picker
        $('#edit_transfer_date').datepicker({
            autoclose: true,
            format: datepicker_date_format
        });

        // Validate payment methods are different
        $('#edit_from_payment_method, #edit_to_payment_method').change(function() {
            var from_method = $('#edit_from_payment_method').val();
            var to_method = $('#edit_to_payment_method').val();
            
            if (from_method && to_method && from_method === to_method) {
                toastr.warning('From and To payment methods must be different');
                $(this).val('');
            }
        });

        // Handle form submission
        $('#edit_internal_transfer_form').submit(function(e) {
            e.preventDefault();
            
            var form = $(this);
            var formData = form.serialize();
            
            $.ajax({
                url: '{{ route("treasury.internal.transfers.update", $transaction->id) }}',
                method: 'PUT',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        $('#edit_transfer_modal').modal('hide');
                        
                        // Reload the table if it exists
                        if (typeof internal_transfers_table !== 'undefined' && internal_transfers_table) {
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
        });
    });
</script>
