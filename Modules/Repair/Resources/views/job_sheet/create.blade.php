@extends('layouts.app')

@section('title', __('repair::lang.add_job_sheet'))

@section('content')
@include('repair::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
    	@lang('repair::lang.job_sheet')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold" >@lang('repair::lang.create')</small>
    </h1>
</section>
<section class="content">
    @if(!empty($repair_settings))
        @php
            $product_conf = isset($repair_settings['product_configuration']) ? explode(',', $repair_settings['product_configuration']) : [];

            $defects = isset($repair_settings['problem_reported_by_customer']) ? explode(',', $repair_settings['problem_reported_by_customer']) : [];

            $product_cond = isset($repair_settings['product_condition']) ? explode(',', $repair_settings['product_condition']) : [];
        @endphp
    @else
        @php
            $product_conf = [];
            $defects = [];
            $product_cond = [];
        @endphp
    @endif
    {!! Form::open(['action' => '\Modules\Repair\Http\Controllers\JobSheetController@store', 'id' => 'job_sheet_form', 'method' => 'post', 'files' => true]) !!}
    @includeIf('repair::job_sheet.partials.scurity_modal')

    <!-- Global Error Section -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @component('components.widget')
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::select('booking_id', $bookings->pluck('booking_name', 'id')->toArray(), null, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width: 100%;', 'id' => 'booking-select']) !!}
                @error('booking_id')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('business.business_location') . ':*' )!!}
                        {!! Form::text('location_name', null, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width: 100%;', 'readonly', 'id' => 'location_name']) !!}
                        {!! Form::hidden('location_id', null, ['id' => 'location_id']) !!} <!-- Hidden field for location_id -->
                        @error('location_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('contact_id', __('role.customer') .':*') !!}
                        <div class="input-group">
                            <input type="hidden" id="default_customer_id" value="{{ $walk_in_customer['id'] ?? ''}}" readonly>
                            <input type="hidden" id="default_customer_name" value="{{ $walk_in_customer['name'] ?? ''}}" readonly>
                            <input type="hidden" id="default_customer_balance" value="{{ $walk_in_customer['balance'] ?? ''}}" readonly>

                            {!! Form::text('contact_name', null, ['class' => 'form-control mousetrap', 'id' => 'contact_name', 'placeholder' => __('repair::lang.enter_customer_name_phone'), 'required', 'style' => 'width: 100%;', 'readonly']) !!}
                            {!! Form::hidden('contact_id', null, ['id' => 'contact_id']) !!} <!-- Hidden field for contact_id -->
                            @error('contact_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('service_id', __('repair::lang.services') . ':') !!}
                        <div class="input-group">
                            {!! Form::text('service_name', null, ['class' => 'form-control', 'id' => 'service_name', 'placeholder' => __('repair::lang.services'), 'readonly']) !!}
                            {!! Form::hidden('service_type', null, ['id' => 'service_type']) !!} <!-- Hidden field for service_id -->
                            @error('service_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                </div>
                @endcomponent

                @component('components.widget')
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('device_id', __('repair::lang.brand')) !!}
                            <div class="input-group">
                                {!! Form::text('device_name', null, ['class' => 'form-control', 'id' => 'device_name', 'placeholder' => __('messages.please_select'), 'readonly']) !!}
                                {!! Form::hidden('device_id', null, ['id' => 'device_id']) !!} <!-- Hidden field for device_id -->
                                @error('device_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('device_model_id', __('repair::lang.model')) !!}
                            <div class="input-group">
                                {!! Form::text('device_model_name', null, ['class' => 'form-control', 'id' => 'device_model_name', 'placeholder' => __('messages.please_select'), 'readonly']) !!}
                                {!! Form::hidden('device_model_id', null, ['id' => 'device_model_id']) !!} <!-- Hidden field for device_model_id -->
                                @error('device_model_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('chassie_number', __('repair::lang.chassie_number') . ':') !!}
                            <div class="input-group">
                                {!! Form::text('chassie_number', null, ['class' => 'form-control', 'placeholder' => __('repair::lang.chassie_number'), 'readonly']) !!}
                                @error('chassie_number')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('plate_number', __('repair::lang.plate_number') . ':') !!}
                            <div class="input-group">
                                {!! Form::text('plate_number', null, ['class' => 'form-control', 'placeholder' => __('repair::lang.plate_number'), 'readonly']) !!}
                                @error('plate_number')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('color', __('repair::lang.color') . ':') !!}
                            <div class="input-group">
                                {!! Form::text('color', null, ['class' => 'form-control', 'placeholder' => __('repair::lang.color'), 'readonly']) !!}
                                @error('color')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('km', __('repair::lang.km') . ':') !!}
                            <div class="input-group">
                                {!! Form::number('km', null, ['class' => 'form-control', 'placeholder' => __('repair::lang.km')]) !!}
                                @error('km')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="form-group">
                            {!! Form::label('booking_notes', __('repair::lang.booking_notes') . ':') !!}
                            <div class="input-group">
                                {!! Form::textarea('booking_notes', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('repair::lang.enter_booking_notes'),
                                    'rows' => 3,
                                    'id' => 'booking_notes',
                                    'readonly'
                                ]) !!}
                                @error('booking_notes')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-solid">
                            <div class="box-header with-border">
                                <h5 class="box-title">
                                    @lang('repair::lang.pre_repair_checklist'):
                                    @show_tooltip(__('repair::lang.prechecklist_help_text'))
                                    <small>
                                        @lang('repair::lang.not_applicable_key') = @lang('repair::lang.not_applicable')
                                    </small>
                                </h5>
                            </div>
                            <div class="box-body">
                                <div class="append_checklists"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">

                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('note_list', __('repair::lang.note_list') . ':') !!}
                                {!! Form::select('note_list', $note_list, null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('repair::lang.select_note')
                                ]) !!}
                                @error('note_list')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('Note', __('repair::lang.note') . ':') !!} <br>
                                {!! Form::textarea('Note', null, ['class' => 'form-control', 'rows' => 3]) !!}
                                @error('Note')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                    </div>
                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('workshop', __('repair::lang.workshop') . ':') !!}
                                {!! Form::select('workshop', $workshops, null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('repair::lang.select_workshop')
                                ]) !!}
                                @error('workshops')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('service_staff', __('repair::lang.service_staff') . ':') !!} <br>
                                {!! Form::select('service_staff[]', $technecians, null, [
                                    'class' => 'form-control select2' . ($errors->has('service_staff') ? ' is-invalid' : ''),
                                    'multiple' => 'multiple',
                                    'placeholder' => __('restaurant.select_service_staff'),

                                ]) !!}

                                <!-- Display validation error for service_staff -->
                                @error('service_staff')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    </div>


                @endcomponent

                @component('components.widget')
                <div class="row">




                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('comment_by_ss', __('repair::lang.comment_by_ss') . ':') !!}
                            {!! Form::textarea('comment_by_ss', null, ['class' => 'form-control ', 'rows' => '3']) !!}
                            @error('comment_by_ss')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('estimated_cost', __('repair::lang.estimated_cost') . ':') !!}
                            {!! Form::number('estimated_cost', null, ['class' => 'form-control input_number', 'placeholder' => __('repair::lang.estimated_cost')]) !!}
                            @error('estimated_cost')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="status_id">{{__('sale.status') . ':*'}}</label>
                            <select name="status_id" class="form-control status" id="status_id" required>
                            </select>
                            @error('status_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-sm-4">

                        <div class="form-group">
                            {!! Form::label('images', __('repair::lang.document') . ':') !!}
                            {!! Form::file('images[]', [
                                'id' => 'upload_job_sheet_image',
                                'accept' => 'image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.txt',
                                'multiple'
                            ]) !!}
                            <small>
                                <p class="help-block">
                                    @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                                    @includeIf('components.document_help_text')
                                </p>
                            </small>
                            @error('images')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('due_date', __('lang_v1.due_date') . ':') !!}
                            @show_tooltip(__('repair::lang.due_date_tooltip'))
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('due_date', null, ['class' => 'form-control', 'readonly']) !!}
                                <span class="input-group-addon">
                                    <i class="fas fa-times-circle cursor-pointer clear_due_date"></i>
                                </span>
                            </div>
                            @error('due_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('entry_date', __('repair::lang.entry_date') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('entry_date', null, ['class' => 'form-control', 'id' => 'entry_date', 'readonly']) !!}
                                <span class="input-group-addon">
                                    <i class="fas fa-times-circle cursor-pointer clear_entry_date"></i>
                                </span>
                            </div>
                            @error('entry_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('start_date', __('repair::lang.start_date') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('start_date', null, ['class' => 'form-control', 'id' => 'start_date', 'readonly']) !!}
                                <span class="input-group-addon">
                                    <i class="fas fa-times-circle cursor-pointer clear_start_date"></i>
                                </span>
                            </div>
                            @error('start_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('delivery_date', __('repair::lang.delivery_date') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('delivery_date', null, ['class' => 'form-control', 'id' => 'delivery_date', 'readonly']) !!}
                                <span class="input-group-addon">
                                    <i class="fas fa-times-circle cursor-pointer clear_delivery_date"></i>
                                </span>
                            </div>
                            @error('delivery_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                </div>
                <div class="clearfix"></div>
                <hr>
                <div class="clearfix"></div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>@lang('repair::lang.send_notification')</label><br>
                        <div class="checkbox-inline">
                            <label class="cursor-pointer">
                                <input type="checkbox" name="send_notification[]" value="sms">
                                @lang('repair::lang.sms')
                            </label>
                        </div>
                        <div class="checkbox-inline">
                            <label class="cursor-pointer">
                                <input type="checkbox" name="send_notification[]" value="email">
                                @lang('business.email')
                            </label>
                        </div>
                        @error('send_notification')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>


                <div class="col-sm-12 text-center">
                    <input type="hidden" name="submit_type" id="submit_type">
                    <button type="submit" class="tw-dw-btn tw-dw-btn-success tw-text-white tw-dw-btn-lg submit_button" value="save_and_add_parts" id="save_and_add_parts">
                        @lang('repair::lang.save_and_add_parts')
                    </button>
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-lg submit_button" value="submit" id="save">
                        @lang('messages.save')
                    </button>
                    <button type="submit" class="tw-dw-btn tw-dw-btn-info tw-text-white tw-dw-btn-lg submit_button" value="save_and_upload_docs" id="save_and_upload_docs">
                        @lang('repair::lang.save_and_upload_docs')
                    </button>
                </div>
                </div>
                @endcomponent
                {!! Form::close() !!} <!-- /form close -->
    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>
</section>
<div class="modal fade brands_modal" tabindex="-1" role="dialog"
    	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade" id="device_model_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
<div class="modal fade category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>
@stop
@section('css')
    @include('repair::job_sheet.tagify_css')
@stop
@section('javascript')
    <script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready( function() {
            window.addEventListener("brandAdded", function(evt) {
                var brand = evt.detail;
                if(brand.use_for_repair == 1) {
                    var newBrand = new Option(brand.name, brand.id, true, true);
                    // Append it to the select
                    $("select#brand_id").append(newBrand);
                    $("#brand_id").val(brand.id).trigger('change');
                }

            }, false);

            window.addEventListener("categoryAdded", function(evt) {
                var device = evt.detail;
                if(device.category_type == 'device') {
                    var newDevice = new Option(device.name, device.id, true, true);
                    // Append it to the select
                    $("select#device_id").append(newDevice);
                    $("#device_id").val(device.id).trigger('change');
                }

            }, false);

            $('.submit_button').click( function(){
                $('#submit_type').val($(this).attr('value'));
            });
            $('form#job_sheet_form').validate({
                errorPlacement: function(error, element) {
                    if (element.parent('.iradio_square-blue').length) {
                        error.insertAfter($(".radio_btns"));
                    } else if (element.hasClass('status')) {
                        error.insertAfter(element.parent());
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    form.submit();
                }
            });

            var data = [{
              id: "",
              text: '@lang("messages.please_select")',
              html: '@lang("messages.please_select")',
              is_complete : '0',
            },
            @foreach($repair_statuses as $repair_status)
                {
                id: {{$repair_status->id}},
                is_complete : '{{$repair_status->is_completed_status}}',
                @if(!empty($repair_status->color))
                    text: '<i class="fa fa-circle" aria-hidden="true" style="color: {{$repair_status->color}};"></i> {{$repair_status->name}}',
                    title: '{{$repair_status->name}}'
                @else
                    text: "{{$repair_status->name}}"
                @endif
                },
            @endforeach
            ];

            $("select#status_id").select2({
                data: data,
                escapeMarkup: function(markup) {
                    return markup;
                },
                templateSelection: function (data, container) {
                    $(data.element).attr('data-is_complete', data.is_complete);
                    return data.text;
                }
            });

            @if(!empty($default_status))
                $("select#status_id").val({{$default_status}}).change();
            @endif

            $('#delivery_date').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });

            $(document).on('click', '.clear_delivery_date', function() {
                $('#delivery_date').data("DateTimePicker").clear();
            });

            $(document).on('click', '.clear_due_date', function() {
                $('#due_date').data("DateTimePicker").clear();
            });


        // Initialize datetimepicker for entry_date
        $('#entry_date').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });

        // Clear entry_date when the clear icon is clicked
        $(document).on('click', '.clear_entry_date', function() {
            $('#entry_date').data("DateTimePicker").clear();
        });

        // Initialize datetimepicker for start_date
        $('#start_date').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });

        // Clear start_date when the clear icon is clicked
        $(document).on('click', '.clear_start_date', function() {
            $('#start_date').data("DateTimePicker").clear();
        });
        // Initialize datetimepicker for start_date
        $('#due_date').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });

        // Clear due_date when the clear icon is clicked
        $(document).on('click', '.clear_due_date', function() {
            $('#due_date').data("DateTimePicker").clear();
        });




            var lock = new PatternLock("#pattern_container", {
                onDraw:function(pattern){
                    $('input#security_pattern').val(pattern);
                },
                enableSetPattern: true
            });

            //filter device model id based on brand & device
            $(document).on('change', '#brand_id', function() {


                getModelForDevice();
                getModelRepairChecklists();
            });

            // get models for particular device
            $(document).on('change', '#device_id', function() {
                getModelForDevice();
            });

            $(document).on('change', '#device_model_id', function() {
                getModelRepairChecklists();
            });

            function getModelForDevice() {
                var data = {
                    device_id : $("#device_id").val(),
                    brand_id: $("#brand_id").val()
                };

                $.ajax({
                    method: 'GET',
                    url: '/repair/get-device-models',
                    dataType: 'html',
                    data: data,
                    success: function(result) {
                        $('select#device_model_id').html(result);
                    }
                });
            }

            function getModelRepairChecklists() {
                console.log('here');
                var data = {
                        model_id : $("#device_model_id").val(),
                    };
                $.ajax({
                    method: 'GET',
                    url: '/repair/models-repair-checklist',
                    dataType: 'html',
                    data: data,
                    success: function(result) {
                        $(".append_checklists").html(result);
                    }
                });
            }

            $('input[type=radio][name=service_type]').on('ifChecked', function(){
              if ($(this).val() == 'pick_up' || $(this).val() == 'on_site') {
                $("div.pick_up_onsite_addr").show();
              } else {
                $("div.pick_up_onsite_addr").hide();
              }
            });

            //initialize file input
            $('#upload_job_sheet_image').fileinput({
                showUpload: false,
                showPreview: false,
                browseLabel: LANG.file_browse_label,
                removeLabel: LANG.remove
            });




        });
        $(document).ready(function() {
    // Listen for change event on the select dropdown
    $('#booking-select').on('change', function() {
    var bookingId = $(this).val(); // Get the selected booking ID

    if (bookingId) {
        $.ajax({
            url: '/bookings/get-booking',  // The route to send the request to
            method: 'GET',  // Use GET method to fetch data
            data: {
                booking_id: bookingId  // Send the selected booking ID
            },
            success: function(response) {

                // Access the first object in the response array
                var bookingData = response[0];

                // Set the values of the form fields based on the response
                $('#booking_notes').val(bookingData['booking_note']); // Set booking note

// Display ID and name in the text fields
$('#contact_name').val(bookingData['contact_name']); // Set customer ID and name (display)
$('#contact_id').val(bookingData['contact_id']); // Set customer ID (hidden)

$('#location_name').val(bookingData['location_name']); // Set location ID and name (display)
$('#location_id').val(bookingData['location_id']); // Set location ID (hidden)

$('#service_name').val(bookingData['type_name']); // Set service ID and name (display)
$('#service_type').val(bookingData['service_type_id']); // Set service ID (hidden)

$('#device_name').val(bookingData['brand_name']); // Set device ID and name (display)
$('#device_id').val(bookingData['brand_id']); // Set device ID (hidden)

$('#device_model_name').val(bookingData['device_name']); // Set device model ID and name (display)
$('#device_model_id').val(bookingData['device_model_id']); // Set device model ID (hidden)

$('#chassie_number').val(bookingData['car_chassis_number']); // Set chassie number
$('#plate_number').val(bookingData['car_plate_number']); // Set plate number
$('#color').val(bookingData['car_color']); // Set color


                var data = {
                        model_id : $("#device_model_id").val(),
                    };
                $.ajax({
                    method: 'GET',
                    url: '/repair/models-repair-checklist',
                    dataType: 'html',
                    data: data,
                    success: function(result) {
                        $(".append_checklists").html(result);
                    }
                });
            },
            error: function(xhr, status, error) {
                // Handle errors if any
                console.error(error);
            }
        });
    }
});
});

        $(document).on('click', '#add_device_model', function () {
            var url = $(this).data('href');
            $.ajax({
                method: 'GET',
                url: url,
                dataType: 'html',
                success: function(result) {
                    $('#device_model_modal').html(result).modal('show');
                }
            });
        });



    </script>

    @includeIf('taxonomy.taxonomies_js', ['cat_code_enabled' => false])
@endsection