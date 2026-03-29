@extends('layouts.app')

@section('title', __('inventory_delivery_returns.title'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('inventory_delivery_returns.title')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">@lang('inventory_delivery_returns.subtitle')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('inventory_delivery_returns.widget_title')])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="inventory_delivery_returns_table">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>@lang('inventory_delivery_returns.columns.job_sheet')</th>
                        <th>@lang('inventory_delivery_returns.columns.customer')</th>
                        <th>@lang('inventory_delivery_returns.columns.location')</th>
                        <th>@lang('inventory_delivery_returns.columns.product')</th>
                        <th>@lang('inventory_delivery_returns.columns.qty')</th>
                        <th>@lang('inventory_delivery_returns.columns.price')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    var table = $('#inventory_delivery_returns_table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: __default_datatable_page_entries,
        ajax: {
            url: '{{ route("inventory-delivery-returns.datatable") }}'
        },
        columnDefs: [{
            targets: [0],
            orderable: false,
            searchable: false
        }],
        columns: [
            { data: 'action', name: 'action' },
            { data: 'job_sheet_no', name: 'rjs.job_sheet_no' },
            { data: 'customer_name', name: 'c.name' },
            { data: 'location_name', name: 'bl.name' },
            { data: 'product_name', name: 'p.name' },
            { data: 'quantity', name: 'pjo.quantity' },
            { data: 'price', name: 'pjo.price' }
        ]
    });

    $(document).on('click', '.js-return-inventory-delivery', function(e) {
        e.preventDefault();
        var url = $(this).data('href');

        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then((confirmed) => {
            if (confirmed) {
                $.ajax({
                    method: 'POST',
                    url: url,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            }
        });
    });
});
</script>
@endsection
