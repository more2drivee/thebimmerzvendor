@php
    $userId = $id ?? request()->route('id');
@endphp
<div class="row mb-3">
    <div class="col-md-12 text-right">
        <button type="button" class="btn btn-primary btn-sm add-leave-btn" data-user-id="{{ $userId }}" data-leave-type="absence">
            <i class="fas fa-plus"></i> @lang('messages.add')
        </button>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>
                <th>@lang('essentials::lang.leave_type')</th>
                <th>@lang('essentials::lang.start_date')</th>
                <th>@lang('essentials::lang.end_date')</th>
                <th>@lang('essentials::lang.reason')</th>
                <th>@lang('essentials::lang.status')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($absences as $absence)
            <tr>
                <td>{{ $absence->leaveType->leave_type ?? '-' }}</td>
                <td>{{ @format_date($absence->start_date) }}</td>
                <td>{{ @format_date($absence->end_date) }}</td>
                <td>{{ $absence->reason ?? '-' }}</td>
                <td>
                    @if($absence->status == 'approved')
                        <span class="label label-success">@lang('essentials::lang.approved')</span>
                    @elseif($absence->status == 'pending')
                        <span class="label label-warning">@lang('essentials::lang.pending')</span>
                    @else
                        <span class="label label-danger">@lang('essentials::lang.rejected')</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted">@lang('lang_v1.no_data')</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
