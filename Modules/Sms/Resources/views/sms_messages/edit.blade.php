@extends('layouts.app')

@section('title', __('sms::lang.edit_sms_message'))

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2 class="mb-0">
                <i class="fas fa-edit"></i> @lang('sms::lang.edit_sms_message')
            </h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('sms.messages.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> @lang('sms::lang.back_to_messages')
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('sms.messages.update', $message->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name" class="font-weight-bold">@lang('sms::lang.message_name') <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           id="name" name="name" value="{{ old('name', $message->name) }}" 
                           placeholder="{{ __('sms::lang.message_name_placeholder') }}" required>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="message_template" class="font-weight-bold">@lang('sms::lang.message_template') <span class="text-danger">*</span></label>
                    <div class="mb-2">
                        <small class="form-text text-muted d-block mb-2">
                            @lang('sms::lang.click_variable_to_insert')
                        </small>
                        <div class="tw-flex tw-flex-wrap" style="gap:6px;">
                            <button type="button" class="btn btn-sm btn-outline-secondary sms-variable"
                                data-variable="@&#123;&#123;customer_name&#125;&#125;">@lang('sms::lang.var_customer_name')</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary sms-variable"
                                data-variable="@&#123;&#123;jobsheete_id&#125;&#125;">@lang('sms::lang.var_jobsheete_id')</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary sms-variable"
                                data-variable="@&#123;&#123;amount&#125;&#125;">@lang('sms::lang.var_amount')</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary sms-variable"
                                data-variable="@&#123;&#123;invoice_no&#125;&#125;">@lang('sms::lang.var_invoice_no')</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary sms-variable"
                                data-variable="@&#123;&#123;delivery_date&#125;&#125;">@lang('sms::lang.var_delivery_date')</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary sms-variable"
                                data-variable="@&#123;&#123;due_date&#125;&#125;">@lang('sms::lang.var_due_date')</button>
                        </div>
                    </div>
                    <textarea class="form-control @error('message_template') is-invalid @enderror" 
                              id="message_template" name="message_template" rows="6" 
                              placeholder="{{ __('sms::lang.message_template_placeholder_blade') }}" 
                              required>{{ old('message_template', $message->message_template) }}</textarea>
                    <small class="form-text text-muted">
                        {!! __('sms::lang.message_template_help_blade') !!}
                    </small>
                    @error('message_template')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description" class="font-weight-bold">@lang('sms::lang.description')</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" name="description" rows="3" 
                              placeholder="{{ __('sms::lang.description_placeholder') }}">{{ old('description', $message->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">@lang('sms::lang.assign_roles') <span class="text-danger">*</span></label>
                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        @forelse($roles as $role)
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" 
                                       id="role_{{ $role->id }}" name="roles[]" value="{{ $role->id }}"
                                       @if(in_array($role->id, old('roles', $assignedRoles))) checked @endif>
                                <label class="custom-control-label" for="role_{{ $role->id }}">
                                    {{ $role->name }}
                                </label>
                            </div>
                        @empty
                            <p class="text-muted">@lang('sms::lang.no_roles_available')</p>
                        @endforelse
                    </div>
                    @error('roles')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="status" name="status" value="1"
                               @if(old('status', $message->status)) checked @endif>
                        <label class="custom-control-label" for="status">
                            <strong>@lang('sms::lang.active')</strong> - @lang('sms::lang.status_active_hint')
                        </label>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> @lang('sms::lang.update_message')
                    </button>
                    <a href="{{ route('sms.messages.index') }}" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> @lang('sms::lang.cancel')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .form-group label {
        margin-bottom: 0.5rem;
    }

    .border.rounded {
        border: 1px solid #dee2e6 !important;
    }

    .custom-control-label {
        cursor: pointer;
        user-select: none;
    }
</style>
@endsection

@push('scripts')
<script>
    (function() {
        function insertAtCaret(el, text) {
            if (!el) return;
            var start = el.selectionStart || 0;
            var end = el.selectionEnd || 0;
            el.value = el.value.substring(0, start) + text + el.value.substring(end);
            var caret = start + text.length;
            el.selectionStart = el.selectionEnd = caret;
            el.focus();
        }

        document.addEventListener('DOMContentLoaded', function() {
            var textarea = document.getElementById('message_template');
            if (!textarea) return;
            var buttons = document.querySelectorAll('.sms-variable');
            buttons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    var val = btn.getAttribute('data-variable') || '';
                    insertAtCaret(textarea, val);
                });
            });
        });
    })();
</script>
@endpush
