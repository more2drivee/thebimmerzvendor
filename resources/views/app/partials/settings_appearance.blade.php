<div class="pos-tab-content">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('primary_color', __('app.primary_color') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-paint-brush"></i>
                    </span>
                    {!! Form::text('primary_color', $app_settings->primary_color ?? '#007bff', ['class' => 'form-control color-picker', 'placeholder' => '#007bff']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('secondary_color', __('app.secondary_color') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-fill-drip"></i>
                    </span>
                    {!! Form::text('secondary_color', $app_settings->secondary_color ?? '#6c757d', ['class' => 'form-control color-picker', 'placeholder' => '#6c757d']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('accent_color', __('app.accent_color') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-palette"></i>
                    </span>
                    {!! Form::text('accent_color', $app_settings->accent_color ?? '#28a745', ['class' => 'form-control color-picker', 'placeholder' => '#28a745']); !!}
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('font_family', __('app.font_family') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-font"></i>
                    </span>
                    {!! Form::select('font_family', ['Arial' => 'Arial', 'Helvetica' => 'Helvetica', 'Times New Roman' => 'Times New Roman', 'Georgia' => 'Georgia', 'Verdana' => 'Verdana', 'Courier New' => 'Courier New'], $app_settings->font_family ?? 'Arial', ['class' => 'form-control select2']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('font_size', __('app.font_size') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-text-height"></i>
                    </span>
                    {!! Form::select('font_size', ['12px' => '12px', '14px' => '14px', '16px' => '16px', '18px' => '18px', '20px' => '20px'], $app_settings->font_size ?? '14px', ['class' => 'form-control select2']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('layout_style', __('app.layout_style') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-th-large"></i>
                    </span>
                    {!! Form::select('layout_style', ['boxed' => __('app.boxed'), 'wide' => __('app.wide'), 'fluid' => __('app.fluid')], $app_settings->layout_style ?? 'wide', ['class' => 'form-control select2']); !!}
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-12">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_animations', true, $app_settings->enable_animations ?? true, ['class' => 'input-icheck']); !!} {{ __('app.enable_animations') }}
                </label>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_gradients', true, $app_settings->enable_gradients ?? false, ['class' => 'input-icheck']); !!} {{ __('app.enable_gradients') }}
                </label>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_shadows', true, $app_settings->enable_shadows ?? true, ['class' => 'input-icheck']); !!} {{ __('app.enable_shadows') }}
                </label>
            </div>
        </div>
    </div>
</div>
