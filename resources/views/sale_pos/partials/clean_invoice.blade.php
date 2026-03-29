<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice</title>
    
    <!-- Include only the necessary CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">
    
    <style>
        body {
            background-color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
            }
            /* Hide URL and page info in print */
            @page {
                size: auto;
                margin: 0mm;  /* Remove default margins */
            }
            /* Hide URL and date from footer */
            @page :footer {
                display: none;
                visibility: hidden;
            }
            @page :header {
                display: none;
                visibility: hidden;
            }
        }
        
        /* Hide the invoice heading text */
        #invoice_content div:first-child {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="text-right mb-3 no-print " style="margin-bottom: 20px;">
            <button type="button" class="btn btn-primary btn-sm" style="padding: 8px 16px; font-weight: 500; border-radius: 4px; margin-right: 5px;" onclick="window.print();" >
                <i class="fa fa-print"></i> Print
            </button>
            <a href="{{ url()->previous() }}" class="btn btn-danger btn-sm" style="padding: 8px 16px !important; font-weight: 500 !important; border-radius: 4px !important; background-color: #dc3545 !important; color: white !important; text-decoration: none !important; display: inline-block !important; border: 1px solid #dc3545 !important;">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
        
        <div id="invoice_content">
            {!! $receipt['html_content'] !!}
        </div>
    </div>
    
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Remove any element with text "Invoice" at the top
            $('#invoice_content').find(':contains("Invoice")').each(function() {
                if ($(this).text().trim() === 'Invoice') {
                    $(this).hide();
                }
            });
            
            if (window.location.search.includes('print=true')) {
                window.print();
            }
        });
        
        // Override the default print behavior to hide URL
        window.onbeforeprint = function() {
            // Additional preparations before printing
        };
    </script>
</body>
</html>





