@extends('layouts.app')
@section('title', __('messages.settings'))
@section('content')
@include('repair::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fas fa-tools"></i>
        @lang('messages.settings')
    </h1>
</section>

<!-- Main content -->
<!-- Main content -->
<section class="content">
    <div class="row">
        @php
            $cat_code_enabled = isset($module_category_data['enable_taxonomy_code']) && !$module_category_data['enable_taxonomy_code'] ? false : true;
        @endphp
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#repair_status_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fa fas fa-check-circle"></i>
                            @lang('sale.status')
                            @show_tooltip(__('repair::lang.all_js_status_tooltip'))
                        </a>
                    </li>
                    <li>
                        <a href="#repair_workshop_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fa fas fa-warehouse"></i> <!-- Fixed the icon here to use a warehouse icon for workshops -->
                            @lang('repair::lang.workshops')
                            @show_tooltip(__('repair::lang.workshops_tooltip'))
                        </a>
                    </li>

                    <li>
                        <a href="#repair_flat_rate_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fas fa-tachometer-alt"></i>
                            @lang('repair::lang.flat_rate')
                        </a>
                    </li>
                    <li>
                        <a href="#repair_device_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fas fa fa-desktop"></i>
                            @lang('repair::lang.vehicle_brand')
                            @show_tooltip(__('repair::lang.device_tooltip'))
                        </a>
                    </li>
                    <li>
                        <a href="#repair_device_models_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fas fa fa-bolt"></i>
                            @lang('repair::lang.vehicle_model')
                            @show_tooltip(__('repair::lang.device_models_tooltip'))
                        </a>
                    </li>
                    <li>
                        <a href="#repair_settings_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fa fas fa-cogs"></i>
                            @lang('repair::lang.repair_settings')
                        </a>
                    </li>
                    <li>
                        <a href="#jobsheet_settings_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fa fas fa-clipboard"></i>
                            @lang('repair::lang.jobsheet_pdf_settings')
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="repair_status_tab">
                        @includeIf('repair::status.index')
                    </div>

                    <!-- Workshop Tab Content -->
                    <div class="tab-pane" id="repair_workshop_tab">
                        @includeIf('repair::workshop.index') <!-- This includes the workshop content -->
                    </div>



                    <!-- Flat Rate Tab Content -->
                    <div class="tab-pane" id="repair_flat_rate_tab">
                        @includeIf('repair::flat_rate.index') <!-- This includes the flat rate content -->
                    </div>

                    <!-- Device (Taxonomy)-->
                    <input type="hidden" name="category_type" id="category_type" value="device">
                    <div class="tab-pane taxonomy_body" id="repair_device_tab">
                    </div>
                    <!-- /Device (Taxonomy)-->

                    <div class="tab-pane" id="repair_device_models_tab">
                        <!-- Device model form and other content here -->
                        @includeIf('repair::device_model.index')
                    </div>

                    <div class="tab-pane" id="repair_settings_tab">
                        @includeIf('repair::settings.partials.repair_settings_tab')
                    </div>
                    <div class="tab-pane" id="jobsheet_settings_tab">
                        @includeIf('repair::settings.partials.jobsheet_settings_tab')
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@stop


@section('javascript')
<script type="text/javascript">
    $(document).ready( function(){

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('href');
            if ( target == '#repair_settings_tab') {
                //Repair Settings Tab Code
                $('#search_product').autocomplete({
                    source: function(request, response) {
                        $.ajax({
                            url: '/purchases/get_products?check_enable_stock=false',
                            dataType: 'json',
                            data: {
                                term: request.term,
                            },
                            success: function(data) {
                                response(
                                    $.map(data, function(v, i) {
                                        if (v.variation_id) {
                                            return { label: v.text, value: v.variation_id };
                                        }
                                        return false;
                                    })
                                );
                            },
                        });
                    },
                    minLength: 2,
                    select: function(event, ui) {
                        $('#default_product')
                            .val(ui.item.value);
                        event.preventDefault();
                        $('#selected_default_product').text(ui.item.label);
                        $(this).val(ui.item.label);
                    },
                    focus: function(event, ui) {
                        event.preventDefault();
                        $(this).val(ui.item.label);
                    },
                });

                var data = [{
                  id: "",
                  text: '@lang("messages.please_select")',
                  html: '@lang("messages.please_select")',
                },
                @foreach($repair_statuses as $repair_status)
                    {
                    id: {{$repair_status->id}},
                    @if(!empty($repair_status->color))
                        text: '<i class="fa fa-circle" aria-hidden="true" style="color: {{$repair_status->color}};"></i> {{$repair_status->name}}',
                        title: '{{$repair_status->name}}'
                    @else
                        text: "{{$repair_status->name}}"
                    @endif
                    },
                @endforeach
                ];

                $("select#repair_status_id").select2({
                  data: data,
                  escapeMarkup: function(markup) {
                    return markup;
                  }
                });

                @if(!empty($repair_settings['default_status']))
                    $("select#repair_status_id").val({{$repair_settings['default_status']}}).change();
                @endif

                if ($('#repair_tc_condition').length) {
                    tinymce.init({
                        selector: 'textarea#repair_tc_condition',
                    });
                }
            }
        });
        //Repair Status Tab Code
        var status_table = $('#status_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                ajax: "{{action([\Modules\Repair\Http\Controllers\RepairStatusController::class, 'index'])}}",
                aaSorting: [[2, 'desc']],
                columnDefs: [ {
                    "targets": 3,
                    "orderable": false,
                    "searchable": false
                } ]
            });

            function formatIcon(icon) {
            if (!icon.id) return icon.text; // Show text if no icon is selected
            return $('<span><i class="fa ' + icon.id + '"></i> ' + icon.text + '</span>');
        }

        $("#icon-selector").select2({
            templateResult: formatIcon, // Show icons in dropdown
            templateSelection: formatIcon, // Show selected icon
            allowClear: true, // Allow clearing selection
            placeholder: "Select an icon",
            width: '100%'
        });

        //
        // DataTable Initialization
        var workshop_table = $('#workshop_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            ajax: "{{ action([\Modules\Repair\Http\Controllers\WorkshopController::class, 'index']) }}",
            order: [[2, 'desc']], // Default sorting on status column
            columns: [
                { data: 'name' },
                { data: 'location' },
                { data: 'status' },
                { data: 'action', orderable: false }  // Expect buttons for Edit / Delete
            ]
        });

        // Utility function to load business locations into a given select element
        function loadBusinessLocations(selectElement, selectedId = null) {
            $.ajax({
                url: '{{ route('business-locations') }}',
                method: 'GET',
                success: function (locations) {
                    selectElement.empty();
                    locations.forEach(function (location) {
                        var selected = (location.id == selectedId) ? 'selected' : '';
                        selectElement.append('<option value="' + location.id + '" ' + selected + '>' + location.name + '</option>');
                    });
                },
                error: function () {
                    alert('Failed to load business locations');
                }
            });
        }

        // --- Create Modal ---

        // Open Create Modal when button is clicked
        $('#openCreateModal').on('click', function () {
            // Clear form and errors
            $('#createWorkshopForm')[0].reset();
            $('#createErrorMessages').hide().empty();
            $('#createModalLabel').text('{{ __('Create workshop') }}');
            // Load business locations into the create select dropdown
            loadBusinessLocations($('#business_location_id_create'));
            // Show modal
            $('#createModal').modal('show');
        });

        // Save new workshop (Create)
        $('#saveWorkshopCreate').on('click', function () {
            var formData = $('#createWorkshopForm').serialize();
            // Clear previous error messages
            $('#createErrorMessages').hide().html('');

            $.ajax({
                url: '{{ route('workshops.store') }}',
                type: 'POST',
                data: formData,
                success: function (response) {
                    $('#createModal').modal('hide');
                    workshop_table.ajax.reload();  // reload DataTable
                    toastr.success(response.message);
                },
                error: function (xhr) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessages = '';
                    for (var key in errors) {
                        if (errors.hasOwnProperty(key)) {
                            errorMessages += '<p>' + errors[key][0] + '</p>';
                        }
                    }
                    $('#createErrorMessages').show().html(errorMessages);
                }
            });
        });

        // --- Edit Modal ---

        // Open Edit Modal when an element with class "edit_workshop" is clicked.
        // In your DataTable action column, make sure you include a button with this class and a data-href attribute
        $(document).on('click', '.edit_workshop', function () {
            var url = $(this).data('href'); // URL to fetch the workshop data
            $.get(url, function (data) {
                // Fill in the form fields with the returned data
                $('#workshop_id_edit').val(data.id);
                $('#name_edit').val(data.name);
                $('#status_edit').val(data.status);
                // Load the locations and set the selected value
                loadBusinessLocations($('#business_location_id_edit'), data.location_id);
                // Show the edit modal
                $('#editModal').modal('show');
            });
        });

        // Save the edited workshop
        $('#saveWorkshopEdit').on('click', function () {
            var formData = $('#editWorkshopForm').serialize();
            var id = $('#workshop_id_edit').val();
            // Clear previous error messages
            $('#editErrorMessages').hide().html('');

            $.ajax({
                url: '{{ route('workshops.update', '') }}/' + id,
                type: 'PUT',
                data: formData,
                success: function (response) {
                    $('#editModal').modal('hide');
                    workshop_table.ajax.reload();
                    toastr.success(response.message);
                },
                error: function (xhr) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessages = '';
                    for (var key in errors) {
                        if (errors.hasOwnProperty(key)) {
                            errorMessages += '<p>' + errors[key][0] + '</p>';
                        }
                    }
                    $('#editErrorMessages').show().html(errorMessages);
                }
            });
        });

        // --- Delete Workshop ---
        // In your DataTable action column, include a delete button with an id or class (here we use id "delete_a_workshop")
        $(document).on('click', '#delete_a_workshop', function () {
            var url = $(this).data('href');
            if (confirm("Are you sure you want to delete this workshop?")) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        workshop_table.ajax.reload();
                        alert('Workshop deleted successfully');
                    },
                    error: function () {
                        alert('There was an error deleting the workshop');
                    }
                });
            }
        });




        $(document).on('submit', 'form#status_form', function(e){
            e.preventDefault();
            $(this).find('button[type="submit"]').attr('disabled', true);
            var data = $(this).serialize();

            $.ajax({
                method: $(this).attr('method'),
                url: $(this).attr("action"),
                dataType: "json",
                data: data,
                success: function(result){
                    if(result.success == true){
                        $('div.view_modal').modal('hide');
                        toastr.success(result.msg);
                        status_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });
        $(document).on('shown.bs.modal', '.view_modal', function() {
            $('input#color').colorpicker({format: 'hex'});
        })
        //Repair Device Model Code
        model_datatable = $("#model_table").DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader:false,
                    ajax: {
                        url: "/repair/device-models",
                        data:function(d) {
                            d.brand_id = $("#brand_id").val();
                            d.device_id = $("#device_id").val();
                        }
                    },
                    columnDefs: [
                        {
                            targets: [0, 2],
                            orderable: false,
                            searchable: false,
                        },
                    ],
                    aaSorting: [[1, 'desc']],
                    columns: [
                        { data: 'action', name: 'action' },
                        { data: 'name', name: 'name' },
                        { data: 'vin_model_code', name: 'vin_model_code' },
                        { data: 'device_id', name: 'device_id' },
                        { data: 'brand_id', name: 'brand_id' },
                    ]
            });

        $(document).on('change', "#brand_id, #device_id", function(){
            model_datatable.ajax.reload();
        });

        $(document).on('click', '#add_device_model', function () {
            var url = $(this).data('href');
            $.ajax({
                method: 'GET',
                url: url,
                dataType: 'html',
                success: function(result) {
                    $('#device_model_modal').html(result).modal('show');
                }
            });
        });

        $(document).on('click', '.edit_device_model', function () {
            var url = $(this).data('href');
            $.ajax({
                method: 'GET',
                url: url,
                dataType: 'html',
                success: function(result) {
                    $('#device_model_modal').html(result).modal('show');
                }
            });
        });

        $('#device_model_modal').on('show.bs.modal', function (event) {
            $('form#device_model').validate();
            $("form#device_model .select2").select2();
        });

        $(document).on('submit', 'form#device_model', function(e){
            e.preventDefault();
            var url = $('form#device_model').attr('action');
            var method = $('form#device_model').attr('method');
            var data = $('form#device_model').serialize();
            $.ajax({
                method: method,
                dataType: "json",
                url: url,
                data:data,
                success: function(result){
                    if (result.success) {
                        $('#device_model_modal').modal("hide");
                        toastr.success(result.msg);
                        model_datatable.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        $(document).on('click', '#delete_a_model', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((confirmed) => {
                if (confirmed) {
                    $.ajax({
                        method: 'DELETE',
                        url: url,
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                model_datatable.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        // --- Flat Rate Services (moved from flat_rate/index) ---
        // Initialize DataTable if the table exists on the page
      
            var flat_rate_table = $('#flat_rate_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("repair.flat_rate") }}',
                    data: function(d) {
                        d.location_id = $('#flat_rate_location_filter').val();
                    }
                },
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'price_per_hour', name: 'price_per_hour' },
                    { data: 'location', name: 'location', orderable: false, searchable: false },
                    { data: 'is_active', name: 'is_active', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });
            // Load locations into a select element
            function loadLocations(selectEl, selectedId = null) {
                $.ajax({
                    url: "{{ route('business-locations') }}",
                    method: 'GET',
                    success: function (locations) {
                        selectEl.empty();
                        // add 'all' option for filter select
                        if (selectEl.attr('id') == 'flat_rate_location_filter') {
                            selectEl.append('<option value="">{{ __('messages.all') }}</option>');
                        }
                        var current = selectEl.data('current-location');
                        var locArray = Array.isArray(locations) ? locations : Object.values(locations);
                        locArray.forEach(function (loc) {
                            var sel = '';
                            if (selectedId && selectedId == loc.id) {
                                sel = ' selected';
                            } else if (!selectedId && current && current == loc.id) {
                                sel = ' selected';
                            }
                            selectEl.append('<option value="' + loc.id + '"' + sel + '>' + loc.name + '</option>');
                        });
                        // if this is the filter, trigger change to set default
                        if (selectEl.attr('id') == 'flat_rate_location_filter') {
                            // if there's a current location, filter by it by default
                            if (selectEl.val()) {
                                flat_rate_table.ajax.reload();
                            }
                        }
                    }
                });
            }

            // Load the filter locations on page load (if the filter exists)
            if ($('#flat_rate_location_filter').length) {
                loadLocations($('#flat_rate_location_filter'));
            }

            // When the filter changes, reload the table
            $(document).on('change', '#flat_rate_location_filter', function () {
                flat_rate_table.ajax.reload();
            });

            // Open Create Modal for flat rate
            $('#openFlatRateModal').on('click', function () {
                $('#createFlatRateForm')[0].reset();
                $('#flatRateErrorMessages').hide().empty();
                $('#flatRateModalLabel').text('{{ __('Add Flat Rate Service') }}');
                // Clear any edit mode
                $('#saveFlatRate').removeData('update');
                // Load locations with current location as default
                var currentLocation = $('#flat_rate_location_filter').data('current-location');
                loadLocations($('#business_location_id_create'), currentLocation);
                $('#flatRateModal').modal('show');
            });

            // Save or Update Flat Rate Service
            $('#saveFlatRate').on('click', function () {
                // gather form values
                var business_location_id = $('#business_location_id_create').val();
                var is_active = $('#is_active_create').is(':checked') ? 1 : 0;
                var formData = $('#createFlatRateForm').serialize();
                $('#flatRateErrorMessages').hide().html('');

                // If user trying to create an active flat rate, ensure uniqueness per location client-side
                function doSave() {
                    var updateUrl = $('#saveFlatRate').data('update');
                    var isEdit = !!updateUrl;
                    $.ajax({
                        url: isEdit ? updateUrl : '{{ route("repair.flat_rate.store") }}',
                        type: isEdit ? 'PUT' : 'POST',
                        data: formData,
                        success: function (response) {
                            $('#flatRateModal').modal('hide');
                            flat_rate_table.ajax.reload();
                            toastr.success(response.message || '{{ __("lang_v1.updated_success") }}');
                            // clear edit mode
                            $('#saveFlatRate').removeData('update');
                        },
                        error: function (xhr) {
                            var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;
                            var errorMessages = '';
                            if (errors) {
                                for (var key in errors) {
                                    if (errors.hasOwnProperty(key)) {
                                        errorMessages += '<p>' + errors[key][0] + '</p>';
                                    }
                                }
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessages = '<p>' + xhr.responseJSON.message + '</p>';
                            } else {
                                errorMessages = '<p>{{ __('messages.something_went_wrong') }}</p>';
                            }
                            $('#flatRateErrorMessages').show().html(errorMessages);
                        }
                    });
                }

                doSave();
            });

            // Edit Flat Rate: open modal prefilled
            $(document).on('click', '.edit-flat-rate', function () {
                var showUrl = $(this).data('show');
                var updateUrl = $(this).data('update');
                $('#flatRateErrorMessages').hide().empty();
                
                $.get(showUrl, function (data) {
                    console.log('Edit data received:', data); // Debug log
                    $('#flatRateModalLabel').text('{{ __('Edit Flat Rate Service') }}');
                    $('#name_create').val(data.name);
                    $('#hours_create').val(data.price_per_hour);
                    
                    // Load locations and then set the selected value
                    loadLocations($('#business_location_id_create'), data.business_location_id);
                    $('#is_active_create').prop('checked', !!data.is_active);
                    $('#saveFlatRate').data('update', updateUrl);
                    $('#flatRateModal').modal('show');
                }).fail(function(xhr, status, error) {
                    console.error('Error loading flat rate data:', error);
                    alert('Error loading flat rate data. Please try again.');
                });
            });

            // Delete Flat Rate
            $(document).on('click', '.delete-flat-rate', function () {
                var deleteUrl = $(this).data('delete');
                if (confirm('{{ __('messages.are_you_sure') }}')) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function (response) {
                            flat_rate_table.ajax.reload();
                            toastr.success(response.message || '{{ __("lang_v1.deleted_success") }}');
                        },
                        error: function () {
                            toastr.error('{{ __('messages.something_went_wrong') }}');
                        }
                    });
                }
            });
   

    });
</script>
@includeIf('taxonomy.taxonomies_js')
@endsection
