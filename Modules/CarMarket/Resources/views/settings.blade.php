@extends('layouts.app')

@section('title', __('messages.settings') . ' - ' . __('carmarket::lang.module_title'))

@section('content')
@include('carmarket::layouts.nav')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('carmarket::lang.module_title') - {{ __('messages.settings') }}
    </h1>
</section>

<section class="content no-print">
    <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6">
        <div class="row">
            <div class="col-md-6">
                <h3 class="tw-font-semibold tw-text-lg tw-mb-3">
                    <i class="fa fa-cog"></i> {{ __('messages.settings') }}
                </h3>
                <div class="form-group">
                    <label>Listing Expiry Days</label>
                    <input type="number" class="form-control" value="{{ config('carmarket.listing_expiry_days', 90) }}" disabled>
                    <p class="help-block">Configure in config/carmarket.php</p>
                </div>
                <div class="form-group">
                    <label>Minimum Photos Required</label>
                    <input type="number" class="form-control" value="{{ config('carmarket.min_photos', 3) }}" disabled>
                </div>
                <div class="form-group">
                    <label>Similar Vehicle Price Range (%)</label>
                    <input type="number" class="form-control" value="{{ config('carmarket.similar_price_range_pct', 20) }}" disabled>
                </div>
                <div class="form-group">
                    <label>Max Upload Size (KB)</label>
                    <input type="number" class="form-control" value="{{ config('carmarket.max_upload_size_kb', 5120) }}" disabled>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
