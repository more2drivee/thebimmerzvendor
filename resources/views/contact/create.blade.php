<div class="modal-dialog modal-lg" role="document">
    <?php $models = DB::table('repair_device_models')->select('id', 'name')->get(); ?>
    <div class="modal-content">
        @php
            $form_id = 'contact_add_form';
            if (isset($quick_add)) {
                $form_id = 'quick_add_contact';
            }

            if (isset($store_action)) {
                $url = $store_action;
                $type = 'lead';
                $customer_groups = [];
            } else {
                $url = action([\App\Http\Controllers\ContactController::class, 'store']);
                $type = isset($selected_type) ? $selected_type : '';
                $sources = [];
                $life_stages = [];
            }
        @endphp

        <style>
            /* Custom dropdown styles for contact form */
            .contact-custom-dropdown {
                position: relative;
                width: 100%;
            }

            .contact-custom-dropdown-search {
                width: 100%;
                padding: 8px;
                border: 1px solid #ced4da;
                border-bottom: none;
                border-radius: 4px 4px 0 0;
            }

            .contact-custom-dropdown select {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            .contact-custom-dropdown-options {
                max-height: 200px;
                overflow-y: auto;
                border: 1px solid #ced4da;
                border-radius: 0 0 4px 4px;
                background-color: white;
                z-index: 1000;
            }

            .contact-custom-dropdown-option {
                padding: 8px 12px;
                cursor: pointer;
            }

            .contact-custom-dropdown-option:hover {
                background-color: #f8f9fa;
            }

            .contact-custom-dropdown-option.selected {
                background-color: #e9ecef;
            }

            .contact-custom-dropdown-option.hidden {
                display: none;
            }

            .contact-custom-dropdown-display {
                padding: 4px 12px;
                border: 1px solid #ced4da;
                border-radius: 4px;
                background-color: white;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .contact-custom-dropdown-display:after {
                content: '\25BC';
                font-size: 0.8em;
            }

            .contact-custom-dropdown.open .contact-custom-dropdown-display {
                border-radius: 4px 4px 0 0;
                border-bottom: none;
            }

            .contact-custom-dropdown.open .contact-custom-dropdown-display:after {
                content: '\25B2';
            }

            .contact-custom-dropdown-options-container {
                display: none;
                position: absolute;
                width: 100%;
                z-index: 1000;
                background-color: white;
            }

            .contact-custom-dropdown.open .contact-custom-dropdown-options-container {
                display: block;
            }

            .contact-custom-input-group {
                display: flex;
                flex-wrap: nowrap;
            }

            .contact-custom-input-group .input-group-prepend {
                display: flex;
            }

            .contact-custom-input-group .input-group-text {
                display: flex;
                align-items: center;
                padding: 0.375rem 0.75rem;
                margin-bottom: 0;
                font-size: 1rem;
                font-weight: 400;
                line-height: 1.5;
                color: #495057;
                text-align: center;
                white-space: nowrap;
                background-color: #e9ecef;
                border: 1px solid #ced4da;
                border-radius: 0.25rem 0 0 0.25rem;
                border-right: 0;
            }
        </style>

        {!! Form::open(['url' => $url, 'method' => 'post', 'id' => $form_id]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('contact.add_contact')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 contact_type_div">
                    <div class="form-group">
                        {!! Form::label('type', __('contact.contact_type') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::select('type', $types, $type, [
                                'class' => 'form-control',
                                'id' => 'contact_type',
                                'placeholder' => __('messages.please_select'),
                                'required',
                            ]) !!}
                        </div>
                    </div>
                </div>
                {!! Form::hidden('contact_type_radio', 'individual') !!}
                
                <!-- Supplier Login Fields - Only shown when type is supplier -->
                <div class="col-md-4 supplier_login_fields" style="display: none;">
                    <div class="form-group">
                        {!! Form::label('username', __('lang_v1.username') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::text('username', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.username')]) !!}
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 supplier_login_fields" style="display: none;">
                    <div class="form-group">
                        {!! Form::label('password', __('lang_v1.password') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-lock"></i>
                            </span>
                            {!! Form::password('password', ['class' => 'form-control', 'placeholder' => __('lang_v1.password')]) !!}
                        </div>
                    </div>
                </div>
                @if(!isset($store_action) && !isset($quick_add))
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('contact_id', __('lang_v1.contact_id') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-id-badge"></i>
                            </span>
                            {!! Form::text('contact_id', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.contact_id')]) !!}
                        </div>
                        <p class="help-block">
                            @lang('lang_v1.leave_empty_to_autogenerate')
                        </p>
                    </div>
                </div>
                @endif
                <!-- Customer group field - hidden in CRM context -->
                @if(!isset($store_action) && false)
                <div class="col-md-4 customer_fields">
                    <div class="form-group">
                        {!! Form::label('customer_group_id', __('lang_v1.customer_group') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-users"></i>
                            </span>
                            {!! Form::select('customer_group_id', $customer_groups, '', ['class' => 'form-control']) !!}
                        </div>
                    </div>
                </div>
                @endif
                <div class="clearfix customer_fields"></div>
                <!-- Removed business div -->

                <div class="clearfix"></div>

                <div class="col-md-4 business" style="display: none;">
                    <div class="form-group">
                        {!! Form::label('supplier_business_name', __('business.business_name') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-briefcase"></i>
                            </span>
                            {!! Form::text('supplier_business_name', null, ['class' => 'form-control', 'placeholder' => __('business.business_name')]) !!}
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>

                @if(!isset($store_action) && !isset($quick_add))
                <div class="col-md-3 individual">
                    <div class="form-group">
                        {!! Form::label('prefix', __('business.prefix') . ':') !!}
                        {!! Form::text('prefix', null, ['class' => 'form-control', 'placeholder' => __('business.prefix_placeholder')]) !!}
                    </div>
                </div>
                @endif
                <div class="col-md-3 individual">
                    <div class="form-group">
                        {!! Form::label('first_name', __('business.first_name') . ':*') !!}
                        {!! Form::text('first_name', null, [
                            'class' => 'form-control',
                            'required',
                            'data-required' => 'true',
                            'placeholder' => __('business.first_name'),
                        ]) !!}
                    </div>
                </div>
                <div class="col-md-3 individual">
                    <div class="form-group">
                        {!! Form::label('middle_name', __('lang_v1.middle_name') . ':') !!}
                        {!! Form::text('middle_name', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.middle_name')]) !!}
                    </div>
                </div>
                <div class="col-md-3 individual">
                    <div class="form-group">
                        {!! Form::label('last_name', __('business.last_name') . ':') !!}
                        {!! Form::text('last_name', null, ['class' => 'form-control', 'placeholder' => __('business.last_name')]) !!}
                    </div>
                </div>
                <div class="clearfix"></div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('mobile', __('contact.mobile') . ':*', ['style' => 'margin-bottom: 29px;']) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-mobile"></i>
                            </span>
                            {!! Form::text('mobile', null, ['class' => 'form-control', 'required', 'placeholder' => __('contact.mobile')]) !!}
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('alternate_number', __('contact.alternate_contact_number') . ':', ['style' => 'margin-bottom: 29px;']) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-phone"></i>
                            </span>
                            {!! Form::text('alternate_number', null, [
                                'class' => 'form-control',
                                'placeholder' => __('contact.alternate_contact_number'),
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('landline', __('contact.landline') . ':', ['style' => 'margin-bottom: 29px;']) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-phone"></i>
                            </span>
                            {!! Form::text('landline', null, ['class' => 'form-control', 'placeholder' => __('contact.landline')]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('email', __('business.email') . ':', ['style' => 'margin-bottom: 29px;']) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-envelope"></i>
                            </span>
                            {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => __('business.email')]) !!}
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                @if(isset($store_action))
                <div class="col-sm-4 individual" style="display:none;">
                    <div class="form-group">
                        {!! Form::label('dob', __('lang_v1.dob') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>

                            {!! Form::text('dob', null, [
                                'class' => 'form-control dob-date-picker',
                                'placeholder' => __('lang_v1.dob'),
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                </div>
                @endif

                <!-- lead additional field -->
                @if(!isset($store_action))
                <div class="col-md-4 lead_additional_div">
                    <div class="form-group">
                        {!! Form::label('crm_source', __('lang_v1.source') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fas fa fa-search"></i>
                            </span>
                            {!! Form::select('crm_source', $sources, null, [
                                'class' => 'form-control',
                                'id' => 'crm_source',
                                'placeholder' => __('messages.please_select'),
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="col-md-4 lead_additional_div">
                    <div class="form-group">
                        {!! Form::label('crm_life_stage', __('lang_v1.life_stage') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fas fa fa-life-ring"></i>
                            </span>
                            {!! Form::select('crm_life_stage', $life_stages, null, [
                                'class' => 'form-control',
                                'id' => 'crm_life_stage',
                                'placeholder' => __('messages.please_select'),
                            ]) !!}
                        </div>
                    </div>
                </div>
                @endif

                <!-- User in create leads -->
                <div class="col-md-6 lead_additional_div">
                    <div class="form-group">
                        {!! Form::label('user_id', __('lang_v1.assigned_to') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::select('user_id[]', $users ?? [], null, [
                                'class' => 'form-control select2',
                                'id' => 'user_id',
                                'multiple',
                                'required',
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <!-- User in create customer & supplier -->
                @if (config('constants.enable_contact_assign') && $type !== 'lead' && !isset($quick_add))
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('assigned_to_users', __('lang_v1.assigned_to') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                {!! Form::select('assigned_to_users[]', $users ?? [], null, [
                                    'class' => 'form-control select2',
                                    'id' => 'assigned_to_users',
                                    'multiple',
                                    'style' => 'width: 100%;',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif

                <div class="clearfix"></div>
            </div>

            {{-- /<div class="space" style="margin-bottom: 25px;"> --}}
            @if(!isset($quick_add))
            <div class="row">
                <div class="col-md-12">
                    <button type="button"
                        class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm center-block more_btn"
                        id="toggleVehicleBtn">
                        @lang('car.addvehicle') <i class="fa fa-chevron-down"></i>
                    </button>
                </div>

                <!-- Vehicle details section (Initially Hidden) -->
                <div id="vehicle_div" style="display: none;">
                    <div class="col-md-12">
                        <hr />
                    </div>

                    <!-- Chassis/VIN Number Input -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="chassis_number">@lang('car.vin'):</label>
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-key"></i>
                                </span>
                                <input type="text" name="chassis_number" id="vin_num" class="form-control"
                                    placeholder="{{ __('car.vin') }}" value="{{ old('chassis_number') }}">
                            </div>
                            <small class="text-muted">@lang('car.entervin')</small>
                            <small id="chassis_lookup_message" class="text-danger d-none">No vehicle information
                                found</small>
                        </div>
                    </div>

                    <!-- Car Type -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="car_type">@lang('car.cartype'):</label>
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-car"></i>
                                </span>
                                <select name="car_type" id="car_type" class="form-control">
                                    <option value="">@lang('car.selectcartype')</option>
                                    <option value="ملاكي">ملاكي</option>
                                    <option value="اجرة">اجرة</option>
                                    <option value="نقل ثقيل">نقل ثقيل</option>
                                    <option value="نقل خفيف">نقل خفيف</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Brand, Model, and Manufacturing Year in one row -->
                    <div class="col-md-12">
                        <div class="row">
                            <!-- Brand Select -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="gehad_category_id">@lang('car.brand'):</label>
                                    <div class="input-group contact-custom-input-group d-flex flex-nowrap">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-car-alt"></i>
                                            </span>
                                        </div>
                                        @php
                                            $brand_information = \Illuminate\Support\Facades\DB::table('categories')
                                                ->where('category_type', 'device')
                                                ->select('id', 'name')
                                                ->get();
                                        @endphp
                                        <!-- Hidden select element for form submission -->
                                        <select name="gehad_category_id" id="gehad_category_id" class="form-control" style="display: none;" required>
                                            <option value="">@lang('car.selectbrand')</option>
                                            @foreach ($brand_information as $category)
                                                <option value="{{ $category->id }}"
                                                    {{ old('gehad_category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <!-- Custom dropdown UI -->
                                        <div class="contact-custom-dropdown" id="contact-brand-dropdown">
                                            <div class="contact-custom-dropdown-display" id="contact-brand-display">@lang('car.selectbrand')</div>
                                            <div class="contact-custom-dropdown-options-container">
                                                <input type="text" class="contact-custom-dropdown-search" id="contact-brand-search" placeholder="Search brands...">
                                                <div class="contact-custom-dropdown-options" id="contact-brand-options">
                                                    <div class="contact-custom-dropdown-option" data-value="">@lang('car.selectbrand')</div>
                                                    @foreach ($brand_information as $category)
                                                        <div class="contact-custom-dropdown-option" data-value="{{ $category->id }}">
                                                            {{ $category->name }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <small id="category_error" class="text-danger d-none">No matched brand</small>
                                </div>
                            </div>

                            <!-- Model Select -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="gehad_model_id">@lang('car.model')</label>
                                    <div class="input-group contact-custom-input-group d-flex flex-nowrap">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-car-alt"></i>
                                            </span>
                                        </div>
                                        <!-- Hidden select element for form submission -->
                                        <select name="gehad_model_id" id="gehad_model_id" class="form-control" style="display: none;">
                                            <option value="">@lang('car.selectmodel')</option>
                                        </select>

                                        <!-- Custom dropdown UI -->
                                        <div class="contact-custom-dropdown" id="contact-model-dropdown">
                                            <div class="contact-custom-dropdown-display" id="contact-model-display">@lang('car.selectmodel')</div>
                                            <div class="contact-custom-dropdown-options-container">
                                                <input type="text" class="contact-custom-dropdown-search" id="contact-model-search" placeholder="Search models...">
                                                <div class="contact-custom-dropdown-options" id="contact-model-options">
                                                    <div class="contact-custom-dropdown-option" data-value="">@lang('car.selectmodel')</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <small id="model_error" class="text-danger d-none">No matched model</small>
                                </div>
                            </div>

                            <!-- Manufacturing Year Input -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vic_year">@lang('car.manufacturing')</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-calendar"></i>
                                        </span>
                                        <select class="form-control" id="vic_year" name="manufacturing_year">
                                            <option value="">@lang('car.selectyear')</option>
                                            @foreach (range(date('Y'), 1990) as $year)
                                                <option value="{{ $year }}">{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Brand Origin / Country (variants) -->
                    <div class="col-md-12" style="margin-top: 10px;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_brand_origin_variant_id">@lang('car.country')</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-flag"></i>
                                        </span>
                                        <select name="brand_origin_variant_id" id="contact_brand_origin_variant_id" class="form-control">
                                            <option value="">@lang('car.selectcountry')</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Color and Plate Number in one row -->
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="color">@lang('car.color'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-paint-brush"></i>
                                        </span>
                                        <input type="text" name="color" id="color" class="form-control"
                                        placeholder="{{ __('car.color') }}" value="{{ old('color') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="plate_number">@lang('car.plate'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-key"></i>
                                        </span>
                                        <input type="text" name="plate_number" id="plate_number"
                                            class="form-control" placeholder="{{ __('car.plate') }}"
                                            value="{{ old('plate_number') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            {{-- </div> --}}

            <div class="row">
                <div class="col-md-12">
                    <button type="button"
                        class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm center-block more_btn"
                        id="toggleMoreInfo">
                        @lang('lang_v1.more_info') <i class="fa fa-chevron-down"></i>
                    </button>
                </div>

                <!-- More Info Section (Initially Hidden) -->
                <div id="more_div" style="display: none;">
                    {!! Form::hidden('position', null, ['id' => 'position']) !!}
                    <div class="col-md-12">
                        <hr />
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('tax_number', __('contact.tax_no') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-info"></i>
                                </span>
                                {!! Form::text('tax_number', null, ['class' => 'form-control', 'placeholder' => __('contact.tax_no')]) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 opening_balance">
                        <div class="form-group">
                            {!! Form::label('opening_balance', __('lang_v1.opening_balance') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fas fa-money-bill-alt"></i>
                                </span>
                                {!! Form::text('opening_balance', 0, ['class' => 'form-control input_number']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 pay_term">
                        <div class="form-group">
                            <div class="multi-input">
                                {!! Form::label('pay_term_number', __('contact.pay_term') . ':') !!} @show_tooltip(__('tooltip.pay_term'))
                                <br />
                                {!! Form::number('pay_term_number', null, [
                                    'class' => 'form-control width-40 pull-left',
                                    'placeholder' => __('contact.pay_term'),
                                ]) !!}

                                {!! Form::select('pay_term_type', ['months' => __('lang_v1.months'), 'days' => __('lang_v1.days')], '', [
                                    'class' => 'form-control width-60 pull-left',
                                    'placeholder' => __('messages.please_select'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    @php
                        $common_settings = session()->get('business.common_settings');
                        $default_credit_limit = !empty($common_settings['default_credit_limit'])
                            ? $common_settings['default_credit_limit']
                            : null;
                    @endphp
                    <div class="col-md-4 customer_fields">
                        <div class="form-group">
                            {!! Form::label('credit_limit', __('lang_v1.credit_limit') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fas fa-money-bill-alt"></i>
                                </span>
                                {!! Form::text('credit_limit', $default_credit_limit ?? null, ['class' => 'form-control input_number']) !!}
                            </div>
                            <p class="help-block">@lang('lang_v1.credit_limit_help')</p>
                        </div>
                    </div>


                    <div class="col-md-12">
                        <hr />
                    </div>
                    <div class="clearfix"></div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('address_line_1', __('lang_v1.address_line_1') . ':') !!}
                            {!! Form::text('address_line_1', null, [
                                'class' => 'form-control',
                                'placeholder' => __('lang_v1.address_line_1'),
                                'rows' => 3,
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('address_line_2', __('lang_v1.address_line_2') . ':') !!}
                            {!! Form::text('address_line_2', null, [
                                'class' => 'form-control',
                                'placeholder' => __('lang_v1.address_line_2'),
                                'rows' => 3,
                            ]) !!}
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('city', __('business.city') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>
                                {!! Form::text('city', null, ['class' => 'form-control', 'placeholder' => __('business.city')]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('state', __('business.state') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>
                                {!! Form::text('state', null, ['class' => 'form-control', 'placeholder' => __('business.state')]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('country', __('business.country') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-globe"></i>
                                </span>
                                {!! Form::text('country', null, ['class' => 'form-control', 'placeholder' => __('business.country')]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('zip_code', __('business.zip_code') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>
                                {!! Form::text('zip_code', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('business.zip_code_placeholder'),
                                ]) !!}
                            </div>
                        </div>
                    </div>

                    <div class="clearfix"></div>
                    <div class="col-md-12">
                        <hr />
                    </div>
                    @php
                        $custom_labels = json_decode(session('business.custom_labels'), true);
                        $contact_custom_field1 = !empty($custom_labels['contact']['custom_field_1'])
                            ? $custom_labels['contact']['custom_field_1']
                            : __('lang_v1.contact_custom_field1');
                        $contact_custom_field2 = !empty($custom_labels['contact']['custom_field_2'])
                            ? $custom_labels['contact']['custom_field_2']
                            : __('lang_v1.contact_custom_field2');
                        $contact_custom_field3 = !empty($custom_labels['contact']['custom_field_3'])
                            ? $custom_labels['contact']['custom_field_3']
                            : __('lang_v1.contact_custom_field3');
                        $contact_custom_field4 = !empty($custom_labels['contact']['custom_field_4'])
                            ? $custom_labels['contact']['custom_field_4']
                            : __('lang_v1.contact_custom_field4');
                        $contact_custom_field5 = !empty($custom_labels['contact']['custom_field_5'])
                            ? $custom_labels['contact']['custom_field_5']
                            : __('lang_v1.custom_field', ['number' => 5]);
                        $contact_custom_field6 = !empty($custom_labels['contact']['custom_field_6'])
                            ? $custom_labels['contact']['custom_field_6']
                            : __('lang_v1.custom_field', ['number' => 6]);
                        $contact_custom_field7 = !empty($custom_labels['contact']['custom_field_7'])
                            ? $custom_labels['contact']['custom_field_7']
                            : __('lang_v1.custom_field', ['number' => 7]);
                        $contact_custom_field8 = !empty($custom_labels['contact']['custom_field_8'])
                            ? $custom_labels['contact']['custom_field_8']
                            : __('lang_v1.custom_field', ['number' => 8]);
                        $contact_custom_field9 = !empty($custom_labels['contact']['custom_field_9'])
                            ? $custom_labels['contact']['custom_field_9']
                            : __('lang_v1.custom_field', ['number' => 9]);
                        $contact_custom_field10 = !empty($custom_labels['contact']['custom_field_10'])
                            ? $custom_labels['contact']['custom_field_10']
                            : __('lang_v1.custom_field', ['number' => 10]);
                    @endphp
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('custom_field1', $contact_custom_field1 . ':') !!}
                            {!! Form::text('custom_field1', null, ['class' => 'form-control', 'placeholder' => $contact_custom_field1]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('custom_field2', $contact_custom_field2 . ':') !!}
                            {!! Form::text('custom_field2', null, ['class' => 'form-control', 'placeholder' => $contact_custom_field2]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('custom_field3', $contact_custom_field3 . ':') !!}
                            {!! Form::text('custom_field3', null, ['class' => 'form-control', 'placeholder' => $contact_custom_field3]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('custom_field4', $contact_custom_field4 . ':') !!}
                            {!! Form::text('custom_field4', null, ['class' => 'form-control', 'placeholder' => $contact_custom_field4]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('custom_field5', $contact_custom_field5 . ':') !!}
                            {!! Form::text('custom_field5', null, ['class' => 'form-control', 'placeholder' => $contact_custom_field5]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('custom_field6', $contact_custom_field6 . ':') !!}
                            {!! Form::text('custom_field6', null, ['class' => 'form-control', 'placeholder' => $contact_custom_field6]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('custom_field7', $contact_custom_field7 . ':') !!}
                            {!! Form::text('custom_field7', null, ['class' => 'form-control', 'placeholder' => $contact_custom_field7]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('custom_field8', $contact_custom_field8 . ':') !!}
                            {!! Form::text('custom_field8', null, ['class' => 'form-control', 'placeholder' => $contact_custom_field8]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('custom_field9', $contact_custom_field9 . ':') !!}
                            {!! Form::text('custom_field9', null, ['class' => 'form-control', 'placeholder' => $contact_custom_field9]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('custom_field10', $contact_custom_field10 . ':') !!}
                            {!! Form::text('custom_field10', null, ['class' => 'form-control', 'placeholder' => $contact_custom_field10]) !!}
                        </div>
                    </div>
                    <div class="col-md-12 shipping_addr_div">
                        <hr>
                    </div>
                    <div class="col-md-8 col-md-offset-2 shipping_addr_div mb-10">
                        <strong>{{ __('lang_v1.shipping_address') }}</strong><br>
                        {!! Form::text('shipping_address', null, [
                            'class' => 'form-control',
                            'placeholder' => __('lang_v1.search_address'),
                            'id' => 'shipping_address',
                        ]) !!}
                        <div class="mb-10" id="map"></div>
                    </div>
                    @php
                        $shipping_custom_label_1 = !empty($custom_labels['shipping']['custom_field_1'])
                            ? $custom_labels['shipping']['custom_field_1']
                            : '';

                        $shipping_custom_label_2 = !empty($custom_labels['shipping']['custom_field_2'])
                            ? $custom_labels['shipping']['custom_field_2']
                            : '';

                        $shipping_custom_label_3 = !empty($custom_labels['shipping']['custom_field_3'])
                            ? $custom_labels['shipping']['custom_field_3']
                            : '';

                        $shipping_custom_label_4 = !empty($custom_labels['shipping']['custom_field_4'])
                            ? $custom_labels['shipping']['custom_field_4']
                            : '';

                        $shipping_custom_label_5 = !empty($custom_labels['shipping']['custom_field_5'])
                            ? $custom_labels['shipping']['custom_field_5']
                            : '';
                    @endphp

                    @if (!empty($custom_labels['shipping']['is_custom_field_1_contact_default']) && !empty($shipping_custom_label_1))
                        @php
                            $label_1 = $shipping_custom_label_1 . ':';
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('shipping_custom_field_1', $label_1) !!}
                                {!! Form::text('shipping_custom_field_details[shipping_custom_field_1]', null, [
                                    'class' => 'form-control',
                                    'placeholder' => $shipping_custom_label_1,
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($custom_labels['shipping']['is_custom_field_2_contact_default']) && !empty($shipping_custom_label_2))
                        @php
                            $label_2 = $shipping_custom_label_2 . ':';
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('shipping_custom_field_2', $label_2) !!}
                                {!! Form::text('shipping_custom_field_details[shipping_custom_field_2]', null, [
                                    'class' => 'form-control',
                                    'placeholder' => $shipping_custom_label_2,
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($custom_labels['shipping']['is_custom_field_3_contact_default']) && !empty($shipping_custom_label_3))
                        @php
                            $label_3 = $shipping_custom_label_3 . ':';
                        @endphp
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('shipping_custom_field_3', $label_3) !!}
                                {!! Form::text('shipping_custom_field_details[shipping_custom_field_3]', null, [
                                    'class' => 'form-control',
                                    'placeholder' => $shipping_custom_label_3,
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($custom_labels['shipping']['is_custom_field_4_contact_default']) && !empty($shipping_custom_label_4))
                        @php
                            $label_4 = $shipping_custom_label_4 . ':';
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('shipping_custom_field_4', $label_4) !!}
                                {!! Form::text('shipping_custom_field_details[shipping_custom_field_4]', null, [
                                    'class' => 'form-control',
                                    'placeholder' => $shipping_custom_label_4,
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($custom_labels['shipping']['is_custom_field_5_contact_default']) && !empty($shipping_custom_label_5))
                        @php
                            $label_5 = $shipping_custom_label_5 . ':';
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('shipping_custom_field_5', $label_5) !!}
                                {!! Form::text('shipping_custom_field_details[shipping_custom_field_5]', null, [
                                    'class' => 'form-control',
                                    'placeholder' => $shipping_custom_label_5,
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($common_settings['is_enabled_export']))
                        <div class="col-md-12 mb-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_export" class="form-check-input"
                                    id="is_customer_export">
                                <label class="form-check-label" for="is_customer_export">@lang('lang_v1.is_export')</label>
                            </div>
                        </div>
                        @php
                            $i = 1;
                        @endphp
                        @for ($i; $i <= 6; $i++)
                            <div class="col-md-4 export_div" style="display: none;">
                                <div class="form-group">
                                    {!! Form::label('export_custom_field_' . $i, __('lang_v1.export_custom_field' . $i) . ':') !!}
                                    {!! Form::text('export_custom_field_' . $i, null, [
                                        'class' => 'form-control',
                                        'placeholder' => __('lang_v1.export_custom_field' . $i),
                                    ]) !!}
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
            @include('layouts.partials.module_form_part')
        </div>

        <div class="modal-footer">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white"
                id="showsavecontact">@lang('messages.save')</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white"
                data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->


<script type="text/javascript">
    $(document).ready(function() {
        // Initialize custom dropdowns
        initCustomDropdowns();

        // Chassis number input handler
        $('#vin_num').on('input', function() {
            var chassisNumber = $(this).val().trim();
            if (chassisNumber.length === 17) {
                // Lookup vehicle data from VIN
                performChassisLookup(chassisNumber);

                // Check if VIN belongs to any groups and show toast alerts
                checkVinGroups(chassisNumber);
            }
        });

        // Function to initialize custom dropdowns
        function initCustomDropdowns() {
            // Brand dropdown functionality
            initDropdown('contact-brand');

            // Model dropdown functionality
            initDropdown('contact-model');

            // Search functionality for brand dropdown
            $('#contact-brand-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                filterDropdownOptions('contact-brand', searchTerm);
            });

            // Search functionality for model dropdown
            $('#contact-model-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                filterDropdownOptions('contact-model', searchTerm);
            });
        }

        // Initialize a custom dropdown
        function initDropdown(type) {
            var $dropdown = $('#' + type + '-dropdown');
            var $display = $('#' + type + '-display');
            var $optionsContainer = $('#' + type + '-dropdown .contact-custom-dropdown-options-container');
            var $options = $('#' + type + '-options');
            var $select = type === 'contact-brand' ? $('#gehad_category_id') : $('#gehad_model_id'); // The original select element

            // Toggle dropdown on display click
            $display.on('click', function() {
                $dropdown.toggleClass('open');
                if ($dropdown.hasClass('open')) {
                    $optionsContainer.show();
                } else {
                    $optionsContainer.hide();
                }
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#' + type + '-dropdown').length) {
                    $dropdown.removeClass('open');
                    $optionsContainer.hide();
                }
            });

            // Handle option selection
            $options.on('click', '.contact-custom-dropdown-option', function() {
                var value = $(this).data('value');
                var text = $(this).text();

                // Update the display
                $display.text(text);

                // Store the selected value in a data attribute on the dropdown for easier retrieval
                $dropdown.attr('data-selected-value', value);
                $dropdown.attr('data-selected-text', text);

                // Update the hidden select and ensure it's properly set
                $select.val(value);

                // Trigger change event after ensuring the value is set
                $select.trigger('change');

                console.log(type + ' select value after setting:', $select.val());
                console.log(type + ' dropdown data-selected-value:', $dropdown.attr('data-selected-value'));

                // Close the dropdown
                $dropdown.removeClass('open');
                $optionsContainer.hide();

                // Mark as selected in the custom dropdown
                $('.contact-custom-dropdown-option', $options).removeClass('selected');
                $(this).addClass('selected');

                // If this is the brand dropdown, fetch models
                if (type === 'contact-brand') {
                    refreshModelDropdown(value);
                }
            });

            // Check if there's a pre-selected value
            var selectedValue = $select.val();
            if (selectedValue) {
                var selectedText = $select.find('option:selected').text();
                $display.text(selectedText);
                $dropdown.attr('data-selected-value', selectedValue);
                $dropdown.attr('data-selected-text', selectedText);
                $('#' + type + '-options .contact-custom-dropdown-option[data-value="' + selectedValue + '"]').addClass('selected');
            } else {
                // Set default text based on type
                if (type === 'contact-brand') {
                    $display.text('@lang("car.selectbrand")');
                } else if (type === 'contact-model') {
                    $display.text('@lang("car.selectmodel")');
                }

                // Clear data attributes
                $dropdown.removeAttr('data-selected-value');
                $dropdown.removeAttr('data-selected-text');
            }
        }

        // Filter dropdown options based on search term
        function filterDropdownOptions(type, searchTerm) {
            $('#' + type + '-options .contact-custom-dropdown-option').each(function() {
                var optionText = $(this).text().toLowerCase();
                if (optionText.indexOf(searchTerm) > -1) {
                    $(this).removeClass('hidden');
                } else {
                    $(this).addClass('hidden');
                }
            });
        }

        // Check VIN groups and show color-coded toast notifications (same behavior as vehicle modal)
        function checkVinGroups(vin) {
            var url = '{{ url("vin/vin-groups-by-number") }}';
            $.get(url, { vin: vin })
                .done(function(groups) {
                    if (!groups || groups.length === 0) { return; }
                    groups.forEach(function(g){
                        var title = g.name || 'VIN Group';
                        var body = g.text || ('VIN is in ' + title);
                        toastr.info(body, title);
                        var $toast = $('#toast-container .toast').last();
                        if (g.color) {
                            $toast.css('background-color', g.color);
                            $toast.css('color', '#000');
                        }
                    });
                })
                .fail(function(xhr){
                    console.warn('VIN group check failed:', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.statusText);
                });
        }

        // Update custom dropdown from select value
        function updateDropdownFromSelect(type) {
            var $select = type === 'contact-brand' ? $('#gehad_category_id') : $('#gehad_model_id');
            var value = $select.val();
            var text = $select.find('option:selected').text();

            $('#' + type + '-display').text(text);

            // Mark as selected in the custom dropdown
            $('#' + type + '-options .contact-custom-dropdown-option').removeClass('selected');
            $('#' + type + '-options .contact-custom-dropdown-option[data-value="' + value + '"]').addClass('selected');
        }

        // Function to refresh model dropdown data based on selected brand
        // Optional selectedModelId & selectedVariantId allow preselecting values (used by VIN lookup)
        function refreshModelDropdown(brandId, selectedModelId = null, selectedVariantId = null) {
            if (!brandId) {
                // Clear model dropdown if no brand is selected
                $('#gehad_model_id').empty().append('<option value="">@lang("car.selectmodel")</option>');
                $('#contact-model-options').empty().append('<div class="contact-custom-dropdown-option" data-value="">@lang("car.selectmodel")</div>');
                $('#contact-model-display').text('@lang("car.selectmodel")');

                // Clear brand origin / variant dropdown
                $('#contact_brand_origin_variant_id').empty().append('<option value="">@lang("car.selectcountry")</option>');
                return;
            }

            $.ajax({
                url: "/bookings/get-models/" + brandId,
                type: "GET",
                dataType: "json",
                success: function(response) {
                    // Support both new {models, variants} and legacy [models] shapes
                    var models = response.models || response || [];
                    var variants = response.variants || [];

                    // Clear and rebuild the select dropdown
                    var $dropdown = $('#gehad_model_id');
                    $dropdown.empty();
                    $dropdown.append('<option value="">@lang("car.selectmodel")</option>');

                    // Clear and rebuild the custom dropdown options
                    var $customOptions = $('#contact-model-options');
                    $customOptions.empty();
                    $customOptions.append('<div class="contact-custom-dropdown-option" data-value="">@lang("car.selectmodel")</div>');

                    // Add models to both the select and custom dropdown
                    $.each(models, function(index, model) {
                        if (!model || !model.id || !model.name) {
                            return; // skip invalid entries
                        }

                        var isSelected = selectedModelId && (model.id == selectedModelId);

                        // Add to select dropdown
                        $dropdown.append('<option value="' + model.id + '"' + (isSelected ? ' selected' : '') + '>' + model.name + '</option>');

                        // Add to custom dropdown
                        $customOptions.append('<div class="contact-custom-dropdown-option' + (isSelected ? ' selected' : '') + '" data-value="' + model.id + '">' + model.name + '</div>');
                    });

                    // Populate brand origin / variant dropdown
                    var $countrySelect = $('#contact_brand_origin_variant_id');
                    $countrySelect.empty().append('<option value="">@lang("car.selectcountry")</option>');
                    $.each(variants, function(index, variant) {
                        if (!variant || !variant.id) {
                            return;
                        }
                        var label = variant.label || variant.name;
                        var isSelectedVariant = selectedVariantId && (variant.id == selectedVariantId);
                        $countrySelect.append('<option value="' + variant.id + '"' + (isSelectedVariant ? ' selected' : '') + '>' + label + '</option>');
                    });

                    // Set the model display text
                    if (selectedModelId && $dropdown.find('option[value="' + selectedModelId + '"]').length > 0) {
                        var selectedText = $dropdown.find('option[value="' + selectedModelId + '"]').text();
                        $('#contact-model-display').text(selectedText);
                    } else {
                        $('#contact-model-display').text('@lang("car.selectmodel")');
                    }

                    console.log('Model dropdown refreshed for brand ' + brandId, models, variants, 'selectedModelId:', selectedModelId, 'selectedVariantId:', selectedVariantId);
                },
                error: function(xhr) {
                    console.error('Error refreshing models for brand ' + brandId + ':', xhr);
                    toastr.error('Error fetching models. Please try again.');
                }
            });
        }

        // Add event handler for brand dropdown change
        $('#gehad_category_id').on('change', function() {
            console.log('Brand ID change event triggered.');
            var brandId = $(this).val(); // Get the selected brand ID
            console.log('Brand dropdown changed to:', brandId);

            if (!brandId) {
                // Clear model dropdown if no brand is selected
                $('#gehad_model_id').empty().append('<option value="">@lang("car.selectmodel")</option>');
                $('#contact-model-options').empty().append('<div class="contact-custom-dropdown-option" data-value="">@lang("car.selectmodel")</div>');
                $('#contact-model-display').text('@lang("car.selectmodel")');
                return;
            }

            // Fetch models for the selected brand
            refreshModelDropdown(brandId);
        });

        function performChassisLookup(chassisNumber) {
            // Reset form fields before new lookup
            $('#gehad_category_id').val('');
            $('#gehad_model_id').empty().append('<option value="">@lang("car.selectmodel")</option>');
            $('#contact-model-options').empty().append('<div class="contact-custom-dropdown-option" data-value="">@lang("car.selectmodel")</div>');
            $('#contact-model-display').text('@lang("car.selectmodel")');
            $('#contact-brand-display').text('@lang("car.selectbrand")');

            // Reset brand origin dropdown
            $('#contact_brand_origin_variant_id').empty().append('<option value="">@lang("car.selectcountry")</option>');

            // Reset year, type and color
            $('#vic_year').val('').trigger('change');
            $('#car_type').val('');
            $('#color').val('');

            $.ajax({
                url: "{{ route('booking.lookup_chassis') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    chassis_number: chassisNumber
                },
                dataType: "json",

                success: function(response) {
                    if (response.success && response.data) {
                        var aiData = response.data.ai_analysis || {};

                        // Set the brand (category)
                        if (response.data.brand_id) {
                            // Update the hidden select
                            $('#gehad_category_id').val(response.data.brand_id);
                            $('#category_error').addClass('d-none');

                            // Update the custom dropdown display
                            var brandText = $('#gehad_category_id option:selected').text();
                            $('#contact-brand-display').text(brandText);

                            // Update selected state in custom dropdown
                            $('#contact-brand-options .contact-custom-dropdown-option').removeClass('selected');
                            $('#contact-brand-options .contact-custom-dropdown-option[data-value="' + response.data.brand_id + '"]').addClass('selected');

                            // Refresh models & brand origins with preselected values from AI response
                            refreshModelDropdown(
                                response.data.brand_id,
                                response.data.model_id || null,
                                response.data.variant_id || null
                            );

                            // If no model ID from response, show hint
                            if (!response.data.model_id) {
                                $('#model_error').removeClass('d-none');
                                setTimeout(function() {
                                    $('#model_error').addClass('d-none');
                                }, 5000);
                            }
                        } else {
                            // Show "No matched brand" message
                            $('#category_error').removeClass('d-none');
                            // Hide the message after 5 seconds
                            setTimeout(function() {
                                $('#category_error').addClass('d-none');
                            }, 5000);

                            // Show "No matched model" message (since brand wasn't found)
                            $('#model_error').removeClass('d-none');
                            // Hide the message after 5 seconds
                            setTimeout(function() {
                                $('#model_error').addClass('d-none');
                            }, 5000);
                        }

                        // Set manufacturing year
                        if (response.data.year) {
                            var year = response.data.year.toString();

                            // Check if the year exists in the dropdown
                            var yearExists = false;
                            $('#vic_year option').each(function() {
                                if ($(this).val() === year) {
                                    yearExists = true;
                                    return false; // Break the loop
                                }
                            });

                            if (yearExists) {
                                $('#vic_year').val(year).trigger('change');
                            } else {
                                // Add the new option and select it
                                $('#vic_year').append('<option value="' + year + '">' + year + '</option>');
                                $('#vic_year').val(year).trigger('change');
                            }
                        }

                        // Set color if available
                        if (response.data.color) {
                            $('#color').val(response.data.color);
                        }

                        toastr.success('Vehicle information retrieved successfully');
                    } else {
                        toastr.warning(response.message || 'Could not find complete vehicle information');
                    }
                },
                error: function(xhr) {
                    var errorMessage = 'Error looking up chassis number';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        }

        // Toggle vehicle section visibility
        $('#toggleVehicleBtn').on('click', function() {
            $('#vehicle_div').slideToggle();
            $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
        });

        // Toggle more info section visibility
        $('#toggleMoreInfo').on('click', function() {
            $('#more_div').slideToggle();
            $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
        });

        // Handle contact type radio button toggle (individual vs business)
        $('input[name="contact_type_radio"]').on('change', function() {
            var selectedType = $(this).val();
            if (selectedType === 'business') {
                $('.business').show();
                $('.individual').hide();
                // Remove required attribute from individual fields
                $('.individual input[required]').removeAttr('required');
            } else {
                $('.business').hide();
                $('.individual').show();
                // Add required attribute back to individual fields
                $('.individual input[data-required]').attr('required', 'required');
            }
        });

        // Handle contact type change to show/hide supplier login fields
        $(document).on('change', '#contact_type', function() {
            var selectedType = $(this).val();
            
            // Show fields for supplier or both, hide for others
            if (selectedType === 'supplier' || selectedType === 'both') {
                $('.supplier_login_fields').show();
            } else {
                $('.supplier_login_fields').hide();
                // Clear values when hiding
                $('.supplier_login_fields input').val('');
            }
        });

        // Trigger on page load if type is already selected
        $('#contact_type').trigger('change');
    });
</script>