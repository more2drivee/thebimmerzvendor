 <a class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm pull-right" data-href="{{action([\Modules\Repair\Http\Controllers\DeviceModelController::class, 'create'])}}" id="add_device_model">
	<i class="fa fa-plus"></i>
	@lang('messages.add')
</a>
 <!-- Import Button -->
 <button type="button" class="mx-3 tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm pull-right"
 data-toggle="modal" data-target="#importDeviceModelModal">
 <i class="fa fa-upload"></i> @lang('messages.import')
</button>
<br><br>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="model_table" style="width: 100%">
        <thead>
            <tr>
                <th>@lang('messages.action')</th>
                <th>@lang('repair::lang.model_name')</th>
                <th>@lang('messages.vin_model_code')</th>
                <th>@lang('repair::lang.device')</th>
                <th>@lang('product.brand')</th>
            </tr>
        </thead>
    </table>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importDeviceModelModal" tabindex="-1" role="dialog" aria-labelledby="importDeviceModelModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importDeviceModelModalLabel">@lang('messages.import_device_models')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('device_models.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="deviceModelFile">@lang('messages.select_file')</label>
                        <input type="file" name="file" id="deviceModelFile" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">@lang('messages.import')</button>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="device_model_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>