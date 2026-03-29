<section class="no-print">
    <nav
        class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#sms-messages-navbar" aria-expanded="false"
                    style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ route('sms.messages.index') }}">
                    <i class="fas fa-sms"></i> @lang('sms::lang.sms_messages')
                </a>
            </div>
            <div class="collapse navbar-collapse" id="sms-messages-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    <li @if(request()->routeIs('sms.messages.dashboard')) class="active" @endif>
                        <a href="{{ route('sms.messages.dashboard') }}">
                            <i class="fa fa-chart-line"></i> @lang('sms::lang.sms_dashboard')
                        </a>
                    </li>
                    <li @if(request()->routeIs('sms.messages.index')) class="active" @endif>
                        <a href="{{ route('sms.messages.index') }}">
                            <i class="fa fa-list"></i> {{ __('messages.all') }}
                        </a>
                    </li>
                    <li @if(request()->routeIs('sms.messages.create')) class="active" @endif>
                        <a href="{{ route('sms.messages.create') }}">
                            <i class="fa fa-plus"></i> {{ __('messages.add') }}
                        </a>
                    </li>
                    <li @if(request()->routeIs('sms.messages.settings')) class="active" @endif>
    <a href="{{ route('sms.messages.settings') }}">
        <i class="fa fa-cog"></i> {{ __('lang_v1.sms_settings') }}
    </a>
</li>
                </ul>
            </div>
        </div>
    </nav>
</section>