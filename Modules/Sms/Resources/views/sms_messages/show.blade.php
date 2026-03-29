@extends('layouts.app')

@section('title', __('sms::lang.view_sms_message'))

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2 class="mb-0">
                <i class="fas fa-sms"></i> {{ $message->name }}
            </h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('sms.messages.edit', $message->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> @lang('sms::lang.edit')
            </a>
            <a href="{{ route('sms.messages.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> @lang('sms::lang.back')
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">@lang('sms::lang.message_details')</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="font-weight-bold">@lang('sms::lang.message_name'):</label>
                        <p>{{ $message->name }}</p>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">@lang('sms::lang.message_template'):</label>
                        <div class="alert alert-info">
                            <pre style="margin: 0; white-space: pre-wrap;">{{ $message->message_template }}</pre>
                        </div>
                    </div>

                    @if($message->description)
                        <div class="form-group">
                            <label class="font-weight-bold">@lang('sms::lang.description'):</label>
                            <p>{{ $message->description }}</p>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="font-weight-bold">@lang('sms::lang.status'):</label>
                        <p>
                            @if($message->status)
                                <span class="badge badge-success">@lang('sms::lang.active')</span>
                            @else
                                <span class="badge badge-danger">@lang('sms::lang.inactive')</span>
                            @endif
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">@lang('sms::lang.created_at'):</label>
                        <p>{{ $message->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">@lang('sms::lang.updated_at'):</label>
                        <p>{{ $message->updated_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">@lang('sms::lang.assigned_roles')</h5>
                </div>
                <div class="card-body">
                    @if(isset($roles) && $roles->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($roles as $role)
                                <li class="list-group-item">
                                    <i class="fas fa-user-shield text-primary"></i> {{ $role->name }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">@lang('sms::lang.no_roles_assigned')</p>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">@lang('sms::lang.actions')</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('sms.messages.edit', $message->id) }}" class="btn btn-warning btn-block mb-2">
                        <i class="fas fa-edit"></i> @lang('sms::lang.edit_sms_message')
                    </a>
                    <form action="{{ route('sms.messages.destroy', $message->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('{{ __('sms::lang.are_you_sure') }}')">
                            <i class="fas fa-trash"></i> @lang('sms::lang.delete_message')
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
