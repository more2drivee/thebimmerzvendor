<div class="modal-dialog" role="document">
    <div class="modal-content">
        <form method="post" action="{{action([\App\Http\Controllers\ServiceController::class, 'store'])}}" id="service_form">
            @csrf
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="gridSystemModalLabel">@lang('Add Labour')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="service_name">@lang('Name') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="service_name" name="name" required>
                        </div>
                    </div>
                </div>

                      <!-- Business Locations -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="product_locations">@lang('business.business_locations')</label>
                            <select class="form-control select2" id="product_locations" name="product_locations[]" multiple>
                                @if(isset($business_locations) && count($business_locations) > 0)
                                    @foreach($business_locations as $id => $name)
                                        <option value="{{$id}}">{{$name}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="help-block">@lang('lang_v1.product_location_help')</small>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="workshop_ids">@lang('Workshops')</label>
                            <select class="form-control select2" id="workshop_ids" name="workshop_ids[]" multiple data-selected-workshops="">
                            </select>
                            <small class="help-block">@lang('You can assign multiple workshops')</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="price_type">@lang('Price Type') <span class="text-danger">*</span></label>
                            <select class="form-control" id="price_type" name="price_type" required>
                                <option value="manual">@lang('Price') (Manual)</option>
                                <option value="per_hour">@lang('Per Hour')</option>
                            </select>
                        </div>
                    </div>
                </div>

          
                <div class="row" id="flat_rate_group" style="display: none;">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="flat_rate_id">@lang('Flat Rate')</label>
                            <select class="form-control select2" id="flat_rate_id" name="flat_rate_id" data-selected-flat-rate="">
                                <option value="">@lang('Please Select')</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6" id="service_hours_group" style="display: none;">
                        <div class="form-group">
                            <label for="service_hours">@lang('Labour Hours')</label>
                            <input type="number" class="form-control" id="service_hours" name="service_hours" 
                                   step="0.1" min="0" value="1">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="service_price">@lang('Labour Price') <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="service_price" name="price" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" id="is_external" name="is_external" value="1">
                                    @lang('External Labor')
                                </label>
                            </div>
                            <small class="help-block">@lang('If checked, this service will create an unpaid expense when added to job orders')</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <hr>
                        <h4><i class="fas fa-chart-line"></i> @lang('Prediction Rules')</h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="prediction_km_interval">@lang('KM Interval')</label>
                            <input type="number" class="form-control" id="prediction_km_interval" name="prediction_km_interval" 
                                   step="1" min="0" placeholder="e.g. 5000">
                            <small class="help-block">@lang('Recommended KM between services (e.g. 5000, 10000)')</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="prediction_time_interval">@lang('Time Interval (Months)')</label>
                            <input type="number" class="form-control" id="prediction_time_interval" name="prediction_time_interval" 
                                   step="1" min="0" placeholder="e.g. 6">
                            <small class="help-block">@lang('Recommended months between services (e.g. 3, 6, 12)')</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <small class="text-muted">
                            <i class="fa fa-info-circle"></i> 
                            @lang('Note: Labour items are products with stock management disabled. They will be created with default system settings for non-editable fields.')
                        </small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('Close')</button>
                <button type="submit" class="btn btn-primary">@lang('Save')</button>
            </div>
        </form>
    </div>
</div>