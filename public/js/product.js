//This file contains all functions used products tab

$(document).ready(function() {
    // Handle generate_sku button click
    $(document).on('click', '#generate_sku', function() {
        var sku = $('#sku_input').val();
        var productName = $('#product_name_input').val();
        // console.log( sku + productName );

        if (!sku) {
            toastr.error(LANG.sku_required || 'Please enter a SKU');
            return;
        }
        // Show loading indicator
        $(this).prop('disabled', true);
        $(this).html('<i class="fa fa-spinner fa-spin"></i>');

        // Make AJAX call to get product details
        $.ajax({
            url: route_get_product_details,
            type: 'GET',
            data: { sku: sku , product_name: productName },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
               
                    // Set product name if available
                    if (response.product_name && !productName ) {
                        $('#product_name_input').val(response.product_name);
                    }

                    // Clear existing compatibility rows
                    $('#product_compatibility_table tbody').empty();

                    // Add compatibility data to table
                    if (response.model_years && response.model_years.length > 0) {
                        // Handle the model_years format which has model_id and brand_category_id
                        var rowCount = 0;

                        console.log("Using model_years data:", response.model_years);

                        response.model_years.forEach(function(item) {
                            // Create new row with compatibility data
                            var newRow = `
                                <tr>
                                    <td>
                                        <input type="text" class="form-control" readonly value="${item.model_name}">
                                        <input type="hidden" name="compatibility[${rowCount}][model_id]" value="${item.model_id || ''}" class="model-id-input">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" readonly value="${item.make || ''}">
                                        <input type="hidden" name="compatibility[${rowCount}][brand_category_id]" value="${item.brand_category_id || ''}" class="brand-category-id-input">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" readonly value="${item.from_year}">
                                        <input type="hidden" name="compatibility[${rowCount}][from_year]" value="${item.from_year}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" readonly value="${item.to_year}">
                                        <input type="hidden" name="compatibility[${rowCount}][to_year]" value="${item.to_year}">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-xs edit-compatibility-row"
                                            data-index="${rowCount}"
                                            data-model-id="${item.model_id || ''}"
                                            data-brand-category-id="${item.brand_category_id || ''}">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-xs remove-compatibility-row"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            `;

                            $('#product_compatibility_table tbody').append(newRow);
                            rowCount++;
                        });

                        toastr.success(LANG.compatibility_data_loaded || 'Compatibility data loaded successfully');
                    } else if (response.data && response.data.vehicle_compatibility) {
                        var rowCount = 0;
                        var vehicleCompatibility = response.data.vehicle_compatibility;

                        console.log("Using vehicle_compatibility data:", vehicleCompatibility);

                        // Iterate through each brand in the vehicle compatibility data
                        Object.keys(vehicleCompatibility).forEach(function(brand) {
                            // Get models for this brand
                            var models = vehicleCompatibility[brand];

                            // Iterate through each model
                            models.forEach(function(modelData) {
                                var modelName = modelData.model;
                                var yearFrom = modelData.year_range ? modelData.year_range.from : '';
                                var yearTo = modelData.year_range ? modelData.year_range.to : '';

                                // Create new row with compatibility data
                                var newRow = `
                                    <tr>
                                        <td>
                                            <input type="text" class="form-control" readonly value="${brand} ${modelName}">
                                            <input type="hidden" name="compatibility[${rowCount}][model_id]" value="${modelData.model_id || ''}" class="model-id-input">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" readonly value="${brand}">
                                            <input type="hidden" name="compatibility[${rowCount}][brand_category_id]" value="${modelData.brand_category_id || ''}" class="brand-category-id-input">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" readonly value="${yearFrom}">
                                            <input type="hidden" name="compatibility[${rowCount}][from_year]" value="${yearFrom}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" readonly value="${yearTo}">
                                            <input type="hidden" name="compatibility[${rowCount}][to_year]" value="${yearTo}">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-xs edit-compatibility-row"
                                                data-index="${rowCount}"
                                                data-model-id="${modelData.model_id || ''}"
                                                data-brand-category-id="${modelData.brand_category_id || ''}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-xs remove-compatibility-row"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                `;

                                $('#product_compatibility_table tbody').append(newRow);
                                rowCount++;
                            });
                        });

                        toastr.success(LANG.compatibility_data_loaded || 'Compatibility data loaded successfully');
                    } else {
                        toastr.info(LANG.no_compatibility_data || 'No compatibility data found for this SKU');
                    }
                } else {
                    toastr.error(response.error || LANG.something_went_wrong);
                }
            },
            error: function(xhr, status, error) {
                toastr.error(LANG.something_went_wrong || 'Something went wrong');
                console.error(error);
            },
            complete: function() {
                // Reset button state
                $('#generate_sku').prop('disabled', false);
                $('#generate_sku').html('<i class="fas fa-robot"></i>');
            }
        });
    });

    $(document).on('ifChecked', 'input#enable_stock', function() {
        $('div#alert_quantity_div').show();
        $('div#quick_product_opening_stock_div').show();

        //Enable expiry selection
        if ($('#expiry_period_type').length) {
            $('#expiry_period_type').removeAttr('disabled');
        }

        if ($('#opening_stock_button').length) {
            $('#opening_stock_button').removeAttr('disabled');
        }
    });
    $(document).on('ifUnchecked', 'input#enable_stock', function() {
        $('div#alert_quantity_div').hide();
        $('div#quick_product_opening_stock_div').hide();
        $('input#alert_quantity').val(0);

        //Disable expiry selection
        if ($('#expiry_period_type').length) {
            $('#expiry_period_type')
                .val('')
                .change();
            $('#expiry_period_type').attr('disabled', true);
        }
        if ($('#opening_stock_button').length) {
            $('#opening_stock_button').attr('disabled', true);
        }
    });

    //Start For product type single

    //If purchase price exc tax is changed
    $(document).on('change', 'input#single_dpp', function(e) {
        var purchase_exc_tax = __read_number($('input#single_dpp'));
        purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var purchase_inc_tax = __add_percent(purchase_exc_tax, tax_rate);
        __write_number($('input#single_dpp_inc_tax'), purchase_inc_tax);

        var profit_percent = __read_number($('#profit_percent'));
        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number($('input#single_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
    });

    //If tax rate is changed
    $(document).on('change', 'select#tax', function() {
        if ($('select#type').val() == 'single') {
            var purchase_exc_tax = __read_number($('input#single_dpp'));
            purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

            var tax_rate = $('select#tax')
                .find(':selected')
                .data('rate');
            tax_rate = tax_rate == undefined ? 0 : tax_rate;

            var purchase_inc_tax = __add_percent(purchase_exc_tax, tax_rate);
            __write_number($('input#single_dpp_inc_tax'), purchase_inc_tax);

            var selling_price = __read_number($('input#single_dsp'));
            var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
            __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
        }
    });

    //If purchase price inc tax is changed
    $(document).on('change', 'input#single_dpp_inc_tax', function(e) {
        var purchase_inc_tax = __read_number($('input#single_dpp_inc_tax'));
        purchase_inc_tax = purchase_inc_tax == undefined ? 0 : purchase_inc_tax;

        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var purchase_exc_tax = __get_principle(purchase_inc_tax, tax_rate);
        __write_number($('input#single_dpp'), purchase_exc_tax);
        $('input#single_dpp').change();

        var profit_percent = __read_number($('#profit_percent'));
        profit_percent = profit_percent == undefined ? 0 : profit_percent;
        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number($('input#single_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
    });

    $(document).on('change', 'input#profit_percent', function(e) {
        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var purchase_inc_tax = __read_number($('input#single_dpp_inc_tax'));
        purchase_inc_tax = purchase_inc_tax == undefined ? 0 : purchase_inc_tax;

        var purchase_exc_tax = __read_number($('input#single_dpp'));
        purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

        var profit_percent = __read_number($('input#profit_percent'));
        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number($('input#single_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
    });

    $(document).on('change', 'input#single_dsp', function(e) {
        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var selling_price = __read_number($('input#single_dsp'));
        var purchase_exc_tax = __read_number($('input#single_dpp'));
        var profit_percent = __read_number($('input#profit_percent'));

        //if purchase price not set
        if (purchase_exc_tax == 0) {
            profit_percent = 0;
        } else {
            profit_percent = __get_rate(purchase_exc_tax, selling_price);
        }

        __write_number($('input#profit_percent'), profit_percent);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
    });

    $(document).on('change', 'input#single_dsp_inc_tax', function(e) {
        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;
        var selling_price_inc_tax = __read_number($('input#single_dsp_inc_tax'));

        var selling_price = __get_principle(selling_price_inc_tax, tax_rate);
        __write_number($('input#single_dsp'), selling_price);
        var purchase_exc_tax = __read_number($('input#single_dpp'));
        var profit_percent = __read_number($('input#profit_percent'));

        //if purchase price not set
        if (purchase_exc_tax == 0) {
            profit_percent = 0;
        } else {
            profit_percent = __get_rate(purchase_exc_tax, selling_price);
        }

        __write_number($('input#profit_percent'), profit_percent);
    });

    if ($('#product_add_form').length) {
        $('form#product_add_form').validate({
            rules: {
                sku: {
                    remote: {
                        url: '/products/check_product_sku',
                        type: 'post',
                        data: {
                            sku: function() {
                                // Use the actual SKU input field id used in the forms
                                // In edit.blade.php the SKU field has id="sku_input"
                                // Fall back to #sku if present (for backward compatibility)
                                var $skuInput = $('#sku_input');
                                if ($skuInput.length) {
                                    return $skuInput.val();
                                }
                                return $('#sku').val();
                            },
                            product_id: function() {
                                if ($('#product_id').length > 0) {
                                    return $('#product_id').val();
                                } else {
                                    return '';
                                }
                            },
                        },
                    },
                },
                expiry_period: {
                    required: {
                        depends: function(element) {
                            return (
                                $('#expiry_period_type')
                                    .val()
                                    .trim() != ''
                            );
                        },
                    },
                },
            },
            messages: {
                sku: {
                    remote: LANG.sku_already_exists,
                },
            },
        });
    }

    $(document).on('click', '.submit_product_form', function(e) {
        e.preventDefault();

        var is_valid_product_form = true;

        var variation_skus = [];

        var submit_type  = $(this).attr('value');
        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;
        var tax_type = $('#tax_type').val();

        // Ensure model_id and brand_category_id are properly set
        $('#product_compatibility_table tbody tr').each(function(index) {
            // Get the values from the data attributes if they exist
            var $row = $(this);
            var $editButton = $row.find('.edit-compatibility-row');
            var modelId = $editButton.attr('data-model-id') || $editButton.data('model-id');
            var brandCategoryId = $editButton.attr('data-brand-category-id') || $editButton.data('brand-category-id');

            console.log("Row " + index + " model_id:", modelId, "brand_category_id:", brandCategoryId);

            // Always update the hidden inputs with the latest values
            var $modelIdInput = $row.find('input[name^="compatibility["][name$="][model_id]"]');
            var $brandCategoryIdInput = $row.find('input[name^="compatibility["][name$="][brand_category_id]"]');

            $modelIdInput.val(modelId || '');
            $brandCategoryIdInput.val(brandCategoryId || '');

            console.log("Updated inputs - model_id:", $modelIdInput.val(), "brand_category_id:", $brandCategoryIdInput.val());
        });

        $('#product_form_part').find('.input_sub_sku').each( function(){
            var element = $(this);
            var row_variation_id = '';
            if ($(this).closest('tr').find('.row_variation_id')) {
                row_variation_id = $(this).closest('tr').find('.row_variation_id').val();
            }

            variation_skus.push({sku: element.val(), variation_id: row_variation_id});

        });

        // Keep single product exc/inc selling prices synchronized before submit.
        if ($('#type').val() == 'single') {
            var $singleDsp = $('input#single_dsp');
            var $singleDspIncTax = $('input#single_dsp_inc_tax');
            var singleDsp = __read_number($singleDsp);
            var singleDspIncTax = __read_number($singleDspIncTax);

            if (tax_type == 'inclusive') {
                if (singleDspIncTax != undefined) {
                    __write_number($singleDsp, __get_principle(singleDspIncTax || 0, tax_rate));
                }
            } else {
                if (singleDsp != undefined) {
                    __write_number($singleDspIncTax, __add_percent(singleDsp || 0, tax_rate));
                }
            }
        }

        // Keep variable product exc/inc selling prices synchronized before submit.
        $('#product_form_part').find('tr').each(function() {
            var $row = $(this);
            var $variableDsp = $row.find('input.variable_dsp');
            var $variableDspIncTax = $row.find('input.variable_dsp_inc_tax');

            if ($variableDsp.length && $variableDspIncTax.length) {
                var variableDsp = __read_number($variableDsp);
                var variableDspIncTax = __read_number($variableDspIncTax);

                if (tax_type == 'inclusive') {
                    __write_number($variableDsp, __get_principle(variableDspIncTax || 0, tax_rate));
                } else {
                    __write_number($variableDspIncTax, __add_percent(variableDsp || 0, tax_rate));
                }
            }
        });

        // Sync single product price fields into hidden inputs so they are
        // always included in the request, even if the visible inputs are
        // not part of the main form due to HTML quirks.
        if ($('#type').val() == 'single') {
            var $formPart = $('#product_form_part');

            var single_variation_id = $formPart.find('input[name="single_variation_id"]').val() || '';
            var single_dpp = $formPart.find('input#single_dpp').val() || '';
            var single_dpp_inc_tax = $formPart.find('input#single_dpp_inc_tax').val() || '';
            var profit_percent = $formPart.find('input#profit_percent').val() || '';
            var single_dsp = $formPart.find('input#single_dsp').val() || '';
            var single_dsp_inc_tax = $formPart.find('input#single_dsp_inc_tax').val() || '';

            $('#single_variation_id_hidden').val(single_variation_id);
            $('#single_dpp_hidden').val(single_dpp);
            $('#single_dpp_inc_tax_hidden').val(single_dpp_inc_tax);
            $('#profit_percent_hidden').val(profit_percent);
            $('#single_dsp_hidden').val(single_dsp);
            $('#single_dsp_inc_tax_hidden').val(single_dsp_inc_tax);
        }

        if (variation_skus.length > 0) {
            $.ajax({
                method: 'post',
                url: '/products/validate_variation_skus',
                data: { skus: variation_skus},
                success: function(result) {
                    if (result.success == true) {
                        $('#submit_type').val(submit_type);
                        if ($('form#product_add_form').valid()) {
                            $('form#product_add_form').submit();
                        }
                    } else {
                        toastr.error(__translate('skus_already_exists', {sku: result.sku}));
                        return false;
                    }
                },
            });
        } else {
            $('#submit_type').val(submit_type);
            if ($('form#product_add_form').valid()) {
                $('form#product_add_form').submit();
            }
        }

    });
    
    // Load the correct product type form (single / variable / combo)
    // on create/edit product pages. This was previously in app.js,
    // but app.js is not loaded on the edit product page, so we move it here.

    function show_product_type_form() {

        //Disable Stock management & Woocommmerce sync if type combo
        if($('#type').val() == 'combo'){
            $('#enable_stock').iCheck('uncheck');
            $('input[name="woocommerce_disable_sync"]').iCheck('check');
        }

        var action = $('#type').attr('data-action');
        var product_id = $('#type').attr('data-product_id');

        console.log('show_product_type_form -> type:', $('#type').val(), 'action:', action, 'product_id:', product_id);

        var token = $('meta[name="csrf-token"]').attr('content');
        var headers = token ? { 'X-CSRF-TOKEN': token } : {};
        if (!token) {
            console.error('CSRF token missing');
        }

        $.ajax({
            method: 'POST',
            url: '/products/product_form_part',
            dataType: 'html',
            data: { type: $('#type').val(), product_id: product_id, action: action },
            headers: headers,
            success: function(result) {
                console.log('show_product_type_form AJAX success, result length:', result ? result.length : 0);
                if (result) {
                    $('#product_form_part').html(result);
                    toggle_dsp_input();
                }
            },
            error: function(xhr, status, error) {
                console.error('show_product_type_form AJAX error:', status, error);
            }
        });
    }

    // On non-create product forms (edit / duplicate), load the
    // appropriate product type partial on page load.
    if ($('.product_form').length && !$('.product_form').hasClass('create')) {
        show_product_type_form();
    }

    // When the product type changes, reload the relevant form part.
    $('#type').change(function() {
        show_product_type_form();
    });
    //End for product type single

    //Start for product type Variable
    //If purchase price exc tax is changed
    $(document).on('change', 'input.variable_dpp', function(e) {
        var tr_obj = $(this).closest('tr');

        var purchase_exc_tax = __read_number($(this));
        purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var purchase_inc_tax = __add_percent(purchase_exc_tax, tax_rate);
        __write_number(tr_obj.find('input.variable_dpp_inc_tax'), purchase_inc_tax);

        var profit_percent = __read_number(tr_obj.find('input.variable_profit_percent'));
        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number(tr_obj.find('input.variable_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number(tr_obj.find('input.variable_dsp_inc_tax'), selling_price_inc_tax);
    });

    //If purchase price inc tax is changed
    $(document).on('change', 'input.variable_dpp_inc_tax', function(e) {
        var tr_obj = $(this).closest('tr');

        var purchase_inc_tax = __read_number($(this));
        purchase_inc_tax = purchase_inc_tax == undefined ? 0 : purchase_inc_tax;

        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var purchase_exc_tax = __get_principle(purchase_inc_tax, tax_rate);
        __write_number(tr_obj.find('input.variable_dpp'), purchase_exc_tax);

        var profit_percent = __read_number(tr_obj.find('input.variable_profit_percent'));
        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number(tr_obj.find('input.variable_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number(tr_obj.find('input.variable_dsp_inc_tax'), selling_price_inc_tax);
    });

    $(document).on('change', 'input.variable_profit_percent', function(e) {
        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var tr_obj = $(this).closest('tr');
        var profit_percent = __read_number($(this));

        var purchase_exc_tax = __read_number(tr_obj.find('input.variable_dpp'));
        purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number(tr_obj.find('input.variable_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number(tr_obj.find('input.variable_dsp_inc_tax'), selling_price_inc_tax);
    });

    $(document).on('change', 'input.variable_dsp', function(e) {
        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var tr_obj = $(this).closest('tr');
        var selling_price = __read_number($(this));
        var purchase_exc_tax = __read_number(tr_obj.find('input.variable_dpp'));

        var profit_percent = __read_number(tr_obj.find('input.variable_profit_percent'));

        //if purchase price not set
        if (purchase_exc_tax == 0) {
            profit_percent = 0;
        } else {
            profit_percent = __get_rate(purchase_exc_tax, selling_price);
        }

        __write_number(tr_obj.find('input.variable_profit_percent'), profit_percent);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number(tr_obj.find('input.variable_dsp_inc_tax'), selling_price_inc_tax);
    });
    $(document).on('change', 'input.variable_dsp_inc_tax', function(e) {
        var tr_obj = $(this).closest('tr');
        var selling_price_inc_tax = __read_number($(this));

        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var selling_price = __get_principle(selling_price_inc_tax, tax_rate);
        __write_number(tr_obj.find('input.variable_dsp'), selling_price);

        var purchase_exc_tax = __read_number(tr_obj.find('input.variable_dpp'));
        var profit_percent = __read_number(tr_obj.find('input.variable_profit_percent'));
        //if purchase price not set
        if (purchase_exc_tax == 0) {
            profit_percent = 0;
        } else {
            profit_percent = __get_rate(purchase_exc_tax, selling_price);
        }

        __write_number(tr_obj.find('input.variable_profit_percent'), profit_percent);
    });

    $(document).on('click', '.add_variation_value_row', function() {
        var variation_row_index = $(this)
            .closest('.variation_row')
            .find('.row_index')
            .val();
        var variation_value_row_index = $(this)
            .closest('table')
            .find('tr:last .variation_row_index')
            .val();

        if (
            $(this)
                .closest('.variation_row')
                .find('.row_edit').length >= 1
        ) {
            var row_type = 'edit';
        } else {
            var row_type = 'add';
        }

        var table = $(this).closest('table');

        $.ajax({
            method: 'GET',
            url: '/products/get_variation_value_row',
            data: {
                variation_row_index: variation_row_index,
                value_index: variation_value_row_index,
                row_type: row_type,
            },
            dataType: 'html',
            success: function(result) {
                if (result) {
                    table.append(result);
                    toggle_dsp_input();
                }
            },
        });
    });
    $(document).on('change', '.variation_template_values', function() {
        var tr_obj = $(this).closest('tr');
        var val = $(this).val();
        tr_obj.find('.variation_value_row').each(function(){
            if(val.includes($(this).attr('data-variation_value_id'))) {
                $(this).removeClass('hide');
                $(this).find('.is_variation_value_hidden').val(0);
            } else {
                $(this).addClass('hide');
                $(this).find('.is_variation_value_hidden').val(1);
            }
        })
    });
    $(document).on('change', '.variation_template', function() {
        tr_obj = $(this).closest('tr');

        if ($(this).val() !== '') {
            tr_obj.find('input.variation_name').val(
                $(this)
                    .find('option:selected')
                    .text()
            );

            var template_id = $(this).val();
            var row_index = $(this)
                .closest('tr')
                .find('.row_index')
                .val();
            $.ajax({
                method: 'POST',
                url: '/products/get_variation_template',
                dataType: 'json',
                data: { template_id: template_id, row_index: row_index },
                success: function(result) {
                    if (result) {
                        if(result.values.length > 0) {
                            tr_obj.find('.variation_template_values').select2();
                            tr_obj.find('.variation_template_values').empty();
                            tr_obj.find('.variation_template_values').select2({data: result.values, closeOnSelect: false});
                            tr_obj.find('.variation_template_values_div').removeClass('hide');
                            tr_obj.find('.variation_template_values').select2('open');
                        } else {
                            tr_obj.find('.variation_template_values_div').addClass('hide');
                        }
                        tr_obj
                            .find('table.variation_value_table')
                            .find('tbody')
                            .html(result.html);

                        toggle_dsp_input();
                    }
                },
            });
        }
    });

    $(document).on('click','.delete_complete_row', function(){
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                .closest('.variation_row')
                .remove();
            }
        });
    });

    $(document).on('click', '.remove_variation_value_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var count = $(this)
                    .closest('table')
                    .find('.remove_variation_value_row').length;
                if (count === 1) {
                    $(this)
                        .closest('.variation_row')
                        .remove();
                } else {
                    $(this)
                        .closest('tr')
                        .remove();
                }
            }
        });
    });

    //If tax rate is changed
    $(document).on('change', 'select#tax', function() {
        if ($('select#type').val() == 'variable') {
            var tax_rate = $('select#tax')
                .find(':selected')
                .data('rate');
            tax_rate = tax_rate == undefined ? 0 : tax_rate;

            $('table.variation_value_table > tbody').each(function() {
                $(this)
                    .find('tr')
                    .each(function() {
                        var purchase_exc_tax = __read_number($(this).find('input.variable_dpp'));
                        purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

                        var purchase_inc_tax = __add_percent(purchase_exc_tax, tax_rate);
                        __write_number(
                            $(this).find('input.variable_dpp_inc_tax'),
                            purchase_inc_tax
                        );

                        var selling_price = __read_number($(this).find('input.variable_dsp'));
                        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
                        __write_number(
                            $(this).find('input.variable_dsp_inc_tax'),
                            selling_price_inc_tax
                        );
                    });
            });
        }
    });
    //End for product type Variable
    $(document).on('change', '#tax_type', function(e) {
        toggle_dsp_input();
    });
    toggle_dsp_input();

    $(document).on('change', '#expiry_period_type', function(e) {
        if ($(this).val()) {
            $('input#expiry_period').prop('disabled', false);
        } else {
            $('input#expiry_period').val('');
            $('input#expiry_period').prop('disabled', true);
        }
    });

    $(document).on('click', 'a.view-product', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('href'),
            dataType: 'html',
            success: function(result) {
                $('#view_product_modal')
                    .html(result)
                    .modal('show');
                __currency_convert_recursively($('#view_product_modal'));
            },
        });
    });
    var img_fileinput_setting = {
        showUpload: false,
        showPreview: true,
        browseLabel: LANG.file_browse_label,
        removeLabel: LANG.remove,
        previewSettings: {
            image: { width: 'auto', height: 'auto', 'max-width': '100%', 'max-height': '100%' },
        },
    };
    $('#upload_image').fileinput(img_fileinput_setting);

    if ($('textarea#product_description').length > 0) {
        tinymce.init({
            selector: 'textarea#product_description',
            height:250
        });
    }
});

function toggle_dsp_input() {
    var tax_type = $('#tax_type').val();
    if (tax_type == 'inclusive') {
        $('.dsp_label').each(function() {
            $(this).text(LANG.inc_tax);
        });
        $('#single_dsp').removeClass('hide');
        $('#single_dsp_inc_tax').removeClass('hide');

        $('.add-product-price-table')
            .find('.variable_dsp_inc_tax')
            .each(function() {
                $(this).removeClass('hide');
            });
        $('.add-product-price-table')
            .find('.variable_dsp')
            .each(function() {
                $(this).removeClass('hide');
            });
    } else if (tax_type == 'exclusive') {
        $('.dsp_label').each(function() {
            $(this).text(LANG.exc_tax);
        });
        $('#single_dsp').removeClass('hide');
        $('#single_dsp_inc_tax').removeClass('hide');

        $('.add-product-price-table')
            .find('.variable_dsp_inc_tax')
            .each(function() {
                $(this).removeClass('hide');
            });
        $('.add-product-price-table')
            .find('.variable_dsp')
            .each(function() {
                $(this).removeClass('hide');
            });
    }
}

function get_product_details(rowData) {
    var div = $('<div/>')
        .addClass('loading')
        .text('Loading...');

    $.ajax({
        url: '/products/' + rowData.id,
        dataType: 'html',
        success: function(data) {
            div.html(data).removeClass('loading');
        },
    });

    return div;
}

//Quick add unit
$(document).on('submit', 'form#quick_add_unit_form', function(e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();

    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function(xhr) {
            __disable_submit_button(form.find('button[type="submit"]'));
        },
        success: function(result) {
            if (result.success == true) {
                var newOption = new Option(result.data.short_name, result.data.id, true, true);
                // Append it to the select
                $('#unit_id')
                    .append(newOption)
                    .trigger('change');
                $('div.view_modal').modal('hide');
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

//Quick add brand
$(document).on('submit', 'form#quick_add_brand_form', function(e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();

    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function(xhr) {
            __disable_submit_button(form.find('button[type="submit"]'));
        },
        success: function(result) {
            if (result.success == true) {
                var newOption = new Option(result.data.name, result.data.id, true, true);
                // Append it to the select
                $('#brand_id')
                    .append(newOption)
                    .trigger('change');
                $('div.view_modal').modal('hide');
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

$(document).on('click', 'button.apply-all', function(){
    var val = $(this).closest('.input-group').find('input').val();
    var target_class = $(this).data('target-class');
    $(this).closest('tbody').find('tr').each( function(){
        element =  $(this).find(target_class);
        element.val(val);
        element.change();
    });
});

// Cascading category selects for multi-level categories
// Note: sub-category AJAX fetch handled by app.js get_sub_categories()



// Wait for document ready to ensure Select2 is initialized
$(document).ready(function() {

    // Try binding to both the select element and select2 container
    $('#category_id').on('select2:select change', function() {
        var cat_id = $(this).val();
        console.log('category_id EVENT FIRED, cat_id:', cat_id);
        if (!cat_id) {
            $('#sub_category_id').html('<option value="">None</option>').trigger('change');
        }

        $('#sub_sub_category_id').html('<option value="">None</option>').trigger('change');
        $('#sub_sub_sub_category_id').html('<option value="">None</option>').trigger('change');
    });

    $('#sub_category_id').on('select2:select change', function(e) {
        console.log('sub_category_id EVENT FIRED');
        var cat_id = $(this).val();
        console.log('sub_category_id selected, cat_id:', cat_id);
        if (cat_id) {
            $.ajax({
                method: 'POST',
                url: '/products/get_sub_categories',
                data: { cat_id: cat_id },
                dataType: 'html',
                success: function(result) {
                    console.log('Sub-sub categories loaded:', result);
                    var $subSubSelect = $('#sub_sub_category_id');
                    $subSubSelect.html(result);
                    if ($subSubSelect.data('select2')) {
                        $subSubSelect.trigger('change.select2');
                    }
                    $subSubSelect.trigger('change');
                    // Clear deeper level
                    var $subSubSubSelect = $('#sub_sub_sub_category_id');
                    $subSubSubSelect.html('<option value="">None</option>');
                    if ($subSubSubSelect.data('select2')) {
                        $subSubSubSelect.trigger('change.select2');
                    }
                    $subSubSubSelect.trigger('change');
                }
            });
        } else {
            var $subSubSelect = $('#sub_sub_category_id');
            $subSubSelect.html('<option value="">None</option>');
            if ($subSubSelect.data('select2')) {
                $subSubSelect.trigger('change.select2');
            }
            $subSubSelect.trigger('change');
            var $subSubSubSelect = $('#sub_sub_sub_category_id');
            $subSubSubSelect.html('<option value="">None</option>');
            if ($subSubSubSelect.data('select2')) {
                $subSubSubSelect.trigger('change.select2');
            }
            $subSubSubSelect.trigger('change');
        }
    });

    $('#sub_sub_category_id').on('select2:select change', function(e) {
        console.log('sub_sub_category_id EVENT FIRED');
        var cat_id = $(this).val();
        console.log('sub_sub_category_id selected, cat_id:', cat_id);
        if (cat_id) {
            $.ajax({
                method: 'POST',
                url: '/products/get_sub_categories',
                data: { cat_id: cat_id },
                dataType: 'html',
                success: function(result) {
                    console.log('Sub-sub-sub categories loaded:', result);
                    var $subSubSubSelect = $('#sub_sub_sub_category_id');
                    $subSubSubSelect.html(result);
                    if ($subSubSubSelect.data('select2')) {
                        $subSubSubSelect.trigger('change.select2');
                    }
                    $subSubSubSelect.trigger('change');
                }
            });
        } else {
            var $subSubSubSelect = $('#sub_sub_sub_category_id');
            $subSubSubSelect.html('<option value="">None</option>');
            if ($subSubSubSelect.data('select2')) {
                $subSubSubSelect.trigger('change.select2');
            }
            $subSubSubSelect.trigger('change');
        }
    });
});
