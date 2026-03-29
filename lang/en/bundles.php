<?php

return [
    'title' => 'Bundles',
    'subtitle' => 'Salvaged body parts bundles',
    'list_title' => 'All Bundles',
    'modal_title' => 'Create / Edit Bundle',

    'fields' => [
        'reference_no' => 'Bundle Ref',
        'device' => 'Brand',
        'model' => 'Model',
        'manufacturing_year' => 'Year',
        'side_type' => 'Body Part',
        'price' => 'Price',
        'has_parts_left' => 'Has parts left',
        'location' => 'Location',
        'description' => 'Description',
        'notes' => 'Notes',
    ],

    'filters' => [
        'device' => 'Brand',
        'model' => 'Model',
        'side_type' => 'Body Part',
        'location' => 'Location',
    ],

    'side_type' => [
        'front_half' => 'Front half',
        'rear_half' => 'Rear half',
        'left_quarter' => 'Left quarter',
        'right_quarter' => 'Right quarter',
        'full_body' => 'Full body',
        'other' => 'Other',
    ],
    'quick_sell_title' => 'Quick sell from bundle',
    'quick_sell_subtitle' => 'Create ad-hoc parts and sell them directly from this bundle',
    'quick_sell_box_title' => 'Bundle quick sell lines',
    'quick_sell_action' => 'Quick sell from bundle',

    'yes' => 'Yes',
    'no' => 'No',

    'overview' => [
        'title' => 'Bundle Overview',
        'bundle_summary' => 'Bundle summary',
        'total_transactions' => 'Total transactions',
        'total_qty_sold' => 'Total quantity sold',
        'unique_customers' => 'Unique customers',
        'total_sales' => 'Total sales',
        'total_purchase_cost' => 'Total purchase cost',
        'net_profit' => 'Net profit',
        'profit_margin' => 'Profit margin',
        'sales_vs_cost_chart' => 'Sales vs Purchase cost',
        'sold_products_chart' => 'Sold products',
        'transactions_table' => 'Bundle transactions',
        'transaction_date' => 'Date',
        'invoice_no' => 'Invoice no.',
        'customer' => 'Customer',
        'qty' => 'Qty',
        'selling_total' => 'Selling total',
        'purchase_total' => 'Purchase total',
        'profit' => 'Profit',
    ],

    'created_successfully' => 'Bundle created successfully',
    'updated_successfully' => 'Bundle updated successfully',
    'deleted_successfully' => 'Bundle deleted successfully',
    'not_found' => 'Bundle not found',
    'edit_bundle_sell_title' => 'Edit bundle sell',
];
