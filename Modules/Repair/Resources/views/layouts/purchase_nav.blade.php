<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ action([\App\Http\Controllers\PurchaseController::class, 'index']) }}"><i class="fas fa-shopping-cart"></i> {{__('repair::lang.Purchase_Requests')}}</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav d-block">
                
            
                    <li @if(request()->is('purchases')) class="active" @endif>
                        <a href="{{ action([\App\Http\Controllers\PurchaseController::class, 'index']) }}">
                            @lang('purchase.list_purchase')
                        </a>
                    </li>
                    <li @if(request()->is('purchases/create')) class="active" @endif>
                        <a href="{{ action([\App\Http\Controllers\PurchaseController::class, 'create']) }}">
                            @lang('purchase.add_purchase')
                        </a>
                    </li>
                    <li @if(request()->is('purchase-return')) class="active" @endif>
                        <a href="{{ action([\App\Http\Controllers\PurchaseReturnController::class, 'index']) }}">
                            @lang('lang_v1.list_purchase_return')
                        </a>
                    </li>
                    <li @if(request()->is('repair/Purchase_Requests')) class="active" @endif>
                        <a href="{{ action([\Modules\Repair\Http\Controllers\MaintenanceNoteController::class, 'index']) }}">
                            @lang('repair::lang.Purchase_Requests')
                        </a>
                    </li>

                
                </ul>

            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</section>