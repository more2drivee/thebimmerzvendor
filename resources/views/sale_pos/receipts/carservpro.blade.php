<style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap');

    .carservpro-receipt table,
    .carservpro-receipt table td,
    .carservpro-receipt table th,
    .carservpro-receipt table div,
    .carservpro-receipt table span {
        font-size: 9px !important;
    }
</style>
<div class="carservpro-receipt" style="direction: rtl; font-family: 'Cairo', 'Arial', sans-serif; text-align: right; max-width: 100%; margin: 0 auto; padding-top: 0;">
    <div style="overflow: hidden; margin-top: 0; padding-top: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="flex: 2; text-align: right;">
                <div style="font-weight: bold; font-size: 20px; color: #333;">
                    @if(!empty($receipt_details->display_name))
                        {{$receipt_details->display_name}}
                    @else
                        مركز صيانة السيارات
                    @endif
                </div>
                @if(!empty($receipt_details->address))
                    <div style="font-size: 10px; color: #555;">
                        {{$receipt_details->address}}
                    </div>
                @endif
                @if(!empty($receipt_details->contact))
                    <div style="font-size: 10px; color: #555; direction: ltr; text-align: left; unicode-bidi: embed; float: right">
                        {{$receipt_details->contact}}
                    </div>
                @endif
        
            </div>
            <div style="flex: 1; text-align: left;">
                @if(!empty($receipt_details->logo))
                    <img style="max-height: 130px; max-width: 170px; float: left;" src="{{$receipt_details->logo}}" class="img">
                @endif
            </div>
        </div>
    
        <table style="width: 100%; border-collapse: collapse; table-layout: fixed; border-radius: 4px; border: 1px solid black !important; margin-bottom: -1px;">
            <tr>
                <td colspan="2" style="width: 50%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f8f8f8; border-radius: 4px 0 0 0;">
                    <div style="font-weight: bold; color: #444; font-size: 10px;">تاريخ البيان:</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        @if(!empty($receipt_details->invoice_date))
                            {{$receipt_details->invoice_date}}
                        @else
                            -
                        @endif
                    </div>
                </td>
                <td style="width: 25%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f8f8f8;">
                    <div style="font-weight: bold; color: #444; font-size: 10px;">رقم</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        @if(!empty($receipt_details->invoice_no))
                            {{$receipt_details->invoice_no}}
                        @else
                            -
                        @endif
                    </div>
                </td>
                <td style="width: 25%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f8f8f8; border-radius: 0 4px 0 0;">
                    <div style="font-weight: bold; color: #444; font-size: 10px;">طريقة الدفع:</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        @php
                            // Prefer the primary payment method exposed by the receipt builder
                            $paymentMethodDisplay = $receipt_details->primary_payment_method ?? null;

                            // Fallback to the first formatted payment entry if available
                            if (empty($paymentMethodDisplay) && !empty($receipt_details->payments)) {
                                $firstPayment = $receipt_details->payments[0] ?? null;
                                if (is_array($firstPayment) && !empty($firstPayment['method'])) {
                                    $paymentMethodDisplay = $firstPayment['method'];
                                }
                            }
                        @endphp

                        {{ $paymentMethodDisplay ?: '-' }}
                    </div>
                </td>
            </tr>
            <tr>
                <td style="width: 25%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f9f9f9; border-radius: 0 0 0 4px;">
                    <div style="font-weight: bold; color: #444; font-size: 10px;">العميل</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        @if(!empty($receipt_details->customer_name))
                            {{$receipt_details->customer_name}}
                        @elseif(!empty($receipt_details->customer_info))
                            {{$receipt_details->customer_info}}
                        @endif
                    </div>
                </td>
                <td style="width: 25%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; font-size: 10px;">رقم الهاتف</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        {{$receipt_details->customer_info}}
                    </div>
                </td>
                <td style="width: 25%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; font-size: 10px;">رقم امر الشغل</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        @if(!empty($receipt_details->service_no))
                            {{$receipt_details->service_no}}
                        @endif
                    </div>
                </td>
                <td style="width: 25%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f9f9f9; border-radius: 0 0 4px 0;">
                    <div style="font-weight: bold; color: #444; font-size: 10px;">رقم اللوحة:</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        @if(!empty($receipt_details->license_plate))
                            {{$receipt_details->license_plate}}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 0;">
        <table style="width: 100%; border-collapse: collapse; border-radius: 4px; border: 1px solid black !important;">
            <tr>
                <td style="width: 20%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 1px; font-size: 10px;">عداد كم</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        @if(!empty($receipt_details->odometer))
                            {{$receipt_details->odometer}}
                        @elseif(!empty($receipt_details->repair_serial_no))
                            {{$receipt_details->repair_serial_no}}
                        @endif
                    </div>
                </td>
           
                <td style="width: 20%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 1px; font-size: 10px;">رقم الهيكل:</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        @if(!empty($receipt_details->vin_number))
                            {{$receipt_details->vin_number}}
                        @endif
                    </div>
                </td>
                <td style="width: 20%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 1px; font-size: 10px;">طراز السيارة:</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        @if(!empty($receipt_details->repair_brand))
                            {{$receipt_details->repair_brand}}
                        @elseif(!empty($receipt_details->car_brand))
                            {{$receipt_details->car_brand}}
                        @endif
                    </div>
                </td>
                <td style="width: 20%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 1px; font-size: 10px;">الموديل:</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        @if(!empty($receipt_details->repair_model_no))
                            {{$receipt_details->repair_model_no}}
                        @elseif(!empty($receipt_details->car_model))
                            {{$receipt_details->car_model}}
                        @endif
                    </div>
                </td>
                <td style="width: 20%; border: 1px solid black; padding: 2px; text-align: center; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 1px; font-size: 10px;">اللون:</div>
                    <div style="color: #333; font-size: 11px; line-height: 1;">
                        @if(!empty($receipt_details->car_color))
                            {{$receipt_details->car_color}}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>




    <!-- Products Table -->
    @php
        $common_settings = $receipt_details->common_settings ?? [];

        // Existing setting from invoice layout: "Show category column (CarServPro)".
        //   common_settings['carservpro_show_category'] = 1  => show category as a table column.
        // New setting to be added in invoice layout: "Show category as row (CarServPro)".
        //   common_settings['carservpro_category_as_row'] = 1 => hide column and show category as a header row.
        // Row mode has priority over column mode. If both are off, category is hidden.
        $category_column_enabled = !empty($common_settings['carservpro_show_category']);
        $category_as_row = !empty($common_settings['carservpro_category_as_row']) && $category_column_enabled;
        $category_as_column = !$category_as_row && $category_column_enabled;

        $separate_parts_services = !empty($common_settings['carservpro_separate_parts_services']);

        $all_lines = collect($receipt_details->lines ?? []);
        if ($separate_parts_services) {
            $spare_parts_lines = $all_lines->filter(function ($line) {
                $is_stock_enabled = isset($line['enable_stock']) && (int) $line['enable_stock'] === 1;
                $is_virtual = !empty($line['virtual_product']) && $line['virtual_product'] == true;
                $is_client_flagged = !empty($line['is_client_flagged']) && $line['is_client_flagged'] == true;
                return $is_stock_enabled || $is_virtual || $is_client_flagged;
            });

            $service_lines = $all_lines->filter(function ($line) {
                $is_stock_enabled = isset($line['enable_stock']) && (int) $line['enable_stock'] === 1;
                $is_virtual = !empty($line['virtual_product']) && $line['virtual_product'] == true;
                $is_client_flagged = !empty($line['is_client_flagged']) && $line['is_client_flagged'] == true;
                return !$is_stock_enabled && !$is_virtual && !$is_client_flagged;
            });

            $sections = [];
            if ($spare_parts_lines->isNotEmpty()) {
                $sections[] = [
                    'title' => __('lang_v1.carservpro_spare_parts'),
                    'lines' => $spare_parts_lines->values(),
                    'type' => 'spare_parts',
                ];
            }
            if ($service_lines->isNotEmpty()) {
                $sections[] = [
                    'title' => 'مصنعيات - وخدمات',
                    'lines' => $service_lines->values(),
                    'type' => 'services',
                ];
            }

            if (empty($sections)) {
                $sections[] = [
                    'title' => null,
                    'lines' => $all_lines->values(),
                    'type' => null,
                ];
            }
        } else {
            $sections = [
                [
                    'title' => null,
                    'lines' => $all_lines->values(),
                    'type' => null,
                ],
            ];
        }
    @endphp

    @foreach($sections as $section)
        @php
            $lines_collection = collect($section['lines']);
            $has_lines = $lines_collection->isNotEmpty();
            $section_type = $section['type'] ?? null;

            // Category display mode is global: either as a column or as separate header rows
            $section_show_category = $category_as_column;

            // Hide quantity for services section only (as per previous requirement)
            $section_show_quantity = $section_type !== 'services';

            $product_col_width = $section_show_category ? '25%' : ($section_show_quantity ? '30%' : '45%');
            $category_col_width = '15%';

            // Total number of columns in the products table body/header for this section
            $columns_count = 2; // # and product
            if ($section_show_category) {
                $columns_count++;
            }
            if ($section_show_quantity) {
                $columns_count++;
            }
            $columns_count += 2; // unit price and subtotal
        @endphp

        @if($has_lines)
            <div style="margin: 12px 0;">
                <table style="width: 100%; border-collapse: collapse; direction: rtl; text-align: right; border-radius: 4px; border: 1px solid black !important;">
                    <tr style="background-color: #f8f8f8; color: black;">
                        <th style="border: 1px solid black; padding: 3px; text-align: center; width: 10%; font-size: 10px;">
                             #
                        </th>
                        <th style="border: 1px solid black; padding: 3px; text-align: center; width: {{ $product_col_width }}; font-size: 11px; font-weight: 600;">
                            @if(!empty($section['title']))
                                {{ $section['title'] }}
                            @else
                                المنتج
                            @endif
                        </th>
                        @if($section_show_category)
                            <th style="border: 1px solid black; padding: 3px; text-align: center; width: {{ $category_col_width }}; font-size: 11px; font-weight: 600;">
                                التصنيف
                            </th>
                        @endif
                        @if($section_show_quantity)
                            <th style="border: 1px solid black; padding: 3px; text-align: center; width: 15%; font-size: 11px; font-weight: 600;">
                                العدد
                            </th>
                        @endif
                        <th style="border: 1px solid black; padding: 3px; text-align: center; width: 20%; font-size: 11px; font-weight: 600;">
                            سعر الوحدة
                        </th>
                        <th style="border: 1px solid black; padding: 3px; text-align: center; width: 20%; font-size: 11px; font-weight: 600;">
                            المجموع الفرعي
                        </th>
                    </tr>
                    @php
                        $sorted_lines = $lines_collection->sortBy(function($line) {
                            $category = isset($line['category_name']) ? $line['category_name'] : '';
                            $name = isset($line['name']) ? $line['name'] : '';
                            return $category . '|' . $name;
                        });

                        // Group by category, but treat uncategorized items as default (empty key)
                        // so that no visible label like "غير مصنف" is ever printed.
                        $grouped_lines = $sorted_lines->groupBy(function($line) {
                            return isset($line['category_name']) && $line['category_name'] !== ''
                                ? $line['category_name']
                                : '';
                        });

                        $row_index = 1;
                    @endphp
                    @foreach($grouped_lines as $category_name => $category_lines)
                        @if($category_as_row && $category_name !== 'غير مصنف')
                            <tr>
                                <td colspan="{{ $columns_count }}" style="border: 1px solid black; padding: 3px 5px; text-align: right; font-size: 11px; font-weight: bold; color: #17375e;">
                                    {{ $category_name }}
                                </td>
                            </tr>
                        @endif

                        @foreach($category_lines as $line)
                            @php
                                $is_even_row = ($row_index % 2) === 0;
                            @endphp
                            <tr @if($is_even_row) style="background-color: #f9f9f9;" @else style="background-color: #fff;" @endif>
                                <td style="border: 1px solid black; padding: 2px; text-align: center; font-size: 10px; color: #555;">
                                    {{$row_index}}
                                </td>
                                <td style="border: 1px solid black; padding: 2px 3px; text-align: right; font-size: 11px;">
                                    @if(!empty($line['name']))
                                        <div style="font-weight: 500; color: #333;">
                                            @php
                                                $originalName = $line['name'];
                                                $productName = $originalName;
                                                $hasClientNote = \Illuminate\Support\Str::contains($productName, ['طرف العميل', 'من طرف العميل']);
                                                if(!empty($line['is_client_flagged']) && $line['is_client_flagged'] == true && !$hasClientNote) {
                                                    $productName .= ' من طرف العميل';
                                                }
                                                $variationText = trim(strip_tags($line['variation'] ?? ''));
                                                $noteText = trim(strip_tags($line['sell_line_note'] ?? ''));
                                            @endphp
                                            @if(!empty($receipt_details->common_settings['show_brand_with_product']) && !empty($line['brand_name']))
                                                {{ $productName }} <b style="font-weight: bold;">({{ $line['brand_name'] }})</b>
                                            @else
                                                {{ $productName }}
                                            @endif
                                        </div>
                                        @if(!empty($variationText) && $variationText !== $productName && $variationText !== $originalName)
                                            <div style="font-size: 11px; color: #666; margin-top: 1px;">{{$line['variation']}}</div>
                                        @endif
                                        @if(!empty($noteText) && $noteText !== $productName)
                                            <div style="font-size: 11px; color: #666; margin-top: 1px; font-style: italic;">{{$line['sell_line_note']}}</div>
                                        @endif
                                    @endif
                                </td>
                                @if($section_show_category)
                                    <td style="border: 1px solid black; padding: 2px 3px; text-align: center; font-size: 11px; color: #333;">
                                        {{ $line['category_name'] ?? '' }}
                                    </td>
                                @endif
                                @if($section_show_quantity)
                                    <td style="border: 1px solid black; padding: 2px 3px; text-align: center; font-size: 11px; color: #333;">
                                        @if(!empty($line['quantity']))
                                            {{$line['quantity']}}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                                <td style="border: 1px solid black; padding: 2px 3px; text-align: center; font-size: 11px; color: #333;">
                                    @if(!empty($line['unit_price_inc_tax']))
                                        {{$line['unit_price_inc_tax']}}
                                    @elseif(!empty($line['unit_price_before_discount_inc_tax']))
                                        {{$line['unit_price_before_discount_inc_tax']}}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="border: 1px solid black; padding: 2px 3px; text-align: center; font-size: 11px; font-weight: 500; color: #333;">
                                    @if(!empty($line['line_total']))
                                        {{$line['line_total']}}
                                    @elseif(!empty($line['line_total_before_discount']))
                                        {{$line['line_total_before_discount']}}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @php $row_index++; @endphp
                        @endforeach
                    @endforeach
                </table>
            </div>
        @else
            <div style="margin: 15px 0;">
                <table style="width: 100%; border-collapse: collapse; direction: rtl; text-align: right; border-radius: 4px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <tr>
                        <td colspan="5" style="border: 1px solid #ddd; padding: 6px; text-align: center; font-size: 12px; color: #777;">
                            لا توجد منتجات
                        </td>
                    </tr>
                </table>
            </div>
        @endif
    @endforeach

    <!-- Totals Section -->
    <div style="margin: 20px 0;">
        @php
            $discount_display = $receipt_details->discount ?? null;
            $discount_label = $receipt_details->discount_label ?? __('sale.discount');
            if ($discount_display === null || $discount_display === '') {
                $discount_display = $receipt_details->total_line_discount ?? null;
                if ($discount_display !== null && $discount_display !== '') {
                    $discount_label = $receipt_details->line_discount_label ?? $discount_label;
                }
            }
            $show_discount = !($discount_display === null || $discount_display === '');
        @endphp
        <table style="width: 100%; border-collapse: collapse; direction: rtl; text-align: right; border-radius: 4px; border: 1px solid black !important;">
            <tr style="background-color: #f5f5f5;">
                <td style="width: 50%; border: 1px solid black; padding: 4px; text-align: right; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #444; font-weight: 600;">
                            المجموع الفرعي:
                        </span>
                        <span style="color: #333; font-weight: bold;">
                            @if(!empty($receipt_details->subtotal))
                                {{$receipt_details->subtotal}}
                            @elseif(!empty($receipt_details->total_before_tax))
                                {{$receipt_details->total_before_tax}}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                </td>
                <td style="width: 50%; border: 1px solid black; padding: 4px; text-align: right; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #444; font-weight: 600;">
                           المدفوع:
                        </span>
                        @php
                            $totalPaidValue = $receipt_details->total_paid ?? null;
                            $totalPaidDisplay = ($totalPaidValue === null || $totalPaidValue === '') ? '-' : $totalPaidValue;
                        @endphp
                        <span style="color: #333; font-weight: 500;">
                            {{$totalPaidDisplay}}
                        </span>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="width: 50%; border: 1px solid black; padding: 4px; text-align: right; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #444; font-weight: 600;">
                            الخصم:
                        </span>
                        <span style="color: #333; font-weight: 500;">
                            {{ $show_discount ? $discount_display : '-' }}
                        </span>
                    </div>
                </td>
                <td style="width: 50%; border: 1px solid black; padding: 4px; text-align: right; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #444; font-weight: 600;">
                            الاجمالي النهائي:
                        </span>
                        <span style="color: #333; font-weight: bold;">
                            @if(!empty($receipt_details->total))
                                {{$receipt_details->total}}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                </td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td style="width: 50%; border: 1px solid black; padding: 4px; text-align: right; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #444; font-weight: bold;">
                            المتبقي:
                        </span>
                        <span style="color: #d9534f; font-weight: bold;">
                            {{$receipt_details->total_due ?? 0}}
                        </span>
                    </div>
                </td>
                <td style="width: 50%; border: 1px solid black; padding: 4px; text-align: right; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #444; font-weight: 600;">
                            الحالة:
                        </span>
                        <span style="color: #333; font-weight: 500;">
                            {{$receipt_details->payment_status ?? '-'}}
                        </span>
                    </div>
                </td>
            </tr>
        </table>
    </div>


    <!-- Payment Info -->
    @if(!empty($receipt_details->payments) && $receipt_details->show_payments)
    <div style="margin-bottom: 15px;">
        <table style="width: 100%; border-collapse: collapse; direction: rtl; text-align: right; border-radius: 4px; border: 1px solid black !important;">
            <tr style="background-color: #3c8dbc; color: white;">
                <th style="border: 1px solid black; padding: 8px; text-align: center; font-size: 12px; font-weight: 500;">
                    @if(!empty($receipt_details->payment_date_label))
                        {{$receipt_details->payment_date_label}}
                    @else
                        التاريخ
                    @endif
                </th>
                <th style="border: 1px solid black; padding: 8px; text-align: center; font-size: 12px; font-weight: 500;">
                    @if(!empty($receipt_details->payment_amount_label))
                        {{$receipt_details->payment_amount_label}}
                    @else
                        المبلغ
                    @endif
                </th>
                <th style="border: 1px solid black; padding: 8px; text-align: center; font-size: 12px; font-weight: 500;">
                    @if(!empty($receipt_details->paid_label))
                        {{$receipt_details->paid_label}}
                    @else
                        طريقة الدفع
                    @endif
                </th>
            </tr>
            @foreach($receipt_details->payments as $payment)
            <tr @if($loop->even) style="background-color: #f9f9f9;" @else style="background-color: #fff;" @endif>
                <td style="border: 1px solid black; padding: 8px; text-align: center; font-size: 12px; color: #333;">
                    @if(!empty($payment['date']))
                        {{$payment['date']}}
                    @endif
                </td>
                <td style="border: 1px solid black; padding: 8px; text-align: center; font-size: 12px; color: #333;">
                    @if(!empty($payment['amount']))
                        {{$payment['amount']}}
                    @endif
                </td>
       
                <td style="border: 1px solid black; padding: 8px; text-align: center; font-size: 12px; color: #333;">
                    @if(!empty($payment['method']))
                        {{$payment['method']}}
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <!-- Maintenance Note Section -->
    @if(!empty($receipt_details->content))
    <div style="margin-bottom: 12px;">
        <table style="width: 100%; border-collapse: collapse; border-radius: 4px; border: 1px solid black !important;">
            <tr>
                <td style="padding: 3px 4px; text-align: right; font-weight: 600; font-size: 11px; background-color: #f5f5f5; border-bottom: 1px solid black;">
                    ملاحظات الصيانة:
                </td>
            </tr>
            <tr>
                <td style="padding: 6px 6px; font-size: 11px; color: #333; line-height: 1.5; text-align: right; background-color: #ffffff;">
                    {{$receipt_details->content}}
                </td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Additional Notes -->
    @if(!empty($receipt_details->additional_notes))
    <div style="margin-top: 15px; text-align: right; font-size: 12px; color: #555; padding: 10px; border: 1px dashed black; border-radius: 4px; background-color: #f9f9f9;">
        {!! $receipt_details->additional_notes !!}
    </div>
    @endif
    <!-- Signature Section -->
    <div style="margin-top: 20px; margin-bottom: 20px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 30%; border-top: 1px solid black; padding: 8px; text-align: center; font-size: 12px;">
                    <div style="font-weight: bold; color: #444; margin-top: 10px;">توقيع العميل</div>
                </td>
                <td style="width: 40%;"></td>
                <td style="width: 30%;"></td>
            </tr>
        </table>
    </div>


    <!-- Footer -->
    <div style="margin-top: 20px; text-align: center;">
        <!-- QR Code -->
        @if(!empty($receipt_details->qr_code_text) && $receipt_details->show_qr_code)
        <div style="margin-bottom: 15px;">
            <img src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE', 3, 3, [39, 48, 54])}}" alt="QR Code">
        </div>
        @endif

        <!-- Barcode -->
        @if(!empty($receipt_details->invoice_no) && $receipt_details->show_barcode)
        <div style="margin-bottom: 15px;">
            <img src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2, 30, [39, 48, 54], true)}}" alt="Barcode">
        </div>
        @endif

    </div>
</div>
