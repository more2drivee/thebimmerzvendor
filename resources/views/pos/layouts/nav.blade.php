<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#pos-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ action([\App\Http\Controllers\POSDashboardController::class, 'index']) }}"><i class="fa fa-shopping-cart"></i> {{ __('pos.point_of_sale') }}</a>
            </div>

            <div class="collapse navbar-collapse" id="pos-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    @if(auth()->user()->can('point-of-sale.dashboard'))
                        <li @if(request()->segment(1) == 'pos' && request()->segment(2) == 'dashboard') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\POSDashboardController::class, 'index']) }}">
                                <i class="fa fa-tachometer"></i> {{ __('business.dashboard') }}
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('sell.view') || auth()->user()->can('sell.create'))
                        <li @if(request()->segment(1) == 'pos' && empty(request()->segment(2))) class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'index']) }}">
                                <i class="fa fa-list"></i> {{ __('pos.all_sales') }}
                            </a>
                        </li>
                    @endif

                    @can('sell.create')
                        <li @if(request()->segment(1) == 'pos' && request()->segment(2) == 'create') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}">
                                <i class="fa fa-plus"></i> {{ __('pos.new_sale') }}
                            </a>
                        </li>
                    @endcan

                    @if(auth()->user()->can('sell.view'))
                        <li @if(request()->segment(1) == 'sell-return') class="active" @endif>
                            <a href="{{ url('/sell-return') }}">
                                <i class="fa fa-undo"></i> {{ __('pos.returns') }}
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('sell.view'))
                        <li @if(request()->segment(1) == 'shipments') class="active" @endif>
                            <a href="{{ url('/shipments') }}">
                                <i class="fa fa-truck"></i> {{ __('pos.shipments') }}
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('discount.view'))
                        <li @if(request()->segment(1) == 'discount') class="active" @endif>
                            <a href="{{ url('/discount') }}">
                                <i class="fa fa-percent"></i> {{ __('pos.discounts') }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
</section>
