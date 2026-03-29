@extends('layouts.app')
@section('title', 'Time Management')
@section('content')
@include('timemanagement::partials.nav')
<section class="content-header">
  <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Time Management</h1>
</section>
<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box box-solid">
        <div class="box-body">
          @include('timemanagement::partials.filters')
          <hr/>
          <div class="row text-center">
            <div class="col-sm-3"><h3>Present</h3><div class="tw-text-3xl">{{ $present_today }}</div></div>
            <div class="col-sm-3"><h3>Late Arrivals</h3><div class="tw-text-3xl">{{ $late_arrivals }}</div></div>
            <div class="col-sm-3"><h3>Productive Hours</h3><div class="tw-text-3xl">{{ number_format($productive_hours, 1) }}h</div></div>
            <div class="col-sm-3"><h3>Efficiency Rate</h3><div class="tw-text-3xl">{{ number_format($efficiency_rate, 0) }}%</div></div>
          </div>
          <hr/>
          <h3>Active Jobs & Timers</h3>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>@lang('timemanagement::lang.dashboard_job_no')</th>
                  <th>@lang('timemanagement::lang.dashboard_status')</th>
                  <th>@lang('timemanagement::lang.dashboard_technicians')</th>
                  <th>@lang('timemanagement::lang.dashboard_workshop')</th>
                  <th>@lang('timemanagement::lang.dashboard_entry')</th>
                  <th>@lang('timemanagement::lang.dashboard_start')</th>
                </tr>
              </thead>
              <tbody>
                @forelse($active_jobs as $job)
                  <tr>
                    <td>{{ $job->job_sheet_no }}</td>
                    <td><span class="label" style="background: {{ $job->status_color }};">{{ $job->status_name }}</span></td>
                    <td>
                      @if(!empty($job->technicians))
                        {{ implode(', ', $job->technicians) }}
                      @else
                        —
                      @endif
                    </td>
                    <td>{{ $job->workshop_name ?? '—' }}</td>
                    <td>{{ $job->entry_date }}</td>
                    <td>{{ $job->start_date ?? '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center">No active jobs.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
