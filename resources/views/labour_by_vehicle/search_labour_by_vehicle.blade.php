@extends('layouts.app')

@section('title', __('Labour by Vehicle Search'))

@section('content')

<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#labour-by-vehicle-search-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ route('labour-by-vehicle.index') }}"><i class="fas fa-car-side"></i> {{ __('Labour by Vehicle') }}</a>
            </div>

            <div class="collapse navbar-collapse" id="labour-by-vehicle-search-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    <li>
                        <a href="{{ route('labour-by-vehicle.index') }}">
                            @lang('All Labour by Vehicle')
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('labour-by-vehicle.labour-products') }}">
                            <i class="fa fa-cogs"></i> @lang('Labours')
                        </a>
                    </li>

                    @if (auth()->user()->can('product.create'))
                        <li @if (request()->segment(1) == 'labour-by-vehicle' && request()->segment(2) == 'import') class="active" @endif>
                            <a href="{{ action([\App\Http\Controllers\LabourByVehicleController::class, 'importLabourByVehicleForm']) }}">
                                @lang('Labour Import')
                            </a>
                        </li>
                    @endif

                    <li class="active">
                        <a href="{{ route('labour-by-vehicle.search.form') }}">
                            <i class="fa fa-search"></i> @lang('Search by Vehicle')
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</section>

<section class="content-header">
    <h1>@lang('Labour by Vehicle Search')
        <small>@lang('Find labour services by vehicle brand, model and year')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('Search Results')])
        <form id="vehicle_search_form" class="form-inline" style="margin-bottom: 15px;">
            <div class="row">
                <div class="col-md-4 col-sm-6" style="margin-bottom: 10px;">
                    <select id="search_brand_id" class="form-control" style="width: 100%;">
                        <option value="">{{ __('Please Select') }}</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom: 10px;">
                    <select id="search_model_id" class="form-control" style="width: 100%;" disabled>
                        <option value="">{{ __('Please Select') }}</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6" style="margin-bottom: 10px;">
                    <select id="search_year" class="form-control" style="width: 100%;" disabled>
                        <option value="">{{ __('Please Select') }}</option>
                    </select>
                </div>
                <div class="col-md-1 col-sm-6" style="margin-bottom: 10px;">
                    <button type="submit" id="vehicle_search_button" class="btn btn-primary btn-block">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
        </form>

        <div id="vehicle_search_loading" class="text-center" style="display:none; margin-bottom: 10px;">
            <i class="fa fa-spinner fa-spin"></i> {{ __('Searching...') }}
        </div>

        <div id="vehicle_search_results_container" style="display:none;">
            <div class="row" style="margin-bottom: 10px;">
                <div class="col-md-4 col-sm-6">
                    <input type="text" id="vehicle_results_search" class="form-control" placeholder="{{ __('Search in results') }}">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="vehicle_search_results_table">
                    <thead>
                        <tr>
                            <th>@lang('Product')</th>
                            <th>@lang('Price')</th>
                        </tr>
                    </thead>
                    <tbody id="vehicle_search_results_body">
                    </tbody>
                </table>
            </div>
            <div id="vehicle_search_empty_state" style="display:none; margin-top: 10px;">
                {{ __('No labour products found for the selected vehicle.') }}
            </div>
        </div>
    @endcomponent
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        var brandSelect = $('#search_brand_id');
        var modelSelect = $('#search_model_id');
        var yearSelect = $('#search_year');
        var searchForm = $('#vehicle_search_form');
        var searchButton = $('#vehicle_search_button');
        var loadingIndicator = $('#vehicle_search_loading');
        var resultsContainer = $('#vehicle_search_results_container');
        var resultsBody = $('#vehicle_search_results_body');
        var emptyState = $('#vehicle_search_empty_state');
        var resultsSearch = $('#vehicle_results_search');
        var placeholderText = "{{ __('Please Select') }}";

        brandSelect.select2({
            width: '100%',
            placeholder: placeholderText,
            allowClear: true
        });

        modelSelect.select2({
            width: '100%',
            placeholder: placeholderText,
            allowClear: true
        });

        yearSelect.select2({
            width: '100%',
            placeholder: placeholderText,
            allowClear: true
        });

        function resetModels() {
            modelSelect.prop('disabled', true);
            modelSelect.empty();
            modelSelect.append($('<option>', { value: '', text: placeholderText }));
            modelSelect.val(null).trigger('change');
        }

        function resetYears() {
            yearSelect.prop('disabled', true);
            yearSelect.empty();
            yearSelect.append($('<option>', { value: '', text: placeholderText }));
            yearSelect.val(null).trigger('change');
        }

        brandSelect.on('change', function () {
            var brandId = $(this).val();
            resetModels();
            resetYears();

            if (!brandId) {
                return;
            }

            modelSelect.prop('disabled', true);
            modelSelect.append($('<option>', { value: '', text: '{{ __('Loading...') }}' }));

            $.ajax({
                url: "{{ route('labour-by-vehicle.models-by-brand') }}",
                data: { brand_id: brandId },
                dataType: 'json',
                success: function (models) {
                    resetModels();
                    if (models && models.length) {
                        $.each(models, function (_, model) {
                            modelSelect.append($('<option>', { value: model.id, text: model.name }));
                        });
                        modelSelect.prop('disabled', false);
                    }
                },
                error: function () {
                    resetModels();
                    toastr.error('{{ __('Failed to load models.') }}');
                }
            });
        });

        modelSelect.on('change', function () {
            var modelId = $(this).val();
            resetYears();

            if (!modelId) {
                return;
            }

            var currentYear = new Date().getFullYear();
            yearSelect.prop('disabled', false);
            for (var y = 2000; y <= currentYear; y++) {
                yearSelect.append($('<option>', { value: y, text: y }));
            }
            yearSelect.trigger('change');
        });

        searchForm.on('submit', function (e) {
            e.preventDefault();

            var brandId = brandSelect.val();
            var modelId = modelSelect.val();
            var year = yearSelect.val();

            if (!brandId) {
                toastr.error('{{ __('Please select a brand.') }}');
                return;
            }
            if (!modelId) {
                toastr.error('{{ __('Please select a model.') }}');
                return;
            }
            if (!year) {
                toastr.error('{{ __('Please select a year.') }}');
                return;
            }

            resultsBody.empty();
            resultsContainer.hide();
            emptyState.hide();

            searchButton.prop('disabled', true);
            loadingIndicator.show();

            $.ajax({
                method: 'POST',
                url: "{{ route('labour-by-vehicle.search') }}",
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    brand_id: brandId,
                    model_id: modelId,
                    year: year
                },
                success: function (result) {
                    searchButton.prop('disabled', false);
                    loadingIndicator.hide();

                    if (result.success) {
                        var data = result.data || [];
                        resultsContainer.show();
                        resultsSearch.val('');

                        if (!data.length) {
                            emptyState.show();
                            return;
                        }

                        $.each(data, function (_, row) {
                            var price = row.price !== null && row.price !== undefined
                                ? parseFloat(row.price).toFixed(2)
                                : '-';

                            var tr = $('<tr>');
                            tr.append($('<td>').text(row.name));
                            tr.append($('<td>').text(price));
                            resultsBody.append(tr);
                        });
                    } else {
                        toastr.error(result.message || '{{ __('An error occurred. Please try again.') }}');
                    }
                },
                error: function (xhr) {
                    searchButton.prop('disabled', false);
                    loadingIndicator.hide();

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function (key, messages) {
                            if (messages.length) {
                                toastr.error(messages[0]);
                            }
                        });
                    } else {
                        toastr.error('{{ __('An error occurred. Please try again.') }}');
                    }
                }
            });
        });

        resultsSearch.on('keyup change', function () {
            var query = $(this).val().toLowerCase();
            $('#vehicle_search_results_body tr').each(function () {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(query) !== -1);
            });
        });
    });
</script>
@endsection
