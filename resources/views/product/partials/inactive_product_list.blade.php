@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
@endphp
<table class="table table-bordered table-striped ajax_view hide-footer" id="inactive_product_table">
    <thead>
        <tr>
            <th><input type="checkbox" id="select-all-row-inactive" data-table-id="inactive_product_table"></th>
            <th class="tw-w-full">{{ __('lang_v1.product_image') }} </th>
            <th>@lang('messages.action')</th>
            <th>@lang('sale.product')</th>
            <th>@lang('lang_v1.Product_Compatability')</th>
            <th>@lang('purchase.business_location') @show_tooltip(__('lang_v1.product_business_location_tooltip'))</th>
            @can('view_purchase_price')
                <th>@lang('lang_v1.unit_perchase_price')</th>
            @endcan
            @can('access_default_selling_price')
                <th>@lang('lang_v1.selling_price')</th>
            @endcan
            <th>@lang('report.current_stock')</th>
            <th>@lang('product.product_type')</th>
            <th>@lang('product.category')</th>
            <th>@lang('product.brand')</th>
            <th>@lang('product.tax')</th>
            <th>@lang('product.sku')</th>
            <th id="inactive_cf_1">{{ $custom_labels['product']['custom_field_1'] ?? '' }}</th>
            <th id="inactive_cf_2">{{ $custom_labels['product']['custom_field_2'] ?? '' }}</th>
            <th id="inactive_cf_3">{{ $custom_labels['product']['custom_field_3'] ?? '' }}</th>
            <th id="inactive_cf_4">{{ $custom_labels['product']['custom_field_4'] ?? '' }}</th>
            <th id="inactive_cf_5">{{ $custom_labels['product']['custom_field_5'] ?? '' }}</th>
            <th id="inactive_cf_6">{{ $custom_labels['product']['custom_field_6'] ?? '' }}</th>
            <th id="inactive_cf_7">{{ $custom_labels['product']['custom_field_7'] ?? '' }}</th>
        </tr>
    </thead>
</table>
