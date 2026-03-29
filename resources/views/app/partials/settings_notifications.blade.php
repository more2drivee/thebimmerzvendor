<div class="pos-tab-content">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('email_notifications', __('app.email_notifications') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-envelope"></i>
                    </span>
                    {!! Form::select('email_notifications', ['all' => __('app.all_notifications'), 'important' => __('app.important_only'), 'none' => __('app.none')], $app_settings->email_notifications ?? 'important', ['class' => 'form-control select2']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('sms_notifications', __('app.sms_notifications') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-sms"></i>
                    </span>
                    {!! Form::select('sms_notifications', ['all' => __('app.all_notifications'), 'important' => __('app.important_only'), 'none' => __('app.none')], $app_settings->sms_notifications ?? 'none', ['class' => 'form-control select2']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('push_notifications', __('app.push_notifications') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-bell"></i>
                    </span>
                    {!! Form::select('push_notifications', ['all' => __('app.all_notifications'), 'important' => __('app.important_only'), 'none' => __('app.none')], $app_settings->push_notifications ?? 'important', ['class' => 'form-control select2']); !!}
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-12">
            <h4>{!! __('app.notification_types') !!}</h4>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('notify_login', true, $app_settings->notify_login ?? true, ['class' => 'input-icheck']); !!} {{ __('app.notify_login') }}
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('notify_registration', true, $app_settings->notify_registration ?? true, ['class' => 'input-icheck']); !!} {{ __('app.notify_registration') }}
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('notify_orders', true, $app_settings->notify_orders ?? true, ['class' => 'input-icheck']); !!} {{ __('app.notify_orders') }}
                </label>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('notify_payments', true, $app_settings->notify_payments ?? true, ['class' => 'input-icheck']); !!} {{ __('app.notify_payments') }}
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('notify_system_updates', true, $app_settings->notify_system_updates ?? false, ['class' => 'input-icheck']); !!} {{ __('app.notify_system_updates') }}
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('notify_security_alerts', true, $app_settings->notify_security_alerts ?? true, ['class' => 'input-icheck']); !!} {{ __('app.notify_security_alerts') }}
                </label>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('notification_sound', __('app.notification_sound') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-volume-up"></i>
                    </span>
                    {!! Form::select('notification_sound', ['default' => __('app.default_sound'), 'chime' => __('app.chime'), 'bell' => __('app.bell'), 'alert' => __('app.alert'), 'none' => __('app.none')], $app_settings->notification_sound ?? 'default', ['class' => 'form-control select2']); !!}
                </div>
            </div>
        </div>
    </div>
</div>
