@extends('layouts.app')

@section('title', 'Import Repair Orders')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Import Repair Orders</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-solid', 'title' => 'Upload Excel'])

        @if(session('import_error'))
            <div class="alert alert-danger">
                {{ session('import_error') }}
            </div>
        @endif

        @if(isset($results))
            <div class="alert alert-info">
                {{ $results['message'] ?? 'Import finished' }}
            </div>

            @if(!empty($results['files_processed']))
                <div class="alert alert-info">
                    <strong>Files Processed:</strong>
                    <pre style="white-space: pre-wrap;">{{ json_encode($results['files_processed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif

            @if(isset($results['succeeded_entries']) || isset($results['failed_entries']))
                <div class="alert alert-success">
                    <strong>Entries:</strong>
                    <div>Succeeded: {{ $results['succeeded_entries'] ?? 0 }}</div>
                    <div>Failed: {{ $results['failed_entries'] ?? 0 }}</div>
                </div>
            @endif

            @if(!empty($results['summary']))
                <div class="alert alert-info">
                    <strong>Per-table summary:</strong>
                    <pre style="white-space: pre-wrap;">{{ json_encode($results['summary'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif

            @if(!empty($results['errors']))
                <div class="alert alert-danger">
                    <strong>Errors:</strong>
                    <pre style="white-space: pre-wrap;">{{ json_encode($results['errors'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif

            @if(!empty($results['created']))
                <div class="alert alert-success">
                    <strong>Created entries:</strong>
                    <pre style="white-space: pre-wrap;">{{ json_encode($results['created'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif

            @if(!empty($results['entries_preview']))
                <div class="alert alert-warning">
                    <strong>Dry Run Preview:</strong>
                    <pre style="white-space: pre-wrap;">{{ json_encode($results['entries_preview'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif
        @endif

        <form method="POST" action="{{ action([\Modules\Connector\Http\Controllers\Api\RepairOrderImportController::class, 'handleImportForm']) }}" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="location_id">Location *</label>
                        <select name="location_id" id="location_id" class="form-control" required>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}" {{ old('location_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="files">Excel Files * (select one or more)</label>
                        <input type="file" name="files[]" id="files" class="form-control" accept=".xlsx,.xls" multiple required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="dry_run" value="1" {{ old('dry_run') ? 'checked' : '' }}>
                            Dry run (preview only)
                        </label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">Import</button>
                </div>
            </div>

        </form>

    @endcomponent
</section>
@endsection
