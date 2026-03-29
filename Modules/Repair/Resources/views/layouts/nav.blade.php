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
                <a class="navbar-brand" href="{{action([\Modules\Repair\Http\Controllers\DashboardController::class, 'index'])}}"><i class="fas fa-wrench"></i> {{__('repair::lang.job_sheets')}}</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav d-block " style="position: relative !important;">
                    @if(auth()->user()->can('job_sheet.create') || auth()->user()->can('job_sheet.view_assigned') || auth()->user()->can('job_sheet.view_all'))
                        <li @if(request()->segment(2) == 'job-sheet' && empty(request()->segment(3))) class="active" @endif>
                            <a href="{{action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'index'])}}">
                                @lang('repair::lang.job_sheets')
                            </a>
                        </li>
                    @endif

                    @can('job_sheet.create')
                        <li @if(request()->segment(2) == 'job-sheet' && request()->segment(3) == 'create') class="active" @endif>
                            <a href="{{action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'create'])}}">
                                @lang('repair::lang.add_job_sheet')
                            </a>
                        </li>
                    @endcan

                    @if(auth()->user()->can('repair.view') || auth()->user()->can('repair.view_own'))
                        <li @if(request()->segment(2) == 'repair' && empty(request()->segment(3))) class="active" @endif><a href="{{action([\Modules\Repair\Http\Controllers\RepairController::class, 'index'])}}">@lang('repair::lang.list_invoices')</a></li>
                    @endif

                   

            

            
                        <li @if(request()->segment(1) == 'repair' && request()->segment(2) == 'repair-settings') class="active" @endif>
                            <a href="{{action([\Modules\Repair\Http\Controllers\RepairSettingsController::class, 'index'])}}">@lang('messages.settings')</a>
                        </li>
           

                    @can('admin')
                        <li @if(request()->segment(2) == 'recycle-bin') class="active" @endif>
                            <a href="{{route('recycle-bin.index')}}">
                                <i class="fas fa-trash"></i> @lang('repair::lang.recycle_bin')
                            </a>
                        </li>
                    @endcan
                </ul>

            </div>
        </div>
    </nav>
</section>