<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action([\App\Http\Controllers\BusinessLocationController::class, 'store']), 'method' => 'post', 'id' => 'business_location_add_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'business.add_business_location' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('name', __( 'invoice.name' ) . ':*') !!}
                        {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'invoice.name' ) ]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('location_id', __( 'lang_v1.location_id' ) . ':') !!}
                        {!! Form::text('location_id', null, ['class' => 'form-control', 'placeholder' => __( 'lang_v1.location_id' ) ]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('landmark', __( 'business.landmark' ) . ':') !!}
                        {!! Form::text('landmark', null, ['class' => 'form-control', 'placeholder' => __( 'business.landmark' ) ]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('city', __( 'business.city' ) . ':*') !!}
                        {!! Form::text('city', null, ['class' => 'form-control', 'placeholder' => __( 'business.city'), 'required' ]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('zip_code', __( 'business.zip_code' ) . ':*') !!}
                        {!! Form::text('zip_code', null, ['class' => 'form-control', 'placeholder' => __( 'business.zip_code'), 'required' ]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('state', __( 'business.state' ) . ':*') !!}
                        {!! Form::text('state', null, ['class' => 'form-control', 'placeholder' => __( 'business.state'), 'required' ]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('country', __( 'business.country' ) . ':*') !!}
                        {!! Form::text('country', null, ['class' => 'form-control', 'placeholder' => __( 'business.country'), 'required' ]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('mobile', __( 'business.mobile' ) . ':') !!}
                        {!! Form::text('mobile', null, ['class' => 'form-control', 'placeholder' => __( 'business.mobile')]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('alternate_number', __( 'business.alternate_number' ) . ':') !!}
                        {!! Form::text('alternate_number', null, ['class' => 'form-control', 'placeholder' => __( 'business.alternate_number')]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('email', __( 'business.email' ) . ':') !!}
                        {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => __( 'business.email')]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('website', __( 'lang_v1.website' ) . ':') !!}
                        {!! Form::text('website', null, ['class' => 'form-control', 'placeholder' => __( 'lang_v1.website')]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('latitude', __( 'lang_v1.latitude' ) . ':') !!}
                        <div class="input-group">
                            {!! Form::text('latitude', null, ['class' => 'form-control', 'placeholder' => __( 'lang_v1.latitude' ), 'id' => 'latitude', 'readonly']); !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" id="pick_location_btn">@lang('lang_v1.pick_on_map')</button>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('longitude', __( 'lang_v1.longitude' ) . ':') !!}
                        <div class="input-group">
                            {!! Form::text('longitude', null, ['class' => 'form-control', 'placeholder' => __( 'lang_v1.longitude' ), 'id' => 'longitude', 'readonly']); !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" id="clear_location_btn">@lang('lang_v1.clear')</button>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-12" id="map_container" style="display:none;">
                    <div class="form-group">
                        <label>@lang('lang_v1.select_location_on_map')</label>
                        <div id="location_map" style="height: 400px; width: 100%;"></div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('invoice_scheme_id', __('invoice.invoice_scheme_for_pos') . ':*') !!} @show_tooltip(__('tooltip.invoice_scheme'))
                        {!! Form::select('invoice_scheme_id', $invoice_schemes, null, ['class' => 'form-control', 'required',
                        'placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('sale_invoice_scheme_id', __('invoice.invoice_scheme_for_sale') . ':*') !!}
                        {!! Form::select('sale_invoice_scheme_id', $invoice_schemes, null, ['class' => 'form-control', 'required',
                        'placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>


                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('invoice_layout_id', __('lang_v1.invoice_layout_for_pos') . ':*') !!} @show_tooltip(__('tooltip.invoice_layout'))
                        {!! Form::select('invoice_layout_id', $invoice_layouts, null, ['class' => 'form-control', 'required',
                        'placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('sale_invoice_layout_id', __('lang_v1.invoice_layout_for_sale') . ':*') !!} @show_tooltip(__('lang_v1.invoice_layout_for_sale_tooltip'))
                        {!! Form::select('sale_invoice_layout_id', $invoice_layouts, null, ['class' => 'form-control', 'required',
                        'placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('selling_price_group_id', __('lang_v1.default_selling_price_group') . ':') !!} @show_tooltip(__('lang_v1.location_price_group_help'))
                        {!! Form::select('selling_price_group_id', $price_groups, null, ['class' => 'form-control',
                        'placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                @php
                $custom_labels = json_decode(session('business.custom_labels'), true);
                $location_custom_field1 = !empty($custom_labels['location']['custom_field_1']) ? $custom_labels['location']['custom_field_1'] : __('lang_v1.location_custom_field1');
                $location_custom_field2 = !empty($custom_labels['location']['custom_field_2']) ? $custom_labels['location']['custom_field_2'] : __('lang_v1.location_custom_field2');
                $location_custom_field3 = !empty($custom_labels['location']['custom_field_3']) ? $custom_labels['location']['custom_field_3'] : __('lang_v1.location_custom_field3');
                $location_custom_field4 = !empty($custom_labels['location']['custom_field_4']) ? $custom_labels['location']['custom_field_4'] : __('lang_v1.location_custom_field4');
                @endphp
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('custom_field1', $location_custom_field1 . ':') !!}
                        {!! Form::text('custom_field1', null, ['class' => 'form-control',
                        'placeholder' => $location_custom_field1]); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('custom_field2', $location_custom_field2 . ':') !!}
                        {!! Form::text('custom_field2', null, ['class' => 'form-control',
                        'placeholder' => $location_custom_field2]); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('custom_field3', $location_custom_field3 . ':') !!}
                        {!! Form::text('custom_field3', null, ['class' => 'form-control',
                        'placeholder' => $location_custom_field3]); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('custom_field4', $location_custom_field4 . ':') !!}
                        {!! Form::text('custom_field4', null, ['class' => 'form-control',
                        'placeholder' => $location_custom_field4]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <hr>
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('featured_products', __('lang_v1.pos_screen_featured_products') . ':') !!} @show_tooltip(__('lang_v1.featured_products_help'))
                        {!! Form::select('featured_products[]', [], null, ['class' => 'form-control',
                        'id' => 'featured_products', 'multiple']); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <hr>
                <div class="col-sm-12">
                    <strong>@lang('lang_v1.payment_options'): @show_tooltip(__('lang_v1.payment_option_help'))</strong>
                    <div class="form-group">
                        <table class="table table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th class="text-center">@lang('lang_v1.payment_method')</th>
                                    <th class="text-center">@lang('lang_v1.enable')</th>
                                    <th class="text-center @if(empty($accounts)) hide @endif">@lang('lang_v1.default_accounts') @show_tooltip(__('lang_v1.default_account_help'))</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payment_types as $key => $value)
                                <tr>
                                    <td class="text-center">{{$value}}</td>
                                    <td class="text-center">{!! Form::checkbox('default_payment_accounts[' . $key . '][is_enabled]', 1, true); !!}</td>
                                    <td class="text-center @if(empty($accounts)) hide @endif">
                                        {!! Form::select('default_payment_accounts[' . $key . '][account]', $accounts, null, ['class' => 'form-control input-sm']); !!}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.save' )</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
var map;
var marker;

$(document).ready(function() {
    $('#pick_location_btn').on('click', function() {
        if (!map) {
            initLocationMap();
        } else {
            $('#map_container').slideToggle();
            setTimeout(function() {
                map.invalidateSize();
            }, 300);
        }
    });

    $('#clear_location_btn').on('click', function() {
        $('#latitude').val('');
        $('#longitude').val('');
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
    });
});

function initLocationMap() {
    $('#map_container').slideDown();

    var lat = parseFloat($('#latitude').val()) || 0;
    var lng = parseFloat($('#longitude').val()) || 0;

    var center = [lat, lng];

    if (lat === 0 && lng === 0) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                center = [position.coords.latitude, position.coords.longitude];
                map.setView(center, 13);
            });
        } else {
            center = [30.0444, 31.2357]; // Cairo, Egypt
        }
    }

    map = L.map('location_map').setView(center, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    if (lat !== 0 && lng !== 0) {
        marker = L.marker(center, {draggable: true}).addTo(map);

        marker.on('dragend', function(event) {
            var position = marker.getLatLng();
            $('#latitude').val(position.lat);
            $('#longitude').val(position.lng);
        });
    }

    map.on('click', function(event) {
        placeMarker(event.latlng);
    });

    setTimeout(function() {
        map.invalidateSize();
    }, 300);
}

function placeMarker(location) {
    if (marker) {
        marker.setLatLng(location);
    } else {
        marker = L.marker(location, {draggable: true}).addTo(map);

        marker.on('dragend', function(event) {
            var position = marker.getLatLng();
            $('#latitude').val(position.lat);
            $('#longitude').val(position.lng);
        });
    }

    $('#latitude').val(location.lat);
    $('#longitude').val(location.lng);
}
</script>