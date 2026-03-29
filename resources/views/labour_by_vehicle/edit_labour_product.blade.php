<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('Edit Labour Product')</h4>
        </div>

        <form id="edit_labour_product_form" method="POST" action="{{ route('labour-by-vehicle.update-labour-product') }}">
            @csrf
            <input type="hidden" name="mapping_id" value="{{ $mapping->id }}">

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('Product Name')</label>
                            <input type="text" class="form-control" value="{{ $product->name }}" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('Selling Price')</label>
                            <input type="text" class="form-control" value="{{ number_format($variation->default_sell_price ?? 0, 2) }}" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('Status')</label>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="is_active" value="1" {{ $mapping->is_active ? 'checked' : '' }}>
                                    @lang('Active')
                                </label>
                            </div>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="is_active" value="0" {{ !$mapping->is_active ? 'checked' : '' }}>
                                    @lang('Inactive')
                                </label>
                            </div>
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

<script>
$(document).ready(function() {
    $('#edit_labour_product_form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    $('#edit_labour_product_modal').modal('hide');
                    // Reload the manage labours table
                    var manageModal = $('#edit_labour_product_modal').closest('.modal').prev('.modal');
                    if (manageModal.length) {
                        var tbody = manageModal.find('#labours_tbody');
                        tbody.html('<tr><td colspan="4" class="text-center">@lang("Loading...")</td></tr>');
                        
                        // Get the labour_by_vehicle_id from the URL
                        var url = manageModal.find('#manage_labours_table').data('url');
                        if (url) {
                            $.ajax({
                                url: url,
                                method: 'GET',
                                success: function(response) {
                                    tbody.empty();
                                    if (response.data && response.data.length > 0) {
                                        $.each(response.data, function(index, item) {
                                            var row = $('<tr>');
                                            row.append('<td>' + item.name + '</td>');
                                            row.append('<td>' + item.status + '</td>');
                                            row.append('<td>' + item.price + '</td>');
                                            row.append('<td>' + item.action + '</td>');
                                            tbody.append(row);
                                        });
                                    } else {
                                        tbody.append('<tr><td colspan="4" class="text-center">@lang("No records found")</td></tr>');
                                    }
                                }
                            });
                        }
                    }
                } else {
                    alert(response.message || '@lang("Error")');
                }
            },
            error: function() {
                alert('@lang("Error processing request")');
            }
        });
    });
});
</script>
