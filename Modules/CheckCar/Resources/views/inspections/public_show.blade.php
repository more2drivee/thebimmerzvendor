@php
    // Force Arabic when no explicit language is stored in the session
    // If the app/session already has a language, keep it
    try {
        $sessionLocale = session()->has('language') ? session('language') : null;
    } catch (\Exception $e) {
        $sessionLocale = null;
    }

    if (empty($sessionLocale)) {
        app()->setLocale('ar');
    } else {
        app()->setLocale($sessionLocale);
    }
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap');
</style>
<style>
    .booking-media-wrapper {
        text-align: center;
    }

    .booking-media-main {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 12px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        margin-bottom: 16px;
    }

    .booking-media-main img,
    .booking-media-main video {
        width: auto;
        max-width: 520px;
        margin: 0 auto;
        display: block;
    }

    .booking-media-thumbs {
        text-align: right;
    }

    .booking-media-thumbs__title {
        font-size: 12px;
        color: #666;
        margin-bottom: 6px;
        font-weight: 600;
    }

    .booking-media-thumbs__list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-start;
    }

    .booking-media-thumb {
        border: 1px solid #d9d9d9;
        border-radius: 6px;
        padding: 4px;
        background: #fff;
        cursor: pointer;
        min-width: 80px;
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .booking-media-thumb img {
        max-width: 80px;
        max-height: 60px;
        border-radius: 4px;
    }

    .booking-media-thumb__video {
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 11px;
        color: #777;
        gap: 4px;
    }

    .booking-media-thumb.is-active {
        border-color: #3c8dbc;
        box-shadow: 0 0 0 2px rgba(60, 141, 188, 0.2);
    }

    .booking-media-thumb__label {
        font-size: 11px;
        color: #555;
    }
</style>
<div style="direction: rtl; font-family: 'Cairo', 'Arial', sans-serif; text-align: right; max-width: 70%; margin: 0 auto; position: relative; z-index: 1;">
    @if(!empty($watermarkUrl))
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.08; z-index: 0; pointer-events: none;">
            <img src="{{ $watermarkUrl }}" alt="Watermark" style="max-width: 100%; height: auto; display: block;">
        </div>
    @endif
    <!-- Print Button -->
    <div class="no-print" style="margin-bottom: 15px; display: flex; gap: 10px;">
        <button onclick="window.print()" style="padding: 8px 16px; background-color: #f0f0f0; border: 1px solid #999; border-radius: 4px; cursor: pointer; font-size: 13px;">{{ __('checkcar::lang.print') }}</button>
    </div>

    <!-- Header with Logo and Business Info -->
    <div style="overflow: hidden; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="flex: 2; text-align: right;">
                @if(!empty($business))
                    <div style="font-size: 14px; font-weight: 700; color: #333;">
                        {{ $business->name ?? '' }}
                    </div>
                @endif

                @if(!empty($businessLocation))
                    <div style="font-size: 11px; color: #555; margin-top: 4px; line-height: 1.5;">
                        {!! $businessLocation->location_address !!}
                    </div>
                    @php
                        $businessPhone = $businessLocation->mobile ?? $businessLocation->landline ?? null;
                    @endphp
                    @if(!empty($businessPhone))
                        <div style="font-size: 11px; color: #555; margin-top: 2px;">
                            هاتف: {{ $businessPhone }}
                        </div>
                    @endif
                @else
                    <div style="font-size: 12px; color: #555; margin-top: 5px;">
                        {{ __('checkcar::lang.fallback_report_title') }}
                    </div>
                @endif
            </div>
            <div style="flex: 1; text-align: left;">
                @php
                    $businessLogo = optional($inspection->creator)->business->logo ?? null;
                @endphp
                @if(!empty($businessLogo))
                    <img src="{{ asset('uploads/business_logos/' . $businessLogo) }}" alt="Logo" style="max-height: 110px; max-width: 160px; display: inline-block;">
                @else
                    <img src="{{ asset('/uploads/images/new_logo.png') }}" alt="Logo" style="max-height: 110px; max-width: 160px; display: inline-block;">
                @endif
            </div>
        </div>
    </div>

    <!-- Inspection Date and Status Info -->
    <div style="margin-bottom: 15px;">
        <table style="width: 100%; border-collapse: collapse; table-layout: fixed; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px; overflow: hidden;">
            <tr>
                <td colspan="2" style="width: 50%; border: 1px solid #ddd; padding: 10px; text-align: center; background-color: #f8f8f8; border-radius: 4px 0 0 0;">
                    <div style="font-weight: bold; color: #444; font-size: 12px;">تاريخ الفحص:</div>
                    <div style="color: #333; font-size: 13px;">
                        {{ optional($inspection->created_at)->format('Y-m-d H:i') ?? '-' }}
                    </div>
                </td>
                <td style="width: 50%; border: 1px solid #ddd; padding: 10px; text-align: center; background-color: #f8f8f8;">
                    <div style="font-weight: bold; color: #444; font-size: 12px;">رقم الفحص</div>
                    <div style="color: #333; font-size: 13px;">
                        {{ $inspection->id ?? '-' }}
                    </div>
                </td>
             
            </tr>
        </table>
    </div>

    <!-- Buyer and Seller Info Container -->
    <div style="margin-bottom: 15px; background-color: #fff; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="background-color: #3c8dbc; color: white; padding: 10px; font-weight: bold; font-size: 12px; text-align: center;">
            {{ __('checkcar::lang.parties_info') }}
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444;">{{ __('checkcar::lang.buyer') }}</div>
                    <div style="color: #333; font-size: 12px; margin-top: 8px;">
                        @php
                            $buyerContact = null;
                            if ($inspection->buyer_contact_id) {
                                try {
                                    $buyerContact = \App\Contact::find($inspection->buyer_contact_id);
                                } catch (Exception $e) {
                                    $buyerContact = null;
                                }
                            }
                        @endphp
                        @if($buyerContact)
                            <div style="font-weight: 500;">{{ $buyerContact->name }}</div>
                            @if($buyerContact->mobile)
                                <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $buyerContact->mobile }}</div>
                            @endif
                            @if($buyerContact->id_number)
                                <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $buyerContact->id_number }}</div>
                            @endif
                        @else
                            {{ $inspection->buyer_full_name ?? '-' }}
                            @if($inspection->buyer_phone)
                                <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $inspection->buyer_phone }}</div>
                            @endif
                            @if($inspection->buyer_id_number)
                                <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $inspection->buyer_id_number }}</div>
                            @endif
                        @endif
                    </div>
                </td>
                <td style="width: 50%; border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444;">{{ __('checkcar::lang.seller') }}</div>
                    <div style="color: #333; font-size: 12px; margin-top: 8px;">
                        @php
                            $sellerContact = null;
                            if ($inspection->seller_contact_id) {
                                try {
                                    $sellerContact = \App\Contact::find($inspection->seller_contact_id);
                                } catch (Exception $e) {
                                    $sellerContact = null;
                                }
                            }
                        @endphp
                        @if($sellerContact)
                            <div style="font-weight: 500;">{{ $sellerContact->name }}</div>
                            @if($sellerContact->mobile)
                                <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $sellerContact->mobile }}</div>
                            @endif
                            @if($sellerContact->id_number)
                                <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $sellerContact->id_number }}</div>
                            @endif
                        @else
                            {{ $inspection->seller_full_name ?? '-' }}
                            @if($inspection->seller_phone)
                                <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $inspection->seller_phone }}</div>
                            @endif
                            @if($inspection->seller_id_number)
                                <div style="font-size: 11px; color: #666; margin-top: 2px;">{{ $inspection->seller_id_number }}</div>
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Car Details -->
    <div style="margin-bottom: 15px;">
        <table style="width: 100%; border-collapse: collapse; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px; overflow: hidden;">
            <tr>
                <td style="width: 20%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 3px;">{{ __('checkcar::lang.odometer') }}</div>
                    <div style="color: #333;">
                        {{ optional($jobSheet)->km ?? $inspection->car_kilometers ?? ($contactDevice->km ?? '-') }}
                    </div>
                </td>
                <td style="width: 20%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 3px;">{{ __('checkcar::lang.chassis_number_label') }}:</div>
                    <div style="color: #333;">
                        {{ $inspection->car_chassis_number ?? ($contactDevice->chassis_number ?? '-') }}
                    </div>
                </td>
                <td style="width: 20%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 3px;">{{ __('checkcar::lang.car_brand_label') }}:</div>
                    <div style="color: #333;">
                        {{ $inspection->car_brand ?? (optional($contactDevice->deviceCategory)->name ?? '-') }}
                    </div>
                </td>
                <td style="width: 20%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 3px;">{{ __('checkcar::lang.car_model_label') }}:</div>
                    <div style="color: #333;">
                        {{ $inspection->car_model ?? (optional($contactDevice->deviceModel)->name ?? '-') }}
                    </div>
                </td>
                <td style="width: 20%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 3px;">{{ __('checkcar::lang.car_color_label') }}:</div>
                    <div style="color: #333;">
                        {{ $inspection->car_color ?? ($contactDevice->color ?? '-') }}
                    </div>
                </td>
            </tr>
            @php
                $brandOriginVariant = optional($contactDevice)->brandOriginVariant;
            @endphp
            @if($brandOriginVariant)
                <tr>
                    <td colspan="5" style="border: 1px solid #ddd; padding: 6px 10px; text-align: center; font-size: 10px; background-color: #f9f9f9;">
                        <span style="font-weight: bold; color: #444;">{{ __('checkcar::lang.origin_country') }}:</span>
                        <span style="color: #333; margin-right: 5px;">
                            {{ $brandOriginVariant->country_of_origin ? ($brandOriginVariant->name . ' (' . $brandOriginVariant->country_of_origin . ')') : $brandOriginVariant->name }}
                        </span>
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <!-- Signature Boxes -->
    @php
        $buyerDocs = collect($documentsByParty->get('buyer', []));
        $sellerDocs = collect($documentsByParty->get('seller', []));
        $buyerSignature = $buyerDocs->first(function ($doc) {
            return data_get($doc, 'document_type', data_get($doc, 'type')) === 'signature';
        });
        $sellerSignature = $sellerDocs->first(function ($doc) {
            return data_get($doc, 'document_type', data_get($doc, 'type')) === 'signature';
        });
        $buyerSignatureUrl = $buyerSignature ? (data_get($buyerSignature, 'url') ?? (data_get($buyerSignature, 'file_path') ? asset('storage/' . data_get($buyerSignature, 'file_path')) : null)) : null;
        $sellerSignatureUrl = $sellerSignature ? (data_get($sellerSignature, 'url') ?? (data_get($sellerSignature, 'file_path') ? asset('storage/' . data_get($sellerSignature, 'file_path')) : null)) : null;
    @endphp

    <div style="margin: 20px 0;">
            <!-- Final Signature Section -->
    <div style="margin-top: 30px; margin-bottom: 20px;">

        
        <!-- Acknowledgment Text -->
        <div style="margin-top: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
            <p style="margin: 0; font-size: 13px; color: #333; line-height: 1.5;">
                {{ __('checkcar::lang.acknowledgment_text') }}
            </p>
        </div>
    </div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; padding: 10px; text-align: center;">
                    <div style="border: 2px solid #333; padding: 20px 10px; min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;">
                        @if(!empty($buyerSignatureUrl))
                            <img src="{{ $buyerSignatureUrl }}" alt="{{ __('checkcar::lang.buyer_signature') }}" style="max-width: 100%; max-height: 80px; object-fit: contain;">
                        @else
                            <span style="font-size: 11px; color: #777;">{{ __('checkcar::lang.no_buyer_signature') }}</span>
                        @endif
                        <div style="text-align: center; font-weight: bold; font-size: 12px;">{{ __('checkcar::lang.buyer_signature') }}</div>
                    </div>
                </td>
                <td style="width: 50%; padding: 10px; text-align: center;">
                    <div style="border: 2px solid #333; padding: 20px 10px; min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;">
                        @if(!empty($sellerSignatureUrl))
                            <img src="{{ $sellerSignatureUrl }}" alt="{{ __('checkcar::lang.seller_signature') }}" style="max-width: 100%; max-height: 80px; object-fit: contain;">
                        @else
                            <span style="font-size: 11px; color: #777;">{{ __('checkcar::lang.no_seller_signature') }}</span>
                        @endif
                        <div style="text-align: center; font-weight: bold; font-size: 12px;">{{ __('checkcar::lang.seller_signature') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @php
        $bookingMedia = $bookingMedia ?? collect();
        $defaultMediaUrl = !empty($carDiagramUrl) ? $carDiagramUrl : asset('uploads/media/jobsheet_def.png');
    @endphp

    <!-- Car Body Diagram / Job Sheet Image -->
    <div class="booking-media-wrapper" style="margin: 20px 0;">
        <div id="bookingMediaMain" class="booking-media-main" data-default-url="{{ $defaultMediaUrl }}">
            <img id="bookingMediaMainImage" src="{{ $defaultMediaUrl }}" alt="Car body diagram" style="max-width: 100%; height: auto; max-height: 320px; border-radius: 8px;">
            <video id="bookingMediaMainVideo" controls style="display: none; max-width: 100%; max-height: 320px; border-radius: 8px;"></video>
        </div>

        @if($bookingMedia->isNotEmpty())
            <div class="booking-media-thumbs">
                <div class="booking-media-thumbs__title">صور / فيديوهات الحجز</div>
                <div class="booking-media-thumbs__list">
                    <button type="button"
                            class="booking-media-thumb is-active"
                            data-type="image"
                            data-url="{{ $defaultMediaUrl }}">
                        <span class="booking-media-thumb__label">الصورة الافتراضية</span>
                    </button>
                    @foreach($bookingMedia as $media)
                        <button type="button"
                                class="booking-media-thumb"
                                data-type="{{ $media['type'] }}"
                                data-url="{{ $media['url'] }}">
                            @if($media['type'] === 'video')
                                <div class="booking-media-thumb__video">
                                    <span>🎬</span>
                                    <small>{{ __('checkcar::lang.video_not_supported') }}</small>
                                </div>
                            @else
                                <img src="{{ $media['url'] }}" alt="Booking media" />
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Inspection Items Table -->
    <div style="margin: 20px 0;">
        <div style="border: 1px solid #ddd; padding: 10px; text-align: right; font-weight: bold; background-color: #3c8dbc; color: white; font-size: 12px; border-radius: 4px 4px 0 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">{{ __('checkcar::lang.inspection_items_title') }}:</div>
        <table style="width: 100%; border-collapse: collapse; direction: rtl; text-align: right; border-radius: 0 0 4px 4px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            @forelse($itemsByCategory as $categoryName => $categoryItems)
                {{-- Category header row --}}
                <tr style="background-color: #e9f2fb;">
                    <td colspan="5" style="border: 1px solid #ddd; padding: 8px 10px; font-size: 12px; font-weight: bold; color: #17375e;">
                        {{ $categoryName }}
                    </td>
                </tr>

                @php
                    $itemsBySubcategory = collect($categoryItems)
                        ->groupBy(function($item) {
                            return $item->subcategory ? $item->subcategory->name : null;
                        });
                @endphp

                @foreach($itemsBySubcategory as $subcategoryName => $subItems)
                    @if($subcategoryName)
                        {{-- Subcategory header row --}}
                        <tr style="background-color: #f2f2f2;">
                            <td colspan="5" style="border: 1px solid #ddd; padding: 6px 10px; font-size: 11px; font-weight: 500; color: #555;">
                                {{ $subcategoryName }}
                            </td>
                        </tr>
                        @if($loop->first)
                            {{-- Column header row under the first subcategory in this category --}}
                            <tr style="background-color: #f8f8f8; color: black;">
                                <th style="border: 1px solid #ddd; padding: 8px; text-align: center; width: 10%; font-size: 11px;">{{ __('checkcar::lang.column_index') }}</th>
                                <th style="border: 1px solid #ddd; padding: 10px; text-align: center; width: 25%; font-size: 12px; font-weight: 500;">{{ __('checkcar::lang.column_item') }}</th>
                                <th style="border: 1px solid #ddd; padding: 10px; text-align: center; width: 25%; font-size: 12px; font-weight: 500;">{{ __('checkcar::lang.column_status') }}</th>
                                <th style="border: 1px solid #ddd; padding: 10px; text-align: center; width: 20%; font-size: 12px; font-weight: 500;">{{ __('checkcar::lang.column_notes') }}</th>
                                <th style="border: 1px solid #ddd; padding: 10px; text-align: center; width: 20%; font-size: 12px; font-weight: 500;">{{ __('checkcar::lang.column_images') }}</th>
                            </tr>
                        @endif
                    @endif

                    @foreach($subItems as $item)
                        <tr @if($loop->even) style="background-color: #f9f9f9;" @else style="background-color: #fff;" @endif>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; color: #555;">
                                {{ $loop->iteration }}
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px; text-align: right; font-size: 12px;">
                                <div style="font-weight: 500; color: #333;">
                                    {{ $item->element ? $item->element->name : '-' }}
                                </div>
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px; text-align: center; font-size: 12px; color: #333;">
                                @php
                                    $optionIds = $item->option_ids ?? [];
                                    if (!empty($optionIds)) {
                                        $options = \Modules\CheckCar\Entities\CheckCarElementOption::whereIn('id', $optionIds)->get();
                                        $optionLabels = $options->pluck('label')->implode(', ');
                                    } else {
                                        $optionLabels = '';
                                    }
                                @endphp
                                @if(!empty($item->title))
                                    {{ $item->title }}
                                @else
                                    {{ $optionLabels ?: '-' }}
                                @endif
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px; text-align: right; font-size: 12px; color: #333;">
                                {{ $item->note ?? '-' }}
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px; text-align: center; font-size: 12px;">
                                @php $images = $item->images ?? []; @endphp
                                @if(count($images) > 0)
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap; justify-content: center;">
                                        @foreach($images as $image)
                                            @php
                                                $filePath = is_array($image) ? ($image['file_path'] ?? null) : null;
                                                $mimeType = is_array($image) ? ($image['mime_type'] ?? '') : '';
                                            @endphp
                                            @if(!empty($filePath))
                                                @if(str_starts_with($mimeType, 'image/'))
                                                    <img src="{{ asset('storage/' . $filePath) }}"
                                                         alt="inspection item"
                                                         class="inspection-image-thumb"
                                                         style="cursor: pointer; max-width: 60px; max-height: 60px; border: 1px solid #ddd; border-radius: 3px;">
                                                @elseif(str_starts_with($mimeType, 'video/'))
                                                    <video controls style="max-width: 120px; max-height: 80px; border: 1px solid #ddd; border-radius: 3px;">
                                                        <source src="{{ asset('storage/' . $filePath) }}" type="{{ $mimeType }}">
                                                        {{ __('checkcar::lang.video_not_supported') }}
                                                    </video>
                                                @elseif($mimeType === 'application/pdf')
                                                    <a href="{{ asset('storage/' . $filePath) }}" target="_blank" style="display: inline-block; font-size: 11px; padding: 3px 6px; border: 1px solid #ccc; border-radius: 3px; background-color: #f5f5f5; text-decoration: none; color: #333;">
                                                        {{ __('checkcar::lang.view_pdf') }}
                                                    </a>
                                                @else
                                                    <a href="{{ asset('storage/' . $filePath) }}" target="_blank" style="display: inline-block; font-size: 11px; padding: 3px 6px; border: 1px solid #ccc; border-radius: 3px; background-color: #f5f5f5; text-decoration: none; color: #333;">
                                                        {{ __('checkcar::lang.view_file') }}
                                                    </a>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color: #999;">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            @empty
                <tr>
                    <td colspan="5" style="border: 1px solid #ddd; padding: 15px; text-align: center; font-size: 12px; color: #777;">
                        {{ __('checkcar::lang.no_inspection_items') }}
                    </td>
                </tr>
            @endforelse
        </table>
    </div>

   





</div>

<style>
    @media print {
        .no-print { display: none !important; }
        body { margin: 0; padding: 0; }
    }

    .inspection-image-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .inspection-image-modal-content {
        background: #fff;
        padding: 10px;
        border-radius: 4px;
        max-width: 90%;
        max-height: 90%;
        box-shadow: 0 2px 10px rgba(0,0,0,0.5);
    }

    .inspection-image-modal-content img {
        max-width: 100%;
        max-height: 80vh;
        display: block;
        margin: 0 auto;
    }

    .inspection-image-modal-close {
        position: absolute;
        top: 15px;
        right: 20px;
        color: #fff;
        font-size: 24px;
        cursor: pointer;
        font-weight: bold;
    }
</style>

<div id="inspectionImageModal" class="inspection-image-modal-overlay no-print">
    <span class="inspection-image-modal-close">&times;</span>
    <div class="inspection-image-modal-content">
        <img src="" alt="inspection item full" id="inspectionImageModalImg">
    </div>
</div>

<script>
    (function() {
        var mainWrapper = document.getElementById('bookingMediaMain');
        var mainImage = document.getElementById('bookingMediaMainImage');
        var mainVideo = document.getElementById('bookingMediaMainVideo');

        function setActiveThumb(button) {
            var buttons = document.querySelectorAll('.booking-media-thumb');
            buttons.forEach(function(btn) {
                btn.classList.remove('is-active');
            });
            button.classList.add('is-active');
        }

        function showMedia(button) {
            if (!mainWrapper || !mainImage || !mainVideo) return;

            var type = button.getAttribute('data-type');
            var url = button.getAttribute('data-url');

            if (type === 'video') {
                mainImage.style.display = 'none';
                mainVideo.style.display = 'block';
                mainVideo.src = url;
                mainVideo.load();
            } else {
                mainVideo.pause();
                mainVideo.removeAttribute('src');
                mainVideo.style.display = 'none';
                mainImage.style.display = 'block';
                mainImage.src = url;
            }

            setActiveThumb(button);
        }

        document.addEventListener('click', function(event) {
            var target = event.target.closest('.booking-media-thumb');
            if (target) {
                event.preventDefault();
                showMedia(target);
            }
        });
    })();

    (function() {
        var modal = document.getElementById('inspectionImageModal');
        var modalImg = document.getElementById('inspectionImageModalImg');
        var closeBtn = modal ? modal.querySelector('.inspection-image-modal-close') : null;

        function openModal(src) {
            if (!modal || !modalImg) return;
            modalImg.src = src;
            modal.style.display = 'flex';
        }

        function closeModal() {
            if (!modal) return;
            modal.style.display = 'none';
            if (modalImg) modalImg.src = '';
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }

        document.addEventListener('click', function(e) {
            var target = e.target;
            if (target && target.classList.contains('inspection-image-thumb')) {
                openModal(target.src);
            }
        });
    })();
</script>
