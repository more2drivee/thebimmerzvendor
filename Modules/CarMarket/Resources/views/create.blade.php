@extends('layouts.app')

@section('title', __('carmarket::lang.create_vehicle'))

@section('css')
<style>
.form-section {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 20px;
    margin-bottom: 20px;
}
.form-section h4 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e7eb;
}
.image-preview-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}
.image-preview-item {
    position: relative;
    width: 100px;
    height: 75px;
}
.image-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 6px;
}
.image-preview-item .remove-image {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
}
</style>
@endsection

@section('content')
@include('carmarket::layouts.nav')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('carmarket::lang.create_vehicle')
    </h1>
</section>

<section class="content no-print">
    {{-- Error Display Section --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-ban"></i> {{ __('messages.error') }}</h4>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-ban"></i> {{ __('messages.error') }}</h4>
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('carmarket.vehicles.store') }}" method="POST" enctype="multipart/form-data" id="vehicle-form">
        @csrf

        {{-- Basic Info --}}
        <div class="form-section">
            <h4><i class="fa fa-car"></i> @lang('carmarket::lang.vehicle_details')</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group @error('brand_category_id') has-error @enderror">
                        <label>@lang('carmarket::lang.brand') *</label>
                        <select name="brand_category_id" id="brand_category_id" class="form-control select2" required>
                            <option value="">@lang('carmarket::lang.select_brand')</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_category_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_category_id') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('repair_device_model_id') has-error @enderror">
                        <label>@lang('carmarket::lang.model_name') *</label>
                        <select name="repair_device_model_id" id="repair_device_model_id" class="form-control select2" required>
                            <option value="">@lang('carmarket::lang.select_model')</option>
                        </select>
                        @error('repair_device_model_id') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('year') has-error @enderror">
                        <label>@lang('carmarket::lang.year') *</label>
                        <input type="number" name="year" class="form-control" required min="1990" max="{{ date('Y') + 1 }}" value="{{ old('year') }}">
                        @error('year') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('trim_level') has-error @enderror">
                        <label>@lang('carmarket::lang.trim_level')</label>
                        <input type="text" name="trim_level" class="form-control" value="{{ old('trim_level') }}">
                        @error('trim_level') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('color') has-error @enderror">
                        <label>@lang('carmarket::lang.color')</label>
                        <input type="text" name="color" class="form-control" value="{{ old('color') }}">
                        @error('color') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group @error('body_type') has-error @enderror">
                        <label>@lang('carmarket::lang.body_type')</label>
                        <select name="body_type" class="form-control">
                            <option value="">@lang('carmarket::lang.select_body_type')</option>
                            @foreach(['sedan','suv','coupe','hatchback','truck','van','convertible','wagon','pickup'] as $bt)
                                <option value="{{ $bt }}" {{ old('body_type') == $bt ? 'selected' : '' }}>@lang('carmarket::lang.' . $bt)</option>
                            @endforeach
                        </select>
                        @error('body_type') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('condition') has-error @enderror">
                        <label>@lang('carmarket::lang.condition') *</label>
                        <select name="condition" class="form-control" required>
                            <option value="">@lang('carmarket::lang.select_condition')</option>
                            <option value="new" {{ old('condition') == 'new' ? 'selected' : '' }}>@lang('carmarket::lang.new_car')</option>
                            <option value="used" {{ old('condition') == 'used' ? 'selected' : '' }}>@lang('carmarket::lang.used_car')</option>
                        </select>
                        @error('condition') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('mileage_km') has-error @enderror">
                        <label>@lang('carmarket::lang.mileage_km')</label>
                        <input type="number" name="mileage_km" class="form-control" min="0" value="{{ old('mileage_km') }}">
                        @error('mileage_km') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('engine_capacity_cc') has-error @enderror">
                        <label>@lang('carmarket::lang.engine_capacity_cc')</label>
                        <input type="number" name="engine_capacity_cc" class="form-control" min="0" value="{{ old('engine_capacity_cc') }}">
                        @error('engine_capacity_cc') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('carmarket::lang.cylinder_count')</label>
                        <input type="number" name="cylinder_count" class="form-control" min="2" max="16" value="{{ old('cylinder_count') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('carmarket::lang.fuel_type')</label>
                        <select name="fuel_type" class="form-control">
                            <option value="">@lang('carmarket::lang.select_fuel_type')</option>
                            @foreach(['gas','diesel','electric','hybrid','natural_gas'] as $ft)
                                <option value="{{ $ft }}" {{ old('fuel_type') == $ft ? 'selected' : '' }}>@lang('carmarket::lang.' . $ft)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('carmarket::lang.transmission')</label>
                        <select name="transmission" class="form-control">
                            <option value="">@lang('carmarket::lang.select_transmission')</option>
                            <option value="automatic" {{ old('transmission') == 'automatic' ? 'selected' : '' }}>@lang('carmarket::lang.automatic')</option>
                            <option value="manual" {{ old('transmission') == 'manual' ? 'selected' : '' }}>@lang('carmarket::lang.manual')</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('carmarket::lang.license_type')</label>
                        <select name="license_type" class="form-control">
                            <option value="">@lang('carmarket::lang.select_license_type')</option>
                            @foreach(['private','commercial','diplomatic','temporary'] as $lt)
                                <option value="{{ $lt }}" {{ old('license_type') == $lt ? 'selected' : '' }}>{{ ucfirst($lt) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('carmarket::lang.factory_paint')</label>
                        <select name="factory_paint" class="form-control">
                            <option value="0">@lang('carmarket::lang.no')</option>
                            <option value="1" {{ old('factory_paint') == '1' ? 'selected' : '' }}>@lang('carmarket::lang.yes')</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('carmarket::lang.imported_specs')</label>
                        <select name="imported_specs" class="form-control">
                            <option value="0">@lang('carmarket::lang.no')</option>
                            <option value="1" {{ old('imported_specs') == '1' ? 'selected' : '' }}>@lang('carmarket::lang.yes')</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('carmarket::lang.vin_number')</label>
                        <input type="text" name="vin_number" class="form-control" value="{{ old('vin_number') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('carmarket::lang.plate_number')</label>
                        <input type="text" name="plate_number" class="form-control" value="{{ old('plate_number') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Price & Location --}}
        <div class="form-section">
            <h4><i class="fa fa-money"></i> @lang('carmarket::lang.listing_price') & @lang('carmarket::lang.location_city')</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group @error('listing_price') has-error @enderror">
                        <label>@lang('carmarket::lang.listing_price') *</label>
                        <input type="number" name="listing_price" class="form-control" required min="0" step="0.01" value="{{ old('listing_price') }}">
                        @error('listing_price') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('currency') has-error @enderror">
                        <label>Currency</label>
                        <select name="currency" class="form-control">
                            <option value="EGP" {{ old('currency') == 'EGP' ? 'selected' : '' }}>EGP</option>
                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="SAR" {{ old('currency') == 'SAR' ? 'selected' : '' }}>SAR</option>
                        </select>
                        @error('currency') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('min_price') has-error @enderror">
                        <label>@lang('carmarket::lang.min_price')</label>
                        <input type="number" name="min_price" class="form-control" min="0" step="0.01" value="{{ old('min_price') }}">
                        @error('min_price') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('location_city') has-error @enderror">
                        <label>@lang('carmarket::lang.location_city')</label>
                        <input type="text" name="location_city" class="form-control" value="{{ old('location_city') }}">
                        @error('location_city') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('location_area') has-error @enderror">
                        <label>@lang('carmarket::lang.location_area')</label>
                        <input type="text" name="location_area" class="form-control" value="{{ old('location_area') }}">
                        @error('location_area') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Seller --}}
        <div class="form-section">
            <h4><i class="fa fa-user"></i> @lang('carmarket::lang.seller')</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group @error('seller_contact_id') has-error @enderror">
                        <label>@lang('carmarket::lang.seller') *</label>
                        <select name="seller_contact_id" class="form-control select2" required>
                            <option value="">@lang('carmarket::lang.select_seller')</option>
                            @foreach($sellers as $c)
                                <option value="{{ $c->id }}" {{ old('seller_contact_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} {{ $c->mobile ? '(' . $c->mobile . ')' : '' }}</option>
                            @endforeach
                        </select>
                        @error('seller_contact_id') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Description --}}
        <div class="form-section">
            <h4><i class="fa fa-file-text"></i> @lang('carmarket::lang.description')</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group @error('description') has-error @enderror">
                        <label>@lang('carmarket::lang.description')</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                        @error('description') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group @error('condition_notes') has-error @enderror">
                        <label>@lang('carmarket::lang.condition_notes')</label>
                        <textarea name="condition_notes" class="form-control" rows="4">{{ old('condition_notes') }}</textarea>
                        @error('condition_notes') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Images --}}
        <div class="form-section">
            <h4><i class="fa fa-image"></i> @lang('carmarket::lang.upload_images')</h4>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group @error('images.*') has-error @enderror">
                        <label>@lang('carmarket::lang.photo_gallery')</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*" id="images-input">
                        @error('images.*') <span class="help-block">{{ $message }}</span> @enderror
                        <div class="image-preview-container" id="image-preview"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Options --}}
        <div class="form-section">
            <h4><i class="fa fa-cog"></i> {{ __('messages.options') }}</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group @error('is_featured') has-error @enderror">
                        <label>@lang('carmarket::lang.is_featured')</label>
                        <select name="is_featured" class="form-control">
                            <option value="0">@lang('carmarket::lang.no')</option>
                            <option value="1" {{ old('is_featured') == '1' ? 'selected' : '' }}>@lang('carmarket::lang.yes')</option>
                        </select>
                        @error('is_featured') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('is_premium') has-error @enderror">
                        <label>@lang('carmarket::lang.is_premium')</label>
                        <select name="is_premium" class="form-control">
                            <option value="0">@lang('carmarket::lang.no')</option>
                            <option value="1" {{ old('is_premium') == '1' ? 'selected' : '' }}>@lang('carmarket::lang.yes')</option>
                        </select>
                        @error('is_premium') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('listing_status') has-error @enderror">
                        <label>@lang('carmarket::lang.listing_status')</label>
                        <select name="listing_status" class="form-control">
                            <option value="draft">@lang('carmarket::lang.draft')</option>
                            <option value="pending" selected>@lang('carmarket::lang.pending')</option>
                        </select>
                        @error('listing_status') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="form-section">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa fa-save"></i> {{ __('messages.save') }}
            </button>
            <a href="{{ route('carmarket.index') }}" class="btn btn-default btn-lg">
                {{ __('messages.cancel') }}
            </a>
        </div>
    </form>
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Initialize select2
    $('.select2').select2();

    // Cascading dropdown: Brand -> Model
    $('#brand_category_id').on('change', function() {
        var brandId = $(this).val();
        var modelSelect = $('#repair_device_model_id');
        
        modelSelect.empty().append('<option value="">@lang('carmarket::lang.select_model')</option>');
        
        if (brandId) {
            $.ajax({
                url: '{{ route('carmarket.brands.models', ['brandId' => '__BRAND_ID__']) }}'.replace('__BRAND_ID__', brandId),
                type: 'GET',
                success: function(models) {
                    $.each(models, function(i, model) {
                        modelSelect.append('<option value="' + model.id + '">' + model.name + '</option>');
                    });
                    modelSelect.trigger('change');
                }
            });
        }
    });

    // Trigger change on page load if brand is selected (for validation errors)
    @if(old('brand_category_id'))
        $('#brand_category_id').trigger('change');
    @endif

    // Image preview
    $('#images-input').on('change', function(e) {
        var files = e.target.files;
        var preview = $('#image-preview');
        preview.empty();

        for (var i = 0; i < files.length; i++) {
            (function(file, index) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var html = '<div class="image-preview-item">' +
                        '<img src="' + e.target.result + '">' +
                        '<span class="remove-image" data-index="' + index + '">&times;</span>' +
                        '</div>';
                    preview.append(html);
                };
                reader.readAsDataURL(file);
            })(files[i], i);
        }
    });

    // Remove image preview
    $(document).on('click', '.remove-image', function() {
        var index = $(this).data('index');
        var input = $('#images-input')[0];
        var dt = new DataTransfer();

        for (var i = 0; i < input.files.length; i++) {
            if (i !== index) {
                dt.items.add(input.files[i]);
            }
        }

        input.files = dt.files;
        $(this).parent().remove();
    });
});
</script>
@endsection
