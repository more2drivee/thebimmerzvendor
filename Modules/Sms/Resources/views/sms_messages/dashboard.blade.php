@extends('layouts.app')

@section('title', __('sms::lang.sms_dashboard'))

@section('content')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fas fa-chart-line"></i> @lang('sms::lang.sms_dashboard')
    </h1>
</section>

<!-- Main navbar -->
@include('sms::layouts.navbar')

<section class="content no-print">
    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fas fa-envelope"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('sms::lang.total_messages')</span>

                    <span class="info-box-number">{{ $totalMessages }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fas fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('sms::lang.total_sent')</span>

                    <span class="info-box-number">{{ $totalSent }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fas fa-times"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('sms::lang.total_failed')</span>

                    <span class="info-box-number">{{ $totalFailed }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fas fa-coins"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('sms::lang.available_credit')</span>

                    <span class="info-box-number">{{ number_format($balance, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('sms::lang.active_sms_messages')])

            <div class="table-responsive">
                <table id="messages-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>@lang('sms::lang.id')</th>
                            <th>@lang('sms::lang.name')</th>
                            <th>@lang('sms::lang.template')</th>
                            <th>@lang('sms::lang.roles')</th>
                            <th>@lang('sms::lang.status')</th>
                        </tr>
                    </thead>

                    <tbody></tbody>
                </table>
            </div>
            @endcomponent
        </div>

        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('sms::lang.recent_sms_logs')])

            <div class="table-responsive">
                <table id="sms-logs-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>@lang('sms::lang.id')</th>
                            <th>@lang('sms::lang.message')</th>
                            <th>@lang('sms::lang.message_content')</th>
                            <th>@lang('sms::lang.contact')</th>
                            <th>@lang('sms::lang.mobile')</th>
                            <th>@lang('sms::lang.status')</th>
                            <th>@lang('sms::lang.sent_at')</th>
                        </tr>
                    </thead>

                    <tbody></tbody>
                </table>
            </div>
            @endcomponent
        </div>
    </div>
</section>

<!-- Message View Modal -->
<div class="modal fade" id="viewMessageModal" tabindex="-1" role="dialog" aria-labelledby="viewMessageModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="viewMessageModalLabel">@lang('sms::lang.message_details')</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><strong>@lang('sms::lang.id'):</strong></label>
                    <p id="modalMessageId"></p>
                </div>
                <div class="form-group">
                    <label><strong>@lang('sms::lang.message_content'):</strong></label>
                    <p id="modalMessageContent" class="well"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('sms::lang.close')</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
    $(function() {
        // Messages table (reuse existing data endpoint)
        $('#messages-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('sms.messages.data') }}",
                type: 'GET'
            },
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'message_template',
                    name: 'message_template',
                    render: function(data) {
                        return data.length > 60 ? data.substring(0, 60) + '...' : data;
                    }
                },
                {
                    data: 'roles',
                    name: 'roles',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false
                }
            ],
            pageLength: 5
        });

        // SMS logs table
        $('#sms-logs-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('sms.messages.logs-data') }}",
                type: 'GET'
            },
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'message_name',
                    name: 'message_name'
                },
                {
                    data: 'message_content',
                    name: 'message_content'
                },
                {
                    data: 'contact_name',
                    name: 'contact_name'
                },
                {
                    data: 'contact_mobile',
                    name: 'contact_mobile'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'sent_at',
                    name: 'sent_at'
                }
            ],
            order: [
                [6, 'desc']
            ],
            pageLength: 10
        });
    });

    // Show message modal
    $(document).on('click', '.btn-show-message', function() {
        var message = $(this).data('message');
        var id = $(this).data('id');
        
        $('#modalMessageId').text(id);
        $('#modalMessageContent').text(message);
        
        $('#viewMessageModal').modal('show');
    });
</script>
@endsection
