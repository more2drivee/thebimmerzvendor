@extends('layouts.app')

@section('title', __('carmarket::lang.reports') . ' - ' . __('carmarket::lang.module_title'))

@section('javascript')
<script>
$(document).ready(function() {
    var reportsTable = $('#reports-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('carmarket.reports.datatables') }}",
            data: function(d) {
                d.status = $('#filter_report_status').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', orderable: true },
            { data: 'vehicle_title', name: 'vehicle_title', orderable: false },
            { data: 'reporter_name', name: 'reporter_name', orderable: false },
            { data: 'reason', name: 'reason', orderable: false },
            { data: 'details', name: 'details', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at', orderable: true }
        ],
        order: [[0, 'desc']],
        pageLength: 25
    });

    $('#filter_report_status').change(function() {
        reportsTable.ajax.reload();
    });

    // Update report status
    $(document).on('change', '.report-status-select', function() {
        var id = $(this).data('id');
        var status = $(this).val();
        $.ajax({
            url: "{{ url('carmarket/reports') }}/" + id + "/status",
            method: 'PUT',
            data: { _token: '{{ csrf_token() }}', status: status },
            success: function(res) {
                if (res.success) toastr.success('Updated');
                else toastr.error('Error');
            }
        });
    });
});
</script>
@endsection

@section('content')
@include('carmarket::layouts.nav')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('carmarket::lang.reports')
    </h1>
</section>

<section class="content no-print">
    <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>@lang('carmarket::lang.report_status')</label>
                    <select id="filter_report_status" class="form-control">
                        <option value="">@lang('carmarket::lang.all_statuses')</option>
                        @foreach(['pending','reviewed','resolved','dismissed'] as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover" id="reports-table">
                <thead>
                    <tr>
                        <th width="50">{{ __('messages.id') }}</th>
                        <th>@lang('carmarket::lang.vehicle')</th>
                        <th>@lang('carmarket::lang.buyer_name')</th>
                        <th>@lang('carmarket::lang.report_reason')</th>
                        <th>@lang('carmarket::lang.report_details')</th>
                        <th width="150">@lang('carmarket::lang.report_status')</th>
                        <th>{{ __('messages.date') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</section>
@endsection
