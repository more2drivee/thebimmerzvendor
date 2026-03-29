@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Job Sheet Media - {{ $jobSheet->id }}</h2>
    
    @if($jobSheet->media->count() > 0)
        <div class="row">
            @foreach ($jobSheet->media as $media)
                <div class="col-md-3 mb-3">
                    <div class="card">
<img src="{{ asset('storage/' . $media->file_name) }}" class="card-img-top" alt="Media Image">
                            <p class="card-text">{{ $media->description ?? 'No description' }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p>No media found for this Job Sheet.</p>
    @endif
</div>
@endsection
