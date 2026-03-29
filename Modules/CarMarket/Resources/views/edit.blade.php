@extends('layouts.app')

@section('title', __('carmarket::lang.edit_vehicle'))

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
.image-preview-item .set-primary {
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    background: #28a745;
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 3px;
    cursor: pointer;
}
.image-preview-item.is-primary .set-primary {
    background: #007bff;
}
</style>
@endsection

@section('content')
@include('carmarket::layouts.nav')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('carmarket::lang.edit_vehicle')
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

    <form action="{{ route('carmarket.vehicles.update', $vehicle->id) }}" method="POST" enctype="multipart/form-data" id="vehicle-form">
        @csrf
        @method('PUT')

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
                                <option value="{{ $brand->id }}" {{ $vehicle->brand_category_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
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
                            @foreach($models as $model)
                                <option value="{{ $model->id }}" {{ $vehicle->repair_device_model_id == $model->id ? 'selected' : '' }}>{{ $model->name }}</option>
                            @endforeach
                        </select>
                        @error('repair_device_model_id') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('year') has-error @enderror">
                        <label>@lang('carmarket::lang.year') *</label>
                        <input type="number" name="year" class="form-control" required min="1990" max="{{ date('Y') + 1 }}" value="{{ $vehicle->year }}">
                        @error('year') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('trim_level') has-error @enderror">
                        <label>@lang('carmarket::lang.trim_level')</label>
                        <input type="text" name="trim_level" class="form-control" value="{{ $vehicle->trim_level }}">
                        @error('trim_level') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('color') has-error @enderror">
                        <label>@lang('carmarket::lang.color')</label>
                        <input type="text" name="color" class="form-control" value="{{ $vehicle->color }}">
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
                                <option value="{{ $bt }}" {{ $vehicle->body_type == $bt ? 'selected' : '' }}>@lang('carmarket::lang.' . $bt)</option>
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
                            <option value="new" {{ $vehicle->condition == 'new' ? 'selected' : '' }}>@lang('carmarket::lang.new_car')</option>
                            <option value="used" {{ $vehicle->condition == 'used' ? 'selected' : '' }}>@lang('carmarket::lang.used_car')</option>
                        </select>
                        @error('condition') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('mileage_km') has-error @enderror">
                        <label>@lang('carmarket::lang.mileage_km')</label>
                        <input type="number" name="mileage_km" class="form-control" min="0" value="{{ $vehicle->mileage_km }}">
                        @error('mileage_km') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('engine_capacity_cc') has-error @enderror">
                        <label>@lang('carmarket::lang.engine_capacity_cc')</label>
                        <input type="number" name="engine_capacity_cc" class="form-control" min="0" value="{{ $vehicle->engine_capacity_cc }}">
                        @error('engine_capacity_cc') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group @error('cylinder_count') has-error @enderror">
                        <label>@lang('carmarket::lang.cylinder_count')</label>
                        <input type="number" name="cylinder_count" class="form-control" min="2" max="16" value="{{ $vehicle->cylinder_count }}">
                        @error('cylinder_count') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('fuel_type') has-error @enderror">
                        <label>@lang('carmarket::lang.fuel_type')</label>
                        <select name="fuel_type" class="form-control">
                            <option value="">@lang('carmarket::lang.select_fuel_type')</option>
                            @foreach(['gas','diesel','electric','hybrid','natural_gas'] as $ft)
                                <option value="{{ $ft }}" {{ $vehicle->fuel_type == $ft ? 'selected' : '' }}>@lang('carmarket::lang.' . $ft)</option>
                            @endforeach
                        </select>
                        @error('fuel_type') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('transmission') has-error @enderror">
                        <label>@lang('carmarket::lang.transmission')</label>
                        <select name="transmission" class="form-control">
                            <option value="">@lang('carmarket::lang.select_transmission')</option>
                            <option value="automatic" {{ $vehicle->transmission == 'automatic' ? 'selected' : '' }}>@lang('carmarket::lang.automatic')</option>
                            <option value="manual" {{ $vehicle->transmission == 'manual' ? 'selected' : '' }}>@lang('carmarket::lang.manual')</option>
                        </select>
                        @error('transmission') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('license_type') has-error @enderror">
                        <label>@lang('carmarket::lang.license_type')</label>
                        <select name="license_type" class="form-control">
                            <option value="">@lang('carmarket::lang.select_license_type')</option>
                            @foreach(['private','commercial','diplomatic','temporary'] as $lt)
                                <option value="{{ $lt }}" {{ $vehicle->license_type == $lt ? 'selected' : '' }}>{{ ucfirst($lt) }}</option>
                            @endforeach
                        </select>
                        @error('license_type') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group @error('factory_paint') has-error @enderror">
                        <label>@lang('carmarket::lang.factory_paint')</label>
                        <select name="factory_paint" class="form-control">
                            <option value="0" {{ !$vehicle->factory_paint ? 'selected' : '' }}>@lang('carmarket::lang.no')</option>
                            <option value="1" {{ $vehicle->factory_paint ? 'selected' : '' }}>@lang('carmarket::lang.yes')</option>
                        </select>
                        @error('factory_paint') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('imported_specs') has-error @enderror">
                        <label>@lang('carmarket::lang.imported_specs')</label>
                        <select name="imported_specs" class="form-control">
                            <option value="0" {{ !$vehicle->imported_specs ? 'selected' : '' }}>@lang('carmarket::lang.no')</option>
                            <option value="1" {{ $vehicle->imported_specs ? 'selected' : '' }}>@lang('carmarket::lang.yes')</option>
                        </select>
                        @error('imported_specs') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('vin_number') has-error @enderror">
                        <label>@lang('carmarket::lang.vin_number')</label>
                        <input type="text" name="vin_number" class="form-control" value="{{ $vehicle->vin_number }}">
                        @error('vin_number') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('plate_number') has-error @enderror">
                        <label>@lang('carmarket::lang.plate_number')</label>
                        <input type="text" name="plate_number" class="form-control" value="{{ $vehicle->plate_number }}">
                        @error('plate_number') <span class="help-block">{{ $message }}</span> @enderror
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
                        <input type="number" name="listing_price" class="form-control" required min="0" step="0.01" value="{{ $vehicle->listing_price }}">
                        @error('listing_price') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('currency') has-error @enderror">
                        <label>Currency</label>
                        <select name="currency" class="form-control">
                            <option value="EGP" {{ $vehicle->currency == 'EGP' ? 'selected' : '' }}>EGP</option>
                            <option value="USD" {{ $vehicle->currency == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="SAR" {{ $vehicle->currency == 'SAR' ? 'selected' : '' }}>SAR</option>
                        </select>
                        @error('currency') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('min_price') has-error @enderror">
                        <label>@lang('carmarket::lang.min_price')</label>
                        <input type="number" name="min_price" class="form-control" min="0" step="0.01" value="{{ $vehicle->min_price }}">
                        @error('min_price') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group @error('location_city') has-error @enderror">
                        <label>@lang('carmarket::lang.location_city')</label>
                        <input type="text" name="location_city" class="form-control" value="{{ $vehicle->location_city }}">
                        @error('location_city') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('location_area') has-error @enderror">
                        <label>@lang('carmarket::lang.location_area')</label>
                        <input type="text" name="location_area" class="form-control" value="{{ $vehicle->location_area }}">
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
                                <option value="{{ $c->id }}" {{ $vehicle->seller_contact_id == $c->id ? 'selected' : '' }}>{{ $c->name }} {{ $c->mobile ? '(' . $c->mobile . ')' : '' }}</option>
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
                        <textarea name="description" class="form-control" rows="4">{{ $vehicle->description }}</textarea>
                        @error('description') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group @error('condition_notes') has-error @enderror">
                        <label>@lang('carmarket::lang.condition_notes')</label>
                        <textarea name="condition_notes" class="form-control" rows="4">{{ $vehicle->condition_notes }}</textarea>
                        @error('condition_notes') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Existing Images --}}
        <div class="form-section">
            <h4><i class="fa fa-image"></i> @lang('carmarket::lang.photo_gallery')</h4>
            <div class="row">
                <div class="col-md-12">
                    <div class="image-preview-container" id="existing-images">
                        @foreach($vehicle->media as $media)
                            <div class="image-preview-item {{ $media->is_primary ? 'is-primary' : '' }}" data-id="{{ $media->id }}">
                                <img src="{{ asset('storage/' . $media->file_path) }}">
                                <span class="remove-image delete-media" data-id="{{ $media->id }}">&times;</span>
                                <span class="set-primary">{{ $media->is_primary ? 'Primary' : 'Set Primary' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-md-12">
                    <div class="form-group @error('images.*') has-error @enderror">
                        <label>@lang('carmarket::lang.upload_images')</label>
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
                            <option value="0" {{ !$vehicle->is_featured ? 'selected' : '' }}>@lang('carmarket::lang.no')</option>
                            <option value="1" {{ $vehicle->is_featured ? 'selected' : '' }}>@lang('carmarket::lang.yes')</option>
                        </select>
                        @error('is_featured') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('is_premium') has-error @enderror">
                        <label>@lang('carmarket::lang.is_premium')</label>
                        <select name="is_premium" class="form-control">
                            <option value="0" {{ !$vehicle->is_premium ? 'selected' : '' }}>@lang('carmarket::lang.no')</option>
                            <option value="1" {{ $vehicle->is_premium ? 'selected' : '' }}>@lang('carmarket::lang.yes')</option>
                        </select>
                        @error('is_premium') <span class="help-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group @error('listing_status') has-error @enderror">
                        <label>@lang('carmarket::lang.listing_status')</label>
                        <select name="listing_status" class="form-control">
                            <option value="draft" {{ $vehicle->listing_status == 'draft' ? 'selected' : '' }}>@lang('carmarket::lang.draft')</option>
                            <option value="pending" {{ $vehicle->listing_status == 'pending' ? 'selected' : '' }}>@lang('carmarket::lang.pending')</option>
                            <option value="active" {{ $vehicle->listing_status == 'active' ? 'selected' : '' }}>@lang('carmarket::lang.active')</option>
                            <option value="sold" {{ $vehicle->listing_status == 'sold' ? 'selected' : '' }}>@lang('carmarket::lang.sold')</option>
                            <option value="reserved" {{ $vehicle->listing_status == 'reserved' ? 'selected' : '' }}>@lang('carmarket::lang.reserved')</option>
                            <option value="rejected" {{ $vehicle->listing_status == 'rejected' ? 'selected' : '' }}>@lang('carmarket::lang.rejected')</option>
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
        var currentModel = '{{ $vehicle->repair_device_model_id }}';
        
        modelSelect.empty().append('<option value="">@lang('carmarket::lang.select_model')</option>');
        
        if (brandId) {
            $.ajax({
                url: '{{ route('carmarket.brands.models', ['brandId' => '__BRAND_ID__']) }}'.replace('__BRAND_ID__', brandId),
                type: 'GET',
                success: function(models) {
                    $.each(models, function(i, model) {
                        var selected = model.id == currentModel ? 'selected' : '';
                        modelSelect.append('<option value="' + model.id + '" ' + selected + '>' + model.name + '</option>');
                    });
                    modelSelect.trigger('change');
                }
            });
        }
    });

    // Delete media
    $(document).on('click', '.delete-media', function() {
        var mediaId = $(this).data('id');
        var item = $(this).closest('.image-preview-item');
        
        if (!confirm('Delete this image?')) return;
        
        $.ajax({
            url: '{{ route('carmarket.vehicles.media.delete', ['vehicleId' => $vehicle->id, 'mediaId' => '__MEDIA_ID__']) }}'.replace('__MEDIA_ID__', mediaId),
            type: 'POST',
            data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
            success: function() {
                item.remove();
                toastr.success('Image deleted');
            }
        });
    });

    // Set primary image
    $(document).on('click', '.set-primary', function() {
        var mediaId = $(this).closest('.image-preview-item').data('id');
        var item = $(this).closest('.image-preview-item');
        
        $.ajax({
            url: '{{ route('carmarket.vehicles.media.set-primary', ['vehicleId' => $vehicle->id, 'mediaId' => '__MEDIA_ID__']) }}'.replace('__MEDIA_ID__', mediaId),
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
                $('.image-preview-item').removeClass('is-primary');
                $('.image-preview-item .set-primary').text('Set Primary');
                item.addClass('is-primary');
                item.find('.set-primary').text('Primary');
                toastr.success('Primary image set');
            }
        });
    });

    // Image preview for new uploads
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
    $(document).on('click', '#image-preview .remove-image', function() {
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
