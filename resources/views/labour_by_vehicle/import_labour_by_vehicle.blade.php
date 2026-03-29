@extends('layouts.app')
@section('title', __('Labour Import'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('Labour Import')</h1>
    <p class="text-muted">@lang('Upload the Excel file and the system will create or update labour data for vehicles and labour products.')</p>
</section>

<section class="content">
    @if (session('status'))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    {{ session('status.msg') }}
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary'])
            {!! Form::open(['url' => action([\App\Http\Controllers\LabourByVehicleController::class, 'importLabourByVehicleStore']), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('labour_excel', __('Excel file to import') . ':') !!}
                            {!! Form::file('labour_excel', ['accept'=> '.xls, .xlsx, .csv', 'required' => 'required']); !!}
                            <small class="text-muted">@lang('Expected sheet name: الادخال')</small>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="submit" class="btn btn-primary" style="margin-top: 25px;">@lang('messages.submit')</button>
                    </div>
                </div>
            {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-info', 'title' => __('Import Notes')])
                <ul>
                    <li>@lang('The importer creates device categories from التصنيف and brands/models from الماركه والموديل.')</li>
                    <li>@lang('Labour products are created/updated with is_labour enabled and stock disabled.')</li>
                    <li>@lang('Prices use السعر بعد الخصم (float) when available, otherwise سعر المصنعيه.')</li>
                </ul>
            @endcomponent
        </div>
    </div>
</section>
@endsection
