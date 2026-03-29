@extends('layouts.app')

@section('title', __('product_organization.title'))

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>@lang('product_organization.title')</h1>
    </section>

    <section class="content">
        <div class="row card-row">
            @foreach($cards as $card)
                @if(auth()->user()->can($card['permission']))
                    <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                        <div class="card card-{{ $card['color'] }} card-outline h-100">
                            <div class="card-body text-center d-flex flex-column">
                                <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                                    <i class="{{ $card['icon'] }} fa-3x text-{{ $card['color'] }}"></i>
                                </div>
                                <h5 class="card-title">{{ $card['title'] }}</h5>
                                <p class="card-text flex-grow-1">{{ $card['description'] }}</p>
                                <a href="{{ $card['route'] }}" class="btn btn-{{ $card['color'] }} btn-sm mt-auto">
                                    <i class="fas fa-arrow-right"></i> @lang('product_organization.access')
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @if(count($cards) == 0)
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h4>@lang('product_organization.no_permissions')</h4>
                        <p>@lang('product_organization.no_permissions_message')</p>
                    </div>
                </div>
            </div>
        @endif
    </section>
</div>



@endsection

@push('css')
<style>
.card-row {
    margin-bottom: 20px;
}

.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    margin-bottom: 20px;
    height: 100%;
    min-height: 280px;
    display: flex;
    flex-direction: column;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.card-title {
    font-weight: 600;
    margin-bottom: 15px;
}

.card-text {
    flex-grow: 1;
    margin-bottom: 20px;
}

.card-primary {
    border-top: 4px solid #007bff;
}

.card-success {
    border-top: 4px solid #28a745;
}

.card-warning {
    border-top: 4px solid #ffc107;
}

.card-info {
    border-top: 4px solid #17a2b8;
}

.card-secondary {
    border-top: 4px solid #6c757d;
}

.card-dark {
    border-top: 4px solid #343a40;
}

.card-light {
    border-top: 4px solid #f8f9fa;
}

.btn {
    font-weight: 500;
}

@media (max-width: 768px) {
    .card {
        min-height: 250px;
    }
}
</style>
@endpush
