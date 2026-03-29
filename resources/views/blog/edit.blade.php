<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\BlogController::class, 'update'], [$blog->id]), 'method' => 'put', 'id' => 'blog_edit_form', 'files' => true ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'blog.edit_blog' )</h4>
    </div>

    <div class="modal-body">
      <!-- EN/AR Tabs -->
      <ul class="nav nav-tabs" role="tablist">
        <li class="active"><a href="#en-content" data-toggle="tab">English</a></li>
        <li><a href="#ar-content" data-toggle="tab">Arabic</a></li>
      </ul>

      <div class="tab-content">
        <!-- English Tab -->
        <div class="tab-pane active" id="en-content">
          <div class="form-group">
            {!! Form::label('title', __( 'blog.title' ) . ':*') !!}
              {!! Form::text('title', $blog->title, ['class' => 'form-control', 'required', 'placeholder' => __( 'blog.title' ) ]); !!}
          </div>

          <div class="form-group">
            {!! Form::label('content', __( 'blog.content' ) . ':') !!}
              {!! Form::textarea('content', $blog->content, ['class' => 'form-control ckeditor', 'placeholder' => __( 'blog.content' ), 'id' => 'blog_content', 'rows' => 10]); !!}
          </div>
        </div>

        <!-- Arabic Tab -->
        <div class="tab-pane" id="ar-content">
          <div class="form-group">
            {!! Form::label('title_ar', __( 'blog.title_ar' ) . ':') !!}
              {!! Form::text('title_ar', $blog->title_ar, ['class' => 'form-control', 'placeholder' => __( 'blog.title_ar' )]); !!}
          </div>

          <div class="form-group">
            {!! Form::label('content_ar', __( 'blog.content_ar' ) . ':') !!}
              {!! Form::textarea('content_ar', $blog->content_ar, ['class' => 'form-control', 'placeholder' => __( 'blog.content_ar' ), 'id' => 'blog_content_ar', 'rows' => 10]); !!}
          </div>
        </div>
      </div>

      <!-- Common fields -->
      <div class="form-group" style="margin-top: 15px;">
        {!! Form::label('blog_date', __( 'blog.blog_date' ) . ':') !!}
          {!! Form::date('blog_date', $blog->blog_date, ['class' => 'form-control']); !!}
      </div>

      <div class="form-group">
        {!! Form::label('status', __( 'blog.status' ) . ':*') !!}
          {!! Form::select('status', ['draft' => __('blog.draft'), 'published' => __('blog.published')], $blog->status, ['class' => 'form-control', 'required']); !!}
      </div>

      <div class="form-group">
        {!! Form::label('category_id', __('product.category') . ':') !!}
        <div class="input-group">
          {!! Form::select('category_id', $categories, $blog->category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'id' => 'category_id']) !!}
          <span class="input-group-btn">
            <button type="button" class="btn btn-default bg-white btn-flat btn-modal" data-href="{{ action([\App\Http\Controllers\BlogController::class, 'createCategory']) }}" data-container=".category_modal" title="@lang('category.add_category')">
              <i class="fa fa-plus-circle text-primary fa-lg"></i>
            </button>
          </span>
        </div>
      </div>

      <div class="form-group">
        {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
        {!! Form::select('sub_category_id', $sub_categories ?? [], $blog->sub_category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'id' => 'sub_category_id']) !!}
      </div>

      <div class="form-group">
        {!! Form::label('image', __( 'blog.image' ) . ':') !!}
        {!! Form::file('image', ['accept' => 'image/*']); !!}
        <p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])</p>
        @if($blog->image)
            <img src="{{ asset('storage/' . $blog->image) }}" width="50px" height="50px">
        @endif
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
