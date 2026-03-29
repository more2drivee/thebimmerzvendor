@extends('layouts.app')
@section('title', __('Data Permissions'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Data Permissions
        <small>Manage Data Permissions</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All Data Permissions')])
        @can('create', App\DataPermission::class)
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" 
                        href="{{action('App\Http\Controllers\DataPermissionController@create')}}">
                        <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot
        @endcan
        @can('view', App\DataPermission::class)
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="data_permissions_table">
                    <thead>
                        <tr>
                            <th>@lang('Permission Key')</th>
                            <th>@lang('Permission Name')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('messages.created_at')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

</section>
<!-- /.content -->
@stop

@section('javascript')
<script>
    $(document).ready( function(){
        var data_permissions_table = $('#data_permissions_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/data-permissions',
            columnDefs: [
                {
                    targets: 0,
                    orderable: false,
                    searchable: false,
                },
            ],
            columns: [
                { data: 'permission_key', name: 'permission_key' },
                { data: 'permission_name', name: 'permission_name' },
                { data: 'is_active', name: 'is_active' },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', name: 'action' },
            ],
        });

        $(document).on('click', 'button.edit_data_permission_button', function(){
            $("div.data_permission_modal").load($(this).data('href'), function(){
                $(this).modal('show');
            });
        });

        $(document).on('click', 'button.delete_data_permission_button', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_data_permission,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).data('href');
                    var data = $(this).serialize();
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        data: data,
                        success: function(result){
                            if(result.success == true){
                                toastr.success(result.msg);
                                data_permissions_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection

