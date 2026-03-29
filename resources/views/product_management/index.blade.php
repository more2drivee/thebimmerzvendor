@extends('layouts.app')

@section('title', __('product_management.title'))

@section('content')
<div class="tw-pb-6 tw-bg-gradient-to-r tw-from-primary-800 tw-to-primary-900 xl:tw-pb-0">
    <div class="tw-px-5 tw-pt-3">
        <div class="sm:tw-flex sm:tw-items-center sm:tw-justify-between sm:tw-gap-12">
            <h1 class="tw-text-2xl tw-font-medium tw-tracking-tight tw-text-white">
                {{ __('product_management.title') }}
            </h1>
        </div>
    </div>
</div>

<section class="tw-py-8">
    <div class="tw-container tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-grid tw-grid-cols-1 tw-gap-6 sm:tw-grid-cols-2 lg:tw-grid-cols-3">
            @foreach($cards as $card)
            <div class="tw-bg-white tw-overflow-hidden tw-shadow-lg tw-rounded-lg tw-border tw-border-gray-200 hover:tw-shadow-xl tw-transition-all tw-duration-300">
                <div class="tw-p-6">
                    <div class="tw-flex tw-items-center tw-mb-4">
                        <div class="tw-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-bg-{{ $card['color'] }}-100 tw-mr-4">
                            <i class="{{ $card['icon'] }} tw-text-2xl tw-text-{{ $card['color'] }}-600"></i>
                        </div>
                        <div>
                            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">
                                {{ $card['title'] }}
                            </h3>
                        </div>
                    </div>

                    <p class="tw-text-gray-600 tw-mb-6 tw-text-sm">
                        {{ $card['description'] }}
                    </p>

                    <div class="tw-space-y-3">
                        @foreach($card['links'] as $link)
                            @can($link['permission'] ?? '')
                            <a href="{{ $link['url'] }}"
                               class="tw-block tw-w-full tw-px-4 tw-py-3 tw-text-sm tw-font-medium tw-text-center tw-text-white tw-bg-{{ $card['color'] }}-600 tw-rounded-lg hover:tw-bg-{{ $card['color'] }}-700 tw-transition-colors tw-duration-200">
                                {{ $link['title'] }}
                            </a>
                            @endcan
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Bundles Card --}}
            <div class="tw-bg-white tw-overflow-hidden tw-shadow-lg tw-rounded-lg tw-border tw-border-gray-200 hover:tw-shadow-xl tw-transition-all tw-duration-300">
                <div class="tw-p-6">
                    <div class="tw-flex tw-items-center tw-mb-4">
                        <div class="tw-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-bg-indigo-100 tw-mr-4">
                            <i class="fas fa-car-crash tw-text-2xl tw-text-indigo-600"></i>
                        </div>
                        <div>
                            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">
                                {{ __('bundles.title') }}
                            </h3>
                        </div>
                    </div>

                    <p class="tw-text-gray-600 tw-mb-6 tw-text-sm">
                        {{ __('bundles.subtitle') }}
                    </p>

                    <div class="tw-space-y-3">
                        @can('product.view')
                        <a href="{{ route('bundles.index') }}"
                           class="tw-block tw-w-full tw-px-4 tw-py-3 tw-text-sm tw-font-medium tw-text-center tw-text-white tw-bg-indigo-600 tw-rounded-lg hover:tw-bg-indigo-700 tw-transition-colors tw-duration-200">
                            {{ __('bundles.title') }}
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        @if(empty($cards))
        <div class="tw-text-center tw-py-12">
            <div class="tw-bg-white tw-rounded-lg tw-shadow-lg tw-p-8 tw-max-w-md tw-mx-auto">
                <div class="tw-flex tw-items-center tw-justify-center tw-w-16 tw-h-16 tw-rounded-full tw-bg-gray-100 tw-mb-4 tw-mx-auto">
                    <i class="fas fa-box-open tw-text-2xl tw-text-gray-400"></i>
                </div>
                <h3 class="tw-text-lg tw-font-medium tw-text-gray-900 tw-mb-2">{{ __('product_management.no_permissions') }}</h3>
                <p class="tw-text-gray-600">{{ __('product_management.no_permissions_message') }}</p>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Add any additional JavaScript if needed
</script>
@endpush
