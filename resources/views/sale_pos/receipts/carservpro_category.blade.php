<style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap');
</style>
<div style="direction: rtl; font-family: 'Cairo', 'Arial', sans-serif; text-align: right; max-width: 100%; margin: 0 auto;">
    <div style="  overflow: hidden;">
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
                    <div style="font-size: 12px; color: #555;">
                        {{$receipt_details->address}}
                    </div>
                @endif
                @if(!empty($receipt_details->contact))
                    <div style="font-size: 12px; color: #555; direction: ltr; text-align: left; unicode-bidi: embed; float: right">
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
    
        <table style="width: 100%; border-collapse: collapse; table-layout: fixed; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px; overflow: hidden;">
            <tr>
                <td colspan="2" style="width: 50%; border: 1px solid #ddd; padding: 10px; text-align: center; background-color: #f8f8f8; border-radius: 4px 0 0 0;">
                    <div style="font-weight: bold; color: #444; font-size: 12px;">تاريخ البيان:</div>
                    <div style="color: #333; font-size: 13px;">
                        @if(!empty($receipt_details->invoice_date))
                            {{$receipt_details->invoice_date}}
                        @else
                            -
                        @endif
                    </div>
                </td>
                <td style="width: 25%; border: 1px solid #ddd; padding: 10px; text-align: center; background-color: #f8f8f8;">
                    <div style="font-weight: bold; color: #444; font-size: 12px;">رقم</div>
                    <div style="color: #333; font-size: 13px;">
                        @if(!empty($receipt_details->invoice_no))
                            {{$receipt_details->invoice_no}}
                        @else
                            -
                        @endif
                    </div>
                </td>
                <td style="width: 25%; border: 1px solid #ddd; padding: 10px; text-align: center; background-color: #f8f8f8; border-radius: 0 4px 0 0;">
                    <div style="font-weight: bold; color: #444; font-size: 12px;">طريقة الدفع:</div>
                    <div style="color: #333; font-size: 13px;">
                        @if(!empty($receipt_details->payments))
                            {{ $receipt_details->payments[0]['method'] ?? '-' }}
                        @else
                            -
                        @endif
                    </div>
                </td>
            </tr>
            <tr>
                <td style="width: 25%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9; border-radius: 0 0 0 4px;">
                    <div style="font-weight: bold; color: #444;">العميل</div>
                    <div style="color: #333;">
                        @if(!empty($receipt_details->customer_name))
                            {{$receipt_details->customer_name}}
                        @elseif(!empty($receipt_details->customer_info))
                            {{$receipt_details->customer_info}}
                        @endif
                    </div>
                </td>
                <td style="width: 25%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444;">رقم الهاتف</div>
                    <div style="color: #333;">
                        {{$receipt_details->customer_info}}
                    </div>
                </td>
                <td style="width: 25%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444;">رقم امر الشغل</div>
                    <div style="color: #333;">
                        @if(!empty($receipt_details->service_no))
                            {{$receipt_details->service_no}}
                        @endif
                    </div>
                </td>
                <td style="width: 25%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9; border-radius: 0 0 4px 0;">
                    <div style="font-weight: bold; color: #444;">رقم اللوحة:</div>
                    <div style="color: #333;">
                        @if(!empty($receipt_details->license_plate))
                            {{$receipt_details->license_plate}}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div >
        <table style="width: 100%; border-collapse: collapse; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px; overflow: hidden;">
            <tr>
                <td style="width: 20%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 3px;">عداد كم</div>
                    <div style="color: #333;">
                        @if(!empty($receipt_details->odometer))
                            {{$receipt_details->odometer}}
                        @elseif(!empty($receipt_details->repair_serial_no))
                            {{$receipt_details->repair_serial_no}}
                        @endif
                    </div>
                </td>
                <td style="width: 20%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 3px;">رقم الشاسيه:</div>
                    <div style="color: #333;">
                        @if(!empty($receipt_details->vin_number))
                            {{$receipt_details->vin_number}}
                        @endif
                    </div>
                </td>
                <td style="width: 20%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 3px;">طراز السيارة:</div>
                    <div style="color: #333;">
                        @if(!empty($receipt_details->repair_brand))
                            {{$receipt_details->repair_brand}}
                        @elseif(!empty($receipt_details->car_brand))
                            {{$receipt_details->car_brand}}
                        @endif
                    </div>
                </td>
                <td style="width: 20%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 3px;">الموديل:</div>
                    <div style="color: #333;">
                        @if(!empty($receipt_details->repair_model_no))
                            {{$receipt_details->repair_model_no}}
                        @elseif(!empty($receipt_details->car_model))
                            {{$receipt_details->car_model}}
                        @endif
                    </div>
                </td>
                <td style="width: 20%; border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; background-color: #f9f9f9;">
                    <div style="font-weight: bold; color: #444; margin-bottom: 3px;">اللون:</div>
                    <div style="color: #333;">
                        @if(!empty($receipt_details->car_color))
                            {{$receipt_details->car_color}}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Maintenance Note Section -->
    @if(!empty($receipt_details->content))
    <div style="margin-bottom: 15px;">
        <div style="border: 1px solid #ddd; padding: 10px; text-align: right; font-weight: bold; background-color: #3c8dbc; color: white; font-size: 12px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">ملاحظات الصيانة:</div>
        <div style=" padding: 15px; border: 1px solid #ddd; border-radius: 4px; background-color: #f9f9f9; font-size: 12px; color: #333; line-height: 1.5; text-align: right;">
            {{$receipt_details->content}}
        </div>
    </div>
    @endif

    <!-- Products Table -->
    <div style="margin: 15px 0;">
        <table style="width: 100%; border-collapse: collapse; direction: rtl; text-align: right; border-radius: 4px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <tr style="background-color: #f8f8f8; color: black;">
                <th style="border: 1px solid #ddd; padding: 8px; text-align: center; width: 15%; font-size: 11px;">
                     #
                </th>
                <th style="border: 1px solid #ddd; padding: 10px; text-align: center; width: 30%; font-size: 12px; font-weight: 500;">
                    المنتج
                </th>
                <th style="border: 1px solid #ddd; padding: 10px; text-align: center; width: 15%; font-size: 12px; font-weight: 500;">
                    العدد
                </th>
                <th style="border: 1px solid #ddd; padding: 10px; text-align: center; width: 20%; font-size: 12px; font-weight: 500;">
                    سعر الوحدة
                </th>
                <th style="border: 1px solid #ddd; padding: 10px; text-align: center; width: 20%; font-size: 12px; font-weight: 500;">
                    المجموع الفرعي
                </th>
            </tr>
            @php
                $common_settings = $receipt_details->common_settings ?? [];
                $separate_parts_services = !empty($common_settings['carservpro_separate_parts_services']);

                $all_lines = collect($receipt_details->lines ?? []);
                if ($separate_parts_services) {
                    $display_lines = $all_lines->filter(function ($line) {
                        $is_stock_enabled = isset($line['enable_stock']) && (int) $line['enable_stock'] === 1;
                        $is_virtual = !empty($line['virtual_product']) && $line['virtual_product'] == true;
                        $is_client_flagged = !empty($line['is_client_flagged']) && $line['is_client_flagged'] == true;
                        return $is_stock_enabled || $is_virtual || $is_client_flagged;
                    });
                } else {
                    $display_lines = $all_lines;
                }
            @endphp
            @if($display_lines->isNotEmpty())
                @php
                    $sorted_lines = $display_lines->sortBy(function($line) {
                        $category = isset($line['category_name']) ? $line['category_name'] : '';
                        $name = isset($line['name']) ? $line['name'] : '';
                        return $category . '|' . $name;
                    });
                    $current_category = null;
                @endphp
                @foreach($sorted_lines as $line)
                    @php
                        $category = isset($line['category_name']) ? $line['category_name'] : '';
                    @endphp
                    @if($category !== $current_category)
                        @php $current_category = $category; @endphp
                        <tr>
                            <td colspan="5" style="border: 1px solid #ddd; padding: 6px 10px; background-color: #e9ecef; font-size: 11px; font-weight: 600; color: #333; text-align: right;">
                                {{ $current_category !== '' ? $current_category : 'بدون فئة' }}
                            </td>
                        </tr>
                    @endif
                    <tr @if($loop->even) style="background-color: #f9f9f9;" @else style="background-color: #fff;" @endif>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 11px; color: #555;">
                            {{$loop->iteration}}
                        </td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: right; font-size: 12px;">
                            @if(!empty($line['name']))
                                @php
                                    $productName = $line['name'];
                                    $hasClientNote = \Illuminate\Support\Str::contains($productName, ['طرف العميل', 'من طرف العميل']);
                                    if(!empty($line['is_client_flagged']) && $line['is_client_flagged'] == true && !$hasClientNote) {
                                        $productName .= ' من طرف العميل';
                                    }
                                    $variationText = trim(strip_tags($line['variation'] ?? ''));
                                    $noteText = trim(strip_tags($line['sell_line_note'] ?? ''));
                                @endphp
                                <div style="font-weight: 500; color: #333;">{{$productName}}</div>
                                @if(!empty($variationText) && $variationText !== $productName)
                                    <div style="font-size: 11px; color: #666; margin-top: 3px;">{{$line['variation']}}</div>
                                @endif
                                @if(!empty($noteText) && $noteText !== $productName)
                                    <div style="font-size: 11px; color: #666; margin-top: 3px; font-style: italic;">{{$line['sell_line_note']}}</div>
                                @endif
                            @endif
                        </td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: center; font-size: 12px; color: #333;">
                            @if(!empty($line['quantity']))
                                {{$line['quantity']}}
                            @else
                                -
                            @endif
                        </td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: center; font-size: 12px; color: #333;">
                            @if(!empty($line['unit_price_inc_tax']))
                                {{$line['unit_price_inc_tax']}}
                            @else
                                -
                            @endif
                        </td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: center; font-size: 12px; font-weight: 500; color: #333;">
                            @if(!empty($line['line_total']))
                                {{$line['line_total']}}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" style="border: 1px solid #ddd; padding: 15px; text-align: center; font-size: 12px; color: #777;">
                        لا توجد منتجات
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <!-- Totals Section -->
    <div style="margin: 20px 0;">
        <table style="width: 100%; border-collapse: collapse; direction: rtl; text-align: right; border-radius: 4px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            @if(!empty($receipt_details->tax))
            <tr>
                <td style="width: 50%; border: 1px solid #ddd; padding: 10px; text-align: right; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #555; font-weight: 500;">
                            @if(!empty($receipt_details->tax_label))
                                {{$receipt_details->tax_label}}:
                            @else
                                الضريبة:
                            @endif
                        </span>
                        <span style="color: #333;">
                            {{$receipt_details->tax}}
                        </span>
                    </div>
                </td>
                <td style="width: 50%; border: 1px solid #ddd; padding: 10px; text-align: right; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #555; font-weight: 500;">
                            @if(!empty($receipt_details->discount_label))
                                {{$receipt_details->discount_label}}:
                            @else
                                الخصم:
                            @endif
                        </span>
                        <span style="color: #333;">
                            @if(!empty($receipt_details->discount))
                                {{$receipt_details->discount}}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                </td>
            </tr>
            @endif
            <tr style="background-color: #f5f5f5;">
                <td style="width: 50%; border: 1px solid #ddd; padding: 10px; text-align: right; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #444; font-weight: 600;">
                            المجموع:
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
                <td style="width: 50%; border: 1px solid #ddd; padding: 10px; text-align: right; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #444; font-weight: 600;">
                                المدفوع
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
            @if(!empty($receipt_details->total_due))
            <tr>
                <td style="width: 50%; border: 1px solid #ddd; padding: 10px; text-align: right; font-size: 12px;"></td>
                <td style="width: 50%; border: 1px solid #ddd; padding: 10px; text-align: right; font-size: 12px; background-color: #f0f0f0;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #444; font-weight: bold;">
                                المتبقي:
                        </span>
                        <span style="color: #d9534f; font-weight: bold;">
                            {{$receipt_details->total_due}}
                        </span>
                    </div>
                </td>
            </tr>
            @endif
            @if(!empty($receipt_details->discount))
            <td style="width: 50%; border: 1px solid #ddd; padding: 8px; text-align: right; font-size: 11px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #555; font-weight: 500;">
                                الخصم:
                        </span>
                        <span style="color: #333;">
                            @if(!empty($receipt_details->discount))
                                {{$receipt_details->discount}}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                </td>
                @endif
        </table>
    </div>

    <!-- Payment Info -->
    @if(!empty($receipt_details->payments) && $receipt_details->show_payments)
    <div style="margin-bottom: 15px;">
        <table style="width: 100%; border-collapse: collapse; direction: rtl; text-align: right; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px; overflow: hidden;">
            <tr style="background-color: #3c8dbc; color: white;">
                <th style="border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 12px; font-weight: 500;">
                    @if(!empty($receipt_details->payment_date_label))
                        {{$receipt_details->payment_date_label}}
                    @else
                        التاريخ
                    @endif
                </th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 12px; font-weight: 500;">
                    @if(!empty($receipt_details->payment_amount_label))
                        {{$receipt_details->payment_amount_label}}
                    @else
                        المبلغ
                    @endif
                </th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 12px; font-weight: 500;">
                    @if(!empty($receipt_details->paid_label))
                        {{$receipt_details->paid_label}}
                    @else
                        طريقة الدفع
                    @endif
                </th>
            </tr>
            @foreach($receipt_details->payments as $payment)
            <tr @if($loop->even) style="background-color: #f9f9f9;" @else style="background-color: #fff;" @endif>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 12px; color: #333;">
                    @if(!empty($payment['date']))
                        {{$payment['date']}}
                    @endif
                </td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 12px; color: #333;">
                    @if(!empty($payment['amount']))
                        {{$payment['amount']}}
                    @endif
                </td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 12px; color: #333;">
                    @if(!empty($payment['method']))
                        {{$payment['method']}}
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <!-- Signature Section -->
    <div style="margin-top: 20px; margin-bottom: 20px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 30%; border-top: 1px solid #333; padding: 8px; text-align: center; font-size: 12px;">
                    <div style="font-weight: bold; color: #444; margin-top: 10px;">توقيع العميل</div>
                </td>
                <td style="width: 40%;"></td>
                <td style="width: 30%;"></td>
            </tr>
        </table>
    </div>

    <!-- Additional Notes -->
    @if(!empty($receipt_details->additional_notes))
    <div style="margin-top: 15px; text-align: right; font-size: 12px; color: #555; padding: 10px; border: 1px dashed #ddd; border-radius: 4px; background-color: #f9f9f9;">
        {!! $receipt_details->additional_notes !!}
    </div>
    @endif

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
