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
            .tc-device-summary{margin-top:4px}
            .tc-device-name{font-weight:600;color:#111827;font-size:14px}
            .tc-device-meta{margin-top:4px;display:flex;flex-wrap:wrap;gap:6px}
            .tc-device-meta .device-chip{display:inline-block;padding:3px 10px;border-radius:999px;background:#eef2ff;color:#1f2937;font-size:12px;font-weight:500}
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
                    @php
                      $hasDeviceDetails = !empty($job->device) && (
                        !empty($job->device->name) || !empty($job->device->model) ||
                        !empty($job->device->plate_number) || !empty($job->device->chassis_number) ||
                        !empty($job->device->color) || !empty($job->device->manufacturing_year) || !empty($job->device->car_type)
                      );
                    @endphp
                    @if($hasDeviceDetails)
                      <div class="tc-device-summary">
                        @if(!empty($job->device->name) || !empty($job->device->model))
                          <div class="tc-device-name">
                            {{ trim(($job->device->name ?? '') . ' ' . ($job->device->model ?? '')) }}
                          </div>
                        @endif
                        <div class="tc-device-meta">
                          @if(!empty($job->device->plate_number))
                            <span class="device-chip">Plate: {{ $job->device->plate_number }}</span>
                          @endif
                          @if(!empty($job->device->chassis_number))
                            <span class="device-chip">VIN: {{ $job->device->chassis_number }}</span>
                          @endif
                          @if(!empty($job->device->color))
                            <span class="device-chip">Color: {{ $job->device->color }}</span>
                          @endif
                          @if(!empty($job->device->manufacturing_year))
                            <span class="device-chip">Year: {{ $job->device->manufacturing_year }}</span>
                          @endif
                          @if(!empty($job->device->car_type))
                            <span class="device-chip">Type: {{ $job->device->car_type }}</span>
                          @endif
                        </div>
                      </div>
                    @endif
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
                    <div class="tc-actions" style="display:flex;gap:6px;">
                      <button class="btn btn-success btn-sm" title="Play all" onclick="playAll({{ $job->id ?? 'null' }})"><i class="fa fa-play"></i></button>
                      <button class="btn btn-warning btn-sm" title="Pause all" onclick="pauseAll({{ $job->id ?? 'null' }})"><i class="fa fa-pause"></i></button>
                      <button class="btn btn-danger btn-sm" title="Complete all" onclick="completeAll({{ $job->id ?? 'null' }})"><i class="fa fa-stop"></i></button>
                    </div>
                  </div>
                </div>

                {{-- Service Groups Display (if available) --}}
                @if(!empty($job->service_groups) && count($job->service_groups) > 0)
                  @foreach($job->service_groups as $group)
                    <div class="job-card" style="margin-left:24px;background:#f9fafb;">
                      <div class="left">
                        <div class="muted" style="margin-bottom:4px;">
                          <span class="badge-soft" style="background:#dbeafe;color:#1e40af;">{{ $group->service_name ?? 'Service' }}</span>
                          @if(!empty($group->workshop_name))
                            <span class="muted" style="margin-left:6px;">{{ $group->workshop_name }}</span>
                          @endif
                          @if(!empty($group->service_hours))
                            <span class="muted" style="margin-left:6px;">Hours: {{ $group->service_hours }}</span>
                          @endif
                        </div>
                        <div class="muted" style="font-size:12px;">Tech: 
                          @if(!empty($group->technicians))
                            <strong>{{ implode(', ', $group->technicians) }}</strong>
                          @else
                            —
                          @endif
                        </div>
                      </div>
                      <div class="right"></div>
                    </div>

                    {{-- Timers for this service group --}}
                    @if(!empty($group->timers))
                      @foreach($group->timers as $timer)
                        <div class="job-card" style="margin-left:48px;">
                          <div class="left">
                            <div style="font-size:14px;font-weight:600;color:#374151;">{{ $timer->user_name ?? ('User #'.$timer->user_id) }}</div>
                          </div>
                          <div class="right">
                            <div class="elapsed" id="elapsed-{{ $job->id }}-{{ $timer->user_id }}-g-{{ $group->workshop_id }}" data-elapsed="{{ (int)($timer->elapsed_seconds ?? 0) }}" data-active="{{ (($timer->timer_status ?? '') === 'active') ? '1' : '0' }}">{{ gmdate('H:i:s', (int)($timer->elapsed_seconds ?? 0)) }}</div>
                            <div class="tc-actions">
                              @if(($timer->timer_status ?? null) === 'active')
                                <button class="btn btn-warning btn-sm" title="Pause" onclick="pauseTimer({{ $timer->timer_id ?? 'null' }})"><i class="fa fa-pause"></i></button>
                                <button class="btn btn-danger btn-sm" title="Stop" onclick="completeTimer({{ $timer->timer_id ?? 'null' }})"><i class="fa fa-stop"></i></button>
                              @elseif(($timer->timer_status ?? null) === 'paused')
                                <button class="btn btn-success btn-sm" title="Resume" onclick="resumeTimer({{ $timer->timer_id ?? 'null' }})"><i class="fa fa-play"></i></button>
                                <button class="btn btn-danger btn-sm" title="Stop" onclick="completeTimer({{ $timer->timer_id ?? 'null' }})"><i class="fa fa-stop"></i></button>
                              @else
                                <button class="btn btn-success btn-sm" title="Start" onclick="startTimer({{ $job->id ?? 'null' }}, {{ $timer->user_id ?? 'null' }})"><i class="fa fa-play"></i></button>
                              @endif
                            </div>
                          </div>
                        </div>
                      @endforeach
                    @endif
                  @endforeach
                @elseif(!empty($job->workers) && count($job->workers) > 0)
                  {{-- Fallback to Workers Display (when no service groups) --}}
                  @foreach($job->workers as $w)
                    <div class="job-card" style="margin-left:24px;">
                      <div class="left">
                        <div style="font-size:14px;font-weight:600;color:#374151;">{{ $w->user_name ?? ('User #'.$w->user_id) }}</div>
                      </div>
                      <div class="right">
                        <div class="elapsed" id="elapsed-{{ $job->id }}-{{ $w->user_id }}" data-elapsed="{{ (int)($w->elapsed_seconds ?? 0) }}" data-active="{{ (($w->timer_status ?? '') === 'active') ? '1' : '0' }}">{{ gmdate('H:i:s', (int)($w->elapsed_seconds ?? 0)) }}</div>
                        <div class="tc-actions">
                          @if(($w->timer_status ?? null) === 'active')
                            <button class="btn btn-warning btn-sm" title="Pause" onclick="pauseTimer({{ $w->timer_id ?? 'null' }})"><i class="fa fa-pause"></i></button>
                            <button class="btn btn-danger btn-sm" title="Stop" onclick="completeTimer({{ $w->timer_id ?? 'null' }})"><i class="fa fa-stop"></i></button>
                          @elseif(($w->timer_status ?? null) === 'paused')
                            <button class="btn btn-success btn-sm" title="Resume" onclick="resumeTimer({{ $w->timer_id ?? 'null' }})"><i class="fa fa-play"></i></button>
                            <button class="btn btn-danger btn-sm" title="Stop" onclick="completeTimer({{ $w->timer_id ?? 'null' }})"><i class="fa fa-stop"></i></button>
                          @else
                            <button class="btn btn-success btn-sm" title="Start" onclick="startTimer({{ $job->id ?? 'null' }}, {{ $w->user_id ?? 'null' }})"><i class="fa fa-play"></i></button>
                          @endif
                        </div>
                      </div>
                    </div>
                  @endforeach
                @endif
              @empty
                <div class="text-center muted" style="padding:20px;">No active timers.</div>
              @endforelse
            </div>
          </div>

          <script>
          (function(){
            function qs(sel){ return document.querySelector(sel); }
            function formatHM(sec){ sec = parseInt(sec||0,10); var h = Math.floor(sec/3600); var m = Math.floor((sec%3600)/60); return h + 'h ' + (m<10?('0'+m):m) + 'm'; }

            var tcPage = 1, tcPerPage = 10;
            var root = qs('#tc-root');
            var listUrl = root ? root.getAttribute('data-list-url') : '';

            function buildJobsHtml(items){
              if(!items || !items.length){ return '<div class="text-center muted" style="padding:20px;">No active timers.</div>'; }
              return items.map(function(j){
                var statusColor = j.status_color || '#999';
                var techs = (j.technicians||[]).join(', ');
                
                // Build workers under job
                var workersHtml = '';
                (j.workers||[]).forEach(function(w){
                  var wid = 'elapsed-' + j.id + '-' + w.user_id;
                  var wElapsed = (w.elapsed_seconds!=null ? w.elapsed_seconds : 0);
                  var activeAttr = (w.timer_status === 'active') ? '1' : '0';
                  var wa = '';
                  if (w.timer_status === 'active') {
                    wa += '<button class="btn btn-warning btn-sm" title="Pause" onclick="pauseTimer(' + (w.timer_id !== null ? w.timer_id : 'null') + ')"><i class="fa fa-pause"></i></button> ';
                    wa += '<button class="btn btn-danger btn-sm" title="Stop" onclick="completeTimer(' + (w.timer_id !== null ? w.timer_id : 'null') + ')"><i class="fa fa-stop"></i></button>';
                  } else if (w.timer_status === 'paused') {
                    wa += '<button class="btn btn-success btn-sm" title="Resume" onclick="resumeTimer(' + (w.timer_id !== null ? w.timer_id : 'null') + ')"><i class="fa fa-play"></i></button> ';
                    wa += '<button class="btn btn-danger btn-sm" title="Stop" onclick="completeTimer(' + (w.timer_id !== null ? w.timer_id : 'null') + ')"><i class="fa fa-stop"></i></button>';
                  } else {
                    wa += '<button class="btn btn-success btn-sm" title="Start" onclick="startTimer(' + (j.id != null ? j.id : 'null') + ', ' + (w.user_id != null ? w.user_id : 'null') + ')"><i class="fa fa-play"></i></button>';
                  }
                  workersHtml += '<div class="job-card" style="margin-left:24px;">'
                    + '<div class="left">'
                    +   '<div style="font-size:14px;font-weight:600;color:#374151;">'+ (w.user_name || ('User #'+w.user_id)) +'</div>'
                    + '</div>'
                    + '<div class="right">'
                    +   '<div class="elapsed" id="'+wid+'" data-elapsed="'+wElapsed+'" data-active="'+activeAttr+'">'+ new Date(wElapsed*1000).toISOString().substr(11,8) +'</div>'
                    +   '<div class="tc-actions">'+ wa +'</div>'
                    + '</div>'
                    + '</div>';
                });

                // Build service groups under job
                var groupsHtml = '';
                (j.service_groups || []).forEach(function(g){
                  var techs2 = (g.technicians||[]).join(', ');
                  var hoursLabel = (g.service_hours!=null ? String(g.service_hours) : '');
                  
                  var gTimersHtml = '';
                  (g.timers||[]).forEach(function(gt){
                    var gid = 'elapsed-' + j.id + '-' + (gt.user_id!=null?gt.user_id:'') + '-g-' + (g.workshop_id!=null?g.workshop_id:'');
                    var gElapsed = (gt.elapsed_seconds!=null ? gt.elapsed_seconds : 0);
                    var gActiveAttr = (gt.timer_status === 'active') ? '1' : '0';
                    var ga = '';
                    if (gt.timer_status === 'active') {
                      ga += '<button class="btn btn-warning btn-sm" title="Pause" onclick="pauseTimer(' + (gt.timer_id !== null ? gt.timer_id : 'null') + ')"><i class="fa fa-pause"></i></button> ';
                      ga += '<button class="btn btn-danger btn-sm" title="Stop" onclick="completeTimer(' + (gt.timer_id !== null ? gt.timer_id : 'null') + ')"><i class="fa fa-stop"></i></button>';
                    } else if (gt.timer_status === 'paused') {
                      ga += '<button class="btn btn-success btn-sm" title="Resume" onclick="resumeTimer(' + (gt.timer_id !== null ? gt.timer_id : 'null') + ')"><i class="fa fa-play"></i></button> ';
                      ga += '<button class="btn btn-danger btn-sm" title="Stop" onclick="completeTimer(' + (gt.timer_id !== null ? gt.timer_id : 'null') + ')"><i class="fa fa-stop"></i></button>';
                    } else {
                      ga += '<button class="btn btn-success btn-sm" title="Start" onclick="startTimer(' + (j.id != null ? j.id : 'null') + ', ' + (gt.user_id != null ? gt.user_id : 'null') + ')"><i class="fa fa-play"></i></button>';
                    }
                    gTimersHtml += '<div class="job-card" style="margin-left:36px;">'
                      + '<div class="left">'
                      +   '<div style="font-size:14px;font-weight:600;color:#374151;">'+ (gt.user_name || ('User #'+gt.user_id)) +'</div>'
                      + '</div>'
                      + '<div class="right">'
                      +   '<div class="elapsed" id="'+gid+'" data-elapsed="'+gElapsed+'" data-active="'+gActiveAttr+'">'+ new Date(gElapsed*1000).toISOString().substr(11,8) +'</div>'
                      +   '<div class="tc-actions">'+ ga +'</div>'
                      + '</div>'
                      + '</div>';
                  });

                  groupsHtml += '<div class="job-card" style="margin-left:24px;">'
                    + '<div class="left">'
                    +   '<div class="muted" style="margin-bottom:4px;">'
                    +     '<span class="badge-soft">'+ (g.service_name || 'Service') +'</span>'
                    +     (g.workshop_name ? '<span class="muted" style="margin-left:6px;">'+ g.workshop_name +'</span>' : '')
                    +     (hoursLabel ? '<span class="muted" style="margin-left:6px;">Hours: '+ hoursLabel +'</span>' : '')
                    +   '</div>'
                    +   '<div class="muted">Tech: '+ (techs2? ('<strong>'+techs2+'</strong>') : '—') +'</div>'
                    + '</div>'
                    + '<div class="right"></div>'
                    + '</div>'
                    + gTimersHtml;
                });

                var device = j.device || {};
                var deviceMeta = '';
                var hasDevice = device && (device.name || device.model || device.plate_number || device.chassis_number || device.color || device.manufacturing_year || device.car_type);
                if (hasDevice) {
                  deviceMeta += '<div class="tc-device-summary">';
                  if (device.name || device.model) {
                    var title = [device.name || '', device.model || ''].join(' ').trim();
                    if (title) {
                      deviceMeta += '<div class="tc-device-name">' + title + '</div>';
                    }
                  }
                  var chips = [];
                  if (device.plate_number) chips.push('<span class="device-chip">Plate: ' + device.plate_number + '</span>');
                  if (device.chassis_number) chips.push('<span class="device-chip">VIN: ' + device.chassis_number + '</span>');
                  if (device.color) chips.push('<span class="device-chip">Color: ' + device.color + '</span>');
                  if (device.manufacturing_year) chips.push('<span class="device-chip">Year: ' + device.manufacturing_year + '</span>');
                  if (device.car_type) chips.push('<span class="device-chip">Type: ' + device.car_type + '</span>');
                  if (chips.length) {
                    deviceMeta += '<div class="tc-device-meta">' + chips.join('') + '</div>';
                  }
                  deviceMeta += '</div>';
                }

                return '<div class="job-card">'
                  + '<div class="left">'
                    + '<div class="muted" style="margin-bottom:6px;">'
                    +   '<span class="status-pill" style="background:'+statusColor+';color:#fff;">'+ (j.status_name||'') +'</span>'
                    +   '<span class="badge-soft" style="margin-left:6px;">JS-'+ (j.job_sheet_no||'') +'</span>'
                  + '</div>'
                  + '<div style="font-size:16px;font-weight:700;color:#111827;">'+ (j.workshop_name||'Workshop') +'</div>'
                  + deviceMeta
                  + '<div class="muted">Tech: '+ (techs? ('<strong>'+techs+'</strong>') : '—') +'</div>'
                  + '</div>'
                  + '<div class="right">'
                    + '<div class="elapsed">'+ (j.elapsed_seconds!=null ? new Date(j.elapsed_seconds*1000).toISOString().substr(11,8) : '00:00:00') +'</div>'
                    + '<div class="tc-actions" style="display:flex;gap:6px;">'
                      + '<button class="btn btn-success btn-sm" title="Play all" onclick="playAll(' + (j.id != null ? j.id : 'null') + ')"><i class="fa fa-play"></i></button>'
                      + '<button class="btn btn-warning btn-sm" title="Pause all" onclick="pauseAll(' + (j.id != null ? j.id : 'null') + ')"><i class="fa fa-pause"></i></button>'
                      + '<button class="btn btn-danger btn-sm" title="Complete all" onclick="completeAll(' + (j.id != null ? j.id : 'null') + ')"><i class="fa fa-stop"></i></button>'
                    + '</div>'
                  + '</div>'
                + '</div>'
                + groupsHtml
                + workersHtml;
              }).join('');
            }

            function refresh(){
              if(!listUrl) return;
              var url = listUrl + '?page=' + encodeURIComponent(tcPage) + '&per_page=' + encodeURIComponent(tcPerPage) + (window.location.search ? ('&' + window.location.search.substring(1)) : '');
              fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                .then(function(r){ return r.json(); })
                .then(function(data){
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
                .catch(function(){ /* silent */ });
            }

            function startTimer(jobId, userId) {
              fetch("{{ route('timemanagement.timecontrol') }}/start", {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({job_sheet_id: jobId, user_id: userId})
              }).then(r=>r.json()).then(data=>{
                if(data && data.success){
                  refresh();
                }
              });
            }
            function pauseTimer(timerId) {
              if(!timerId) return;
              fetch("{{ route('timemanagement.timecontrol') }}/pause", {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({timer_id: timerId})
              }).then(r=>r.json()).then(data=>{ if(data && data.success){ refresh(); } });
            }
            function resumeTimer(timerId) {
              if(!timerId) return;
              fetch("{{ route('timemanagement.timecontrol') }}/resume", {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({timer_id: timerId})
              }).then(r=>r.json()).then(data=>{ if(data && data.success){ refresh(); } });
            }
            function completeTimer(timerId) {
              if(!timerId) return;
              fetch("{{ route('timemanagement.timecontrol') }}/complete", {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({timer_id: timerId})
              }).then(r=>r.json()).then(data=>{ if(data && data.success){ refresh(); } });
            }
            function playAll(jobId) {
              if(!jobId) return;
              fetch("{{ route('timemanagement.timecontrol') }}/play-all", {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({job_sheet_id: jobId})
              }).then(r=>r.json()).then(function(data){ if(data && data.success){ refresh(); } });
            }
            function pauseAll(jobId) {
              if(!jobId) return;
              fetch("{{ route('timemanagement.timecontrol') }}/pause-all", {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({job_sheet_id: jobId})
              }).then(r=>r.json()).then(function(data){ if(data && data.success){ refresh(); } });
            }
            function completeAll(jobId) {
              if(!jobId) return;
              fetch("{{ route('timemanagement.timecontrol') }}/complete-all", {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({job_sheet_id: jobId})
              }).then(r=>r.json()).then(function(data){ if(data && data.success){ refresh(); } });
            }

            // expose functions globally for inline onclick handlers
            window.pauseTimer = pauseTimer;
            window.resumeTimer = resumeTimer;
            window.completeTimer = completeTimer;
            window.startTimer = startTimer;
            window.playAll = playAll;
            window.pauseAll = pauseAll;
            window.completeAll = completeAll;

            // Auto refresh every 30s
            setInterval(refresh, 30000);

            // Controls
            var pr = qs('#tc-prev'), nx = qs('#tc-next');
            if(pr){ pr.addEventListener('click', function(){ if(tcPage>1){ tcPage--; refresh(); } }); }
            if(nx){ nx.addEventListener('click', function(){ tcPage++; refresh(); }); }
            var pp = qs('#tc-per-page'); if(pp){ pp.addEventListener('change', function(){ tcPerPage = parseInt(this.value,10)||10; tcPage = 1; refresh(); }); }

            // Initial fetch to align server view with pagination
            setTimeout(refresh, 200);

            // Ticking active timers locally every 1s
            setInterval(function(){
              var els = document.querySelectorAll('.elapsed[data-active="1"]');
              Array.prototype.forEach.call(els, function(el){
                var sec = parseInt(el.getAttribute('data-elapsed')||'0',10);
                sec += 1;
                el.setAttribute('data-elapsed', String(sec));
                el.textContent = new Date(sec*1000).toISOString().substr(11,8);
              });
            }, 1000);
          })();
          </script>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
