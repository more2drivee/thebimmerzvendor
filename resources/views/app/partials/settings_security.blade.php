<div class="pos-tab-content">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('password_policy', __('app.password_policy') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-shield-alt"></i>
                    </span>
                    {!! Form::select('password_policy', ['weak' => __('app.weak'), 'medium' => __('app.medium'), 'strong' => __('app.strong'), 'very_strong' => __('app.very_strong')], $app_settings->password_policy ?? 'medium', ['class' => 'form-control select2']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('session_duration', __('app.session_duration') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-clock"></i>
                    </span>
                    {!! Form::number('session_duration', $app_settings->session_duration ?? 30, ['class' => 'form-control', 'required', 'placeholder' => __('app.minutes')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('max_login_attempts', __('app.max_login_attempts') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-lock"></i>
                    </span>
                    {!! Form::number('max_login_attempts', $app_settings->max_login_attempts ?? 5, ['class' => 'form-control', 'required']); !!}
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-12">
            <h4>{!! __('app.security_features') !!}</h4>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_two_factor', true, $app_settings->enable_two_factor ?? false, ['class' => 'input-icheck']); !!} {{ __('app.enable_two_factor') }}
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('force_password_change', true, $app_settings->force_password_change ?? false, ['class' => 'input-icheck']); !!} {{ __('app.force_password_change') }}
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('log_failed_attempts', true, $app_settings->log_failed_attempts ?? true, ['class' => 'input-icheck']); !!} {{ __('app.log_failed_attempts') }}
                </label>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_ip_whitelist', true, $app_settings->enable_ip_whitelist ?? false, ['class' => 'input-icheck']); !!} {{ __('app.enable_ip_whitelist') }}
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_captcha', true, $app_settings->enable_captcha ?? false, ['class' => 'input-icheck']); !!} {{ __('app.enable_captcha') }}
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('auto_logout', true, $app_settings->auto_logout ?? true, ['class' => 'input-icheck']); !!} {{ __('app.auto_logout') }}
                </label>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('ip_whitelist', __('app.ip_whitelist') . ':') !!}
                {!! Form::textarea('ip_whitelist', $app_settings->ip_whitelist ?? '', ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('app.enter_ip_addresses')]); !!}
                <p class="help-block"><i>@lang('app.ip_whitelist_help')</i></p>
            </div>
        </div>
    </div>
</div>
