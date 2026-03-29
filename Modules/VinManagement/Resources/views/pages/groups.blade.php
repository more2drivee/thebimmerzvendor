@extends('layouts.app')
@section('title', __('vin.groups_title'))
@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('vin.groups_title')</h1>
    <a href="{{ route('vin.dashboard') }}" class="btn btn-default tw-mt-2"><i class="fas fa-arrow-left"></i> @lang('vin.groups_back_to_dashboard')</a>
    <hr>
    <p class="text-muted">@lang('vin.groups_subtitle')</p>
    
</section>
<section class="content">
    <div class="row">
        <div class="col-md-5">
            <div class="box box-solid">
                <div class="box-header with-border"><h3 class="box-title">@lang('vin.groups_create_edit')</h3></div>
                <div class="box-body">
                    <form id="group-form">
                        <input type="hidden" id="group-id" value="">
                        <div class="form-group">
                            <label for="group-name">@lang('vin.groups_name')</label>
                            <input type="text" class="form-control" id="group-name" placeholder="@lang('vin.groups_name_placeholder')" required>
                        </div>
                        <div class="form-group">
                            <label for="group-color">@lang('vin.groups_color')</label>
                            <input type="color" class="form-control" id="group-color" value="#ffcc00">
                        </div>
                        <div class="form-group">
                            <label for="group-text">@lang('vin.groups_text')</label>
                            <textarea class="form-control" id="group-text" rows="3" placeholder="@lang('vin.groups_text_placeholder')"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" id="group-save-btn">@lang('vin.groups_save')</button>
                        <button type="button" class="btn btn-default" id="group-reset-btn">@lang('vin.groups_reset')</button>
                    </form>
                    <hr>
                    <h4 class="tw-text-base tw-font-semibold">@lang('vin.groups_existing')</h4>
                    <div class="table-responsive">
                        <table id="groups-table" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>@lang('vin.groups_th_name')</th>
                                    <th>@lang('vin.groups_th_color')</th>
                                    <th>@lang('vin.groups_th_text')</th>
                                    <th>@lang('vin.groups_th_actions')</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="box box-solid">
                <div class="box-header with-border"><h3 class="box-title">@lang('vin.groups_assign_title')</h3></div>
                <div class="box-body">
                    <div class="tw-mb-3 tw-flex tw-gap-2 tw-flex-wrap">
                        <select id="assign-group-select" class="form-control" style="max-width:260px">
                            <option value="">@lang('vin.groups_select_group')</option>
                        </select>
                        <button class="btn btn-success" id="assign-selected">@lang('vin.groups_assign_selected')</button>
                        <button class="btn btn-warning" id="unassign-selected">@lang('vin.groups_unassign_selected')</button>
                        <button class="btn btn-default" id="vin-reload">@lang('vin.groups_reload_vins')</button>
                    </div>
                    <div class="table-responsive">
                        <table id="vin-assign-table" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>@lang('vin.groups_th_id')</th>
                                    <th>@lang('vin.groups_th_vin_number')</th>
                                    <th>@lang('vin.groups_th_car_brand')</th>
                                    <th>@lang('vin.groups_th_car_model')</th>
                                    <th>@lang('vin.groups_th_manufacturer')</th>
                                    <th>@lang('vin.groups_th_color2')</th>
                                    <th>@lang('vin.groups_th_year')</th>
                                    <th>@lang('vin.groups_th_car_type')</th>
                                    <th>@lang('vin.groups_th_transmission')</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(function(){
    function loadGroups(){
        $.get('{{ route("vin.groups.list") }}', function(items){
            var $sel = $('#assign-group-select');
            $sel.find('option:not(:first)').remove();
            var $tbody = $('#groups-table tbody').empty();
            (items||[]).forEach(function(g){
                $sel.append('<option value="'+g.id+'">'+g.name+'</option>');
                var colorHtml = g.color ? '<span class="label" style="background:'+g.color+'; color:#000">'+g.color+'</span>' : '';
                var row = '<tr>'+
                    '<td><a href="#" class="edit-group" data-id="'+g.id+'" data-name="'+(g.name||'')+'" data-color="'+(g.color||'')+'" data-text="'+(g.text||'')+'">'+g.name+'</a></td>'+
                    '<td>'+colorHtml+'</td>'+
                    '<td>'+ (g.text || '') +'</td>'+
                    '<td>'+
                        '<button class="btn btn-xs btn-primary edit-group" data-id="'+g.id+'" data-name="'+(g.name||'')+'" data-color="'+(g.color||'')+'" data-text="'+(g.text||'')+'"><i class="fas fa-edit"></i> Edit</button> '+
                        '<button class="btn btn-xs btn-info view-group-vins" data-id="'+g.id+'"><i class="fas fa-list"></i> Manage VINs</button> '+
                        '<button class="btn btn-xs btn-danger delete-group" data-id="'+g.id+'"><i class="fas fa-trash"></i> Delete</button>'+
                    '</td>'+
                '</tr>';
                $tbody.append(row);
            });
        }).fail(function(xhr){
            toastr.error('Failed to load groups: ' + (xhr.responseJSON?.message || xhr.statusText));
        });
    }

    // Create/Update group
    $('#group-form').on('submit', function(e){
        e.preventDefault();
        var id = $('#group-id').val();
        var payload = {
            name: $('#group-name').val(),
            color: $('#group-color').val(),
            text: $('#group-text').val(),
            _token: '{{ csrf_token() }}'
        };
        var url, method;
        if(id){
            url = '{{ url("vin/groups") }}/' + id;
            method = 'PUT';
        } else {
            url = '{{ route("vin.groups.store") }}';
            method = 'POST';
        }
        $.ajax({ url: url, type: method, data: payload })
            .done(function(resp){
                toastr.success('@lang('vin.groups_toast_saved')');
                $('#group-id').val('');
                $('#group-name').val('');
                $('#group-color').val('');
                $('#group-text').val('');
                loadGroups();
            })
            .fail(function(xhr){
                toastr.error(xhr.responseJSON?.message || '@lang('vin.groups_toast_load_failed', ['message' => ''])');
            });
    });

    // Reset form
    $('#group-reset-btn').on('click', function(){
        $('#group-id').val('');
        $('#group-name').val('');
        $('#group-color').val('');
        $('#group-text').val('');
    });

    // Edit existing group (open modal)
    $(document).on('click', '.edit-group', function(e){
        e.preventDefault();
        var gid = $(this).data('id');
        $('#edit-group-id').val(gid);
        $('#edit-group-name').val($(this).data('name'));
        $('#edit-group-color').val($(this).data('color'));
        $('#edit-group-text').val($(this).data('text'));
        $('#group-edit-modal').modal('show');
    });

    // Save edit from modal
    $(document).on('click', '#group-edit-save', function(){
        var gid = $('#edit-group-id').val();
        if(!gid){ toastr.warning('@lang('vin.groups_assign_select_group_first')'); return; }
        var payload = {
            name: $('#edit-group-name').val(),
            color: $('#edit-group-color').val(),
            text: $('#edit-group-text').val(),
            _token: '{{ csrf_token() }}'
        };
        $.ajax({ url: '{{ url("vin/groups") }}/' + gid, type: 'PUT', data: payload })
            .done(function(){
                toastr.success('@lang('vin.groups_toast_updated')');
                $('#group-edit-modal').modal('hide');
                loadGroups();
            })
            .fail(function(xhr){ toastr.error('@lang('vin.groups_toast_load_failed', ['message' => ''])'.replace(':message', (xhr.responseJSON?.message || xhr.statusText))); });
    });

    // Delete group
    $(document).on('click', '.delete-group', function(){
        var id = $(this).data('id');
        if(!confirm('@lang('vin.groups_confirm_delete')')) return;
        $.ajax({ url: '{{ url("vin/groups") }}/' + id, type: 'DELETE', data: { _token: '{{ csrf_token() }}' } })
            .done(function(){ toastr.success('@lang('vin.groups_toast_deleted')'); loadGroups(); })
            .fail(function(xhr){ toastr.error(xhr.responseJSON?.message || '@lang('vin.groups_toast_load_failed', ['message' => ''])'); });
    });

    // Modal: view and manage assigned VINs of a group
    function loadGroupVins(gid){
        var url = '{{ url("vin/groups") }}/' + gid + '/vins';
        var $tbody = $('#group-vins-table tbody').empty();
        $.get(url, function(items){
            (items||[]).forEach(function(row){
                var tr = '<tr>'+
                    '<td>'+row.id+'</td>'+
                    '<td>'+ (row.vin_number||'') +'</td>'+
                    '<td>'+ (row.manufacturer||'') +'</td>'+
                    '<td>'+ (row.year||'') +'</td>'+
                    '<td>'+ (row.car_type||'') +'</td>'+
                    '<td>'+ (row.transmission||'') +'</td>'+
                    '<td><button class="btn btn-xs btn-warning unassign-one" data-vin="'+row.id+'">Unassign</button></td>'+
                '</tr>';
                $tbody.append(tr);
            });
        }).fail(function(xhr){ toastr.error(xhr.responseJSON?.message || '@lang('vin.groups_vins_toast_load_failed')'); });
    }

    $(document).on('click', '.view-group-vins', function(){
        var gid = $(this).data('id');
        $('#group-vins-modal').data('gid', gid).modal('show');
        loadGroupVins(gid);
    });

    $(document).on('click', '#group-vins-refresh', function(){
        var gid = $('#group-vins-modal').data('gid');
        if(gid) loadGroupVins(gid);
    });

    $(document).on('click', '.unassign-one', function(){
        var vinId = $(this).data('vin');
        var gid = $('#group-vins-modal').data('gid');
        if(!gid) return;
        $.post('{{ route("vin.groups.unassign") }}', { vin_id: vinId, group_id: gid, _token: '{{ csrf_token() }}' })
            .done(function(){
                toastr.success('@lang('vin.groups_vins_toast_unassigned', ['id' => ''])'.replace(':id', vinId));
                // remove the row
                $('#group-vins-table tbody').find('button.unassign-one[data-vin="'+vinId+'"]').closest('tr').remove();
            })
            .fail(function(xhr){ toastr.error(xhr.responseJSON?.message || '@lang('vin.groups_vins_toast_unassign_failed')'); });
    });

    // VINs DataTable for assignment
    var vinTable = $('#vin-assign-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("vin.list") }}',
            error: function(xhr){ toastr.error('@lang('vin.toast_load_failed', ['message' => ''])'.replace(':message', (xhr.responseJSON?.message || xhr.statusText))); }
        },
        columns: [
            { data: null, orderable:false, searchable:false, render: function(data,type,row){ return '<input type="checkbox" class="vin-select" value="'+row.id+'">'; } },
            { data: 'id' },
            { data: 'vin_number' },
            { data: 'car_brand_name' },
            { data: 'car_model_name' },
            { data: 'manufacturer' },
            { data: 'color' },
            { data: 'year' },
            { data: 'car_type' },
            { data: 'transmission' }
        ]
    });

    // Select all toggle
    $('#select-all').on('change', function(){
        var checked = $(this).prop('checked');
        $('#vin-assign-table').find('input.vin-select').prop('checked', checked);
    });
    $('#vin-reload').on('click', function(){ vinTable.ajax.reload(); });

    function getSelectedVinIds(){
        var ids = [];
        $('#vin-assign-table').find('input.vin-select:checked').each(function(){ ids.push($(this).val()); });
        return ids;
    }

    function ensureGroupSelected(){
        var gid = $('#assign-group-select').val();
        if(!gid){ toastr.warning('@lang('vin.groups_assign_select_group_first')'); return null; }
        return gid;
    }

    function bulkAssign(ids, gid, action){
        if(ids.length === 0){ toastr.info('@lang('vin.groups_assign_select_at_least_one')'); return; }
        var url = action === 'assign' ? '{{ route("vin.groups.assign") }}' : '{{ route("vin.groups.unassign") }}';
        var promises = ids.map(function(id){
            return $.post(url, { vin_id: id, group_id: gid, _token: '{{ csrf_token() }}' })
                .fail(function(xhr){ console.error('Failed for VIN '+id+': ', xhr.responseJSON || xhr); });
        });
        Promise.all(promises).then(function(){
            toastr.success('@lang('vin.groups_assign_done', ['action' => ''])'.replace(':action', action).replace(':count', ids.length));
            vinTable.ajax.reload(null, false);
        });
    }

    $('#assign-selected').on('click', function(){
        var gid = ensureGroupSelected();
        if(!gid) return;
        bulkAssign(getSelectedVinIds(), gid, 'assign');
    });
    $('#unassign-selected').on('click', function(){
        var gid = ensureGroupSelected();
        if(!gid) return;
        bulkAssign(getSelectedVinIds(), gid, 'unassign');
    });

    // Initial load
    loadGroups();
});
</script>
<!-- Modal: Edit Group -->
<div class="modal fade" id="group-edit-modal" tabindex="-1" role="dialog" aria-labelledby="groupEditLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="groupEditLabel">@lang('vin.groups_edit_modal_title')</h4>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit-group-id" value="">
        <div class="form-group">
            <label for="edit-group-name">@lang('vin.groups_name')</label>
            <input type="text" class="form-control" id="edit-group-name" required>
        </div>
        <div class="form-group">
            <label for="edit-group-color">@lang('vin.groups_color')</label>
            <input type="color" class="form-control" id="edit-group-color" value="#ffcc00">
        </div>
        <div class="form-group">
            <label for="edit-group-text">@lang('vin.groups_text')</label>
            <textarea class="form-control" id="edit-group-text" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('vin.groups_edit_close')</button>
        <button type="button" class="btn btn-primary" id="group-edit-save">@lang('vin.groups_edit_save')</button>
      </div>
    </div>
  </div>
</div>
<!-- Modal: Group VINs -->
<div class="modal fade" id="group-vins-modal" tabindex="-1" role="dialog" aria-labelledby="groupVinsLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="groupVinsLabel">@lang('vin.groups_vins_modal_title')</h4>
      </div>
      <div class="modal-body">
        <div class="tw-mb-2">
            <button class="btn btn-default" id="group-vins-refresh"><i class="fas fa-sync"></i> @lang('vin.groups_vins_refresh')</button>
        </div>
        <div class="table-responsive">
            <table id="group-vins-table" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>@lang('vin.groups_th_id')</th>
                        <th>@lang('vin.groups_th_vin_number')</th>
                        <th>@lang('vin.groups_th_manufacturer')</th>
                        <th>@lang('vin.groups_th_year')</th>
                        <th>@lang('vin.groups_th_car_type')</th>
                        <th>@lang('vin.groups_th_transmission')</th>
                        <th>@lang('vin.groups_vins_th_actions')</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection