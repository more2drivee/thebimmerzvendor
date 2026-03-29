<div class="modal-dialog" role="document">
    <div class="modal-content">
        <form method="post" action="{{action([\App\Http\Controllers\LabourByVehicleController::class, 'updateLabourProduct'])}}" id="labour_product_edit_form">
            @csrf
            @method('PUT')
            <input type="hidden" name="id" value="{{ $product->id }}">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="gridSystemModalLabel">@lang('Edit Labour Product')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="labour_product_name">@lang('Name') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="labour_product_name" name="name" value="{{ $product->name }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="labour_product_category">@lang('Category')</label>
                            <select class="form-control select2" id="labour_product_category" name="category_id">
                                <option value="">{{ __('Please Select') }}</option>
                                @if(isset($categories) && count($categories) > 0)
                                    @foreach($categories as $id => $name)
                                        <option value="{{$id}}" {{ $product->category_id == $id ? 'selected' : '' }}>{{$name}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="labour_product_sku">@lang('SKU')</label>
                            <input type="text" class="form-control" id="labour_product_sku" name="sku" value="{{ $product->sku }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="labour_product_price">@lang('Labour Price') <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="labour_product_price" name="price" 
                                   step="0.01" min="0" value="{{ $variation ? number_format($variation->default_sell_price, 2, '.', '') : '' }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <small class="text-muted">
                            <i class="fa fa-info-circle"></i> 
                            @lang('Note: Labour products have stock management disabled and are marked as labour items.')
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

<script>
$(document).ready(function() {
    if ($('#labour_product_category').length && !$('#labour_product_category').hasClass('select2-hidden-accessible')) {
        $('#labour_product_category').select2({
            width: '100%',
            dropdownParent: $('.labour_products_modal'),
            placeholder: "{{ __('Please Select') }}",
            allowClear: true
        });
    }
});
</script>
