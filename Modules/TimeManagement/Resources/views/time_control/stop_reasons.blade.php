@extends('layouts.app')
@section('title', __('timemanagement::lang.stop_reasons_title'))

@section('content')
@include('timemanagement::partials.nav')
<section class="content-header">
  <h1>@lang('timemanagement::lang.stop_reasons_title')</h1>

</section>
<section class="content">
  <div class="box box-solid">
    <div class="box-header with-border">
      {{-- Creation of stop reasons is disabled here; this page is read-only list with edit/delete only. --}}
    </div>
    <div class="box-body">
      <table class="table table-bordered" id="reasons_table">
        <thead>
          <tr>
            <th>@lang('timemanagement::lang.column_id')</th>
            <th>@lang('timemanagement::lang.label_job_sheet')</th>
            <th>@lang('timemanagement::lang.technician')</th>
            <th>@lang('timemanagement::lang.column_reason_type')</th>
            <th>@lang('timemanagement::lang.column_body')</th>
            <th>@lang('timemanagement::lang.column_pause_start')</th>
            <th>@lang('timemanagement::lang.column_pause_end')</th>
            <th>@lang('timemanagement::lang.column_active')</th>
            <th>@lang('timemanagement::lang.column_created_at')</th>
            <th>@lang('timemanagement::lang.column_actions')</th>

          </tr>
        </thead>
      </table>
    </div>
  </div>

  <div class="modal fade" id="reasonModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">@lang('timemanagement::lang.stop_reasons_title')</h4>

        </div>
        <div class="modal-body">
          <form id="reason_form">
            <input type="hidden" id="reason_id">
            <div class="form-group">
              <label>@lang('timemanagement::lang.label_job_sheet')</label>
              <select id="reason_job_sheet_id" class="form-control">
                <option value="">@lang('timemanagement::lang.option_select_job_sheet')</option>
              </select>
            </div>

            <div class="form-group">
              <label>@lang('timemanagement::lang.label_phrase')</label>
              <select id="reason_phrase_id" class="form-control">
                <option value="">@lang('timemanagement::lang.option_select_phrase')</option>
              </select>
            </div>

            <div class="form-group">
              <label>@lang('timemanagement::lang.label_body')</label>
              <textarea id="reason_body" class="form-control" rows="3"></textarea>
            </div>

            <div class="form-group">
              <label>@lang('timemanagement::lang.label_pause_start')</label>
              <input type="datetime-local" id="reason_pause_start" class="form-control">
            </div>
            <div class="form-group">
              <label>@lang('timemanagement::lang.label_pause_end')</label>
              <input type="datetime-local" id="reason_pause_end" class="form-control">
            </div>

            <div class="checkbox">
              <label>
                <input type="checkbox" id="reason_active" checked> @lang('timemanagement::lang.label_active')

              </label>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">@lang('timemanagement::lang.btn_close')</button>
          <button type="button" id="reason_save_btn" class="btn btn-primary">@lang('timemanagement::lang.btn_save')</button>

        </div>
      </div>
    </div>
  </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
$(function() {
  var phrasesLoaded = false;
  var jobsheetsLoaded = false;

  function loadPhrases(callback) {
    if (phrasesLoaded) {
      if (typeof callback === 'function') callback();
      return;
    }
    $.ajax({
      url: "{{ url('time-management/phrases/list') }}",
      method: 'GET',
      dataType: 'json',
      success: function(resp) {
        var $sel = $('#reason_phrase_id');
        $sel.empty();
        $sel.append('<option value="">-- Select Phrase (optional) --</option>');
        (resp.data || []).forEach(function(p) {
          var label = '';
          if (p.reason_type === 'record_reason') label += '[Record] ';
          else if (p.reason_type === 'finishtimer') label += '[Finish] ';
          else if (p.reason_type === 'ignore') label += '[Ignore] ';
          if (p.body) label += p.body.substring(0, 60);
          $sel.append('<option value="' + p.id + '">' + label.replace(/"/g, '&quot;') + '</option>');
        });
        phrasesLoaded = true;
        if (typeof callback === 'function') callback();
      }
    });
  }

  function loadJobSheets(callback) {
    if (jobsheetsLoaded) {
      if (typeof callback === 'function') callback();
      return;
    }
    $.ajax({
      url: "{{ url('time-management/stop-reasons/ongoing-job-sheets') }}",
      method: 'GET',
      dataType: 'json',
      success: function(resp) {
        var $sel = $('#reason_job_sheet_id');
        $sel.empty();
        $sel.append('<option value="">-- Select Job Sheet --</option>');
        (resp.data || []).forEach(function(js) {
          var label = js.job_sheet_no ? js.job_sheet_no : ('Job #' + js.id);
          $sel.append('<option value="' + js.id + '">' + label.replace(/"/g, '&quot;') + '</option>');
        });
        jobsheetsLoaded = true;
        if (typeof callback === 'function') callback();
      }
    });
  }

  var table = $('#reasons_table').DataTable({
    processing: true,
    serverSide: false,
    order: [[8, 'desc']],
    ajax: "{{ url('time-management/stop-reasons/list') }}",
    columns: [
      { data: 'id' },
      { data: null, render: function(row){
          if (row.job_sheet_no) return row.job_sheet_no;
          if (row.job_sheet_id) return 'Job #' + row.job_sheet_id;
          return '';
        }
      },
      { data: null, render: function(row){
          if(row.first_name) return row.first_name + ' ' + (row.last_name || '');
          return '';
        }
      },
      { data: 'phrase_reason_type', render: function(data, type, row){
          if (row.phrase_body) return row.phrase_body;
          if (!data) return '';
          if (data === 'record_reason') return 'Record Reason';
          if (data === 'finishtimer') return 'Finish Timer';
          if (data === 'ignore') return 'Ignore';
          return data;
        }
      },
      { data: 'body', render: function(data){ return data ? data.substring(0, 80) : ''; } },
      { data: 'pause_start', defaultContent: '' },
      { data: 'pause_end', defaultContent: '' },
      { data: 'is_active', render: function(data){ return data ? 'Yes' : 'No'; } },
      { data: 'created_at', defaultContent: '' },
      { data: null, orderable: false, searchable: false, render: function(row){
          return '<button class="btn btn-xs btn-primary btn-edit" data-id="'+row.id+'">Edit</button> ' +
                 '<button class="btn btn-xs btn-danger btn-delete" data-id="'+row.id+'">Delete</button>';
        }
      }
    ]
  });

  // Creation of new stop reasons is disabled from this screen.

  $('#reasons_table').on('click', '.btn-edit', function(){
    var id = $(this).data('id');
    var row = table.rows().data().toArray().find(function(r){ return r.id === id; });
    if (!row) return;
    var openEdit = function() {
      $('#reason_id').val(row.id);
      $('#reason_job_sheet_id').val(row.job_sheet_id || '').prop('disabled', true);

      $('#reason_phrase_id').val(row.phrase_id || '');
      $('#reason_body').val(row.body || '');
      $('#reason_pause_start').val(row.pause_start ? row.pause_start.replace(' ', 'T') : '');
      $('#reason_pause_end').val(row.pause_end ? row.pause_end.replace(' ', 'T') : '');
      $('#reason_active').prop('checked', !!row.is_active);
      $('#reasonModal').modal('show');
    };
    loadPhrases(function(){ loadJobSheets(openEdit); });
  });

  $('#reason_save_btn').on('click', function(){
    var id = $('#reason_id').val();
    var payload = {
      phrase_id: $('#reason_phrase_id').val(),
      body: $('#reason_body').val(),
      pause_start: $('#reason_pause_start').val(),
      pause_end: $('#reason_pause_end').val(),
      is_active: $('#reason_active').is(':checked') ? 1 : 0,
      _token: '{{ csrf_token() }}'
    };

    // Do not send job_sheet_id when updating existing reasons to avoid
    // re-resolving timer_id and triggering "No active or paused timer" errors.
    if (!id) {
      payload.job_sheet_id = $('#reason_job_sheet_id').val();
    }

    var method = id ? 'PUT' : 'POST';
    var url = id ? '{{ url("time-management/stop-reasons") }}/' + id : '{{ url("time-management/stop-reasons") }}';

    $.ajax({
      url: url,
      method: method,
      data: payload,
      success: function(){
        $('#reasonModal').modal('hide');
        table.ajax.reload(null, false);
      },
      error: function(xhr){
        console.error(xhr.responseJSON || xhr.responseText);
      }
    });
  });

  $('#reasons_table').on('click', '.btn-delete', function(){
    if (!confirm('Delete this stop reason?')) return;
    var id = $(this).data('id');
    $.ajax({
      url: '{{ url("time-management/stop-reasons") }}/' + id,
      method: 'POST',
      data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
      success: function(){
        table.ajax.reload(null, false);
      },
      error: function(xhr){
        console.error(xhr.responseJSON || xhr.responseText);
      }
    });
  });
});
</script>
@endsection