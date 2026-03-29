@extends('layouts.app')

@section('title', __('sale.pos_sale'))

@section('content')
    <section class="content no-print">
        <input type="hidden" id="amount_rounding_method" value="{{ $pos_settings['amount_rounding_method'] ?? '' }}">
        @if (!empty($pos_settings['allow_overselling']))
            <input type="hidden" id="is_overselling_allowed">
        @endif
        @if (session('business.enable_rp') == 1)
            <input type="hidden" id="reward_point_enabled">
        @endif
        @php
            $is_discount_enabled = $pos_settings['disable_discount'] != 1 ? true : false;
            $is_rp_enabled = session('business.enable_rp') == 1 ? true : false;
        @endphp
        {!! Form::open([
            'url' => action([\App\Http\Controllers\SellPosController::class, 'store']),
            'method' => 'post',
            'id' => 'add_pos_sell_form',
        ]) !!}
        <div class="row mb-12">
            <div class="col-md tw-row tw-flex md:tw-flex-row tw-flex-col tw-items-start md:tw-gap-4">
                    {{-- <div class="@if (empty($pos_settings['hide_product_suggestion'])) col-md-7 @else col-md-10 col-md-offset-1 @endif no-padding pr-12"> --}}
                    <div class="tw-px-3 tw-w-full lg:tw-px-0 lg:tw-pr-0 @if(empty($pos_settings['hide_product_suggestion'])) md:tw-w-1/2 lg:tw-w-[60%]  @else md:tw-w-full lg:tw-w-[100%] @endif">

                        <div class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-rounded-2xl tw-bg-white tw-mb-2 md:tw-mb-8 tw-p-2">

                            {{-- <div class="box box-solid mb-12 @if (!isMobile()) mb-40 @endif"> --}}
                                <div class="box-body pb-0">
                                    {!! Form::hidden('location_id', $default_location->id ?? null, [
                                        'id' => 'location_id',
                                        'data-receipt_printer_type' => !empty($default_location->receipt_printer_type)
                                            ? $default_location->receipt_printer_type
                                            : 'browser',
                                        'data-default_payment_accounts' => $default_location->default_payment_accounts ?? '',
                                    ]) !!}
                                    <!-- sub_type -->
                                    {!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
                                    <input type="hidden" id="item_addition_method"
                                        value="{{ $business_details->item_addition_method }}">
                                    @include('sale_pos.partials.pos_form')

                                    @include('sale_pos.partials.pos_form_totals')

                                    @include('sale_pos.partials.payment_modal')

                                    @if (empty($pos_settings['disable_suspend']))
                                        @include('sale_pos.partials.suspend_note_modal')
                                    @endif

                                    @if (empty($pos_settings['disable_recurring_invoice']))
                                        @include('sale_pos.partials.recurring_invoice_modal')
                                    @endif
                                </div>
                            {{-- </div> --}}
                        </div>
                    </div>
                    @if (empty($pos_settings['hide_product_suggestion']) && !isMobile())
                        <div class="md:tw-no-padding tw-w-full md:tw-w-1/2 lg:tw-w-[40%] tw-px-5">

                            @include('sale_pos.partials.pos_sidebar')
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @include('sale_pos.partials.pos_form_actions')
        {!! Form::close() !!}
    </section>

    <!-- This will be printed -->
    <section class="invoice print_section" id="receipt_section">
    </section>
    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>
    @if (empty($pos_settings['hide_product_suggestion']) && isMobile())
        @include('sale_pos.partials.mobile_product_suggestions')
    @endif
    <!-- /.content -->
    <div class="modal fade register_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade close_register_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <!-- quick product modal -->
    <div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

    <div class="modal fade" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    @include('sale_pos.partials.configure_search_modal')

    @include('sale_pos.partials.recent_transactions_modal')

    @include('sale_pos.partials.weighing_scale_modal')
@endsection

@section('javascript')
    <script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    @include('sale_pos.partials.keyboard_shortcuts')

    <!-- Call restaurant module if defined -->
    @if (in_array('tables', $enabled_modules) ||
            in_array('modifiers', $enabled_modules) ||
            in_array('service_staff', $enabled_modules))
        <script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    <!-- include module js -->
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_js_path']))
                @includeIf($value['module_js_path'], ['view_data' => $value['view_data']])
            @endif
        @endforeach
    @endif
    

    <script>
        $(document).ready(function() {
            function loadCustomerVehicles(customerId) {
                var $vehicleDropdown = $('#repair_device_id');
                if ($vehicleDropdown.length === 0) return;

                if (!customerId) {
                    $vehicleDropdown.html('<option value="">{{ __('messages.please_select') }}</option>').trigger('change');
                    return;
                }

                $vehicleDropdown.html('<option value="">Loading vehicles...</option>');
                $.ajax({
                    url: '/bookings/get-custumer-vehicles/' + customerId,
                    type: 'GET',
                    success: function(vehicles) {
                        var options = '<option value="">{{ __('messages.please_select') }}</option>';
                        if (vehicles && vehicles.length > 0) {
                            vehicles.forEach(function(vehicle) {
                                var deviceInfo = vehicle.model_name || 'Unknown Model';
                                if (vehicle.plate_number) deviceInfo += ' - ' + vehicle.plate_number;
                                if (vehicle.color) deviceInfo += ' (' + vehicle.color + ')';
                                options += '<option value="' + vehicle.id + '" data-model-id="' + (vehicle.model_id || '') + '">' + deviceInfo + '</option>';
                            });
                        } else {
                            options += '<option value="" disabled>No vehicles found for this customer</option>';
                        }
                        $vehicleDropdown.html(options).trigger('change');
                    },
                    error: function() {
                        $vehicleDropdown.html('<option value="">Error loading vehicles</option>');
                        toastr.error('Error loading customer vehicles');
                    }
                });
            }

            // Handle vehicle selection change to apply compatibility filters
            $(document).on('change', '#repair_device_id', function() {
                var vehicleId = $(this).val();
                
                if (!vehicleId) {
                    // Clear compatibility filters
                    global_compat_brand_category_id = null;
                    global_compat_model_id = null;
                    
                    // Refresh product list
                    var location_id = $('input#location_id').val();
                    if (location_id) {
                        $('input#suggestion_page').val(1);
                        get_product_suggestion_list(
                            null,
                            null,
                            location_id,
                            null,
                            null,
                            null,
                            null,
                            null
                        );
                    }
                    return;
                }

                // Get vehicle compatibility information
                $.ajax({
                    url: '/bookings/get-vehicle-compatibility/' + vehicleId,
                    type: 'GET',
                    success: function(response) {
                        if (response && response.model_id) {
                            // Apply compatibility filters
                            global_compat_brand_category_id = response.brand_category_id || null;
                            global_compat_model_id = response.model_id || null;
                            
                            // Refresh product list with compatibility filters
                            var location_id = $('input#location_id').val();
                            if (location_id) {
                                $('input#suggestion_page').val(1);
                                get_product_suggestion_list(
                                    null,
                                    null,
                                    location_id,
                                    null,
                                    null,
                                    null,
                                    global_compat_brand_category_id,
                                    global_compat_model_id
                                );
                            }
                            
                            if (typeof toastr !== 'undefined') {
                                toastr.success('Compatible products highlighted with green frame and sorted to top');
                            }
                        }
                    },
                    error: function() {
                        console.error('Error loading vehicle compatibility');
                    }
                });
            });

            // Bind to customer change/select events
            $(document).on('change', '#customer_id', function() {
                loadCustomerVehicles($(this).val());
            });
            $('#customer_id').on('select2:select', function(e) {
                var cid = e.params.data.id;
                loadCustomerVehicles(cid);
            });
            $('#customer_id').on('select2:clear', function() {
                loadCustomerVehicles(null);
            });

            // Preload if customer is pre-selected (walk-in)
            setTimeout(function() {
                var preSelected = $('#customer_id').val();
                if (preSelected) {
                    loadCustomerVehicles(preSelected);
                }
            }, 600);
        });
    </script>
@endsection
@section('css')
    <!-- include module css -->
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_css_path']))
                @includeIf($value['module_css_path'])
            @endif
        @endforeach
    @endif
    <style>
        /* Compatible product styling - green frame */
        .product_box.compatible-product {
            border: 3px solid #28a745 !important;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.3) !important;
            position: relative;
        }
        
        .product_box.compatible-product::before {
         
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #28a745;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            z-index: 10;
        }
        
        .product_box.compatible-product:hover {
            border-color: #218838 !important;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.5) !important;
        }
    </style>
@stop
