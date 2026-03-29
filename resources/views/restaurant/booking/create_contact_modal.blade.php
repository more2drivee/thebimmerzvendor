<div class="modal fade" id="create_contact_modal" tabindex="-1" role="dialog" aria-labelledby="createContactLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="create_contact_form" method="POST" novalidate>
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" style="width:100%; text-align: center;">@lang('restaurant.create_contact')</h4>
                </div>

                <div class="modal-body">
                    <div id="create_contact_errors" class="alert alert-danger" style="display:none;">
                        <ul class="mb-0"></ul>
                    </div>
                    <!-- Tabs for contact vs. contact device -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#contact_tab" aria-controls="contact_tab" role="tab" data-toggle="tab">
                                @lang('restaurant.buyer_contact') / @lang('restaurant.seller_contact')
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#vehicle_tab" aria-controls="vehicle_tab" role="tab" data-toggle="tab">
                                @lang('checkcar::lang.vehicle_information')
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" style="margin-top:15px;">
                        <!-- Tab 1: Buyer & Seller contact information -->
                        <div role="tabpanel" class="tab-pane active" id="contact_tab">
                            <!-- Buyer Information Section -->
                            <div id="buyer_info_section">
                                <h5>@lang('restaurant.buyer_contact')</h5>
                                <div class="row">
                            <!-- Buyer Mobile Number -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="buyer_mobile">@lang('contact.mobile'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-phone"></i>
                                        </span>
                                        <input type="text" name="buyer_mobile" id="buyer_mobile" class="form-control"
                                            placeholder="@lang('contact.mobile')">
                                        <span class="input-group-addon" id="buyer_mobile_status" style="display:none;">
                                            <i class="fa fa-spinner fa-spin"></i>
                                        </span>
                                    </div>
                                    <span id="buyer_mobile_error" class="help-block text-danger" style="display:none;"></span>
                                </div>
                            </div>

                            <!-- Buyer First Name -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="buyer_first_name">@lang('contact.first_name'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-user"></i>
                                        </span>
                                        <input type="text" name="buyer_first_name" id="buyer_first_name" class="form-control"
                                            placeholder="@lang('contact.first_name')">
                                    </div>
                                </div>
                            </div>

                            <!-- Buyer Last Name -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="buyer_last_name">@lang('business.last_name'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-user"></i>
                                        </span>
                                        <input type="text" name="buyer_last_name" id="buyer_last_name" class="form-control"
                                            placeholder="@lang('business.last_name')">
                                    </div>
                                </div>
                            </div>

                            <!-- Buyer National ID -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="buyer_national_id">@lang('checkcar::lang.national_id'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-id-card"></i>
                                        </span>
                                        <input type="text" name="buyer_national_id" id="buyer_national_id" class="form-control"
                                            placeholder="@lang('checkcar::lang.national_id')">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Seller Information Section -->
                    <div id="seller_info_section">
                        <h5>@lang('restaurant.seller_contact')</h5>
                        <div class="row">
                            <!-- Seller Mobile Number -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="seller_mobile">@lang('contact.mobile'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-phone"></i>
                                        </span>
                                        <input type="text" name="seller_mobile" id="seller_mobile" class="form-control"
                                            placeholder="@lang('contact.mobile')">
                                        <span class="input-group-addon" id="seller_mobile_status" style="display:none;">
                                            <i class="fa fa-spinner fa-spin"></i>
                                        </span>
                                    </div>
                                    <span id="seller_mobile_error" class="help-block text-danger" style="display:none;"></span>
                                </div>
                            </div>

                            <!-- Seller First Name -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="seller_first_name">@lang('contact.first_name'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-user"></i>
                                        </span>
                                        <input type="text" name="seller_first_name" id="seller_first_name" class="form-control"
                                            placeholder="@lang('contact.first_name')">
                                    </div>
                                </div>
                            </div>

                            <!-- Seller Last Name -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="seller_last_name">@lang('business.last_name'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-user"></i>
                                        </span>
                                        <input type="text" name="seller_last_name" id="seller_last_name" class="form-control"
                                            placeholder="@lang('business.last_name')">
                                    </div>
                                </div>
                            </div>

                            <!-- Seller National ID -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="seller_national_id">@lang('checkcar::lang.national_id'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-id-card"></i>
                                        </span>
                                        <input type="text" name="seller_national_id" id="seller_national_id" class="form-control"
                                            placeholder="@lang('checkcar::lang.national_id')">
                                    </div>
                                </div>
                            </div>

                            <!-- Seller License Number -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="seller_license_number">@lang('checkcar::lang.license_number'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-id-card"></i>
                                        </span>
                                        <input type="text" name="seller_license_number" id="seller_license_number" class="form-control"
                                            placeholder="@lang('checkcar::lang.license_number')">
                                    </div>
                                </div>
                            </div>

                            <!-- Seller License Expiry -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="seller_license_expiry">@lang('checkcar::lang.license_expiry'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-calendar"></i>
                                        </span>
                                        <input type="date" name="seller_license_expiry" id="seller_license_expiry" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Seller vehicle / contact device information -->
                <div role="tabpanel" class="tab-pane" id="vehicle_tab">
                    <!-- Seller Vehicle Information Section -->
                    <div id="seller_vehicle_info_section">
                        <h5>@lang('checkcar::lang.vehicle_information') <span class="text-muted">(@lang('checkcar::lang.section_label_optional'))</span></h5>
                        <div class="row">
                            <!-- VIN -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_chassis_number">@lang('car.vin'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-key"></i>
                                        </span>
                                        <input type="text" name="seller_chassis_number" id="seller_chassis_number" class="form-control"
                                            placeholder="@lang('car.vin')" maxlength="17">
                                    </div>
                                </div>
                            </div>

                            <!-- Car Type -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_car_type">@lang('car.cartype'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-car"></i>
                                        </span>
                                        <select name="seller_car_type" id="seller_car_type" class="form-control">
                                            <option value="">@lang('car.selectcartype')</option>
                                            <option value="ملاكي">ملاكي</option>
                                            <option value="اجرة">اجرة</option>
                                            <option value="نقل ثقيل">نقل ثقيل</option>
                                            <option value="نقل خفيف">نقل خفيف</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Brand -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_category_id">@lang('car.brand'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-car-alt"></i>
                                        </span>
                                        @php
                                            $brand_information = \Illuminate\Support\Facades\DB::table('categories')
                                                ->where('category_type', 'device')
                                                ->select('id', 'name')
                                                ->get();
                                        @endphp
                                        <select name="seller_category_id" id="seller_category_id" class="form-control">
                                            <option value="">@lang('car.selectbrand')</option>
                                            @foreach ($brand_information as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Model -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_model_id">@lang('car.model'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-car-alt"></i>
                                        </span>
                                        <select name="seller_model_id" id="seller_model_id" class="form-control">
                                            <option value="">@lang('car.selectmodel')</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Manufacturing Year -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_manufacturing_year">@lang('car.manufacturing'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-calendar"></i>
                                        </span>
                                        <select name="seller_manufacturing_year" id="seller_manufacturing_year" class="form-control">
                                            <option value="">@lang('car.selectyear')</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Brand Origin -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_brand_origin_variant_id">@lang('car.country'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-flag"></i>
                                        </span>
                                        <select name="seller_brand_origin_variant_id" id="seller_brand_origin_variant_id" class="form-control">
                                            <option value="">@lang('car.selectcountry')</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Color -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_color">@lang('restaurant.color'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-paint-brush"></i>
                                        </span>
                                        <input type="text" name="seller_color" id="seller_color" class="form-control"
                                            placeholder="@lang('car.color')">
                                    </div>
                                </div>
                            </div>

                            <!-- Plate Number -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_plate_number">@lang('restaurant.plate_number'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-key"></i>
                                        </span>
                                        <input type="text" name="seller_plate_number" id="seller_plate_number" class="form-control"
                                            placeholder="@lang('car.plate')">
                                    </div>
                                </div>
                            </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        var $modal = $('#create_contact_modal');
        var contactType = $modal.data('contact-type') || 'seller';

        var buyerLabel = "@lang('restaurant.buyer_contact')";
        var sellerLabel = "@lang('restaurant.seller_contact')";
        var vehicleLabel = "@lang('checkcar::lang.vehicle_information')";

        if (contactType === 'buyer') {
            $('#buyer_info_section').show();
            $('#seller_info_section').hide();
            $('a[href="#vehicle_tab"]').closest('li').hide();
            $modal.find('.modal-title').text(buyerLabel);
        } else if (contactType === 'seller') {
            $('#buyer_info_section').hide();
            $('#seller_info_section').show();
            $('a[href="#vehicle_tab"]').closest('li').show();
            $modal.find('.modal-title').text(sellerLabel);
        } else if (contactType === 'vehicle') {
            $('#buyer_info_section').hide();
            $('#seller_info_section').hide();
            $('a[href="#contact_tab"]').closest('li').hide();
            $('a[href="#vehicle_tab"]').closest('li').addClass('active').show();
            $('#contact_tab').removeClass('active');
            $('#vehicle_tab').addClass('active');
            $modal.find('.modal-title').text(vehicleLabel);
        }

        // Initialize manufacturing year dropdown for seller vehicle
        initSellerManufacturingYearDropdown();

        // Make seller brand/model (and related) dropdowns searchable inside this modal
        if ($.fn.select2) {
            $('#seller_category_id, #seller_model_id, #seller_brand_origin_variant_id, #seller_car_type').select2({
                dropdownParent: $('#create_contact_modal')
            });
        }

        function initSellerManufacturingYearDropdown() {
            var $yearDropdown = $('#seller_manufacturing_year');
            var currentYear = new Date().getFullYear();
            $yearDropdown.empty().append('<option value="">@lang("car.selectyear")</option>');
            for (var year = currentYear; year >= 1980; year--) {
                $yearDropdown.append('<option value="' + year + '">' + year + '</option>');
            }
        }

        // --- VIN lookup integration (seller_chassis_number) ---
        var isVinLookupInProgress = false;
        
        // Store pending model/variant selections from VIN lookup
        var pendingModelId = null;
        var pendingModelName = null;
        var pendingVariantId = null;

        // When VIN reaches 17 characters, trigger lookup and VIN groups check
        $('#seller_chassis_number').on('input', function() {
            var chassisNumber = $(this).val().trim();
            if (chassisNumber.length === 17) {
                isVinLookupInProgress = true;
                performChassisLookup(chassisNumber);
                checkVinGroups(chassisNumber);
            }
        });

        // Check VIN groups and show color-coded toast notifications
        function checkVinGroups(vin) {
            var url = '{{ url("vin/vin-groups-by-number") }}';
            $.get(url, { vin: vin })
                .done(function(groups) {
                    if (!groups || groups.length === 0) { return; }
                    groups.forEach(function(g){
                        var title = g.name || 'VIN Group';
                        var body = g.text || ('VIN is in ' + title);
                        toastr.info(body, title);
                        var $toast = $('#toast-container .toast').last();
                        if (g.color) {
                            $toast.css('background-color', g.color);
                            $toast.css('color', '#000');
                        }
                    });
                })
                .fail(function(xhr){
                    console.warn('VIN group check failed:', xhr.responseJSON?.message || xhr.statusText);
                });
        }

        function performChassisLookup(chassisNumber) {
            // Reset pending selections
            pendingModelId = null;
            pendingModelName = null;
            pendingVariantId = null;

            // Reset seller vehicle fields before new lookup
            $('#seller_model_id').empty().append('<option value="">@lang("car.selectmodel")</option>');
            $('#seller_manufacturing_year').val('');
            $('#seller_car_type').val('').trigger('change');
            $('#seller_color').val('');
            $('#seller_brand_origin_variant_id').empty().append('<option value="">@lang("car.selectcountry")</option>');

            $.ajax({
                url: "{{ route('booking.lookup_chassis') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    chassis_number: chassisNumber
                },
                dataType: "json",
                success: function(response) {
                    if (response.success && response.data) {
                        var data = response.data;

                        // Store model info for later application after brand change loads models
                        if (data.model_id) {
                            pendingModelId = data.model_id;
                            pendingModelName = data.model_name || 'Model';
                        }

                        // Store variant info for later application
                        if (data.variant_id) {
                            pendingVariantId = data.variant_id;
                        }

                        // Set brand (seller_category_id) - this triggers change handler which loads models
                        if (data.brand_id) {
                            $('#seller_category_id').val(data.brand_id).trigger('change');
                        }

                        // Set manufacturing year
                        if (data.year) {
                            var yearStr = String(data.year);
                            if ($('#seller_manufacturing_year option[value="' + yearStr + '"]').length === 0) {
                                $('#seller_manufacturing_year').append('<option value="' + yearStr + '">' + yearStr + '</option>');
                            }
                            $('#seller_manufacturing_year').val(yearStr);
                        }

                        // Set color if available
                        if (data.color) {
                            $('#seller_color').val(data.color);
                        }

                        toastr.success("@lang('messages.success')");
                    } else {
                        toastr.warning(response.message || 'Could not find complete vehicle information');
                    }

                    isVinLookupInProgress = false;
                },
                error: function(xhr, status, error) {
                    toastr.error('Error looking up chassis number: ' + error);
                    isVinLookupInProgress = false;
                }
            });
        }

        // Load models when brand changes
        $('#seller_category_id').on('change', function() {
            var brandId = $(this).val();
            if (brandId) {
                $.ajax({
                    url: '/bookings/get-models/' + brandId,
                    type: 'GET',
                    success: function(response) {
                        // Populate models dropdown
                        let $modelDropdown = $('#seller_model_id');
                        $modelDropdown.empty().append("<option value=\"\">@lang('car.selectmodel')</option>");

                        var models = response.models || response || [];
                        $.each(models, function(index, model) {
                            if (model && model.id && model.name) {
                                $modelDropdown.append('<option value="' + model.id + '">' + model.name + '</option>');
                            }
                        });

                        // If VIN lookup set a pending model, apply it now
                        if (pendingModelId) {
                            if ($modelDropdown.find('option[value="' + pendingModelId + '"]').length === 0 && pendingModelName) {
                                $modelDropdown.append('<option value="' + pendingModelId + '">' + pendingModelName + '</option>');
                            }
                            $modelDropdown.val(pendingModelId).trigger('change');
                            pendingModelId = null;
                            pendingModelName = null;
                        }

                        // Populate brand origins/variants dropdown from the same response
                        let $countrySelect = $('#seller_brand_origin_variant_id');
                        $countrySelect.empty().append('<option value="">@lang("car.selectcountry")</option>');
                        
                        var variants = response.variants || [];
                        $.each(variants, function(index, variant) {
                            var label = variant.label || variant.name;
                            $countrySelect.append('<option value="' + variant.id + '">' + label + '</option>');
                        });

                        // If VIN lookup set a pending variant, apply it now
                        if (pendingVariantId) {
                            $countrySelect.val(String(pendingVariantId)).trigger('change');
                            pendingVariantId = null;
                        }
                    }
                });
            } else {
                $('#seller_model_id').empty().append("<option value=\"\">@lang('car.selectmodel')</option>");
                $('#seller_brand_origin_variant_id').empty().append('<option value="">@lang("car.selectcountry")</option>');
                pendingModelId = null;
                pendingModelName = null;
                pendingVariantId = null;
            }
        });

        // Form submission
        $('#create_contact_form').on('submit', function(e) {
            e.preventDefault();

            if ($(this).data('submitting')) {
                return;
            }

            $(this).data('submitting', true);
            var $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.attr('disabled', true);
            
            let formData = {
                buyer_first_name: $('#buyer_first_name').val(),
                buyer_last_name: $('#buyer_last_name').val(),
                buyer_mobile: $('#buyer_mobile').val(),
                buyer_national_id: $('#buyer_national_id').val(),
                seller_first_name: $('#seller_first_name').val(),
                seller_last_name: $('#seller_last_name').val(),
                seller_mobile: $('#seller_mobile').val(),
                seller_national_id: $('#seller_national_id').val(),
                seller_license_number: $('#seller_license_number').val(),
                seller_license_expiry: $('#seller_license_expiry').val(),
                seller_chassis_number: $('#seller_chassis_number').val(),
                seller_car_type: $('#seller_car_type').val(),
                seller_category_id: $('#seller_category_id').val(),
                seller_model_id: $('#seller_model_id').val(),
                seller_manufacturing_year: $('#seller_manufacturing_year').val(),
                seller_brand_origin_variant_id: $('#seller_brand_origin_variant_id').val(),
                seller_color: $('#seller_color').val(),
                seller_plate_number: $('#seller_plate_number').val()
            };

            $.ajax({
                url: '/buy-sell/store-contact',
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#create_contact_errors').hide().find('ul').empty();

                    if (response.success) {
                        if (response.buyer) {
                            $('#buy_sell_buyer_search').val(response.buyer.name);
                            $('#buy_sell_buyer_contact_id').val(response.buyer.id);
                            $('#buy_sell_clear_buyer').show();
                        }
                        if (response.seller) {
                            $('#buy_sell_seller_search').val(response.seller.name);
                            $('#buy_sell_contact_id').val(response.seller.id);
                            $('#buy_sell_clear_seller').show();

                            // Enable the add vehicle button since we now have a seller
                            $('#buy_sell_inspection_modal .add_new_models_buysell').prop('disabled', false);
                        }

                        // If a new contact device (vehicle) was created, add it to the dropdown and select it
                        if (response.contact_device && response.contact_device.id) {
                            var device = response.contact_device;
                            var displayText = device.model_name || '';
                            var additionalInfo = [];

                            if (device.plate_number) {
                                additionalInfo.push("@lang('car.plate'): " + device.plate_number);
                            }
                            if (device.color) {
                                additionalInfo.push("@lang('car.color'): " + device.color);
                            }
                            if (additionalInfo.length > 0) {
                                displayText += ' (' + additionalInfo.join(', ') + ')';
                            }

                            var $carDropdown = $('#buy_sell_model_id');
                            // Clear the "no models" placeholder if present
                            $carDropdown.find('option[value=""]').text("@lang('restaurant.select_car_model')");
                            // Add the new device option and select it
                            $carDropdown.append('<option value="' + device.id + '" selected>' + displayText + '</option>');
                            $carDropdown.val(device.id).trigger('change');
                        }

                        $('#create_contact_modal').modal('hide');
                        toastr.success(response.message);
                        resetContactForm();
                    } else {
                        toastr.error(response.message || "@lang('messages.something_went_wrong')");
                        $('#create_contact_form').data('submitting', false);
                        $submitBtn.attr('disabled', false);
                    }
                },
                error: function(xhr) {
                    var errorBox = $('#create_contact_errors');
                    var list = errorBox.find('ul');
                    list.empty();

                    var errors = (xhr.responseJSON && xhr.responseJSON.errors) ? xhr.responseJSON.errors : {};
                    var hasError = false;

                    $.each(errors, function(field, messages) {
                        if ($.isArray(messages)) {
                            $.each(messages, function(_, msg) {
                                list.append('<li>' + msg + '</li>');
                                toastr.error(msg);
                            });
                        } else if (messages) {
                            list.append('<li>' + messages + '</li>');
                            toastr.error(messages);
                        }
                        hasError = true;
                    });

                    if (!hasError) {
                        var genericMsg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : "@lang('messages.something_went_wrong')";
                        list.append('<li>' + genericMsg + '</li>');
                        toastr.error(genericMsg);
                    }

                    errorBox.show();

                    $('#create_contact_form').data('submitting', false);
                    $submitBtn.attr('disabled', false);
                }
            });
        });

        // Real-time mobile number validation
        var mobileCheckTimeout = {};

        function checkMobileExists(inputId, errorId, statusId) {
            var mobileNumber = $('#' + inputId).val().trim();
            var $error = $('#' + errorId);
            var $status = $('#' + statusId);
            var $input = $('#' + inputId);

            // Clear previous timeout
            if (mobileCheckTimeout[inputId]) {
                clearTimeout(mobileCheckTimeout[inputId]);
            }

            // Reset state
            $error.hide().text('');
            $input.removeClass('is-invalid');
            $status.hide();

            // Skip if empty or too short
            if (!mobileNumber || mobileNumber.length < 5) {
                return;
            }

            // Debounce: wait 500ms after user stops typing
            mobileCheckTimeout[inputId] = setTimeout(function() {
                // Show loading spinner
                $status.show().find('i').removeClass('fa-check fa-times text-success text-danger').addClass('fa-spinner fa-spin');

                $.ajax({
                    url: '/check-mobile',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        mobile_number: mobileNumber
                    },
                    success: function(response) {
                        $status.show();
                        if (response.is_mobile_exists) {
                            // Mobile exists - show error
                            $status.find('i').removeClass('fa-spinner fa-spin fa-check text-success').addClass('fa-times text-danger');
                            $error.text(response.msg).show();
                            $input.addClass('is-invalid');
                        } else {
                            // Mobile is available - show success
                            $status.find('i').removeClass('fa-spinner fa-spin fa-times text-danger').addClass('fa-check text-success');
                        }
                    },
                    error: function() {
                        $status.hide();
                    }
                });
            }, 500);
        }

        // Attach event handlers for buyer and seller mobile inputs
        $('#buyer_mobile').on('input blur', function() {
            checkMobileExists('buyer_mobile', 'buyer_mobile_error', 'buyer_mobile_status');
        });

        $('#seller_mobile').on('input blur', function() {
            checkMobileExists('seller_mobile', 'seller_mobile_error', 'seller_mobile_status');
        });
    });
</script>
