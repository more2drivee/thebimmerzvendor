<div class="pos-tab-content active">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('app_name', __('app.app_name') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-mobile-alt"></i>
                    </span>
                    {!! Form::text('app_name', $app_settings->app_name ?? '', ['class' => 'form-control', 'required',
                    'placeholder' => __('app.app_name')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('app_version', __('app.app_version') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-code-branch"></i>
                    </span>
                    {!! Form::text('app_version', $app_settings->app_version ?? '1.0.0', ['class' => 'form-control','placeholder' => __('app.app_version'), 'readonly']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('app_status', __('app.app_status') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-toggle-on"></i>
                    </span>
                    {!! Form::select('app_status', ['active' => __('app.active'), 'inactive' => __('app.inactive'), 'maintenance' => __('app.maintenance')], $app_settings->app_status ?? 'active', ['class' => 'form-control select2','required']); !!}
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('app_logo', __('app.upload_logo') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-image"></i>
                    </span>
                    {!! Form::file('app_logo', ['accept' => 'image/*']); !!}
                </div>
                <p class="help-block"><i> @lang('app.logo_help')</i></p>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('app_icon', __('app.app_icon') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-icons"></i>
                    </span>
                    {!! Form::file('app_icon', ['accept' => 'image/*']); !!}
                </div>
                <p class="help-block"><i>Upload app icon (512x512 pixels recommended)</i></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('app_theme', __('app.app_theme') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-palette"></i>
                    </span>
                    {!! Form::select('app_theme', ['light' => __('app.light_theme'), 'dark' => __('app.dark_theme'), 'auto' => __('app.auto_theme')], $app_settings->app_theme ?? 'light', ['class' => 'form-control select2', 'required']); !!}
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('default_language', __('app.default_language') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-language"></i>
                    </span>
                    {!! Form::select('default_language', $languages, $app_settings->default_language ?? 'en', ['class' => 'form-control select2', 'required']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('app_timezone', __('app.app_timezone') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-clock"></i>
                    </span>
                    {!! Form::select('app_timezone', $timezone_list, $app_settings->app_timezone ?? 'UTC', ['class' => 'form-control select2', 'required']); !!}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('app_currency', __('app.app_currency') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-money-bill-alt"></i>
                    </span>
                    {!! Form::select('app_currency', $currencies, $app_settings->app_currency ?? 'USD', ['class' => 'form-control select2', 'required']); !!}
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('max_users', __('app.max_users') . ':*') !!}
                @show_tooltip(__('tooltip.max_users'))
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-users"></i>
                    </span>
                    {!! Form::number('max_users', $app_settings->max_users ?? 100, ['class' => 'form-control','placeholder' => __('app.max_users'), 'required']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('session_timeout', __('app.session_timeout') . ':*') !!}
                @show_tooltip(__('tooltip.session_timeout'))
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-hourglass-half"></i>
                    </span>
                    {!! Form::number('session_timeout', $app_settings->session_timeout ?? 30, ['class' => 'form-control','placeholder' => __('app.session_timeout'), 'required']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('date_format', __('app.date_format') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </span>
                    {!! Form::select('date_format', $date_formats, $app_settings->date_format ?? 'Y-m-d', ['class' => 'form-control select2', 'required']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('time_format', __('app.time_format') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-clock"></i>
                    </span>
                    {!! Form::select('time_format', [12 => __('app.12_hour'), 24 => __('app.24_hour')], $app_settings->time_format ?? 24, ['class' => 'form-control select2', 'required']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('currency_precision', __('app.currency_precision') . ':*') !!} @show_tooltip(__('app.currency_precision_help'))
                {!! Form::select('currency_precision', [0 =>0, 1=>1, 2=>2, 3=>3,4=>4], $app_settings->currency_precision ?? 2, ['class' => 'form-control select2', 'required']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('quantity_precision', __('app.quantity_precision') . ':*') !!} @show_tooltip(__('app.quantity_precision_help'))
                {!! Form::select('quantity_precision', [0 =>0, 1=>1, 2=>2, 3=>3,4=>4], $app_settings->quantity_precision ?? 2, ['class' => 'form-control select2', 'required']); !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('app_description', __('app.app_description') . ':') !!}
                {!! Form::textarea('app_description', $app_settings->app_description ?? '', ['class' => 'form-control', 'rows' => 5, 'placeholder' => __('app.enter_app_description')]); !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_notifications', true, $app_settings->enable_notifications ?? true, ['class' => 'input-icheck']); !!} {{ __('app.enable_notifications') }}
                </label>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_dark_mode', true, $app_settings->enable_dark_mode ?? false, ['class' => 'input-icheck']); !!} {{ __('app.enable_dark_mode') }}
                </label>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_analytics', true, $app_settings->enable_analytics ?? false, ['class' => 'input-icheck']); !!} {{ __('app.enable_analytics') }}
                </label>
            </div>
        </div>
    </div>
</div>
