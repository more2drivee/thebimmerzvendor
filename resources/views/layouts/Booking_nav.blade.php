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
                <a class="navbar-brand" href="{{ route('booking.index') }}"><i class="fa fa-calendar"></i> Bookings</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav d-block " style="position: relative !important;">
             
                        <li @if(request()->segment(1) == 'bookings' && empty(request()->segment(2))) class="active" @endif>
                            <a href="{{ route('booking.index') }}">
                                <i class="fa fa-calendar"></i> Bookings
                            </a>
                        </li>
           

                 
                        <li @if(request()->segment(1) == 'job-estimator' && empty(request()->segment(2))) class="active" @endif>
                            <a href="{{ route('job_estimator.index') }}">
                                <i class="fa fa-list-alt"></i> Estimated Jobs
                            </a>
                        </li>
                

                </ul>

            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</section>