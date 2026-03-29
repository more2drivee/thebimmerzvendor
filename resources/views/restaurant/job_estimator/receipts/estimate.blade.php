<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مقايسة اصلاح - {{ $estimator->estimate_no }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap');
        
        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 10px;
            color: #333;
            background-color: #fff;
            font-size: 13px;
        }

        .container {
            max-width: 850px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .logo-section {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .logo {
            max-height: 60px;
            margin-bottom: 5px;
        }

        .title-section {
            text-align: center;
            flex-grow: 1;
        }

        .title-section h1 {
            margin: 0;
            font-size: 24px;
            color: #000;
            text-decoration: underline;
        }

        .contact-section {
            text-align: right;
            font-size: 11px;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-grid td {
            border: 1px solid #000;
            padding: 3px 6px;
            font-size: 12px;
        }

        .label-cell {
            background-color: #f9f9f9;
            font-weight: bold;
            width: 20%;
        }

        .value-cell {
            width: 30%;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .main-table th {
            background-color: #eee;
            border: 1px solid #000;
            padding: 4px;
            font-size: 12px;
            text-align: center;
        }

        .main-table td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 12px;
            text-align: center;
            height: 20px;
        }

        .footer-summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .footer-summary td {
            border: 1px solid #000;
            padding: 5px 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .footer-label {
            width: 75%;
            text-align: left;
            padding-left: 20px;
            background-color: #f9f9f9;
        }

        .footer-value {
            width: 25%;
            text-align: center;
        }

        .print-btn {
            display: block;
            width: 100px;
            margin: 15px auto;
            padding: 8px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-align: center;
        }

        @media print {
            .print-btn {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .container {
                box-shadow: none;
                border: none;
                max-width: 100%;
                padding: 0;
            }
            @page {
                size: A4;
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
           
          
            <div class="contact-section">
                @if(!empty($estimator->location))
                    {{ $estimator->location->name }}<br>
                    {{ $estimator->location->mobile }}<br>
                    {{ $estimator->location->email }}
                @endif
            </div>
              <div class="title-section">
                <h1>مقايسه اصلاح</h1>
            </div>
             <div class="logo-section">
                @if(!empty($business_details->logo))
                    <img src="{{ asset( 'uploads/business_logos/' . $business_details->logo ) }}" class="logo">
                @else
                    <h2 style="margin:0; font-size: 18px;">{{ $business_details->name }}</h2>
                @endif
            </div>
        </div>

        <table class="info-grid">
            <tr>
                <td class="label-cell">التاريخ</td>
                <td class="value-cell">{{ @format_date($estimator->created_at) }}</td>
                <td class="label-cell">رقم المقايسة</td>
                <td class="value-cell">{{ $estimator->estimate_no }}</td>
            </tr>
            <tr>
                <td class="label-cell">اسم العميل</td>
                <td class="value-cell">{{ $estimator->customer->name }}</td>
                <td class="label-cell">رقم الهاتف</td>
                <td class="value-cell">{{ $estimator->customer->mobile ?? '' }}</td>
            </tr>
            <tr>
                <td class="label-cell">موديل السيارة</td>
                <td class="value-cell">{{ $brand }} {{ $repair_model }}</td>
                <td class="label-cell">رقم اللوحة</td>
                <td class="value-cell">{{ $estimator->device->plate_number ?? '' }}</td>
            </tr>
            <tr>
                <td class="label-cell">اللون</td>
                <td class="value-cell">{{ $estimator->device->color ?? '' }}</td>
                <td class="label-cell">رقم الشاسيه</td>
                <td class="value-cell">{{ $estimator->device->chassis_number ?? '' }}</td>
            </tr>
        </table>

        <table class="main-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="30%">قطع الغيار</th>
                    <th width="10%">الكميه</th>
                    <th width="15%">السعر</th>
                    <th width="40%">بيان الاعمال المطلوبه</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $total_parts_price = 0; 
                    $total_labor_price = 0;
                    $spare_parts_index = 0;
                    $services_index = 0;
                    
                    // Separate spare parts and services
                    $spare_parts = [];
                    $services = [];
                    foreach($job_order_lines as $line) {
                        $line_total = $line->price * $line->quantity;
                        if($line->enable_stock == 1) {
                            $total_parts_price += $line_total;
                            $spare_parts[] = $line;
                        } else {
                            $total_labor_price += $line_total;
                            $services[] = $line;
                        }
                    }
                    
                    // Calculate max rows needed (minimum 10, but scale up if more items)
                    $max_rows = max(10, count($spare_parts), count($services));
                @endphp
                {{-- Display Services and Spare Parts side by side --}}
                @for($i = 0; $i < $max_rows; $i++)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        {{-- Spare Parts Column --}}
                        <td>
                            @if(isset($spare_parts[$i]))
                                {{ $spare_parts[$i]->product_name }}
                            @endif
                        </td>
                        <td>
                            @if(isset($spare_parts[$i]))
                                {{ number_format($spare_parts[$i]->quantity, 0) }}
                            @endif
                        </td>
                        <td>
                            @if(isset($spare_parts[$i]))
                                {{ @num_format($spare_parts[$i]->price * $spare_parts[$i]->quantity) }}
                            @endif
                        </td>
                        {{-- Services Column --}}
                        <td>
                            @if(isset($services[$i]))
                                {{ $services[$i]->product_name }}{{ !empty($services[$i]->Notes) ? ' - ' . $services[$i]->Notes : '' }} <strong>({{ @num_format($services[$i]->price) }})</strong>
                            @endif
                        </td>
                    </tr>
                @endfor
                {{-- Fill remaining rows up to a total of 10 for compact display --}}
                @for($i = count($job_order_lines); $i < $max_rows; $i++)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <table class="footer-summary">
            <tr>
                <td class="footer-label">اجمالي قطع الغيار</td>
                <td class="footer-value">{{ @num_format($total_parts_price) }}</td>
            </tr>
            <tr>
                <td class="footer-label">اجمالي المصنعيات</td>
                <td class="footer-value">{{ @num_format($total_labor_price) }}</td>
            </tr>
            <tr>
                <td class="footer-label">الاجمالي</td>
                <td class="footer-value">{{ @num_format($total_parts_price + $total_labor_price) }}</td>
            </tr>
        </table>

        <div style="margin-top: 20px; text-align: center; font-size: 12px;">
            <p>شكراً لتعاملكم معنا</p>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()">طباعة</button>
</body>
</html>
