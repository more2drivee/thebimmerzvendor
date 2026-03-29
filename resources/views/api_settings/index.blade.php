@extends('layouts.app')
@section('title', __('API Settings'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>API Settings
        <small>Manage API Settings</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All API Settings')])
        @can('create', App\ApiSetting::class)
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" 
                        href="{{action('App\Http\Controllers\ApiSettingController@create')}}">
                        <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot
        @endcan
        @can('view', App\ApiSetting::class)
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="api_settings_table">
                    <thead>
                        <tr>
                            <th>@lang('Token')</th>
                            <th>@lang('Domain')</th>
                            <th>@lang('Base URL')</th>
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
        var api_settings_table = $('#api_settings_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/api-settings',
            columnDefs: [
                {
                    targets: 0,
                    orderable: false,
                    searchable: false,
                },
            ],
            columns: [
                { data: 'token', name: 'token' },
                { data: 'domain', name: 'domain' },
                { data: 'base_url', name: 'base_url' },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', name: 'action' },
            ],
        });

        $(document).on('click', 'button.edit_api_setting_button', function(){
            $("div.api_setting_modal").load($(this).data('href'), function(){
                $(this).modal('show');
            });
        });

        $(document).on('click', 'button.delete_api_setting_button', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_api_setting,
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
                                api_settings_table.ajax.reload();
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

