<div class="modal fade" id="location_modal" tabindex="-1" role="dialog" aria-labelledby="locationModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="locationModalLabel">@lang('business.business_locations')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="location_table">
                                <thead>
                                    <tr>
                                        <th>@lang('messages.action')</th>
                                        <th>@lang('business.location_name')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($business_locations as $id => $location)
                                        <tr>
                                            <td>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" class="location-checkbox" value="{{ $id }}" 
                                                        @if(isset($default_location) && $default_location == $id) checked @endif>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>{{ $location }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="btn btn-primary" id="add_selected_locations">@lang('messages.save')</button>
            </div>
        </div>
    </div>
</div>
