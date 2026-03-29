@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h2 class="mb-0 h4">
                        <i class="fas fa-cog me-2"></i> Default AI Provider Settings
                    </h2>
                </div>
                <div class="card-body p-4">
                    <!-- Alert container with improved visibility -->
                    <div id="alertContainer" class="mb-4 alert-container">
                        <div class="alert alert-dismissible fade show shadow-sm border-left" role="alert">
                            <i class="fas fa-info-circle me-2 alert-icon"></i>
                            <span id="alertMessage" class="fw-semibold"></span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                    
                    <form id="defaultSettingsForm" action="{{ route('artificialintelligence.settings.update-active') }}" method="POST">
                        @csrf
                        <!-- AI Provider Selection -->
                        <div class="mb-4">
                            <label for="ai_provider" class="form-label fw-bold">Select AI Provider</label>
                            <select class="form-control" id="ai_provider" name="provider">
                                @foreach($aiProviders as $provider)
                                    <option value="{{ $provider }}" {{ $provider == $defaultProvider ? 'selected' : '' }}>
                                        {{ ucfirst($provider) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- AI Model Selection -->
                        <div class="mb-4">
                            <label for="ai_model" class="form-label fw-bold">Select AI Model</label>
                            <select class="form-control" id="ai_model" name="model_name">
                                <!-- Will be populated via AJAX -->
                            </select>
                            <small class="form-text text-muted">
                                <span class="badge bg-success">Free</span> models don't count towards your API usage limits
                            </small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg rounded-3">
                                <i class="fas fa-save me-2"></i> Set as Default AI
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const aiProviderSelect = $('#ai_provider');
    const aiModelSelect = $('#ai_model');
    const form = $('#defaultSettingsForm');
    const alertContainer = $('#alertContainer');
    const alertMessage = $('#alertMessage');

    function showAlert(message, type = 'success') {
        const alert = alertContainer.find('.alert');
        alert.removeClass('alert-success alert-danger')
            .addClass(`alert-${type}`);
            
        // Update icon based on alert type
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        alert.find('.alert-icon').removeClass('fa-info-circle fa-check-circle fa-exclamation-circle')
            .addClass(iconClass);
            
        alertMessage.text(message);
        
        // Slide down animation for better visibility
        alertContainer.hide().slideDown(300);

        // Auto hide after 5 seconds
        setTimeout(() => {
            alertContainer.slideUp(300);
        }, 5000);
    }

    function loadModels(provider, selectedModel = null) {
        aiModelSelect.empty().append('<option value="">Loading models...</option>');
        
        $.ajax({
            url: '{{ route('artificialintelligence.get-models') }}',
            method: 'GET',
            data: { provider: provider },
            success: function(data) {
                aiModelSelect.empty();
                
                if (data.models && data.models.length > 0) {
                    data.models.forEach(function(model) {
                        const icon = model.status === 'free' ? '🆓' : '💰';
                        const isSelected = selectedModel && selectedModel === model.model_name;
                        
                        aiModelSelect.append(
                            `<option value="${model.model_name}" data-status="${model.status}" ${isSelected ? 'selected' : ''}>
                                ${model.model_name} ${icon}
                            </option>`
                        );
                    });
                    aiModelSelect.trigger('change');
                } else {
                    aiModelSelect.append('<option value="">No models available</option>');
                }
            },
            error: function() {
                aiModelSelect.empty().append('<option value="">Error loading models</option>');
                showAlert('Error loading AI models', 'danger');
            }
        });
    }

    aiProviderSelect.on('change', function() {
        loadModels($(this).val());
    });

    // Initial load with default model
    loadModels(aiProviderSelect.val(), '{{ $defaultModel }}');

    form.on('submit', function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    showAlert('Default AI settings updated successfully');
                } else {
                    showAlert(response.message || 'Error updating settings', 'danger');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error updating default settings';
                showAlert(message, 'danger');
            },
            complete: function() {
                submitButton.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Set as Default AI');
            }
        });
    });

    // Close alert button handler
    $(document).on('click', '.alert .btn-close', function() {
        $(this).closest('.alert-container').fadeOut();
    });
});
</script>

<style>
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
    
    .badge {
        font-size: 0.85em;
        font-weight: 500;
    }
    
    /* Improved alert styling */
    .alert-container {
        display: none;
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
    
    .border-left {
        border-left: 4px solid;
    }
    
    .alert-success.border-left {
        border-left-color: #28a745;
    }
    
    .alert-danger.border-left {
        border-left-color: #dc3545;
    }
    
    .alert-icon {
        font-size: 1.1em;
    }
</style>
@endsection
