@extends('layouts.app')

@section('content')
<!-- Full-screen loader overlay -->
<div id="fullScreenLoader" class="loader-overlay">
        <div class="loader-content">
        <div class="car-loader">🚗💨</div>
        <p class="mt-3 loader-text">{{ trans('artificialintelligence::lang.fetching_car_models') }}</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h2 class="mb-0 h4">
                        <i class="fas fa-car me-2"></i> {{ trans('artificialintelligence::lang.import_car_brand') }}
                    </h2>
                </div>
                <div class="card-body p-4">
                    <!-- Alert container for notifications -->
                    <div id="alertContainer" class="mb-4" style="display: none;">
                        <div class="alert alert-dismissible fade show border-left" role="alert">
                            <i class="fas fa-info-circle me-2 alert-icon"></i>
                            <span id="alertMessage" class="fw-semibold"></span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>

                    <form id="brandImportForm" action="{{ route('ai.BrandAndModels') }}" method="GET">
                        @csrf
                        <div class="mb-4">
                            <label for="brand" class="form-label fw-bold">{{ trans('artificialintelligence::lang.car_brand_name') }}</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text"><i class="fas fa-car-side"></i></span>
                                <input type="text" id="brand" name="brand" required
                                    class="form-control @error('brand') is-invalid @enderror"
                                    placeholder="{{ trans('artificialintelligence::lang.enter_car_brand') }}"
                                    aria-describedby="brandHelp brandError">
                                @error('brand')
                                    <div id="brandError" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small id="brandHelp" class="form-text text-muted">
                                {{ trans('artificialintelligence::lang.enter_full_brand_name', ['example' => 'Toyota, Ford']) }}
                            </small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="importButton" class="btn btn-primary btn-lg rounded-3 pulse-on-hover">
                                <i class="fas fa-file-import me-2"></i> {{ trans('artificialintelligence::lang.import_brand_models') }}
                            </button>
                        </div>
                    </form>

                    <div id="importResult" class="mt-4 fade">
                        <div id="resultMessage" class="alert text-center rounded-3 shadow-sm" role="alert"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const AI_LANG = {
    models: {!! json_encode(trans('artificialintelligence::lang.models')) !!},
    obd_codes: {!! json_encode(trans('artificialintelligence::lang.obd_codes')) !!},
    added: {!! json_encode(trans('artificialintelligence::lang.added')) !!},
    skipped: {!! json_encode(trans('artificialintelligence::lang.skipped')) !!},
    manufacturing_locations: {!! json_encode(trans('artificialintelligence::lang.manufacturing_locations')) !!},
    country_specific_brands_created: {!! json_encode(trans('artificialintelligence::lang.country_specific_brands_created')) !!},
    import_summary: {!! json_encode(trans('artificialintelligence::lang.import_summary')) !!},
    models_label: {!! json_encode(trans('artificialintelligence::lang.models')) !!},
    obd_label: {!! json_encode(trans('artificialintelligence::lang.obd_codes')) !!}
};

document.addEventListener('DOMContentLoaded', function() {
    const form = $('#brandImportForm');
    const brandInput = $('#brand');
    const importButton = $('#importButton');
    const importResult = $('#importResult');
    const resultMessage = $('#resultMessage');
    const fullScreenLoader = $('#fullScreenLoader');
    const alertContainer = $('#alertContainer');
    const alertMessage = $('#alertMessage');

    function showAlert(message, type = 'success') {
        const alert = alertContainer.find('.alert');
        alert.removeClass('alert-success alert-danger alert-warning alert-info')
            .addClass(`alert-${type}`);

        // Update icon based on alert type
        let iconClass = 'fa-info-circle';
        if (type === 'success') iconClass = 'fa-check-circle';
        else if (type === 'danger') iconClass = 'fa-exclamation-circle';
        else if (type === 'warning') iconClass = 'fa-exclamation-triangle';

        alert.find('.alert-icon').removeClass('fa-info-circle fa-check-circle fa-exclamation-circle fa-exclamation-triangle')
            .addClass(iconClass);

        alertMessage.text(message);
        alertContainer.slideDown(300);

        // Auto hide after 5 seconds
        setTimeout(() => {
            alertContainer.slideUp(300);
        }, 5000);
    }

    form.on('submit', function(e) {
        e.preventDefault();

        if (!brandInput.val().trim()) {
            showAlert({!! json_encode(trans('artificialintelligence::lang.please_enter_brand')) !!}, 'danger');
            return;
        }

        // Show full-screen loader
        fullScreenLoader.css('visibility', 'visible').css('opacity', '1');
        importButton.prop('disabled', true);

        $.ajax({
            url: '{{ route('ai.BrandAndModels') }}',
            method: 'GET',
            data: {
                _token: '{{ csrf_token() }}',
                brand: brandInput.val()
            },
            dataType: 'json',
            beforeSend: function() {
                console.log("Sending request...");
            },
            success: function(data) {
                console.log("Response received:", data);
                // Hide loader
                fullScreenLoader.css('opacity', '0');
                setTimeout(() => fullScreenLoader.css('visibility', 'hidden'), 300);
                importButton.prop('disabled', false);

                if (data.message || data.success) {
                    // Extract data from the response
                    const brandData = data.data || {};

                    // Initialize a normalized data structure that works with both API and DB responses
                    const normalizedData = {
                        brand: brandData.brand || data.brand || '',
                        vin_category_code: brandData.vin_category_code || data.vin_category_code || '',
                        country_of_origin: brandData.country_of_origin || data.country_of_origin || '-',
                        manufacturing_locations: brandData.manufacturing_locations || data.manufacturing_locations || [],
                        models: brandData.models || data.models || [],
                        obd_codes: brandData.obd_codes || data.obd_codes || [],
                        added_models: data.added_models || 0,
                        updated_models: data.updated_models || 0,
                        skipped_models: data.skipped_models || 0,
                        added_obd_codes: data.added_obd_codes || 0,
                        skipped_obd_codes: data.skipped_obd_codes || 0,
                        country_specific_brands: data.country_specific_brands || [],
                        source: data.source || ''  // Changed from 'unknown' to empty string
                    };

                    // Ensure we have a valid data structure
                    if (!normalizedData.brand) {
                        showResult({!! json_encode(trans('artificialintelligence::lang.invalid_data_format')) !!}, 'danger');
                        return;
                    }

                    const brandName = normalizedData.brand;

                    // Create dashboard layout
                    let dashboardHtml = `
                        <div class="dashboard">
                            ${normalizedData.source !== 'database' ? `
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card shadow-sm">
                                        <div class="card-body">
                                            <h4 class="text-primary mb-3"><i class="fas fa-car me-2"></i>${brandName} {{ trans('artificialintelligence::lang.import_summary') }}</h4>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="info-card bg-info p-3 rounded">
                                                        <p><i class="fas fa-barcode me-2"></i><strong>{{ trans('artificialintelligence::lang.main_brand_vin_code') }}:</strong> <span class="badge bg-info">${normalizedData.vin_category_code || '-'}</span></p>
                                                        <p><i class="fas fa-globe me-2"></i><strong>{{ trans('artificialintelligence::lang.country_of_origin') }}:</strong> <span class="badge bg-primary">${normalizedData.country_of_origin}</span></p>

                                                        ${normalizedData.manufacturing_locations.length > 0 ?
                                                        `<p><i class="fas fa-industry me-2"></i><strong>${AI_LANG.manufacturing_locations}:</strong>
                                                            ${normalizedData.manufacturing_locations.map(loc => `<span class="badge bg-secondary">${loc}</span>`).join(' ')}
                                                        </p>` : ''
                                                        }

                                                        ${normalizedData.country_specific_brands.length > 0 ?
                                                        `<p><i class="fas fa-flag me-2"></i><strong>${AI_LANG.country_specific_brands_created}:</strong>
                                                            ${normalizedData.country_specific_brands.map(country =>
                                                                `<span class="badge bg-info">${country === 'default' ? normalizedData.country_of_origin : country}</span>`
                                                            ).join(' ')}
                                                        </p>` : ''
                                                        }

                                                        <p><i class="fas fa-car-side me-2"></i><strong>{{ trans('artificialintelligence::lang.models') }}:</strong>
                                                            <span class="badge bg-success">${normalizedData.added_models} {{ trans('artificialintelligence::lang.added') }}</span>
                                                            <span class="badge bg-secondary">${normalizedData.skipped_models} {{ trans('artificialintelligence::lang.skipped') }}</span>
                                                        </p>
                                                        <p><i class="fas fa-microchip me-2"></i><strong>{{ trans('artificialintelligence::lang.obd_codes') }}:</strong>
                                                            <span class="badge bg-success">${normalizedData.added_obd_codes} {{ trans('artificialintelligence::lang.added') }}</span>
                                                            <span class="badge bg-secondary">${normalizedData.skipped_obd_codes} {{ trans('artificialintelligence::lang.skipped') }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="chart-container" style="position: relative; height:200px;">
                                                        <canvas id="importSummaryChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="alert alert-success mt-3">${data.message}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ` : `
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card shadow-sm">
                                        <div class="card-body">
                                            <h4 class="text-primary mb-3"><i class="fas fa-car me-2"></i>${brandName} {{ trans('artificialintelligence::lang.import_summary') }}</h4>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="info-card bg-info p-3 rounded">
                                                        <p><i class="fas fa-barcode me-2"></i><strong>{{ trans('artificialintelligence::lang.vin_code') }}:</strong> <span class="badge bg-info">${normalizedData.vin_category_code || '-'}</span></p>
                                                        <p><i class="fas fa-globe me-2"></i><strong>{{ trans('artificialintelligence::lang.country_of_origin') }}:</strong> <span class="badge bg-primary">${normalizedData.country_of_origin}</span></p>
                                                        <p><i class="fas fa-car-side me-2"></i><strong>{{ trans('artificialintelligence::lang.models_count') }}:</strong> <span class="badge bg-success">${normalizedData.models.length}</span></p>
                                                        <p><i class="fas fa-microchip me-2"></i><strong>{{ trans('artificialintelligence::lang.obd_codes') }} {{ trans('artificialintelligence::lang.count_suffix') }}:</strong> <span class="badge bg-success">${normalizedData.obd_codes.length}</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                           
                                        </div>
                                    </div>
                                </div>
                            </div>
                            `}

                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><i class="fas fa-globe-americas me-2"></i>{{ trans('artificialintelligence::lang.country_specific_brands') }}</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-striped">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>{{ trans('artificialintelligence::lang.brand_name') }}</th>
                                                            <th>{{ trans('artificialintelligence::lang.country') }}</th>
                                                            <th>{{ trans('artificialintelligence::lang.vin_code') }}</th>
                                                            <th>{{ trans('artificialintelligence::lang.models_count') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Add the main brand first -->
                                                        <tr>
                                                            <td>${normalizedData.brand}</td>
                                                            <td><span class="badge bg-primary">${normalizedData.country_of_origin}</span></td>
                                                            <td><span class="badge bg-info">${normalizedData.vin_category_code}</span></td>
                                                            <td>${Array.isArray(normalizedData.models) ?
                                                                normalizedData.models.filter(model =>
                                                                    (model.manufacturing_country || normalizedData.country_of_origin) === normalizedData.country_of_origin
                                                                ).length : 0}</td>
                                                        </tr>

                                                        <!-- Add other country-specific brands -->
                                                        ${normalizedData.country_specific_brands
                                                            .filter(country => country !== normalizedData.country_of_origin)
                                                            .map(country => {
                                                                const brandName = `${normalizedData.brand} ${country}`;
                                                                const modelsCount = Array.isArray(normalizedData.models) ?
                                                                    normalizedData.models.filter(
                                                                        model => (model.manufacturing_country || '') === country
                                                                    ).length : 0;

                                                                // Generate country-specific VIN code based on country
                                                                let countryVinCode = '';
                                                                const countryVinMap = {
                                                                    'Japan': 'J',
                                                                    'South Korea': 'K',
                                                                    'China': 'L',
                                                                    'India': 'M',
                                                                    'United States': '1',
                                                                    'USA': '1',
                                                                    'Canada': '2',
                                                                    'Mexico': '3',
                                                                    'Australia': '6',
                                                                    'New Zealand': '7',
                                                                    'United Kingdom': 'S',
                                                                    'UK': 'S',
                                                                    'Germany': 'W',
                                                                    'Italy': 'Z',
                                                                    'France': 'V',
                                                                    'Sweden': 'Y',
                                                                    'Spain': 'V',
                                                                    'Brazil': '9',
                                                                    'Thailand': 'M',
                                                                    'South Africa': 'A'
                                                                };

                                                                if (countryVinMap[country]) {
                                                                    if (data.vin_category_code && data.vin_category_code.length > 1) {
                                                                        countryVinCode = countryVinMap[country] + data.vin_category_code.substring(1);
                                                                    } else {
                                                                        countryVinCode = countryVinMap[country] + 'XX';
                                                                    }
                                                                }

                                                                return `
                                                                <tr>
                                                                    <td>${brandName}</td>
                                                                    <td><span class="badge bg-primary">${country}</span></td>
                                                                    <td><span class="badge bg-info">${countryVinCode}</span></td>
                                                                    <td>${modelsCount}</td>
                                                                </tr>`;
                                                            }).join('')}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-6">
                                    <div class="card shadow-sm h-100">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><i class="fas fa-car-alt me-2"></i>{{ trans('artificialintelligence::lang.models') }}</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                <table class="table table-hover table-striped">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>{{ trans('artificialintelligence::lang.model_name') }}</th>
                                                            <th>{{ trans('artificialintelligence::lang.vin_code') }}</th>
                                                            <th>{{ trans('artificialintelligence::lang.manufacturer') }}</th>
                                                            <th>{{ trans('artificialintelligence::lang.brand_name') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="modelsTableBody">
                                                        <!-- Will be populated with models -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 mb-6">
                                    <div class="card shadow-sm h-100">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><i class="fas fa-microchip me-2"></i>{{ trans('artificialintelligence::lang.obd_codes') }}</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                <table class="table table-hover table-striped">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>{{ trans('artificialintelligence::lang.code') }}</th>
                                                            <th>{{ trans('artificialintelligence::lang.problem_description') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="obdCodesTableBody">
                                                        <!-- Will be populated with OBD codes -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    resultMessage.html(dashboardHtml);
                    importResult.removeClass('fade').addClass('show').css('display', 'block');

                    // Populate models table
                    const modelsTableBody = $('#modelsTableBody');
                    const models = normalizedData.models;
                    const countryOfOrigin = normalizedData.country_of_origin;

                    models.forEach(model => {
                        const country = model.manufacturing_country || countryOfOrigin || '-';
                        const brandName = country === countryOfOrigin ?
                            normalizedData.brand :
                            `${normalizedData.brand} ${country}`;

                        modelsTableBody.append(`
                            <tr>
                                <td>${model.name}</td>
                                <td><span class="badge bg-info">${model.vin_model_code || '-'}</span></td>
                                <td><span class="badge bg-secondary">${country}</span></td>
                                <td><span class="badge bg-primary">${brandName}</span></td>
                            </tr>
                        `);
                    });

                    // Populate OBD codes table
                    const obdCodesTableBody = $('#obdCodesTableBody');
                    const obdCodes = normalizedData.obd_codes;

                    obdCodes.forEach(obd => {
                        obdCodesTableBody.append(`
                            <tr>
                                <td><span class="badge bg-warning text-dark">${obd.code}</span></td>
                                <td>${obd.problem_name}</td>
                            </tr>
                        `);
                    });

                    // Create summary chart only if data is not from database
                    if (normalizedData.source !== 'database') {
                        const ctx = document.getElementById('importSummaryChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: [AI_LANG.models_label, AI_LANG.obd_label],
                                datasets: [
                                        {
                                        label: AI_LANG.added,
                                        data: [
                                            normalizedData.added_models,
                                            normalizedData.added_obd_codes
                                        ],
                                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                                        borderColor: 'rgba(40, 167, 69, 1)',
                                        borderWidth: 1
                                    },
                                    {
                                        label: AI_LANG.skipped,
                                        data: [
                                            normalizedData.skipped_models,
                                            normalizedData.skipped_obd_codes
                                        ],
                                        backgroundColor: 'rgba(108, 117, 125, 0.7)',
                                        borderColor: 'rgba(108, 117, 125, 1)',
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    }
                                }
                            }
                        });
                    }

                    brandInput.val('');
                } else {
                    // Handle case where data.message is not present
                    if (data && data.error) {
                        showResult(data.error, 'danger');
                    } else if (data && data.message) {
                        showResult(data.message, 'info');
                    } else {
                        showResult({!! json_encode(trans('artificialintelligence::lang.failed_import')) !!}, 'danger');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("Error occurred:", status, error);

                // Hide loader
                fullScreenLoader.css('opacity', '0');
                setTimeout(() => fullScreenLoader.css('visibility', 'hidden'), 300);
                importButton.prop('disabled', false);

                // Get error message
                let errorMessage = {!! json_encode(trans('artificialintelligence::lang.failed_import')) !!};

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 0) {
                    errorMessage = {!! json_encode(trans('artificialintelligence::lang.network_error')) !!};
                } else if (xhr.status === 404) {
                    errorMessage = {!! json_encode(trans('artificialintelligence::lang.resource_not_found')) !!};
                } else if (xhr.status === 500) {
                    errorMessage = {!! json_encode(trans('artificialintelligence::lang.server_error')) !!};
                } else if (xhr.status === 429) {
                    errorMessage = {!! json_encode(trans('artificialintelligence::lang.too_many_requests')) !!};
                }

                // Show error alert
                showAlert(errorMessage, 'danger');

                // Also show in result area
                showResult(errorMessage, 'danger');
            },
            complete: function() {
                // Ensure loader is hidden and button is re-enabled even if there's an unexpected error
                setTimeout(() => {
                    fullScreenLoader.css('visibility', 'hidden').css('opacity', '0');
                    importButton.prop('disabled', false);
                }, 500);
            }
        });
    });

    function showResult(message, type) {
        resultMessage.html(`<p>${message}</p>`);
        resultMessage.attr('class', `alert alert-${type} text-center rounded-3 shadow-sm`);
        importResult.removeClass('fade').addClass('show').css('display', 'block');
    }
});
</script>

<style>
    /* Full-screen loader styles */
    .loader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .loader-content {
        text-align: center;
        background-color: rgba(255, 255, 255, 0.9);
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        max-width: 80%;
        animation: bounce-in 0.5s ease-out;
    }

    .loader-text {
        color: #333;
        font-size: 1.2rem;
        font-weight: 500;
    }

    .car-loader {
        font-size: 4rem;
        display: inline-block;
        animation: drive 1.5s ease-in-out infinite alternate;
    }

    @keyframes drive {
        0% { transform: translateX(-30px) rotate(-5deg); opacity: 0.7; }
        100% { transform: translateX(30px) rotate(5deg); opacity: 1; }
    }

    @keyframes bounce-in {
        0% { transform: scale(0.8); opacity: 0; }
        70% { transform: scale(1.05); }
        100% { transform: scale(1); opacity: 1; }
    }

    /* Card and form styling improvements */
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important;
    }

    .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        border-color: #86b7fe;
    }

    /* Button animation */
    .pulse-on-hover:hover {
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    /* Result animation */
    #importResult.show {
        animation: slide-up 0.5s ease-out;
    }

    @keyframes slide-up {
        0% { transform: translateY(20px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }

    /* List styling */
    .list-group-item {
        transition: background-color 0.2s ease;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
    }

    /* Dashboard styling */
    .dashboard {
        animation: fade-in 0.5s ease-out;
    }

    .info-card {
        border-left: 4px solid #0d6efd;
        transition: transform 0.2s ease;
    }

    .info-card:hover {
        transform: translateX(5px);
    }

    .table-responsive {
        scrollbar-width: thin;
        scrollbar-color: #0d6efd #f8f9fa;
    }

    .table-responsive::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f8f9fa;
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background-color: #0d6efd;
        border-radius: 10px;
    }

    @keyframes fade-in {
        0% { opacity: 0; }
        100% { opacity: 1; }
    }

    .badge {
        font-size: 0.85em;
        font-weight: 500;
    }

    /* Alert styling */
    .alert-container {
        margin-bottom: 1.5rem;
    }

    .alert {
        border-radius: 8px;
        position: relative;
    }

    .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    .alert-warning {
        background-color: #fff3cd;
        border-color: #ffeeba;
    }

    .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
    }

    .border-left {
        border-left: 4px solid;
    }

    .alert-success.border-left {
        border-left-color: #28a745;
    }

    .alert-danger.border-left {
        border-left-color: #dc3545;
    }

    .alert-warning.border-left {
        border-left-color: #ffc107;
    }

    .alert-info.border-left {
        border-left-color: #17a2b8;
    }

    .alert-icon {
        font-size: 1.1em;
    }

    /* ... rest of existing styles ... */
</style>
@endsection
