@extends('layouts.app')

@section('title', __('report.reports_dashboard_title'))

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>@lang('report.reports_dashboard_title')</h1>
        <p class="text-muted">@lang('report.reports_dashboard_subtitle')</p>
    </section>

    <section class="content">
        <!-- Financial Reports -->
        <div class="row">
            <div class="col-md-12">
                <h3 class="mb-3"><i class="fas fa-chart-line"></i> @lang('report.reports_dashboard_financial')</h3>
            </div>
        </div>
        <div class="row card-row">
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-primary card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-balance-scale fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_profit_loss_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_profit_loss_desc')</p>
                        <a href="{{ url('/reports/profit-loss') }}" class="btn btn-primary btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-success card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-exchange-alt fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_purchase_sell_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_purchase_sell_desc')</p>
                        <a href="{{ url('/reports/purchase-sell') }}" class="btn btn-success btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-info card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-coins fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_items_report_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_items_report_desc')</p>
                        <a href="{{ url('/reports/items-report') }}" class="btn btn-info btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Reports -->
        <div class="row">
            <div class="col-md-12">
                <h3 class="mb-3"><i class="fas fa-boxes"></i> @lang('report.reports_dashboard_stock_reports')</h3>
            </div>
        </div>
        <div class="row card-row">
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-warning card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-chart-bar fa-3x text-warning"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_stock_report_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_stock_report_desc')</p>
                        <a href="{{ url('/reports/stock-report') }}" class="btn btn-warning btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-secondary card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-search fa-3x text-secondary"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_stock_details_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_stock_details_desc')</p>
                        <a href="{{ url('/reports/stock-details') }}" class="btn btn-secondary btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-dark card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-clock fa-3x text-dark"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_stock_expiry_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_stock_expiry_desc')</p>
                        <a href="{{ url('/reports/stock-expiry') }}" class="btn btn-dark btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Reports -->
        <div class="row">
            <div class="col-md-12">
                <h3 class="mb-3"><i class="fas fa-shopping-cart"></i> @lang('report.reports_dashboard_sales_reports')</h3>
            </div>
        </div>
        <div class="row card-row">
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-primary card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-chart-line fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_sales_report_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_sales_report_desc')</p>
                        <a href="{{ url('/reports/sale-report') }}" class="btn btn-primary btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-info card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-table fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_comprehensive_sales_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_comprehensive_sales_desc')</p>
                        <a href="{{ url('/reports/comprehensive-sales-report') }}" class="btn btn-info btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-info card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-star fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_trending_products_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_trending_products_desc')</p>
                        <a href="{{ url('/reports/trending-products') }}" class="btn btn-info btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-success card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-users fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_sales_representative_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_sales_representative_desc')</p>
                        <a href="{{ url('/reports/sales-representative-report') }}" class="btn btn-success btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Reports -->
        <div class="row">
            <div class="col-md-12">
                <h3 class="mb-3"><i class="fas fa-shopping-bag"></i> @lang('report.reports_dashboard_purchase_reports')</h3>
            </div>
        </div>
        <div class="row card-row">
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-warning card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-clipboard-list fa-3x text-warning"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_purchase_report_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_purchase_report_desc')</p>
                        <a href="{{ url('/reports/purchase-report') }}" class="btn btn-warning btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-info card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-box fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_product_purchase_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_product_purchase_desc')</p>
                        <a href="{{ url('/reports/product-purchase-report') }}" class="btn btn-info btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer & Payment Reports -->
        <div class="row">
            <div class="col-md-12">
                <h3 class="mb-3"><i class="fas fa-handshake"></i> @lang('report.reports_dashboard_customer_payment_reports')</h3>
            </div>
        </div>
        <div class="row card-row">
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-primary card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-users fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_customer_supplier_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_customer_supplier_desc')</p>
                        <a href="{{ url('/reports/customer-supplier') }}" class="btn btn-primary btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-success card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-credit-card fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_payment_reports_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_payment_reports_desc')</p>
                        <a href="{{ url('/reports/purchase-payment-report') }}" class="btn btn-success btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- GST & Tax Reports -->
        <div class="row">
            <div class="col-md-12">
                <h3 class="mb-3"><i class="fas fa-file-invoice-dollar"></i> @lang('report.reports_dashboard_gst_tax_reports')</h3>
            </div>
        </div>
        <div class="row card-row">
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-info card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-receipt fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_gst_purchase_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_gst_purchase_desc')</p>
                        <a href="{{ url('/reports/gst-purchase-report') }}" class="btn btn-info btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-warning card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-shopping-bag fa-3x text-warning"></i>
                        </div>
                            <h5 class="card-title">@lang('report.reports_dashboard_gst_sales_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_gst_sales_desc')</p>
                        <a href="{{ url('/reports/gst-sales-report') }}" class="btn btn-warning btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-secondary card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-calculator fa-3x text-secondary"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_tax_report_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_tax_report_desc')</p>
                        <a href="{{ url('/reports/tax-report') }}" class="btn btn-secondary btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff & Service Reports -->
        <div class="row">
            <div class="col-md-12">
                <h3 class="mb-3"><i class="fas fa-user-tie"></i> @lang('report.reports_dashboard_staff_service_reports')</h3>
            </div>
        </div>
        <div class="row card-row">
            <!-- Service Staff Reports -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-warning card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-user-tie fa-3x text-warning"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_service_staff_report_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_service_staff_report_desc')</p>
                        <a href="{{ url('/reports/service-staff-report') }}" class="btn btn-warning btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sales Representative -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-success card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-users fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title">@lang('Sales Representative')</h5>
                        <p class="card-text flex-grow-1">@lang('Sales representative performance analysis')</p>
                        <a href="{{ url('/reports/sales-representative-report') }}" class="btn btn-success btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('View Report')
                        </a>
                    </div>
                </div>
            </div>

            <!-- Table Report -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-info card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-table fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_table_report_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_table_report_desc')</p>
                        <a href="{{ url('/reports/table-report') }}" class="btn btn-info btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Management -->
        <div class="row">
            <div class="col-md-12">
                <h3 class="mb-3"><i class="fas fa-boxes"></i> @lang('report.reports_dashboard_inventory_management')</h3>
            </div>
        </div>
        <div class="row card-row">
            <!-- Stock by Sell Price -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-info card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-tags fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_stock_by_sell_price_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_stock_by_sell_price_desc')</p>
                        <a href="{{ url('/reports/get-stock-by-sell-price') }}" class="btn btn-info btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stock Adjustment Report -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-danger card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-exchange-alt fa-3x text-danger"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_stock_adjustment_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_stock_adjustment_desc')</p>
                        <a href="{{ url('/reports/stock-adjustment-report') }}" class="btn btn-danger btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <!-- Lot Report -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-warning card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-boxes fa-3x text-warning"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_lot_report_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_lot_report_desc')</p>
                        <a href="{{ url('/reports/lot-report') }}" class="btn btn-warning btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <!-- Product Stock Details -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-success card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-box-open fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_product_stock_details_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_product_stock_details_desc')</p>
                        <a href="{{ url('/reports/product-stock-details') }}" class="btn btn-success btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stock Value -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-primary card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-dollar-sign fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_stock_value_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_stock_value_desc')</p>
                        <a href="{{ url('/reports/get-stock-value') }}" class="btn btn-primary btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- System & Activity -->
        <div class="row">
            <div class="col-md-12">
                <h3 class="mb-3"><i class="fas fa-cogs"></i> @lang('report.reports_dashboard_system_activity')</h3>
            </div>
        </div>
        <div class="row card-row">
            <!-- Activity Log -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-primary card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-history fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_activity_log_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_activity_log_desc')</p>
                        <a href="{{ url('/reports/activity-log') }}" class="btn btn-primary btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <!-- Register Report -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-info card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-cash-register fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_register_report_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_register_report_desc')</p>
                        <a href="{{ url('/reports/register-report') }}" class="btn btn-info btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <!-- Expense Report -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-dark card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-money-bill-wave fa-3x text-dark"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_expense_report_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_expense_report_desc')</p>
                        <a href="{{ url('/reports/expense-report') }}" class="btn btn-dark btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>

            <!-- Customer Group Report -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-success card-outline h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-users fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title">@lang('report.reports_dashboard_customer_groups_title')</h5>
                        <p class="card-text flex-grow-1">@lang('report.reports_dashboard_customer_groups_desc')</p>
                        <a href="{{ url('/reports/customer-group') }}" class="btn btn-success btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> @lang('report.reports_dashboard_view_report_button')
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('css')
<style>
.card-row {
    margin-bottom: 20px;
}

.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    margin-bottom: 20px;
    height: 100%;
    min-height: 280px;
    display: flex;
    flex-direction: column;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.card-title {
    font-weight: 600;
    margin-bottom: 15px;
}

.card-text {
    flex-grow: 1;
    margin-bottom: 20px;
}

.btn {
    font-weight: 500;
}

@media (max-width: 768px) {
    .card {
        min-height: 250px;
    }
}
</style>
@endpush
