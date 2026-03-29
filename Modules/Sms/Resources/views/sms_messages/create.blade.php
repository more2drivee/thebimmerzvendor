@extends('layouts.app')

@section('title', __('sms::lang.create_sms_message'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fas fa-plus-circle"></i> @lang('sms::lang.create_new_sms_message')
    </h1>
</section>

<!-- Reuse the same SMS navbar as index -->
<!-- Reuse the same SMS navbar as index -->
@include('sms::layouts.navbar')

<section class="content no-print">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('sms::lang.create_sms_message')])
    @slot('tool')
    <div class="box-tools">
        <a href="{{ route('sms.messages.index') }}" class="btn btn-default pull-right">
            <i class="fas fa-arrow-left"></i> @lang('sms::lang.back_to_messages')
        </a>
    </div>
    @endslot

    <form action="{{ route('sms.messages.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name" class="font-weight-bold">@lang('sms::lang.message_name') <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror"
                id="name" name="name" value="{{ old('name') }}"
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
                        data-variable="&#123;&#123;customer_name&#125;&#125;">@lang('sms::lang.var_customer_name')</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary sms-variable"
                        data-variable="&#123;&#123;jobsheete_id&#125;&#125;">@lang('sms::lang.var_jobsheete_id')</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary sms-variable"
                        data-variable="&#123;&#123;amount&#125;&#125;">@lang('sms::lang.var_amount')</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary sms-variable"
                        data-variable="&#123;&#123;invoice_no&#125;&#125;">@lang('sms::lang.var_invoice_no')</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary sms-variable"
                        data-variable="&#123;&#123;delivery_date&#125;&#125;">@lang('sms::lang.var_delivery_date')</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary sms-variable"
                        data-variable="&#123;&#123;due_date&#125;&#125;">@lang('sms::lang.var_due_date')</button>
                </div>
                
                <div class="mt-2">
                    <small class="form-text text-muted d-block mb-1">
                        <strong>Car Inspection:</strong>
                    </small>
                    <div class="tw-flex tw-flex-wrap" style="gap:4px;">
                        <button type="button" class="btn btn-sm btn-outline-info sms-variable"
                            data-variable="&#123;&#123;inspection_id&#125;&#125;">ID</button>
                        <button type="button" class="btn btn-sm btn-outline-info sms-variable"
                            data-variable="&#123;&#123;car_brand&#125;&#125;">Brand</button>
                        <button type="button" class="btn btn-sm btn-outline-info sms-variable"
                            data-variable="&#123;&#123;car_model&#125;&#125;">Model</button>
                        <button type="button" class="btn btn-sm btn-outline-info sms-variable"
                            data-variable="&#123;&#123;car_year&#125;&#125;">Year</button>
                        <button type="button" class="btn btn-sm btn-outline-info sms-variable"
                            data-variable="&#123;&#123;car_color&#125;&#125;">Color</button>
                        <button type="button" class="btn btn-sm btn-outline-info sms-variable"
                            data-variable="&#123;&#123;car_chassis_number&#125;&#125;">Chassis</button>
                        <button type="button" class="btn btn-sm btn-outline-info sms-variable"
                            data-variable="&#123;&#123;car_plate_number&#125;&#125;">Plate</button>
                        <button type="button" class="btn btn-sm btn-outline-info sms-variable"
                            data-variable="&#123;&#123;car_kilometers&#125;&#125;">KM</button>
                        <button type="button" class="btn btn-sm btn-outline-info sms-variable"
                            data-variable="&#123;&#123;inspection_status&#125;&#125;">Status</button>
                        <button type="button" class="btn btn-sm btn-outline-info sms-variable"
                            data-variable="&#123;&#123;inspection_date&#125;&#125;">Date</button>
                        <button type="button" class="btn btn-sm btn-outline-info sms-variable"
                            data-variable="&#123;&#123;share_url&#125;&#125;">Share URL</button>
                    </div>
                </div>
                
                <div class="mt-2">
                    <small class="form-text text-muted d-block mb-1">
                        <strong>Buyer/Seller:</strong>
                    </small>
                    <div class="tw-flex tw-flex-wrap" style="gap:4px;">
                        <button type="button" class="btn btn-sm btn-outline-success sms-variable"
                            data-variable="&#123;&#123;customer_full_name&#125;&#125;">Full Name</button>
                        <button type="button" class="btn btn-sm btn-outline-success sms-variable"
                            data-variable="&#123;&#123;customer_mobile&#125;&#125;">Mobile</button>
                        <button type="button" class="btn btn-sm btn-outline-warning sms-variable"
                            data-variable="&#123;&#123;contact_type&#125;&#125;">Buyer/Seller</button>
                    </div>
                </div>
            </div>
            <textarea class="form-control @error('message_template') is-invalid @enderror"
                id="message_template" name="message_template" rows="6"
                placeholder="{{ __('sms::lang.message_template_placeholder') }}"
                required>{{ old('message_template') }}</textarea>
            <small class="form-text text-muted">
                {!! __('sms::lang.message_template_help') !!}
            </small>

            @error('message_template')
            <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="description" class="font-weight-bold">@lang('sms::lang.description')</label>
            <textarea class="form-control @error('description') is-invalid @enderror"
                id="description" name="description" rows="3"
                placeholder="{{ __('sms::lang.description_placeholder') }}">{{ old('description') }}</textarea>

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
                        @if(in_array($role->id, old('roles', []))) checked @endif>
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
                    @if(old('status', true)) checked @endif>
                <label class="custom-control-label" for="status">
                    <strong>@lang('sms::lang.active')</strong> - @lang('sms::lang.status_active_hint')
                </label>
            </div>
        </div>

        <div class="form-group mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> @lang('sms::lang.create_message')
            </button>
            <a href="{{ route('sms.messages.index') }}" class="btn btn-default">
                <i class="fas fa-times"></i> @lang('sms::lang.cancel')
            </a>
        </div>

    </form>

    @endcomponent
</section>
<script>
    (function() {
        function insertAtCaret(el, text) {
            if (!el) return;
            // Save current selection
            var start = el.selectionStart || 0;
            var end = el.selectionEnd || 0;
            // Insert text at selection/caret
            el.value = el.value.substring(0, start) + text + el.value.substring(end);
            // Move caret after inserted text
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
                    if (val.charAt(0) === '@') {
                        val = val.substring(1);
                    }
                    insertAtCaret(textarea, val);
                });
            });
        });
    })();
</script>
@endsection