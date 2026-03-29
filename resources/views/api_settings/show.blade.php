@extends('layouts.app')
@section('title', __('View API Setting'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>API Settings
        <small>View API Setting</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('API Setting Details')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>@lang('Token'):</strong>
                                {{ $api_setting->token }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>@lang('Domain'):</strong>
                                {{ $api_setting->domain }}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <strong>@lang('Base URL'):</strong>
                                {{ $api_setting->base_url }}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>@lang('messages.created_at'):</strong>
                                {{ @format_datetime($api_setting->created_at) }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>@lang('messages.updated_at'):</strong>
                                {{ @format_datetime($api_setting->updated_at) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <a href="{{ action('App\Http\Controllers\ApiSettingController@index') }}" class="btn btn-primary pull-right">
                <i class="fa fa-list"></i> @lang('messages.back')
            </a>
        </div>
    </div>
</section>
@stop
