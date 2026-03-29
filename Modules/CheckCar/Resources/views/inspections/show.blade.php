@extends('layouts.app')

@section('title', __('checkcar::lang.menu_check_car'))

@section('content')
@include('checkcar::layouts.nav')

<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-body">
                    <div class="pull-right">
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            @lang('messages.print')
                        </button>
                        <button type="button" class="btn btn-default" onclick="window.history.back()">
                            @lang('messages.back')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="content invoice">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-6">
                            <h2 class="page-header" style="margin-top:0;">
                                {{ config('app.name') }}
                                <small class="pull-right">{{ optional($inspection->created_at)->format('Y-m-d H:i') }}</small>
                            </h2>
                            <p><strong>@lang('checkcar::lang.buyer_section_title'):</strong></p>
                            <p>{{ $inspection->buyer_full_name }}<br>
                                {{ $inspection->buyer_phone }}<br>
                                {{ $inspection->buyer_id_number }}</p>
                        </div>
                        <div class="col-xs-6 text-right">
                            <h4>@lang('checkcar::lang.seller_section_title')</h4>
                            <p>{{ $inspection->seller_full_name }}<br>
                                {{ $inspection->seller_phone }}<br>
                                {{ $inspection->seller_id_number }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-xs-12">
                            <table class="table table-bordered">
                                <tbody>
                                <tr>
                                    <th>@lang('checkcar::lang.brand')</th>
                                    <td>{{ $inspection->car_brand }}</td>
                                    <th>@lang('checkcar::lang.model')</th>
                                    <td>{{ $inspection->car_model }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('checkcar::lang.color')</th>
                                    <td>{{ $inspection->car_color }}</td>
                                    <th>@lang('checkcar::lang.year')</th>
                                    <td>{{ $inspection->car_year }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('checkcar::lang.chassis_number')</th>
                                    <td>{{ $inspection->car_chassis_number }}</td>
                                    <th>@lang('checkcar::lang.plate_number')</th>
                                    <td>{{ $inspection->car_plate_number }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('checkcar::lang.km')</th>
                                    <td>{{ $inspection->car_kilometers }}</td>
                                    <th>@lang('checkcar::lang.overall_rating')</th>
                                    <td>{{ $inspection->overall_rating }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row" style="margin-top:30px; margin-bottom:30px;">
                        <div class="col-xs-6 text-center" style="border:1px solid #000; padding:20px;">
                            @lang('checkcar::lang.seller_section_title')
                        </div>
                        <div class="col-xs-6 text-center" style="border:1px solid #000; padding:20px;">
                            @lang('checkcar::lang.buyer_section_title')
                        </div>
                    </div>

                    <div class="row" style="margin-bottom:20px;">
                        <div class="col-xs-12 text-center">
                            {{-- Placeholder for car body image similar to reference design --}}
                            <img src="{{ asset('img/car_body_placeholder.png') }}" alt="Car body" style="max-width:100%; height:auto;">
                        </div>
                    </div>

                    <div class="row" style="margin-top:20px;">
                        <div class="col-xs-12">
                            <h4>@lang('checkcar::lang.final_report_title')</h4>
                            <p class="tw-whitespace-pre-line">{{ $inspection->final_summary }}</p>
                        </div>
                    </div>

                    <div class="row" style="margin-top:30px;">
                        <div class="col-xs-12">
                            <h4>@lang('checkcar::lang.section_exterior')</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th>@lang('checkcar::lang.question_title')</th>
                                        <th>@lang('checkcar::lang.status_label')</th>
                                        <th>@lang('checkcar::lang.notes')</th>
                                        <th>@lang('checkcar::lang.images')</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($itemsByCategory as $categoryName => $categoryItems)
                                        @foreach($categoryItems as $item)
                                            <tr>
                                                <td>{{ $item->element ? $item->element->name : 'Unknown' }}</td>
                                                <td>
                                                    @php
                                                        $optionIds = $item->option_ids ?? [];
                                                        if (!empty($optionIds)) {
                                                            $options = \Modules\CheckCar\Entities\CheckCarElementOption::whereIn('id', $optionIds)->get();
                                                            echo $options->pluck('label')->implode(', ');
                                                        }
                                                    @endphp
                                                </td>
                                                <td>{{ $item->note }}</td>
                                                <td>
                                                    @php $images = $item->images ?? []; @endphp
                                                    @foreach($images as $image)
                                                        @if(!empty($image['file_path']))
                                                            <img src="{{ asset('storage/' . $image['file_path']) }}" alt="" style="max-width:80px; max-height:80px; margin:2px;">
                                                        @endif
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-top:40px;">
                        <div class="col-xs-6 text-center">
                            <strong>@lang('checkcar::lang.seller_section_title')</strong>
                            <div style="border-top:1px solid #000; margin-top:40px;"></div>
                        </div>
                        <div class="col-xs-6 text-center">
                            <strong>@lang('checkcar::lang.buyer_section_title')</strong>
                            <div style="border-top:1px solid #000; margin-top:40px;"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
