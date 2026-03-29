@extends('layouts.app')

@section('title', __('sms::lang.sms_messages'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fas fa-sms"></i> @lang('sms::lang.sms_messages')
    </h1>
</section>

<!-- Main navbar (similar to purchase index) -->
<!-- Main navbar -->
@include('sms::layouts.navbar')

<section class="content no-print">

    @if ($message = Session::get('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ $message }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @component('components.widget', ['class' => 'box-primary', 'title' => __('sms::lang.all_sms_messages')])

    @slot('tool')
    <div class="box-tools">
        <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right"
            href="{{ route('sms.messages.create') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M12 5l0 14" />
                <path d="M5 12l14 0" />
            </svg>
            {{ __('sms::lang.add_sms_message') }}
*        </a>
    </div>
    @endslot

    <div class="table-responsive">
        <table id="sms-messages-table" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>@lang('sms::lang.id')</th>
                    <th>@lang('sms::lang.message_name')</th>
                    <th>@lang('sms::lang.template')</th>
                    <th>@lang('sms::lang.description')</th>
                    <th>@lang('sms::lang.assigned_roles')</th>
                    <th>@lang('sms::lang.status')</th>
                    <th>@lang('sms::lang.created_by')</th>
                    <th>@lang('sms::lang.created_at')</th>
                    <th>@lang('sms::lang.actions')</th>
                </tr>
            </thead>

            <tbody>
            </tbody>
        </table>
    </div>
    @endcomponent

    <!-- Assign Roles Modal -->
    <div class="modal fade" id="assignRolesModal" tabindex="-1" role="dialog"
        aria-labelledby="assignRolesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="assignRolesModalLabel">
                        <i class="fas fa-user-shield"></i> @lang('sms::lang.assign_roles_to_message')
                    </h5>

                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="rolesContainer">
                        <!-- Roles will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('sms::lang.cancel')</button>
                    <button type="button" class="btn btn-primary" id="saveRolesBtn">
                        <i class="fas fa-save"></i> @lang('sms::lang.save_roles')
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@php $sms_assign_roles_to = __('sms::lang.assign_roles_to'); @endphp

@section('javascript')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#sms-messages-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('sms.messages.data') }}",
                type: 'GET'
            },
            columns: [{
                    data: 'id',
                    name: 'id',
                    width: '5%'
                },
                {
                    data: 'name',
                    name: 'name',
                    width: '15%'
                },
                {
                    data: 'message_template',
                    name: 'message_template',
                    width: '25%',
                    render: function(data) {
                        return data.substring(0, 50) + (data.length > 50 ? '...' : '');
                    }
                },
                {
                    data: 'description',
                    name: 'description',
                    width: '15%',
                    render: function(data) {
                        return data ? data.substring(0, 30) + (data.length > 30 ? '...' : '') : '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: 'roles',
                    name: 'roles',
                    width: '15%',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'status',
                    name: 'status',
                    width: '8%',
                    orderable: false
                },
                {
                    data: 'created_by',
                    name: 'created_by',
                    width: '10%',
                    render: function(data) {
                        return data ? data : '<span class="text-muted">{{ __("sms::lang.system") }}</span>';
                    }
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    width: '12%',
                    render: function(data) {
                        return new Date(data).toLocaleDateString();
                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    width: '15%',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [7, 'desc']
            ],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            language: {
                processing: '<i class="fas fa-spinner fa-spin"></i> {{ __("sms::lang.loading") }}',
                emptyTable: '{{ __("sms::lang.no_sms_messages_found") }}',
                zeroRecords: '{{ __("sms::lang.no_matching_records_found") }}'
            }
        });

        // Assign Roles Modal Handler
        $(document).on('click', '.btn-assign-roles', function() {
            var messageId = $(this).data('id');
            var messageName = $(this).data('name');

            $('#assignRolesModal').data('messageId', messageId);
            $('#assignRolesModalLabel').text('{{ $sms_assign_roles_to }}'.replace(':name', messageName));

            // Load roles
            loadRolesForMessage(messageId);
            $('#assignRolesModal').modal('show');
        });

        // Load roles for message
        function loadRolesForMessage(messageId) {
            $.ajax({
                url: "{{ route('sms.messages.show', ':id') }}".replace(':id', messageId),
                type: 'GET',
                dataType: 'html',
                success: function(data) {
                    // Extract roles from the response
                    var rolesHtml = '<div class="form-group">';
                    rolesHtml += '<label class="font-weight-bold mb-3">{{ __("sms::lang.select_roles") }}</label>';
                    rolesHtml += '<div id="rolesList"></div>';

                    rolesHtml += '</div>';

                    $('#rolesContainer').html(rolesHtml);

                    // Load available roles
                    $.ajax({
                        url: "{{ route('sms.messages.index') }}",
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            action: 'get_roles',
                            message_id: messageId
                        },
                        success: function(response) {
                            var rolesHtml = '';
                            if (response.roles && response.roles.length > 0) {
                                response.roles.forEach(function(role) {
                                    var isChecked = response.assignedRoles.includes(role.id) ? 'checked' : '';
                                    rolesHtml += `
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input role-checkbox" 
                                                   id="role_${role.id}" value="${role.id}" ${isChecked}>
                                            <label class="custom-control-label" for="role_${role.id}">
                                                ${role.name}
                                            </label>
                                        </div>
                                    `;
                                });
                            } else {
                                rolesHtml = '<p class="text-muted">{{ __("sms::lang.no_roles_available") }}</p>';

                            }
                            $('#rolesList').html(rolesHtml);
                        }
                    });
                }
            });
        }

        // Save Roles
        $('#saveRolesBtn').click(function() {
            var messageId = $('#assignRolesModal').data('messageId');
            var selectedRoles = [];

            $('.role-checkbox:checked').each(function() {
                selectedRoles.push($(this).val());
            });

            $.ajax({
                url: "{{ route('sms.messages.assign-roles', ':id') }}".replace(':id', messageId),
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: "{{ csrf_token() }}",
                    roles: selectedRoles
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#assignRolesModal').modal('hide');
                        $('#sms-messages-table').DataTable().ajax.reload();
                    }
                },
                error: function(xhr) {
                    toastr.error('{{ __("sms::lang.error_assigning_roles") }}');
                }
            });
        });

        // Delete Message
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            if (confirm('{{ __("sms::lang.delete_message_confirm") }}')) {
                var form = $(this).closest('form');
                form.submit();
            }

        });
    });
</script>
@endsection