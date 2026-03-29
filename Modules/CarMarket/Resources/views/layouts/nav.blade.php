<section class="no-print">
    <nav
        class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#carmarket-navbar" aria-expanded="false"
                    style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ route('carmarket.index') }}">
                    <i class="fa fa-car"></i> @lang('carmarket::lang.module_title')
                </a>
            </div>
            <div class="collapse navbar-collapse" id="carmarket-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    <li @if(request()->routeIs('carmarket.index') || request()->routeIs('carmarket.vehicles.*')) class="active" @endif>
                        <a href="{{ route('carmarket.index') }}">
                            <i class="fa fa-list"></i> @lang('carmarket::lang.vehicles')
                        </a>
                    </li>
                    <li @if(request()->routeIs('carmarket.inquiries*')) class="active" @endif>
                        <a href="{{ route('carmarket.inquiries') }}">
                            <i class="fa fa-envelope"></i> @lang('carmarket::lang.inquiries')
                        </a>
                    </li>
                    <li @if(request()->routeIs('carmarket.reports*')) class="active" @endif>
                        <a href="{{ route('carmarket.reports') }}">
                            <i class="fa fa-flag"></i> @lang('carmarket::lang.reports')
                        </a>
                    </li>
                    <li @if(request()->routeIs('carmarket.settings')) class="active" @endif>
                        <a href="{{ route('carmarket.settings') }}">
                            <i class="fa fa-cog"></i> {{ __('messages.settings') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</section>
