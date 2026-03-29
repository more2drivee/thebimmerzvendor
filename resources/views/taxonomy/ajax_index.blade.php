@php
    $is_cat_code_enabled = isset($module_category_data['enable_taxonomy_code']) && !$module_category_data['enable_taxonomy_code'] ? false : true;
@endphp
{{-- @can('category.create')
	<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm pull-right btn-modal" data-href="{{action([\App\Http\Controllers\TaxonomyController::class, 'create'])}}?type={{$category_type}}" data-container=".category_modal">
		<i class="fa fa-plus"></i>
		@lang( 'messages.add' )
	</button>
	<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm pull-right btn-modal" data-href="{{action([\App\Http\Controllers\TaxonomyController::class, 'create'])}}?type={{$category_type}}" data-container=".category_modal">
		<form action="{{ route('categories.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" required>
            <button type="submit">Import Categories</button>
        </form>
        
	</button>
	<br><br>
@endcan --}}
@can('category.create')
    <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm pull-right btn-modal" 
        data-href="{{ action([\App\Http\Controllers\TaxonomyController::class, 'create']) }}?type={{ $category_type }}" 
        data-container=".category_modal">
        <i class="fa fa-plus"></i> @lang('messages.add')
    </button>

    <!-- Import Button -->
    <button type="button" class="mx-3 tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm pull-right" 
        data-toggle="modal" data-target="#importCategoryModal">
        <i class="fa fa-upload"></i> @lang('import')
    </button>
    <br><br>
@endcan

<!-- Import Modal -->
<div class="modal fade" id="importCategoryModal" tabindex="-1" role="dialog" aria-labelledby="importCategoryModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importCategoryModalLabel">@lang('messages.import_categories')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('categories.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="categoryFile">@lang('messages.select_file')</label>
                        <input type="file" name="file" id="categoryFile" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">@lang('messages.import')</button>
                </form>
            </div>
        </div>
    </div>
</div>

 @can('category.view')
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="category_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>@if(!empty($module_category_data['taxonomy_label'])) {{$module_category_data['taxonomy_label']}} @else @lang( 'category.category' ) @endif</th>
                    @if($is_cat_code_enabled)
                        <th>{{ $module_category_data['taxonomy_code_label'] ?? __( 'category.code' )}}</th>
                    @endif
                    <th>@lang( 'lang_v1.description' )</th>
                    <th>@lang( 'lang_v1.logo' )</th>
                    <th>@lang( 'messages.action' )</th>
                </tr>
            </thead>
        </table>
    </div>
@endcan

<div class="modal fade category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>