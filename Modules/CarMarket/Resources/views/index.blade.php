@extends('layouts.app')

@section('title', __('carmarket::lang.module_title'))

@section('javascript')
<script>
$(document).ready(function() {
    var vehiclesTable = $('#vehicles-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('carmarket.vehicles.datatables') }}",
            data: function(d) {
                d.listing_status = $('#filter_status').val();
                d.condition = $('#filter_condition').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', orderable: true, searchable: false },
            { data: 'primary_image', name: 'primary_image', orderable: false, searchable: false,
                render: function(data) {
                    if (data) {
                        return '<img src="' + data + '" style="width:60px;height:45px;object-fit:cover;border-radius:4px;" onerror="this.src=\'/img/default.png\'">';
                    }
                    return '<span class="text-muted"><i class="fa fa-image"></i></span>';
                }
            },
            { data: 'title', name: 'title', orderable: false, searchable: true },
            { data: 'listing_price', name: 'listing_price', orderable: true,
                render: function(data, type, row) {
                    return '<strong class="text-success">' + parseFloat(data).toLocaleString() + ' ' + (row.currency || 'EGP') + '</strong>';
                }
            },
            { data: 'condition', name: 'condition', orderable: true,
                render: function(data) {
                    var cls = data === 'new' ? 'label-info' : 'label-warning';
                    return '<span class="label ' + cls + '">' + data + '</span>';
                }
            },
            { data: 'seller_name', name: 'seller_name', orderable: false },
            { data: 'seller_phone', name: 'seller_phone', orderable: false },
            { data: 'listing_status', name: 'listing_status', orderable: true,
                render: function(data) {
                    var colors = {
                        'draft': 'default', 'pending': 'warning', 'active': 'success',
                        'sold': 'primary', 'reserved': 'info', 'expired': 'danger', 'rejected': 'danger'
                    };
                    return '<span class="label label-' + (colors[data] || 'default') + '">' + data + '</span>';
                }
            },
            { data: 'view_count', name: 'view_count', orderable: true },
            { data: 'inquiries_count', name: 'inquiries_count', orderable: false },
            { data: 'media_count', name: 'media_count', orderable: false },
            { data: 'created_at', name: 'created_at', orderable: true },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25
    });

    // Filter change
    $('#filter_status, #filter_condition').change(function() {
        vehiclesTable.ajax.reload();
    });

    // Approve
    $(document).on('click', '.approve-btn', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (!confirm('@lang("carmarket::lang.approve_confirm")')) return;
        $.post("{{ url('carmarket/vehicles') }}/" + id + "/approve", { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success) {
                    toastr.success(res.msg);
                    vehiclesTable.ajax.reload(null, false);
                } else {
                    toastr.error(res.msg);
                }
            });
    });

    // Reject
    $(document).on('click', '.reject-btn', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var reason = prompt('@lang("carmarket::lang.enter_rejection_reason")');
        if (reason === null) return;
        $.post("{{ url('carmarket/vehicles') }}/" + id + "/reject", {
            _token: '{{ csrf_token() }}',
            reason: reason
        }).done(function(res) {
            if (res.success) {
                toastr.success(res.msg);
                vehiclesTable.ajax.reload(null, false);
            } else {
                toastr.error(res.msg);
            }
        });
    });

    // Deactivate
    $(document).on('click', '.deactivate-btn', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (!confirm('@lang("carmarket::lang.deactivate_confirm")')) return;
        $.post("{{ url('carmarket/vehicles') }}/" + id + "/deactivate", { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success) {
                    toastr.success(res.msg);
                    vehiclesTable.ajax.reload(null, false);
                } else {
                    toastr.error(res.msg);
                }
            });
    });
});
</script>
@endsection

@section('content')
@include('carmarket::layouts.nav')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('carmarket::lang.module_title')
    </h1>
    <p class="tw-text-gray-700 tw-mt-1">
        @lang('carmarket::lang.module_subtitle')
    </p>
</section>

<section class="content no-print">
    {{-- Stats Cards --}}
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('carmarket::lang.active_listings')</span>
                    <span class="info-box-number">{{ $stats['active'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('carmarket::lang.pending_approval')</span>
                    <span class="info-box-number">{{ $stats['pending'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon"><i class="fa fa-handshake-o"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('carmarket::lang.sold_vehicles')</span>
                    <span class="info-box-number">{{ $stats['sold'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon"><i class="fa fa-envelope"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('carmarket::lang.new_inquiries')</span>
                    <span class="info-box-number">{{ $stats['new_inquiries'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Create New Listing Button --}}
    <div class="tw-mb-4">
        <a href="{{ route('carmarket.vehicles.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> @lang('carmarket::lang.add_vehicle')
        </a>
    </div>

    {{-- Filters --}}
    <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>@lang('carmarket::lang.listing_status')</label>
                    <select id="filter_status" class="form-control">
                        <option value="">@lang('carmarket::lang.all_statuses')</option>
                        @foreach(['draft','pending','active','sold','reserved','expired','rejected'] as $s)
                            <option value="{{ $s }}">@lang('carmarket::lang.' . $s)</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>@lang('carmarket::lang.condition')</label>
                    <select id="filter_condition" class="form-control">
                        <option value="">@lang('carmarket::lang.all_conditions')</option>
                        <option value="new">@lang('carmarket::lang.new_car')</option>
                        <option value="used">@lang('carmarket::lang.used_car')</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Vehicles Table --}}
    <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover" id="vehicles-table">
                <thead>
                    <tr>
                        <th width="50">{{ __('messages.id') }}</th>
                        <th width="70">@lang('carmarket::lang.media_count')</th>
                        <th>@lang('carmarket::lang.vehicle')</th>
                        <th>@lang('carmarket::lang.listing_price')</th>
                        <th>@lang('carmarket::lang.condition')</th>
                        <th>@lang('carmarket::lang.seller_name')</th>
                        <th>@lang('carmarket::lang.seller_phone')</th>
                        <th>@lang('carmarket::lang.listing_status')</th>
                        <th>@lang('carmarket::lang.view_count')</th>
                        <th>@lang('carmarket::lang.inquiries_count')</th>
                        <th>@lang('carmarket::lang.media_count')</th>
                        <th>{{ __('messages.date') }}</th>
                        <th width="120">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</section>
@endsection
