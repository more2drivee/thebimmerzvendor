<div class="modal-dialog modal-lg" id="dialog" role="document">
    <div class="modal-content">
        <!-- Inline Style Tag for Layout Control -->
        <style>
            /* .custom-input-group {
                display: flex;
                align-items: center;
                flex-wrap: nowrap;
            } */

            /* Keep icon and input order consistent even in RTL (Arabic) */
            .custom-input-group {
                direction: ltr;
            }

            .custom-input-group .input-group-prepend {
                margin-right: 0;
                /* Remove the space between icon and input */
            }

            .custom-input-group .input-group-text {
                border-right: 0;
                /* Remove right border of icon container */
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
                padding: 0.5rem 0.75rem !important;
            }

            .custom-input-group .form-control {
                flex-grow: 1;
                border-left: 0;
                /* Remove left border of input */
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
            }

            /* Custom dropdown styles */
            .custom-dropdown {
                position: relative;
                width: 100%;
            }

            .custom-dropdown-search {
                width: 100%;
                padding: 8px;
                border: 1px solid #ced4da;
                border-bottom: none;
                border-radius: 4px 4px 0 0;
            }

            .custom-dropdown select {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            .custom-dropdown-options {
                max-height: 200px;
                overflow-y: auto;
                border: 1px solid #ced4da;
                border-radius: 0 0 4px 4px;
                background-color: white;
                z-index: 1000;
            }

            .custom-dropdown-option {
                padding: 8px 12px;
                cursor: pointer;
            }

            .custom-dropdown-option:hover {
                background-color: #f8f9fa;
            }

            .custom-dropdown-option.selected {
                background-color: #e9ecef;
            }

            .custom-dropdown-option.hidden {
                display: none;
            }

            .custom-dropdown-display {
                padding: 4px 12px;
                border: 1px solid #ced4da;
                border-radius: 4px;
                background-color: white;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .custom-dropdown-display:after {
                content: '\25BC';
                font-size: 0.8em;
            }

            .custom-dropdown.open .custom-dropdown-display {
                border-radius: 4px 4px 0 0;
                border-bottom: none;
            }

            .custom-dropdown.open .custom-dropdown-display:after {
                content: '\25B2';
            }

            .custom-dropdown-options-container {
                display: none;
                position: absolute;
                width: 100%;
                z-index: 1000;
                background-color: white;
            }

            .custom-dropdown.open .custom-dropdown-options-container {
                display: block;
            }
        </style>

        <form id="addVehicleForm" action="{{ route('vehicles.store') }}" method="POST" novalidate>
            @csrf <!-- CSRF token for security -->
            <div class="modal-header">

                <h4 class="modal-title" style="width:100%; text-align: center;">@lang('restaurant.add_car')</h4>
            </div>

            <div class="modal-body">
                <div class="row">
                    <!-- Chassis Number Input -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="chassis_number">@lang('car.vin'):</label>
                            <div class="input-group custom-input-group d-flex align-items-center flex-nowrap">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fa fa-key"></i>
                                    </span>
                                </div>
                                <input type="text" name="chassis_number" id="chassis_number" class="form-control"
                                    placeholder="{{ __('car.vin') }}" value="{{ old('chassis_number') }}" maxlength="17"
                                    required>
                            </div>
                            <small class="text-muted">@lang('car.entervin')</small>
                            @error('chassis_number')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Car Type -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="car_type">@lang('car.cartype'):</label>
                            <div class="input-group custom-input-group d-flex align-items-center flex-nowrap">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fa fa-car"></i>
                                    </span>
                                </div>
                                <select name="car_type" id="car_type" class="form-control" required>
                                    <option value="">@lang('car.selectcartype')</option>
                                    <option value="ملاكي">ملاكي</option>
                                    <option value="اجرة">اجرة</option>
                                    <option value="نقل ثقيل">نقل ثقيل</option>
                                    <option value="نقل خفيف">نقل خفيف</option>
                                </select>
                            </div>
                            <small id="car_type_error" class="text-danger d-none">@lang('messages.required')</small>
                            @error('car_type')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Brand, Model, and Manufacturing Year in one row -->
                    <div class="col-md-12">
                        <div class="row">
                            <!-- Brand Select -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="category_id">@lang('car.brand'):</label>
                                    <div class="input-group custom-input-group d-flex flex-nowrap">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-car-alt"></i>
                                            </span>
                                        </div>
                                        @php
                                            $brand_information = \Illuminate\Support\Facades\DB::table('categories')
                                                ->where('category_type', 'device')
                                                ->select('id', 'name')
                                                ->get();
                                        @endphp
                                        <div class="custom-dropdown" id="brand-dropdown">
                                            <div class="custom-dropdown-display" id="brand-display">@lang('car.selectbrand')</div>
                                            <div class="custom-dropdown-options-container">
                                                <input type="text" class="custom-dropdown-search" id="brand-search" placeholder="Search brands...">
                                                <div class="custom-dropdown-options" id="brand-options">
                                                    <div class="custom-dropdown-option" data-value="">@lang('car.selectbrand')</div>
                                                    @foreach ($brand_information as $category)
                                                        <div class="custom-dropdown-option" data-value="{{ $category->id }}"
                                                            {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}</div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <select name="category_id" id="category_id" class="form-control" required>
                                                <option value="">@lang('car.selectbrand')</option>
                                                @foreach ($brand_information as $category)
                                                    <option value="{{ $category->id }}"
                                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <small id="category_error" class="text-danger d-none">@lang('car.no_matched_brand')</small>
                                    @error('brand_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Model Select -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="model_id">@lang('car.model')</label>
                                    <div class="input-group custom-input-group d-flex  flex-nowrap">
                                        <div class="input-group-prepend">
                                            <!-- Change in Brand Select icon -->
                                            <span class="input-group-text">
                                                <i class="fa fa-car-alt"></i>
                                                <!-- Changed from fa-cogs to fa-trademark -->
                                            </span>
                                        </div>
                                        <div class="custom-dropdown" id="model-dropdown">
                                            <div class="custom-dropdown-display" id="model-display">@lang('car.selectmodel')</div>
                                            <div class="custom-dropdown-options-container">
                                                <input type="text" class="custom-dropdown-search" id="model-search" placeholder="Search models...">
                                                <div class="custom-dropdown-options" id="model-options">
                                                    <div class="custom-dropdown-option" data-value="">@lang('car.selectmodel')</div>
                                                    <!-- Models will be populated dynamically -->
                                                </div>
                                            </div>
                                            <select name="model_id" id="model_id" class="form-control" required>
                                                <option value="">@lang('car.selectmodel')</option>
                                            </select>
                                        </div>
                                    </div>
                                    <small id="model_error" class="text-danger d-none">@lang('car.no_matched_model')</small>
                                    @error('model_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Manufacturing Year Input -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="manufacturing_year">@lang('car.manufacturing')</label>
                                    <div class="input-group custom-input-group d-flex align-items-center flex-nowrap">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-calendar"></i>
                                            </span>
                                        </div>
                                        <select class="form-control" id="manufacturing_year" name="manufacturing_year"
                                            required>
                                            <option value="">@lang('car.selectyear')</option>
                                        </select>
                                    </div>
                                    @error('manufacturing_year')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Car Country (origin) - depends on selected brand -->
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="brand_origin_variant_id">@lang('car.country')</label>
                                    <div class="input-group custom-input-group d-flex align-items-center flex-nowrap">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-flag"></i>
                                            </span>
                                        </div>
                                        <select name="brand_origin_variant_id" id="brand_origin_variant_id" class="form-control">
                                            <option value="">@lang('car.selectcountry')</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Color -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="color">@lang('restaurant.color'):</label>
                            <div class="input-group custom-input-group d-flex align-items-center flex-nowrap">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fa fa-paint-brush"></i>
                                    </span>
                                </div>
                                <input type="text" name="color" id="color" class="form-control"
                                    placeholder="@lang('car.color')" value="{{ old('color') }}" required>
                            </div>
                            @error('color')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Plate Number Input -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="plate_number">@lang('restaurant.plate_number'):</label>
                            <div class="input-group custom-input-group d-flex align-items-center flex-nowrap">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fa fa-key"></i>
                                    </span>
                                </div>
                                <input type="text" name="plate_number" id="plate_number" class="form-control"
                                    placeholder="@lang('car.plate')" value="{{ old('plate_number') }}" required>
                            </div>
                            @error('plate_number')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Motor CC (optional) -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="motor_cc">Motor CC:</label>
                            <div class="input-group custom-input-group d-flex align-items-center flex-nowrap">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fa fa-tachometer"></i>
                                    </span>
                                </div>
                                <input type="number" name="motor_cc" id="motor_cc" class="form-control"
                                    placeholder="Motor CC" value="{{ old('motor_cc') }}">
                            </div>
                            @error('motor_cc')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="contact_id" id="model_customer_id" value="{{ $customer_id }}" required>
            <input type="hidden" name="contact_name" id="model_customer_name" value="{{ $contact_name ?? '' }}">

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save</button>

                <!-- Add hidden fields to track if brand/model were found in database -->
                <input type="hidden" name="brand_not_in_db" id="brand_not_in_db" value="0">
                <input type="hidden" name="model_not_in_db" id="model_not_in_db" value="0">
            </div>
        </form>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<!-- Update the JavaScript -->
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize manufacturing year dropdown
        initManufacturingYearDropdown();

        // Initialize custom dropdowns
        initCustomDropdowns();

        // Function to initialize manufacturing year dropdown
        function initManufacturingYearDropdown() {
            var $yearDropdown = $('#manufacturing_year');
            var currentYear = new Date().getFullYear();

            // Clear existing options
            $yearDropdown.empty();

            // Add default option
            $yearDropdown.append('<option value="">@lang("car.selectyear")</option>');

            // Add year options (current year down to 1980)
            for (var year = currentYear; year >= 1980; year--) {
                $yearDropdown.append('<option value="' + year + '">' + year + '</option>');
            }

            console.log('Manufacturing year dropdown initialized with years from ' + currentYear + ' to 1980');
        }

        // Flag to prevent change event during VIN lookup
        var isVinLookupInProgress = false;

        // Chassis number input handler
        $('#chassis_number').on('input', function() {
            var chassisNumber = $(this).val().trim();
            if (chassisNumber.length === 17) {
                console.log('VIN lookup triggered for:', chassisNumber);
                isVinLookupInProgress = true;
                performChassisLookup(chassisNumber);
                // Check if VIN belongs to any groups and show toast alerts
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

        // Function to initialize custom dropdowns
        function initCustomDropdowns() {
            console.log('Initializing custom dropdowns');

            // Brand dropdown functionality
            initDropdown('brand');

            // Model dropdown functionality
            initDropdown('model');

            // Search functionality for brand dropdown
            $('#brand-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                filterDropdownOptions('brand', searchTerm);
            });

            // Search functionality for model dropdown
            $('#model-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                filterDropdownOptions('model', searchTerm);
            });

            // Ensure brand dropdown change triggers model dropdown update and country refresh
            $('#category_id').on('change', function() {
                var brandId = $(this).val();
                console.log('Brand dropdown changed to:', brandId, 'VIN lookup in progress:', isVinLookupInProgress);
                
                // Skip if VIN lookup is in progress (to avoid clearing country dropdown)
                if (isVinLookupInProgress) {
                    console.log('Skipping change event during VIN lookup');
                    return;
                }
                
                if (brandId) {
                    refreshModelDropdown(brandId);
                } else {
                    // Clear model dropdown if no brand selected
                    $('#model_id').empty().append('<option value="">@lang("car.selectmodel")</option>');
                    $('#model-options').empty().append('<div class="custom-dropdown-option" data-value="">@lang("car.selectmodel")</div>');
                    $('#model-display').text('@lang("car.selectmodel")');
                    $('#model-dropdown').removeAttr('data-selected-value');
                    $('#model-dropdown').removeAttr('data-selected-text');

                    // Clear country dropdown as well
                    $('#brand_origin_variant_id').empty().append('<option value="">@lang("car.selectcountry")</option>');
                }
            });
        }

        // Initialize a custom dropdown
        function initDropdown(type) {
            var $dropdown = $('#' + type + '-dropdown');
            var $display = $('#' + type + '-display');
            var $optionsContainer = $('#' + type + '-dropdown .custom-dropdown-options-container');
            var $options = $('#' + type + '-options');
            var $select = $('#' + type + '_id'); // The original select element

            // Toggle dropdown on display click
            $display.on('click', function() {
                $dropdown.toggleClass('open');
                if ($dropdown.hasClass('open')) {
                    $optionsContainer.show();
                } else {
                    $optionsContainer.hide();
                }
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#' + type + '-dropdown').length) {
                    $dropdown.removeClass('open');
                    $optionsContainer.hide();
                }
            });

            // Handle option selection
            $options.on('click', '.custom-dropdown-option', function() {
                var value = $(this).data('value');
                var text = $(this).text();

                // Update the display
                $display.text(text);

                // Store the selected value in a data attribute on the dropdown for easier retrieval
                $dropdown.attr('data-selected-value', value);
                $dropdown.attr('data-selected-text', text);

                // Update the hidden select and ensure it's properly set
                $select.val(value);

                // Force the select element to have the selected value
                if ($select.find('option[value="' + value + '"]').length > 0) {
                    $select.find('option').prop('selected', false);
                    $select.find('option[value="' + value + '"]').prop('selected', true);
                } else if (value) {
                    // If the option doesn't exist but we have a value, add it
                    $select.append('<option value="' + value + '" selected>' + text + '</option>');
                }

                // Trigger change event after ensuring the value is set
                $select.trigger('change');

                console.log(type + ' select value after setting:', $select.val());
                console.log(type + ' dropdown data-selected-value:', $dropdown.attr('data-selected-value'));

                // Close the dropdown
                $dropdown.removeClass('open');
                $optionsContainer.hide();

                // Mark as selected in the custom dropdown
                $('.custom-dropdown-option', $options).removeClass('selected');
                $(this).addClass('selected');

                // If this is the brand dropdown, fetch models
                if (type === 'brand') {
                    refreshModelDropdown(value);
                }
            });

            // Initialize with current select value or default text
            var selectedValue = $select.val();
            if (selectedValue && selectedValue !== '') {
                var selectedText = $select.find('option:selected').text();
                $display.text(selectedText);

                // Store the selected value in data attributes
                $dropdown.attr('data-selected-value', selectedValue);
                $dropdown.attr('data-selected-text', selectedText);

                console.log('Initializing ' + type + ' dropdown with value:', selectedValue, 'text:', selectedText);

                $('#' + type + '-options .custom-dropdown-option[data-value="' + selectedValue + '"]').addClass('selected');
            } else {
                // Set default text based on type
                if (type === 'brand') {
                    $display.text('@lang("car.selectbrand")');
                } else if (type === 'model') {
                    $display.text('@lang("car.selectmodel")');
                }

                // Clear data attributes
                $dropdown.removeAttr('data-selected-value');
                $dropdown.removeAttr('data-selected-text');
            }
        }

        // Filter dropdown options based on search term
        function filterDropdownOptions(type, searchTerm) {
            var normalizedSearch = (searchTerm || '').toString().toLowerCase();
            $('#' + type + '-options .custom-dropdown-option').each(function() {
                var optionText = ($(this).text() || '').toString().toLowerCase();
                if (optionText.indexOf(normalizedSearch) > -1) {
                    $(this).removeClass('hidden');
                } else {
                    $(this).addClass('hidden');
                }
            });
        }

        // Update custom dropdown from select value
        function updateDropdownFromSelect(type) {
            var $select = $('#' + type + '_id');
            var value = $select.val();
            var text = $select.find('option:selected').text();

            $('#' + type + '-display').text(text);

            // Mark as selected in the custom dropdown
            $('#' + type + '-options .custom-dropdown-option').removeClass('selected');
            $('#' + type + '-options .custom-dropdown-option[data-value="' + value + '"]').addClass('selected');
        }


            // Function to refresh brand dropdown data
            function refreshBrandDropdown(selectedBrandId = null) {
                $.ajax({
                    url: "/bookings/get-brands", // Create this route
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        // Store current selection if any
                        var currentSelection = selectedBrandId || $('#category_id').val();

                        // Clear and rebuild the select dropdown
                        var $dropdown = $('#category_id');
                        $dropdown.empty();
                        $dropdown.append('<option value="">@lang("car.selectbrand")</option>');

                        // Clear and rebuild the custom dropdown options
                        var $customOptions = $('#brand-options');
                        $customOptions.empty();
                        $customOptions.append('<div class="custom-dropdown-option" data-value="">@lang("car.selectbrand")</div>');

                        // Add all brands from the response
                        $.each(response, function(index, brand) {
                            var isSelected = (brand.id == currentSelection);

                            // Add to the original select
                            $dropdown.append('<option value="' + brand.id + '"' +
                                (isSelected ? ' selected' : '') + '>' + brand.name + '</option>');

                            // Add to the custom dropdown
                            $customOptions.append('<div class="custom-dropdown-option' +
                                (isSelected ? ' selected' : '') + '" data-value="' +
                                brand.id + '">' + brand.name + '</div>');
                        });

                        // If we have a selected brand, trigger change to load models
                        if (currentSelection) {
                            $dropdown.trigger('change');

                            // Update the custom dropdown display
                            var selectedText = $dropdown.find('option:selected').text();
                            $('#brand-display').text(selectedText);

                            // Store the selected value in data attributes
                            $('#brand-dropdown').attr('data-selected-value', currentSelection);
                            $('#brand-dropdown').attr('data-selected-text', selectedText);

                            console.log('Setting brand dropdown data attributes - value:', currentSelection, 'text:', selectedText);
                        }

                        console.log('Brand dropdown refreshed, selected:', currentSelection);
                    },
                    error: function(xhr) {
                        console.error('Error refreshing brands:', xhr);
                    }
                });
            }

            // Function to refresh brand origins (countries) for a specific brand
            function refreshBrandOrigins(brandId, selectedVariantId = null, selectedCountry = null) {
                if (!brandId) return;

                $.ajax({
                    url: "/bookings/get-brand-origins/" + brandId,
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        console.log('Brand origins loaded for brand ' + brandId + ':', response);

                        // Clear and rebuild the country select dropdown
                        var $countrySelect = $('#brand_origin_variant_id');
                        $countrySelect.empty().append('<option value="">@lang("car.selectcountry")</option>');

                        // Add all variants from the response
                        $.each(response || [], function(index, variant) {
                            var label = variant.label || variant.name;
                            if (variant.country_of_origin && !label.includes('(')) {
                                label += ' (' + variant.country_of_origin + ')';
                            }
                            $countrySelect.append('<option value="' + variant.id + '">' + label + '</option>');
                        });

                        // Preselect by variant id if provided
                        if (selectedVariantId) {
                            $countrySelect.val(String(selectedVariantId));
                            console.log('Preselected country variant by ID:', selectedVariantId);
                        } else if (selectedCountry) {
                            // Fallback: try to preselect by country name (case-insensitive)
                            var matchId = null;
                            var normalizedTarget = String(selectedCountry).trim().toLowerCase();
                            $.each(response || [], function(index, variant) {
                                var vCountry = (variant.country_of_origin || '').trim().toLowerCase();
                                if (vCountry && vCountry === normalizedTarget) {
                                    matchId = variant.id;
                                    return false; // break
                                }
                            });
                            if (matchId) {
                                $countrySelect.val(String(matchId));
                                console.log('Preselected country variant by name:', selectedCountry, '-> id:', matchId);
                            }
                        }

                        console.log('Country dropdown refreshed for brand ' + brandId);
                    },
                    error: function(xhr) {
                        console.error('Error loading brand origins for brand ' + brandId + ':', xhr);
                    }
                });
            }

            // Function to refresh model dropdown data for a specific brand
            function refreshModelDropdown(brandId, selectedModelId = null) {
                if (!brandId) return;

                // Store current selection before AJAX call
                var currentSelection = selectedModelId || $('#model_id').val();
                console.log('Current model selection before refresh:', currentSelection);

                $.ajax({
                    url: "/bookings/get-models/" + brandId,
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        console.log('Brand data received for brand ' + brandId + ':', response);

                        // Clear and rebuild the model select dropdown
                        var $dropdown = $('#model_id');
                        $dropdown.empty();
                        $dropdown.append('<option value="">@lang("car.selectmodel")</option>');

                        // Clear and rebuild the custom dropdown options for models
                        var $customOptions = $('#model-options');
                        $customOptions.empty();
                        $customOptions.append('<div class="custom-dropdown-option" data-value="">@lang("car.selectmodel")</div>');

                        // Add all models from the response.models
                        $.each(response.models || [], function(index, model) {
                            var isSelected = (model.id == currentSelection);

                            // Add to the original select
                            $dropdown.append('<option value="' + model.id + '"' +
                                (isSelected ? ' selected' : '') + '>' + model.name + '</option>');

                            // Add to the custom dropdown
                            $customOptions.append('<div class="custom-dropdown-option' +
                                (isSelected ? ' selected' : '') + '" data-value="' +
                                model.id + '">' + model.name + '</div>');
                        });

                        // Populate brand origins variants from the same response
                        var $countrySelect = $('#brand_origin_variant_id');
                        $countrySelect.empty().append('<option value="">@lang("car.selectcountry")</option>');
                        
                        $.each(response.variants || [], function(index, variant) {
                            var label = variant.label || variant.name;
                            $countrySelect.append('<option value="' + variant.id + '">' + label + '</option>');
                        });

                        // Explicitly set the selected value after rebuilding the dropdown
                        if (currentSelection) {
                            var targetStr = String(currentSelection);
                            // Try immediate selection first (no delay)
                            if ($dropdown.find('option[value="' + targetStr + '"]').length > 0) {
                                $dropdown.val(targetStr).trigger('change');

                                var selectedTextNow = $dropdown.find('option:selected').text();
                                $('#model-display').text(selectedTextNow);
                                $('#model-dropdown').attr('data-selected-value', targetStr);
                                $('#model-dropdown').attr('data-selected-text', selectedTextNow);
                                $('#model-options .custom-dropdown-option').removeClass('selected');
                                $('#model-options .custom-dropdown-option[data-value="' + targetStr + '"]').addClass('selected');

                                console.log('Model preselected immediately to:', targetStr);
                            } else {
                                // Fallback with small delay
                                setTimeout(function() {
                                    console.log('Checking for model ID (delayed):', targetStr);
                                    console.log('Available options:', $dropdown.find('option').map(function() {
                                        return { value: $(this).val(), text: $(this).text() };
                                    }).get());

                                    if ($dropdown.find('option[value="' + targetStr + '"]').length > 0) {
                                        $dropdown.val(targetStr).trigger('change');
                                        var selectedText = $dropdown.find('option:selected').text();
                                        $('#model-display').text(selectedText);
                                        $('#model-dropdown').attr('data-selected-value', targetStr);
                                        $('#model-dropdown').attr('data-selected-text', selectedText);
                                        $('#model-options .custom-dropdown-option').removeClass('selected');
                                        $('#model-options .custom-dropdown-option[data-value="' + targetStr + '"]').addClass('selected');
                                        console.log('Model selected after delay:', targetStr);
                                    } else {
                                        console.warn('Model ID ' + currentSelection + ' not found in dropdown options');

                                        // Check if we have a model name from window.aiAnalysisData
                                        if (window.aiAnalysisData && window.aiAnalysisData.model_name) {
                                            var modelName = window.aiAnalysisData.model_name;
                                            var modelId = currentSelection;

                                            console.log('Adding model from AI analysis:', modelName, currentSelection);

                                            // Add to the original select
                                            $dropdown.append('<option value="' + targetStr + '" selected>' + modelName + '</option>');

                                            // Add to the custom dropdown
                                            $customOptions.append('<div class="custom-dropdown-option selected" data-value="' +
                                                targetStr + '">' + modelName + '</div>');

                                            // Update the display
                                            $('#model-display').text(modelName);

                                            // Store the selected value in data attributes
                                            $('#model-dropdown').attr('data-selected-value', targetStr);
                                            $('#model-dropdown').attr('data-selected-text', modelName);

                                            // Trigger change event
                                            $dropdown.trigger('change');
                                        } else {
                                            $('#model-display').text('@lang("car.selectmodel")');
                                        }
                                    }
                                    $('#model_error').addClass('d-none');
                                }, 250);
                            }
                        } else {
                            $('#model-display').text('@lang("car.selectmodel")');
                        }

                        console.log('Model dropdown refreshed for brand ' + brandId + ', selected:', currentSelection);
                    },
                    error: function(xhr) {
                        console.error('Error refreshing models for brand ' + brandId + ':', xhr);
                    }
                });
            }

            function performChassisLookup(chassisNumber) {
                console.log('Starting VIN lookup for:', chassisNumber);

                // Reset form fields before new lookup
                $('#category_id').val('');
                $('#model_id').empty().append('<option value="">Select Model</option>');
                $('#manufacturing_year').val('');
                $('#car_type').val('');
                $('#color').val('');

                // Reset custom dropdowns
                $('#brand-display').text('@lang("car.selectbrand")');
                $('#model-display').text('@lang("car.selectmodel")');
                $('#brand-options .custom-dropdown-option').removeClass('selected');
                $('#model-options').empty().append('<div class="custom-dropdown-option" data-value="">@lang("car.selectmodel")</div>');

                // Reset data attributes
                $('#brand-dropdown').removeAttr('data-selected-value');
                $('#brand-dropdown').removeAttr('data-selected-text');
                $('#model-dropdown').removeAttr('data-selected-value');
                $('#model-dropdown').removeAttr('data-selected-text');

                // Reset the not-in-db flags
                $('#brand_not_in_db').val('0');
                $('#model_not_in_db').val('0');

                // Clear any previous AI data
                window.aiAnalysisData = null;

                $.ajax({
                    url: "{{ route('booking.lookup_chassis') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        chassis_number: chassisNumber
                    },
                    dataType: "json",
                    beforeSend: function() {
                        // Show loading indicator if needed
                        console.log('Sending VIN lookup request...');
                    },
                    success: function(response) {
                        console.log('VIN lookup response:', response);

                        if (response.success && response.data) {
                            var aiData = response.data.ai_analysis || {};

                            // Store AI data globally for later use
                            window.aiAnalysisData = aiData;
                            console.log('Stored AI analysis data:', window.aiAnalysisData);

                            // Check if new brand or model was created
                            var brandCreated = response.data.brand_created === true;
                            var modelCreated = response.data.model_created === true;

                            // If brand was created, refresh the brand dropdown
                            if (brandCreated) {
                                console.log('New brand created, refreshing brand dropdown');
                                refreshBrandDropdown(response.data.brand_id);
                            }

                            // Set the brand (category)
                            if (response.data.brand_id) {
                                console.log('Setting brand ID to:', response.data.brand_id);

                                // If no new brand was created, just set the value
                                if (!brandCreated) {
                                    // First update the select element
                                    $('#category_id').val(response.data.brand_id);

                                    // Get the selected text
                                    var selectedText = $('#category_id option:selected').text();
                                    console.log('Selected brand text:', selectedText);

                                    // Update the custom brand dropdown display
                                    $('#brand-display').text(selectedText);

                                    // Store the selected value in data attributes
                                    $('#brand-dropdown').attr('data-selected-value', response.data.brand_id);
                                    $('#brand-dropdown').attr('data-selected-text', selectedText);

                                    console.log('Setting brand dropdown data attributes - value:', response.data.brand_id, 'text:', selectedText);

                                    // Update selected state in custom dropdown
                                    $('#brand-options .custom-dropdown-option').removeClass('selected');
                                    $('#brand-options .custom-dropdown-option[data-value="' + response.data.brand_id + '"]').addClass('selected');

                                    // Call functions directly instead of triggering change to avoid clearing country dropdown
                                    // Pass selected model id so refreshModelDropdown preselects it
                                    refreshModelDropdown(response.data.brand_id, response.data.model_id || null);
                                    refreshBrandOrigins(
                                        response.data.brand_id,
                                        response.data.variant_id || null,
                                        response.data.variant_country_of_origin || response.data.country_of_origin || null
                                    );
                                }
                                $('#category_error').addClass('d-none');

                                // Check if brand was found in database or added by AI
                                if (response.data.brand_found_in_db === false) {
                                    $('#brand_not_in_db').val('1');
                                    console.log('Setting brand_not_in_db to 1');
                                } else {
                                    $('#brand_not_in_db').val('0');
                                }

                                // If no model ID from response, show hint. Otherwise, refreshModelDropdown has already selected the model
                                if (!response.data.model_id) {
                                    $('#model_error').removeClass('d-none');
                                    setTimeout(function() {
                                        $('#model_error').addClass('d-none');
                                    }, 5000);
                                    $('#model_not_in_db').val('1');
                                    console.log('Setting model_not_in_db to 1 (no model_id)');
                                }
                            } else {
                                // Show "No matched brand" message
                                $('#category_error').removeClass('d-none');
                                // Hide the message after 5 seconds
                                setTimeout(function() {
                                    $('#category_error').addClass('d-none');
                                }, 5000);

                                // Set flag that brand wasn't found in database
                                $('#brand_not_in_db').val('1');
                                console.log('Setting brand_not_in_db to 1 (no brand_id)');

                                // Show "No matched model" message (since brand wasn't found)
                                $('#model_error').removeClass('d-none');
                                // Hide the message after 5 seconds
                                setTimeout(function() {
                                    $('#model_error').addClass('d-none');
                                }, 5000);

                                // Set flag that model wasn't found in database
                                $('#model_not_in_db').val('1');
                                console.log('Setting model_not_in_db to 1 (no brand_id)');
                            }

                            // Set manufacturing year
                            if (response.data.year) {
                                var year = response.data.year.toString();
                                console.log('Setting manufacturing year to:', year);

                                // Check if year option exists
                                if ($('#manufacturing_year option[value="' + year + '"]').length === 0) {
                                    console.log('Year option not found, adding it');
                                    $('#manufacturing_year').append(new Option(year, year, false, true));
                                }

                                // Set the year value
                                $('#manufacturing_year').val(year);
                                console.log('Manufacturing year value after setting:', $('#manufacturing_year').val());
                            } else {
                                console.log('No year data available in response');
                            }

                            // Set color if available
                            if (response.data.color) {
                                console.log('Setting color to:', response.data.color);
                                $('#color').val(response.data.color);
                            }

                            // Set country of origin if variant was created/found from AI response
                            if (response.data.variant_id) {
                                console.log('Variant ID from AI:', response.data.variant_id);
                                
                                // Fetch variants from database for this brand
                                if (response.data.brand_id) {
                                    $.ajax({
                                        url: "/bookings/get-brand-origins/" + response.data.brand_id,
                                        type: "GET",
                                        dataType: "json",
                                        success: function(variants) {
                                            console.log('Brand origins loaded:', variants);
                                            
                                            // Clear and rebuild the country select dropdown
                                            var $countrySelect = $('#brand_origin_variant_id');
                                            $countrySelect.empty().append('<option value="">@lang("car.selectcountry")</option>');
                                            
                                            // Add all variants from the response
                                            $.each(variants || [], function(index, variant) {
                                                var label = variant.name;
                                                if (variant.country_of_origin) {
                                                    label += ' (' + variant.country_of_origin + ')';
                                                }
                                                $countrySelect.append('<option value="' + variant.id + '">' + label + '</option>');
                                                
                                                // Check if this variant matches the AI variant ID
                                                if (variant.id == response.data.variant_id) {
                                                    $countrySelect.val(variant.id);
                                                    console.log('Selected variant from AI:', variant.id);
                                                }
                                            });
                                        },
                                        error: function(xhr) {
                                            console.error('Error loading brand origins:', xhr);
                                        }
                                    });
                                }
                            }

                            toastr.success('Vehicle information retrieved successfully');
                            console.log('VIN lookup completed successfully');
                        } else {
                            toastr.warning(response.message || 'Could not find complete vehicle information');
                        }
                        
                        // Reset VIN lookup flag
                        isVinLookupInProgress = false;
                        console.log('VIN lookup flag reset');
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Error looking up chassis number: ' + error);
                        // Reset VIN lookup flag even on error
                        isVinLookupInProgress = false;
                    }
                });
            }

            // Add form submit handler to check if brand/model need to be added to database
            $('#addVehicleForm').on('submit', function(e) {
                e.preventDefault(); // Always prevent default form submission

                if ($(this).data('submitting')) {
                    return false;
                }

                // Validate required fields before submission
                var isValid = true;
                var errorMessages = [];

                // Get values from both the select elements and the data attributes
                var brandId = $('#category_id').val() || $('#brand-dropdown').attr('data-selected-value');
                var modelId = $('#model_id').val() || $('#model-dropdown').attr('data-selected-value');

                // Log the values for debugging
                console.log('Form validation - Brand ID from select:', $('#category_id').val());
                console.log('Form validation - Brand ID from data attribute:', $('#brand-dropdown').attr('data-selected-value'));
                console.log('Form validation - Model ID from select:', $('#model_id').val());
                console.log('Form validation - Model ID from data attribute:', $('#model-dropdown').attr('data-selected-value'));

                // If we have values in the data attributes but not in the selects, update the selects
                if (!$('#category_id').val() && $('#brand-dropdown').attr('data-selected-value')) {
                    $('#category_id').val($('#brand-dropdown').attr('data-selected-value'));
                    console.log('Updated category_id from data attribute:', $('#category_id').val());
                }

                if (!$('#model_id').val() && $('#model-dropdown').attr('data-selected-value')) {
                    $('#model_id').val($('#model-dropdown').attr('data-selected-value'));
                    console.log('Updated model_id from data attribute:', $('#model_id').val());
                }

                // Check if brand is selected
                if (!brandId) {
                    isValid = false;
                    errorMessages.push('Please select a brand');
                    $('#category_error').removeClass('d-none').text('Please select a brand');
                } else {
                    $('#category_error').addClass('d-none');
                }

                // Check if model is selected
                if (!modelId) {
                    isValid = false;
                    errorMessages.push('Please select a model');
                    $('#model_error').removeClass('d-none').text('Please select a model');
                } else {
                    $('#model_error').addClass('d-none');
                }

                // Check if manufacturing year is selected
                if (!$('#manufacturing_year').val()) {
                    isValid = false;
                    errorMessages.push('Please select a manufacturing year');
                    $('#manufacturing_year').after('<div class="text-danger">Please select a manufacturing year</div>');
                } else {
                    $('#manufacturing_year').next('.text-danger').remove();
                }

                // Check if car type is selected
                if (!$('#car_type').val()) {
                    isValid = false;
                    errorMessages.push('Please select a car type');
                    $('#car_type_error').removeClass('d-none');
                } else {
                    $('#car_type_error').addClass('d-none');
                }

                // Log form values for debugging
                console.log('Form values before submission:');
                console.log('Brand ID:', $('#category_id').val());
                console.log('Model ID:', $('#model_id').val());
                console.log('Manufacturing Year:', $('#manufacturing_year').val());
                console.log('Car Type:', $('#car_type').val());
                console.log('Color:', $('#color').val());
                console.log('Plate Number:', $('#plate_number').val());
                console.log('Chassis Number:', $('#chassis_number').val());

                if (!isValid) {
                    // Show error message
                    toastr.error('Please fill in all required fields');
                    return false;
                }

                var brandNotInDb = $('#brand_not_in_db').val() === '1';
                var modelNotInDb = $('#model_not_in_db').val() === '1';
                var aiData = window.aiAnalysisData || {}; // Store AI data globally

                console.log('Form submission check - Brand not in DB:', brandNotInDb);
                console.log('Form submission check - Model not in DB:', modelNotInDb);

                // Function to submit the form via AJAX
                function submitForm(updateDatabase) {
                    // Get values from both the select elements and the data attributes
                    var brandId = $('#category_id').val() || $('#brand-dropdown').attr('data-selected-value');
                    var modelId = $('#model_id').val() || $('#model-dropdown').attr('data-selected-value');

                    // Update the form fields with values from data attributes if needed
                    if (!$('#category_id').val() && $('#brand-dropdown').attr('data-selected-value')) {
                        $('#category_id').val($('#brand-dropdown').attr('data-selected-value'));
                        console.log('Updated category_id from data attribute before submission:', $('#category_id').val());
                    }

                    if (!$('#model_id').val() && $('#model-dropdown').attr('data-selected-value')) {
                        $('#model_id').val($('#model-dropdown').attr('data-selected-value'));
                        console.log('Updated model_id from data attribute before submission:', $('#model_id').val());
                    }

                    // Double-check that required fields are set before submission
                    if (!brandId || !modelId || !$('#manufacturing_year').val() || !$('#car_type').val()) {
                        console.error('Required fields are missing before form submission');
                        console.log('Brand ID (select):', $('#category_id').val());
                        console.log('Brand ID (data-attr):', $('#brand-dropdown').attr('data-selected-value'));
                        console.log('Model ID (select):', $('#model_id').val());
                        console.log('Model ID (data-attr):', $('#model-dropdown').attr('data-selected-value'));
                        console.log('Manufacturing Year:', $('#manufacturing_year').val());
                        console.log('Car Type:', $('#car_type').val());

                        toastr.error('Please fill in all required fields');
                        return false;
                    }

                    var formData = $('#addVehicleForm').serialize();

                    // Add update_vehicle_database flag if needed
                    if (updateDatabase) {
                        formData += '&update_vehicle_database=1';
                    }

                    $.ajax({
                        url: "{{ route('vehicles.store') }}",
                        type: "POST",
                        data: formData,
                        dataType: "json",
                        beforeSend: function() {
                            // Show loading indicator
                            $('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
                        },
                        success: function(response) {
                            if (response.message) {
                                toastr.success(response.message);

                                // If a new contact device was created and buy_sell modal is open, add it to the dropdown
                                if (response.contact_device && response.contact_device.id && $('#buy_sell_model_id').length) {
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

                                // Close the modal after successful submission
                                setTimeout(function() {
                                    // Properly close the modal using Bootstrap's modal method
                                    $('.create_models').modal('hide');
                                    // Remove any modal-backdrop elements that might be left
                                    $('.modal-backdrop').remove();

                                    // If a refresh function exists, call it
                                    if (typeof refreshVehiclesList === 'function') {
                                        refreshVehiclesList();
                                    }
                                    // The parent page will handle UI updates via the hidden.bs.modal event
                                }, 1500);
                            }
                        },
                        error: function(xhr) {
                            var errorMessage = 'Error saving vehicle data';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                                // Format validation errors
                                errorMessage = 'Validation errors:<br>';
                                $.each(xhr.responseJSON.errors, function(field, errors) {
                                    errorMessage += field + ': ' + errors.join(', ') + '<br>';
                                });
                            }
                            toastr.error(errorMessage);

                            // Reset button state
                            $('button[type="submit"]').prop('disabled', false).html('Save');

                            // Allow resubmission after failure
                            $('#addVehicleForm').data('submitting', false);
                        },
                        complete: function() {
                            // Reset button state
                            $('button[type="submit"]').prop('disabled', false).html('Save');
                        }
                    });
                }

                // Check if confirmation is needed
                if (brandNotInDb || modelNotInDb) {
                    var message = '';
                    var brandName = $('#category_id option:selected').text() || aiData.brand_name || 'Unknown';
                    var modelName = $('#model_id option:selected').text() || aiData.model_name || 'Unknown';

                    if (confirm(message)) {
                        $('#addVehicleForm').data('submitting', true);
                        submitForm(true); // Submit with database update flag
                    }
                } else {
                    $('#addVehicleForm').data('submitting', true);
                    submitForm(false); // Submit without database update flag
                }
            });

        // Add event handler for brand dropdown change
        $('#category_id').on('change', function() {
            console.log('Category ID change event triggered.'); // Added console log
            var brandId = $(this).val(); // Get the selected brand ID
            console.log('Brand dropdown changed to:', brandId);

            // Update the custom brand dropdown display
            var selectedText = $(this).find('option:selected').text();
            $('#brand-display').text(selectedText);

            // Update selected state in custom dropdown
            $('#brand-options .custom-dropdown-option').removeClass('selected');
            $('#brand-options .custom-dropdown-option[data-value="' + brandId + '"]').addClass('selected');

            // Clear model dropdown and display when brand changes
            $('#model_id').empty().append('<option value="">@lang("car.selectmodel")</option>');
            $('#model-options').empty().append('<div class="custom-dropdown-option" data-value="">@lang("car.selectmodel")</div>');
            $('#model-display').text('@lang("car.selectmodel")');


            if (!brandId) {
                return;
            }

            // Fetch models for the selected brand
            refreshModelDropdown(brandId); // Use the dedicated function
        });

        // Populate manufacturing years
        var currentYear = new Date().getFullYear();
        for (var year = currentYear; year >= 1950; year--) {
            $('#manufacturing_year').append(new Option(year, year));
        }

        // Chassis number lookup
        $('#lookup_chassis').on('click', function() {
            var chassisNumber = $('#chassis_number').val();

            if (!chassisNumber) {
                toastr.error('Please enter a chassis number');
                return;
            }

            // Reset form fields before new lookup
            $('#category_id').val('');
            $('#model_id').empty().append('<option value="">@lang("car.selectmodel")</option>');
            $('#manufacturing_year').val('');
            $('#car_type').val('');
            $('#color').val('');

            // Show loading indicator with better styling
            var $btn = $(this);
            var originalContent = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Searching...');
            $btn.addClass('disabled').css('width', $btn.outerWidth() + 'px');

            // Update the AJAX URL in the lookup_chassis click handler
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
                        var aiData = response.data.ai_analysis;

                        // Set the brand (category)
                        if (response.data.brand_id) {
                            // Force the select element to have the selected value
                            $('#category_id').find('option').prop('selected', false);
                            $('#category_id').find('option[value="' + response.data.brand_id + '"]').prop('selected', true);
                            $('#category_id').val(response.data.brand_id);

                            console.log('Setting brand_id to:', response.data.brand_id);
                            console.log('Brand dropdown value after setting:', $('#category_id').val());

                            // Update the custom brand dropdown display
                            var selectedText = $('#category_id option:selected').text();
                            $('#brand-display').text(selectedText);

                            // Update selected state in custom dropdown
                            $('#brand-options .custom-dropdown-option').removeClass('selected');
                            $('#brand-options .custom-dropdown-option[data-value="' + response.data.brand_id + '"]').addClass('selected');

                            // Trigger change event after ensuring the value is set
                            $('#category_id').trigger('change');
                            $('#category_id').siblings('small.text-danger').remove();

                            // Wait for models to load then set the model
                            if (response.data.model_id) {
                                // First, ensure models are loaded for this brand
                                console.log('Loading models for brand ID:', response.data.brand_id);

                                // Load models first, then set the selected model
                                $.ajax({
                                    url: "/bookings/get-models/" + response.data.brand_id,
                                    type: "GET",
                                    dataType: "json",
                                    success: function(modelsResponse) {
                                        console.log('Models loaded:', modelsResponse);

                                        // Clear and rebuild the select dropdown
                                        var $dropdown = $('#model_id');
                                        $dropdown.empty();
                                        $dropdown.append('<option value="">@lang("car.selectmodel")</option>');

                                        // Clear and rebuild the custom dropdown options
                                        var $customOptions = $('#model-options');
                                        $customOptions.empty();
                                        $customOptions.append('<div class="custom-dropdown-option" data-value="">@lang("car.selectmodel")</div>');

                                        // Add all models from the response
                                        $.each(modelsResponse, function(index, model) {
                                            var isSelected = (model.id == response.data.model_id);

                                            // Add to the original select
                                            $dropdown.append('<option value="' + model.id + '"' +
                                                (isSelected ? ' selected' : '') + '>' + model.name + '</option>');

                                            // Add to the custom dropdown
                                            $customOptions.append('<div class="custom-dropdown-option' +
                                                (isSelected ? ' selected' : '') + '" data-value="' +
                                                model.id + '">' + model.name + '</div>');
                                        });

                                        // Now set the selected model
                                        setTimeout(function() {
                                            if ($dropdown.find('option[value="' + response.data.model_id + '"]').length > 0) {
                                                // Force the select element to have the selected value
                                                $dropdown.find('option').prop('selected', false);
                                                $dropdown.find('option[value="' + response.data.model_id + '"]').prop('selected', true);
                                                $dropdown.val(response.data.model_id);

                                                console.log('Setting model_id to:', response.data.model_id);
                                                console.log('Model dropdown value after setting:', $dropdown.val());

                                                // Update the custom dropdown display
                                                var selectedText = $dropdown.find('option:selected').text();
                                                $('#model-display').text(selectedText);

                                                // Mark as selected in the custom dropdown
                                                $('#model-options .custom-dropdown-option').removeClass('selected');
                                                $('#model-options .custom-dropdown-option[data-value="' + response.data.model_id + '"]').addClass('selected');

                                                // Trigger change event to ensure any listeners are notified
                                                $dropdown.trigger('change');

                                                // Remove any error messages
                                                $('#model_id').siblings('small.text-danger').remove();
                                            } else {
                                                console.warn('Model ID ' + response.data.model_id + ' not found in dropdown options');
                                                $('#model-display').text('@lang("car.selectmodel")');
                                            }
                                        }, 100);
                                    },
                                    error: function(xhr) {
                                        console.error('Error loading models for brand ' + response.data.brand_id + ':', xhr);
                                        // Add "No matched model" message if model not found
                                        if (!$('#model_id').siblings('small.text-danger').length) {
                                            $('#model_id').after(
                                                '<small class="text-danger d-block mt-1">{{ __("car.no_matched_model") }}</small>'
                                            );
                                            // Make the message disappear after 5 seconds
                                            setTimeout(function() {
                                                $('#model_id').siblings('small.text-danger')
                                                    .fadeOut(function() {
                                                        $(this).remove();
                                                    });
                                            }, 5000);
                                        }
                                    }
                                });
                            } else {
                                // Add "No matched model" message if model not found
                                if (!$('#model_id').siblings('small.text-danger').length) {
                                    $('#model_id').after(
                                        '<small class="text-danger d-block mt-1">{{ __("car.no_matched_model") }}</small>'
                                    );
                                    // Make the message disappear after 5 seconds
                                    setTimeout(function() {
                                        $('#model_id').siblings('small.text-danger')
                                            .fadeOut(function() {
                                                $(this).remove();
                                            });
                                    }, 5000);
                                }
                            }
                        } else {
                            // Add "No matched brand" message if brand not found
                            if (!$('#category_id').siblings('small.text-danger').length) {
                                $('#category_id').after(
                                    '<small class="text-danger d-block mt-1">{{ __("car.no_matched_brand") }}</small>'
                                    );
                                // Make the message disappear after 5 seconds
                                setTimeout(function() {
                                    $('#category_id').siblings('small.text-danger')
                                        .fadeOut(function() {
                                            $(this).remove();
                                        });
                                }, 5000);
                            }

                            // Add "No matched model" message if brand not found (model can't be found either)
                            if (!$('#model_id').siblings('small.text-danger').length) {
                                $('#model_id').after(
                                    '<small class="text-danger d-block mt-1">{{ __("car.no_matched_model") }}</small>'
                                    );
                                // Make the message disappear after 5 seconds
                                setTimeout(function() {
                                    $('#model_id').siblings('small.text-danger')
                                        .fadeOut(function() {
                                            $(this).remove();
                                        });
                                }, 5000);
                            }
                        }

                        // Set manufacturing year
                        if (response.data.year) {
                            var year = response.data.year.toString();
                            if ($('#manufacturing_year option[value="' + year + '"]')
                                .length === 0) {
                                $('#manufacturing_year').append(new Option(year, year,
                                    false, true));
                            }
                            $('#manufacturing_year').val(year);
                        }

                        // Set color if available
                        if (response.data.color) {
                            $('#color').val(response.data.color);
                        }

                        // Set vehicle type based on AI analysis
                        if (aiData && aiData['Vehicle Type/Category']) {
                            var vehicleType = aiData['Vehicle Type/Category'].toLowerCase();
                            var typeMapping = {
                                'passenger': 'ملاكي',
                                'sedan': 'ملاكي',
                                'commercial': 'اجرة',
                                'taxi': 'اجرة',
                                'heavy': 'نقل ثقيل',
                                'truck': 'نقل ثقيل',
                                'light': 'نقل خفيف',
                                'pickup': 'نقل خفيف'
                            };

                            for (var key in typeMapping) {
                                if (vehicleType.includes(key)) {
                                    $('#car_type').val(typeMapping[key]);
                                    break;
                                }
                            }
                        }

                        toastr.success('Vehicle information retrieved successfully');
                    } else {
                        toastr.warning(response.message ||
                            'Could not find complete vehicle information');
                    }
                },
                error: function(xhr) {
                    var errorMessage = 'Error looking up chassis number';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                },
                complete: function() {
                    // Reset button state with original content
                    $btn.html(originalContent);
                    $btn.removeClass('disabled').css('width', '');
                }
            });
        });
    });
</script>
