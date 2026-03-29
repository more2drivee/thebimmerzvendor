@extends('layouts.app')
@section('title', __('products'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('product.products')
            <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">@lang('product.manage')</small>
        </h1>
        <!-- <ol class="breadcrumb">
                                <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                                <li class="active">Here</li>
                            </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('product.title')])
            @can('brand.create')
                @slot('tool')
                    <div class="box-tools">
                        <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal pull-right"
                            data-href="{{ action([\App\Http\Controllers\BrandController::class, 'create']) }}"
                            data-container=".brands_modal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg> @lang('messages.add')
                        </a>
                    </div>
                @endslot
            @endcan
            @can('brand.view')
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="products_table">
                        <thead>
                            <tr>
                                <th>@lang('messages.date')</th>
                                <th>@lang('product.product_name')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('product.qty')</th>
                                <th>@lang('car.jobNo')</th>
                                <th>@lang('product.location')</th>
                                <th>@lang('product.workshop')</th>
                                <th>@lang('car.chassis')</th>
                                <th>@lang('car.plate')</th>
                                <th>@lang('car.color')</th>
                                <th>@lang('product.category')</th>
                                <th>@lang('car.model')</th>
                                <th>@lang('messages.status')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcan
        @endcomponent

        <div class="modal fade brands_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

    </section>
    <!-- /.content -->

@endsection

@section('javascript')

    <script type="text/javascript">
        $(document).ready(function() {
            $('#products_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('products.permission') }}",
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    error: function(xhr, error, code) {
                        console.log("Error: ", error);
                        console.log("Code: ", code);
                        console.log("Response: ", xhr.responseText);
                    }
                },
                scrollY: "75vh",
                scrollCollapse: true,
                columns: [{
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'product',
                        name: 'product'
                    },
                    {
                        data: 'SKU',
                        name: 'SKU'
                    },
                    {
                        data: 'Quantity',
                        name: 'Quantity'
                    },
                    {
                        data: 'job_sheet_no',
                        name: 'job_sheet_no'
                    },
                    {
                        data: 'location',
                        name: 'location'
                    },
                    {
                        data: 'workshop',
                        name: 'workshop'
                    },
                    {
                        data: 'chassis_number',
                        name: 'chassis_number'
                    },
                    {
                        data: 'plate_number',
                        name: 'plate_number'
                    },
                    {
                        data: 'color',
                        name: 'color'
                    },
                    {
                        data: 'Category',
                        name: 'Category'
                    },
                    {
                        data: 'model',
                        name: 'model'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
        });
    </script>
@endsection
