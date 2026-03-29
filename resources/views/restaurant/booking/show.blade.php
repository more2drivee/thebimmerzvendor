<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header text-center">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title" style="width:100%; text-align: center;">@lang('restaurant.booking_details')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-sm-6">
                    <strong>@lang('messages.location'):</strong> {{ $booking->location_name }}<br>
                </div>
                <div class="col-sm-6">
                    <strong>@lang('restaurant.booking_starts'):</strong> {{ $booking_start }}<br>
                </div>
                <div class="col-sm-6">
                    <strong>@lang('contact.customer'):</strong> {{ $booking->contact_name }}<br>
                </div>
                <div class="col-sm-6">
                    <strong>@lang('restaurant.model'):</strong> {{ $booking->device_name }}<br>
                </div>
                @if (!empty($booking->booking_note))
                <div class="col-sm-12">
                    <strong>@lang('restaurant.customer_note'):</strong> {{ $booking->booking_note }}
                </div>
                @endif
            </div>

            <hr>

            <div class="row">
                <div class="col-sm-12">
                    <button type="button" class="btn btn-info btn-modal w-100"
                        data-href="{{ action([\App\Http\Controllers\NotificationController::class, 'getTemplate'], ['transaction_id' => $booking->id, 'template_for' => 'new_booking']) }}"
                        data-container=".view_modal">
                        @lang('restaurant.send_notification_to_customer')
                    </button>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-sm-8">
					{!! Form::open(['url' => action([\App\Http\Controllers\Restaurant\BookingController::class, 'update_status'], [$booking->id]), 'method' => 'PUT', 'id' => 'update_booking_status']) !!}
						<div class="input-group">
							{!! Form::select('booking_status', $booking_statuses, $booking->booking_status, ['class' => 'form-control', 'placeholder' => __('restaurant.change_booking_status'), 'required']) !!}
							<div class="input-group-btn">
								<button type="submit" class="btn btn-primary">@lang('messages.update')</button>
							</div>
						</div>
					{!! Form::close() !!}
				</div>

                <div class="col-sm-3 text-center">
                    <button type="button" class="btn btn-danger"
                        id="delete_booking"
                        data-href="{{ action([\App\Http\Controllers\Restaurant\BookingController::class, 'destroy'], [$booking->id]) }}">
                        @lang('restaurant.delete_booking')
                    </button>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>
    </div>
</div>

