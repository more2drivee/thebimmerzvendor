@extends('layouts.app')
@section('title', __('report.comprehensive_sales_report_title'))

@section('content')
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('report.comprehensive_sales_report_title')</h1>
    <p class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">@lang('report.comprehensive_sales_report_subtitle')</p>
   
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('comprehensive_sales_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('comprehensive_sales_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'comprehensive_sales_date_range', 'readonly']); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sale_payment_status_filter', __('sale.payment_status') . ':') !!}
                        {!! Form::select('sale_payment_status_filter', [
                            '' => __('lang_v1.all'),
                            'paid' => __('lang_v1.paid'),
                            'partial' => __('lang_v1.partial'),
                            'due' => __('lang_v1.due'),
                            'overdue' => __('lang_v1.overdue'),
                        ], null, ['class' => 'form-control select2', 'id' => 'sale_payment_status_filter', 'style' => 'width:100%']); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('purchase_payment_status_filter', __('purchase.payment_status') . ':') !!}
                        {!! Form::select('purchase_payment_status_filter', [
                            '' => __('lang_v1.all'),
                            'paid' => __('lang_v1.paid'),
                            'partial' => __('lang_v1.partial'),
                            'due' => __('lang_v1.due'),
                            'overdue' => __('lang_v1.overdue'),
                        ], null, ['class' => 'form-control select2', 'id' => 'purchase_payment_status_filter', 'style' => 'width:100%']); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('supplier_id', __('purchase.supplier') . ':') !!}
                        {!! Form::select('supplier_id', $suppliers ?? [], null, ['class' => 'form-control select2', 'id' => 'supplier_id', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('business.location') . ':') !!}
                        {!! Form::select('location_id', $locations ?? [], null, ['class' => 'form-control select2', 'id' => 'location_id', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div id="comprehensive_sales_summary" class="alert alert-info tw-text-sm tw-font-semibold" style="display:none;">
                <div class="row">
                    <div class="col-sm-3">@lang('sale.qty'): <span class="summary_sold_qty"></span></div>
                    <div class="col-sm-3">@lang('sale.total'): <span class="summary_selling_value"></span></div>
                    <div class="col-sm-3">@lang('purchase.total'): <span class="summary_purchase_value"></span></div>
                    <div class="col-sm-3">@lang('report.profit'): <span class="summary_profit_value"></span></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="comprehensive_sales_table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>@lang('product.product_name')</th>
                            <th>@lang('sale.qty')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('sale.unit_price')</th>
                            <th>@lang('sale.payment_status')</th>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('purchase.unit_cost_after_tax')</th>
                            <th>@lang('purchase.payment_status')</th>
                            <th>@lang('purchase.supplier')</th>
                            <th>@lang('lang_v1.invoice_datetime')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td><strong>@lang('sale.total'):</strong></td>
                            <td class="total_sold_qty"></td>
                            <td></td>
                            <td class="total_selling_value"></td>
                            <td></td>
                            <td></td>
                            <td class="total_purchase_value"></td>
                            <td></td>
                            <td class="total_profit_value"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        var LABEL_SALE_QTY = "{{ __('sale.qty') }}";
        var LABEL_SALE_TOTAL = "{{ __('sale.total') }}";
        var LABEL_PURCHASE_TOTAL = "{{ __('purchase.total') }}";
        var LABEL_REPORT_PROFIT = "{{ __('report.profit') !== 'report.profit' ? __('report.profit') : __('lang_v1.profit') }}";

        var comprehensiveTotals = { qty: '0', selling: '0', purchase: '0', profit: '0' };

        function getTotalsFromPage() {
            var fallback = function(val){ return val && $.trim(val) !== '' ? val : '0'; };
            return {
                qty: fallback($('.total_sold_qty').text()),
                selling: fallback($('.total_selling_value').text()),
                purchase: fallback($('.total_purchase_value').text()),
                profit: fallback($('.total_profit_value').text())
            };
        }

        function renderSummary() {
            var totals = getTotalsFromPage();
            comprehensiveTotals = totals;
            $('.summary_sold_qty').text('').text(totals.qty);
            $('.summary_selling_value').text('').text(totals.selling);
            $('.summary_purchase_value').text('').text(totals.purchase);
            $('.summary_profit_value').text('').text(totals.profit);
            $('#comprehensive_sales_summary').show();
        }

        dateRangeSettings.startDate = moment().startOf('month');
        dateRangeSettings.endDate = moment().endOf('month');
        $('#comprehensive_sales_date_range').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#comprehensive_sales_date_range').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
                comprehensive_sales_table.ajax.reload();
            }
        );
        $('#comprehensive_sales_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#comprehensive_sales_date_range').val('');
            comprehensive_sales_table.ajax.reload();
        });

        comprehensive_sales_table = $('#comprehensive_sales_table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
            aaSorting: [[9, 'desc']],
            scrollY: "75vh",
            scrollX: true,
            scrollCollapse: true,
            fixedHeader:false,
            dom: '<"row"<"col-sm-12"<"pull-left"l><"pull-right"B>>>rtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel" aria-hidden="true"></i> Excel',
                    className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2',
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                // Strip HTML tags and get data-orig-value if exists
                                var $node = $(node);
                                if ($node.find('[data-orig-value]').length) {
                                    return $node.find('[data-orig-value]').data('orig-value');
                                }
                                // Remove HTML tags
                                return data.replace(/<[^>]*>/g, '');
                            }
                        }
                    },
                    footer: true
                },
                {
                    extend: 'csv',
                    text: '<i class="fa fa-file-csv" aria-hidden="true"></i> CSV',
                    className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2',
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                // Strip HTML tags and get data-orig-value if exists
                                var $node = $(node);
                                if ($node.find('[data-orig-value]').length) {
                                    return $node.find('[data-orig-value]').data('orig-value');
                                }
                                // Remove HTML tags
                                return data.replace(/<[^>]*>/g, '');
                            }
                        }
                    },
                    footer: true
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print" aria-hidden="true"></i> Print',
                    className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2',
                    exportOptions: {
                        columns: ':visible',
                        stripHtml: true
                    },
                    footer: true,
                    customize: function(win) {
                        var $tables = $(win.document.body).find('table');
                        var $footer = $('#comprehensive_sales_table').find('tfoot').clone();
                        // Remove every footer first
                        $tables.find('tfoot').remove();
                        // Add a single footer to the last table only
                        if ($tables.length && $footer.length) {
                            $tables.last().append($footer);
                        }

                        // Read latest totals from source footer (so we get current filtered values)
                        var $sourceFoot = $('#comprehensive_sales_table').find('tfoot');
                        var totals = {
                            qty: $.trim($sourceFoot.find('.total_sold_qty').text()) || comprehensiveTotals.qty || '0',
                            selling: $.trim($sourceFoot.find('.total_selling_value').text()) || comprehensiveTotals.selling || '0',
                            purchase: $.trim($sourceFoot.find('.total_purchase_value').text()) || comprehensiveTotals.purchase || '0',
                            profit: $.trim($sourceFoot.find('.total_profit_value').text()) || comprehensiveTotals.profit || '0'
                        };

                        var summaryHtml = '<div style="margin-top:10px;font-weight:bold;">' +
                            '<div>' + LABEL_SALE_QTY + ': ' + totals.qty + '</div>' +
                            '<div>' + LABEL_SALE_TOTAL + ': ' + totals.selling + '</div>' +
                            '<div>' + LABEL_PURCHASE_TOTAL + ': ' + totals.purchase + '</div>' +
                            '<div>' + LABEL_REPORT_PROFIT + ': ' + totals.profit + '</div>' +
                        '</div>';
                        $(win.document.body).append(summaryHtml);

                        // Arabic-friendly font, RTL layout, better column fit
                        var style = document.createElement('style');
                        style.type = 'text/css';
                        style.innerHTML = '\
                            @import url("https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap");\
                            body { font-family: "Cairo", "Tahoma", "Arial", "DejaVu Sans", sans-serif !important; direction: rtl; text-align: right; }\
                            table { width: 100% !important; direction: rtl; table-layout: auto !important; }\
                            th, td { white-space: normal !important; word-break: break-word; font-size: 12px; }\
                            h1, h2, h3, h4 { font-family: "Cairo", "Tahoma", "Arial", "DejaVu Sans", sans-serif !important; text-align: center; }\
                            thead th { text-align: center; }\
                        ';
                        win.document.head.appendChild(style);
                        $(win.document.body).css({ direction: 'rtl', 'text-align': 'right' });
                        $tables.css({ 'table-layout': 'auto', width: '100%' });
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i class="fa fa-columns" aria-hidden="true"></i> Columns',
                    className: 'tw-dw-btn-xs tw-dw-btn tw-dw-btn-outline tw-my-2'
                }
            ],
            ajax: {
                url: '/reports/comprehensive-sales-report',
                data: function(d) {
                    var start = '';
                    var end = '';
                    if ($('#comprehensive_sales_date_range').val()) {
                        start = $('input#comprehensive_sales_date_range')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        end = $('input#comprehensive_sales_date_range')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;

                    d.sale_payment_status = $('#sale_payment_status_filter').val();
                    d.purchase_payment_status = $('#purchase_payment_status_filter').val();
                    d.supplier_id = $('#supplier_id').val();
                    d.location_id = $('#location_id').val();
                },
            },
            columns: [
                { data: 'product_name', name: 'p.name' },
                { data: 'sold_qty', name: 'sold_qty', searchable: false },
                { data: 'transaction_number', name: 'sale.invoice_no', orderable: false, searchable: false },
                { data: 'selling_price', name: 'transaction_sell_lines.unit_price_inc_tax', orderable: false, searchable: false },
                { data: 'sale_payment_status', name: 'sale.payment_status', orderable: false, searchable: false },
                { data: 'purchase_ref', name: 'purchase.ref_no', orderable: false, searchable: false },
                { data: 'purchase_unit_price', name: 'purchase_lines.purchase_price_inc_tax', orderable: false, searchable: false },
                { data: 'purchase_payment_statuses', name: 'purchase.payment_status', orderable: false, searchable: false },
                { data: 'supplier_name', name: 'suppliers.name' },
                { data: 'transaction_date', name: 'sale.transaction_date' },
            ],
            footerCallback: function ( row, data, start, end, display ) {
                var total_sold_qty = 0;
                var total_selling_value = 0;
                var total_purchase_value = 0;

                for (var r in data){
                    var qty_el = $(data[r].sold_qty);
                    var qty = qty_el.data('orig-value');
                    qty = qty ? parseFloat(qty) : 0;

                    var sp_el = $(data[r].selling_price);
                    var sp = sp_el.data('orig-value');
                    sp = sp ? parseFloat(sp) : 0;

                    var pp_el = $(data[r].purchase_unit_price);
                    var pp = pp_el.data('orig-value');
                    pp = pp ? parseFloat(pp) : 0;

                    total_sold_qty += qty;
                    total_selling_value += (qty * sp);
                    total_purchase_value += (qty * pp);
                }

                var total_profit = total_selling_value - total_purchase_value;

                $('.total_sold_qty').html(__currency_trans_from_en(total_sold_qty, false));
                $('.total_selling_value').html(__currency_trans_from_en(total_selling_value, true));
                $('.total_purchase_value').html(__currency_trans_from_en(total_purchase_value, true));
                $('.total_profit_value').html(__currency_trans_from_en(total_profit, true));

                renderSummary();
            },
        });

        $(document).on('change', '#sale_payment_status_filter, #purchase_payment_status_filter, #supplier_id, #location_id', function() {
            comprehensive_sales_table.ajax.reload();
        });

    });
</script>
@endsection
