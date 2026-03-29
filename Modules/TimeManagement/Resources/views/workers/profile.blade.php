@extends('layouts.app')
@section('title', __('timemanagement::lang.technician_profile'))
@section('content')
@include('timemanagement::partials.nav')

<section class="content-header">
  <h1>@lang('timemanagement::lang.technician_profile')</h1>
  <small>@lang('timemanagement::lang.overview_and_stats')</small>
</section>
<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box box-solid">
        <div class="box-body">
          <style>
            .wp-card{border:1px solid #e5e7eb;border-radius:12px;padding:16px;background:#fff;margin-bottom:12px}
            .wp-title{font-size:18px;font-weight:700;color:#111827}
            .wp-sub{color:#6b7280}
            .wp-grid{display:flex;flex-wrap:wrap;gap:14px}
            .wp-kpi{flex:1 1 160px;border:1px solid #eef0f2;border-radius:10px;padding:12px;background:#f9fafb}
            .wp-kpi .tt{color:#6b7280;font-size:12px}
            .wp-kpi .vv{font-weight:700;font-size:20px;color:#111827}
            .badge-soft{border-radius:999px;padding:3px 8px;font-size:12px;background:#ecfdf5;color:#065f46}
          </style>

          <div class="wp-card">
            <div class="clearfix" style="margin-bottom:10px;">
              <a href="{{ route('timemanagement.workers') }}" class="btn btn-default btn-sm pull-right">@lang('timemanagement::lang.back')</a>
              <a href="{{ route('timemanagement.workers.jobs', $user->id) }}" class="btn btn-primary btn-sm pull-right" style="margin-right:6px;">@lang('timemanagement::lang.view_job_history')</a>
            </div>

            <div class="wp-title">{{ $user->name }}</div>
            <div class="wp-sub" style="margin-bottom:6px;">
              @if(!empty($specialty))<span class="badge-soft">{{ $specialty }}</span>@endif
              @if($present)
                <span class="label label-success" style="margin-left:6px;">@lang('timemanagement::lang.present')</span>
              @else
                <span class="label label-default" style="margin-left:6px;">@lang('timemanagement::lang.away')</span>
              @endif
              @if(!empty($zone_label))
                <span class="label label-info" style="margin-left:6px;">@lang('timemanagement::lang.assigned_zone', ['zone' => $zone_label])</span>
              @endif
            </div>

            <div class="wp-grid">
              <div class="wp-kpi">
                <div class="tt">@lang('timemanagement::lang.rating')</div>
                <div class="vv">{{ $rating !== null ? number_format($rating, 1) : '—' }}</div>
              </div>
              <div class="wp-kpi">
                <div class="tt">@lang('timemanagement::lang.todays_hours')</div>
                <div class="vv">{{ number_format($today_hours, 1) }}h</div>
              </div>
              <div class="wp-kpi">
                <div class="tt">@lang('timemanagement::lang.avg_job_time')</div>
                <div class="vv">{{ number_format($avg_job_hours, 1) }}h</div>
              </div>
              <div class="wp-kpi">
                <div class="tt">@lang('timemanagement::lang.total_jobs_completed')</div>
                <div class="vv">{{ $total_jobs_completed }}</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>
@endsection
