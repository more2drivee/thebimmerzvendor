@extends('layouts.app')
@section('title', __('repair::lang.recycle_bin'))

@section('content')
@include('repair::layouts.nav')
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('repair::lang.recycle_bin') (Job Sheets)</h1>
</section>

<section class="content no-print">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="job_sheet_recycle_bin_table">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>@lang('repair::lang.job_sheet_no')</th>
                        <th>@lang('role.customer')</th>
                        <th>@lang('business.location')</th>
                        <th>@lang('lang_v1.deleted_at')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var job_sheet_recycle_bin_table = $('#job_sheet_recycle_bin_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/repair/job-sheet/recycle-bin',
            columnDefs: [
                {
                    targets: [0],
                    orderable: false,
                    searchable: false,
                },
            ],
            columns: [
                { data: 'action', name: 'action' },
                { data: 'job_sheet_no', name: 'job_sheet_no' },
                { data: 'customer', name: 'contacts.name' },
                { data: 'location', name: 'bl.name' },
                { data: 'deleted_at', name: 'repair_job_sheets.deleted_at' },
            ],
        });

        $(document).on('click', '.restore_job_sheet', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            $.ajax({
                method: 'POST',
                url: url,
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        job_sheet_recycle_bin_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', '.delete_job_sheet_permanent', function(e) {
            e.preventDefault();
            if (confirm("{{__('lang_v1.confirmation')}}")) {
                var url = $(this).data('href');
                $.ajax({
                    method: 'DELETE',
                    url: url,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            job_sheet_recycle_bin_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
</script>
@endsection
