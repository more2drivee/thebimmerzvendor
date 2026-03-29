@extends('layouts.app')

@section('title', __('repair::lang.add_job_sheet'))

@section('content')
@include('repair::layouts.nav')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
    	@lang('repair::lang.job_sheet')
        (<code>{{$job_sheet->job_sheet_no}}</code>)
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

    {!! Form::open(['url' => action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'update'], [$job_sheet->id]), 'method' => 'put', 'id' => 'edit_job_sheet_form', 'files' => true]) !!}
    @csrf <!-- Add CSRF token here -->
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
                {!! Form::text('booking_id', $bookings[0]->booking_name, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width: 100%;', 'id' => '', 'readonly']) !!}
                {!! Form::hidden('booking_id', $bookings[0]->id, ['id' => 'booking_id']) !!}
                @error('booking_id')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('location_id', __('business.business_location') . ':*') !!}
                {!! Form::text('location_name', $bookings[0]->location_name, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width: 100%;', 'readonly', 'id' => 'location_name']) !!}
                {!! Form::hidden('location_id', $bookings[0]->location_id, ['id' => 'location_id']) !!}
                @error('location_id')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('contact_id', __('role.customer') . ':*') !!}
                                <div class="input-group">
                                    <input type="hidden" id="default_customer_id" value="{{ $bookings[0]->contact_id }}" readonly>
                                    <input type="hidden" id="default_customer_name" value="{{ $bookings[0]->contact_name }}" readonly>
                                    {!! Form::text('contact_name', $bookings[0]->contact_name, ['class' => 'form-control mousetrap', 'id' => 'contact_name', 'placeholder' => __('repair::lang.enter_customer_name_phone'), 'required', 'style' => 'width: 100%;', 'readonly']) !!}
                                    {!! Form::hidden('contact_id', $bookings[0]->contact_id, ['id' => 'contact_id']) !!}
                                    @error('contact_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('service_id', __('repair::lang.services') . ':') !!}
                                <div class="input-group">
                                    {!! Form::text('service_name', $bookings[0]->type_name, ['class' => 'form-control', 'id' => 'service_name', 'placeholder' => __('repair::lang.services'), 'readonly']) !!}
                                    {!! Form::hidden('service_type', $bookings[0]->service_type_id, ['id' => 'service_type']) !!}
                                    @error('service_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('device_id', __('repair::lang.device') . ':') !!}
                                <div class="input-group">
                                    {!! Form::text('device_name', $bookings[0]->device_name, ['class' => 'form-control', 'id' => 'device_name', 'placeholder' => __('messages.please_select'), 'readonly']) !!}
                                    {!! Form::hidden('device_id', $bookings[0]->device_id, ['id' => 'device_id']) !!}
                                    @error('device_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('device_model_id', __('repair::lang.device_model') . ':') !!}
                                <div class="input-group">
                                    {!! Form::text('device_model_name', $bookings[0]->device_name, ['class' => 'form-control', 'id' => 'device_model_name', 'placeholder' => __('messages.please_select'), 'readonly']) !!}
                                    {!! Form::hidden('device_model_id', $bookings[0]->device_model_id, ['id' => 'device_model_id']) !!}
                                    @error('device_model_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('chassie_number', __('repair::lang.chassie_number') . ':') !!}
                                <div class="input-group">
                                    {!! Form::text('chassie_number', $bookings[0]->car_chassis_number, ['class' => 'form-control', 'placeholder' => __('repair::lang.chassie_number'), 'readonly']) !!}
                                    @error('chassie_number')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('plate_number', __('repair::lang.plate_number') . ':') !!}
                                <div class="input-group">
                                    {!! Form::text('plate_number', $bookings[0]->car_plate_number, ['class' => 'form-control', 'placeholder' => __('repair::lang.plate_number'), 'readonly']) !!}
                                    @error('plate_number')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('color', __('repair::lang.color') . ':') !!}
                                <div class="input-group">
                                    {!! Form::text('color', $bookings[0]->car_color, ['class' => 'form-control', 'placeholder' => __('repair::lang.color'), 'readonly']) !!}
                                    @error('color')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('km', __('repair::lang.km') . ':') !!}
                                <div class="input-group">
                                    {!! Form::number('km', $job_sheet->km ?? null, ['class' => 'form-control', 'placeholder' => __('repair::lang.km')]) !!}
                                    @error('km')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('booking_notes', __('repair::lang.booking_notes') . ':') !!}
                                <div class="input-group">
                                    {!! Form::textarea('booking_notes', $bookings[0]->booking_note, [
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
                                {!! Form::label('maintenance_note_title', __('repair::lang.note_title') . ':') !!}
                                {!! Form::select('maintenance_note[0][title]', $noteTitles, null, ['class' => 'form-control', 'placeholder' => __('repair::lang.select_note_title')]) !!}
                                @error('maintenance_note.*.title')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('maintenance_note_content', __('repair::lang.note_content') . ':') !!}
                                {!! Form::textarea('maintenance_note[0][content]', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('repair::lang.enter_note_content')]) !!}
                                @error('maintenance_note.*.content')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="col-md-12">
                            <div class="form-group">

                                {!! Form::label('workshop', __('repair::lang.workshop') . ':') !!}
                                {!! Form::select('workshop', $workshops, $job_sheet->workshop_id, [
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
                                {!! Form::label('service_staff', __('repair::lang.assign_service_staff') . ':') !!}
                                {!! Form::select('service_staff[]', $technecians, json_decode($job_sheet->service_staff), [
                                    'class' => 'form-control select2',
                                    'multiple' => 'multiple',
                                    'placeholder' => __('repair::lang.select_service_staff')
                                ]) !!}
                                @error('service_staff')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('comment_by_ss', __('repair::lang.comment_by_ss') . ':') !!}
                                {!! Form::textarea('comment_by_ss', $job_sheet->comment_by_ss, ['class' => 'form-control', 'rows' => 3]) !!}
                                @error('comment_by_ss')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('estimated_cost', __('repair::lang.estimated_cost') . ':') !!}
                                {!! Form::number('estimated_cost', $job_sheet->estimated_cost, ['class' => 'form-control input_number', 'placeholder' => __('repair::lang.estimated_cost')]) !!}
                                @error('estimated_cost')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="status_id">{{__('sale.status') . ':*'}}</label>
                                <select name="status_id" class="form-control status" id="status_id" required>
                                    <!-- Populate status options here -->
                                </select>
                                @error('status_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('images', __('repair::lang.document') . ':') !!}
                                {!! Form::file('images[]', ['id' => 'upload_job_sheet_image', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types'))), 'multiple']) !!}
                                <small>
                                    <p class="help-block">
                                        @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                                        @includeIf('components.document_help_text')
                                    </p>
                                </small>
                                @error('images')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <!-- Button to trigger the media/notes modal -->
                                <button type="button" class="btn btn-info mt-2" data-toggle="modal" data-target="#jobSheetDetailsModal">
                                    @lang('repair::lang.view_media_and_notes')
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('delivery_date', __('lang_v1.due_date') . ':') !!}
                                @show_tooltip(__('repair::lang.delivery_date_tooltip'))
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    {!! Form::text('delivery_date', !empty($job_sheet->delivery_date) ? @format_datetime($job_sheet->delivery_date) : null, ['class' => 'form-control', 'readonly']) !!}
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
                    <div class="col-md-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('entry_date', __('repair::lang.entry_date') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    {!! Form::text('entry_date', !empty($job_sheet->entry_date) ? @format_datetime($job_sheet->entry_date) : null, ['class' => 'form-control', 'id' => 'entry_date', 'readonly']) !!}
                                    <span class="input-group-addon">
                                        <i class="fas fa-times-circle cursor-pointer clear_entry_date"></i>
                                    </span>
                                </div>
                                @error('entry_date')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('start_date', __('repair::lang.start_date') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    {!! Form::text('start_date', !empty($job_sheet->start_date) ? @format_datetime($job_sheet->start_date) : null, ['class' => 'form-control', 'id' => 'start_date', 'readonly']) !!}
                                    <span class="input-group-addon">
                                        <i class="fas fa-times-circle cursor-pointer clear_start_date"></i>
                                    </span>
                                </div>
                                @error('start_date')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>@lang('repair::lang.send_notification')</label><br>
                                <div class="checkbox-inline">
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="send_notification[]" value="sms" @if(in_array('sms', $job_sheet->send_notification ?? [])) checked @endif>
                                        @lang('repair::lang.sms')
                                    </label>
                                </div>
                                <div class="checkbox-inline">
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="send_notification[]" value="email" @if(in_array('email', $job_sheet->send_notification ?? [])) checked @endif>
                                        @lang('business.email')
                                    </label>
                                </div>
                                @error('send_notification')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
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
        {!! Form::close() !!}
    <!-- Modal for viewing media, notes, and comments -->
<div class="modal fade" id="jobSheetDetailsModal" tabindex="-1" role="dialog" aria-labelledby="jobSheetDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jobSheetDetailsModalLabel">Job Sheet Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Tabs for Media, Notes, and Comments -->
                <ul class="nav nav-tabs" id="jobSheetTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="media-tab" data-toggle="tab" href="#media" role="tab" aria-controls="media" aria-selected="true">Media</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="notes-tab" data-toggle="tab" href="#notes" role="tab" aria-controls="notes" aria-selected="false">Notes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="comments-tab" data-toggle="tab" href="#comments" role="tab" aria-controls="comments" aria-selected="false">Comments</a>
                    </li>
                </ul>

                <div class="tab-content" id="jobSheetTabContent">
                    <!-- Media Tab -->
                    <div class="tab-pane fade show active" id="media" role="tabpanel" aria-labelledby="media-tab">
                        @if(!empty($job_sheet->media) && $job_sheet->media->isNotEmpty())
                            <div class="row">
                                @foreach($job_sheet->media as $media)
                                    @php
                                        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
                                    @endphp
                                    <div class="col-md-4">
                                        @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']))
                                            <img src="{{ asset('storage/' . $media->file_name) }}" class="img-fluid" alt="Job Sheet Media" style="max-height: 200px;">
                                        @elseif(strtolower($extension) == 'pdf')
                                            <a href="{{ asset('storage/' . $media->file_name) }}" target="_blank">View PDF</a>
                                        @else
                                            <a href="{{ asset('storage/' . $media->file_name) }}" download>{{ $media->file_name }}</a>
                                        @endif
                                        <p>{{ basename($media->file_name) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p>No media uploaded yet.</p>
                        @endif
                    </div>

                    <!-- Notes Tab -->
                    <div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                        @if(!empty($job_sheet->Note))
                            <p>{{ $job_sheet->Note }}</p>
                        @else
                            <p>No notes added yet.</p>
                        @endif
                    </div>

                    <!-- Comments Tab -->
                    <div class="tab-pane fade" id="comments" role="tabpanel" aria-labelledby="comments-tab">
                        @if(!empty($job_sheet->comment_by_ss))
                            <p>{{ $job_sheet->comment_by_ss }}</p>
                        @else
                            <p>No comments added yet.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>
</section>

@stop
@section('css')
    @include('repair::job_sheet.tagify_css')
@stop
@section('javascript')
    <script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready( function() {
            $('.submit_button').click( function(){
                $('#submit_type').val($(this).attr('value'));
            });
            $('form#edit_job_sheet_form').validate({
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

            @if(!empty($job_sheet->status_id))
                $("select#status_id").val({{$job_sheet->status_id}}).change();
            @elseif(!empty($default_status))
                $("select#status_id").val({{$default_status}}).change();
            @endif

            // $('#delivery_date').datetimepicker({
            //     format: moment_date_format + ' ' + moment_time_format,
            //     ignoreReadonly: true,
            // });

            // $(document).on('click', '.clear_delivery_date', function() {
            //     $('#delivery_date').data("DateTimePicker").clear();
            // });
            $('#delivery_date').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });

            $(document).on('click', '.clear_delivery_date', function() {
                $('#delivery_date').data("DateTimePicker").clear();
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


            var lock = new PatternLock("#pattern_container", {
                onDraw:function(pattern){
                    $('input#security_pattern').val(pattern);
                },
                enableSetPattern: true
            });

            @if(!empty($job_sheet->security_pattern))
                lock.setPattern("{{$job_sheet->security_pattern}}");
            @endif

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
                var data = {
                        model_id : $("#device_model_id").val(),
                        job_sheet_id : $("#job_sheet_id").val()
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

            getModelRepairChecklists();

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



            //TODO:Uncomment the below code

            // function toggleSubmitButton () {
            //     if ($('select#status_id').find(':selected').data('is_complete')) {
            //         $("#save_and_add_parts").attr('disabled', false);
            //         $("#save_and_upload_docs").attr('disabled', true);
            //     } else {
            //         $("#save_and_add_parts").attr('disabled', true);
            //         $("#save_and_upload_docs").attr('disabled', false);
            //     }
            // }

            // $("select#status_id").on('change', function () {
            //     toggleSubmitButton();
            // });

            // toggleSubmitButton();
        });
    </script>
@endsection