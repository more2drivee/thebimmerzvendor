@if($__is_repair_enabled)

@php
	$default = [
		'show_service_no' => 1,
		'service_no_label' => 'Service No.',

		'show_license_plate' => 1,
		'license_plate_label' => 'License Plate',

		'show_vin_number' => 1,
		'vin_number_label' => 'VIN Number',

		'show_repair_serial_no' => 1,
		'repair_serial_no_label' => 'Odometer',

		'show_car_brand' => 1,
		'car_brand_label' => 'Car Brand',

		'show_car_model' => 1,
		'car_model_label' => 'Car Model',

		'show_car_color' => 1,
		'car_color_label' => 'Car Color',

		'show_chassis_number' => 1,
		'chassis_number_label' => 'Chassis Number',

		'show_plate_number' => 1,
		'plate_number_label' => 'Plate Number',

		'show_odometer' => 1,
		'odometer_label' => 'Odometer',

		'show_manufacturing_year' => 1,
		'manufacturing_year_label' => 'Manufacturing Year',
	];

	if(!empty($edit_il)){
		$default = [
			'show_service_no' => !empty($module_info['repair']['show_service_no']) ? 1 : 0,
			'service_no_label' => !empty($module_info['repair']['service_no_label']) ? $module_info['repair']['service_no_label'] : '',

			'show_license_plate' => !empty($module_info['repair']['show_license_plate']) ? 1 : 0,
			'license_plate_label' => !empty($module_info['repair']['license_plate_label']) ? $module_info['repair']['license_plate_label'] : '',

			'show_vin_number' => !empty($module_info['repair']['show_vin_number']) ? 1 : 0,
			'vin_number_label' => !empty($module_info['repair']['vin_number_label']) ? $module_info['repair']['vin_number_label'] : '',

			'show_repair_serial_no' => !empty($module_info['repair']['show_repair_serial_no']) ? 1 : 0,
			'repair_serial_no_label' => !empty($module_info['repair']['repair_serial_no_label']) ? $module_info['repair']['repair_serial_no_label'] : '',

			'show_car_brand' => !empty($module_info['repair']['show_car_brand']) ? 1 : 0,
			'car_brand_label' => !empty($module_info['repair']['car_brand_label']) ? $module_info['repair']['car_brand_label'] : '',

			'show_car_model' => !empty($module_info['repair']['show_car_model']) ? 1 : 0,
			'car_model_label' => !empty($module_info['repair']['car_model_label']) ? $module_info['repair']['car_model_label'] : '',

			'show_car_color' => !empty($module_info['repair']['show_car_color']) ? 1 : 0,
			'car_color_label' => !empty($module_info['repair']['car_color_label']) ? $module_info['repair']['car_color_label'] : '',

			'show_chassis_number' => !empty($module_info['repair']['show_chassis_number']) ? 1 : 0,
			'chassis_number_label' => !empty($module_info['repair']['chassis_number_label']) ? $module_info['repair']['chassis_number_label'] : '',

			'show_plate_number' => !empty($module_info['repair']['show_plate_number']) ? 1 : 0,
			'plate_number_label' => !empty($module_info['repair']['plate_number_label']) ? $module_info['repair']['plate_number_label'] : '',

			'show_odometer' => !empty($module_info['repair']['show_odometer']) ? 1 : 0,
			'odometer_label' => !empty($module_info['repair']['odometer_label']) ? $module_info['repair']['odometer_label'] : '',

			'show_manufacturing_year' => !empty($module_info['repair']['show_manufacturing_year']) ? 1 : 0,
			'manufacturing_year_label' => !empty($module_info['repair']['manufacturing_year_label']) ? $module_info['repair']['manufacturing_year_label'] : '',
		];
	}

@endphp

	@component('components.widget', ['class' => 'box-solid', 'title' => __('repair::lang.jobsheet_fields')])
		<div class="row">
			<div class="col-sm-3">
				<div class="form-group">
					<div class="checkbox">
						<label>
							{!! Form::checkbox('module_info[repair][show_service_no]', 1, $default['show_service_no'], ['class' => 'input-icheck']); !!} @lang('repair::lang.show_service_no')
						</label>
					</div>
				</div>
			</div>
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('module_info[repair][service_no_label]', __('repair::lang.service_no_label') . ':' ) !!}
					{!! Form::text('module_info[repair][service_no_label]', $default['service_no_label'], ['class' => 'form-control', 'placeholder' => __('repair::lang.service_no_label') ]); !!}
				</div>
			</div>

			<div class="col-sm-3">
				<div class="form-group">
					<div class="checkbox">
						<label>
							{!! Form::checkbox('module_info[repair][show_license_plate]', 1, $default['show_license_plate'], ['class' => 'input-icheck']); !!} @lang('repair::lang.show_license_plate')
						</label>
					</div>
				</div>
			</div>
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('module_info[repair][license_plate_label]', __('repair::lang.license_plate_label') . ':' ) !!}
					{!! Form::text('module_info[repair][license_plate_label]', $default['license_plate_label'], ['class' => 'form-control', 'placeholder' => __('repair::lang.license_plate_label') ]); !!}
				</div>
			</div>

			<div class="col-sm-3">
				<div class="form-group">
					<div class="checkbox">
						<label>
							{!! Form::checkbox('module_info[repair][show_vin_number]', 1, $default['show_vin_number'], ['class' => 'input-icheck']); !!} @lang('repair::lang.show_vin_number')
						</label>
					</div>
				</div>
			</div>
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('module_info[repair][vin_number_label]', __('repair::lang.vin_number_label') . ':' ) !!}
					{!! Form::text('module_info[repair][vin_number_label]', $default['vin_number_label'], ['class' => 'form-control', 'placeholder' => __('repair::lang.vin_number_label') ]); !!}
				</div>
			</div>

			<div class="col-sm-3">
				<div class="form-group">
					<div class="checkbox">
						<label>
							{!! Form::checkbox('module_info[repair][show_car_brand]', 1, $default['show_car_brand'], ['class' => 'input-icheck']); !!} @lang('repair::lang.show_car_brand')
						</label>
					</div>
				</div>
			</div>
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('module_info[repair][car_brand_label]', __('repair::lang.car_brand_label') . ':' ) !!}
					{!! Form::text('module_info[repair][car_brand_label]', $default['car_brand_label'], ['class' => 'form-control', 'placeholder' => __('repair::lang.car_brand_label') ]); !!}
				</div>
			</div>

			<div class="col-sm-3">
				<div class="form-group">
					<div class="checkbox">
						<label>
							{!! Form::checkbox('module_info[repair][show_car_model]', 1, $default['show_car_model'], ['class' => 'input-icheck']); !!} @lang('repair::lang.show_car_model')
						</label>
					</div>
				</div>
			</div>
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('module_info[repair][car_model_label]', __('repair::lang.car_model_label') . ':' ) !!}
					{!! Form::text('module_info[repair][car_model_label]', $default['car_model_label'], ['class' => 'form-control', 'placeholder' => __('repair::lang.car_model_label') ]); !!}
				</div>
			</div>

			<div class="col-sm-3">
				<div class="form-group">
					<div class="checkbox">
						<label>
							{!! Form::checkbox('module_info[repair][show_car_color]', 1, $default['show_car_color'], ['class' => 'input-icheck']); !!} @lang('repair::lang.show_car_color')
						</label>
					</div>
				</div>
			</div>
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('module_info[repair][car_color_label]', __('repair::lang.car_color_label') . ':' ) !!}
					{!! Form::text('module_info[repair][car_color_label]', $default['car_color_label'], ['class' => 'form-control', 'placeholder' => __('repair::lang.car_color_label') ]); !!}
				</div>
			</div>
		</div>
    @endcomponent
@endif
