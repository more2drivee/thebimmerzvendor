@extends('layouts.app')

@section('title', __('product_management.core_title'))

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>{{ __('product_management.core_title') }}</h1>
    </section>

    <section class="content">
        <div class="row card-row">
            {{-- Products Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-primary h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-boxes fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">{{ __('lang_v1.list_products') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('product.manage_products_description') }}</p>
                        <div class="btn-group-vertical mt-auto">
                            <a href="{{ action([\App\Http\Controllers\ProductController::class, 'index']) }}" class="btn btn-primary btn-sm mb-2">
                                <i class="fas fa-list"></i> {{ __('lang_v1.list_products') }}
                            </a>
                            <a href="{{ action([\App\Http\Controllers\ProductController::class, 'create']) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus"></i> {{ __('product.add_product') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Services Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-purple h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-tools fa-3x text-purple"></i>
                        </div>
                        <h5 class="card-title">{{ __('product.services') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('product.manage_labour_description') }}</p>
                        <div class="btn-group-vertical mt-auto">
                            <a href="{{ action([\App\Http\Controllers\ServiceController::class, 'index']) }}" class="btn btn-purple btn-sm mb-2">
                                <i class="fas fa-list"></i> {{ __('product.list_labour') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Brands Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-success h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-tags fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title">{{ __('brand.brands') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('brand.manage_brands_description') }}</p>
                        <a href="{{ action([\App\Http\Controllers\BrandController::class, 'index']) }}" class="btn btn-success btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> {{ __('brand.manage_your_brands') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Units Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-info h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-balance-scale fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">{{ __('unit.units') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('unit.manage_units_description') }}</p>
                        <a href="{{ action([\App\Http\Controllers\UnitController::class, 'index']) }}" class="btn btn-info btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> {{ __('unit.manage_your_units') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Categories Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-warning h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-folder fa-3x text-warning"></i>
                        </div>
                        <h5 class="card-title">{{ __('category.categories') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('category.manage_categories_description') }}</p>
                        <a href="{{ action([\App\Http\Controllers\TaxonomyController::class, 'index']) }}?type=product" class="btn btn-warning btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> {{ __('category.manage_your_categories') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Variations Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-secondary h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-layer-group fa-3x text-secondary"></i>
                        </div>
                        <h5 class="card-title">{{ __('product.variations') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('product.manage_variations_description') }}</p>
                        <a href="{{ action([\App\Http\Controllers\VariationTemplateController::class, 'index']) }}" class="btn btn-secondary btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> {{ __('product.manage_variations') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Import Products Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-dark h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-file-import fa-3x text-dark"></i>
                        </div>
                        <h5 class="card-title">{{ __('product.import_products') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('product.import_products_description') }}</p>
                        <a href="{{ action([\App\Http\Controllers\ImportProductsController::class, 'index']) }}" class="btn btn-dark btn-sm mt-auto">
                            <i class="fas fa-file-import"></i> {{ __('product.import_products') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Bundles Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-purple h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-car-crash fa-3x text-purple"></i>
                        </div>
                        <h5 class="card-title">{{ __('bundles.title') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('bundles.subtitle') }}</p>
                        <a href="{{ route('bundles.index') }}" class="btn btn-purple btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> {{ __('bundles.title') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Simple Import with Compatibilities Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-dark h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-project-diagram fa-3x text-dark"></i>
                        </div>
                        <h5 class="card-title">{{ __('product.universal_import_title') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('product.universal_import_description') }}</p>
                        <a href="{{ route('import-products.universal') }}" class="btn btn-dark btn-sm mt-auto">
                            <i class="fas fa-file-upload"></i> {{ __('product.universal_import') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Print Labels Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-dark h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-barcode fa-3x text-dark"></i>
                        </div>
                        <h5 class="card-title">{{ __('barcode.print_labels') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('barcode.print_labels_description') }}</p>
                        <a href="{{ action([\App\Http\Controllers\LabelsController::class, 'show']) }}" class="btn btn-dark btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> {{ __('barcode.print_labels') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Purchase Receiving Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-info h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-truck-loading fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">{{ __('purchase.receive_purchases') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('purchase.receive_purchases_description') }}</p>
                        <a href="{{ route('purchase_receiving.index') }}" class="btn btn-info btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> {{ __('purchase.manage_receiving') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Labour by Vehicle Card --}}
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <div class="card card-primary h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                            <i class="fas fa-car-side fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">{{ __('Labour by Vehicle') }}</h5>
                        <p class="card-text flex-grow-1">{{ __('Manage labour services by vehicle model') }}</p>
                        <a href="{{ route('labour-by-vehicle.index') }}" class="btn btn-primary btn-sm mt-auto">
                            <i class="fas fa-arrow-right"></i> {{ __('Manage Labour by Vehicle') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('css')
<style>
.card-row {
    margin-bottom: 20px;
}

.card {
    transition: all 0.3s ease;
    margin-bottom: 20px;
    height: 100%;
    min-height: 320px;
    display: flex;
    flex-direction: column;
    border: 1px solid #e3e6f0;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    background: #ffffff;
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    border-color: transparent;
}

.card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 2rem;
}

.card-title {
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 1.25rem;
    color: #2c3e50;
}

.card-text {
    flex-grow: 1;
    margin-bottom: 20px;
    color: #6c757d;
    line-height: 1.6;
}

/* Card color variants with solid borders */
.card-primary {
    border-left: 5px solid #007bff;
}

.card-success {
    border-left: 5px solid #28a745;
}

.card-warning {
    border-left: 5px solid #ffc107;
}

.card-info {
    border-left: 5px solid #17a2b8;
}

.card-secondary {
    border-left: 5px solid #6c757d;
}

.card-dark {
    border-left: 5px solid #343a40;
}

.card-purple {
    border-left: 5px solid #6f42c1;
}

/* Button styles */
.btn {
    font-weight: 500;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-purple {
    background-color: #6f42c1;
    border-color: #6f42c1;
    color: #ffffff;
}

.btn-purple:hover {
    background-color: #5a32a3;
    border-color: #5a32a3;
    color: #ffffff;
}

.btn-outline-purple {
    color: #6f42c1;
    border-color: #6f42c1;
    background-color: transparent;
}

.btn-outline-purple:hover {
    background-color: #6f42c1;
    border-color: #6f42c1;
    color: #ffffff;
}

.text-purple {
    color: #6f42c1 !important;
}

/* Icon styling */
.fa-3x {
    opacity: 0.8;
}

/* Button group styling */
.btn-group-vertical .btn {
    margin-bottom: 0.5rem;
}

.btn-group-vertical .btn:last-child {
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card {
        min-height: 280px;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .card-title {
        font-size: 1.1rem;
    }
}

@media (max-width: 576px) {
    .card {
        min-height: 250px;
    }
    
    .card-body {
        padding: 1rem;
    }
}
</style>
@endpush
