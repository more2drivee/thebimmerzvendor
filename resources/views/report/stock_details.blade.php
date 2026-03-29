@extends('layouts.app')

@section('title', __('report.stock_details'))

@section('content')
@if(empty($product_details))
    <!-- Product Selection Form for non-Ajax requests -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">@lang('report.select_product_for_stock_details')</h3>
                </div>
                <div class="box-body">
                    {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockDetails']), 'method' => 'post', 'id' => 'stock_details_form']) !!}
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('product_id', __('lang_v1.product') . ':') !!}
                                {!! Form::select('product_id', [], null, ['class' => 'form-control select2', 'id' => 'product_id', 'placeholder' => __('lang_v1.search_product_placeholder'), 'required']); !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('location_id', __('purchase.business_location').':') !!}
                                {!! Form::select('location_id', $business_locations ?? [], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label><br>
                                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="get_stock_details">@lang('report.get_details')</button>
                            </div>
                        </div>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>

            <!-- Results Area -->
            <div id="stock_details_result" style="display: none;">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">@lang('report.stock_details')</h3>
                    </div>
                    <div class="box-body" id="stock_details_content">
                        <!-- Table will be loaded here via Ajax -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <!-- Stock Details Table for direct requests with data -->
    <div class="row">
        <div class="col-md-10 col-md-offset-1 col-xs-12">
            <div class="table-responsive">
                <table class="table table-condensed bg-gray">
                    <tr>
                        <th>@lang('report.sku')</th>
                        <th>@lang('report.variation')</th>
                        <th>@lang('sale.unit_price')</th>
                        <th>@lang('report.current_stock')</th>
                        <th>@lang('report.total_unit_sold')</th>
                        <th>@lang('lang_v1.total_unit_transfered')</th>
                        <th>@lang('lang_v1.total_unit_adjusted')</th>
                    </tr>
                    @foreach( $product_details as $details )
                        <tr>
                            <td>{{ $details->sub_sku}}</td>
                            <td>
                                {{ $details->product . '-' . $details->product_variation .
                                '-' .  $details->variation }}
                            </td>
                            <td><span class="display_currency" data-currency_symbol=true>{{$details->sell_price_inc_tax}}</span></td>
                            <td>
                                @if($details->stock)
                                    <span class="display_currency" data-currency_symbol=false>{{ (float)$details->stock }}</span> {{$details->unit}}
                                @else
                                    0
                                @endif
                            </td>
                            <td>
                                @if($details->total_sold)
                                    <span class="display_currency" data-currency_symbol=false>{{ (float)$details->total_sold }}</span> {{$details->unit}}
                                @else
                                    0
                                @endif
                            </td>
                            <td>
                                @if($details->total_transfered)
                                    <span class="display_currency" data-currency_symbol=false>{{ (float)$details->total_transfered }}</span> {{$details->unit}}
                                @else
                                    0
                                @endif
                            </td>
                            <td>
                                @if($details->total_adjusted)
                                    <span class="display_currency" data-currency_symbol=false>{{ (float)$details->total_adjusted }}</span> {{$details->unit}}
                                @else
                                    0
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@endif
@endsection

@section('javascript')
<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        //Initialize select2 for product search
        $('#product_id').select2({
            ajax: {
                url: '/purchases/get_products',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        term: params.term, // search term
                    };
                },
                processResults: function (data) {
                    var data_formated = [];
                    data.forEach(function (item) {
                        var temp = {
                            'id': item.variation_id,
                            'text': item.text
                        }
                        data_formated.push(temp);
                    });
                    return {
                        results: data_formated
                    };
                }
            },
            minimumInputLength: 1,
        });

        //Handle get details button click
        $('#get_stock_details').click(function() {
            var product_id = $('#product_id').val();
            var location_id = $('#location_id').val();

            if (!product_id) {
                alert('@lang("report.please_select_product")');
                return;
            }

            var data = {
                product_id: product_id,
                location_id: location_id
            };

            $.ajax({
                url: '{{ action([\App\Http\Controllers\ReportController::class, "getStockDetails"]) }}',
                data: data,
                dataType: 'html',
                success: function(data) {
                    $('#stock_details_content').html(data);
                    $('#stock_details_result').show();
                    __currency_convert_recursively($('#stock_details_content'));
                },
                error: function() {
                    alert('@lang("messages.something_went_wrong")');
                }
            });
        });
    });
</script>
@endsection