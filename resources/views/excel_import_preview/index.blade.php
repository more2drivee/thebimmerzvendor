@extends('layouts.app')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>Excel Import Preview</h1>
    </section>

    <section class="content">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Upload Excel File</h3>
            </div>

            <div class="box-body">
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('excel_import_preview.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label>Select Excel File</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                        <p class="help-block">Supported formats: .xlsx, .xls</p>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-upload"></i> Upload & Preview
                    </button>
                </form>
            </div>
        </div>

        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Expected Columns</h3>
            </div>
            <div class="box-body">
                <p>The Excel file should contain the following columns (in any order):</p>
                <ul>
                    <li><strong>اسم الصنف / Product Name / Name</strong> - Product name (required)</li>
                    <li><strong>كود الصنف / SKU / Code</strong> - Product SKU</li>
                    <li><strong>CATEGORY / Category / الفئة</strong> - Category</li>
                    <li><strong>SUB-CATEGORY / Sub Category / الفئة الفرعية</strong> - Sub-category</li>
                    <li><strong>brand / Brand / الماركة</strong> - Brand</li>
                    <li><strong>الكمية / QTY / Quantity / Stock</strong> - Opening stock quantity</li>
                    <li><strong>سعر الشراء / Cost / Purchase Price</strong> - Purchase price</li>
                    <li><strong>سعر البيع / Price / Selling Price</strong> - Selling price</li>
                    <li><strong>المخزن / Location / Store</strong> - Location name</li>
                </ul>
            </div>
        </div>
    </section>
</div>
@endsection
