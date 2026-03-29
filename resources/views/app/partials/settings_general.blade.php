<div class="pos-tab-content">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('company_name', __('app.company_name') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-building"></i>
                    </span>
                    {!! Form::text('company_name', $app_settings->company_name ?? '', ['class' => 'form-control', 'required',
                    'placeholder' => __('app.company_name')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('company_email', __('app.company_email') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-envelope"></i>
                    </span>
                    {!! Form::email('company_email', $app_settings->company_email ?? '', ['class' => 'form-control', 'required',
                    'placeholder' => __('app.company_email')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('company_phone', __('app.company_phone') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-phone"></i>
                    </span>
                    {!! Form::text('company_phone', $app_settings->company_phone ?? '', ['class' => 'form-control', 'required',
                    'placeholder' => __('app.company_phone')]); !!}
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('company_address', __('app.company_address') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-map-marker-alt"></i>
                    </span>
                    {!! Form::text('company_address', $app_settings->company_address ?? '', ['class' => 'form-control',
                    'placeholder' => __('app.company_address')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('company_city', __('app.company_city') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-city"></i>
                    </span>
                    {!! Form::text('company_city', $app_settings->company_city ?? '', ['class' => 'form-control',
                    'placeholder' => __('app.company_city')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('company_country', __('app.company_country') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-globe"></i>
                    </span>
                    {!! Form::select('company_country', $countries, $app_settings->company_country ?? '', ['class' => 'form-control select2']); !!}
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('company_description', __('app.company_description') . ':') !!}
                {!! Form::textarea('company_description', $app_settings->company_description ?? '', ['class' => 'form-control', 'rows' => 4, 'placeholder' => __('app.enter_company_description')]); !!}
            </div>
        </div>
    </div>
</div>
