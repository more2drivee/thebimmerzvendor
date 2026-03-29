<div class="modal-content">
  {!! Form::open(['url' => action([\App\Http\Controllers\BlogController::class, 'storeCategory']), 'method' => 'post', 'id' => 'blog_category_form']) !!}

  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title">@lang('category.add_category')</h4>
  </div>

  <div class="modal-body">
    <div class="form-group">
      {!! Form::label('name', __('category.name') . ':*') !!}
      {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('category.name')]) !!}
    </div>

    <div class="form-group">
      {!! Form::label('short_code', __('category.short_code') . ':') !!}
      {!! Form::text('short_code', null, ['class' => 'form-control', 'placeholder' => __('category.short_code')]) !!}
    </div>

    <div class="form-group">
      {!! Form::label('description', __('category.description') . ':') !!}
      {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('category.description')]) !!}
    </div>

    <div class="form-group">
      {!! Form::label('parent_id', __('category.parent_category') . ':') !!}
      {!! Form::select('parent_id', $parent_categories, null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']) !!}
    </div>
  </div>

  <div class="modal-footer">
    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
  </div>

  {!! Form::close() !!}
</div>

<script>
$(document).ready(function() {
  $('#blog_category_form').submit(function(e) {
    e.preventDefault();
    var form = $(this);
    var url = form.attr('action');
    var method = form.attr('method');
    var formData = new FormData(this);

    $.ajax({
      url: url,
      type: method,
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response.success) {
          $('.category_modal').modal('hide');
          toastr.success(response.msg);

          // Refresh the category dropdowns
          var business_id = {{ request()->session()->get('user.business_id') }};
          $.get('/blogs/get-categories', { business_id: business_id }, function(data) {
            $('#category_id').html(data.categories).trigger('change');
          });
        } else {
          toastr.error(response.msg);
        }
      },
      error: function(xhr) {
        var errors = xhr.responseJSON.errors;
        $.each(errors, function(key, value) {
          toastr.error(value[0]);
        });
      }
    });
  });
});
</script>
