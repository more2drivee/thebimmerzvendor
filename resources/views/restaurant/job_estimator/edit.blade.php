@php
    $statusOptions = [
        'pending' => __('lang_v1.pending'),
        'sent' => __('lang_v1.sent'),
        'replied' => 'replied',
        'rejected' => __('lang_v1.rejected'),
        'booked' => 'booked',
    ];
@endphp

{!! Form::open([
    'url' => route('job_estimator.update', $estimator->id),
    'method' => 'PUT',
    'id' => 'edit_estimator_form',
    'enctype' => 'multipart/form-data'
]) !!}
    @csrf
    {!! Form::hidden('contact_id', $estimator->contact_id) !!}

    <div class="row">
        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('customer_display', __('restaurant.customer')) !!}
                {!! Form::text('customer_display', $estimator->customer->name ?? '', [
                    'class' => 'form-control',
                    'readonly' => true
                ]) !!}
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('location_id', __('restaurant.location') . ':*') !!}
                {!! Form::select('location_id', $business_locations, $estimator->location_id, [
                    'class' => 'form-control select2',
                    'required' => true,
                    'style' => 'width:100%'
                ]) !!}
            </div>
        </div>

        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('device_id', __('restaurant.vehicle') . ':*') !!}
                {!! Form::select('device_id', $devices, $estimator->device_id, [
                    'class' => 'form-control select2',
                    'required' => true,
                    'style' => 'width:100%'
                ]) !!}
            </div>
        </div>

        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('service_type_id', __('restaurant.service_type')) !!}
                {!! Form::select('service_type_id', $services, $estimator->service_type_id, [
                    'class' => 'form-control select2',
                    'placeholder' => __('restaurant.select_service'),
                    'style' => 'width:100%'
                ]) !!}
            </div>
        </div>

        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('amount', __('restaurant.amount')) !!}
                {!! Form::number('amount', $estimator->amount, [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0'
                ]) !!}
            </div>
        </div>

        <div class="col-sm-6">
            <div class="form-group">
                {!! Form::label('estimator_status', __('restaurant.estimator_status') . ':*') !!}
                {!! Form::select('estimator_status', $statusOptions, $estimator->estimator_status, [
                    'class' => 'form-control',
                    'required' => true
                ]) !!}
            </div>
        </div>

        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('vehicle_details', __('restaurant.vehicle_details')) !!}
                {!! Form::textarea('vehicle_details', $estimator->vehicle_details, [
                    'class' => 'form-control',
                    'rows' => 3
                ]) !!}
            </div>
        </div>

    </div>

    <div class="text-right">
        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
    </div>
{!! Form::close() !!}

<script>
$(document).ready(function() {
    $('#edit_estimator_form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var formData = new FormData(this);
        var $modal = $('#edit_estimator_modal');

        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg);
                    $modal.modal('hide');
                    $('#job_estimators_table').DataTable().ajax.reload();
                } else {
                    toastr.error(response.msg || 'Something went wrong');
                }
            },
            error: function(xhr) {
                var message = 'Something went wrong';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    message = xhr.responseJSON.msg;
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                }
                toastr.error(message);
            }
        });
    });
});
</script>
