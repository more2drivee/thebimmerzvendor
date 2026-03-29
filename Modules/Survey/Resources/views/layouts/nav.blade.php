<section class="no-print">
    <style type="text/css">
        #contacts_login_dropdown::after {
            display: inline-block;
            width: 0;
            height: 0;
            margin-left: 0.255em;
            vertical-align: 0.255em;
            content: "";
            border-top: 0.3em solid;
            border-right: 0.3em solid transparent;
            border-bottom: 0;
            border-left: 0.3em solid transparent;
        }
    </style>
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-2 tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{action([\Modules\Survey\Http\Controllers\DashboardController::class, 'index'])}}"><i class="fas fa fa-broadcast-tower"></i> {{__('survey::lang.survey')}}</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav d-block">
                    @if(auth()->user()->can('crm.access_all_leads') || auth()->user()->can('crm.access_own_leads'))
                    <li @if(request()->segment(2) == 'Show') class="active" @endif><a href="{{ route('survey.index') }}">@lang('survey::lang.all-surveys')</a></li>
                    @endif

                    @if(auth()->user()->can('crm.access_all_leads') || auth()->user()->can('crm.access_own_leads'))
                    <li @if(request()->segment(2) == 'add') class="active" @endif><a href="{{ route('survey.create') }}">@lang('survey::lang.add-survey')</a></li>
                    @endif

                    @if(auth()->user()->can('crm.access_all_schedule') || auth()->user()->can('crm.access_own_schedule'))
                    <li @if(request()->segment(2) == 'create-group') class="active" @endif><a href="{{ route('create.group') }}">@lang('survey::lang.create-group')</a></li>
                    @endif

                    @if(auth()->user()->can('crm.access_all_schedule') || auth()->user()->can('crm.access_own_schedule'))
                    <li @if(request()->segment(2) == 'create-group-service') class="active" @endif><a href="{{ route('create.group.service') }}">@lang('survey::lang.create-group-service')</a></li>
                    @endif


                    @if(auth()->user()->can('crm.access_all_schedule') || auth()->user()->can('crm.access_own_schedule'))
                    <li @if(request()->segment(2) == 'show-survey-sent') class="active" @endif><a href="{{ route('survey.index.sent') }}">@lang('survey::lang.show-survey-sent')</a></li>
                    @endif

                    @if(auth()->user()->can('crm.access_all_schedule') || auth()->user()->can('crm.access_own_schedule'))
                    <li @if(request()->segment(2) == 'show-group') class="active" @endif><a href="{{ route('show.groups') }}">@lang('survey::lang.show-groups')</a></li>
                    @endif

                    @if(auth()->user()->can('crm.access_all_schedule') || auth()->user()->can('crm.access_own_schedule'))
                    <li @if(request()->segment(2) == 'general-group') class="active" @endif><a href="{{ route('data.general.groups') }}">@lang('survey::lang.general-group')</a></li>
                    @endif

                    @if(auth()->user()->can('survey.view'))
                    <li @if(request()->segment(2) == 'categories') class="active" @endif><a href="{{ route('survey.categories.index') }}">@lang('survey::lang.category')</a></li>
                    @endif

                    @if(auth()->user()->can('survey.send'))
                    <li @if(request()->segment(2) == 'conditional-send') class="active" @endif><a href="{{ route('survey.conditional-send') }}">{{ __('survey::lang.conditional-send') }}</a></li>
                    @endif

                    @if(auth()->user()->can('survey.update'))
                    <li @if(request()->segment(2) == 'settings') class="active" @endif><a href="{{ route('survey.settings.index') }}">{{ __('survey::lang.settings') }}</a></li>
                    @endif

                </ul>

            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</section>