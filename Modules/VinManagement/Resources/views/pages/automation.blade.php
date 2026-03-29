@extends('layouts.app')
@section('title', 'VIN Automation')
@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Set Automation</h1>
</section>
<section class="content">
    <div class="box box-solid">
        <div class="box-body">
            <p class="text-muted">Create workflow rules and scheduled tasks. (Stub page)</p>
            <a href="{{ route('vin.dashboard') }}" class="btn btn-default"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</section>
@endsection