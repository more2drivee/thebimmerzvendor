@extends('layouts.app')
@section('title', 'VIN Management')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">VIN Management
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">Organize VINs, run campaigns, and automate workflows</small>
    </h1>
</section>

<section class="content">
    <div class="row card-row">
        <!-- Import VINs -->
        <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="card card-primary card-outline h-100">
                <div class="card-body text-center d-flex flex-column">
                    <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                        <i class="fas fa-file-upload fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title">Import VINs</h5>
                    <p class="card-text flex-grow-1">Upload Excel/CSV files to bulk add VIN records</p>
                    <a href="{{ route('vin.import') }}" class="btn btn-primary btn-sm mt-auto">
                        <i class="fas fa-arrow-right"></i> Start Import
                    </a>
                </div>
            </div>
        </div>

        <!-- Create Group -->
        <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="card card-info card-outline h-100">
                <div class="card-body text-center d-flex flex-column">
                    <div class="mb-3 flex-grow-1 d-flex align-items-center justify-content-center">
                        <i class="fas fa-layer-group fa-3x text-info"></i>
                    </div>
                    <h5 class="card-title">Create Group</h5>
                    <p class="card-text flex-grow-1">Organize VINs by tags and segments</p>
                    <a href="{{ route('vin.groups') }}" class="btn btn-info btn-sm mt-auto">
                        <i class="fas fa-arrow-right"></i> Manage Groups
                    </a>
                </div>
            </div>
        </div>


   
    </div>
</section>
@endsection