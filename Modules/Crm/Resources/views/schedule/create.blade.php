<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\Modules\Crm\Http\Controllers\ScheduleController::class, 'store']), 'method' => 'post', 'id' => 'add_schedule' ]) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">
                    @lang('crm::lang.add_schedule')
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <input type="hidden" name="schedule_for" value="{{$schedule_for}}" id="schedule_for">
                    <div class="col-md-8">
                       <div class="form-group">
                            {!! Form::label('title', __('crm::lang.title') . ':*' )!!}
                            {!! Form::text('title', null, ['class' => 'form-control', 'required' ]) !!}
                       </div>
                    </div>
                    <div class="col-md-4">
                       <div class="form-group">
                            {!! Form::label('contact_id', __('contact.customer') . '/' . __('crm::lang.lead') .':*') !!}
                            {!! Form::select('contact_id', $customers, $contact_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width: 100%;']); !!}
                       </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                       <div class="form-group">
                            {!! Form::label('status', __('sale.status') .':') !!}
                            {!! Form::select('status', $statuses, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;', 'id' => 'follow_up_create_status']); !!}
                       </div>
                    </div>
                    <div class="col-md-4">
                       <div class="form-group">
                            {!! Form::label('start_datetime', __('crm::lang.start_datetime') . ':*' )!!}
                            {!! Form::text('start_datetime', null, ['class' => 'form-control datetimepicker', 'required', 'readonly']) !!}
                       </div>
                    </div>
                    <div class="col-md-4">
                       <div class="form-group">
                            {!! Form::label('end_datetime', __('crm::lang.end_datetime') . ':*' )!!}
                            {!! Form::text('end_datetime', null, ['class' => 'form-control datetimepicker', 'required', 'readonly']) !!}
                       </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('description', __('crm::lang.description') . ':') !!}
                            {!! Form::textarea('description', null, ['class' => 'form-control ', 'id' => 'description']); !!}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('schedule_type', __('crm::lang.schedule_type') .':*') !!}
                            {!! Form::select('schedule_type', $follow_up_types, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('followup_category_id', __('crm::lang.followup_category') .':*') !!}
                            {!! Form::select('followup_category_id', $followup_category, null, ['class' => 'form-control select2', 'required', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]); !!}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('user_id', __('crm::lang.assgined') .':*') !!}
                            {!! Form::select('user_id[]', $users, null, ['class' => 'form-control select2', 'multiple', 'required', 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>
                </div>
                <div class="row">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">
                @lang('messages.close')
                </button>
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                    @lang('messages.save')
                </button>
            </div>
        {!! Form::close() !!}
    </div>
</div>
