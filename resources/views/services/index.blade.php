@extends('layouts.app')
@section('title', __('Labour Management'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Labour Management')
        <small>@lang('Manage your labour')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All Labour')])
        @slot('tool')
            <div class="box-tools" style="display:flex; gap:8px; align-items:center;">
                <div>
                    <select id="location_filter" class="form-control input-sm" style="min-width: 220px;" multiple>
                        @if(isset($business_locations) && count($business_locations) > 0)
                            @foreach($business_locations as $id => $name)
                                <option value="{{$id}}">{{$name}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <button type="button" class="btn btn-block btn-primary btn-modal" 
                    data-href="{{action([\App\Http\Controllers\ServiceController::class, 'create'])}}" 
                    data-container=".services_modal">
                    <i class="fa fa-plus"></i> @lang('Add Labour')</button>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="services_table">
                <thead>
                    <tr>
                        <th>@lang('Name')</th>
                        <th>@lang('Price')</th>
                        <th>@lang('Workshops')</th>
                        <th>@lang('Location')</th>
                        <th>@lang('Labour Hours')</th>
                        <th>@lang('Action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade services_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        
        // Initialize multi-select for location filter
        $('#location_filter').select2({
            placeholder: '{{ __("Select locations") }}',
            allowClear: true,
            width: 'resolve'
        });
        
        // Services DataTable
        var services_table = $('#services_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('s.datable') }}",
                data: function (d) {
                    d.location_ids = $('#location_filter').val();
                }
            },
       
            columns: [
                { data: 'name', name: 'products.name' },
                { data: 'selling_price', name: 'selling_price', orderable: false, searchable: false },
                { data: 'workshop_names', name: 'workshop_names', orderable: false, searchable: false },
                { data: 'location_names', name: 'location_names', orderable: false, searchable: false },
                { data: 'serviceHours', name: 'products.serviceHours', orderable: false, searchable: false },
                { data: 'action', name: 'action' }
            ]
        });

        // Reload table on location change
        $(document).on('change', '#location_filter', function(){
            services_table.ajax.reload();
        });

        // Helper: Refresh workshops and flat rates by selected product locations
        function refreshOptionsByLocations(locationIds) {
            $.ajax({
                url: "{{ route('services.options-by-locations') }}",
                data: { location_ids: locationIds },
                dataType: 'json',
                success: function(res) {
                    // Update workshops
                    var $workshops = $('#workshop_ids');
                    if ($workshops.length) {
                        var selectedWorkshops = $workshops.val();
                        if (!selectedWorkshops || selectedWorkshops.length === 0) {
                            var selectedWorkshopsCsv = $workshops.data('selected-workshops');
                            selectedWorkshops = selectedWorkshopsCsv ? String(selectedWorkshopsCsv).split(',').filter(Boolean) : [];
                        }
                        $workshops.empty();
                        $.each(res.workshops || [], function(_, item) {
                            var isSelected = selectedWorkshops.includes(String(item.id)) || selectedWorkshops.includes(item.id);
                            var opt = new Option(item.name, item.id, false, isSelected);
                            $workshops.append(opt);
                        });
                        $workshops.trigger('change.select2');
                    }

                    // Update flat rates
                    var $flatRate = $('#flat_rate_id');
                    if ($flatRate.length) {
                        var selectedFlatRate = $flatRate.val() || $flatRate.data('selected-flat-rate');
                        $flatRate.empty();
                        $flatRate.append(new Option('{{ __("Please Select") }}', '', true, false));
                        $.each(res.flat_rates || [], function(_, fr) {
                            var isSelected = String(fr.id) === String(selectedFlatRate);
                            var label = fr.name + ' (' + fr.price_per_hour + '/hr)';
                            var opt = new Option(label, fr.id, false, isSelected);
                            $(opt).attr('data-price-per-hour', fr.price_per_hour);
                            $flatRate.append(opt);
                        });
                        $flatRate.trigger('change.select2');
                    }
                },
                error: function() {
                    toastr.error('{{ __("Failed to load options for selected locations.") }}');
                }
            });
        }

        $('.services_modal').on('hidden.bs.modal', function () {
            $(this).html('');
            $(this).removeData('trigger-element');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        });

        $(document).on('click', '.btn-modal[data-container=".services_modal"]', function(){
            $('.services_modal').data('trigger-element', $(this));
        });

        $('.services_modal').on('shown.bs.modal', function () {
            initializeServiceForm();
        });

        // Delete Service
        $(document).on('click', '.delete-service', function(e){
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
                                services_table.ajax.reload();
                            } else {
                                toastr.error(result.message);
                            }
                        }
                    });
                }
            });
        });

        // Service Form Submit
        $(document).on('submit', '#service_form', function(e){
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
                        $('.services_modal').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        toastr.success(result.message);
                        services_table.ajax.reload();
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

        function initializeServiceForm() {
            if (!$('#service_form').length) {
                return;
            }

            // Use setTimeout to ensure DOM is fully loaded before initializing Select2
            setTimeout(function() {
                // Initialize Select2 only if not already initialized and elements exist
                

                $('#flat_rate_id').each(function() {
                    var $this = $(this);
                    if (!$this.hasClass('select2-hidden-accessible') && $this.length > 0) {
                        try {
                            $this.select2();
                        } catch (e) {
                            console.warn('Select2 initialization failed for flat_rate_id:', e);
                        }
                    }
                });
                $('#workshop_ids').each(function() {
                    var $this = $(this);
                    if (!$this.hasClass('select2-hidden-accessible') && $this.length > 0) {
                        try {
                            $this.select2();
                        } catch (e) {
                            console.warn('Select2 initialization failed for workshop_ids:', e);
                        }
                    }
                });
                $('#product_locations').each(function() {
                    var $this = $(this);
                    if (!$this.hasClass('select2-hidden-accessible') && $this.length > 0) {
                        try {
                            $this.select2();
                        } catch (e) {
                            console.warn('Select2 initialization failed for product_locations:', e);
                        }
                    }
                });

                // Prefetch options based on currently selected locations
                var initSelectedLocations = $('#product_locations').val() || [];
                if (initSelectedLocations.length) {
                    refreshOptionsByLocations(initSelectedLocations);
                }
            }, 100);

            // Price type change handler
            $('#price_type').off('change.servicePriceType').on('change.servicePriceType', function() {
                var priceType = $(this).val();
                if (priceType === 'per_hour') {
                    $('#flat_rate_group').show();
                    $('#service_price').prop('readonly', true);
                    $('#service_hours_group').show();
                } else {
                    $('#flat_rate_group').hide();
                    $('#service_price').prop('readonly', false);
                    $('#service_hours_group').hide();
                }
            });

            // Flat rate change handler - auto calculate price
            $('#flat_rate_id').off('change.serviceFlatRate').on('change.serviceFlatRate', function() {
                var flatRateId = $(this).val();
                var serviceHours = parseFloat($('#service_hours').val()) || 1;
                
                if (flatRateId) {
                    $.get("{{route('services.flat-rate-details', ':id')}}".replace(':id', flatRateId), function(data) {
                        var pricePerHour = parseFloat(data.price_per_hour) || 0;
                        
                        if (pricePerHour > 0) {
                            var calculatedPrice = (pricePerHour * serviceHours);
                            $('#service_price').val(calculatedPrice.toFixed(2));
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('Failed to load flat rate details:', error);
                        toastr.error('Failed to load flat rate details. Please try again.');
                    });
                } else {
                    $('#service_price').val('');
                }
            });

            // Service hours change handler - recalculate price if per hour
            $('#service_hours').off('change.serviceHours').on('change.serviceHours', function() {
                if ($('#price_type').val() === 'per_hour' && $('#flat_rate_id').val()) {
                    $('#flat_rate_id').trigger('change.serviceFlatRate');
                }
            });

            // Refresh workshops & flat rates when locations change
            $('#product_locations').off('change.optionsByLocations').on('change.optionsByLocations', function() {
                var selected = $(this).val() || [];
                refreshOptionsByLocations(selected);
            });

            // Trigger initial price type to set visibility
            $('#price_type').trigger('change.servicePriceType');
        }
    });
</script>
@endsection