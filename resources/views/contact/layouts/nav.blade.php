<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#contact-loyalty-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ action([\App\Http\Controllers\ContactController::class, 'loyaltyRequestsIndex']) }}"><i class="fa fa-users"></i> {{ __('lang_v1.loyalty_requests') }}</a>
            </div>

            <div class="collapse navbar-collapse" id="contact-loyalty-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                      @if (auth()->user()->can('supplier.view') || auth()->user()->can('supplier.view_own'))
                            <li class="{{ (request()->input('type') == 'supplier') ? 'active' : '' }}">
                                <a href="{{ action([\App\Http\Controllers\ContactController::class, 'index'], ['type' => 'supplier']) }}" title="{{ __('report.supplier') }}"><i class="fa fa-users"></i> {{ __('report.supplier') }}</a>
                            </li>
                        @endif
                        @if (auth()->user()->can('customer.view') || auth()->user()->can('customer.view_own'))
                            <li class="{{ (request()->input('type') == 'customer') ? 'active' : '' }}">
                                <a href="{{ action([\App\Http\Controllers\ContactController::class, 'index'], ['type' => 'customer']) }}" title="{{ __('report.customer') }}"><i class="fa fa-users"></i> {{ __('report.customer') }}</a>
                            </li>
                            <li class="{{ (request()->segment(1) == 'customer-group') ? 'active' : '' }}">
                                <a href="{{ action([\App\Http\Controllers\CustomerGroupController::class, 'index']) }}" title="{{ __('lang_v1.customer_groups') }}"><i class="fa fa-users"></i> {{ __('lang_v1.customer_groups') }}</a>
                            </li>
                            <li class="{{ (request()->segment(1) == 'contacts' && request()->segment(2) == 'loyalty-requests') ? 'active' : '' }}">
                                <a href="{{ action([\App\Http\Controllers\ContactController::class, 'loyaltyRequestsIndex']) }}" title="{{ __('lang_v1.loyalty_requests') }}"><i class="fa fa-users"></i> {{ __('lang_v1.loyalty_requests') }}</a>
                            </li>
                        @endif
                </ul>
            </div>
        </div>
    </nav>
</section>
