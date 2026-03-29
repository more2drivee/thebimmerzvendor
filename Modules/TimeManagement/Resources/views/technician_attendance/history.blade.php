@extends('layouts.app')
@section('title', __('timemanagement::technician_attendance.attendance_history'))

@section('content')
@include('timemanagement::partials.nav')

<section class="content-header">
    <h1>@lang('timemanagement::technician_attendance.attendance_history')</h1>
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

    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">@lang('timemanagement::technician_attendance.attendance_records')</h3>
                    <div class="box-tools">
                        <a href="{{ route('timemanagement.index') }}" class="btn btn-sm btn-default">
                            <i class="fa fa-arrow-left"></i> @lang('timemanagement::technician_attendance.back')
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="box-header with-border">
                    <form method="GET" action="{{ route('timemanagement.history') }}" class="form-inline">
                        <div class="form-group">
                            <label for="user_id">@lang('timemanagement::technician_attendance.technician'):</label>
                            <select name="user_id" id="user_id" class="form-control input-sm select2">
                                <option value="">@lang('timemanagement::technician_attendance.all_technicians')</option>
                                @foreach ($technicians as $technician)
                                    <option value="{{ $technician->id }}" {{ ($filters['user_id'] ?? '') == $technician->id ? 'selected' : '' }}>
                                        {{ $technician->first_name }} {{ $technician->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="from_date">@lang('timemanagement::technician_attendance.from_date'):</label>
                            <input type="date" name="from_date" id="from_date" class="form-control input-sm" value="{{ $filters['from_date'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label for="to_date">@lang('timemanagement::technician_attendance.to_date'):</label>
                            <input type="date" name="to_date" id="to_date" class="form-control input-sm" value="{{ $filters['to_date'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label for="per_page">@lang('timemanagement::technician_attendance.per_page'):</label>
                            <select name="per_page" id="per_page" class="form-control input-sm">
                                <option value="15" {{ ($filters['per_page'] ?? 15) == 15 ? 'selected' : '' }}>15</option>
                                <option value="25" {{ ($filters['per_page'] ?? 15) == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ ($filters['per_page'] ?? 15) == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ ($filters['per_page'] ?? 15) == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">@lang('timemanagement::technician_attendance.filter')</button>
                    </form>
                </div>

                <div class="box-body">
                    @if ($attendances->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="attendance_history_table">
                                <thead>
                                    <tr>
                                        <th>@lang('timemanagement::technician_attendance.technician')</th>
                                        <th>@lang('timemanagement::technician_attendance.date')</th>
                                        <th>@lang('timemanagement::technician_attendance.clock_in')</th>
                                        <th>@lang('timemanagement::technician_attendance.clock_out')</th>
                                        <th>@lang('timemanagement::technician_attendance.worked_hours')</th>
                                        <th>@lang('timemanagement::technician_attendance.status')</th>
                                        <th>@lang('timemanagement::technician_attendance.notes')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($attendances as $attendance)
                                        <tr>
                                            <td>{{ $attendance['technician_name'] }}</td>
                                            <td>{{ $attendance['date'] }}</td>
                                            <td>{{ $attendance['clock_in_time'] ? date('Y-m-d H:i', strtotime($attendance['clock_in_time'])) : __('timemanagement::technician_attendance.not_available') }}</td>
                                            <td>{{ $attendance['clock_out_time'] ? date('Y-m-d H:i', strtotime($attendance['clock_out_time'])) : __('timemanagement::technician_attendance.not_available') }}</td>
                                            <td>
                                                @if ($attendance['worked_hours'])
                                                    <span class="badge bg-blue">{{ $attendance['worked_hours'] }}h {{ $attendance['worked_minutes'] % 60 }}m</span>
                                                @else
                                                    <span class="text-muted">@lang('timemanagement::technician_attendance.not_available')</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($attendance['status'] == 'completed')
                                                    <span class="label label-success">{{ __('timemanagement::lang.' . $attendance['status']) }}</span>
                                                @else
                                                    <span class="label label-warning">{{ ucfirst($attendance['status']) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($attendance['clock_in_note'] || $attendance['clock_out_note'])
                                                    <small>
                                                        @if ($attendance['clock_in_note'])
                                                            <strong>@lang('timemanagement::technician_attendance.in'):</strong> {{ $attendance['clock_in_note'] }}<br>
                                                        @endif
                                                        @if ($attendance['clock_out_note'])
                                                            <strong>@lang('timemanagement::technician_attendance.out'):</strong> {{ $attendance['clock_out_note'] }}
                                                        @endif
                                                    </small>
                                                @else
                                                    <span class="text-muted">@lang('timemanagement::technician_attendance.not_available')</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="text-center">
                            {{ $attendances->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">@lang('timemanagement::technician_attendance.no_attendance_records_found')</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    $(document).ready(function() {
        $('.select2').select2();
        $('#attendance_history_table').DataTable({
            "ordering": true,
            "searching": true,
            "paging": false,
            "responsive": true,
            "info": false,
            "columnDefs": [
                {
                    "targets": [4, 5, 6],
                    "orderable": false
                }
            ]
        });
    });
</script>
@endsection
