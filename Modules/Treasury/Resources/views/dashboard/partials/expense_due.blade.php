@component('components.widget', [
    'class' => 'box-primary',
    'icon' => 'fa fa-exclamation-triangle',
    'bg_color' => 'bg-orange'
])
    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
        @lang('treasury::lang.expense_due')
    </p>
    <p class="treasury_expense_due tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
        <span class="display_currency" data-currency_symbol="true">{{ $sales_cards['expense_due'] ?? 0 }}</span>
    </p>
    <small class="treasury_date_range">@lang('home.today')</small>
@endcomponent
