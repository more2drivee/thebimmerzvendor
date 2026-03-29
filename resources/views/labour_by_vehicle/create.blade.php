<div class="modal-dialog" role="document">
    <div class="modal-content">
        <form id="labour_vehicle_form" action="{{ action([\App\Http\Controllers\LabourByVehicleController::class, 'store']) }}" method="POST">
            @csrf
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">@lang('Add Labour by Vehicle')</h4>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="device_id">@lang('Brand') <span class="text-danger">*</span></label>
                            <select name="device_id" id="device_id" class="form-control select2" required>
                                <option value="">{{ __('Please Select') }}</option>
                                @foreach($device_categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="from">@lang('From')</label>
                            <input type="number" name="from" id="from" class="form-control" placeholder="{{ __('From') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="to">@lang('To')</label>
                            <input type="number" name="to" id="to" class="form-control" placeholder="{{ __('To') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="repair_device_model_id">@lang('Model')</label>
                            <select name="repair_device_model_id" id="repair_device_model_id" class="form-control select2" data-selected-model="">
                                <option value="">{{ __('Please Select') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            </div>
        </form>
    </div>
</div>
