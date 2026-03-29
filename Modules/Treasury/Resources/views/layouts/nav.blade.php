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
                <a class="navbar-brand" href="{{action([\Modules\Treasury\Http\Controllers\TreasuryController::class, 'index'])}}"><i class="fas fa-university"></i> {{__('treasury::lang.treasury')}}</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav d-block " style="position: relative !important;">
                    @if(auth()->user()->can('treasury.view') || auth()->user()->can('treasury.create'))
                        <li @if(request()->segment(1) == 'treasury' && empty(request()->segment(2))) class="active" @endif>
                            <a href="{{action([\Modules\Treasury\Http\Controllers\TreasuryController::class, 'index'])}}">
                                @lang('treasury::lang.dashboard')
                            </a>
                        </li>
                    @endif

              

                    @if(auth()->user()->can('treasury.view') || auth()->user()->can('treasury.create'))
                        <li @if(request()->segment(2) == 'internal-transfers') class="active" @endif>
                            <a href="{{route('treasury.internal.transfers.index')}}">
                                @lang('treasury::lang.internal_transfers')
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('treasury.view') || auth()->user()->can('treasury.create'))
                        <li @if(request()->segment(2) == 'payments') class="active" @endif>
                            <a href="{{ route('treasury.payments.index') }}">
                                <i class="fas fa-money-check-alt"></i> @lang('treasury::lang.payment_transactions')
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('treasury.view') || auth()->user()->can('treasury.create'))
                        <li @if(request()->segment(2) == 'due-transactions') class="active" @endif>
                            <a href="{{ route('treasury.due-transactions.index') }}">
                                <i class="fas fa-clock"></i> @lang('treasury::lang.due_transactions')
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('treasury.view') || auth()->user()->can('treasury.create'))
                        <li @if(request()->segment(2) == 'opening-balance') class="active" @endif>
                            <a href="{{route('treasury.opening-balance.index')}}">
                                <i class="fas fa-plus-circle"></i> @lang('treasury::lang.opening_balance')
                            </a>
                        </li>
                    @endif

                    @can('treasury.create')
                        <li @if(request()->segment(1) == 'income') class="active" @endif>
                            <a href="{{route('treasury.income')}}">
                                @lang('treasury::lang.add_income')
                            </a>
                        </li>
                    @endcan

                    @can('treasury.create')
                        <li @if(request()->segment(1) == 'expense') class="active" @endif>
                            <a href="{{route('treasury.expense')}}">
                                @lang('treasury::lang.add_expense')
                            </a>
                        </li>
                    @endcan
                </ul>

            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</section>