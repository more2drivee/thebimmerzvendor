@extends('layouts.app')

@section('title', __('client_flagged_products.title'))

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>{{ __('client_flagged_products.title') }}</h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('client_flagged_products.list') }}</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-primary btn-sm" id="add_client_flagged_product">
                                <i class="fas fa-plus"></i> {{ __('client_flagged_products.add') }}
                            </button>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="client_flagged_products_table">
                                <thead>
                                    <tr>
                                        <th>{{ __('product.name') }}</th>
                                        <th>{{ __('product.sku') }}</th>
                                        <th>{{ __('category.category') }}</th>
                                        <th>{{ __('brand.brand') }}</th>
                                        <th>{{ __('business.location') }}</th>
                                        <th>{{ __('lang_v1.created_at') }}</th>
                                        <th>{{ __('lang_v1.action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="client_flagged_product_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('client_flagged_products.add') }}</h4>
            </div>
            <div class="modal-body" id="client_flagged_product_modal_body">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var datatable = $('#client_flagged_products_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("client_flagged_products.datatable") }}',
            data: function(d) {}
        },
        columns: [
            { data: 'name' },
            { data: 'sku' },
            { data: 'category_name' },
            { data: 'brand_name' },
            { data: 'location_name' },
            { data: 'created_at' },
            {
                data: 'action',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    var html = '<div class="btn-group">';
                    html += '<button type="button" class="btn btn-info btn-xs edit_client_flagged_product" data-id="' + row.id + '"><i class="fas fa-edit"></i></button>';
                    html += '<button type="button" class="btn btn-danger btn-xs delete_client_flagged_product" data-id="' + row.id + '"><i class="fas fa-trash"></i></button>';
                    html += '<a href="' + row.quick_sell_url + '" class="btn btn-success btn-xs"><i class="fas fa-shopping-cart"></i></a>';
                    html += '</div>';
                    return html;
                }
            }
        ],
        language: {
            url: "{{ asset('js/datatables/' . app()->getLocale() . '.json') }}"
        }
    });

    $(document).on('click', '#add_client_flagged_product', function(e) {
        e.preventDefault();
        openModal();
    });

    $(document).on('click', '.edit_client_flagged_product', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        openModal(id);
    });

    $(document).on('click', '.delete_client_flagged_product', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (confirm('{{ __("lang_v1.delete_confirm") }}')) {
            $.ajax({
                url: '{{ route("client_flagged_products.destroy", ["id" => ""]) }}' + id,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        datatable.ajax.reload();
                    } else {
                        toastr.error(response.msg);
                    }
                },
                error: function(xhr) {
                    toastr.error('{{ __("messages.something_went_wrong") }}');
                }
            });
        }
    });

    function openModal(id = null) {
        var url = id ? '{{ route("client_flagged_products.edit", ["id" => ""]) }}' + id : '{{ route("client_flagged_products.create") }}';

        $('#client_flagged_product_modal').modal('show');
        $('#client_flagged_product_modal_body').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#client_flagged_product_modal_body').html(response.html);
                    $('.select2').select2();
                }
            },
            error: function(xhr) {
                toastr.error('{{ __("messages.something_went_wrong") }}');
                $('#client_flagged_product_modal').modal('hide');
            }
        });
    }

    $(document).on('submit', '#client_flagged_product_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(form[0]);
        var id = form.find('input[name="id"]').val();
        var url = id ? '{{ route("client_flagged_products.update", ["id" => ""]) }}' + id : '{{ route("client_flagged_products.store") }}';
        var method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-HTTP-Method-Override': method
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg);
                    $('#client_flagged_product_modal').modal('hide');
                    datatable.ajax.reload();
                } else {
                    toastr.error(response.msg);
                    if (response.errors) {
                        $.each(response.errors, function(key, value) {
                            toastr.error(value);
                        });
                    }
                }
            },
            error: function(xhr) {
                toastr.error('{{ __("messages.something_went_wrong") }}');
            }
        });
    });
});
</script>
@endpush
