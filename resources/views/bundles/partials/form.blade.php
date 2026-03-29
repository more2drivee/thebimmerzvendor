<form id="bundle_form" method="POST" action="{{ $bundle ? route('bundles.update', $bundle->id) : route('bundles.store') }}">
    @csrf
    @if($bundle)
        @method('PUT')
        <input type="hidden" name="id" value="{{ $bundle->id }}">
    @endif

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="device_id">@lang('repair::lang.brand') *</label>
                <select class="form-control select2-brand" id="device_id" name="device_id" style="width:100%" required>
                    <option value="">@lang('messages.please_select')</option>
                    @if($bundle && $bundle->device_id)
                        <option value="{{ $bundle->device_id }}" selected>
                            {{ $bundle->device ? $bundle->device->name : '' }}
                        </option>
                    @endif
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="repair_device_model_id">@lang('repair::lang.model')</label>
                <select class="form-control select2" id="repair_device_model_id" name="repair_device_model_id" style="width:100%">
                    <option value="">@lang('messages.please_select')</option>
                    @foreach($repairDeviceModels as $model)
                        <option value="{{ $model->id }}" {{ $bundle && $bundle->repair_device_model_id == $model->id ? 'selected' : '' }}>
                            {{ $model->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="manufacturing_year">@lang('bundles.fields.manufacturing_year')</label>
                <input type="number" class="form-control" id="manufacturing_year" name="manufacturing_year" min="1900" max="2100"
                       value="{{ $bundle ? $bundle->manufacturing_year : '' }}" placeholder="@lang('bundles.fields.manufacturing_year')">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="side_type">@lang('bundles.fields.side_type') *</label>
                <select class="form-control" id="side_type" name="side_type" required>
                    <option value="front_half" {{ $bundle && $bundle->side_type == 'front_half' ? 'selected' : '' }}>@lang('bundles.side_type.front_half')</option>
                    <option value="rear_half" {{ $bundle && $bundle->side_type == 'rear_half' ? 'selected' : '' }}>@lang('bundles.side_type.rear_half')</option>
                    <option value="left_quarter" {{ $bundle && $bundle->side_type == 'left_quarter' ? 'selected' : '' }}>@lang('bundles.side_type.left_quarter')</option>
                    <option value="right_quarter" {{ $bundle && $bundle->side_type == 'right_quarter' ? 'selected' : '' }}>@lang('bundles.side_type.right_quarter')</option>
                    <option value="full_body" {{ $bundle && $bundle->side_type == 'full_body' ? 'selected' : '' }}>@lang('bundles.side_type.full_body')</option>
                    <option value="other" {{ $bundle && $bundle->side_type == 'other' ? 'selected' : '' }}>@lang('bundles.side_type.other')</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="location_id">@lang('business.business_location') *</label>
                <select class="form-control select2" id="location_id" name="location_id" style="width:100%" required>
                    <option value="">@lang('messages.please_select')</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ $bundle && $bundle->location_id == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="price">@lang('bundles.fields.price')</label>
                <input type="text" class="form-control input_number" id="price" name="price"
                       value="{{ $bundle ? $bundle->price : '' }}" placeholder="@lang('bundles.fields.price')">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="has_parts_left" value="1" {{ !$bundle || $bundle->has_parts_left ? 'checked' : '' }}>
                    @lang('bundles.fields.has_parts_left')
                </label>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="description">@lang('bundles.fields.description')</label>
                <textarea class="form-control" id="description" name="description" rows="3" placeholder="@lang('bundles.fields.description')">{{ $bundle ? $bundle->description : '' }}</textarea>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="notes">@lang('bundles.fields.notes')</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="@lang('bundles.fields.notes')">{{ $bundle ? $bundle->notes : '' }}</textarea>
            </div>
        </div>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">
            {{ $bundle ? __('messages.update') : __('messages.save') }}
        </button>
        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>
</form>
