@extends('layouts.app')

@section('title', __('Service Overview') . ' - ' . $service->name)

@section('content')

    @include('products.layouts.nav')

<section class="content-header">
    <h1>
        {{ __('Service Overview') }}
        <small>{{ $service->name }}</small>
    </h1>
    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
        @if (count($locations) > 1)
            {!! Form::select('service_location', $locations->pluck('name', 'id')->all(), $location_id ?? null, [
                'class' => 'form-control select2',
                'placeholder' => __('lang_v1.select_location'),
                'id' => 'service_location',
                'onchange' => "window.location.href = '" . action([\App\Http\Controllers\ServiceController::class, 'getServiceOverview'], [$service->id]) . "' + (this.value ? ('?location=' + this.value) : '')",
            ]) !!}
        @endif
    </div>
</section>

<!-- Main content -->
<section class="content">
    <!-- Service Info -->
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-info-circle"></i> {{ __('Service Information') }}</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>{{ __('Name') }}:</strong> {{ $service->name }}
                </div>
                <div class="col-md-3">
                    <strong>{{ __('SKU') }}:</strong> {{ $service->sku }}
                </div>
                <div class="col-md-3">
                    <strong>{{ __('Price') }}:</strong> EGP {{ number_format($service->selling_price, 2) }}
                </div>
                <div class="col-md-3">
                    <strong>{{ __('Labour Hours') }}:</strong> {{ $service->serviceHours ? $service->serviceHours . ' hrs' : '-' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon"><i class="fa fa-wrench"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('Joborders') }}</span>
                    <span class="info-box-number">{{ $joborder_count }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon"><i class="fa fa-shopping-cart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('Sales') }}</span>
                    <span class="info-box-number">{{ $sale_count }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon"><i class="fa fa-cubes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('Total Quantity') }}</span>
                    <span class="info-box-number">{{ $summary_quantity }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon"><i class="fa fa-money"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('Total Revenue') }}</span>
                    <span class="info-box-number">EGP {{ number_format($summary_revenue, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Joborders Table -->
    @if($joborders->count() > 0)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-wrench"></i> {{ __('Joborders') }}</h3>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="joborders_table">
                    <thead>
                        <tr>
                            <th>{{ __('Job Sheet No') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Location') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Total') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($joborders as $joborder)
                        <tr>
                            <td><strong>{{ $joborder->job_sheet_no ?? '#' . $joborder->id }}</strong></td>
                            <td>{{ $joborder->contact_name }}</td>
                            <td>{{ $joborder->location_name }}</td>
                            <td>
                                @if($joborder->status_name)
                                    <span class="label label-info">{{ $joborder->status_name }}</span>
                                @else
                                    <span class="label label-default">{{ __('N/A') }}</span>
                                @endif
                            </td>
                            <td>{{ $joborder->quantity }}</td>
                            <td>EGP {{ number_format($joborder->price ?? 0, 2) }}</td>
                            <td>EGP {{ number_format(($joborder->price ?? 0) * $joborder->quantity, 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($joborder->created_at)->format('Y-m-d') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f5f5f5; font-weight: bold;">
                            <td colspan="4" style="text-align: right;"><strong>{{ __('Total') }}:</strong></td>
                            <td>{{ $total_joborder_quantity }}</td>
                            <td>-</td>
                            <td>EGP {{ number_format($total_joborder_revenue, 2) }}</td>
                            <td>-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i> {{ __('No joborders found for this service') }}
    </div>
    @endif

    <!-- Sales Table -->
    @if($sales->count() > 0)
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-shopping-cart"></i> {{ __('Sales') }}</h3>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="sales_table">
                    <thead>
                        <tr>
                            <th>{{ __('Invoice No') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Location') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Unit Price') }}</th>
                            <th>{{ __('Discount') }}</th>
                            <th>{{ __('Total') }}</th>
                            <th>{{ __('Payment Status') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $sale)
                        <tr>
                            <td><strong>{{ $sale->invoice_no }}</strong></td>
                            <td>{{ $sale->contact_name }}</td>
                            <td>{{ $sale->location_name }}</td>
                            <td>{{ $sale->quantity }}</td>
                            <td>EGP {{ number_format($sale->unit_price, 2) }}</td>
                            <td>EGP {{ number_format($sale->line_discount_amount ?? 0, 2) }}</td>
                            <td>EGP {{ number_format($sale->line_total, 2) }}</td>
                            <td>
                                @php
                                    $ps = strtolower($sale->payment_status ?? 'due');
                                    $ps_color = match($ps){
                                        'paid' => '#00a65a',
                                        'partial' => '#f39c12',
                                        'due' => '#dd4b39',
                                        default => '#777'
                                    };
                                @endphp
                                <span class="label" style="background: {{ $ps_color }};">{{ __('lang_v1.' . $ps) }}</span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($sale->transaction_date)->format('Y-m-d') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f5f5f5; font-weight: bold;">
                            <td colspan="6" style="text-align: right;"><strong>{{ __('Total') }}:</strong></td>
                            <td>EGP {{ number_format($total_sale_revenue, 2) }}</td>
                            <td colspan="2">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i> {{ __('No sales found for this service') }}
    </div>
    @endif
</section>
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function(){
    // Initialize DataTables
    $('#joborders_table').DataTable({
        responsive: true,
        order: [[7, 'desc']],
        pageLength: 25
    });

    $('#sales_table').DataTable({
        responsive: true,
        order: [[8, 'desc']],
        pageLength: 25
    });
});
</script>
@endsection
