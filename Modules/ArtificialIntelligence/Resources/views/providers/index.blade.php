@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">@lang('artificialintelligence::lang.providers')</h3>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createModal">
                            <i class="fas fa-plus"></i> @lang('artificialintelligence::lang.add_new_provider')
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>@lang('artificialintelligence::lang.provider')</th>
                                    <th>@lang('artificialintelligence::lang.model_name')</th>
                                    <th>@lang('artificialintelligence::lang.status')</th>
                                    <th>@lang('artificialintelligence::lang.actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($providers as $provider)
                                    <tr>
                                        <td>{{ $provider->id }}</td>
                                        <td>{{ $provider->provider }}</td>
                                        <td>{{ $provider->model_name }}</td>
                                        <td>
                                            <span class="badge badge-{{ $provider->status == 'free' ? 'success' : 'warning' }}">
                                                {{ ucfirst($provider->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm"
                                                    onclick="showProvider({{ $provider->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-primary btn-sm"
                                                    onclick="editProvider({{ $provider->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <!-- Replace the delete form with this button -->
                                            <button type="button" class="btn btn-danger btn-sm delete-provider"
                                                    data-url="{{ route('artificialintelligence.providers.destroy', $provider->id) }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">@lang('artificialintelligence::lang.no_providers_found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $providers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">@lang('artificialintelligence::lang.add_new_provider')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('artificialintelligence.providers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="provider">@lang('artificialintelligence::lang.provider_name')</label>
                        <input type="text" class="form-control" id="provider" name="provider" required>
                    </div>
                    <div class="form-group">
                        <label for="model_name">@lang('artificialintelligence::lang.model_name')</label>
                        <input type="text" class="form-control" id="model_name" name="model_name" required>
                    </div>
                    <div class="form-group">
                        <label for="status">@lang('artificialintelligence::lang.status')</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="free">@lang('artificialintelligence::lang.free')</option>
                            <option value="paid">@lang('artificialintelligence::lang.paid')</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('artificialintelligence::lang.close')</button>
                    <button type="submit" class="btn btn-primary">@lang('artificialintelligence::lang.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">@lang('artificialintelligence::lang.edit_provider')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_provider">@lang('artificialintelligence::lang.provider_name')</label>
                        <input type="text" class="form-control" id="edit_provider" name="provider" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_model_name">@lang('artificialintelligence::lang.model_name')</label>
                        <input type="text" class="form-control" id="edit_model_name" name="model_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">@lang('artificialintelligence::lang.status')</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <option value="free">@lang('artificialintelligence::lang.free')</option>
                            <option value="paid">@lang('artificialintelligence::lang.paid')</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('artificialintelligence::lang.close')</button>
                    <button type="submit" class="btn btn-primary">@lang('artificialintelligence::lang.update_provider')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Show Modal -->
<div class="modal fade" id="showModal" tabindex="-1" role="dialog" aria-labelledby="showModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showModalLabel">@lang('artificialintelligence::lang.provider_details')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 200px;">ID</th>
                        <td id="show_id"></td>
                    </tr>
                    <tr>
                        <th>@lang('artificialintelligence::lang.provider_name')</th>
                        <td id="show_provider"></td>
                    </tr>
                    <tr>
                        <th>@lang('artificialintelligence::lang.model_name')</th>
                        <td id="show_model_name"></td>
                    </tr>
                    <tr>
                        <th>@lang('artificialintelligence::lang.status')</th>
                        <td id="show_status"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('artificialintelligence::lang.close')</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    function showProvider(id) {
        $.ajax({
            // Update the URL to use Laravel's route helper
            url: "{{ route('artificialintelligence.providers.show', ':id') }}".replace(':id', id),
            type: 'GET',
            success: function(response) {
                const provider = response.data; // Access the data property from the response
                $('#show_id').text(provider.id);
                $('#show_provider').text(provider.provider);
                $('#show_model_name').text(provider.model_name);
                $('#show_status').html(`
                    <span class="badge badge-${provider.status == 'free' ? 'success' : 'warning'}">
                        ${provider.status == 'free' ? '@lang('artificialintelligence::lang.free')' : '@lang('artificialintelligence::lang.paid')'}
                    </span>
                `);
                $('#showModal').modal('show');
            },
            error: function() {
                alert('Error loading provider details');
            }
        });
    }

    function editProvider(id) {
        $.ajax({
            // Update the URL to use Laravel's route helper
            url: "{{ route('artificialintelligence.providers.show', ':id') }}".replace(':id', id),
            type: 'GET',
            success: function(response) {
                const provider = response.data; // Access the data property from the response
                $('#editForm').attr('action', "{{ route('artificialintelligence.providers.update', ':id') }}".replace(':id', id));
                $('#edit_provider').val(provider.provider);
                $('#edit_model_name').val(provider.model_name);
                $('#edit_status').val(provider.status);
                $('#editModal').modal('show');
            },
            error: function() {
                alert('Error loading provider details');
            }
        });
    }

    $(document).ready(function() {
        $('.datatable').DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            pageLength: 10
        });

        $('#createModal form').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#createModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function(key) {
                            $(`#${key}`).addClass('is-invalid')
                                .after(`<div class="invalid-feedback">${errors[key][0]}</div>`);
                        });
                    }
                }
            });
        });

        $('#editModal form').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#editModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function(key) {
                            $(`#edit_${key}`).addClass('is-invalid')
                                .after(`<div class="invalid-feedback">${errors[key][0]}</div>`);
                        });
                    }
                }
            });
        });

        $('.delete-provider').click(function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this provider?')) {
                $.ajax({
                    url: $(this).data('url'),
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        location.reload();
                    }
                });
            }
        });

        $('.modal').on('hidden.bs.modal', function() {
            $(this).find('form').trigger('reset');
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
        });
    });
</script>

@endsection