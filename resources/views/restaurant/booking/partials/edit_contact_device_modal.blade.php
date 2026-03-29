<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['route' => ['bookings.contact_device.update', $device->id], 'method' => 'PUT']) !!}
        @csrf
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" style="width:100%; text-align: center;">Edit Contact Device</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('device_id', 'Brand') !!}
                        <select name="device_id" id="edit_brand_id" class="form-control" required>
                            <option value="">Select Brand</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" @if($b->id == $device->device_id) selected @endif>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('models_id', 'Model') !!}
                        <select name="models_id" id="edit_model_id" class="form-control" required>
                            <option value="{{ $device->models_id }}">{{ $device->model_name }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('plate_number', 'Plate') !!}
                        {!! Form::text('plate_number', $device->plate_number, ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('color', 'Color') !!}
                        {!! Form::text('color', $device->color, ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('manufacturing_year', 'Year') !!}
                        {!! Form::text('manufacturing_year', $device->manufacturing_year, ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('car_type', 'Type') !!}
                        {!! Form::text('car_type', $device->car_type, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('chassis_number', 'VIN') !!}
                        {!! Form::text('chassis_number', $device->chassis_number, ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            {!! Form::submit(__('messages.update'), ['class' => 'btn btn-primary']) !!}
        </div>
        {!! Form::close() !!}
    </div>
</div>

<script>
$(function(){
    $('#edit_brand_id').on('change', function(){
        var brandId = $(this).val();
        var $model = $('#edit_model_id');
        $model.empty().append('<option value="">Select Model</option>');
        if(!brandId){ return; }
        $.get('/bookings/get-models/'+brandId, function(res){
            var models = (res && res.models) ? res.models : res;
            if(Array.isArray(models)){
                models.forEach(function(m){
                    if (m && m.id && m.name) {
                        $model.append('<option value="'+m.id+'">'+m.name+'</option>');
                    }
                });
            }
        });
    });
});
</script>