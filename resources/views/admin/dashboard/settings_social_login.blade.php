<form action="{{ route('admin.dashboard.save') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="_social_login_form" value="1">

    <div class="pos-tab-content">
        <div class="row">
            <div class="col-md-12">
                <h4>@lang('lang_v1.social_login_settings')</h4>
                <p class="text-muted">@lang('lang_v1.social_login_settings_description')</p>
            </div>
        </div>

        <!-- Google Login Settings -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fab fa-google text-danger me-2"></i>
                            @lang('lang_v1.google_login_settings')
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group mb-3">
                                    {!! Form::label(
                                        'social_login_settings[google_enabled]',
                                        __('lang_v1.enable_google_login'),
                                        ['class' => 'form-label fw-bold']
                                    ) !!}
                                    {!! Form::select(
                                        'social_login_settings[google_enabled]',
                                        [1 => __('lang_v1.yes'), 0 => __('lang_v1.no')],
                                        $social_login_settings['google_enabled'] ?? 0,
                                        ['class' => 'form-select']
                                    ) !!}
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="form-group mb-3">
                                    {!! Form::label(
                                        'social_login_settings[google_client_id]',
                                        __('lang_v1.google_client_id'),
                                        ['class' => 'form-label fw-bold']
                                    ) !!}
                                    {!! Form::text(
                                        'social_login_settings[google_client_id]',
                                        $social_login_settings['google_client_id'] ?? '',
                                        ['class' => 'form-control', 'placeholder' => 'e.g., 123456789-abcdefg.apps.googleusercontent.com']
                                    ) !!}
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="form-group mb-3">
                                    {!! Form::label(
                                        'social_login_settings[google_client_secret]',
                                        __('lang_v1.google_client_secret'),
                                        ['class' => 'form-label fw-bold']
                                    ) !!}
                                    {!! Form::text(
                                        'social_login_settings[google_client_secret]',
                                        $social_login_settings['google_client_secret'] ?? '',
                                        ['class' => 'form-control', 'placeholder' => 'e.g., GOCSPX-xxxxxxxxx']
                                    ) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Apple Login Settings -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <i class="fab fa-apple text-dark me-2"></i>
                            @lang('lang_v1.apple_login_settings')
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group mb-3">
                                    {!! Form::label(
                                        'social_login_settings[apple_enabled]',
                                        __('lang_v1.enable_apple_login'),
                                        ['class' => 'form-label fw-bold']
                                    ) !!}
                                    {!! Form::select(
                                        'social_login_settings[apple_enabled]',
                                        [1 => __('lang_v1.yes'), 0 => __('lang_v1.no')],
                                        $social_login_settings['apple_enabled'] ?? 0,
                                        ['class' => 'form-select']
                                    ) !!}
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="form-group mb-3">
                                    {!! Form::label(
                                        'social_login_settings[apple_client_id]',
                                        __('lang_v1.apple_client_id'),
                                        ['class' => 'form-label fw-bold']
                                    ) !!}
                                    {!! Form::text(
                                        'social_login_settings[apple_client_id]',
                                        $social_login_settings['apple_client_id'] ?? '',
                                        ['class' => 'form-control', 'placeholder' => 'e.g., com.yourapp.signin']
                                    ) !!}
                                    <small class="form-text text-muted">@lang('lang_v1.apple_client_id_help')</small>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="form-group mb-3">
                                    {!! Form::label(
                                        'social_login_settings[apple_team_id]',
                                        __('lang_v1.apple_team_id'),
                                        ['class' => 'form-label fw-bold']
                                    ) !!}
                                    {!! Form::text(
                                        'social_login_settings[apple_team_id]',
                                        $social_login_settings['apple_team_id'] ?? '',
                                        ['class' => 'form-control', 'placeholder' => 'e.g., ABCDEF1234']
                                    ) !!}
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="form-group mb-3">
                                    {!! Form::label(
                                        'social_login_settings[apple_key_id]',
                                        __('lang_v1.apple_key_id'),
                                        ['class' => 'form-label fw-bold']
                                    ) !!}
                                    {!! Form::text(
                                        'social_login_settings[apple_key_id]',
                                        $social_login_settings['apple_key_id'] ?? '',
                                        ['class' => 'form-control', 'placeholder' => 'e.g., ABC12DEF34']
                                    ) !!}
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="form-group mb-3">
                                    {!! Form::label(
                                        'social_login_settings[apple_redirect_url]',
                                        __('lang_v1.apple_redirect_url'),
                                        ['class' => 'form-label fw-bold']
                                    ) !!}
                                    {!! Form::text(
                                        'social_login_settings[apple_redirect_url]',
                                        $social_login_settings['apple_redirect_url'] ?? '',
                                        ['class' => 'form-control', 'placeholder' => 'e.g., https://yourdomain.com/apple-callback']
                                    ) !!}
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group mb-3">
                                    {!! Form::label(
                                        'apple_service_file',
                                        __('lang_v1.apple_p8_file'),
                                        ['class' => 'form-label fw-bold']
                                    ) !!}
                                    {!! Form::file(
                                        'apple_service_file',
                                        [
                                            'class' => 'form-control',
                                            // Keep .p8 hint but allow any mime to avoid browser rejection on unknown types
                                            'accept' => '.p8,*/*'
                                        ]
                                    ) !!}
                                    <small class="form-text text-muted">
                                        @lang('lang_v1.apple_p8_file_help')
                                        @if(!empty($social_login_settings['apple_service_file']))
                                            <br><strong>@lang('lang_v1.current_file'): </strong>{{ $social_login_settings['apple_service_file'] }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="alert alert-info d-flex align-items-center">
                    <i class="fas fa-info-circle me-3"></i>
                    <div>
                        <strong>@lang('lang_v1.note'):</strong>
                        @lang('lang_v1.social_login_setup_instructions')
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i> @lang('messages.save')
                </button>
            </div>
        </div>
    </div>
</form>
