@component('components.widget', [
    'class' => 'box-primary',
    'icon' => 'fa fa-undo',
    'bg_color' => 'bg-yellow'
])
    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
        @lang('lang_v1.total_purchase_return')
    </p>
    <p class="treasury_total_purchase_return tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
        <span class="display_currency" data-currency_symbol="true">{{ $sales_cards['total_purchase_return'] ?? 0 }}</span>
    </p>
    <small class="treasury_date_range">@lang('home.today')</small>
@endcomponent
