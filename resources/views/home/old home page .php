@extends('layouts.app')
@section('title', __('home.home'))
@section('content')
    <div class="header_top content_icons o_web_client o_home_menu_background">
        {{--    <header class="header_bottom">--}}
        {{--        @include('layouts.partials.header')--}}
        {{--    </header>--}}
        <div class="o_action_manager">
            <div class="o_home_menu h-100 overflow-auto">
                <div class="container">
                    <div class="o_apps row user-select-none mt-5 mx-0" role="listbox">
                        @if (auth()->user()?->can('calendar.view'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ route('calendar') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/2.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Calendar')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('bookings.view'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="bookings"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/3.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Reservation')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('essentials.todo'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="essentials/todo"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/4.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.To-Do')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('repair.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="repair/dashboard"

                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/22.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Job Order')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('contacts.view'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ route('contacts.dashboard') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/6.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Contacts')</div>
                            </a>

                        </div>
                        @endif
                        @if (auth()->user()?->can('spreadsheet.sheets'))    
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="spreadsheet/sheets"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/23.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Documents')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('crm.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="crm/dashboard"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/7.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.CRM')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('sell.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ route('sells.dashboard') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/8.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Sales')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('dashboards.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ route('dashboard_item', ['id' => app()->getLocale() == 'ar' ? 'Dashboards' : 'Dashboards']) }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/9.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Dashboards')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('point-of-sale.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ url('/point_of_sale/dashboard') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/10.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Point of Sale')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('payment-accounts.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ url('/account/dashboard') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/11.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Accounting')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('project.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="project/project?project_view=list_view"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/12.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Project')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('surveys.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="survey/dashboard"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/14.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Surveys')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('purchases.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ route('purchases.dashboard') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/15.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Purchase')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('products.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ url('/inventory/dashboard') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/16.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Inventory')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('barcode.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="barcodes"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/17.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Barcode')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('hrm.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="hrm/dashboard"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/19.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Employees')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('settings.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ route('dashboard_item', ['id' => app()->getLocale() == 'ar' ? 'إعدادات' : 'Settings']) }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/21.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Settings')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('artificial-intelligence.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ route('dashboard_item', ['id' => app()->getLocale() == 'ar' ? 'الذكاء الاصطناعي' : 'Artificial Intelligence']) }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/25.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Ai')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('time-management.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ route('timemanagement.dashboard') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/TimeMangment.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Time Management')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('reports.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ route('reports.index') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/reports.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.reports')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('treasury.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ url('/treasury') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/26.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.treasury')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('vin.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ url('/vin/dashboard') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/27.jpg') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.Vin')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('sms.dashboard'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ url('/sms/messages/dashboard') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/sms.png') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('home.SMS')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('carmarket.index') || auth()->user()?->can('admin'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ url('/carmarket') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/car_market.jpeg') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('carmarket::lang.module_title')</div>
                            </a>
                        </div>
                        @endif
                        @if (auth()->user()?->can('checkcar.inspections.index'))
                        <div class="col-3 col-md-2 o_draggable mb-3 px-0">
                            <a href="{{ url('/checkcar/inspections') }}"
                               class="o_app o_menuitem d-flex flex-column rounded-3 justify-content-start align-items-center w-100 p-1 p-md-2"
                               role="option">
                                <img class="o_app_icon rounded-3" src="{{ asset('/uploads/images/car_inspection.jpg') }}" alt="">
                                <div class="o_caption w-100 text-center text-truncate mt-2">@lang('checkcar::lang.menu_check_car')</div>
                            </a>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection




@section('javascript')
    <script src="{{ asset('js/home.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    @includeIf('sales_order.common_js')
    @includeIf('purchase_order.common_js')
    @if (!empty($all_locations))
        {!! $sells_chart_1->script() !!}
        {!! $sells_chart_2->script() !!}
    @endif
    <script type="text/javascript">
        $(document).ready(function() {
            sales_order_table = $('#sales_order_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                scrollY: '75vh',
                scrollX: true,
                scrollCollapse: true,
                aaSorting: [
                    [1, 'desc'],
                ],
                'ajax': {
                    'url': '{{ action([\App\Http\Controllers\SellController::class, 'index']) }}?sale_type=sales_order',
                    'data': function(d) {
                        d.for_dashboard_sales_order = true;

                        if ($('#so_location').length > 0) {
                            d.location_id = $('#so_location').val();
                        }
                    },
                },
                columnDefs: [{
                    'targets': 7,
                    'orderable': false,
                    'searchable': false,
                }],
                columns: [{
                    data: 'action',
                    name: 'action',
                },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date',
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no',
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name',
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile',
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name',
                    },
                    {
                        data: 'status',
                        name: 'status',
                    },
                    {
                        data: 'shipping_status',
                        name: 'shipping_status',
                    },
                    {
                        data: 'so_qty_remaining',
                        name: 'so_qty_remaining',
                        'searchable': false,
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name',
                    },
                ],
            });

            @if (auth()->user()->can('account.access') && config('constants.show_payments_recovered_today') == true)

            // Cash Flow Table
            cash_flow_table = $('#cash_flow_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                'ajax': {
                    'url': "{{ action([\App\Http\Controllers\AccountController::class, 'cashFlow']) }}",
                    'data': function(d) {
                        d.type = 'credit';
                        d.only_payment_recovered = true;
                    },
                },
                'ordering': false,
                'searching': false,
                columns: [{
                    data: 'operation_date',
                    name: 'operation_date',
                },
                    {
                        data: 'account_name',
                        name: 'account_name',
                    },
                    {
                        data: 'sub_type',
                        name: 'sub_type',
                    },
                    {
                        data: 'method',
                        name: 'TP.method',
                    },
                    {
                        data: 'payment_details',
                        name: 'payment_details',
                        searchable: false,
                    },
                    {
                        data: 'credit',
                        name: 'amount',
                    },
                    {
                        data: 'balance',
                        name: 'balance',
                    },
                    {
                        data: 'total_balance',
                        name: 'total_balance',
                    },
                ],
                'fnDrawCallback': function(oSettings) {
                    __currency_convert_recursively($('#cash_flow_table'));
                },
                'footerCallback': function(row, data, start, end, display) {
                    var footer_total_credit = 0;

                    for (var r in data) {
                        footer_total_credit += $(data[r].credit).data('orig-value') ? parseFloat($(
                            data[r].credit).data('orig-value')) : 0;
                    }
                    $('.footer_total_credit').html(__currency_trans_from_en(footer_total_credit));
                },
            });
            @endif

            $('#so_location').change(function() {
                sales_order_table.ajax.reload();
            });
            @if (!empty($common_settings['enable_purchase_order']))
            //Purchase table
            purchase_order_table = $('#purchase_order_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                aaSorting: [
                    [1, 'desc'],
                ],
                scrollY: '75vh',
                scrollX: true,
                scrollCollapse: true,
                ajax: {
                    url: '{{ action([\App\Http\Controllers\PurchaseOrderController::class, 'index']) }}',
                    data: function(d) {
                        d.from_dashboard = true;

                        if ($('#po_location').length > 0) {
                            d.location_id = $('#po_location').val();
                        }
                    },
                },
                columns: [{
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date',
                    },
                    {
                        data: 'ref_no',
                        name: 'ref_no',
                    },
                    {
                        data: 'location_name',
                        name: 'BS.name',
                    },
                    {
                        data: 'name',
                        name: 'contacts.name',
                    },
                    {
                        data: 'status',
                        name: 'transactions.status',
                    },
                    {
                        data: 'po_qty_remaining',
                        name: 'po_qty_remaining',
                        'searchable': false,
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name',
                    },
                ],
            });

            $('#po_location').change(function() {
                purchase_order_table.ajax.reload();
            });
            @endif

            @if (!empty($common_settings['enable_purchase_requisition']))
            //Purchase table
            purchase_requisition_table = $('#purchase_requisition_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                aaSorting: [
                    [1, 'desc'],
                ],
                scrollY: '75vh',
                scrollX: true,
                scrollCollapse: true,
                ajax: {
                    url: '{{ action([\App\Http\Controllers\PurchaseRequisitionController::class, 'index']) }}',
                    data: function(d) {
                        d.from_dashboard = true;

                        if ($('#pr_location').length > 0) {
                            d.location_id = $('#pr_location').val();
                        }
                    },
                },
                columns: [{
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date',
                    },
                    {
                        data: 'ref_no',
                        name: 'ref_no',
                    },
                    {
                        data: 'location_name',
                        name: 'BS.name',
                    },
                    {
                        data: 'status',
                        name: 'status',
                    },
                    {
                        data: 'delivery_date',
                        name: 'delivery_date',
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name',
                    },
                ],
            });

            $('#pr_location').change(function() {
                purchase_requisition_table.ajax.reload();
            });

            $(document).on('click', 'a.delete-purchase-requisition', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    purchase_requisition_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            });
            @endif

                sell_table = $('#shipments_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                aaSorting: [
                    [1, 'desc'],
                ],
                scrollY: '75vh',
                scrollX: true,
                scrollCollapse: true,
                'ajax': {
                    'url': '{{ action([\App\Http\Controllers\SellController::class, 'index']) }}',
                    'data': function(d) {
                        d.only_pending_shipments = true;
                        if ($('#pending_shipments_location').length > 0) {
                            d.location_id = $('#pending_shipments_location').val();
                        }
                    },
                },
                columns: [{
                    data: 'action',
                    name: 'action',
                    searchable: false,
                    orderable: false,
                },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date',
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no',
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name',
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile',
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name',
                    },
                    {
                        data: 'shipping_status',
                        name: 'shipping_status',
                    },
                        @if (!empty($custom_labels['shipping']['custom_field_1']))
                    {
                        data: 'shipping_custom_field_1',
                        name: 'shipping_custom_field_1',
                    },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_2']))
                    {
                        data: 'shipping_custom_field_2',
                        name: 'shipping_custom_field_2',
                    },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_3']))
                    {
                        data: 'shipping_custom_field_3',
                        name: 'shipping_custom_field_3',
                    },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_4']))
                    {
                        data: 'shipping_custom_field_4',
                        name: 'shipping_custom_field_4',
                    },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_5']))
                    {
                        data: 'shipping_custom_field_5',
                        name: 'shipping_custom_field_5',
                    },
                        @endif {
                        data: 'payment_status',
                        name: 'payment_status',
                    },
                    {
                        data: 'waiter',
                        name: 'ss.first_name',
                        @if (empty($is_service_staff_enabled))
                        visible: false
                        @endif
                    },
                ],
                'fnDrawCallback': function(oSettings) {
                    __currency_convert_recursively($('#sell_table'));
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td:eq(4)').attr('class', 'clickable_td');
                },
            });

            $('#pending_shipments_location').change(function() {
                sell_table.ajax.reload();
            });
        });
    </script>

@endsection