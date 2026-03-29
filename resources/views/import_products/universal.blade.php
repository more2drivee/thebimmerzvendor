@extends('layouts.app')
@section('title', 'Universal Product Import')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Universal Product Import</h1>
</section>

<section class="content">
    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    @if(!empty($notification['msg']))
                        {{$notification['msg']}}
                    @elseif(session('notification.msg'))
                        {{ session('notification.msg') }}
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if (session('status'))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    {{ session('status.msg') }}
                </div>
            </div>
        </div>
    @endif

    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::open(['url' => action([\App\Http\Controllers\UniversalProductImportController::class, 'preview']), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('products_excel', 'File to import:') !!}
                    {!! Form::file('products_excel', ['accept'=> '.xls, .xlsx, .csv', 'required' => 'required']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Excel Template:</label><br>
                    <a href="{{ route('import.products.template') }}" class="btn btn-sm btn-success">
                        <i class="fa fa-download"></i> Download Excel Template
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('default_unit', 'Default Unit (when missing):') !!}
                    {!! Form::text('default_unit', 'pcs', ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('default_manage_stock', 'Default Manage Stock:') !!}
                    {!! Form::select('default_manage_stock', ['1' => 'Yes', '0' => 'No'], '1', ['class' => 'form-control select2']) !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('default_tax_amount', 'Default Tax % (optional):') !!}
                    {!! Form::number('default_tax_amount', null, ['class' => 'form-control', 'step' => '0.01', 'min' => '0']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('create_opening_stock', 'Create Opening Stock?') !!}
                    {!! Form::select('create_opening_stock', ['0' => 'No', '1' => 'Yes'], '0', ['class' => 'form-control select2']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('create_purchase', 'Create Purchase?') !!}
                    {!! Form::select('create_purchase', ['0' => 'No', '1' => 'Yes'], '0', ['class' => 'form-control select2']) !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('require_supplier', 'Require Supplier (if purchase)?') !!}
                    {!! Form::select('require_supplier', ['0' => 'No', '1' => 'Yes'], '0', ['class' => 'form-control select2']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('auto_create_supplier', 'Auto-create Supplier?') !!}
                    {!! Form::select('auto_create_supplier', ['0' => 'No', '1' => 'Yes'], '0', ['class' => 'form-control select2']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('create_location', 'Auto-create Location?') !!}
                    {!! Form::select('create_location', ['1' => 'Yes', '0' => 'No'], '1', ['class' => 'form-control select2']) !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('update_existing', 'If SKU Exists:') !!}
                    {!! Form::select('update_existing', ['0' => 'Skip', '1' => 'Update Product & Add Stock'], '1', ['class' => 'form-control select2']) !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 text-right">
                <button type="submit" class="btn btn-primary" style="margin-top: 25px;">@lang('messages.submit')</button>
            </div>
        </div>
        {!! Form::close() !!}
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => 'Template Headers (mapping will be done on next step)'])
        <div class="row">
            <div class="col-sm-12">
                <p class="text-muted">Only Product Name is required. Others are optional; missing values will use defaults or be created.</p>
                <table class="table table-striped">
                    <tr>
                        <th>#</th>
                        <th>Column</th>
                    </tr>
                    @foreach($import_fields as $field)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $field['label'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endcomponent
</section>
@endsection
