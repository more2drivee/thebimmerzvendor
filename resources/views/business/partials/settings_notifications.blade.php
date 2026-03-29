<div class="pos-tab-content">
    <div class="row">
        <div class="col-md-12">
            <h4>@lang('lang_v1.notification_settings')</h4>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label(
                    'notification_settings[enabled]',
                    __('lang_v1.enable_notifications')
                ) !!}
                {!! Form::select(
                    'notification_settings[enabled]',
                    [1 => __('lang_v1.yes'), 0 => __('lang_v1.no')],
                    $notification_settings['enabled'] ?? 0,
                    ['class' => 'form-control']
                ) !!}
            </div>
        </div>

        <div class="clearfix"></div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label(
                    'notification_settings[firebase_project_id]',
                    'Firebase Project ID'
                ) !!}
                {!! Form::text(
                    'notification_settings[firebase_project_id]',
                    $notification_settings['firebase_project_id'] ?? '',
                    ['class' => 'form-control']
                ) !!}
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label(
                    'notification_settings[firebase_client_email]',
                    'Firebase Client Email'
                ) !!}
                {!! Form::email(
                    'notification_settings[firebase_client_email]',
                    $notification_settings['firebase_client_email'] ?? '',
                    ['class' => 'form-control']
                ) !!}
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label(
                    'notification_settings[firebase_private_key]',
                    'Firebase Private Key'
                ) !!}
                {!! Form::textarea(
                    'notification_settings[firebase_private_key]',
                    $notification_settings['firebase_private_key'] ?? '',
                    ['class' => 'form-control', 'rows' => 6]
                ) !!}
                <small class="text-muted">
                    انسخ الـ private_key كامل من Firebase Service Account
                </small>
            </div>
        </div>
    </div>
</div>
