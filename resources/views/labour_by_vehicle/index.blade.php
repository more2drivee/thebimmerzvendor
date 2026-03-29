@extends('layouts.app')

@section('title', __('Labour by Vehicle'))

@section('content')

<!-- Navbar -->
<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#labour-by-vehicle-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ route('labour-by-vehicle.index') }}"><i class="fas fa-car-side"></i> {{__('Labour by Vehicle')}}</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="labour-by-vehicle-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    <li class="active">
                        <a href="{{ route('labour-by-vehicle.index') }}">
                            @lang('All Labour by Vehicle')
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('labour-by-vehicle.search.form') }}">
                            <i class="fa fa-search"></i> @lang('Search by Vehicle')
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('labour-by-vehicle.labour-products') }}">
                            <i class="fa fa-cogs"></i> @lang('Labours')
                        </a>
                    </li>
                    
                        @if (auth()->user()->can('product.create'))
                            <li>
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
    <h1>@lang('Labour by Vehicle')
        <small>@lang('Manage labour services by vehicle model')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All Labour by Vehicle')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal" 
                    data-href="{{action([\App\Http\Controllers\LabourByVehicleController::class, 'create'])}}" 
                    data-container=".labour_vehicle_modal">
                    <i class="fa fa-plus"></i> @lang('Add Labour by Vehicle')</button>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="labour_by_vehicle_table">
                <thead>
                    <tr>
                        <th>@lang('ID')</th>
                        <th>@lang('Name')</th>
                        <th>@lang('Brand')</th>
                        <th>@lang('Model')</th>
                        <th>@lang('From')</th>
                        <th>@lang('To')</th>
                        <th>@lang('Action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade labour_vehicle_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        
        // Labour by Vehicle DataTable
        var labour_by_vehicle_table = $('#labour_by_vehicle_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('labour-by-vehicle.datatable') }}"
            },
            columns: [
                { data: 'id', name: 'labour_by_vehicle.id' },
                { data: 'name', name: 'name', orderable: false, searchable: false },
                { data: 'brand_name', name: 'brand_name', orderable: false, searchable: false },
                { data: 'model_name', name: 'model_name', orderable: false, searchable: false },
                { data: 'from', name: 'labour_by_vehicle.from' },
                { data: 'to', name: 'labour_by_vehicle.to' },
             
                { data: 'action', name: 'action' }
            ]
        });

        $('.labour_vehicle_modal').on('hidden.bs.modal', function () {
            $(this).html('');
            $(this).removeData('trigger-element');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        });

        $(document).on('click', '.btn-modal[data-container=".labour_vehicle_modal"]', function(){
            $('.labour_vehicle_modal').data('trigger-element', $(this));
        });

        // Delete Labour by Vehicle
        $(document).on('click', '.delete-labour-vehicle', function(e){
            e.preventDefault();
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_service,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).data('href');
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        data: {_token: $('meta[name="csrf-token"]').attr('content')},
                        success: function(result){
                            if(result.success == true){
                                toastr.success(result.message);
                                labour_by_vehicle_table.ajax.reload();
                            } else {
                                toastr.error(result.message);
                            }
                        }
                    });
                }
            });
        });

        var modelPlaceholder = "{{ __('Please Select') }}";

        function loadModelsByBrand(brandId, selectedModelId) {
            var modelSelect = $('#repair_device_model_id');
            if (!modelSelect.length) {
                return;
            }

            if (!brandId) {
                modelSelect.empty();
                modelSelect.append(new Option(modelPlaceholder, '', true, false));
                modelSelect.trigger('change.select2');
                return;
            }

            $.ajax({
                url: "{{ route('labour-by-vehicle.models-by-brand') }}",
                data: { brand_id: brandId },
                dataType: 'json',
                success: function(models) {
                    modelSelect.empty();
                    modelSelect.append(new Option(modelPlaceholder, '', true, false));
                    $.each(models || [], function(_, model) {
                        var isSelected = selectedModelId && String(selectedModelId) === String(model.id);
                        var option = new Option(model.name, model.id, false, isSelected);
                        modelSelect.append(option);
                    });
                    modelSelect.trigger('change.select2');
                }
            });
        }

        // Initialize modal content (select2)
        $('.labour_vehicle_modal').on('shown.bs.modal', function () {
            if ($('#device_id').length) {
                if ($('#device_id').hasClass('select2-hidden-accessible')) {
                    $('#device_id').select2('destroy');
                }
                $('#device_id').select2({
                    width: '100%',
                    dropdownParent: $('.labour_vehicle_modal'),
                    placeholder: "{{ __('Please Select') }}",
                    allowClear: true
                });
            }
            if ($('#repair_device_model_id').length) {
                if ($('#repair_device_model_id').hasClass('select2-hidden-accessible')) {
                    $('#repair_device_model_id').select2('destroy');
                }
                $('#repair_device_model_id').select2({
                    width: '100%',
                    dropdownParent: $('.labour_vehicle_modal'),
                    placeholder: "{{ __('Please Select') }}",
                    allowClear: true
                });
            }

            var selectedModel = $('#repair_device_model_id').data('selected-model');
            var selectedBrand = $('#device_id').val();
            if (selectedBrand) {
                loadModelsByBrand(selectedBrand, selectedModel);
            }
        });

        $(document).on('change', '#device_id', function() {
            var brandId = $(this).val();
            $('#repair_device_model_id').data('selected-model', '');
            loadModelsByBrand(brandId, null);
        });

        // Labour by Vehicle Form Submit
        $(document).on('submit', '#labour_vehicle_form', function(e){
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            var method = form.find('input[name="_method"]').val() || 'POST';
            
            $.ajax({
                method: method,
                url: url,
                dataType: "json",
                data: form.serialize(),
                success: function(result){
                    if(result.success == true){
                        $('.labour_vehicle_modal').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        toastr.success(result.message);
                        labour_by_vehicle_table.ajax.reload();
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

        // Edit Labour by Vehicle
        $(document).on('click', '.edit-labour-vehicle', function(e){
            e.preventDefault();
            var btn = $(this);
            var url = btn.data('href');
            
            $.ajax({
                url: url,
                dataType: 'html',
                success: function(result) {
                    $('.labour_vehicle_modal').html(result).modal('show');
                },
                error: function(xhr) {
                    toastr.error('Failed to load edit form.');
                }
            });
        });

        // Manage Labours
        $(document).on('click', '.manage-labours', function(e){
            e.preventDefault();
            var btn = $(this);
            var url = btn.data('href');
            
            $.ajax({
                url: url,
                dataType: 'html',
                success: function(result) {
                    $('.labour_vehicle_modal').html(result).modal('show');
                },
                error: function(xhr) {
                    toastr.error('Failed to load manage labours.');
                }
            });
        });

        // Toggle Labour (Add/Remove)
        $(document).on('click', '.toggle-labour', function(e){
            e.preventDefault();
            var btn = $(this);
            var labourByVehicleId = btn.data('labour-by-vehicle-id');
            var productId = btn.data('product-id');
            var action = btn.data('action');
            var mappingId = btn.data('mapping-id');
            
            $.ajax({
                method: "POST",
                url: "{{ route('labour-by-vehicle.toggle-labour') }}",
                dataType: "json",
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    labour_by_vehicle_id: labourByVehicleId,
                    product_id: productId,
                    action: action
                },
                success: function(result){
                    if(result.success == true){
                        toastr.success(result.message);
                        // Reload the manage labours datatable
                        var manageLaboursTable = $('#manage_labours_table');
                        if (manageLaboursTable.length) {
                            manageLaboursTable.DataTable().ajax.reload();
                        }
                    } else {
                        toastr.error(result.message);
                    }
                },
                error: function(xhr) {
                    toastr.error('An error occurred. Please try again.');
                }
            });
        });
    });
</script>
@endsection
