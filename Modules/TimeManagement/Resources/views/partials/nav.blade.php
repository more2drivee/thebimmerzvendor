<section class="no-print">
  <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
    <div class="container-fluid">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#timemanagement-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="{{ route('timemanagement.dashboard') }}"><i class="fas fa-clock"></i> @lang('timemanagement::lang.module_name')</a>
      </div>

      <div class="collapse navbar-collapse" id="timemanagement-navbar">
        <ul class="nav navbar-nav d-block">
     
          <li class="{{ request()->routeIs('timemanagement.timesheet') ? 'active' : '' }}">
            <a href="{{ route('timemanagement.index') }}">@lang('timemanagement::lang.nav_timesheet')</a>
          </li>
          <li class="{{ request()->routeIs('timemanagement.assignments') ? 'active' : '' }}">
            <a href="{{ route('timemanagement.assignments') }}">@lang('timemanagement::lang.nav_assignments')</a>
          </li>
          <li class="{{ request()->routeIs('timemanagement.timecontrol') ? 'active' : '' }}">
            <a href="{{ route('timemanagement.timecontrol') }}">@lang('timemanagement::lang.nav_timecontrol')</a>
          </li>
          <li class="{{ request()->routeIs('timemanagement.performance') ? 'active' : '' }}">
            <a href="{{ route('timemanagement.performance') }}">@lang('timemanagement::lang.nav_performance')</a>
          </li>
          <li class="{{ request()->routeIs('timemanagement.time_statistics') ? 'active' : '' }}">
            <a href="{{ route('timemanagement.time_statistics') }}">@lang('timemanagement::lang.nav_time_statistics')</a>
          </li>
          <li class="{{ request()->routeIs('timemanagement.phrases') ? 'active' : '' }}">
            <a href="{{ route('timemanagement.phrases') }}">@lang('timemanagement::lang.nav_phrases')</a>
          </li>
          <li class="{{ request()->routeIs('timemanagement.stop_reasons') ? 'active' : '' }}">
            <a href="{{ route('timemanagement.stop_reasons') }}">@lang('timemanagement::lang.nav_stop_reasons')</a>
          </li>
        
        </ul>
      </div>
    </div>
  </nav>
</section>
