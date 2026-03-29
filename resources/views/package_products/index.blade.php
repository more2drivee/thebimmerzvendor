@extends('layouts.app')
@section('title', 'Package Products')

@php
    $servicePackageId = request('service_package_id');
    $servicePackage = $servicePackageId ? DB::table('service_package')->find($servicePackageId) : null;
@endphp

@section('content')
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            Package Products
            <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
                @if($servicePackage)
                    Products in: {{ $servicePackage->name }}
                @else
                    Manage products inside packages
                @endif
            </small>
        </h1>
        @if($servicePackage)
            <div style="margin-top: 10px;">
                <a href="{{ route('service-packages.index') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Back to Service Packages
                </a>
            </div>
        @endif
    </section>

    <section class="content">
        @component('components.widget', ['class' => 'box-primary', 'title' => 'All Package Products'])
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full"
                        id="btn_create_pp">
                        + @lang('messages.add')
                    </button>
                </div>
            @endslot

            <div class="row" style="margin-bottom:10px">
                <div class="col-md-4">
                    <label>Package</label>
                    <select id="filter_package_id" class="form-control select2" style="width:100%">
                        <option value="">All</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Search</label>
                    <input type="text" id="filter_q" class="form-control" placeholder="Product name / SKU / Package name">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="package_products_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Package</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent

        <div class="modal fade" id="pp_modal" tabindex="-1" role="dialog" aria-labelledby="ppModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="ppModalLabel">@lang('messages.add') @lang('package_product.package_product')</h4>
                    </div>
                    <div class="modal-body">
                        <!-- Content will be loaded here via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2').select2();
    }

    var servicePackageId = null;
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('service_package_id')) {
        servicePackageId = urlParams.get('service_package_id');
    }

    var ppTable = $('#package_products_table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ route('package-products.datatable') }}",
            data: function(d) {
                d.package_id = $('#filter_package_id').val();
                d.service_package_id = servicePackageId;
                d.q = $('#filter_q').val();
            }
        },
        columns: [
            { data: 'id', name: 'pp.id' },
            { data: 'package_name', name: 'sp.name' },
            { data: 'product_name', name: 'p.name' },
            { data: 'product_sku', name: 'p.sku' },
            
        ],
        order: [[0, 'desc']]
    });

    $('#filter_package_id').on('change', function(){ ppTable.ajax.reload(); });
    $('#filter_q').on('keyup change', function(){ ppTable.ajax.reload(); });
});
</script>
@endsection
