<section class="no-print">
    <nav
        class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#checkcar-navbar" aria-expanded="false"
                    style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ route('checkcar.inspections.index') }}">
                    <i class="fa fa-car"></i> @lang('checkcar::lang.module_title')
                </a>
            </div>
            <div class="collapse navbar-collapse" id="checkcar-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    <li @if(request()->routeIs('checkcar.inspections.index')) class="active" @endif>
                        <a href="{{ route('checkcar.inspections.index') }}">
                            <i class="fa fa-list"></i> {{ __('messages.all') }}
                        </a>
                    </li>
            
                    <li @if(request()->routeIs('checkcar.settings.index')) class="active" @endif>
                        <a href="{{ route('checkcar.settings.index') }}">
                            <i class="fa fa-cog"></i> {{ __('messages.settings') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</section>
