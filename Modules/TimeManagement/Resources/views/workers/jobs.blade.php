@extends('layouts.app')
@section('title', __('timemanagement::lang.job_history'))
@section('content')
@include('timemanagement::partials.nav')

<section class="content-header">
  <h1>@lang('timemanagement::lang.job_history')</h1>
  <small>@lang('timemanagement::lang.all_job_sheets')</small>
</section>
<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box box-solid">
        <div class="box-body">
          @include('timemanagement::partials.filters', ['action' => route('timemanagement.workers.jobs', ['user' => $user_id])])
          <hr/>

          <div class="clearfix" style="margin-bottom:8px;">
            <form method="GET" class="form-inline pull-right" action="{{ route('timemanagement.workers.jobs', ['user' => $user_id]) }}">
              <label style="font-weight:normal; margin-right:6px;">@lang('timemanagement::lang.per_page')</label>
              @php $pp = request()->get('per_page', 10); @endphp
              <select name="per_page" class="form-control input-sm" onchange="this.form.submit()" style="display:inline-block; width:auto;">
                <option value="5" {{ $pp==5?'selected':'' }}>5</option>
                <option value="10" {{ $pp==10?'selected':'' }}>10</option>
                <option value="15" {{ $pp==15?'selected':'' }}>15</option>
                <option value="20" {{ $pp==20?'selected':'' }}>20</option>
              </select>
              <input type="hidden" name="workshop_id" value="{{ request('workshop_id') }}"/>
              <input type="hidden" name="location_id" value="{{ request('location_id') }}"/>
              <input type="hidden" name="start_date" value="{{ request('start_date') }}"/>
              <input type="hidden" name="end_date" value="{{ request('end_date') }}"/>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>@lang('timemanagement::lang.job_no')</th>
                  <th>@lang('timemanagement::lang.status')</th>
                  <th>@lang('timemanagement::lang.workshop')</th>
                  <th>@lang('timemanagement::lang.entry')</th>
                  <th>@lang('timemanagement::lang.start')</th>
                  <th>@lang('timemanagement::lang.due')</th>
                  <th>@lang('timemanagement::lang.delivered')</th>
                  <th>@lang('timemanagement::lang.duration')</th>
                </tr>
              </thead>
              <tbody>
                @forelse(($rows ?? []) as $r)
                  <tr>
                    <td>{{ $r->job_sheet_no }}</td>
                    <td><span class="label" style="background: {{ $r->status_color }};">{{ $r->status_name }}</span></td>
                    <td>{{ $r->workshop_name ?? '—' }}</td>
                    <td>{{ $r->entry_date ?? '—' }}</td>
                    <td>{{ $r->start_date ?? '—' }}</td>
                    <td>{{ $r->due_date ?? '—' }}</td>
                    <td>{{ $r->delivery_date ?? '—' }}</td>
                    <td>
                      @php
                        $start = $r->start_date ?? $r->entry_date;
                        $end = $r->delivery_date;
                      @endphp
                      @if($start && $end)
                        {{ \Carbon\Carbon::parse($start)->diffForHumans(\Carbon\Carbon::parse($end), true) }}
                      @else
                        —
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="8" class="text-center">@lang('timemanagement::lang.no_jobs_found')</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if(isset($paginator))
            <div class="text-center">
              {{ $paginator->appends(request()->query())->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
