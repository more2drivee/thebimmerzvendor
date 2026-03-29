<div class="row mb-3">
    <div class="col-md-12 text-right">
        <button type="button" class="btn btn-primary btn-sm add-attendance-btn" data-user-id="{{ $id }}">
            <i class="fas fa-plus"></i> @lang('messages.add')
        </button>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>
                <th>@lang('lang_v1.date')</th>
                <th>@lang('essentials::lang.clock_in')</th>
                <th>@lang('essentials::lang.clock_out')</th>
                <th>@lang('essentials::lang.work_duration')</th>
                <th>@lang('essentials::lang.ip_address')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $attendance)
            <tr>
                <td>{{ @format_date($attendance->clock_in_time) }}</td>
                <td>{{ @format_time($attendance->clock_in_time) }}</td>
                <td>{{ $attendance->clock_out_time ? @format_time($attendance->clock_out_time) : '-' }}</td>
                <td>
                    @if($attendance->clock_out_time)
                        @php
                            $diff = \Carbon\Carbon::parse($attendance->clock_in_time)->diff(\Carbon\Carbon::parse($attendance->clock_out_time));
                            echo $diff->format('%H:%I:%S');
                        @endphp
                    @else
                        -
                    @endif
                </td>
                <td>{{ $attendance->ip_address ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted">@lang('lang_v1.no_data')</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
