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
<?php session_start();

?>

{{-- @php
    use Illuminate\Support\Facades\Session;
    session()->forget('contactid');

    $namesave = 'Search Customer';
    if ($_SESSION['contactid']) {
        $contactid = $_SESSION['contactid'];
        $name = DB::table('contacts')->select('name')->where('id', $contactid)->first();
        $namesave = $name->name;
        $_SESSION['contactid'] = 0;
    }

@endphp --}}

<div class="modal fade" id="add_booking_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="add_booking_form" action="{{ route('store_new_booking.store') }}" method="POST">
                @csrf
                <input type="hidden" name="booking_mode" id="booking_mode" value="standard">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" data-default-title="@lang('restaurant.add_booking')" style="width:100%; text-align: center;">@lang('restaurant.add_booking')</h4>
                </div>

                <div class="modal-body">
                    <!-- Display Form Validation Errors -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>

                    <!-- Buyer Customer Search (optional, mainly for Buy & Sell mode) -->
                    <div class="form-group position-relative" id="buyer_contact_group" style="display:none;">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            <div class="input-group">
                                <input type="text" id="buyer_customer_search" class="form-control"
                                    placeholder="@lang('restaurant.search_customer')" aria-label="@lang('restaurant.search_customer')" autocomplete="off">

                                <span class="input-group-btn">
                                    <button type="button" id="buyer_clear_customer" class="btn btn-danger"
                                        style="display:none;">&times;</button>
                                </span>
                            </div>
                            <div id="buyer_customer_results" class="results-container" style="display: none;"></div>
                            <input type="hidden" name="buyer_contact_id" id="buyer_contact_id" value="">
                            @error('buyer_contact_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @endif
                    <!-- Location Dropdown -->
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            <select name="location_id" id="booking_location_id" class="form-control">
                                <option value="">@lang('messages.please_select')</option>
                            @foreach ($business_locations as $key => $location)
                                    <option value="{{ $key }}">{{ $location }}</option>
                                @endforeach
                            </select>
                            @error('location_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Customer Search (Seller) -->
                    <div class="form-group position-relative">
                     
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            <div class="input-group">
                                <input type="text" id="customer_search" class="form-control"
                                    placeholder="@lang('restaurant.search_customer')" aria-label="@lang('restaurant.search_customer')" autocomplete="off">

                                <span class="input-group-btn">
                                    <button type="button" id="clear_customer" class="btn btn-danger"
                                        style="display:none;">&times;</button>
                                </span>
                            </div>
                            <div id="customer_results" class="results-container" style="display: none;"></div>
                            <input type="hidden" name="contact_id" id="contact_id" value="">
                            @error('contact_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat add_new_customer"
                                    data-name="" @if (!auth()->user()->can('customer.create')) disabled @endif>
                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- Buyer Customer Search (optional, mainly for Buy & Sell mode) -->
                    <div class="form-group position-relative" id="buyer_contact_group" style="display:none;">
                        <label>@lang('restaurant.buyer_contact'):</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            <div class="input-group">
                                <input type="text" id="buyer_customer_search" class="form-control"
                                    placeholder="@lang('restaurant.search_customer')" aria-label="@lang('restaurant.search_customer')" autocomplete="off">

                                <span class="input-group-btn">
                                    <button type="button" id="buyer_clear_customer" class="btn btn-danger"
                                        style="display:none;">&times;</button>
                                </span>
                            </div>
                            <div id="buyer_customer_results" class="results-container" style="display: none;"></div>
                            <input type="hidden" name="buyer_contact_id" id="buyer_contact_id" value="">
                            @error('buyer_contact_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Car Model Dropdown -->
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-car"></i>
                            </span> <select name="model_id" id="booking_model_id" class="form-control" required>
                                <option value="">@lang('restaurant.select_car_model')</option>
                                <!-- Models will be populated here based on customer selection -->
                            </select>
                            @error('model_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat add_new_models_booking"
                                    data-name="" disabled>
                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- Start Time -->
                    <div class="form-group">
                        <label for="start_time">@lang('restaurant.booked_time'):</label>
                        <input type="datetime-local" id="start_time" name="booking_start" class="form-control" required>
                        @error('booking_start')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Service Type -->
                    <div class="form-group">
                        <label for="service_type">@lang('restaurant.service_type'):</label>
                        <select name="services" id="service_type" class="form-control">
                            <option value="">@lang('restaurant.select_service')</option>
                            @foreach ($services as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('services')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group" id="service_price_group" style="display:none;">
                        <label for="service_price">Service Price</label>
                        <input type="number" name="service_price" id="service_price" class="form-control" step="0.01" min="0">
                    </div>

                    <!-- Job Estimator (Optional) -->
                    <div class="form-group" id="job_estimator_group">
                        <label for="job_estimator_id">@lang('restaurant.job_estimator') (@lang('lang_v1.optional')):</label>
                        <select name="job_estimator_id" id="job_estimator_id" class="form-control">
                            <option value="">@lang('messages.please_select')</option>
                         
                        </select>
                        @error('job_estimator_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Customer Note -->
                    <div class="form-group">
                        <label for="booking_note">@lang('restaurant.customer_note'):</label>
                        <textarea id="booking_note" name="booking_note" class="form-control" placeholder="@lang('restaurant.customer_note')" rows="3"></textarea>
                        @error('booking_note')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Send Notification Checkbox -->
                    <div class="form-group" id="send_notification_group">
                        <label>
                            <input type="checkbox" name="send_notification" id="send_notification" value="1"> @lang('restaurant.send_notification_to_customer')
                        </label>
                        <input type="hidden" name="send_notification_value" id="send_notification_value" value="0">
                    </div>

                    <!-- Callback Checkbox -->
                    <div class="form-group" id="callback_group">
                        <label>
                            <input type="checkbox" name="is_callback" id="is_callback" value="1"> @lang('restaurant.callback')
                        </label>
                    </div>

                    <!-- Callback Reference Dropdown (hidden by default) -->
                    <div class="form-group" id="callback_ref_group" style="display: none;">
                        <label for="call_back_ref">@lang('restaurant.callback_reference'):</label>
                        <select name="call_back_ref" id="call_back_ref" class="form-control">
                            <option value="">@lang('restaurant.select_callback_reference')</option>
                        </select>
                        @error('call_back_ref')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">@lang('restaurant.save')</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- External Scripts -->
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-datetimepicker@4.17.47/build/css/bootstrap-datetimepicker.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Customer Search (seller)
        $('#customer_search').on('keyup', function() {
            let searchTerm = $(this).val().trim();
            if (searchTerm.length === 0) {
                $('#customer_results').hide();
                return;
            }

            $.ajax({
                url: '/customers/search',
                method: 'GET',
                data: {
                    q: searchTerm
                },
                success: function(data) {
                    $('#customer_results').empty().show();
                    if (data.length > 0) {
                        $.each(data, function(index, customer) {
                            let resultDiv = $('<div class="result-item"></div>')
                                .text(customer.name);
                            resultDiv.on('click', function() {
                                $('#customer_search').val(customer.name);
                                $('#contact_id').val(customer.id).trigger(
                                    'change'
                                ); // 🔥 Ensure change event triggers!
                                $('#customer_results').hide();
                                $('#clear_customer')
                                    .show(); // Show clear button
                            });
                            $('#customer_results').append(resultDiv);
                        });
                    } else {
                        $('#customer_results').append('<div>@lang("restaurant.no_models_available")</div>');
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching customers:', xhr.responseText);
                }
            });
        });

        // Clear customer selection (seller)
        $('#clear_customer').on('click', function() {
            $('#customer_search').val('');
            $('#contact_id').val('').trigger('change'); // 🔥 Trigger change event
            $(this).hide();
        });

        // Buyer Customer Search (optional)
        $('#buyer_customer_search').on('keyup', function() {
            let searchTerm = $(this).val().trim();
            if (searchTerm.length === 0) {
                $('#buyer_customer_results').hide();
                return;
            }

            $.ajax({
                url: '/customers/search',
                method: 'GET',
                data: {
                    q: searchTerm
                },
                success: function(data) {
                    $('#buyer_customer_results').empty().show();
                    if (data.length > 0) {
                        $.each(data, function(index, customer) {
                            let resultDiv = $('<div class="result-item"></div>')
                                .text(customer.name);
                            resultDiv.on('click', function() {
                                $('#buyer_customer_search').val(customer.name);
                                $('#buyer_contact_id').val(customer.id);
                                $('#buyer_customer_results').hide();
                                $('#buyer_clear_customer').show();
                            });
                            $('#buyer_customer_results').append(resultDiv);
                        });
                    } else {
                        $('#buyer_customer_results').append('<div>@lang("restaurant.no_models_available")</div>');
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching customers (buyer):', xhr.responseText);
                }
            });
        });

        // Clear buyer customer selection
        $('#buyer_clear_customer').on('click', function() {
            $('#buyer_customer_search').val('');
            $('#buyer_contact_id').val('');
            $(this).hide();
        });

        // Load services based on selected location
        $('#booking_location_id').on('change', function() {
            var locationId = $(this).val();
            var $serviceSelect = $('#service_type');

            $serviceSelect.empty().append('<option value="">@lang('restaurant.select_service')</option>');

            if (!locationId) {
                return;
            }

            $.ajax({
                url: '/bookings/services/' + locationId,
                type: 'GET',
                success: function(services) {
                    $.each(services, function(id, name) {
                        $serviceSelect.append('<option value="' + id + '">' + name + '</option>');
                    });
                },
                error: function(xhr) {
                    console.error('Error fetching services for location:', xhr.responseText);
                }
            });
        });

        // Listen for changes in `contact_id` and fetch car models
        $('#contact_id').on('change', function() {
            console.log('Customer ID changed:', $(this).val());
            toggleAddNewModelsButton(); // Check the state on change

            let customerId = $(this).val();
            if (customerId) {
                fetchCustomerVehicles(customerId);
                // Clear estimators until a vehicle is selected
                $('#job_estimator_id').empty().append('<option value="">@lang('messages.please_select')</option>');
            } else {
                clearCarDropdown();
                $('#job_estimator_id').empty().append('<option value="">@lang('messages.please_select')</option>');
            }
        });

        // When estimator is selected, prepopulate device and service
        $('#job_estimator_id').on('change', function() {
            let estId = $(this).val();
            if (!estId) {
                return;
            }

            $.ajax({
                url: '/bookings/estimators/' + estId,
                method: 'GET',
                success: function(resp) {
                    if (resp && resp.success && resp.data) {
                        let data = resp.data;
                        // Prepopulate device
                        if (data.device_id) {
                            $('#booking_model_id').val(data.device_id).trigger('change');
                        }
                        // Prepopulate service if estimator has one
                        if (data.service_type_id) {
                            $('#service_type').val(data.service_type_id).trigger('change');
                        }
                    }
                }
            });
        });

        // Initialize datetime picker
        $('#start_time').datetimepicker({
            format: 'YYYY-MM-DD HH:mm', // Change the format as needed
            icons: {
                time: 'fa fa-clock',
                date: 'fa fa-calendar',
                up: 'fa fa-arrow-up',
                down: 'fa fa-arrow-down',
            },
        });

        // We don't need to handle the addVehicleForm submission here anymore
        // The form in create_models.blade.php has its own submit handler
        // We just need to make sure we refresh the vehicle list when the modal is closed

        // Listen for when the create_models modal is hidden
        $('.create_models').on('hidden.bs.modal', function () {
            // Refresh car models dropdown after modal is closed
            const customerId = $('#contact_id').val();
            if (customerId) {
                fetchCustomerVehicles(customerId);
            }
        });

        // Function to fetch customer vehicles
        function fetchCustomerVehicles(customerId) {
            if (customerId) {
                $.ajax({
                    url: '/bookings/get-custumer-vehicles/' + customerId, // Ensure correct route
                    type: 'GET',
                    success: function(response) {
                        let $carDropdown = $('#booking_model_id');
                        $carDropdown.empty().append('<option value="">@lang('restaurant.select_car_model')</option>');

                        if (response.length > 0) {
                            $.each(response, function(index, model) {
                                // Create display text with model name, plate number and color if available
                                var displayText = model.model_name;
                                var additionalInfo = [];

                                if (model.plate_number) {
                                    additionalInfo.push('@lang("car.plate"): ' + model.plate_number);
                                }

                                if (model.color) {
                                    additionalInfo.push('@lang("car.color"): ' + model.color);
                                }

                                if (additionalInfo.length > 0) {
                                    displayText += ' (' + additionalInfo.join(', ') + ')';
                                }

                                $carDropdown.append('<option value="' + model.id + '">' + displayText + '</option>');
                            });
                        } else {
                            $carDropdown.append('<option value="">@lang('restaurant.no_models_available')</option>');
                        }
                    },
                    error: function() {
                        toastr.error('Error fetching car models. Please try again.');
                    }
                });
            } else {
                clearCarDropdown();
            }
        }

        // Function to clear car model dropdown
        function clearCarDropdown() {
            $('#booking_model_id').empty().append('<option value="">@lang('restaurant.select_car_model')</option>');
        }

        // Function to toggle "Add New Models" button
        function toggleAddNewModelsButton() {

            let customerId = $('#contact_id').val();
            let addNewModelsButton = $('.add_new_models_booking');
            let customerIdInput = $('#model_customer_id');

            if (!customerId) {
                addNewModelsButton.prop('disabled', true); // Disable the button
                customerIdInput.val(""); // Clear hidden input
            } else {
                addNewModelsButton.prop('disabled', false); // Enable the button
                customerIdInput.val(customerId); // Set hidden input to selected customer
            }
        }

        // Handle add new vehicle button for booking modal
        $(document).on('click', '.add_new_models_booking', function(e) {
            e.preventDefault();
            // Set the customer ID for the vehicle creation modal
            let customerId = $('#contact_id').val();
            $('#model_customer_id').val(customerId);
            // Open the vehicle creation modal
            $('.create_models').modal('show');
        });


        // Initialize button state on page load
        toggleAddNewModelsButton();

        // Initialize form validation
        $('#add_booking_form').validate({
            ignore: [],
            rules: {
                location_id: {
                    required: true
                },
                contact_id: {
                    required: true
                },
                model_id: {
                    required: true
                },
                booking_start: {
                    required: true
                }
            },
            messages: {
                location_id: "@lang('messages.please_select')",
                contact_id: "@lang('restaurant.seller_contact') - @lang('messages.required')",
                model_id: "@lang('restaurant.select_car_model')",
                booking_start: "@lang('restaurant.booked_time') - @lang('messages.required')"
            },
            errorElement: 'span',
            errorClass: 'text-danger',
            errorPlacement: function(error, element) {
                if (element.closest('.input-group').length) {
                    error.insertAfter(element.closest('.input-group'));
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                // Prevent duplicate submissions
                if ($(form).data('submitting')) {
                    return false;
                }
                $(form).data('submitting', true);

                var data = $(form).serialize();
                var $submitBtn = $(form).find('button[type="submit"]');

                $.ajax({
                    method: "POST",
                    url: $(form).attr("action"),
                    dataType: "json",
                    data: data,
                    beforeSend: function(xhr) {
                        __disable_submit_button($submitBtn);
                    },
                    success: function(result) {
                        if (result.success == true) {
                            $('div#add_booking_modal').modal('hide');
                            toastr.success(result.msg);
                            if (typeof reload_calendar === 'function') {
                                reload_calendar();
                            }
                            if (typeof todays_bookings_table !== 'undefined') {
                                todays_bookings_table.ajax.reload();
                            }
                            // Keep button disabled after success to prevent re-submission
                        } else if (result.msg) {
                            toastr.error(result.msg);
                            // Re-enable only on non-success response
                            $submitBtn.attr('disabled', false);
                            $(form).data('submitting', false);
                        }
                    },
                    error: function(xhr) {
                        var allErrors = [];

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(field, messages) {
                                if ($.isArray(messages)) {
                                    allErrors = allErrors.concat(messages);
                                } else if (messages) {
                                    allErrors.push(messages);
                                }
                            });
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            allErrors.push(xhr.responseJSON.message);
                        }

                        if (allErrors.length === 0) {
                            allErrors.push("@lang('messages.something_went_wrong')");
                        }

                        toastr.error(allErrors.join(' - '));
                        $submitBtn.attr('disabled', false);
                        $(form).data('submitting', false);
                    }
                });
            }
        });

        // Handle callback checkbox change
        $('#is_callback').on('change', function() {
            if ($(this).is(':checked')) {
                $('#callback_ref_group').show();
                // Fetch job sheet references if customer and car are selected
                fetchJobSheetReferences();
            } else {
                $('#callback_ref_group').hide();
                $('#call_back_ref').empty().append('<option value="">@lang("restaurant.select_callback_reference")</option>');
            }
        });

        // Listen for changes in car model selection
        $('#booking_model_id').on('change', function() {
            // Update callback references if needed
            if ($('#is_callback').is(':checked')) {
                fetchJobSheetReferences();
            }

            // Trigger job estimator loading based on selected vehicle
            const deviceId = $(this).val();
            let $est = $('#job_estimator_id');
            // Preserve currently selected estimator (e.g., when set from estimator details)
            const selectedEstimator = $est.val();
            $est.empty().append('<option value="">@lang('messages.please_select')</option>');

            if (deviceId) {
                $.ajax({
                    url: '/bookings/estimators/by-contact/' + deviceId, // Backend accepts either contact or vehicle id
                    method: 'GET',
                    success: function(resp) {
                        if (resp && resp.success && resp.data && resp.data.length) {
                            resp.data.forEach(function(item) {
                                $est.append('<option value="' + item.id + '">' + item.estimate_no + '</option>');
                            });
                            // Restore previous selection if still valid for this vehicle
                            if (selectedEstimator && $est.find('option[value="' + selectedEstimator + '"]').length) {
                                $est.val(selectedEstimator);
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching estimators:', xhr.responseText);
                    }
                });
            }
        });

        // Function to fetch job sheet references
        function fetchJobSheetReferences() {
            let customerId = $('#contact_id').val();
            let deviceId = $('#booking_model_id').val();

            if (!customerId || !deviceId) {
                $('#call_back_ref').empty().append('<option value="">@lang("restaurant.select_callback_reference")</option>');
                return;
            }

            $.ajax({
                url: '/bookings/get-job-sheet-references',
                method: 'GET',
                data: {
                    contact_id: customerId,
                    device_id: deviceId
                },
                success: function(response) {
                    let $callbackDropdown = $('#call_back_ref');
                    $callbackDropdown.empty().append('<option value="">@lang("restaurant.select_callback_reference")</option>');

                    if (response.success && response.data.length > 0) {
                        $.each(response.data, function(index, jobSheet) {
                            $callbackDropdown.append('<option value="' + jobSheet.id + '">' + jobSheet.text + '</option>');
                        });
                    } else {
                        $callbackDropdown.append('<option value="">@lang("restaurant.no_job_sheets_found")</option>');
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching job sheet references:', xhr.responseText);
                    toastr.error('Error fetching job sheet references. Please try again.');
                }
            });
        }
    });
</script>
