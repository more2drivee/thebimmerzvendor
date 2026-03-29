@extends('layouts.app')
@section('title', __('repair::lang.recycle_bin'))

@section('content')
@include('repair::layouts.nav')
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('repair::lang.recycle_bin') (Invoices)</h1>
</section>

<section class="content no-print">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="repair_recycle_bin_table">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>@lang('receipt.date')</th>
                        <th>@lang('sale.invoice_no')</th>
                        <th>@lang('sale.customer_name')</th>
                        <th>@lang('sale.location')</th>
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
        var repair_recycle_bin_table = $('#repair_recycle_bin_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/repair/repair/recycle-bin',
            columnDefs: [
                {
                    targets: [0],
                    orderable: false,
                    searchable: false,
                },
            ],
            columns: [
                { data: 'action', name: 'action' },
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'customer', name: 'contacts.name' },
                { data: 'location', name: 'bl.name' },
                { data: 'deleted_at', name: 'transactions.deleted_at' },
            ],
        });

        $(document).on('click', '.restore_repair', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            $.ajax({
                method: 'POST',
                url: url,
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        repair_recycle_bin_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', '.delete_repair_permanent', function(e) {
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
                            repair_recycle_bin_table.ajax.reload();
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
