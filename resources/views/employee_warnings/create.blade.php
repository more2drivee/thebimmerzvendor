<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action('\App\Http\Controllers\EmployeeWarningController@store'), 'method' => 'post', 'id' => 'add_warning_form']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('essentials::lang.add_warning')</h4>
    </div>
    <div class="modal-body">
      <div class="row">
        <div class="form-group col-md-12">
          {!! Form::label('user_id', __('essentials::lang.select_employee') . ':*') !!}
          {!! Form::select('user_id', $employees, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]); !!}
        </div>
        <div class="form-group col-md-12">
          {!! Form::label('warning_type', __('essentials::lang.warning_type') . ':*') !!}
          {!! Form::select('warning_type', ['verbal' => __('essentials::lang.warning_verbal'), 'written' => __('essentials::lang.warning_written'), 'final' => __('essentials::lang.warning_final')], null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]); !!}
        </div>
        <div class="form-group col-md-12">
          {!! Form::label('warning_date', __('essentials::lang.warning_date') . ':') !!}
          {!! Form::text('warning_date', date('Y-m-d'), ['class' => 'form-control', 'readonly']); !!}
        </div>
        <div class="form-group col-md-12">
          {!! Form::label('reason', __('essentials::lang.reason') . ':*') !!}
          {!! Form::textarea('reason', null, ['class' => 'form-control', 'rows' => 3, 'required']); !!}
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white ladda-button" data-style="expand-right">
        <span class="ladda-label">@lang('messages.save')</span>
      </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
