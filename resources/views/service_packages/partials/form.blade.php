<form id="sp_form" method="POST" action="{{ $servicePackage ? route('service-packages.update', $servicePackage->id) : route('service-packages.store') }}">
    @csrf
    @if($servicePackage)
        @method('PUT')
        <input type="hidden" name="id" value="{{ $servicePackage->id }}">
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" class="form-control" id="name" name="name" required
                       placeholder="Enter service package name" value="{{ $servicePackage ? $servicePackage->name : '' }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="km">KM</label>
                <input type="number" class="form-control" id="km" name="km" min="0"
                       placeholder="Enter KM value" value="{{ $servicePackage ? $servicePackage->km : '' }}">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="device_id">Device *</label>
                <select class="form-control select2" id="device_id" name="device_id" style="width:100%" required>
                    <option value="">Select Device</option>
                    @if(isset($devices) && $devices->count() > 0)
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}" {{ $servicePackage && $servicePackage->device_id == $device->id ? 'selected' : '' }}>
                                {{ $device->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="repair_device_model_id">Repair Device Model *</label>
                <select class="form-control select2" id="repair_device_model_id" name="repair_device_model_id" style="width:100%" required>
                    <option value="">Select Repair Device Model</option>
                    @if(isset($repairDeviceModels) && $repairDeviceModels->count() > 0)
                        @foreach($repairDeviceModels as $model)
                            <option value="{{ $model->id }}" {{ $servicePackage && $servicePackage->repair_device_model_id == $model->id ? 'selected' : '' }}>
                                {{ $model->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="from">From Year</label>
                <input type="number" class="form-control" id="from" name="from" min="1900" max="2100"
                       placeholder="Enter starting year" value="{{ $servicePackage ? $servicePackage->from : '' }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="to">To Year</label>
                <input type="number" class="form-control" id="to" name="to" min="1900" max="2100"
                       placeholder="Enter ending year" value="{{ $servicePackage ? $servicePackage->to : '' }}">
            </div>
        </div>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">
            {{ $servicePackage ? 'Update' : 'Create' }} Service Package
        </button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
    </div>
</form>
