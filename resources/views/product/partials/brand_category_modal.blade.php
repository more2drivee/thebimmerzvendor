<div class="modal fade" id="brand_category_modal" tabindex="-1" role="dialog" aria-labelledby="brandCategoryModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="brandCategoryModalLabel">@lang('lang_v1.brand_category')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="brand_category_table">
                                <thead>
                                    <tr>
                                        <th>@lang('messages.action')</th>
                                        <th>@lang('lang_v1.brand_category')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($brand_category as $id => $category)
                                        <tr>
                                            <td>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" class="brand-category-checkbox" value="{{ $id }}">
                                                    </label>
                                                </div>
                                            </td>
                                            <td>{{ $category }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="btn btn-primary" id="add_selected_brand_categories">@lang('messages.save')</button>
            </div>
        </div>
    </div>
</div>
