@extends('layouts.app')
@section('title', __('essentials::lang.add_employee'))

@section('content')
@include('essentials::layouts.nav_hrm')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('essentials::lang.add_employee')</h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="row">
            <div class="col-sm-12">
                @if(session('status.success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        {{ session('status.msg') }}
                    </div>
                @else
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        {{ session('status.msg') }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {!! Form::open(['url' => action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'store']), 'method' => 'POST', 'id' => 'employee_create_form', 'files' => true]) !!}

    <div class="row">
        {{-- Employee Image --}}
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.employee_photo')])
                <div class="col-md-3 text-center">
                    <div style="margin-bottom:15px;">
                        <img src="" alt="" id="employee_image_preview" class="tw-rounded-xl tw-shadow-lg"
                             style="width:150px;height:150px;object-fit:cover;display:none;">
                    </div>
                    <div class="form-group">
                        {!! Form::file('user_image', ['id' => 'user_image', 'accept' => 'image/*']) !!}
                    </div>
                </div>
            @endcomponent
        </div>

        {{-- Basic Info --}}
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.basic_info')])
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('surname', __('business.prefix') . ':') !!}
                        {!! Form::text('surname', null, ['class' => 'form-control', 'placeholder' => __('business.prefix')]) !!}
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        {!! Form::label('first_name', __('business.first_name') . ':*') !!}
                        {!! Form::text('first_name', null, ['class' => 'form-control', 'required', 'placeholder' => __('business.first_name')]) !!}
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        {!! Form::label('last_name', __('business.last_name') . ':') !!}
                        {!! Form::text('last_name', null, ['class' => 'form-control', 'placeholder' => __('business.last_name')]) !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('email', __('business.email') . ':') !!}
                        {!! Form::text('email', null, ['class' => 'form-control', 'placeholder' => __('business.email')]) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('contact_number', __('lang_v1.mobile_number') . ':') !!}
                        {!! Form::text('contact_number', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.mobile_number')]) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('alt_number', __('business.alternate_number') . ':') !!}
                        {!! Form::text('alt_number', null, ['class' => 'form-control', 'placeholder' => __('business.alternate_number')]) !!}
                    </div>
                </div>
            @endcomponent
        </div>

        {{-- Work Info: Department, Designation, Location, Salary --}}
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.work_info')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('essentials_department_id', __('essentials::lang.department') . ':') !!}
                        {!! Form::select('essentials_department_id', $departments, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('essentials_designation_id', __('essentials::lang.designation') . ':') !!}
                        {!! Form::select('essentials_designation_id', $designations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('essentials::lang.location_site') . ':') !!}
                        {!! Form::select('location_id', $locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('essentials_salary', __('essentials::lang.salary') . ':') !!}
                        {!! Form::text('essentials_salary', null, ['class' => 'form-control input_number', 'placeholder' => __('essentials::lang.salary')]) !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('essentials_pay_period', __('essentials::lang.pay_cycle') . ':') !!}
                        {!! Form::select('essentials_pay_period', [
                            'month' => __('essentials::lang.month'),
                            'week' => __('essentials::lang.week'),
                        ], null, ['class' => 'form-control', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
            @endcomponent
        </div>

        {{-- Personal Info --}}
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.personal_info')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('user_dob', __('lang_v1.dob') . ':') !!}
                        {!! Form::text('dob', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.dob'), 'readonly', 'id' => 'user_dob']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('gender', __('lang_v1.gender') . ':') !!}
                        {!! Form::select('gender', ['male' => __('lang_v1.male'), 'female' => __('lang_v1.female'), 'others' => __('lang_v1.others')], null, ['class' => 'form-control', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('marital_status', __('lang_v1.marital_status') . ':') !!}
                        {!! Form::select('marital_status', ['married' => __('lang_v1.married'), 'unmarried' => __('lang_v1.unmarried'), 'divorced' => __('lang_v1.divorced')], null, ['class' => 'form-control', 'placeholder' => __('lang_v1.marital_status')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('blood_group', __('lang_v1.blood_group') . ':') !!}
                        {!! Form::text('blood_group', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.blood_group')]) !!}
                    </div>
                </div>
            @endcomponent
        </div>

        {{-- Contact & Emergency Numbers --}}
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.contact_emergency')])
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('family_number', __('lang_v1.family_contact_number') . ':') !!}
                        {!! Form::text('family_number', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.family_contact_number')]) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('guardian_name', __('lang_v1.guardian_name') . ':') !!}
                        {!! Form::text('guardian_name', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.guardian_name')]) !!}
                    </div>
                </div>
            @endcomponent
        </div>

        {{-- ID & Documents --}}
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.id_documents')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('id_proof_name', __('lang_v1.id_proof_name') . ':') !!}
                        {!! Form::text('id_proof_name', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.id_proof_name')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('id_proof_number', __('lang_v1.id_proof_number') . ':') !!}
                        {!! Form::text('id_proof_number', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.id_proof_number')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('fingerprint_id', __('essentials::lang.fingerprint_id') . ':') !!}
                        {!! Form::text('fingerprint_id', null, ['class' => 'form-control', 'placeholder' => __('essentials::lang.fingerprint_id_placeholder')]) !!}
                        <p class="help-block">
                            <i class="fa fa-info-circle"></i>
                            @lang('essentials::lang.fingerprint_id_help')
                        </p>
                    </div>
                </div>
            @endcomponent
        </div>

        {{-- Address --}}
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.address_info')])
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('permanent_address', __('lang_v1.permanent_address') . ':') !!}
                        {!! Form::textarea('permanent_address', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.permanent_address'), 'rows' => 3]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('current_address', __('lang_v1.current_address') . ':') !!}
                        {!! Form::textarea('current_address', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.current_address'), 'rows' => 3]) !!}
                    </div>
                </div>
            @endcomponent
        </div>

        {{-- Social Media --}}
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.social_media')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('fb_link', __('lang_v1.fb_link') . ':') !!}
                        {!! Form::text('fb_link', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.fb_link')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('twitter_link', __('lang_v1.twitter_link') . ':') !!}
                        {!! Form::text('twitter_link', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.twitter_link')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('social_media_1', __('lang_v1.social_media', ['number' => 1]) . ':') !!}
                        {!! Form::text('social_media_1', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.social_media', ['number' => 1])]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('social_media_2', __('lang_v1.social_media', ['number' => 2]) . ':') !!}
                        {!! Form::text('social_media_2', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.social_media', ['number' => 2])]) !!}
                    </div>
                </div>
            @endcomponent
        </div>

        {{-- Custom Fields --}}
        @php
            $custom_labels = json_decode(session('business.custom_labels'), true);
            $user_custom_field1 = !empty($custom_labels['user']['custom_field_1']) ? $custom_labels['user']['custom_field_1'] : __('lang_v1.user_custom_field1');
            $user_custom_field2 = !empty($custom_labels['user']['custom_field_2']) ? $custom_labels['user']['custom_field_2'] : __('lang_v1.user_custom_field2');
            $user_custom_field3 = !empty($custom_labels['user']['custom_field_3']) ? $custom_labels['user']['custom_field_3'] : __('lang_v1.user_custom_field3');
            $user_custom_field4 = !empty($custom_labels['user']['custom_field_4']) ? $custom_labels['user']['custom_field_4'] : __('lang_v1.user_custom_field4');
        @endphp
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.custom_fields')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('custom_field_1', $user_custom_field1 . ':') !!}
                        {!! Form::text('custom_field_1', null, ['class' => 'form-control', 'placeholder' => $user_custom_field1]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('custom_field_2', $user_custom_field2 . ':') !!}
                        {!! Form::text('custom_field_2', null, ['class' => 'form-control', 'placeholder' => $user_custom_field2]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('custom_field_3', $user_custom_field3 . ':') !!}
                        {!! Form::text('custom_field_3', null, ['class' => 'form-control', 'placeholder' => $user_custom_field3]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('custom_field_4', $user_custom_field4 . ':') !!}
                        {!! Form::text('custom_field_4', null, ['class' => 'form-control', 'placeholder' => $user_custom_field4]) !!}
                    </div>
                </div>
            @endcomponent
        </div>

        {{-- Bank Details --}}
        <div class="col-md-12">
            @component('components.widget', ['title' => __('lang_v1.bank_details')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('account_holder_name', __('lang_v1.account_holder_name') . ':') !!}
                        {!! Form::text('bank_details[account_holder_name]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.account_holder_name')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('account_number', __('lang_v1.account_number') . ':') !!}
                        {!! Form::text('bank_details[account_number]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.account_number')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('bank_name', __('lang_v1.bank_name') . ':') !!}
                        {!! Form::text('bank_details[bank_name]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.bank_name')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('bank_code', __('lang_v1.bank_code') . ':') !!}
                        {!! Form::text('bank_details[bank_code]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.bank_code')]) !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('branch', __('lang_v1.branch') . ':') !!}
                        {!! Form::text('bank_details[branch]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.branch')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('tax_payer_id', __('lang_v1.tax_payer_id') . ':') !!}
                        {!! Form::text('bank_details[tax_payer_id]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.tax_payer_id')]) !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-center" style="margin-bottom:20px;">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white" id="submit_employee_button">
                @lang('messages.save')
            </button>
            <a href="{{ action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'index']) }}" class="tw-dw-btn tw-dw-btn-neutral tw-dw-btn-lg tw-text-white">
                @lang('messages.cancel')
            </a>
        </div>
    </div>

    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        __page_leave_confirmation('#employee_create_form');

        $('.select2').select2();

        $('#user_dob').datetimepicker({
            format: moment_date_format,
            ignoreReadonly: true,
        });

        $('#user_image').on('change', function() {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#employee_image_preview').attr('src', e.target.result).show();
            };
            if (this.files && this.files[0]) {
                reader.readAsDataURL(this.files[0]);
            }
        });

        $('form#employee_create_form').validate({
            rules: {
                first_name: { required: true },
                email: { email: true },
            },
        });
    });
</script>
@endsection
