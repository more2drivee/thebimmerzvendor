@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">@lang('artificialintelligence::lang.provider_details')</h3>
                        <div>
                            <a href="{{ route('artificialintelligence.providers.edit', $provider->id) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> @lang('artificialintelligence::lang.edit_provider')
                            </a>
                            <a href="{{ route('artificialintelligence.providers') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> @lang('artificialintelligence::lang.back_to_list')
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th style="width: 200px;">ID</th>
                                    <td>{{ $provider->id }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('artificialintelligence::lang.provider_name')</th>
                                    <td>{{ $provider->provider }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('artificialintelligence::lang.model_name')</th>
                                    <td>{{ $provider->model_name }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('artificialintelligence::lang.status')</th>
                                    <td>
                                        <span class="badge badge-pill badge-{{ $provider->status == 'free' ? 'success' : 'warning' }}">
                                            {{ $provider->status == 'free' ? __('artificialintelligence::lang.free') : __('artificialintelligence::lang.paid') }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection