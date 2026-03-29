<div class="modal-dialog" role="document">
    <div class="modal-content">
        <form id="labour_vehicle_form" action="{{ action([\App\Http\Controllers\LabourByVehicleController::class, 'update'], $labour_by_vehicle->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">@lang('Edit Labour by Vehicle')</h4>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="device_id">@lang('Brand') <span class="text-danger">*</span></label>
                            <select name="device_id" id="device_id" class="form-control select2" required>
                                <option value="">{{ __('Please Select') }}</option>
                                @foreach($device_categories as $category)
                                    <option value="{{ $category->id }}" {{ $labour_by_vehicle->device_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="from">@lang('From')</label>
                            <input type="number" name="from" id="from" class="form-control" placeholder="{{ __('From') }}" value="{{ $labour_by_vehicle->from ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="to">@lang('To')</label>
                            <input type="number" name="to" id="to" class="form-control" placeholder="{{ __('To') }}" value="{{ $labour_by_vehicle->to ?? '' }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="repair_device_model_id">@lang('Model')</label>
                            <select name="repair_device_model_id" id="repair_device_model_id" class="form-control select2" data-selected-model="{{ $labour_by_vehicle->repair_device_model_id }}">
                                <option value="">{{ __('Please Select') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            </div>
        </form>
    </div>
</div>
