<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
           <!-- Include Bootstrap Icons (if not already included) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <title>بطاقة عمل السيارة</title>
    <style>
        /* RTL support */
        body { text-align: right; }

        @font-face {
            font-family: 'Cairo';
            src: url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap');
        }

        body {
            font-family: 'Cairo', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }

        .job-card {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff; /* Light yellow background */
            border: 2px solid #000;
            padding: 10px;
            box-sizing: border-box;
            /*page-break-after: always;*/
        }

       .header {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center; /* Ensures text alignment */
    padding: 10px;
    border-bottom: 2px solid #000;
    width: 100%;
}


        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .company-logo {
            max-height: 250px;
            max-width: 220px;
            margin-bottom: 10px;
        }

        .business-location {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
            font-size: 13px;
        }

        .bilingual-label {
            font-weight: bold;
            font-size: 12px;
            display: block;
            margin-bottom: 4px;
        }

        .arabic {
            display: block;
            font-family: 'Cairo', Arial, sans-serif;
            text-align: right;
            direction: rtl;
        }

        .row-highlight:nth-child(even) {
            background-color: #ffffee;
        }

        .row-highlight:nth-child(odd) {
            background-color: #ffffdd;
        }

        .section-heading {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            padding: 5px;
            border: 1px solid #000;
            margin: 10px 0;
        }

        .parts-table td {
            padding: 5px;
            font-size: 12px;
        }

        .parts-header {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .signature-box {
            height: 40px;
            border: 1px dashed #000;
            margin-top: 5px;
        }

        .total-box {
            border: 1px solid #000;
            padding: 5px;
            text-align: right;
            font-weight: bold;
            margin: 2px 0;
        }

        .footer {
            margin-top: 10px;
            font-size: 10px;
            text-align: center;
            padding: 5px;
            border-top: 1px solid #000;
        }

        .media-section {
            margin-top: 15px;
            border-top: 2px solid #000;
            padding: 10px;
        }

        .media-content {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .media-item img {
            max-width: 150px;
            height: auto;
            border: 1px solid #ddd;
            padding: 5px;
        }

        /* Print styles */
        @media print {
            body {
                background-color: #fff;
            }
            .job-card {
                border: none;
                max-width: 100%;
                margin: 0;
            }
        }

        [dir="rtl"] .bilingual-label {
            text-align: right;
        }

        [dir="rtl"] td, [dir="rtl"] th {
            text-align: right;
        }

        [dir="rtl"] .pull-right {
            float: left !important;
        }

        [dir="rtl"] .pull-left {
            float: right !important;
        }
    </style>
</head>
<body dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <div class="job-card">
        <!-- Header Section -->
<div class="header">
    @if(!empty($job_sheet->logo))
        <img src="{{ asset('uploads/business_logos/' . $job_sheet->logo) }}"  class="company-logo" alt="Logo" style="width: auto; max-height: 90px; margin: auto;">
    @else
        <img src="{{ asset('/uploads/images/new_logo.png') }}" alt="CarServPro Logo" class="company-logo">
    @endif
    <div class="business-location">{{ $job_sheet->businessLocation->name }}</div>
</div>


        <!-- Details Table -->

        <table>
            <tr class="row-highlight">
                <td width="25%">
                    <span class="bilingual-label">
                        <span class="arabic">رقم العمل:</span>
                    </span>
                    {{ $job_sheet->job_sheet_no ?? '' }}
                </td>
                <td width="25%">
                    <span class="bilingual-label">
                        <span class="arabic">تاريخ العمل:</span>
                    </span>
                    {{ $job_sheet->entry_date ?? '' }}
                </td>
                <td width="25%">
                    <span class="bilingual-label">
                        <span class="arabic">رقم تسجيل السيارة:</span>
                    </span>
                    {{ $job_sheet->plate_number ?? '' }}
                </td>
                <td width="25%">
                    <span class="bilingual-label">
                        <span class="arabic">السنة:</span>
                    </span>
                    {{ $job_sheet->manufacturing_year ?? '' }}
                </td>
            </tr>
            <tr class="row-highlight">
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">تاريخ/وقت الدخول:</span>
                    </span>
                    {{ $job_sheet->entry_date ?? '' }}
                </td>
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">تاريخ/وقت الخروج:</span>
                    </span>
                    {{ $job_sheet->delivery_date ?? '' }}
                </td>
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">صنع السيارة:</span>
                    </span>
                    {{ $job_sheet->brand_name ?? '' }}
                </td>
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">موديل السيارة:</span>
                    </span>
                    {{ $job_sheet->model_name ?? '' }}
                </td>
            </tr>
            <tr class="row-highlight">
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">اسم العميل:</span>
                    </span>
                    {{ $job_sheet->customer_name ?? '' }}
                </td>
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">رقم الاتصال بالعميل:</span>
                    </span>
                    {{ $job_sheet->customer_phone ?? '' }}
                </td>
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">نوع السيارة:</span>
                    </span>
                    {{ optional(optional($job_sheet->booking)->device)->brandOriginVariant->name ?? '' }}
                </td>
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">رقم الهيكل:</span>
                    </span>
                    {{ $job_sheet->chassis_number ?? '' }}
                </td>
            </tr>
            <tr class="row-highlight">
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">اللون:</span>
                    </span>
                    {{ $job_sheet->color ?? '' }}
                </td>
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">عدد الكيلومترات:</span>
                    </span>
                    {{ $job_sheet->km ?? '' }}
                </td>
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">نوع الخدمة:</span>
                    </span>
                    {{ $job_sheet->service_type ?? '' }}
                </td>
                <td>
                    <span class="bilingual-label">
                        <span class="arabic">الحالة:</span>
                    </span>
                    {{ $job_sheet->status->name ?? '' }}
                </td>
            </tr>
        </table>


        <!-- Vehicle Inspection Section -->

    <table>
    <tr>
        <td width="50%" height="120px">
            <span class="bilingual-label">
                <span class="arabic">العيب المبلغ عنه</span>
            </span>
            {{ $job_sheet->booking_note ?? '' }}
        </td>
        <td width="50%" rowspan="2">
            <span class="bilingual-label">
                <span class="arabic">تقرير هيكل السيارة (ضع علامة X حيث الضرر)</span>
            </span>
            <!-- Media Section - Display only first untagged media -->
            @if(!empty($job_sheet->jobSheet_media))
                <div class="media-section">
                    <div class="media-content">
                        <img src="{{ $job_sheet->jobSheet_media }}"
                            alt="Media Image"
                            style="width: 100%; max-width: 250px; height: auto; max-height: 150px; object-fit: contain; margin: 5px auto; display: block;">
                    </div>
                </div>
            @elseif(!empty($job_sheet->media_list) && count($job_sheet->media_list) > 0)
                <!-- Fallback: use first item from media_list -->
                <div class="media-section">
                    <div class="media-content">
                        <img src="{{ $job_sheet->media_list[0]['url'] }}"
                            alt="Media Image"
                            style="width: 100%; max-width: 250px; height: auto; max-height: 150px; object-fit: contain; margin: 5px auto; display: block;">
                    </div>
                </div>
            @else
                <div style="min-height: 40px; border: 1px dashed #999; padding: 5px; text-align: center; color: #999;">لا توجد صورة</div>
            @endif
        </td>
    </tr>
    <tr>
        <td height="100px">
            <span class="bilingual-label">
                <span class="arabic">ملاحظات الإنجاز / تعليقات الفني</span>
            </span>
           <br> {{ $job_sheet->car_condition ?? '' }}
        </td>
    </tr>
</table>








        <!-- Parts & Labor Section -->

        <table class="parts-table">
            <tr class="parts-header">
                <td width="15%">
                    <span class="arabic">رقم القطعة</span>
                </td>
                <td width="40%">
                    <span class="arabic">وصف القطعة</span>
                </td>
                <td width="10%">
                    <span class="arabic">الكمية</span>
                </td>
                <td width="15%">
                    <span class="arabic">سعر الوحدة</span>
                </td>
                <td width="10%">
                    <span class="arabic">المجموع</span>
                </td>
            </tr>
            @if(!empty($parts) && $parts->isNotEmpty())
                @foreach($parts as $part)
                    <tr class="row-highlight">
                        <td>{{ $part->product_sku ?? 'غير متاح' }}</td>
                        <td>{{ $part->product_name ?? '' }}</td>
                        <td>{{ $part->quantity ?? '' }}</td>
                        <td>£{{ !is_null($part->price) ? number_format($part->price, 2) : '' }}</td>
                        <td>£{{ !is_null($part->total_price) ? number_format($part->total_price, 2) : '' }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" style="text-align: center; font-weight: bold;">لا يوجد قطع غيار</td>
                </tr>
            @endif
        </table>



     <table>
            <tr>
                <td width="60%" style="vertical-align: top;">
                    <span class="bilingual-label">
                        <span class="arabic">تفويض العميل:</span>
                    </span>
                    <p style="font-size: 10px;">
                        <span class="arabic">أفوض بموجب هذا القيام بالعمل المذكور أعلاه مع المواد اللازمة وأوافق على الشروط والأحكام.</span>
                    </p>
                    <div style="margin-top: 10px;">
                        <span class="bilingual-label">
                            <span class="arabic">توقيع العميل:</span>
                        </span>
                        <div class="signature-box"></div>
                    </div>
                </td>
                <td width="40%">
                    <table class="small-summary-table">

                        <tr>
                            <td class="bilingual-label"><span class="arabic">المجموع الكلي</span></td>
                            <td class="total-box" style="font-size: 14px; font-weight: bold;">£{{ !is_null($job_sheet->estimated_cost) ? number_format($job_sheet->estimated_cost, 2) : '' }}</td>
                        </tr>
                    </table>
                </td>

            </tr>
        </table>


        <!-- Footer -->
        <div class="footer">
            <p>
                <span class="arabic">هذا سجلنا الرسمي. جميع التقديرات صالحة لمدة 30 يومًا. نحن لسنا مسؤولين عن العناصر المتروكة لأكثر من 30 يومًا.</span>
            </p>
        </div>

    </div>
</body>
</html>