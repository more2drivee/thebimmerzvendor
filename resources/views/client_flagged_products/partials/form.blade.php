<div class="row">
    <form id="client_flagged_product_form" method="POST">
        @csrf
        @if($product)
            <input type="hidden" name="id" value="{{ $product->id }}">
            @method('PUT')
        @endif

        <div class="col-md-12">
            <div class="form-group">
                <label for="name">{{ __('product.name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $product->name ?? '' }}" required>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="sku">{{ __('product.sku') }}</label>
                <input type="text" class="form-control" id="sku" name="sku" value="{{ $product->sku ?? '' }}" placeholder="{{ __('client_flagged_products.auto_sku') }}">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="category_id">{{ __('category.category') }}</label>
                <select class="form-control select2" id="category_id" name="category_id">
                    <option value="">{{ __('lang_v1.select') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @if(isset($product) && $product->category_id == $category->id) selected @endif>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="brand_id">{{ __('brand.brand') }}</label>
                <select class="form-control select2" id="brand_id" name="brand_id">
                    <option value="">{{ __('lang_v1.select') }}</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @if(isset($product) && $product->brand_id == $brand->id) selected @endif>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="location_id">{{ __('business.location') }}</label>
                <select class="form-control select2" id="location_id" name="location_id">
                    <option value="">{{ __('lang_v1.select') }}</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @if(isset($product) && $product->location_id == $location->id) selected @endif>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="description">{{ __('product.description') }}</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ $product->description ?? '' }}</textarea>
            </div>
        </div>

        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                {{ __('client_flagged_products.info_text') }}
            </div>
        </div>

        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ isset($product) ? __('lang_v1.update') : __('lang_v1.save') }}
            </button>
        </div>
    </form>
</div>
