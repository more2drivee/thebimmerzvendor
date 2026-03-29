<div class="modal fade" id="add_booking_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            {!! Form::open([
                'url' => action([\Modules\Crm\Http\Controllers\ContactBookingController::class, 'store']),
                'method' => 'post',
                'id' => 'add_booking_form',
            ]) !!}
            {!! Form::hidden('contact_id', auth()->user()->crm_contact_id) !!}
            {!! Form::hidden('booking_status', 'waiting') !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang('restaurant.add_booking')</h4>
            </div>

            {{-- <div class="col-md-4">
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
                        <select name="gehad_category_id" id="gehad_category_id" class="form-control" required>
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
            </div> --}}

            {{-- <div class="col-md-4">
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
                    <label for="chassis_number">@lang('restaurant.chassis_number'):</label>
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

            <div class="col-md-4">
                <div class="form-group">
                    <label for="plate_number">@lang('restaurant.plate_number'):</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-key"></i>
                        </span>
                        <input type="text" name="plate_number" id="plate_number" class="form-control"
                            placeholder="Plate Number" value="{{ old('plate_number') }}" required>
                    </div>
                    @error('plate_number')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label for="manufacturing_year">Manufacturing Year</label>
                    <div class="input-group">
                        <select class="form-control" id="manufacturing_year" name="manufacturing_year" required>
                            <option value="">Select Year</option>
                            @foreach (range(date('Y'), 1990) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('manufacturing_year')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div> --}}

            <div class="modal-body">
                @if (count($business_locations) == 1)
                    @php
                        $default_location = current(array_keys($business_locations->toArray()));
                    @endphp
                @else
                    @php $default_location = null; @endphp
                @endif
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>
                                {!! Form::select('location_id', $business_locations, $default_location, [
                                    'class' => 'form-control',
                                    'placeholder' => __('purchase.business_location'),
                                    'required',
                                    'id' => 'booking_location_id',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="brand_id">@lang('car.brand'):</label>
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
                                <select name="gehad_category_id" id="gehad_category_id" class="form-control" required>
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
                            <label for="gehad_model_id">@lang('car.model')</label>
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
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="color">@lang('car.color'):</label>
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-paint-brush"></i>
                                </span>
                                <input type="text" name="color" id="color" class="form-control"
                                    placeholder="Color" value="{{ old('color') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="chassis_number">@lang('car.chassis'):</label>
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

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="plate_number">@lang('car.plate'):</label>
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-key"></i>
                                </span>
                                <input type="text" name="plate_number" id="plate_number" class="form-control"
                                    placeholder="Plate Number" value="{{ old('plate_number') }}" required>
                            </div>
                            @error('plate_number')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="manufacturing_year">@lang('car.manufacturing')</label>
                            <div class="input-group">
                                <select class="form-control" id="manufacturing_year" name="manufacturing_year" required>
                                    <option value="">Select Year</option>
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
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="service_type">@lang('restaurant.service_type'):</label>
                            <?php $services = DB::table('types_of_services')->select('name', 'id')->get(); ?>
                            <select name="service_type" id="service_type" class="form-control">
                                <option value="">@lang('car.selectcartype')</option>
                                <?php foreach($services as $service) { ?>
                                <option value="{{$service->id}}">{{$service->name}}</option>
                                <?php } ?>
                            </select>
                            @error('service_type')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('status', __('restaurant.start_time') . ':*') !!}
                            <div class='input-group date'>
                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                                {!! Form::text('booking_start', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('restaurant.start_time'),
                                    'required',
                                    'id' => 'start_time',
                                    'readonly',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('status', __('restaurant.end_time') . ':*') !!}
                            <div class='input-group date'>
                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                                {!! Form::text('booking_end', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('restaurant.end_time'),
                                    'required',
                                    'id' => 'end_time',
                                    'readonly',
                                ]) !!}
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

                <div class="modal-footer">
                    <button type="submit"
                        class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
                    <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white"
                        data-dismiss="modal">@lang('messages.close')</button>
                </div>

                {!! Form::close() !!}

            </div><!-- /.modal-content -->

        </div><!-- /.modal-dialog -->
    </div>
