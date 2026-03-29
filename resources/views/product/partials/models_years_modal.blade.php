<div class="modal fade" id="models_years_modal" tabindex="-1" role="dialog" aria-labelledby="modelsYearsModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modelsYearsModalLabel">@lang('lang_v1.models_and_years')</h4>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="add_new_model_year">
                            <i class="fa fa-plus"></i> @lang('lang_v1.add_new_model_year')
                        </button>
                    </div>
                </div>

                <div id="model_year_form" class="row mb-3" style="display: none;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="modal_brand">@lang('lang_v1.brand'):</label>
                            <select id="modal_brand" class="form-control select2">
                                <option value="">@lang('messages.please_select')</option>
                                @foreach($brands as $id => $brand)
                                    <option value="{{ $id }}">{{ $brand }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="modal_model_name">@lang('lang_v1.car_model'):</label>
                            <select id="modal_model_name" class="form-control select2" disabled>
                                <option value="">@lang('messages.please_select')</option>
                                @foreach($models as $id => $model)
                                    <option value="{{ $id }}">{{ $model }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="modal_from_year">@lang('lang_v1.from_year'):</label>
                            <select id="modal_from_year" class="form-control select2">
                                <option value="">@lang('messages.please_select')</option>
                                @php
                                    $currentYear = date('Y');
                                    $startYear = 1990;
                                @endphp
                                @for($year = $currentYear; $year >= $startYear; $year--)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="modal_to_year">@lang('lang_v1.to_year'):</label>
                            <select id="modal_to_year" class="form-control select2">
                                <option value="">@lang('messages.please_select')</option>
                                @for($year = $currentYear; $year >= $startYear; $year--)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="modal_motor_cc">Motor CC:</label>
                            <input type="text" id="modal_motor_cc" class="form-control" placeholder="e.g. 1600">
                        </div>
                    </div>
                    <div class="col-md-12 mt-2">
                        <button type="button" class="btn btn-primary btn-sm" id="add_model_year_row">
                            @lang('messages.add')
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="models_years_table">
                                <thead>
                                    <tr>
                                        <th>@lang('lang_v1.brand')</th>
                                        <th>@lang('lang_v1.car_model')</th>
                                        <th>@lang('lang_v1.from_year')</th>
                                        <th>@lang('lang_v1.to_year')</th>
                                        <th>Motor CC</th>
                                        <th>@lang('messages.action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Table rows will be added dynamically -->
                                    @if (!empty($product->compatibility) && $product->compatibility->count() > 0)
                                        @foreach($product->compatibility as $compat)
                                            @php
                                                $brand_name = '';
                                                $model_name = '';
                                                if (!empty($compat->brand_category_id) && isset($brands[$compat->brand_category_id])) {
                                                    $brand_name = $brands[$compat->brand_category_id];
                                                }
                                                if (!empty($compat->model_id) && isset($models[$compat->model_id])) {
                                                    $model_name = $models[$compat->model_id];
                                                }
                                            @endphp
                                            <tr data-compat-id="{{ $compat->id }}" data-brand-id="{{ $compat->brand_category_id ?? '' }}" data-model-id="{{ $compat->model_id ?? '' }}" data-from-year="{{ $compat->from_year ?? '' }}" data-to-year="{{ $compat->to_year ?? '' }}" data-motor-cc="{{ $compat->motor_cc ?? '' }}">
                                                <td>{{ $brand_name }}</td>
                                                <td>{{ $model_name }}</td>
                                                <td>{{ $compat->from_year ?? '' }}</td>
                                                <td>{{ $compat->to_year ?? '' }}</td>
                                                <td>{{ $compat->motor_cc ?? '' }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-xs remove-model-year-row">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="btn btn-primary" id="save_models_years">@lang('messages.save')</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Store brand and model data for lookup
    const brandsData = @json($brands ?? []);
    const modelsData = @json($models ?? []);

    // Show/hide form when add button is clicked
    $('#add_new_model_year').on('click', function() {
        $('#model_year_form').slideToggle();
    });

    // Enable model dropdown when brand is selected
    $('#modal_brand').on('change', function() {
        const brandId = $(this).val();
        if (brandId) {
            $('#modal_model_name').prop('disabled', false);
        } else {
            $('#modal_model_name').prop('disabled', true).val('');
        }
    });

    // Add row to table
    $('#add_model_year_row').on('click', function() {
        const brandId = $('#modal_brand').val();
        const modelId = $('#modal_model_name').val();
        const fromYear = $('#modal_from_year').val();
        const toYear = $('#modal_to_year').val();
        const motorCc = $('#modal_motor_cc').val();

        // Validate that brand and model are selected
        if (!brandId || !modelId) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Please select both brand and model');
            }
            return;
        }

        if (!fromYear || !toYear) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Please select both from and to year');
            }
            return;
        }

        if (parseInt(fromYear) > parseInt(toYear)) {
            if (typeof toastr !== 'undefined') {
                toastr.error('From year cannot be greater than to year');
            }
            return;
        }

        // Get brand and model names
        const brandName = brandsData[brandId] || '';
        const modelName = modelsData[modelId] || '';

        // Create new row
        const newRow = `
            <tr data-brand-id="${brandId}" data-model-id="${modelId}" data-from-year="${fromYear}" data-to-year="${toYear}" data-motor-cc="${motorCc}">
                <td>${brandName}</td>
                <td>${modelName}</td>
                <td>${fromYear}</td>
                <td>${toYear}</td>
                <td>${motorCc}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-xs remove-model-year-row">
                        <i class="fa fa-times"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#models_years_table tbody').append(newRow);

        // Clear form fields
        $('#modal_brand').val('');
        $('#modal_model_name').prop('disabled', true).val('');
        $('#modal_from_year').val('');
        $('#modal_to_year').val('');
        $('#modal_motor_cc').val('');
    });

    // Remove row
    $(document).on('click', '.remove-model-year-row', function() {
        $(this).closest('tr').remove();
    });

    // Save compatibility data
    $('#save_models_years').on('click', function() {
        const compatibilityData = [];
        let rowIndex = 0;

        $('#models_years_table tbody tr').each(function() {
            const brandId = $(this).data('brand-id');
            const modelId = $(this).data('model-id');
            const fromYear = $(this).data('from-year');
            const toYear = $(this).data('to-year');
            const motorCc = $(this).data('motor-cc');

            // Only add if brand and model are present
            if (brandId && modelId) {
                compatibilityData.push({
                    brand_category_id: brandId,
                    model_id: modelId,
                    from_year: fromYear,
                    to_year: toYear,
                    motor_cc: motorCc
                });
                rowIndex++;
            }
        });

        // Add compatibility data to the main form
        const form = $('form').first();
        // Remove existing compatibility inputs
        form.find('input[name^="compatibility["]').remove();

        // Add new compatibility inputs
        compatibilityData.forEach(function(data, index) {
            form.append(`<input type="hidden" name="compatibility[${index}][brand_category_id]" value="${data.brand_category_id}">`);
            form.append(`<input type="hidden" name="compatibility[${index}][model_id]" value="${data.model_id}">`);
            form.append(`<input type="hidden" name="compatibility[${index}][from_year]" value="${data.from_year}">`);
            form.append(`<input type="hidden" name="compatibility[${index}][to_year]" value="${data.to_year}">`);
            form.append(`<input type="hidden" name="compatibility[${index}][motor_cc]" value="${data.motor_cc}">`);
        });

        // Close modal
        $('#models_years_modal').modal('hide');

        if (typeof toastr !== 'undefined') {
            toastr.success('Compatibility data saved successfully');
        }
    });
});
</script>
