@extends('layouts.app')

@section('title', __('repair::lang.view_job_sheet'))

@section('content')
@include('repair::layouts.nav')

@php
$custom_labels = json_decode(session('business.custom_labels'), true);
$contact_custom_fields = !empty($jobsheet_settings['contact_custom_fields']) ?
$jobsheet_settings['contact_custom_fields'] : [];
@endphp

<style>
    [dir="rtl"] .pull-right {
        float: left !important;
    }

    [dir="rtl"] .pull-left {
        float: right !important;
    }

    [dir="rtl"] .text-right {
        text-align: left !important;
    }

    [dir="rtl"] .text-left {
        text-align: right !important;
    }

    [dir="rtl"] th, [dir="rtl"] td {
        text-align: right;
    }

    [dir="rtl"] .f-left {
        float: right !important;
    }

    [dir="rtl"] .f-right {
        float: left !important;
    }
</style>
<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('repair::lang.job_sheet')
        (<code>{{$job_sheet->job_sheet_no}}</code>)
    </h1>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header no-print">
                    <div class="box-tools">
                        @if(auth()->user()->can("job_sheet.edit"))
                        <a href="{{action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'edit'], [$job_sheet->id])}}" class="tw-dw-btn tw-dw-btn-info tw-text-white tw-dw-btn-sm cursor-pointer">
                            <i class="fa fa-edit"></i>
                            @lang("messages.edit")
                        </a>
                        @endif
                        <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm" aria-label="Print" id="print_jobsheet">
                            <i class="fa fa-print"></i>
                            @lang( 'repair::lang.print_format_1' )
                        </button>

                        <a class="tw-dw-btn tw-dw-btn-success tw-text-white tw-dw-btn-sm" href="{{action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'print'], [$job_sheet->id])}}" target="_blank">
                            <i class="fas fa-file-pdf"></i>
                            @lang( 'repair::lang.print_format_2' )
                        </a>

                        <a class="tw-dw-btn tw-dw-btn-error tw-text-white tw-dw-btn-sm" href="{{action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'printLabel'], [$job_sheet->id])}}" target="_blank">
                            <i class="fas fa-barcode"></i>
                            @lang( 'repair::lang.print_label' )
                        </a>
                    </div>
                </div>
                <div class="box-body" id="job_sheet" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
                    {{-- business address --}}
                    <div class="width-100">
                        <div class="width-50 f-left" style="padding-top: 40px;">
                            @if(!empty(Session::get('business.logo')))
                            <img src="{{ asset( 'uploads/business_logos/' . Session::get('business.logo') ) }}" alt="Logo" style="width: auto; max-height: 90px; margin: auto;">
                            @endif
                        </div>
                        <div class="width-50 f-left">
                            <p style="text-align: center;padding-top: 40px;padding-left: 110px;">
                                <strong class="font-23">
                                    {{$job_sheet->customer->business->name}}
                                </strong>
                                <br>
                                @if(!empty($job_sheet->businessLocation))
                                {{$job_sheet->businessLocation->name}}<br>
                                @endif
                                <span>
                                    {!!$job_sheet->businessLocation->location_address!!}
                                </span>
                                @if(!empty($job_sheet->businessLocation->mobile))
                                <br>
                                @lang('business.mobile'): {{$job_sheet->businessLocation->mobile}},
                                @endif
                                @if(!empty($job_sheet->businessLocation->alternate_number))
                                @lang('invoice.show_alternate_number'): {{$job_sheet->businessLocation->alternate_number}},
                                @endif
                                @if(!empty($job_sheet->businessLocation->email))
                                <br>
                                @lang('business.email'): {{$job_sheet->businessLocation->email}},
                                @endif

                                @if(!empty($job_sheet->businessLocation->website))
                                @lang('lang_v1.website'): {{$job_sheet->businessLocation->website}}
                                @endif
                            </p>
                        </div>
                    </div>
                    {{-- Job sheet details --}}
                    <table class="table table-bordered" style="margin-top: 15px;">
                        <tr>
                            <th rowspan="3">
                                @lang('receipt.date'):
                                <span style="font-weight: 100">
                                    {{@format_datetime($job_sheet->created_at)}}
                                </span>
                            </th>
                        </tr>
                        <tr>
                            <td>
                                <b>@lang('repair::lang.service_type'):</b>
                                @lang($job_sheet->service_type)
                            </td>
                            <th rowspan="2">
                                <b>
                                    @lang('lang_v1.due_date'):
                                </b>
                                @if(!empty($job_sheet->due_date))
                                <span style="font-weight: 100">
                                    {{@format_datetime($job_sheet->due_date)}}
                                </span>
                                @endif
                                <br>
                                <b>
                                    @lang('lang_v1.delivery_date'):
                                </b>
                                @if(!empty($job_sheet->delivery_date))
                                <span style="font-weight: 100">
                                    {{@format_datetime($job_sheet->delivery_date)}}
                                </span>
                                @endif
                                <br>
                                <b>
                                    @lang('repair::lang.entry_date'):
                                </b>
                                @if(!empty($job_sheet->entry_date))
                                <span style="font-weight: 100">
                                    {{@format_datetime($job_sheet->entry_date)}}
                                </span>
                                @endif
                            </th>
                        </tr>
                        <tr>
                            <td>
                                <b>@lang('repair::lang.job_sheet_no'):</b>
                                {{$job_sheet->job_sheet_no}}
                            </td>
                        </tr>

                        <tr>




                                <td colspan="2">
                                    <b>@lang('repair::lang.vehicle_brand'):</b>
                                    <br>
                                    <b>@lang('repair::lang.vehicle_model'):</b>


                                </td>
                                <td>
                                    {{$job_sheet->brand_name ?? ''}}
                                    <br>
                                    {{$job_sheet->model_name ?? ''}}
                                </td>

                            </tr>
                            <tr>
                                <td colspan="2">
                                    <b>
                                        @lang('repair::lang.plate_number'):
                                    </b>
                                </td>
                                <td>
                                {{$job_sheet->plate_number ?? ''}}

                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <b>
                                        @lang('repair::lang.vehicle_color'):
                                    </b>
                                </td>
                                <td>
                                {{$job_sheet->color ?? ''}}

                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <b>
                                        @lang('repair::lang.chassis_number'):
                                    </b>
                                </td>
                                <td>
                                {{$job_sheet->chassis_number ?? ''}}

                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <b>
                                        @lang('repair::lang.km'):
                                    </b>
                                </td>
                                <td>
                                {{$job_sheet->km ?? ''}}

                                </td>
                            </tr>
                            @if(!empty($job_sheet->motor_cc))
                            <tr>
                                <td colspan="2">
                                    <b>
                                        @lang('car.motor_cc'):
                                    </b>
                                </td>
                                <td>
                                {{$job_sheet->motor_cc}}

                                </td>
                            </tr>
                            @endif
                        <tr>
                            <td colspan="2">
                                <b>
                                    @lang('sale.invoice_no'):
                                </b>
                            </td>
                            <td>
                                @if($job_sheet->invoices->count() > 0)
                                @foreach($job_sheet->invoices as $invoice)
                                {{$invoice->invoice_no}}
                                @if (!$loop->last)
                                {{', '}}
                                @endif
                                @endforeach
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <b>
                                    @lang('sale.status'):
                                </b>
                            </td>
                            <td>
                                {{$job_sheet->status?->name}}
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <b>
                                    @lang('repair::lang.technician'):
                                </b>
                            </td>
                            <td>
                                @if (!empty($job_sheet->service_staff) && $job_sheet->service_staff->count() > 0)
                                    @foreach ($job_sheet->service_staff as $staff)
                                        {{ $staff->technicans }}@if (!$loop->last), @endif
                                    @endforeach
                                @else

                                @endif
                            </td>
                        </tr>



                        <tr>
                            <td colspan="2">
                                <b>
                                    @lang('repair::lang.pre_repair_checklist'):
                                </b>
                            </td>
                            <td>
                                @if (!empty($job_sheet->checklist) && is_array($job_sheet->checklist))
                                    @foreach ($job_sheet->checklist as $check)
                                        <div class="col-xs-4">
                                            @if (isset($check['title']))
                                                @if ($check['title'] == 'yes')
                                                    <i class="fas fa-check-square text-success fa-lg"></i>
                                                @elseif ($check['title'] == 'no')
                                                    <i class="fas fa-window-close text-danger fa-lg"></i>
                                                @elseif ($check['title'] == 'not_applicable')
                                                    <i class="fas fa-square fa-lg"></i>
                                                @endif
                                                {{ $check['title'] }}
                                                <br>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <span></span>
                                @endif
                            </td>
                        </tr>

                        @if($job_sheet->service_type == 'pick_up' || $job_sheet->service_type == 'on_site')
                        <tr>
                            <td colspan="3">
                                <b>
                                    @lang('repair::lang.pick_up_on_site_addr'):
                                </b> <br>
                                {!!$job_sheet->pick_up_on_site_addr!!}
                            </td>
                        </tr>
                        @endif


                        @if(!empty($job_sheet->custom_field_1))
                        <tr>
                            <td colspan="2">
                                <b>
                                    {{$repair_settings['job_sheet_custom_field_1'] ?? __('lang_v1.custom_field', ['number' => 1])}}:
                                </b>
                            </td>
                            <td>
                                {{$job_sheet->custom_field_1}}
                            </td>
                        </tr>
                        @endif
                        </td>
                        </tr>
                        {{-- <tr>
                            <th colspan="2">@lang('repair::lang.parts_used'):</th>
                            <th colspan="2">@lang('repair::lang.parts_used'):</th>
                            <td>
                                @if(!$parts->isEmpty())
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>@lang('repair::lang.part_name')</th>
                                            <th>@lang('repair::lang.quantity')</th>
                                            <th>@lang('repair::lang.unit')</th>
                                            <th>@lang('repair::lang.total_price')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($parts as $part)
                                        <tr>
                                            <td>{{ $part->product_name_with_approval }}</td>
                                            <td>{{ $part->quantity }}</td>
                                            <td>{{ $part->unit }}</td>
                                            <td>{{ number_format($part->total_price, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @else
                                <p>@lang('repair::lang.no_parts_used')</p>
                                @endif
                            </td>
                            
                        </tr> --}}

                        @if(!empty($job_sheet->custom_field_2))
                        <tr>
                            <td colspan="2">
                                <b>
                                    {{$repair_settings['job_sheet_custom_field_2'] ?? __('lang_v1.custom_field', ['number' => 2])}}:
                                </b>
                            </td>
                            <td>
                                {{$job_sheet->custom_field_2}}
                            </td>
                        </tr>
                        @endif
                        @if(!empty($job_sheet->custom_field_3))
                        <tr>
                            <td colspan="2">
                                <b>
                                    {{$repair_settings['job_sheet_custom_field_3'] ?? __('lang_v1.custom_field', ['number' => 3])}}:
                                </b>
                            </td>
                            <td>
                                {{$job_sheet->custom_field_3}}
                            </td>
                        </tr>
                        @endif
                        @if(!empty($job_sheet->custom_field_4))
                        <tr>
                            <td colspan="2">
                                <b>
                                    {{$repair_settings['job_sheet_custom_field_4'] ?? __('lang_v1.custom_field', ['number' => 4])}}:
                                </b>
                            </td>
                            <td>
                                {{$job_sheet->custom_field_4}}
                            </td>
                        </tr>
                        @endif
                        @if(!empty($job_sheet->custom_field_5))
                        <tr>
                            <td colspan="2">
                                <b>
                                    {{$repair_settings['job_sheet_custom_field_5'] ?? __('lang_v1.custom_field', ['number' => 5])}}:
                                </b>
                            </td>
                            <td>
                                {{$job_sheet->custom_field_5}}
                            </td>
                        </tr>
                        @endif

                        <tr>
                            <td colspan="2">
                                <b>
                                    @lang('repair::lang.problem_reported_by_customer'):
                                </b> <br>
                            </td>
                            <td>
                                {{$job_sheet->problem_reported_by_customer}}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <b>
                                    @lang('repair::lang.booking_note'):
                                </b> <br>
                            </td>
                            <td>
                                {{$job_sheet->booking_note}}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <b>
                                    @lang('repair::lang.car_condition'):
                                </b> <br>
                            </td>
                            <td>
                                {{$job_sheet->car_condition}}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <b>
                                    @lang('repair::lang.comment_by_ss'):
                                </b>
                            </td>
                            <td>
                                {{$job_sheet->comment_by_ss}}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <b>
                                    @lang('repair::lang.estimated_cost'):
                                </b>
                            </td>
                            <td>
                                <span class="display_currency" data-currency_symbol="true">
                                    {{$job_sheet->estimated_cost}}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <strong>
                                    @lang("lang_v1.terms_conditions"):
                                </strong>
                                @if(!empty($repair_settings['repair_tc_condition']))
                                {!!$repair_settings['repair_tc_condition']!!}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <b>
                                    @lang('repair::lang.customer_signature'):
                                </b>
                            </td>
                            <td>
                                <b>
                                    @lang('repair::lang.authorized_signature'):
                                </b>
                            </td>
                        </tr>
                    </table>
                    @if(!$parts->isEmpty())
                    <h4>@lang('repair::lang.parts_used')</h4>
                    <table class="table table-bordered table-sm w-100">
                        <thead>
                            <tr>
                                <th>@lang('repair::lang.part_name')</th>
                                <th>@lang('repair::lang.quantity')</th>
                                <th>@lang('repair::lang.unit')</th>
                                <th>@lang('repair::lang.total_price')</th>
                                <th>@lang('repair::lang.approval_status')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($parts as $part)
                            <tr>
                                <td>{{ $part->product_name }}</td>
                                <td>{{ $part->quantity }}</td>
                                <td>{{ $part->unit }}</td>
                                <td>{{ number_format($part->total_price, 2) }}</td>
                                <td>{{ $part->approval_status }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>@lang('repair::lang.no_parts_used')</p>
                @endif
                    
                </div>
                
            </div>
        </div>
    </div>
    <div class="row">
        @if($job_sheet->media->count() > 0)
        <div class="col-md-6">
            <div class="box box-solid no-print">
                <div class="box-header with-border">
                    <h4 class="box-title">
                        @lang('repair::lang.uploaded_image_for', ['job_sheet_no' => $job_sheet->job_sheet_no])
                    </h4>
                </div>
                <div class="box-body">
                    @includeIf('repair::job_sheet.partials.document_table_view', ['medias' => $job_sheet->media])
                </div>
            </div>
        </div>
        @endif
        <div class="col-md-6">
            <div class="box box-solid box-solid no-print">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('repair::lang.activities') }}:</h3>
                </div>
                <!-- /.box-header -->
                @include('repair::repair.partials.activities')
            </div>
        </div>
    </div>

    @if(isset($booking_media) && $booking_media->count() > 0)
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid no-print">
                <div class="box-header with-border">
                    <h4 class="box-title">
                        @lang('repair::lang.uploaded_image_for', ['job_sheet_no' => $job_sheet->job_sheet_no]) - @lang('restaurant.booking')
                    </h4>
                </div>
                <div class="box-body">
                    @includeIf('repair::job_sheet.partials.document_table_view', ['medias' => $booking_media])
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(isset($inspection_documents) && $inspection_documents->isNotEmpty())
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid no-print">
                <div class="box-header with-border">
                    <h4 class="box-title">
                        @lang('checkcar::lang.car_inspections') - @lang('repair::lang.uploaded_image_for', ['job_sheet_no' => $job_sheet->job_sheet_no])
                    </h4>
                </div>
                <div class="box-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('repair::lang.type')</th>
                                <th>@lang('repair::lang.document')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['buyer','seller'] as $party)
                                @if(isset($inspection_documents[$party]))
                                    @foreach($inspection_documents[$party] as $doc)
                                    <tr>
                                        <td>{{ ucfirst($party) }} - {{ $doc->document_type }}</td>
                                        <td>
                                            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank">
                                                {{ basename($doc->file_path) }}
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    </div>
</section>
<!-- /.content -->
@stop
@section('css')
<style type="text/css">
    .table-bordered>thead>tr>th,
    .table-bordered>tbody>tr>th,
    .table-bordered>tfoot>tr>th,
    .table-bordered>thead>tr>td,
    .table-bordered>tbody>tr>td,
    .table-bordered>tfoot>tr>td {
        border: 1px solid #1d1a1a;
    }
</style>
@stop
@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#print_jobsheet').click(function() {
            $('#job_sheet').printThis();
        });

        // Auto-trigger print dialog if print parameter is in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('print') && urlParams.get('print') === '1') {
            // Remove print parameter from URL without reloading
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({path: newUrl}, '', newUrl);

            // Trigger print dialog
            setTimeout(function() {
                $('#job_sheet').printThis();
            }, 500);
        }

        $(document).on('click', '.delete_media', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            var this_btn = $(this);
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((confirmed) => {
                if (confirmed) {
                    $.ajax({
                        method: 'GET',
                        url: url,
                        dataType: 'json',
                        success: function(result) {
                            if (result.success == true) {
                                this_btn.closest('tr').remove();
                                toastr.success(result.msg);
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@stop