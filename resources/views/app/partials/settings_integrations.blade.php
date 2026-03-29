<div class="pos-tab-content">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('google_analytics', __('app.google_analytics') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fab fa-google"></i>
                    </span>
                    {!! Form::text('google_analytics', $app_settings->google_analytics ?? '', ['class' => 'form-control', 'placeholder' => __('app.enter_tracking_id')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('facebook_pixel', __('app.facebook_pixel') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fab fa-facebook"></i>
                    </span>
                    {!! Form::text('facebook_pixel', $app_settings->facebook_pixel ?? '', ['class' => 'form-control', 'placeholder' => __('app.enter_pixel_id')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('google_maps_key', __('app.google_maps_key') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-map"></i>
                    </span>
                    {!! Form::text('google_maps_key', $app_settings->google_maps_key ?? '', ['class' => 'form-control', 'placeholder' => __('app.enter_api_key')]); !!}
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('twitter_api', __('app.twitter_api') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fab fa-twitter"></i>
                    </span>
                    {!! Form::text('twitter_api', $app_settings->twitter_api ?? '', ['class' => 'form-control', 'placeholder' => __('app.enter_api_key')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('instagram_api', __('app.instagram_api') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fab fa-instagram"></i>
                    </span>
                    {!! Form::text('instagram_api', $app_settings->instagram_api ?? '', ['class' => 'form-control', 'placeholder' => __('app.enter_api_key')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('youtube_api', __('app.youtube_api') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fab fa-youtube"></i>
                    </span>
                    {!! Form::text('youtube_api', $app_settings->youtube_api ?? '', ['class' => 'form-control', 'placeholder' => __('app.enter_api_key')]); !!}
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-12">
            <h4>{!! __('app.payment_gateways') !!}</h4>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_stripe', true, $app_settings->enable_stripe ?? false, ['class' => 'input-icheck']); !!} {{ __('app.enable_stripe') }}
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_paypal', true, $app_settings->enable_paypal ?? false, ['class' => 'input-icheck']); !!} {{ __('app.enable_paypal') }}
                </label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>
                    {!! Form::checkbox('enable_authorize', true, $app_settings->enable_authorize ?? false, ['class' => 'input-icheck']); !!} {{ __('app.enable_authorize') }}
                </label>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('stripe_key', __('app.stripe_key') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fab fa-stripe"></i>
                    </span>
                    {!! Form::text('stripe_key', $app_settings->stripe_key ?? '', ['class' => 'form-control', 'placeholder' => __('app.enter_stripe_key')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('paypal_client_id', __('app.paypal_client_id') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fab fa-paypal"></i>
                    </span>
                    {!! Form::text('paypal_client_id', $app_settings->paypal_client_id ?? '', ['class' => 'form-control', 'placeholder' => __('app.enter_client_id')]); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('webhook_url', __('app.webhook_url') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-link"></i>
                    </span>
                    {!! Form::text('webhook_url', $app_settings->webhook_url ?? '', ['class' => 'form-control', 'placeholder' => __('app.enter_webhook_url')]); !!}
                </div>
            </div>
        </div>
    </div>
</div>
