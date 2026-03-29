@php
    $userId = $id ?? request()->route('id');
@endphp
<div class="row mb-3">
    <div class="col-md-12 text-right">
        <button type="button" class="btn btn-primary btn-sm add-leave-btn" data-user-id="{{ $userId }}">
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
            @forelse($leaves as $leave)
            <tr>
                <td>{{ $leave->leaveType->leave_type ?? '-' }}</td>
                <td>{{ @format_date($leave->start_date) }}</td>
                <td>{{ @format_date($leave->end_date) }}</td>
                <td>{{ $leave->reason ?? '-' }}</td>
                <td>
                    @if($leave->status == 'approved')
                        <span class="label label-success">@lang('essentials::lang.approved')</span>
                    @elseif($leave->status == 'pending')
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
