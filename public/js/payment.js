$(document).ready(function() {
    $(document).on('click', '.add_payment_modal', function(e) {
        e.preventDefault();
        var container = $('.payment_modal');

        $.ajax({
            url: $(this).attr('href'),
            dataType: 'json',
            success: function(result) {
                if (result.status == 'due') {
                    container.html(result.view).modal('show');
                    __currency_convert_recursively(container);
                    $('#paid_on').datetimepicker({
                        format: moment_date_format + ' ' + moment_time_format,
                        ignoreReadonly: true,
                    });
                    container.find('form#transaction_payment_add_form').validate();
                    set_default_payment_account();

                    $('.payment_modal')
                        .find('input[type="checkbox"].input-icheck')
                        .each(function() {
                            $(this).iCheck({
                                checkboxClass: 'icheckbox_square-blue',
                                radioClass: 'iradio_square-blue',
                            });
                        });

                    // Initialize postpone date picker
                    $('#postpone_date').datetimepicker({
                        format: 'YYYY-MM-DD',
                        ignoreReadonly: true,
                    }).on('dp.change', function(e) {
                        // Ensure the date is properly set
                        $(this).val(e.date.format('YYYY-MM-DD'));
                    });

                    // Handle postpone checkbox toggle
                    // Try both regular click and iCheck events
                    $('#is_postpone').on('ifChanged', function() {
                        var isPostpone = $(this).is(':checked');
                        togglePostponeFields(isPostpone);
                    });
                    
                    // Also try regular change event as fallback
                    $('#is_postpone').on('change', function() {
                        var isPostpone = $(this).is(':checked');
                        togglePostponeFields(isPostpone);
                    });
                    
                    // Function to toggle fields
                    function togglePostponeFields(isPostpone) {
                        if (isPostpone) {
                            $('#postpone_date_row').show();
                            $('#payment_fields_row').hide();
                            $('#transaction_payment_add_form').find('input[name="amount"]').prop('required', false);
                            $('#transaction_payment_add_form').find('select[name="method"]').prop('required', false);
                        } else {
                            $('#postpone_date_row').hide();
                            $('#payment_fields_row').show();
                            $('#transaction_payment_add_form').find('input[name="amount"]').prop('required', true);
                            $('#transaction_payment_add_form').find('select[name="method"]').prop('required', true);
                        }
                    }
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
    $(document).on('click', '.edit_payment', function(e) {
        e.preventDefault();
        var container = $('.edit_payment_modal');

        $.ajax({
            url: $(this).data('href'),
            dataType: 'html',
            success: function(result) {
                container.html(result).modal('show');
                __currency_convert_recursively(container);
                $('#paid_on').datetimepicker({
                    format: moment_date_format + ' ' + moment_time_format,
                    ignoreReadonly: true,
                });
                container.find('form#transaction_payment_add_form').validate();
            },
        });
    });

    $(document).on('click', '.view_payment_modal', function(e) {
        e.preventDefault();
        var container = $('.payment_modal');

        $.ajax({
            url: $(this).attr('href'),
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                __currency_convert_recursively(container);
            },
        });
    });
    $(document).on('click', '.delete_payment', function(e) {
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_payment,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $.ajax({
                    url: $(this).data('href'),
                    method: 'delete',
                    dataType: 'json',
                    success: function(result) {
                        if (result.success === true) {
                            $('div.payment_modal').modal('hide');
                            $('div.edit_payment_modal').modal('hide');
                            toastr.success(result.msg);
                            if (typeof purchase_table != 'undefined') {
                                purchase_table.ajax.reload();
                            }
                            if (typeof sell_table != 'undefined') {
                                sell_table.ajax.reload();
                            }
                            if (typeof expense_table != 'undefined') {
                                expense_table.ajax.reload();
                            }
                            if (typeof ob_payment_table != 'undefined') {
                                ob_payment_table.ajax.reload();
                            }
                            // project Module
                            if (typeof project_invoice_datatable != 'undefined') {
                                project_invoice_datatable.ajax.reload();
                            }
                            
                            if ($('#contact_payments_table').length) {
                                get_contact_payments();
                            }
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    //view single payment
    $(document).on('click', '.view_payment', function() {
        var url = $(this).data('href');
        var container = $('.view_modal');
        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                __currency_convert_recursively(container);
            },
        });
    });
});

$(document).on('change', '#transaction_payment_add_form .payment_types_dropdown', function(e) {
    set_default_payment_account();
});

function set_default_payment_account() {
    var default_accounts = {};

    if (!_.isUndefined($('#transaction_payment_add_form #default_payment_accounts').val())) {
        default_accounts = JSON.parse($('#transaction_payment_add_form #default_payment_accounts').val());
    }

    var payment_type = $('#transaction_payment_add_form .payment_types_dropdown').val();
    if (payment_type && payment_type != 'advance') {
        var default_account = !_.isEmpty(default_accounts) && default_accounts[payment_type]['account'] ? 
            default_accounts[payment_type]['account'] : '';
        $('#transaction_payment_add_form #account_id').val(default_account);
        $('#transaction_payment_add_form #account_id').change();
    }
}

$(document).on('change', '.payment_types_dropdown', function(e) {
    var payment_type = $('#transaction_payment_add_form .payment_types_dropdown').val();
    account_dropdown = $('#transaction_payment_add_form #account_id');
    if (payment_type == 'advance') {
        if (account_dropdown) {
            account_dropdown.prop('disabled', true);
            account_dropdown.closest('.form-group').addClass('hide');
        }
    } else {
        if (account_dropdown) {
            account_dropdown.prop('disabled', false); 
            account_dropdown.closest('.form-group').removeClass('hide');
        }    
    }
});

$(document).on('submit', 'form#transaction_payment_add_form', function(e){
    e.preventDefault();
    
    var is_valid = true;
    var payment_type = $('#transaction_payment_add_form .payment_types_dropdown').val();
    var denominationField = $('#transaction_payment_add_form .enable_cash_denomination_for_payment_methods').val();
    var denomination_for_payment_types = [];
    
    if (denominationField && denominationField.trim() !== '') {
        try {
            denomination_for_payment_types = JSON.parse(denominationField);
        } catch(e) {
            denomination_for_payment_types = [];
        }
    }
    
    if (denomination_for_payment_types.includes(payment_type) && $('#transaction_payment_add_form .is_strict').length && $('#transaction_payment_add_form .is_strict').val() === '1' ) {
        var payment_amount = __read_number($('#transaction_payment_add_form .payment_amount'));
        var total_denomination = $('#transaction_payment_add_form').find('input.denomination_total_amount').val();
        if (payment_amount != total_denomination ) {
            is_valid = false;
        }
    }

    var $submitBtn = $('#transaction_payment_add_form').find('button[type="submit"]');
    $submitBtn.attr('disabled', false);

    if (!is_valid) {
        $('#transaction_payment_add_form').find('.cash_denomination_error').removeClass('hide');
        return false;
    } else {
        $('#transaction_payment_add_form').find('.cash_denomination_error').addClass('hide');
    }
    
    // Submit via AJAX to prevent page refresh
    var $form = $(this);
    var formData = new FormData($form[0]);
    
    $submitBtn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + ($submitBtn.text() || 'Processing...'));
    
    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(result) {
            if (result.success === true) {
                $('div.payment_modal').modal('hide');
                toastr.success(result.msg);
                
                // Reload relevant tables without page refresh
                if (typeof purchase_table != 'undefined') {
                    purchase_table.ajax.reload();
                }
                if (typeof sell_table != 'undefined') {
                    sell_table.ajax.reload();
                }
                if (typeof pending_repair_table != 'undefined') {
                    pending_repair_table.ajax.reload();
                }
                if (typeof expense_table != 'undefined') {
                    expense_table.ajax.reload();
                }
                if (typeof ob_payment_table != 'undefined') {
                    ob_payment_table.ajax.reload();
                }
                if (typeof project_invoice_datatable != 'undefined') {
                    project_invoice_datatable.ajax.reload();
                }
                if (
                    typeof treasury_transactions_table !== 'undefined' &&
                    treasury_transactions_table !== null &&
                    treasury_transactions_table.ajax &&
                    typeof treasury_transactions_table.ajax.reload === 'function'
                ) {
                    treasury_transactions_table.ajax.reload();
                } else if (
                    typeof $.fn !== 'undefined' &&
                    $.fn.DataTable &&
                    typeof $.fn.DataTable.isDataTable === 'function' &&
                    $.fn.DataTable.isDataTable('#treasury_transactions_table')
                ) {
                    $('#treasury_transactions_table').DataTable().ajax.reload();
                }
                if ($('#contact_payments_table').length) {
                    get_contact_payments();
                }
                
                // If current page explicitly opts in, reload to update static payment status tables
                if (typeof window !== 'undefined' && window.transaction_overview_auto_reload) {
                    window.location.reload();
                    return;
                }
                
                // Refresh payment view if visible
                var transaction_id = $form.find('input[name="transaction_id"]').val();
                if (transaction_id) {
                    $.ajax({
                        url: '/payments/' + transaction_id,
                        method: 'GET',
                        dataType: 'html',
                        success: function(html) {
                            $('div.payment_modal').html(html);
                        }
                    });
                }
            } else {
                toastr.error(result.msg || 'An error occurred');
            }
        },
        error: function(xhr) {
            var errorMsg = 'An error occurred while processing the payment';
            if (xhr.responseJSON && xhr.responseJSON.msg) {
                errorMsg = xhr.responseJSON.msg;
            }
            toastr.error(errorMsg);
        },
        complete: function() {
            $submitBtn.attr('disabled', false).html($submitBtn.data('original-text') || 'Save Payment');
        }
    });
    
    return false;
})