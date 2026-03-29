@extends('layouts.app')
@section('title', __('Edit Data Permission'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Data Permissions
        <small>Edit Data Permission</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action('App\Http\Controllers\DataPermissionController@update', [$data_permission->id]), 'method' => 'PUT', 'id' => 'data_permission_edit_form' ]) !!}
    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('permission_key', __('Permission Key') . ':*') !!}
                        {!! Form::text('permission_key', $data_permission->permission_key, ['class' => 'form-control', 'required', 'placeholder' => __('Permission Key')]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('permission_name', __('Permission Name') . ':*') !!}
                        {!! Form::text('permission_name', $data_permission->permission_name, ['class' => 'form-control', 'required', 'placeholder' => __('Permission Name')]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <br>
                        <label>
                            {!! Form::checkbox('is_active', 1, $data_permission->is_active, ['class' => 'input-icheck']); !!} <strong>@lang('messages.active')</strong>
                        </label>
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
        $('#data_permission_edit_form').validate();
    });
</script>
@endsection
