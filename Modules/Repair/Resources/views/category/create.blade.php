<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\App\Http\Controllers\CategoryController::class, 'store']), 'method' => 'post', 'id' => 'category_form']) !!}
        
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('messages.add_category')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('name', __('messages.category_name') . ':*') !!}
                {!! Form::text('name', null, ['class' => 'form-control', 'required']) !!}
            </div>

            <div class="form-group">
                {!! Form::label('business_id', __('messages.business') . ':*') !!}
                {!! Form::select('business_id', $businesses, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']) !!}
            </div>

            <div class="form-group">
                {!! Form::label('category_type', __('messages.category_type') . ':') !!}
                {!! Form::text('category_type', null, ['class' => 'form-control']) !!}
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
