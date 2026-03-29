@extends('layouts.app')

@section('title', $vehicle->getTitle() . ' - ' . __('carmarket::lang.module_title'))

@section('css')
<style>
.vehicle-gallery { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
.vehicle-gallery img { width: 150px; height: 110px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid transparent; transition: border-color 0.2s; }
.vehicle-gallery img:hover { border-color: #3c8dbc; }
.vehicle-gallery img.primary { border-color: #00a65a; }
.vehicle-main-image { width: 100%; max-height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 15px; }
.spec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
.spec-item { background: #f8f9fa; border-radius: 8px; padding: 12px; }
.spec-item .spec-label { font-size: 12px; color: #888; margin-bottom: 2px; }
.spec-item .spec-value { font-size: 16px; font-weight: 600; color: #333; }
.status-badge { font-size: 14px; padding: 5px 12px; }
</style>
@endsection

@section('content')
@include('carmarket::layouts.nav')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ $vehicle->getTitle() }}
        @php
            $statusColors = ['draft'=>'default','pending'=>'warning','active'=>'success','sold'=>'primary','reserved'=>'info','expired'=>'danger','rejected'=>'danger'];
        @endphp
        <span class="label label-{{ $statusColors[$vehicle->listing_status] ?? 'default' }} status-badge">
            @lang('carmarket::lang.' . $vehicle->listing_status)
        </span>
        @if($vehicle->is_premium)
            <span class="label label-warning status-badge"><i class="fa fa-star"></i> @lang('carmarket::lang.is_premium')</span>
        @endif
    </h1>
</section>

<section class="content no-print">
    <div class="row">
        {{-- Left: Images & Specs --}}
        <div class="col-md-8">
            {{-- Photo Gallery --}}
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                <h3 class="tw-font-semibold tw-text-lg tw-mb-3">
                    <i class="fa fa-image"></i> @lang('carmarket::lang.photo_gallery')
                    <span class="badge">{{ $vehicle->media->count() }}</span>
                </h3>
                @if($vehicle->media->count())
                    @php $primary = $vehicle->media->firstWhere('is_primary', true) ?? $vehicle->media->first(); @endphp
                    <img src="{{ asset('storage/' . $primary->file_path) }}" class="vehicle-main-image" id="mainImage" onerror="this.src='/img/default.png'">
                    <div class="vehicle-gallery">
                        @foreach($vehicle->media as $m)
                            <img src="{{ asset('storage/' . $m->file_path) }}"
                                 class="{{ $m->is_primary ? 'primary' : '' }}"
                                 onclick="document.getElementById('mainImage').src=this.src"
                                 title="{{ $m->media_type }}"
                                 onerror="this.src='/img/default.png'">
                        @endforeach
                    </div>
                @else
                    <img src="/img/default.png" class="vehicle-main-image">
                @endif
            </div>

            {{-- Specs Summary --}}
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                <h3 class="tw-font-semibold tw-text-lg tw-mb-3">
                    <i class="fa fa-cogs"></i> @lang('carmarket::lang.specs_summary')
                </h3>
                <div class="spec-grid">
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.condition')</div>
                        <div class="spec-value">@lang('carmarket::lang.' . $vehicle->condition . '_car')</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.mileage_km')</div>
                        <div class="spec-value">{{ number_format($vehicle->mileage_km ?? 0) }} KM</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.body_type')</div>
                        <div class="spec-value">@lang('carmarket::lang.' . ($vehicle->body_type ?? 'sedan'))</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.transmission')</div>
                        <div class="spec-value">@lang('carmarket::lang.' . ($vehicle->transmission ?? 'automatic'))</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.color')</div>
                        <div class="spec-value">{{ $vehicle->color ?? '-' }}</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.fuel_type')</div>
                        <div class="spec-value">@lang('carmarket::lang.' . ($vehicle->fuel_type ?? 'gas'))</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.engine_capacity_cc')</div>
                        <div class="spec-value">{{ $vehicle->engine_capacity_cc ?? '-' }} CC</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.cylinder_count')</div>
                        <div class="spec-value">{{ $vehicle->cylinder_count ?? '-' }}</div>
                    </div>
                    @if($vehicle->vin_number)
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.vin_number')</div>
                        <div class="spec-value">{{ $vehicle->vin_number }}</div>
                    </div>
                    @endif
                    @if($vehicle->plate_number)
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.plate_number')</div>
                        <div class="spec-value">{{ $vehicle->plate_number }}</div>
                    </div>
                    @endif
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.factory_paint')</div>
                        <div class="spec-value">{{ $vehicle->factory_paint ? '✓' : '✗' }}</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.imported_specs')</div>
                        <div class="spec-value">{{ $vehicle->imported_specs ? '✓' : '✗' }}</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.license_type')</div>
                        <div class="spec-value">{{ ucfirst(str_replace('_', ' ', $vehicle->license_type ?? '-')) }}</div>
                    </div>
                </div>
            </div>

            {{-- Ownership Costs --}}
            @if($vehicle->license_3year_cost || $vehicle->insurance_annual_cost)
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                <h3 class="tw-font-semibold tw-text-lg tw-mb-3">
                    <i class="fa fa-money"></i> @lang('carmarket::lang.ownership_costs')
                </h3>
                <div class="spec-grid">
                    @if($vehicle->license_3year_cost)
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.license_3year_cost')</div>
                        <div class="spec-value text-primary">{{ number_format($vehicle->license_3year_cost, 2) }} {{ $vehicle->currency }}</div>
                    </div>
                    @endif
                    @if($vehicle->insurance_annual_cost)
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.insurance_annual_cost')</div>
                        <div class="spec-value">{{ number_format($vehicle->insurance_annual_cost, 2) }} {{ $vehicle->currency }}</div>
                    </div>
                    @endif
                    @if($vehicle->insurance_rate_pct)
                    <div class="spec-item">
                        <div class="spec-label">@lang('carmarket::lang.insurance_rate_pct')</div>
                        <div class="spec-value">{{ $vehicle->insurance_rate_pct }}%</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Description --}}
            @if($vehicle->description)
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                <h3 class="tw-font-semibold tw-text-lg tw-mb-3">
                    <i class="fa fa-file-text"></i> @lang('carmarket::lang.description')
                </h3>
                <p>{{ $vehicle->description }}</p>
                @if($vehicle->condition_notes)
                    <hr>
                    <strong>@lang('carmarket::lang.condition_notes'):</strong>
                    <p>{{ $vehicle->condition_notes }}</p>
                @endif
            </div>
            @endif

            {{-- Inquiries --}}
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                <h3 class="tw-font-semibold tw-text-lg tw-mb-3">
                    <i class="fa fa-envelope"></i> @lang('carmarket::lang.inquiries')
                    <span class="badge">{{ $vehicle->inquiries->count() }}</span>
                </h3>
                @if($vehicle->inquiries->count())
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('carmarket::lang.buyer_name')</th>
                                <th>@lang('carmarket::lang.inquiry_type')</th>
                                <th>@lang('carmarket::lang.message')</th>
                                <th>@lang('carmarket::lang.offered_price')</th>
                                <th>@lang('carmarket::lang.inquiry_status')</th>
                                <th>{{ __('messages.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehicle->inquiries as $inq)
                            <tr>
                                <td>{{ optional($inq->buyer)->name ?? '-' }}</td>
                                <td><span class="label label-default">{{ $inq->inquiry_type }}</span></td>
                                <td>{{ \Illuminate\Support\Str::limit($inq->message, 80) }}</td>
                                <td>{{ $inq->offered_price ? number_format($inq->offered_price, 2) : '-' }}</td>
                                <td>
                                    @php
                                        $inqColors = ['new'=>'warning','contacted'=>'info','negotiating'=>'primary','closed_won'=>'success','closed_lost'=>'danger'];
                                    @endphp
                                    <span class="label label-{{ $inqColors[$inq->status] ?? 'default' }}">{{ $inq->status }}</span>
                                </td>
                                <td>{{ $inq->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                    <p class="text-muted">@lang('carmarket::lang.inquiries') - 0</p>
                @endif
            </div>
        </div>

        {{-- Right: Price, Seller, Actions --}}
        <div class="col-md-4">
            {{-- Price Card --}}
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                <h2 class="text-success" style="font-size: 28px; font-weight: 700; margin-top: 0;">
                    {{ number_format($vehicle->listing_price, 0) }} {{ $vehicle->currency }}
                </h2>
                @if($vehicle->min_price)
                    <p class="text-muted">@lang('carmarket::lang.min_price'): {{ number_format($vehicle->min_price, 0) }} {{ $vehicle->currency }}</p>
                @endif
                <hr>
                <p><i class="fa fa-map-marker"></i> {{ $vehicle->location_city ?? '-' }}{{ $vehicle->location_area ? ', ' . $vehicle->location_area : '' }}</p>
                <p><i class="fa fa-clock-o"></i> {{ $vehicle->created_at->diffForHumans() }}</p>
                <p><i class="fa fa-eye"></i> @lang('carmarket::lang.view_count'): {{ $vehicle->view_count }}</p>
                <p><i class="fa fa-heart"></i> @lang('carmarket::lang.favorites_count'): {{ $vehicle->favorites->count() }}</p>
            </div>

            {{-- Seller Info --}}
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                <h3 class="tw-font-semibold tw-text-lg tw-mb-3">
                    <i class="fa fa-user"></i> @lang('carmarket::lang.seller_info')
                </h3>
                @if($vehicle->seller)
                    <p><strong>{{ $vehicle->seller->name }}</strong></p>
                    <p><i class="fa fa-phone"></i> {{ $vehicle->seller->mobile ?? '-' }}</p>
                    <p><i class="fa fa-envelope"></i> {{ $vehicle->seller->email ?? '-' }}</p>
                @else
                    <p class="text-muted">-</p>
                @endif
            </div>

            {{-- Admin Actions --}}
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                <h3 class="tw-font-semibold tw-text-lg tw-mb-3">
                    <i class="fa fa-wrench"></i> {{ __('messages.actions') }}
                </h3>
                @if($vehicle->listing_status == 'pending')
                    <button class="btn btn-success btn-block approve-btn" data-id="{{ $vehicle->id }}">
                        <i class="fa fa-check"></i> @lang('carmarket::lang.approve')
                    </button>
                    <button class="btn btn-danger btn-block reject-btn" data-id="{{ $vehicle->id }}">
                        <i class="fa fa-times"></i> @lang('carmarket::lang.reject')
                    </button>
                @endif
                @if($vehicle->rejection_reason)
                    <div class="alert alert-danger tw-mt-3">
                        <strong>@lang('carmarket::lang.rejection_reason'):</strong> {{ $vehicle->rejection_reason }}
                    </div>
                @endif
            </div>

            {{-- Seller Change Logs --}}
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                <h3 class="tw-font-semibold tw-text-lg tw-mb-3">
                    <i class="fa fa-history"></i> Change Log
                    <span class="badge">{{ $vehicle->auditLogs->count() }}</span>
                </h3>
                @if($vehicle->auditLogs->count())
                    @foreach($vehicle->auditLogs->take(10) as $log)
                        <div class="callout callout-info" style="padding: 10px 12px; margin-bottom: 10px;">
                            <p style="margin:0 0 6px;"><strong>{{ $log->created_at->format('Y-m-d H:i') }}</strong></p>
                            <p style="margin:0 0 6px;">
                                By: {{ optional($log->changedByContact)->name ?? optional($log->changedByUser)->username ?? 'Unknown' }}
                            </p>
                            @if(!empty($log->changed_fields))
                                <ul style="margin:0; padding-left: 18px;">
                                    @foreach($log->changed_fields as $field)
                                        <li>
                                            <strong>{{ $field }}</strong>:
                                            <span class="text-danger">{{ data_get($log->old_values, $field, '-') }}</span>
                                            →
                                            <span class="text-success">{{ data_get($log->new_values, $field, '-') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                @else
                    <p class="text-muted">No seller change logs found.</p>
                @endif
            </div>

            {{-- Reports --}}
            @if($vehicle->reports->count())
            <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                <h3 class="tw-font-semibold tw-text-lg tw-mb-3">
                    <i class="fa fa-flag text-danger"></i> @lang('carmarket::lang.reports')
                    <span class="badge bg-red">{{ $vehicle->reports->count() }}</span>
                </h3>
                @foreach($vehicle->reports as $r)
                <div class="callout callout-warning" style="padding: 8px 12px;">
                    <p><strong>{{ $r->reason }}</strong> - {{ optional($r->reporter)->name }}</p>
                    <p class="text-muted">{{ $r->details }}</p>
                    <small>{{ $r->created_at->format('Y-m-d H:i') }}</small>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Similar Vehicles --}}
    @if($similar->count())
    <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
        <h3 class="tw-font-semibold tw-text-lg tw-mb-3">
            <i class="fa fa-th-large"></i> @lang('carmarket::lang.similar_vehicles')
        </h3>
        <div class="row">
            @foreach($similar as $sv)
            <div class="col-md-4 col-sm-6">
                <div class="box box-widget">
                    <div class="box-body" style="padding: 10px;">
                        @php $sImg = $sv->media->firstWhere('is_primary', true) ?? $sv->media->first(); @endphp
                        @if($sImg)
                            <img src="{{ asset('storage/' . $sImg->file_path) }}" style="width:100%;height:120px;object-fit:cover;border-radius:6px;" onerror="this.src='/img/default.png'">
                        @else
                            <img src="/img/default.png" style="width:100%;height:120px;object-fit:cover;border-radius:6px;">
                        @endif
                        <h4 style="margin: 8px 0 4px;">
                            <a href="{{ route('carmarket.vehicles.show', $sv->id) }}">{{ $sv->getTitle() }}</a>
                        </h4>
                        <p class="text-success" style="font-weight:700;margin:0;">{{ number_format($sv->listing_price, 0) }} {{ $sv->currency }}</p>
                        <small class="text-muted">{{ $sv->location_city }}</small>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Approve
    $(document).on('click', '.approve-btn', function() {
        var id = $(this).data('id');
        if (!confirm('@lang("carmarket::lang.approve") ?')) return;
        $.post("{{ url('carmarket/vehicles') }}/" + id + "/approve", { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success) { toastr.success(res.msg); location.reload(); }
                else { toastr.error(res.msg); }
            });
    });

    // Reject
    $(document).on('click', '.reject-btn', function() {
        var id = $(this).data('id');
        var reason = prompt('@lang("carmarket::lang.enter_rejection_reason")');
        if (reason === null) return;
        $.post("{{ url('carmarket/vehicles') }}/" + id + "/reject", {
            _token: '{{ csrf_token() }}', reason: reason
        }).done(function(res) {
            if (res.success) { toastr.success(res.msg); location.reload(); }
            else { toastr.error(res.msg); }
        });
    });
});
</script>
@endsection
