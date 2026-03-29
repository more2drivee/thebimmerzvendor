@extends('layouts.app')
@section('title', __('contact.contacts') . ' ' . __('business.dashboard'))

<style>
    :root{
        --chart-bg: #ffffff;
        --muted: #6b7280;
        --accent: #2563eb;
        --card-radius: 10px;
    }

    .small-box {
        border-radius: 10px;
        background: var(--chart-bg);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        padding: 18px;
        margin-bottom: 18px;
        transition: transform .18s ease, box-shadow .18s ease;
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .small-box:hover {
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.09);
        transform: translateY(-4px);
    }

    .inner { text-align:center; }
    .inner p { margin:0 0 6px 0; color:var(--muted); font-size:15px; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px; }
    .inner i { color:var(--accent); font-size:18px; }
    .inner h3 { margin:0; font-size:22px; font-weight:700; color:#0f172a; }
</style>

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @include('contact.layouts.nav')
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('contact.contacts')
            <small>@lang('business.dashboard')</small>
        </h1>
    </section>

    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('contact.contact_statistics')])
                    @forelse($counters as $key => $value)
                        <div class="col-md-3 col-sm-6 col-xs-12">
                            <div class="small-box">
                                <div class="inner">
                                    <p><i class="{{ $value['icon'] }}"></i> {{ $key }}</p>
                                    <h3>{{ $value['data'] }}</h3>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h4>@lang('lang_v1.no_data_found')</h4>
                            </div>
                        </div>
                    @endforelse
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                @component('components.widget', ['title' => __('lang_v1.recent_suppliers')])
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('contact.name')</th>
                                <th>@lang('contact.supplier_business_name')</th>
                                <th>@lang('business.phone')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_suppliers as $supplier)
                            <tr>
                                <td>{{ $supplier->name }}</td>
                                <td>{{ $supplier->supplier_business_name ?? '-' }}</td>
                                <td>{{ $supplier->mobile ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center">{{ __('lang_v1.no_data_found') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endcomponent
            </div>

            <div class="col-md-6">
                @component('components.widget', ['title' => __('lang_v1.recent_customers')])
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('contact.name')</th>
                                <th>@lang('business.phone')</th>
                                <th>@lang('business.email')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_customers as $customer)
                            <tr>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->mobile ?? '-' }}</td>
                                <td>{{ $customer->email ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center">{{ __('lang_v1.no_data_found') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('lang_v1.recent_loyalty_requests')])
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('messages.date')</th>
                                <th>@lang('contact.name')</th>
                                <th>@lang('lang_v1.points_requested')</th>
                                <th>@lang('lang_v1.status')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_loyalty_requests as $request)
                            <tr>
                                <td>{{ $request->created_at }}</td>
                                <td>{{ $request->contact ? $request->contact->name : '-' }}</td>
                                <td>{{ $request->points_requested }}</td>
                                <td>
                                    @if($request->status == 'pending')
                                        <span class="label label-warning">{{ __('lang_v1.pending') }}</span>
                                    @elseif($request->status == 'approved')
                                        <span class="label label-success">{{ __('lang_v1.approved') }}</span>
                                    @elseif($request->status == 'rejected')
                                        <span class="label label-danger">{{ __('lang_v1.rejected') }}</span>
                                    @else
                                        <span class="label label-default">{{ $request->status }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">{{ __('lang_v1.no_requests_found') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endcomponent
            </div>
        </div>
    </section>
@stop
