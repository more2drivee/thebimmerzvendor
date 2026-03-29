@component('components.static', [
    'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
<path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
<path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
<path d="M12 3v12"></path>
<path d="M16 11l-4 4l-4 -4"></path>
<path d="M3 12a9 9 0 0 0 18 0"></path>
</svg>',
])
    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
        @lang('home.total_purchase')
    </p>
    <p class="treasury_total_purchase tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
        <span class="display_currency" data-currency_symbol="true">{{ $sales_cards['total_purchase'] ?? 0 }}</span>
    </p>
    <small class="treasury_date_range">@lang('home.today')</small>
@endcomponent
