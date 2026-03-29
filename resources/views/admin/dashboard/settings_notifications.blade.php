<form action="{{ route('admin.dashboard.save') }}" method="POST">
    @csrf
    <input type="hidden" name="_notifications_form" value="1">

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
            <hr>
<div class="col-md-12">
    <h4>Firebase Web App Configuration (Client Side)</h4>
</div>

<div class="col-md-4">
    <div class="form-group">
        {!! Form::label('notification_settings[firebase_api_key]', 'API Key') !!}
        {!! Form::text(
            'notification_settings[firebase_api_key]',
            $notification_settings['firebase_api_key'] ?? '',
            ['class' => 'form-control']
        ) !!}
    </div>
</div>

<div class="col-md-4">
    <div class="form-group">
        {!! Form::label('notification_settings[firebase_auth_domain]', 'Auth Domain') !!}
        {!! Form::text(
            'notification_settings[firebase_auth_domain]',
            $notification_settings['firebase_auth_domain'] ?? '',
            ['class' => 'form-control']
        ) !!}
    </div>
</div>

<div class="col-md-4">
    <div class="form-group">
        {!! Form::label('notification_settings[firebase_storage_bucket]', 'Storage Bucket') !!}
        {!! Form::text(
            'notification_settings[firebase_storage_bucket]',
            $notification_settings['firebase_storage_bucket'] ?? '',
            ['class' => 'form-control']
        ) !!}
    </div>
</div>

<div class="col-md-4">
    <div class="form-group">
        {!! Form::label('notification_settings[firebase_messaging_sender_id]', 'Messaging Sender ID') !!}
        {!! Form::text(
            'notification_settings[firebase_messaging_sender_id]',
            $notification_settings['firebase_messaging_sender_id'] ?? '',
            ['class' => 'form-control']
        ) !!}
    </div>
</div>

<div class="col-md-4">
    <div class="form-group">
        {!! Form::label('notification_settings[firebase_app_id]', 'App ID') !!}
        {!! Form::text(
            'notification_settings[firebase_app_id]',
            $notification_settings['firebase_app_id'] ?? '',
            ['class' => 'form-control']
        ) !!}
    </div>
</div>

<div class="col-md-4">
    <div class="form-group">
        {!! Form::label('notification_settings[firebase_measurement_id]', 'Measurement ID') !!}
        {!! Form::text(
            'notification_settings[firebase_measurement_id]',
            $notification_settings['firebase_measurement_id'] ?? '',
            ['class' => 'form-control']
        ) !!}
    </div>
</div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> @lang('messages.save')
            </button>
        </div>
    </div>
</form>
