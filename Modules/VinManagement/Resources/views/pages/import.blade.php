@extends('layouts.app')
@section('title', __('vin.import_title'))
@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('vin.import_title')</h1>
    <p class="help-block">@lang('vin.import_help')</p>
    <a href="{{ route('vin.dashboard') }}" class="btn btn-default"><i class="fas fa-arrow-left"></i> @lang('vin.back_to_dashboard')</a>
    <hr>
</section>
<section class="content">


    <div class="row tw-mt-6">
        <div class="col-md-12">
            <div class="tw-mb-3 tw-flex tw-gap-2 tw-flex-wrap tw-items-center tw-justify-between">
                <div class="tw-flex tw-gap-2">
                    <a href="{{ route('vin.template') }}" class="btn btn-info text-white">
                        <i class="fas fa-download"></i> @lang('vin.download_template')
                    </a>
                    <a href="{{ route('vin.export') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-file-export"></i> @lang('vin.export_all_csv')
                    </a>
                </div>
                <button id="btn-add-vin-manual" type="button" class="btn btn-primary">
                    <i class="fas fa-plus"></i> @lang('vin.add_vin_manually')
                </button>
            </div>

            <div class="tw-mb-4">
                <div id="drop-zone" class="tw-border-2 tw-border-dashed tw-border-gray-300 tw-rounded-lg tw-p-10 tw-text-center tw-cursor-pointer tw-bg-white hover:tw-border-blue-500 hover:tw-bg-blue-50 tw-transition-all tw-duration-200">
                    <input type="file" id="file-upload" class="tw-hidden" accept=".xlsx,.xls,.csv" />
                    <div class="tw-text-gray-500 tw-pointer-events-none">
                        <i class="fas fa-cloud-upload-alt tw-text-5xl tw-mb-3 tw-text-blue-400"></i>
                        <p class="tw-text-xl tw-font-semibold tw-text-gray-700">Drag & Drop Excel File Here</p>
                        <p class="tw-text-sm tw-text-gray-500">or click to select file</p>
                        <p class="tw-text-xs tw-mt-3 tw-text-gray-400">Supported formats: .xlsx, .xls, .csv</p>
                    </div>
                </div>
                <div id="upload-progress" class="progress tw-mt-3 tw-h-2 tw-rounded-full tw-hidden">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="upload-status" class="tw-mt-2 tw-text-center tw-hidden"></div>
            </div>

            <div id="upload-row-errors" style="display:none" class="tw-mb-3">
                <div class="alert alert-danger">
                    <strong>@lang('vin.row_errors_title')</strong>
                    <ul id="upload-row-errors-list" class="tw-list-disc tw-ml-5"></ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row tw-mt-8">
        <div class="col-md-12">
            <h3 class="tw-text-lg tw-font-semibold tw-mb-2">@lang('vin.all_vins')</h3>
            <div class="tw-mb-3 tw-flex tw-gap-2 tw-flex-wrap">
                <input id="flt-manufacturer" class="form-control" style="max-width:220px" placeholder="@lang('vin.filter_manufacturer')" list="manufacturer-list" />
                <datalist id="manufacturer-list"></datalist>
                <select id="flt-car-type" class="form-control" style="max-width:200px">
                    <option value="">@lang('vin.filter_car_type')</option>
                    <option>Sedan</option>
                    <option>SUV</option>
                    <option>Truck</option>
                    <option>Hatchback</option>
                    <option>Coupe</option>
                    <option>Convertible</option>
                    <option>Van</option>
                    <option>Other</option>
                </select>
                <select id="flt-transmission" class="form-control" style="max-width:200px">
                    <option value="">@lang('vin.filter_transmission')</option>
                    <option>Automatic</option>
                    <option>Manual</option>
                    <option>CVT</option>
                    <option>Dual-Clutch</option>
                    <option>Other</option>
                </select>
                <input id="flt-year" class="form-control" style="max-width:120px" placeholder="@lang('vin.filter_year')" type="number" min="1900" max="{{ date('Y') + 1 }}" />
                <button id="flt-reset" class="btn btn-default">@lang('vin.filter_reset')</button>
            </div>
            <div class="table-responsive">
                <table id="vin-list-table" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>@lang('vin.th_vin_number')</th>
                            <th>@lang('vin.th_car_brand')</th>
                            <th>@lang('vin.th_car_model')</th>
                            <th>@lang('vin.th_color')</th>
                            <th>@lang('vin.th_year')</th>
                            <th>@lang('vin.th_manufacturer')</th>
                            <th>@lang('vin.th_car_type')</th>
                            <th>@lang('vin.th_transmission')</th>
                            <th>@lang('restaurant.action')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Manual VIN entry modal --}}
    <div class="modal fade" id="manual-vin-modal" tabindex="-1" role="dialog" aria-labelledby="manualVinModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="manualVinModalLabel">@lang('vin.modal_title')</h4>
                </div>
                <form id="manual-vin-form">
                    <div class="modal-body">
                        @csrf
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="vin_number">@lang('vin.field_vin_number')*</label>
                                    <input type="text" name="vin_number" id="vin_number" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="car_brand">@lang('vin.field_car_brand')*</label>
                                    <select name="car_brand" id="car_brand" class="form-control" required>
                                        <option value="">@lang('vin.select_placeholder')</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="car_model">@lang('vin.field_car_model')*</label>
                                    <select name="car_model" id="car_model" class="form-control" required>
                                        <option value="">@lang('vin.select_placeholder')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="color">@lang('vin.field_color')</label>
                                    <input type="text" name="color" id="color" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="year">@lang('vin.field_year')*</label>
                                    <input type="number" name="year" id="year" class="form-control" min="1900" max="{{ date('Y') + 1 }}" required>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="manufacturer">@lang('vin.field_manufacturer')*</label>
                                    <input type="text" name="manufacturer" id="manufacturer" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="car_type">@lang('vin.field_car_type')*</label>
                                    <select name="car_type" id="car_type" class="form-control" required>
                                        <option value="">@lang('vin.select_placeholder')</option>
                                        <option>Sedan</option>
                                        <option>SUV</option>
                                        <option>Truck</option>
                                        <option>Hatchback</option>
                                        <option>Coupe</option>
                                        <option>Convertible</option>
                                        <option>Van</option>
                                        <option>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="transmission">@lang('vin.field_transmission')*</label>
                                    <select name="transmission" id="transmission" class="form-control" required>
                                        <option value="">@lang('vin.select_placeholder')</option>
                                        <option>Automatic</option>
                                        <option>Manual</option>
                                        <option>CVT</option>
                                        <option>Dual-Clutch</option>
                                        <option>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('vin.btn_cancel')</button>
                        <button type="submit" class="btn btn-primary">@lang('vin.btn_save')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
    $(function() {


        var listTable = $('#vin-list-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '{{ route("vin.list") }}',
                data: function(d) {
                    d.manufacturer = $('#flt-manufacturer').val();
                    d.car_type = $('#flt-car-type').val();
                    d.transmission = $('#flt-transmission').val();
                    d.year = $('#flt-year').val();
                },
                error: function(xhr) {
                    toastr.error('Failed to load VINs: ' + (xhr.responseJSON?.message || xhr.statusText));
                }
            },
            columns: [{
                    data: 'vin_number'
                },
                {
                    data: 'car_brand_name',
                    name: 'car_brand_name'
                },
                {
                    data: 'car_model_name',
                    name: 'car_model_name'
                },
                {
                    data: 'color'
                },
                {
                    data: 'year'
                },
                {
                    data: 'manufacturer'
                },
                {
                    data: 'car_type'
                },
                {
                    data: 'transmission'
                },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return '<button class="btn btn-xs btn-danger btn-delete-vin" data-id="' + data + '"><i class="fa fa-trash"></i></button>';
                    }
                }
            ]
        });




        // Drag and Drop Logic
        var $dropZone = $('#drop-zone');
        var $fileInput = $('#file-upload');
        var $progress = $('#upload-progress');
        var $progressBar = $progress.find('.progress-bar');
        var $status = $('#upload-status');
        var $errorList = $('#upload-row-errors-list');
        var $errorContainer = $('#upload-row-errors');

        $dropZone.on('click', function() {
            $fileInput.click();
        });

        $dropZone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('tw-border-blue-500 tw-bg-blue-50');
        });

        $dropZone.on('dragleave dragend drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('tw-border-blue-500 tw-bg-blue-50');
        });

        $dropZone.on('drop', function(e) {
            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileUpload(files[0]);
            }
        });

        $fileInput.on('change', function() {
            if (this.files.length > 0) {
                handleFileUpload(this.files[0]);
            }
        });

        function handleFileUpload(file) {
            var formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');

            $progress.removeClass('tw-hidden');
            $progressBar.css('width', '0%');
            $status.removeClass('tw-hidden').html('<span class="tw-text-blue-600"><i class="fas fa-spinner fa-spin"></i> Uploading...</span>');
            $errorContainer.hide();
            $errorList.empty();

            $.ajax({
                url: '{{ route("vin.import.upload") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = (evt.loaded / evt.total) * 100;
                            $progressBar.css('width', percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(resp) {
                    $progressBar.css('width', '100%');
                    $status.html('<span class="tw-text-green-600"><i class="fas fa-check-circle"></i> Upload Complete! ' + (resp.inserted || 0) + ' records imported.</span>');

                    if (resp.row_errors && Object.keys(resp.row_errors).length > 0) {
                        $errorContainer.show();
                        Object.keys(resp.row_errors).forEach(function(row) {
                            var msgs = resp.row_errors[row];
                            msgs.forEach(function(m) {
                                $errorList.append('<li>Row ' + row + ': ' + m + '</li>');
                            });
                        });
                        $status.append('<br><span class="tw-text-orange-500">Some rows had errors. See below.</span>');
                    } else {
                        setTimeout(function() {
                            $progress.addClass('tw-hidden');
                            $status.addClass('tw-hidden');
                        }, 3000);
                    }

                    if (typeof listTable !== 'undefined') {
                        listTable.ajax.reload();
                    }
                },
                error: function(xhr) {
                    $progressBar.css('width', '0%');
                    var msg = 'Upload Failed';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg += ': ' + xhr.responseJSON.message;
                    }
                    $status.html('<span class="tw-text-red-600"><i class="fas fa-exclamation-circle"></i> ' + msg + '</span>');
                }
            });
        }

        // Open manual VIN modal and load brand dropdown
        $('#btn-add-vin-manual').on('click', function() {
            $('#manual-vin-modal').modal('show');

            // Load brands for dropdown
            $.get('{{ route("vin.brands") }}', function(items) {
                var $brand = $('#car_brand');
                $brand.empty().append('<option value="">@lang("vin.select_placeholder")</option>');
                (items || []).forEach(function(b) {
                    $brand.append('<option value="' + b.id + '">' + b.name + '</option>');
                });
            });

            // Reset models when reopening
            $('#car_model').empty().append('<option value="">@lang("vin.select_placeholder")</option>');
        });

        // Load models when brand changes
        $('#car_brand').on('change', function() {
            var brandId = $(this).val();
            var $model = $('#car_model');
            $model.empty().append('<option value="">@lang("vin.select_placeholder")</option>');

            if (!brandId) {
                return;
            }

            var url = '{{ route("vin.models_by_brand", ["brand" => ":id"]) }}';
            url = url.replace(':id', brandId);

            $.get(url, function(items) {
                (items || []).forEach(function(m) {
                    $model.append('<option value="' + m.id + '">' + m.name + '</option>');
                });
            });
        });

        // Submit manual VIN form via AJAX
        $('#manual-vin-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            $.ajax({
                url: '{{ route("vin.store_single") }}',
                type: 'POST',
                data: $form.serialize(),
                success: function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message || 'VIN added successfully');
                        $('#manual-vin-modal').modal('hide');
                        $form[0].reset();
                        listTable.ajax.reload();
                    } else {
                        toastr.error(resp.message || 'Failed to add VIN');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var msgs = [];
                        Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                            msgs = msgs.concat(xhr.responseJSON.errors[key]);
                        });
                        toastr.error(msgs.join('\n'));
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Failed to add VIN');
                    }
                }
            });
        });

        // Delete VIN
        $(document).on('click', '.btn-delete-vin', function() {
            var id = $(this).data('id');
            if (!confirm('Are you sure you want to delete this VIN?')) {
                return;
            }

            $.ajax({
                url: '{{ route("vin.destroy", ["vin" => "__ID__"]) }}'.replace('__ID__', id),
                type: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: '{{ csrf_token() }}'
                },
                success: function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message || 'VIN deleted successfully');
                        listTable.ajax.reload();
                    } else {
                        toastr.error(resp.message || 'Failed to delete VIN');
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to delete VIN');
                }
            });
        });

        // Filters
        function reloadList() {
            listTable.ajax.reload();
        }
        $('#flt-manufacturer, #flt-car-type, #flt-transmission, #flt-year').on('change keyup', reloadList);
        $('#flt-reset').on('click', function() {
            $('#flt-manufacturer').val('');
            $('#flt-car-type').val('');
            $('#flt-transmission').val('');
            $('#flt-year').val('');
            reloadList();
        });

        // Load manufacturer suggestions
        $.get('{{ route("vin.manufacturers.suggestions") }}', function(items) {
            var $dl = $('#manufacturer-list').empty();
            (items || []).forEach(function(m) {
                $dl.append('<option value="' + m + '"></option>');
            });
        });
    });
</script>
@endsection