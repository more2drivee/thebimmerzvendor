@extends('layouts.app')
@section('title', __('View Data Permission'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Data Permissions
        <small>View Data Permission</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('Data Permission Details')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>@lang('Permission Key'):</strong>
                                {{ $data_permission->permission_key }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>@lang('Permission Name'):</strong>
                                {{ $data_permission->permission_name }}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>@lang('Status'):</strong>
                                @if($data_permission->is_active)
                                    <span class="label bg-green">@lang('messages.active')</span>
                                @else
                                    <span class="label bg-gray">@lang('messages.inactive')</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <strong>@lang('messages.created_at'):</strong>
                                {{ @format_datetime($data_permission->created_at) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <a href="{{ action('App\Http\Controllers\DataPermissionController@index') }}" class="btn btn-primary pull-right">
                <i class="fa fa-list"></i> @lang('messages.back')
            </a>
        </div>
    </div>
</section>
@stop
