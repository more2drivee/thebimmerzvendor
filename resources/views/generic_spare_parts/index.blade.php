@extends('layouts.app')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">{{ __('generic_spare_parts.title') }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('home.home') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('generic_spare_parts.title') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('generic_spare_parts.list') }}</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-primary btn-sm" id="add_generic_spare_part">
                                <i class="fas fa-plus"></i> {{ __('generic_spare_parts.add_new') }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="generic_spare_parts_table">
                                <thead>
                                    <tr>
                                        <th>{{ __('generic_spare_parts.name') }}</th>
                                        <th>{{ __('generic_spare_parts.description') }}</th>
                                        <th>{{ __('generic_spare_parts.created_by') }}</th>
                                        <th>{{ __('generic_spare_parts.created_at') }}</th>
                                        <th>{{ __('generic_spare_parts.actions') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal -->
<div class="modal fade" id="generic_spare_part_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('generic_spare_parts.add_edit') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal_body">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var genericSparePartsTable = $('#generic_spare_parts_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("generic-spare-parts.datatable") }}',
        columns: [
            { data: 'name' },
            { data: 'description' },
            { data: 'created_by' },
            { 
                data: 'created_at',
                render: function(data) {
                    return data ? moment(data).format('YYYY-MM-DD HH:mm') : '-';
                }
            },
            { 
                data: 'action',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return '<button type="button" class="btn btn-sm btn-info edit-btn" data-id="' + data.edit_id + '">' +
                           '<i class="fas fa-edit"></i>' +
                           '</button> ' +
                           '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' + data.delete_id + '">' +
                           '<i class="fas fa-trash"></i>' +
                           '</button>';
                }
            }
        ],
        language: {
            processing: '{{ __("datatable.processing") }}',
            lengthMenu: '{{ __("datatable.length_menu") }}',
            zeroRecords: '{{ __("datatable.zero_records") }}',
            info: '{{ __("datatable.info") }}',
            infoEmpty: '{{ __("datatable.info_empty") }}',
            infoFiltered: '{{ __("datatable.info_filtered") }}',
            search: '{{ __("datatable.search") }}',
            paginate: {
                first: '{{ __("datatable.first") }}',
                last: '{{ __("datatable.last") }}',
                next: '{{ __("datatable.next") }}',
                previous: '{{ __("datatable.previous") }}'
            }
        }
    });

    // Add new
    $('#add_generic_spare_part').click(function() {
        $.ajax({
            url: '{{ route("generic-spare-parts.create") }}',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#modal_body').html(response.html);
                    $('#generic_spare_part_modal').modal('show');
                }
            }
        });
    });

    // Edit
    $(document).on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        $.ajax({
            url: '{{ route("generic-spare-parts.edit") }}/' + id,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#modal_body').html(response.html);
                    $('#generic_spare_part_modal').modal('show');
                }
            }
        });
    });

    // Delete
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        if (confirm('{{ __('generic_spare_parts.delete_confirm') }}')) {
            $.ajax({
                url: '{{ route("generic-spare-parts.destroy") }}/' + id,
                type: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        genericSparePartsTable.ajax.reload();
                    } else {
                        toastr.error(response.msg);
                    }
                }
            });
        }
    });

    // Save form
    $(document).on('submit', '#generic_spare_part_form', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var url = $(this).attr('action');
        var method = $(this).find('input[name="_method"]').val() || 'POST';

        $.ajax({
            url: url,
            type: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg);
                    $('#generic_spare_part_modal').modal('hide');
                    genericSparePartsTable.ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                var errorMessages = [];
                for (var key in errors) {
                    errorMessages.push(errors[key][0]);
                }
                toastr.error(errorMessages.join('<br>'));
            }
        });
    });
});
</script>
@endpush
