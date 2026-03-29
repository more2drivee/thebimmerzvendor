@extends('layouts.app')
@section('title', 'Time Management - Time Control')
@section('content')
@include('timemanagement::partials.nav')
<section class="content-header">
  <h1>Time Control</h1>
  <small>Monitor and manage live job timers</small>
</section>
<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box box-solid">
        <div class="box-body">
          <style>
            .tc-kpi .kpi-card{border:1px solid #e5e7eb;border-radius:12px;padding:16px;background:#f9fafb;display:flex;align-items:center;justify-content:space-between;margin-bottom:15px}
            .tc-kpi .kpi-title{color:#6b7280;margin:0 0 6px 0;font-weight:600}
            .tc-kpi .kpi-value{font-size:28px;font-weight:700;color:#111827}
            .tc-list .job-card{border:1px solid #e5e7eb;border-radius:12px;padding:16px;background:#fff;display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
            .tc-list .left{flex:1}
            .tc-list .right{display:flex;align-items:center;gap:12px}
            .badge-soft{border-radius:999px;padding:3px 8px;font-size:12px;background:#ecfdf5;color:#065f46}
            .status-pill{border-radius:999px;padding:3px 8px;font-size:12px;background:#eef2ff;color:#111}
            .elapsed{font-weight:700;color:#059669;font-size:20px}
            .muted{color:#6b7280}
            .tc-actions .btn{border-radius:8px}
            .tc-section-title{font-size:16px;font-weight:700;color:#111827;margin:10px 0 14px}
          </style>

          <div class="clearfix">
            <button type="button" class="btn btn-default pull-right" onclick="(function(){var el=document.getElementById('tm-filters'); if(el){ el.scrollIntoView({behavior:'smooth'}); }})();"><i class="fa fa-filter"></i> Filter Jobs</button>
            <div class="clearfix" style="margin-top:8px;">
              <div class="pull-left">
                <button id="tc-prev" type="button" class="btn btn-default btn-sm">Prev</button>
                <button id="tc-next" type="button" class="btn btn-default btn-sm">Next</button>
              </div>
            </div>
          </div>

          <div id="tm-filters">@include('timemanagement::partials.filters', ['action' => route('timemanagement.timecontrol')])</div>
          <hr/>

          @php
            $active_count = count($timers ?? []);
            $total_seconds = 0; $techs = collect();
            foreach (($timers ?? []) as $t) {
              $total_seconds += ($t->elapsed_seconds ?? 0);
              foreach (json_decode($t->service_staff ?? '[]', true) ?: [] as $sid) { $techs->push($sid); }
            }
            $unique_techs = $techs->unique()->count();
            $total_hm = sprintf('%dh %02dm', floor($total_seconds/3600), floor(($total_seconds%3600)/60));
          @endphp

          <div id="tc-root" data-list-url="{{ route('timemanagement.timecontrol.list') }}">
            <div class="row tc-kpi">
              <div class="col-sm-4">
                <div class="kpi-card">
                  <div>
                    <div class="kpi-title">Active Timers</div>
                    <div class="kpi-value" id="kpi-active">{{ $active_count }}</div>
                  </div>
                  <i class="fa fa-clock-o text-success" aria-hidden="true"></i>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="kpi-card">
                  <div>
                    <div class="kpi-title">Total Active Time</div>
                    <div class="kpi-value" id="kpi-total">{{ $total_hm }}</div>
                  </div>
                  <i class="fa fa-line-chart text-primary" aria-hidden="true"></i>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="kpi-card">
                  <div>
                    <div class="kpi-title">Technicians Active</div>
                    <div class="kpi-value" id="kpi-techs">{{ $unique_techs }}</div>
                  </div>
                  <i class="fa fa-hourglass-half text-info" aria-hidden="true"></i>
                </div>
              </div>
            </div>

            <div class="tc-section-title">Live Job Timers</div>
            <div class="clearfix" style="margin-bottom:8px;">
              <div class="pull-right">
                <label style="font-weight:normal; margin-right:6px;">Per page</label>
                <select id="tc-per-page" class="form-control input-sm" style="display:inline-block; width:auto;">
                  <option value="5">5</option>
                  <option value="10" selected>10</option>
                  <option value="15">15</option>
                  <option value="20">20</option>
                </select>
              </div>
            </div>
            <div id="tc-jobs" class="tc-list">
              @forelse(($timers ?? []) as $job)
                <div class="job-card">
                  <div class="left">
                    <div class="muted" style="margin-bottom:6px;">
                      <span class="status-pill" style="background: {{ $job->status_color }}; color:#fff;">{{ $job->status_name }}</span>
                      <span class="badge-soft" style="margin-left:6px;">JS-{{ $job->job_sheet_no }}</span>
                      @if(!empty($job->workshop_id))
                        <span class="muted" style="margin-left:6px;">L-{{ $job->workshop_id }}</span>
                      @endif
                    </div>
                    <div style="font-size:16px;font-weight:700;color:#111827;">{{ $job->workshop_name ?? 'Workshop' }}</div>
                    <div class="muted">Tech: 
                      @if(!empty($job->technicians))
                        <strong>{{ implode(', ', $job->technicians) }}</strong>
                      @else
                        —
                      @endif
                    </div>
                  </div>
                  <div class="right">
                    <div class="elapsed">{{ gmdate('H:i:s', $job->elapsed_seconds ?? 0) }}</div>
                    <div class="tc-actions">
                      <button class="btn btn-warning btn-sm" title="Pause" @if(($job->timer_status ?? 'active') !== 'active') disabled @endif onclick="pauseTimer({{ $job->timer_id ?? 'null' }})"><i class="fa fa-pause"></i></button>
                      <button class="btn btn-success btn-sm" title="Resume" @if(($job->timer_status ?? 'paused') !== 'paused') disabled @endif onclick="resumeTimer({{ $job->timer_id ?? 'null' }})"><i class="fa fa-play"></i></button>
                      <button class="btn btn-danger btn-sm" title="Stop" @if(($job->timer_status ?? 'completed') === 'completed') disabled @endif onclick="completeTimer({{ $job->timer_id ?? 'null' }})"><i class="fa fa-stop"></i></button>
                    </div>
                  </div>
                </div>
              @empty
                <div class="text-center muted" style="padding:20px;">No active timers.</div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<script>
(function(){
  function qs(sel){ return document.querySelector(sel); }
  function qsa(sel){ return Array.prototype.slice.call(document.querySelectorAll(sel)); }
  function formatHM(sec){ sec = parseInt(sec||0,10); var h = Math.floor(sec/3600); var m = Math.floor((sec%3600)/60); return h + 'h ' + (m<10?('0'+m):m) + 'm'; }

  function buildJobsHtml(items){
    if(!items || !items.length){ return '<div class="text-center muted" style="padding:20px;">No active timers.</div>'; }
    return items.map(function(j){
      var statusColor = j.status_color || '#999';
      var techs = (j.technicians||[]).join(', ');
      var wsid = j.workshop_id ? ('<span class="muted" style="margin-left:6px;">L-'+j.workshop_id+'</span>') : '';
      return '<div class="job-card'>
        + '<div class="left'>
          + '<div class="muted" style="margin-bottom:6px;">'
          + '<span class="status-pill" style="background:'+statusColor+';color:#fff;">'+ (j.status_name||'') +'</span>'
          + '<span class="badge-soft" style="margin-left:6px;">JS-'+ (j.job_sheet_no||'') +'</span>'
          + wsid
          + '<div style="font-size:16px;font-weight:700;color:#111827;">'+ (j.workshop_name||'Workshop') +'</div>'
          + '<div class="muted">Tech: '+ (techs? ('<strong>'+techs+'</strong>') : '—') +'</div>'
          + '</div>'
        + '</div>'
        + '<div class="right'>
          + '<div class="elapsed'>'+ (j.elapsed_seconds!=null ? new Date(j.elapsed_seconds*1000).toISOString().substr(11,8) : '00:00:00') +'</div>'
          + '<button class="btn btn-warning btn-sm" title="Pause" ' + (j.timer_status !== 'active' ? 'disabled' : '') + ' onclick="pauseTimer(' + (j.timer_id !== null ? j.timer_id : 'null') + ')"><i class="fa fa-pause"></i></button>'
          + '<button class="btn btn-success btn-sm" title="Resume" ' + (j.timer_status !== 'paused' ? 'disabled' : '') + ' onclick="resumeTimer(' + (j.timer_id !== null ? j.timer_id : 'null') + ')"><i class="fa fa-play"></i></button>'
          + '<button class="btn btn-danger btn-sm" title="Stop" ' + (j.timer_status === 'completed' ? 'disabled' : '') + ' onclick="completeTimer(' + (j.timer_id !== null ? j.timer_id : 'null') + ')"><i class="fa fa-stop"></i></button>'
          + '</div>'
        + '</div>'
      + '</div>';
    }).join('');
  }

  var tcPage = 1, tcPerPage = 10;

  function startTimer(jobId) {
    fetch("{{ route('timemanagement.timecontrol') }}/start", {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
      body: JSON.stringify({job_sheet_id: jobId})
    }).then(r=>r.json()).then(data=>{ if(data.success) refresh(); });
  }

  function pauseTimer(timerId) {
    if(!timerId) return;
    fetch("{{ route('timemanagement.timecontrol') }}/pause", {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
      body: JSON.stringify({timer_id: timerId})
    }).then(r=>r.json()).then(data=>{ if(data.success) refresh(); });
  }

  function resumeTimer(timerId) {
    if(!timerId) return;
    fetch("{{ route('timemanagement.timecontrol') }}/resume", {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
      body: JSON.stringify({timer_id: timerId})
    }).then(r=>r.json()).then(data=>{ if(data.success) refresh(); });
  }

  function completeTimer(timerId) {
    if(!timerId) return;
    fetch("{{ route('timemanagement.timecontrol') }}/complete", {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
      body: JSON.stringify({timer_id: timerId})
    }).then(r=>r.json()).then(data=>{ if(data.success) refresh(); });
  }

  function refresh(){
    var root = qs('#tc-root'); if(!root) return; var url = root.getAttribute('data-list-url');
    var params = new URLSearchParams();
    params.set('page', tcPage);
    params.set('per_page', tcPerPage);
    var full = url + (params.toString()? ('?'+params.toString()):'');
    fetch(full, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
      .then(r=>r.json())
      .then(data=>{
        if(data && data.stats){
          var k1 = qs('#kpi-active'), k2 = qs('#kpi-total'), k3 = qs('#kpi-techs');
          if(k1) k1.textContent = data.stats.active_count||0;
          if(k2) k2.textContent = formatHM(data.stats.total_seconds||0);
          if(k3) k3.textContent = data.stats.unique_techs||0;
        }
        if(data && Array.isArray(data.timers)){
          var list = qs('#tc-jobs'); if(list){ list.innerHTML = buildJobsHtml(data.timers); }
        }
        var pg = data && data.pagination ? data.pagination : null;
        var pr = qs('#tc-prev'), nx = qs('#tc-next');
        if(pr) pr.disabled = !pg || pg.page <= 1;
        if(nx) nx.disabled = !pg || !pg.has_more;
      })
      .catch(()=>{});
  }

  setInterval(refresh, 30000);

  var pr = qs('#tc-prev'), nx = qs('#tc-next');
  if(pr){ pr.addEventListener('click', function(){ if(tcPage>1){ tcPage--; refresh(); } }); }
  if(nx){ nx.addEventListener('click', function(){ tcPage++; refresh(); }); }
  var pp = qs('#tc-per-page'); if(pp){ pp.addEventListener('change', function(){ tcPerPage = parseInt(this.value,10)||10; tcPage = 1; refresh(); }); }

  setTimeout(refresh, 200);
})();
</script>
@endsection
