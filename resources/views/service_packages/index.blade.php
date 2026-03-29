@extends('layouts.app')
@section('title', 'Service Packages')

@section('content')
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            Service Packages
            <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">Manage service packages</small>
        </h1>
    </section>

    <section class="content">
        @component('components.widget', ['class' => 'box-primary', 'title' => 'All Service Packages'])
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full"
                        id="btn_create_sp">
                        + @lang('messages.add')
                    </button>
                </div>
            @endslot

            <div class="row" style="margin-bottom:10px">
                <div class="col-md-3">
                    <label>Device</label>
                    <select id="filter_device_id" class="form-control select2" style="width:100%">
                        <option value="">All</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Model</label>
                    <select id="filter_repair_device_model_id" class="form-control select2" style="width:100%">
                        <option value="">All</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>From KM</label>
                    <input type="number" id="filter_from_km" class="form-control" placeholder="e.g. 10000">
                </div>
                <div class="col-md-3">
                    <label>To KM</label>
                    <input type="number" id="filter_to_km" class="form-control" placeholder="e.g. 50000">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="service_packages_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>KM</th>
                            <th>Device</th>
                            <th>Model</th>
                            <th>From</th>
                            <th>To</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent

        <!-- Service Package Modal -->
        <div class="modal fade" id="sp_modal" tabindex="-1" role="dialog" aria-labelledby="spModalLabel">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="spModalLabel">Create/Edit Service Package</h4>
                    </div>
                    <div class="modal-body">
                        <!-- Service package form will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Manage Package Products Modal -->
        <div class="modal fade" id="pp_modal" tabindex="-1" role="dialog" aria-labelledby="ppModalLabel">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="ppModalLabel">Manage Package Products</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row" style="margin-bottom:15px">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary" id="btn_add_product">
                                    <i class="fa fa-plus"></i> Add Product
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="package_products_table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Product Name</th>
                                        <th>SKU</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Management Modal -->
        <div class="modal fade" id="product_modal" tabindex="-1" role="dialog" aria-labelledby="productModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="productModalLabel">Add/Edit Product</h4>
                    </div>
                    <div class="modal-body">
                        <!-- Product form will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Initialize select2
    if ($.fn.select2) {
        $('.select2').select2();
    }

    var currentServicePackageId = null;
    var ppTable = null;

    // Initialize DataTable
    var spTable = $('#service_packages_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('service-packages.datatable') }}',
            data: function(d) {
                d.device_id = $('#filter_device_id').val();
                d.repair_device_model_id = $('#filter_repair_device_model_id').val();
                d.from_year = $('#filter_from_year').val();
                d.to_year = $('#filter_to_year').val();
                d.q = $('#filter_q').val();
            }
        },
        columns: [
            {
                data: null,
                name: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'name', name: 'name' },
            { data: 'km', name: 'km' },
            { data: 'device_name', name: 'c.name' },
            { data: 'repair_device_model_name', name: 'rdm.name' },
            { data: 'from', name: 'from', render: function(data) {
                return data ? data : 'N/A';
            }},
            { data: 'to', name: 'to', render: function(data) {
                return data ? data : 'N/A';
            }},
            {
                data: 'action',
                orderable: false,
                searchable: false,
                render: function(val, type, row){
                    return '<button class="btn btn-xs btn-primary edit-sp" data-id="'+(val.edit_id||'')+'">Edit</button> ' +
                           '<button class="btn btn-xs btn-info manage-products" data-id="'+(val.edit_id||'')+'">Manage Products</button> ' +
                           '<button class="btn btn-xs btn-danger delete-sp" data-id="'+(val.delete_id||'')+'">Delete</button>';
                }
            }
        ],
        order: [[0, 'desc']]
    });

    // Handle create button click
    $(document).on('click', '#btn_create_sp', function(e) {
        e.preventDefault();
        var url = '{{ route("service-packages.create") }}';
        loadModal(url);
    });

    // Handle manage products button click
    $(document).on('click', '.manage-products', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (id) {
            currentServicePackageId = id;
            loadPackageProducts();
        }
    });

    // Handle add product button click
    $(document).on('click', '#btn_add_product', function(e) {
        e.preventDefault();
        if (currentServicePackageId) {
            showProductModal(null, currentServicePackageId);
        }
    });

    // Handle edit product button click
    $(document).on('click', '.edit-product', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (id) {
            showProductModal(id, currentServicePackageId);
        }
    });

    // Handle delete product button click
    $(document).on('click', '.delete-product', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (id) {
            deletePackageProduct(id);
        }
    });

    // Handle form submission for service packages
    $(document).on('submit', '#sp_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        var url = form.attr('action');
        var method = form.attr('method');

        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#sp_modal').modal('hide');
                    toastr.success(response.msg);
                    spTable.ajax.reload();
                } else {
                    if (response.errors) {
                        var errors = '';
                        $.each(response.errors, function(key, value) {
                            errors += value + '<br>';
                        });
                        toastr.error(errors);
                    } else {
                        toastr.error(response.msg || 'An error occurred. Please try again.');
                    }
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred. Please try again.');
            }
        });
    });

    // Handle form submission for products
    $(document).on('submit', '#product_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        var url = form.attr('action');
        var method = form.attr('method');

        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#product_modal').modal('hide');
                    toastr.success(response.msg);
                    loadPackageProducts();
                } else {
                    if (response.errors) {
                        var errors = '';
                        $.each(response.errors, function(key, value) {
                            errors += value + '<br>';
                        });
                        toastr.error(errors);
                    } else {
                        toastr.error(response.msg || 'An error occurred. Please try again.');
                    }
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred. Please try again.');
            }
        });
    });

    // Handle delete button click for service packages
    $(document).on('click', '.delete-sp', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (id) {
            swal({
                title: LANG.sure,
                text: 'This will delete the service package permanently',
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var url = '{{ route("service-packages.destroy", ["id" => "__id__"]) }}';
                    url = url.replace('__id__', id);

                    $.ajax({
                        method: 'DELETE',
                        url: url,
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                spTable.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error('An error occurred. Please try again.');
                        }
                    });
                }
            });
        }
    });

    // Helper function to load modal content
    function loadModal(url) {
        $.ajax({
            url: url,
            method: 'GET',
            beforeSend: function() {
                $('#sp_modal .modal-body').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
                $('#sp_modal').modal('show');
            },
            success: function(response) {
                if (response.success) {
                    $('#sp_modal .modal-body').html(response.html);
                    $('#sp_modal').modal('show');
                    // Reinitialize select2 in modal
                    if ($.fn.select2) {
                        $('#sp_modal .select2').select2({
                            dropdownParent: $('#sp_modal')
                        });

                        // Handle device change to filter repair device models
                        $('#device_id').on('change', function() {
                            var deviceId = $(this).val();
                            var modelSelect = $('#repair_device_model_id');

                            if (deviceId) {
                                // Clear current options
                                modelSelect.find('option:not(:first)').remove();

                                // Fetch models for selected device
                                $.ajax({
                                    url: '/service-packages/get-models/' + deviceId,
                                    method: 'GET',
                                    success: function(models) {
                                        if (models.length > 0) {
                                            $.each(models, function(index, model) {
                                                modelSelect.append('<option value="' + model.id + '">' + model.name + '</option>');
                                            });
                                        }
                                        modelSelect.trigger('change');
                                    },
                                    error: function() {
                                        toastr.error('Failed to load repair device models');
                                    }
                                });
                            } else {
                                // Clear models if no device selected
                                modelSelect.find('option:not(:first)').remove();
                                modelSelect.trigger('change');
                            }
                        });
                    }
                } else {
                    toastr.error(response.msg || 'Failed to load form. Please try again.');
                }
            },
            error: function() {
                $('#sp_modal .modal-body').html('<div class="alert alert-danger">Failed to load form. Please try again.</div>');
            }
        });
    }

    // Load package products into the modal
    function loadPackageProducts() {
        $('#pp_modal').modal('show');

        if (ppTable) {
            ppTable.ajax.reload();
        } else {
            // Initialize package products DataTable
            ppTable = $('#package_products_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("package-products.datatable") }}',
                    data: function(d) {
                        d.service_package_id = currentServicePackageId;
                    }
                },
                columns: [
                    { data: 'id', name: 'pp.id' },
                    { data: 'product_name', name: 'p.name' },
                    { data: 'product_sku', name: 'p.sku' },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false,
                        render: function(val, type, row){
                            return '<button class="btn btn-xs btn-danger delete-product" data-id="'+(val.delete_id||'')+'">Delete</button>';
                        }
                    }
                ],
                order: [[0, 'desc']]
            });
        }
    }

    // Show product modal for add/edit
    function showProductModal(productId, servicePackageId) {
        var url = productId ?
            '{{ route("package-products.edit", ["id" => "__id__"]) }}'.replace('__id__', productId) :
            '{{ route("package-products.create") }}';

        $.ajax({
            url: url,
            method: 'GET',
            data: { service_package_id: servicePackageId },
            beforeSend: function() {
                $('#product_modal .modal-body').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
                $('#product_modal').modal('show');
            },
            success: function(response) {
                if (response.success) {
                    $('#product_modal .modal-body').html(response.html);
                    $('#product_modal').modal('show');
                    if ($.fn.select2) {
                        $('#product_modal .select2').select2({
                            dropdownParent: $('#product_modal')
                        });
                    }
                } else {
                    toastr.error(response.msg || 'Failed to load form. Please try again.');
                }
            },
            error: function() {
                $('#product_modal .modal-body').html('<div class="alert alert-danger">Failed to load form. Please try again.</div>');
            }
        });
    }

    // Delete package product
    function deletePackageProduct(id) {
        swal({
            title: LANG.sure,
            text: 'This will remove the product from this package',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                var url = '{{ route("package-products.destroy", ["id" => "__id__"]) }}'.replace('__id__', id);

                $.ajax({
                    method: 'DELETE',
                    url: url,
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            loadPackageProducts();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('An error occurred. Please try again.');
                    }
                });
            }
        });
    }

    // Close modal on click outside
    $(document).on('click', '.modal', function(e) {
        if ($(e.target).hasClass('modal')) {
            $(this).modal('hide');
        }
    });

    // Handle modal hidden event
    $('#sp_modal').on('hidden.bs.modal', function() {
        $(this).find('.modal-body').html('');
    });

    $('#pp_modal').on('hidden.bs.modal', function() {
        if (ppTable) {
            ppTable.destroy();
            ppTable = null;
        }
        currentServicePackageId = null;
    });

    // Reload table on filter changes
    $('#filter_device_id, #filter_repair_device_model_id, #filter_from_year, #filter_to_year').on('change', function() {
        spTable.ajax.reload();
    });

    // Add debounce to search input
    var searchTimer;
    $('#filter_q').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            spTable.ajax.reload();
        }, 500);
    });
});
</script>
@endsection
