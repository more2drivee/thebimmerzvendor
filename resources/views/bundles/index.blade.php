@extends('layouts.app')
@section('title', __('bundles.title'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('bundles.title')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">@lang('bundles.subtitle')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('bundles.list_title')])
    @slot('tool')
    <div class="box-tools">
        <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-green-600 tw-to-emerald-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full"
            id="btn_quick_sell_bundle" style="margin-right: 10px;">
            <i class="fa fa-shopping-cart"></i> @lang('bundles.quick_sell_action')
        </button>
        <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full"
            id="btn_create_bundle">
            + @lang('messages.add')
        </button>
    </div>
    @endslot

    <div class="row" style="margin-bottom:10px">
        <div class="col-md-3">
            <label>@lang('bundles.filters.device')</label>
            <select id="filter_device_id" class="form-control select2" style="width:100%">
                <option value="">@lang('messages.all')</option>
            </select>
        </div>
        <div class="col-md-3">
            <label>@lang('bundles.filters.model')</label>
            <select id="filter_repair_device_model_id" class="form-control select2" style="width:100%">
                <option value="">@lang('messages.all')</option>
            </select>
        </div>
        <div class="col-md-2">
            <label>@lang('bundles.filters.side_type')</label>
            <select id="filter_side_type" class="form-control" style="width:100%">
                <option value="">@lang('messages.all')</option>
                <option value="front_half">@lang('bundles.side_type.front_half')</option>
                <option value="rear_half">@lang('bundles.side_type.rear_half')</option>
                <option value="left_quarter">@lang('bundles.side_type.left_quarter')</option>
                <option value="right_quarter">@lang('bundles.side_type.right_quarter')</option>
                <option value="full_body">@lang('bundles.side_type.full_body')</option>
                <option value="other">@lang('bundles.side_type.other')</option>
            </select>
        </div>
        <div class="col-md-2">
            @if (!$isAdmin && count($locations) > 1)
                <label>@lang('bundles.filters.location')</label>
                <select id="filter_location_id" class="form-control select2" style="width:100%">
                    <option value="">@lang('messages.all')</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="bundles_table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>@lang('bundles.fields.reference_no')</th>
                    <th>@lang('bundles.fields.device')</th>
                    <th>@lang('bundles.fields.model')</th>
                    <th>@lang('bundles.fields.manufacturing_year')</th>
                    <th>@lang('bundles.fields.side_type')</th>
                    <th>@lang('bundles.fields.price')</th>
                    <th>@lang('bundles.fields.has_parts_left')</th>
                    <th>@lang('bundles.fields.location')</th>
                    <th>@lang('bundles.fields.description')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

    <div class="modal fade" id="bundle_modal" tabindex="-1" role="dialog" aria-labelledby="bundleModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="bundleModalLabel">@lang('bundles.modal_title')</h4>
                </div>
                <div class="modal-body">
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

        // Load locations into filter using business-location index page (simple approach)
        $.ajax({
            url: '/business-location',
            method: 'GET',
            success: function(html) {
                var temp = $('<div>').html(html);
                var locSelect = $('#filter_location_id');
                temp.find('table tbody tr').each(function() {
                    var idCell = $(this).find('td').eq(0).text().trim();
                    var nameCell = $(this).find('td').eq(1).text().trim();
                    if (idCell && nameCell && !isNaN(idCell)) {
                        locSelect.append('<option value="' + idCell + '">' + nameCell + '</option>');
                    }
                });
                if ($.fn.select2) {
                    $('#filter_location_id').select2();
                }
            }
        });

        var bundlesTable = $('#bundles_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("bundles.datatable") }}',
                data: function(d) {
                    d.device_id = $('#filter_device_id').val();
                    d.repair_device_model_id = $('#filter_repair_device_model_id').val();
                    d.side_type = $('#filter_side_type').val();
                    d.location_id = $('#filter_location_id').val();
                }
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: 'reference_no',
                    name: 'b.reference_no'
                },
                {
                    data: 'device_name',
                    name: 'c.name'
                },
                {
                    data: 'repair_device_model_name',
                    name: 'rdm.name'
                },
                {
                    data: 'manufacturing_year',
                    name: 'b.manufacturing_year'
                },
                {
                    data: 'side_type',
                    name: 'b.side_type'
                },
                {
                    data: 'price',
                    name: 'b.price'
                },
                {
                    data: 'has_parts_left',
                    name: 'b.has_parts_left',
                    render: function(val) {
                        if (val) {
                            return '<span class="label label-success">{{ __('bundles.yes') }}</span>';
                        }
                        return '<span class="label label-default">{{ __('bundles.no') }}</span>';
                    }
                },
                {
                    data: 'location_name',
                    name: 'bl.name'
                },
                {
                    data: 'description',
                    name: 'b.description'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false,
                    render: function(val, type, row) {
                        var editId = val.edit_id || '';
                        var deleteId = val.delete_id || '';
                        var overviewUrl = val.overview_url || '#';

                        return '' +
                            '<div class="btn-group">' +
                                '<button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                                    '{{ __('messages.action') }} <span class="caret"></span>' +
                                '</button>' +
                                '<ul class="dropdown-menu dropdown-menu-right" role="menu">' +
                                    '<li><a href="#" class="edit-bundle" data-id="' + editId + '">{{ __('messages.edit') }}</a></li>' +
                                    '<li><a href="#" class="delete-bundle" data-id="' + deleteId + '">{{ __('messages.delete') }}</a></li>' +
                                    '<li><a href="' + overviewUrl + '">{{ __('bundles.overview.title') }}</a></li>' +
                                '</ul>' +
                            '</div>';
                    }
                }
            ],
            order: [
                [1, 'desc']
            ]
        });

        $('#filter_device_id, #filter_repair_device_model_id, #filter_side_type, #filter_location_id').on('change', function() {
            bundlesTable.ajax.reload();
        });

        $(document).on('click', '#btn_quick_sell_bundle', function(e) {
            e.preventDefault();
            window.location.href = '{{ route("bundles.quick_sell.form") }}';
        });

        $(document).on('click', '#btn_create_bundle', function(e) {
            e.preventDefault();
            loadBundleForm('{{ route("bundles.create") }}');
        });

        $(document).on('click', '.edit-bundle', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            if (id) {
                var url = '{{ route("bundles.edit", ["id" => "__id__"]) }}'.replace('__id__', id);
                loadBundleForm(url);
            }
        });

        $(document).on('submit', '#bundle_form', function(e) {
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
                        $('#bundle_modal').modal('hide');
                        toastr.success(response.msg);
                        bundlesTable.ajax.reload();
                    } else {
                        toastr.error(response.msg || '{{ __('messages.something_went_wrong') }}');
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = '';
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            errors += value + '<br>';
                        });
                        toastr.error(errors);
                    } else {
                        toastr.error('{{ __('messages.something_went_wrong') }}');
                    }
                }
            });
        });

        $(document).on('click', '.delete-bundle', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            if (!id) return;

            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function(willDelete) {
                if (willDelete) {
                    var url = '{{ route("bundles.destroy", ["id" => "__id__"]) }}'.replace('__id__', id);
                    $.ajax({
                        method: 'DELETE',
                        url: url,
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                bundlesTable.ajax.reload();
                            } else {
                                toastr.error(result.msg || '{{ __('messages.something_went_wrong') }}');
                            }
                        },
                        error: function() {
                            toastr.error('{{ __('messages.something_went_wrong') }}');
                        }
                    });
                }
            });
        });

        function loadBundleForm(url) {
            $.ajax({
                url: url,
                method: 'GET',
                beforeSend: function() {
                    $('#bundle_modal .modal-body').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
                    $('#bundle_modal').modal('show');
                },
                success: function(response) {
                    if (response.success) {
                        $('#bundle_modal .modal-body').html(response.html);
                        $('#bundle_modal').modal('show');
                        if ($.fn.select2) {
                            $('#bundle_modal .select2').select2({
                                dropdownParent: $('#bundle_modal')
                            });
                            $('#bundle_modal .select2-brand').select2({
                                ajax: {
                                    url: '{{ route("bundles.ajax.devices") }}',
                                    dataType: 'json',
                                    delay: 250,
                                    data: function(params) {
                                        return {
                                            q: params.term,
                                            page: params.page
                                        };
                                    },
                                    processResults: function(data) {
                                        return {
                                            results: data.results,
                                            pagination: data.pagination
                                        };
                                    }
                                },
                                placeholder: '@lang("messages.please_select")',
                                allowClear: true,
                                minimumInputLength: 1,
                                dropdownParent: $('#bundle_modal'),
                                width: '100%'
                            });
                        }

                        var $brandSelect = $('#bundle_modal #device_id');
                        var $modelSelect = $('#bundle_modal #repair_device_model_id');

                        $brandSelect.off('change.bundleModels').on('change.bundleModels', function() {
                            var brandId = $(this).val();

                            $modelSelect.val(null).trigger('change');
                            $modelSelect.empty().append('<option value="">@lang("messages.please_select")</option>');

                            if (!brandId) {
                                return;
                            }

                            var urlModels = '{{ route("booking.get_models_by_brand", ["brandId" => "__id__"]) }}'.replace('__id__', brandId);

                            $.ajax({
                                url: urlModels,
                                type: 'GET',
                                dataType: 'json',
                                success: function(models) {
                                    $.each(models, function(index, model) {
                                        $modelSelect.append(
                                            $('<option>', {
                                                value: model.id,
                                                text: model.name
                                            })
                                        );
                                    });
                                    $modelSelect.trigger('change');
                                }
                            });
                        });
                    } else {
                        toastr.error(response.msg || '{{ __('messages.something_went_wrong') }}');
                    }
                },
                error: function() {
                    $('#bundle_modal .modal-body').html('<div class="alert alert-danger">{{ __('messages.something_went_wrong') }}</div>');
                }
            });
        }

        $('#bundle_modal').on('hidden.bs.modal', function() {
            $(this).find('.modal-body').html('');
        });
    });
</script>
@endsection