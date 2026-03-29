@extends('layouts.app')
@section('title', __('essentials::lang.leaderboard'))

@section('content')
@include('essentials::layouts.nav_hrm')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black"><i class="fas fa-trophy tw-text-yellow-500"></i> @lang('essentials::lang.leaderboard')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.employee_of_the_month')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('leaderboard_month', __('essentials::lang.month_year') . ':') !!}
                        <input type="month" id="leaderboard_month" name="month" class="form-control" value="{{ $selected_month }}">
                    </div>
                </div>
                <div class="col-md-3" style="padding-top:25px;">
                    <button type="button" id="load_leaderboard" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full">
                        <i class="fas fa-sync"></i> @lang('essentials::lang.load_calendar')
                    </button>
                </div>
            @endcomponent
        </div>
    </div>

    @if(!empty($leaderboard) && count($leaderboard) > 0)
    {{-- Top 3 Cards --}}
    <div class="row">
        @foreach(array_slice($leaderboard, 0, 3) as $index => $emp)
        @php
            $medals = ['🥇', '🥈', '🥉'];
            $colors = ['tw-from-yellow-400 tw-to-yellow-600', 'tw-from-gray-300 tw-to-gray-500', 'tw-from-orange-400 tw-to-orange-600'];
            $borderColors = ['border-warning', 'border-default', 'border-info'];
        @endphp
        <div class="col-md-4">
            <div class="box box-solid {{ $borderColors[$index] }}">
                <div class="box-header text-center" style="padding-top:20px;">
                    <h1 style="font-size:40px; margin:0;">{{ $medals[$index] }}</h1>
                    <h3 class="tw-font-bold tw-text-gray-800" style="margin-top:10px;">{{ $emp['full_name'] }}</h3>
                    <p class="text-muted">{{ $emp['department_name'] }} - {{ $emp['designation_name'] }}</p>
                </div>
                <div class="box-body">
                    <div class="row text-center">
                        <div class="col-xs-3">
                            <div class="description-block">
                                <h5 class="description-header tw-font-bold tw-text-blue-600">{{ $emp['total_hours'] }}</h5>
                                <span class="description-text text-muted" style="font-size:11px;">@lang('essentials::lang.total_hours')</span>
                            </div>
                        </div>
                        <div class="col-xs-3">
                            <div class="description-block">
                                <h5 class="description-header tw-font-bold tw-text-green-600">{{ $emp['days_present'] }}</h5>
                                <span class="description-text text-muted" style="font-size:11px;">@lang('essentials::lang.days_present')</span>
                            </div>
                        </div>
                        <div class="col-xs-3">
                            <div class="description-block">
                                <h5 class="description-header tw-font-bold tw-text-purple-600">{{ $emp['perfect_days'] }}</h5>
                                <span class="description-text text-muted" style="font-size:11px;">@lang('essentials::lang.perfect_days')</span>
                            </div>
                        </div>
                        <div class="col-xs-3">
                            <div class="description-block">
                                <h5 class="description-header tw-font-bold tw-text-red-600">{{ $emp['overtime_hours'] }}</h5>
                                <span class="description-text text-muted" style="font-size:11px;">@lang('essentials::lang.overtime')</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-center" style="margin-top:10px;">
                        <span class="label label-primary" style="font-size:14px;">@lang('essentials::lang.score'): {{ $emp['score'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Full Ranking Table --}}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['title' => __('essentials::lang.full_ranking')])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width:100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>@lang('essentials::lang.employee')</th>
                                <th>@lang('essentials::lang.department')</th>
                                <th>@lang('essentials::lang.designation')</th>
                                <th>@lang('essentials::lang.total_hours')</th>
                                <th>@lang('essentials::lang.days_present')</th>
                                <th>@lang('essentials::lang.perfect_days')</th>
                                <th>@lang('essentials::lang.overtime')</th>
                                <th>@lang('essentials::lang.score')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaderboard as $index => $emp)
                            <tr @if($index < 3) style="font-weight:bold; background-color:#fffde7;" @endif>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $emp['full_name'] }}</td>
                                <td>{{ $emp['department_name'] }}</td>
                                <td>{{ $emp['designation_name'] }}</td>
                                <td>{{ $emp['total_hours'] }} @lang('essentials::lang.hrs')</td>
                                <td>{{ $emp['days_present'] }}</td>
                                <td>{{ $emp['perfect_days'] }}</td>
                                <td>{{ $emp['overtime_hours'] }} @lang('essentials::lang.hrs')</td>
                                <td><span class="label label-primary">{{ $emp['score'] }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
    @else
    <div class="row">
        <div class="col-md-12 text-center" style="padding:40px;">
            <i class="fas fa-chart-bar fa-3x text-muted"></i>
            <p class="text-muted" style="margin-top:15px;">@lang('essentials::lang.no_data_for_month')</p>
        </div>
    </div>
    @endif
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#load_leaderboard').on('click', function() {
            var month = $('#leaderboard_month').val();
            window.location.href = "{{action([\Modules\Essentials\Http\Controllers\EmployeeController::class, 'leaderboard'])}}" + '?month=' + month;
        });
    });
</script>
@endsection
