<form id="product_form" method="POST" action="{{ route('package-products.store') }}">
    @csrf
    @if($packageProduct)
        @method('PUT')
        <input type="hidden" name="id" value="{{ $packageProduct->id }}">
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="package_id">Service Package *</label>
                <select class="form-control select2" id="package_id" name="package_id" style="width:100%" required>
                    <option value="">Select Service Package</option>
                    @if($servicePackage)
                        <option value="{{ $servicePackage->id }}" selected>{{ $servicePackage->name }}</option>
                    @endif
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="product_id">Product *</label>
                <select class="form-control select2" id="product_id" name="product_id" style="width:100%" required>
                    <option value="">Select Product</option>
                    @if(isset($products) && $products->count() > 0)
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ $packageProduct && $packageProduct->product_id == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">
            {{ $packageProduct ? 'Update' : 'Add' }} Product to Package
        </button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
    </div>
</form>
