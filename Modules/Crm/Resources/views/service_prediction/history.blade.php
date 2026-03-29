@extends('layouts.app')

@section('title', 'سجل صيانة السيارة')

@section('content')

    @include('crm::layouts.nav')

    <section class="content no-print">
        <div class="row">
            {{-- Customer & Car Info --}}
            <div class="col-md-4">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-user"></i> بيانات العميل</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-condensed">
                            <tr><td><strong>الاسم:</strong></td><td>{{ $contact->name ?? '-' }}</td></tr>
                            <tr><td><strong>الجوال:</strong></td><td>{{ $contact->mobile ?? '-' }}</td></tr>
                        </table>
                    </div>
                </div>

                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-car"></i> بيانات السيارة</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-condensed">
                            <tr><td><strong>الماركة:</strong></td><td>{{ $device->brand_name ?? '-' }}</td></tr>
                            <tr><td><strong>الموديل:</strong></td><td>{{ $device->model_name ?? '-' }}</td></tr>
                            <tr><td><strong>اللوحة:</strong></td><td>{{ $device->plate_number ?? '-' }}</td></tr>
                            <tr><td><strong>الشاسيه:</strong></td><td>{{ $device->chassis_number ?? '-' }}</td></tr>
                            <tr><td><strong>اللون:</strong></td><td>{{ $device->color ?? '-' }}</td></tr>
                            <tr><td><strong>سنة الصنع:</strong></td><td>{{ $device->manufacturing_year ?? '-' }}</td></tr>
                        </table>
                    </div>
                </div>

                {{-- Car Switcher --}}
                @if($contact_cars->count() > 1)
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-exchange-alt"></i> سيارات العميل</h3>
                    </div>
                    <div class="box-body">
                        @foreach($contact_cars as $car)
                            @php
                                $carLabel = trim(($car->brand_name ?? '') . ' ' . ($car->model_name ?? ''));
                                if ($car->plate_number) $carLabel .= " ({$car->plate_number})";
                                $isActive = ($car->id == $device_id);
                            @endphp
                            <a href="{{ action([\Modules\Crm\Http\Controllers\ServicePredictionController::class, 'customerHistory'], ['contact_id' => $contact_id, 'device_id' => $car->id]) }}"
                               class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-default' }}" style="margin-bottom: 5px;">
                                <i class="fas fa-car"></i> {{ $carLabel ?: 'سيارة #' . $car->id }}
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Predictions & Filters --}}
            <div class="col-md-8">
                {{-- Filters --}}
                <div class="box box-default">
                    <div class="box-body">
                        <form method="GET" class="form-inline">
                            <div class="form-group" style="margin-left: 10px;">
                                <label>فلتر الخدمة:</label>
                                <select name="service_filter" class="form-control" onchange="this.form.submit()">
                                    <option value="">-- كل الخدمات --</option>
                                    @foreach($services_used as $pid => $pname)
                                        <option value="{{ $pid }}" {{ $service_filter == $pid ? 'selected' : '' }}>{{ $pname }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-info" id="toggle_chart_btn">
                                <i class="fas fa-chart-bar"></i> الرسم البياني
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Chart (hidden by default) --}}
                <div class="box box-default" id="chart_box" style="display: none;">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-chart-bar"></i> تكرار الصيانة شهرياً</h3>
                    </div>
                    <div class="box-body">
                        <canvas id="serviceChart" height="200"></canvas>
                    </div>
                </div>

                {{-- Per-Service Predictions --}}
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-chart-line"></i> توقعات الخدمات</h3>
                    </div>
                    <div class="box-body">
                        @if($predictions->isEmpty())
                            <p class="text-muted">لا توجد توقعات لهذه السيارة بعد.</p>
                        @else
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>الخدمة</th>
                                        <th>المعدل (شهر)</th>
                                        <th>آخر صيانة</th>
                                        <th>الموعد المتوقع</th>
                                        <th>الكمية المتوقعة</th>
                                        <th>الحالة</th>
                                        <th>المصدر</th>
                                        <th>الزيارات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($predictions as $pred)
                                        <tr>
                                            <td>{{ optional($pred->serviceProduct)->name ?? '-' }}</td>
                                            <td>{{ $pred->avg_interval_months }}</td>
                                            <td>{{ $pred->last_service_date ? $pred->last_service_date->format('Y-m-d') : '-' }}</td>
                                            <td>{{ $pred->next_expected_date ? $pred->next_expected_date->format('Y-m') : '-' }}</td>
                                            <td>{{ $pred->predicted_quantity ? number_format($pred->predicted_quantity, 1) : '-' }}</td>
                                            <td>
                                                @if($pred->status === 'on_time')
                                                    <span class="badge bg-success">في الموعد</span>
                                                @elseif($pred->status === 'due')
                                                    <span class="badge bg-warning">مستحق</span>
                                                @else
                                                    <span class="badge bg-danger">متأخر ({{ $pred->overdue_months }} شهر)</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $srcMap = ['history' => 'سجل', 'rule' => 'قاعدة', 'hybrid' => 'مدمج'];
                                                @endphp
                                                {{ $srcMap[$pred->prediction_source] ?? '-' }}
                                            </td>
                                            <td>{{ $pred->total_services_count }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>

                {{-- Service History --}}
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-history"></i> سجل الصيانات</h3>
                    </div>
                    <div class="box-body">
                        @if($history->isEmpty())
                            <p class="text-muted">لا يوجد سجل صيانات لهذه السيارة.</p>
                        @else
                            <table class="table table-bordered table-striped" id="history_table">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>رقم الفاتورة</th>
                                        <th>رقم أمر الشغل</th>
                                        <th>الخدمة</th>
                                        <th>التصنيف</th>
                                        <th>الكمية</th>
                                        <th>السعر</th>
                                        <th>العداد (كم)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($history as $record)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($record->transaction_date)->format('Y-m-d') }}</td>
                                            <td>{{ $record->invoice_no ?? '-' }}</td>
                                            <td>{{ $record->job_sheet_no ?? '-' }}</td>
                                            <td>{{ $record->product_name ?? '-' }}</td>
                                            <td>{{ $record->category_name ?? '-' }}</td>
                                            <td>{{ $record->quantity }}</td>
                                            <td>{{ number_format($record->unit_price, 2) }}</td>
                                            <td>{{ $record->km ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <a href="{{ action([\Modules\Crm\Http\Controllers\ServicePredictionController::class, 'index']) }}" class="btn btn-default">
                    <i class="fas fa-arrow-right"></i> العودة للتوقعات
                </a>
            </div>
        </div>
    </section>

@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    $(document).ready(function() {
        $('#history_table').DataTable({
            order: [[0, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
            }
        });

        // Toggle chart
        $('#toggle_chart_btn').on('click', function() {
            $('#chart_box').slideToggle();
        });

        // Chart
        var chartData = @json($chart_data);
        if (chartData.length > 0) {
            var ctx = document.getElementById('serviceChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.map(function(d) { return d.month; }),
                    datasets: [
                        {
                            label: 'عدد الخدمات',
                            data: chartData.map(function(d) { return d.count; }),
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'إجمالي الكمية',
                            data: chartData.map(function(d) { return d.total_qty; }),
                            backgroundColor: 'rgba(255, 159, 64, 0.5)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });
        }
    });
</script>
@endsection
