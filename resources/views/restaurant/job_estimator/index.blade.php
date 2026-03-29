@extends('layouts.app')
@section('title', __('restaurant.job_estimators'))

@section('content')
@include('layouts.booking_nav')
    <!-- Content Header (Page header) -->
    <section class="content-header">
      
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('restaurant.manage_estimators_requests')</h3>
                        <div class="box-tools pull-right">
                            <!-- <button type="button" class="btn btn-primary" id="add_estimator_modal_btn">
                                <i class="fa fa-plus"></i> @lang('restaurant.add_estimator')
                            </button> -->
                        </div>
                    </div>
                    <!-- /.box-header -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="job_estimators_table">
                            <thead>
                                <tr>
                                    <th>@lang('messages.action')</th>
                                    <th>@lang('restaurant.estimate_no')</th>
                                    <th>@lang('restaurant.customer')</th>
                                    <th>@lang('restaurant.vehicle')</th>
                                    <th>@lang('restaurant.location')</th>
                                    <th>@lang('restaurant.estimator_status')</th>
                                    <th>@lang('restaurant.amount')</th>
                                    <th>@lang('restaurant.created_by')</th>
                                    <th>@lang('restaurant.created_at')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->

    <!-- View Modal -->
    <div class="modal fade view_modal" id="view_modal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="viewModalLabel">@lang('messages.view_details')</h4>
                </div>
                <div class="modal-body" id="view_modal_content">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade edit_estimator_modal" id="edit_estimator_modal" tabindex="-1" role="dialog" aria-labelledby="editEstimatorLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="editEstimatorLabel">@lang('messages.edit')</h4>
                </div>
                <div class="modal-body" id="edit_estimator_modal_content">
                    <!-- Edit form will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </div>
        </div>
    </div>

    @include('restaurant.booking.estimator_create')
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    $('#add_estimator_modal_btn').on('click', function() {
        $('#add_estimator_modal').modal('show');
    });

    // Initialize DataTable
    $('#job_estimators_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('job_estimator.index') }}",
            data: function(d) {
                // Add any additional parameters if needed
            }
        },
        columns: [
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            },
            { data: 'estimate_no', name: 'estimate_no' },
            { data: 'customer_name', name: 'contacts.name' },
            { data: 'vehicle_info', name: 'vehicle_info' },
            { data: 'location_name', name: 'business_locations.name' },
            { 
                data: 'status_badge', 
                name: 'estimator_status',
                orderable: true
            },
            {
                data: 'amount',
                name: 'amount',
                orderable: true,
                searchable: false
            },
            { data: 'created_by_name', name: 'users.first_name' },
            { 
                data: 'created_at', 
                name: 'created_at',
                render: function(data, type, row) {
                    return data ? moment(data).format('{{ config('constants.datetime_format') }}') : '';
                }
            }
        ],
        language: {
            url: '{{ asset("vendor/datatables/js/datatables-".app()->getLocale().".json") }}'
        }
    });

    // View estimator modal
    $('#job_estimators_table').on('click', '.view-estimator', function (e) {
        e.preventDefault();
        const estimatorId = $(this).data('id');
        if (!estimatorId) {
            return;
        }

        $('#view_modal').modal('show');
        $('#view_modal_content').html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');

        $.ajax({
            url: `${"{{ url('job-estimator') }}"}/${estimatorId}`,
            method: 'GET',
            success: function (response) {
                $('#view_modal_content').html(response);
            },
            error: function () {
                $('#view_modal').modal('hide');
                toastr.error('{{ __('messages.something_went_wrong') }}');
            }
        });
    });

    // Edit estimator modal
    $('#job_estimators_table').on('click', '.edit-estimator', function (e) {
        e.preventDefault();
        const estimatorId = $(this).data('id');
        if (!estimatorId) {
            return;
        }

        $('#edit_estimator_modal').modal('show');
        $('#edit_estimator_modal_content').html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');

        $.ajax({
            url: `${"{{ url('job-estimator') }}"}/${estimatorId}/edit`,
            method: 'GET',
            success: function (response) {
                $('#edit_estimator_modal_content').html(response);
            },
            error: function () {
                $('#edit_estimator_modal').modal('hide');
                toastr.error('{{ __('messages.something_went_wrong') }}');
            }
        });
    });

    // Handle edit form submission via AJAX
    $(document).on('submit', '#edit_estimator_form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const formData = new FormData(this);
        const $modal = $('#edit_estimator_modal');

        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    $modal.modal('hide');
                    $('#job_estimators_table').DataTable().ajax.reload();
                } else {
                    toastr.error(response.msg || '{{ __('messages.something_went_wrong') }}');
                }
            },
            error: function (xhr) {
                let message = '{{ __('messages.something_went_wrong') }}';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    message = xhr.responseJSON.msg;
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                }
                toastr.error(message);
            }
        });
    });

    // Send SMS action
    $('#job_estimators_table').on('click', '.send-estimator-sms', function (e) {
        e.preventDefault();
        const $el = $(this);
        const estimatorId = $el.data('id');
        const mobile = $el.data('mobile');
        const name = $el.data('name');

        if (!estimatorId || !mobile) {
            toastr.warning('{{ __('messages.something_went_wrong') }}');
            return;
        }

        swal({
            title: '{{ __('restaurant.send_sms_to_customer') }}',
            text: name ? name + ' — ' + mobile : mobile,
            buttons: true,
            icon: 'info'
        }).then((willSend) => {
            if (!willSend) {
                return;
            }

            $.ajax({
                method: 'POST',
                url: '{{ route('job_estimator.send_sms') }}',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    estimator_id: estimatorId
                },
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);
                    } else {
                        toastr.error(result.msg || '{{ __('messages.something_went_wrong') }}');
                    }
                },
                error: function (xhr) {
                    let message = '{{ __('messages.something_went_wrong') }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    toastr.error(message);
                }
            });
        });
    });
});
</script>
@endsection