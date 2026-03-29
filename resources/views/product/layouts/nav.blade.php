<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#products-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ route('dashboard_item', ['id' => app()->getLocale() == 'ar' ? 'المنتجات' : 'Products']) }}"><i class="fa fa-box"></i> {{ __('product.products') }}</a>
            </div>

            <div class="collapse navbar-collapse" id="products-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    @if(auth()->user()->can('products.dashboard'))
                        <li @if(request()->segment(2) == 'dashboard_item' && (request()->segment(3) == 'Products' || request()->segment(3) == 'المنتجات')) class="active" @endif>
                            <a href="{{ route('dashboard_item', ['id' => app()->getLocale() == 'ar' ? 'المنتجات' : 'Products']) }}">
                                <i class="fa fa-tachometer"></i> {{ __('business.dashboard') }}
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('product.view') || auth()->user()->can('product.create'))
                        <li @if(request()->segment(2) == 'products' && empty(request()->segment(3))) class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\ProductController::class, 'index']) }}">
                                <i class="fa fa-list"></i> {{ __('product.all_products') }}
                            </a>
                        </li>
                    @endif

                    @can('product.create')
                        <li @if(request()->segment(2) == 'products' && request()->segment(3) == 'create') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\ProductController::class, 'create']) }}">
                                <i class="fa fa-plus"></i> {{ __('product.add_product') }}
                            </a>
                        </li>
                    @endcan

                    @if(auth()->user()->can('category.view'))
                        <li @if(request()->segment(2) == 'categories') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\TaxonomyController::class, 'index']) }}">
                                <i class="fa fa-sitemap"></i> {{ __('category.categories') }}
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('brand.view'))
                        <li @if(request()->segment(2) == 'brands') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\BrandController::class, 'index']) }}">
                                <i class="fa fa-tag"></i> {{ __('brand.brands') }}
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('unit.view'))
                        <li @if(request()->segment(2) == 'units') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\UnitController::class, 'index']) }}">
                                <i class="fa fa-ruler"></i> {{ __('unit.units') }}
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('variation.view'))
                        <li @if(request()->segment(2) == 'variations') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\VariationController::class, 'index']) }}">
                                <i class="fa fa-cubes"></i> {{ __('product.variations') }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
</section>
