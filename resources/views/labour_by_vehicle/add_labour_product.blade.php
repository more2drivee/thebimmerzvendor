<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('Add Labour Products')</h4>
        </div>

        <div class="modal-body">
            <form id="add_labour_product_form">
                @csrf
                <div class="form-group">
                    <label>@lang('Select Products')</label>
                    <select name="product_ids[]" id="product_select" class="form-control select2" multiple="multiple">
                        <option value="">@lang('Loading...')</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
            <button type="button" class="btn btn-success" id="add_selected_products">@lang('Add Selected')</button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var labourByVehicleId = {{ $labour_by_vehicle->id }};
    
    // Initialize Select2
    $('#product_select').select2({
        width: '100%',
        placeholder: '@lang("Select products to add")'
    });
    
    // Load available products
    $.ajax({
        url: '{{ route("labour-by-vehicle.available-products", $labour_by_vehicle->id) }}',
        method: 'GET',
        success: function(response) {
            var select = $('#product_select');
            select.empty();
            
            if (response.data && response.data.length > 0) {
                $.each(response.data, function(index, item) {
                    var typeLabel = item.category_name ? ' | ' + item.category_name : '';
                    select.append('<option value="' + item.id + '">' + item.name + typeLabel + ' - ' + item.price + '</option>');
                });
            } else {
                select.append('<option value="">@lang("No available products")</option>');
            }
        },
        error: function() {
            $('#product_select').html('<option value="">@lang("Error loading products")</option>');
        }
    });
    
    // Add selected products button click
    $('#add_selected_products').on('click', function(e) {
        e.preventDefault();
        var selectedProducts = $('#product_select').val();
        
        if (!selectedProducts || selectedProducts.length === 0) {
            alert('@lang("Please select at least one product")');
            return;
        }
        
        if (!confirm('@lang("Are you sure you want to add selected products?")')) {
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> @lang("Adding...")');
        
        $.ajax({
            url: '{{ route("labour-by-vehicle.add-multiple-products") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                labour_by_vehicle_id: labourByVehicleId,
                product_ids: selectedProducts
            },
            success: function(response) {
                if (response.success) {
                    var manageUrl = $('#add_labour_product_modal').data('manage-url');
                    $('#add_labour_product_modal').modal('hide');

                    if (manageUrl) {
                        $('.labour_vehicle_modal').load(manageUrl, function() {
                            $('.labour_vehicle_modal').modal('show');
                        });
                    }
                } else {
                    alert(response.message || '@lang("Error")');
                    btn.prop('disabled', false).html('@lang("Add Selected")');
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr.responseText);
                alert('@lang("Error processing request")');
                btn.prop('disabled', false).html('@lang("Add Selected")');
            }
        });
    });
});
</script>
