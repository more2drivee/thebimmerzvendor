<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\App\Http\Controllers\CategoryController::class, 'update'], [$category->id]), 'method' => 'put', 'id' => 'category_form']) !!}
        
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('messages.edit_category')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('name', __('messages.category_name') . ':*') !!}
                {!! Form::text('name', $category->name, ['class' => 'form-control', 'required']) !!}
            </div>

            <div class="form-group">
                {!! Form::label('business_id', __('messages.business') . ':*') !!}
                {!! Form::select('business_id', $businesses, $category->business_id, ['class' => 'form-control select2', 'required']) !!}
            </div>

            <div class="form-group">
                {!! Form::label('category_type', __('messages.category_type') . ':') !!}
                {!! Form::text('category_type', $category->category_type, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">
                @lang('messages.close')
            </button>
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                @lang('messages.update')
            </button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
