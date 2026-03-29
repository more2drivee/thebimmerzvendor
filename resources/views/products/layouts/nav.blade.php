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
                <a class="navbar-brand" href="{{ action([\App\Http\Controllers\ProductsDashboardController::class, 'index']) }}"><i class="fa fa-box"></i> {{ __('product.products') }}</a>
            </div>

            <div class="collapse navbar-collapse" id="products-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
       

                    @if (auth()->user()->can('product.view'))
                        <li @if(request()->segment(1) == 'products' && request()->segment(2) == '') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\ProductController::class, 'index']) }}">
                                <i class="fa fa-list"></i> {{ __('lang_v1.list_products') }}
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->can('product.view') || auth()->user()->can('product.create'))
                        <li @if(request()->segment(1) == 'product-management') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\ProductManagementController::class, 'index']) }}">
                                <i class="fa fa-list"></i> {{ __('product_management.title') }}
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->can('unit.view') || auth()->user()->can('unit.create') ||
                            auth()->user()->can('category.view') || auth()->user()->can('category.create') ||
                            auth()->user()->can('brand.view') || auth()->user()->can('brand.create') ||
                            auth()->user()->can('product.view') || auth()->user()->can('product.create') ||
                            auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create'))
                        <li @if(request()->segment(1) == 'product-organization') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\ProductOrganizationController::class, 'index']) }}">
                                <i class="fa fa-list"></i> {{ __('product_organization.title') }}
                            </a>
                        </li>
                    @endif

                </ul>
            </div>
        </div>
    </nav>
</section>
