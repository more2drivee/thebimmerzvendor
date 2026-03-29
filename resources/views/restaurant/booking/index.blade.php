@extends('layouts.app')
@section('title', __('restaurant.bookings'))

@section('content')
@include('layouts.booking_nav')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('restaurant.bookings')</h1>
        <!-- <ol class="breadcrumb">
                                                                                    <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                                                                                    <li class="active">Here</li>
                                                                                </ol> -->
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            @if (count($business_locations) > 1)
                <div class="col-sm-12">
                    <select id="business_location_id" class="select2" style="width:50%">
                        <option value="">@lang('purchase.business_location')</option>
                        @foreach ($business_locations as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
        <br>
        <div class="row">
            <div class="col-sm-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('restaurant.todays_bookings')</h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed" id="todays_bookings_table">
                            <thead>
                                <tr>

                                    <th>@lang('restaurant.action')</th>
                                    <th>@lang('restaurant.customer')</th>
                                    <th>@lang('restaurant.booking_name')</th>
                                    {{-- <th>Car Type</th> --}}
                                    <th>@lang('restaurant.booking_status')</th>
                                    <th>@lang('restaurant.callback')</th>
                                    <th>@lang('restaurant.created_by')</th>
                                    <th>@lang('restaurant.service')</th>
                                    <th>@lang('restaurant.start_time')</th>

                                    <th>@lang('restaurant.location')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated by DataTables -->
                            </tbody>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-10">
                <div class="box">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-12 text-right">
                                <button type="button" class="btn btn-info" id="add_new_estimator_btn"><i
                                        class="fa fa-calculator"></i> @lang('restaurant.add_estimator')</button>
                                <button type="button" class="btn btn-warning" id="add_new_buy_sell_booking_btn"><i
                                        class="fa fa-car"></i> @lang('restaurant.buy_sell_car_inspection')</button>
                                <button type="button" class="btn btn-primary" id="add_new_booking_btn"><i
                                        class="fa fa-plus"></i> @lang('restaurant.add_booking')</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <div id="calendar"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="box box-solid">
                    <div class="box-body">
                        <!-- the events -->
                        <div class="external-event bg-purple text-center" style="position: relative;">
                            <small>@lang('lang_v1.Request')</small>
                        </div>
                        <div class="external-event bg-yellow text-center" style="position: relative;">
                            <small>@lang('lang_v1.waiting')</small>
                        </div>
                        <div class="external-event bg-light-blue text-center" style="position: relative;">
                            <small>@lang('restaurant.booked')</small>
                        </div>
                        <div class="external-event bg-green text-center" style="position: relative;">
                            <small>@lang('restaurant.completed')</small>
                        </div>
                        <div class="external-event bg-red text-center" style="position: relative;">
                            <small>@lang('restaurant.cancelled')</small>
                        </div>
                        <small>
                            <p class="help-block">
                                <i>@lang('restaurant.click_on_any_booking_to_view_or_change_status')<br><br>
                                    @lang('restaurant.double_click_on_any_day_to_add_new_booking')
                                </i>
                            </p>
                        </small>
                    </div>
                    <!-- /.box-body -->
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
    <!-- Your Blade Template -->
    @include('restaurant.booking.create')
    @include('restaurant.booking.estimator_create')
    @include('restaurant.booking.buy_sell_inspection')

    <!-- Contact Modal -->
    <div class="modal fade create_models" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create_models', ['quick_add' => true, 'customer_id' => ''])
    </div>
    <div class="modal fade contact_modal" tabindex="-1" id='contact_modal' role="dialog"
        aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>
    <div class="modal fade" id="edit_booking_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {!! Form::open([
                    'action' => 'App\Http\Controllers\Restaurant\BookingController@update',
                    'id' => 'editForm',
                    'method' => 'PUT',
                ]) !!}
                @csrf

                <input type="hidden" id="editId" name="id">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" style="width:100%; text-align: center;">@lang('restaurant.edit_booking')</h4>
                </div>

                <div class="modal-body">
                    <!-- Display Form Validation Errors -->
                    <div class="alert alert-danger" id="edit_errors" style="display: none;">
                        <ul id="edit_errors_list"></ul>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-map-marker"></i>
                                    </span>
                                    {!! Form::select('location_id', [], null, [
                                        'placeholder' => __('restaurant.location'),
                                        'class' => 'form-control',
                                        'required',
                                        'id' => 'location_id',
                                    ]) !!}
                                    @error('location_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix"></div>

                    <!-- Customer Dropdown with Search -->
                    <div class="col-sm-6">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                {!! Form::text('contact_name', null, [
                                    'id' => 'booking_customer_id',
                                    'class' => 'form-control',
                                    'placeholder' => __('restaurant.search_customer'),
                                    'required',
                                    'readonly',
                                ]) !!}
                                {!! Form::hidden('contact_id', null, ['id' => 'contact_id']) !!} <!-- Hidden field for contact_id -->

                                @error('contact_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror

                                @error('contact_name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror

                            </div>
                        </div>
                    </div>

                    <!-- Model Dropdown that depends on the selected customer -->
                    <div class="col-sm-6">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-car"></i>
                                </span>
                                {!! Form::select('car_model_id', [], null, [
                                    'placeholder' => __('restaurant.select_car_model'),
                                    'class' => 'form-control',
                                    'required',
                                    'id' => 'car_model_id',
                                ]) !!}
                                @error('car_model_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default bg-white btn-flat add_new_car"
                                        data-name="">
                                        <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix"></div>


                    <!-- Start Time Field -->
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('booking_start', __('restaurant.start_time') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                                {!! Form::input('datetime-local', 'booking_start', null, [
                                    'class' => 'form-control',
                                    'required',
                                    'id' => 'start_time',
                                    'style' => 'width: 80%;',
                                ]) !!}
                            </div>
                            @error('booking_start')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- End Time Field -->
                    <div class="col-sm-6">
                        <div class="form-group">
                            @php
                                // Define booking statuses
                                $booking_statuses = [
                                        'request' => __('lang_v1.Request'),
                                        'waiting' => __('lang_v1.waiting'),
                                        'booked' => __('restaurant.booked'),
                                        'completed' => __('restaurant.completed'),
                                        'cancelled' => __('restaurant.cancelled'),
                                        'pickup_request' => __('Pickup Request'),
                                    ];
                            @endphp
                            {!! Form::label('booking_status', __('restaurant.booking_status') . ':*') !!}
                            {!! Form::select('booking_status', $booking_statuses, null, [
                                'id' => 'booking_status',
                                'class' => 'form-control',
                                'placeholder' => __('restaurant.change_booking_status'),
                                'required',
                            ]) !!}

                            @error('booking_status')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror

                        </div>
                    </div>

                    <!-- Services Dropdown -->
                    <div class="col-sm-12">
                        <div class="form-group">
                            {!! Form::select('service_type_id', [], null, [
                                'placeholder' => __('restaurant.select_service'),
                                'class' => 'form-control',
                                'required',
                                'id' => 'service_type_id',
                            ]) !!}
                            @error('service_type_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Job Estimator (Optional) -->
                    <div class="col-sm-12">
                        <div class="form-group">
                            {!! Form::label('job_estimator_id', __('lang_v1.job_estimator') . ' (' . __('lang_v1.optional') . '):') !!}
                            <select name="job_estimator_id" id="job_estimator_id" class="form-control">
                                <option value="">@lang('messages.please_select')</option>
                                @if(!empty($pending_estimators))
                                    @foreach($pending_estimators as $est)
                                        <option value="{{ $est->id }}">{{ $est->estimate_no }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('job_estimator_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Customer Note -->
                    <div class="col-sm-12">
                        <div class="form-group">
                            {!! Form::label('booking_note', __('restaurant.customer_note') . ':') !!}
                            {!! Form::textarea('booking_note', null, [
                                'id' => 'booking_note',
                                'class' => 'form-control',
                                'placeholder' => __('restaurant.customer_note'),
                                'rows' => 3,
                            ]) !!}
                            @error('booking_note')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Send Notification Checkbox -->
                    <div class="col-sm-6">
                        <div class="form-group">
                            <div class="checkbox">
                                {!! Form::checkbox('send_notification', 1, true, ['id' => 'send_notification']) !!}
                                @lang('restaurant.send_notification_to_customer')
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <div class="checkbox">
                                {!! Form::checkbox('is_callback', 1, null, ['id' => 'is_callback']) !!}
                                {!! Form::label('is_callback', __('restaurant.callback')) !!}
                            </div>
                            @error('is_callback')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    <div class="modal-footer col-sm-12">
                        {!! Form::submit(__('messages.update'), ['class' => 'btn btn-danger']) !!}
                    </div>
                </div><!-- /.modal-content -->
                {!! Form::close() !!}
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <!-- Custom CSS for modal stacking -->
    <style>
        /* Ensure proper modal stacking */
        .create_models {
            z-index: 1060 !important;
        }
        .create_models .modal-dialog {
            margin-top: 50px;
        }
        /* Ensure edit modal stays behind */
        #edit_booking_modal {
            overflow: auto;
        }
        /* Fix for multiple backdrops */
        body.modal-open .modal-backdrop.show:nth-child(n+3) {
            z-index: 1059 !important;
        }
    </style>

    <!-- JavaScript to Open the Modal -->

    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}

    <script type="text/javascript">
        function configureBookingModal(mode) {
            var $modal = $('#add_booking_modal');
            var $title = $modal.find('.modal-title');
            var $form = $('#add_booking_form');

            // Cache default action once
            if ($form.length && !$form.data('default-action')) {
                $form.data('default-action', $form.attr('action'));
            }

            var defaultAction = $form.data('default-action');
            var buySellAction = "{{ route('checkcar.buy_sell_booking.store') }}";

            if (mode === 'buy_sell') {
                $('#booking_mode').val('buy_sell');
                $('#job_estimator_group').hide();
                $('#send_notification_group').hide();
                $('#callback_group').hide();
                $('#callback_ref_group').hide();
                $('#service_price_group').show();
                $('#buyer_contact_group').show();

                if ($form.length) {
                    $form.attr('action', buySellAction);
                }

                // Set a clear title for buy & sell car inspection
                $title.text("@lang('restaurant.buy_sell_car_inspection')");

                // Ensure flags are reset for this mode
                $('#send_notification').prop('checked', false);
                $('#send_notification_value').val(0);
                $('#is_callback').prop('checked', false);
                $('#job_estimator_id').val('');

                // Load only inspection services for Buy & Sell mode
                $.ajax({
                    url: '/bookings/get-inspection-services',
                    method: 'GET',
                    success: function(services) {
                        let $serviceDropdown = $('#service_type');
                        $serviceDropdown.empty().append("<option value=\"\">@lang('restaurant.select_service')</option>");
                        $.each(services, function(id, name) {
                            $serviceDropdown.append('<option value="' + id + '">' + name + '</option>');
                        });
                    },
                    error: function(xhr) {
                        console.error('Error fetching inspection services:', xhr.responseText);
                    }
                });
            } else {
                $('#booking_mode').val('standard');
                $('#job_estimator_group').show();
                $('#send_notification_group').show();
                $('#callback_group').show();
                $('#callback_ref_group').hide();
                $('#service_price_group').hide();
                $('#buyer_contact_group').hide();
                $('#buyer_contact_id').val('');
                $('#buyer_customer_search').val('');

                if ($form.length && defaultAction) {
                    $form.attr('action', defaultAction);
                }

                // Restore original title from data attribute if available
                var defaultTitle = $title.data('defaultTitle') || $title.data('default-title') || $title.text();
                $title.text(defaultTitle);

                // Load all services for standard mode
                $.ajax({
                    url: '/bookings/get-services-by-location/' + ($('select#booking_location_id').val() || 0),
                    method: 'GET',
                    success: function(services) {
                        let $serviceDropdown = $('#service_type');
                        $serviceDropdown.empty().append("<option value=\"\">@lang('restaurant.select_service')</option>");
                        $.each(services, function(id, name) {
                            $serviceDropdown.append('<option value="' + id + '">' + name + '</option>');
                        });
                    },
                    error: function(xhr) {
                        console.error('Error fetching services:', xhr.responseText);
                    }
                });
            }
        }

        // Keep a reference to the DataTable instance so we can safely reload it
        var todays_bookings_table = null;

        $(document).ready(function() {
            // keep hidden value in sync with checkbox for standard bookings
            var checkbox = document.getElementById('send_notification');
            var hiddenInput = document.getElementById('send_notification_value');
            if (checkbox && hiddenInput) {
                checkbox.addEventListener('change', function() {
                    hiddenInput.value = this.checked ? 1 : 0;
                });
            }
            // Check if the 'show_contact_modal' flag is set

            clickCount = 0;
            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay,listWeek'
                },
                eventLimit: 2,
                events: '/bookings',
                eventRender: function(event, element) {
                    var title_html = event.customer_name;
                    if (event.table) {
                        title_html += '<br>' + event.table;
                    }

                    element.find('.fc-title').html(title_html);
                    element.attr('data-href', event.url);
                    element.attr('data-container', '.view_modal');
                    element.addClass('btn-modal');
                },
                dayClick: function(date, jsEvent, view) {
                    clickCount++;
                    if (clickCount == 2) {
                        // Default to standard booking mode when adding from calendar
                        configureBookingModal('standard');
                        $('#add_booking_modal').modal('show');
                        $('form#add_booking_form #start_time').data("DateTimePicker").date(date)
                            .ignoreReadonly(true);
                        $('form#add_booking_form #end_time').data("DateTimePicker").date(date)
                            .ignoreReadonly(true);
                    }
                    var clickTimer = setInterval(function() {
                        clickCount = 0;
                        clearInterval(clickTimer);
                    }, 500);
                }
            });

            //If location is set then show tables.

            $('#add_booking_modal').on('shown.bs.modal', function(e) {
                getLocationTables($('select#booking_location_id').val());
                $(this).find('select').each(function() {
                    if (!($(this).hasClass('select2'))) {
                        $(this).select2({
                            dropdownParent: $('#add_booking_modal')
                        });
                    }
                });
                booking_form_validator = $('form#add_booking_form').validate({
                    submitHandler: function(form) {
                        // Prevent duplicate submissions
                        if ($(form).data('submitting')) {
                            return false;
                        }
                        $(form).data('submitting', true);

                        var data = $(form).serialize();
                        var $submitBtn = $(form).find('button[type="submit"]');

                        $.ajax({
                            method: "POST",
                            url: $(form).attr("action"),
                            dataType: "json",
                            data: data,
                            beforeSend: function(xhr) {
                                __disable_submit_button($submitBtn);
                            },
                            success: function(result) {
                                if (result.success == true) {
                                    if (result.send_notification) {
                                        $("div.view_modal").load(result
                                            .notification_url,
                                            function() {
                                                $(this).modal('show');
                                            });
                                    }

                                    $('div#add_booking_modal').modal('hide');
                                    toastr.success(result.msg);
                                    reload_calendar();
                                    if (todays_bookings_table) {
                                        todays_bookings_table.ajax.reload();
                                    }
                                    // Keep button disabled after success
                                } else {
                                    toastr.error(result.msg);
                                    $submitBtn.attr('disabled', false);
                                    $(form).data('submitting', false);
                                }
                            },
                            error: function(xhr) {
                                var errorMsg = "@lang('messages.something_went_wrong')";
                                if (xhr.responseJSON && xhr.responseJSON.msg) {
                                    errorMsg = xhr.responseJSON.msg;
                                }
                                toastr.error(errorMsg);
                                $submitBtn.attr('disabled', false);
                                $(form).data('submitting', false);
                            }
                        });
                    }
                });
            });
            $('#add_booking_modal').on('hidden.bs.modal', function(e) {
                if (typeof booking_form_validator !== 'undefined' && $.isFunction(booking_form_validator
                        .destroy)) {
                    booking_form_validator.destroy();
                }
                reset_booking_form();
            });

            $('form#add_booking_form #start_time').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                minDate: moment(),
                ignoreReadonly: true
            });

            $('form#add_booking_form #end_time').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                minDate: moment(),
                ignoreReadonly: true,
            });

            $('.view_modal').on('shown.bs.modal', function(e) {
                $('form#edit_booking_form').validate({
                    submitHandler: function(form) {
                        var data = $(form).serialize();

                        $.ajax({
                            method: "PUT",
                            url: $(form).attr("action"),
                            dataType: "json",
                            data: data,
                            beforeSend: function(xhr) {
                                __disable_submit_button($(form).find(
                                    'button[type="submit"]'));
                            },
                            success: function(result) {

                                if (result.success == true) {
                                    $('div.view_modal').modal('hide');
                                    toastr.success(result.msg);
                                    reload_calendar();
                                    if (todays_bookings_table) {
                                        todays_bookings_table.ajax.reload();
                                    }
                                    $(form).find('button[type="submit"]').attr(
                                        'disabled', false);
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            // Initialize DataTable and store the instance for later reloads
            todays_bookings_table = $('#todays_bookings_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('bookings.getTodaysBookings') }}",
                    data: function(d) {
                        d.location_id = $('#business_location_id').val(); // Filter by location
                    }
                },
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'customer_name',
                        name: 'contacts.name'

                    },
                    {
                        data: 'booking_name',
                        name: 'booking_name'
                    },
                    // {
                    //     data: 'car_type',
                    //     name: 'car_type'
                    // },
                    {
                        data: 'booking_status',
                        name: 'booking_status'
                    },
                    {
                        data: 'is_callback',
                        name: 'is_callback'
                    },
                    {
                        data: 'created_by_name',
                        name: 'created_by_user.first_name'
                    },

                    {
                        data: 'service_type_name',
                        name: 'types_of_services.name'
                    },
                    {
                        data: 'booking_start',
                        name: 'booking_start'
                    },

                    {
                        data: 'location_name',
                        name: 'business_locations.name'
                    },
                ]
            });

            //// Handle edit button click
            $('#todays_bookings_table').on('click', '.edit-booking', function() {
                var bookingId = $(this).data('id'); // Get the booking ID

                // Fetch booking data via AJAX
                $.ajax({
                    url: '/bookings/' + bookingId + '/edit', // Route to fetch booking data
                    method: 'GET',
                    success: function(response) {
                        console.log(response);

                        populateDropdown('#location_id', response.business_locations);
                        populateDropdown('#car_model_id', response.models);
                        populateDropdown('#service_type_id', response.services);


                        // Set selected values (after options are added)
                        $('#edit_booking_modal #booking_status').val(response.booking
                            .booking_status);
                        $('#edit_booking_modal #editId').val(response.booking.id);
                        $('#edit_booking_modal #location_id').val(String(response.booking
                            .location_id));
                        $('#edit_booking_modal #car_model_id').val(String(response.booking
                            .device_id));
                        $('#edit_booking_modal #booking_customer_id').val(response.booking
                            .contact_name); // Display name, not dropdown
                        $('#edit_booking_modal #contact_id').val(response.booking
                            .contact_id); // Display name, not dropdown
                        $('#edit_booking_modal #start_time').val(formatDateTime(response.booking
                            .start_time));
                        $('#edit_booking_modal #end_time').val(formatDateTime(response.booking
                            .end_time));
                        $('#edit_booking_modal #service_type_id').val(response.booking
                            .service_type_id);
                        $('#edit_booking_modal #booking_note').val(response.booking
                            .booking_note);
                        $('#edit_booking_modal #send_notification').prop('checked', response.booking.send_notification == 1);
                        $('#edit_booking_modal #is_callback').prop('checked', response.booking.is_callback == 1);

                        $('#model_customer_id').val(response.booking.contact_id);
                        // Open modal
                        $('#edit_booking_modal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching booking data:', error);
                        alert(
                            'An error occurred while fetching booking data. Please try again.'
                        );
                    }
                });
            });

            // Function to populate dropdowns
            function populateDropdown(selector, data) {
                $(selector).empty(); // Clear current options
                $(selector).append('<option value="">Select</option>'); // Add placeholder

                if (data && Object.keys(data).length) {
                    $.each(data, function(key, value) {
                        $(selector).append('<option value="' + key + '">' + value + '</option>');
                    });
                } else {
                    console.warn('No data to populate for:', selector);
                }
            }

            // Function to format date/time for datetime-local input
            // - Accepts common DB formats like "YYYY-MM-DD HH:MM:SS" or "YYYY-MM-DDTHH:MM:SS"
            // - Returns a local "YYYY-MM-DDTHH:MM" string suitable for <input type="datetime-local">
            // - Safely returns empty string for invalid or zero dates (e.g. "0000-00-00 00:00:00")
            function formatDateTime(dateTimeString) {
                if (!dateTimeString) return '';

                // If an explicit zero date is provided, treat it as empty
                if (typeof dateTimeString === 'string' && /^0{4}-0{2}-0{2}/.test(dateTimeString)) {
                    return '';
                }

                // Ensure it's a string
                dateTimeString = String(dateTimeString);

                // Match common DB format: YYYY-MM-DD HH:MM(:SS)? or YYYY-MM-DDTHH:MM(:SS)?
                var m = dateTimeString.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::\d{2})?$/);
                if (m) {
                    // Build a local Date using components to avoid timezone parsing issues
                    var year = parseInt(m[1], 10);
                    var month = parseInt(m[2], 10) - 1; // JS months are 0-based
                    var day = parseInt(m[3], 10);
                    var hour = parseInt(m[4], 10);
                    var minute = parseInt(m[5], 10);

                    var d = new Date(year, month, day, hour, minute, 0);
                    if (isNaN(d.getTime())) return '';

                    var YYYY = d.getFullYear();
                    var MM = ('0' + (d.getMonth() + 1)).slice(-2);
                    var DD = ('0' + d.getDate()).slice(-2);
                    var hh = ('0' + d.getHours()).slice(-2);
                    var mm = ('0' + d.getMinutes()).slice(-2);
                    return YYYY + '-' + MM + '-' + DD + 'T' + hh + ':' + mm;
                }

                // Fallback: try Date.parse, but guard against invalid dates
                var parsed = new Date(dateTimeString);
                if (isNaN(parsed.getTime())) return '';

                // Convert to ISO and take the local-equivalent for datetime-local if possible
                // Use the parsed Date's local components
                var YY = parsed.getFullYear();
                var Mo = ('0' + (parsed.getMonth() + 1)).slice(-2);
                var Da = ('0' + parsed.getDate()).slice(-2);
                var Ho = ('0' + parsed.getHours()).slice(-2);
                var Mi = ('0' + parsed.getMinutes()).slice(-2);
                return YY + '-' + Mo + '-' + Da + 'T' + Ho + ':' + Mi;
            }



            // Delete functionality
            $(document).on('click', '.delete-booking', function(e) {
                e.preventDefault();
                var url = $(this).data('href');

                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((confirmed) => {
                    if (confirmed) {
                        $.ajax({
                            method: 'DELETE',
                            url: url,
                            dataType: 'json',
                            data: {
                                _token: '{{ csrf_token() }}',
                            },
                            success: function(result) {
                                if (result.success) {
                                    toastr.success(result.msg);
                                    $('#todays_bookings_table').DataTable().ajax
                                        .reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                            error: function(xhr) {
                                toastr.error('Something went wrong. Please try again.');
                            }
                        });
                    }
                });
            });


            $('button#add_new_booking_btn').click(function() {
                configureBookingModal('standard');
                $('div#add_booking_modal').modal('show');
                $('.div#add_booking_modal').modal('show').css('z-index', 1050);
            });

            $('button#add_new_buy_sell_booking_btn').click(function() {
                $('div#buy_sell_inspection_modal').modal('show');
            });

        });
        $(document).on('change', 'select#booking_location_id', function() {
            getLocationTables($(this).val());
        });

        $(document).on('change', 'select#business_location_id', function() {
            reload_calendar();
            todays_bookings_table.ajax.reload();
        });

        $(document).on('click', 'button#delete_booking', function() {
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).data('href');
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        success: function(result) {
                            if (result.success == true) {
                                $('div.view_modal').modal('hide');
                                toastr.success(result.msg);
                                reload_calendar();
                                todays_bookings_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        function getLocationTables(location_id) {
            $.ajax({
                method: "GET",
                url: '/modules/data/get-pos-details',
                data: {
                    'location_id': location_id
                },
                dataType: "html",
                success: function(result) {
                    $('div#restaurant_module_span').html(result);
                }
            });
        }

        function reset_booking_form() {
            $('select#booking_location_id').val('').change();
            // $('select#booking_customer_id').val('').change();
            $('select#correspondent').val('').change();
            $('#booking_note, #start_time, #end_time').val('');
            // Reset submission flag and re-enable submit button for next use
            $('form#add_booking_form').data('submitting', false);
            $('form#add_booking_form').find('button[type="submit"]').attr('disabled', false);
        }

        function reload_calendar() {
            var location_id = '';
            if ($('select#business_location_id').val()) {
                location_id = $('select#business_location_id').val();
            }

            var events_source = {
                url: '/bookings',
                type: 'get',
                data: {
                    'location_id': location_id
                }
            }
            $('#calendar').fullCalendar('removeEventSource', events_source);
            $('#calendar').fullCalendar('addEventSource', events_source);
            $('#calendar').fullCalendar('refetchEvents');
        }

        $(document).on('click', '.add_new_customer', function() {
            $('.contact_modal')
                .find('select#contact_type')
                .val('customer')
                .closest('div.contact_type_div')
                .addClass('hide');
            $('.contact_modal').modal('show');

        });
        $(document).on('click', '.add_new_models, .add_new_car', function() {
            // Get the contact ID from the hidden field or from the edit form
            var contactId = '';

            // If we're in the buy_sell_inspection_modal (seller contact)
            if ($('#buy_sell_inspection_modal').is(':visible')) {
                contactId = $('#buy_sell_contact_id').val();
            }
            // If we're in the edit modal
            else if ($('#edit_booking_modal').is(':visible')) {
                contactId = $('#edit_booking_modal #contact_id').val();
            } else {
                // Otherwise get it from the regular form
                contactId = $('#contact_id').val();
            }

            // Set the customer ID in the create_models form
            $('#model_customer_id').val(contactId);

            // If we're in the edit modal, also set the contact name
            if ($('#edit_booking_modal').is(':visible')) {
                var contactName = $('#edit_booking_modal #booking_customer_id').val();
                $('#model_customer_name').val(contactName);
            }

            // First hide the buy_sell_inspection_modal (but keep it in the DOM)
            if ($('#buy_sell_inspection_modal').is(':visible')) {
                $('#buy_sell_inspection_modal').modal('hide');
                // Store a flag to reopen the buy_sell modal when create_models is closed
                window.shouldReopenBuySellModal = true;
            }
            // First hide the edit modal (but keep it in the DOM)
            else if ($('#edit_booking_modal').is(':visible')) {
                $('#edit_booking_modal').modal('hide');
                // Store a flag to reopen the edit modal when create_models is closed
                window.shouldReopenEditModal = true;
            }

            // Show the create_models modal with higher z-index
            $('.create_models').css('z-index', 1060);
            $('.create_models').modal({
                backdrop: 'static',
                keyboard: false,
                show: true
            });
        });

        $("#toggleVehicleBtn").on("click", function() {
            $("#vehicle_div").slideToggle();
            $(this).find("i").toggleClass("fa-chevron-down fa-chevron-up");
        });

        $("#toggleMoreInfo").on("click", function() {
            $("#more_div").slideToggle();
            $(this).find("i").toggleClass("fa-chevron-down fa-chevron-up");
        });


        $('form#quick_add_contact')
            .submit(function(e) {
                e.preventDefault();
            })
            .validate({
                rules: {
                    contact_id: {
                        remote: {
                            url: '/contacts/check-contacts-id',
                            type: 'post',
                            data: {
                                contact_id: function() {
                                    return $('#contact_id').val();
                                },
                                hidden_id: function() {
                                    if ($('#hidden_id').length) {
                                        return $('#hidden_id').val();
                                    } else {
                                        return '';
                                    }
                                },
                            },
                        },
                    },
                },

                messages: {
                    contact_id: {
                        remote: LANG.contact_id_already_exists,
                    },
                },
            // ... existing code ...
            submitHandler: function(form) {
                var data = $(form).serialize();
                $.ajax({
                    method: 'POST',
                    url: $(form).attr('action'),
                    dataType: 'json',
                    data: data,
                    beforeSend: function(xhr) {
                        __disable_submit_button($(form).find('button[type="submit"]'));
                    },
                    success: function(result) {
                        console.log(result);

                        if (result.success == true) {
                            // Update the booking_customer_id dropdown
                            $('select#booking_customer_id').append(
                                $('<option>', {
                                    value: result.data.id,
                                    text: result.data.name
                                })
                            );
                            $('select#booking_customer_id')
                                .val(result.data.id)
                                .trigger('change');

                            // Close the contact modal
                            $('div.contact_modal').modal('hide');
                            toastr.success(result.msg);

                            // Update the contact search field in the booking form
                            $('#customer_search').val(result.data.name);
                            $('#contact_id').val(result.data.id).trigger('change');
                            $('#customer_results').hide();
                            $('#clear_customer').show();

                            // Fetch the latest contact details including vehicles
                            fetchLatestContact();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });

                // ... existing code ...
            function fetchLatestContact() {
                $.ajax({
                    method: 'GET',
                    url: '/contact/show/dd',
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            // Check if data exists and has at least one item
                            if (result.data && result.data.length > 0) {
                                let contact = result.data[0];

                                // Use the correct property names based on your API response
                                // If contact_name doesn't exist, use name property instead
                                const contactName = contact.contact_name || contact.name || '';
                                const contactId = contact.contact_id || contact.id || '';

                                // Update the contact search field in the booking form
                                $('#customer_search').val(contactName);
                                $('#contact_id').val(contactId).trigger('change');
                                $('#customer_results').hide();
                                $('#clear_customer').show();

                                // Get the car model dropdown element
                                let modelSelect = document.getElementById("booking_model_id");
                                if (modelSelect) {
                                    modelSelect.innerHTML = ""; // Clear previous options

                                    // Create and append a default option
                                    let defaultOption = document.createElement("option");
                                    defaultOption.value = "";
                                    defaultOption.text = "Select Car Model";
                                    modelSelect.appendChild(defaultOption);

                                    // Loop through each returned device and create an option element
                                    if (result.data.length > 0) {
                                        result.data.forEach(item => {
                                            // Check if device properties exist before using them
                                            if (item && (item.device_id || item.id) && (item.device_name || item.name)) {
                                                let option = document.createElement("option");
                                                option.value = item.device_id || item.id;
                                                option.text = item.device_name || item.name;
                                                modelSelect.appendChild(option);
                                            }
                                        });
                                    }
                                }
                            } else {
                                // console.warn("No contact data found in the response");
                                // toastr.warning("No contact details found.");
                            }
                        } else {
                            toastr.error("Failed to retrieve contact details.");
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        toastr.error("An error occurred while fetching contact details.");
                    }
                });
            }
            // ... existing code ...

                },
            });
        $('.contact_modal').on('hidden.bs.modal', function() {
            $('form#quick_add_contact')
                .find('button[type="submit"]')
                .removeAttr('disabled');
            $('form#quick_add_contact')[0].reset();
        });

        // Listen for when the create_models modal is hidden
        $('.create_models').on('hidden.bs.modal', function () {
            // Check if we need to refresh car models and reopen the buy_sell modal
            if (window.shouldReopenBuySellModal) {
                var contactId = $('#buy_sell_contact_id').val();
                if (contactId) {
                    // Fetch updated car models for the buy_sell form
                    $.ajax({
                        url: '/bookings/get-custumer-vehicles/' + contactId,
                        type: 'GET',
                        success: function(response) {
                            var $carDropdown = $('#buy_sell_model_id');
                            $carDropdown.empty().append('<option value="">@lang("restaurant.select_car_model")</option>');

                            if (response.length > 0) {
                                $.each(response, function(index, model) {
                                    var displayText = model.model_name;
                                    var additionalInfo = [];

                                    if (model.plate_number) {
                                        additionalInfo.push('@lang("car.plate"): ' + model.plate_number);
                                    }

                                    if (model.color) {
                                        additionalInfo.push('@lang("car.color"): ' + model.color);
                                    }

                                    if (additionalInfo.length > 0) {
                                        displayText += ' (' + additionalInfo.join(', ') + ')';
                                    }

                                    $carDropdown.append('<option value="' + model.id + '">' + displayText + '</option>');
                                });
                            } else {
                                $carDropdown.append('<option value="">@lang("restaurant.no_models_available")</option>');
                            }

                            // Reopen the buy_sell modal after a short delay
                            setTimeout(function() {
                                $('#buy_sell_inspection_modal').modal('show');
                                window.shouldReopenBuySellModal = false;
                            }, 300);
                        },
                        error: function() {
                            toastr.error('Error fetching car models. Please try again.');
                            // Still reopen the buy_sell modal even if there was an error
                            setTimeout(function() {
                                $('#buy_sell_inspection_modal').modal('show');
                                window.shouldReopenBuySellModal = false;
                            }, 300);
                        }
                    });
                } else {
                    // If no contact ID, just reopen the buy_sell modal
                    setTimeout(function() {
                        $('#buy_sell_inspection_modal').modal('show');
                        window.shouldReopenBuySellModal = false;
                    }, 300);
                }
            }
            // Check if we need to refresh car models and reopen the edit modal
            else if (window.shouldReopenEditModal) {
                var contactId = $('#edit_booking_modal #contact_id').val();
                if (contactId) {
                    // Fetch updated car models for the edit form
                    $.ajax({
                        url: '/bookings/get-custumer-vehicles/' + contactId,
                        type: 'GET',
                        success: function(response) {
                            var $carDropdown = $('#car_model_id');
                            $carDropdown.empty().append('<option value="">@lang("restaurant.select_car_model")</option>');

                            if (response.length > 0) {
                                $.each(response, function(index, model) {
                                    // Create display text with model name, plate number and color if available
                                    var displayText = model.model_name;
                                    var additionalInfo = [];

                                    if (model.plate_number) {
                                        additionalInfo.push('@lang("car.plate"): ' + model.plate_number);
                                    }

                                    if (model.color) {
                                        additionalInfo.push('@lang("car.color"): ' + model.color);
                                    }

                                    if (additionalInfo.length > 0) {
                                        displayText += ' (' + additionalInfo.join(', ') + ')';
                                    }

                                    $carDropdown.append('<option value="' + model.id + '">' + displayText + '</option>');
                                });
                            } else {
                                $carDropdown.append('<option value="">@lang("restaurant.no_models_available")</option>');
                            }

                            // Reopen the edit modal after a short delay
                            setTimeout(function() {
                                $('#edit_booking_modal').modal('show');
                                window.shouldReopenEditModal = false;
                            }, 300);
                        },
                        error: function() {
                            toastr.error('Error fetching car models. Please try again.');
                            // Still reopen the edit modal even if there was an error
                            setTimeout(function() {
                                $('#edit_booking_modal').modal('show');
                                window.shouldReopenEditModal = false;
                            }, 300);
                        }
                    });
                } else {
                    // If no contact ID, just reopen the edit modal
                    setTimeout(function() {
                        $('#edit_booking_modal').modal('show');
                        window.shouldReopenEditModal = false;
                    }, 300);
                }
            }
        });
        $(document).on('submit', '#update_booking_status', function(e) {
            e.preventDefault();


            const form = $(this);
            const url = form.attr('action');

            $.ajax({
                url: url,
                type: 'PUT',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.success); // Display success message
                        $('div.view_modal').modal('hide'); // Close the modal
                        // Optionally refresh the table or UI here
                    } else {
                        toastr.error('Unexpected response format'); // Handle unexpected cases
                    }
                },
                error: function(xhr) {
                    // If there's an error, display the error message from the server
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        toastr.error(xhr.responseJSON.error);
                    } else {
                        toastr.error('An error occurred');
                    }
                },
            });
        });
        // Get the current year
        const currentYear = new Date().getFullYear();
        const startYear = 1990; // You can adjust this to any start year you prefer
        const selectElement = document.getElementById('manufacturing_year');

        // Populate the dropdown with years from startYear to currentYear
        for (let year = currentYear; year >= startYear; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            selectElement.appendChild(option);
        }



        // Add change event listener on the select input for booking (you can use #booking_id or your specific select ID)
        $('#booking_id').on('change', function() {
            var bookingId = $(this).val(); // Get the selected booking ID

            if (bookingId) {
                // If a booking is selected, send the request to the controller to retrieve booking data
                $.ajax({
                    url: '/bookings/get-booking-data/' + bookingId, // Your route for fetching booking data
                    type: 'GET',
                    success: function(response) {
                        // Handle the response and update the page with the booking data
                        // Example: Display the booking details in a div with id 'booking_details'
                        if (response) {
                            $('#booking_details').html(
                                response); // You can update this as per your design
                        } else {
                            alert('No booking data found for this selection.');
                        }
                    },
                    error: function() {
                        alert('Error fetching booking data.');
                    }
                });
            } else {
                // If no booking is selected, clear any previous data
                $('#booking_details').empty(); // Clear the details
            }
        });

        // If a brand is selected, fetch models for that brand
        $('#category_id').on('change', function() {
            var brandId = $(this).val(); // Get the selected brand ID
            //console.log(brandId);
            //alert(brandId);
            $.ajax({
                url: '/bookings/get-models/' + brandId, // Your route for fetching models
                type: 'GET',
                success: function(response) {
                    // Empty the models dropdown and add a default option
                    $('#model_id').empty();
                    $('#model_id').append('<option value="">Select Model</option>');

                    // Check if the response contains models
                    if (response.length) {
                        $.each(response, function(index, model) {
                            $('#model_id').append('<option value="' + model.id + '">' + model
                                .name + '</option>');
                        });
                    } else {
                        // If no models are returned, show a "No models available" option
                        $('#model_id').append('<option value="">No models available</option>');
                    }
                },
                error: function() {
                    alert('Error fetching models.');
                }
            });
        });

        $('#gehad_category_id').on('change', function() {
            var brandId = $(this).val(); // Get the selected brand ID
            //console.log(brandId);
            //alert(brandId);
            $.ajax({
                url: '/bookings/get-models/' + brandId, // Your route for fetching models
                type: 'GET',
                success: function(response) {
                    // Empty the models dropdown and add a default option
                    $('#gehad_model_id').empty();
                    $('#gehad_model_id').append('<option value="">Select Model</option>');

                    // Check if the response contains models
                    if (response.length) {
                        $.each(response, function(index, model) {
                            $('#gehad_model_id').append('<option value="' + model.id + '">' +
                                model
                                .name + '</option>');
                        });
                    } else {
                        // If no models are returned, show a "No models available" option
                        $('#gehad_model_id').append('<option value="">No models available</option>');
                    }
                },
                error: function() {
                    alert('Error fetching models.');
                }
            });
        });
  
        
        // Handle Estimator Modal
        $('#add_new_estimator_btn').click(function() {
            $('#add_estimator_modal').modal('show');
        });

        $('#add_estimator_modal').on('shown.bs.modal', function(e) {
            $(this).find('select').each(function() {
                if (!($(this).hasClass('select2'))) {
                    $(this).select2({
                        dropdownParent: $('#add_estimator_modal')
                    });
                }
            });
            
            estimator_form_validator = $('form#add_estimator_form').validate({
                submitHandler: function(form) {
                    var data = $(form).serialize();

                    $.ajax({
                        method: "POST",
                        url: $(form).attr("action"),
                        dataType: "json",
                        data: data,
                        beforeSend: function(xhr) {
                            __disable_submit_button($(form).find('button[type="submit"]'));
                        },
                        success: function(result) {
                            if (result.success == true) {
                                if (result.send_notification) {
                                    $("div.view_modal").load(result.notification_url, function() {
                                        $(this).modal('show');
                                    });
                                }

                                $('div#add_estimator_modal').modal('hide');
                                toastr.success(result.msg);
                                // Optionally reload estimator list or datatable
                            } else {
                                toastr.error(result.msg);
                            }
                            $(form).find('button[type="submit"]').attr('disabled', false);
                        },
                        error: function(xhr) {
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                var errors = xhr.responseJSON.errors;
                                var errorHtml = '<ul>';
                                for (var key in errors) {
                                    errorHtml += '<li>' + errors[key][0] + '</li>';
                                }
                                errorHtml += '</ul>';
                                $('#estimator_errors_list').html(errorHtml);
                                $('#estimator_errors').show();
                            }
                            $(form).find('button[type="submit"]').attr('disabled', false);
                        }
                    });
                }
            });
        });

        $('#add_estimator_modal').on('hidden.bs.modal', function(e) {
            if (typeof estimator_form_validator !== 'undefined' && $.isFunction(estimator_form_validator.destroy)) {
                estimator_form_validator.destroy();
            }
            reset_estimator_form();
        });

        function reset_estimator_form() {
            $('form#add_estimator_form')[0].reset();
            $('#estimator_errors').hide();
            $('#availability_notes_section').hide();
        }
    </script>
@endsection
