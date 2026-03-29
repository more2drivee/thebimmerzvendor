@extends('layouts.app')
@section('title', __('Edit API Setting'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>API Settings
        <small>Edit API Setting</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action('App\Http\Controllers\ApiSettingController@update', [$api_setting->id]), 'method' => 'PUT', 'id' => 'api_setting_edit_form' ]) !!}
    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('token', __('Token') . ':*') !!}
                        {!! Form::text('token', $api_setting->token, ['class' => 'form-control', 'required', 'placeholder' => __('Token')]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('domain', __('Domain') . ':*') !!}
                        {!! Form::text('domain', $api_setting->domain, ['class' => 'form-control', 'required', 'placeholder' => __('Domain')]); !!}
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('base_url', __('Base URL') . ':*') !!}
                        {!! Form::text('base_url', $api_setting->base_url, ['class' => 'form-control', 'required', 'placeholder' => __('Base URL')]); !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <button type="submit" class="btn btn-primary pull-right">@lang('messages.update')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        $('#api_setting_edit_form').validate();
    });
</script>
@endsection
