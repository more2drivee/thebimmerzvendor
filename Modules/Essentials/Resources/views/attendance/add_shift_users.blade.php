<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">
                @lang('essentials::lang.assign_users')
                ({{ $shift->name }}@if($shift->type == 'fixed_shift'): {{ @format_time($shift->start_time) }} - {{ @format_time($shift->end_time) }}@endif)
            </h4>
        </div>
        <div class="modal-body">
            {{-- Currently assigned users list --}}
            @if(!empty($user_shifts))
            <div class="well well-sm" style="margin-bottom:15px;">
                <strong>@lang('essentials::lang.currently_assigned'):</strong>
                <ul class="list-inline" style="margin-top:5px;">
                    @foreach($user_shifts as $uid => $udata)
                    <li><span class="label label-info">{{ $users[$uid] ?? $uid }}</span>
                        <small>{{ $udata['start_date'] ?? '' }}{{ !empty($udata['end_date']) ? ' - ' . $udata['end_date'] : '' }}</small>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('shift_location_filter', __('purchase.business_location') . ':') !!}
                        {!! Form::select('shift_location_filter', $locations, null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'id' => 'shift_location_filter', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('shift_user_select', __('report.user') . ':') !!}
                        <select id="shift_user_select" class="form-control select2" multiple="multiple" style="width:100%;">
                            @foreach($user_shifts as $uid => $udata)
                                <option value="{{ $uid }}" selected>{{ $users[$uid] ?? $uid }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">@lang('essentials::lang.search_and_select_employees')</small>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('shift_start_date_global', __('business.start_date') . ':') !!}
                        {!! Form::text('shift_start_date_global', null, ['class' => 'form-control', 'readonly', 'id' => 'shift_start_date_global', 'placeholder' => __('business.start_date')]); !!}
                        <small class="text-muted">@lang('essentials::lang.applies_to_all_selected')</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('shift_end_date_global', __('essentials::lang.end_date') . ':') !!}
                        {!! Form::text('shift_end_date_global', null, ['class' => 'form-control', 'readonly', 'id' => 'shift_end_date_global', 'placeholder' => __('essentials::lang.end_date')]); !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="save_shift_users_btn"
                data-shift-id="{{ $shift->id }}">
                @lang('messages.submit')
            </button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function() {
    $('#shift_user_select').select2({
        dropdownParent: $('#user_shift_modal'),
        ajax: {
            url: '/hrm/employees/search',
            dataType: 'json',
            delay: 250,
            data: function(params) { 
                return { 
                    q: params.term, 
                    page: params.page,
                    location_id: $('#shift_location_filter').val()
                }; 
            },
            processResults: function(data) { return { results: data.results, pagination: { more: data.more } }; },
            cache: true
        },
        placeholder: '{{ __("lang_v1.please_select") }}',
        minimumInputLength: 0,
    });

    $('#shift_location_filter').on('change', function() {
        $('#shift_user_select').val(null).trigger('change');
    });

    $('#shift_start_date_global, #shift_end_date_global').datetimepicker({
        format: moment_date_format,
        ignoreReadonly: true,
    });

    $('#save_shift_users_btn').on('click', function() {
        var shift_id   = $(this).data('shift-id');
        var user_ids   = $('#shift_user_select').val() || [];
        var start_date = $('#shift_start_date_global').val();
        var end_date   = $('#shift_end_date_global').val();

        var user_shift = {};
        $.each(user_ids, function(i, uid) {
            user_shift[uid] = { is_added: 1, start_date: start_date, end_date: end_date };
        });

        $(this).attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            method: 'POST',
            url: '/hrm/shift/assign-users',
            data: { shift_id: shift_id, user_shift: user_shift, _token: $('meta[name="csrf-token"]').attr('content') },
            dataType: 'json',
            success: function(result) {
                $('#save_shift_users_btn').attr('disabled', false).html('{{ __("messages.submit") }}');
                if (result.success) {
                    toastr.success(result.msg);
                    $('#user_shift_modal').modal('hide');
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
});
</script>
