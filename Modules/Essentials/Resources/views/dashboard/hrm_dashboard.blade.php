@extends('layouts.app')
@section('title', __('essentials::lang.hrm') . ' ' . __('business.dashboard'))

<style>
    :root {
        --hrm-primary: #2563eb;
        --hrm-primary-light: rgba(37, 99, 235, 0.08);
        --hrm-success: #10b981;
        --hrm-success-light: rgba(16, 185, 129, 0.08);
        --hrm-warning: #f59e0b;
        --hrm-warning-light: rgba(245, 158, 11, 0.08);
        --hrm-danger: #ef4444;
        --hrm-danger-light: rgba(239, 68, 68, 0.08);
        --hrm-purple: #8b5cf6;
        --hrm-purple-light: rgba(139, 92, 246, 0.08);
        --hrm-cyan: #06b6d4;
        --hrm-cyan-light: rgba(6, 182, 212, 0.08);
        --hrm-card-bg: #ffffff;
        --hrm-card-radius: 16px;
        --hrm-card-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 6px 16px rgba(0, 0, 0, 0.04);
        --hrm-muted: #64748b;
        --hrm-border: #e2e8f0;
        --hrm-body-bg: #f8fafc;
    }

    .hrm-dashboard-content {
        font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    /* ── Stat Cards ── */
    .hrm-stat-card {
        background: var(--hrm-card-bg);
        border-radius: var(--hrm-card-radius);
        padding: 20px 22px;
        box-shadow: var(--hrm-card-shadow);
        border: 1px solid var(--hrm-border);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        position: relative;
        overflow: hidden;
    }

    .hrm-stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 16px 16px 0 0;
    }

    .hrm-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .hrm-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }

    .hrm-stat-body h4 {
        font-size: 26px;
        font-weight: 700;
        margin: 0 0 2px 0;
        line-height: 1.1;
    }

    .hrm-stat-body p {
        font-size: 13px;
        color: var(--hrm-muted);
        margin: 0;
        font-weight: 500;
    }

    .hrm-stat-card.primary::before { background: linear-gradient(90deg, var(--hrm-primary), #60a5fa); }
    .hrm-stat-card.primary .hrm-stat-icon { background: var(--hrm-primary-light); color: var(--hrm-primary); }
    .hrm-stat-card.primary h4 { color: var(--hrm-primary); }

    .hrm-stat-card.success::before { background: linear-gradient(90deg, var(--hrm-success), #34d399); }
    .hrm-stat-card.success .hrm-stat-icon { background: var(--hrm-success-light); color: var(--hrm-success); }
    .hrm-stat-card.success h4 { color: var(--hrm-success); }

    .hrm-stat-card.warning::before { background: linear-gradient(90deg, var(--hrm-warning), #fbbf24); }
    .hrm-stat-card.warning .hrm-stat-icon { background: var(--hrm-warning-light); color: var(--hrm-warning); }
    .hrm-stat-card.warning h4 { color: var(--hrm-warning); }

    .hrm-stat-card.danger::before { background: linear-gradient(90deg, var(--hrm-danger), #f87171); }
    .hrm-stat-card.danger .hrm-stat-icon { background: var(--hrm-danger-light); color: var(--hrm-danger); }
    .hrm-stat-card.danger h4 { color: var(--hrm-danger); }

    .hrm-stat-card.purple::before { background: linear-gradient(90deg, var(--hrm-purple), #a78bfa); }
    .hrm-stat-card.purple .hrm-stat-icon { background: var(--hrm-purple-light); color: var(--hrm-purple); }
    .hrm-stat-card.purple h4 { color: var(--hrm-purple); }

    .hrm-stat-card.cyan::before { background: linear-gradient(90deg, var(--hrm-cyan), #22d3ee); }
    .hrm-stat-card.cyan .hrm-stat-icon { background: var(--hrm-cyan-light); color: var(--hrm-cyan); }
    .hrm-stat-card.cyan h4 { color: var(--hrm-cyan); }

    /* ── Chart Wrappers ── */
    .hrm-chart-card {
        background: var(--hrm-card-bg);
        border-radius: var(--hrm-card-radius);
        box-shadow: var(--hrm-card-shadow);
        border: 1px solid var(--hrm-border);
        padding: 20px;
        height: 100%;
    }

    .hrm-chart-title {
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 16px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .hrm-chart-title i {
        font-size: 16px;
        opacity: 0.6;
    }

    .hrm-chart-canvas-wrap {
        position: relative;
        width: 100%;
        height: 280px;
    }

    /* ── Widget Table Styles ── */
    .hrm-widget-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .hrm-widget-table thead th {
        background: #f1f5f9;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 10px 14px;
        border: none;
    }

    .hrm-widget-table thead th:first-child { border-radius: 8px 0 0 8px; }
    .hrm-widget-table thead th:last-child { border-radius: 0 8px 8px 0; }

    .hrm-widget-table tbody td {
        padding: 10px 14px;
        font-size: 13px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .hrm-widget-table tbody tr:last-child td {
        border-bottom: none;
    }

    .hrm-widget-table tbody tr:hover td {
        background: #f8fafc;
    }

    /* ── Status badges ── */
    .hrm-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .hrm-badge.pending { background: #fef3c7; color: #92400e; }
    .hrm-badge.approved { background: #d1fae5; color: #065f46; }
    .hrm-badge.rejected { background: #fee2e2; color: #991b1b; }

    /* ── Section Title ── */
    .hrm-section-title {
        font-size: 17px;
        font-weight: 700;
        color: #0f172a;
        margin: 28px 0 16px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .hrm-section-title .hrm-title-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    /* ── Birthday Card ── */
    .hrm-birthday-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .hrm-birthday-item:last-child {
        border-bottom: none;
    }

    .hrm-birthday-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        color: #fff;
        flex-shrink: 0;
    }

    .hrm-birthday-info {
        flex: 1;
        min-width: 0;
    }

    .hrm-birthday-name {
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .hrm-birthday-date {
        font-size: 11px;
        color: var(--hrm-muted);
    }

    /* ── Holiday Item ── */
    .hrm-holiday-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .hrm-holiday-item:last-child { border-bottom: none; }

    .hrm-holiday-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    /* ── Attendance dot ── */
    .hrm-attendance-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .hrm-stat-card { padding: 14px 16px; }
        .hrm-stat-icon { width: 44px; height: 44px; font-size: 18px; }
        .hrm-stat-body h4 { font-size: 22px; }
        .hrm-chart-canvas-wrap { height: 220px; }
    }

    /* ── Animate in ── */
    .hrm-fade-in {
        opacity: 0;
        transform: translateY(16px);
        animation: hrmFadeIn 0.5s ease forwards;
    }

    @keyframes hrmFadeIn {
        to { opacity: 1; transform: translateY(0); }
    }

    .hrm-fade-in:nth-child(1) { animation-delay: 0.05s; }
    .hrm-fade-in:nth-child(2) { animation-delay: 0.1s; }
    .hrm-fade-in:nth-child(3) { animation-delay: 0.15s; }
    .hrm-fade-in:nth-child(4) { animation-delay: 0.2s; }
    .hrm-fade-in:nth-child(5) { animation-delay: 0.25s; }
    .hrm-fade-in:nth-child(6) { animation-delay: 0.3s; }
</style>

@section('content')
    @include('essentials::layouts.nav_hrm')

    <section class="content hrm-dashboard-content">

        {{-- ═══════════════ Section: Stat Summary Cards ═══════════════ --}}
        <div class="row" style="margin-bottom: 6px;">
            {{-- Total Employees --}}
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12" style="margin-bottom: 16px;">
                <div class="hrm-stat-card primary hrm-fade-in">
                    <div class="hrm-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="hrm-stat-body">
                        <h4>{{ $total_employees }}</h4>
                        <p>@lang('essentials::lang.total_employees')</p>
                    </div>
                </div>
            </div>

            {{-- Departments --}}
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12" style="margin-bottom: 16px;">
                <div class="hrm-stat-card purple hrm-fade-in">
                    <div class="hrm-stat-icon">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <div class="hrm-stat-body">
                        <h4>{{ $departments->count() }}</h4>
                        <p>@lang('essentials::lang.departments')</p>
                    </div>
                </div>
            </div>

            @if ($is_admin)
                {{-- Present Today --}}
                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12" style="margin-bottom: 16px;">
                    <div class="hrm-stat-card success hrm-fade-in">
                        <div class="hrm-stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="hrm-stat-body">
                            <h4>{{ $present_today }}</h4>
                            <p>@lang('essentials::lang.present_today')</p>
                        </div>
                    </div>
                </div>

                {{-- Absent Today --}}
                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12" style="margin-bottom: 16px;">
                    <div class="hrm-stat-card danger hrm-fade-in">
                        <div class="hrm-stat-icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="hrm-stat-body">
                            <h4>{{ $absent_today }}</h4>
                            <p>@lang('essentials::lang.absent_today')</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Pending Leaves + On Leave Today (summary row) ── --}}
        <div class="row" style="margin-bottom: 6px;">
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12" style="margin-bottom: 16px;">
                <div class="hrm-stat-card warning hrm-fade-in">
                    <div class="hrm-stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="hrm-stat-body">
                        <h4>{{ $pending_leaves_count }}</h4>
                        <p>@lang('essentials::lang.pending_requests')</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12" style="margin-bottom: 16px;">
                <div class="hrm-stat-card cyan hrm-fade-in">
                    <div class="hrm-stat-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <div class="hrm-stat-body">
                        <h4>{{ count($todays_leaves) }}</h4>
                        <p>@lang('essentials::lang.on_leave_today')</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12" style="margin-bottom: 16px;">
                <div class="hrm-stat-card success hrm-fade-in">
                    <div class="hrm-stat-icon">
                        <i class="fas fa-birthday-cake"></i>
                    </div>
                    <div class="hrm-stat-body">
                        <h4>{{ $today_births->count() }}</h4>
                        <p>@lang('essentials::lang.birthdays_today')</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12" style="margin-bottom: 16px;">
                <div class="hrm-stat-card purple hrm-fade-in">
                    <div class="hrm-stat-icon">
                        <i class="fas fa-suitcase-rolling"></i>
                    </div>
                    <div class="hrm-stat-body">
                        <h4>{{ count($todays_holidays) + count($upcoming_holidays) }}</h4>
                        <p>@lang('essentials::lang.upcoming_holidays')</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════ Section: Charts Row 1 ═══════════════ --}}
        @if ($is_admin)
            <div class="row">
                {{-- Department Distribution (Doughnut) --}}
                <div class="col-lg-4 col-md-6 col-xs-12" style="margin-bottom: 20px;">
                    <div class="hrm-chart-card hrm-fade-in">
                        <h4 class="hrm-chart-title">
                            <i class="fas fa-sitemap"></i>
                            @lang('essentials::lang.employees_by_department')
                        </h4>
                        <div class="hrm-chart-canvas-wrap">
                            <canvas id="hrmDeptChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Leave Status (Doughnut) --}}
                <div class="col-lg-4 col-md-6 col-xs-12" style="margin-bottom: 20px;">
                    <div class="hrm-chart-card hrm-fade-in">
                        <h4 class="hrm-chart-title">
                            <i class="fas fa-chart-pie"></i>
                            @lang('essentials::lang.leave_by_status')
                        </h4>
                        <div class="hrm-chart-canvas-wrap">
                            <canvas id="hrmLeaveStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Leave by Type (Polar Area) --}}
                <div class="col-lg-4 col-md-12 col-xs-12" style="margin-bottom: 20px;">
                    <div class="hrm-chart-card hrm-fade-in">
                        <h4 class="hrm-chart-title">
                            <i class="fas fa-layer-group"></i>
                            @lang('essentials::lang.leave_by_type')
                        </h4>
                        <div class="hrm-chart-canvas-wrap">
                            <canvas id="hrmLeaveTypeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ Section: Charts Row 2 — Trends ═══════════════ --}}
            <div class="row">
                {{-- Attendance Trend (Line) --}}
                <div class="col-lg-6 col-md-12 col-xs-12" style="margin-bottom: 20px;">
                    <div class="hrm-chart-card hrm-fade-in">
                        <h4 class="hrm-chart-title">
                            <i class="fas fa-chart-line"></i>
                            @lang('essentials::lang.attendance_trend')
                        </h4>
                        <div class="hrm-chart-canvas-wrap">
                            <canvas id="hrmAttendanceTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Leave Trend (Bar) --}}
                <div class="col-lg-6 col-md-12 col-xs-12" style="margin-bottom: 20px;">
                    <div class="hrm-chart-card hrm-fade-in">
                        <h4 class="hrm-chart-title">
                            <i class="fas fa-chart-bar"></i>
                            @lang('essentials::lang.leave_trend')
                        </h4>
                        <div class="hrm-chart-canvas-wrap">
                            <canvas id="hrmLeaveTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            @if ($is_admin)
            {{-- Attendance Today (Doughnut - Present vs Absent) --}}
            <div class="row">
                <div class="col-lg-4 col-md-6 col-xs-12" style="margin-bottom: 20px;">
                    <div class="hrm-chart-card hrm-fade-in">
                        <h4 class="hrm-chart-title">
                            <i class="fas fa-user-clock"></i>
                            @lang('essentials::lang.todays_attendance_overview')
                        </h4>
                        <div class="hrm-chart-canvas-wrap">
                            <canvas id="hrmAttendanceTodayChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- ═══ Today's Attendance Table ═══ --}}
                <div class="col-lg-8 col-md-6 col-xs-12" style="margin-bottom: 20px;">
                    @component('components.widget', [
                        'class' => '',
                        'title' => __('essentials::lang.todays_attendance'),
                        'icon' => '<i class="fas fa-user-check"></i>',
                    ])
                        <div style="max-height: 320px; overflow-y: auto;">
                            <table class="hrm-widget-table">
                                <thead>
                                    <tr>
                                        <th>@lang('essentials::lang.employee')</th>
                                        <th>@lang('essentials::lang.clock_in')</th>
                                        <th>@lang('essentials::lang.clock_out')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($todays_attendances as $attendance)
                                        <tr>
                                            <td>
                                                <span style="display: flex; align-items: center; gap: 8px;">
                                                    <span class="hrm-attendance-dot" style="background: {{ !empty($attendance->clock_out_time) ? 'var(--hrm-success)' : 'var(--hrm-warning)' }};"></span>
                                                    {{ $attendance->employee->user_full_name ?? '' }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ @format_datetime($attendance->clock_in_time) }}
                                                @if (!empty($attendance->clock_in_note))
                                                    <br><small class="text-muted">{{ $attendance->clock_in_note }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if (!empty($attendance->clock_out_time))
                                                    {{ @format_datetime($attendance->clock_out_time) }}
                                                @else
                                                    <span class="hrm-badge pending">@lang('essentials::lang.still_working')</span>
                                                @endif
                                                @if (!empty($attendance->clock_out_note))
                                                    <br><small class="text-muted">{{ $attendance->clock_out_note }}</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center" style="padding: 30px; color: var(--hrm-muted);">
                                                <i class="fas fa-inbox" style="font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.4;"></i>
                                                @lang('lang_v1.no_data')
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endcomponent
                </div>
            </div>
            @endif

        @endif

        {{-- ═══════════════ Section: Data Tables Row ═══════════════ --}}
        <div class="row">
            {{-- Pending Leave Requests --}}
            @can('essentials.approve_leave')
                <div class="col-lg-6 col-md-12 col-xs-12" style="margin-bottom: 20px;">
                    @component('components.widget', [
                        'class' => '',
                        'title' => __('essentials::lang.pending_leave_requests') . ' (' . $pending_leaves_count . ')',
                        'icon' => '<i class="fas fa-hourglass-half"></i>',
                    ])
                        <div style="max-height: 350px; overflow-y: auto;">
                            <table class="hrm-widget-table">
                                <thead>
                                    <tr>
                                        <th>@lang('essentials::lang.employee')</th>
                                        <th>@lang('essentials::lang.leave_type')</th>
                                        <th>@lang('report.date')</th>
                                        <th>@lang('sale.status')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pending_leaves as $leave)
                                        <tr>
                                            <td style="font-weight: 500;">
                                                {{ optional($leave->user)->user_full_name ?? '-' }}
                                            </td>
                                            <td>{{ optional($leave->leave_type)->leave_type ?? '-' }}</td>
                                            <td>
                                                {{ @format_date($leave->start_date) }}
                                                <span style="color: var(--hrm-muted);">→</span>
                                                {{ @format_date($leave->end_date) }}
                                            </td>
                                            <td>
                                                <span class="hrm-badge pending">
                                                    <i class="fas fa-clock" style="font-size: 9px;"></i>
                                                    @lang('essentials::lang.pending')
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center" style="padding: 30px; color: var(--hrm-muted);">
                                                <i class="fas fa-check-circle" style="font-size: 24px; display: block; margin-bottom: 8px; color: var(--hrm-success); opacity: 0.6;"></i>
                                                @lang('essentials::lang.no_pending_requests')
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endcomponent
                </div>
            @endcan

            {{-- Leaves On Today & Upcoming --}}
            <div class="col-lg-6 col-md-12 col-xs-12" style="margin-bottom: 20px;">
                @component('components.widget', [
                    'class' => '',
                    'title' => __('essentials::lang.leaves'),
                    'icon' => '<i class="fas fa-user-times"></i>',
                ])
                    <div style="max-height: 350px; overflow-y: auto;">
                        <table class="hrm-widget-table">
                            <thead>
                                <tr>
                                    <th colspan="2" style="background: #dbeafe; color: var(--hrm-primary);">
                                        <i class="fas fa-calendar-day"></i> @lang('home.today')
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($todays_leaves as $leave)
                                    <tr>
                                        <td style="font-weight: 500;">
                                            {{ optional($leave->user)->user_full_name ?? '' }}
                                        </td>
                                        <td>
                                            <span class="hrm-badge approved">{{ optional($leave->leave_type)->leave_type ?? '' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center" style="color: var(--hrm-muted);">
                                            @lang('lang_v1.no_data')
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <thead>
                                <tr>
                                    <th colspan="2" style="background: #fef3c7; color: #92400e;">
                                        <i class="fas fa-calendar-alt"></i> @lang('lang_v1.upcoming')
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($upcoming_leaves as $leave)
                                    <tr>
                                        <td>
                                            {{ optional($leave->user)->user_full_name ?? '' }}
                                            <br>
                                            <small class="text-muted">
                                                {{ @format_date($leave->start_date) }} - {{ @format_date($leave->end_date) }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="hrm-badge approved">{{ optional($leave->leave_type)->leave_type ?? '' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center" style="color: var(--hrm-muted);">
                                            @lang('lang_v1.no_data')
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>

        {{-- ═══════════════ Section: Birthdays & Holidays ═══════════════ --}}
        <div class="row">
            {{-- Birthdays --}}
            <div class="col-lg-6 col-md-12 col-xs-12" style="margin-bottom: 20px;">
                @component('components.widget', [
                    'class' => '',
                    'title' => __('essentials::lang.birthdays'),
                    'icon' => '<i class="fas fa-birthday-cake"></i>',
                ])
                    <div style="max-height: 320px; overflow-y: auto;">
                        {{-- Today's Birthdays --}}
                        <div style="padding: 6px 14px; background: #fef3c7; border-radius: 8px; margin-bottom: 10px;">
                            <strong style="font-size: 12px; color: #92400e;">
                                <i class="fas fa-star"></i> @lang('home.today')
                            </strong>
                        </div>
                        @forelse($today_births as $birthday)
                            @php
                                $colors = ['#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899'];
                                $initial = strtoupper(substr($birthday->first_name ?? $birthday->surname ?? '?', 0, 1));
                            @endphp
                            <div class="hrm-birthday-item">
                                <div class="hrm-birthday-avatar" style="background: {{ $colors[$loop->index % count($colors)] }};">
                                    {{ $initial }}
                                </div>
                                <div class="hrm-birthday-info">
                                    <div class="hrm-birthday-name">
                                        {{ $birthday->surname }} {{ $birthday->first_name }} {{ $birthday->last_name }}
                                    </div>
                                    <div class="hrm-birthday-date">
                                        🎂 {{ @format_date(\Carbon::parse($birthday->dob)->setYear(date('Y'))) }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center" style="padding: 12px; color: var(--hrm-muted); font-size: 13px;">
                                @lang('lang_v1.no_data')
                            </div>
                        @endforelse

                        {{-- Upcoming Birthdays --}}
                        <div style="padding: 6px 14px; background: #dbeafe; border-radius: 8px; margin: 14px 0 10px;">
                            <strong style="font-size: 12px; color: var(--hrm-primary);">
                                <i class="fas fa-calendar-alt"></i> @lang('lang_v1.upcoming')
                            </strong>
                        </div>
                        @forelse($up_comming_births as $birthday)
                            @php
                                $colors = ['#8b5cf6','#06b6d4','#ec4899','#2563eb','#10b981','#f59e0b','#ef4444'];
                                $initial = strtoupper(substr($birthday->first_name ?? $birthday->surname ?? '?', 0, 1));
                            @endphp
                            <div class="hrm-birthday-item">
                                <div class="hrm-birthday-avatar" style="background: {{ $colors[$loop->index % count($colors)] }};">
                                    {{ $initial }}
                                </div>
                                <div class="hrm-birthday-info">
                                    <div class="hrm-birthday-name">
                                        {{ $birthday->surname }} {{ $birthday->first_name }} {{ $birthday->last_name }}
                                    </div>
                                    <div class="hrm-birthday-date">
                                        @if (date('m') == '12' && \Carbon::parse($birthday->dob)->format('m') == '1')
                                            {{ @format_date(\Carbon::parse($birthday->dob)->setYear(date('Y') + 1)) }}
                                        @else
                                            {{ @format_date(\Carbon::parse($birthday->dob)->setYear(date('Y'))) }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center" style="padding: 12px; color: var(--hrm-muted); font-size: 13px;">
                                @lang('lang_v1.no_data')
                            </div>
                        @endforelse
                    </div>
                @endcomponent
            </div>

            {{-- Holidays --}}
            <div class="col-lg-6 col-md-12 col-xs-12" style="margin-bottom: 20px;">
                @component('components.widget', [
                    'class' => '',
                    'title' => __('essentials::lang.holidays'),
                    'icon' => '<i class="fas fa-suitcase-rolling"></i>',
                ])
                    <div style="max-height: 320px; overflow-y: auto;">
                        {{-- Today's Holidays --}}
                        <div style="padding: 6px 14px; background: #d1fae5; border-radius: 8px; margin-bottom: 10px;">
                            <strong style="font-size: 12px; color: #065f46;">
                                <i class="fas fa-calendar-day"></i> @lang('home.today')
                            </strong>
                        </div>
                        @forelse($todays_holidays as $holiday)
                            @php
                                $start_date = \Carbon::parse($holiday->start_date);
                                $end_date = \Carbon::parse($holiday->end_date);
                                $diff = $start_date->diffInDays($end_date) + 1;
                            @endphp
                            <div class="hrm-holiday-item">
                                <div class="hrm-holiday-icon" style="background: var(--hrm-success-light); color: var(--hrm-success);">
                                    <i class="fas fa-umbrella-beach"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; font-weight: 600; color: #1e293b;">{{ $holiday->name }}</div>
                                    <div style="font-size: 11px; color: var(--hrm-muted);">
                                        {{ @format_date($holiday->start_date) }}
                                        ({{ $diff . ' ' . Str::plural(__('lang_v1.day'), $diff) }})
                                        · {{ $holiday->location->name ?? __('lang_v1.all') }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center" style="padding: 12px; color: var(--hrm-muted); font-size: 13px;">
                                @lang('lang_v1.no_data')
                            </div>
                        @endforelse

                        {{-- Upcoming Holidays --}}
                        <div style="padding: 6px 14px; background: #e0e7ff; border-radius: 8px; margin: 14px 0 10px;">
                            <strong style="font-size: 12px; color: #3730a3;">
                                <i class="fas fa-calendar-alt"></i> @lang('lang_v1.upcoming')
                            </strong>
                        </div>
                        @forelse($upcoming_holidays as $holiday)
                            @php
                                $start_date = \Carbon::parse($holiday->start_date);
                                $end_date = \Carbon::parse($holiday->end_date);
                                $diff = $start_date->diffInDays($end_date) + 1;
                            @endphp
                            <div class="hrm-holiday-item">
                                <div class="hrm-holiday-icon" style="background: var(--hrm-purple-light); color: var(--hrm-purple);">
                                    <i class="fas fa-suitcase-rolling"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; font-weight: 600; color: #1e293b;">{{ $holiday->name }}</div>
                                    <div style="font-size: 11px; color: var(--hrm-muted);">
                                        {{ @format_date($holiday->start_date) }}
                                        ({{ $diff . ' ' . Str::plural(__('lang_v1.day'), $diff) }})
                                        · {{ $holiday->location->name ?? __('lang_v1.all') }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center" style="padding: 12px; color: var(--hrm-muted); font-size: 13px;">
                                @lang('lang_v1.no_data')
                            </div>
                        @endforelse
                    </div>
                @endcomponent
            </div>
        </div>

    </section>
@stop

@section('javascript')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="text/javascript">
        Chart.defaults.font.family = "Inter, 'Segoe UI', system-ui, -apple-system, sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#475569';

        const hrmPalette = [
            '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#06b6d4', '#ec4899', '#f97316', '#14b8a6', '#6366f1',
            '#84cc16', '#e11d48'
        ];

        function hrmGradient(ctx, colorHex) {
            const g = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height || 280);
            g.addColorStop(0, colorHex + '55');
            g.addColorStop(1, colorHex + '05');
            return g;
        }

        // ─── Department Distribution (Doughnut) ───
        (function () {
            const el = document.getElementById('hrmDeptChart');
            if (!el) return;
            const ctx = el.getContext('2d');

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: @json($dept_labels),
                    datasets: [{
                        data: @json($dept_counts),
                        backgroundColor: hrmPalette.slice(0, @json(count($dept_labels))),
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 10, usePointStyle: true, font: { size: 11 } }
                        }
                    }
                }
            });
        })();

        // ─── Leave by Status (Doughnut) ───
        (function () {
            const el = document.getElementById('hrmLeaveStatusChart');
            if (!el) return;
            const ctx = el.getContext('2d');

            const statusColors = {
                'Pending': '#f59e0b',
                'Approved': '#10b981',
                'Rejected': '#ef4444',
            };
            const labels = @json($leave_status_labels);
            const colors = labels.map(l => statusColors[l] || hrmPalette[Math.floor(Math.random() * hrmPalette.length)]);

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: @json($leave_status_data),
                        backgroundColor: colors,
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 10, usePointStyle: true, font: { size: 11 } }
                        }
                    }
                }
            });
        })();

        // ─── Leave by Type (Polar Area) ───
        (function () {
            const el = document.getElementById('hrmLeaveTypeChart');
            if (!el) return;
            const ctx = el.getContext('2d');

            new Chart(ctx, {
                type: 'polarArea',
                data: {
                    labels: @json($leave_type_labels),
                    datasets: [{
                        data: @json($leave_type_data),
                        backgroundColor: hrmPalette.slice(0, @json(count($leave_type_labels))).map(c => c + 'AA'),
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 10, usePointStyle: true, font: { size: 11 } }
                        }
                    },
                    scales: {
                        r: {
                            ticks: { display: false },
                            grid: { color: 'rgba(0,0,0,0.04)' }
                        }
                    }
                }
            });
        })();

        // ─── Attendance Trend (Line) ───
        (function () {
            const el = document.getElementById('hrmAttendanceTrendChart');
            if (!el) return;
            const ctx = el.getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($attendance_trend_labels),
                    datasets: [{
                        label: '@lang("essentials::lang.attendance_records")',
                        data: @json($attendance_trend_data),
                        borderColor: '#2563eb',
                        backgroundColor: hrmGradient(ctx, '#2563eb'),
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        borderWidth: 2.5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { precision: 0 }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: { usePointStyle: true, boxWidth: 10 }
                        }
                    }
                }
            });
        })();

        // ─── Leave Trend (Bar) ───
        (function () {
            const el = document.getElementById('hrmLeaveTrendChart');
            if (!el) return;
            const ctx = el.getContext('2d');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($leave_trend_labels),
                    datasets: [{
                        label: '@lang("essentials::lang.approved_leaves")',
                        data: @json($leave_trend_data),
                        backgroundColor: hrmPalette.slice(0, 6).map(c => c + '99'),
                        borderColor: hrmPalette.slice(0, 6),
                        borderWidth: 1.5,
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { precision: 0 }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: { usePointStyle: true, boxWidth: 10 }
                        }
                    }
                }
            });
        })();

        // ─── Today's Attendance (Present vs Absent Doughnut) ───
        (function () {
            const el = document.getElementById('hrmAttendanceTodayChart');
            if (!el) return;
            const ctx = el.getContext('2d');

            const present = {{ $present_today }};
            const absent = {{ $absent_today }};

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['@lang("essentials::lang.present")', '@lang("essentials::lang.absent")'],
                    datasets: [{
                        data: [present, absent],
                        backgroundColor: ['#10b981', '#ef4444'],
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 12, usePointStyle: true }
                        }
                    }
                }
            });
        })();
    </script>
@endsection
