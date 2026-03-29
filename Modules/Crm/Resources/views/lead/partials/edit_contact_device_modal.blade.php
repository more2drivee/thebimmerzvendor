<div class="modal-dialog modal-md" role="document" style="width: 60%; max-width: 600px;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('crm::lang.edit_contact_device')</h4>
        </div>
        <form id="edit_contact_device_form">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_device_id">@lang('repair::lang.brand') *</label>
                    <select class="form-control" id="edit_device_id" name="device_id" required>
                        <option value="">@lang('messages.please_select')</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" {{ $contact_device->device_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_models_id">@lang('repair::lang.model') *</label>
                    <select class="form-control" id="edit_models_id" name="models_id" required>
                        <option value="{{ $contact_device->models_id }}">{{ $contact_device->model_name }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_color">@lang('repair::lang.color') *</label>
                    <input type="text" class="form-control" id="edit_color" name="color" value="{{ $contact_device->color }}" required>
                </div>
                <div class="form-group">
                    <label for="edit_plate_number">@lang('repair::lang.plate_number') *</label>
                    <input type="text" class="form-control" id="edit_plate_number" name="plate_number" value="{{ $contact_device->plate_number }}" required>
                </div>
                <div class="form-group">
                    <label for="edit_manufacturing_year">@lang('repair::lang.manufacturing_year') *</label>
                    <input type="text" class="form-control" id="edit_manufacturing_year" name="manufacturing_year" value="{{ $contact_device->manufacturing_year }}" required>
                </div>
                <div class="form-group">
                    <label for="edit_car_type">@lang('repair::lang.car_type')</label>
                    <input type="text" class="form-control" id="edit_car_type" name="car_type" value="{{ $contact_device->car_type }}">
                </div>
                <div class="form-group">
                    <label for="edit_chassis_number">@lang('repair::lang.chassis_number')</label>
                    <input type="text" class="form-control" id="edit_chassis_number" name="chassis_number" value="{{ $contact_device->chassis_number }}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.update')</button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Handle brand change to load models
        $('#edit_device_id').change(function() {
            var brand_id = $(this).val();
            if (brand_id) {
                $.ajax({
                    url: '/crm/get-models-for-brand/' + brand_id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#edit_models_id').empty();
                        $('#edit_models_id').append('<option value="">@lang("messages.please_select")</option>');
                        if (data.success) {
                            $.each(data.models, function(key, model) {
                                $('#edit_models_id').append('<option value="' + model.id + '">' + model.name + '</option>');
                            });
                        }
                    }
                });
            } else {
                $('#edit_models_id').empty();
                $('#edit_models_id').append('<option value="">@lang("messages.please_select")</option>');
            }
        });

        // Load models for the selected brand on page load
        var brand_id = $('#edit_device_id').val();
        var selected_model_id = '{{ $contact_device->models_id }}';
        if (brand_id) {
            $.ajax({
                url: '/crm/get-models-for-brand/' + brand_id,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#edit_models_id').empty();
                    $('#edit_models_id').append('<option value="">@lang("messages.please_select")</option>');
                    if (data.success) {
                        $.each(data.models, function(key, model) {
                            var selected = (model.id == selected_model_id) ? 'selected' : '';
                            $('#edit_models_id').append('<option value="' + model.id + '" ' + selected + '>' + model.name + '</option>');
                        });
                    }
                }
            });
        }

        // Handle edit contact device form submission
        $('#edit_contact_device_form').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: '/crm/contact-device/{{ $contact_device->id }}',
                type: 'PUT',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        $('.edit_contact_device_modal').modal('hide');
                        // Reload the contact devices modal
                        loadContactDevices();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        function loadContactDevices() {
            var contact_id = '{{ $contact_device->contact_id }}';
            $.ajax({
                url: '/crm/contact-devices/' + contact_id,
                type: 'GET',
                dataType: 'html',
                success: function(result) {
                    $('.contact_devices_modal').html(result);
                }
            });
        }
    });
</script>
