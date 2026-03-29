@extends('layouts.app')

@section('title', __('survey::lang.category'))

@section('content')
    @include('survey::layouts.nav')

    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('survey::lang.category')</h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('survey::lang.category')])
                    <div class="mb-10">
                        <button class="btn btn-primary" id="add_category_btn">
                            <i class="fa fa-plus"></i> @lang('messages.add')
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="survey_categories_table" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>@lang('survey::lang.category')</th>
                                    <th>@lang('lang_v1.status')</th>
                                    <th>@lang('messages.created_at')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>
    </section>

    {{-- Add / Edit Modal --}}
    <div class="modal fade" id="category_modal" tabindex="-1" role="dialog" aria-labelledby="categoryModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="categoryModalLabel"></h4>
                </div>
                <div class="modal-body">
                    <form id="category_form">
                        @csrf
                        <input type="hidden" id="category_id">
                        <div class="form-group">
                            <label for="category_name">@lang('survey::lang.category')</label>
                            <input type="text" class="form-control" id="category_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="category_active">@lang('lang_v1.status')</label>
                            <select class="form-control" id="category_active" name="active">
                                <option value="1">@lang('messages.active')</option>
                                <option value="0">@lang('messages.inactive')</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="button" class="btn btn-primary" id="category_save_btn">@lang('messages.save')</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
<script>
$(function(){
    const langAdd = @json(__('messages.add'));
    const langEdit = @json(__('messages.edit'));
    const langSave = @json(__('messages.save'));
    const langPleaseSelect = @json(__('messages.please_select'));
    const langSomethingWrong = @json(__('messages.something_went_wrong'));
    const langSuccess = @json(__('messages.success'));
    const langSure = @json(__('lang_v1.are_you_sure'));
    const csrfToken = @json(csrf_token());

    var table = $('#survey_categories_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('survey.categories.data') }}",
        columns: [
            { data: 'name', name: 'name' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#add_category_btn').on('click', function(){
        $('#category_modal').find('form')[0].reset();
        $('#category_id').val('');
        $('#categoryModalLabel').text(langAdd);
        $('#category_modal').modal('show');
    });

    $(document).on('click', '.edit-category', function(){
        $('#category_modal').find('form')[0].reset();
        $('#category_id').val($(this).data('id'));
        $('#category_name').val($(this).data('name'));
        $('#category_active').val($(this).data('active'));
        $('#categoryModalLabel').text(langEdit);
        $('#category_modal').modal('show');
    });

    $('#category_save_btn').on('click', function(){
        var id = $('#category_id').val();
        var url = id ? "{{ url('survey/categories') }}/" + id : "{{ route('survey.categories.store') }}";
        var method = id ? 'PUT' : 'POST';

        $.ajax({
            method: method,
            url: url,
            data: {
                name: $('#category_name').val(),
                active: $('#category_active').val(),
                _token: csrfToken
            },
            success: function(result){
                if (result.success) {
                    toastr.success(result.message || langSuccess);
                    $('#category_modal').modal('hide');
                    table.ajax.reload();
                } else {
                    toastr.error(result.message || langSomethingWrong);
                }
            },
            error: function(xhr){
                var msg = langSomethingWrong;
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toastr.error(msg);
            }
        });
    });

    $(document).on('click', '.delete-category', function(){
        var id = $(this).data('id');
        swal({
            title: langSure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(function(value){
            if (!value) return;
            $.ajax({
                method: 'DELETE',
                url: "{{ url('survey/categories') }}/" + id,
                data: { _token: csrfToken },
                success: function(result){
                    if (result.success) {
                        toastr.success(result.message || langSuccess);
                        table.ajax.reload();
                    } else {
                        toastr.error(result.message || langSomethingWrong);
                    }
                },
                error: function(xhr){
                    var msg = langSomethingWrong;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    toastr.error(msg);
                }
            });
        });
    });
});
</script>
@endsection
