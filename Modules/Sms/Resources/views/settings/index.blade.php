@extends('layouts.app')

@section('title', 'SMS & Email Settings')

@section('content')
    @include('sms::layouts.navbar')

    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">SMS & Email Settings</h1>
    </section>

    <section class="content">
        <style>
            .pos-tab-content { display: block !important; }
            .pos-tab { display: block !important; }
            /* keep panes visible to avoid Bootstrap JS mismatch */
            .tab-pane { display: none; }
            .tab-pane.active { display: block; }
            .settings-tabs { display: flex; border-bottom: 2px solid #dee2e6; margin-bottom: 1.5rem; }
            .settings-tabs .nav-link { border: none; border-bottom: 2px solid transparent; margin-bottom: -2px; color: #6c757d; font-weight: 600; padding: 0.75rem 1.5rem; }
            .settings-tabs .nav-link:hover { border-color: #e9ecef; color: #495057; }
            .settings-tabs .nav-link.active { border-color: #5b4fc9; color: #5b4fc9; background: transparent; }
        </style>
        {!! Form::open([
            'url' => route('sms.messages.settings.update'),
            'method' => 'post',
            'id' => 'sms_settings_form',
        ]) !!}

        <div class="row">
            <div class="col-xs-12">
                @component('components.widget', ['class' => 'pos-tab-container'])
                    {{-- Tabs Navigation --}}
                    <ul class="nav nav-tabs settings-tabs" role="tablist">
                        <li class="nav-item active" role="presentation">
                            <a class="nav-link active" data-toggle="tab" href="#sms-settings-tab" role="tab" aria-expanded="true">
                                <i class="fas fa-sms me-2"></i>@lang('lang_v1.sms_settings')
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" data-toggle="tab" href="#email-settings-tab" role="tab" aria-expanded="false">
                                <i class="fas fa-envelope me-2"></i>@lang('lang_v1.email_settings')
                            </a>
                        </li>
                    </ul>
                    
                    {{-- Tab Content --}}
                    <div class="tab-content">
                        <div class="tab-pane active" id="sms-settings-tab" role="tabpanel">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 pos-tab">
                                @include('sms::partials.settings_sms')
                            </div>
                        </div>
                        <div class="tab-pane" id="email-settings-tab" role="tabpanel">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 pos-tab">
                                @include('sms::partials.settings_email', ['email_settings' => $email_settings ?? [], 'mail_drivers' => $mail_drivers ?? [], 'allow_superadmin_email_settings' => $allow_superadmin_email_settings ?? false])
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12 text-center">
                <button class="tw-dw-btn tw-dw-btn-error tw-dw-btn-lg tw-text-white" type="submit">Save Settings</button>
            </div>
        </div>

        {!! Form::close() !!}
    </section>


<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('change', '#sms_service', function() {
            var service = $(this).val();
            $('.sms_service_settings').addClass('hide');
            $('.sms_service_settings[data-service="' + service + '"]').removeClass('hide');
        });

        $('#test_sms_btn').off('click').on('click', function() {
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
                        swal({ text: response.message, icon: 'success' });
                    } else {
                        swal({ text: response.message, icon: 'error' });
                    }
                },
                error: function(xhr) {
                    swal({ text: (xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred'), icon: 'error' });
                }
            });
        });

        // Ensure tabs switch (manual toggle to avoid Bootstrap JS mismatch)
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.settings-tabs a[data-toggle="tab"]').forEach(function(tab) {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    var target = this.getAttribute('href');

                    // nav active state
                    document.querySelectorAll('.settings-tabs li').forEach(function(li) {
                        li.classList.remove('active');
                    });
                    document.querySelectorAll('.settings-tabs a').forEach(function(a) {
                        a.classList.remove('active');
                    });
                    this.classList.add('active');
                    this.closest('li').classList.add('active');

                    // pane active state
                    document.querySelectorAll('.tab-content .tab-pane').forEach(function(pane) {
                        pane.classList.remove('active');
                    });
                    document.querySelector(target).classList.add('active');
                });
            });
        });
        
        // Email test button
        $(document).on('ifToggled', '#use_superadmin_settings', function() {
            if ($('#use_superadmin_settings').is(':checked')) {
                $('#toggle_visibility').addClass('hide');
                $('.test_email_btn').addClass('hide');
            } else {
                $('#toggle_visibility').removeClass('hide');
                $('.test_email_btn').removeClass('hide');
            }
        });

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
                        swal({ text: result.msg, icon: 'success' });
                    } else {
                        swal({ text: result.msg, icon: 'error' });
                    }
                },
            });
        });
    });
</script>
@endsection
