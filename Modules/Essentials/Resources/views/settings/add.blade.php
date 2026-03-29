@extends('layouts.app')
@section('title', __('essentials::lang.essentials_n_hrm_settings'))

@section('content')
    @include('essentials::layouts.nav_hrm')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('essentials::lang.essentials_n_hrm_settings')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        {!! Form::open([
            'action' => '\Modules\Essentials\Http\Controllers\EssentialsSettingsController@update',
            'method' => 'post',
            'id' => 'essentials_settings_form',
        ]) !!}
        @component('components.widget', ['class' => 'pos-tab-container'])
            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 pos-tab-menu tw-rounded-lg">
                <div class="list-group">
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base active">@lang('essentials::lang.leave')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('essentials::lang.payroll')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('essentials::lang.attendance')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('essentials::lang.sales_target')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base">@lang('essentials::lang.essentials')</a>
                    @can('essentials.crud_leave_type')
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base settings-data-tab" data-table="leave_type">@lang('essentials::lang.leave_type')</a>
                    @endcan
                    @can('essentials.crud_department')
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base settings-data-tab" data-table="departments">@lang('essentials::lang.departments')</a>
                    @endcan
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base settings-data-tab" data-table="designations">@lang('essentials::lang.designations')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base settings-data-tab" data-table="holidays">@lang('essentials::lang.holiday')</a>
                </div>
            </div>

            <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                {{-- 5 settings partial tabs --}}
                @include('essentials::settings.partials.leave_settings')
                @include('essentials::settings.partials.payroll_settings')
                @include('essentials::settings.partials.attendance_settings')
                @include('essentials::settings.partials.sales_target_settings')
                @include('essentials::settings.partials.essentials_settings')

                {{-- Leave Type tab --}}
                <div class="pos-tab-content" id="pos-tab-leave_type">
                    <div class="row" style="margin-bottom:10px;">
                        <div class="col-xs-12 text-right">
                            <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full"
                                data-toggle="modal" data-target="#add_leave_type_modal">
                                <i class="fa fa-plus"></i> @lang('messages.add')
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="leave_type_table">
                            <thead>
                                <tr>
                                    <th>@lang('essentials::lang.leave_type')</th>
                                    <th>@lang('essentials::lang.max_leave_count')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                {{-- Departments tab --}}
                <div class="pos-tab-content" id="pos-tab-departments">
                    <div class="row" style="margin-bottom:10px;">
                        <div class="col-xs-12 text-right">
                            <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal"
                                data-href="{{action([\App\Http\Controllers\TaxonomyController::class, 'create']) . '?type=hrm_department'}}"
                                data-container="#taxonomy_modal">
                                <i class="fa fa-plus"></i> @lang('messages.add')
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="department_table">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.name')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                {{-- Designations tab --}}
                <div class="pos-tab-content" id="pos-tab-designations">
                    <div class="row" style="margin-bottom:10px;">
                        <div class="col-xs-12 text-right">
                            <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal"
                                data-href="{{action([\App\Http\Controllers\TaxonomyController::class, 'create']) . '?type=hrm_designation'}}"
                                data-container="#taxonomy_modal">
                                <i class="fa fa-plus"></i> @lang('messages.add')
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="designation_table">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.name')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                {{-- Holidays tab --}}
                <div class="pos-tab-content" id="pos-tab-holidays">
                    <div class="row" style="margin-bottom:10px;">
                        <div class="col-xs-3">
                            <div class="form-group">
                                {!! Form::label('holiday_location_id', __('purchase.business_location') . ':') !!}
                                {!! Form::select('holiday_location_id', $locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'holiday_location_id']); !!}
                            </div>
                        </div>
                        <div class="col-xs-3">
                            <div class="form-group">
                                {!! Form::label('holiday_filter_date_range', __('report.date_range') . ':') !!}
                                {!! Form::text('holiday_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly', 'id' => 'holiday_filter_date_range']); !!}
                            </div>
                        </div>
                        @if($is_admin)
                        <div class="col-xs-6 text-right" style="padding-top:25px;">
                            <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal"
                                data-href="{{action([\Modules\Essentials\Http\Controllers\EssentialsHolidayController::class, 'create'])}}"
                                data-container="#add_holiday_modal">
                                <i class="fa fa-plus"></i> @lang('messages.add')
                            </button>
                        </div>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="holidays_table">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.name')</th>
                                    <th>@lang('lang_v1.date')</th>
                                    <th>@lang('business.business_location')</th>
                                    <th>@lang('brand.note')</th>
                                    @if($is_admin)
                                        <th>@lang('messages.action')</th>
                                    @endif
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        @endcomponent
        <div class="row" id="settings_save_row">
            <div class="col-xs-12">
                <div class="form-group pull-right">
                    {{ Form::submit(__('messages.update'), ['class' => 'tw-dw-btn tw-dw-btn-error tw-text-white']) }}
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </section>

    {{-- Modals --}}
    @include('essentials::leave_type.create')
    <div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade" id="add_holiday_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade" id="taxonomy_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            tinymce.init({ selector: 'textarea#leave_instructions' });
            $('#essentials_settings_form').validate({ ignore: [] });

            // ===== Lazy-load DataTables for data tabs =====
            var leave_type_table = null;
            var department_table  = null;
            var designation_table = null;
            var holidays_table    = null;
            var settingsIsAdmin = {{ $is_admin ? 'true' : 'false' }};

            // Data tab names in sidebar order (matching their index in the list-group)
            // Indices 0-4 are the settings partials; 5+ are data tabs
            $('div.pos-tab-menu>div.list-group>a.settings-data-tab').on('click', function() {
                var tableKey = $(this).data('table');

                if (tableKey === 'leave_type' && !leave_type_table) {
                    leave_type_table = $('#leave_type_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: "{{action([\Modules\Essentials\Http\Controllers\EssentialsLeaveTypeController::class, 'index'])}}",
                        columnDefs: [{ targets: 2, orderable: false, searchable: false }],
                    });
                }

                if (tableKey === 'departments' && !department_table) {
                    department_table = $('#department_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{action([\App\Http\Controllers\TaxonomyController::class, 'index'])}}",
                            data: function(d) { d.type = 'hrm_department'; }
                        },
                        columns: [
                            { data: 'name', name: 'name' },
                            { data: 'action', name: 'action', orderable: false, searchable: false },
                        ],
                    });
                }

                if (tableKey === 'designations' && !designation_table) {
                    designation_table = $('#designation_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{action([\App\Http\Controllers\TaxonomyController::class, 'index'])}}",
                            data: function(d) { d.type = 'hrm_designation'; }
                        },
                        columns: [
                            { data: 'name', name: 'name' },
                            { data: 'action', name: 'action', orderable: false, searchable: false },
                        ],
                    });
                }

                if (tableKey === 'holidays' && !holidays_table) {
                    // Initialize daterangepicker BEFORE DataTable to avoid undefined error on first load
                    $('#holiday_filter_date_range').daterangepicker(
                        dateRangeSettings,
                        function(start, end) {
                            $('#holiday_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                        }
                    );
                    $('#holiday_filter_date_range').on('cancel.daterangepicker', function() {
                        $('#holiday_filter_date_range').val('');
                        if (holidays_table) holidays_table.ajax.reload();
                    });

                    holidays_table = $('#holidays_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{action([\Modules\Essentials\Http\Controllers\EssentialsHolidayController::class, 'index'])}}",
                            data: function(d) {
                                d.location_id = $('#holiday_location_id').val();
                                var dr = $('#holiday_filter_date_range').data('daterangepicker');
                                if (dr && $('#holiday_filter_date_range').val()) {
                                    d.start_date = dr.startDate.format('YYYY-MM-DD');
                                    d.end_date   = dr.endDate.format('YYYY-MM-DD');
                                }
                            }
                        },
                        columnDefs: settingsIsAdmin ? [{ targets: 4, orderable: false, searchable: false }] : [],
                        columns: (function() {
                            var cols = [
                                { data: 'name', name: 'essentials_holidays.name' },
                                { data: 'start_date', name: 'start_date' },
                                { data: 'location', name: 'bl.name' },
                                { data: 'note', name: 'note' },
                            ];
                            if (settingsIsAdmin) { cols.push({ data: 'action', name: 'action', orderable: false, searchable: false }); }
                            return cols;
                        })(),
                    });
                }

                // Hide save button for data tabs, show for settings tabs
                $('#settings_save_row').hide();
            });

            // Show save button when a settings partial tab is clicked
            $('div.pos-tab-menu>div.list-group>a:not(.settings-data-tab)').on('click', function() {
                $('#settings_save_row').show();
            });

            // Filters for holidays
            $(document).on('change', '#holiday_location_id, #holiday_filter_date_range', function() {
                if (holidays_table) holidays_table.ajax.reload();
            });

            // ===== Leave Type form =====
            $(document).on('submit', 'form#add_leave_type_form, form#edit_leave_type_form', function(e) {
                e.preventDefault();
                $.ajax({
                    method: $(this).attr('method'),
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(result) {
                        if (result.success == true) {
                            $('div#add_leave_type_modal').modal('hide');
                            $('.view_modal').modal('hide');
                            toastr.success(result.msg);
                            if (leave_type_table) leave_type_table.ajax.reload();
                            $('form#add_leave_type_form')[0].reset();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });

            // ===== Holiday modal =====
            $('#add_holiday_modal').on('shown.bs.modal', function() {
                $('#add_holiday_modal .select2').select2();
                $('form#add_holiday_form #start_date, form#add_holiday_form #end_date').datepicker({ autoclose: true });
            });

            $(document).on('submit', 'form#add_holiday_form', function(e) {
                e.preventDefault();
                $(this).find('button[type="submit"]').attr('disabled', true);
                $.ajax({
                    method: $(this).attr('method'),
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(result) {
                        if (result.success == true) {
                            $('div#add_holiday_modal').modal('hide');
                            toastr.success(result.msg);
                            if (holidays_table) holidays_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });

            $(document).on('click', 'button.delete-holiday', function() {
                var href = $(this).data('href');
                swal({ title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true })
                    .then(function(willDelete) {
                        if (willDelete) {
                            $.ajax({
                                method: 'DELETE', url: href, dataType: 'json',
                                success: function(result) {
                                    if (result.success == true) {
                                        toastr.success(result.msg);
                                        if (holidays_table) holidays_table.ajax.reload();
                                    } else {
                                        toastr.error(result.msg);
                                    }
                                },
                            });
                        }
                    });
            });

            // Taxonomy (department) reload after modal submit
            $(document).on('shown.bs.modal', '#taxonomy_modal', function() {
                $('#taxonomy_modal .select2').select2();
            });

            // ===== Taxonomy modal (department/designation) =====
            $(document).on('click', 'button.edit_category_button', function(e) {
                e.preventDefault();
                $('#taxonomy_modal').load($(this).data('href'), function() {
                    $(this).modal('show');
                });
            });

            $(document).on('submit', 'form#category_add_form', function(e) {
                e.preventDefault();
                var form = $(this);
                var formData = new FormData(this);

                $.ajax({
                    method: 'POST',
                    url: form.attr('action'),
                    dataType: 'json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        __disable_submit_button(form.find('button[type="submit"]'));
                    },
                    success: function(result) {
                        if (result.success === true) {
                            $('#taxonomy_modal').modal('hide');
                            toastr.success(result.msg);
                            if (department_table) department_table.ajax.reload();
                            if (designation_table) designation_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function() {
                        toastr.error(LANG.something_went_wrong || 'Something went wrong');
                    },
                    complete: function() {
                        form.find('button[type="submit"]').prop('disabled', false);
                    }
                });
            });

            $(document).on('submit', 'form#category_edit_form', function(e) {
                e.preventDefault();
                var form = $(this);
                var formData = new FormData(this);

                $.ajax({
                    method: 'POST',
                    url: form.attr('action'),
                    dataType: 'json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        __disable_submit_button(form.find('button[type="submit"]'));
                    },
                    success: function(result) {
                        if (result.success === true) {
                            $('#taxonomy_modal').modal('hide');
                            toastr.success(result.msg);
                            if (department_table) department_table.ajax.reload();
                            if (designation_table) designation_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function() {
                        toastr.error(LANG.something_went_wrong || 'Something went wrong');
                    },
                    complete: function() {
                        form.find('button[type="submit"]').prop('disabled', false);
                    }
                });
            });

            $(document).on('click', 'button.delete_category_button', function(e) {
                e.preventDefault();
                var href = $(this).data('href');
                swal({ title: LANG.sure, icon: 'warning', buttons: true, dangerMode: true })
                    .then(function(willDelete) {
                        if (willDelete) {
                            $.ajax({
                                method: 'DELETE',
                                url: href,
                                dataType: 'json',
                                success: function(result) {
                                    if (result.success === true) {
                                        toastr.success(result.msg);
                                        if (department_table) department_table.ajax.reload();
                                        if (designation_table) designation_table.ajax.reload();
                                    } else {
                                        toastr.error(result.msg);
                                    }
                                },
                                error: function() {
                                    toastr.error(LANG.something_went_wrong || 'Something went wrong');
                                }
                            });
                        }
                    });
            });
        });
    </script>
@endsection
