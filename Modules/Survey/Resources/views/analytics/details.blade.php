@extends('layouts.app')

@section('content')
    @include('survey::layouts.nav')
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>{{ $survey->title }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ url('survey/analytics') }}">{{ __('survey::lang.analytics') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('survey::lang.details') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <!-- Overview Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-0">{{ __('survey::lang.total-sent') }}</h5>
                        <p class="card-text h3 mt-2">{{ $sentData->total ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-0">{{ __('survey::lang.total-seen') }}</h5>
                        <p class="card-text h3 mt-2">{{ $sentData->seen ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-0">{{ __('survey::lang.total-filled') }}</h5>
                        <p class="card-text h3 mt-2">{{ $sentData->filled ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-0">{{ __('survey::lang.response-rate') }}</h5>
                        <p class="card-text h3 mt-2">
                            @if($sentData->total > 0)
                                {{ round(($sentData->filled / $sentData->total) * 100, 2) }}%
                            @else
                                0%
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 my-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-0">{{ __('survey::lang.overall-rating') }}</h5>
                        <p class="card-text h3 mt-2">
                            @if($overallRating !== null)
                                {{ $overallRating }}/5
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Question Filter -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('survey::lang.filter-by-questions') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <select class="form-control" id="questionFilter" onchange="filterByQuestions()">
                                <option value="both" {{ request('question_filter', 'both') == 'both' ? 'selected' : '' }}>
                                    {{ __('survey::lang.all-questions') }}
                                </option>
                                <option value="old" {{ request('question_filter') == 'old' ? 'selected' : '' }}>
                                    {{ __('survey::lang.old-questions') }}
                                </option>
                                <option value="current" {{ request('question_filter') == 'current' ? 'selected' : '' }}>
                                    {{ __('survey::lang.current-questions') }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Questions Analytics -->
        @foreach($analytics as $questionId => $data)
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title">{{ $data['question'] }}</h3>
                            <small class="text-muted">{{ __('survey::lang.total-responses') }}: {{ $data['total_responses'] }}</small>
                        </div>
                        @if($data['type_id'] == 2 || $data['type_id'] == 3 || $data['type_id'] == 5)
                        <button class="btn btn-sm btn-primary" onclick="toggleChart({{ $questionId }})">
                            <i class="fa fa-chart-pie"></i> {{ __('survey::lang.show-chart') }}
                        </button>
                        @endif
                    </div>
                    <div class="card-body">
                        <!-- Chart Container (Hidden by default) -->
                        @if($data['type_id'] == 2 || $data['type_id'] == 3)
                        <div id="chart-{{ $questionId }}" style="display: none; margin-bottom: 20px;">
                            <h5>{{ __('survey::lang.response-distribution') }}</h5>
                            <div style="height: 300px;">
                                <canvas id="pie-chart-{{ $questionId }}"></canvas>
                            </div>
                        </div>
                        @elseif($data['type_id'] == 5)
                        <div id="chart-{{ $questionId }}" style="display: none; margin-bottom: 20px;">
                            <h5>{{ __('survey::lang.like-dislike-rating') }}</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <h4><i class="fa fa-thumbs-up"></i> {{ $data['like_counts']['like'] }}</h4>
                                            <small>{{ __('survey::lang.likes') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <h4><i class="fa fa-thumbs-down"></i> {{ $data['like_counts']['dislike'] }}</h4>
                                            <small>{{ __('survey::lang.dislikes') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="progress">
                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $data['like_percentage'] }}%">
                                        {{ $data['like_percentage'] }}% {{ __('survey::lang.liked') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Question Type Specific Display -->
                        @if($data['type_id'] == 1)
                            <!-- Text Responses -->
                            <h5 class="mb-3">{{ __('survey::lang.text-responses') }}</h5>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('survey::lang.response') }}</th>
                                        <th>{{ __('survey::lang.contact-name') }}</th>
                                        <th>{{ __('survey::lang.contact-mobile') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['responses'] as $index => $response)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $response->answer }}</td>
                                        <td>{{ $response->contact_name ?? '-' }}</td>
                                        <td>{{ $response->contact_mobile ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('survey::lang.no-responses') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        @elseif($data['type_id'] == 2)
                            <!-- Radio Button Responses -->
                            <h5 class="mb-3">{{ __('survey::lang.response-summary') }}</h5>
                            <div class="row mb-4">
                                @foreach($data['option_counts'] as $option => $count)
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                        <span>{{ $option }}</span>
                                        <span class="badge badge-primary">{{ $count }} ({{ $data['total_responses'] > 0 ? round(($count / $data['total_responses']) * 100, 1) : 0 }}%)</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <h5 class="mb-3">{{ __('survey::lang.detailed-responses') }}</h5>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('survey::lang.response') }}</th>
                                        <th>{{ __('survey::lang.contact-name') }}</th>
                                        <th>{{ __('survey::lang.contact-mobile') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['responses'] as $index => $response)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><span class="badge badge-info">{{ $response->answer }}</span></td>
                                        <td>{{ $response->contact_name ?? '-' }}</td>
                                        <td>{{ $response->contact_mobile ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('survey::lang.no-responses') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        @elseif($data['type_id'] == 3)
                            <!-- Checkbox Responses -->
                            <h5 class="mb-3">{{ __('survey::lang.response-summary') }}</h5>
                            <div class="row mb-4">
                                @foreach($data['option_counts'] as $option => $count)
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                        <span>{{ $option }}</span>
                                        <span class="badge badge-primary">{{ $count }} ({{ $data['total_responses'] > 0 ? round(($count / $data['total_responses']) * 100, 1) : 0 }}%)</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <h5 class="mb-3">{{ __('survey::lang.detailed-responses') }}</h5>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('survey::lang.response') }}</th>
                                        <th>{{ __('survey::lang.contact-name') }}</th>
                                        <th>{{ __('survey::lang.contact-mobile') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['responses'] as $index => $response)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            @php
                                                $answers = json_decode($response->answer, true);
                                                if (is_array($answers)) {
                                                    echo implode(', ', array_map(fn($a) => '<span class="badge badge-info">' . $a . '</span>', $answers));
                                                } else {
                                                    echo '<span class="badge badge-info">' . $response->answer . '</span>';
                                                }
                                            @endphp
                                        </td>
                                        <td>{{ $response->contact_name ?? '-' }}</td>
                                        <td>{{ $response->contact_mobile ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('survey::lang.no-responses') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        @elseif($data['type_id'] == 4)
                            <!-- Star Rating Responses -->
                            <h5 class="mb-3">{{ __('survey::lang.average-rating') }}: <strong>{{ $data['average_rating'] ?? 'N/A' }}/5</strong></h5>
                            <div class="row mb-4">
                                @foreach($data['rating_distribution'] as $rating => $count)
                                <div class="col-md-2 text-center">
                                    <div class="card border">
                                        <div class="card-body">
                                            <h4>{{ $rating }} <i class="fa fa-star text-warning"></i></h4>
                                            <small>{{ $count }} {{ __('survey::lang.responses') }}</small>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <h5 class="mb-3">{{ __('survey::lang.detailed-responses') }}</h5>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('survey::lang.rating') }}</th>
                                        <th>{{ __('survey::lang.contact-name') }}</th>
                                        <th>{{ __('survey::lang.contact-mobile') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['responses'] as $index => $response)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            @for($i = 0; $i < $response->answer; $i++)
                                                <i class="fa fa-star text-warning"></i>
                                            @endfor
                                            <span class="badge badge-secondary">{{ $response->answer }}/5</span>
                                        </td>
                                        <td>{{ $response->contact_name ?? '-' }}</td>
                                        <td>{{ $response->contact_mobile ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('survey::lang.no-responses') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        @elseif($data['type_id'] == 5)
                            <!-- Like/Dislike Responses -->
                            <h5 class="mb-3">{{ __('survey::lang.response-summary') }}</h5>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center p-3 border rounded bg-light">
                                        <span><i class="fa fa-thumbs-up"></i> {{ __('survey::lang.likes') }}</span>
                                        <span class="badge badge-success" style="font-size: 16px;">{{ $data['like_counts']['like'] }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center p-3 border rounded bg-light">
                                        <span><i class="fa fa-thumbs-down"></i> {{ __('survey::lang.dislikes') }}</span>
                                        <span class="badge badge-danger" style="font-size: 16px;">{{ $data['like_counts']['dislike'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mb-3">{{ __('survey::lang.detailed-responses') }}</h5>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('survey::lang.response') }}</th>
                                        <th>{{ __('survey::lang.contact-name') }}</th>
                                        <th>{{ __('survey::lang.contact-mobile') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['responses'] as $index => $response)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            @if($response->answer === 'like')
                                                <span class="badge badge-success"><i class="fa fa-thumbs-up"></i> {{ __('survey::lang.liked') }}</span>
                                            @else
                                                <span class="badge badge-danger"><i class="fa fa-thumbs-down"></i> {{ __('survey::lang.disliked') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $response->contact_name ?? '-' }}</td>
                                        <td>{{ $response->contact_mobile ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('survey::lang.no-responses') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @endif

                        <!-- Pagination -->
                        @if($data['responses']->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $data['responses']->appends(['customer_id' => request('customer_id'), 'question_filter' => request('question_filter', 'both')])->links() }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleChart(questionId) {
    const chartDiv = document.getElementById('chart-' + questionId);
    if (chartDiv) {
        chartDiv.style.display = chartDiv.style.display === 'none' ? 'block' : 'none';
    }
}

function filterByCustomer() {
    const customerId = document.getElementById('customerFilter').value;
    const url = new URL(window.location.href);
    if (customerId) {
        url.searchParams.set('customer_id', customerId);
    } else {
        url.searchParams.delete('customer_id');
    }
    url.searchParams.delete('page_1');
    url.searchParams.delete('page_2');
    url.searchParams.delete('page_3');
    url.searchParams.delete('page_4');
    url.searchParams.delete('page_5');
    window.location.href = url.toString();
}

function filterByQuestions() {
    const questionFilter = document.getElementById('questionFilter').value;
    const url = new URL(window.location.href);
    url.searchParams.set('question_filter', questionFilter);
    url.searchParams.delete('customer_id');
    url.searchParams.delete('page_1');
    url.searchParams.delete('page_2');
    url.searchParams.delete('page_3');
    url.searchParams.delete('page_4');
    url.searchParams.delete('page_5');
    window.location.href = url.toString();
}
</script>

<script>
$(document).ready(function() {
    // Initialize charts
    initializeCharts();
});

function initializeCharts() {
    @foreach($analytics as $questionId => $data)
        @if($data['type_id'] == 2 || $data['type_id'] == 3)
            (function() {
                const ctx = document.getElementById('pie-chart-{{ $questionId }}');
                if (!ctx) return;

                const optionCounts = @json($data['option_counts'] ?? []);
                const labels = Object.keys(optionCounts);
                const dataValues = Object.values(optionCounts);

                const colors = labels.map((_, i) => {
                    const hue = (i * 137.508) % 360;
                    return `hsla(${hue}, 70%, 60%, 0.8)`;
                });

                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: dataValues,
                            backgroundColor: colors,
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: { padding: 15, usePointStyle: true }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            })();
        @endif
    @endforeach
}
</script>
@endsection
