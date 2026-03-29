<div class="modal-dialog modal-xl" role="document" style="width: 90%; max-width: 1200px;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                @lang('crm::lang.contact_devices') - {{ $contact->name }}
            </h4>
        </div>
        <div class="modal-body">
            <div class="row mb-10">
                <div class="col-md-12">
                    <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="add_contact_device" style="margin-bottom: 10px;">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="contact_devices_table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>@lang('repair::lang.brand')</th>
                                    <th>@lang('repair::lang.model')</th>
                                    <th>@lang('repair::lang.color')</th>
                                    <th>@lang('repair::lang.plate_number')</th>
                                    <th>@lang('repair::lang.manufacturing_year')</th>
                                    <th>@lang('repair::lang.car_type')</th>
                                    <th>@lang('repair::lang.chassis_number')</th>
                                    <th class="text-center">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contact_devices as $device)
                                    <tr>
                                        <td>{{ $device->brand_name }}</td>
                                        <td>{{ $device->model_name }}</td>
                                        <td>{{ $device->color }}</td>
                                        <td>{{ $device->plate_number }}</td>
                                        <td>{{ $device->manufacturing_year }}</td>
                                        <td>{{ $device->car_type }}</td>
                                        <td>{{ $device->chassis_number }}</td>
                                        <td class="text-center">
                                            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary edit_contact_device" data-id="{{ $device->id }}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-danger delete_contact_device" data-id="{{ $device->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

<!-- Add Contact Device Modal -->
<div class="modal fade" id="add_contact_device_modal" tabindex="-1" role="dialog" aria-labelledby="addContactDeviceModalLabel">
    <div class="modal-dialog modal-md" role="document" style="width: 60%; max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="addContactDeviceModalLabel">@lang('crm::lang.add_contact_device')</h4>
            </div>
            <form id="add_contact_device_form">
                <div class="modal-body">
                    <input type="hidden" name="contact_id" value="{{ $contact->id }}">
                    <div class="form-group">
                        <label for="device_id">@lang('repair::lang.brand') *</label>
                        <select class="form-control" id="device_id" name="device_id" required>
                            <option value="">@lang('messages.please_select')</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="models_id">@lang('repair::lang.model') *</label>
                        <select class="form-control" id="models_id" name="models_id" required>
                            <option value="">@lang('messages.please_select')</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="color">@lang('repair::lang.color') *</label>
                        <input type="text" class="form-control" id="color" name="color" required>
                    </div>
                    <div class="form-group">
                        <label for="plate_number">@lang('repair::lang.plate_number') *</label>
                        <input type="text" class="form-control" id="plate_number" name="plate_number" required>
                    </div>
                    <div class="form-group">
                        <label for="manufacturing_year">@lang('repair::lang.manufacturing_year') *</label>
                        <input type="text" class="form-control" id="manufacturing_year" name="manufacturing_year" required>
                    </div>
                    <div class="form-group">
                        <label for="car_type">@lang('repair::lang.car_type')</label>
                        <input type="text" class="form-control" id="car_type" name="car_type">
                    </div>
                    <div class="form-group">
                        <label for="chassis_number">@lang('repair::lang.chassis_number')</label>
                        <input type="text" class="form-control" id="chassis_number" name="chassis_number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tw-dw-btn tw-dw-btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Custom styles for the contact devices modal */
    .modal-xl {
        width: 90%;
        max-width: 1200px;
    }

    #contact_devices_table_wrapper .dataTables_filter {
        float: right;
    }

    #contact_devices_table_wrapper .dataTables_filter input {
        margin-left: 0.5em;
        width: 200px;
    }

    #contact_devices_table_wrapper .dataTables_length {
        float: left;
    }

    #contact_devices_table {
        width: 100% !important;
    }

    #contact_devices_table th,
    #contact_devices_table td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    @media screen and (max-width: 767px) {
        .modal-xl {
            width: 95%;
        }

        #contact_devices_table_wrapper .dataTables_filter,
        #contact_devices_table_wrapper .dataTables_length {
            text-align: left;
            float: none;
        }
    }
</style>

<script type="text/javascript">
    $(document).ready(function() {
        // Initialize DataTable with responsive features
        $('#contact_devices_table').DataTable({
            responsive: true,
            scrollX: false,
            autoWidth: false,
            columnDefs: [
                { width: '12%', targets: 0 }, // Brand
                { width: '12%', targets: 1 }, // Model
                { width: '10%', targets: 2 }, // Color
                { width: '12%', targets: 3 }, // Plate Number
                { width: '12%', targets: 4 }, // Manufacturing Year
                { width: '12%', targets: 5 }, // Car Type
                { width: '20%', targets: 6 }, // Chassis Number
                { width: '10%', targets: 7 }  // Action
            ],
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>><"row"<"col-sm-12"<"table-responsive"t>>><"row"<"col-sm-5"i><"col-sm-7"p>>',
            language: {
                search: '',
                searchPlaceholder: 'Search...'
            }
        });

        // Show add contact device modal
        $('#add_contact_device').click(function() {
            $('#add_contact_device_form')[0].reset();
            $('#add_contact_device_modal').modal('show');
        });

        // Handle brand change to load models
        $('#device_id').change(function() {
            var brand_id = $(this).val();
            if (brand_id) {
                $.ajax({
                    url: '/crm/get-models-for-brand/' + brand_id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#models_id').empty();
                        $('#models_id').append('<option value="">@lang("messages.please_select")</option>');
                        if (data.success) {
                            $.each(data.models, function(key, model) {
                                $('#models_id').append('<option value="' + model.id + '">' + model.name + '</option>');
                            });
                        }
                    }
                });
            } else {
                $('#models_id').empty();
                $('#models_id').append('<option value="">@lang("messages.please_select")</option>');
            }
        });

        // Handle add contact device form submission
        $('#add_contact_device_form').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: '/crm/contact-devices',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        $('#add_contact_device_modal').modal('hide');
                        // Reload the contact devices modal
                        loadContactDevices();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        // Handle edit contact device button click
        $(document).on('click', '.edit_contact_device', function() {
            var device_id = $(this).data('id');
            $.ajax({
                url: '/crm/contact-device/' + device_id,
                type: 'GET',
                dataType: 'html',
                success: function(result) {
                    $('.edit_contact_device_modal').html(result).modal('show');
                }
            });
        });

        // Handle delete contact device button click
        $(document).on('click', '.delete_contact_device', function() {
            var device_id = $(this).data('id');
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((confirmed) => {
                if (confirmed) {
                    $.ajax({
                        url: '/crm/contact-device/' + device_id,
                        type: 'DELETE',
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                // Reload the contact devices modal
                                loadContactDevices();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        function loadContactDevices() {
            var contact_id = $('input[name="contact_id"]').val();
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
