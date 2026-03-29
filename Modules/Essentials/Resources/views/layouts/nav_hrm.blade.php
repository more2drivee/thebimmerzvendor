<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">{{ __('essentials::lang.toggle_navigation') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{action([\Modules\Essentials\Http\Controllers\DashboardController::class, 'hrmDashboard'])}}"><i class="fa fas fa-users"></i> {{__('essentials::lang.hrm')}}</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav d-block">
                    <li @if(request()->segment(2) == 'employees') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'index'])}}"><i class="fas fa-users"></i> @lang('essentials::lang.personnel')</a></li>

                    @if(auth()->user()->can('essentials.crud_all_leave') || auth()->user()->can('essentials.crud_own_leave'))
                        <li @if(request()->segment(2) == 'leave') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\EssentialsLeaveController::class, 'index'])}}">@lang('essentials::lang.leave')</a></li>
                    @endif
                    @if(auth()->user()->can('essentials.crud_all_attendance') || auth()->user()->can('essentials.view_own_attendance'))
                    <li @if(request()->segment(2) == 'attendance') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\AttendanceController::class, 'index'])}}">@lang('essentials::lang.attendance')</a></li>
                    @endif
                    <li @if(request()->segment(2) == 'payroll') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\PayrollController::class, 'index'])}}">@lang('essentials::lang.payroll')</a></li>

                    <li @if(request()->segment(2) == 'warnings') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'warningsIndex'])}}"><i class="fas fa-exclamation-triangle"></i> @lang('essentials::lang.warnings')</a></li>
                    <li @if(request()->segment(2) == 'bonuses') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'bonusesIndex'])}}"><i class="fas fa-gift"></i> @lang('essentials::lang.bonuses')</a></li>
                    <li @if(request()->segment(2) == 'deductions') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'deductionsIndex'])}}"><i class="fas fa-minus-circle"></i> @lang('essentials::lang.deductions')</a></li>
                    <li @if(request()->segment(2) == 'payment-history') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'paymentHistoryIndex'])}}"><i class="fas fa-money-bill"></i> @lang('essentials::lang.payment_history')</a></li>
                    <li @if(request()->segment(2) == 'advances') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'advancesIndex'])}}"><i class="fas fa-hand-holding-usd"></i> @lang('essentials::lang.salary_advance')</a></li>
                    <li @if(request()->segment(2) == 'leaderboard') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'leaderboard'])}}"><i class="fas fa-trophy"></i> @lang('essentials::lang.leaderboard')</a></li>

                    @if(auth()->user()->can('essentials.access_sales_target'))
                        <li @if(request()->segment(1) == 'hrm' && request()->segment(2) == 'sales-target') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\SalesTargetController::class, 'index'])}}">@lang('essentials::lang.sales_target')</a></li>
                    @endif

                    @if(auth()->user()->can('edit_essentials_settings'))
                        <li @if(request()->segment(2) == 'settings') class="active" @endif><a href="{{action([\Modules\Essentials\Http\Controllers\EssentialsSettingsController::class, 'edit'])}}"><i class="fas fa-cog"></i> @lang('business.settings')</a></li>
                    @endif
                </ul>

            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</section>