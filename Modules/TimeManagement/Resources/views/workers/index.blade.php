@extends('layouts.app')
@section('title', __('timemanagement::lang.workers'))
@section('content')
@include('timemanagement::partials.nav')

<section class="content-header">
  <h1>@lang('timemanagement::lang.technicians')</h1>
  <small>@lang('timemanagement::lang.technician_roster')</small>
</section>
<section class="content">
  <div class="row">

    <div class="col-md-12">
      <div class="box box-solid">
        <div class="box-body">
          @include('timemanagement::partials.filters', ['action' => route('timemanagement.workers')])
          <hr/>
          <div class="clearfix" style="margin-bottom:8px;">
            <form method="GET" class="form-inline pull-right" action="{{ route('timemanagement.workers') }}">
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
                  <th>@lang('timemanagement::lang.technician')</th>
                  <th>@lang('timemanagement::lang.status')</th>
                  <th>@lang('timemanagement::lang.assigned')</th>
                  <th>@lang('timemanagement::lang.present')</th>
                  <th style="width: 120px;">@lang('timemanagement::lang.actions')</th>
                </tr>
              </thead>
              <tbody>
                @forelse(($workersPage ?? collect()) as $w)
                  <tr>
                    <td>{{ $w->user_name }}</td>
                    <td>
                      @php 
                        $status = $w->status === 'Active' ? __('timemanagement::lang.active') : 
                                ($w->status === 'Clocked-in' ? __('timemanagement::lang.clocked_in') : 
                                ($w->status === 'Inactive' ? __('timemanagement::lang.inactive') : $w->status));
                        $color = $w->status === 'Active' ? 'green' : ($w->status === 'Clocked-in' ? 'orange' : 'gray');
                      @endphp
                      <span class="label" style="background: {{ $color }};">{{ $status }}</span>
                    </td>
                    <td>{{ $w->assigned ? __('timemanagement::lang.yes') : __('timemanagement::lang.no') }}</td>
                    <td>{{ $w->present ? __('timemanagement::lang.yes') : __('timemanagement::lang.no') }}</td>
                    <td>
                      <a class="btn btn-primary btn-xs" href="{{ route('timemanagement.workers.profile', ['user' => $w->user_id]) }}">@lang('timemanagement::lang.profile')</a>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center">@lang('timemanagement::lang.no_workers_found')</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if(isset($workersPage))
            <div class="text-center">
              {{ $workersPage->appends(request()->query())->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
