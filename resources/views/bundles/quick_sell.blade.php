@extends('layouts.app')

@section('title', __('bundles.quick_sell_title'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('bundles.quick_sell_title')</h1>
</section>

	<section class="content no-print">
		{{-- Top navbar-style row with location --}}
		@if(count($business_locations) > 0)
		<div class="row">
			<div class="col-sm-3">
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-map-marker"></i>
						</span>
						{!! Form::select('select_location_id', $business_locations, $location->id ?? null, ['class' => 'form-control input-sm', 'id' => 'select_location_id', 'required', 'autofocus'], $bl_attributes); !!}
						<span class="input-group-addon">
							@show_tooltip(__('tooltip.sale_location'))
						</span>
					</div>
				</div>
			</div>
		</div>
		@endif

		{!! Form::open(['url' => route('bundles.quick_sell.store', ['id' => $bundle->id]), 'method' => 'post', 'id' => 'bundle_quick_sell_form', 'files' => true]) !!}

		<div class="row">
			<div class="col-md-12 col-sm-12">
				@component('components.widget', ['class' => 'box-solid'])
					{!! Form::hidden('location_id', $location->id ?? null, ['id' => 'location_id']) !!}

					<div class="row">
						<div class="col-sm-4">
							<div class="form-group">
								{!! Form::label('contact_id', __('contact.customer') . ':*') !!}
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fa fa-user"></i>
									</span>
									<input type="hidden" id="default_customer_id" value="{{ $walk_in_customer['id'] ?? '' }}">
									<input type="hidden" id="default_customer_name" value="{{ $walk_in_customer['name'] ?? '' }}">
									<input type="hidden" id="default_customer_balance" value="{{ $walk_in_customer['balance'] ?? '' }}">
									{!! Form::select('contact_id', [], $walk_in_customer['id'] ?? null, ['class' => 'form-control mousetrap', 'id' => 'customer_id', 'placeholder' => 'Enter Customer name / phone', 'required']) !!}
									<span class="input-group-btn">
										<button type="button" class="btn btn-default bg-white btn-flat add_new_customer" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
									</span>
								</div>
								<small class="text-danger hide contact_due_text"><strong>@lang('account.customer_due'):</strong> <span></span></small>
							</div>

							<div class="form-group">
								<label for="contact_device_id">@lang('repair::lang.customer_vehicle'):</label>
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fa fa-car"></i>
									</span>
									<select id="contact_device_id" name="repair_device_id" class="form-control select2">
										<option value="">@lang('messages.please_select')</option>
									</select>
									<span class="input-group-btn">
										<button type="button" class="btn btn-default bg-white btn-flat" id="add_vehicle_btn">
											<i class="fa fa-plus-circle text-primary fa-lg"></i>
										</button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-md-3">
							<div class="form-group">
								{!! Form::label('transaction_date', __('sale.sale_date') . ':*') !!}
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</span>
									{!! Form::text('transaction_date', $default_datetime, ['class' => 'form-control', 'readonly', 'required']) !!}
								</div>
							</div>
						</div>

						<div class="col-md-3">
							<div class="form-group">
								{!! Form::label('status', __('sale.status') . ':*') !!}
								{!! Form::select('status', $statuses, 'final', ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']) !!}
							</div>
						</div>

						<div class="col-sm-2">
							<div class="form-group">
								{!! Form::label('repair_device_km', __('Kilometers') . ':') !!}
								{!! Form::text('repair_device_km', null, ['class' => 'form-control', 'placeholder' => __('Enter vehicle kilometers')]) !!}
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-4">
							<div class="form-group">
								{!! Form::label('sell_document', __('purchase.attach_document') . ':') !!}
								{!! Form::file('sell_document', ['id' => 'sell_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]) !!}
								<p class="help-block">
									@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
									@includeIf('components.document_help_text')
								</p>
							</div>
						</div>
					</div>
				@endcomponent

				@component('components.widget', ['class' => 'box-solid'])
					<div class="row">
						<div class="col-sm-12">
							<button type="button" class="btn btn-default" id="add_quick_sell_line">
								<i class="fa fa-plus"></i> @lang('messages.add')
							</button>
						</div>
					</div>

					<div class="row col-sm-12 pos_product_div" style="min-height: 0">
						<input type="hidden" name="sell_price_tax" id="sell_price_tax" value="0">
						<input type="hidden" id="product_row_count" value="0">
						<div class="table-responsive">
						<table class="table table-condensed table-bordered table-striped table-responsive" id="bundle_quick_sell_lines">
							<thead>
								<tr>
									<th class="text-center">@lang('bundles.fields.reference_no')</th>
									<th class="text-center">@lang('sale.product')</th>
									<th class="text-center">@lang('sale.qty')</th>
									<th class="text-center">@lang('sale.unit_price')</th>
									<th class="text-center">@lang('receipt.discount')</th>
									<th class="text-center">@lang('sale.subtotal')</th>
									<th class="text-center"><i class="fas fa-times" aria-hidden="true"></i></th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
						</div>
						<div class="table-responsive">
						<table class="table table-condensed table-bordered table-striped">
							<tr>
								<td>
									<div class="pull-right">
									<b>@lang('sale.item'):</b> 
									<span class="total_quantity">0</span>
									&nbsp;&nbsp;&nbsp;&nbsp;
									<b>@lang('sale.total'): </b>
										<span class="price_total">0</span>
									</div>
								</td>
							</tr>
						</table>
						</div>
					</div>
				@endcomponent

				@component('components.widget', ['class' => 'box-solid'])
					<div class="col-md-4">
						<div class="form-group">
							{!! Form::label('discount_type', __('sale.discount_type') . ':*') !!}
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fa fa-info"></i>
								</span>
								{!! Form::select('discount_type', ['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')], 'percentage', ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required']) !!}
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							{!! Form::label('discount_amount', __('sale.discount_amount') . ':*') !!}
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fa fa-info"></i>
								</span>
								{!! Form::text('discount_amount', 0, ['class' => 'form-control input_number', 'required']) !!}
							</div>
						</div>
					</div>
					<div class="col-md-4"><br>
						<b>@lang('sale.discount_amount'):</b>(-) 
						<span class="display_currency" id="total_discount">0</span>
					</div>
					<div class="clearfix"></div>
					<div class="col-md-4">
						<div class="form-group">
							{!! Form::label('tax_rate_id', __('sale.order_tax') . ':*') !!}
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fa fa-info"></i>
								</span>
								{!! Form::select('tax_rate_id', $taxes['tax_rates'], null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control', 'required'], $taxes['attributes']) !!}
							</div>
						</div>
					</div>
					<div class="col-md-4 col-md-offset-4">
						<b>@lang('sale.order_tax'):</b>(+) 
						<span class="display_currency" id="order_tax">0</span>
					</div>
					<div class="clearfix"></div>
					<div class="col-md-12">
						<div class="form-group">
							{!! Form::label('sale_note', __('sale.sell_note')) !!}
							{!! Form::textarea('sale_note', null, ['class' => 'form-control', 'rows' => 3]) !!}
						</div>
					</div>
				@endcomponent

				@component('components.widget', ['class' => 'box-solid', 'title' => __('purchase.add_payment')])
					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label('payment_method', __('lang_v1.payment_method') . ':') !!}
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fas fa-money-bill-alt"></i>
									</span>
									{!! Form::select('payment_method', $payment_types, 'cash', ['class' => 'form-control']) !!}
								</div>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label('payment_amount', __('sale.amount') . ':') !!}
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fa fa-info"></i>
									</span>
									{!! Form::text('payment_amount', null, ['class' => 'form-control input_number']) !!}
								</div>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label('payment_note', __('lang_v1.payment_note') . ':') !!}
								{!! Form::text('payment_note', null, ['class' => 'form-control']) !!}
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-4 col-md-offset-8">
							<b>@lang('sale.total_payable'): </b>
							<input type="hidden" name="final_total" id="final_total_input">
							<span id="total_payable">0</span>
						</div>
					</div>
				@endcomponent

				<div class="row" style="margin-top:10px;">
					<div class="col-md-12 text-center">
						<button type="submit" class="btn btn-primary btn-lg">@lang('messages.save')</button>
					</div>
				</div>
			</div>
		</div>

		{!! Form::close() !!}
	</section>

	<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
		@include('contact.create', ['quick_add' => true])
	</div>
@endsection

@section('javascript')
<script src="{{ asset('js/pos.js?v=' . config('app.version')) }}"></script>
<script>
    $(document).ready(function() {
        let lineIndex = 0;

        // Load customer vehicles when customer changes
        function loadCustomerVehicles(customerId) {
            var $vehicleDropdown = $('#contact_device_id');

            if (!customerId) {
                $vehicleDropdown.html('<option value="">@lang("messages.please_select")</option>');
                return;
            }

            $.ajax({
                url: '/bookings/get-custumer-vehicles/' + customerId,
                type: 'GET',
                success: function(vehicles) {
                    var options = '<option value="">@lang("messages.please_select")</option>';
                    if (vehicles && vehicles.length > 0) {
                        vehicles.forEach(function(vehicle) {
                            var deviceInfo = vehicle.model_name || 'Unknown Model';
                            if (vehicle.plate_number) {
                                deviceInfo += ' - ' + vehicle.plate_number;
                            }
                            if (vehicle.color) {
                                deviceInfo += ' (' + vehicle.color + ')';
                            }
                            options += `<option value="${vehicle.id}">${deviceInfo}</option>`;
                        });
                    }
                    $vehicleDropdown.html(options);
                    $vehicleDropdown.trigger('change');
                },
                error: function() {
                    toastr.error('Error loading customer vehicles');
                }
            });
        }

        // Handle customer change
        $('#customer_id').on('change', function() {
            var customerId = $(this).val();
            loadCustomerVehicles(customerId);
        });

        // Add product line
        let lineIndex_manual = 0;
        
        $('#add_quick_sell_line').on('click', function() {
            const rowHtml = `
                <tr>
                    <td>
                        <select name="lines[${lineIndex_manual}][bundle_id]" class="form-control line-bundle select2-bundle" style="width: 100%" required>
                            <option value="">@lang("messages.please_select")</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="lines[${lineIndex_manual}][name]" class="form-control" placeholder="Product name" required>
                    </td>
                    <td>
                        <input type="text" name="lines[${lineIndex_manual}][qty]" class="form-control input_number line-qty" placeholder="Qty" required>
                    </td>
                    <td>
                        <input type="text" name="lines[${lineIndex_manual}][price]" class="form-control input_number line-price" placeholder="Price" required>
                    </td>
                    <td>
                        <input type="text" name="lines[${lineIndex_manual}][discount]" class="form-control input_number line-discount" placeholder="0">
                    </td>
                    <td class="text-right">
                        <span class="display_currency line-subtotal" data-currency_symbol="true">0</span>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-danger remove-line"><i class="fa fa-times"></i></button>
                    </td>
                </tr>
            `;
            $('#bundle_quick_sell_lines tbody').append(rowHtml);
            
            // Initialize Select2 with AJAX for the newly added bundle dropdown
            $('select[name="lines[' + lineIndex_manual + '][bundle_id]"]').select2({
                ajax: {
                    url: '{{ route("bundles.ajax.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page,
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results,
                            pagination: data.pagination,
                        };
                    },
                },
                placeholder: '@lang("messages.please_select")',
                allowClear: true,
                minimumInputLength: 1,
                width: '100%',
            });
            
            lineIndex_manual++;
            recalcTotal();
        });

        function recalcTotal() {
            let total = 0;
            let totalQty = 0;
            $('#bundle_quick_sell_lines tbody tr').each(function() {
                const qty = __read_number($(this).find('.line-qty')) || 0;
                const price = __read_number($(this).find('.line-price')) || 0;
                const discount = __read_number($(this).find('.line-discount')) || 0;
                const sub = (qty * price) - discount;
                // update per-line subtotal text
                $(this).find('.line-subtotal').text(__currency_trans_from_en(sub, true));
                total += sub;
                totalQty += qty;
            });
            // update footer totals
            $('.total_quantity').text(totalQty);
            var totalFormatted = __currency_trans_from_en(total, true);
            $('.price_total').text(totalFormatted);
            // link to payment section
            $('#final_total_input').val(total);
            $('#total_payable').text(totalFormatted);
            // keep payment amount equal to total
            if ($('#payment_amount').length) {
                __write_number($('#payment_amount'), total);
            }
        }

        $(document).on('input', '.line-price, .line-qty, .line-discount', function() {
            recalcTotal();
        });

        $(document).on('click', '.remove-line', function() {
            $(this).closest('tr').remove();
            recalcTotal();
        });

        $('#bundle_quick_sell_form').on('submit', function(e) {
            if ($('#bundle_quick_sell_lines tbody tr').length === 0) {
                e.preventDefault();
                toastr.error('{{ __('messages.no_products_added') }}');
            }
        });
    });
</script>
@endsection
