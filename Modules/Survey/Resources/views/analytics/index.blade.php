@extends('layouts.app')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>{{ __('survey::lang.survey_analytics') }}</h1>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('survey::lang.survey_performance') }}</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped" id="survey_analytics_table">
                            <thead>
                                <tr>
                                    <th>{{ __('survey::lang.survey_title') }}</th>
                                    <th>{{ __('survey::lang.total_sent') }}</th>
                                    <th>{{ __('survey::lang.total_seen') }}</th>
                                    <th>{{ __('survey::lang.total_filled') }}</th>
                                    <th>{{ __('survey::lang.response_rate') }}</th>
                                    <th>{{ __('messages.action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var surveyAnalyticsTable = $('#survey_analytics_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ url('survey/analytics/data') }}",
        columns: [
            { data: 'title', name: 'title' },
            { data: 'total_sent', name: 'total_sent' },
            { data: 'total_seen', name: 'total_seen' },
            { data: 'total_filled', name: 'total_filled' },
            { data: 'response_rate', name: 'response_rate' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
});
</script>
@endpush
