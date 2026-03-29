@extends('layouts.app')
@section('title', __('treasury::lang.treasury'))

@section('content')
@include('treasury::layouts.nav')
<!-- Content Header (Page header) -->
{{-- <section class="content-header">
    <h1>{{ __('treasury::lang.treasury') }}
        <small>{{ __('treasury::lang.dashboard') }}</small>
    </h1>
</section> --}}

<!-- Main content -->
<section class="content">
    <!-- Date Filter -->
    <div class="row">
        <div class="col-md-12">
            <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
                <h1 class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ __('treasury::lang.treasury') }} {{ __('treasury::lang.dashboard') }}</h1>
                <div class="tw-flex tw-items-center tw-gap-3">
                    @if(!empty($business_locations) && count($business_locations) > 0)
                    <!-- Branch Filter -->
                    <select id="treasury_branch_filter" class="tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-900 tw-bg-white tw-rounded-lg tw-border tw-border-gray-300 hover:tw-bg-gray-50">
                        @foreach($business_locations as $location_id => $location_name)
                            @if($location_id === '' && !auth()->user()->can('access_all_locations'))
                                @continue
                            @endif
                            <option value="{{ $location_id }}" {{ isset($initial_location_id) && $location_id == $initial_location_id ? 'selected' : '' }}>{{ $location_name }}</option>
                        @endforeach
                    </select>
                    @endif
                    <!-- Date Filter -->
                    <button type="button" id="treasury_date_filter"
                        class="tw-inline-flex tw-items-center tw-justify-center tw-gap-1 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-900 tw-transition-all tw-duration-200 tw-bg-white tw-rounded-lg tw-border tw-border-gray-300 hover:tw-bg-gray-50">
                        <svg aria-hidden="true" class="tw-size-5" xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                            <path d="M16 3v4" />
                            <path d="M8 3v4" />
                            <path d="M4 11h16" />
                        </svg>
                        <span>{{ __('messages.filter_by_date') }}</span>
                        <svg aria-hidden="true" class="tw-size-4" xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M6 9l6 6l6 -6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Cards -->
    <div class="tw-grid tw-grid-cols-1 tw-gap-4 tw-mt-6 sm:tw-grid-cols-2 xl:tw-grid-cols-4 sm:tw-gap-5" id="dashboard_cards">
        <!-- Sales Cards Row -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                            <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                            <path d="M17 17h-11v-14h-2" />
                            <path d="M6 5l14 1l-1 7h-13" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('home.total_sell') }}
                        </p>
                        <p class="treasury_total_sell tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['total_sell'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-text-yellow-500 tw-bg-yellow-100 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                            <path d="M9 7l1 0" />
                            <path d="M9 13l6 0" />
                            <path d="M13 17l2 0" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.total_sales_due') }}
                        </p>
                        <p class="treasury_invoice_due tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['invoice_due'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-text-red-500 tw-bg-red-100 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M21 7l-18 0" />
                            <path d="M18 10l3 -3l-3 -3" />
                            <path d="M6 20l-3 -3l3 -3" />
                            <path d="M3 17l18 0" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('lang_v1.total_sell_return') }}
                        </p>
                        <p class="treasury_total_sell_return tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['total_sell_return'] ?? 0 }}</span>
                        </p>
                        <small class="tw-text-xs tw-text-gray-400">
                            <span class="tw-text-green-600">{{ __('treasury::lang.paid') }}: <span class="treasury_sell_return_paid display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['sell_return_paid'] ?? 0 }}</span></span>
                            <span class="tw-ml-2 tw-text-yellow-600">{{ __('treasury::lang.due') }}: <span class="treasury_sell_return_due display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['sell_return_due'] ?? 0 }}</span></span>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-emerald-100 tw-text-emerald-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M6 5h10a2 2 0 0 1 2 2v10l-2 -1l-2 1l-2 -1l-2 1l-2 -1l-2 1v-11a2 2 0 0 1 2 -2z" />
                            <path d="M9 9h6" />
                            <path d="M9 13h3" />
                            <path d="M15 17l2 2l4 -4" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.selling_paid') }}
                        </p>
                        <p class="treasury_selling_paid tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['selling_paid'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Row -->
    <div class="tw-grid tw-grid-cols-1 tw-gap-4 tw-mt-4 sm:tw-grid-cols-2 xl:tw-grid-cols-4 sm:tw-gap-5">
        <!-- Total Purchase -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M12 3v12"></path>
                            <path d="M16 11l-4 4l-4 -4"></path>
                            <path d="M3 12a9 9 0 0 0 18 0"></path>
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('home.total_purchase') }}
                        </p>
                        <p class="treasury_total_purchase tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['total_purchase'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Due -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-text-yellow-500 tw-bg-yellow-100 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 9v4" />
                            <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                            <path d="M12 16h.01" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('home.purchase_due') }}
                        </p>
                        <p class="treasury_purchase_due tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['purchase_due'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Return -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-text-red-500 tw-bg-red-100 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2" />
                            <path d="M15 14v-2a2 2 0 0 0 -2 -2h-4l2 -2m0 4l-2 -2" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('lang_v1.total_purchase_return') }}
                        </p>
                        <p class="treasury_total_purchase_return tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['total_purchase_return'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Paid -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-emerald-100 tw-text-emerald-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M6 6h13l-1 7h-11l-1 -7z" />
                            <path d="M6 6l-1 -2h-2" />
                            <path d="M10 19a1 1 0 1 0 0 .01" />
                            <path d="M17 19a1 1 0 1 0 0 .01" />
                            <path d="M16 13l2 2l4 -4" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.purchase_paid') }}
                        </p>
                        <p class="treasury_purchase_paid tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['purchase_paid'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Row -->
    <div class="tw-grid tw-grid-cols-1 tw-gap-4 tw-mt-4 sm:tw-grid-cols-2 xl:tw-grid-cols-4 sm:tw-gap-4">
        <!-- Total Expense -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-text-red-500 tw-bg-red-100 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"></path>
                            <path d="M14.8 8a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1"></path>
                            <path d="M12 6v10"></path>
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.total_expense') }}
                        </p>
                        <p class="treasury_total_expense tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['total_expense'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expense Due -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-text-orange-500 tw-bg-orange-100 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 9v4" />
                            <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                            <path d="M12 16h.01" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.expense_due') }}
                        </p>
                        <p class="treasury_expense_due tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['expense_due'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Expense Paid -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-emerald-100 tw-text-emerald-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 7a2 2 0 0 1 2 -2h9a2 2 0 0 1 2 2v3h-13a2 2 0 0 1 -2 -2z" />
                            <path d="M4 12h14a2 2 0 0 1 2 2v3a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
                            <path d="M14 16l2 2l4 -4" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.expense_paid') }}
                        </p>
                        <p class="treasury_expense_paid tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['expense_paid'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>
            <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-amber-100 tw-text-amber-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 3v18" />
                            <path d="M9 9h6" />
                            <path d="M9 15h6" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.under_processing_sell') }}
                        </p>
                        <p class="treasury_under_processing_sell_card tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['under_processing_sell'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Row -->
    <div class="tw-grid tw-grid-cols-1 tw-gap-4 tw-mt-4 sm:tw-grid-cols-2 xl:tw-grid-cols-4 sm:tw-gap-5">
        <!-- Total Payroll -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-indigo-100 tw-text-indigo-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path>
                            <path d="M12 7v5l3 3"></path>
                            <path d="M9 16h6"></path>
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.total_payroll') }}
                        </p>
                        <p class="treasury_total_payroll tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['total_payroll'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payroll Paid -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-emerald-100 tw-text-emerald-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M5 7a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-10z"></path>
                            <path d="M9 14l2 2l4 -4"></path>
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.payroll_paid') }}
                        </p>
                        <p class="treasury_payroll_paid tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['payroll_paid'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payroll Due -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-text-yellow-500 tw-bg-yellow-100 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M12 9v4"></path>
                            <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"></path>
                            <path d="M12 16h.01"></path>
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.payroll_due') }}
                        </p>
                        <p class="treasury_payroll_due tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['sales_cards']['payroll_due'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Treasury Summary Cards (Horizontal) -->
    <div class="d-flex gap-3 my-4 mx-3 gap-sm-4 mx-sm-4">
        <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-green-100 tw-text-green-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                            <path d="M14.8 9a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1" />
                            <path d="M12 7v10" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.total_income') }}
                        </p>
                        <p class="treasury_total_income tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['total_income'] ?? 0 }}</span>
                        </p>
                   
                    </div>
                    <div class="tw-flex tw-flex-col tw-items-center tw-gap-1">
                        <a href="{{ route('treasury.income') }}" class="btn btn-success btn-sm rounded-circle" title="{{ __('treasury::lang.add_income') }}">
                            <i class="fas fa-plus"></i>
                        </a>
                        <span class="tw-text-xs tw-text-gray-500 tw-font-medium">{{ __('treasury::lang.income') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-red-100 tw-text-red-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12" />
                            <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.total_expense') }}
                        </p>
                        <p class="treasury_total_expense_main tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['total_expense'] ?? 0 }}</span>
                        </p>
                    </div>
                    <div class="tw-flex tw-flex-col tw-items-center tw-gap-1">
                        <a href="{{ route('treasury.expense') }}" class="btn btn-danger btn-sm rounded-circle" title="{{ __('treasury::lang.add_expense') }}">
                            <i class="fas fa-plus"></i>
                        </a>
                        <span class="tw-text-xs tw-text-gray-500 tw-font-medium">{{ __('treasury::lang.expense') }}</span>
                    </div>
                </div>
            </div>
        </div>
     
        <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-green-100 tw-text-green-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 3v18" />
                            <path d="M6 18h12" />
                        </svg>
                    </div>
                 
                      <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.cash_in_hand') }}
                        </p>
                        <p class="treasury_under_processing_sell tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['cashe_in_hand'] ?? 0 }}</span>
                        </p>
                   
                    </div>
                    <div class="tw-flex tw-gap-2">
                        <div class="tw-flex tw-flex-col tw-items-center tw-gap-1">
                            <button type="button" class="btn btn-info btn-sm rounded-circle" onclick="window.location.href='{{ route('treasury.internal.transfers.index') }}'" title="Manage Internal Transfers">
                                <i class="fas fa-list"></i>
                            </button>
                            <span class="tw-text-xs tw-text-gray-500 tw-font-medium">{{ __('treasury::lang.manage') }}</span>
                        </div>
                        <div class="tw-flex tw-flex-col tw-items-center tw-gap-1">
                            <button type="button" class="btn btn-primary btn-sm rounded-circle" data-toggle="modal" data-target="#internal_transfer_modal" title="{{ __('treasury::lang.internal_transfer') }}">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            <span class="tw-text-xs tw-text-gray-500 tw-font-medium">{{ __('treasury::lang.internal_transfer') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <div class="d-flex gap-3 my-4 mx-3 mx-lg-5" style="display: flex;">
        <!-- Card 1: Total Profit -->
        <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-emerald-100 tw-text-emerald-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M7 9a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                            <path d="M7 9v10a1 1 0 0 0 1 1h8a1 1 0 0 0 1 -1v-10" />
                            <path d="M12 6v3" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.total_profit') }}
                        </p>
                        <p class="treasury_total_profit tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['total_profit'] ?? 0 }}</span>
                        </p>
                                <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Real Balance -->
        <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-text-purple-500 tw-bg-purple-100 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 6v12" />
                            <path d="M9 12h6" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.real_balance') }}
                        </p>
                        <p class="treasury_real_balance tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['real_balance'] ?? 0 }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Job Sheet Expenses -->
        <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-rose-100 tw-text-rose-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 3v18" />
                            <path d="M4 12h16" />
                            <path d="M4 6h16" />
                            <path d="M4 18h16" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.job_sheet_expenses') }}
                        </p>
                        <p class="treasury_job_sheet_expenses tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['job_sheet_expenses'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Virtual Products Profit -->
        <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-cyan-100 tw-text-cyan-500">
                        <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                            <path d="M10 11l4 4" />
                            <path d="M14 11l-4 4" />
                        </svg>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                            {{ __('treasury::lang.virtual_products_profit') }}
                        </p>
                        <p class="treasury_virtual_products_profit tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            <span class="display_currency" data-currency_symbol="true">{{ $summary['virtual_products_profit'] ?? 0 }}</span>
                        </p>
                        <small class="treasury_date_range">@lang('home.today')</small>
                    </div>
                </div>
            </div>
        </div>

    
 
    </div>

    <!-- Spare Parts Profit Cards Section (Separated) -->
    <div class="tw-border-t-2 tw-border-gray-200 tw-mt-6 tw-pt-6">
        <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4 tw-ml-3">{{ __('treasury::lang.spare_parts_profit') }}</h3>
        <div class="d-flex gap-3 my-4 mx-3 mx-lg-5" style="display: flex; flex-wrap: wrap;">
            <!-- Card 1: Total Profit Invoice (Spare Parts) -->
            <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200" style="min-width: 250px;">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-indigo-100 tw-text-indigo-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                <path d="M9 7l1 0" />
                                <path d="M9 13l6 0" />
                                <path d="M13 17l2 0" />
                                <circle cx="12" cy="12" r="1" />
                                <path d="M8 17l4 -4l4 4" />
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                                {{ __('treasury::lang.total_profit_invoice') }}
                            </p>
                            <p class="treasury_total_profit_invoice tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                                <span class="display_currency" data-currency_symbol="true">{{ $summary['total_profit_invoice'] ?? 0 }}</span>
                            </p>
                            <small class="treasury_date_range">@lang('home.today')</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Spare Parts Selling Price -->
            <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200" style="min-width: 250px;">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-blue-100 tw-text-blue-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 3v18" />
                                <path d="M6 9h12" />
                                <path d="M6 15h12" />
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                                {{ __('treasury::lang.spare_parts_selling_price') }}
                            </p>
                            <p class="treasury_spare_parts_selling_price tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                                <span class="display_currency" data-currency_symbol="true">{{ $summary['spare_parts_selling_price'] ?? 0 }}</span>
                            </p>
                            <small class="treasury_date_range">@lang('home.today')</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Spare Parts Buying Price -->
            <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200" style="min-width: 250px;">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-orange-100 tw-text-orange-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 3v18" />
                                <path d="M6 9h12" />
                                <path d="M6 15h12" />
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                                {{ __('treasury::lang.spare_parts_buying_price') }}
                            </p>
                            <p class="treasury_spare_parts_buying_price tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                                <span class="display_currency" data-currency_symbol="true">{{ $summary['spare_parts_buying_price'] ?? 0 }}</span>
                            </p>
                            <small class="treasury_date_range">@lang('home.today')</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Spare Parts Profit Paid -->
            <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200" style="min-width: 250px;">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-green-100 tw-text-green-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 3v18" />
                                <path d="M6 9h12" />
                                <path d="M6 15h12" />
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                                {{ __('treasury::lang.spare_parts_profit_paid') }}
                            </p>
                            <p class="treasury_spare_parts_profit_paid tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                                <span class="display_currency" data-currency_symbol="true">{{ $summary['spare_parts_profit_paid'] ?? 0 }}</span>
                            </p>
                            <small class="treasury_date_range">@lang('home.today')</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 6: Spare Parts Profit Due -->
            <div class="tw-flex-1 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw-translate-y-0.5 tw-ring-1 tw-ring-gray-200" style="min-width: 250px;">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-yellow-100 tw-text-yellow-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 3v18" />
                                <path d="M6 9h12" />
                                <path d="M6 15h12" />
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                                {{ __('treasury::lang.spare_parts_profit_due') }}
                            </p>
                            <p class="treasury_spare_parts_profit_due tw-mt-0.5 tw-text-gray-900 tw-text-xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                                <span class="display_currency" data-currency_symbol="true">{{ $summary['spare_parts_profit_due'] ?? 0 }}</span>
                            </p>
                            <small class="treasury_date_range">@lang('home.today')</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="tw-grid tw-grid-cols-1 tw-gap-6 tw-mt-8">
        <!-- Monthly Trend Chart (2 rows height) -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-6">
                <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">{{ __('treasury::lang.monthly_trend') }}</h3>
                <div class="chart-container">
                    <canvas id="trend_chart" style="height: 400px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Methods and Top Transaction Types (Same Row) -->
        <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6">
             <!-- Top Transaction Types Chart -->
             <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-6">
                    <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">{{ __('treasury::lang.top_transaction_types') }}</h3>
                    <div class="chart-container">
                        <canvas id="payment_methods_distribution" style="height: 300px;"></canvas>
                    </div>
                    <div id="top_transaction_types_breakdown" class="tw-mt-4 tw-text-sm tw-text-gray-700"></div>
                </div>
            </div>
            <!-- Payment Methods Distribution Chart -->
            <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-6">
                    <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">{{ __('treasury::lang.payment_methods_distribution') }} </h3>
                    <div class="chart-container">
                        <canvas id="income_expense_chart" style="height: 300px;"></canvas>
                    </div>
                    <div id="income_expense_breakdown" class="tw-mt-4 tw-text-sm tw-text-gray-700"></div>
                </div>
            </div>

           
        </div>
    </div>

 

    <!-- All Transactions Table with Filters -->
    <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200 my-5">
        <div class="tw-p-4 sm:tw-p-6">
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">{{ __('treasury::lang.all_transactions') }}</h3>
            <!-- Filters -->
            <div class="tw-flex tw-gap-4 tw-mb-4">
                {{-- <div>
                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">{{ __('sale.payment_status') }}</label>
                    <select id="filter_payment_status" class="tw-mt-1 tw-block tw-w-full tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-bg-white tw-rounded-md">
                        <option value="">All</option>
                        <option value="paid">Paid</option>
                        <option value="due">Due</option>
                    </select>
                </div> --}}
                <div>
                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">{{ __('treasury::lang.transaction_type') }}</label>
                    <select id="filter_transaction_type" class="tw-mt-1 tw-block tw-w-full tw-px-3 tw-py-2 tw-border tw-border-gray-300 tw-bg-white tw-rounded-md">
                        <option value="">All</option>
                        {{-- <option value="income">{{ __('treasury::lang.income') }}</option> --}}
                        <option value="expense">{{ __('treasury::lang.expense') }}</option>
                        <option value="payroll">{{ __('treasury::lang.payroll') }}</option>
                        <option value="purchase">Purchase</option>
                        <option value="sell">Sell</option>
                        <option value="sell_return">Sell Return</option>
                        <option value="purchase_return">Purchase Return</option>
                    </select>
                </div>
            </div>
            <div class="tw-overflow-x-auto">
                <table class="table table-bordered table-striped" id="treasury_transactions_table" style="width: 100%; white-space: nowrap;">
                    <thead>
                        <tr>
                            <th>{{ __('messages.date') }}</th>
                            <th>{{ __('treasury::lang.invoice_no') }}</th>
                            <th>{{ __('treasury::lang.transaction_type') }}</th>
                            <th>{{ __('treasury::lang.sub_type') }}</th>
                            <th>{{ __('contact.contact') }}</th>
                            <th>{{ __('business.location') }}</th>
                            <th>{{ __('sale.payment_status') }}</th>
                            <th>{{ __('sale.total_amount') }}</th>
                            <th>{{ __('treasury::lang.remaining_amount') }}</th>
                            <th>{{ __('treasury::lang.Status') }}</th>
                            <th>{{ __('messages.action') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Pending Payments (Bottom Section) -->
    <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200 my-5">
        <div class="tw-p-4 sm:tw-p-6">
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">{{ __('treasury::lang.pending_payments') }}</h3>
            <div class="tw-overflow-x-auto">
                <table class="table table-bordered table-striped" id="pending_payments_table" style="width: 100%; white-space: nowrap;">
                    <thead>
                        <tr>
                            <th>{{ __('messages.date') }}</th>
                            <th>{{ __('purchase.ref_no') }}</th>
                            <th>{{ __('sale.total_amount') }}</th>
                            <th>{{ __('lang_v1.payment_method') }}</th>
                            <th>{{ __('sale.payment_status') }}</th>
                            <th>{{ __('lang_v1.document') }}</th>
                            <th>{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade payment_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>
    <!-- Edit Payment Modal -->
    <div class="modal fade" id="edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    <!-- View Payment Modal -->
    {{-- <div class="modal fade" id="view_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div> --}}

    <!-- View Invoice Modal -->
    <div class="modal fade" id="view_invoice_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    <!-- View Modal for general use -->
    {{-- <div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div> --}}

    {{-- Lightweight contact edit modal for Treasury dashboard --}}
    <div class="modal fade" id="repair_edit_contact_modal" tabindex="-1" role="dialog" aria-labelledby="repairEditContactLabel"></div>

    {{-- Contact merge confirmation modal --}}
    <div class="modal fade" id="contact_merge_modal" tabindex="-1" role="dialog" aria-labelledby="contactMergeLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="contactMergeLabel">{{ __('contact.merge_contacts') }}</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <p>{{ __('contact.mobile_already_exists') }}</p>
                        <p><strong>{{ __('contact.mobile') }}:</strong> <span id="merge_mobile"></span></p>
                    </div>
                    <p>{{ __('contact.choose_merge_option') }}:</p>
                    <div class="radio">
                        <label>
                            <input type="radio" name="merge_option" value="keep_current" checked>
                            <strong>{{ __('contact.merge_other_into_current') }}</strong>
                            <br>
                            <small>{{ __('contact.current_contact') }}: <span id="current_contact_name"></span></small>
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="merge_option" value="keep_duplicate">
                            <strong>{{ __('contact.merge_current_into_other') }}</strong>
                            <br>
                            <small>{{ __('contact.other_contact') }}: <span id="duplicate_contact_name"></span></small>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="confirm_merge_contact">{{ __('contact.merge') }}</button>
                </div>
            </div>
        </div>
    </div>

</section>

    {{-- Reuse internal transfer create modal so the transfer button can open it --}}
    @include('treasury::internal_transfers.create_modal')

</section>
@endsection

@section('javascript')
<style>
/* Treasury status badge styling */
.treasury-status.bg-danger {
    background-color: #dc3545 !important;
    color: #fff !important;
}

.treasury-status.bg-success {
    background-color: #28a745 !important;
    color: #fff !important;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        // Payment methods provided by server (key => label)
        var treasuryPaymentMethods = @json($payment_methods ?? []);
        // Initialize date range picker with predefined ranges (like main dashboard)
        dateRangeSettings.startDate = moment();
        dateRangeSettings.endDate = moment();

        $('#treasury_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#treasury_date_filter span').html(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            updateDashboardData(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            updateDateRangeDisplay(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        });

        $('#treasury_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#treasury_date_filter span').html('<i class="fa fa-calendar"></i> ' + '{{ __("messages.filter_by_date") }}');
            updateDashboardData(moment().format('YYYY-MM-DD'), moment().format('YYYY-MM-DD'));
            updateDateRangeDisplay('{{ __("home.today") }}');
        });

        // Branch filter change handler
        $('#treasury_branch_filter').change(function() {
            var daterangepicker = $('#treasury_date_filter').data('daterangepicker');
            var startDate = daterangepicker ? daterangepicker.startDate.format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
            var endDate = daterangepicker ? daterangepicker.endDate.format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
            updateDashboardData(startDate, endDate);
            updateUnfilteredTotals();
        });

        // Function to update unfiltered totals (Total Income, Total Expense, Cash In Hand, Real Balance)
        function updateUnfilteredTotals() {
            var locationId = $('#treasury_branch_filter').val() || '';
            
            $.ajax({
                url: '{{ route("treasury.dashboard.unfiltered.totals") }}',
                method: 'GET',
                data: { location_id: locationId },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        
                        $('.treasury_total_income .display_currency').text(__currency_trans_from_en(data.total_income, true));
                        $('.treasury_total_expense_main .display_currency').text(__currency_trans_from_en(data.total_expense, true));
                        $('.treasury_under_processing_sell .display_currency').text(__currency_trans_from_en(data.cashe_in_hand, true));
                        $('.treasury_real_balance .display_currency').text(__currency_trans_from_en(data.real_balance, true));
                        
                        __currency_convert_recursively($('.treasury_total_income, .treasury_total_expense_main, .treasury_under_processing_sell, .treasury_real_balance'));
                    }
                },
                error: function() {
                    toastr.error('{{ __("treasury::lang.failed_update_dashboard_cards") }}');
                }
            });
        }

        // Initialize with today's data and current filters
        var initialStartDate = moment().format('YYYY-MM-DD');
        var initialEndDate = moment().format('YYYY-MM-DD');

        // Utility helpers
        function escapeHtml(value) {
            var safeValue = (value === null || value === undefined) ? '' : value;
            return $('<div>').text(safeValue).html();
        }

        var statusClassMap = {
            due: 'bg-yellow',
            paid: 'bg-green'
        };

        var statusLabelMap = {
            due: "{{ __('lang_v1.due') }}",
            paid: "{{ __('sale.paid') }}"
        };

        // Set initial date range display
        updateDateRangeDisplay('{{ __("home.today") }}');

        // Load initial data
        updateDashboardData(initialStartDate, initialEndDate);
        updateUnfilteredTotals();
        loadPendingPayments();

        // Function to update all dashboard data
        function updateDashboardData(startDate, endDate) {
            var locationId = $('#treasury_branch_filter').val() || '';
            
            // Update dashboard cards
            $.ajax({
                url: '{{ route("treasury.dashboard.cards") }}',
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_id: locationId
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;

                        // Update card values
                        $('.treasury_total_sell .display_currency').text(__currency_trans_from_en(data.total_sell, true));
                        $('.treasury_total_purchase .display_currency').text(__currency_trans_from_en(data.total_purchase, true));
                        $('.treasury_total_purchase_return .display_currency').text(__currency_trans_from_en(data.total_purchase_return, true));
                        $('.treasury_total_sell_return .display_currency').text(__currency_trans_from_en(data.total_sell_return, true));
                        if (data.sell_return_paid !== undefined) {
                            $('.treasury_sell_return_paid').text(__currency_trans_from_en(data.sell_return_paid, true));
                        }
                        if (data.sell_return_due !== undefined) {
                            $('.treasury_sell_return_due').text(__currency_trans_from_en(data.sell_return_due, true));
                        }
                        $('.treasury_total_expense .display_currency').text(__currency_trans_from_en(data.total_expense, true));
                        $('.treasury_invoice_due .display_currency').text(__currency_trans_from_en(data.invoice_due, true));
                        $('.treasury_purchase_due .display_currency').text(__currency_trans_from_en(data.purchase_due, true));
                        $('.treasury_expense_due .display_currency').text(__currency_trans_from_en(data.expense_due, true));
                        // New paid amounts
                        if (data.selling_paid !== undefined) {
                            $('.treasury_selling_paid .display_currency').text(__currency_trans_from_en(data.selling_paid, true));
                        }
                        if (data.purchase_paid !== undefined) {
                            $('.treasury_purchase_paid .display_currency').text(__currency_trans_from_en(data.purchase_paid, true));
                        }
                        if (data.expense_paid !== undefined) {
                            $('.treasury_expense_paid .display_currency').text(__currency_trans_from_en(data.expense_paid, true));
                        }

                        if (data.under_processing_sell !== undefined) {
                            $('.treasury_under_processing_sell_card .display_currency').text(__currency_trans_from_en(data.under_processing_sell, true));
                        }
                        
                        // Update total profit invoice
                        if (data.total_profit_invoice !== undefined) {
                            $('.treasury_total_profit_invoice .display_currency').text(__currency_trans_from_en(data.total_profit_invoice, true));
                        }
                        if (data.total_profit !== undefined) {
                            $('.treasury_total_profit .display_currency').text(__currency_trans_from_en(data.total_profit, true));
                        }
                        if (data.virtual_products_profit !== undefined) {
                            $('.treasury_virtual_products_profit .display_currency').text(__currency_trans_from_en(data.virtual_products_profit, true));
                        }

                        // Update spare parts selling and buying prices
                        if (data.spare_parts_selling_price !== undefined) {
                            $('.treasury_spare_parts_selling_price .display_currency').text(__currency_trans_from_en(data.spare_parts_selling_price, true));
                        }
                        if (data.spare_parts_buying_price !== undefined) {
                            $('.treasury_spare_parts_buying_price .display_currency').text(__currency_trans_from_en(data.spare_parts_buying_price, true));
                        }

                        // Update job sheet expenses
                        if (data.job_sheet_expenses !== undefined) {
                            $('.treasury_job_sheet_expenses .display_currency').text(__currency_trans_from_en(data.job_sheet_expenses, true));
                        }

                        // Update spare parts profit paid and due amounts
                        if (data.spare_parts_profit_paid !== undefined) {
                            $('.treasury_spare_parts_profit_paid .display_currency').text(__currency_trans_from_en(data.spare_parts_profit_paid, true));
                        }
                        if (data.spare_parts_profit_due !== undefined) {
                            $('.treasury_spare_parts_profit_due .display_currency').text(__currency_trans_from_en(data.spare_parts_profit_due, true));
                        }

                        // Update payroll cards
                        if (data.total_payroll !== undefined) {
                            $('.treasury_total_payroll .display_currency').text(__currency_trans_from_en(data.total_payroll, true));
                        }
                        if (data.payroll_paid !== undefined) {
                            $('.treasury_payroll_paid .display_currency').text(__currency_trans_from_en(data.payroll_paid, true));
                        }
                        if (data.payroll_due !== undefined) {
                            $('.treasury_payroll_due .display_currency').text(__currency_trans_from_en(data.payroll_due, true));
                        }

                        // Convert currency display
                        __currency_convert_recursively($('#dashboard_cards, .treasury_total_profit_invoice'));
                        __currency_convert_recursively($('#dashboard_cards, .treasury_total_profit, .treasury_virtual_products_profit'));
                        __currency_convert_recursively($('.treasury_selling_paid, .treasury_purchase_paid, .treasury_expense_paid'));
                        __currency_convert_recursively($('.treasury_spare_parts_selling_price, .treasury_spare_parts_buying_price'));
                        __currency_convert_recursively($('.treasury_job_sheet_expenses'));
                        __currency_convert_recursively($('.treasury_spare_parts_profit_paid, .treasury_spare_parts_profit_due'));
                        __currency_convert_recursively($('.treasury_total_payroll, .treasury_payroll_paid, .treasury_payroll_due'));
                    }
                },
                error: function() {
                    toastr.error('{{ __("treasury::lang.failed_update_dashboard_cards") }}');
                }
            });

     
            // Update charts
            updateCharts(startDate, endDate, locationId);
            // Update pending payments table
            // loadPendingPayments();
        }

        // Function to update all charts
        function updateCharts(startDate, endDate, locationId) {
            // Update main charts (trend and income vs expense)
            updateMainCharts(startDate, endDate, locationId);

            // Update payment methods chart
            updatePaymentMethodsChart(startDate, endDate, locationId);

    
        }

        // Load and render pending payments table
        function loadPendingPayments() {
            var locationId = $('#treasury_branch_filter').val() || '';
            $.ajax({
                url: '{{ route("treasury.pending.payments") }}',
                method: 'GET',
                data: { location_id: locationId },
                success: function(response) {
                    var tbody = $('#pending_payments_table tbody');
                    tbody.empty();

                    var rows = (response.data || []).map(function(item) {
                        var amount = __currency_trans_from_en(item.amount || 0, true);
                        var status = (item.status || '').toLowerCase();
                        var statusBadgeClass = statusClassMap[status] || 'bg-gray';
                        var statusLabel = statusLabelMap[status] || escapeHtml(item.status || '-');
                        var documentHref = item.document_url || item.document;
                        var documentLabel = item.document_name || "{{ __('lang_v1.view_document') }}";
                        var documentLink = '-';

                        if (documentHref) {
                            var safeHref = String(documentHref).replace(/"/g, '&quot;');
                            var safeLabel = escapeHtml(documentLabel);
                            documentLink = `<a href="${safeHref}" target="_blank" rel="noopener">${safeLabel}</a>`;
                        }

                        var actions = `<button class="btn btn-sm btn-primary" onclick="openPaymentModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                            <i class="fa fa-eye"></i> {{ __('messages.view') }}
                        </button>`;

                        return `<tr>
                            <td>${escapeHtml(item.date || '')}</td>
                            <td>${escapeHtml(item.payment_ref_no || '')}</td>
                            <td><span class="display_currency" data-currency_symbol="true">${amount}</span></td>
                            <td>${escapeHtml(treasuryPaymentMethods[item.method] || item.method || '')}</td>
                            <td><span class="label ${statusBadgeClass}">${statusLabel}</span></td>
                            <td>${documentLink}</td>
                            <td>${actions}</td>
                        </tr>`;
                    }).join('');

                    tbody.html(rows);
                    __currency_convert_recursively($('#pending_payments_table'));
                }
            });
        }

        // Open payment details modal
        window.openPaymentModal = function(item) {
            var status = (item.status || '').toLowerCase();
            var statusBadgeClass = statusClassMap[status] || 'bg-gray';
            var statusLabel = statusLabelMap[status] || escapeHtml(item.status || '-');
            var documentHref = item.document_url || item.document;
            var documentLabel = item.document_name || "{{ __('lang_v1.view_document') }}";
            var documentHtml = '-';

            if (documentHref) {
                var safeHref = String(documentHref).replace(/"/g, '&quot;');
                var safeLabel = escapeHtml(documentLabel);
                documentHtml = `<a href="${safeHref}" target="_blank" rel="noopener">${safeLabel}</a>`;
            }

            var statusOptions = `
                <option value="due" ${status === 'due' ? 'selected' : ''}>{{ __('lang_v1.due') }}</option>
                <option value="paid" ${status === 'paid' ? 'selected' : ''}>{{ __('sale.paid') }}</option>
            `;

            // Build payment method options dynamically from server-provided list
            var paymentMethodOptions = '<option value="">{{ __('treasury::lang.payment_method_placeholder') }}</option>';
            var normalizedOriginalMethod = (item.method || '').toLowerCase();
            for (var pmKey in treasuryPaymentMethods) {
                if (!treasuryPaymentMethods.hasOwnProperty(pmKey)) continue;
                var pmLabel = treasuryPaymentMethods[pmKey] || pmKey;
                var isAdvanceOption = pmKey.toLowerCase() === 'advance';
                var selected = (item.method && item.method === pmKey && !isAdvanceOption) ? 'selected' : '';
                var disabled = isAdvanceOption ? 'disabled' : '';
                paymentMethodOptions += `<option value="${pmKey}" ${selected} ${disabled}>${escapeHtml(pmLabel)}</option>`;
            }
     
            var statusSelectHtml = '';
            if (status === 'due') {
                var advanceHintHtml = '';
               

                statusSelectHtml = `
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="payment_status_select" class="font-weight-semibold">{{ __('sale.payment_status') }}</label>
                            <select id="payment_status_select" class="form-control mt-1">
                                ${statusOptions}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_method_select" class="font-weight-semibold">{{ __('lang_v1.payment_method') }}</label>
                            <select id="payment_method_select" class="form-control mt-1">
                                ${paymentMethodOptions}
                            </select>
                        </div>
                        ${advanceHintHtml}
                        <input type="hidden" id="payment_original_method" value="${escapeHtml(item.method || '')}">
                    </div>
                `;
            }

            // Display method label if available
            var displayMethodLabel = (item.method && treasuryPaymentMethods[item.method]) ? treasuryPaymentMethods[item.method] : (item.method || '-');

            var modalHtml = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('treasury::lang.payment_details') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="container-fluid px-0">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">{{ __('messages.date') }}</small>
                                        <span class="font-weight-semibold">${escapeHtml(item.date || '-')}</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">{{ __('purchase.ref_no') }}</small>
                                        <span class="font-weight-semibold">${escapeHtml(item.payment_ref_no || '-')}</span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">{{ __('sale.total_amount') }}</small>
                                        <span class="font-weight-semibold"><span class="display_currency" data-currency_symbol="true">${item.amount || 0}</span></span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">{{ __('lang_v1.payment_method') }}</small>
                                        <span class="font-weight-semibold">${escapeHtml(displayMethodLabel)}</span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">{{ __('sale.payment_status') }}</small>
                                        <span class="label ${statusBadgeClass}">${statusLabel}</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">{{ __('lang_v1.document') }}</small>
                                        <span>${documentHtml}</span>
                                    </div>
                                </div>
                                ${statusSelectHtml}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.close') }}</button>
                            ${status === 'due' ? `<button type="button" class="btn btn-success" onclick="updatePaymentStatus(${item.id})">{{ __('messages.update') }}</button>` : ''}
                        </div>
                    </div>
                </div>
            `;

            var modal = $('#pending_payment_details_modal');
            if (modal.length === 0) {
                $('body').append('<div class="modal fade" id="pending_payment_details_modal" tabindex="-1" role="dialog"></div>');
                modal = $('#pending_payment_details_modal');
            }

            modal.html(modalHtml);
            modal.modal('show');
            __currency_convert_recursively(modal);
        };

        // Update payment status to paid
        window.updatePaymentStatus = function(paymentId) {
            var newStatus = $('#payment_status_select').val();
            var paymentMethod = $('#payment_method_select').val();
            var originalMethod = ($('#payment_original_method').val() || '').toLowerCase();
            if (newStatus === 'due') {
                toastr.warning('{{ __('messages.select_valid_option') }}');
                return;
            }

            if (!paymentMethod) {
                toastr.warning('{{ __('messages.select_valid_option') }} - {{ __('lang_v1.payment_method') }}');
                return;
            }

            if (paymentMethod.toLowerCase() === 'advance' || (originalMethod === 'advance' && paymentMethod.toLowerCase() === originalMethod)) {
                toastr.warning('{{ __('treasury::lang.advance_payment_method_not_allowed') }}');
                return;
            }

            $.ajax({
                url: '{{ route("treasury.payment.update.status") }}',
                method: 'POST',
                data: {
                    payment_id: paymentId,
                    status: newStatus,
                    payment_method: paymentMethod,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg || '{{ __("messages.updated_successfully") }}');
                        $('#pending_payment_details_modal').modal('hide');
                        loadPendingPayments();
                    } else {
                        toastr.error(response.msg || '{{ __("messages.something_went_wrong") }}');
                    }
                },
                error: function(xhr) {
                    toastr.error('{{ __("messages.something_went_wrong") }}');
                }
            });
        };

        // --- BEGIN: Internal transfer modal JS (reused from internal_transfers.index) ---

        // Function to load payment method balances
        function loadPaymentMethodBalances() {
            $.ajax({
                url: '{{ route("treasury.get.payment.method.balances") }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Update payment method cards
                        var cardsHtml = '';
                        var selectOptions = '<option value="">@lang("treasury::lang.select_payment_method")</option>';

                        response.data.forEach(function(method) {
                            var balanceFormatted = __currency_trans_from_en(method.balance, true);
                            var balanceClass = method.balance >= 0 ? 'text-success' : 'text-danger';

                            // Create card
                            cardsHtml += `
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">${method.name}</h5>
                                            <p class="card-text">
                                                <span class="display_currency ${balanceClass}" data-currency_symbol="true">${method.balance}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            `;

                            // Add to select options with balance display
                            selectOptions += `<option value="${method.id}">${method.name} - ${balanceFormatted}</option>`;
                        });

                        $('#payment_method_cards').html(cardsHtml);
                        $('#from_payment_method, #to_payment_method').html(selectOptions);

                        // Convert currency display
                        __currency_convert_recursively($('#payment_method_cards'));
                    }
                },
                error: function() {
                    toastr.error('{{ __("treasury::lang.failed_load_payment_method_balances") }}');
                }
            });
        }

        // Function to load branch-specific balances for payment method transfers
        function loadBranchSpecificBalances(locationId) {
            $.ajax({
                url: '{{ route("treasury.branch.payment.method.balances") }}',
                method: 'GET',
                data: { location_id: locationId },
                success: function(response) {
                    if (response.success) {
                        // Update payment method cards with branch-specific balances
                        var cardsHtml = '';
                        var selectOptions = '<option value="">@lang("treasury::lang.select_payment_method")</option>';
                        
                        response.data.forEach(function(method) {
                            var balanceFormatted = __currency_trans_from_en(method.balance, true);
                            var balanceClass = method.balance >= 0 ? 'text-success' : 'text-danger';
                            
                            // Create card with branch indicator
                            cardsHtml += `
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">${method.name}
                                            </h5>
                                            <p class="card-text">
                                                <span class="display_currency ${balanceClass}" data-currency_symbol="true">${method.balance}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            // Add to select options with balance display
                            selectOptions += `<option value="${method.id}">${method.name} - ${balanceFormatted}</option>`;
                        });
                        
                        $('#payment_method_cards').html(cardsHtml);
                        $('#from_payment_method, #to_payment_method').html(selectOptions);
                        
                        // Convert currency display
                        __currency_convert_recursively($('#payment_method_cards'));
                    }
                },
                error: function() {
                    toastr.error('{{ __("treasury::lang.failed_load_branch_balances") }}');
                    // Fall back to general balances
                    loadPaymentMethodBalances();
                }
            });
        }

        // Handle payment transfer location change to show branch-specific balances
        $(document).on('change', '#payment_transfer_location_id', function() {
            var locationId = $(this).val();
            
            if (locationId) {
                // Show available balances container when branch is selected
                $('#payment_method_cards_container').show();
                // Load branch-specific balances
                loadBranchSpecificBalances(locationId);
            } else {
                // Hide available balances when no branch is selected
                $('#payment_method_cards_container').hide();
                // Clear payment method selects
                $('#from_payment_method, #to_payment_method').html('<option value="">@lang("treasury::lang.select_payment_method")</option>');
            }
        });

        // Handle transfer submission
        $('#submit_transfer').click(function() {
            var transferType = $('input[name="transfer_type"]:checked').val();
            
            if (transferType === 'payment_transfer') {
                submitPaymentTransfer();
            } else if (transferType === 'branch_transfer') {
                submitBranchTransfer();
            }
        });

        // Function to submit payment method transfer
        function submitPaymentTransfer() {
            var form = $('#payment_transfer_form');
            
            if (form[0].checkValidity()) {
                // Validate different payment methods
                var fromMethod = $('#from_payment_method').val();
                var toMethod = $('#to_payment_method').val();
                
                if (fromMethod === toMethod && fromMethod !== '') {
                    toastr.warning('{{ __("treasury::lang.please_select_different_payment_methods") }}');
                    return;
                }
                
                var data = {
                    from_payment_method: fromMethod,
                    to_payment_method: toMethod,
                    amount: $('#payment_transfer_amount').val(),
                    date: $('#payment_transfer_date').val(),
                    notes: $('#payment_transfer_notes').val(),
                    location_id: $('#payment_transfer_location_id').val() || '' // Optional branch for payment transfer
                };

                submitTransferRequest(data);
            } else {
                form[0].reportValidity();
            }
        }

        // Function to submit branch transfer
        function submitBranchTransfer() {
            var form = $('#branch_transfer_form');
            
            if (form[0].checkValidity()) {
                // Validate different branches
                var fromBranch = $('#from_location_id').val();
                var toBranch = $('#to_location_id').val();
                
                if (!fromBranch || !toBranch) {
                    toastr.warning('{{ __("treasury::lang.please_select_both_branches") }}');
                    return;
                }
                
                if (fromBranch === toBranch) {
                    toastr.warning('{{ __("treasury::lang.please_select_different_branches") }}');
                    $('#to_location_id').val('');
                    return;
                }
                
                var paymentMethod = $('#branch_payment_method').val();
                if (!paymentMethod) {
                    toastr.warning('{{ __("treasury::lang.please_select_a_payment_method") }}');
                    return;
                }
                
                var data = {
                    payment_method: paymentMethod, // Single payment method for branch transfer
                    amount: $('#branch_transfer_amount').val(),
                    date: $('#branch_transfer_date').val(),
                    notes: $('#branch_transfer_notes').val(),
                    from_location_id: fromBranch,
                    to_location_id: toBranch
                };

                submitTransferRequest(data);
            } else {
                form[0].reportValidity();
            }
        }

        // Function to submit transfer request
        function submitTransferRequest(data) {
            // Add CSRF token
            data._token = $('meta[name="csrf-token"]').attr('content');
            
            $.ajax({
                url: '{{ route("treasury.submit.internal.transfer") }}',
                method: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        $('#internal_transfer_modal').modal('hide');
                        // Reload payment method balances
                        loadPaymentMethodBalances();
                        // Reload dashboard cards to reflect balance changes
                        updateDashboardData(moment().format('YYYY-MM-DD'), moment().format('YYYY-MM-DD'));
                    } else {
                        toastr.error(response.msg);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        var errorMsg = '';
                        $.each(errors, function(key, value) {
                            errorMsg += value[0] + '\n';
                        });
                        toastr.error(errorMsg);
                    } else {
                        toastr.error('{{ __("messages.something_went_wrong") }}');
                    }
                }
            });
        }

        // Prevent selecting same payment method for from and to
        $(document).on('change', '#from_payment_method, #to_payment_method', function() {
            var fromVal = $('#from_payment_method').val();
            var toVal = $('#to_payment_method').val();
            
            if (fromVal === toVal && fromVal !== '') {
                toastr.warning('{{ __("treasury::lang.please_select_different_payment_methods") }}');
                $(this).val('');
                $(this).focus();
            }
        });

        // Prevent selecting same branch for from and to (improved)
        $(document).on('change', '#from_location_id, #to_location_id', function() {
            var fromVal = $('#from_location_id').val();
            var toVal = $('#to_location_id').val();
            
            if (fromVal === toVal && fromVal !== '') {
                toastr.warning('{{ __("treasury::lang.please_select_different_branches") }}');
                $(this).val('');
                $(this).focus();
                $('#branch_balances_container').hide();
            }
        });

        // Reset form properly when switching transfer types
        $(document).on('change', 'input[name="transfer_type"]', function() {
            var transferType = $(this).val();
            switchTransferType(transferType);
        });

        // Function to switch transfer type (show/hide relevant containers)
        function switchTransferType(type) {
            if (type === 'payment_transfer') {
                $('#payment_transfer_form_container').show();
                $('#branch_transfer_form_container').hide();
                $('#payment_transfer_tab').addClass('active');
                $('#branch_transfer_tab').removeClass('active');
                $('#payment_method_cards_container').hide();
                $('#branch_balances_container').hide();
                if ($('#branch_transfer_form').length) {
                    $('#branch_transfer_form')[0].reset();
                }
            } else if (type === 'branch_transfer') {
                $('#payment_transfer_form_container').hide();
                $('#branch_transfer_form_container').show();
                $('#branch_transfer_tab').addClass('active');
                $('#payment_transfer_tab').removeClass('active');
                $('#payment_method_cards_container').hide();
                if ($('#payment_transfer_form').length) {
                    $('#payment_transfer_form')[0].reset();
                }
                // Load payment methods for branch transfer
                loadBranchPaymentMethods();
            }

            // Reset forms and dates and clear selects each time
            if ($('#payment_transfer_form').length) {
                $('#payment_transfer_form')[0].reset();
                $('#payment_transfer_date').val('{{ @format_date("now") }}');
            }
            if ($('#branch_transfer_form').length) {
                $('#branch_transfer_form')[0].reset();
                $('#branch_transfer_date').val('{{ @format_date("now") }}');
            }
            $('#branch_balances_container').hide();
            $('#payment_method_cards_container').hide();
            $('#from_payment_method, #to_payment_method').html('<option value="">@lang("treasury::lang.select_payment_method")</option>');
        }

        // Load payment methods for branch transfer
        function loadBranchPaymentMethods() {
            $.ajax({
                url: '{{ route("treasury.payment.method.balances") }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        var selectOptions = '<option value="">@lang("treasury::lang.select_payment_method")</option>';
                        response.data.forEach(function(method) {
                            selectOptions += `<option value="${method.id}">${method.name}</option>`;
                        });
                        $('#branch_payment_method').html(selectOptions);
                    }
                },
                error: function() {
                    toastr.error('{{ __("treasury::lang.failed_load_payment_methods") }}');
                }
            });
        }

        // Handle branch selection for branch transfer (load balances when both branches selected)
        $(document).on('change', '#from_location_id, #to_location_id', function() {
            var fromLocationId = $('#from_location_id').val();
            var toLocationId = $('#to_location_id').val();
            
            // Clear the branch balance display first
            $('#branch_balances_container').hide();
            
            // Check for same branch selection
            if (fromLocationId && toLocationId && fromLocationId === toLocationId) {
                toastr.warning('{{ __("treasury::lang.please_select_different_branches") }}');
                $(this).val('');
                return;
            }
            
            // Load balances only if both branches are selected and different
            if (fromLocationId && toLocationId && fromLocationId !== toLocationId) {
                loadBranchBalancesForTransfer(fromLocationId, toLocationId);
            }
        });

        // Function to load branch balances for transfer
        function loadBranchBalancesForTransfer(fromLocationId, toLocationId) {
            // Load From Branch Balance
            if (fromLocationId) {
                $.ajax({
                    url: '{{ route("treasury.branch.payment.method.balances") }}',
                    method: 'GET',
                    data: { location_id: fromLocationId },
                    success: function(response) {
                        if (response.success) {
                            var fromBranchName = $('#from_location_id option:selected').text();
                            var fromBranchHtml = `
                                <div class="col-md-6 mb-3">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0">{{ __('treasury::lang.from_branch') }}: ${fromBranchName}</h6>
                                        </div>
                                        <div class="card-body">
                            `;
                            response.data.forEach(function(method) {
                                var balanceFormatted = __currency_trans_from_en(method.balance, true);
                                var balanceClass = method.balance >= 0 ? 'text-success' : 'text-danger';
                                fromBranchHtml += `
                                    <div class="row mb-2">
                                        <div class="col-8">${method.name}:</div>
                                        <div class="col-4 text-right">
                                            <span class="display_currency ${balanceClass}" data-currency_symbol="true">${method.balance}</span>
                                        </div>
                                    </div>
                                `;
                            });
                            fromBranchHtml += `
                                        </div>
                                    </div>
                                </div>
                            `;
                            updateBranchBalanceDisplay(fromBranchHtml, 'from');
                        }
                    },
                    error: function() {
                        console.log('{{ __("treasury::lang.failed_load_branch_balances") }}');
                    }
                });
            }
            
            // Load To Branch Balance
            if (toLocationId && toLocationId !== fromLocationId) {
                $.ajax({
                    url: '{{ route("treasury.branch.payment.method.balances") }}',
                    method: 'GET',
                    data: { location_id: toLocationId },
                    success: function(response) {
                        if (response.success) {
                            var toBranchName = $('#to_location_id option:selected').text();
                            var toBranchHtml = `
                                <div class="col-md-6 mb-3">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">{{ __('treasury::lang.to_branch') }}: ${toBranchName}</h6>
                                        </div>
                                        <div class="card-body">
                            `;
                            response.data.forEach(function(method) {
                                var balanceFormatted = __currency_trans_from_en(method.balance, true);
                                var balanceClass = method.balance >= 0 ? 'text-success' : 'text-danger';
                                toBranchHtml += `
                                    <div class="row mb-2">
                                        <div class="col-8">${method.name}:</div>
                                        <div class="col-4 text-right">
                                            <span class="display_currency ${balanceClass}" data-currency_symbol="true">${method.balance}</span>
                                        </div>
                                    </div>
                                `;
                            });
                            toBranchHtml += `
                                        </div>
                                    </div>
                                </div>
                            `;
                            updateBranchBalanceDisplay(toBranchHtml, 'to');
                        }
                    },
                    error: function() {
                        console.log('{{ __("treasury::lang.failed_load_branch_balances") }}');
                    }
                });
            }
        }

        // Function to update branch balance display
        function updateBranchBalanceDisplay(html, type) {
            var containerId = type === 'from' ? '#from_branch_balances' : '#to_branch_balances';
            
            // Create container if it doesn't exist
            if ($(containerId).length === 0) {
                var containerHtml = `
                    <div class="row mb-4" id="branch_balances_container">
                        <div class="col-md-12">
                            <h5>{{ __('treasury::lang.branch_balance') }}</h5>
                            <div class="row">
                                <div id="from_branch_balances"></div>
                                <div id="to_branch_balances"></div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Insert before the branch transfer form container
                $('#branch_transfer_form_container').before(containerHtml);
            }
            
            $(containerId).html(html);
            $('#branch_balances_container').show();
            __currency_convert_recursively($(containerId));
        }

        // Modal show/hide handlers to prepare forms when opening/closing from dashboard
        $('#internal_transfer_modal').on('show.bs.modal', function() {
            // Reset all forms
            if ($('#payment_transfer_form').length) { $('#payment_transfer_form')[0].reset(); }
            if ($('#branch_transfer_form').length) { $('#branch_transfer_form')[0].reset(); }

            // Default to payment transfer
            $('input[name="transfer_type"][value="payment_transfer"]').prop('checked', true);
            $('#payment_transfer_tab').addClass('active').find('input').prop('checked', true);
            $('#branch_transfer_tab').removeClass('active').find('input').prop('checked', false);

            // Show payment transfer container, hide branch transfer
            $('#payment_transfer_form_container').show();
            $('#branch_transfer_form_container').hide();

            // Hide balances until selection
            $('#payment_method_cards_container').hide();
            $('#branch_balances_container').hide();

            // Ensure selects are cleared
            $('#from_payment_method, #to_payment_method').html('<option value="">@lang("treasury::lang.select_payment_method")</option>');
        });

        $('#internal_transfer_modal').on('hidden.bs.modal', function() {
            if ($('#payment_transfer_form').length) { $('#payment_transfer_form')[0].reset(); }
            if ($('#branch_transfer_form').length) { $('#branch_transfer_form')[0].reset(); }
            $('#branch_balances_container').hide();
        });

        // --- END: Internal transfer modal JS ---

        // Helpers to render breakdown text under charts
        function renderPaymentMethodsBreakdown(details) {
            var $container = $('#income_expense_breakdown');
            if (!details || details.length === 0) { $container.html(''); return; }
            var html = '';
            details.forEach(function(d) {
                html += '<div class="tw-mb-2">';
                html += '<span class="tw-font-semibold">' + d.method + '</span> — ';
                html += '<span class="display_currency" data-currency_symbol="true">' + d.total + '</span>';
                if (d.breakdown && d.breakdown.length > 0) {
                    html += '<ul class="tw-ml-4 tw-text-gray-600">';
                    d.breakdown.forEach(function(item){
                        html += '<li>' + item.type + ': ' + '<span class="display_currency" data-currency_symbol="true">' + item.amount + '</span>' + ' (' + item.percentage + '%)' + '</li>';
                    });
                    html += '</ul>';
                }
                html += '</div>';
            });
            $container.html(html);
            __currency_convert_recursively($container);
        }

        function renderTopTransactionTypesBreakdown(labels, data) {
            var $container = $('#top_transaction_types_breakdown');
            if (!labels || labels.length === 0 || !data || data.length === 0) { $container.html(''); return; }
            var total = data.reduce(function(a,b){ return a + (parseFloat(b) || 0); }, 0);
            var html = '<ul class="tw-list-disc tw-ml-5">';
            for (var i = 0; i < labels.length; i++) {
                var amount = data[i] || 0;
                var pct = total > 0 ? Math.round((amount / total) * 1000) / 10 : 0; // 0.1 precision
                html += '<li><span class="tw-font-medium">' + labels[i] + '</span>: ' + '<span class="display_currency" data-currency_symbol="true">' + amount + '</span>' + ' (' + pct + '%)' + '</li>';
            }
            html += '</ul>';
            $container.html(html);
            __currency_convert_recursively($container);
        }

        // Function to update main charts (trend and income vs expense)
        function updateMainCharts(startDate, endDate, locationId) {
            $.ajax({
                url: '{{ route("treasury.chart.data") }}',
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_id: locationId || ''
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;

                        // Convert monthly data to array format for charts
                        var income_data = [];
                        var expense_data = [];

                        for (var i = 1; i <= 12; i++) {
                            income_data.push(data.monthly_income[i] ? data.monthly_income[i].total : 0);
                            expense_data.push(data.monthly_expense[i] ? data.monthly_expense[i].total : 0);
                        }

                        // Update trend chart
                        if (window.trend_chart) {
                            window.trend_chart.data.datasets[0].data = income_data;
                            window.trend_chart.data.datasets[1].data = expense_data;
                            window.trend_chart.update();
                        }

                        // Update payment methods distribution chart (replacing income vs expense)
                        if (window.income_expense_chart && data.payment_methods_by_transaction_type) {
                            if (data.payment_methods_by_transaction_type.labels && data.payment_methods_by_transaction_type.labels.length > 0) {
                                window.income_expense_chart.data.labels = data.payment_methods_by_transaction_type.labels;
                                window.income_expense_chart.data.datasets[0].data = data.payment_methods_by_transaction_type.data;
                                window.income_expense_chart.data.datasets[0].backgroundColor = data.payment_methods_by_transaction_type.colors;
                                window.income_expense_chart.data.datasets[0].borderColor = data.payment_methods_by_transaction_type.colors;

                                // Update the global data for tooltips
                                payment_methods_by_transaction_type_data = data.payment_methods_by_transaction_type;

                                // Update tooltip callbacks to use new data
                                window.income_expense_chart.options.plugins.tooltip.callbacks.label = function(context) {
                                    var label = context.label || '';
                                    var total = __currency_trans_from_en(context.parsed, true);

                                    // Get enhanced breakdown details for this payment method
                                    var details = payment_methods_by_transaction_type_data.details[context.dataIndex];

                                    if (details && details.breakdown && details.breakdown.length > 0) {
                                        var breakdown = details.breakdown.map(function(item) {
                                            return item.type + ': ' + __currency_trans_from_en(item.amount, true) + ' (' + item.percentage + '%)';
                                        }).join('\n');

                                        return [
                                            label + ': ' + total,
                                            'Breakdown:',
                                            breakdown
                                        ];
                                    }

                                    return label + ': ' + total;
                                };

                                window.income_expense_chart.update();
                                renderPaymentMethodsBreakdown(payment_methods_by_transaction_type_data.details);
                            } else {
                                // Handle empty data case
                                window.income_expense_chart.data.labels = [];
                                window.income_expense_chart.data.datasets[0].data = [];
                                payment_methods_by_transaction_type_data = { labels: [], data: [], colors: [], details: [] };
                                window.income_expense_chart.update();
                                renderPaymentMethodsBreakdown([]);
                            }
                        }


                    }
                },
                error: function() {
                    toastr.error('{{ __("treasury::lang.failed_update_charts") }}');
                }
            });
        }

        // Function to update top transaction types pie chart (replacing payment methods chart)
        function updatePaymentMethodsChart(startDate, endDate, locationId) {
            $.ajax({
                url: '{{ route("treasury.payment.methods.chart") }}',
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_id: locationId || ''
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;

                        if (window.payment_methods_distribution) {
                            if (data.labels && data.labels.length > 0) {
                                window.payment_methods_distribution.data.labels = data.labels;
                                window.payment_methods_distribution.data.datasets[0].data = data.data;
                                window.payment_methods_distribution.data.datasets[0].backgroundColor = data.colors;
                                window.payment_methods_distribution.data.datasets[0].borderColor = data.colors;
                                window.payment_methods_distribution.update();
                                renderTopTransactionTypesBreakdown(data.labels, data.data);
                            } else {
                                // Handle empty data case
                                window.payment_methods_distribution.data.labels = [];
                                window.payment_methods_distribution.data.datasets[0].data = [];
                                window.payment_methods_distribution.data.datasets[0].backgroundColor = [];
                                window.payment_methods_distribution.data.datasets[0].borderColor = [];
                                window.payment_methods_distribution.update();
                                renderTopTransactionTypesBreakdown([], []);
                            }
                        }
                    }
                },
                error: function() {
                    toastr.error('{{ __("treasury::lang.failed_update_top_transaction_types_chart") }}');
                }
            });
        }

 
        // Function to update date range display (limit to main dashboard cards only)
        function updateDateRangeDisplay(dateText) {
            $('.treasury_date_range').text(dateText);
        }

        // Localized labels for DataTable badges
        const paymentStatusLabels = {
            due: '{{ __('treasury::lang.payment_status_due') }}',
            partial: '{{ __('treasury::lang.payment_status_partial') }}',
            paid: '{{ __('treasury::lang.payment_status_paid') }}',
        };

        const transactionTypeLabels = {
            sell: '{{ __('treasury::lang.type_sell') }}',
            purchase: '{{ __('treasury::lang.type_purchase') }}',
            repair: '{{ __('treasury::lang.type_repair') }}',
            purchase_return: '{{ __('treasury::lang.type_purchase_return') }}',
            expense: '{{ __('treasury::lang.type_expense') }}',
        };

        // Status badges are fully rendered (HTML) on the backend

        // Initialize Treasury Transactions DataTable
        var treasury_transactions_table = $('#treasury_transactions_table').DataTable({
            processing: true,
            serverSide: true,
            // responsive: true,
            pageLength: 25,
            // order: [[0, 'asc']], // Oldest first, ordered by transaction_date
            ajax: {
                url: "{{ url('/treasury/get-treasury-transactions') }}",
                data: function(d) {
                    // Add filters (only branch and transaction type, no date filter)
                    d.location_id = $('#treasury_branch_filter').val() || '';
                    d.transaction_type = $('#filter_transaction_type').val();
                }
            },

            columns: [
                { data: 'transaction_date', name: 'transaction_date', orderable: true, searchable: true },
                { data: 'invoice_no', name: 'invoice_no', orderable: true, searchable: true },
                { data: 'type', name: 'type', render: function(data) {
                    if (!data) return '';

                    let key = data.toLowerCase();
                    let label = transactionTypeLabels[key] || data;

                    let badgeClass = 'badge bg-secondary';
                    if (key === 'sell') badgeClass = 'badge bg-primary';
                    if (key === 'purchase') badgeClass = 'badge bg-warning text-dark';
                    if (key === 'payroll') badgeClass = 'badge bg-warning text-dark';
                    if (key === 'repair') badgeClass = 'badge bg-info';
                    if (key === 'purchase_return') badgeClass = 'badge';
                    if (key === 'expense') badgeClass = 'badge';

                    let badgeStyle = '';
                    if (key === 'purchase_return' || key === 'expense') badgeStyle = 'background-color: #dc3545; color: #fff;';

                    return `<span class='${badgeClass}' style='${badgeStyle}'>${label}</span>`;
                }},
                { data: 'sub_type', name: 'sub_type', render: function(data) {
                    return `<span class='badge bg-info'>${data}</span>`;
                }},
                { data: 'contact_name', name: 'contacts.name' },
                { data: 'location_name', name: 'business_locations.name' },
                {
                data: 'payment_status',
                name: 'payment_status',
                render: function (data) {
                    if (!data) return '';

                    let status = data.toLowerCase();
                    let label = paymentStatusLabels[status] || data;

                    if (status === 'due') {
                        // Yellow background for due
                        return `<span class='badge bg-warning text-dark'>${label}</span>`;
                    }
                    if (status === 'partial') {
                        // Blue info badge for partial
                        return `<span class='badge bg-info'>${label}</span>`;
                    }
                    if (status === 'paid') {
                        // Green for paid
                        return `<span class='badge bg-success'>${label}</span>`;
                    }

                    return `<span class='badge bg-secondary'>${label}</span>`;
                }
            },

                { data: 'final_total', name: 'final_total' },
                { data: 'remaining_amount', name: 'remaining_amount' , orderable: false, searchable: false },
                // Status column uses pre-rendered HTML from the server
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            // scrollX: true,
            // autoWidth: true,

            "fnDrawCallback": function(oSettings) {
                __currency_convert_recursively($('#treasury_transactions_table'));
            }
        });

        // Apply branch and transaction type filters to DataTable only
        $('#filter_payment_status, #filter_transaction_type, #treasury_branch_filter').change(function() {
            treasury_transactions_table.ajax.reload();
        });

        // Handle delete treasury transaction
        $(document).on('click', '.delete-treasury-transaction', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    var href = $(this).attr('href');
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                treasury_transactions_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        error: function() {
                            toastr.error('{{ __("messages.something_went_wrong") }}');
                        }
                    });
                }
            });
        });

        // Initialize Charts without data - they will be populated via AJAX calls
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Initialize empty data arrays
        var income_data = new Array(12).fill(0);
        var expense_data = new Array(12).fill(0);

        // Line Chart - Monthly Trend (store globally for updates)
        window.trend_chart = new Chart(document.getElementById('trend_chart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: '{{ __('treasury::lang.income') }}',
                        data: income_data,
                        borderColor: 'rgba(60, 141, 188, 1)',
                        backgroundColor: 'rgba(60, 141, 188, 0.2)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: '{{ __('treasury::lang.expense') }}',
                        data: expense_data,
                        borderColor: 'rgba(210, 214, 222, 1)',
                        backgroundColor: 'rgba(210, 214, 222, 0.2)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Pie Chart - Payment Methods Distribution by Transaction Type (initialize empty)
        var payment_methods_by_transaction_type_data = {
            labels: [],
            data: [],
            colors: [],
            details: []
        };

        // Create empty chart for updates
        window.income_expense_chart = new Chart(document.getElementById('income_expense_chart'), {
            type: 'pie',
            data: {
                labels: [],
                datasets: [
                    {
                        data: [],
                        backgroundColor: [],
                        borderColor: [],
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var total = __currency_trans_from_en(context.parsed, true);

                                // Get enhanced breakdown details for this payment method
                                var details = payment_methods_by_transaction_type_data.details[context.dataIndex];

                                if (details && details.breakdown && details.breakdown.length > 0) {
                                    var breakdown = details.breakdown.map(function(item) {
                                        return item.type + ': ' + __currency_trans_from_en(item.amount, true) + ' (' + item.percentage + '%)';
                                    }).join('\n');

                                    return [
                                        label + ': ' + total,
                                        'Breakdown:',
                                        breakdown
                                    ];
                                }

                                return label + ': ' + total;
                            },
                            title: function(context) {
                                return 'Payment Method Distribution';
                            }
                        }
                    }
                }
            }
        });



        // Pie Chart - Top Transaction Types (initialize empty)
        var top_transaction_types_data = {
            labels: [],
            data: [],
            colors: []
        };

        // Create empty chart for updates
        window.payment_methods_distribution = new Chart(document.getElementById('payment_methods_distribution'), {
            type: 'pie',
            data: {
                labels: [],
                datasets: [
                    {
                        data: [],
                        backgroundColor: [],
                        borderColor: [],
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += __currency_trans_from_en(context.parsed, true);
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Initialize transaction type trend chart with empty data
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var datasets = [];

        // Store transaction type trend chart globally for updates (only if canvas exists)
        var typeTrendCanvas = document.getElementById('type_trend_chart');
        if (typeTrendCanvas) {
            window.type_trend_chart = new Chart(typeTrendCanvas, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        } else {
            window.type_trend_chart = null;
        }




    });
</script>

<script>
    // Handle lightweight contact edit modal
    $(document).on('click', '.repair-edit-contact-basic', function(e){
        e.preventDefault();
        var url = $(this).data('href');
        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html',
            success: function(result) {
                $('#repair_edit_contact_modal').html(result).modal('show');
            }
        });
    });

    $(document).on('submit', 'form#repair_edit_contact_form', function(e){
        e.preventDefault();

        var data = $(this).serialize();

        $.ajax({
            method: $(this).attr("method"),
            url: $(this).attr("action"),
            dataType: "json",
            data: data,
            success: function(result){
                if(result.success == true){
                    $('#repair_edit_contact_modal').modal('hide');
                    toastr.success(result.msg);
                    treasury_transactions_table.ajax.reload();
                } else if(result.duplicate_mobile == true){
                    // Show merge options modal
                    $('#merge_mobile').text(result.mobile);
                    $('#current_contact_name').text(result.current_contact_name);
                    $('#duplicate_contact_name').text(result.duplicate_contact_name);
                    $('#contact_merge_modal').data('current-contact-id', result.current_contact_id);
                    $('#contact_merge_modal').data('duplicate-contact-id', result.duplicate_contact_id);
                    $('#contact_merge_modal').modal('show');
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(xhr){
                var errorMsg = __('messages.something_went_wrong');
                if(xhr.responseJSON && xhr.responseJSON.msg){
                    errorMsg = xhr.responseJSON.msg;
                }
                toastr.error(errorMsg);
            }
        });
    });

    // Handle merge contact confirmation
    $('#confirm_merge_contact').on('click', function(){
        var mergeOption = $('input[name="merge_option"]:checked').val();
        var currentContactId = $('#contact_merge_modal').data('current-contact-id');
        var duplicateContactId = $('#contact_merge_modal').data('duplicate-contact-id');

        var keepContactId, mergeContactId;

        if(mergeOption == 'keep_current'){
            keepContactId = currentContactId;
            mergeContactId = duplicateContactId;
        } else {
            keepContactId = duplicateContactId;
            mergeContactId = currentContactId;
        }

        $.ajax({
            method: 'POST',
            url: '{{ route("repair.contacts.merge") }}',
            dataType: "json",
            data: {
                keep_contact_id: keepContactId,
                merge_contact_id: mergeContactId,
                _token: '{{ csrf_token() }}'
            },
            success: function(result){
                if(result.success == true){
                    $('#contact_merge_modal').modal('hide');
                    $('#repair_edit_contact_modal').modal('hide');
                    toastr.success(result.msg);
                    treasury_transactions_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(xhr){
                var errorMsg = __('messages.something_went_wrong');
                if(xhr.responseJSON && xhr.responseJSON.msg){
                    errorMsg = xhr.responseJSON.msg;
                }
                toastr.error(errorMsg);
            }
        });
    });
</script>
@endsection