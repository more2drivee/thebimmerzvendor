<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<style>
    /* Inspired Mobile Service Interface Styling */

    .service-container {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        margin: 0 auto;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .service-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .service-header h2 {
        font-size: 18px;
        color: #333;
        margin: 0 0 5px 0;
    }

    .service-header p {
        font-size: 14px;
        color: #666;
        margin: 0;
    }

    .banner {
        background-color: #28a745;
        color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }

    .banner::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="%23ffffff" opacity="0.1"/></svg>') no-repeat center;
        background-size: 50%;
        z-index: 0;
    }

    .banner-content {
        position: relative;
        z-index: 1;
    }

    .banner h3 {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 5px 0;
    }

    .banner p {
        font-size: 14px;
        margin: 0;
    }

    .service-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .service-card {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .service-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    }

    .service-card i {
        font-size: 24px;
        color: #28a745;
        margin-bottom: 10px;
    }

    .service-card h4 {
        font-size: 16px;
        color: #333;
        margin: 0 0 5px 0;
    }

    .service-card p {
        font-size: 12px;
        color: #666;
        margin: 0;
    }

    /* Form Styling for location_id */
    .form-group {
        margin-bottom: 20px;
    }

    .input-group {
        position: relative;
        width: 100%;
    }

    .input-group-addon {
        position: absolute;
        left: 0;
        top: 0;
        height: 45%;
        padding: 10px 15px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-right: none;
        border-radius: 6px 0 0 6px;
        color: #28a745;
        z-index: 2;
    }

    .form-control {
        width: 100%;
        /* height: 800px; */

        padding: 10px 15px 10px 45px;
        /* Extra padding-left for icon */
        border: 1px solid #ddd;
        border-radius: 6px;
        background-color: #f8f9fa;
        font-size: 14px;
        color: #333;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        appearance: none;
        /* Remove default select arrow */
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"><path fill="%23666" d="M7 10l5 5 5-5z"/></svg>');
        /* Custom dropdown arrow */
        background-repeat: no-repeat;
        background-position: right 10px center;
    }

    .form-control:hover,
    .form-control:focus {
        border-color: #28a745;
        box-shadow: 0 0 5px rgba(40, 167, 69, 0.3);
        outline: none;
    }

    .form-control option {
        color: #333;
    }

    .service-card.selected {
        background-color: #28a745;
        color: #ffffff;
    }

    .service-card.selected i,
    .service-card.selected h4,
    .service-card.selected p {
        color: #ffffff;
    }

    /* Save Button Styling */
    .btn-save {
        background-color: #28a745;
        /* Matches your theme color */
        color: #ffffff;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s ease, transform 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        /* Space between icon and text */
        width: 100%;
        /* Full width for consistency */
        max-width: 200px;
        /* Optional: Limit width */
        margin: 20px auto 0;
        display: none;
        /* Center the button */
    }

    .btn-save:hover {
        background-color: #218838;
        /* Darker shade on hover */
        transform: translateY(-2px);
        /* Slight lift effect */
    }

    .btn-save i {
        font-size: 16px;
        /* Icon size */
    }

    .form-container {
        display: flex;
        gap: 20px;
        /* Adds space between the two divs */
    }

    .form-date-container {
        display: flex;
        gap: 40px;
    }

    /* Responsive Adjustments */
    @media (max-width: 1024px) {
        .service-container {
            max-width: 100%;
            /* Adjust container width */
            padding: 15px;
        }

        .form-container>div,
        .form-date-container>div {
            flex: 2 2 100%;
            /* Two columns on medium screens */

        }

        .service-grid {
            grid-template-columns: repeat(2, 1fr);
            /* Two columns */
        }

        .form-control {
            font-size: 13px;
            /* padding: 8px 12px 8px 40px; */
            height: 80px;
            /* Adjust padding */
        }

        .input-group-addon {
            /* padding: 8px 12px; */
            height: 57%;
        }
    }

    @media (max-width: 768px) {
        .service-container {
            max-width: 95%;
            padding: 10px;
        }

        .banner {
            padding: 15px;
        }

        .banner h3 {
            font-size: 18px;
        }

        .form-container>div,
        .form-date-container>div {
            flex: 1 1 100%;
            /* Stack items on smaller screens */
        }

        .service-grid {
            grid-template-columns: 1fr;
            /* Single column */
        }

        .form-control {
            font-size: 13px;
            padding: 8px 12px 8px 40px;
            /* Adjust padding */
        }

        .input-group-addon {
            padding: 8px 12px;
            height: 57%;

        }



        .btn-save {
            max-width: 150px;
            /* Smaller button */
        }
    }

    @media (max-width: 480px) {
        .service-container {
            margin: 10px auto;
            padding: 8px;
        }

        .banner {
            padding: 10px;
        }

        .banner h3 {
            font-size: 16px;
        }

        .banner p {
            font-size: 12px;
        }

        .service-card {
            padding: 10px;
        }

        .service-card i {
            font-size: 20px;
        }

        .service-card h4 {
            font-size: 14px;
        }

        .service-card p {
            font-size: 11px;
        }

        .form-control {
            font-size: 13px;
            /* Reduced for small screens */
            padding: 8px 12px 8px 35px;
            /* Adjusted padding */
            height: 40px;
            /* Reduced height for smaller screens */
        }

        .input-group-addon {
            height: 57%;

            /* Match the adjusted height */
            padding: 8px 10px;
            /* Adjusted padding */
        }

        .btn-save {
            padding: 8px 15px;
            font-size: 12px;
            max-width: 120px;
        }

        h4 {
            font-size: 16px;
            /* Smaller headings */
        }
    }
</style>

<div class="service-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    {{-- <div class="service-header">
        <h2>Hi, {{ auth()->user()->first_name }}</h2>
        <p>You can choose a service below</p>
    </div> --}}

    <div class="banner">
        <div class="banner-content">
            <h3>Hi, {{ auth()->user()->first_name }}</h3>
            {{-- <p>Car Servicing & Repair</p> --}}
        </div>
    </div>

    <div class="modal-content">
        {!! Form::open([
            'url' => action([\Modules\Crm\Http\Controllers\ContactBookingController::class, 'storebooking']),
            'method' => 'post',
            'id' => 'add_booking_form',
        ]) !!}
        {!! Form::hidden('contact_id', auth()->user()->crm_contact_id) !!}
        {!! Form::hidden('booking_status', 'waiting') !!}

        <h4>Add Car</h4>
        <div class="row">

            <div class="form-container">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="brand_id">@lang('restaurant.car_brand'):</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-cogs"></i>
                            </span>
                            @php
                                $brand_information = \Illuminate\Support\Facades\DB::table('categories')
                                    ->where('category_type', 'device')
                                    ->select('id', 'name')
                                    ->get();
                            @endphp
                            <select name="gehad_category_id" id="gehad_category_id" class="form-control"
                                style="margin-left: 5px;" required>
                                <option value="">@lang('select category')</option>
                                @foreach ($brand_information as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('gehad_category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="gehad_model_id">Model</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-id-badge"></i>
                            </span>
                            <select name="gehad_model_id" id="gehad_model_id" class="form-control" required>
                                <option value="">Select Model</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-container">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="color">@lang('restaurant.color'):</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-paint-brush"></i>
                            </span>
                            <input type="text" name="color" id="color" class="form-control" placeholder="Color"
                                value="{{ old('color') }}" required>
                        </div>
                    </div>
                </div>



                <div class="col-md-4">
                    <div class="form-group">
                        <label for="chassis_number">@lang('car.vin'):</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-key"></i>
                            </span>
                            <input type="text" name="chassis_number" id="chassis_number" class="form-control"
                                placeholder="Chassis Number" value="{{ old('chassis_number') }}">
                        </div>
                        @error('chassis_number')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="form-container">

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="plate_number">@lang('car.plate'):</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-key"></i>
                            </span>
                            <input type="text" name="plate_number" id="plate_number" class="form-control"
                            placeholder="{{ __('car.plate') }}" value="{{ old('plate_number') }}" required>
                        </div>
                        @error('plate_number')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="manufacturing_year" style="font-size: 14px">@lang('car.manufacturing')</label>
                        <div class="input-group date">
                            <span class="input-group-addon">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <select class="form-control" id="manufacturing_year" name="manufacturing_year" required>
                                <option value="">@lang('car.selectyear')</option>
                                @foreach (range(date('Y'), 1990) as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('manufacturing_year')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label for="service_type">@lang('car.cartype'):</label>
                    <select name="car_type" id="car_type" class="form-control">
                        <option value="">@lang('car.selectcartype')</option>
                        <option value="ملاكي">ملاكي</option>
                        <option value="اجرة">اجرة</option>
                        <option value="نقل ثقيل">نقل ثقيل</option>
                        <option value=" نقل خفيف">نقل</option>

                    </select>
                    @error('car_type')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <hr>

        <h4> Location </h4>
        <div class="col-sm-12">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-map-marker"></i>
                    </span>
                    {!! Form::select('location_id', $business_locations->pluck('name', 'id')->toArray(), null, [
                        'class' => 'form-control',
                        'placeholder' => __('Choose Center Service'),
                        'required' => 'required',
                        'id' => 'booking_location_id',
                    ]) !!}
                </div>
            </div>
        </div>

        <hr>

        <h4>Choose Service</h4>
        <div class="service-grid">
            @foreach ($services as $service)
                <div class="service-card" data-service-id="{{ $service->id }}"
                    data-service-name="{{ $service->name }}"
                    onclick="selectService(this, '{{ $service->id }}', '{{ $service->name }}')">
                    <i class="fas fa-tools"></i>
                    <i class="fas fa-truck"></i>
                    <h4>{{ $service->name }}</h4>
                    <p>Repair, Assistance, etc.</p>
                </div>
            @endforeach
        </div>

        <!-- Hidden fields to store selected service ID and Name -->
        <input type="hidden" id="service_id" name="service_id">




        <hr>
        <div class="row">

            <div class="form-date-container">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('booking_start', __('restaurant.start_time') . ':') !!}
                        <div class='input-group date'>
                            <span class="input-group-addon">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            {!! Form::text('booking_start', null, [
                                'class' => 'form-control',
                                'placeholder' => __('restaurant.start_time'),
                                'required',
                                'id' => 'start_time',
                                'data-input' => '',
                            ]) !!}
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    {!! Form::label('booking_note', __('restaurant.customer_note') . ':') !!}
                    {!! Form::textarea('booking_note', null, [
                        'class' => 'form-control',
                        'placeholder' => __('restaurant.customer_note'),
                        'rows' => 3,
                    ]) !!}
                </div>
            </div>

        </div>

        <div class="col-sm-12">
            <button type="submit" class="btn-save">
                Save Book
            </button>
        </div>


        {!! Form::close() !!}
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    flatpickr("#start_time", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
    });

    function selectService(element, serviceId, serviceName) {
        document.querySelectorAll('.service-card').forEach(card => {
            card.classList.remove('selected');
        });
        element.classList.add('selected');
        document.getElementById('service_id').value = serviceId;
        checkFormCompletion();
    }

    function checkFormCompletion() {
        const requiredFields = [
            $('#gehad_category_id').val(),
            $('#gehad_model_id').val(),
            $('#color').val(),
            $('#plate_number').val(),
            $('#manufacturing_year').val(),
            $('#car_type').val()
        ];

        const allFilled = requiredFields.every(field =>
            field !== '' && field !== null && field !== undefined
        );

        if (allFilled) {
            $('.btn-save').show();
        } else {
            $('.btn-save').hide();
        }
    }

    $(document).ready(function() {
        $('#gehad_category_id, #gehad_model_id, #color, #plate_number, #manufacturing_year, #car_type')
            .on('change input', checkFormCompletion);

        checkFormCompletion();

        $('#gehad_category_id').on('change', function() {
            var brandId = $(this).val();
            $.ajax({
                url: '/contact/bookings/get-models/' + brandId,
                type: 'GET',
                success: function(response) {
                    $('#gehad_model_id').empty();
                    $('#gehad_model_id').append('<option value="">Select Model</option>');
                    if (response.length) {
                        $.each(response, function(index, model) {
                            $('#gehad_model_id').append('<option value="' + model
                                .id + '">' +
                                model.name + '</option>');
                        });
                    } else {
                        $('#gehad_model_id').append(
                            '<option value="">No models available</option>');
                    }
                    checkFormCompletion();
                },
                error: function() {
                    alert('Error fetching models.');
                }
            });
        });
    });
</script>
