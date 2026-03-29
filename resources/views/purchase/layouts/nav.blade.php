<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#purchase-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ action([\App\Http\Controllers\PurchaseDashboardController::class, 'index']) }}"><i class="fa fa-shopping-cart"></i> {{ __('purchase.purchases') }}</a>
            </div>

            <div class="collapse navbar-collapse" id="purchase-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    @if(auth()->user()->can('purchases.dashboard'))
                        <li @if(request()->segment(1) == 'purchases' && request()->segment(2) == 'dashboard') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\PurchaseDashboardController::class, 'index']) }}">
                                <i class="fa fa-tachometer"></i> {{ __('business.dashboard') }}
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create'))
                        <li @if(request()->segment(1) == 'purchases' && empty(request()->segment(2))) class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\PurchaseController::class, 'index']) }}">
                                <i class="fa fa-list"></i> {{ __('purchase.all_purchases') }}
                            </a>
                        </li>
                    @endif

                    @can('purchase.create')
                        <li @if(request()->segment(1) == 'purchases' && request()->segment(2) == 'create') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\PurchaseController::class, 'create']) }}">
                                <i class="fa fa-plus"></i> {{ __('purchase.add_purchase') }}
                            </a>
                        </li>
                    @endcan

          

                    
                    
                    @if(auth()->user()->can('purchase.view'))
                    <li @if(request()->segment(1) == 'purchase-return' && request()->segment(2) == 'create') class="active" @endif>
                        <a href="{{ url('purchase-return') }}">
                            <i class="fa fa-undo"></i> {{ __('purchase.purchase_returns') }}
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create'))

                <li @if(request()->segment(1) == 'repair' && request()->segment(2) == 'Purchase_Requests') class="active" @endif>
                    <a href="{{ url('/Purchase-Requests') }}">
                        {{ __('repair::lang.Purchase_Requests') }}
                    </a>
                </li>
                @endif

                </ul>
            </div>
        </div>
    </nav>
</section>
