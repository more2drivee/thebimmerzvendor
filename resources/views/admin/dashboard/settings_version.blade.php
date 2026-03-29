<form action="{{ route('admin.dashboard.save') }}" method="POST">
    @csrf
    <input type="hidden" name="_version_form" value="1">

    <div class="pos-tab-content">
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    {{ __('lang_v1.version_update_help_text') }}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('version_settings[latest_version_name]', __('lang_v1.latest_version_name')) !!}
                    {!! Form::text('version_settings[latest_version_name]', $version_settings['latest_version_name'] ?? '', ['class' => 'form-control', 'placeholder' => '']) !!}
                    <p class="help-block">{{ __('lang_v1.latest_version_name_help') }}</p>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('version_settings[force_update]', __('lang_v1.force_update')) !!}
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('version_settings[force_update]', 1, $version_settings['force_update'] ?? false, ['class' => 'input-icheck']) !!}
                            {{ __('lang_v1.force_update_help') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    {!! Form::label('version_settings[apk_url]', __('lang_v1.apk_url')) !!}
                    {!! Form::text('version_settings[apk_url]', $version_settings['apk_url'] ?? '', ['class' => 'form-control', 'placeholder' => '']) !!}
                    <p class="help-block">{{ __('lang_v1.apk_url_help') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    {!! Form::label('version_settings[message]', __('lang_v1.update_message')) !!}
                    {!! Form::textarea('version_settings[message]', $version_settings['message'] ?? '', ['class' => 'form-control', 'rows' => 3, 'placeholder' => '']) !!}
                    <p class="help-block">{{ __('lang_v1.update_message_help') }}</p>
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
