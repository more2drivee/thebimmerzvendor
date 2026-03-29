@extends('layouts.app')
@section('title', 'VIN Campaigns')
@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">New Campaign</h1>
</section>
<section class="content">
    <div class="box box-solid">
        <div class="box-body">
            <p class="text-muted">Plan and launch targeted campaigns for VIN groups. (Stub page)</p>
            <a href="{{ route('vin.dashboard') }}" class="btn btn-default"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</section>
@endsection