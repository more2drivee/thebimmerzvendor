@component('components.static', [
    'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                    stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                    <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                    <path d="M17 17h-11v-14h-2" />
                    <path d="M6 5l14 1l-1 7h-13" />
                </svg>', 'svg_bg' => 'tw-bg-sky-100',
])
    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
        @lang('home.total_sell')
    </p>
    <p class="treasury_total_sell tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
        <span class="display_currency" data-currency_symbol="true">{{ $sales_cards['total_sell'] ?? 0 }}</span>
    </p>
    <small class="treasury_date_range">@lang('home.today')</small>
@endcomponent
