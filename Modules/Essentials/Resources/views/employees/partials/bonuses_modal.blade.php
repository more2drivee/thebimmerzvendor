@php
    $userId = $id ?? request()->route('id');
@endphp
<div class="row mb-3">
    <div class="col-md-12 text-right">
        <button type="button" class="btn btn-primary btn-sm add-bonus-btn" data-user-id="{{ $userId }}">
            <i class="fas fa-plus"></i> @lang('messages.add')
        </button>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>
                <th>@lang('essentials::lang.description')</th>
                <th>@lang('sale.amount')</th>
                <th>@lang('essentials::lang.amount_type')</th>
                <th>@lang('essentials::lang.apply_on_payroll')</th>
                <th>@lang('essentials::lang.start_date')</th>
                <th>@lang('essentials::lang.end_date')</th>
                <th>@lang('essentials::lang.status')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bonuses as $bonus)
            <tr>
                <td>{{ $bonus->description }}</td>
                <td>
                    @if($bonus->amount_type == 'percent')
                        {{ $bonus->amount }}%
                    @else
                        <span class="display_currency" data-currency_symbol="true">{{ $bonus->amount }}</span>
                    @endif
                </td>
                <td>
                    @if($bonus->amount_type == 'fixed')
                        @lang('lang_v1.fixed')
                    @else
                        @lang('lang_v1.percentage')
                    @endif
                </td>
                <td>
                    @if($bonus->apply_on == 'next_payroll')
                        @lang('essentials::lang.next_payroll')
                    @elseif($bonus->apply_on == 'after_next')
                        @lang('essentials::lang.after_next')
                    @else
                        @lang('essentials::lang.every_payroll')
                    @endif
                </td>
                <td>{{ $bonus->start_date ? @format_date($bonus->start_date) : '-' }}</td>
                <td>{{ $bonus->end_date ? @format_date($bonus->end_date) : '-' }}</td>
                <td>
                    @if($bonus->status == 'active')
                        <span class="label label-success">@lang('sale.active')</span>
                    @else
                        <span class="label label-default">@lang('essentials::lang.cancelled')</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted">@lang('lang_v1.no_data')</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
