@extends('layouts.app')

@section('title', 'توقعات الصيانة الذكية')

@section('content')

    @include('crm::layouts.nav')

    <section class="content no-print">
        {{-- Stats Cards --}}
        <div class="row">
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">في الموعد</span>
                        <span class="info-box-number">{{ $stats->on_time_count ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">مستحق هذا الشهر</span>
                        <span class="info-box-number">{{ $stats->due_count ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">متأخر</span>
                        <span class="info-box-number">{{ $stats->overdue_count ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon"><i class="fas fa-skull-crossbones"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">خطر عالي (+3 شهور)</span>
                        <span class="info-box-number">{{ $stats->high_risk_count ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters & Actions --}}
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-chart-line"></i> توقعات الصيانة الذكية</h3>
                        <div class="box-tools">
                            <button type="button" class="tw-dw-btn tw-dw-btn-sm tw-dw-btn-success" id="recalculate_btn">
                                <i class="fas fa-sync-alt"></i> إعادة حساب التوقعات
                            </button>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-3">
                                <select class="form-control" id="status_filter">
                                    <option value="">-- كل الحالات --</option>
                                    <option value="on_time">في الموعد</option>
                                    <option value="due">مستحق</option>
                                    <option value="overdue">متأخر</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="service_filter">
                                    <option value="">-- كل الخدمات --</option>
                                    @foreach($services_list as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <table class="table table-bordered table-striped" id="predictions_table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>العميل</th>
                                    <th>الجوال</th>
                                    <th>السيارة</th>
                                    <th>الخدمة</th>
                                    <th>آخر صيانة</th>
                                    <th>المعدل (شهر)</th>
                                    <th>الموعد المتوقع</th>
                                    <th>الكمية المتوقعة</th>
                                    <th>الحالة</th>
                                    <th>الاتجاه</th>
                                    <th>عدد الزيارات</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        var predictions_table = $('#predictions_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ action([\Modules\Crm\Http\Controllers\ServicePredictionController::class, "index"]) }}',
                data: function(d) {
                    d.status_filter = $('#status_filter').val();
                    d.service_filter = $('#service_filter').val();
                }
            },
            columns: [
                { data: 'customer_name', name: 'contacts.name' },
                { data: 'customer_mobile', name: 'contacts.mobile' },
                { data: 'car_info', name: 'car_info', orderable: false, searchable: false },
                { data: 'service_name', name: 'svc_product.name' },
                { data: 'last_service_date', name: 'service_predictions.last_service_date' },
                { data: 'avg_interval_months', name: 'service_predictions.avg_interval_months' },
                { data: 'next_expected_date', name: 'service_predictions.next_expected_date' },
                { data: 'predicted_qty_display', name: 'service_predictions.predicted_quantity' },
                { data: 'status_badge', name: 'service_predictions.status', orderable: false, searchable: false },
                { data: 'trend_badge', name: 'service_predictions.behavior_trend', orderable: false, searchable: false },
                { data: 'total_services_count', name: 'service_predictions.total_services_count' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            order: [[8, 'desc'], [6, 'asc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
            }
        });

        // Filter change
        $('#status_filter, #service_filter').on('change', function() {
            predictions_table.ajax.reload();
        });

        // Recalculate button
        $('#recalculate_btn').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الحساب...');

            $.ajax({
                url: '{{ action([\Modules\Crm\Http\Controllers\ServicePredictionController::class, "recalculate"]) }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        predictions_table.ajax.reload();
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        toastr.error(response.msg);
                    }
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء إعادة الحساب');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> إعادة حساب التوقعات');
                }
            });
        });

        // Send reminder button
        $(document).on('click', '.send_reminder_btn', function() {
            var prediction_id = $(this).data('id');
            var btn = $(this);
            btn.prop('disabled', true);

            $.ajax({
                url: '{{ action([\Modules\Crm\Http\Controllers\ServicePredictionController::class, "sendReminder"]) }}',
                type: 'POST',
                data: { prediction_id: prediction_id },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        predictions_table.ajax.reload();
                    } else {
                        toastr.error(response.msg);
                    }
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء إرسال التذكير');
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });
    });
</script>
@endsection
