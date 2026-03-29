<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('Manage Labours')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <strong>Brand:</strong> {{ $labour_by_vehicle->brand_name ?? '-' }}
                        <strong>Model:</strong> {{ $labour_by_vehicle->model_name ?? '-' }}
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="manage_labours_table" data-url="{{ route('labour-by-vehicle.manage-labours.datatable', $labour_by_vehicle->id) }}">
                    <thead>
                        <tr>
                            <th>@lang('Name')</th>
                            <th>@lang('Type')</th>
                            <th>@lang('Price')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var labourByVehicleId = {{ $labour_by_vehicle->id }};
    var manageLaboursUrl = '{{ route("labour-by-vehicle.manage-labours.datatable", $labour_by_vehicle->id) }}';
    var manageLaboursTable = $('#manage_labours_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: manageLaboursUrl
        },
        columns: [
            { data: 'name', name: 'products.name' },
            { data: 'type', name: 'type', orderable: false, searchable: false },
            { data: 'price_input', name: 'price_input', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
    
    $(document).on('click', '.save-labour-price', function(e) {
        e.preventDefault();
        var btn = $(this);
        var mappingId = btn.data('mapping-id');
        var productId = btn.data('product-id');
        var priceInput = $('#labour_price_' + productId);
        var price = priceInput.val();

        $.ajax({
            url: '{{ route("labour-by-vehicle.update-labour-price") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                mapping_id: mappingId,
                product_id: productId,
                labour_by_vehicle_id: labourByVehicleId,
                price: price
            },
            success: function(response) {
                if (response.success) {
                    manageLaboursTable.ajax.reload(null, false);
                    if (response.message) {
                        toastr.success(response.message);
                    }
                } else {
                    if (response.message) {
                        toastr.error(response.message);
                    } else {
                        toastr.error('@lang("Error")');
                    }
                }
            },
            error: function() {
                toastr.error('@lang("Error processing request")');
            }
        });
    });

    $(document).on('change', '.toggle-labour-product', function() {
        var checkbox = $(this);
        var mappingId = checkbox.data('mapping-id');

        if (!mappingId) {
            return;
        }

        $.ajax({
            url: '{{ route("labour-by-vehicle.update-labour-product") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                mapping_id: mappingId,
                is_active: checkbox.is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (!response.success) {
                    checkbox.prop('checked', !checkbox.is(':checked'));
                    if (response.message) {
                        toastr.error(response.message);
                    } else {
                        toastr.error('@lang("Error")');
                    }
                } else if (response.message) {
                    toastr.success(response.message);
                }
            },
            error: function() {
                checkbox.prop('checked', !checkbox.is(':checked'));
                toastr.error('@lang("Error processing request")');
            }
        });
    });
    
});
</script>
