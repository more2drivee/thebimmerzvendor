@extends('layouts.app')

@section('title', __('essentials::lang.edit_employee'))

@section('content')
@include('essentials::layouts.nav_hrm')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('essentials::lang.edit_employee')</h1>
</section>

<!-- Main content -->
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

    {!! Form::open(['url' => action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'update'], [$user->id]), 'method' => 'PUT', 'id' => 'user_edit_form', 'files' => true]) !!}
    <div class="row">
        <div class="col-md-12">
        @component('components.widget', ['class' => 'box-primary'])
            {{-- Employee Photo --}}
            <div class="col-md-2 text-center">
                <div style="margin-bottom:15px;">
                    <img src="{{ $user->image_url }}" alt="{{ $user->user_full_name }}"
                         id="employee_image_preview"
                         class="tw-rounded-xl tw-shadow-lg"
                         style="width:120px;height:120px;object-fit:cover;">
                </div>
                <div class="form-group">
                    {!! Form::file('user_image', ['id' => 'user_image', 'accept' => 'image/*']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                  {!! Form::label('surname', __('business.prefix') . ':') !!}
                    {!! Form::text('surname', $user->surname, ['class' => 'form-control', 'placeholder' => __('business.prefix_placeholder')]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('first_name', __('business.first_name') . ':*') !!}
                    {!! Form::text('first_name', $user->first_name, ['class' => 'form-control', 'required', 'placeholder' => __('business.first_name')]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('last_name', __('business.last_name') . ':') !!}
                    {!! Form::text('last_name', $user->last_name, ['class' => 'form-control', 'placeholder' => __('business.last_name')]); !!}
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('email', __('business.email') . ':') !!}
                    {!! Form::text('email', $user->email, ['class' => 'form-control', 'placeholder' => __('business.email')]); !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                  <div class="checkbox">
                    <br>
                    <label>
                         {!! Form::checkbox('is_active', $user->status, $user->status == 'active', ['class' => 'input-icheck status']); !!} {{ __('lang_v1.status_for_user') }}
                    </label>
                    @show_tooltip(__('lang_v1.tooltip_enable_user_active'))
                  </div>
                </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <div class="checkbox">
                  <br/>
                  <label>
                       {!! Form::checkbox('is_enable_service_staff_pin', 1, $user->is_enable_service_staff_pin, ['class' => 'input-icheck status', 'id' => 'is_enable_service_staff_pin']); !!} {{ __('lang_v1.enable_service_staff_pin') }}
                  </label>
                  @show_tooltip(__('lang_v1.tooltip_is_enable_service_staff_pin'))
                </div>
              </div>
            </div>
            <div class="col-md-2 service_staff_pin_div {{ $user->is_enable_service_staff_pin == 1 ? '' : 'hide' }}">
              <div class="form-group">
                {!! Form::label('service_staff_pin', __('lang_v1.staff_pin') . ':') !!}
                  {!! Form::password('service_staff_pin', ['class' => 'form-control','placeholder' => __('lang_v1.staff_pin')]); !!}
              </div>
            </div>
        @endcomponent
        </div>

        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.work_info')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('essentials_department_id', __('essentials::lang.department') . ':') !!}
                        {!! Form::select('essentials_department_id', $departments, $user->essentials_department_id, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('essentials_designation_id', __('essentials::lang.designation') . ':') !!}
                        {!! Form::select('essentials_designation_id', $designations, $user->essentials_designation_id, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('lang_v1.primary_work_location') . ':') !!} @show_tooltip(__('lang_v1.tooltip_primary_work_location'))
                        {!! Form::select('location_id', $locations, $user->location_id, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('essentials_salary', __('essentials::lang.salary') . ':') !!}
                        {!! Form::text('essentials_salary', !empty($user->essentials_salary) ? @num_format($user->essentials_salary) : null, ['class' => 'form-control input_number', 'placeholder' => __('essentials::lang.salary')]) !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('essentials_pay_period', __('essentials::lang.pay_cycle') . ':') !!}
                        {!! Form::select('essentials_pay_period', [
                            'month' => __('essentials::lang.month'),
                            'week' => __('essentials::lang.week'),
                        ], $user->essentials_pay_period, ['class' => 'form-control', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('fingerprint_id', __('essentials::lang.fingerprint_id') . ':') !!}
                        {!! Form::text('fingerprint_id', $user->fingerprint_id ?? null, [
                            'class'       => 'form-control',
                            'placeholder' => __('essentials::lang.fingerprint_id_placeholder'),
                        ]) !!}
                        <p class="help-block">
                            <i class="fa fa-info-circle"></i>
                            @lang('essentials::lang.fingerprint_id_help')
                        </p>
                    </div>
                </div>
            @endcomponent
        </div>

        <div class="col-md-12">
            @component('components.widget', ['title' => __('sale.sells')])

            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('cmmsn_percent', __('lang_v1.cmmsn_percent') . ':') !!} @show_tooltip(__('lang_v1.commsn_percent_help'))
                    {!! Form::text('cmmsn_percent', !empty($user->cmmsn_percent) ? @num_format($user->cmmsn_percent) : 0, ['class' => 'form-control input_number', 'placeholder' => __('lang_v1.cmmsn_percent')]); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('max_sales_discount_percent', __('lang_v1.max_sales_discount_percent') . ':') !!} @show_tooltip(__('lang_v1.max_sales_discount_percent_help'))
                    {!! Form::text('max_sales_discount_percent', !is_null($user->max_sales_discount_percent) ? @num_format($user->max_sales_discount_percent) : null, ['class' => 'form-control input_number', 'placeholder' => __('lang_v1.max_sales_discount_percent')]); !!}
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="col-md-4">
                <div class="form-group">
                    <div class="checkbox">
                    <br/>
                      <label>
                        {!! Form::checkbox('selected_contacts', 1,
                        $user->selected_contacts,
                        [ 'class' => 'input-icheck', 'id' => 'selected_contacts']); !!} {{ __('lang_v1.allow_selected_contacts') }}
                      </label>
                      @show_tooltip(__('lang_v1.allow_selected_contacts_tooltip'))
                    </div>
                </div>
            </div>

            <div class="col-sm-4 selected_contacts_div @if(!$user->selected_contacts) hide @endif">
                <div class="form-group">
                  {!! Form::label('user_allowed_contacts', __('lang_v1.selected_contacts') . ':') !!}
                    <div class="form-group">
                      {!! Form::select('selected_contact_ids[]', $contact_access ?? [], !empty($user->contactAccess) ? $user->contactAccess->pluck('id')->toArray() : [], ['class' => 'form-control select2', 'multiple', 'style' => 'width: 100%;', 'id' => 'user_allowed_contacts']); !!}
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>
    @include('user.edit_profile_form_part', [
        'bank_details'       => !empty($user->bank_details) ? json_decode($user->bank_details, true) : null,
        'hide_custom_field_4' => true,
    ])

    {{-- ===================== Emergency & Family Contacts ===================== --}}
    {{-- Stored as JSON in custom_field_4 --}}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['title' => '<i class="fa fa-phone-square"></i> Emergency &amp; Family Contacts'])

                {{-- Hidden field that carries the JSON value --}}
                <input type="hidden" name="custom_field_4" id="emergency_contacts_json" value="{{ old('custom_field_4', $user->custom_field_4?? '') }}">

                <div class="col-md-12" style="margin-bottom:10px;">
                    <p class="text-muted" style="margin-bottom:8px;">
                        <i class="fa fa-info-circle"></i>
                        Add as many emergency contacts (family members, relatives, friends, etc.) as needed. All entries are saved automatically with the form.
                    </p>
                </div>

                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="emergency_contacts_table">
                            <thead>
                                <tr>
                                    <th style="width:28%;">Full Name</th>
                                    <th style="width:20%;">Relationship</th>
                                    <th style="width:25%;">Phone / Mobile</th>
                                    <th style="width:20%;">Alternate Phone</th>
                                    <th style="width:7%; text-align:center;">Remove</th>
                                </tr>
                            </thead>
                            <tbody id="emergency_contacts_tbody">
                                {{-- rows injected by JS on page load --}}
                            </tbody>
                        </table>
                    </div>

                    <button type="button" id="add_emergency_contact_row" class="btn btn-default btn-sm" style="margin-top:4px;">
                        <i class="fa fa-plus"></i> Add Contact
                    </button>
                </div>

            @endcomponent
        </div>
    </div>
    {{-- ===================================================================== --}}

    {{-- Employee Documents Section --}}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.documents')])

                {{-- Upload form --}}
                <div class="col-md-12" style="margin-bottom:15px;">
                    <div class="box box-default box-solid" style="border:1px dashed #aaa; padding:15px; border-radius:6px;">
                        <h4 style="margin-top:0;"><i class="fa fa-upload"></i> @lang('messages.add') @lang('essentials::lang.documents')</h4>

                        <div id="emp_doc_file_rows">
                            <div class="emp-doc-row" style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <input type="file" name="emp_doc_files[]" class="form-control emp-doc-file-input" style="flex:1;" accept="*/*">
                                <input type="text" name="emp_doc_labels[]" class="form-control emp-doc-label-input" style="flex:1;" placeholder="@lang('essentials::lang.document_label')">
                                <button type="button" class="btn btn-danger btn-sm emp-doc-remove-row"><i class="fa fa-times"></i></button>
                            </div>
                        </div>

                        <div style="margin-top:8px;display:flex;gap:8px;">
                            <button type="button" id="emp_doc_add_row" class="btn btn-default btn-sm">
                                <i class="fa fa-plus"></i> @lang('essentials::lang.add_another')
                            </button>
                            <button type="button" id="emp_doc_upload_btn" class="btn btn-primary btn-sm">
                                <i class="fa fa-upload"></i> @lang('messages.save')
                            </button>
                            <span id="emp_doc_uploading" style="display:none;"><i class="fa fa-spinner fa-spin"></i></span>
                        </div>
                    </div>
                </div>

                {{-- Existing documents list --}}
                <div class="col-md-12">
                    <h4><i class="fa fa-file-text-o"></i> @lang('essentials::lang.uploaded_documents')</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="emp_docs_table">
                            <thead>
                                <tr>
                                    <th style="width:60px;">#</th>
                                    <th>@lang('essentials::lang.document_label')</th>
                                    <th>@lang('lang_v1.file')</th>
                                    <th>@lang('essentials::lang.uploaded_on')</th>
                                    <th style="width:80px;">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody id="emp_docs_tbody">
                                @forelse($employee_documents as $doc)
                                    @php
                                        $ext = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION));
                                        $is_image = in_array($ext, ['jpg','jpeg','png','gif','bmp','webp']);
                                    @endphp
                                    <tr id="emp_doc_row_{{ $doc->id }}">
                                        <td>
                                            @if($is_image)
                                                <a href="{{ $doc->display_url }}" target="_blank">
                                                    <img src="{{ $doc->display_url }}" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">
                                                </a>
                                            @else
                                                <a href="{{ $doc->display_url }}" target="_blank" class="btn btn-xs btn-default">
                                                    <i class="fa fa-download"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td>{{ $doc->description ?: basename($doc->file_name) }}</td>
                                        <td>
                                            <a href="{{ $doc->display_url }}" target="_blank" class="text-info" style="word-break:break-all;">
                                                {{ basename($doc->file_name) }}
                                            </a>
                                        </td>
                                        <td>{{ @format_date($doc->created_at) }}</td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-xs btn-danger emp-doc-delete"
                                                data-id="{{ $doc->id }}"
                                                data-url="{{ action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'deleteDocument'], [$user->id, $doc->id]) }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="emp_docs_empty_row">
                                        <td colspan="5" class="text-center text-muted">@lang('essentials::lang.no_documents_found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white" id="submit_user_button">@lang('messages.update')</button>
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
  $(document).ready(function(){
    __page_leave_confirmation('#user_edit_form');

    $('.select2').select2();

    $('#user_dob').datetimepicker({
        format: moment_date_format,
        ignoreReadonly: true,
    });

    // Image preview
    $('#user_image').on('change', function() {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#employee_image_preview').attr('src', e.target.result);
        };
        if (this.files && this.files[0]) {
            reader.readAsDataURL(this.files[0]);
        }
    });

    $('#selected_contacts').on('ifChecked', function(event){
      $('div.selected_contacts_div').removeClass('hide');
    });
    $('#selected_contacts').on('ifUnchecked', function(event){
      $('div.selected_contacts_div').addClass('hide');
    });

    $('#is_enable_service_staff_pin').on('ifChecked', function(event){
      $('div.service_staff_pin_div').removeClass('hide');
    });

    $('#is_enable_service_staff_pin').on('ifUnchecked', function(event){
      $('div.service_staff_pin_div').addClass('hide');
      $('#service_staff_pin').val('');
    });

    $('#user_allowed_contacts').select2({
        ajax: {
            url: '/contacts/customers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    page: params.page,
                    all_contact: true
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        templateResult: function (data) {
            var template = '';
            if (data.supplier_business_name) {
                template += data.supplier_business_name + "<br>";
            }
            template += data.text + "<br>" + LANG.mobile + ": " + data.mobile;

            return  template;
        },
        minimumInputLength: 1,
        escapeMarkup: function(markup) {
            return markup;
        },
    });
  });

  $('form#user_edit_form').validate({
                rules: {
                    first_name: {
                        required: true,
                    },
                    email: {
                        email: true,
                    }
                }
            });

    // -------- Employee Documents --------

    // Add another file row
    $('#emp_doc_add_row').on('click', function() {
        var row = '<div class="emp-doc-row" style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">' +
            '<input type="file" name="emp_doc_files[]" class="form-control emp-doc-file-input" style="flex:1;" accept="*/*">' +
            '<input type="text" name="emp_doc_labels[]" class="form-control emp-doc-label-input" style="flex:1;" placeholder="{{ __("essentials::lang.document_label") }}">' +
            '<button type="button" class="btn btn-danger btn-sm emp-doc-remove-row"><i class="fa fa-times"></i></button>' +
            '</div>';
        $('#emp_doc_file_rows').append(row);
    });

    // Remove a file row
    $(document).on('click', '.emp-doc-remove-row', function() {
        if ($('.emp-doc-row').length > 1) {
            $(this).closest('.emp-doc-row').remove();
        } else {
            $(this).closest('.emp-doc-row').find('input').val('');
        }
    });

    // Upload documents via AJAX
    $('#emp_doc_upload_btn').on('click', function() {
        var formData = new FormData();
        var hasFile = false;

        $('.emp-doc-row').each(function(index) {
            var fileInput = $(this).find('.emp-doc-file-input')[0];
            var labelInput = $(this).find('.emp-doc-label-input').val();
            if (fileInput.files && fileInput.files[0]) {
                formData.append('doc_files[]', fileInput.files[0]);
                formData.append('doc_labels[]', labelInput || fileInput.files[0].name);
                hasFile = true;
            }
        });

        if (!hasFile) {
            toastr.warning('{{ __("essentials::lang.please_select_file") }}');
            return;
        }

        formData.append('_token', '{{ csrf_token() }}');
        $('#emp_doc_upload_btn').prop('disabled', true);
        $('#emp_doc_uploading').show();

        $.ajax({
            url: '{{ action([\Modules\Essentials\Http\Controllers\EmployeeController::class, "storeDocument"], [$user->id]) }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(result) {
                $('#emp_doc_upload_btn').prop('disabled', false);
                $('#emp_doc_uploading').hide();
                if (result.success) {
                    toastr.success('{{ __("lang_v1.success") }}');
                    // Remove empty-row if exists
                    $('#emp_docs_empty_row').remove();
                    // Append new rows
                    $.each(result.docs, function(i, doc) {
                        var preview = '';
                        if (doc.is_image) {
                            preview = '<a href="' + doc.display_url + '" target="_blank"><img src="' + doc.display_url + '" style="width:50px;height:50px;object-fit:cover;border-radius:4px;"></a>';
                        } else {
                            preview = '<a href="' + doc.display_url + '" target="_blank" class="btn btn-xs btn-default"><i class="fa fa-download"></i></a>';
                        }
                        var row = '<tr id="emp_doc_row_' + doc.id + '">' +
                            '<td>' + preview + '</td>' +
                            '<td>' + $('<div>').text(doc.label).html() + '</td>' +
                            '<td><a href="' + doc.display_url + '" target="_blank" class="text-info">' + $('<div>').text(doc.file_name.split('/').pop()).html() + '</a></td>' +
                            '<td>{{ \Carbon\Carbon::now()->format(config("constants.display_date_format", "d/m/Y")) }}</td>' +
                            '<td><button type="button" class="btn btn-xs btn-danger emp-doc-delete" data-id="' + doc.id + '" ' +
                            'data-url="{{ url("hrm/employees/" . $user->id . "/documents/") }}/' + doc.id + '">' +
                            '<i class="fa fa-trash"></i></button></td>' +
                            '</tr>';
                        $('#emp_docs_tbody').append(row);
                    });
                    // Reset form rows
                    $('#emp_doc_file_rows').html(
                        '<div class="emp-doc-row" style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">' +
                        '<input type="file" name="emp_doc_files[]" class="form-control emp-doc-file-input" style="flex:1;" accept="*/*">' +
                        '<input type="text" name="emp_doc_labels[]" class="form-control emp-doc-label-input" style="flex:1;" placeholder="{{ __("essentials::lang.document_label") }}">' +
                        '<button type="button" class="btn btn-danger btn-sm emp-doc-remove-row"><i class="fa fa-times"></i></button>' +
                        '</div>'
                    );
                } else {
                    toastr.error(result.msg || '{{ __("messages.something_went_wrong") }}');
                }
            },
            error: function() {
                $('#emp_doc_upload_btn').prop('disabled', false);
                $('#emp_doc_uploading').hide();
                toastr.error('{{ __("messages.something_went_wrong") }}');
            }
        });
    });

    // Delete document
    $(document).on('click', '.emp-doc-delete', function() {
        var btn = $(this);
        var url = btn.data('url');
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function(willDelete) {
            if (willDelete) {
                $.ajax({
                    method: 'DELETE',
                    url: url,
                    data: { _token: '{{ csrf_token() }}' },
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            var rowId = 'emp_doc_row_' + btn.data('id');
                            $('#' + rowId).fadeOut(300, function() {
                                $(this).remove();
                                if ($('#emp_docs_tbody tr').length === 0) {
                                    $('#emp_docs_tbody').append('<tr id="emp_docs_empty_row"><td colspan="5" class="text-center text-muted">{{ __("essentials::lang.no_documents_found") }}</td></tr>');
                                }
                            });
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            }
        });
    });
    // =====================================================================
    // Emergency & Family Contacts  (stored as JSON in custom_field_4)
    // =====================================================================

    var RELATIONSHIP_OPTIONS = [
        { value: '',           label: '-- Select --' },
        { value: 'family',     label: 'Family' },
        { value: 'spouse',     label: 'Spouse' },
        { value: 'parent',     label: 'Parent' },
        { value: 'sibling',    label: 'Sibling' },
        { value: 'child',      label: 'Child' },
        { value: 'relative',   label: 'Relative' },
        { value: 'friend',     label: 'Friend' },
        { value: 'colleague',  label: 'Colleague' },
        { value: 'other',      label: 'Other' },
    ];

    function buildRelationshipSelect(selected) {
        var html = '<select class="form-control ec-relationship">';
        $.each(RELATIONSHIP_OPTIONS, function(_, opt) {
            var sel = (opt.value === selected) ? ' selected' : '';
            html += '<option value="' + opt.value + '"' + sel + '>' + opt.label + '</option>';
        });
        html += '</select>';
        return html;
    }

    function buildEcRow(name, relationship, phone, alt_phone) {
        name        = name        || '';
        relationship= relationship|| '';
        phone       = phone       || '';
        alt_phone   = alt_phone   || '';

        return '<tr class="ec-row">' +
            '<td><input type="text" class="form-control ec-name"     value="' + $('<div>').text(name).html()      + '" placeholder="Full name"></td>' +
            '<td>' + buildRelationshipSelect(relationship) + '</td>' +
            '<td><input type="text" class="form-control ec-phone"    value="' + $('<div>').text(phone).html()     + '" placeholder="Primary phone"></td>' +
            '<td><input type="text" class="form-control ec-altphone" value="' + $('<div>').text(alt_phone).html() + '" placeholder="Alternate phone"></td>' +
            '<td style="text-align:center;">' +
                '<button type="button" class="btn btn-danger btn-xs ec-remove-row"><i class="fa fa-times"></i></button>' +
            '</td>' +
        '</tr>';
    }

    function serializeEmergencyContacts() {
        var contacts = [];
        $('#emergency_contacts_tbody .ec-row').each(function() {
            var name     = $(this).find('.ec-name').val().trim();
            var rel      = $(this).find('.ec-relationship').val();
            var phone    = $(this).find('.ec-phone').val().trim();
            var altPhone = $(this).find('.ec-altphone').val().trim();
            // only persist rows that have at least a name or phone
            if (name || phone) {
                contacts.push({ name: name, relationship: rel, phone: phone, alt_phone: altPhone });
            }
        });
        $('#emergency_contacts_json').val(JSON.stringify(contacts));
    }

    function loadEmergencyContacts() {
        var raw = $('#emergency_contacts_json').val();
        var contacts = [];
        try { contacts = JSON.parse(raw); } catch(e) {}
        if (!Array.isArray(contacts) || contacts.length === 0) {
            // start with one blank row
            $('#emergency_contacts_tbody').append(buildEcRow('','','',''));
        } else {
            $.each(contacts, function(_, c) {
                $('#emergency_contacts_tbody').append(buildEcRow(c.name, c.relationship, c.phone, c.alt_phone));
            });
        }
    }

    // Load existing contacts on page ready
    loadEmergencyContacts();

    // Add a new blank row
    $('#add_emergency_contact_row').on('click', function() {
        $('#emergency_contacts_tbody').append(buildEcRow('','','',''));
    });

    // Remove a row (keep at least one)
    $(document).on('click', '.ec-remove-row', function() {
        if ($('#emergency_contacts_tbody .ec-row').length > 1) {
            $(this).closest('tr').remove();
        } else {
            $(this).closest('tr').find('input').val('');
            $(this).closest('tr').find('select').val('');
        }
    });

    // Serialize before the main form submits
    $('form#user_edit_form').on('submit', function() {
        serializeEmergencyContacts();
    });

</script>
@endsection
