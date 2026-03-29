<style>
    .buy-sell-results-container {
        position: absolute;
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ccc;
        background-color: #fff;
        width: 100%;
        z-index: 10;
    }

    .buy-sell-result-item {
        padding: 10px;
        cursor: pointer;
    }

    .buy-sell-result-item:hover {
        background-color: #f0f0f0;
    }
</style>

<div class="modal fade" id="buy_sell_inspection_modal" tabindex="-1" role="dialog" aria-labelledby="buySellInspectionModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="buy_sell_inspection_form" action="{{ route('checkcar.buy_sell_booking.store') }}" method="POST">
                @csrf
                <input type="hidden" name="booking_mode" id="buy_sell_booking_mode" value="buy_sell">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" style="width:100%; text-align: center;">@lang('restaurant.buy_sell_car_inspection')</h4>
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
                    @endif

                    <!-- Location Dropdown -->
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            <select name="location_id" id="buy_sell_location_id" class="form-control">
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

                    <!-- Seller Customer Search -->
                    <div class="form-group position-relative">
                        <label>@lang('restaurant.seller_contact'):</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            <div class="input-group">
                                <input type="text" id="buy_sell_seller_search" class="form-control"
                                    placeholder="@lang('restaurant.search_customer')" aria-label="@lang('restaurant.search_customer')" autocomplete="off">

                                <span class="input-group-btn">
                                    <button type="button" id="buy_sell_clear_seller" class="btn btn-danger"
                                        style="display:none;">&times;</button>
                                </span>
                            </div>
                            <div id="buy_sell_seller_results" class="buy-sell-results-container" style="display: none;"></div>
                            <input type="hidden" name="contact_id" id="buy_sell_contact_id" value="">
                            @error('contact_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat add_new_seller_contact"
                                    data-name="" @if (!auth()->user()->can('customer.create')) disabled @endif>
                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- Buyer Customer Search -->
                    <div class="form-group position-relative" id="buy_sell_buyer_group">
                        <label>@lang('restaurant.buyer_contact'):</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            <div class="input-group">
                                <input type="text" id="buy_sell_buyer_search" class="form-control"
                                    placeholder="@lang('restaurant.search_customer')" aria-label="@lang('restaurant.search_customer')" autocomplete="off">

                                <span class="input-group-btn">
                                    <button type="button" id="buy_sell_clear_buyer" class="btn btn-danger"
                                        style="display:none;">&times;</button>
                                </span>
                            </div>
                            <div id="buy_sell_buyer_results" class="buy-sell-results-container" style="display: none;"></div>
                            <input type="hidden" name="buyer_contact_id" id="buy_sell_buyer_contact_id" value="">
                            @error('buyer_contact_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat add_new_buyer_contact"
                                    data-name="" @if (!auth()->user()->can('customer.create')) disabled @endif>
                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- Car Model Dropdown -->
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-car"></i>
                            </span>
                            <select name="model_id" id="buy_sell_model_id" class="form-control" required>
                                <option value="">@lang('restaurant.select_car_model')</option>
                                <!-- Models will be populated here based on customer selection -->
                            </select>
                            @error('model_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat add_new_models_buysell"
                                    data-name="" disabled>
                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- Start Time -->
                    <div class="form-group">
                        <label for="buy_sell_start_time">@lang('restaurant.booked_time'):</label>
                        <input type="text" id="buy_sell_start_time" name="booking_start" class="form-control" required>
                        @error('booking_start')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Service Price -->
                    <div class="form-group">
                        <label for="buy_sell_service_price">@lang('restaurant.service_price'):</label>
                        <input type="number" step="0.01" min="0" id="buy_sell_service_price" name="service_price" class="form-control">
                        @error('service_price')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Transaction/Jobsheet Contact Selection -->
                    <div class="form-group">
                        <label>@lang('restaurant.transaction_contact'):</label>
                        <div class="radio">
                            <label style="margin-right: 20px;">
                                <input type="radio" name="transaction_contact_type" value="seller" checked>
                                @lang('restaurant.seller_contact')
                            </label>
                            <label>
                                <input type="radio" name="transaction_contact_type" value="buyer">
                                @lang('restaurant.buyer_contact')
                            </label>
                        </div>
                        <small class="text-muted">@lang('restaurant.transaction_contact_help')</small>
                    </div>

                    <!-- Customer Note -->
                    <div class="form-group">
                        <label for="buy_sell_booking_note">@lang('restaurant.customer_note'):</label>
                        <textarea id="buy_sell_booking_note" name="booking_note" class="form-control" placeholder="@lang('restaurant.customer_note')" rows="3"></textarea>
                        @error('booking_note')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Send Notification Checkbox -->
                    <div class="form-group" id="buy_sell_send_notification_group">
                        <label>
                            <input type="checkbox" name="send_notification" id="buy_sell_send_notification" value="1"> @lang('restaurant.send_notification_to_customer')
                        </label>
                        <input type="hidden" name="send_notification_value" id="buy_sell_send_notification_value" value="0">
                    </div>
    
                    <!-- Verification Required -->
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="buy_sell_verification_required" name="verification_required" value="1" checked>
                                @lang('restaurant.verification_required')
                            </label>
                        </div>
                        @error('verification_required')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">@lang('restaurant.save')</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    $(document).ready(function() {
        // Seller Customer Search
        $('#buy_sell_seller_search').on('keyup', function() {
            let searchTerm = $(this).val().trim();
            if (searchTerm.length === 0) {
                $('#buy_sell_seller_results').hide();
                return;
            }

            $.ajax({
                url: '/customers/search',
                method: 'GET',
                data: {
                    q: searchTerm
                },
                success: function(data) {
                    $('#buy_sell_seller_results').empty().show();
                    if (data.length > 0) {
                        $.each(data, function(index, customer) {
                            let resultDiv = $('<div class="buy-sell-result-item"></div>')
                                .text(customer.name);
                            resultDiv.on('click', function() {
                                $('#buy_sell_seller_search').val(customer.name);
                                $('#buy_sell_contact_id').val(customer.id).trigger('change');
                                $('#buy_sell_seller_results').hide();
                                $('#buy_sell_clear_seller').show();
                            });
                            $('#buy_sell_seller_results').append(resultDiv);
                        });
                    } else {
                        $('#buy_sell_seller_results').append('<div>@lang("restaurant.no_models_available")</div>');
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching customers:', xhr.responseText);
                }
            });
        });

        // Clear seller selection
        $('#buy_sell_clear_seller').on('click', function() {
            $('#buy_sell_seller_search').val('');
            $('#buy_sell_contact_id').val('').trigger('change');
            $(this).hide();
        });

        // Buyer Customer Search
        $('#buy_sell_buyer_search').on('keyup', function() {
            let searchTerm = $(this).val().trim();
            if (searchTerm.length === 0) {
                $('#buy_sell_buyer_results').hide();
                return;
            }

            $.ajax({
                url: '/customers/search',
                method: 'GET',
                data: {
                    q: searchTerm
                },
                success: function(data) {
                    $('#buy_sell_buyer_results').empty().show();
                    if (data.length > 0) {
                        $.each(data, function(index, customer) {
                            let resultDiv = $('<div class="buy-sell-result-item"></div>')
                                .text(customer.name);
                            resultDiv.on('click', function() {
                                $('#buy_sell_buyer_search').val(customer.name);
                                $('#buy_sell_buyer_contact_id').val(customer.id);
                                $('#buy_sell_buyer_results').hide();
                                $('#buy_sell_clear_buyer').show();
                            });
                            $('#buy_sell_buyer_results').append(resultDiv);
                        });
                    } else {
                        $('#buy_sell_buyer_results').append('<div>@lang("restaurant.no_models_available")</div>');
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching customers (buyer):', xhr.responseText);
                }
            });
        });

        // Clear buyer selection
        $('#buy_sell_clear_buyer').on('click', function() {
            $('#buy_sell_buyer_search').val('');
            $('#buy_sell_buyer_contact_id').val('');
            $(this).hide();
        });

        // Listen for changes in seller contact_id and fetch car models
        $('#buy_sell_contact_id').on('change', function() {
            let customerId = $(this).val();
            let addNewModelsButton = $('#buy_sell_inspection_modal .add_new_models_buysell');
            
            if (customerId) {
                addNewModelsButton.prop('disabled', false);
                fetchBuySellCustomerVehicles(customerId);
            } else {
                addNewModelsButton.prop('disabled', true);
                $('#buy_sell_model_id').empty().append('<option value="">@lang("restaurant.select_car_model")</option>');
            }
        });

        // Function to fetch customer vehicles for Buy & Sell modal
        function fetchBuySellCustomerVehicles(customerId) {
            if (customerId) {
                $.ajax({
                    url: '/bookings/get-custumer-vehicles/' + customerId,
                    type: 'GET',
                    success: function(response) {
                        let $carDropdown = $('#buy_sell_model_id');
                        $carDropdown.empty().append("<option value=\"\">@lang('restaurant.select_car_model')</option>");

                        if (response.length > 0) {
                            $.each(response, function(index, model) {
                                var displayText = model.model_name;
                                var additionalInfo = [];

                                if (model.plate_number) {
                                    additionalInfo.push("@lang('car.plate'): " + model.plate_number);
                                }

                                if (model.color) {
                                    additionalInfo.push("@lang('car.color'): " + model.color);
                                }

                                if (additionalInfo.length > 0) {
                                    displayText += ' (' + additionalInfo.join(', ') + ')';
                                }

                                $carDropdown.append('<option value="' + model.id + '">' + displayText + '</option>');
                            });
                        } else {
                            $carDropdown.append("<option value=\"\">@lang('restaurant.no_models_available')</option>");
                        }
                    },
                    error: function() {
                        toastr.error('Error fetching car models. Please try again.');
                    }
                });
            } else {
                clearBuySellCarDropdown();
            }
        }

        // Function to clear car model dropdown
        function clearBuySellCarDropdown() {
            $('#buy_sell_model_id').empty().append("<option value=\"\">@lang('restaurant.select_car_model')</option>");
        }

        // Initialize modal when shown
        $('#buy_sell_inspection_modal').on('shown.bs.modal', function(e) {
            // Initialize select2 for dropdowns
            $(this).find('select').each(function() {
                if (!($(this).hasClass('select2'))) {
                    $(this).select2({
                        dropdownParent: $('#buy_sell_inspection_modal')
                    });
                }
            });

            // Initialize form validation
            buy_sell_form_validator = $('form#buy_sell_inspection_form').validate({
                ignore: [],
                rules: {
                    location_id: {
                        required: true
                    },
                    contact_id: {
                        required: true
                    },
                    buyer_contact_id: {
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
                    buyer_contact_id: "@lang('restaurant.buyer_contact') - @lang('messages.required')",
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
                                $('div#buy_sell_inspection_modal').modal('hide');
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
        });

        // Reset form when modal is hidden
        $('#buy_sell_inspection_modal').on('hidden.bs.modal', function(e) {
            if (typeof buy_sell_form_validator !== 'undefined' && $.isFunction(buy_sell_form_validator.destroy)) {
                buy_sell_form_validator.destroy();
            }
            resetBuySellForm();
        });

        // Function to reset the Buy & Sell form
        function resetBuySellForm() {
            $('#buy_sell_location_id').val('').change();
            $('#buy_sell_seller_search').val('');
            $('#buy_sell_contact_id').val('');
            $('#buy_sell_clear_seller').hide();
            $('#buy_sell_buyer_search').val('');
            $('#buy_sell_buyer_contact_id').val('');
            $('#buy_sell_clear_buyer').hide();
            clearBuySellCarDropdown();
            $('#buy_sell_start_time').val('');
            $('#buy_sell_booking_note').val('');
            $('#buy_sell_verification_required').prop('checked', true); // Reset to checked by default
            $('#buy_sell_send_notification').prop('checked', false);
            // Reset submission flag and re-enable submit button for next use
            $('#buy_sell_inspection_form').data('submitting', false);
            $('#buy_sell_inspection_form').find('button[type="submit"]').attr('disabled', false);
        }

        // Handle add new buyer contact button (Buy & Sell modal only)
        $(document).on('click', '.add_new_buyer_contact', function(e) {
            e.preventDefault();
            openCreateContactModal('buyer');
        });

        // Handle add new seller contact button (Buy & Sell modal only)
        $(document).on('click', '.add_new_seller_contact', function(e) {
            e.preventDefault();
            openCreateContactModal('seller');
        });

        // Handle add new vehicle (car) button (Buy & Sell modal only)
        $(document).on('click', '.add_new_models_buysell', function(e) {
            e.preventDefault();
            // Set the customer ID for the vehicle creation modal
            let customerId = $('#buy_sell_contact_id').val();
            $('#model_customer_id').val(customerId);
            // Open the vehicle creation modal
            $('.create_models').modal('show');
        });

        // Open modal to create buyer, seller or vehicle
        function openCreateContactModal(contactType) {
            $.ajax({
                url: '/buy-sell/create-contact-modal',
                method: 'GET',
                data: { type: contactType },
                success: function(html) {
                    // Remove existing modal if present
                    $('#create_contact_modal').remove();
                    $('body').append(html);

                    // Store context type on the modal so its own script can adapt
                    $('#create_contact_modal').attr('data-contact-type', contactType);

                    $('#create_contact_modal').modal('show');
                },
                error: function() {
                    toastr.error('Error loading contact creation form');
                }
            });
        }

        // Initialize datetime picker for start time
        if ($.fn.datetimepicker) {
            $('#buy_sell_start_time').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                minDate: moment(),
                ignoreReadonly: true
            });
        }
    });
</script>
