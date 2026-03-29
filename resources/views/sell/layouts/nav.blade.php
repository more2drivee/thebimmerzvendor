<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#sell-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ action([\App\Http\Controllers\SellDashboardController::class, 'index']) }}"><i class="fa fa-shopping-cart"></i> {{ __('sale.sells') }}</a>
            </div>

            <div class="collapse navbar-collapse" id="sell-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    @if(auth()->user()->can('sell.dashboard'))
                        <li @if(request()->segment(1) == 'sells' && request()->segment(2) == 'dashboard') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellDashboardController::class, 'index']) }}">
                                <i class="fa fa-tachometer"></i> {{ __('business.dashboard') }}
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('sell.view') || auth()->user()->can('sell.create'))
                        <li @if(request()->segment(1) == 'sells' && empty(request()->segment(2))) class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellController::class, 'index']) }}">
                                <i class="fa fa-list"></i> {{ __('sale.all_sales') }}
                            </a>
                        </li>
                    @endif 

                    @can('sell.create')
                        <li @if(request()->segment(1) == 'sells' && request()->segment(2) == 'create') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellController::class, 'create']) }}">
                                <i class="fa fa-plus"></i> {{ __('sale.add_sale') }}
                            </a>
                        </li>
                    @endcan

                    @if(auth()->user()->can('sell.view'))
                        <li @if(request()->segment(1) == 'sells' && request()->segment(2) == 'return') class="active" @endif>
                            <a href="{{ url('sell-return') }}">
                                <i class="fa fa-undo"></i> {{ __('sale.sell_returns') }}
                            </a>
                        </li>
                    @endif

               
                    @if( auth()->user()->can('sell.create'))
                        <li @if(request()->segment(1) == 'pos' && empty(request()->segment(2))) class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'index']) }}">
                                <i class="fa fa-desktop"></i> {{ __('sale.list_pos') }}
                            </a>
                        </li>
                        <li @if(request()->segment(1) == 'pos' && request()->segment(2) == 'create') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'create']) }}">
                                <i class="fa fa-shopping-basket"></i> {{ __('sale.pos_sale') }}
                            </a>
                        </li>
                    @endif

                    <!-- @if(in_array('add_sale', $enabled_modules) && auth()->user()->can('direct_sell.access'))
                        <li @if(request()->get('status') == 'draft') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellController::class, 'create'], ['status' => 'draft']) }}">
                                <i class="fa fa-file"></i> {{ __('lang_v1.add_draft') }}
                            </a>
                        </li>
                    @endif -->

                    <!-- @if(in_array('add_sale', $enabled_modules) && (auth()->user()->can('draft.view_all') || auth()->user()->can('draft.view_own')))
                        <li @if(request()->segment(1) == 'sells' && request()->segment(2) == 'drafts') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellController::class, 'getDrafts']) }}">
                                <i class="fa fa-files-o"></i> {{ __('lang_v1.list_drafts') }}
                            </a>
                        </li>
                    @endif -->

                    <!-- @if(in_array('add_sale', $enabled_modules) && auth()->user()->can('direct_sell.access'))
                        <li @if(request()->get('status') == 'quotation') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellController::class, 'create'], ['status' => 'quotation']) }}">
                                <i class="fa fa-file-text-o"></i> {{ __('lang_v1.add_quotation') }}
                            </a>
                        </li>
                    @endif

                    @if(in_array('add_sale', $enabled_modules) && (auth()->user()->can('quotation.view_all') || auth()->user()->can('quotation.view_own')))
                        <li @if(request()->segment(1) == 'sells' && request()->segment(2) == 'quotations') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellController::class, 'getQuotations']) }}">
                                <i class="fa fa-list-alt"></i> {{ __('lang_v1.list_quotations') }}
                            </a>
                        </li>
                    @endif -->

                    <li @if(request()->segment(1) == 'sells' && request()->segment(2) == 'bundles') class="active" @endif>
                        <a href="{{ action([\App\Http\Controllers\SellController::class, 'bundleIndex']) }}">
                            <i class="fa fa-cubes"></i> {{ __('bundles.overview.transactions_table') }}
                        </a>
                    </li>

                
                    @if(auth()->user()->can('discount.access'))
                        <li @if(request()->segment(1) == 'discount') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\DiscountController::class, 'index']) }}">
                                <i class="fa fa-percent"></i> {{ __('lang_v1.discounts') }}
                            </a>
                        </li>
                    @endif

                    <!-- @if(in_array('subscription', $enabled_modules) && auth()->user()->can('direct_sell.access'))
                        <li @if(request()->segment(1) == 'subscriptions') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'listSubscriptions']) }}">
                                <i class="fa fa-refresh"></i> {{ __('lang_v1.subscriptions') }}
                            </a>
                        </li>
                    @endif -->

                    @if(auth()->user()->can('sell.create'))
                        <li @if(request()->segment(1) == 'import-sales') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\ImportSalesController::class, 'index']) }}">
                                <i class="fa fa-upload"></i> {{ __('lang_v1.import_sales') }}
                            </a>
                        </li>
                    @endif

       

                  
                </ul>
            </div>
        </div>
    </nav>
</section>
