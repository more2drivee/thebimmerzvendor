<div class="modal-dialog" role="document" style="max-width: 900px;">
    <div class="modal-content">
        <div class="modal-header text-center">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title" style="width:100%; text-align: center;">@lang('restaurant.job_estimator_details')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-sm-6">
                    <table class="table table-striped">
                        <tr>
                            <td width="30%"><strong>@lang('restaurant.customer'):</strong></td>
                            <td>{{ $estimator->customer->name ?? '' }}</td>
                        </tr>
                        <tr>
                            <td><strong>@lang('restaurant.vehicle'):</strong></td>
                            <td>{{ $device_model ? $device_model->name : '' }}</td>
                        </tr>
                        <tr>
                            <td><strong>@lang('restaurant.location'):</strong></td>
                            <td>{{ $estimator->location->name ?? '' }}</td>
                        </tr>
                        <tr>
                            <td><strong>@lang('restaurant.service_type'):</strong></td>
                            <td>{{ $estimator->serviceType->name ?? '' }}</td>
                        </tr>
                        <tr>
                            <td><strong>@lang('restaurant.estimator_status'):</strong></td>
                            <td>
                                <span class="label bg-{{ 
                                    $estimator->estimator_status == 'pending' ? 'warning' : 
                                    ($estimator->estimator_status == 'sent' ? 'info' : 
                                    ($estimator->estimator_status == 'approved' ? 'success' : 
                                    ($estimator->estimator_status == 'rejected' ? 'danger' : 'primary'))) 
                                }}">
                                    {{ $estimator->status_label }}
                                </span>
                            </td>
                        </tr>
                        @if(!is_null($estimator->amount))
                        <tr>
                            <td><strong>@lang('restaurant.amount'):</strong></td>
                            <td>{{ @num_format($estimator->amount) }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
              
            </div>

            @if($estimator->vehicle_details)
            <div class="row">
                <div class="col-sm-12">
                    <h4>@lang('restaurant.vehicle_details')</h4>
                    <p>{{ $estimator->vehicle_details }}</p>
                </div>
            </div>
            @endif

            <hr>

            <div class="row">
                <div class="col-sm-12">
                    <h4>@lang('restaurant.estimator_lines')</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('sale.product')</th>
                                    <th>@lang('restaurant.sku')</th>
                                    <th>@lang('lang_v1.quantity')</th>
                                    <th>@lang('product.unit')</th>
                                    <th>@lang('sale.unit_price')</th>
                                    <th>@lang('purchase.supplier')</th>
                                    <th>@lang('restaurant.approval')</th>
                                    <th>@lang('brand.note')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lines as $line)
                                    <tr>
                                        <td>{{ $line->product_name }}</td>
                                        <td>{{ $line->sku }}</td>
                                        <td>{{ @num_format($line->quantity) }}</td>
                                        <td>{{ $line->unit }}</td>
                                        <td>{{ @num_format($line->price) }}</td>
                                        <td>{{ $line->supplier_name }}</td>
                                        <td>
                                            <select class="form-control js-approval-select" data-line-id="{{ $line->line_id }}">
                                                <option value="1" {{ $line->client_approval ? 'selected' : '' }}>@lang('messages.yes')</option>
                                                <option value="0" {{ !$line->client_approval ? 'selected' : '' }}>@lang('messages.no')</option>
                                            </select>
                                        </td>
                                        <td>{{ $line->notes }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">@lang('messages.no_data_found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

<script>
    $(function() {
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        $(document).off('change', '.js-approval-select').on('change', '.js-approval-select', function() {
            var $el = $(this);
            var lineId = $el.data('line-id');
            var val = $el.val();

            $el.prop('disabled', true);

            $.ajax({
                url: '{{ url('job-estimator/line') }}/' + lineId + '/approval',
                type: 'PUT',
                data: { client_approval: val },
                success: function(res) {
                    if (res && res.success) {
                        if (typeof toastr !== 'undefined') { toastr.success(res.msg || '{{ __('lang_v1.updated_success') }}'); }
                    } else {
                        if (typeof toastr !== 'undefined') { toastr.error('{{ __('messages.something_went_wrong') }}'); }
                    }
                },
                error: function() {
                    if (typeof toastr !== 'undefined') { toastr.error('{{ __('messages.something_went_wrong') }}'); }
                },
                complete: function() {
                    $el.prop('disabled', false);
                }
            });
        });
    });
</script>
