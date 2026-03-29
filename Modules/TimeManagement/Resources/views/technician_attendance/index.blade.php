@extends('layouts.app')
@section('title', __('timemanagement::technician_attendance.title'))

@section('content')
@include('timemanagement::partials.nav')

<section class="content-header">
    <h1>@lang('timemanagement::technician_attendance.title')</h1>
</section>

<section class="content">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ session('error') }}
        </div>
    @endif
    @if (isset($message))
        <div class="alert alert-info alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ $message }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">@lang('timemanagement::technician_attendance.title')</h3>
                    <div class="box-tools">
                        <!-- Date Filter -->
                        <form method="GET" action="{{ route('timemanagement.index') }}" class="form-inline">
                            <div class="form-group">
                                <label for="date">@lang('timemanagement::technician_attendance.date'):</label>
                                <input type="date" name="date" id="date" class="form-control input-sm" value="{{ $date }}">
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">@lang('timemanagement::technician_attendance.filter')</button>
                        </form>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-3 col-xs-6">
                            <div class="small-box bg-green">
                                <div class="inner">
                                    <h3>{{ $present_count }}</h3>
                                    <p>@lang('timemanagement::lang.present')</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <div class="small-box bg-red">
                                <div class="inner">
                                    <h3>{{ $absent_count }}</h3>
                                    <p>@lang('timemanagement::lang.absent')</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <div class="small-box bg-yellow">
                                <div class="inner">
                                    <h3>{{ $on_leave_count }}</h3>
                                    <p>@lang('timemanagement::lang.on_leave')</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-user-times"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <div class="small-box bg-blue">
                                <div class="inner">
                                    <h3>{{ count($technicians) }}</h3>
                                    <p>@lang('timemanagement::technician_attendance.total_technicians')</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">@lang('timemanagement::lang.technicians')</h3>
                    <div class="box-tools">
                        <div class="btn-group">
                            <a href="{{ route('timemanagement.clock_in_form') }}" class="btn btn-sm btn-success">
                                <i class="fa fa-sign-in"></i> @lang('timemanagement::technician_attendance.clock_in')
                            </a>
                            <a href="{{ route('timemanagement.clock_out_form') }}" class="btn btn-sm btn-danger">
                                <i class="fa fa-sign-out"></i> @lang('timemanagement::technician_attendance.clock_out')
                            </a>
                            <a href="{{ route('timemanagement.history') }}" class="btn btn-sm btn-info">
                                <i class="fa fa-history"></i> @lang('timemanagement::technician_attendance.history')
                            </a>
                            <button type="button" class="btn btn-sm btn-success" id="bulkClockInBtn" data-toggle="modal" data-target="#bulkClockInModal">
                                <i class="fa fa-users"></i> @lang('timemanagement::technician_attendance.bulk_clock_in')
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" id="bulkClockOutBtn" data-toggle="modal" data-target="#bulkClockOutModal">
                                <i class="fa fa-users"></i> @lang('timemanagement::technician_attendance.bulk_clock_out')
                            </button>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="technician_attendance_table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select_all_technicians"></th>
                                    <th>@lang('timemanagement::lang.name')</th>
                                    <th>@lang('timemanagement::technician_attendance.email')</th>
                                    <th>@lang('timemanagement::technician_attendance.clock_in')</th>
                                    <th>@lang('timemanagement::technician_attendance.clock_out')</th>
                                    <th>@lang('timemanagement::technician_attendance.worked_hours')</th>
                                    <th>@lang('timemanagement::technician_attendance.attendance_status')</th>
                                    <th>@lang('timemanagement::technician_attendance.job_count')</th>
                                    <th>@lang('timemanagement::technician_attendance.actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($technicians as $technician)
                                    <tr>
                                        <td><input type="checkbox" class="tech-checkbox" value="{{ $technician['user_id'] }}"></td>
                                        <td>{{ $technician['name'] }}</td>
                                        <td>{{ $technician['email'] }}</td>
                                        <td>
                                            @if($technician['clock_in_time'])
                                                <span class="label label-info">{{ date('H:i', strtotime($technician['clock_in_time'])) }}</span>
                                            @else
                                                <span class="text-muted">@lang('timemanagement::technician_attendance.not_available')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($technician['clock_out_time'])
                                                <span class="label label-warning">{{ date('H:i', strtotime($technician['clock_out_time'])) }}</span>
                                            @else
                                                <span class="text-muted">@lang('timemanagement::technician_attendance.not_available')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($technician['worked_hours'] && $technician['worked_hours']['hours'] >= 0)
                                                <span class="badge bg-green">{{ $technician['worked_hours']['hours'] }}h {{ $technician['worked_hours']['minutes'] }}m</span>
                                            @else
                                                <span class="text-muted">@lang('timemanagement::technician_attendance.not_available')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($technician['attendance_status'] == 'present')
                                                <span class="label label-success">{{ __('timemanagement::technician_attendance.present') }}</span>
                                            @elseif($technician['attendance_status'] == 'absent')
                                                <span class="label label-danger">{{ __('timemanagement::technician_attendance.absent') }}</span>
                                            @elseif($technician['attendance_status'] == 'on_leave')
                                                <span class="label label-warning">{{ __('timemanagement::technician_attendance.on_leave') }}</span>
                                            @else
                                                <span class="label label-default">{{ ucfirst($technician['attendance_status']) }}</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-blue">{{ $technician['job_count'] }}</span></td>
                                        <td>
                                            <div class="btn-group">
                                                @if(empty($technician['clock_in_time']))
                                                    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#clockInModal" 
                                                        data-user-id="{{ $technician['user_id'] }}" 
                                                        data-user-name="{{ $technician['name'] }}" 
                                                        title="@lang('timemanagement::lang.clock_in')">
                                                        @lang('timemanagement::lang.clock_in')
                                                        <i class="fa fa-sign-in"></i>
                                                    </button>
                                                @endif

                                                @if(!empty($technician['clock_in_time']) && empty($technician['clock_out_time']))
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#clockOutModal" 
                                                        data-user-id="{{ $technician['user_id'] }}" 
                                                        data-user-name="{{ $technician['name'] }}" 
                                                        title="@lang('timemanagement::lang.clock_out')">
                                                        <i class="fa fa-sign-out"></i>
                                                        @lang('timemanagement::lang.clock_out')
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

<!-- Clock In Modal -->
<div class="modal fade" id="clockInModal" tabindex="-1" role="dialog" aria-labelledby="clockInModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="clockInModalLabel">@lang('lang_v1.clock_in_technician')</h4>
            </div>
            <form method="POST" action="{{ route('timemanagement.clock_in') }}">
                @csrf
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="form-group">
                        <label for="modal_user_name">@lang('lang_v1.technician')</label>
                        <input type="text" class="form-control" id="modal_user_name" readonly>
                        <input type="hidden" name="user_id" id="modal_user_id" value="">
                    </div>
                    <div class="form-group">
                        <label for="clock_in_time">@lang('lang_v1.clock_in_time')</label>
                        <input type="datetime-local" name="clock_in_time" id="clock_in_time" class="form-control" value="{{ date('Y-m-d\TH:i') }}">
                    </div>
                    <div class="form-group">
                        <label for="note">@lang('lang_v1.note')</label>
                        <textarea name="note" id="note" class="form-control" rows="3" placeholder="@lang('lang_v1.add_note')"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="submit" class="btn btn-primary">@lang('lang_v1.clock_in')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Clock Out Modal -->
<div class="modal fade" id="clockOutModal" tabindex="-1" role="dialog" aria-labelledby="clockOutModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="clockOutModalLabel">@lang('lang_v1.clock_out_technician')</h4>
            </div>
            <form method="POST" action="{{ route('timemanagement.clock_out') }}">
                @csrf
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="form-group">
                        <label for="modal_user_name_out">@lang('lang_v1.technician')</label>
                        <input type="text" class="form-control" id="modal_user_name_out" readonly>
                        <input type="hidden" name="user_id" id="modal_user_id_out">
                    </div>
                    <div class="form-group">
                        <label for="clock_out_time">@lang('lang_v1.clock_out_time')</label>
                        <input type="datetime-local" name="clock_out_time" id="clock_out_time" class="form-control" value="{{ date('Y-m-d\TH:i') }}">
                    </div>
                    <div class="form-group">
                        <label for="note_out">@lang('lang_v1.note')</label>
                        <textarea name="note" id="note_out" class="form-control" rows="3" placeholder="@lang('lang_v1.add_note')"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="submit" class="btn btn-primary">@lang('lang_v1.clock_out')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Clock In Modal -->
<div class="modal fade" id="bulkClockInModal" tabindex="-1" role="dialog" aria-labelledby="bulkClockInModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="bulkClockInModalLabel">@lang('timemanagement::lang.bulk_clock_in')</h4>
            </div>
            <form id="bulkClockInForm" method="POST" action="{{ route('timemanagement.bulk_clock_in') }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        @lang('timemanagement::lang.bulk_clock_in_help')
                    </div>
                    <div class="form-group">
                        <label for="bulk_clock_in_time">@lang('lang_v1.clock_in_time')</label>
                        <input type="datetime-local" name="clock_in_time" id="bulk_clock_in_time" class="form-control" value="{{ date('Y-m-d\TH:i') }}">
                    </div>
                    <div class="form-group">
                        <label for="bulk_clock_in_note">@lang('lang_v1.note')</label>
                        <textarea name="note" id="bulk_clock_in_note" class="form-control" rows="3" placeholder="@lang('lang_v1.add_note')"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="submit" class="btn btn-primary">@lang('timemanagement::lang.bulk_clock_in')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Clock Out Modal -->
<div class="modal fade" id="bulkClockOutModal" tabindex="-1" role="dialog" aria-labelledby="bulkClockOutModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="bulkClockOutModalLabel">@lang('timemanagement::lang.bulk_clock_out')</h4>
            </div>
            <form id="bulkClockOutForm" method="POST" action="{{ route('timemanagement.bulk_clock_out') }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        @lang('timemanagement::lang.bulk_clock_out_help')
                    </div>
                    <div class="form-group">
                        <label for="bulk_clock_out_time">@lang('lang_v1.clock_out_time')</label>
                        <input type="datetime-local" name="clock_out_time" id="bulk_clock_out_time" class="form-control" value="{{ date('Y-m-d\TH:i') }}">
                    </div>
                    <div class="form-group">
                        <label for="bulk_clock_out_note">@lang('lang_v1.note')</label>
                        <textarea name="note" id="bulk_clock_out_note" class="form-control" rows="3" placeholder="@lang('lang_v1.add_note')"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="submit" class="btn btn-primary">@lang('timemanagement::lang.bulk_clock_out')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {

        // === CLOCK IN MODAL ===
        $('#clockInModal').on('show.bs.modal', function (event) {

            var button = $(event.relatedTarget);

            // Use .attr() to read raw HTML attribute (not jQuery .data cache)
            var userId = button.attr('data-user-id');
            var userName = button.attr('data-user-name');


            if (!userId || userId.trim() === '') {
                console.error('❌ ERROR: data-user-id is missing or empty!');
                alert('Technician ID not found. Please refresh the page.');
                return;
            }

            var modal = $(this);
            modal.find('#modal_user_id').val(userId);
            modal.find('#modal_user_name').val(userName);

        });

        // === CLOCK OUT MODAL ===
        $('#clockOutModal').on('show.bs.modal', function (event) {

            var button = $(event.relatedTarget);

            var userId = button.attr('data-user-id');
            var userName = button.attr('data-user-name');


            if (!userId || userId.trim() === '') {
           
                alert('Technician ID not found. Please refresh the page.');
                return;
            }

            var modal = $(this);
            modal.find('#modal_user_id_out').val(userId);
            modal.find('#modal_user_name_out').val(userName);

        });

        // Clear modals on close
        $('#clockInModal, #clockOutModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            $(this).find('.alert-danger').remove();
        });

        // === PREVENT SUBMIT IF MISSING ===
        $('#clockInModal form').on('submit', function(e) {

            var userIdInput = $('#modal_user_id');
            var userId = userIdInput.val();


            if (!userId || userId.trim() === '') {
                e.preventDefault();
                console.error('❌ ABORTING: user_id is empty in Clock In form!');
                alert('Error: Technician ID is missing. Cannot submit.');
                return false;
            }

        });

        $('#clockOutModal form').on('submit', function(e) {

            var userId = $('#modal_user_id_out').val();


            if (!userId || userId.trim() === '') {
                e.preventDefault();
                console.error('❌ ABORTING: user_id is empty in Clock Out form!');
                alert('Error: Technician ID is missing. Cannot submit.');
                return false;
            }

        });

        // Initialize DataTable
        $('#technician_attendance_table').DataTable({
            "ordering": true,
            "searching": true,
            "paging": true,
      
            "columnDefs": [
                {
                    "targets": -1,
                    "orderable": false,
                    "searchable": false
                }
            ]
        });

        // === BULK SELECTION ===
        function getSelectedTechnicianIds() {
            var ids = [];
            $('.tech-checkbox:checked').each(function() {
                var val = $(this).val();
                if (val) ids.push(val);
            });
            return ids;
        }

        $('#select_all_technicians').on('change', function() {
            var checked = $(this).is(':checked');
            $('.tech-checkbox').prop('checked', checked);
        });

        $('#bulkClockInBtn, #bulkClockOutBtn').on('click', function(e) {
            var ids = getSelectedTechnicianIds();
            if (ids.length === 0) {
                e.preventDefault();
                alert('@lang('messages.select_at_least_one')');
                return false;
            }
        });

        // Inject selected IDs into bulk forms on submit
        $('#bulkClockInForm').on('submit', function(e) {
            var ids = getSelectedTechnicianIds();
            if (ids.length === 0) {
                e.preventDefault();
                alert('@lang('messages.select_at_least_one')');
                return false;
            }
            var form = $(this);
            form.find('.bulk-user-id').remove();
            ids.forEach(function(id) {
                form.append('<input type="hidden" class="bulk-user-id" name="user_ids[]" value="' + id + '">');
            });
        });

        $('#bulkClockOutForm').on('submit', function(e) {
            var ids = getSelectedTechnicianIds();
            if (ids.length === 0) {
                e.preventDefault();
                alert('@lang('messages.select_at_least_one')');
                return false;
            }
            var form = $(this);
            form.find('.bulk-user-id').remove();
            ids.forEach(function(id) {
                form.append('<input type="hidden" class="bulk-user-id" name="user_ids[]" value="' + id + '">');
            });
        });

    });
</script>
@endsection