@extends('layouts.app')
@section('title', __('treasury::lang.payment_transactions'))

@section('content')
@include('treasury::layouts.nav')

<section class="content-header">
    <h1>{{ __('treasury::lang.payment_transactions') }}</h1>
    <small>{{ __('treasury::lang.payments_manage_help') }}</small>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('treasury::lang.payment_transactions')])
    @slot('tool')
    <div class="box-tools">
        <a class="btn btn-primary btn-sm" href="{{ route('treasury.index') }}">
            <i class="fa fa-arrow-left"></i> {{ __('treasury::lang.back_to_dashboard') }}
        </a>
    </div>
    @endslot

    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-3">
            <div class="form-group">
                <label>{{ __('treasury::lang.payments_date_range') }}</label>
                <input type="text" id="payments_date_range" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>{{ __('treasury::lang.amount_min') }}</label>
                <input type="number" step="0.01" id="amount_min_filter" class="form-control" placeholder="0">
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>{{ __('treasury::lang.amount_max') }}</label>
                <input type="number" step="0.01" id="amount_max_filter" class="form-control" placeholder="">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>{{ __('treasury::lang.payment_method') }}</label>
                <select id="payment_method_filter" class="form-control">
                    <option value="">{{ __('treasury::lang.status_all') }}</option>
                    @foreach($payment_methods as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>{{ __('treasury::lang.transaction_type') }}</label>
                <select id="transaction_type_filter" class="form-control">
                    <option value="">{{ __('treasury::lang.status_all') }}</option>
                    <option value="sell">{{ __('sale.sale') }}</option>
                    <option value="purchase">{{ __('lang_v1.purchase') }}</option>
                    <option value="sell_return">{{ __('lang_v1.sell_return') }}</option>
                    <option value="purchase_return">{{ __('lang_v1.purchase_return') }}</option>
                    <option value="expense">{{ __('treasury::lang.type_expense') }}</option>
                    <option value="opening_balance">{{ __('lang_v1.opening_balance') }}</option>
                    <option value="payroll">{{ __('lang_v1.payroll') }}</option>
                    <option value="income">{{ __('treasury::lang.income') }}</option>
                    <option value="outcome">{{ __('treasury::lang.expense') }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <button type="button" class="btn btn-primary btn-sm" id="apply_payments_filters">
                    <i class="fas fa-filter"></i> {{ __('treasury::lang.filter') }}
                </button>
                <button type="button" class="btn btn-default btn-sm" id="reset_payments_filters">
                    <i class="fas fa-times" style="font-size: 15px !important;"></i> {{ __('messages.clear') }}
                </button>
                <button type="button" class="btn btn-info btn-sm" id="print_payments_report">
                    <i class="fa fa-print"></i> {{ __('messages.print') }}
                </button>
            </div>
        </div>
    </div>

    <div id="payments_loading" style="display:none;">
        <i class="fa fa-spinner fa-spin"></i>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="payments_transactions_table" style="width:100%">
            <thead>
                <tr>
                    <th>{{ __('treasury::lang.transaction_date') }}</th>
                    <th>{{ __('treasury::lang.reference_no') }}</th>
                    <th>{{ __('treasury::lang.invoice_no') }}</th>
                    <th>{{ __('treasury::lang.payment_method') }}</th>
                    <th>{{ __('treasury::lang.amount') }}</th>
                    <th>{{ __('treasury::lang.transaction_type') }}</th>
                    <th>{{ __('treasury::lang.income') }}/{{ __('treasury::lang.expense') }}</th>
                    <th>{{ __('treasury::lang.contact') }}</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<style>
    .label-income {
        background-color: #28a745;
        color: #fff;
    }

    .label-outcome {
        background-color: #dc3545;
        color: #fff;
    }
</style>
<script type="text/javascript">
    $(document).ready(function() {
        if ($('#payments_date_range').length) {
            // Set current month as default but don't auto-load
            var now = new Date();
            var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            var lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            
            $('#payments_date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: LANG.clear,
                }
            });
            
            // Set initial value to current month (display only)
            $('#payments_date_range').val(
                moment(firstDay).format('YYYY-MM-DD') + ' ~ ' + moment(lastDay).format('YYYY-MM-DD')
            );
            
            // Store default dates internally
            $('#payments_date_range').data('daterangepicker').setStartDate(firstDay);
            $('#payments_date_range').data('daterangepicker').setEndDate(lastDay);
            
            $('#payments_date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' ~ ' + picker.endDate.format('YYYY-MM-DD'));
            });
            $('#payments_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        }

        var PAYMENT_METHOD_LABELS = @json($payment_methods);

        function mapMethodLabel(m) {
            return PAYMENT_METHOD_LABELS[m] || m;
        }

        var TRANS_MAP = {
            'sell': "{{ __('sale.sale') }}",
            'purchase': "{{ __('lang_v1.purchase') }}",
            'sell_return': "{{ __('lang_v1.sell_return') }}",
            'purchase_return': "{{ __('lang_v1.purchase_return') }}",
            'expense': "{{ __('treasury::lang.type_expense') }}",
            'opening_balance': "{{ __('lang_v1.opening_balance') }}",
            'payroll': "{{ __('lang_v1.payroll') }}",
            'income': "{{ __('treasury::lang.income') }}",
            'outcome': "{{ __('treasury::lang.expense') }}"
        };

        function trans(key) {
            return TRANS_MAP[key] || key;
        }

        function typeIndicator(t) {
            var income = ['sell', 'purchase_return', 'opening_balance', 'deposit', 'income', 'payment_received'];
            var outcome = ['purchase', 'sell_return', 'expense', 'withdrawal', 'payment_sent', 'payroll'];
            if (income.indexOf(String(t)) !== -1) {
                return 'income';
            }
            if (outcome.indexOf(String(t)) !== -1) {
                return 'outcome';
            }
            return '';
        }

        var payments_table = $('#payments_transactions_table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 25,
            order: [
                [0, 'desc']
            ],
            ajax: {
                url: '{{ route("treasury.payments.data") }}',
                data: function(d) {
                    var start = '';
                    var end = '';
                    if ($('#payments_date_range').val()) {
                        start = $('#payments_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        end = $('#payments_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;
                    d.method = $('#payment_method_filter').val();
                    d.transaction_type = $('#transaction_type_filter').val();
                    d.amount_min = $('#amount_min_filter').val();
                    d.amount_max = $('#amount_max_filter').val();
                },
                error: function(xhr, error, thrown) {}
            },
            columns: [{
                    data: 'paid_on',
                    name: 'transaction_payments.paid_on'
                },
                {
                    data: 'payment_ref_no',
                    name: 'transaction_payments.payment_ref_no'
                },
                {
                    data: 'invoice_no',
                    name: 't.invoice_no'
                },
                {
                    data: 'method',
                    name: 'transaction_payments.method',
                    render: function(data) {
                        return mapMethodLabel(data);
                    }
                },
                {
                    data: 'amount',
                    name: 'transaction_payments.amount'
                },
                {
                    data: 'transaction_type',
                    name: 't.type',
                    render: function(data) {
                        return trans(data);
                    }
                },
                {
                    data: 'transaction_type',
                    name: 't.type',
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        var i = typeIndicator(data);
                        if (!i) {
                            return '';
                        }
                        var cls = i === 'income' ? 'label label-income' : 'label label-outcome';
                        return '<span class="' + cls + '">' + trans(i) + '</span>';
                    }
                },
                {
                    data: 'contact',
                    name: 'c.name',
                    orderable: false,
                    searchable: true
                }
            ],
            createdRow: function(row, data, dataIndex) {
                if (data.row_class) {
                    $(row).addClass(data.row_class);
                }
            },
            fnDrawCallback: function(oSettings) {
                __currency_convert_recursively($('#payments_transactions_table'));
            }
        });

        payments_table.on('preXhr.dt', function() {
            $('#payments_loading').show();
        });
        payments_table.on('xhr.dt', function() {
            $('#payments_loading').hide();
        });

        $('#apply_payments_filters').on('click', function() {
            payments_table.ajax.reload();
        });
        $('#payment_method_filter').on('change', function() {
            payments_table.ajax.reload();
        });
        $('#transaction_type_filter').on('change', function() {
            payments_table.ajax.reload();
        });
        $('#print_payments_report').on('click', function() {
            var params = {};

            if ($('#payments_date_range').val()) {
                var drp = $('#payments_date_range').data('daterangepicker');
                if (drp) {
                    params.start_date = drp.startDate.format('YYYY-MM-DD');
                    params.end_date = drp.endDate.format('YYYY-MM-DD');
                }
            }

            var method = $('#payment_method_filter').val();
            if (method) {
                params.method = method;
            }

            var transactionType = $('#transaction_type_filter').val();
            if (transactionType) {
                params.transaction_type = transactionType;
            }

            var amountMin = $('#amount_min_filter').val();
            if (amountMin) {
                params.amount_min = amountMin;
            }

            var amountMax = $('#amount_max_filter').val();
            if (amountMax) {
                params.amount_max = amountMax;
            }

            params.print = 1;

            var query = $.param(params);
            var url = '{{ route("treasury.payments.report") }}';
            if (query) {
                url += '?' + query;
            }

            window.open(url, '_blank');
        });
        $('#reset_payments_filters').on('click', function() {
            $('#payments_date_range').val('');
            $('#amount_min_filter').val('');
            $('#amount_max_filter').val('');
            $('#payment_method_filter').val('');
            $('#transaction_type_filter').val('');
            payments_table.ajax.reload();
        });
    });
</script>
@endsection