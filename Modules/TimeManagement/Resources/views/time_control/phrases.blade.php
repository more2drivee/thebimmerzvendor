@extends('layouts.app')
@section('title', __('timemanagement::lang.phrases_title'))

@section('content')
@include('timemanagement::partials.nav')
<section class="content-header">
  <h1>@lang('timemanagement::lang.phrases_title')</h1>

</section>
<section class="content">
  <div class="box box-solid">
    <div class="box-header with-border">
      <button id="btn-add-phrase" class="btn btn-primary pull-right">@lang('timemanagement::lang.btn_add_phrase')</button>

    </div>
    <div class="box-body">
      <table class="table table-bordered" id="phrases_table">
        <thead>
          <tr>
            <th>@lang('timemanagement::lang.column_id')</th>
            <th>@lang('timemanagement::lang.column_reason_type')</th>
            <th>@lang('timemanagement::lang.column_body')</th>
            <th>@lang('timemanagement::lang.column_active')</th>
            <th>@lang('timemanagement::lang.column_created_at')</th>
            <th>@lang('timemanagement::lang.column_actions')</th>

          </tr>
        </thead>
      </table>
    </div>
  </div>

  <div class="modal fade" id="phraseModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">@lang('timemanagement::lang.label_phrase')</h4>

        </div>
        <div class="modal-body">
          <form id="phrase_form">
            <input type="hidden" id="phrase_id">
            <div class="form-group">
              <label>@lang('timemanagement::lang.label_reason_type')</label>

              <select id="phrase_reason_type" class="form-control">
                <option value="record_reason">@lang('timemanagement::lang.reason_type_record')</option>
                <option value="finishtimer">@lang('timemanagement::lang.reason_type_finish')</option>
                <option value="ignore">@lang('timemanagement::lang.reason_type_ignore')</option>

              </select>
            </div>

            <div class="form-group">
              <label>@lang('timemanagement::lang.label_body')</label>

              <textarea id="phrase_body" class="form-control" rows="3"></textarea>
            </div>
            <div class="checkbox">
              <label>
                <input type="checkbox" id="phrase_active" checked> @lang('timemanagement::lang.label_active')

              </label>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">@lang('timemanagement::lang.btn_close')</button>
          <button type="button" id="phrase_save_btn" class="btn btn-primary">@lang('timemanagement::lang.btn_save')</button>

        </div>
      </div>
    </div>
  </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
$(function() {
  var table = $('#phrases_table').DataTable({
    processing: true,
    serverSide: false,
    ajax: "{{ url('time-management/phrases/list') }}",
    columns: [
      { data: 'id' },
      { data: 'reason_type', render: function(data){
          if (!data) return '';
          if (data === 'record_reason') return 'Record Reason';
          if (data === 'finishtimer') return 'Finish Timer';
          if (data === 'ignore') return 'Ignore';
          return data;
        }
      },
      { data: 'body', render: function(data){ return data ? data.substring(0, 80) : ''; } },
      { data: 'is_active', render: function(data){ return data ? 'Yes' : 'No'; } },
      { data: 'created_at', defaultContent: '' },
      { data: null, orderable: false, searchable: false, render: function(row){
          return '<button class="btn btn-xs btn-primary btn-edit" data-id="'+row.id+'">Edit</button> ' +
                 '<button class="btn btn-xs btn-danger btn-delete" data-id="'+row.id+'">Delete</button>';
        }
      }
    ]
  });

  $('#btn-add-phrase').on('click', function(){
    $('#phrase_id').val('');
    $('#phrase_reason_type').val('record_reason');
    $('#phrase_body').val('');
    $('#phrase_active').prop('checked', true);
    $('#phraseModal').modal('show');
  });

  $('#phrases_table').on('click', '.btn-edit', function(){
    var id = $(this).data('id');
    var row = table.rows().data().toArray().find(function(r){ return r.id === id; });
    if (!row) return;
    $('#phrase_id').val(row.id);
    $('#phrase_reason_type').val(row.reason_type || 'record_reason');

    $('#phrase_body').val(row.body || '');
    $('#phrase_active').prop('checked', !!row.is_active);
    $('#phraseModal').modal('show');
  });

  $('#phrase_save_btn').on('click', function(){
    var id = $('#phrase_id').val();
    var payload = {
      reason_type: $('#phrase_reason_type').val(),
      body: $('#phrase_body').val(),
      is_active: $('#phrase_active').is(':checked') ? 1 : 0,
      _token: '{{ csrf_token() }}'
    };

    var method = id ? 'PUT' : 'POST';
    var url = id ? '{{ url("time-management/phrases") }}/' + id : '{{ url("time-management/phrases") }}';

    $.ajax({
      url: url,
      method: method,
      data: payload,
      success: function(){
        $('#phraseModal').modal('hide');
        table.ajax.reload(null, false);
      },
      error: function(xhr){
        console.error(xhr.responseJSON || xhr.responseText);
      }
    });
  });

  $('#phrases_table').on('click', '.btn-delete', function(){
    if (!confirm('Delete this phrase?')) return;
    var id = $(this).data('id');
    $.ajax({
      url: '{{ url("time-management/phrases") }}/' + id,
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