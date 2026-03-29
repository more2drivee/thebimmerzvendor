<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>
                <th>@lang('purchase.ref_no')</th>
                <th>@lang('essentials::lang.month_year')</th>
                <th>@lang('sale.total_amount')</th>
                <th>@lang('essentials::lang.status')</th>
                <th>@lang('lang_v1.date')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
            <tr>
                <td>{{ $payment->ref_no }}</td>
                <td>{{ $payment->transaction_date ? \Carbon\Carbon::parse($payment->transaction_date)->format('F Y') : '-' }}</td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $payment->final_total }}</span></td>
                <td>
                    @if($payment->payment_status == 'paid')
                        <span class="label label-success">@lang('lang_v1.paid')</span>
                    @elseif($payment->payment_status == 'partial')
                        <span class="label label-warning">@lang('lang_v1.partial')</span>
                    @else
                        <span class="label label-danger">@lang('lang_v1.due')</span>
                    @endif
                </td>
                <td>{{ @format_date($payment->transaction_date) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted">@lang('lang_v1.no_data')</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
