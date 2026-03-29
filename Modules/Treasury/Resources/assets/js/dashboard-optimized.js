/**
 * Treasury Dashboard Optimized JavaScript
 * 
 * Consolidates multiple AJAX requests into fewer, more efficient calls
 * Reduces page load requests from 7+ to 2-3 optimized requests
 * 
 * Request Consolidation:
 * - Before: 7 separate requests (dashboard-cards, filtered-totals, chart-data, payment-methods-chart, transaction-type-trend-chart, etc.)
 * - After: 2 requests (getAllDashboardData + getUnfilteredTotals)
 * - Improvement: 71% fewer requests
 */

$(document).ready(function() {
    // Initialize date range picker with predefined ranges
    dateRangeSettings.startDate = moment();
    dateRangeSettings.endDate = moment();

    $('#treasury_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
        $('#treasury_date_filter span').html(
            start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
        );
        updateDashboardData(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
        updateDateRangeDisplay(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
    });

    $('#treasury_date_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#treasury_date_filter span').html('<i class="fa fa-calendar"></i> ' + '{{ __("messages.filter_by_date") }}');
        updateDashboardData(moment().format('YYYY-MM-DD'), moment().format('YYYY-MM-DD'));
        updateDateRangeDisplay('{{ __("home.today") }}');
    });

    // Branch filter change handler
    $('#treasury_branch_filter').change(function() {
        var daterangepicker = $('#treasury_date_filter').data('daterangepicker');
        var startDate = daterangepicker ? daterangepicker.startDate.format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
        var endDate = daterangepicker ? daterangepicker.endDate.format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
        updateDashboardData(startDate, endDate);
    });

    // Initialize with today's data
    var initialStartDate = moment().format('YYYY-MM-DD');
    var initialEndDate = moment().format('YYYY-MM-DD');
    updateDateRangeDisplay('{{ __("home.today") }}');
    updateDashboardData(initialStartDate, initialEndDate);

    /**
     * OPTIMIZED: Update all dashboard data with consolidated requests
     * Replaces 5+ separate AJAX calls with 2 optimized calls
     * 
     * Request 1: getAllDashboardData - Gets all filtered data (cards, totals, charts)
     * Request 2: getUnfilteredTotals - Gets all-time totals (income, expense, balance)
     */
    function updateDashboardData(startDate, endDate) {
        var locationId = $('#treasury_branch_filter').val() || '';
        
        // Show loading state
        showLoadingState();

        // REQUEST 1: Get all dashboard data (consolidated)
        $.ajax({
            url: '{{ route("treasury.dashboard.all.data") }}',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                location_id: locationId
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;

                    // Update dashboard cards
                    updateDashboardCards(data.dashboard_cards);

                    // Update filtered totals
                    updateFilteredTotals(data.filtered_totals);

                    // Update all charts
                    updateAllCharts(data.chart_data, data.top_transaction_types, data.monthly_type_totals);

                    // Convert currency display
                    __currency_convert_recursively($('#dashboard_cards, .treasury_total_profit_invoice'));
                }
            },
            error: function() {
                toastr.error('{{ __("treasury::lang.failed_update_dashboard") }}');
            }
        });

        // REQUEST 2: Get unfiltered totals (all-time values)
        $.ajax({
            url: '{{ route("treasury.dashboard.unfiltered.totals") }}',
            method: 'GET',
            data: {
                location_id: locationId
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;

                    // Update unfiltered totals (income, expense, balance)
                    $('.treasury_total_income .display_currency').text(__currency_trans_from_en(data.total_income, true));
                    $('.treasury_total_expense_main .display_currency').text(__currency_trans_from_en(data.total_expense, true));
                    $('.treasury_real_balance .display_currency').text(__currency_trans_from_en(data.real_balance, true));

                    // Convert currency display
                    __currency_convert_recursively($('.treasury_total_income, .treasury_total_expense_main, .treasury_real_balance'));
                }
            },
            error: function() {
                toastr.error('{{ __("treasury::lang.failed_update_financial_totals") }}');
            },
            complete: function() {
                // Hide loading state after both requests complete
                hideLoadingState();
            }
        });
    }

    /**
     * Update dashboard cards with data
     */
    function updateDashboardCards(data) {
        $('.treasury_total_sell .display_currency').text(__currency_trans_from_en(data.total_sell, true));
        $('.treasury_total_purchase .display_currency').text(__currency_trans_from_en(data.total_purchase, true));
        $('.treasury_total_purchase_return .display_currency').text(__currency_trans_from_en(data.total_purchase_return, true));
        $('.treasury_total_sell_return .display_currency').text(__currency_trans_from_en(data.total_sell_return, true));
        $('.treasury_total_expense .display_currency').text(__currency_trans_from_en(data.total_expense, true));
        $('.treasury_invoice_due .display_currency').text(__currency_trans_from_en(data.invoice_due, true));
        $('.treasury_purchase_due .display_currency').text(__currency_trans_from_en(data.purchase_due, true));
        $('.treasury_expense_due .display_currency').text(__currency_trans_from_en(data.expense_due, true));
    }

    /**
     * Update filtered totals
     */
    function updateFilteredTotals(data) {
        $('.treasury_total_sales_due .display_currency').text(__currency_trans_from_en(data.total_sales_due, true));
        $('.treasury_total_purchase_due .display_currency').text(__currency_trans_from_en(data.total_purchase_due, true));
        $('.treasury_total_profit .display_currency').text(__currency_trans_from_en(data.total_profit, true));
        $('.treasury_total_profit_invoice .display_currency').text(__currency_trans_from_en(data.total_profit_invoice, true));
        $('.treasury_virtual_products_profit .display_currency').text(__currency_trans_from_en(data.virtual_products_profit, true));

        __currency_convert_recursively($('.treasury_total_sales_due, .treasury_total_purchase_due, .treasury_total_profit, .treasury_total_profit_invoice, .treasury_virtual_products_profit'));
    }

    /**
     * Update all charts with consolidated data
     */
    function updateAllCharts(chartData, topTransactionTypes, monthlyTypeTotals) {
        // Update trend chart (income vs expense) - guard against uninitialized chart
        try {
            if (window.trend_chart && window.trend_chart.data && Array.isArray(window.trend_chart.data.datasets)) {
                updateTrendChart(chartData);
            } else if (window.trend_chart) {
                // Ensure structure and call
                window.trend_chart.data = window.trend_chart.data || {};
                window.trend_chart.data.datasets = window.trend_chart.data.datasets || [];
                updateTrendChart(chartData);
            } else {
                // Chart not ready yet - retry shortly
                setTimeout(function() {
                    try { updateTrendChart(chartData); } catch (e) { console.warn('Retry updateTrendChart failed', e); }
                }, 200);
            }
        } catch (e) {
            console.error('updateAllCharts: failed to update trend chart', e);
        }

        // Update payment methods distribution chart
        updatePaymentMethodsChart(chartData);

        // Update top transaction types chart
        updateTopTransactionTypesChart(topTransactionTypes);

        // Update transaction type trend chart
        updateTransactionTypeTrendChart(monthlyTypeTotals);
    }

    /**
     * Update trend chart (income vs expense)
     */
    function updateTrendChart(data) {
        try {
            // Defensive: ensure chart object exists
            if (!window.trend_chart) {
                console.warn('Trend chart object (window.trend_chart) not found');
                return;
            }

        var income_data = [];
        var expense_data = [];

        for (var i = 1; i <= 12; i++) {
            income_data.push(data.monthly_income[i] ? data.monthly_income[i].total : 0);
            expense_data.push(data.monthly_expense[i] ? data.monthly_expense[i].total : 0);
        }

        if (window.trend_chart && window.trend_chart.data && window.trend_chart.data.datasets && window.trend_chart.data.datasets.length >= 2) {
            window.trend_chart.data.datasets[0].data = income_data;
            window.trend_chart.data.datasets[1].data = expense_data;
            window.trend_chart.update();
        }
    }

    /**
     * Update payment methods distribution chart
     */
    function updatePaymentMethodsChart(data) {
        if (window.income_expense_chart && data.payment_methods_by_transaction_type) {
            if (data.payment_methods_by_transaction_type.labels && data.payment_methods_by_transaction_type.labels.length > 0) {
                window.income_expense_chart.data.labels = data.payment_methods_by_transaction_type.labels;
                window.income_expense_chart.data.datasets[0].data = data.payment_methods_by_transaction_type.data;
                window.income_expense_chart.data.datasets[0].backgroundColor = data.payment_methods_by_transaction_type.colors;
                window.income_expense_chart.data.datasets[0].borderColor = data.payment_methods_by_transaction_type.colors;

                payment_methods_by_transaction_type_data = data.payment_methods_by_transaction_type;

                window.income_expense_chart.options.plugins.tooltip.callbacks.label = function(context) {
                    var label = context.label || '';
                    var total = __currency_trans_from_en(context.parsed, true);
                    var details = payment_methods_by_transaction_type_data.details[context.dataIndex];

                    if (details && details.breakdown && details.breakdown.length > 0) {
                        var breakdown = details.breakdown.map(function(item) {
                            return item.type + ': ' + __currency_trans_from_en(item.amount, true) + ' (' + item.percentage + '%)';
                        }).join('\n');

                        return [
                            label + ': ' + total,
                            'Breakdown:',
                            breakdown
                        ];
                    }

                    return label + ': ' + total;
                };

                window.income_expense_chart.update();
            } else {
                window.income_expense_chart.data.labels = [];
                window.income_expense_chart.data.datasets[0].data = [];
                payment_methods_by_transaction_type_data = { labels: [], data: [], colors: [], details: [] };
                window.income_expense_chart.update();
            }
        }
    }

    /**
     * Update top transaction types chart
     */
    function updateTopTransactionTypesChart(data) {
        if (window.payment_methods_distribution) {
            if (data.labels && data.labels.length > 0) {
                window.payment_methods_distribution.data.labels = data.labels;
                window.payment_methods_distribution.data.datasets[0].data = data.data;
                window.payment_methods_distribution.data.datasets[0].backgroundColor = data.colors;
                window.payment_methods_distribution.data.datasets[0].borderColor = data.colors;
                window.payment_methods_distribution.update();
            } else {
                window.payment_methods_distribution.data.labels = [];
                window.payment_methods_distribution.data.datasets[0].data = [];
                window.payment_methods_distribution.data.datasets[0].backgroundColor = [];
                window.payment_methods_distribution.data.datasets[0].borderColor = [];
                window.payment_methods_distribution.update();
            }
        }
    }

    /**
     * Update transaction type trend chart
     */
    function updateTransactionTypeTrendChart(data) {
        if (window.type_trend_chart) {
            var typeLabels = Object.keys(data);
            window.type_trend_chart.data.datasets = [];

            typeLabels.forEach(function(type, idx) {
                window.type_trend_chart.data.datasets.push({
                    label: type.charAt(0).toUpperCase() + type.slice(1),
                    data: data[type],
                    borderColor: [
                        '#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#8B5CF6'
                    ][idx % 7],
                    fill: false,
                    tension: 0.4
                });
            });

            window.type_trend_chart.update();
        }
    }

    /**
     * Update date range display
     */
    function updateDateRangeDisplay(dateText) {
        $('#dashboard_cards .treasury_date_range').text(dateText);
    }

    /**
     * Show loading state
     */
    function showLoadingState() {
        // Optional: Add loading spinner or disable buttons
        // $('#dashboard_cards').css('opacity', '0.6');
    }

    /**
     * Hide loading state
     */
    function hideLoadingState() {
        // Optional: Remove loading spinner or enable buttons
        // $('#dashboard_cards').css('opacity', '1');
    }

    // Load payment method balances
    function loadPaymentMethodBalances() {
        $.ajax({
            url: '{{ route("treasury.get.payment.method.balances") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    var cardsHtml = '';
                    var selectOptions = '<option value="">@lang("treasury::lang.select_payment_method")</option>';

                    response.data.forEach(function(method) {
                        var balanceFormatted = __currency_trans_from_en(method.balance, true);
                        var balanceClass = method.balance >= 0 ? 'text-success' : 'text-danger';

                        cardsHtml += `
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">${method.name}</h5>
                                        <p class="card-text">
                                            <span class="display_currency ${balanceClass}" data-currency_symbol="true">${method.balance}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        `;

                        selectOptions += `<option value="${method.id}">${method.name} - ${balanceFormatted}</option>`;
                    });

                    $('#payment_method_cards').html(cardsHtml);
                    $('#from_payment_method, #to_payment_method').html(selectOptions);
                    __currency_convert_recursively($('#payment_method_cards'));
                }
            },
            error: function() {
                toastr.error('{{ __("treasury::lang.failed_load_payment_method_balances") }}');
            }
        });
    }

    // Load branch-specific balances
    function loadBranchSpecificBalances(locationId) {
        $.ajax({
            url: '{{ route("treasury.branch.payment.method.balances") }}',
            method: 'GET',
            data: { location_id: locationId },
            success: function(response) {
                if (response.success) {
                    var cardsHtml = '';
                    var selectOptions = '<option value="">@lang("treasury::lang.select_payment_method")</option>';
                    
                    response.data.forEach(function(method) {
                        var balanceFormatted = __currency_trans_from_en(method.balance, true);
                        var balanceClass = method.balance >= 0 ? 'text-success' : 'text-danger';
                        
                        cardsHtml += `
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">${method.name}</h5>
                                        <p class="card-text">
                                            <span class="display_currency ${balanceClass}" data-currency_symbol="true">${method.balance}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        selectOptions += `<option value="${method.id}">${method.name} - ${balanceFormatted}</option>`;
                    });
                    
                    $('#payment_method_cards').html(cardsHtml);
                    $('#from_payment_method, #to_payment_method').html(selectOptions);
                    __currency_convert_recursively($('#payment_method_cards'));
                }
            },
            error: function() {
                toastr.error('{{ __("treasury::lang.failed_load_branch_balances") }}');
                loadPaymentMethodBalances();
            }
        });
    }

    // Handle payment transfer location change
    $(document).on('change', '#payment_transfer_location_id', function() {
        var locationId = $(this).val();
        
        if (locationId) {
            $('#payment_method_cards_container').show();
            loadBranchSpecificBalances(locationId);
        } else {
            $('#payment_method_cards_container').hide();
            $('#from_payment_method, #to_payment_method').html('<option value="">@lang("treasury::lang.select_payment_method")</option>');
        }
    });

    // Initialize Treasury Transactions DataTable
    var treasury_transactions_table = $('#treasury_transactions_table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        ajax: {
            url: "{{ url('/treasury/get-treasury-transactions') }}",
            data: function(d) {
                d.location_id = $('#treasury_branch_filter').val() || '';
                d.transaction_type = $('#filter_transaction_type').val();
            }
        },
        columns: [
            { data: 'transaction_date', name: 'transaction_date', orderable: true, searchable: true },
            { data: 'invoice_no', name: 'invoice_no', orderable: true, searchable: true },
            { data: 'type', name: 'type', render: function(data) {
                let badgeClass = 'badge bg-secondary';
                if(data.toLowerCase() === 'sell') badgeClass = 'badge bg-primary';
                if(data.toLowerCase() === 'purchase') badgeClass = 'badge bg-warning text-dark';
                return `<span class='${badgeClass}'>${data}</span>`;
            }},
            { data: 'sub_type', name: 'sub_type', render: function(data) {
                return `<span class='badge bg-info'>${data}</span>`;
            }},
            { data: 'contact_name', name: 'contacts.name' },
            {
                data: 'payment_status',
                name: 'payment_status',
                render: function (data) {
                    if (!data) return '';
                    let status = data.toLowerCase();
                    if (status === 'due') {
                        return `<span class='badge' style="background-color: #dc3545; font-weight: bold;">${data}</span>`;
                    }
                    if (status === 'partial') {
                        return `<span class='badge' style="background-color: orange; color: #fff;">${data}</span>`;
                    }
                    return `<span class='badge bg-success'>${data}</span>`;
                }
            },
            { data: 'final_total', name: 'final_total', render: function(data) {
                return `<span class="display_currency" data-currency_symbol="true">${data}</span>`;
            }},
            { data: 'remaining_amount', name: 'remaining_amount', render: function(data) {
                return `<span class="display_currency" data-currency_symbol="true">${data}</span>`;
            }},
            { data: 'status', name: 'status', render: function(data) {
                let badgeClass = 'badge bg-secondary';
                if(data && data.toLowerCase() === 'final') badgeClass = 'badge bg-success';
                if(data && data.toLowerCase() === 'draft') badgeClass = 'badge bg-warning text-dark';
                return `<span class='${badgeClass}'>${data}</span>`;
            }},
            {
                data: 'id',
                name: 'id',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return `
                        <div class="btn-group" role="group">
                            <a href="/treasury/transactions/${data}" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    `;
                }
            }
        ]
    });

    // Reload table when filters change
    $('#filter_transaction_type, #treasury_branch_filter').change(function() {
        treasury_transactions_table.draw();
    });
});
