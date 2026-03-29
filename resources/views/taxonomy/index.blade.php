@extends('layouts.app')
@php
    $heading = !empty($module_category_data['heading']) ? $module_category_data['heading'] : __('category.categories');
    $navbar = !empty($module_category_data['navbar']) ? $module_category_data['navbar'] : null;
@endphp
@section('title', $heading)

@section('content')
    @if (!empty($navbar))
        @include($navbar)
    @endif
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black" >{{ $heading }}
            <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold" >
                {{ $module_category_data['sub_heading'] ?? __('category.manage_your_categories') }}
            </small>
            @if (isset($module_category_data['heading_tooltip']))
                @show_tooltip($module_category_data['heading_tooltip'])
            @endif
        </h1>
        <!-- <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
            <li class="active">Here</li>
        </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">
        @php
            $cat_code_enabled =
                isset($module_category_data['enable_taxonomy_code']) && !$module_category_data['enable_taxonomy_code']
                    ? false
                    : true;
        @endphp
        <input type="hidden" id="category_type" value="{{ request()->get('type') }}">
        @php
            $can_add = true;
            if (request()->get('type') == 'product' && !auth()->user()->can('category.create')) {
                $can_add = false;
            }
        @endphp
        @component('components.widget', ['class' => 'box-solid', 'can_add' => $can_add])
            @if ($can_add)
                @slot('tool')
                    <div class="box-tools">
                        {{-- <button type="button" class="btn btn-block btn-primary btn-modal" 
                    data-href="{{action([\App\Http\Controllers\TaxonomyController::class, 'create'])}}?type={{request()->get('type')}}" 
                    data-container=".category_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button> --}}
                        <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal"
                            data-href="{{action([\App\Http\Controllers\TaxonomyController::class, 'create'])}}?type={{request()->get('type')}}" 
                            data-container=".category_modal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg> @lang('messages.add')
                        </a>
                        <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-emerald-600 tw-to-teal-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full"
                            data-toggle="modal" data-target="#importCategoryModal">
                            <i class="fa fa-upload"></i> @lang('import')
                        </button>
                    </div>
                @endslot
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="category_table">
                    <thead>
                        <tr>
                            <th>
                                @if (!empty($module_category_data['taxonomy_label']))
                                    {{ $module_category_data['taxonomy_label'] }}
                                @else
                                    @lang('category.category')
                                @endif
                            </th>
                            @if ($cat_code_enabled)
                                <th>{{ $module_category_data['taxonomy_code_label'] ?? __('category.code') }}</th>
                            @endif
                            <th>@lang('lang_v1.description')</th>
                            <th>@lang('lang_v1.logo')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent

        <div class="modal fade category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

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
                            <input type="hidden" name="category_type" value="{{ request()->get('type', 'product') }}">
                            <div class="form-group">
                                <a href="{{ asset('files/import_categories_template.csv') }}" class="btn btn-success" download>
                                    <i class="fa fa-download"></i> @lang('lang_v1.download_template_file')
                                </a>
                            </div>
                            <div class="form-group">
                                <label for="categoryFile">@lang('messages.select_file')</label>
                                <input type="file" name="file" id="categoryFile" class="form-control" accept=".xls,.xlsx,.csv" required>
                                <small class="text-muted">
                                    @lang('category.category') / @lang('product.sub_category'): @lang('lang_v1.separate_with_commas')
                                </small>
                            </div>
                            <div class="well well-sm">
                                <strong>@lang('lang_v1.instructions'):</strong>
                                <ul class="mb-0">
                                    <li>@lang('category.category') (Column A)</li>
                                    <li>@lang('product.sub_category') (Column B): multiple values separated by comma, semicolon, or new line</li>
                                    <li>@lang('lang_v1.sub_category') 2 (Column C)</li>
                                    <li>@lang('lang_v1.sub_category') 3 (Column D)</li>
                                </ul>
                                <div class="mt-5">
                                    <table class="table table-condensed">
                                        <thead>
                                            <tr>
                                                <th>@lang('category.category')</th>
                                                <th>@lang('product.sub_category')</th>
                                                <th>@lang('lang_v1.sub_category') 2</th>
                                                <th>@lang('lang_v1.sub_category') 3</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Oils</td>
                                                <td>Engine, Gear, Brake</td>
                                                <td>Heavy Duty</td>
                                                <td>Premium</td>
                                            </tr>
                                            <tr>
                                                <td>Filters</td>
                                                <td>Air; Fuel</td>
                                                <td>Cabin</td>
                                                <td>Carbon</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">@lang('messages.import')</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </section>
    <!-- /.content -->
@stop
@section('javascript')
    @includeIf('taxonomy.taxonomies_js')
@endsection
