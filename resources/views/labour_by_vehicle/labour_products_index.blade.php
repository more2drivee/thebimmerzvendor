@extends('layouts.app')

@section('title', __('Labours'))

@section('content')

<!-- Navbar -->
<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#labour-products-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ route('labour-by-vehicle.index') }}"><i class="fas fa-car-side"></i> {{__('Labour by Vehicle')}}</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="labour-products-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    <li>
                        <a href="{{ route('labour-by-vehicle.index') }}">
                            @lang('All Labour by Vehicle')
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('labour-by-vehicle.search.form') }}">
                            <i class="fa fa-search"></i> @lang('Search by Vehicle')
                        </a>
                    </li>

                    <li class="active">
                        <a href="{{ route('labour-by-vehicle.labour-products') }}">
                            @lang('Labours')
                        </a>
                    </li>
                    
                            @if(auth()->user()->can('product.create'))
                                <li @if (request()->segment(1) == 'labour-by-vehicle' && request()->segment(2) == 'import') class="active" @endif>
                                    <a href="{{ action([\App\Http\Controllers\LabourByVehicleController::class, 'importLabourByVehicleForm']) }}">
                                        @lang('Labour Import')
                                    </a>
                                </li>
                            @endif

                
                </ul>
            </div>
        </div>
    </nav>
</section>

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Labours')
        <small>@lang('Manage labour services')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All Labours')])
        @slot('tool')
            <div class="box-tools">
                @if(auth()->user()->can('product.create'))
                    <button type="button" class="btn btn-block btn-primary btn-modal" 
                        data-href="{{ action([\App\Http\Controllers\LabourByVehicleController::class, 'createLabourProductModal']) }}" 
                        data-container=".labour_products_modal">
                        <i class="fa fa-plus"></i> @lang('Add New Labour')</button>
                @endif
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="labour_products_table">
                <thead>
                    <tr>
                        <th>@lang('Name')</th>
                        <th>@lang('Type')</th>
                        <th>@lang('Action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

</section>
<!-- /.content -->

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        
        // Labour Products DataTable
        var labour_products_table = $('#labour_products_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('labour-by-vehicle.labour-products.datatable') }}"
            },
            columns: [
                { data: 'name', name: 'products.name' },
                { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $('.labour_products_modal').on('hidden.bs.modal', function () {
            $(this).html('');
            $(this).removeData('trigger-element');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        });

        $(document).on('click', '.btn-modal[data-container=".labour_products_modal"]', function(){
            $('.labour_products_modal').data('trigger-element', $(this));
        });

        $(document).on('submit', '#labour_product_form', function(e){
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');

            $.ajax({
                method: 'POST',
                url: url,
                dataType: "json",
                data: form.serialize(),
                success: function(result){
                    if(result.success == true){
                        $('.labour_products_modal').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        toastr.success(result.message);
                        labour_products_table.ajax.reload();
                    } else {
                        toastr.error(result.message);
                    }
                },
                error: function(xhr) {
                    if (xhr.status == 422) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            toastr.error(value[0]);
                        });
                    } else {
                        toastr.error('An error occurred. Please try again.');
                    }
                }
            });
        });

        $(document).on('submit', '#labour_product_edit_form', function(e){
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');

            $.ajax({
                method: 'PUT',
                url: url,
                dataType: "json",
                data: form.serialize(),
                success: function(result){
                    if(result.success == true){
                        $('.labour_products_modal').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        toastr.success(result.message);
                        labour_products_table.ajax.reload();
                    } else {
                        toastr.error(result.message);
                    }
                },
                error: function(xhr) {
                    if (xhr.status == 422) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            toastr.error(value[0]);
                        });
                    } else {
                        toastr.error('An error occurred. Please try again.');
                    }
                }
            });
        });
    });
</script>
@endsection

<div class="modal fade labour_products_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>
