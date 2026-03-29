@php
    $userId = $id ?? request()->route('id');
@endphp
<div class="row mb-3">
    <div class="col-md-12 text-right">
        <button type="button" class="btn btn-primary btn-sm add-advance-btn" data-user-id="{{ $userId }}">
            <i class="fas fa-plus"></i> @lang('messages.add')
        </button>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>
                <th>@lang('sale.amount')</th>
                <th>@lang('essentials::lang.reason')</th>
                <th>@lang('essentials::lang.request_date')</th>
                <th>@lang('essentials::lang.deduct_from_month')</th>
                <th>@lang('essentials::lang.status')</th>
                <th>@lang('essentials::lang.approved_by')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($advances as $advance)
            <tr>
                <td><span class="display_currency" data-currency_symbol="true">{{ $advance->amount }}</span></td>
                <td>{{ $advance->reason ?? '-' }}</td>
                <td>{{ @format_date($advance->request_date) }}</td>
                <td>{{ $advance->deduct_from_payroll ? \Carbon\Carbon::parse($advance->deduct_from_payroll)->format('F Y') : '-' }}</td>
                <td>
                    @if($advance->status == 'approved')
                        <span class="label label-success">@lang('essentials::lang.approved')</span>
                    @elseif($advance->status == 'pending')
                        <span class="label label-warning">@lang('essentials::lang.pending')</span>
                    @else
                        <span class="label label-danger">@lang('essentials::lang.rejected')</span>
                    @endif
                </td>
                <td>{{ $advance->approvedBy->full_name ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted">@lang('lang_v1.no_data')</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
