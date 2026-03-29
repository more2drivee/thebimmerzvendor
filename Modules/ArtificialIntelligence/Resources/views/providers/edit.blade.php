@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">@lang('artificialintelligence::lang.edit_provider')</h3>
                        <a href="{{ route('artificialintelligence.providers') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> @lang('artificialintelligence::lang.back_to_list')
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <form action="{{ route('artificialintelligence.providers.update', $provider->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group row">
                            <label for="provider" class="col-sm-2 col-form-label">@lang('artificialintelligence::lang.provider_name')</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control @error('provider') is-invalid @enderror"
                                       id="provider" name="provider" value="{{ old('provider', $provider->provider) }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="model_name" class="col-sm-2 col-form-label">@lang('artificialintelligence::lang.model_name')</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control @error('model_name') is-invalid @enderror"
                                       id="model_name" name="model_name" value="{{ old('model_name', $provider->model_name) }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="status" class="col-sm-2 col-form-label">@lang('artificialintelligence::lang.status')</label>
                            <div class="col-sm-10">
                                <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="free" {{ old('status', $provider->status) == 'free' ? 'selected' : '' }}>@lang('artificialintelligence::lang.free')</option>
                                    <option value="paid" {{ old('status', $provider->status) == 'paid' ? 'selected' : '' }}>@lang('artificialintelligence::lang.paid')</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-sm-10 offset-sm-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> @lang('artificialintelligence::lang.update_provider')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection