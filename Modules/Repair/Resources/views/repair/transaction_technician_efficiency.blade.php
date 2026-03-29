@extends('layouts.app')
@section('title', __('repair::lang.technician_efficiency'))

@section('content')
@include('treasury::layouts.nav')

<style>
/* Transaction Technician Efficiency Styles */
.efficiency-overview {
    padding: 20px;
    font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
    max-width: 100%;
    overflow-x: hidden;
    background: #f5f7fa;
}

.efficiency-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.header-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.header-subtitle {
    font-size: 0.95rem;
    opacity: 0.9;
    margin: 0;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.metric-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #2a5298;
    transition: transform 0.2s, box-shadow 0.2s;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.metric-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-bottom: 12px;
    font-size: 1.2rem;
}

.icon-hours { background: #e3f2fd; color: #1976d2; }
.icon-efficiency { background: #e8f5e9; color: #388e3c; }
.icon-cost { background: #fff3e0; color: #f57c00; }
.icon-income { background: #f3e5f5; color: #7b1fa2; }

.metric-label {
    font-size: 0.85rem;
    color: #7a8aa3;
    margin-bottom: 8px;
    font-weight: 500;
}

.metric-value {
    font-size: 1.6rem;
    font-weight: 700;
    color: #1e3c72;
    margin-bottom: 4px;
}

.metric-detail {
    font-size: 0.8rem;
    color: #9aa0a6;
}

/* Charts Section */
.charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.chart-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.chart-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 18px;
}

.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #455a64;
    background: #f5f7fa;
    padding: 6px 12px;
    border-radius: 16px;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.chart-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 15px 0;
    color: #1e3c72;
    display: flex;
    align-items: center;
}

.chart-icon {
    margin-right: 10px;
    font-size: 1.2rem;
}

.chart-container {
    height: 300px;
    position: relative;
}

/* Technician Metrics Table */
.table-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.table-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 15px 0;
    color: #1e3c72;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.efficiency-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.efficiency-table th {
    background: #f8f9fa;
    padding: 12px 16px;

    font-weight: 600;
    border-bottom: 2px solid #e9ecef;
    color: #1e3c72;
    font-size: 0.9rem;
}

.efficiency-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.9rem;
    vertical-align: middle;
}

.efficiency-table tbody tr:hover {
    background: #f8f9fa;
}

.technician-name {
    font-weight: 600;
    color: #1e3c72;
}

.efficiency-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    font-weight: 700;
    font-size: 1.1rem;
    color: white;
    position: relative;
}

.efficiency-badge::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    opacity: 0.2;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.2; }
    50% { transform: scale(1.1); opacity: 0; }
}

.efficiency-high { 
    background: linear-gradient(135deg, #34a853 0%, #2d9648 100%);
}
.efficiency-medium { 
    background: linear-gradient(135deg, #fbbc04 0%, #f9ab00 100%);
    color: #1e3c72;
}
.efficiency-low { 
    background: linear-gradient(135deg, #ea4335 0%, #d93025 100%);
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-good { background: #e8f5e9; color: #388e3c; }
.status-warning { background: #fff3e0; color: #f57c00; }
.status-alert { background: #ffebee; color: #c62828; }

/* Table Alignment Helpers */
.efficiency-table th, .efficiency-table td { vertical-align: middle; }
.efficiency-table .text-center { text-align: center; }
.column-efficiency, .column-status { text-align: center; }
.badge-cell, .image-cell { display: flex; align-items: center; justify-content: center; }
.badge-cell .efficiency-badge { margin: 0 auto; line-height: 1; }

/* Responsive Design */
@media (max-width: 768px) {
    .efficiency-overview {
        padding: 15px;
    }

    .efficiency-header {
        padding: 15px;
    }

    .header-title {
        font-size: 1.3rem;
    }

    .metrics-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .charts-section {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .chart-container {
        height: 250px;
    }

    .efficiency-table {
        font-size: 0.8rem;
    }

    .efficiency-table th,
    .efficiency-table td {
        padding: 8px;
    }

    .efficiency-badge {
        width: 50px;
        height: 50px;
        font-size: 0.9rem;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .metrics-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .charts-section {
        grid-template-columns: 1fr;
    }
}

@media (min-width: 1025px) {
    .metrics-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .charts-section {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Utility Classes */
.text-muted { color: #6c757d; }
.text-success { color: #28a745; }
.text-warning { color: #ffc107; }
.text-danger { color: #ea4335; }
.text-primary { color: #1976d2; }


</style>

<div class="efficiency-overview">
    <!-- Info Banner -->
    <div style="background: #e3f2fd; border-left: 4px solid #1976d2; padding: 15px 20px; border-radius: 6px; margin-bottom: 20px;">
        <div style="display: flex; align-items: start; gap: 12px;">
            <i class="fas fa-info-circle" style="color: #1976d2; font-size: 1.3rem; margin-top: 2px;"></i>
            <div>
                <strong style="color: #1565c0; font-size: 0.95rem;">{{ __('repair::lang.efficiency_explanation') ?? 'How Efficiency Works' }}</strong>
                <p style="margin: 8px 0 0 0; color: #455a64; font-size: 0.85rem; line-height: 1.5;">
                    <strong>{{ __('repair::lang.expected_time') ?? 'Expected Time' }}:</strong> {{ __('repair::lang.expected_time_desc') ?? 'The maximum/standard time allocated for each service.' }}
                    <br>
                    <strong>{{ __('repair::lang.actual_time') ?? 'Actual Time' }}:</strong> {{ __('repair::lang.actual_time_desc') ?? 'The real time the technician took to complete the work.' }}
                    <br>
                    <strong style="color: #388e3c;">✓ {{ __('repair::lang.good') }}</strong>: {{ __('repair::lang.good_desc') }}
                    <br>
                    <strong style="color: #ea4335;">✗ {{ __('repair::lang.bad') }}</strong>: {{ __('repair::lang.bad_desc') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="metrics-grid" style="margin-top: 20px;">
        <div class="metric-card">
            <div class="metric-icon icon-hours">
                <i class="fas fa-clock"></i>
            </div>
            <div class="metric-label">{{ __('repair::lang.expected_time') ?? 'Expected Time (Max)' }}</div>
            <div class="metric-value">{{ number_format($total_allocated_hours, 1) }}h</div>
            <div class="metric-detail">{{ __('repair::lang.service_standard_time') ?? 'Service standard time' }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-icon icon-efficiency">
                <i class="fas fa-stopwatch"></i>
            </div>
            <div class="metric-label">{{ __('repair::lang.actual_time') ?? 'Actual Time Taken' }}</div>
            <div class="metric-value">{{ number_format($total_worked_hours, 1) }}h</div>
            <div class="metric-detail">{{ __('repair::lang.real_work_time') ?? 'Real work time' }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-icon icon-efficiency">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="metric-label">{{ __('repair::lang.time_usage') ?? 'Time Usage' }}</div>
            <div class="metric-value" style="color: @if($overall_efficiency <= 90) #388e3c @elseif($overall_efficiency <= 110) #f57c00 @else #ea4335 @endif">
                {{ number_format($overall_efficiency, 1) }}%
            </div>
            <div class="metric-detail">
                @if($overall_efficiency <= 90)
                    <span style="color: #388e3c;">✓ {{ __('repair::lang.under_time') ?? 'Under expected time' }}</span>
                @elseif($overall_efficiency <= 100)
                    <span style="color: #388e3c;">✓ {{ __('repair::lang.on_time') ?? 'On time' }}</span>
                @elseif($overall_efficiency <= 110)
                    <span style="color: #f57c00;">⚠ {{ __('repair::lang.slightly_over') ?? 'Slightly over' }}</span>
                @else
                    <span style="color: #ea4335;">✗ {{ __('repair::lang.exceeded_time') ?? 'Exceeded time' }}</span>
                @endif
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon icon-cost">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="metric-label">{{ __('repair::lang.total_labor_cost') }}</div>
            <div class="metric-value">EGP {{ number_format($total_labor_cost, 0) }}</div>
            <div class="metric-detail">{{ __('repair::lang.based_on_actual_time') }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-icon icon-income">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="metric-label">{{ __('repair::lang.labor_income') ?? 'Labor Income' }}</div>
            <div class="metric-value">EGP {{ number_format($total_labor_income, 0) }}</div>
            <div class="metric-detail">
                @php
                    $profit = $total_labor_income - $total_labor_cost;
                    $profitMargin = $total_labor_income > 0 ? ($profit / $total_labor_income) * 100 : 0;
                @endphp
                <span style="color: {{ $profit >= 0 ? '#388e3c' : '#ea4335' }}">
                    {{ $profit >= 0 ? '+' : '' }}EGP {{ number_format($profit, 0) }} ({{ number_format($profitMargin, 1) }}%)
                </span>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <!-- Time Comparison Chart -->
        <div class="chart-card">
            <h4 class="chart-title">
                <i class="fas fa-chart-bar chart-icon"></i>{{ __('repair::lang.time_performance') ?? 'Expected vs Actual Time' }}
            </h4>
            <p style="font-size: 0.85rem; color: #7a8aa3; margin: -10px 0 15px 0;">
                {{ __('repair::lang.time_comparison_desc') ?? 'Compare service standard time with actual technician performance' }}
            </p>
            <div class="chart-container">
                <canvas id="timeComparisonChart"></canvas>
                <div id="timeComparisonEmpty" class="text-muted" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);display:none;">{{ __('repair::lang.no_data') }}</div>
            </div>
            <div id="timeComparisonLegend" class="chart-legend"></div>
        </div>

        
        <!-- Cost Distribution Chart -->
        <div class="chart-card">
            <h4 class="chart-title">
                <i class="fas fa-chart-pie chart-icon"></i>{{ __('repair::lang.cost_distribution') ?? 'Cost Distribution' }}
            </h4>
            <div class="chart-container">
                <canvas id="costDistributionChart"></canvas>
                <div id="costDistributionEmpty" class="text-muted" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);display:none;">{{ __('repair::lang.no_data') }}</div>
            </div>
            <div id="costDistributionLegend" class="chart-legend"></div>
        </div>
    </div>
     <!-- Key Metrics - Top 3 Technician Efficiency -->
    <div class="metrics-grid">
        @php
            $topTechnicians = array_slice($technician_metrics, 0, 3);
        @endphp

        @forelse($topTechnicians as $tech)
            @php
                // Allow efficiency above 100% when worked time exceeds allocated time
                $eff = max(0, $tech['efficiency']);
            @endphp
            <div class="metric-card" style="text-align: center; padding: 30px 20px;">
                <div style="margin-bottom: 15px;">
                    <div class="efficiency-badge @if($eff <= 90) efficiency-high @elseif($eff <= 110) efficiency-medium @else efficiency-low @endif" style="margin: 0 auto;">
                        {{ number_format($eff, 0) }}%
                    </div>
                </div>
                <div class="metric-label" style="margin-bottom: 8px;">{{ $tech['name'] }}</div>
                <div style="font-size: 0.85rem; color: #9aa0a6;">
                    @if($eff <= 90)
                        <span class="status-badge status-good">✓ {{ __('repair::lang.excellent') }}</span>
                    @elseif($eff <= 110)
                        <span class="status-badge status-warning">⚠ {{ __('repair::lang.acceptable') }}</span>
                    @else
                        <span class="status-badge status-alert">✗ {{ __('repair::lang.needs_improvement') }}</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="metric-card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <p class="text-muted">{{ __('repair::lang.no_technician_data') ?? 'No technician data available' }}</p>
            </div>
        @endforelse
    </div>

    <!-- Technician Details Table -->
    @if(count($technician_metrics) > 0)
    <div class="table-section">
        <h4 class="table-title">
            <i class="fas fa-users"></i>{{ $chartLabels['technician_details'] ?? 'Technician Details' }}
        </h4>
        <div class="table-responsive">
            <table class="efficiency-table">
                <thead>
                    <tr>
                        <th>{{ __('repair::lang.technician') ?? 'Technician' }}</th>
                        <th>{{ __('repair::lang.department') ?? 'Department' }}</th>
                        <th>{{ __('repair::lang.expected_time') ?? 'Expected Time' }}</th>
                        <th>{{ __('repair::lang.actual_time') ?? 'Actual Time' }}</th>
                        <th>{{ __('repair::lang.time_diff') ?? 'Time Diff' }}</th>
                        <th>{{ __('repair::lang.hourly_rate') ?? 'Hourly Rate' }}</th>
                        <th>{{ __('repair::lang.total_cost') ?? 'Total Cost' }}</th>
                        <th>{{ __('repair::lang.paused_hours') ?? 'Paused' }}</th>
                        <th>{{ __('repair::lang.last_timer_reason') ?? 'Last Status / Reason' }}</th>
                        <th class="column-efficiency">{{ __('repair::lang.efficiency') ?? 'Efficiency' }}</th>
                        <th class="column-status">{{ __('repair::lang.status') ?? 'Status' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($technician_metrics as $tech)
                    @php
                        $timeDiff = $tech['allocated_hours'] - $tech['worked_hours'];
                        $timeDiffPercent = $tech['allocated_hours'] > 0 ? ($timeDiff / $tech['allocated_hours']) * 100 : 0;
                        // Allow efficiency above 100% when worked time exceeds allocated time
                        $eff = max(0, $tech['efficiency']);
                    @endphp
                    <tr class="tech-row" data-user-id="{{ $tech['user_id'] }}">
                        <td class="technician-name">{{ $tech['name'] }}</td>
                        <td>{{ $tech['department'] }}</td>
                        <td class="editable-time" data-field="allocated">
                            <strong><span class="allocated-hours">{{ number_format($tech['allocated_hours'], 2) }}</span>h</strong>
                            <div style="font-size: 0.75rem; color: #9aa0a6;">{{ __('repair::lang.max_time') ?? 'Max' }}</div>
                        </td>
                        <td class="editable-time" data-field="worked">
                            <strong><span class="worked-hours">{{ number_format($tech['worked_hours'], 2) }}</span>h</strong>
                            <div style="font-size: 0.75rem; color: #9aa0a6;">{{ __('repair::lang.real_time') ?? 'Real' }}</div>
                        </td>
                        <td>
                            <span class="time-diff" style="color: {{ $timeDiff >= 0 ? '#388e3c' : '#ea4335' }}; font-weight: 600;">
                                {{ $timeDiff >= 0 ? '+' : '' }}{{ number_format($timeDiff, 2) }}h
                            </span>
                            <div class="time-diff-label" style="font-size: 0.75rem; color: #9aa0a6;">
                                @if($timeDiff >= 0)
                                    {{ __('repair::lang.saved') ?? 'Saved' }}
                                @else
                                    {{ __('repair::lang.exceeded') ?? 'Exceeded' }}
                                @endif
                            </div>
                        </td>
                        <td>EGP {{ number_format($tech['hourly_rate'], 2) }}</td>
                        <td>EGP {{ number_format($tech['total_cost'], 2) }}</td>
                        <td class="text-center">
                            @php $paused = $tech['paused_hours'] ?? 0; @endphp
                            @if($paused > 0)
                                <span class="status-badge status-warning">{{ number_format($paused, 2) }}h</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td style="max-width:240px; white-space:normal;">
                            @if(!empty($tech['reasons']))
                                @foreach($tech['reasons'] as $reason)
                                    <span class="status-badge status-info" style="display:inline-block; margin:2px; white-space:normal;">
                                        {{ $reason['body'] }}
                                        @if(isset($reason['hours']) && $reason['hours'] > 0)
                                            ({{ number_format($reason['hours'], 2) }}h)
                                        @endif
                                    </span>
                                @endforeach
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="badge-cell column-efficiency">
                            <div class="efficiency-badge @if($eff <= 90) efficiency-high @elseif($eff <= 110) efficiency-medium @else efficiency-low @endif">
                                <span class="efficiency-value">{{ number_format($eff, 0) }}</span>%
                            </div>
                        </td>
                        <td class="text-center column-status">
                            @if($eff <= 90)
                                <span class="status-badge status-good">
                                    <i class="fas fa-check-circle"></i> {{ __('repair::lang.excellent') }}
                                </span>
                            @elseif($eff <= 100)
                                <span class="status-badge status-good">
                                    <i class="fas fa-check"></i> {{ __('repair::lang.on_time') ?? 'On Time' }}
                                </span>
                            @elseif($eff <= 110)
                                <span class="status-badge status-warning">
                                    <i class="fas fa-exclamation-circle"></i> {{ __('repair::lang.acceptable') ?? 'Acceptable' }}
                                </span>
                            @else
                                <span class="status-badge status-alert">
                                    <i class="fas fa-times-circle"></i> {{ __('repair::lang.needs_improvement') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="table-section">
        <p class="text-muted">{{ __('repair::lang.no_technician_data') ?? 'No technician data available for this transaction' }}</p>
    </div>
    @endif
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const transactionId = {{ $transaction->id }};

    function isAdmin() {
        // Server-side already restricts this page to admin middleware; return true for simplicity
        return true;
    }

    function attachInlineEditors() {
        if (!isAdmin()) return;

        const rows = document.querySelectorAll('.efficiency-table tbody tr.tech-row');
        rows.forEach(function(row) {
            const userId = row.getAttribute('data-user-id');
            row.querySelectorAll('.editable-time').forEach(function(cell) {
                cell.addEventListener('dblclick', function() {
                    const field = cell.getAttribute('data-field'); // 'allocated' or 'worked'
                    const span = cell.querySelector(field === 'allocated' ? '.allocated-hours' : '.worked-hours');
                    if (!span) return;

                    const originalText = span.textContent.trim();
                    const originalValue = parseFloat(originalText.replace(',', '')) || 0;

                    const input = document.createElement('input');
                    input.type = 'number';
                    input.min = '0';
                    input.step = '0.1';
                    input.value = originalValue.toFixed(2);
                    input.style.width = '80px';

                    span.style.display = 'none';
                    const unitText = cell.querySelector('strong').childNodes[1];
                    if (unitText) unitText.textContent = '';
                    cell.querySelector('strong').appendChild(input);
                    input.focus();
                    input.select();

                    function cleanup() {
                        input.remove();
                        span.style.display = '';
                        if (unitText) unitText.textContent = 'h';
                    }

                    function save() {
                        const newVal = parseFloat(input.value);
                        if (isNaN(newVal) || newVal < 0) {
                            cleanup();
                            return;
                        }

                        fetch(`{{ url('repair/transactions') }}/${transactionId}/technician-efficiency/timer`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                user_id: userId,
                                field: field,
                                value: newVal
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data || !data.success) {
                                if (window.toastr) {
                                    toastr.error(data && data.message ? data.message : '{{ __('messages.something_went_wrong') }}');
                                }
                                cleanup();
                                return;
                            }

                            span.textContent = newVal.toFixed(2);
                            recalcRow(row);
                            if (window.toastr) {
                                toastr.success(data.message || '{{ __('lang_v1.updated_success') }}');
                            }
                            cleanup();
                        })
                        .catch(() => {
                            if (window.toastr) {
                                toastr.error('{{ __('messages.something_went_wrong') }}');
                            }
                            cleanup();
                        });
                    }

                    input.addEventListener('blur', save);
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            save();
                        } else if (e.key === 'Escape') {
                            e.preventDefault();
                            cleanup();
                        }
                    });
                });
            });
        });
    }

    function recalcRow(row) {
        const allocatedSpan = row.querySelector('.allocated-hours');
        const workedSpan = row.querySelector('.worked-hours');
        const efficiencySpan = row.querySelector('.efficiency-value');
        const diffSpan = row.querySelector('.time-diff');
        const diffLabel = row.querySelector('.time-diff-label');
        const badge = row.querySelector('.efficiency-badge');

        const allocated = parseFloat(allocatedSpan ? allocatedSpan.textContent.replace(',', '') : '0') || 0;
        const worked = parseFloat(workedSpan ? workedSpan.textContent.replace(',', '') : '0') || 0;

        const diff = allocated - worked;
        let efficiency = allocated > 0 ? (worked / allocated) * 100 : 0;
        // Clamp efficiency between 0 and 100 for display
        efficiency = Math.max(0, Math.min(100, efficiency));

        if (efficiencySpan) {
            efficiencySpan.textContent = efficiency.toFixed(0);
        }

        if (badge) {
            badge.classList.remove('efficiency-high', 'efficiency-medium', 'efficiency-low');
            if (efficiency <= 90) {
                badge.classList.add('efficiency-high');
            } else if (efficiency <= 110) {
                badge.classList.add('efficiency-medium');
            } else {
                badge.classList.add('efficiency-low');
            }
        }

        if (diffSpan) {
            diffSpan.textContent = (diff >= 0 ? '+' : '') + diff.toFixed(2) + 'h';
            diffSpan.style.color = diff >= 0 ? '#388e3c' : '#ea4335';
        }

        if (diffLabel) {
            diffLabel.textContent = diff >= 0
                ? ('{{ __('repair::lang.saved') ?? 'Saved' }}')
                : ('{{ __('repair::lang.exceeded') ?? 'Exceeded' }}');
        }
    }

    attachInlineEditors();

    // Time Comparison Chart
    const timeCtx = document.getElementById('timeComparisonChart');
    const timeEmpty = document.getElementById('timeComparisonEmpty');
    if (timeCtx) {
        const technicianNames = @json(array_column($technician_metrics, 'name'));
        const allocatedHours = @json(array_column($technician_metrics, 'allocated_hours'));
        const workedHours = @json(array_column($technician_metrics, 'worked_hours'));
        if (technicianNames.length === 0) {
            if (timeEmpty) timeEmpty.style.display = 'block';
        } else {
            const timeChart = new Chart(timeCtx, {
                type: 'bar',
                data: {
                    labels: technicianNames,
                    datasets: [
                        {
                            label: '{{ __('repair::lang.expected_time') ?? 'Expected Time (Max)' }}',
                            data: allocatedHours,
                            backgroundColor: '#1976d2',
                            borderRadius: 4,
                            order: 2
                        },
                        {
                            label: '{{ __('repair::lang.actual_time') ?? 'Actual Time Taken' }}',
                            data: workedHours,
                            backgroundColor: '#34a853',
                            borderRadius: 4,
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                afterLabel: function(context) {
                                    const index = context.dataIndex;
                                    const allocated = allocatedHours[index];
                                    const worked = workedHours[index];
                                    const diff = allocated - worked;
                                    const efficiency = allocated > 0 ? ((worked / allocated) * 100).toFixed(1) : 0;
                                    
                                    if (context.datasetIndex === 1) {
                                        return [
                                            '{{ __('repair::lang.difference') }}: ' + (diff >= 0 ? '+' : '') + diff.toFixed(2) + 'h',
                                            '{{ __('repair::lang.efficiency') }}: ' + efficiency + '%'
                                        ];
                                    }
                                    return '';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '{{ __('repair::lang.hours') }}'
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });

            const timeLegend = document.getElementById('timeComparisonLegend');
            if (timeLegend) {
                timeChart.data.datasets.forEach(dataset => {
                    const item = document.createElement('div');
                    item.className = 'legend-item';

                    const dot = document.createElement('span');
                    dot.className = 'legend-dot';
                    dot.style.backgroundColor = dataset.backgroundColor;

                    const label = document.createElement('span');
                    label.textContent = dataset.label;

                    item.appendChild(dot);
                    item.appendChild(label);
                    timeLegend.appendChild(item);
                });
            }
        }
    }

    // Cost Distribution Chart
    const costCtx = document.getElementById('costDistributionChart');
    const costEmpty = document.getElementById('costDistributionEmpty');
    if (costCtx) {
        const costDistribution = @json($cost_distribution);
        const colors = ['#1976d2', '#34a853', '#fbbc04', '#ea4335', '#9c27b0', '#ff9800'];
        if (!costDistribution || costDistribution.length === 0) {
            if (costEmpty) costEmpty.style.display = 'block';
        } else {
            const costChart = new Chart(costCtx, {
                type: 'doughnut',
                data: {
                    labels: costDistribution.map(item => item.name),
                    datasets: [{
                        data: costDistribution.map(item => item.cost),
                        backgroundColor: colors.slice(0, costDistribution.length),
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = 'EGP ' + context.parsed.toFixed(2);
                                    const percentage = costDistribution[context.dataIndex].percentage.toFixed(1) + '%';
                                    return label + ': ' + value + ' (' + percentage + ')';
                                }
                            }
                        }
                    }
                }
            });

            const costLegend = document.getElementById('costDistributionLegend');
            if (costLegend) {
                costChart.data.labels.forEach((label, index) => {
                    const item = document.createElement('div');
                    item.className = 'legend-item';

                    const dot = document.createElement('span');
                    dot.className = 'legend-dot';
                    dot.style.backgroundColor = costChart.data.datasets[0].backgroundColor[index];

                    const text = document.createElement('span');
                    const percentage = costDistribution[index]?.percentage ?? 0;
                    text.textContent = `${label} • ${percentage.toFixed(1)}%`;

                    item.appendChild(dot);
                    item.appendChild(text);
                    costLegend.appendChild(item);
                });
            }
        }
    }
});
</script>

@endsection
