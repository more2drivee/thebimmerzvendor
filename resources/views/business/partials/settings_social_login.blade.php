<div class="pos-tab-content">
    <div class="row">
        <div class="col-md-12">
            <h4>@lang('lang_v1.social_login_settings')</h4>
            <p class="text-muted">@lang('lang_v1.social_login_settings_description')</p>
        </div>
    </div>

    <!-- Google Login Settings -->
    <div class="row">
        <div class="col-md-12">
            <h5 class="tw-font-bold tw-mt-4 tw-mb-2">
                <i class="fa fa-google tw-text-red-500 tw-mr-2"></i>
                @lang('lang_v1.google_login_settings')
            </h5>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label(
                    'social_login_settings[google_enabled]',
                    __('lang_v1.enable_google_login')
                ) !!}
                {!! Form::select(
                    'social_login_settings[google_enabled]',
                    [1 => __('lang_v1.yes'), 0 => __('lang_v1.no')],
                    $social_login_settings['google_enabled'] ?? 0,
                    ['class' => 'form-control']
                ) !!}
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label(
                    'social_login_settings[google_client_id]',
                    __('lang_v1.google_client_id')
                ) !!}
                {!! Form::text(
                    'social_login_settings[google_client_id]',
                    $social_login_settings['google_client_id'] ?? '',
                    ['class' => 'form-control', 'placeholder' => 'e.g., 123456789-abcdefg.apps.googleusercontent.com']
                ) !!}
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label(
                    'social_login_settings[google_client_secret]',
                    __('lang_v1.google_client_secret')
                ) !!}
                {!! Form::text(
                    'social_login_settings[google_client_secret]',
                    $social_login_settings['google_client_secret'] ?? '',
                    ['class' => 'form-control', 'placeholder' => 'e.g., GOCSPX-xxxxxxxxx']
                ) !!}
            </div>
        </div>
    </div>

    <hr class="tw-my-4">

    <!-- Apple Login Settings -->
    <div class="row">
        <div class="col-md-12">
            <h5 class="tw-font-bold tw-mt-4 tw-mb-2">
                <i class="fa fa-apple tw-mr-2"></i>
                @lang('lang_v1.apple_login_settings')
            </h5>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label(
                    'social_login_settings[apple_enabled]',
                    __('lang_v1.enable_apple_login')
                ) !!}
                {!! Form::select(
                    'social_login_settings[apple_enabled]',
                    [1 => __('lang_v1.yes'), 0 => __('lang_v1.no')],
                    $social_login_settings['apple_enabled'] ?? 0,
                    ['class' => 'form-control']
                ) !!}
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label(
                    'social_login_settings[apple_client_id]',
                    __('lang_v1.apple_client_id')
                ) !!}
                {!! Form::text(
                    'social_login_settings[apple_client_id]',
                    $social_login_settings['apple_client_id'] ?? '',
                    ['class' => 'form-control', 'placeholder' => 'e.g., com.yourapp.signin']
                ) !!}
                <small class="text-muted">@lang('lang_v1.apple_client_id_help')</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label(
                    'social_login_settings[apple_team_id]',
                    __('lang_v1.apple_team_id')
                ) !!}
                {!! Form::text(
                    'social_login_settings[apple_team_id]',
                    $social_login_settings['apple_team_id'] ?? '',
                    ['class' => 'form-control', 'placeholder' => 'e.g., ABCDEF1234']
                ) !!}
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label(
                    'social_login_settings[apple_key_id]',
                    __('lang_v1.apple_key_id')
                ) !!}
                {!! Form::text(
                    'social_login_settings[apple_key_id]',
                    $social_login_settings['apple_key_id'] ?? '',
                    ['class' => 'form-control', 'placeholder' => 'e.g., ABC12DEF34']
                ) !!}
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label(
                    'social_login_settings[apple_redirect_url]',
                    __('lang_v1.apple_redirect_url')
                ) !!}
                {!! Form::text(
                    'social_login_settings[apple_redirect_url]',
                    $social_login_settings['apple_redirect_url'] ?? '',
                    ['class' => 'form-control', 'placeholder' => 'e.g., https://yourdomain.com/apple-callback']
                ) !!}
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label(
                    'apple_service_file',
                    __('lang_v1.apple_p8_file')
                ) !!}
                {!! Form::file(
                    'apple_service_file',
                    [
                        'class' => 'form-control',
                        // Keep .p8 hint but allow any mime to avoid browser rejection on unknown types
                        'accept' => '.p8,*/*'
                    ]
                ) !!}
                <small class="text-muted">
                    @lang('lang_v1.apple_p8_file_help')
                    @if(!empty($social_login_settings['apple_service_file']))
                        <br><strong>@lang('lang_v1.current_file'): </strong>{{ $social_login_settings['apple_service_file'] }}
                    @endif
                </small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                <strong>@lang('lang_v1.note'):</strong>
                @lang('lang_v1.social_login_setup_instructions')
            </div>
        </div>
    </div>
</div>