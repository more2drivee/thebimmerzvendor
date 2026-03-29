@extends('layouts.app')
@section('title', __('sale.products'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('sale.products')
            <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">@lang('lang_v1.manage_products')</small>
        </h1>
        <!-- <ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                    <li class="active">Here</li>
                </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_products')])
            {{-- @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal"
                        data-href="{{action([\App\Http\Controllers\ProductController::class, 'create'])}}"
                        data-container=".product_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')</button>
                </div>
            @endslot --}}

            <!-- Your existing content here -->

        @endcomponent

        <!-- Product Details Modal -->
        <div class="modal fade" id="productDetailsModal" tabindex="-1" role="dialog" aria-labelledby="productDetailsModalLabel">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="product-details-content">
                        <div class="text-center">
                            <i class="fa fa-refresh fa-spin fa-fw"></i>
                            @lang('lang_v1.loading')...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Product Details Modal -->
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('type', __('product.product_type') . ':') !!}
                            {!! Form::select(
                                'type',
                                ['single' => __('lang_v1.single'), 'variable' => __('lang_v1.variable'), 'combo' => __('lang_v1.combo')],
                                null,
                                [
                                    'class' => 'form-control select2',
                                    'style' => 'width:100%',
                                    'id' => 'product_list_filter_type',
                                    'placeholder' => __('lang_v1.all'),
                                ],
                            ) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('category_id', __('product.category') . ':') !!}
                            {!! Form::select('category_id', $categories, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_list_filter_category_id',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                            {!! Form::select('sub_category_id', [], null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_list_filter_sub_category_id',
                                'placeholder' => __('lang_v1.all'),
                                'disabled' => 'disabled'
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('unit_id', __('product.unit') . ':') !!}
                            {!! Form::select('unit_id', $units, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_list_filter_unit_id',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('tax_id', __('product.tax') . ':') !!}
                            {!! Form::select('tax_id', $taxes, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_list_filter_tax_id',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('brand_id', __('product.brand') . ':') !!}
                            {!! Form::select('brand_id', $brands, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_list_filter_brand_id',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('device_id', 'ماركة السيارة:') !!}
                            {!! Form::select('device_id', [], null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_list_filter_device_id',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('repair_model_id', 'طراز السيارة:') !!}
                            {!! Form::select('repair_model_id', [], null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'product_list_filter_repair_model_id',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3" id="location_filter">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                            @php
                            $permitted_locations = auth()->user()->permitted_locations();
                            $default_location_id = ($permitted_locations == 'all') ? null : auth()->user()->location_id;
                            @endphp
                            {!! Form::select('location_id', $business_locations, $default_location_id, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                                'id' => 'location_id',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <br>
                        <div class="form-group">
                            {!! Form::label('active_state', __('business.is_active') . ':') !!}
                            {!! Form::select(
                                'active_state',
                                ['active' => __('business.is_active'), 'inactive' => __('lang_v1.inactive')],
                                'active',
                                [
                                    'class' => 'form-control select2',
                                    'style' => 'width:100%',
                                    'id' => 'active_state',
                                    'placeholder' => __('lang_v1.all'),
                                ],
                            ) !!}
                        </div>
                    </div>

                    <!-- include module filter -->
                    @if (!empty($pos_module_data))
                        @foreach ($pos_module_data as $key => $value)
                            @if (!empty($value['view_path']))
                                @includeIf($value['view_path'], ['view_data' => $value['view_data']])
                            @endif
                        @endforeach
                    @endif

                    <div class="col-md-3">
                        <div class="form-group">
                            <br>
                            <label>
                                {!! Form::checkbox('not_for_selling', 1, false, ['class' => 'input-icheck', 'id' => 'not_for_selling']) !!} <strong>@lang('lang_v1.not_for_selling')</strong>
                            </label>
                        </div>
                    </div>
            @if ($is_woocommerce)
                        <div class="col-md-3">
                            <div class="form-group">
                                <br>
                                <label>
                                    {!! Form::checkbox('woocommerce_enabled', 1, false, ['class' => 'input-icheck', 'id' => 'woocommerce_enabled']) !!} {{ __('lang_v1.woocommerce_enabled') }}
                                </label>
                            </div>
                        </div>
                    @endif
                @endcomponent
            </div>
        </div>
        @can('product.view')
            <div class="row">
                <div class="col-md-12">
                    <!-- Custom Tabs -->
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="#product_list_tab" data-toggle="tab" aria-expanded="true"><i class="fa fa-cubes"
                                        aria-hidden="true"></i> @lang('lang_v1.all_products')</a>
                            </li>
                            @can('stock_report.view')
                                <li>
                                    <a href="#product_stock_report" class="product_stock_report" data-toggle="tab"
                                        aria-expanded="true"><i class="fa fa-hourglass-half" aria-hidden="true"></i>
                                     @lang('report.stock_report')</a>
                                </li>
                            @endcan
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane active " id="product_list_tab">
                                @if ($is_admin)

                                    <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right tw-m-2"
                                        href="{{ action([\App\Http\Controllers\ProductController::class, 'downloadExcel']) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="icon icon-tabler icons-tabler-outline icon-tabler-download">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                            <path d="M7 11l5 5l5 -5" />
                                            <path d="M12 4l0 12" />
                                        </svg> @lang('lang_v1.download_excel')
                                    </a>
                                @endif
                                @can('product.create')

                                    <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right tw-m-2"
                                        href="{{ action([\App\Http\Controllers\ProductController::class, 'create']) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M12 5l0 14" />
                                            <path d="M5 12l14 0" />
                                        </svg> @lang('messages.add')
                                    </a>
                                    <br><br>
                                @endcan
                                @include('product.partials.product_list')
                            </div>
                            @can('stock_report.view')
                                <div class="tab-pane" id="product_stock_report">
                                    @include('report.partials.stock_report_table')
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        <input type="hidden" id="is_rack_enabled" value="{{ $rack_enabled }}">

        <div class="modal fade product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade" id="view_product_modal" tabindex="-1" role="dialog"
            aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade" id="opening_stock_modal" tabindex="-1" role="dialog"
            aria-labelledby="gridSystemModalLabel">
        </div>

        @if ($is_woocommerce)
            @include('product.partials.toggle_woocommerce_sync_modal')
        @endif
        @include('product.partials.edit_product_location_modal')

        <div class="modal fade" id="merge_products_modal" tabindex="-1" role="dialog" aria-labelledby="mergeProductsLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="mergeProductsLabel">@lang('lang_v1.merge_selected')</h4>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">@lang('lang_v1.merge_selected')</p>
                        <form id="merge_products_form">
                            @csrf
                            <div class="form-group">
                                <label>@lang('lang_v1.target_product')</label>
                                <p class="form-control-static text-bold" id="merge_target_product_name"></p>
                            </div>
                            <input type="hidden" id="merge_target_product_id" name="target_product_id">
                            <div class="form-group">
                                <label for="merge_source_products_select">@lang('lang_v1.products')</label>
                                <select id="merge_source_products_select" name="source_product_ids[]" class="form-control" style="width:100%" multiple></select>
                                <span class="help-block">@lang('lang_v1.select_products_to_merge')</span>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                        <button type="button" class="btn btn-primary" id="confirm_merge_products">@lang('lang_v1.merge_selected')</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Compatibility Modal -->
        <div class="modal fade" id="compatibility_modal" tabindex="-1" role="dialog" aria-labelledby="compatibilityModalLabel">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="compatibilityModalLabel"><i class="fa fa-car"></i> @lang('lang_v1.Product_Compatability') - <span id="compat_product_name"></span></h4>
                    </div>
                    <div class="modal-body">
                        {{-- Existing compatibility list --}}
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="compatibility_table">
                                <thead>
                                    <tr>
                                        <th>ماركة السيارة</th>
                                        <th>الموديل</th>
                                        <th>من سنة</th>
                                        <th>الى سنة</th>
                                        <th>@lang('messages.action')</th>
                                    </tr>
                                </thead>
                                <tbody id="compatibility_table_body">
                                    <tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <hr>
                        {{-- Add new compatibility form --}}
                        <h4><i class="fa fa-plus"></i> اضافة توافق جديد</h4>
                        <form id="add_compatibility_form">
                            @csrf
                            <input type="hidden" id="compat_product_id" name="product_id">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>ماركة السيارة</label>
                                        <select name="brand_category_id" id="compat_brand_category_id" class="form-control select2" style="width:100%">
                                            <option value="">-- اختر --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>الموديل</label>
                                        <select name="model_id" id="compat_model_id" class="form-control select2" style="width:100%">
                                            <option value="">-- اختر --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>من سنة</label>
                                        <input type="number" name="from_year" id="compat_from_year" class="form-control" placeholder="2020">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>الى سنة</label>
                                        <input type="number" name="to_year" id="compat_to_year" class="form-control" placeholder="2025">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-success btn-block"><i class="fa fa-plus"></i> اضافة</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Product Compatibility Modal -->

    </section>
    <!-- /.content -->

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize subcategory dropdown
        $('#product_list_filter_category_id').change(function() {
            var cat_id = $(this).val();
            if (cat_id) {
                $.ajax({
                    method: 'POST',
                    url: '/products/get_sub_categories',
                    dataType: 'html',
                    data: { cat_id: cat_id },
                    success: function(result) {
                        if (result) {
                            $('#product_list_filter_sub_category_id').html(result);
                            $('#product_list_filter_sub_category_id').prop('disabled', false);
                        }
                    },
                });
            } else {
                $('#product_list_filter_sub_category_id').html('<option value="">{{ __("lang_v1.all") }}</option>');
                $('#product_list_filter_sub_category_id').prop('disabled', true);
            }
        });

        // Load available car brands (device categories)
        function loadCarBrands() {
            $.ajax({
                method: 'GET',
                url: '/products/get_car_brands',
                dataType: 'json',
                success: function(result) {
                    var options = '<option value="">{{ __("lang_v1.all") }}</option>';
                    if (result && result.length > 0) {
                        $.each(result, function(index, brand) {
                            options += '<option value="' + brand.id + '">' + brand.name + '</option>';
                        });
                    }
                    $('#product_list_filter_device_id').html(options);
                    $('#product_list_filter_device_id').select2();
                }
            });
        }

        // Load car models based on selected brand
        function loadCarModels(deviceId) {
            if (!deviceId) {
                $('#product_list_filter_repair_model_id').html('<option value="">{{ __("lang_v1.all") }}</option>');
                $('#product_list_filter_repair_model_id').select2();
                return;
            }

            $.ajax({
                method: 'GET',
                url: '/products/get_car_models_by_brand',
                data: { brand_id: deviceId },
                dataType: 'json',
                success: function(result) {
                    var options = '<option value="">{{ __("lang_v1.all") }}</option>';
                    if (result && result.length > 0) {
                        $.each(result, function(index, model) {
                            options += '<option value="' + model.id + '">' + model.name + '</option>';
                        });
                    }
                    $('#product_list_filter_repair_model_id').html(options);
                    $('#product_list_filter_repair_model_id').select2();
                    $('#product_list_filter_repair_model_id').val('').trigger('change');
                }
            });
        }

        // Load car brands on page load
        loadCarBrands();

        // Handle car brand filter change
        $('#product_list_filter_device_id').change(function() {
            var deviceId = $(this).val();
            loadCarModels(deviceId);
            product_table.ajax.reload();
        });

        // Handle car model filter change
        $('#product_list_filter_repair_model_id').change(function() {
            product_table.ajax.reload();
        });

        // Handle product details button click
        $(document).on('click', '.product-details-btn', function() {
            var product_id = $(this).data('product-id');
            var modal = $('#productDetailsModal');

            // Show loading
            modal.find('.product-details-content').html('<div class="text-center"><i class="fa fa-refresh fa-spin fa-fw"></i><br>@lang("lang_v1.loading")...</div>');
            modal.modal('show');

            // Load product details
            $.ajax({
                url: "{{ url('/products/details') }}/" + product_id,
                dataType: 'html',
                success: function(result) {
                    modal.find('.product-details-content').html(result);
                }
            });
        });
    });
</script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            product_table = $('#product_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                aaSorting: [
                    [3, 'asc']
                ],
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                "ajax": {
                    "url": "/products",
                    "data": function(d) {
                        d.type = $('#product_list_filter_type').val();
                        d.category_id = $('#product_list_filter_category_id').val();
                        d.sub_category_id = $('#product_list_filter_sub_category_id').val();
                        d.brand_id = $('#product_list_filter_brand_id').val();
                        d.unit_id = $('#product_list_filter_unit_id').val();
                        d.tax_id = $('#product_list_filter_tax_id').val();
                        d.active_state = $('#active_state').val();
                        d.not_for_selling = $('#not_for_selling').is(':checked');
                        d.location_id = $('#location_id').val();
                        if ($('#product_list_filter_device_id').length == 1) {
                            d.device_id = $('#product_list_filter_device_id').val();
                        }
                        if ($('#product_list_filter_repair_model_id').length == 1) {
                            d.repair_model_id = $('#product_list_filter_repair_model_id').val();
                        }

                        if ($('#woocommerce_enabled').length == 1 && $('#woocommerce_enabled').is(
                                ':checked')) {
                            d.woocommerce_enabled = 1;
                        }

                        d = __datatable_ajax_callback(d);
                    }
                },
                columnDefs: [{
                    "targets": [0, 1, 2],
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'mass_delete'
                    },
                    {
                        data: 'image',
                        name: 'products.image',
                        visible: false  //
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'product',
                        name: 'products.name'
                    },
                    {
                        data: 'info_button',
                        name: 'info_button',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'product_locations',
                        name: 'product_locations'
                    },
                    @can('view_purchase_price')
                        {
                            data: 'purchase_price',
                            name: 'max_purchase_price',
                            searchable: false
                        },
                    @endcan
                    @can('access_default_selling_price')
                        {
                            data: 'selling_price',
                            name: 'max_price',
                            searchable: false
                        },
                    @endcan
                    {
                        data: 'current_stock',
                        searchable: false
                    },
                    {
                        data: 'type',
                        name: 'products.type'
                    },
                    {
                        data: 'category',
                        name: 'c1.name'
                    },
                    {
                        data: 'brand',
                        name: 'brands.name'
                    },
                    {
                        data: 'tax',
                        name: 'tax_rates.name',
                        searchable: false
                    },
                    {
                        data: 'sku',
                        name: 'products.sku'
                    },
                    {
                        data: 'product_custom_field1',
                        name: 'products.product_custom_field1',
                        visible: $('#cf_1').text().length > 0
                    },
                    {
                        data: 'product_custom_field2',
                        name: 'products.product_custom_field2',
                        visible: $('#cf_2').text().length > 0
                    },
                    {
                        data: 'product_custom_field3',
                        name: 'products.product_custom_field3',
                        visible: $('#cf_3').text().length > 0
                    },
                    {
                        data: 'product_custom_field4',
                        name: 'products.product_custom_field4',
                        visible: $('#cf_4').text().length > 0
                    },
                    {
                        data: 'product_custom_field5',
                        name: 'products.product_custom_field5',
                        visible: $('#cf_5').text().length > 0
                    },
                    {
                        data: 'product_custom_field6',
                        name: 'products.product_custom_field6',
                        visible: $('#cf_6').text().length > 0
                    },
                    {
                        data: 'product_custom_field7',
                        name: 'products.product_custom_field7',
                        visible: $('#cf_7').text().length > 0
                    },
                ],
                createdRow: function(row, data, dataIndex) {
                    if ($('input#is_rack_enabled').val() == 1) {
                        var target_col = 0;
                        @can('product.delete')
                            target_col = 1;
                        @endcan
                        $(row).find('td:eq(' + target_col + ') div').prepend(
                            '<i style="margin:auto;" class="fa fa-plus-circle text-success cursor-pointer no-print rack-details" title="' +
                            LANG.details + '"></i>&nbsp;&nbsp;');
                    }
                    $(row).find('td:eq(0)').attr('class', 'selectable_td');
                },
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#product_table'));
                },
            });
            // Array to track the ids of the details displayed rows
            var detailRows = [];

            $('#product_table tbody').on('click', 'tr i.rack-details', function() {
                var i = $(this);
                var tr = $(this).closest('tr');
                var row = product_table.row(tr);
                var idx = $.inArray(tr.attr('id'), detailRows);

                if (row.child.isShown()) {
                    i.addClass('fa-plus-circle text-success');
                    i.removeClass('fa-minus-circle text-danger');

                    row.child.hide();

                    // Remove from the 'open' array
                    detailRows.splice(idx, 1);
                } else {
                    i.removeClass('fa-plus-circle text-success');
                    i.addClass('fa-minus-circle text-danger');

                    row.child(get_product_details(row.data())).show();

                    // Add to the 'open' array
                    if (idx === -1) {
                        detailRows.push(tr.attr('id'));
                    }
                }
            });

            $('#opening_stock_modal').on('hidden.bs.modal', function(e) {
                product_table.ajax.reload();
            });

            $(document).on('click', 'a.delete-product', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    product_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', '#delete-selected', function(e) {
                e.preventDefault();
                var selected_rows = getSelectedRows();

                if (selected_rows.length > 0) {
                    $('input#selected_rows').val(selected_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $('form#mass_delete_form').submit();
                        }
                    });
                } else {
                    $('input#selected_rows').val('');
                    swal(LANG.no_row_selected || "{{ __('lang_v1.no_row_selected') }}");
                }
            });

            $(document).on('click', '#deactivate-selected', function(e) {
                e.preventDefault();
                var selected_rows = getSelectedRows();

                if (selected_rows.length > 0) {
                    $('input#selected_products').val(selected_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            var form = $('form#mass_deactivate_form')

                            var data = form.serialize();
                            $.ajax({
                                method: form.attr('method'),
                                url: form.attr('action'),
                                dataType: 'json',
                                data: data,
                                success: function(result) {
                                    if (result.success == true) {
                                        toastr.success(result.msg);
                                        product_table.ajax.reload();
                                        form
                                            .find('#selected_products')
                                            .val('');
                                    } else {
                                        toastr.error(result.msg);
                                    }
                                },
                            });
                        }
                    });
                } else {
                    $('input#selected_products').val('');
                    swal(LANG.no_row_selected || "{{ __('lang_v1.no_row_selected') }}");
                }
            })

            $(document).on('click', '#edit-selected', function(e) {
                e.preventDefault();
                var selected_rows = getSelectedRows();

                if (selected_rows.length > 0) {
                    $('input#selected_products_for_edit').val(selected_rows);
                    $('form#bulk_edit_form').submit();
                } else {
                    $('input#selected_products').val('');
                    swal('@lang('lang_v1.no_row_selected')');
                }
            })

            $(document).on('click', 'a.activate-product', function(e) {
                e.preventDefault();
                var href = $(this).attr('href');
                $.ajax({
                    method: "get",
                    url: href,
                    dataType: "json",
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            product_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $(document).on('click', '.merge-product-action', function(e) {
                e.preventDefault();
                var target_product_id = $(this).data('product-id');
                var target_product_name = $(this).data('product-name');
                $('#merge_target_product_id').val(target_product_id);
                $('#merge_target_product_name').text(target_product_name);
                $('#merge_source_products_select').val(null).trigger('change');
                $('#merge_products_modal').modal('show');
            });

            $('#merge_source_products_select').select2({
                dropdownParent: $('#merge_products_modal'),
                ajax: {
                    url: '/products/list',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { term: params.term };
                    },
                    processResults: function (data) {
                        // Ensure we have an array of objects with id and text
                        var results = data.results || data || [];
                        if (!Array.isArray(results)) {
                            results = [];
                        }
                        return {
                            results: results.map(function(item) {
                                return {
                                    id: item.product_id || item.id,
                                    text: item.name || item.text || item.product_name
                                };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                placeholder: '{{ __("messages.search") }}',
                multiple: true
            });

            $(document).on('click', '#confirm_merge_products', function() {
                var target_id = $('#merge_target_product_id').val();
                var sources = $('#merge_source_products_select').val() || [];
                sources = sources.filter(function(id) {
                    return id != target_id;
                });

                if (!target_id || sources.length === 0) {
                    toastr.error(LANG.invalid_data || 'Invalid selection');
                    return;
                }

                $.ajax({
                    method: 'POST',
                    url: "{{ route('products.merge') }}",
                    data: {
                        target_product_id: target_id,
                        source_product_ids: sources.join(','),
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            $('#merge_products_modal').modal('hide');
                            product_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(LANG.something_went_wrong);
                    }
                });
            });

            $(document).on('change',
                '#product_list_filter_type, #product_list_filter_category_id, #product_list_filter_sub_category_id, #product_list_filter_brand_id, #product_list_filter_unit_id, #product_list_filter_tax_id, #location_id, #active_state, #repair_model_id',
                function() {
                    if ($("#product_list_tab").hasClass('active')) {
                        product_table.ajax.reload();
                    }

                    if ($("#product_stock_report").hasClass('active')) {
                        stock_report_table.ajax.reload();
                    }
                });

            $(document).on('ifChanged', '#not_for_selling, #woocommerce_enabled', function() {
                if ($("#product_list_tab").hasClass('active')) {
                    product_table.ajax.reload();
                }

                if ($("#product_stock_report").hasClass('active')) {
                    stock_report_table.ajax.reload();
                }
            });

            $('#product_location').select2({
                dropdownParent: $('#product_location').closest('.modal')
            });

            @if ($is_woocommerce)
                $(document).on('click', '.toggle_woocomerce_sync', function(e) {
                    e.preventDefault();
                    var selected_rows = getSelectedRows();
                    if (selected_rows.length > 0) {
                        $('#woocommerce_sync_modal').modal('show');
                        $("input#woocommerce_products_sync").val(selected_rows);
                    } else {
                        $('input#selected_products').val('');
                        swal(LANG.no_row_selected || "{{ __('lang_v1.no_row_selected') }}");
                    }
                });

                $(document).on('submit', 'form#toggle_woocommerce_sync_form', function(e) {
                    e.preventDefault();
                    var url = $('form#toggle_woocommerce_sync_form').attr('action');
                    var method = $('form#toggle_woocommerce_sync_form').attr('method');
                    var data = $('form#toggle_woocommerce_sync_form').serialize();
                    var ladda = Ladda.create(document.querySelector('.ladda-button'));
                    ladda.start();
                    $.ajax({
                        method: method,
                        dataType: "json",
                        url: url,
                        data: data,
                        success: function(result) {
                            ladda.stop();
                            if (result.success) {
                                $("input#woocommerce_products_sync").val('');
                                $('#woocommerce_sync_modal').modal('hide');
                                toastr.success(result.msg);
                                product_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                });
            @endif
        });

        $(document).on('shown.bs.modal', 'div.view_product_modal, div.view_modal, #view_product_modal',
            function() {
                var div = $(this).find('#view_product_stock_details');
                if (div.length) {
                    $.ajax({
                        url: "{{ action([\App\Http\Controllers\ReportController::class, 'getStockReport']) }}" +
                            '?for=view_product&product_id=' + div.data('product_id'),
                        dataType: 'html',
                        success: function(result) {
                            div.html(result);
                            __currency_convert_recursively(div);
                        },
                    });
                }
                __currency_convert_recursively($(this));
            });
        var data_table_initailized = false;
        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            if ($(e.target).attr('href') == '#product_stock_report') {
                if (!data_table_initailized) {
                    //Stock report table
                    var stock_report_cols = [{
                            data: 'action',
                            name: 'action',
                            searchable: false,
                            orderable: false
                        },
                        {
                            data: 'sku',
                            name: 'variations.sub_sku'
                        },
                        {
                            data: 'product',
                            name: 'p.name'
                        },
                        {
                            data: 'variation',
                            name: 'variation'
                        },
                        {
                            data: 'category_name',
                            name: 'c.name'
                        },
                        {
                            data: 'location_name',
                            name: 'l.name'
                        },
                        {
                            data: 'unit_price',
                            name: 'variations.sell_price_inc_tax'
                        },
                        {
                            data: 'stock',
                            name: 'stock',
                            searchable: false
                        },
                    ];
                    if ($('th.stock_price').length) {
                        stock_report_cols.push({
                            data: 'stock_price',
                            name: 'stock_price',
                            searchable: false
                        });
                        stock_report_cols.push({
                            data: 'stock_value_by_sale_price',
                            name: 'stock_value_by_sale_price',
                            searchable: false,
                            orderable: false
                        });
                        stock_report_cols.push({
                            data: 'potential_profit',
                            name: 'potential_profit',
                            searchable: false,
                            orderable: false
                        });
                    }

                    stock_report_cols.push({
                        data: 'total_sold',
                        name: 'total_sold',
                        searchable: false
                    });
                    stock_report_cols.push({
                        data: 'total_transfered',
                        name: 'total_transfered', 
                        searchable: false
                    });
                    stock_report_cols.push({
                        data: 'total_adjusted',
                        name: 'total_adjusted',
                        searchable: false
                    });
                    stock_report_cols.push({
                        data: 'product_custom_field1',
                        name: 'p.product_custom_field1'
                    });
                    stock_report_cols.push({
                        data: 'product_custom_field2',
                        name: 'p.product_custom_field2'
                    });
                    stock_report_cols.push({
                        data: 'product_custom_field3',
                        name: 'p.product_custom_field3'
                    });
                    stock_report_cols.push({
                        data: 'product_custom_field4',
                        name: 'p.product_custom_field4'
                    });

                    if ($('th.current_stock_mfg').length) {
                        stock_report_cols.push({
                            data: 'total_mfg_stock',
                            name: 'total_mfg_stock',
                            searchable: false
                        });
                    }
                    stock_report_table = $('#stock_report_table').DataTable({
                        order: [
                            [1, 'asc']
                        ],
                        processing: true,
                        serverSide: true,
                        scrollY: "75vh",
                        scrollX: true,
                        scrollCollapse: true,
                        fixedHeader:false,
                        ajax: {
                            url: '/reports/stock-report',
                            data: function(d) {
                                d.location_id = $('#location_id').val();
                                d.category_id = $('#product_list_filter_category_id').val();
                                d.sub_category_id = $('#product_list_filter_sub_category_id').val();
                                d.brand_id = $('#product_list_filter_brand_id').val();
                                d.unit_id = $('#product_list_filter_unit_id').val();
                                d.type = $('#product_list_filter_type').val();
                                d.active_state = $('#active_state').val();
                                d.not_for_selling = $('#not_for_selling').is(':checked');
                                if ($('#repair_model_id').length == 1) {
                                    d.repair_model_id = $('#repair_model_id').val();
                                }
                            }
                        },
                        columns: stock_report_cols,
                        fnDrawCallback: function(oSettings) {
                            __currency_convert_recursively($('#stock_report_table'));
                        },
                        "footerCallback": function(row, data, start, end, display) {
                            var footer_total_stock = 0;
                            var footer_total_sold = 0;
                            var footer_total_transfered = 0;
                            var total_adjusted = 0;
                            var total_stock_price = 0;
                            var footer_stock_value_by_sale_price = 0;
                            var total_potential_profit = 0;
                            var footer_total_mfg_stock = 0;
                            for (var r in data) {
                                footer_total_stock += $(data[r].stock).data('orig-value') ?
                                    parseFloat($(data[r].stock).data('orig-value')) : 0;

                                footer_total_sold += $(data[r].total_sold).data('orig-value') ?
                                    parseFloat($(data[r].total_sold).data('orig-value')) : 0;

                                footer_total_transfered += $(data[r].total_transfered).data(
                                        'orig-value') ?
                                    parseFloat($(data[r].total_transfered).data('orig-value')) : 0;

                                total_adjusted += $(data[r].total_adjusted).data('orig-value') ?
                                    parseFloat($(data[r].total_adjusted).data('orig-value')) : 0;

                                total_stock_price += $(data[r].stock_price).data('orig-value') ?
                                    parseFloat($(data[r].stock_price).data('orig-value')) : 0;

                                footer_stock_value_by_sale_price += $(data[r].stock_value_by_sale_price)
                                    .data('orig-value') ?
                                    parseFloat($(data[r].stock_value_by_sale_price).data(
                                        'orig-value')) : 0;

                                total_potential_profit += $(data[r].potential_profit).data(
                                        'orig-value') ?
                                    parseFloat($(data[r].potential_profit).data('orig-value')) : 0;

                                footer_total_mfg_stock += $(data[r].total_mfg_stock).data(
                                        'orig-value') ?
                                    parseFloat($(data[r].total_mfg_stock).data('orig-value')) : 0;
                            }

                            $('.footer_total_stock').html(__currency_trans_from_en(footer_total_stock,
                                false));
                            $('.footer_total_stock_price').html(__currency_trans_from_en(
                                total_stock_price));
                            $('.footer_total_sold').html(__currency_trans_from_en(footer_total_sold,
                                false));
                            $('.footer_total_transfered').html(__currency_trans_from_en(
                                footer_total_transfered, false));
                            $('.footer_total_adjusted').html(__currency_trans_from_en(total_adjusted,
                                false));
                            $('.footer_stock_value_by_sale_price').html(__currency_trans_from_en(
                                footer_stock_value_by_sale_price));
                            $('.footer_potential_profit').html(__currency_trans_from_en(
                                total_potential_profit));
                            if ($('th.current_stock_mfg').length) {
                                $('.footer_total_mfg_stock').html(__currency_trans_from_en(
                                    footer_total_mfg_stock, false));
                            }
                        },
                    });
                    data_table_initailized = true;
                } else {
                    stock_report_table.ajax.reload();
                }
            } else {
                product_table.ajax.reload();
            }

            // remove class from data table button
            $('.btn-default').removeClass('btn-default');
            $('.tw-dw-btn-outline').removeClass('btn');
        });

        $(document).on('click', '.update_product_location', function(e) {
            e.preventDefault();
            var selected_rows = getSelectedRows();

            if (selected_rows.length > 0) {
                $('input#selected_products').val(selected_rows);
                var type = $(this).data('type');
                var modal = $('#edit_product_location_modal');
                if (type == 'add') {
                    modal.find('.remove_from_location_title').addClass('hide');
                    modal.find('.add_to_location_title').removeClass('hide');
                } else if (type == 'remove') {
                    modal.find('.add_to_location_title').addClass('hide');
                    modal.find('.remove_from_location_title').removeClass('hide');
                }

                modal.modal('show');
                modal.find('#product_location').select2({
                    dropdownParent: modal
                });
                modal.find('#product_location').val('').change();
                modal.find('#update_type').val(type);
                modal.find('#products_to_update_location').val(selected_rows);
            } else {
                $('input#selected_products').val('');
                swal(LANG.no_row_selected || "{{ __('lang_v1.no_row_selected') }}");
            }
        });

        $(document).on('submit', 'form#edit_product_location_form', function(e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();

            $.ajax({
                method: $(this).attr('method'),
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                beforeSend: function(xhr) {
                    __disable_submit_button(form.find('button[type="submit"]'));
                },
                success: function(result) {
                    if (result.success == true) {
                        $('div#edit_product_location_modal').modal('hide');
                        toastr.success(result.msg);
                        product_table.ajax.reload();
                        $('form#edit_product_location_form')
                            .find('button[type="submit"]')
                            .attr('disabled', false);
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        // ===== Product Compatibility Modal JS =====
        function loadCompatibilityData(productId) {
            $('#compatibility_table_body').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i></td></tr>');
            $.ajax({
                url: '/products/compatibility/' + productId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '';
                        $.each(response.data, function(i, item) {
                            html += '<tr>';
                            html += '<td>' + (item.brand_category_name || '-') + '</td>';
                            html += '<td>' + (item.model_name || '-') + '</td>';
                            html += '<td>' + (item.from_year || '-') + '</td>';
                            html += '<td>' + (item.to_year || '-') + '</td>';
                            html += '<td><button type="button" class="btn btn-xs btn-danger delete-compatibility" data-id="' + item.id + '"><i class="fa fa-trash"></i></button></td>';
                            html += '</tr>';
                        });
                        $('#compatibility_table_body').html(html);
                    } else {
                        $('#compatibility_table_body').html('<tr><td colspan="5" class="text-center text-muted">لا يوجد توافق</td></tr>');
                    }
                },
                error: function() {
                    $('#compatibility_table_body').html('<tr><td colspan="5" class="text-center text-danger">حدث خطأ</td></tr>');
                }
            });
        }

        function loadCompatBrands() {
            $.ajax({
                url: '/products/get_car_brands',
                method: 'GET',
                dataType: 'json',
                success: function(brands) {
                    var opts = '<option value="">-- اختر --</option>';
                    $.each(brands, function(i, brand) {
                        opts += '<option value="' + brand.id + '">' + brand.name + '</option>';
                    });
                    $('#compat_brand_category_id').html(opts);
                    if ($('#compat_brand_category_id').data('select2')) {
                        $('#compat_brand_category_id').trigger('change');
                    }
                }
            });
        }

        // Open compatibility modal
        $(document).on('click', '.open-compatibility-modal', function() {
            var productId = $(this).data('product-id');
            var productName = $(this).data('product-name');
            $('#compat_product_id').val(productId);
            $('#compat_product_name').text(productName);
            $('#compat_model_id').html('<option value="">-- اختر --</option>');
            $('#compat_from_year').val('');
            $('#compat_to_year').val('');

            loadCompatibilityData(productId);
            loadCompatBrands();

            $('#compatibility_modal').modal('show');

            setTimeout(function() {
                $('#compat_brand_category_id').select2({ dropdownParent: $('#compatibility_modal') });
                $('#compat_model_id').select2({ dropdownParent: $('#compatibility_modal') });
            }, 300);
        });

        // Brand change -> load models
        $(document).on('change', '#compat_brand_category_id', function() {
            var brandId = $(this).val();
            if (!brandId) {
                $('#compat_model_id').html('<option value="">-- اختر --</option>').trigger('change');
                return;
            }
            $.ajax({
                url: '/products/get-models-by-brand/' + brandId,
                method: 'GET',
                dataType: 'json',
                success: function(models) {
                    var opts = '<option value="">-- اختر --</option>';
                    $.each(models, function(i, model) {
                        opts += '<option value="' + model.id + '">' + model.name + '</option>';
                    });
                    $('#compat_model_id').html(opts).trigger('change');
                }
            });
        });

        // Add compatibility form submit
        $(document).on('submit', '#add_compatibility_form', function(e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();

            $.ajax({
                url: '{{ route("products.compatibility.store") }}',
                method: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        loadCompatibilityData($('#compat_product_id').val());
                        product_table.ajax.reload(null, false);
                        // Reset form fields
                        $('#compat_brand_category_id').val('').trigger('change');
                        $('#compat_model_id').html('<option value="">-- اختر --</option>').trigger('change');
                        $('#compat_from_year').val('');
                        $('#compat_to_year').val('');
                    } else {
                        toastr.error(response.msg);
                    }
                },
                error: function() {
                    toastr.error("{{ __('messages.something_went_wrong') }}");
                }
            });
        });

        // Delete compatibility
        $(document).on('click', '.delete-compatibility', function() {
            var id = $(this).data('id');
            var btn = $(this);
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then(function(willDelete) {
                if (willDelete) {
                    $.ajax({
                        url: '/products/compatibility/' + id,
                        method: 'DELETE',
                        data: { _token: $('meta[name="csrf-token"]').attr('content') },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg);
                                loadCompatibilityData($('#compat_product_id').val());
                                product_table.ajax.reload(null, false);
                            } else {
                                toastr.error(response.msg);
                            }
                        },
                        error: function() {
                            toastr.error("{{ __('messages.something_went_wrong') }}");
                        }
                    });
                }
            });
        });
    </script>
@endsection
