@extends('layouts.app')

@section('title', __('repair::lang.purchase_requests'))

@section('content')
@include('repair::layouts.purchase_nav')
<section class="content-header no-print">
    <div class="tw-flex tw-flex-col md:tw-flex-row md:tw-items-center md:tw-justify-between tw-gap-4">
        <form method="GET" action="{{ request()->url() }}" class="tw-flex tw-flex-col lg:tw-flex-row tw-items-stretch lg:tw-items-end tw-gap-3 tw-w-full">
            @if($locations->isNotEmpty())
                <div class="tw-flex tw-flex-col">
                    <label for="location_id" class="tw-text-sm tw-font-semibold tw-text-gray-600">@lang('business.business_locations')</label>
                    <select id="location_id" name="location_id" class="form-control tw-min-w-[180px]">
                        @if($isAdmin)
                            <option value="" {{ empty($selectedLocation) ? 'selected' : '' }}>@lang('messages.all')</option>
                        @endif
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ (int) $selectedLocation === $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="tw-flex tw-flex-col">
                <label for="status" class="tw-text-sm tw-font-semibold tw-text-gray-600">@lang('repair::lang.status')</label>
                <select id="status" name="status" class="form-control tw-min-w-[180px]">
                    <option value="">@lang('messages.all')</option>
                    @foreach($availableStatuses as $status)
                        @php
                            $statusLabel = $statusMeta[$status]['label'] ?? \Illuminate\Support\Str::headline($status);
                        @endphp
                        <option value="{{ $status }}" {{ $selectedStatus === $status ? 'selected' : '' }}>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tw-flex tw-flex-col">
                <label for="job_sheet" class="tw-text-sm tw-font-semibold tw-text-gray-600">@lang('repair::lang.job_sheet')</label>
                <input id="job_sheet" type="text" name="job_sheet" class="form-control" value="{{ $jobSheetSearch }}" placeholder="@lang('repair::lang.job_sheet')">
            </div>

            <div class="tw-flex tw-items-center tw-gap-2 my-4">
                <button type="submit" class="btn btn-primary btn-sm tw-self-start">@lang('repair::lang.filter')</button>
                <a href="{{ request()->url() }}" class="btn btn-outline-secondary btn-sm tw-self-start">@lang('messages.clear')</a>
            </div>
        </form>
    </div>
</section>

<section class="content no-print tw-pt-6">
    @if($notes->isEmpty())
        <div class="tw-bg-white tw-rounded-xl tw-shadow-sm tw-border tw-border-gray-200 tw-p-8 tw-text-center">
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-700">@lang('repair::lang.no_purchase_requests')</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-2">@lang('repair::lang.no_purchase_requests_helper')</p>
        </div>
    @else
        <div class="row tw-g-4">
            @foreach($notes as $note)
                @php
                    $noteStatusKey = $note->status ?? 'awaiting_reply';
                    $statusConfig = $statusMeta[$noteStatusKey] ?? [
                        'label' => \Illuminate\Support\Str::headline($noteStatusKey),
                        'color' => '#9ca3af',
                        'badge_bg' => '#f3f4f6',
                        'badge_text' => '#374151',
                    ];
                    $statusName = $statusConfig['label'];
                    $statusColor = $statusConfig['color'];
                    $vehicleName = $note->vehicle_display;
                    $engineerName = $note->engineer_name;
                    $contactName = $note->contact_name;
                    $vin = $note->vin_display;
                    $repairStatusName = $note->repair_status_display;
                    $modalId = 'maintenance-note-modal-' . $note->id;
                    $origin = $note->origin ?? 'job_sheet';
                    $originLabel = $note->origin_label ?? __('repair::lang.job_sheet');
                    $cardBorderClass = $origin === 'job_sheet' ? 'tw-border-blue-200 tw-shadow-blue-100/80' : 'tw-border-purple-200 tw-shadow-purple-100/80';
                    $originBadgeClass = $origin === 'job_sheet' ? 'tw-bg-blue-100 tw-text-blue-700' : 'tw-bg-purple-100 tw-text-purple-700';
                @endphp
                <div class="col-12 col-md-4 tw-p-3" data-note-id="{{ $note->id }}" data-updated-at="{{ $note->updated_at }}" data-location-id="{{ $note->location_id }}">
                    <div class="tw-bg-white tw-rounded-2xl tw-border tw-shadow-lg tw-p-6 tw-flex tw-flex-col tw-gap-4 tw-h-full my-3 {{ $cardBorderClass }}">
                        <div class="tw-flex tw-items-start tw-justify-between tw-gap-3">
                            <div class="tw-space-y-1">
                                <h2 class="tw-text-base tw-font-semibold tw-text-gray-900 tw-tracking-wide">{{ $note->display_reference ?: '' }}</h2>
                                <span class="tw-inline-flex tw-items-center tw-rounded-full tw-text-[10px] tw-font-semibold tw-px-2 tw-py-0.5 js-origin-badge {{ $originBadgeClass }}">{{ $originLabel }}</span>
                            </div>
                            <button type="button" class="tw-text-gray-400 tw-text-lg hover:tw-text-gray-600 js-open-maintenance-note" data-note-id="{{ $note->id }}" data-target="#{{ $modalId }}" aria-label="@lang('repair::lang.reply_request')">
                                <i class="fas fa-comment-dots"></i>
                            </button>
                        </div>

                        <div class="tw-space-y-3">
                     
                        
                            <div class="tw-text-sm tw-text-gray-500 tw-leading-5">
                                <span class="tw-uppercase tw-tracking-wide tw-text-[11px] tw-font-semibold tw-text-gray-400">@lang($origin === 'job_estimator' ? 'repair::lang.customer_service' : 'repair::lang.engineer')</span><br>
                                <span class="tw-font-semibold tw-text-gray-900">{{ $engineerName }}</span>
                            </div>

                            <!-- Contact Information -->
                            <div class="tw-text-sm tw-text-gray-500 tw-leading-5">
                                <span class="tw-uppercase tw-tracking-wide tw-text-[11px] tw-font-semibold tw-text-gray-400">@lang('repair::lang.customer')</span><br>
                                <span class="tw-font-semibold tw-text-gray-900">{{ $contactName }}</span>
                            </div>

                            <!-- Vehicle Information -->
                            <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-4 tw-text-xs">
                                <div class="tw-flex tw-items-center tw-gap-1">
                                    <span class="tw-text-gray-400 tw-font-medium">@lang('repair::lang.vehicle_label'):</span>
                                    <span class="tw-text-gray-900 tw-font-semibold">{{ $vehicleName }}</span>
                                </div>

                                <div class="tw-flex tw-items-center tw-gap-1">
                                    <span class="tw-text-gray-400 tw-font-medium">@lang('repair::lang.vin_label'):</span>
                                    <span class="tw-text-gray-900 tw-font-semibold">{{ $vin }}</span>
                                </div>
                                <div class="tw-flex tw-items-center tw-gap-1">
                                    <span class="tw-text-gray-400 tw-font-medium">@lang('repair::lang.plate_label'):</span>
                                    <span class="tw-text-gray-900 tw-font-semibold">{{ $note->plate_number ?? '—' }}</span>
                                </div>
                                <div class="tw-flex tw-items-center tw-gap-1">
                                    <span class="tw-text-gray-400 tw-font-medium">@lang('repair::lang.color_label'):</span>
                                    <span class="tw-text-gray-900 tw-font-semibold">{{ $note->color ?? '—' }}</span>
                                </div>
                                <div class="tw-flex tw-items-center tw-gap-1">
                                    <span class="tw-text-gray-400 tw-font-medium">@lang('repair::lang.type_label'):</span>
                                    <span class="tw-text-gray-900 tw-font-semibold">{{ $note->car_type ?? '—' }}</span>
                                </div>
                                @if(!empty($note->motor_cc))
                                <div class="tw-flex tw-items-center tw-gap-1">
                                    <span class="tw-text-gray-400 tw-font-medium">@lang('car.motor_cc'):</span>
                                    <span class="tw-text-gray-900 tw-font-semibold">{{ $note->motor_cc }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="tw-border-t tw-border-gray-200 tw-pt-4 tw-mt-auto">
                            <div class="tw-flex tw-items-center tw-justify-between tw-gap-3">
                                <span class="tw-inline-flex tw-items-center tw-font-medium tw-rounded-full tw-text-xs tw-px-3 tw-py-1" style="background-color: {{ $statusConfig['badge_bg'] }}; color: {{ $statusConfig['badge_text'] }};">
                                    <span class="tw-w-2 tw-h-2 tw-rounded-full tw-mr-2" style="background-color: {{ $statusColor }}"></span>
                                    {{ $statusName }}
                                </span>
                              

                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}Label" aria-hidden="true" data-location-id="{{ $note->location_id }}">
                    <div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
                        <div class="modal-content tw-rounded-2xl tw-overflow-x-auto">
                            <div class="modal-header tw-border-b tw-border-gray-200 tw-px-5 tw-py-4">
                                <h4 class="modal-title tw-text-lg tw-font-semibold tw-text-gray-900" id="{{ $modalId }}Label">
                                    @lang('repair::lang.maintenance_note_reply_title') - {{ $note->display_reference ?: '' }}
                                </h4>
                                <div class="tw-flex tw-items-center tw-gap-2">
                                  
                                    <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            </div>
                            <div class="modal-body tw-space-y-6 tw-px-5 tw-py-6">
                                    <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-6 tw-bg-white tw-rounded-xl tw-p-6 tw-shadow-sm">

                                        <!-- Vehicle -->
                                        <div class="tw-flex tw-items-center tw-gap-2">
                                            <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[50px]">@lang('repair::lang.vehicle_label'):</span>
                                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-vehicle">{{ $vehicleName }}</span>
                                        </div>

                                 
                                        <!-- VIN -->
                                        <div class="tw-flex tw-items-center tw-gap-2">
                                            <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[35px]">@lang('repair::lang.vin_label'):</span>
                                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-vin">{{ $vin }}</span>
                                        </div>

                                        <!-- Plate -->
                                        <div class="tw-flex tw-items-center tw-gap-2">
                                            <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[45px]">@lang('repair::lang.plate_label'):</span>
                                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-plate">{{ $note->plate_number ?? '—' }}</span>
                                        </div>

                                        <!-- Color -->
                                        <div class="tw-flex tw-items-center tw-gap-2">
                                            <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[45px]">@lang('repair::lang.color_label'):</span>
                                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-color">{{ $note->color ?? '—' }}</span>
                                        </div>

                                        <!-- Type -->
                                        <div class="tw-flex tw-items-center tw-gap-2">
                                            <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[40px]">@lang('repair::lang.type_label'):</span>
                                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-type">{{ $note->car_type ?? '—' }}</span>
                                        </div>
                                        @if(!empty($note->motor_cc))
                                        <!-- Motor CC -->
                                        <div class="tw-flex tw-items-center tw-gap-2">
                                            <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[40px]">@lang('car.motor_cc'):</span>
                                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-motor-cc">{{ $note->motor_cc }}</span>
                                        </div>
                                        @endif

                                    </div>




                                <div class="form-group tw-mb-0">
                                    <label class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('repair::lang.engineer_request')</label>
                                    <textarea class="form-control tw-rounded-xl tw-min-h-[120px]" rows="4" readonly>{{ $note->content }}</textarea>
                                </div>

                                 <div class="tw-flex tw-items-center tw-gap-2">
                                    <button type="button" class="btn btn-outline-info btn-sm js-open-purchase-orders" data-note-id="{{ $note->id }}" title="Track Purchase Orders">
                                        <i class="fas fa-shopping-cart tw-mr-1"></i>@lang('repair::lang.purchase_orders')
                                    </button>
                          
                                    <button type="button" class="btn btn-outline-primary btn-sm js-open-quick-supplier" data-note-id="{{ $note->id }}">
                                        <i class="fas fa-user-plus tw-mr-1"></i>@lang('contact.add_supplier')
                                    </button>
                                </div>
                                <div>
                                    <div class="tw-flex tw-flex-col xl:tw-flex-row xl:tw-items-center xl:tw-justify-between tw-gap-3 tw-mb-3">
                                        <div class="tw-w-full">
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li class="active" role="presentation">
                                                    <a id="tab-stock-{{ $note->id }}" data-toggle="tab" href="#tab-pane-stock-{{ $note->id }}" role="tab" aria-controls="tab-pane-stock-{{ $note->id }}" aria-selected="true">
                                                        @lang('product.products')
                                                    </a>
                                                </li>
                                                <li role="presentation">
                                                    <a id="tab-nonstock-{{ $note->id }}" data-toggle="tab" href="#tab-pane-nonstock-{{ $note->id }}" role="tab" aria-controls="tab-pane-nonstock-{{ $note->id }}" aria-selected="false">
                                                        @lang('product.services')
                                                    </a>
                                                </li>
                                            </ul>
                                            <div class="tab-content tw-border tw-border-gray-200 tw-border-t-0 tw-rounded-b-xl tw-p-3">
                                                <div class="tab-pane fade in active" id="tab-pane-stock-{{ $note->id }}" role="tabpanel" aria-labelledby="tab-stock-{{ $note->id }}">
                                                    <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-stretch sm:tw-items-center my-3 tw-gap-2">
                                                        <select class="form-control maintenance-note-product-search" data-enable-stock="1" data-note-id="{{ $note->id }}" id="maintenance-note-product-search-{{ $note->id }}" style="width: 100%; min-width: 240px;" data-placeholder="@lang('repair::lang.search_parts')"></select>
                                                        <button type="button" class="btn btn-outline-success btn-sm js-open-quick-product" data-note-id="{{ $note->id }}">
                                                            <i class="fas fa-plus tw-mr-1"></i>@lang('repair::lang.add_new_product')
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="tab-pane-nonstock-{{ $note->id }}" role="tabpanel" aria-labelledby="tab-nonstock-{{ $note->id }}">
                                                    <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-stretch sm:tw-items-center my-3 tw-gap-2">
                                                        <select class="form-control maintenance-note-product-search" data-enable-stock="0" data-note-id="{{ $note->id }}" id="maintenance-note-product-search-ns-{{ $note->id }}" style="width: 100%; min-width: 240px;" data-placeholder="@lang('repair::lang.search_parts')"></select>
                                                   
                                                                  <button type="button" class="btn btn-outline-warning btn-sm js-open-services" data-note-id="{{ $note->id }}" data-location-id="{{ $note->location_id }}">
                                                                    <i class="fas fa-tools tw-mr-1"></i>@lang('repair::lang.services')
                                                                </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive tw-rounded-2xl tw-border tw-border-gray-200 tw-shadow-inner">
                                        <table class="table table-striped table-borderless tw-mb-0">
                                            <thead class="tw-bg-gray-50">
                                                <tr class="tw-text-xs tw-uppercase tw-text-gray-500">
                                                    <th>@lang('repair::lang.part_name')</th>
                                             
                                                    <th class="tw-text-right">@lang('repair::lang.quantity')</th>
                                                    <th class="tw-text-right">@lang('repair::lang.end_user_price')</th>
                                                    <th class="tw-text-right">@lang('repair::lang.purchase_price')</th>
                                                    <th class="tw-text-right">@lang('repair::lang.supplier_name')</th>
                                                    <th class="tw-text-right">@lang('repair::lang.out_for_delivery')</th>
                                                    <th class="tw-text-right">@lang('repair::lang.client_approval')</th>
                                                    
                                                    <th class="tw-text-right">@lang('repair::lang.notes')</th>
                                                    <th class="tw-text-right">@lang('messages.actions')</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modal-lines-{{ $note->id }}">
                                                <tr>
                                                    <td colspan="9" class="tw-text-center tw-text-sm tw-text-gray-500">@lang('repair::lang.loading')</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="tw-mt-4 tw-flex tw-gap-2 tw-justify-end">
                                        <button type="button" class="btn btn-secondary js-cancel-all-products" data-note-id="{{ $note->id }}">
                                            @lang('messages.cancel')
                                        </button>
                                        <button type="button" class="btn btn-primary js-save-all-products" data-note-id="{{ $note->id }}">
                                            @lang('messages.save_all')
                                        </button>
                                    </div>
                                </div>
                            </div>
                       
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="tw-mt-6">
            {{ $notes->links() }}
        </div>
    @endif
</section>

<div class="modal fade" id="quick-product-modal" tabindex="-1" role="dialog" aria-labelledby="quick-product-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content tw-rounded-2xl">
            <div class="modal-header tw-border-b tw-border-gray-200 tw-px-4 tw-py-3">
                <h5 class="modal-title tw-text-lg tw-font-semibold tw-text-gray-900" id="quick-product-modal-label">@lang('repair::lang.add_new_product')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="quick-product-form" class="tw-space-y-4">
                @csrf
                <div class="modal-body tw-space-y-4 tw-px-4 tw-py-3">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="quick_product_name" class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('product.product_name')</label>
                            <input type="text" class="form-control" id="quick_product_name" name="name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="quick_product_sku" class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('product.sku')</label>
                            <input type="text" class="form-control" id="quick_product_sku" name="sku">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="quick_product_price" class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('product.selling_price')</label>
                            <input type="number" min="0" step="0.01" class="form-control" id="quick_product_price" name="price" required>
                        </div>
                        <div class="form-group col-md-6">
                            <!-- Empty column for btns -->
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="quick_product_category" class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('product.category')</label>
                            <div class="input-group">
                                <select id="quick_product_category" class="form-control js-select2" name="category_id" data-placeholder="@lang('messages.please_select')">
                                    <option value="">@lang('messages.please_select')</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default bg-white btn-flat btn-modal js-add-product-category" data-href="{{ action([\App\Http\Controllers\TaxonomyController::class, 'create']) }}?type=product" data-container=".category_modal" title="@lang('category.add_category')">
                                        <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                         <div class="form-group col-md-6">
                            <label for="quick_product_brand" class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('product.brand')</label>
                            <div class="input-group">
                                <select id="quick_product_brand" class="form-control js-select2" name="brand_id" data-placeholder="@lang('messages.please_select')">
                                    <option value="">@lang('messages.please_select')</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default bg-white btn-flat btn-modal" data-href="{{ action([\App\Http\Controllers\BrandController::class, 'create'], ['quick_add' => true]) }}" data-container=".view_modal" title="@lang('brand.add_brand')">
                                        <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                      
                    </div>
                    <div class="form-row">
                         <div class="form-group col-md-6">
                            <label for="quick_product_sub_category" class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('product.sub_category')</label>
                            <div class="input-group">
                                <select id="quick_product_sub_category" class="form-control js-select2" name="sub_category_id" data-placeholder="@lang('messages.please_select')">
                                    <option value="">@lang('messages.please_select')</option>
                                    @foreach($subCategories as $subCategory)
                                        <option value="{{ $subCategory->id }}" data-parent="{{ $subCategory->parent_id }}">{{ $subCategory->name }}</option>
                                    @endforeach
                                </select>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default bg-white btn-flat btn-modal js-add-product-subcategory" data-href="{{ action([\App\Http\Controllers\TaxonomyController::class, 'create']) }}?type=product" data-container=".category_modal" title="@lang('category.add_sub_category')">
                                        <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="quick_product_unit" class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('product.unit')</label>
                            <div class="input-group">
                                <select id="quick_product_unit" class="form-control js-select2" name="unit_id" data-placeholder="@lang('messages.please_select')">
                                    <option value="">@lang('messages.please_select')</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->actual_name }}</option>
                                    @endforeach
                                </select>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default bg-white btn-flat quick_add_unit btn-modal" data-href="{{ action([\App\Http\Controllers\UnitController::class, 'create'], ['quick_add' => true]) }}" data-container=".view_modal" title="@lang('unit.add_unit')">
                                        <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-row {{ $locations->count() == 1 ? 'hide' : '' }}">
                        <div class="form-group col-md-6">
                            <label for="quick_product_locations" class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('business.business_locations')<span class="tw-text-red-500">*</span></label>
                            <select id="quick_product_locations" class="form-control js-select2" name="product_locations[]" multiple required data-placeholder="@lang('messages.please_select')">
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ $locations->count() == 1 ? 'selected' : '' }}>{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer tw-border-t tw-border-gray-200 tw-px-4 tw-py-3">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                </div>
            </form>
        </div>
    </div>
 </div>

<div class="modal fade" id="quick-supplier-modal" tabindex="-1" role="dialog" aria-labelledby="quick-supplier-modal-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content tw-rounded-2xl">
            <div class="modal-header tw-border-b tw-border-gray-200 tw-px-4 tw-py-3">
                <h5 class="modal-title tw-text-lg tw-font-semibold tw-text-gray-900" id="quick-supplier-modal-label">@lang('contact.add_supplier')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="quick-supplier-form" class="tw-space-y-4">
                @csrf
                <div class="modal-body tw-space-y-4 tw-px-4 tw-py-3">
                    <div class="form-group">
                        <label for="quick_supplier_first_name" class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('contact.first_name')<span class="tw-text-red-500">*</span></label>
                        <input type="text" class="form-control" id="quick_supplier_first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="quick_supplier_middle_name" class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('contact.middle_name')</label>
                        <input type="text" class="form-control" id="quick_supplier_middle_name" name="middle_name">
                    </div>
                    <div class="form-group">
                        <label for="quick_supplier_mobile" class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('contact.mobile')<span class="tw-text-red-500">*</span></label>
                        <input type="text" class="form-control" id="quick_supplier_mobile" name="mobile" required>
                    </div>
                </div>
                <div class="modal-footer tw-border-t tw-border-gray-200 tw-px-4 tw-py-3">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="services-modal" tabindex="-1" role="dialog" aria-labelledby="services-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
        <div class="modal-content tw-rounded-2xl tw-overflow-hidden">
            <div class="modal-header tw-border-b tw-border-gray-200 tw-px-4 tw-py-3">
                <h5 class="modal-title tw-text-lg tw-font-semibold tw-text-gray-900" id="services-modal-label">
                    <i class="fas fa-tools tw-mr-2"></i>@lang('repair::lang.services')
                </h5>
                <div class="tw-flex tw-items-center tw-gap-2">
                    <button type="button" class="btn btn-primary btn-sm btn-modal" data-href="{{ action([\App\Http\Controllers\ServiceController::class, 'create']) }}" data-container=".services_modal">
                        <i class="fa fa-plus tw-mr-1"></i>@lang('repair::lang.services_add_labour')
                    </button>
                    <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body tw-px-4 tw-py-4 tw-space-y-4">
                <div class="tw-flex tw-flex-col lg:tw-flex-row lg:tw-items-center tw-gap-3">
                    <label for="services-location-filter" class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-0">@lang('business.business_locations')</label>
                    <select id="services-location-filter" class="form-control" multiple data-placeholder="@lang('messages.please_select')">
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="table-responsive tw-rounded-2xl tw-border tw-border-gray-200 tw-shadow-inner">
                    <table class="table table-striped table-borderless tw-mb-0" id="services-modal-table">
                        <thead class="tw-bg-gray-50">
                            <tr class="tw-text-xs tw-uppercase tw-text-gray-500">
                                <th>@lang('repair::lang.services_name')</th>
                                <th>@lang('repair::lang.services_price')</th>
                                <th>@lang('repair::lang.services_workshops')</th>
                                <th>@lang('repair::lang.services_location')</th>
                                <th>@lang('repair::lang.services_labour_hours')</th>
                                <th>@lang('messages.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="tw-text-center tw-text-sm tw-text-gray-500">@lang('repair::lang.loading')</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer tw-border-t tw-border-gray-200 tw-px-4 tw-py-3">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade services_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>

<div class="modal fade category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

<!-- Purchase Orders Modal (re-added) -->
<div class="modal fade" id="purchase-orders-modal" tabindex="-1" role="dialog" aria-labelledby="purchase-orders-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content tw-rounded-2xl">
            <div class="modal-header tw-border-b tw-border-gray-200 tw-px-4 tw-py-3">
                <h5 class="modal-title tw-text-lg tw-font-semibold tw-text-gray-900" id="purchase-orders-modal-label">
                    <i class="fas fa-shopping-cart tw-mr-2"></i>@lang('repair::lang.purchase_orders_for_job_sheet')
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body tw-space-y-4 tw-px-4 tw-py-3">
                <div id="purchase-orders-container" class="tw-space-y-3">
                    <div class="tw-text-center tw-text-sm tw-text-gray-500 tw-py-8">
                        <i class="fas fa-spinner fa-spin tw-mr-2"></i>@lang('repair::lang.loading')
                    </div>
                </div>
            </div>
            <div class="modal-footer tw-border-t tw-border-gray-200 tw-px-4 tw-py-3">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
@php
    $maintenanceNoteTranslations = [
        'loading' => __('repair::lang.loading'),
        'noLines' => __('repair::lang.no_transaction_lines'),
        'fail' => __('repair::lang.failed_to_load'),
        'yes' => __('repair::lang.status_yes'),
        'no' => __('repair::lang.status_no'),
        'delivered' => __('repair::lang.status_delivered'),
        'notDelivered' => __('repair::lang.status_not_delivered'),
        'outForDelivery' => __('repair::lang.status_out_for_delivery'),
        'save' => __('messages.save'),
        'update' => __('messages.update'),
        'cancel' => __('messages.cancel'),
        'searchPlaceholder' => __('repair::lang.search_parts'),
        'confirmDelete' => __('messages.are_you_sure'),
        'productCreateSuccess' => __('product.product_added_success'),
        'productCreateFail' => __('messages.something_went_wrong'),
        'searchSelectPlaceholder' => __('messages.please_select'),
        'servicesSelectLocations' => __('repair::lang.services_select_locations'),
        'poEmpty' => __('repair::lang.no_purchase_orders_found'),
        'poFail' => __('repair::lang.failed_to_load_purchase_orders'),
        'engineer' => __('repair::lang.engineer'),
        'customerService' => __('repair::lang.customer_service'),
        'vehicleLabel' => __('repair::lang.vehicle_label'),
        'vinLabel' => __('repair::lang.vin_label'),
        'plateLabel' => __('repair::lang.plate_label'),
        'colorLabel' => __('repair::lang.color_label'),
        'typeLabel' => __('repair::lang.type_label'),
        'compatibility' => __('lang_v1.compatibility'),
    ];
@endphp
@includeIf('taxonomy.taxonomies_js')
<script>
(function ($) {
    const translations = <?php echo json_encode($maintenanceNoteTranslations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const productSearchUrl = <?php echo json_encode(route('repair.maintenance_notes.products.search')); ?>;
    const addProductUrlTemplate = <?php echo json_encode(route('repair.maintenance_notes.add_product', ['id' => 'NOTE_ID_PLACEHOLDER'])); ?>;
    const lineEndpointTemplate = addProductUrlTemplate.replace(/\/add-product$/, '/line/LINE_ID_PLACEHOLDER');
    const subCategoryUrlTemplate = <?php echo json_encode(route('repair.maintenance_notes.subcategories', ['category' => 'CATEGORY_ID'])); ?>;
    const servicesDatatableUrl = <?php echo json_encode(route('s.datable')); ?>;
    const servicesOptionsUrl = <?php echo json_encode(route('services.options-by-locations')); ?>;
    const servicesFlatRateDetailsUrlTemplate = <?php echo json_encode(route('services.flat-rate-details', ['id' => 'FLAT_RATE_ID'])); ?>;
    const supplierOptions = <?php echo json_encode(
        $suppliers->map(function ($supplier) {
            return [
                'id' => (string) $supplier->id,
                'text' => $supplier->display_name,
            ];
        })->values()->all(),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ); ?>;
    const supplierSearchUrl = <?php echo json_encode(route('repair.maintenance_notes.suppliers.search')); ?>;

    let activeNoteForQuickProduct = null;
    let lastSubcategoryParentId = null;

    // Track which parent category was selected when opening the subcategory modal
    $(document).on('click', '.js-add-product-subcategory', function () {
        const $parentSelect = $('#quick_product_category');
        lastSubcategoryParentId = $parentSelect.val() || null;
    });

    // When the taxonomy create modal is shown, preselect parent for subcategory context
    $('.category_modal').on('shown.bs.modal', function () {
        const $modal = $(this);
        const $categoryType = $modal.find('input[name="category_type"]');

        if ($categoryType.val() === 'product' && lastSubcategoryParentId) {
            const $addAsSub = $modal.find('input[name="add_as_sub_cat"]');
            const $parentSelect = $modal.find('select[name="parent_id"]');

            if ($addAsSub.length) {
                $addAsSub.prop('checked', true).trigger('change');
            }

            if ($parentSelect.length) {
                $parentSelect.val(lastSubcategoryParentId).trigger('change');
            }
        }
    });

    // When a category is added via taxonomy modal, update quick product category/subcategory selects
    window.addEventListener('categoryAdded', function (evt) {
        const cat = evt.detail || {};

        if (!cat.category_type || cat.category_type !== 'product') {
            return;
        }

        const parentId = parseInt(cat.parent_id || 0, 10);
        const $categorySelect = $('#quick_product_category');
        const $subCategorySelect = $('#quick_product_sub_category');

        if (parentId === 0) {
            // Main category
            if ($categorySelect.length) {
                if (!$categorySelect.find('option[value="' + cat.id + '"]').length) {
                    const opt = new Option(cat.name, cat.id, true, true);
                    $categorySelect.append(opt);
                }
                $categorySelect.val(cat.id).trigger('change');
            }
        } else {
            // Subcategory
            if ($subCategorySelect.length) {
                if (!$subCategorySelect.find('option[value="' + cat.id + '"]').length) {
                    const opt = new Option(cat.name, cat.id, true, true);
                    $(opt).attr('data-parent', parentId);
                    $subCategorySelect.append(opt);
                }
                $subCategorySelect.val(cat.id).trigger('change');
            }

            // If no main category selected yet, select the parent
            if ($categorySelect.length && !$categorySelect.val()) {
                $categorySelect.val(parentId).trigger('change');
            }
        }
    }, false);

    // Auto-refresh functionality
    let autoRefreshInterval = null;
    let lastFetchTimestamp = null;
    let existingCards = new Map();

    // Initialize existing cards map
    function initializeExistingCards() {
        existingCards.clear();
        $('.row.tw-g-4 .col-12.col-md-4').each(function() {
            const $card = $(this);
            const noteId = $card.find('.js-open-maintenance-note').data('note-id');
            if (noteId) {
                existingCards.set(noteId, {
                    element: $card,
                    updated_at: $card.data('updated-at') || null
                });
            }
        });
        lastFetchTimestamp = new Date().toISOString();
    }

    // Auto-refresh toggle functionality
    function startAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }

        autoRefreshInterval = setInterval(function() {
            refreshMaintenanceNotes();
        }, 60000); // 60 seconds = 1 minute
    }

    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }

    function updateAutoRefreshIndicator(isActive) {
        // Auto-refresh is always enabled - no UI needed
        return;
    }

    function refreshMaintenanceNotes() {
        const currentFilters = {
            location_id: $('#location_id').val(),
            status: $('#status').val(),
            job_sheet: $('#job_sheet').val()
        };

        $.get('{{ route("repair.maintenance_notes.api") }}', currentFilters)
            .done(function(response) {
                if (response.success && response.notes) {
                    handleNewNotes(response.notes);
                    lastFetchTimestamp = new Date().toISOString();

                    // Show success notification
                   // if (typeof toastr !== 'undefined') {
                       // toastr.info('Maintenance notes refreshed automatically');
                    //}
                }
            })
            .fail(function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to refresh maintenance notes');
                }
            });
    }


    // Services modal logic
    let servicesTable = null;

    function refreshServiceOptionsByLocations(locationIds, $modal) {
        const payload = {};
        if (locationIds && locationIds.length) {
            payload.location_ids = locationIds;
        }

        $.ajax({
            url: servicesOptionsUrl,
            data: payload,
            dataType: 'json'
        }).done(function (res) {
            const $workshops = $modal.find('#workshop_ids');
            if ($workshops.length) {
                let selectedWorkshops = $workshops.val() || [];
                if (!selectedWorkshops.length) {
                    const selectedCsv = $workshops.data('selected-workshops');
                    selectedWorkshops = selectedCsv ? String(selectedCsv).split(',').filter(Boolean) : [];
                }
                $workshops.empty();
                $.each(res.workshops || [], function (_, item) {
                    const isSelected = selectedWorkshops.includes(String(item.id)) || selectedWorkshops.includes(item.id);
                    const option = new Option(item.name, item.id, false, isSelected);
                    $workshops.append(option);
                });
                $workshops.trigger('change.select2');
            }

            const $flatRate = $modal.find('#flat_rate_id');
            if ($flatRate.length) {
                let selectedFlatRate = $flatRate.val() || $flatRate.data('selected-flat-rate') || '';
                $flatRate.empty();
                $flatRate.append(new Option(translations.searchSelectPlaceholder || 'Please select', '', true, false));
                $.each(res.flat_rates || [], function (_, fr) {
                    const isSelected = String(fr.id) === String(selectedFlatRate);
                    const label = fr.name + ' (' + fr.price_per_hour + '/hr)';
                    const option = new Option(label, fr.id, false, isSelected);
                    $(option).attr('data-price-per-hour', fr.price_per_hour);
                    $flatRate.append(option);
                });
                $flatRate.trigger('change.select2');
            }
        }).fail(function () {
            if (typeof toastr !== 'undefined') {
                toastr.error(translations.fail);
            }
        });
    }

    function handlePriceTypeVisibility($modal) {
        const priceType = $modal.find('#price_type').val();
        const $flatRateGroup = $modal.find('#flat_rate_group');
        const $serviceHoursGroup = $modal.find('#service_hours_group');

        if (priceType === 'per_hour') {
            $flatRateGroup.show();
            $serviceHoursGroup.show();
        } else {
            $flatRateGroup.hide();
            $serviceHoursGroup.hide();
        }
    }

    function handleFlatRatePrice($modal) {
        const $flatRate = $modal.find('#flat_rate_id');
        const flatRateId = $flatRate.val();
        if (!flatRateId) {
            return;
        }

        const url = servicesFlatRateDetailsUrlTemplate.replace('FLAT_RATE_ID', flatRateId);
        $.get(url)
            .done(function (data) {
                const pricePerHour = parseFloat(data.price_per_hour) || 0;
                const serviceHours = parseFloat($modal.find('#service_hours').val()) || 1;
                if (pricePerHour > 0) {
                    const calculatedPrice = pricePerHour * serviceHours;
                    $modal.find('#service_price').val(calculatedPrice.toFixed(2));
                }
            })
            .fail(function () {
                if (typeof toastr !== 'undefined') {
                    toastr.error(translations.fail);
                }
            });
    }

    function initializeServiceForm($container) {
        const $form = $container.find('#service_form');
        if (!$form.length) {
            return;
        }

        setTimeout(function () {
            $container.find('#product_locations').each(function () {
                const $this = $(this);
                if (!$this.hasClass('select2-hidden-accessible')) {
                    try {
                        $this.select2({ dropdownParent: $container });
                    } catch (e) {}
                }
            });

            $container.find('#workshop_ids').each(function () {
                const $this = $(this);
                if (!$this.hasClass('select2-hidden-accessible')) {
                    try {
                        $this.select2({ dropdownParent: $container });
                    } catch (e) {}
                }
            });

            $container.find('#flat_rate_id').each(function () {
                const $this = $(this);
                if (!$this.hasClass('select2-hidden-accessible')) {
                    try {
                        $this.select2({ dropdownParent: $container });
                    } catch (e) {}
                }
            });

            const selectedLocations = $container.find('#product_locations').val() || [];
            refreshServiceOptionsByLocations(selectedLocations, $container);

            handlePriceTypeVisibility($container);
        }, 50);

        $container.find('#product_locations').off('change.services').on('change.services', function () {
            const selected = $(this).val() || [];
            refreshServiceOptionsByLocations(selected, $container);
        });

        $container.find('#price_type').off('change.services').on('change.services', function () {
            handlePriceTypeVisibility($container);
        });

        $container.find('#flat_rate_id').off('change.services').on('change.services', function () {
            if ($(this).val()) {
                handleFlatRatePrice($container);
            }
        });

        $container.find('#service_hours').off('change.services').on('change.services', function () {
            if ($container.find('#price_type').val() === 'per_hour' && $container.find('#flat_rate_id').val()) {
                handleFlatRatePrice($container);
            }
        });
    }

    function ensureServicesTable() {
        if (servicesTable) {
            return servicesTable;
        }

        servicesTable = $('#services-modal-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: servicesDatatableUrl,
                data: function (d) {
                    d.location_ids = $('#services-location-filter').val();
                }
            },
            columns: [
                { data: 'name', name: 'products.name' },
                { data: 'selling_price', name: 'selling_price' },
                { data: 'workshop_names', name: 'workshop_names', orderable: false, searchable: false },
                { data: 'location_names', name: 'location_names', orderable: false, searchable: false },
                { data: 'serviceHours', name: 'products.serviceHours' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
             // ✅ Show 10 rows per page by default
    pageLength: 10,

    // ✅ Allow users to change how many rows to display
    lengthMenu: [10, 25, 50, 100],

            drawCallback: function () {
                $('#services-modal-table .btn-modal').attr('data-container', '.services_modal');
            }
        });

        return servicesTable;
    }

    function openServicesModal(locationId) {
        const $modal = $('#services-modal');
        $modal.modal('show');

        const $filter = $('#services-location-filter');
        if (!$filter.hasClass('select2-hidden-accessible')) {
            $filter.select2({
                dropdownParent: $modal,
                width: '100%',
                placeholder: translations.servicesSelectLocations || translations.searchSelectPlaceholder
            });
        }

        if (locationId) {
            const currentVals = $filter.val() || [];
            if (!currentVals.includes(String(locationId))) {
                $filter.val([String(locationId)]).trigger('change');
            }
        }

        ensureServicesTable().ajax.reload();
    }

    $(document).on('click', '.js-open-services', function () {
        const locationId = $(this).data('location-id');
        openServicesModal(locationId);
    });

    $('#services-modal').on('hidden.bs.modal', function () {
        const $container = $('.services_modal');
        $container.html('');
        $container.removeData('trigger-element');
    });

    $(document).on('change', '#services-location-filter', function () {
        if (!servicesTable) {
            return;
        }
        servicesTable.ajax.reload();
    });

    $('.services_modal').on('shown.bs.modal', function () {
        initializeServiceForm($(this));
    });

    $(document).on('submit', '.services_modal #service_form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const method = $form.find('input[name="_method"]').val() || 'POST';
        const url = $form.attr('action');
        const data = $form.serialize();

        $.ajax({
            method: method,
            url: url,
            dataType: 'json',
            data: data,
        }).done(function (result) {
            if (result.success) {
                $('.services_modal').modal('hide');
                if (typeof toastr !== 'undefined') {
                    toastr.success(result.message || translations.productCreateSuccess);
                }
                if (servicesTable) {
                    servicesTable.ajax.reload();
                }
            } else if (typeof toastr !== 'undefined') {
                toastr.error(result.message || translations.productCreateFail);
            }
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                $.each(xhr.responseJSON.errors, function (key, messages) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(messages[0]);
                    }
                });
            } else if (typeof toastr !== 'undefined') {
                toastr.error(translations.productCreateFail);
            }
        });
    });

    $(document).on('click', '.services_modal .delete-service', function (e) {
        e.preventDefault();
        const href = $(this).data('href');
        if (!href) {
            return;
        }
        if (!window.confirm(translations.confirmDelete || 'Are you sure?')) {
            return;
        }

        $.ajax({
            method: 'DELETE',
            url: href,
            dataType: 'json',
            data: { _token: $('meta[name="csrf-token"]').attr('content') }
        }).done(function (result) {
            if (result.success) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(result.message || translations.productCreateSuccess);
                }
                if (servicesTable) {
                    servicesTable.ajax.reload();
                }
            } else if (typeof toastr !== 'undefined') {
                toastr.error(result.message || translations.productCreateFail);
            }
        }).fail(function () {
            if (typeof toastr !== 'undefined') {
                toastr.error(translations.productCreateFail);
            }
        });
    });

    function handleNewNotes(newNotes) {
        let hasChanges = false;
        const $container = $('.row.tw-g-4');

        // Create a set of current note IDs from API response
        const currentNoteIds = new Set(newNotes.map(note => note.id));

        // Remove cards that are no longer in the API response
        $('.row.tw-g-4 .col-12.col-md-4').each(function() {
            const $card = $(this);
            const noteId = $card.find('.js-open-maintenance-note').data('note-id');

            if (noteId && !currentNoteIds.has(noteId)) {
                // Remove the card and its modal
                $card.fadeOut(300, function() {
                    $(this).remove();
                });
                $(`#maintenance-note-modal-${noteId}`).remove();
                existingCards.delete(noteId);
                hasChanges = true;
            }
        });

        // Process each new note
        newNotes.forEach(function(note) {
            const noteId = note.id;
            const existingCard = existingCards.get(noteId);

            if (!existingCard) {
                // New note - add it
                addNewCard($container, note);
                existingCards.set(noteId, {
                    element: $container.find(`[data-note-id="${noteId}"]`).parent(),
                    updated_at: note.updated_at
                });
                hasChanges = true;
            } else if (existingCard.updated_at !== note.updated_at) {
                // Updated note - replace it
                updateExistingCard(existingCard.element, note);
                existingCard.updated_at = note.updated_at;
                hasChanges = true;
            }
        });

        // Show notification if there are changes
        if (hasChanges) {
            showNewCardsNotification();
        }
    }

    function addNewCard($container, note) {
        const statusConfig = note.status_config;
        const modalId = `maintenance-note-modal-${note.id}`;

        const origin = note.origin || 'job_sheet';
        const originLabel = note.origin_label || 'Job Sheet';
        const cardBorderClass = origin === 'job_sheet' ? 'tw-border-blue-200 tw-shadow-blue-100/80' : 'tw-border-purple-200 tw-shadow-purple-100/80';
        const originBadgeClass = origin === 'job_sheet' ? 'tw-bg-blue-100 tw-text-blue-700' : 'tw-bg-purple-100 tw-text-purple-700';

        const displayReference = note.display_reference || '';

        const cardHtml = `
            <div class="col-12 col-md-4 tw-p-3 new-card tw-animate-pulse" data-note-id="${note.id}" data-updated-at="${note.updated_at}" data-location-id="${note.location_id || ''}">
                <div class="tw-bg-white tw-rounded-2xl tw-border tw-shadow-lg tw-p-6 tw-flex tw-flex-col tw-gap-4 tw-h-full my-3 ${cardBorderClass}">
                    <div class="tw-flex tw-items-start tw-justify-between tw-gap-3">
                        <div class="tw-space-y-1">
                            <h2 class="tw-text-base tw-font-semibold tw-text-gray-900 tw-tracking-wide">${escapeHtml(displayReference)}</h2>
                            <span class="tw-inline-flex tw-items-center tw-rounded-full tw-text-[10px] tw-font-semibold tw-px-2 tw-py-0.5 js-origin-badge ${originBadgeClass}">${escapeHtml(originLabel)}</span>
                        </div>
                        <button type="button" class="tw-text-gray-400 tw-text-lg hover:tw-text-gray-600 js-open-maintenance-note" data-note-id="${note.id}" data-target="#${modalId}" aria-label="Reply request">
                            <i class="fas fa-comment-dots"></i>
                        </button>
                    </div>

                    <div class="tw-space-y-3">
                        <div class="tw-text-sm tw-text-gray-500 tw-leading-5">
                            <span class="tw-uppercase tw-tracking-wide tw-text-[11px] tw-font-semibold tw-text-gray-400">${origin === 'job_estimator' ? translations.customerService : translations.engineer}</span><br>
                            <span class="tw-font-semibold tw-text-gray-900">${escapeHtml(note.engineer_name)}</span>
                        </div>

                        <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-4 tw-text-xs">
                            <div class="tw-flex tw-items-center tw-gap-1">
                                <span class="tw-text-gray-400 tw-font-medium">${translations.vehicleLabel}:</span>
                                <span class="tw-text-gray-900 tw-font-semibold">${escapeHtml(note.vehicle_display)}</span>
                            </div>
                            <div class="tw-flex tw-items-center tw-gap-1">
                                <span class="tw-text-gray-400 tw-font-medium">${translations.vinLabel}:</span>
                                <span class="tw-text-gray-900 tw-font-semibold">${escapeHtml(note.vin_display)}</span>
                            </div>
                            <div class="tw-flex tw-items-center tw-gap-1">
                                <span class="tw-text-gray-400 tw-font-medium">${translations.plateLabel}:</span>
                                <span class="tw-text-gray-900 tw-font-semibold">${escapeHtml(note.plate_number || '—')}</span>
                            </div>
                            <div class="tw-flex tw-items-center tw-gap-1">
                                <span class="tw-text-gray-400 tw-font-medium">${translations.colorLabel}:</span>
                                <span class="tw-text-gray-900 tw-font-semibold">${escapeHtml(note.color || '—')}</span>
                            </div>
                            <div class="tw-flex tw-items-center tw-gap-1">
                                <span class="tw-text-gray-400 tw-font-medium">${translations.typeLabel}:</span>
                                <span class="tw-text-gray-900 tw-font-semibold">${escapeHtml(note.car_type || '—')}</span>
                            </div>
                        </div>
                    </div>

                    <div class="tw-border-t tw-border-gray-200 tw-pt-4 tw-mt-auto">
                        <div class="tw-flex tw-items-center tw-justify-between tw-gap-3">
                            <span class="tw-inline-flex tw-items-center tw-font-medium tw-rounded-full tw-text-xs tw-px-3 tw-py-1" style="background-color: ${statusConfig.badge_bg}; color: ${statusConfig.badge_text};">
                                <span class="tw-w-2 tw-h-2 tw-rounded-full tw-mr-2" style="background-color: ${statusConfig.color};"></span>
                                ${escapeHtml(statusConfig.label)}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add the modal structure for this new card
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog" aria-labelledby="${modalId}Label" aria-hidden="true" data-location-id="${note.location_id || ''}">
                <div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
                    <div class="modal-content tw-rounded-2xl tw-overflow-x-auto">
                        <div class="modal-header tw-border-b tw-border-gray-200 tw-px-5 tw-py-4">
                            <h4 class="modal-title tw-text-lg tw-font-semibold tw-text-gray-900" id="${modalId}Label">
                                @lang('repair::lang.maintenance_note_reply_title') - ${escapeHtml(displayReference)}
                            </h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body tw-space-y-6 tw-px-5 tw-py-6">
                            <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-6 tw-bg-white tw-rounded-xl tw-p-6 tw-shadow-sm">
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[50px]">Vehicle:</span>
                                    <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-vehicle">${escapeHtml(note.vehicle_display)}</span>
                                </div>
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[35px]">VIN:</span>
                                    <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-vin">${escapeHtml(note.vin_display)}</span>
                                </div>
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[45px]">Plate:</span>
                                    <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-plate">${escapeHtml(note.plate_number || '—')}</span>
                                </div>
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[45px]">Color:</span>
                                    <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-color">${escapeHtml(note.color || '—')}</span>
                                </div>
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[40px]">Type:</span>
                                    <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-type">${escapeHtml(note.car_type || '—')}</span>
                                </div>
                                ${note.motor_cc ? `
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <span class="tw-text-[11px] tw-font-semibold tw-uppercase tw-text-gray-400 tw-min-w-[40px]">Motor CC:</span>
                                    <span class="tw-text-sm tw-font-semibold tw-text-gray-900 js-note-motor-cc">${escapeHtml(note.motor_cc)}</span>
                                </div>
                                ` : ''}
                            </div>

                            <div class="form-group tw-mb-0">
                                <label class="tw-text-sm tw-font-semibold tw-text-gray-700">@lang('repair::lang.engineer_request')</label>
                                <textarea class="form-control tw-rounded-xl tw-min-h-[120px]" rows="4" readonly>${escapeHtml(note.content || '')}</textarea>
                            </div>

                            <div>
                                <div class="tw-flex tw-flex-col xl:tw-flex-row xl:tw-items-center xl:tw-justify-between tw-gap-3 tw-mb-3">
                                      
                                        <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-stretch sm:tw-items-center my-5 tw-gap-2 tw-w-full xl:tw-w-auto">
                                            <select class="form-control maintenance-note-product-search" data-note-id="${note.id}" id="maintenance-note-product-search-${note.id}" style="width: 100%; min-width: 240px;" data-placeholder="@lang('repair::lang.search_parts')"></select>
                                            <button type="button" class="btn btn-outline-success btn-sm js-open-quick-product" data-note-id="${note.id}">
                                                <i class="fas fa-plus tw-mr-1"></i>@lang('repair::lang.add_new_product')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="table-responsive tw-rounded-2xl tw-border tw-border-gray-200 tw-shadow-inner">
                                        <table class="table table-striped table-borderless tw-mb-0">
                                            <thead class="tw-bg-gray-50">
                                                <tr class="tw-text-xs tw-uppercase tw-text-gray-500">
                                                    <th>@lang('repair::lang.part_name')</th>
                                             
                                                    <th class="tw-text-right">@lang('repair::lang.quantity')</th>
                                                    <th class="tw-text-right">@lang('repair::lang.end_user_price')</th>
                                                    <th class="tw-text-right">@lang('repair::lang.purchase_price')</th>
                                                    <th class="tw-text-right">@lang('repair::lang.supplier_name')</th>
                                                    <th class="tw-text-right">Delivered</th>
                                                    <th class="tw-text-right">@lang('repair::lang.client_approval')</th>
                                                    
                                                    <th class="tw-text-right">@lang('repair::lang.notes')</th>
                                                    <th class="tw-text-right">@lang('messages.actions')</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modal-lines-${note.id}">
                                                <tr>
                                                    <td colspan="9" class="tw-text-center tw-text-sm tw-text-gray-500">@lang('repair::lang.loading')</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="tw-mt-4 tw-flex tw-gap-2 tw-justify-end">
                                        <button type="button" class="btn btn-secondary js-cancel-all-products" data-note-id="${note.id}">
                                            @lang('messages.cancel')
                                        </button>
                                        <button type="button" class="btn btn-primary js-save-all-products" data-note-id="${note.id}">
                                            @lang('messages.save_all')
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add the new card to the beginning of the container
        $container.prepend(cardHtml);

        // Add the modal after the quick product modal
        $('#quick-product-modal').after(modalHtml);

        // Remove the pulse animation after a short delay
        setTimeout(function() {
            $container.find('.new-card').removeClass('tw-animate-pulse');
        }, 2000);
    }

    function updateExistingCard($card, note) {
        const statusConfig = note.status_config;
        const origin = note.origin || 'job_sheet';
        const originLabel = note.origin_label || 'Job Sheet';
        const cardBorderClass = origin === 'job_sheet' ? 'tw-border-blue-200 tw-shadow-blue-100/80' : 'tw-border-purple-200 tw-shadow-purple-100/80';
        const originBadgeClass = origin === 'job_sheet' ? 'tw-bg-blue-100 tw-text-blue-700' : 'tw-bg-purple-100 tw-text-purple-700';

        // Update status badge
        const $statusBadge = $card.find('.js-status-badge');
        if ($statusBadge.length) {
            $statusBadge.attr('style', `background-color: ${statusConfig.badge_bg}; color: ${statusConfig.badge_text};`);
            $statusBadge.find('.js-status-dot').attr('style', `background-color: ${statusConfig.color};`);
            const $statusLabel = $statusBadge.find('.js-status-label');
            if ($statusLabel.length) {
                $statusLabel.text(statusConfig.label);
            }
        }

        // Update origin badge text and classes
        const $originBadge = $card.find('.js-origin-badge');
        if ($originBadge.length) {
            $originBadge.text(originLabel)
                .removeClass('tw-bg-blue-100 tw-text-blue-700 tw-bg-purple-100 tw-text-purple-700')
                .addClass(originBadgeClass);
        }
        // Update card border/shadow classes
        const $wrapper = $card.find('> .tw-bg-white.tw-rounded-2xl.tw-border');
        $wrapper.removeClass('tw-border-blue-200 tw-shadow-blue-100/80 tw-border-purple-200 tw-shadow-purple-100/80')
               .addClass(cardBorderClass);

        // Add a subtle update indicator
        $card.addClass('tw-ring-2 tw-ring-blue-300 tw-ring-opacity-50');
        setTimeout(function() {
            $card.removeClass('tw-ring-2 tw-ring-blue-300 tw-ring-opacity-50');
        }, 2000);
    }

    function showNewCardsNotification() {
        // Show a toast notification for new cards
        // if (typeof toastr !== 'undefined') {
        //     toastr.success('New maintenance notes available!');
        // }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initializeExistingCards();

        // Start auto-refresh immediately and always keep it on
        // startAutoRefresh();

        // Stop auto-refresh when filters change and restart after a delay
        $('#location_id, #status, #job_sheet').on('change', function() {
            if (autoRefreshInterval) {
                stopAutoRefresh();
                // Reinitialize after filter change and restart auto-refresh
                setTimeout(function() {
                    initializeExistingCards();
                    startAutoRefresh();
                }, 1000);
            }
        });
    });

    function escapeHtml(value) {
        return $('<div/>').text(value == null ? '' : value).html();
    }

    function formatNumber(value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }
        const num = Number(value);
        if (Number.isNaN(num)) {
            return escapeHtml(value);
        }
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function renderOutForDeliverBadge(item) {
        const isOutForDeliver = Number(item.out_for_deliver) === 1;
        const label = isOutForDeliver ? (translations.outForDelivery || 'Out for delivery') : (translations.notOutForDelivery || 'Not out for delivery');
        const classes = isOutForDeliver ? 'tw-bg-amber-100 tw-text-amber-600' : 'tw-bg-gray-100 tw-text-gray-500';

        return '<span class="tw-inline-flex tw-items-center tw-rounded-full tw-text-xs tw-font-semibold tw-px-3 tw-py-1 ' + classes + '">' + escapeHtml(label) + '</span>';
    }

    function buildSupplierSelect(selectClass, selectedId) {
        const placeholderOption = '<option value="">' + escapeHtml(translations.searchSelectPlaceholder) + '</option>';
        const options = supplierOptions.map(function (option) {
            const isSelected = String(option.id) === String(selectedId || '');
            return '<option value="' + escapeHtml(option.id) + '"' + (isSelected ? ' selected' : '') + '>' + escapeHtml(option.text) + '</option>';
        }).join('');

        return '<select class="form-control form-control-sm ' + selectClass + '">' + placeholderOption + options + '</select>';
    }

    function renderApprovalBadge(item) {
        const approved = Number(item.client_approval) === 1;
        const label = approved ? translations.yes : translations.no;
        const classes = approved ? 'tw-bg-emerald-100 tw-text-emerald-600' : 'tw-bg-gray-100 tw-text-gray-500';
        return '<span class="tw-inline-flex tw-items-center tw-rounded-full tw-text-xs tw-font-semibold tw-px-3 tw-py-1 ' + classes + '">' + escapeHtml(label) + '</span>';
    }
    function renderTransactionRows($tbody, items, noteId) {
        if (!items.length) {
            $tbody.html('<tr><td colspan="9" class="tw-text-center tw-text-sm tw-text-gray-500">' + escapeHtml(translations.noLines) + '</td></tr>');
            return;
        }

        const rows = items.map(function (item) {
            const partName = item.part_name ? escapeHtml(item.part_name) : '—';
            const sku = item.part_sku ? escapeHtml(item.part_sku) : '—';
            const quantity = item.quantity ?? '—';
            const quantityRaw = item.quantity ?? '';
            const endUserPrice = formatNumber(item.end_user_price);
            const endUserPriceRaw = item.end_user_price ?? '';
            const purchasePrice = formatNumber(item.purchase_price);
            const purchasePriceRaw = item.purchase_price ?? '';
            const supplierIdRaw = item.supplier_id ?? '';
            const supplierDisplayRaw = item.supplier_display ?? item.Supplier_Name ?? '';
            const supplierName = supplierDisplayRaw ? escapeHtml(supplierDisplayRaw) : '—';
            const supplierNameRaw = supplierDisplayRaw;
            const contactPersonRaw = item.Contact_Person ?? '';
            const clientApprovalRaw = item.client_approval ?? 0;
            const outForDeliverRaw = item.out_for_deliver ?? 0;

            const notes = item.Notes ? escapeHtml(item.Notes) : '—';
            const notesRaw = item.Notes ?? '';
            const lineId = item.line_id;

            return '<tr class="js-line-row" data-line-id="' + escapeHtml(lineId) + '" data-note-id="' + escapeHtml(noteId) + '" data-product-id="' + escapeHtml(item.product_id) + '" data-quantity="' + escapeHtml(quantityRaw) + '" data-purchase-price="' + escapeHtml(purchasePriceRaw) + '" data-supplier-id="' + escapeHtml(supplierIdRaw) + '" data-supplier-name="' + escapeHtml(supplierNameRaw) + '" data-contact-person="' + escapeHtml(contactPersonRaw) + '" data-notes="' + escapeHtml(notesRaw) + '" data-end-user-price="' + escapeHtml(endUserPriceRaw) + '" data-client-approval="' + escapeHtml(clientApprovalRaw) + '" data-out-for-deliver="' + escapeHtml(outForDeliverRaw) + '">' +
                '<td class="tw-align-middle">' + partName + ' - ' + sku + '</td>' +
                '<td class="tw-text-right tw-align-middle">' + escapeHtml(quantity) + '</td>' +
                '<td class="tw-text-right tw-align-middle">' + endUserPrice + '</td>' +
                '<td class="tw-text-right tw-align-middle">' + purchasePrice + '</td>' +
                '<td class="tw-text-right tw-align-middle">' + supplierName + '</td>' +
                '<td class="tw-text-right tw-align-middle">' + renderOutForDeliverBadge(item) + '</td>' +
                '<td class="tw-text-right tw-align-middle">' + renderApprovalBadge(item) + '</td>' +
                '<td class="tw-text-right tw-align-middle">' + notes + '</td>' +
                '<td class="tw-text-right tw-align-middle tw-space-x-1 nowrap">' +
                    '<button type="button" class="btn btn-outline-secondary btn-sm js-edit-line" data-line-id="' + escapeHtml(lineId) + '"><i class="fas fa-edit"></i></button>' +
                    '<button type="button" class="btn btn-outline-danger btn-sm js-delete-line" data-line-id="' + escapeHtml(lineId) + '"><i class="fas fa-trash"></i></button>' +
                '</td>' +
                '</tr>';
        });

        $tbody.html(rows.join(''));
    }

    function setMetaText($modal, selector, value) {
        const $el = $modal.find(selector);
        if (!$el.length) {
            return;
        }
        const textValue = value === null || value === undefined || value === '' ? '—' : value;
        $el.text(textValue);
    }

    function updateNoteMeta($modal, note) {
        if (!note) {
            return;
        }

        setMetaText($modal, '.js-note-vehicle', note.vehicle_display);
        setMetaText($modal, '.js-note-vin', note.vin_display);
        setMetaText($modal, '.js-note-plate', note.plate_number);
        setMetaText($modal, '.js-note-color', note.color);
        setMetaText($modal, '.js-note-type', note.car_type);
        setMetaText($modal, '.js-note-motor-cc', note.motor_cc);
    }

    function refreshTransactionLines(noteId) {
        const $modal = $('.modal').has('#modal-lines-' + noteId);
        const $tbody = $modal.find('#modal-lines-' + noteId);

        $tbody.find('tr.js-draft-row').remove();
        $tbody.html('<tr><td colspan="9" class="tw-text-center tw-text-sm tw-text-gray-500">' + escapeHtml(translations.loading) + '</td></tr>');

        const dataUrl = '{{ route("repair.maintenance_notes.data", ":id") }}'.replace(':id', noteId);
        $.get(dataUrl)
            .done(function (resp) {
                if (resp && resp.success) {
                    const items = resp.line_items || [];
                    renderTransactionRows($tbody, items, noteId);
                } else {
                    const msg = (resp && resp.message) ? resp.message : translations.fail;
                    $tbody.html('<tr><td colspan="9" class="tw-text-center tw-text-sm tw-text-red-600">' + escapeHtml(msg) + '</td></tr>');
                }
            })
            .fail(function () {
                $tbody.html('<tr><td colspan="9" class="tw-text-center tw-text-sm tw-text-red-600">' + escapeHtml(translations.fail) + '</td></tr>');
            })
            .always(function () {
                updateSaveAllButtonState(noteId, $modal);
            });
    }

    // Initialize a supplier <select> with Select2 AJAX bound to current note/location context
    function initSupplierSelect($select, noteId, $modal) {
        if (!$select || !$select.length) return;
        // Destroy only if Select2 is already attached
        if ($select.hasClass('select2-hidden-accessible') || $select.data('select2')) {
            try { $select.select2('destroy'); } catch (e) { /* ignore */ }
        }
        // Initialize once
        if ($select.data('select2')) {
            return;
        }
        $select.select2({
            width: '100%',
            dropdownParent: $modal,
            placeholder: translations.searchSelectPlaceholder || 'Please select',
            allowClear: true,
            ajax: {
                url: supplierSearchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '',
                        note_id: noteId || '',
                        location_id: ($modal && $modal.data('location-id')) ? $modal.data('location-id') : ''
                    };
                },
                processResults: function (data) {
                    const results = Array.isArray(data && data.results) ? data.results : [];
                    return { results: results };
                },
                cache: true
            }
        });
    }

    function initSelect2Dropdowns($modal) {
        $modal.find('.js-select2').each(function () {
            const $select = $(this);
            if ($select.data('select2')) {
                return;
            }

            $select.select2({
                dropdownParent: $modal,
                width: '100%',
                placeholder: $select.data('placeholder') || translations.searchSelectPlaceholder,
                allowClear: true,
            });
        });
    }

    function populateSubCategories($subCategorySelect, parentId) {
        const placeholder = $subCategorySelect.data('placeholder') || translations.searchSelectPlaceholder;

        $subCategorySelect.empty();

        const placeholderOption = new Option(placeholder, '', false, false);
        $subCategorySelect.append(placeholderOption);
        $subCategorySelect.prop('disabled', true);

        if (!parentId) {
            $subCategorySelect.val('').trigger('change.select2');
            return;
        }

        const url = subCategoryUrlTemplate.replace('CATEGORY_ID', parentId);

        $.get(url)
            .done(function (resp) {
                if (resp && resp.success && Array.isArray(resp.sub_categories)) {
                    resp.sub_categories.forEach(function (option) {
                        const opt = new Option(option.name, option.id, false, false);
                        $subCategorySelect.append(opt);
                    });
                    $subCategorySelect.prop('disabled', false);
                }
            })
            .fail(function () {
                if (typeof toastr !== 'undefined') {
                    toastr.error(translations.fail);
                }
            })
            .always(function () {
                $subCategorySelect.val('').trigger('change.select2');
            });
    }

    function initProductSearch(noteId, $modal) {
        initSelect2Dropdowns($modal);

        const $categorySelect = $modal.find('#quick_product_category');
        const $subCategorySelect = $modal.find('#quick_product_sub_category');

        $categorySelect.on('change', function () {
            populateSubCategories($subCategorySelect, $categorySelect.val());
        });

        const locationId = ($modal && $modal.data('location-id')) ? String($modal.data('location-id')) : ($('.col-12.col-md-4[data-note-id="' + noteId + '"]').data('location-id') || '');

        // Initialize both selects (stock-enabled and non-stock) within this modal
        $modal.find('.maintenance-note-product-search').each(function () {
            const $select = $(this);
            if ($select.data('select2')) { return; }

            const enableStock = $select.data('enable-stock');

            $select.select2({
                ajax: {
                    url: productSearchUrl,
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            location_id: locationId,
                            enable_stock: (enableStock === 0 || enableStock === '0') ? 0 : 1
                        };
                    },
                    processResults: function (data) {
                        const products = (data && data.products) ? data.products : [];
                        return { results: products };
                    }
                },
                dropdownParent: $modal,
                placeholder: translations.searchPlaceholder,
                minimumInputLength: 1,
                dropdownCssClass: 'maintenance-product-search-dropdown',
                closeOnSelect: false,
                templateResult: function (product) {
                    if (!product.id) { return product.text; }
                    const name = escapeHtml(product.name || product.text || '');
                    const sku = product.sku ? escapeHtml(product.sku) : '';
                    const brand = product.brand_name ? escapeHtml(product.brand_name) : '';
                    const category = product.category_name ? escapeHtml(product.category_name) : '';
                    const price = product.price !== undefined ? escapeHtml(String(product.price)) : '';
                    const cost = (product.purchase_price !== undefined && product.purchase_price !== null) ? escapeHtml(String(product.purchase_price)) : '';
                    const qty = product.qty_available !== undefined ? escapeHtml(String(product.qty_available)) : '';
                    const compatibility = product.compatibility ? escapeHtml(product.compatibility) : '';

                    let html = '<div class="tw-flex tw-flex-col tw-space-y-0.5">';
                    html += '<div class="tw-flex tw-items-center tw-justify-between tw-text-sm tw-text-gray-900">';
                    html += '<span class="tw-font-medium">' + name + '</span>';
                    if (sku) {
                        html += '<span class="tw-text-xs tw-text-gray-500">SKU: ' + sku + '</span>';
                    }
                    html += '</div>';

                    const infoChips = [];
                    if (brand) infoChips.push('<span class="tw-bg-gray-100 tw-text-gray-600 tw-rounded-full tw-px-2 tw-py-0.5 tw-text-xs">Brand: ' + brand + '</span>');
                    if (category) infoChips.push('<span class="tw-bg-gray-100 tw-text-gray-600 tw-rounded-full tw-px-2 tw-py-0.5 tw-text-xs">Category: ' + category + '</span>');
                    if (qty) infoChips.push('<span class="tw-bg-blue-50 tw-text-blue-600 tw-rounded-full tw-px-2 tw-py-0.5 tw-text-xs">Qty: ' + qty + '</span>');
                    if (infoChips.length) {
                        html += '<div class="tw-flex tw-flex-wrap tw-gap-1">' + infoChips.join('') + '</div>';
                    }

                    if (compatibility) {
                        const compatLabel = translations.compatibility || 'Compatibility';
                        html += '<div class="tw-text-[11px] tw-text-gray-600 tw-mt-0.5">' + escapeHtml(compatLabel) + ': ' + compatibility + '</div>';
                    }

                    const pricingBits = [];
                    if (price) pricingBits.push('<span class="tw-text-xs tw-text-green-600 tw-font-semibold">Sell: ' + price + '</span>');
                    if (cost) pricingBits.push('<span class="tw-text-xs tw-text-amber-600 tw-font-semibold">Cost: ' + cost + '</span>');
                    if (pricingBits.length) {
                        html += '<div class="tw-flex tw-gap-3">' + pricingBits.join('') + '</div>';
                    }

                    html += '</div>';
                    return $(html);
                },
                templateSelection: function (product) { return product.text || product.name || ''; },
                width: '100%'
            });

            $select.on('select2:select', function (e) {
                const product = e.params.data || {};
                addDraftRow(noteId, product, $modal);
                $select.select2('close');
            });

            $select.on('select2:open', function () {
                const $dropdown = $('.select2-dropdown--open');
                $dropdown.on('mousedown', function (e) {
                    e.stopPropagation();
                });
            });
        });
    }

    function updateSaveAllButtonState(noteId, $modal) {
        const $targetModal = $modal && $modal.length ? $modal : $('#maintenance-note-modal-' + noteId);
        if (!$targetModal.length) {
            return;
        }

        // Enable "Save all" whenever there is at least one line (draft, edit, or saved).
        const hasAnyRows =
            $targetModal.find('tr.js-draft-row').length > 0 ||
            $targetModal.find('tr.js-edit-row').length > 0 ||
            $targetModal.find('tr.js-line-row').length > 0;

        const $saveAllBtn = $targetModal.find('.js-save-all-products');
        if ($saveAllBtn.length) {
            $saveAllBtn.prop('disabled', !hasAnyRows);
        }
    }

    function addDraftRow(noteId, product, $modal) {

        if (!product || !product.id) {
            return;
        }

        const $tbody = $modal.find('#modal-lines-' + noteId);
        const productId = String(product.id);
        
        // Check if product already exists in draft rows
        const $existingRow = $tbody.find('tr.js-draft-row[data-product-id="' + productId + '"]');
        if ($existingRow.length > 0) {
            // Product already exists, increment quantity
            const $qtyInput = $existingRow.find('.js-draft-quantity');
            const currentQty = Number($qtyInput.val()) || 1;
            $qtyInput.val(currentQty + 1);
            
            // Highlight the row to show it was updated
            $existingRow.addClass('tw-bg-yellow-50').delay(1500).queue(function(next) {
                $(this).removeClass('tw-bg-yellow-50');
                next();
            });
            
            if (typeof toastr !== 'undefined') {
                toastr.info('Quantity increased for ' + (product.name || product.text));
            }
            return;
        }
        
        // Remove only the empty placeholder row if it exists
        const $emptyRow = $tbody.find('tr:has(td[colspan])');
        if ($emptyRow.length > 0) {
            $emptyRow.remove();
        }

        const productName = product.name ? escapeHtml(product.name) : escapeHtml(product.text || '');
        const productSku = product.sku ? escapeHtml(product.sku) : '—';
        const endUserPrice = product.price !== undefined ? escapeHtml(product.price) : '—';
        const purchasePricePrefill = (product.purchase_price !== undefined && product.purchase_price !== null) ? String(product.purchase_price) : '';
        const supplierSelect = buildSupplierSelect('js-draft-supplier-id', '');

        // Draft rows always start as "not out for delivery"; this column is a
        // read-only badge for now. The real client approval control lives in
        // the next column and is the only one posted back to the server.
        const deliveredBadge = renderOutForDeliverBadge({ out_for_deliver: 0 });
        const clientApprovalSelect = `
            <select class="form-control form-control-sm js-draft-client-approval">
                <option value="0">${escapeHtml(translations.no)}</option>
                <option value="1">${escapeHtml(translations.yes)}</option>
            </select>
        `;

        const rowHtml = '<tr class="js-draft-row" data-note-id="' + noteId + '" data-product-id="' + escapeHtml(product.id) + '">' +
            '<td class="tw-align-middle">' + productName + ' - ' + productSku + '</td>' +
            '<td class="tw-text-right tw-align-middle"><input type="number" min="1" value="1" class="form-control form-control-sm js-draft-quantity"></td>' +
            '<td class="tw-text-right tw-align-middle"><input type="text" value="' + endUserPrice + '" class="form-control form-control-sm js-draft-end-user-price"></td>' +
            '<td class="tw-text-right tw-align-middle"><input type="number" step="0.01" min="0" class="form-control form-control-sm js-draft-purchase-price" value="' + escapeHtml(purchasePricePrefill) + '"></td>' +
            '<td class="tw-text-right tw-align-middle">' + supplierSelect + '</td>' +
            '<td class="tw-text-right tw-align-middle">' + deliveredBadge + '</td>' +
            '<td class="tw-text-right tw-align-middle">' + clientApprovalSelect + '</td>' +
            '<td class="tw-text-right tw-align-middle"><input type="text" class="form-control form-control-sm js-draft-notes"></td>' +
            '<td class="tw-text-right tw-align-middle tw-space-x-1">' +
                '<button type="button" class="btn btn-link text-danger js-cancel-draft-product">' + escapeHtml(translations.cancel) + '</button>' +
            '</td>' +
            '</tr>';

        // Prepend new draft row
        $tbody.prepend(rowHtml);

        // Initialize supplier select as Select2 with AJAX
        try {
            const $newRow = $tbody.find('tr.js-draft-row').first();
            initSupplierSelect($newRow.find('.js-draft-supplier-id'), noteId, $modal);
        } catch (e) { /* no-op */ }
        
        updateSaveAllButtonState(noteId, $modal);
    }

    function submitDraftRow($row) {
        const noteId = $row.data('note-id');
        const productId = $row.data('product-id');
        const quantity = Number($row.find('.js-draft-quantity').val()) || 1;
        const endUserPrice = $row.find('.js-draft-end-user-price').val();
        const purchasePrice = $row.find('.js-draft-purchase-price').val();
        const supplierId = $row.find('.js-draft-supplier-id').val();
        const supplierName = supplierId ? $row.find('.js-draft-supplier-id option:selected').text() : '';
        const contactPerson = $row.find('.js-draft-contact-person').val();
        const notes = $row.find('.js-draft-notes').val();
        const clientApproval = $row.find('.js-draft-client-approval').val() || '0';

        if (!noteId || !productId) {
            return;
        }

        const postUrl = addProductUrlTemplate.replace('NOTE_ID_PLACEHOLDER', noteId);

        // Ensure focus is not inside a soon-to-be removed element
        try { if (document.activeElement) { $(document.activeElement).blur(); } } catch(e){}
        $.ajax({
            method: 'POST',
            url: postUrl,
            data: {
                product_id: productId,
                quantity: quantity,
                price: endUserPrice,
                purchase_price: purchasePrice,
                supplier_id: supplierId,
                supplier_name: supplierName,
                contact_person: contactPerson,
                notes: notes,
                client_approval: clientApproval
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
            .done(function (resp) {
                if (resp && resp.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(resp.message || '');
                    }
                    refreshTransactionLines(noteId);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error((resp && resp.message) ? resp.message : translations.fail);
                    }
                }
            })
            .fail(function () {
                if (typeof toastr !== 'undefined') {
                    toastr.error(translations.fail);
                }
            })
            .always(function () {
                $row.remove();
                const $modal = $('.modal').has('#modal-lines-' + noteId);
                updateSaveAllButtonState(noteId, $modal);
            });
    }

    function buildLineEndpoint(noteId, lineId) {
        return lineEndpointTemplate
            .replace('NOTE_ID_PLACEHOLDER', noteId)
            .replace('LINE_ID_PLACEHOLDER', lineId);
    }

    function enterEditMode($row) {

        if (!$row.length) {
            return;
        }

        const noteId = $row.data('note-id');
        const lineId = $row.data('line-id');
        const productId = $row.data('product-id') || '';
        const quantity = $row.data('quantity') || 1;
        const purchasePrice = $row.data('purchase-price') || '';
        const supplierId = $row.data('supplier-id') || '';
        const supplierName = $row.data('supplier-name') || '';
        const contactPerson = $row.data('contact-person') || '';
        const notes = $row.data('notes') || '';
        const endUserPrice = $row.data('end-user-price') || '';
        const clientApproval = $row.data('clientApproval') ?? $row.data('client-approval') ?? 0;
        const productCell = $row.find('td').eq(0).html();
        const supplierSelect = buildSupplierSelect('js-edit-supplier-id', supplierId);
        const outForDeliver = $row.data('outForDeliver') ?? $row.data('out-for-deliver') ?? 0;
        const outForDeliverSelect = `
            <select class="form-control form-control-sm js-edit-out-for-deliver">
                <option value="0" ${Number(outForDeliver) === 0 ? 'selected' : ''}>${escapeHtml(translations.no)}</option>
                <option value="1" ${Number(outForDeliver) === 1 ? 'selected' : ''}>${escapeHtml(translations.yes)}</option>
            </select>
        `;
        const approvalSelect = `
            <select class="form-control form-control-sm js-edit-client-approval">
                <option value="0" ${Number(clientApproval) === 0 ? 'selected' : ''}>${escapeHtml(translations.no)}</option>
                <option value="1" ${Number(clientApproval) === 1 ? 'selected' : ''}>${escapeHtml(translations.yes)}</option>
            </select>
        `;

        const editRowHtml = '<tr class="js-edit-row" data-note-id="' + escapeHtml(noteId) + '" data-line-id="' + escapeHtml(lineId) + '" data-product-id="' + escapeHtml(productId) + '">' +
            '<td class="tw-align-middle">' + productCell + '</td>' +
            '<td class="tw-text-right tw-align-middle"><input type="number" min="1" value="' + escapeHtml(quantity) + '" class="form-control form-control-sm js-edit-quantity"></td>' +
            '<td class="tw-text-right tw-align-middle"><input type="text" value="' + escapeHtml(endUserPrice) + '" class="form-control form-control-sm js-edit-end-user-price"></td>' +
            '<td class="tw-text-right tw-align-middle"><input type="number" step="0.01" min="0" class="form-control form-control-sm js-edit-purchase-price" value="' + escapeHtml(purchasePrice) + '"></td>' +
            '<td class="tw-text-right tw-align-middle">' + supplierSelect + '</td>' +
            '<td class="tw-text-right tw-align-middle">' + outForDeliverSelect + '</td>' +
            '<td class="tw-text-right tw-align-middle">' + approvalSelect + '</td>' +
            '<td class="tw-text-right tw-align-middle"><input type="text" class="form-control form-control-sm js-edit-notes" value="' + escapeHtml(notes) + '"></td>' +
            '<td class="tw-text-right tw-align-middle tw-space-x-1 nowrap">' +
                '<button type="button" class="btn btn-link text-danger js-cancel-edit-line">' + escapeHtml(translations.cancel) + '</button>' +
            '</td>' +
            '</tr>';

        // Blur focused element before replacing the row to avoid focus on a removed node
        try { if (document.activeElement) { $(document.activeElement).blur(); } } catch(e){}
        $row.replaceWith(editRowHtml);

        // Initialize supplier select as Select2 with AJAX
        try {
            const noteIdSafe = noteId;
            const $modal = $('.modal').has('#modal-lines-' + noteIdSafe);
            const $newRow = $('.js-edit-row[data-line-id="' + lineId + '"]');
            initSupplierSelect($newRow.find('.js-edit-supplier-id'), noteIdSafe, $modal);
        } catch (e) { /* no-op */ }

        const $modalForState = $('.modal').has('#modal-lines-' + noteId);
        updateSaveAllButtonState(noteId, $modalForState);
    }

    function submitEditRow($row) {
        const noteId = $row.data('note-id');
        const lineId = $row.data('line-id');
        if (!noteId || !lineId) {
            return;
        }

        const clientApproval = $row.find('.js-edit-client-approval').val() || '0';

        const payload = {
            quantity: Number($row.find('.js-edit-quantity').val()) || 1,
            price: $row.find('.js-edit-end-user-price').val(),
            purchase_price: $row.find('.js-edit-purchase-price').val(),
            supplier_id: $row.find('.js-edit-supplier-id').val(),
            supplier_name: (function () {
                const $select = $row.find('.js-edit-supplier-id');
                return $select.val() ? $select.find('option:selected').text() : '';
            })(),
            client_approval: clientApproval,
            out_for_deliver: $row.find('.js-edit-out-for-deliver').val() || '0',
            notes: $row.find('.js-edit-notes').val()
        };

        const url = buildLineEndpoint(noteId, lineId);

        $.ajax({
            method: 'PUT',
            url: url,
            data: payload,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
            .done(function (resp) {
                if (resp && resp.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(resp.message || '');
                    }
                    refreshTransactionLines(noteId);
                } else if (typeof toastr !== 'undefined') {
                    toastr.error((resp && resp.message) ? resp.message : translations.fail);
                }
            })
            .fail(function (xhr) {
                if (typeof toastr !== 'undefined') {
                    let msg = translations.fail;
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    toastr.error(msg);
                }
            });
    }

    function deleteLine(noteId, lineId) {
        if (!noteId || !lineId) {
            return;
        }

        if (!window.confirm(translations.confirmDelete || 'Are you sure?')) {
            return;
        }

        const url = buildLineEndpoint(noteId, lineId);

        $.ajax({
            method: 'DELETE',
            url: url,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
            .done(function (resp) {
                if (resp && resp.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(resp.message || '');
                    }
                    refreshTransactionLines(noteId);
                } else if (typeof toastr !== 'undefined') {
                    toastr.error((resp && resp.message) ? resp.message : translations.fail);
                }
            })
            .fail(function () {
                if (typeof toastr !== 'undefined') {
                    toastr.error(translations.fail);
                }
            });
    }


    $(document).on('click', '.js-open-maintenance-note', function () {
        const id = $(this).data('note-id');
        const target = $(this).data('target');
        const $modal = $(target);
        const $tbody = $modal.find('tbody#modal-lines-' + id);

        // Blur current active element to avoid keeping focus on a soon-to-be hidden ancestor
        try { if (document.activeElement) { $(document.activeElement).blur(); } } catch(e){}
        $modal.modal('show');
        $modal.attr('aria-hidden', 'false');
        if (!$modal.attr('tabindex')) { $modal.attr('tabindex', '-1'); }
        try { $modal.trigger('focus'); } catch(e){}
        $tbody.html('<tr><td colspan="8" class="tw-text-center tw-text-sm tw-text-gray-500">' + escapeHtml(translations.loading) + '</td></tr>');

        initProductSearch(id, $modal);

        const url = '{{ route("repair.maintenance_notes.data", ":id") }}'.replace(':id', id);
        $.get(url)
            .done(function (resp) {
                if (resp && resp.success) {
                    updateNoteMeta($modal, resp.note);
                    const items = resp.line_items || [];
                    renderTransactionRows($tbody, items, id);
                } else {
                    const msg = (resp && resp.message) ? resp.message : translations.fail;
                    $tbody.html('<tr><td colspan="8" class="tw-text-center tw-text-sm tw-text-red-600">' + escapeHtml(msg) + '</td></tr>');
                }
            })
            .fail(function () {
                $tbody.html('<tr><td colspan="8" class="tw-text-center tw-text-sm tw-text-red-600">' + escapeHtml(translations.fail) + '</td></tr>');
            });
    });

    $(document).on('click', '.js-save-draft-product', function () {
        const $row = $(this).closest('tr');
        submitDraftRow($row);
    });

    $(document).on('click', '.js-cancel-draft-product', function () {
        const $row = $(this).closest('tr');
        $row.remove();
        const noteId = $row.data('note-id');
        if (noteId) {
            const $modal = $('.modal').has('#modal-lines-' + noteId);
            updateSaveAllButtonState(noteId, $modal);
        }
    });

    $(document).on('click', '.js-edit-line', function () {
        const $row = $(this).closest('tr');
        enterEditMode($row);
    });

    $(document).on('click', '.js-cancel-edit-line', function () {
        const $row = $(this).closest('tr');
        const noteId = $row.data('note-id');
        if (noteId) {
            refreshTransactionLines(noteId);
        } else {
            $row.remove();
        }
    });

    $(document).on('click', '.js-save-edit-line', function () {
        const $row = $(this).closest('tr');
        submitEditRow($row);
    });

    $(document).on('click', '.js-delete-line', function () {
        const $row = $(this).closest('tr');
        const noteId = $row.data('note-id');
        const lineId = $row.data('line-id');
        deleteLine(noteId, lineId);
    });

    // Batch save all products
    $(document).on('click', '.js-save-all-products', function () {
        const noteId = $(this).data('note-id');
        const $modal = $('#maintenance-note-modal-' + noteId);
        const $tbody = $modal.find('#modal-lines-' + noteId);

        // Collect all rows (both saved and draft)
        const rows = $tbody.find('tr.js-draft-row, tr.js-edit-row, tr.js-line-row');

        const productsData = [];
        rows.each(function () {
            const $row = $(this);
            const productId = $row.data('product-id');
            if (!productId) {
                return;
            }
            function toCamel(str) {
                return String(str).replace(/-([a-z])/g, function (g) { return g[1].toUpperCase(); });
            }

            function valOrData(selector, dataKey, fallback = '') {
                const $el = $row.find(selector);
                if ($el.length) {
                    const v = $el.val();
                    if (v !== undefined && v !== null && v !== '') return v;
                }
                let dataVal = $row.data(dataKey);
                if (dataVal === undefined) {
                    dataVal = $row.data(toCamel(dataKey));
                }
                return (dataVal !== undefined && dataVal !== null && dataVal !== '') ? dataVal : fallback;
            }

            const quantity = Number(valOrData('.js-draft-quantity, .js-edit-quantity', 'quantity', 1)) || 1;
            const price = valOrData('.js-draft-end-user-price, .js-edit-end-user-price', 'end-user-price', 0) || 0;
            const purchasePrice = valOrData('.js-draft-purchase-price, .js-edit-purchase-price', 'purchase-price', null);
            const supplierIdRaw = valOrData('.js-draft-supplier-id, .js-edit-supplier-id', 'supplier-id', '');
            const supplierId = supplierIdRaw === '' ? null : supplierIdRaw;
            const clientApproval = String(valOrData('.js-draft-client-approval, .js-edit-client-approval', 'client-approval', '0'));
            const notes = valOrData('.js-draft-notes, .js-edit-notes', 'notes', '');
            const lineId = $row.data('line-id') || null;

            productsData.push({
                product_id: productId,
                line_id: lineId,
                quantity: quantity,
                price: price,
                purchase_price: purchasePrice,
                supplier_id: supplierId,
                client_approval: clientApproval,
                notes: notes
            });
        });

        // if (productsData.length === 0) {
        //     if (typeof toastr !== 'undefined') {
        //         toastr.warning('No valid products to save');
        //     }
        //     return;
        // }

        // Send batch save request
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin tw-mr-1"></i>Saving...');

        $.ajax({
            method: 'POST',
            url: <?php echo json_encode(route('repair.maintenance_notes.batch_save', ['id' => 'NOTE_ID_PLACEHOLDER'])); ?>.replace('NOTE_ID_PLACEHOLDER', noteId),
            data: JSON.stringify({ data: productsData }),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
            .done(function (resp) {
                if (resp && resp.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(resp.message || 'Products saved successfully');
                    }
                    // Refresh the table
                    refreshTransactionLines(noteId);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error((resp && resp.message) ? resp.message : translations.fail);
                    }
                }
            })
            .fail(function (xhr) {
                if (typeof toastr !== 'undefined') {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to save products';
                    toastr.error(errorMsg);
                }
            })
            .always(function () {
                $btn.prop('disabled', false).html(originalText);
            });
    });

    // Cancel all draft products
    $(document).on('click', '.js-cancel-all-products', function () {
        const noteId = $(this).data('note-id');
        const $modal = $('#maintenance-note-modal-' + noteId);
        const $tbody = $modal.find('#modal-lines-' + noteId);

        // Remove only draft rows
        $tbody.find('tr.js-draft-row').remove();

        // Refresh Save All button enabled/disabled state based on remaining rows
        updateSaveAllButtonState(noteId, $modal);

        if (typeof toastr !== 'undefined') {
            toastr.info('Draft products cleared');
        }
    });

    $(document).on('click', '.js-open-quick-product', function () {
        const noteId = $(this).data('note-id');
        activeNoteForQuickProduct = noteId;
        const $modal = $('#quick-product-modal');
        const form = $modal.find('form')[0];
        if (form) {
            form.reset();
        }
        $modal.find('.js-select2').val(null).trigger('change');
        $modal.modal('show');
        initSelect2Dropdowns($modal);
        const $categorySelect = $modal.find('#quick_product_category');
        const $subCategorySelect = $modal.find('#quick_product_sub_category');
        $categorySelect.off('change.quickProductSubCat').on('change.quickProductSubCat', function () {
            populateSubCategories($subCategorySelect, $categorySelect.val());
        });
        populateSubCategories($subCategorySelect, $categorySelect.val());

        // Auto-select location in quick product locations based on the note's location
        try {
            const cardSelector = '.col-12.col-md-4[data-note-id="' + noteId + '"]';
            const cardLocationId = $(cardSelector).data('location-id');
            if (cardLocationId) {
                const $locSelect = $modal.find('#quick_product_locations');
                if ($locSelect.length) {
                    const val = Array.isArray($locSelect.val()) ? $locSelect.val() : [];
                    const idStr = String(cardLocationId);
                    if (!val.includes(idStr)) {
                        $locSelect.val([idStr]).trigger('change');
                    }
                }
            }
        } catch (e) { /* no-op */ }
    });

    $('#quick-product-form').on('submit', function (event) {
        event.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        $submitBtn.prop('disabled', true);

        const formData = $form.serialize();

        $.ajax({
            method: 'POST',
            url: <?php echo json_encode(route('repair.maintenance_notes.quick_product')); ?>,
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
            .done(function (resp) {
                if (resp && resp.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(resp.message || translations.productCreateSuccess);
                    }
                    $('#quick-product-modal').modal('hide');

                    if (resp.product) {
                        const noteId = activeNoteForQuickProduct;
                        const $select = $('#maintenance-note-product-search-' + noteId);
                        if ($select.length) {
                            const newOption = new Option(resp.product.display_name, resp.product.id, true, true);
                            $select.append(newOption).trigger({
                                type: 'select2:select',
                                params: {
                                    data: {
                                        id: resp.product.id,
                                        text: resp.product.display_name,
                                        name: resp.product.name,
                                        sku: resp.product.sku,
                                        price: resp.product.price
                                    }
                                }
                            });
                        }
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error((resp && resp.message) ? resp.message : translations.productCreateFail);
                    }
                }
            })
            .fail(function () {
                if (typeof toastr !== 'undefined') {
                    toastr.error(translations.productCreateFail);
                }
            })
            .always(function () {
                $submitBtn.prop('disabled', false);
            });
    });

    // Ensure stacked modals manage body scroll & backdrop z-index correctly
    $(document).on('show.bs.modal', '.modal', function () {
        const visibleCount = $('.modal:visible').length;
        const zIndex = 1040 + (10 * visibleCount);
        $(this).css('z-index', zIndex);
        setTimeout(function () {
            $('.modal-backdrop').not('.modal-stack')
                .css('z-index', zIndex - 1)
                .addClass('modal-stack');
        }, 0);
    });

    $(document).on('hidden.bs.modal', '.modal', function () {
        const $visibleModals = $('.modal:visible');
        const expectedBackdrops = $visibleModals.length;
        const $backdrops = $('.modal-backdrop');

        if ($backdrops.length > expectedBackdrops) {
            $backdrops.slice(expectedBackdrops).remove();
        }

        if ($visibleModals.length) {
            $('body').addClass('modal-open');
        } else {
            $('body').removeClass('modal-open');
        }
    });

    // Quick Supplier Modal - Open handler
    $(document).on('click', '.js-open-quick-supplier', function () {
        const noteId = $(this).data('note-id');
        activeNoteForQuickProduct = noteId;
        const $modal = $('#quick-supplier-modal');
        const form = $modal.find('form')[0];
        if (form) {
            form.reset();
        }
        $modal.modal('show');
    });

    // Quick Supplier Form - Submit handler
    $('#quick-supplier-form').on('submit', function (event) {
        event.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        $submitBtn.prop('disabled', true);

        const formData = $form.serialize();
        const activeNoteId = activeNoteForQuickProduct;

        if (!activeNoteId) {
            if (typeof toastr !== 'undefined') {
                toastr.error('No active note selected');
            }
            $submitBtn.prop('disabled', false);
            return;
        }

        $.ajax({
            method: 'POST',
            url: <?php echo json_encode(route('repair.maintenance_notes.quick_supplier', ['id' => 'NOTE_ID_PLACEHOLDER'])); ?>.replace('NOTE_ID_PLACEHOLDER', activeNoteId),
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
            .done(function (resp) {
                if (resp && resp.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(resp.message || 'Supplier added successfully');
                    }
                    $('#quick-supplier-modal').modal('hide');

                    // Update supplier selects gracefully if present (Select2-aware)
                    if (resp.supplier) {
                        const $supplierSelects = $('.js-draft-supplier-id, .js-edit-supplier-id');
                        if ($supplierSelects.length) {
                            $supplierSelects.each(function () {
                                const $select = $(this);
                                const idStr = String(resp.supplier.id);
                                const textStr = resp.supplier.display_name;

                                if ($select.hasClass('select2-hidden-accessible')) {
                                    // If Select2 initialized, append option if missing and refresh
                                    if ($select.find('option[value="' + idStr + '"]').length === 0) {
                                        const newOpt = new Option(textStr, idStr, false, false);
                                        $select.append(newOpt);
                                    }
                                    $select.trigger('change.select2');
                                } else {
                                    // Fallback for non-select2 legacy
                                    const currentValue = $select.val();
                                    if ($select.find('option[value="' + idStr + '"]').length === 0) {
                                        $select.append('<option value="' + escapeHtml(idStr) + '">' + escapeHtml(textStr) + '</option>');
                                    }
                                    $select.val(currentValue);
                                }
                            });
                        }
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error((resp && resp.message) ? resp.message : 'Failed to add supplier');
                    }
                }
            })
            .fail(function (xhr) {
                if (typeof toastr !== 'undefined') {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to add supplier';
                    toastr.error(errorMsg);
                }
            })
            .always(function () {
                $submitBtn.prop('disabled', false);
            });
    });

    // Purchase Orders Modal (re-added)
    // Delegated handler to open PO modal when a trigger exists with class .js-open-purchase-orders
    $(document).on('click', '.js-open-purchase-orders', function() {
        const noteId = $(this).data('note-id');
        const purchaseOrdersUrl = <?php echo json_encode(route('repair.maintenance_notes.purchase_orders', ['id' => 'NOTE_ID_PLACEHOLDER'])); ?>.replace('NOTE_ID_PLACEHOLDER', noteId);

        const $container = $('#purchase-orders-container');
        if ($container.length) {
            $container.html('<div class="tw-text-center tw-text-sm tw-text-gray-500 tw-py-8"><i class="fas fa-spinner fa-spin tw-mr-2"></i>' + translations.loading + '</div>');
        }
        $('#purchase-orders-modal').modal('show');

        $.get(purchaseOrdersUrl)
            .done(function(response) {
                if (response && response.success && Array.isArray(response.purchase_orders)) {
                    renderPurchaseOrders(response.purchase_orders, noteId);
                } else {
                    $('#purchase-orders-container').html('<div class="tw-text-center tw-text-sm tw-text-gray-500 tw-py-8">' + translations.poEmpty + '</div>');
                }
            })
            .fail(function() {
                $('#purchase-orders-container').html('<div class="tw-text-center tw-text-sm tw-text-red-500 tw-py-8"><i class="fas fa-exclamation-circle tw-mr-2"></i>' + translations.poFail + '</div>');
                if (typeof toastr !== 'undefined') {
                    toastr.error(translations.poFail);
                }
            });
    });

    function renderPurchaseOrders(purchaseOrders, noteId) {
        const html = purchaseOrders.map(function(po) {
            const statusBadgeClass = getStatusBadgeClass(po.status);
            const poDate = po.transaction_date ? new Date(po.transaction_date).toLocaleDateString() : '';

            const linesHtml = (po.lines || []).map(function(line) {
                const name = (line.product_name || '') + (line.sku ? ' (' + line.sku + ')' : '');
                const quantity = Number(line.quantity ?? 0).toFixed(2);
                const askedQty = Number(line.asked_qty ?? 0).toFixed(2);
                const unit = formatCurrency(line.unit_price ?? 0);
                return '<div class="tw-flex tw-justify-between tw-items-center tw-gap-2 tw-py-1">' +
                    '<span class="tw-flex-1">' + escapeHtml(name) + '</span>' +
                    '<div class="tw-flex tw-items-center tw-gap-2">' +
                        '<div class="tw-flex tw-items-center tw-gap-1">' +
                            '<span class="tw-text-xs tw-text-gray-500">Qty:</span>' +
                            '<input type="number" min="1" step="0.01" class="form-control form-control-sm po-line-quantity" ' +
                                'data-line-id="' + line.id + '" ' +
                                'data-po-id="' + po.id + '" ' +
                                'data-note-id="' + noteId + '" ' +
                                'value="' + quantity + '" ' +
                                'style="width: 72px; min-width: 72px;">' +
                        '</div>' +
                        '<span class="tw-text-xs tw-text-gray-400">x ' + unit + '</span>' +
                    '</div>' +
                '</div>';
            }).join('');

            return (
                '<div class="tw-bg-white tw-rounded-lg tw-border tw-border-gray-200 tw-p-4 tw-shadow-sm">' +
                    '<div class="tw-flex tw-items-start tw-justify-between tw-gap-4 tw-mb-3">' +
                        '<div class="tw-flex-1">' +
                            '<div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">' +
                                '<h6 class="tw-text-sm tw-font-semibold tw-text-gray-900"><i class="fas fa-file-invoice tw-mr-1"></i>' + escapeHtml(po.ref_no || '') + '</h6>' +
                                '<span class="tw-inline-flex tw-items-center tw-text-xs tw-font-semibold tw-px-2.5 tw-py-0.5 tw-rounded-full ' + statusBadgeClass + '">' + escapeHtml(po.status || '') + '</span>' +
                            '</div>' +
                            '<div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-4 tw-gap-3 tw-text-xs tw-text-gray-600">' +
                                '<div><span class="tw-text-gray-400">Supplier:</span><br><span class="tw-font-medium tw-text-gray-900">' + escapeHtml(po.supplier_name || '') + '</span></div>' +
                                '<div><span class="tw-text-gray-400">Date:</span><br><span class="tw-font-medium tw-text-gray-900">' + escapeHtml(poDate) + '</span></div>' +
                                '<div><span class="tw-text-gray-400">Qty:</span><br><span class="tw-font-medium tw-text-gray-900">' + (po.total_qty ?? 0) + '</span></div>' +
                                '<div><span class="tw-text-gray-400">Total:</span><br><span class="tw-font-medium tw-text-gray-900">' + formatCurrency(po.final_total ?? 0) + '</span></div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="tw-flex tw-flex-col tw-gap-2">' +
                            '<select class="form-control form-control-sm po-status-select" data-note-id="' + noteId + '" data-po-id="' + po.id + '" style="min-width: 120px;"' + (po.status === 'received' ? ' disabled' : '') + '>' +
                                '<option value="pending" ' + (po.status === 'pending' ? 'selected' : '') + '>Pending</option>' +
                                '<option value="received" ' + (po.status === 'received' ? 'selected' : '') + '>Received</option>' +
                              
                            '</select>' +
                            (po.status === 'received' ? '' : '<button type="button" class="btn btn-sm btn-outline-primary update-po-status" data-note-id="' + noteId + '" data-po-id="' + po.id + '">Update</button>') +
                        '</div>' +
                    '</div>' +
                    ((po.lines && po.lines.length) ? '<div class="tw-border-t tw-border-gray-200 tw-pt-3 tw-mt-3"><div class="tw-text-xs tw-text-gray-600 tw-space-y-1">' + linesHtml + '</div></div>' : '') +
                '</div>'
            );
        }).join('');

        $('#purchase-orders-container').html(html || '<div class="tw-text-center tw-text-sm tw-text-gray-500 tw-py-8">' + translations.poEmpty + '</div>');
    }

    function getStatusBadgeClass(status) {
        const classes = {
            'pending': 'tw-bg-yellow-100 tw-text-yellow-800',
            'received': 'tw-bg-green-100 tw-text-green-800',
            'partial': 'tw-bg-blue-100 tw-text-blue-800',
            'cancelled': 'tw-bg-red-100 tw-text-red-800'
        };
        return classes[status] || 'tw-bg-gray-100 tw-text-gray-800';
    }

    function formatCurrency(value) {
        try {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value || 0));
        } catch (e) {
            return Number(value || 0).toFixed(2);
        }
    }

    // Update purchase order status
    $(document).on('click', '.update-po-status', function() {
        const noteId = $(this).data('note-id');
        const poId = $(this).data('po-id');
        const $statusSelect = $('.po-status-select[data-po-id="' + poId + '"]');
        const newStatus = $statusSelect.val();
        const $btn = $(this);

        // Collect quantities from all line inputs for this PO
        const quantities = {};
        const askedQuantities = {};
        $('.po-line-quantity[data-po-id="' + poId + '"]').each(function() {
            const lineId = $(this).data('line-id');
            quantities[lineId] = $(this).val();
        });
        $('.po-line-asked-qty[data-po-id="' + poId + '"]').each(function() {
            const lineId = $(this).data('line-id');
            askedQuantities[lineId] = $(this).val();
        });

        $btn.prop('disabled', true);
        const original = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i>');

        const updateUrl = <?php echo json_encode(route('repair.maintenance_notes.purchase_orders.update_status', ['id' => 'NOTE_ID_PLACEHOLDER', 'poId' => 'PO_ID_PLACEHOLDER'])); ?>
            .replace('NOTE_ID_PLACEHOLDER', noteId)
            .replace('PO_ID_PLACEHOLDER', poId);

        $.ajax({
            method: 'PUT',
            url: updateUrl,
            data: { 
                status: newStatus,
                quantities: quantities,
                asked_quantities: askedQuantities
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        })
        .done(function(resp) {
            if (resp && resp.success) {
                if (typeof toastr !== 'undefined') toastr.success('Purchase order status updated successfully');
                // Refresh the purchase orders display without closing modal
                const purchaseOrdersUrl = <?php echo json_encode(route('repair.maintenance_notes.purchase_orders', ['id' => 'NOTE_ID_PLACEHOLDER'])); ?>.replace('NOTE_ID_PLACEHOLDER', noteId);
                $('#purchase-orders-container').html('<div class="tw-text-center tw-text-sm tw-text-gray-500 tw-py-8"><i class="fas fa-spinner fa-spin tw-mr-2"></i>' + translations.loading + '</div>');
                $.get(purchaseOrdersUrl)
                    .done(function(response) {
                        if (response && response.success && Array.isArray(response.purchase_orders)) {
                            renderPurchaseOrders(response.purchase_orders, noteId);
                        } else {
                            $('#purchase-orders-container').html('<div class="tw-text-center tw-text-sm tw-text-gray-500 tw-py-8">' + translations.poEmpty + '</div>');
                        }
                    })
                    .fail(function() {
                        $('#purchase-orders-container').html('<div class="tw-text-center tw-text-sm tw-text-red-500 tw-py-8"><i class="fas fa-exclamation-circle tw-mr-2"></i>' + translations.poFail + '</div>');
                    });
            } else {
                if (typeof toastr !== 'undefined') toastr.error((resp && resp.message) || 'Failed to update status');
            }
        })
        .fail(function() {
            if (typeof toastr !== 'undefined') toastr.error('Failed to update purchase order status');
        })
        .always(function() {
            $btn.prop('disabled', false).html(original);
        });
    });

    // Copy quantity to asked_qty by default when quantity changes
    $(document).on('input change', '.po-line-quantity', function() {
        const lineId = $(this).data('line-id');
        const quantity = $(this).val();
        const $askedQtyInput = $('.po-line-asked-qty[data-line-id="' + lineId + '"]');
        
        // Only copy if asked_qty is empty or same as previous quantity (user hasn't manually changed it)
        if ($askedQtyInput.length) {
            const currentAskedQty = Number($askedQtyInput.val());
            if (currentAskedQty === 0 || isNaN(currentAskedQty)) {
                $askedQtyInput.val(quantity);
            }
        }
    });

    // Validate asked_qty input
    $(document).on('input change', '.po-line-asked-qty', function() {
        const lineId = $(this).data('line-id');
        const askedQty = $(this).val();
        const $qtyInput = $('.po-line-quantity[data-line-id="' + lineId + '"]');
        
        // Only validate if both values are present
        if ($qtyInput.length && askedQty !== '' && $qtyInput.val() !== '') {
            const currentQty = Number($qtyInput.val());
            const newAskedQty = Number(askedQty);
            // Only warn if quantity exceeds asked_qty, don't auto-adjust
            if (currentQty > newAskedQty) {
                // Add visual warning class
                $qtyInput.addClass('tw-border-red-500');
            } else {
                $qtyInput.removeClass('tw-border-red-500');
            }
        }
    });

    // Quick Add Brand AJAX
    $(document).on('submit', 'form#quick_add_brand_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var data = form.serialize();

        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            dataType: 'json',
            data: data,
            beforeSend: function(xhr) {
                __disable_submit_button(form.find('button[type="submit"]'));
            },
            success: function(result) {
                if (result.success == true) {
                    var newOption = new Option(result.data.name, result.data.id, true, true);
                    $('#quick_product_brand').append(newOption).trigger('change');
                    $('.view_modal').modal('hide');
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    // Quick Add Unit AJAX
    $(document).on('submit', 'form#quick_add_unit_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var data = form.serialize();

        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            dataType: 'json',
            data: data,
            beforeSend: function(xhr) {
                __disable_submit_button(form.find('button[type="submit"]'));
            },
            success: function(result) {
                if (result.success == true) {
                    var newOption = new Option(result.data.actual_name, result.data.id, true, true);
                    $('#quick_product_unit').append(newOption).trigger('change');
                    $('.view_modal').modal('hide');
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    // Quick Add Category AJAX
    $(document).on('submit', 'form#category_add_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var data = form.serialize();

        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            dataType: 'json',
            data: data,
            beforeSend: function(xhr) {
                __disable_submit_button(form.find('button[type="submit"]'));
            },
            success: function(result) {
                if (result.success == true) {
                    var newOption = new Option(result.data.name, result.data.id, true, true);
                    
                    if (result.data.parent_id != 0) {
                        $('#quick_product_sub_category').append(newOption).trigger('change');
                    } else {
                        $('#quick_product_category').append(newOption).trigger('change');
                    }
                    
                    $('.category_modal').modal('hide');
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
})(jQuery);
</script>

<style>
.maintenance-product-search-dropdown .select2-results__options {
    max-height: 400px !important;
}

.maintenance-product-search-dropdown .select2-results__option--highlighted {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
}

.maintenance-product-search-dropdown .select2-results__option--highlighted .tw-text-gray-900,
.maintenance-product-search-dropdown .select2-results__option--highlighted .tw-text-gray-600,
.maintenance-product-search-dropdown .select2-results__option--highlighted .tw-text-blue-600,
.maintenance-product-search-dropdown .select2-results__option--highlighted .tw-text-amber-600,
.maintenance-product-search-dropdown .select2-results__option--highlighted .tw-text-green-600,
.maintenance-product-search-dropdown .select2-results__option--highlighted .tw-text-xs,
.maintenance-product-search-dropdown .select2-results__option--highlighted .tw-text-sm,
.maintenance-product-search-dropdown .select2-results__option--highlighted .tw-text-gray-500 {
    color: #ffffff !important;
}

.maintenance-product-search-dropdown .select2-results__option--highlighted .tw-bg-gray-100,
.maintenance-product-search-dropdown .select2-results__option--highlighted .tw-bg-blue-50 {
    background-color: rgba(255, 255, 255, 0.2) !important;
}
</style>
@endsection
