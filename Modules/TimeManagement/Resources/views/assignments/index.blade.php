@extends('layouts.app')
@section('title', __('timemanagement::lang.assignments_title'))
@section('content')
@include('timemanagement::partials.nav')

<section class="content-header">
  <h1>@lang('timemanagement::lang.assignments_title')</h1>
  <small>@lang('timemanagement::lang.assignments_subtitle')</small>
</section>
<section class="content">
  <div class="row">

    <div class="col-md-12">
      <div class="box box-solid">
        <div class="box-body">
          @include('timemanagement::partials.filters', ['action' => route('timemanagement.assignments')])
          <hr/>

          <style>
            /* Modern Assignment Page Styling */
            .assignment-container {
              background: #f8fafc;
              border-radius: 12px;
              padding: 20px;
              margin-bottom: 20px;
            }
            
            .assignment-section {
              background: #ffffff;
              border: 1px solid #e2e8f0;
              border-radius: 12px;
              box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
              margin-bottom: 20px;
              overflow: hidden;
            }
            
            .assignment-header {
              background: #f8fafc;
              border-bottom: 1px solid #e2e8f0;
              padding: 16px 20px;
              display: flex;
              align-items: center;
              justify-content: space-between;
            }
            
            .assignment-header h3 {
              margin: 0;
              font-size: 16px;
              font-weight: 600;
              color: #1e293b;
            }
            
            .assignment-body {
              padding: 0;
            }
            
            .assignment-footer {
              background: #f8fafc;
              border-top: 1px solid #e2e8f0;
              padding: 16px 20px;
            }
            
            /* List Items */
            .assignment-list {
              max-height: 420px;
              overflow-y: auto;
            }
            
            .assignment-list .list-group-item {
              border: none;
              border-bottom: 1px solid #f1f5f9;
              padding: 16px 20px;
              background: #ffffff;
              transition: all 0.2s ease;
            }
            
            .assignment-list .list-group-item:hover {
              background: #f8fafc;
            }
            
            .assignment-list .list-group-item:last-child {
              border-bottom: none;
            }
            
            /* Job Items */
            .job-item {
              cursor: pointer;
              position: relative;
            }
            
            .job-item input[type="radio"] {
              margin-right: 12px;
              transform: scale(1.2);
            }
            
            .job-number {
              font-size: 16px;
              font-weight: 600;
              color: #1e293b;
              margin-bottom: 8px;
            }
            
            .job-meta {
              margin-bottom: 6px;
            }
            
            .job-meta .label {
              background: #e2e8f0;
              color: #475569;
              border-radius: 6px;
              padding: 4px 8px;
              font-size: 12px;
              font-weight: 500;
            }
            
            /* Worker Items */
            .worker-item {
              display: flex;
              align-items: center;
              justify-content: space-between;
            }
            
            .worker-item input[type="checkbox"] {
              margin-right: 12px;
              transform: scale(1.2);
            }
            
            .worker-name {
              font-size: 14px;
              font-weight: 600;
              color: #1e293b;
            }
            
            .worker-item .label-success {
              background: #dcfce7;
              color: #166534;
            }
            
            .worker-item .label-warning {
              background: #fef3c7;
              color: #92400e;
            }
            
            .worker-item .label-default {
              background: #f1f5f9;
              color: #64748b;
            }
            
            /* Workshop Items */
            .workshop-item {
              cursor: pointer;
            }
            
            .workshop-item input[type="radio"] {
              margin-right: 12px;
              transform: scale(1.2);
            }
            
            .workshop-name {
              font-size: 14px;
              font-weight: 500;
              color: #1e293b;
            }
            
            /* Buttons */
            .assignment-btn {
              border-radius: 8px;
              padding: 8px 16px;
              font-weight: 500;
              font-size: 14px;
              border: none;
              transition: all 0.2s ease;
              margin-right: 8px;
              margin-bottom: 8px;
            }
            
            .assignment-btn-primary {
              background: #3b82f6;
              color: white;
            }
            
            .assignment-btn-primary:hover {
              background: #2563eb;
              color: white;
            }
            
            .assignment-btn-warning {
              background: #f59e0b;
              color: white;
            }
            
            .assignment-btn-warning:hover {
              background: #d97706;
              color: white;
            }
            
            .assignment-btn-danger {
              background: #ef4444;
              color: white;
            }
            
            .assignment-btn-danger:hover {
              background: #dc2626;
              color: white;
            }
            
            .assignment-btn-info {
              background: #06b6d4;
              color: white;
            }
            
            .assignment-btn-info:hover {
              background: #0891b2;
              color: white;
            }
            
            .assignment-btn-default {
              background: #f1f5f9;
              color: #64748b;
              border: 1px solid #e2e8f0;
            }
            
            .assignment-btn-default:hover {
              background: #e2e8f0;
              color: #475569;
            }
            
            /* Search Input */
            .assignment-search {
              border-radius: 8px;
              border: 1px solid #e2e8f0;
              padding: 8px 12px;
              font-size: 14px;
              transition: all 0.2s ease;
            }
            
            .assignment-search:focus {
              outline: none;
              border-color: #3b82f6;
              box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            
            /* Per Page Select */
            .per-page-container {
              display: flex;
              align-items: center;
              gap: 8px;
              margin-bottom: 16px;
            }
            
            .per-page-container label {
              font-size: 14px;
              color: #64748b;
              margin: 0;
            }
            
            .per-page-container select {
              border-radius: 6px;
              border: 1px solid #e2e8f0;
              padding: 6px 10px;
              font-size: 14px;
            }
            
            /* Alert Styling */
            .assignment-alert {
              border-radius: 8px;
              border: none;
              padding: 12px 16px;
              margin-bottom: 16px;
            }
            
            .assignment-alert.alert-success {
              background: #dcfce7;
              color: #166534;
            }
            
            .assignment-alert.alert-danger {
              background: #fee2e2;
              color: #991b1b;
            }
            
            /* Modal Styling */
            .assignment-modal .modal-content {
              border-radius: 12px;
              border: none;
              box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            }
            
            .assignment-modal .modal-header {
              background: #f8fafc;
              border-bottom: 1px solid #e2e8f0;
              border-radius: 12px 12px 0 0;
              padding: 20px;
            }
            
            .assignment-modal .modal-title {
              font-size: 18px;
              font-weight: 600;
              color: #1e293b;
            }
            
            .assignment-modal .modal-body {
              padding: 20px;
            }
            
            .assignment-modal .modal-footer {
              background: #f8fafc;
              border-top: 1px solid #e2e8f0;
              border-radius: 0 0 12px 12px;
              padding: 16px 20px;
            }
            
            /* Form Controls */
            .assignment-form-control {
              border-radius: 8px;
              border: 1px solid #e2e8f0;
              padding: 10px 12px;
              font-size: 14px;
              transition: all 0.2s ease;
            }
            
            .assignment-form-control:focus {
              outline: none;
              border-color: #3b82f6;
              box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            
            /* Responsive Design */
            @media (max-width: 768px) {
              .assignment-section {
                margin-bottom: 16px;
              }
              
              .assignment-header {
                padding: 12px 16px;
              }
              
              .assignment-footer {
                padding: 12px 16px;
              }
              
              .assignment-list .list-group-item {
                padding: 12px 16px;
              }
            }
          </style>

          <div class="clearfix per-page-container">
            <div class="pull-right">
              <label>@lang('timemanagement::lang.per_page')</label>
              <select id="per-page" class="form-control input-sm">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="15">15</option>
                <option value="20">20</option>
              </select>
            </div>
          </div>

          <div id="assign-alert" class="assignment-alert alert-success" style="display:none;"></div>
          @if(session('status'))
            <div class="assignment-alert alert-success">{{ session('status') }}</div>
          @endif

          @if($errors->any())
            <div class="assignment-alert alert-danger">
              <ul class="list-unstyled">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="row">
            <div class="col-md-6">
              <div class="assignment-section">
                <div class="assignment-header">
                  <h3>@lang('timemanagement::lang.workshops_for_job_assignment')</h3>
                  <div>
                    <input type="text" id="search-workshops-job" class="assignment-search" placeholder="@lang('timemanagement::lang.search_placeholder')" style="width:160px;">
                  </div>
                </div>
                <div class="assignment-body">
                  <div id="workshops-job-list" class="assignment-list">
                    <div class="list-group-item text-center text-muted">@lang('timemanagement::lang.select_job_sheet_to_view_workshops')</div>
                  </div>
                </div>
                <div class="assignment-footer clearfix">
                  <div class="pull-left">
                    <button type="button" id="wj-prev" class="assignment-btn assignment-btn-default">@lang('timemanagement::lang.prev')</button>
                    <button type="button" id="wj-next" class="assignment-btn assignment-btn-default">@lang('timemanagement::lang.next')</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Job Sheets Column -->
            <div class="col-md-6">
              <div class="assignment-section">
                <div class="assignment-header">
                  <h3>@lang('timemanagement::lang.job_sheets')</h3>
                  <div>
                    <input type="text" id="search-jobs" class="assignment-search" placeholder="@lang('timemanagement::lang.search_placeholder')" style="width:160px;">
                  </div>
                </div>
                <div class="assignment-body">
                  <div id="jobs-list" class="assignment-list">
                    @forelse(($jobs ?? collect()) as $job)
                      @php
                        $workshopIds = json_decode($job->workshops ?? '[]', true) ?: [];
                        $workshopNames = [];
                        foreach ($workshopIds as $workshopId) {
                          $workshopNames[] = $workshops[$workshopId] ?? __('timemanagement::lang.unknown_workshop');
                        }
                        $workshopsText = implode(', ', $workshopNames);
                      
                      @endphp
                    
                      <label class="list-group-item job-item">
                        <input type="radio" name="job_sheet_id" class="job-radio" value="{{ $job->id }}" data-workshop-ids="{{ implode(',', $workshopIds) }}">
                        <div class="job-number">{{ $job->job_sheet_no }}</div>
                        <div class="text-muted job-meta">
                          @lang('timemanagement::lang.status'): <span class="label label-default">{{ $job->status_name }}</span>
  
                        </div>
                        <div class="text-muted">                        @if($workshopsText)
                            <span class="tw-ml-2"> @lang('timemanagement::lang.assigned_workshops'): {{ $workshopsText }}</span>
                          @else
                            <span class="tw-ml-2">· @lang('timemanagement::lang.no_workshops_assigned')</span>
                          @endif</div>
                        <div class="text-muted">@lang('timemanagement::lang.start'): {{ $job->start_date ?? $job->entry_date ?? __('timemanagement::lang.not_available') }}</div>
                      </label>
                    @empty
                      <div class="list-group-item text-center text-muted">@lang('timemanagement::lang.no_job_sheets')</div>
                    @endforelse
                  </div>
                </div>

                <div class="assignment-footer text-right">
                  <button type="button" id="btn-assign" class="assignment-btn assignment-btn-primary">@lang('timemanagement::lang.assign_workshops_to_job')</button>
                  <button type="button" id="btn-unassign" class="assignment-btn assignment-btn-warning">@lang('timemanagement::lang.unassign_selected')</button>
                  <button type="button" id="btn-clear-all" class="assignment-btn assignment-btn-danger">@lang('timemanagement::lang.clear_all_workshops')</button>
                </div>
                <div class="assignment-footer clearfix">
                  <div class="pull-left">
                    <button type="button" id="j-prev" class="assignment-btn assignment-btn-default">@lang('timemanagement::lang.prev')</button>
                    <button type="button" id="j-next" class="assignment-btn assignment-btn-default">@lang('timemanagement::lang.next')</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Workshop Assignment & Attendance Actions -->
          <div class="row" style="margin-top:15px;">
            <div class="col-md-6">
              <div class="assignment-section">
                <div class="assignment-header">
                  <h3>@lang('timemanagement::lang.attendance_actions')</h3>
                </div>
                <div class="assignment-body">
                  <div class="assignment-list">
                    @forelse(($workers ?? collect()) as $worker)
                      @php
                        $badgeClass = $worker->status === 'Active' ? 'label-success' : ($worker->status === 'Clocked-in' ? 'label-warning' : 'label-default');
                      @endphp
                      <label class="list-group-item worker-item">
                        <div style="display: flex; align-items: center;">
                          <input type="checkbox" class="worker-checkbox" value="{{ $worker->user_id }}">
                          <strong class="worker-name">{{ $worker->user_name }}</strong>
                          <span class="label {{ $badgeClass }}" style="margin-left: 8px;">{{ $worker->status }}</span>
                        </div>
                        <div class="text-muted" style="margin-top:4px;">
                          @if($worker->current_workshop)
                            <span class="text-success">📍 {{ $worker->current_workshop }}</span>
                          @else
                            <span class="text-muted">@lang('timemanagement::lang.not_assigned')</span>
                          @endif
                          <br>
                          <small>
                            @lang('timemanagement::lang.assigned_label'): {{ $worker->assigned ? __('Yes') : __('No') }} · 
                            @lang('timemanagement::lang.present_label'): {{ $worker->present ? __('Yes') : __('No') }}
                          </small>
                        </div>
                      </label>
                    @empty
                      <div class="list-group-item text-center text-muted">@lang('timemanagement::lang.no_technicians')</div>
                    @endforelse
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="assignment-section">
                <div class="assignment-header">
                  <h3>@lang('timemanagement::lang.workshops')</h3>
                  <div>
                    <input type="text" id="search-workshops" class="assignment-search" placeholder="@lang('timemanagement::lang.search_placeholder')" style="width:160px;">
                  </div>
                </div>
                <div class="assignment-body">
                  <div id="workshops-list" class="assignment-list">
                    @forelse(($workshops ?? collect()) as $id => $name)
                      <label class="list-group-item workshop-item">
                        <input type="radio" name="workshop_id_select" class="workshop-radio" value="{{ $id }}">
                        <span class="workshop-name">{{ $name }}</span>
                      </label>
                    @empty
                      <div class="list-group-item text-center text-muted">@lang('timemanagement::lang.no_workshops')</div>
                    @endforelse
                  </div>
                </div>
                <div class="assignment-footer text-right">
                  <button type="button" id="btn-assign-workshop" class="assignment-btn assignment-btn-primary">@lang('timemanagement::lang.assign_selected_to_workshop')</button>
                  <button type="button" id="btn-unassign-workshop" class="assignment-btn assignment-btn-warning">@lang('timemanagement::lang.unassign_selected_from_workshop')</button>
                  <button type="button" id="btn-view-history" class="assignment-btn assignment-btn-info">@lang('timemanagement::lang.view_assignment_history')</button>
                </div>
              </div>
            </div>

            
          </div>

          <!-- Assignment History Modal -->
          <div class="modal fade assignment-modal" id="assignmentHistoryModal" tabindex="-1" role="dialog" aria-labelledby="assignmentHistoryModalLabel">
            <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <h4 class="modal-title" id="assignmentHistoryModalLabel">@lang('timemanagement::lang.assignment_history')</h4>
                </div>
                <div class="modal-body">
                  <div class="row">
                    <div class="col-md-12">
                      <div class="form-group">
                        <label for="history_technician">@lang('timemanagement::lang.technician')</label>
                        <select id="history_technician" class="assignment-form-control">
                          <option value="">@lang('timemanagement::lang.select_technician')</option>
                          @foreach(($workers ?? collect()) as $worker)
                            <option value="{{ $worker->user_id }}">{{ $worker->user_name }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="history_start_date">@lang('timemanagement::lang.start_date')</label>
                        <input type="date" id="history_start_date" class="assignment-form-control">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="history_end_date">@lang('timemanagement::lang.end_date')</label>
                        <input type="date" id="history_end_date" class="assignment-form-control">
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-12">
                      <button type="button" id="btn-load-history" class="assignment-btn assignment-btn-primary">@lang('timemanagement::lang.load_history')</button>
                    </div>
                  </div>
                  <hr>
                  <div id="history-content">
                    <div class="text-center text-muted">@lang('timemanagement::lang.select_technician_and_dates_to_view_history')</div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="assignment-btn assignment-btn-default" data-dismiss="modal">@lang('timemanagement::lang.close')</button>
                </div>
              </div>
            </div>
          </div>

          @endsection
          @section('javascript')


          <script>
          (function(){
            let currentJobSheetId = null;
            let workshopsForJob = [];

            // Job sheet selection handler - fetch workshops for selected job
            $(document).on('change', '.job-radio', function() {
              const jobSheetId = $(this).val();
              currentJobSheetId = jobSheetId;
              
              if (jobSheetId) {
                // Fetch workshops for this job sheet
                $.ajax({
                  url: '{{ route("timemanagement.assignments.workshopsByJob", ":id") }}'.replace(':id', jobSheetId),
                  method: 'GET',
                  success: function(response) {
                    workshopsForJob = response.workshops || {};
                    displayWorkshopsForJob(workshopsForJob);
                  },
                  error: function(xhr) {
                    console.error('Error fetching workshops for job:', xhr);
                    $('#workshops-job-list').html('<div class="list-group-item text-center text-muted">@lang('timemanagement::lang.error_loading_workshops')</div>');
                  }
                });
              } else {
                workshopsForJob = [];
                $('#workshops-job-list').html('<div class="list-group-item text-center text-muted">@lang('timemanagement::lang.select_job_sheet_to_view_workshops')</div>');
              }
            });

            // Display workshops for job assignment
            function displayWorkshopsForJob(workshops) {
              let html = '';
              
              if (Object.keys(workshops).length === 0) {
                html = '<div class="list-group-item text-center text-muted">@lang('timemanagement::lang.no_workshops_available_for_job')</div>';
              } else {
                Object.entries(workshops).forEach(([id, name]) => {
                  html += `
                    <label class="list-group-item workshop-item">
                      <input type="checkbox" class="workshop-job-checkbox" value="${id}">
                      <span class="workshop-name">${name}</span>
                    </label>
                  `;
                });
              }
              
              $('#workshops-job-list').html(html);
            }

            // Search functionality for workshops (job assignment)
            $('#search-workshops-job').on('input', function() {
              const searchTerm = $(this).val().toLowerCase();
              $('.workshop-job-checkbox').closest('.list-group-item').each(function() {
                const workshopName = $(this).find('.workshop-name').text().toLowerCase();
                $(this).toggle(workshopName.includes(searchTerm));
              });
            });

            // Search functionality for jobs
            $('#search-jobs').on('input', function() {
              const searchTerm = $(this).val().toLowerCase();
              $('.job-radio').closest('.list-group-item').each(function() {
                const jobText = $(this).text().toLowerCase();
                $(this).toggle(jobText.includes(searchTerm));
              });
            });

            // Search functionality for workshops (technician assignment)
            $('#search-workshops').on('input', function() {
              const searchTerm = $(this).val().toLowerCase();
              $('.workshop-radio').closest('.list-group-item').each(function() {
                const workshopName = $(this).find('.workshop-name').text().toLowerCase();
                $(this).toggle(workshopName.includes(searchTerm));
              });
            });

            // Workshop to Job Sheet assignment functionality
            $('#btn-assign').on('click', function() {
              var selectedJob = $('.job-radio:checked');
              var selectedWorkshops = $('.workshop-job-checkbox:checked');

              console.log('Assign button clicked');
              console.log('Selected job:', selectedJob.val());
              console.log('Selected workshops:', selectedWorkshops.length);

              if (selectedJob.length === 0) {
                alert('@lang('timemanagement::lang.select_job_sheet_first')');
                return;
              }

              if (selectedWorkshops.length === 0) {
                alert('@lang('timemanagement::lang.select_workshops_first')');
                return;
              }

              var workshopIds = selectedWorkshops.map(function() { return $(this).val(); }).get();
              console.log('Workshop IDs to assign:', workshopIds);

              $.ajax({
                url: '{{ route("timemanagement.assignments.assign") }}',
                method: 'POST',
                data: {
                  job_sheet_id: selectedJob.val(),
                  workshop_ids: workshopIds,
                  _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                  console.log('Assignment successful:', response);
                  if (response.status === 'success') {
                    $('#assign-alert').text(response.message).show();
                    setTimeout(function() { location.reload(); }, 1000);
                  } else {
                    alert(response.message);
                  }
                },
                error: function(xhr) {
                  console.log('AJAX Error:', xhr);
                  var errorMessage = '{{ __("timemanagement::lang.error_something_went_wrong") }}';
                  if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                  } else if (xhr.responseText) {
                    try {
                      var response = JSON.parse(xhr.responseText);
                      errorMessage = response.message || errorMessage;
                    } catch (e) {
                      errorMessage = xhr.responseText || errorMessage;
                    }
                  }
                  alert('{{ __("timemanagement::lang.error_something_went_wrong") }}: ' + errorMessage + ' (Status: ' + xhr.status + ')');
                }
              });
            });

            $('#btn-unassign').on('click', function() {
              var selectedJob = $('.job-radio:checked');
              var selectedWorkshops = $('.workshop-job-checkbox:checked');

              if (selectedJob.length === 0) {
                alert('@lang('timemanagement::lang.select_job_sheet_first')');
                return;
              }

              if (selectedWorkshops.length === 0) {
                alert('@lang('timemanagement::lang.select_workshops_to_unassign')');
                return;
              }

              if (!confirm('@lang('timemanagement::lang.confirm_unassign_workshops_from_job')')) {
                return;
              }

              var workshopIds = selectedWorkshops.map(function() { return $(this).val(); }).get();

              $.ajax({
                url: '{{ route("timemanagement.assignments.unassign") }}',
                method: 'POST',
                data: {
                  job_sheet_id: selectedJob.val(),
                  workshop_ids: workshopIds,
                  _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                  if (response.status === 'success') {
                    $('#assign-alert').text(response.message).show();
                    setTimeout(function() { location.reload(); }, 1000);
                  } else {
                    alert(response.message);
                  }
                },
                error: function(xhr) {
                  console.log('AJAX Error:', xhr);
                  var errorMessage = '{{ __("timemanagement::lang.error_something_went_wrong") }}';
                  if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                  } else if (xhr.responseText) {
                    try {
                      var response = JSON.parse(xhr.responseText);
                      errorMessage = response.message || errorMessage;
                    } catch (e) {
                      errorMessage = xhr.responseText || errorMessage;
                    }
                  }
                  alert('{{ __("timemanagement::lang.error_something_went_wrong") }}: ' + errorMessage + ' (Status: ' + xhr.status + ')');
                }
              });
            });

            $('#btn-clear-all').on('click', function() {
              var selectedJob = $('.job-radio:checked');

              if (selectedJob.length === 0) {
                alert('@lang('timemanagement::lang.select_job_sheet_first')');
                return;
              }

              if (!confirm('@lang('timemanagement::lang.confirm_clear_all_workshops_from_job')')) {
                return;
              }

              // Determine current assigned workshops for the selected job
              var workshopIdsToUnassign = [];
              if (workshopsForJob && Object.keys(workshopsForJob).length > 0) {
                workshopIdsToUnassign = Object.keys(workshopsForJob).map(function(id) { return parseInt(id, 10); });
              } else {
                var dataAttr = selectedJob.attr('data-workshop-ids') || '';
                workshopIdsToUnassign = dataAttr ? dataAttr.split(',').filter(function(x) { return x !== ''; }).map(function(x) { return parseInt(x, 10); }) : [];
              }

              if (workshopIdsToUnassign.length === 0) {
                alert('@lang('timemanagement::lang.no_workshops_assigned')');
                return;
              }

              $.ajax({
                url: '{{ route("timemanagement.assignments.unassign") }}',
                method: 'POST',
                data: {
                  job_sheet_id: selectedJob.val(),
                  workshop_ids: workshopIdsToUnassign,
                  _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                  if (response.status === 'success') {
                    $('#assign-alert').text('@lang('timemanagement::lang.all_workshops_cleared_from_job_sheet')').show();
                    setTimeout(function() { location.reload(); }, 1000);
                  } else {
                    alert(response.message);
                  }
                },
                error: function(xhr) {
                  alert('@lang('messages.something_went_wrong')');
                }
              });
            });

            // Workshop assignment functionality - FIXED for multiple workers
            $('#btn-assign-workshop').on('click', function() {
              var selectedWorkers = $('.worker-checkbox:checked');
              var selectedWorkshop = $('.workshop-radio:checked');

              if (selectedWorkers.length === 0) {
                alert('@lang('timemanagement::lang.select_technicians_first')');
                return;
              }

              if (selectedWorkshop.length === 0) {
                alert('@lang('timemanagement::lang.select_workshop_first')');
                return;
              }

              var notes = prompt('@lang('timemanagement::lang.assignment_notes') (optional):');
              var workerIds = selectedWorkers.map(function() { return $(this).val(); }).get();
              var workshopId = selectedWorkshop.val();

              // Process multiple workers sequentially
              processWorkerAssignments(workerIds, workshopId, notes, 0);
            });

            // Function to process multiple worker assignments sequentially
            function processWorkerAssignments(workerIds, workshopId, notes, index) {
              if (index >= workerIds.length) {
                // All assignments completed
                $('#assign-alert').text('@lang('timemanagement::lang.all_technicians_assigned_successfully')').show();
                setTimeout(function() { location.reload(); }, 1000);
                return;
              }

              $.ajax({
                url: '{{ route("timemanagement.assignments.assignWorkshop") }}',
                method: 'POST',
                data: {
                  user_id: workerIds[index],
                  workshop_id: workshopId,
                  notes: notes,
                  _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                  if (response.status === 'success') {
                    // Process next worker
                    processWorkerAssignments(workerIds, workshopId, notes, index + 1);
                  } else {
                    alert('Error assigning worker ' + (index + 1) + ': ' + response.message);
                  }
                },
                error: function(xhr) {
                  alert('Error assigning worker ' + (index + 1) + ': @lang('messages.something_went_wrong')');
                }
              });
            }

            // Workshop unassignment functionality - FIXED for multiple workers
            $('#btn-unassign-workshop').on('click', function() {
              var selectedWorkers = $('.worker-checkbox:checked');

              if (selectedWorkers.length === 0) {
                alert('@lang('timemanagement::lang.select_technicians_first')');
                return;
              }

              if (!confirm('@lang('timemanagement::lang.confirm_unassign_technicians')')) {
                return;
              }

              var notes = prompt('@lang('timemanagement::lang.assignment_notes') (optional):');
              var workerIds = selectedWorkers.map(function() { return $(this).val(); }).get();

              // Process multiple workers sequentially
              processWorkerUnassignments(workerIds, notes, 0);
            });

            // Function to process multiple worker unassignments sequentially
            function processWorkerUnassignments(workerIds, notes, index) {
              if (index >= workerIds.length) {
                // All unassignments completed
                $('#assign-alert').text('@lang('timemanagement::lang.all_technicians_unassigned_successfully')').show();
                setTimeout(function() { location.reload(); }, 1000);
                return;
              }

              $.ajax({
                url: '{{ route("timemanagement.assignments.unassignWorkshop") }}',
                method: 'POST',
                data: {
                  user_id: workerIds[index],
                  notes: notes,
                  _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                  if (response.status === 'success') {
                    // Process next worker
                    processWorkerUnassignments(workerIds, notes, index + 1);
                  } else {
                    alert('Error unassigning worker ' + (index + 1) + ': ' + response.message);
                  }
                },
                error: function(xhr) {
                  alert('Error unassigning worker ' + (index + 1) + ': @lang('messages.something_went_wrong')');
                }
              });
            }

            // View assignment history
            $('#btn-view-history').on('click', function() {
              $('#assignmentHistoryModal').modal('show');
            });

            // Load assignment history
            $('#btn-load-history').on('click', function() {
              var technicianId = $('#history_technician').val();
              var startDate = $('#history_start_date').val();
              var endDate = $('#history_end_date').val();

              if (!technicianId) {
                alert('@lang('timemanagement::lang.select_technician_first')');
                return;
              }

              $.ajax({
                url: '{{ route("timemanagement.assignments.assignmentHistory") }}',
                method: 'GET',
                data: {
                  user_id: technicianId,
                  start_date: startDate,
                  end_date: endDate
                },
                success: function(response) {
                  var history = response.data;
                  var html = '';

                  if (history.length === 0) {
                    html = '<div class="text-center text-muted">@lang('timemanagement::lang.no_assignment_history')</div>';
                  } else {
                    html += '<div class="table-responsive"><table class="table table-striped">';
                    html += '<thead><tr>';
                    html += '<th>@lang('timemanagement::lang.date')</th>';
                    html += '<th>@lang('timemanagement::lang.workshop')</th>';
                    html += '<th>@lang('timemanagement::lang.assigned_by')</th>';
                    html += '<th>@lang('timemanagement::lang.notes')</th>';
                    html += '<th>@lang('timemanagement::lang.status')</th>';
                    html += '</tr></thead><tbody>';

                    history.forEach(function(item) {
                      var statusLabel = (item.status === 'unassigned')
                        ? '<span class="label label-default">@lang('timemanagement::lang.unassigned')</span>'
                        : '<span class="label label-success">@lang('timemanagement::lang.active')</span>';

                      html += '<tr>';
                      html += '<td>' + new Date(item.assigned_at).toLocaleDateString() + '</td>';
                      html += '<td>' + (item.workshop ? item.workshop.name : '@lang('timemanagement::lang.not_available')') + '</td>';
                      html += '<td>' + (item.assigned_by ? item.assigned_by.name : '@lang('timemanagement::lang.not_available')') + '</td>';
                      html += '<td>' + (item.notes || '@lang('timemanagement::lang.no_notes')') + '</td>';
                      html += '<td>' + statusLabel + '</td>';
                      html += '</tr>';
                    });

                    html += '</tbody></table></div>';
                  }

                  $('#history-content').html(html);
                },
                error: function(xhr) {
                  $('#history-content').html('<div class="alert alert-danger">@lang('messages.something_went_wrong')</div>');
                }
              });
            });
          })();
          </script>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
