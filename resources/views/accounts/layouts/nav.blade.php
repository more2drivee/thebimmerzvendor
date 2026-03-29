<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#accounts-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ url('/account/dashboard') }}"><i class="fa fa-university"></i> {{ __('account.accounts') }}</a>
            </div>

            <div class="collapse navbar-collapse" id="accounts-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    @if(auth()->user()->can('payment-accounts.dashboard'))
                        <li @if(request()->segment(2) == 'dashboard') class="active" @endif>
                            <a href="{{ url('/account/dashboard') }}">
                                <i class="fa fa-tachometer"></i> {{ __('business.dashboard') }}
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('account.access'))
                        <li @if(request()->segment(2) == 'account' && request()->segment(3) == null) class="active" @endif>
                            <a href="{{ url('/account/account') }}">
                                <i class="fa fa-university"></i> {{ __('account.all_accounts') }}
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->can('account.access'))
                        <li @if(request()->segment(2) == 'account' && request()->segment(3) == 'fund-transfer') class="active" @endif>
                            <a href="{{ url('/account/fund-transfer') }}">
                                <i class="fa fa-exchange"></i> {{ __('account.fund_transfer') }}
                            </a>
                        </li>
                    @endif

                    
                    @if(auth()->user()->can('account.access'))
                        <li @if(request()->segment(2) == 'cash-flow') class="active" @endif>
                            <a href="{{ url('/account/cash-flow') }}">
                                <i class="fa fa-money"></i> {{ __('account.cash_flow') }}
                            </a>
                        </li>
                        @endif

                    @if(auth()->user()->can('account.access'))
                        <li @if(request()->segment(2) == 'dashboard')  @endif>
                            <a href="{{ url('/accounting/dashboard') }}">
                                <i class="fa fa-dashboard"></i> {{ __('account.dashboard') }}
                            </a>
                        </li>
                        @endif

                    @if(auth()->user()->can('account.access'))
                        <li @if(request()->segment(2) == 'balance-sheet') class="active" @endif>
                            <a href="{{ url('/account/balance-sheet') }}">
                                <i class="fa fa-file-text-o"></i> {{ __('account.balance_sheet') }}
                            </a>
                        </li>
                        @endif

                    @if(auth()->user()->can('account.access'))
                        <li @if(request()->segment(2) == 'trial-balance') class="active" @endif>
                            <a href="{{ url('/account/trial-balance') }}">
                                <i class="fa fa-file-text"></i> {{ __('account.trial_balance') }}
                            </a>
                        </li>
                    @endif
                  
                        <li @if(request()->segment(1) == 'expenses') class="active" @endif>
                            <a href="{{ url('/expenses') }}">
                                <i class="fa fa-dollar"></i> {{ __('expense.expenses') }}
                            </a>
                        </li>
                 
                </ul>
            </div>
        </div>
    </nav>
</section>