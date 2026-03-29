<style>
    .results-container {
        position: absolute;
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ccc;
        background-color: #fff;
        width: 100%;
        z-index: 10;
    }

    .result-item {
        padding: 10px;
        cursor: pointer;
    }

    .result-item:hover {
        background-color: #f0f0f0;
    }
</style>

<div class="modal fade" id="add_estimator_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('job_estimator.store') }}" method="POST" id="add_estimator_form" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" style="width:100%; text-align: center;">@lang('restaurant.create_job_estimator')</h4>
                </div>

                <div class="modal-body">
                    <!-- Display Form Validation Errors -->
                    <div class="alert alert-danger" id="estimator_errors" style="display: none;">
                        <ul id="estimator_errors_list"></ul>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                {!! Form::label('estimator_location_id', __('restaurant.location') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-map-marker"></i>
                                    </span>
                                    {!! Form::select('location_id', $business_locations, null, [
                                        'placeholder' => __('restaurant.location'),
                                        'class' => 'form-control',
                                        'required',
                                        'id' => 'estimator_location_id',
                                    ]) !!}
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <!-- Customer Search with Dropdown -->
                        <div class="col-sm-6">
                            <div class="form-group position-relative">
                                <label for="estimator_customer_search">@lang('restaurant.customer')</label>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    <div class="input-group">
                                        <input type="text" id="estimator_customer_search" class="form-control"
                                            placeholder="@lang('restaurant.search_customer')" aria-label="@lang('restaurant.search_customer')" autocomplete="off">

                                        <span class="input-group-btn">
                                            <button type="button" id="clear_estimator_customer" class="btn btn-danger"
                                                style="display:none;">&times;</button>
                                        </span>
                                    </div>
                                    <div id="estimator_customer_results" class="results-container" style="display: none;"></div>
                                    <input type="hidden" name="contact_id" id="estimator_contact_id" value="">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default bg-white btn-flat add_new_customer_estimator"
                                            data-name="" @if (!auth()->user()->can('customer.create')) disabled @endif>
                                            <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Device/Model Dropdown that depends on the selected customer -->
                        <div class="col-sm-6">
                            <div class="form-group">
                                {!! Form::label('estimator_device_id', __('restaurant.vehicle') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-car"></i>
                                    </span>
                                    {!! Form::select('device_id', [], null, [
                                        'placeholder' => __('restaurant.select_car_model'),
                                        'class' => 'form-control',
                                        'required',
                                        'id' => 'estimator_device_id',
                                    ]) !!}
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default bg-white btn-flat add_new_car_estimator"
                                            data-name="" disabled>
                                            <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Services Dropdown -->
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('estimator_service_type_id', __('restaurant.service_type') . ':') !!}
                            {!! Form::select('service_type_id', $services, null, [
                                'placeholder' => __('restaurant.select_service'),
                                'class' => 'form-control',
                                'id' => 'estimator_service_type_id',
                            ]) !!}
                        </div>
                    </div>

                    <!-- Amount -->
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('amount', __('restaurant.amount') . ':') !!}
                            {!! Form::number('amount', null, [
                                'class' => 'form-control',
                                'id' => 'amount',
                                'step' => '0.01',
                                'min' => '0'
                            ]) !!}
                        </div>
                    </div>

                    <div class="clearfix"></div>

                    <!-- Vehicle Details -->
                    <div class="col-sm-12">
                        <div class="form-group">
                            {!! Form::label('vehicle_details', __('restaurant.vehicle_details') . ':') !!}
                            {!! Form::textarea('vehicle_details', null, [
                                'id' => 'vehicle_details',
                                'class' => 'form-control',
                                'placeholder' => __('restaurant.vehicle_details'),
                                'rows' => 2,
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    {!! Form::submit(__('messages.save'), ['class' => 'btn btn-primary']) !!}
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Vehicle Create Modal is provided globally on the bookings index page; do not include here to avoid duplicate IDs. -->

<script>
$(document).ready(function() {
    // Customer search functionality (aligned with booking create)
    $('#estimator_customer_search').on('input', function() {
        var searchTerm = $(this).val().trim();
        if (searchTerm.length > 0) {
            $.ajax({
                url: '/customers/search',
                type: 'GET',
                data: { q: searchTerm },
                success: function(data) {
                    var resultsHtml = '';
                    $.each(data, function(index, contact) {
                        resultsHtml += '<div class="result-item" data-id="' + contact.id + '" data-name="' + contact.name + '">' + contact.name + '</div>';
                    });
                    $('#estimator_customer_results').html(resultsHtml).show();
                },
                error: function(xhr){
                    console.error('Error fetching customers:', xhr.responseText);
                }
            });
        } else {
            $('#estimator_customer_results').hide();
        }
    });

    // Handle customer result selection
    $(document).on('click', '#estimator_customer_results .result-item', function() {
        var customerId = $(this).data('id');
        var customerName = $(this).data('name');
        
        $('#estimator_customer_search').val(customerName);
        $('#estimator_contact_id').val(customerId).trigger('change');
        $('#estimator_customer_results').hide();
        
        // Enable add car button
        $('.add_new_car_estimator').prop('disabled', false);
    });

    // Listen for changes in selected customer and fetch their vehicles (aligned with booking create)
    $('#estimator_contact_id').on('change', function() {
        var customerId = $(this).val();
        if (customerId) {
            $.ajax({
                url: '/bookings/get-custumer-vehicles/' + customerId,
                type: 'GET',
                success: function(response) {
                    var $carDropdown = $('#estimator_device_id');
                    $carDropdown.empty().append('<option value="">@lang("restaurant.select_car_model")</option>');

                    if (response.length > 0) {
                        $.each(response, function(index, model) {
                            var displayText = model.model_name;
                            var additionalInfo = [];
                            if (model.plate_number) { additionalInfo.push('@lang("car.plate"): ' + model.plate_number); }
                            if (model.color) { additionalInfo.push('@lang("car.color"): ' + model.color); }
                            if (additionalInfo.length > 0) { displayText += ' (' + additionalInfo.join(', ') + ')'; }
                            $carDropdown.append('<option value="' + model.id + '">' + displayText + '</option>');
                        });
                    } else {
                        $carDropdown.append('<option value="">@lang("restaurant.no_models_available")</option>');
                    }
                },
                error: function(){
                    toastr.error('Error fetching car models. Please try again.');
                }
            });
        } else {
            $('#estimator_device_id').empty().append('<option value="">@lang("restaurant.select_car_model")</option>');
            $('.add_new_car_estimator').prop('disabled', true);
        }
    });

    // Clear customer search
    $('#clear_estimator_customer').on('click', function() {
        $('#estimator_customer_search').val('');
        $('#estimator_contact_id').val('').trigger('change');
        $('#estimator_customer_results').hide();
        $(this).hide();
        $('.add_new_car_estimator').prop('disabled', true);
        $('#estimator_device_id').empty().append('<option value="">@lang("restaurant.select_car_model")</option>');
    });

    // Show clear button when typing
    $('#estimator_customer_search').on('input', function() {
        if ($(this).val().length > 0) {
            $('#clear_estimator_customer').show();
        } else {
            $('#clear_estimator_customer').hide();
        }
    });

    // Add new customer: use the globally included contact modal to avoid duplicates
    $('.add_new_customer_estimator').on('click', function() {
        $('.contact_modal')
            .find('select#contact_type')
            .val('customer')
            .closest('div.contact_type_div')
            .addClass('hide');
        $('.contact_modal').modal('show');
    });

    // Add new car (show included modal, same flow as booking)
    $('.add_new_car_estimator').on('click', function() {
        var customerId = $('#estimator_contact_id').val();
        if (!customerId) {
            toastr.error('Please select a customer first');
            return;
        }

        // Set customer id into the vehicle modal and open it
        $('#model_customer_id').val(customerId);
        $('.create_models').css('z-index', 1060);
        $('.create_models').modal({
            backdrop: 'static',
            keyboard: false
        });

        // When modal closes, refresh vehicles list
        $('.create_models').off('hidden.bs.modal').on('hidden.bs.modal', function () {
            $.get('/bookings/get-custumer-vehicles/' + customerId, function(response) {
                var $carDropdown = $('#estimator_device_id');
                $carDropdown.empty().append('<option value="">@lang("restaurant.select_car_model")</option>');
                if (response.length > 0) {
                    $.each(response, function(index, model) {
                        var displayText = model.model_name;
                        var additionalInfo = [];
                        if (model.plate_number) { additionalInfo.push('@lang("car.plate"): ' + model.plate_number); }
                        if (model.color) { additionalInfo.push('@lang("car.color"): ' + model.color); }
                        if (additionalInfo.length > 0) { displayText += ' (' + additionalInfo.join(', ') + ')'; }
                        $carDropdown.append('<option value="' + model.id + '">' + displayText + '</option>');
                    });
                } else {
                    $carDropdown.append('<option value="">@lang("restaurant.no_models_available")</option>');
                }
            });
        });
    });
});
</script>
