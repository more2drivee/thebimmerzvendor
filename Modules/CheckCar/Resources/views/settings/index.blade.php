@extends('layouts.app')

@section('title', __('checkcar::lang.menu_check_car'))

@section('content')
@include('checkcar::layouts.nav')

<style>
.settings-sidebar {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}
.settings-sidebar .nav-link {
    color: #333;
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 5px;
    transition: all 0.3s;
}
.settings-sidebar .nav-link:hover {
    background: #e9ecef;
    color: #007bff;
}
.settings-sidebar .nav-link.active {
    background: #007bff;
    color: white;
}
.settings-content {
    display: none;
}
.settings-content.active {
    display: block;
}
</style>

<!-- <section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('checkcar::lang.menu_check_car') - {{ __('messages.settings') }}
    </h1>
    @if(!empty($locations) && $locations->count())
        <div class="tw-mt-2">
            <strong>{{ __('business.location') }}:</strong>
            <select id="checkcar_location_switcher" class="form-control input-sm" style="display:inline-block; width:auto; margin-right:10px;">
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" {{ (int)$loc->id === (int)($locationId ?? 0) ? 'selected' : '' }}>
                        {{ $loc->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif
</section> -->

<section class="content no-print">
    @if (session('status'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('status') }}
        </div>
    @endif

    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3">
            <div class="settings-sidebar">
                <h4 class="mb-3">{{ __('checkcar::lang.navigation') }}</h4>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#" data-target="categories">
                        <i class="fa fa-tags"></i> {{ __('checkcar::lang.question_categories_title') }}
                    </a>
                    <a class="nav-link" href="#" data-target="subcategories">
                        <i class="fa fa-sitemap"></i> {{ __('checkcar::lang.question_subcategories_title') }}
                    </a>
                    <a class="nav-link" href="#" data-target="elements">
                        <i class="fa fa-th-large"></i> {{ __('checkcar::lang.elements') }}
                    </a>
                    <a class="nav-link" href="#" data-target="services">
                        <i class="fa fa-cog"></i> {{ __('checkcar::lang.services') }}
                    </a>
                    <a class="nav-link" href="#" data-target="privacy">
                        <i class="fa fa-shield"></i> {{ __('checkcar::lang.privacy_policy') }}
                    </a>
                    <a class="nav-link" href="#" data-target="templates">
                        <i class="fa fa-file-text"></i> {{ __('checkcar::lang.phrase_templates_title') }}
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Categories Section -->
            <div id="categories" class="settings-content active">
                <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                        <h3 class="tw-font-semibold tw-text-lg tw-m-0">
                            <i class="fa fa-list"></i> {{ __('checkcar::lang.categories_management') }}
                        </h3>
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addCategoryModal">
                            <i class="fa fa-plus"></i> {{ __('messages.add') }}
                        </button>
                    </div>

                    <!-- Search/Filter -->
                    <div class="tw-mb-4">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                            <input type="text" id="categoriesSearch" class="form-control" placeholder="{{ __('messages.search') }} {{ __('checkcar::lang.category_name') }}...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="categoriesTable">
                            <thead class="tw-bg-gray-50">
                                <tr>
                                    <th width="60" class="tw-text-center">{{ __('messages.id') }}</th>
                                    <th>{{ __('checkcar::lang.category_name') }}</th>
                                    <th width="120" class="tw-text-center">{{ __('business.location') }}</th>
                                    <th width="60" class="tw-text-center">{{ __('checkcar::lang.sort') }}</th>
                                    <th width="80" class="tw-text-center">{{ __('messages.active') }}</th>
                                    <th width="140" class="tw-text-center">{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $category)
                                    <tr>
                                        <td class="tw-text-center tw-font-semibold">{{ $category->id }}</td>
                                        <td>
                                            <span class="tw-font-medium">{{ $category->name }}</span>
                                        </td>
                                        <td class="tw-text-center">
                                            @if(is_null($category->location_id))
                                                <span class="badge badge-secondary">{{ __('lang_v1.all') }}</span>
                                            @else
                                                @php $locName = optional($locationNames)[$category->location_id] ?? null; @endphp
                                                <span class="badge badge-info">{{ $locName ?: $category->location_id }}</span>
                                            @endif
                                        </td>
                                        <td class="tw-text-center">
                                            <span class="badge badge-secondary">{{ $category->sort_order }}</span>
                                        </td>
                                        <td class="tw-text-center">
                                            @if($category->active)
                                                <span class="badge badge-success">{{ __('messages.active') }}</span>
                                            @else
                                                <span class="badge badge-warning">{{ __('messages.inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="tw-text-center">
                                            <button type="button" class="btn btn-xs btn-info js-edit-category" data-toggle="modal" 
                                                data-target="#editCategoryModal" 
                                                data-id="{{ $category->id }}"
                                                data-name="{{ htmlentities($category->name, ENT_QUOTES, 'UTF-8') }}"
                                                data-sort-order="{{ $category->sort_order ?? 0 }}"
                                                data-active="{{ $category->active ? 'true' : 'false' }}"
                                                data-location-id="{{ $category->location_id }}">
                                                <i class="fa fa-edit"></i> {{ __('messages.edit') }}
                                            </button>
                                            <button type="button" class="btn btn-xs btn-danger js-delete-category" 
                                                data-id="{{ $category->id }}">
                                                <i class="fa fa-trash"></i> {{ __('messages.delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Service Setting Modal -->
            <div class="modal fade" id="addServiceSettingModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header tw-bg-blue-50">
                            <h5 class="modal-title">
                                <i class="fa fa-plus-circle"></i> {{ __('messages.add') }} {{ __('messages.service') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="addServiceSettingForm" method="POST" action="{{ route('checkcar.settings.services.select') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="service_product_id">{{ __('messages.service') }} <span class="text-danger">*</span></label>
                                    <select name="product_id" id="service_product_id" class="form-control">
                                        <option value="">{{ __('messages.please_select') }}</option>
                                        @foreach($serviceProducts as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="service_type">{{ __('checkcar::lang.type') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="type" id="service_type" class="form-control" value="service" >
                                </div>
                                <div class="form-group">
                                    <label for="service_value">{{ __('checkcar::lang.value') }}</label>
                                    <input type="text" name="value" id="service_value" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="service_watermark_image">{{ __('messages.watermark_image') }}</label>
                                    <input type="file" name="watermark_image" id="service_watermark_image" class="form-control" accept="image/*">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                <button type="submit" class="btn btn-primary js-submit-form">
                                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Service Setting Modal -->
            <div class="modal fade" id="editServiceSettingModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header tw-bg-blue-50">
                            <h5 class="modal-title">
                                <i class="fa fa-edit"></i> {{ __('messages.edit') }} {{ __('messages.service') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="editServiceSettingForm" method="POST" action="" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="service_id" id="edit_service_id">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="edit_service_product_id">{{ __('messages.service') }}</label>
                                    <select name="product_id" id="edit_service_product_id" class="form-control">
                                        <option value="">{{ __('messages.please_select') }}</option>
                                        @foreach($serviceProducts as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit_service_type">{{ __('checkcar::lang.type') }}</label>
                                    <input type="text" name="type" id="edit_service_type" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="edit_service_value">{{ __('checkcar::lang.value') }}</label>
                                    <input type="text" name="value" id="edit_service_value" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="edit_service_watermark_image">{{ __('messages.watermark_image') }}</label>
                                    <input type="file" name="watermark_image" id="edit_service_watermark_image" class="form-control" accept="image/*">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                <button type="submit" class="btn btn-primary js-submit-form">
                                    <i class="fa fa-save"></i> {{ __('messages.update') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Services Section -->
            <div id="services" class="settings-content">
                <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                        <h3 class="tw-font-semibold tw-text-lg tw-m-0">
                            <i class="fa fa-cog"></i> {{ __('checkcar::lang.services') }}
                        </h3>
                        <button type="button" class="btn btn-primary btn-sm" id="btnAddServiceSetting">
                            <i class="fa fa-plus"></i> {{ __('messages.add') }}
                        </button>
                    </div>

                    @if(!empty($serviceSetting) && $serviceSetting->product)
                        <div class="alert alert-info tw-mb-4">
                            <strong>{{ __('checkcar::lang.selected_service') }}:</strong>
                            <span class="tw-ml-2">
                                {{ $serviceSetting->product->name }} ({{ $serviceSetting->product->sku }})
                                @if(!empty($serviceSetting->type))
                                    <span class="badge badge-info tw-ml-2">{{ $serviceSetting->type }}</span>
                                @endif
                            </span>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="servicesTable" style="width: 100% !important;">
                            <thead class="tw-bg-gray-50">
                                <tr>
                                    <th>{{ __('messages.name') }}</th>
                                    <th>{{ __('checkcar::lang.type') }}</th>
                                    <th>{{ __('checkcar::lang.value') }}</th>
                                    <th>{{ __('messages.watermark') }}</th>
                                    <th width="140" class="tw-text-center">{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Privacy Policy Section -->
            <div id="privacy" class="settings-content">
                <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                        <h3 class="tw-font-semibold tw-text-lg tw-m-0">
                            <i class="fa fa-shield"></i> {{ __('checkcar::lang.privacy_policy') }}
                        </h3>
                    </div>

                    <form method="POST" action="{{ route('checkcar.settings.privacy.update') }}">
                        @csrf
                        <div class="form-group">
                            <textarea
                                id="checkcar_privacy_policy"
                                name="privacy_policy"
                                class="form-control"
                                rows="8"
                            >{{ old('privacy_policy', optional($privacyPolicy)->content) }}</textarea>
                        </div>
                        <div class="tw-flex tw-justify-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> {{ __('messages.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Category Modal -->
            <div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header tw-bg-blue-50">
                            <h5 class="modal-title">
                                <i class="fa fa-plus-circle"></i> {{ __('checkcar::lang.add_category') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="addCategoryForm" method="POST" action="{{ route('checkcar.settings.categories.store') }}">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="new_category_name">{{ __('checkcar::lang.category_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="new_category_name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="new_category_sort_order">Sort Order</label>
                                    <input type="number" name="sort_order" id="new_category_sort_order" class="form-control" value="0" min="0">
                                </div>
                                @if(!empty($isAdmin) && $isAdmin && !empty($locations) && $locations->count())
                                    <div class="form-group">
                                        <label for="new_category_location_id">{{ __('business.location') }}</label>
                                        <select name="location_id" id="new_category_location_id" class="form-control">
                                            <option value="">{{ __('lang_v1.all') }}</option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="active" id="new_category_active" class="custom-control-input" checked>
                                        <label class="custom-control-label" for="new_category_active">
                                            {{ __('messages.active') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                <button type="submit" class="btn btn-primary js-submit-form">
                                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Element Modal -->
            <div class="modal fade" id="editElementModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header tw-bg-blue-50">
                            <h5 class="modal-title">
                                <i class="fa fa-edit"></i> Edit Element
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="editElementForm" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <div class="row">
                                    <!-- Left Column: Element Details -->
                                    <div class="col-md-6">
                                        <h5 class="tw-font-semibold tw-mb-3"><i class="fa fa-info-circle"></i> {{ __('checkcar::lang.element_details') }}</h5>
                                        <div class="form-group">
                                            <label for="edit_element_name">{{ __('checkcar::lang.element_name') }} <span class="text-danger">*</span></label>
                                            <input type="text" name="name" id="edit_element_name" class="form-control" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="edit_element_type">{{ __('checkcar::lang.element_type') }} <span class="text-danger">*</span></label>
                                                    <select name="type" id="edit_element_type" class="form-control" required>
                                                        <option value="single">{{ __('checkcar::lang.element_type_single') }}</option>
                                                        <option value="multiple">{{ __('checkcar::lang.element_type_multiple') }}</option>
                                                        <option value="text">{{ __('checkcar::lang.element_type_text') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="edit_element_sort_order">{{ __('checkcar::lang.sort_order') }}</label>
                                                    <input type="number" name="sort_order" id="edit_element_sort_order" class="form-control" value="0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_element_category">{{ __('checkcar::lang.category_name') }}</label>
                                            <select name="category_id" id="edit_element_category" class="form-control">
                                                <option value="">---</option>
                                                @foreach ($categories as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_element_subcategory">{{ __('checkcar::lang.subcategory_name') }}</label>
                                            <select name="subcategory_id" id="edit_element_subcategory" class="form-control">
                                                <option value="">---</option>
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="edit_element_max_options">{{ __('checkcar::lang.max_options') }}</label>
                                                    <input type="number" name="max_options" id="edit_element_max_options" class="form-control" value="0" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <div class="checkbox">
                                                        <label>
                                                            <input type="checkbox" name="required" id="edit_element_required" value="1"> {{ __('checkcar::lang.required') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <div class="checkbox">
                                                        <label>
                                                            <input type="checkbox" name="active" id="edit_element_active" value="1"> {{ __('checkcar::lang.active') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @if(!empty($isAdmin) && $isAdmin && !empty($locations) && $locations->count())
                                            <div class="form-group">
                                                <label for="edit_element_location_id">{{ __('business.location') }}</label>
                                                <select name="location_id" id="edit_element_location_id" class="form-control">
                                                    <option value="">{{ __('lang_v1.all') }}</option>
                                                    @foreach($locations as $loc)
                                                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Right Column: Element Options & Phrase Templates side by side -->
                                    <div class="col-md-6">
                                        <div style="border-left: 1px solid #dee2e6; padding-left: 15px;">
                                            <div class="row">
                                                <!-- Element Options -->
                                                <div class="col-md-6">
                                                    <h5 class="tw-font-semibold tw-mb-3"><i class="fa fa-list"></i> {{ __('checkcar::lang.element_options') }}</h5>
                                                    <div id="editElementOptionsContainer" style="max-height: 250px; overflow-y: auto;">
                                                        <!-- Options will be loaded dynamically -->
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-info mt-2" onclick="addEditOption()">
                                                        <i class="fa fa-plus"></i> {{ __('checkcar::lang.add_option') }}
                                                    </button>
                                                </div>
                                                
                                                <!-- Phrase Templates -->
                                                <div class="col-md-6">
                                                    <h5 class="tw-font-semibold tw-mb-3"><i class="fa fa-comment"></i> {{ __('checkcar::lang.phrase_templates') }}</h5>
                                                    <div id="editPhraseTemplatesContainer" style="max-height: 250px; overflow-y: auto;">
                                                        <!-- Phrase templates will be loaded dynamically -->
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-success mt-2" onclick="addEditPhraseTemplate()">
                                                        <i class="fa fa-plus"></i> {{ __('checkcar::lang.add_phrase') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                <button type="submit" class="btn btn-primary js-submit-form">
                                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Category Modal -->
            <div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header tw-bg-blue-50">
                            <h5 class="modal-title">
                                <i class="fa fa-edit"></i> {{ __('checkcar::lang.edit_category') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="editCategoryForm" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="edit_category_name">{{ __('checkcar::lang.category_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="edit_category_name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_category_sort_order">Sort Order</label>
                                    <input type="number" name="sort_order" id="edit_category_sort_order" class="form-control" value="0" min="0">
                                </div>
                                @if(!empty($isAdmin) && $isAdmin && !empty($locations) && $locations->count())
                                    <div class="form-group">
                                        <label for="edit_category_location_id">{{ __('business.location') }}</label>
                                        <select name="location_id" id="edit_category_location_id" class="form-control">
                                            <option value="">{{ __('lang_v1.all') }}</option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="active" id="edit_category_active" class="custom-control-input" checked>
                                        <label class="custom-control-label" for="edit_category_active">
                                            {{ __('messages.active') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                <button type="submit" class="btn btn-primary js-submit-form">
                                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Subcategory Modal -->
            <div class="modal fade" id="addSubcategoryModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header tw-bg-blue-50">
                            <h5 class="modal-title">
                                <i class="fa fa-plus-circle"></i> {{ __('checkcar::lang.add_subcategory') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="addSubcategoryForm" method="POST" action="{{ route('checkcar.settings.subcategories.store') }}">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="new_subcategory_category">{{ __('checkcar::lang.category_name') }} <span class="text-danger">*</span></label>
                                    <select name="category_id" id="new_subcategory_category" class="form-control" required>
                                        <option value="">{{ __('messages.select_category') }}</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="new_subcategory_name">{{ __('checkcar::lang.subcategory_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="new_subcategory_name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="new_subcategory_sort_order">Sort Order</label>
                                    <input type="number" name="sort_order" id="new_subcategory_sort_order" class="form-control" value="0" min="0">
                                </div>
                                @if(!empty($isAdmin) && $isAdmin && !empty($locations) && $locations->count())
                                    <div class="form-group">
                                        <label for="new_subcategory_location_id">{{ __('business.location') }}</label>
                                        <select name="location_id" id="new_subcategory_location_id" class="form-control">
                                            <option value="">{{ __('lang_v1.all') }}</option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="active" id="new_subcategory_active" class="custom-control-input" checked>
                                        <label class="custom-control-label" for="new_subcategory_active">
                                            {{ __('messages.active') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                <button type="submit" class="btn btn-primary js-submit-form">
                                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            
            <!-- Subcategories Section -->
            <div id="subcategories" class="settings-content">
                <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                        <h3 class="tw-font-semibold tw-text-lg tw-m-0">
                            <i class="fa fa-sitemap"></i> {{ __('checkcar::lang.subcategories_management') }}
                        </h3>
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addSubcategoryModal">
                            <i class="fa fa-plus"></i> {{ __('messages.add') }}
                        </button>
                    </div>

                    <!-- Search/Filter -->
                    <div class="tw-mb-4">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                            <input type="text" id="subcategoriesSearch" class="form-control" placeholder="{{ __('messages.search') }} {{ __('checkcar::lang.subcategory_name') }}...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="subcategoriesTable">
                            <thead class="tw-bg-gray-50">
                                <tr>
                                    <th width="60" class="tw-text-center">{{ __('messages.id') }}</th>
                                    <th>{{ __('checkcar::lang.category_name') }}</th>
                                    <th>{{ __('checkcar::lang.subcategory_name') }}</th>
                                    <th width="120" class="tw-text-center">{{ __('business.location') }}</th>
                                    <th width="60" class="tw-text-center">Sort</th>
                                    <th width="80" class="tw-text-center">{{ __('messages.active') }}</th>
                                    <th width="140" class="tw-text-center">{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subcategories as $subcat)
                                    <tr>
                                        <td class="tw-text-center tw-font-semibold">{{ $subcat->id }}</td>
                                        <td>
                                            <span class="tw-font-medium">
                                                {{ $subcat->category->name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="tw-font-medium">{{ $subcat->name }}</span>
                                        </td>
                                        <td class="tw-text-center">
                                            @if(is_null($subcat->location_id))
                                                <span class="badge badge-secondary">{{ __('lang_v1.all') }}</span>
                                            @else
                                                @php $locName = optional($locationNames)[$subcat->location_id] ?? null; @endphp
                                                <span class="badge badge-info">{{ $locName ?: $subcat->location_id }}</span>
                                            @endif
                                        </td>
                                        <td class="tw-text-center">
                                            <span class="badge badge-secondary">{{ $subcat->sort_order }}</span>
                                        </td>
                                        <td class="tw-text-center">
                                            @if($subcat->active)
                                                <span class="badge badge-success">{{ __('messages.active') }}</span>
                                            @else
                                                <span class="badge badge-warning">{{ __('messages.inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="tw-text-center">
                                            <button type="button" class="btn btn-xs btn-info js-edit-subcategory" data-toggle="modal" 
                                                data-target="#editSubcategoryModal" 
                                                data-id="{{ $subcat->id }}"
                                                data-category-id="{{ $subcat->category_id }}"
                                                data-name="{{ htmlentities($subcat->name, ENT_QUOTES, 'UTF-8') }}"
                                                data-sort-order="{{ $subcat->sort_order ?? 0 }}"
                                                data-active="{{ $subcat->active ? 'true' : 'false' }}"
                                                data-location-id="{{ $subcat->location_id }}">
                                                <i class="fa fa-edit"></i> {{ __('messages.edit') }}
                                            </button>
                                            <button type="button" class="btn btn-xs btn-danger js-delete-subcategory" 
                                                data-id="{{ $subcat->id }}">
                                                <i class="fa fa-trash"></i> {{ __('messages.delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Edit Subcategory Modal -->
            <div class="modal fade" id="editSubcategoryModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header tw-bg-blue-50">
                            <h5 class="modal-title">
                                <i class="fa fa-edit"></i> {{ __('checkcar::lang.edit_subcategory') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="editSubcategoryForm" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="edit_subcategory_category">{{ __('checkcar::lang.category_name') }} <span class="text-danger">*</span></label>
                                    <select name="category_id" id="edit_subcategory_category" class="form-control" required>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit_subcategory_name">{{ __('checkcar::lang.subcategory_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="edit_subcategory_name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_subcategory_sort_order">Sort Order</label>
                                    <input type="number" name="sort_order" id="edit_subcategory_sort_order" class="form-control" value="0" min="0">
                                </div>
                                @if(!empty($isAdmin) && $isAdmin && !empty($locations) && $locations->count())
                                    <div class="form-group">
                                        <label for="edit_subcategory_location_id">{{ __('business.location') }}</label>
                                        <select name="location_id" id="edit_subcategory_location_id" class="form-control">
                                            <option value="">{{ __('lang_v1.all') }}</option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="active" id="edit_subcategory_active" class="custom-control-input" checked>
                                        <label class="custom-control-label" for="edit_subcategory_active">
                                            {{ __('messages.active') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                <button type="submit" class="btn btn-primary js-submit-form">
                                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            
            <!-- Elements Section -->
            <div id="elements" class="settings-content">
                <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                        <h3 class="tw-font-semibold tw-text-lg tw-m-0">
                            <i class="fa fa-th-large"></i> Elements management
                        </h3>
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addElementModal">
                            <i class="fa fa-plus"></i> {{ __('messages.add') }}
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="elementsTable">
                            <thead class="tw-bg-gray-50">
                                <tr>
                                    <th width="60" class="tw-text-center">{{ __('messages.id') }}</th>
                                    <th>{{ __('checkcar::lang.category_name') }}</th>
                                    <th>{{ __('checkcar::lang.subcategory_name') }}</th>
                                    <th>{{ __('messages.name') }}</th>
                                    <th width="120" class="tw-text-center">{{ __('business.location') }}</th>
                                    <th width="80" class="tw-text-center">Type</th>
                                    <th width="60" class="tw-text-center">Sort</th>
                                    <th width="80" class="tw-text-center">Required</th>
                                    <th width="100" class="tw-text-center">Max Options</th>
                                    <th width="80" class="tw-text-center">Active</th>
                                    <th width="180" class="tw-text-center">{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($elements as $element)
                                    <tr>
                                        <td class="tw-text-center tw-font-semibold">{{ $element->id }}</td>
                                        <td>
                                            {{ optional($element->category)->name ?? '---' }}
                                        </td>
                                        <td>
                                            {{ optional($element->subcategory)->name ?? '---' }}
                                        </td>
                                        <td>
                                            <span class="tw-font-medium">{{ $element->name }}</span>
                                        </td>
                                        <td class="tw-text-center">
                                            @if(is_null($element->location_id))
                                                <span class="badge badge-secondary">{{ __('lang_v1.all') }}</span>
                                            @else
                                                @php $locName = optional($locationNames)[$element->location_id] ?? null; @endphp
                                                <span class="badge badge-info">{{ $locName ?: $element->location_id }}</span>
                                            @endif
                                        </td>
                                        <td class="tw-text-center">
                                            <span class="badge badge-primary">{{ ucfirst($element->type ?? 'text') }}</span>
                                        </td>
                                        <td class="tw-text-center">
                                            <span class="badge badge-secondary">{{ $element->sort_order }}</span>
                                        </td>
                                        <td class="tw-text-center">
                                            @if($element->required)
                                                <span class="badge badge-warning">{{ __('messages.yes') }}</span>
                                            @else
                                                <span class="badge badge-light">{{ __('messages.no') }}</span>
                                            @endif
                                        </td>
                                        <td class="tw-text-center">
                                            <span class="badge badge-info">{{ $element->max_options ?? 0 }} {{ ($element->max_options ?? 0) == 0 ? '(∞)' : '' }}</span>
                                        </td>
                                        <td class="tw-text-center">
                                            @if($element->active)
                                                <span class="label label-success">{{ __('messages.yes') }}</span>
                                            @else
                                                <span class="label label-default">{{ __('messages.no') }}</span>
                                            @endif
                                        </td>
                                        <td class="tw-text-center">
                                            <button type="button" class="btn btn-xs btn-info js-edit-element" data-toggle="modal" 
                                                data-target="#editElementModal" 
                                                data-id="{{ $element->id }}"
                                                data-name="{{ htmlentities($element->name, ENT_QUOTES, 'UTF-8') }}"
                                                data-type="{{ $element->type ?? 'text' }}"
                                                data-required="{{ $element->required ? 'true' : 'false' }}"
                                                data-category-id="{{ $element->category_id }}"
                                                data-subcategory-id="{{ $element->subcategory_id }}"
                                                data-sort-order="{{ $element->sort_order }}"
                                                data-max-options="{{ $element->max_options ?? 0 }}"
                                                data-active="{{ $element->active ? 'true' : 'false' }}"
                                                data-location-id="{{ $element->location_id }}">
                                                <i class="fa fa-edit"></i> {{ __('messages.edit') }}
                                            </button>
                                            <button type="button" class="btn btn-xs btn-danger js-delete-element" 
                                                data-id="{{ $element->id }}">
                                                <i class="fa fa-trash"></i> {{ __('messages.delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            
            <!-- Templates Section -->
            <div id="templates" class="settings-content">
                <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6 tw-mb-4">
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                        <h3 class="tw-font-semibold tw-text-lg tw-m-0">
                            <i class="fa fa-file-text"></i> {{ __('checkcar::lang.phrase_templates_title') }}
                        </h3>
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addTemplateModal">
                            <i class="fa fa-plus"></i> {{ __('messages.add') }}
                        </button>
                    </div>

                    <!-- Search/Filter -->
                    <div class="tw-mb-4">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                            <input type="text" id="templatesSearch" class="form-control" placeholder="{{ __('messages.search') }} {{ __('checkcar::lang.question_label') }}...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="templatesTable">
                            <thead class="tw-bg-gray-50">
                                <tr>
                                    <th width="60" class="tw-text-center">{{ __('messages.id') }}</th>
                                    <th>{{ __('checkcar::lang.element_name') }}</th>
                                    <th>{{ __('checkcar::lang.question_label') }}</th>
                                    <th>{{ __('checkcar::lang.question_answer') }}</th>
                                    <th width="120" class="tw-text-center">{{ __('business.location') }}</th>
                                    <th width="140" class="tw-text-center">{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($templates as $tpl)
                                    <tr>
                                        <td class="tw-text-center tw-font-semibold">{{ $tpl->id }}</td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ $tpl->element->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="tw-font-medium">{{ $tpl->label }}</span>
                                        </td>
                                        <td>
                                            <span class="tw-text-gray-600">{{ Str::limit($tpl->phrase, 100) }}</span>
                                        </td>
                                        <td class="tw-text-center">
                                            @if(is_null($tpl->location_id))
                                                <span class="badge badge-secondary">{{ __('lang_v1.all') }}</span>
                                            @else
                                                @php $locName = optional($locationNames)[$tpl->location_id] ?? null; @endphp
                                                <span class="badge badge-info">{{ $locName ?: $tpl->location_id }}</span>
                                            @endif
                                        </td>
                                        <td class="tw-text-center">
                                            <button type="button" class="btn btn-xs btn-info js-edit-template" data-toggle="modal" 
                                                data-target="#editTemplateModal" 
                                                data-id="{{ $tpl->id }}"
                                                data-element-id="{{ $tpl->element_id }}"
                                                data-label="{{ htmlentities($tpl->label, ENT_QUOTES, 'UTF-8') }}"
                                                data-phrase="{{ htmlentities($tpl->phrase, ENT_QUOTES, 'UTF-8') }}"
                                                data-location-id="{{ $tpl->location_id }}">
                                                <i class="fa fa-edit"></i> {{ __('messages.edit') }}
                                            </button>
                                            <button type="button" class="btn btn-xs btn-danger js-delete-template" 
                                                data-id="{{ $tpl->id }}">
                                                <i class="fa fa-trash"></i> {{ __('messages.delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Template Modal -->
            <div class="modal fade" id="addTemplateModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header tw-bg-blue-50">
                            <h5 class="modal-title">
                                <i class="fa fa-plus-circle"></i> {{ __('messages.add') }} {{ __('checkcar::lang.phrase_templates_title') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="addTemplateForm" method="POST" action="{{ route('checkcar.settings.templates.store') }}">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="new_template_element">{{ __('checkcar::lang.element_name') }} <span class="text-danger">*</span></label>
                                    <select name="element_id" id="new_template_element" class="form-control" required>
                                        <option value="">{{ __('messages.please_select') }}</option>
                                        @foreach ($elements as $el)
                                            <option value="{{ $el->id }}">{{ $el->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="new_template_label">{{ __('checkcar::lang.question_label') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="label" id="new_template_label" class="form-control" required>
                                </div>
                                @if(!empty($isAdmin) && $isAdmin && !empty($locations) && $locations->count())
                                    <div class="form-group">
                                        <label for="new_template_location_id">{{ __('business.location') }}</label>
                                        <select name="location_id" id="new_template_location_id" class="form-control">
                                            <option value="">{{ __('lang_v1.all') }}</option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label for="new_template_phrase">{{ __('checkcar::lang.question_answer') }} <span class="text-danger">*</span></label>
                                    <textarea name="phrase" id="new_template_phrase" rows="4" class="form-control" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                <button type="submit" class="btn btn-primary js-submit-form">
                                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Element Modal -->
            <div class="modal fade" id="addElementModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header tw-bg-blue-50">
                            <h5 class="modal-title">
                                <i class="fa fa-plus-circle"></i> Add Element
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="addElementForm" method="POST" action="{{ route('checkcar.settings.elements.store') }}">
                            @csrf
                            <div class="modal-body">
                                <div class="row">
                                    <!-- Left Column: Element Details -->
                                    <div class="col-md-6">
                                        <h5 class="tw-font-semibold tw-mb-3"><i class="fa fa-info-circle"></i> Element Details</h5>
                                        <div class="form-group">
                                            <label for="new_element_name">Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" id="new_element_name" class="form-control" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="new_element_type">Type <span class="text-danger">*</span></label>
                                                    <select name="type" id="new_element_type" class="form-control" required>
                                                        <option value="single">Single Selection</option>
                                                        <option value="multiple">Multiple Selection</option>
                                                        <option value="text">Text Input</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="new_element_sort_order">Sort Order</label>
                                                    <input type="number" name="sort_order" id="new_element_sort_order" class="form-control" value="0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="new_element_category">{{ __('checkcar::lang.category_name') }}</label>
                                            <select name="category_id" id="new_element_category" class="form-control">
                                                <option value="">---</option>
                                                @foreach ($categories as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="new_element_subcategory">{{ __('checkcar::lang.subcategory_name') }}</label>
                                            <select name="subcategory_id" id="new_element_subcategory" class="form-control">
                                                <option value="">---</option>
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="new_element_max_options">Max Options (0 = unlimited)</label>
                                                    <input type="number" name="max_options" id="new_element_max_options" class="form-control" value="0" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <div class="checkbox">
                                                        <label>
                                                            <input type="checkbox" name="required" value="1"> Required
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <div class="checkbox">
                                                        <label>
                                                            <input type="checkbox" name="active" value="1" checked> Active
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Right Column: Element Options & Phrase Templates side by side -->
                                    <div class="col-md-6">
                                        <div style="border-left: 1px solid #dee2e6; padding-left: 15px;">
                                            <div class="row">
                                                <!-- Element Options -->
                                                <div class="col-md-6">
                                                    <h5 class="tw-font-semibold tw-mb-3"><i class="fa fa-list"></i> Element Options</h5>
                                                    <div id="elementOptionsContainer" style="max-height: 250px; overflow-y: auto;">
                                                        <div class="element-option-item mb-2">
                                                            <div class="input-group input-group-sm">
                                                                <input type="text" name="element_options[0][label]" class="form-control" placeholder="Option label">
                                                                <input type="number" name="element_options[0][sort_order]" class="form-control" style="max-width: 50px;" value="0" min="0">
                                                                <div class="input-group-append">
                                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeOption(this)">
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-info mt-2" onclick="addOption()">
                                                        <i class="fa fa-plus"></i> Add Option
                                                    </button>
                                                </div>
                                                
                                                <!-- Phrase Templates -->
                                                <div class="col-md-6">
                                                    <h5 class="tw-font-semibold tw-mb-3"><i class="fa fa-comment"></i> Phrase Templates</h5>
                                                    <div id="phraseTemplatesContainer" style="max-height: 250px; overflow-y: auto;">
                                                        <div class="phrase-template-item mb-2">
                                                            <div class="input-group input-group-sm">
                                                                <textarea name="phrase_templates[0][phrase]" class="form-control form-control-sm" rows="2" placeholder="Phrase text"></textarea>
                                                                <div class="input-group-append">
                                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removePhraseTemplate(this)">
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" name="phrase_templates[0][label]" value="">
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-success mt-2" onclick="addPhraseTemplate()">
                                                        <i class="fa fa-plus"></i> Add Phrase
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                <button type="submit" class="btn btn-primary js-submit-form">
                                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Template Modal -->
            <div class="modal fade" id="editTemplateModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header tw-bg-blue-50">
                            <h5 class="modal-title">
                                <i class="fa fa-edit"></i> {{ __('messages.edit') }} {{ __('checkcar::lang.phrase_templates_title') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="editTemplateForm" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="edit_template_element">{{ __('checkcar::lang.element_name') }} <span class="text-danger">*</span></label>
                                    <select name="element_id" id="edit_template_element" class="form-control" required>
                                        <option value="">{{ __('messages.please_select') }}</option>
                                        @foreach ($elements as $el)
                                            <option value="{{ $el->id }}">{{ $el->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit_template_label">{{ __('checkcar::lang.question_label') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="label" id="edit_template_label" class="form-control" required>
                                </div>
                                @if(!empty($isAdmin) && $isAdmin && !empty($locations) && $locations->count())
                                    <div class="form-group">
                                        <label for="edit_template_location_id">{{ __('business.location') }}</label>
                                        <select name="location_id" id="edit_template_location_id" class="form-control">
                                            <option value="">{{ __('lang_v1.all') }}</option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label for="edit_template_phrase">{{ __('checkcar::lang.question_answer') }} <span class="text-danger">*</span></label>
                                    <textarea name="phrase" id="edit_template_phrase" rows="4" class="form-control" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                <button type="submit" class="btn btn-primary js-submit-form">
                                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            
            
            
        </div>
    </div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle sidebar navigation with persistent active tab
    const navLinks = document.querySelectorAll('.settings-sidebar .nav-link');
    const contents = document.querySelectorAll('.settings-content');

    function activateTab(target) {
        // Remove active from all
        navLinks.forEach(function (l) { l.classList.remove('active'); });
        contents.forEach(function (c) { c.classList.remove('active'); });

        // Activate matching nav link
        navLinks.forEach(function (l) {
            if (l.getAttribute('data-target') === target) {
                l.classList.add('active');
            }
        });

        // Activate corresponding content
        const targetElement = document.getElementById(target);
        if (targetElement) {
            targetElement.classList.add('active');
        }
    }

    navLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            const target = this.getAttribute('data-target');
            if (!target) return;

            // Persist active tab key
            try {
                localStorage.setItem('checkcar_settings_active_tab', target);
            } catch (e) {}

            activateTab(target);
        });
    });

    // On load, restore last active tab if available
    try {
        const savedTab = localStorage.getItem('checkcar_settings_active_tab');
        if (savedTab) {
            const exists = Array.from(navLinks).some(function (l) {
                return l.getAttribute('data-target') === savedTab;
            });
            if (exists) {
                activateTab(savedTab);
            }
        }
    } catch (e) {}

    if (typeof $ !== 'undefined' && $('#servicesTable').length) {
        var servicesTable = $('#servicesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('checkcar.settings.services') }}",
                type: "GET"
            },
            columns: [
                { data: 'name', name: 'products.name' },
                { data: 'setting_type', name: 'checkcar_service_settings.type' },
                { data: 'value', name: 'checkcar_service_settings.value' },
                { data: 'watermark_image', name: 'checkcar_service_settings.watermark_image', defaultContent: '' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            autoWidth: true,
        });

        // Handle Edit Service button click - open modal with pre-filled data
        $(document).on('click', '.js-edit-service', function () {
            var button = $(this);
            var serviceId = button.data('id');
            var productId = button.data('product-id');
            var serviceType = button.data('type');
            var serviceValue = button.data('value');
            var watermarkImage = button.data('watermark-image');

            // Set form action URL
            var updateUrl = "{{ route('checkcar.settings.services.update', ':id') }}".replace(':id', serviceId);
            $('#editServiceSettingForm').attr('action', updateUrl);

            // Pre-fill form fields (file input cannot be pre-filled for security; it will be empty)
            $('#edit_service_id').val(serviceId);
            $('#edit_service_product_id').val(productId);
            $('#edit_service_type').val(serviceType);
            $('#edit_service_value').val(serviceValue || '');
            $('#edit_service_watermark_image').val('');

            // Show modal
            $('#editServiceSettingModal').modal('show');
        });

        // Handle Edit Service form submission (supports file upload)
        $('#editServiceSettingForm').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitBtn = form.find('.js-submit-form');
            var originalText = submitBtn.html();
            var formData = new FormData(form[0]);
            
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ __("messages.loading") }}');
            
            $.ajax({
                method: "POST",
                url: form.attr('action'),
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res && res.success) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(res.message || "{{ __('messages.success') }}");
                        }
                        $('#editServiceSettingModal').modal('hide');
                        servicesTable.ajax.reload(null, false);
                    } else if (res && res.message) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error(res.message);
                        }
                    }
                },
                error: function (xhr) {
                    var msg = "{{ __('messages.something_went_wrong') }}";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error(msg);
                    }
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Handle Add Service form submission with AJAX (supports file upload)
        $('#addServiceSettingForm').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitBtn = form.find('.js-submit-form');
            var originalText = submitBtn.html();
            var formData = new FormData(form[0]);
            
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ __("messages.loading") }}');
            
            $.ajax({
                method: "POST",
                url: form.attr('action'),
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res && res.success) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(res.message || "{{ __('messages.success') }}");
                        }
                        $('#addServiceSettingModal').modal('hide');
                        // Reset form
                        form[0].reset();
                        $('#service_type').val('service');
                        servicesTable.ajax.reload(null, false);
                    } else if (res && res.message) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error(res.message);
                        }
                    }
                },
                error: function (xhr) {
                    var msg = "{{ __('messages.something_went_wrong') }}";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error(msg);
                    }
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });
    }

    // Open Add Service Setting modal
    var btnAddServiceSetting = document.getElementById('btnAddServiceSetting');
    if (btnAddServiceSetting && typeof $ !== 'undefined') {
        btnAddServiceSetting.addEventListener('click', function () {
            $('#addServiceSettingModal').modal('show');
        });
    }

    // Initialize client-side DataTables for settings tables if jQuery/DataTables is available
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        if ($('#categoriesTable').length) {
            $('#categoriesTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 25
            });
        }
        if ($('#subcategoriesTable').length) {
            $('#subcategoriesTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 25
            });
        }
        if ($('#elementsTable').length) {
            $('#elementsTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 25
            });
        }
        if ($('#templatesTable').length) {
            $('#templatesTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 25
            });
        }

        // Location switcher: reload page with selected location so PHP filters data
        if ($('#checkcar_location_switcher').length) {
            $('#checkcar_location_switcher').on('change', function() {
                var locId = $(this).val();
                var url = new URL(window.location.href);
                url.searchParams.set('location_id', locId);
                window.location.href = url.toString();
            });
        }
    }
});

// Edit Category Function
function editCategory(btn) {
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    const sortOrder = btn.dataset.sortOrder || 0;
    const active = btn.dataset.active === 'true';
    const locationId = btn.dataset.locationId || '';

    var nameInput = document.getElementById('edit_category_name');
    var sortInput = document.getElementById('edit_category_sort_order');
    var activeCheckbox = document.getElementById('edit_category_active');
    var locationSelect = document.getElementById('edit_category_location_id');

    if (nameInput) nameInput.value = name || '';
    if (sortInput) sortInput.value = sortOrder;
    if (activeCheckbox) activeCheckbox.checked = active;
    if (locationSelect) locationSelect.value = locationId || '';

    document.getElementById('editCategoryForm').action = '{{ route("checkcar.settings.categories.update", ":id") }}'.replace(':id', id);
}

// Edit Subcategory Function
function editSubcategory(btn) {
    const id = btn.dataset.id;
    const categoryId = btn.dataset.categoryId;
    const name = btn.dataset.name;
    const sortOrder = btn.dataset.sortOrder || 0;
    const active = btn.dataset.active === 'true';
    const locationId = btn.dataset.locationId || '';
    
    document.getElementById('edit_subcategory_category').value = categoryId;
    document.getElementById('edit_subcategory_name').value = name;
    document.getElementById('edit_subcategory_sort_order').value = sortOrder;
    document.getElementById('edit_subcategory_active').checked = active;

    var locationSelect = document.getElementById('edit_subcategory_location_id');
    if (locationSelect) {
        locationSelect.value = locationId || '';
    }
    document.getElementById('editSubcategoryForm').action = '{{ route("checkcar.settings.subcategories.update", ":id") }}'.replace(':id', id);
}


// Edit Template Function
function editTemplate(btn) {
    const id = btn.dataset.id;
    const elementId = btn.dataset.elementId;
    const label = btn.dataset.label;
    const phrase = btn.dataset.phrase;
    const locationId = btn.dataset.locationId || '';
    
    document.getElementById('edit_template_element').value = elementId;
    document.getElementById('edit_template_label').value = label;
    document.getElementById('edit_template_phrase').value = phrase;

    var locationSelect = document.getElementById('edit_template_location_id');
    if (locationSelect) {
        locationSelect.value = locationId || '';
    }
    document.getElementById('editTemplateForm').action = '{{ route("checkcar.settings.templates.update", ":id") }}'.replace(':id', id);
}

// Edit Element Function
function editElement(btn) {
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    const type = btn.dataset.type || 'text';
    const required = btn.dataset.required === 'true';
    const categoryId = btn.dataset.categoryId;
    const subcategoryId = btn.dataset.subcategoryId;
    const sortOrder = btn.dataset.sortOrder || 0;
    const maxOptions = btn.dataset.maxOptions || 0;
    const active = btn.dataset.active === 'true';
    const locationId = btn.dataset.locationId || '';
    
    var nameInput = document.getElementById('edit_element_name');
    var typeSelect = document.getElementById('edit_element_type');
    var requiredCheckbox = document.getElementById('edit_element_required');
    var categorySelect = document.getElementById('edit_element_category');
    var subcategorySelect = document.getElementById('edit_element_subcategory');
    var sortOrderInput = document.getElementById('edit_element_sort_order');
    var maxOptionsInput = document.getElementById('edit_element_max_options');
    var activeCheckbox = document.getElementById('edit_element_active');
    var locationSelect = document.getElementById('edit_element_location_id');

    if (nameInput) nameInput.value = name || '';
    if (typeSelect) typeSelect.value = type;
    if (requiredCheckbox) requiredCheckbox.checked = required;
    if (categorySelect) categorySelect.value = categoryId || '';
    if (subcategorySelect) subcategorySelect.value = subcategoryId || '';
    if (sortOrderInput) sortOrderInput.value = sortOrder;
    if (maxOptionsInput) maxOptionsInput.value = maxOptions;
    if (activeCheckbox) activeCheckbox.checked = active;
    if (locationSelect) locationSelect.value = locationId || '';

    document.getElementById('editElementForm').action = '{{ route("checkcar.settings.elements.update", ":id") }}'.replace(':id', id);

    // Load subcategories based on selected category for edit modal
    if (categoryId) {
        loadSubcategories(categoryId, 'edit_element_subcategory', subcategoryId);
    }

    // Load element options
    loadElementOptions(id);
}

// Load subcategories for a given category into a target select element
function loadSubcategories(categoryId, targetSelectId, selectedSubcategoryId) {
    var target = document.getElementById(targetSelectId);
    if (!target || !categoryId) {
        return;
    }

    target.innerHTML = '<option value="">---</option>';

    fetch('{{ url("checkcar/api/categories") }}/' + categoryId + '/subcategories')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (!data || !data.success || !data.data) {
                return;
            }

            var items = data.data;
            var autoSelectId = null;

            // If no preselected subcategory and exactly one item, auto-select it
            if (!selectedSubcategoryId && items.length === 1) {
                autoSelectId = String(items[0].id);
            }

            items.forEach(function(sub) {
                var opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name;

                if (selectedSubcategoryId && String(selectedSubcategoryId) === String(sub.id)) {
                    opt.selected = true;
                } else if (!selectedSubcategoryId && autoSelectId && autoSelectId === String(sub.id)) {
                    opt.selected = true;
                }

                target.appendChild(opt);
            });
        })
        .catch(function(error) {
            console.error('Error loading subcategories:', error);
        });
}

// Element Options Management
let optionIndex = 0;
let editOptionIndex = 0;

function addOption() {
    const container = document.getElementById('elementOptionsContainer');
    const optionHtml = `
        <div class="element-option-item mb-2">
            <div class="input-group input-group-sm">
                <input type="text" name="element_options[${++optionIndex}][label]" class="form-control" placeholder="Option label">
                <input type="number" name="element_options[${optionIndex}][sort_order]" class="form-control" style="max-width: 50px;" value="0" min="0">
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeOption(this)">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', optionHtml);
}

function removeOption(button) {
    button.closest('.element-option-item').remove();
}

function addEditOption() {
    const container = document.getElementById('editElementOptionsContainer');
    const optionHtml = `
        <div class="element-option-item mb-2">
            <div class="input-group input-group-sm">
                <input type="text" name="edit_element_options[${++editOptionIndex}][label]" class="form-control" placeholder="Option label">
                <input type="number" name="edit_element_options[${editOptionIndex}][sort_order]" class="form-control" style="max-width: 50px;" value="0" min="0">
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeOption(this)">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', optionHtml);
}

function loadElementOptions(elementId) {
    // Clear containers first
    document.getElementById('editElementOptionsContainer').innerHTML = '';
    document.getElementById('editPhraseTemplatesContainer').innerHTML = '';
    editOptionIndex = 0;
    editPhraseTemplateIndex = 0;
    
    // Fetch element data via AJAX
    fetch('{{ url("checkcar/settings/elements") }}/' + elementId + '/data')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Render options
                if (data.options && data.options.length > 0) {
                    data.options.forEach((option, index) => {
                        const optionHtml = `
                            <div class="element-option-item mb-2">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="edit_element_options[${index}][label]" class="form-control" value="${escapeHtml(option.label)}" placeholder="Option label">
                                    <input type="number" name="edit_element_options[${index}][sort_order]" class="form-control" style="max-width: 50px;" value="${option.sort_order}" min="0">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeOption(this)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        document.getElementById('editElementOptionsContainer').insertAdjacentHTML('beforeend', optionHtml);
                        editOptionIndex = index;
                    });
                }
                
                // Render phrase templates
                if (data.phrase_templates && data.phrase_templates.length > 0) {
                    data.phrase_templates.forEach((template, index) => {
                        const templateHtml = `
                            <div class="phrase-template-item mb-2">
                                <div class="input-group input-group-sm">
                                    <textarea name="edit_phrase_templates[${index}][phrase]" class="form-control form-control-sm" rows="2" placeholder="Phrase text">${escapeHtml(template.phrase)}</textarea>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removePhraseTemplate(this)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="edit_phrase_templates[${index}][label]" value="${escapeHtml(template.label || '')}">
                            </div>
                        `;
                        document.getElementById('editPhraseTemplatesContainer').insertAdjacentHTML('beforeend', templateHtml);
                        editPhraseTemplateIndex = index;
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading element data:', error);
        });
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Phrase Templates Management
let phraseTemplateIndex = 0;
let editPhraseTemplateIndex = 0;

function addPhraseTemplate() {
    const container = document.getElementById('phraseTemplatesContainer');
    const itemHtml = `
        <div class="phrase-template-item mb-2">
            <div class="input-group input-group-sm">
                <textarea name="phrase_templates[${++phraseTemplateIndex}][phrase]" class="form-control form-control-sm" rows="2" placeholder="Phrase text"></textarea>
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removePhraseTemplate(this)">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
            <input type="hidden" name="phrase_templates[${phraseTemplateIndex}][label]" value="">
        </div>
    `;
    container.insertAdjacentHTML('beforeend', itemHtml);
}

function removePhraseTemplate(button) {
    button.closest('.phrase-template-item').remove();
}

function addEditPhraseTemplate() {
    const container = document.getElementById('editPhraseTemplatesContainer');
    const itemHtml = `
        <div class="phrase-template-item mb-2">
            <div class="input-group input-group-sm">
                <textarea name="edit_phrase_templates[${++editPhraseTemplateIndex}][phrase]" class="form-control form-control-sm" rows="2" placeholder="Phrase text"></textarea>
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removePhraseTemplate(this)">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
            <input type="hidden" name="edit_phrase_templates[${editPhraseTemplateIndex}][label]" value="">
        </div>
    `;
    container.insertAdjacentHTML('beforeend', itemHtml);
}


// Edit Button Event Handlers - Using event delegation for DataTables pagination support
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit category buttons (event delegation)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.js-edit-category');
        if (btn) {
            e.preventDefault();
            editCategory(btn);
        }
    });
    
    // Handle edit subcategory buttons (event delegation)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.js-edit-subcategory');
        if (btn) {
            e.preventDefault();
            editSubcategory(btn);
        }
    });
    
    // Handle edit element buttons (event delegation)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.js-edit-element');
        if (btn) {
            e.preventDefault();
            editElement(btn);
        }
    });
    
    // Handle edit template buttons (event delegation)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.js-edit-template');
        if (btn) {
            e.preventDefault();
            editTemplate(btn);
        }
    });

    // Add Element: load subcategories when category changes
    var newElementCategory = document.getElementById('new_element_category');
    if (newElementCategory) {
        newElementCategory.addEventListener('change', function() {
            var catId = this.value;
            loadSubcategories(catId, 'new_element_subcategory', null);
        });
    }

    // Edit Element: load subcategories when category changes
    var editElementCategory = document.getElementById('edit_element_category');
    if (editElementCategory) {
        editElementCategory.addEventListener('change', function() {
            var catId = this.value;
            loadSubcategories(catId, 'edit_element_subcategory', null);
        });
    }
});

// AJAX Form Submission Handler
document.addEventListener('DOMContentLoaded', function() {
    // Handle all form submissions with class js-submit-form
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!form.querySelector('.js-submit-form')) return;
            
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('.js-submit-form');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __("messages.loading") }}';
            
            fetch(form.action, {
                method: form.method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message || '{{ __("messages.success") }}');
                    // Close modal if this form is inside one
                    const modal = form.closest('.modal');
                    if (modal) {
                        const closeBtn = modal.querySelector('[data-dismiss="modal"]');
                        if (closeBtn) closeBtn.click();
                    }
                } else {
                    toastr.error(data.message || '{{ __("messages.error") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('{{ __("messages.error") }}');
            })
            .finally(() => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    });

    // Handle delete category buttons (event delegation for DataTables pagination)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.js-delete-category');
        if (!btn) return;
        
        if (!confirm('{{ __("messages.are_you_sure") }}')) return;
        
        e.preventDefault();
        const categoryId = btn.dataset.id;
        const originalText = btn.innerHTML;
        
        // Show loading state
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
        
        fetch('{{ route("checkcar.settings.categories.destroy", ":id") }}'.replace(':id', categoryId), {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message || '{{ __("messages.success") }}');
                // Reload page after short delay
                setTimeout(() => location.reload(), 500);
            } else {
                toastr.error(data.message || '{{ __("messages.error") }}');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('{{ __("messages.error") }}');
        })
        .finally(() => {
            // Restore button state
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // Handle delete subcategory buttons (event delegation for DataTables pagination)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.js-delete-subcategory');
        if (!btn) return;
        
        if (!confirm('{{ __("messages.are_you_sure") }}')) return;
        
        e.preventDefault();
        const subcategoryId = btn.dataset.id;
        const originalText = btn.innerHTML;
        
        // Show loading state
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
        
        fetch('{{ route("checkcar.settings.subcategories.destroy", ":id") }}'.replace(':id', subcategoryId), {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message || '{{ __("messages.success") }}');
                // Reload page after short delay
                setTimeout(() => location.reload(), 500);
            } else {
                toastr.error(data.message || '{{ __("messages.error") }}');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('{{ __("messages.error") }}');
        })
        .finally(() => {
            // Restore button state
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // Handle delete template buttons (event delegation for DataTables pagination)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.js-delete-template');
        if (!btn) return;
        
        if (!confirm('{{ __("messages.are_you_sure") }}')) return;
        
        e.preventDefault();
        const templateId = btn.dataset.id;
        const originalText = btn.innerHTML;
        
        // Show loading state
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
        
        fetch('{{ route("checkcar.settings.templates.destroy", ":id") }}'.replace(':id', templateId), {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message || '{{ __("messages.success") }}');
                // Reload page after short delay
                setTimeout(() => location.reload(), 500);
            } else {
                toastr.error(data.message || '{{ __("messages.error") }}');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('{{ __("messages.error") }}');
        })
        .finally(() => {
            // Restore button state
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // Handle delete element buttons (event delegation for DataTables pagination)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.js-delete-element');
        if (!btn) return;
        
        if (!confirm('{{ __("messages.are_you_sure") }}')) return;

        e.preventDefault();
        const elementId = btn.dataset.id;
        const originalText = btn.innerHTML;

        // Show loading state
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

        fetch('{{ route("checkcar.settings.elements.destroy", ":id") }}'.replace(':id', elementId), {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message || '{{ __("messages.success") }}');
                // Reload page after short delay
                setTimeout(() => location.reload(), 500);
            } else {
                toastr.error(data.message || '{{ __("messages.error") }}');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('{{ __("messages.error") }}');
        })
        .finally(() => {
            // Restore button state
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // Handle delete element option buttons
    document.querySelectorAll('.js-delete-element-option').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('{{ __("messages.are_you_sure") }}')) return;

            e.preventDefault();
            const optionId = btn.dataset.id;
            const originalText = btn.innerHTML;

            // Show loading state
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

            fetch('{{ route("checkcar.settings.element-options.destroy", ":id") }}'.replace(':id', optionId), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message || '{{ __("messages.success") }}');
                    // Reload page after short delay
                    setTimeout(() => location.reload(), 500);
                } else {
                    toastr.error(data.message || '{{ __("messages.error") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('{{ __("messages.error") }}');
            })
            .finally(() => {
                // Restore button state
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    });
});

// Search/Filter Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Categories Search
    const categoriesSearch = document.getElementById('categoriesSearch');
    if (categoriesSearch) {
        categoriesSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#categoriesTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Subcategories Search
    const subcategoriesSearch = document.getElementById('subcategoriesSearch');
    if (subcategoriesSearch) {
        subcategoriesSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#subcategoriesTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Templates Search
    const templatesSearch = document.getElementById('templatesSearch');
    if (templatesSearch) {
        templatesSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#templatesTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Element Options Search
    const elementOptionsSearch = document.getElementById('elementOptionsSearch');
    if (elementOptionsSearch) {
        elementOptionsSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#elementOptionsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

});
</script>
