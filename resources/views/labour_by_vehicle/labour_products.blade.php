<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('Labours')</h4>
            @if(auth()->user()->can('product.create'))
                <a href="{{ action([\App\Http\Controllers\ProductController::class, 'create']) }}" class="btn btn-success btn-xs pull-right" target="_blank" style="margin-top: 5px;">
                    <i class="fa fa-plus"></i> @lang('Add New')
                </a>
            @endif
        </div>

        <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="labour_products_table">
                    <thead>
                        <tr>
                            <th>@lang('Name')</th>
                            <th>@lang('Type')</th>
                            <th>@lang('Price')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#labour_products_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("labour-by-vehicle.labour-products.datatable") }}'
        },
        columns: [
            { data: 'name', name: 'products.name' },
            { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
            { data: 'price', name: 'price', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
});
</script>
