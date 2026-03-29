@extends('layouts.app')
@section('title', __('business.business_settings'))

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('business.business_settings')</h1>
        <br>
        @include('layouts.partials.search_settings')
    </section>

    <!-- Main content -->
    <section class="content">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\BusinessController::class, 'postBusinessSettings']),
            'method' => 'post',
            'id' => 'bussiness_edit_form',
            'files' => true,
        ]) !!}
        <div class="row">
            <div class="col-xs-12">
                @component('components.widget', ['class' => 'pos-tab-container'])
                    <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 pos-tab-menu tw-rounded-lg">
                        <div class="list-group">
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base active">@lang('business.business')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('business.tax')
                                @show_tooltip(__('tooltip.business_tax'))</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('business.product')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('contact.contact')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('business.sale')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('sale.pos_sale')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('purchase.purchases')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('lang_v1.payment')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('business.dashboard')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('business.system')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('lang_v1.prefixes')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('lang_v1.reward_point_settings')</a>
                            <a href="#"
                                class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('lang_v1.custom_labels')</a>
                                   <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">Scan
                                QR Code</a>
                        </div>
                    </div>
                    <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                        @include('business.partials.settings_business')
                        @include('business.partials.settings_tax')
                        @include('business.partials.settings_product')
                        @include('business.partials.settings_contact')
                        @include('business.partials.settings_sales')
                        @include('business.partials.settings_pos')
                        @include('business.partials.settings_purchase')
                        @include('business.partials.settings_payment')
                        @include('business.partials.settings_dashboard')
                        @include('business.partials.settings_system')
                        @include('business.partials.settings_prefixes')
                        @include('business.partials.settings_reward_point')
                        @include('business.partials.settings_custom_labels')
                        @include('business.partials.settings_scanqrcode')

                    </div>
                @endcomponent
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 text-center">
                <button class="tw-dw-btn tw-dw-btn-error tw-dw-btn-lg tw-text-white"
                    type="submit">@lang('business.update_settings')</button>
            </div>
        </div>
        {!! Form::close() !!}
    </section>
@stop

@section('javascript')
    <script type="text/javascript">
        __page_leave_confirmation('#bussiness_edit_form');
        $(document).on('ifToggled', '#use_superadmin_settings', function() {
            if ($('#use_superadmin_settings').is(':checked')) {
                $('#toggle_visibility').addClass('hide');
                $('.test_email_btn').addClass('hide');
            } else {
                $('#toggle_visibility').removeClass('hide');
                $('.test_email_btn').removeClass('hide');
            }
        });

        $(document).ready(function() {

            $('#test_email_btn').click(function() {
                var data = {
                    mail_driver: $('#mail_driver').val(),
                    mail_host: $('#mail_host').val(),
                    mail_port: $('#mail_port').val(),
                    mail_username: $('#mail_username').val(),
                    mail_password: $('#mail_password').val(),
                    mail_encryption: $('#mail_encryption').val(),
                    mail_from_address: $('#mail_from_address').val(),
                    mail_from_name: $('#mail_from_name').val(),
                };
                $.ajax({
                    method: 'post',
                    data: data,
                    url: "{{ action([\App\Http\Controllers\BusinessController::class, 'testEmailConfiguration']) }}",
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true) {
                            swal({
                                text: result.msg,
                                icon: 'success'
                            });
                        } else {
                            swal({
                                text: result.msg,
                                icon: 'error'
                            });
                        }
                    },
                });
            });

            $('#test_sms_btn').click(function() {
                var test_number = $('#test_number').val();
                if (test_number.trim() == '') {
                    toastr.error('{{ __('lang_v1.test_number_is_required') }}');
                    $('#test_number').focus();

                    return false;
                }

                var data = {
                    url: $('#sms_settings_url').val(),
                    message: $('#message').val(),
                    send_to_param_name: $('#send_to_param_name').val(),
                    msg_param_name: $('#msg_param_name').val(),
                    request_method: $('#request_method').val(),
                    param_1: $('#sms_settings_param_key1').val(),
                    param_2: $('#sms_settings_param_key2').val(),
                    param_3: $('#sms_settings_param_key3').val(),
                    param_4: $('#sms_settings_param_key4').val(),
                    param_5: $('#sms_settings_param_key5').val(),
                    param_6: $('#sms_settings_param_key6').val(),
                    param_7: $('#sms_settings_param_key7').val(),
                    param_8: $('#sms_settings_param_key8').val(),
                    param_9: $('#sms_settings_param_key9').val(),
                    param_10: $('#sms_settings_param_key10').val(),

                    param_val_1: $('#sms_settings_param_val1').val(),
                    param_val_2: $('#sms_settings_param_val2').val(),
                    param_val_3: $('#sms_settings_param_val3').val(),
                    param_val_4: $('#sms_settings_param_val4').val(),
                    param_val_5: $('#sms_settings_param_val5').val(),
                    param_val_6: $('#sms_settings_param_val6').val(),
                    param_val_7: $('#sms_settings_param_val7').val(),
                    param_val_8: $('#sms_settings_param_val8').val(),
                    param_val_9: $('#sms_settings_param_val9').val(),
                    param_val_10: $('#sms_settings_param_val10').val(),
                    test_number: test_number
                };


                $.ajax({
                    method: 'POST',
                    data: data,
                    url: "{{ action([\App\Http\Controllers\BusinessController::class, 'testSmsConfiguration']) }}",
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            console.log("SMS Sent Successfully!", response.api_response);
                            swal({
                                text: response.message,
                                icon: 'success'
                            });
                        } else {
                            swal({
                                text: response.message,
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error("Error Response:", xhr.responseJSON);
                        swal({
                            text: xhr.responseJSON ? xhr.responseJSON.message :
                                "An error occurred",
                            icon: 'error'
                        });
                    }
                });




            });

            $('select.custom_labels_products').change(function() {
                value = $(this).val();
                textarea = $(this).parents('div.custom_label_product_div').find(
                    'div.custom_label_product_dropdown');
                if (value == 'dropdown') {
                    textarea.removeClass('hide');
                } else {
                    textarea.addClass('hide');
                }
            })

            const padTime = (value) => {
                return value && value.length === 5 ? value : value;
            };

            const calculateTotalHours = (startValue, endValue) => {
                if (!startValue || !endValue) {
                    return '';
                }

                const [startHour, startMinute] = startValue.split(':').map(Number);
                const [endHour, endMinute] = endValue.split(':').map(Number);

                if (isNaN(startHour) || isNaN(endHour)) {
                    return '';
                }

                let startMinutes = startHour * 60 + startMinute;
                let endMinutes = endHour * 60 + endMinute;
                if (endMinutes <= startMinutes) {
                    return '';
                }

                const diffMinutes = endMinutes - startMinutes;
                const rawHours = diffMinutes / 60;
                const roundedHours = Math.round(rawHours * 4) / 4;

                return roundedHours > 0 ? roundedHours.toFixed(2) : '';
            };

            const toggleRowInputs = ($row, enabled) => {
                $row.find('.work-start, .work-end').prop('disabled', !enabled);
                if (!enabled) {
                    $row.find('.work-total').val('');
                }
            };

            const computeRowTotal = ($row) => {
                const start = padTime($row.find('.work-start').val());
                const end = padTime($row.find('.work-end').val());
                const total = calculateTotalHours(start, end);
                $row.find('.work-total').val(total);
            };

            $('.work-schedule-row').each(function () {
                const $row = $(this);
                const isChecked = $row.find('.workday-toggle').is(':checked');
                toggleRowInputs($row, isChecked);
                if (isChecked) {
                    computeRowTotal($row);
                }
            });

            $(document).on('ifChecked', '.workday-toggle', function () {
                const $row = $(this).closest('.work-schedule-row');
                toggleRowInputs($row, true);
                computeRowTotal($row);
            });

            $(document).on('ifUnchecked', '.workday-toggle', function () {
                const $row = $(this).closest('.work-schedule-row');
                toggleRowInputs($row, false);
            });

            $(document).on('change', '.work-start, .work-end', function () {
                const $row = $(this).closest('.work-schedule-row');
                if ($row.find('.workday-toggle').is(':checked')) {
                    computeRowTotal($row);
                }
            });
        });
    </script>
@endsection
