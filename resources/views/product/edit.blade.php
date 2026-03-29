@extends('layouts.app')
@section('title', __('product.edit_product'))

@section('content')

<style>
    /* Custom dropdown styles for product compatibility modals */
    .product-custom-dropdown {
        position: relative;
        width: 100%;
    }

    .product-custom-dropdown-search {
        width: 100%;
        padding: 8px;
        border: 1px solid #ced4da;
        border-bottom: none;
        border-radius: 4px 4px 0 0;
    }

    .product-custom-dropdown select {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .product-custom-dropdown-options {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ced4da;
        border-radius: 0 0 4px 4px;
        background-color: white;
        z-index: 1000;
    }

    .product-custom-dropdown-option {
        padding: 8px 12px;
        cursor: pointer;
    }

    .product-custom-dropdown-option:hover {
        background-color: #f8f9fa;
    }

    .product-custom-dropdown-option.selected {
        background-color: #e9ecef;
    }

    .product-custom-dropdown-option.hidden {
        display: none;
    }

    .product-custom-dropdown-display {
        padding: 6px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        background-color: white;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 34px;
    }

    .product-custom-dropdown-display:after {
        content: '\25BC';
        font-size: 0.8em;
    }

    .product-custom-dropdown.open .product-custom-dropdown-display {
        border-radius: 4px 4px 0 0;
        border-bottom: none;
    }

    .product-custom-dropdown.open .product-custom-dropdown-display:after {
        content: '\25B2';
    }

    .product-custom-dropdown-options-container {
        display: none;
        position: absolute;
        width: 100%;
        z-index: 1000;
        background-color: white;
    }

    .product-custom-dropdown.open .product-custom-dropdown-options-container {
        display: block;
    }
</style>

@php
  $is_image_required = !empty($common_settings['is_product_image_required']) && empty($product->image);
@endphp

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('product.edit_product')</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
{!! Form::open(['url' => action([\App\Http\Controllers\ProductController::class, 'update'] , [$product->id] ), 'method' => 'PUT', 'id' => 'product_add_form',
        'class' => 'product_form', 'files' => true ]) !!}
    <input type="hidden" id="product_id" value="{{ $product->id }}">

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('name', __('product.product_name') . ':*') !!}
                  {!! Form::text('name', $product->name, ['class' => 'form-control', 'required',
                  'placeholder' => __('product.product_name'),
                  'id' => 'product_name_input'

                  ])

                  !!}
              </div>
            </div>

            {{-- <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('sku', __('product.sku')  . ':*') !!} @show_tooltip(__('tooltip.sku'))
                {!! Form::text('sku', $product->sku, ['class' => 'form-control',
                'placeholder' => __('product.sku'), 'required']) !!}
              </div>
            </div> --}}

            <div class="col-sm-4">
              <div class="form-group">
                  {!! Form::label('sku', __('product.sku') . ':') !!} @show_tooltip(__('tooltip.sku'))
                  <div class="input-group" style="display: flex; flex-wrap: nowrap;">
                      {!! Form::text('sku', $product->sku, ['class' => 'form-control', 'id' => 'sku_input', 'placeholder' => __('product.sku'), 'style' => 'flex: 1;']) !!}
                      <div class="input-group-append">
                          <button type="button" class="btn btn-primary ai-btn" id="generate_sku">
                              <i class="fas fa-robot"></i>
                          </button>
                      </div>
                  </div>
              </div>
          </div>

            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('barcode_type', __('product.barcode_type') . ':*') !!}
                  {!! Form::select('barcode_type', $barcode_types, $product->barcode_type, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'required']) !!}
              </div>
            </div>

            <div class="clearfix"></div>

            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('unit_id', __('product.unit') . ':*') !!}
                <div class="input-group">
                  {!! Form::select('unit_id', $units, $product->unit_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'required']) !!}
                  <span class="input-group-btn">
                    <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat quick_add_unit btn-modal" data-href="{{action([\App\Http\Controllers\UnitController::class, 'create'], ['quick_add' => true])}}" title="@lang('unit.add_unit')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                  </span>
                </div>
              </div>
            </div>

            <div class="col-sm-4 @if(!session('business.enable_sub_units')) hide @endif">
              <div class="form-group">
                {!! Form::label('sub_unit_ids', __('lang_v1.related_sub_units') . ':') !!} @show_tooltip(__('lang_v1.sub_units_tooltip'))

                <select name="sub_unit_ids[]" class="form-control select2" multiple id="sub_unit_ids">
                  @foreach($sub_units as $sub_unit_id => $sub_unit_value)
                    <option value="{{$sub_unit_id}}"
                      @if(is_array($product->sub_unit_ids) &&in_array($sub_unit_id, $product->sub_unit_ids))   selected
                      @endif>{{$sub_unit_value['name']}}</option>
                  @endforeach
                </select>
              </div>
            </div>

            @if(!empty($common_settings['enable_secondary_unit']))
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('secondary_unit_id', __('lang_v1.secondary_unit') . ':') !!} @show_tooltip(__('lang_v1.secondary_unit_help'))
                        {!! Form::select('secondary_unit_id', $units, $product->secondary_unit_id, ['class' => 'form-control select2']) !!}
                    </div>
                </div>
            @endif

            <div class="col-sm-4 @if(!session('business.enable_brand')) hide @endif">
              <div class="form-group">
                {!! Form::label('brand_id', __('product.brand') . ':') !!}
                <div class="input-group">
                  {!! Form::select('brand_id', $brands, $product->brand_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']) !!}
                  <span class="input-group-btn">
                    <button type="button" @if(!auth()->user()->can('brand.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\BrandController::class, 'create'], ['quick_add' => true])}}" title="@lang('brand.add_brand')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                  </span>
                </div>
              </div>
            </div>
            <div class="col-sm-4 @if(!session('business.enable_category')) hide @endif">
              <div class="form-group">
                {!! Form::label('category_id', __('product.category') . ':') !!}
                  {!! Form::select('category_id', $categories, $product->category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'id' => 'category_id']) !!}
              </div>
            </div>

            <div class="col-sm-4 @if(!(session('business.enable_category') && session('business.enable_sub_category'))) hide @endif">
              <div class="form-group">
                {!! Form::label('sub_category_id', __('product.sub_category')  . ':') !!}
                  {!! Form::select('sub_category_id', $sub_categories, $product->sub_category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'id' => 'sub_category_id']) !!}
              </div>
            </div>

            <div class="col-sm-4 @if(!(session('business.enable_category') && session('business.enable_sub_category') && !empty(session('business.common_settings.enable_sub_sub_category')))) hide @endif">
              <div class="form-group">
                {!! Form::label('sub_sub_category_id', __('product.sub_sub_category') . ':') !!}
                  {!! Form::select('sub_sub_category_id', $sub_sub_categories, $product->sub_sub_category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'id' => 'sub_sub_category_id']) !!}
              </div>
            </div>

            <div class="col-sm-4 @if(!(session('business.enable_category') && session('business.enable_sub_category') && !empty(session('business.common_settings.enable_sub_sub_category')))) hide @endif">
              <div class="form-group">
                {!! Form::label('sub_sub_sub_category_id', __('product.sub_sub_sub_category') . ':') !!}
                  {!! Form::select('sub_sub_sub_category_id', $sub_sub_sub_categories, $product->sub_sub_sub_category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'id' => 'sub_sub_sub_category_id']) !!}
              </div>
            </div>

            <div class="col-sm-4">
            <div class="form-group">
                <br>
                <label>
                {!! Form::checkbox('is_ecom', 1, $product->is_ecom, ['class' => 'input-icheck']) !!} <strong>E-commerce</strong>
                </label>
            </div>
            </div>

            <div class="col-sm-12">
              <div class="form-group">
                {!! Form::label('product_locations', __('business.business_locations') . ':') !!} @show_tooltip(__('lang_v1.product_location_help'))
                <div class="table-responsive">
                  <table class="table table-bordered table-striped" id="product_locations_table">
                    <thead>
                      <tr>
                        <th>@lang('business.business_location')</th>
                        <th>@lang('messages.action')</th>
                      </tr>
                    </thead>
                    <tbody>
                      @php
                        $selected_locations = $product->product_locations->pluck('id')->toArray();
                      @endphp
                      @foreach($selected_locations as $location_id)
                        <tr>
                          <td>
                            {{ $business_locations[$location_id] ?? '' }}
                            <input type="hidden" name="product_locations[]" value="{{ $location_id }}">
                          </td>
                          <td>
                            <button type="button" class="btn btn-danger btn-xs remove-location-row"><i class="fa fa-times"></i></button>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                    <tfoot>
                      <tr>
                        <td colspan="2">
                          <button type="button" class="btn btn-primary" id="add-location-row">
                            <i class="fa fa-plus"></i> @lang('messages.add')
                          </button>
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>



        </div>

        <!-- Models and Years Section -->
        <div class="col-sm-12">
            <div class="form-group">
                {!! Form::label('product-compatibility', __('lang_v1.models_and_years') . ':') !!}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="product_compatibility_table">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.car_model')</th>
                                <th>@lang('lang_v1.brand_category')</th>
                                <th>@lang('lang_v1.from_year')</th>
                                <th>@lang('lang_v1.to_year')</th>
                                <th>Motor CC</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                        <tbody>
                                @foreach ($compatibility_data as $index => $compatibility)
                                    <tr>
                                        <td>
                                            <input type="text" class="form-control" readonly value="{{ $compatibility['model_display_name'] ?? '' }}">
                                            <input type="hidden" name="compatibility[{{ $index }}][model_id]" value="{{ $compatibility['model_id'] }}">
                                        </td>
                                        <td>
                                            @php
                                                $brand_category_id = $compatibility['brand_category_id'] ?? null;
                                                $brand_category_name = '';
                                                if ($brand_category_id && isset($brand_category[$brand_category_id])) {
                                                    $brand_category_name = $brand_category[$brand_category_id];
                                                }
                                            @endphp
                                            <input type="text" class="form-control" readonly value="{{ $brand_category_name }}">
                                            <input type="hidden" name="compatibility[{{ $index }}][brand_category_id]" value="{{ $brand_category_id }}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" readonly value="{{ $compatibility['from_year'] }}">
                                            <input type="hidden" name="compatibility[{{ $index }}][from_year]" value="{{ $compatibility['from_year'] }}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" readonly value="{{ $compatibility['to_year'] }}">
                                            <input type="hidden" name="compatibility[{{ $index }}][to_year]" value="{{ $compatibility['to_year'] }}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" readonly value="{{ $compatibility['motor_cc'] ?? '' }}">
                                            <input type="hidden" name="compatibility[{{ $index }}][motor_cc]" value="{{ $compatibility['motor_cc'] ?? '' }}">
                                        </td>
                                        <td>
                            <button type="button" class="btn btn-primary btn-xs edit-compatibility-row" data-index="{{ $index }}" data-model-id="{{ $compatibility['model_id'] }}" data-brand-category-id="{{ $brand_category_id }}"><i class="fa fa-edit"></i></button>
                            <button type="button" class="btn btn-danger btn-xs remove-compatibility-row"><i class="fa fa-trash"></i></button>
                        </td>
                                    </tr>
                                @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">
                                    <button type="button" class="btn btn-primary" id="add-compatibility-row">
                                        <i class="fa fa-plus"></i> @lang('lang_v1.add_compatibility')
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="clearfix"></div>

            @if(!empty($common_settings['enable_product_warranty']))
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('warranty_id', __('lang_v1.warranty') . ':') !!}
                {!! Form::select('warranty_id', $warranties, $product->warranty_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]) !!}
              </div>
            </div>
            @endif
            <!-- include module fields -->
            {{-- @if(!empty($pos_module_data))
                @foreach($pos_module_data as $key => $value)
                    @if(!empty($value['view_path']))
                        @includeIf($value['view_path'], ['view_data' => $value['view_data']])
                    @endif
                @endforeach
            @endif --}}

            <div class="clearfix"></div>
            <div class="col-md-6">
              <div class="col-sm-4">
                <div class="form-group">
                <br>
                  <label>
                    {!! Form::checkbox('enable_stock', 1, $product->enable_stock, ['class' => 'input-icheck', 'id' => 'enable_stock']) !!} <strong>@lang('product.manage_stock')</strong>
                  </label>@show_tooltip(__('tooltip.enable_stock')) <p class="help-block"><i>@lang('product.enable_stock_help')</i></p>
                </div>
              </div>
              <div class="col-sm-4" id="alert_quantity_div" @if(!$product->enable_stock) style="display:none" @endif>
                <div class="form-group">
                  {!! Form::label('alert_quantity', __('product.alert_quantity') . ':') !!} @show_tooltip(__('tooltip.alert_quantity'))
                  {!! Form::text('alert_quantity', $alert_quantity, ['class' => 'form-control input_number',
                  'placeholder' => __('product.alert_quantity') , 'min' => '0']) !!}
                </div>
              </div>
              
              <div class="col-sm-4" id="stock_location_div" @if(!$product->enable_stock) style="display:none" @endif>
                <div class="form-group">
                  {!! Form::label('stock_location', __('lang_v1.stock_location') . ':') !!} @show_tooltip(__('lang_v1.stock_location_help'))
                  {!! Form::text('stock_location', $product->stock_location, [
                    'class' => 'form-control',
                    'placeholder' => __('lang_v1.stock_location_placeholder')
                  ]) !!}
                </div>
              </div>
            </div>
            <div class="col-sm-8">
              <div class="form-group">
                {!! Form::label('product_description', __('lang_v1.product_description') . ':') !!}
                  {!! Form::textarea('product_description', $product->product_description, ['class' => 'form-control']) !!}
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('image', __('lang_v1.product_image') . ':') !!}
                {!! Form::file('image', ['id' => 'upload_image', 'accept' => 'image/*', 'required' => $is_image_required]) !!}
                <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]). @lang('lang_v1.aspect_ratio_should_be_1_1') @if(!empty($product->image)) <br> @lang('lang_v1.previous_image_will_be_replaced') @endif</p></small>
              </div>
            </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('product_brochure', __('lang_v1.product_brochure') . ':') !!}
                {!! Form::file('product_brochure', ['id' => 'product_brochure', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]) !!}
                <small>
                    <p class="help-block">
                        @lang('lang_v1.previous_file_will_be_replaced')<br>
                        @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                        @includeIf('components.document_help_text')
                    </p>
                </small>
              </div>
            </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
        @if(session('business.enable_product_expiry'))

          @if(session('business.expiry_type') == 'add_expiry')
            @php
              $expiry_period = 12;
              $hide = true;
            @endphp
          @else
            @php
              $expiry_period = null;
              $hide = false;
            @endphp
          @endif
          <div class="col-sm-4 @if($hide) hide @endif">
            <div class="form-group">
              <div class="multi-input">
                @php
                  $disabled = false;
                  $disabled_period = false;
                  if( empty($product->expiry_period_type) || empty($product->enable_stock) ){
                    $disabled = true;
                  }
                  if( empty($product->enable_stock) ){
                    $disabled_period = true;
                  }
                @endphp
                  {!! Form::label('expiry_period', __('product.expires_in') . ':') !!}<br>
                  {!! Form::text('expiry_period', @num_format($product->expiry_period), ['class' => 'form-control pull-left input_number',
                    'placeholder' => __('product.expiry_period'), 'style' => 'width:60%;', 'disabled' => $disabled]) !!}
                  {!! Form::select('expiry_period_type', ['months'=>__('product.months'), 'days'=>__('product.days'), '' =>__('product.not_applicable') ], $product->expiry_period_type, ['class' => 'form-control select2 pull-left', 'style' => 'width:40%;', 'id' => 'expiry_period_type', 'disabled' => $disabled_period]) !!}
              </div>
            </div>
          </div>
          @endif
          <div class="col-sm-4">
            <div class="checkbox">
              <label>
                {!! Form::checkbox('enable_sr_no', 1, $product->enable_sr_no, ['class' => 'input-icheck']) !!} <strong>@lang('lang_v1.enable_imei_or_sr_no')</strong>
              </label>
              @show_tooltip(__('lang_v1.tooltip_sr_no'))
            </div>
          </div>

          <div class="col-sm-4">
          <div class="form-group">
            <br>
            <label>
              {!! Form::checkbox('not_for_selling', 1, $product->not_for_selling, ['class' => 'input-icheck']) !!} <strong>@lang('lang_v1.not_for_selling')</strong>
            </label> @show_tooltip(__('lang_v1.tooltip_not_for_selling'))
          </div>
        </div>



        <div class="clearfix"></div>

        <!-- Rack, Row & position number -->
        @if(session('business.enable_racks') || session('business.enable_row') || session('business.enable_position'))
          <div class="col-md-12">
            <h4>@lang('lang_v1.rack_details'):
              @show_tooltip(__('lang_v1.tooltip_rack_details'))
            </h4>
          </div>
          @foreach($business_locations as $id => $location)
            <div class="col-sm-3">
              <div class="form-group">
                {!! Form::label('rack_' . $id,  $location . ':') !!}


                  @if(!empty($rack_details[$id]))
                    @if(session('business.enable_racks'))
                      {!! Form::text('product_racks_update[' . $id . '][rack]', $rack_details[$id]['rack'], ['class' => 'form-control', 'id' => 'rack_' . $id]) !!}
                    @endif

                    @if(session('business.enable_row'))
                      {!! Form::text('product_racks_update[' . $id . '][row]', $rack_details[$id]['row'], ['class' => 'form-control']) !!}
                    @endif

                    @if(session('business.enable_position'))
                      {!! Form::text('product_racks_update[' . $id . '][position]', $rack_details[$id]['position'], ['class' => 'form-control']) !!}
                    @endif
                  @else
                    {!! Form::text('product_racks[' . $id . '][rack]', null, ['class' => 'form-control', 'id' => 'rack_' . $id, 'placeholder' => __('lang_v1.rack')]) !!}

                    {!! Form::text('product_racks[' . $id . '][row]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.row')]) !!}

                    {!! Form::text('product_racks[' . $id . '][position]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.position')]) !!}
                  @endif

              </div>
            </div>
          @endforeach
        @endif


        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('weight',  __('lang_v1.weight') . ':') !!}
            {!! Form::text('weight', $product->weight, ['class' => 'form-control', 'placeholder' => __('lang_v1.weight')]) !!}
          </div>
        </div>
        <div class="clearfix"></div>

        @php
            $custom_labels = json_decode(session('business.custom_labels'), true);
            $product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];
            $product_cf_details = !empty($custom_labels['product_cf_details']) ? $custom_labels['product_cf_details'] : [];
        @endphp
        <!--custom fields-->

        @foreach($product_custom_fields as $index => $cf)
            @if(!empty($cf))
                @php
                    $db_field_name = 'product_custom_field' . $loop->iteration;
                    $cf_type = !empty($product_cf_details[$loop->iteration]['type']) ? $product_cf_details[$loop->iteration]['type'] : 'text';
                    $dropdown = !empty($product_cf_details[$loop->iteration]['dropdown_options']) ? explode(PHP_EOL, $product_cf_details[$loop->iteration]['dropdown_options']) : [];
                @endphp

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label($db_field_name, $cf . ':') !!}
                        @if(in_array($cf_type, ['text', 'date']))
                            <input type="{{$cf_type}}" name="{{$db_field_name}}" id="{{$db_field_name}}"
                            value="{{$product->$db_field_name}}" class="form-control" placeholder="{{$cf}}">
                        @elseif($cf_type == 'dropdown')
                            {!! Form::select($db_field_name, $dropdown, $product->$db_field_name, ['placeholder' => $cf, 'class' => 'form-control select2']) !!}
                        @endif
                    </div>
                </div>
            @endif
        @endforeach

        <div class="col-sm-3">
          <div class="form-group">
            {!! Form::label('preparation_time_in_minutes',  __('lang_v1.preparation_time_in_minutes') . ':') !!}
            {!! Form::number('preparation_time_in_minutes', $product->preparation_time_in_minutes, ['class' => 'form-control', 'placeholder' => __('lang_v1.preparation_time_in_minutes')]) !!}
          </div>
        </div>
        <!--custom fields-->
        @include('layouts.partials.module_form_part')
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])

        <div class="row">
            <div class="col-sm-4 @if(!session('business.enable_price_tax')) hide @endif">
              <div class="form-group">
                {!! Form::label('tax', __('product.applicable_tax') . ':') !!}
                  {!! Form::select('tax', $taxes, $product->tax, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2'], $tax_attributes) !!}
              </div>
            </div>

            <div class="col-sm-4 @if(!session('business.enable_price_tax')) hide @endif">
              <div class="form-group">
                {!! Form::label('tax_type', __('product.selling_price_tax_type') . ':*') !!}
                  {!! Form::select('tax_type',['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')], $product->tax_type,
                  ['class' => 'form-control select2', 'required']) !!}
              </div>
            </div>

            <div class="clearfix"></div>
            <div class="col-sm-4">
              <div class="form-group">
                {!! Form::label('type', __('product.product_type') . ':*') !!} @show_tooltip(__('tooltip.product_type'))
                {!! Form::select('type', $product_types, $product->type, ['class' => 'form-control select2', 'required', 'disabled', 'data-action' => 'edit', 'data-product_id' => $product->id]) !!}
              </div>
            </div>

            <div class="form-group col-sm-12" id="product_form_part">
                @if($product->type == 'single')
                    @php
                        // Load product variation details directly in the view for single products
                        $product_deatails = \App\ProductVariation::where('product_id', $product->id)
                            ->with(['variations', 'variations.media'])
                            ->first();
                    @endphp
                    @if(!empty($product_deatails))
                        @include('product.partials.edit_single_product_form_part', ['product_deatails' => $product_deatails, 'action' => 'edit'])
                    @endif
                @endif
            </div>

            {{-- Hidden fields used only for JS mirroring/debug; no names to avoid overriding visible inputs --}}
            <input type="hidden" id="single_variation_id_hidden">
            <input type="hidden" id="single_dpp_hidden">
            <input type="hidden" id="single_dpp_inc_tax_hidden">
            <input type="hidden" id="profit_percent_hidden">
            <input type="hidden" id="single_dsp_hidden">
            <input type="hidden" id="single_dsp_inc_tax_hidden">

            <input type="hidden" id="variation_counter" value="0">
            <input type="hidden" id="default_profit_percent" value="{{ $default_profit_percent }}">
            </div>

    @endcomponent

  <div class="row">
    <input type="hidden" name="submit_type" id="submit_type">
        <div class="col-sm-12">
          <div class="text-center">
            <div class="btn-group">
              @if($selling_price_group_count)
                <button type="submit" value="submit_n_add_selling_prices" class="tw-dw-btn tw-dw-btn-warning tw-text-white tw-dw-btn-lg submit_product_form">@lang('lang_v1.save_n_add_selling_price_group_prices')</button>
              @endif

              @can('product.opening_stock')
              <button type="submit" @if(empty($product->enable_stock)) disabled="true" @endif id="opening_stock_button"  value="update_n_edit_opening_stock" class="tw-dw-btn tw-text-white tw-dw-btn-lg bg-purple submit_product_form">@lang('lang_v1.update_n_edit_opening_stock')</button>
              @endif

              <button type="submit" value="save_n_add_another" class="tw-dw-btn tw-text-white tw-dw-btn-lg bg-maroon submit_product_form">@lang('lang_v1.update_n_add_another')</button>

              <button type="submit" value="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-lg submit_product_form">@lang('messages.update')</button>
            </div>
          </div>
        </div>
  </div>
{!! Form::close() !!}
</section>
<!-- /.content -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>

  <script type="text/javascript">
    // Define route for get.product.details
    var route_get_product_details = "{{ route('get.product.details') }}";
  </script>


    <script type="text/javascript">
        $(document).ready(function() {
            // Initialize select2 in modals (excluding compatibility modal dropdowns)
            $('#modal_location_id, #modal_category_id').select2({
                dropdownParent: $('.modal.fade')
            });

            // Initialize custom dropdowns for compatibility modals
            initCompatibilityDropdowns();

            // Handle add compatibility row button
            $('#add-compatibility-row').on('click', function() {
                // Reset modal form
                resetCustomDropdown('modal-brand');
                resetCustomDropdown('modal-model');
                $('#modal_from_year').val('');
                $('#modal_to_year').val('');
                $('#add_compatibility_modal').modal('show');
            });

            // Remove compatibility row
            $(document).on('click', '.remove-compatibility-row', function() {
                $(this).closest('tr').remove();
                // Re-index rows to maintain correct array keys on submission
                $('#product_compatibility_table tbody tr').each(function(index) {
                    $(this).find('input[name^="compatibility["]').each(function() {
                        const name = $(this).attr('name');
                        if (name) {
                            const newName = name.replace(/compatibility\[\d+\]/, `compatibility[${index}]`);
                            $(this).attr('name', newName);
                        }
                    });
                });
                if (typeof toastr !== 'undefined') {
                    toastr.info('@lang("lang_v1.compatibility_removed")');
                }
            });

            // Create brand category options for use in template
            const brandCategoryOptions = JSON.parse('{!! json_encode($brand_category) !!}');

            // Add compatibility row from modal
            $('#add_compatibility_btn').on('click', function() {
                const brandCategoryId = $('#modal_brand_category_id').val();
                const brandCategoryText = $('#modal-brand-dropdown').attr('data-selected-text') || '';
                const modelId = $('#modal_model_id').val();
                const modelText = $('#modal-model-dropdown').attr('data-selected-text') || '';
                const fromYear = $('#modal_from_year').val();
                const toYear = $('#modal_to_year').val();
                const motorCc = $('#modal_motor_cc').val();

                // Basic Validation - Brand category is now required
                if (!brandCategoryId || !modelId || !fromYear || !toYear) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('@lang("messages.fill_required_fields")');
                    }
                    return;
                }

                if (parseInt(fromYear) > parseInt(toYear)) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('@lang("lang_v1.from_year_greater_than_to_year")');
                    }
                    return;
                }

                // Get current row count
                const rowCount = $('#product_compatibility_table tbody tr').length;

                // Create new row
                const newRow = `
                    <tr>
                        <td>
                            <input type="text" class="form-control" readonly value="${modelText}">
                            <input type="hidden" name="compatibility[${rowCount}][model_id]" value="${modelId}">
                        </td>
                        <td>
                            <input type="text" class="form-control" readonly value="${brandCategoryId ? brandCategoryOptions[brandCategoryId] : ''}">
                            <input type="hidden" name="compatibility[${rowCount}][brand_category_id]" value="${brandCategoryId}">
                        </td>
                        <td>
                            <input type="text" class="form-control" readonly value="${fromYear}">
                            <input type="hidden" name="compatibility[${rowCount}][from_year]" value="${fromYear}">
                        </td>
                        <td>
                            <input type="text" class="form-control" readonly value="${toYear}">
                            <input type="hidden" name="compatibility[${rowCount}][to_year]" value="${toYear}">
                        </td>
                        <td>
                            <input type="text" class="form-control" readonly value="${motorCc}">
                            <input type="hidden" name="compatibility[${rowCount}][motor_cc]" value="${motorCc}">
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-xs edit-compatibility-row" data-index="${rowCount}" data-model-id="${modelId}" data-brand-category-id="${brandCategoryId}"><i class="fa fa-edit"></i></button>
                            <button type="button" class="btn btn-danger btn-xs remove-compatibility-row"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                `;

                $('#product_compatibility_table tbody').append(newRow);
                $('#add_compatibility_modal').modal('hide');

                if (typeof toastr !== 'undefined') {
                    toastr.success('@lang("lang_v1.compatibility_added")');
                }
            });

            // Handle edit compatibility row button
            $(document).on('click', '.edit-compatibility-row', function() {
                const rowIndex = $(this).data('index');
                const row = $(this).closest('tr');

                // Get current values - first try to get from data attributes
                const modelId = $(this).data('model-id') || row.find('input[name="compatibility[' + rowIndex + '][model_id]"]').val();
                const modelText = row.find('td:first-child input[type="text"]').val();
                const brandCategoryId = $(this).data('brand-category-id') || row.find('input[name="compatibility[' + rowIndex + '][brand_category_id]"]').val();
                const fromYear = row.find('input[name="compatibility[' + rowIndex + '][from_year]"]').val();
                const toYear = row.find('input[name="compatibility[' + rowIndex + '][to_year]"]').val();
                const motorCc = row.find('input[name="compatibility[' + rowIndex + '][motor_cc]"]').val();

                // Set values in edit modal
                $('#edit_row_index').val(rowIndex);

                // Set brand category in custom dropdown
                setCustomDropdownValue('edit-modal-brand', brandCategoryId);

                // Set year values
                $('#edit_modal_from_year').val(fromYear);
                $('#edit_modal_to_year').val(toYear);
                $('#edit_modal_motor_cc').val(motorCc);

                // Load models for the selected brand and then set the model value
                if (brandCategoryId) {
                    refreshModelDropdown('edit-modal-model', brandCategoryId, function() {
                        setCustomDropdownValue('edit-modal-model', modelId);
                    });
                }

                // Show modal
                $('#edit_compatibility_modal').modal('show');
            });

            // Handle update compatibility button
            $('#update_compatibility_btn').on('click', function() {
                const rowIndex = $('#edit_row_index').val();
                const brandCategoryId = $('#edit_modal_brand_category_id').val();
                const brandCategoryText = $('#edit-modal-brand-dropdown').attr('data-selected-text') || '';
                const modelId = $('#edit_modal_model_id').val();
                const modelText = $('#edit-modal-model-dropdown').attr('data-selected-text') || '';
                const fromYear = $('#edit_modal_from_year').val();
                const toYear = $('#edit_modal_to_year').val();
                const motorCc = $('#edit_modal_motor_cc').val();

                // Basic Validation - Brand category is now required
                if (!brandCategoryId || !modelId || !fromYear || !toYear) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('@lang("messages.fill_required_fields")');
                    }
                    return;
                }

                if (parseInt(fromYear) > parseInt(toYear)) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('@lang("lang_v1.from_year_greater_than_to_year")');
                    }
                    return;
                }

                // Find the row to update
                const row = $('#product_compatibility_table tbody tr').eq(rowIndex);

                // Update the row values
                row.find('td:first-child input[type="text"]').val(modelText);
                row.find('input[name="compatibility[' + rowIndex + '][model_id]"]').val(modelId);

                // Get brand category name
                let brandCategoryName = '';
                if (brandCategoryId) {
                    brandCategoryName = $('#edit_modal_brand_category_id option:selected').text();
                }

                // Update brand category
                row.find('td:nth-child(2) input[type="text"]').val(brandCategoryName);
                row.find('input[name="compatibility[' + rowIndex + '][brand_category_id]"]').val(brandCategoryId);

                // Update years
                row.find('td:nth-child(3) input[type="text"]').val(fromYear);
                row.find('input[name="compatibility[' + rowIndex + '][from_year]"]').val(fromYear);

                row.find('td:nth-child(4) input[type="text"]').val(toYear);
                row.find('input[name="compatibility[' + rowIndex + '][to_year]"]').val(toYear);

                // Update motor_cc
                row.find('td:nth-child(5) input[type="text"]').val(motorCc);
                row.find('input[name="compatibility[' + rowIndex + '][motor_cc]"]').val(motorCc);

                // Store the values in data attributes for the edit button
                row.find('.edit-compatibility-row')
                   .data('model-id', modelId)
                   .data('brand-category-id', brandCategoryId)
                   .attr('data-model-id', modelId)
                   .attr('data-brand-category-id', brandCategoryId);

                // Close modal
                $('#edit_compatibility_modal').modal('hide');

                if (typeof toastr !== 'undefined') {
                    toastr.success('@lang("lang_v1.compatibility_updated")');
                }
            });

            // Business Locations Table Functionality

            // Handle add location row button
            $('#add-location-row').on('click', function() {
                // Reset modal form
                $('#modal_location_id').val(null).trigger('change'); // Reset Select2
                $('#add_location_modal').modal('show');
            });

            // Remove location row
            $(document).on('click', '.remove-location-row', function() {
                $(this).closest('tr').remove();
                if (typeof toastr !== 'undefined') {
                    toastr.info('@lang("business.location_removed")');
                }
            });

            // Add location row from modal
            $('#add_location_btn').on('click', function() {
                const locationId = $('#modal_location_id').val();
                const locationText = $('#modal_location_id option:selected').text();

                // Basic Validation
                if (!locationId) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('@lang("messages.please_select_business_location")');
                    }
                    return;
                }

                // Check if location already exists in the table
                let locationExists = false;
                $('#product_locations_table tbody tr').each(function() {
                    const existingLocationId = $(this).find('input[name="product_locations[]"]').val();
                    if (existingLocationId === locationId) {
                        locationExists = true;
                        return false; // Break the loop
                    }
                });

                if (locationExists) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('@lang("business.location_already_added")');
                    }
                    return;
                }

                // Create new row
                const newRow = `
                    <tr>
                        <td>
                            ${locationText}
                            <input type="hidden" name="product_locations[]" value="${locationId}">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-xs remove-location-row"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                `;

                $('#product_locations_table tbody').append(newRow);
                $('#add_location_modal').modal('hide');

                if (typeof toastr !== 'undefined') {
                    toastr.success('@lang("business.location_added")');
                }
            });

            // Brand Category Table Functionality

            // Handle add category row button
            $('#add-category-row').on('click', function() {
                // Reset modal form
                $('#modal_category_id').val(null).trigger('change'); // Reset Select2
                $('#add_category_modal').modal('show');
            });

            // Remove category row
            $(document).on('click', '.remove-category-row', function() {
                $(this).closest('tr').remove();
                if (typeof toastr !== 'undefined') {
                    toastr.info('@lang("lang_v1.category_removed")');
                }
            });

            // Add category row from modal
            $('#add_category_btn').on('click', function() {
                const categoryId = $('#modal_category_id').val();
                const categoryText = $('#modal_category_id option:selected').text();

                // Basic Validation
                if (!categoryId) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('@lang("messages.please_select_category")');
                    }
                    return;
                }

                // Check if category already exists in the table
                let categoryExists = false;
                $('#brand_category_table tbody tr').each(function() {
                    const existingCategoryId = $(this).find('input[name="brand_category[]"]').val();
                    if (existingCategoryId === categoryId) {
                        categoryExists = true;
                        return false; // Break the loop
                    }
                });

                if (categoryExists) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('@lang("lang_v1.category_already_added")');
                    }
                    return;
                }

                // Create new row
                const newRow = `
                    <tr>
                        <td>
                            ${categoryText}
                            <input type="hidden" name="brand_category[]" value="${categoryId}">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-xs remove-category-row"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                `;

                $('#brand_category_table tbody').append(newRow);
                $('#add_category_modal').modal('hide');

                if (typeof toastr !== 'undefined') {
                    toastr.success('@lang("lang_v1.category_added")');
                }
            });

            // Custom dropdown functions for product compatibility modals
            function initCompatibilityDropdowns() {
                // Initialize brand dropdowns
                initProductDropdown('modal-brand');
                initProductDropdown('edit-modal-brand');

                // Initialize model dropdowns
                initProductDropdown('modal-model');
                initProductDropdown('edit-modal-model');

                // Search functionality for brand dropdowns
                $('#modal-brand-search').on('input', function() {
                    var searchTerm = $(this).val().toLowerCase();
                    filterProductDropdownOptions('modal-brand', searchTerm);
                });

                $('#edit-modal-brand-search').on('input', function() {
                    var searchTerm = $(this).val().toLowerCase();
                    filterProductDropdownOptions('edit-modal-brand', searchTerm);
                });

                // Search functionality for model dropdowns
                $('#modal-model-search').on('input', function() {
                    var searchTerm = $(this).val().toLowerCase();
                    filterProductDropdownOptions('modal-model', searchTerm);
                });

                $('#edit-modal-model-search').on('input', function() {
                    var searchTerm = $(this).val().toLowerCase();
                    filterProductDropdownOptions('edit-modal-model', searchTerm);
                });
            }

            // Initialize a custom dropdown
            function initProductDropdown(type) {
                var $dropdown = $('#' + type + '-dropdown');
                var $display = $('#' + type + '-display');
                var $optionsContainer = $('#' + type + '-dropdown .product-custom-dropdown-options-container');
                var $options = $('#' + type + '-options');
                var $select = getSelectElement(type);

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
                $options.on('click', '.product-custom-dropdown-option', function() {
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

                    // Close the dropdown
                    $dropdown.removeClass('open');
                    $optionsContainer.hide();

                    // Mark as selected in the custom dropdown
                    $('.product-custom-dropdown-option', $options).removeClass('selected');
                    $(this).addClass('selected');

                    // If this is a brand dropdown, fetch models
                    if (type.includes('brand')) {
                        var modelType = type.replace('brand', 'model');
                        refreshModelDropdown(modelType, value);
                    }
                });
            }

            // Get the corresponding select element for a dropdown type
            function getSelectElement(type) {
                if (type === 'modal-brand') return $('#modal_brand_category_id');
                if (type === 'edit-modal-brand') return $('#edit_modal_brand_category_id');
                if (type === 'modal-model') return $('#modal_model_id');
                if (type === 'edit-modal-model') return $('#edit_modal_model_id');
                return $();
            }

            // Filter dropdown options based on search term
            function filterProductDropdownOptions(type, searchTerm) {
                $('#' + type + '-options .product-custom-dropdown-option').each(function() {
                    var optionText = $(this).text().toLowerCase();
                    if (optionText.indexOf(searchTerm) > -1) {
                        $(this).removeClass('hidden');
                    } else {
                        $(this).addClass('hidden');
                    }
                });
            }

            // Reset custom dropdown to default state
            function resetCustomDropdown(type) {
                var $dropdown = $('#' + type + '-dropdown');
                var $display = $('#' + type + '-display');
                var $options = $('#' + type + '-options');
                var $select = getSelectElement(type);

                // Reset display text
                $display.text('@lang("messages.please_select")');

                // Clear data attributes
                $dropdown.removeAttr('data-selected-value');
                $dropdown.removeAttr('data-selected-text');

                // Reset select element
                $select.val('');

                // Clear selected state
                $('.product-custom-dropdown-option', $options).removeClass('selected');

                // Close dropdown
                $dropdown.removeClass('open');
                $('.product-custom-dropdown-options-container', $dropdown).hide();
            }

            // Set custom dropdown value
            function setCustomDropdownValue(type, value) {
                var $dropdown = $('#' + type + '-dropdown');
                var $display = $('#' + type + '-display');
                var $options = $('#' + type + '-options');
                var $select = getSelectElement(type);

                if (value) {
                    var $option = $options.find('.product-custom-dropdown-option[data-value="' + value + '"]');
                    if ($option.length) {
                        var text = $option.text();
                        $display.text(text);
                        $dropdown.attr('data-selected-value', value);
                        $dropdown.attr('data-selected-text', text);
                        $select.val(value);
                        $('.product-custom-dropdown-option', $options).removeClass('selected');
                        $option.addClass('selected');
                    }
                } else {
                    resetCustomDropdown(type);
                }
            }

            // Refresh model dropdown with new options
            function refreshModelDropdown(modelType, brandId, callback) {
                var $options = $('#' + modelType + '-options');
                var $select = getSelectElement(modelType);

                // Clear current options
                $options.empty().append('<div class="product-custom-dropdown-option" data-value="">@lang("messages.please_select")</div>');
                $select.empty().append('<option value="">@lang("messages.please_select")</option>');

                // Reset dropdown display
                resetCustomDropdown(modelType);

                if (brandId) {
                    $.ajax({
                        url: '/products/get-models-by-brand/' + brandId,
                        type: 'GET',
                        success: function(response) {
                            if (response.length > 0) {
                                $.each(response, function(index, model) {
                                    $options.append('<div class="product-custom-dropdown-option" data-value="' + model.id + '">' + model.name + '</div>');
                                    $select.append('<option value="' + model.id + '">' + model.name + '</option>');
                                });
                            } else {
                                $options.append('<div class="product-custom-dropdown-option" data-value="">@lang("lang_v1.no_models_available")</div>');
                                $select.append('<option value="">@lang("lang_v1.no_models_available")</option>');
                            }

                            if (callback && typeof callback === 'function') {
                                callback();
                            }
                        },
                        error: function() {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('@lang("lang_v1.error_fetching_models")');
                            }
                        }
                    });
                }
            }
        });
    </script>

    <!-- Add Compatibility Modal -->
    <div class="modal fade" id="add_compatibility_modal" tabindex="-1" role="dialog" aria-labelledby="addCompatibilityModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="addCompatibilityModalLabel">@lang('lang_v1.add_compatibility')</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!! Form::label('modal_brand_category_id', __('lang_v1.brand_category') . ':*') !!}
                        {!! Form::select('modal_brand_category_id', $brand_category, null, ['class' => 'form-control', 'required', 'id' => 'modal_brand_category_id', 'style' => 'display: none;']) !!}
                        <div class="product-custom-dropdown" id="modal-brand-dropdown">
                            <div class="product-custom-dropdown-display" id="modal-brand-display">@lang('messages.please_select')</div>
                            <div class="product-custom-dropdown-options-container">
                                <input type="text" class="product-custom-dropdown-search" id="modal-brand-search" placeholder="Search brands...">
                                <div class="product-custom-dropdown-options" id="modal-brand-options">
                                    <div class="product-custom-dropdown-option" data-value="">@lang('messages.please_select')</div>
                                    @foreach($brand_category as $key => $value)
                                        <div class="product-custom-dropdown-option" data-value="{{ $key }}">{{ $value }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('modal_model_id', __('lang_v1.car_model') . ':*') !!}
                        {!! Form::select('modal_model_id', [], null, ['class' => 'form-control', 'required', 'id' => 'modal_model_id', 'style' => 'display: none;']) !!}
                        <div class="product-custom-dropdown" id="modal-model-dropdown">
                            <div class="product-custom-dropdown-display" id="modal-model-display">@lang('messages.please_select')</div>
                            <div class="product-custom-dropdown-options-container">
                                <input type="text" class="product-custom-dropdown-search" id="modal-model-search" placeholder="Search models...">
                                <div class="product-custom-dropdown-options" id="modal-model-options">
                                    <div class="product-custom-dropdown-option" data-value="">@lang('messages.please_select')</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('modal_from_year', __('lang_v1.from_year') . ':*') !!}
                        {!! Form::number('modal_from_year', null, ['class' => 'form-control', 'required', 'id' => 'modal_from_year', 'min' => '1900', 'max' => date('Y')]) !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('modal_to_year', __('lang_v1.to_year') . ':*') !!}
                        {!! Form::number('modal_to_year', null, ['class' => 'form-control', 'required', 'id' => 'modal_to_year', 'min' => '1900', 'max' => date('Y')]) !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('modal_motor_cc', 'Motor CC:') !!}
                        {!! Form::text('modal_motor_cc', null, ['class' => 'form-control', 'placeholder' => 'e.g. 1600', 'id' => 'modal_motor_cc']) !!}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="button" class="btn btn-primary" id="add_compatibility_btn">@lang('messages.save')</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Compatibility Modal -->
    <div class="modal fade" id="edit_compatibility_modal" tabindex="-1" role="dialog" aria-labelledby="editCompatibilityModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="editCompatibilityModalLabel">@lang('lang_v1.edit_compatibility')</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_row_index" value="">
                    <div class="form-group">
                        {!! Form::label('edit_modal_brand_category_id', __('lang_v1.brand_category') . ':*') !!}
                        {!! Form::select('edit_modal_brand_category_id', $brand_category, null, ['class' => 'form-control', 'required', 'id' => 'edit_modal_brand_category_id', 'style' => 'display: none;']) !!}
                        <div class="product-custom-dropdown" id="edit-modal-brand-dropdown">
                            <div class="product-custom-dropdown-display" id="edit-modal-brand-display">@lang('messages.please_select')</div>
                            <div class="product-custom-dropdown-options-container">
                                <input type="text" class="product-custom-dropdown-search" id="edit-modal-brand-search" placeholder="Search brands...">
                                <div class="product-custom-dropdown-options" id="edit-modal-brand-options">
                                    <div class="product-custom-dropdown-option" data-value="">@lang('messages.please_select')</div>
                                    @foreach($brand_category as $key => $value)
                                        <div class="product-custom-dropdown-option" data-value="{{ $key }}">{{ $value }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('edit_modal_model_id', __('lang_v1.car_model') . ':*') !!}
                        {!! Form::select('edit_modal_model_id', [], null, ['class' => 'form-control', 'required', 'id' => 'edit_modal_model_id', 'style' => 'display: none;']) !!}
                        <div class="product-custom-dropdown" id="edit-modal-model-dropdown">
                            <div class="product-custom-dropdown-display" id="edit-modal-model-display">@lang('messages.please_select')</div>
                            <div class="product-custom-dropdown-options-container">
                                <input type="text" class="product-custom-dropdown-search" id="edit-modal-model-search" placeholder="Search models...">
                                <div class="product-custom-dropdown-options" id="edit-modal-model-options">
                                    <div class="product-custom-dropdown-option" data-value="">@lang('messages.please_select')</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('edit_modal_from_year', __('lang_v1.from_year') . ':*') !!}
                        {!! Form::number('edit_modal_from_year', null, ['class' => 'form-control', 'required', 'id' => 'edit_modal_from_year', 'min' => '1900', 'max' => date('Y')]) !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('edit_modal_to_year', __('lang_v1.to_year') . ':*') !!}
                        {!! Form::number('edit_modal_to_year', null, ['class' => 'form-control', 'required', 'id' => 'edit_modal_to_year', 'min' => '1900', 'max' => date('Y')]) !!}
                    </div>
                    <div class="form-group">
                        {!! Form::label('edit_modal_motor_cc', 'Motor CC:') !!}
                        {!! Form::text('edit_modal_motor_cc', null, ['class' => 'form-control', 'placeholder' => 'e.g. 1600', 'id' => 'edit_modal_motor_cc']) !!}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="button" class="btn btn-primary" id="update_compatibility_btn">@lang('messages.update')</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Locations Modal -->
    <div class="modal fade" id="add_location_modal" tabindex="-1" role="dialog" aria-labelledby="addLocationModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="addLocationModalLabel">@lang('business.business_locations')</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!! Form::label('modal_location_id', __('business.business_location') . ':*') !!}
                        {!! Form::select('modal_location_id', $business_locations, null, ['class' => 'form-control select2', 'required', 'id' => 'modal_location_id', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="button" class="btn btn-primary" id="add_location_btn">@lang('messages.save')</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Brand Category Modal -->
    <div class="modal fade" id="add_category_modal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="addCategoryModalLabel">@lang('lang_v1.brand_category')</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        {!! Form::label('modal_category_id', __('lang_v1.brand_category') . ':*') !!}
                        {!! Form::select('modal_category_id', $brand_category, null, ['class' => 'form-control select2', 'required', 'id' => 'modal_category_id', 'placeholder' => __('messages.please_select')]) !!}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="button" class="btn btn-primary" id="add_category_btn">@lang('messages.save')</button>
                </div>
            </div>
        </div>
    </div>
@endsection
