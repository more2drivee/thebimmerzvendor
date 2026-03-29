@extends('layouts.app')
@section('title', 'Custom Product Import')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Custom Product Import</h1>
</section>

<!-- Main content -->
<section class="content">
    @if (session('status'))
        <div class="alert alert-{{ session('status')['success'] ? 'success' : 'danger' }}">
            {{ session('status')['msg'] }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">Upload Excel File</h3>
                </div>
                <div class="box-body">
                    <form action="{{ route('custom-import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="form-group">
                            <label for="products_excel">Excel File:</label>
                            <input type="file" name="products_excel" id="products_excel" class="form-control" required>
                            <p class="help-block">Supported formats: .xlsx, .xls</p>
                        </div>

                        <div class="form-group">
                            <p><strong>Expected Columns (Auto-detected):</strong></p>
                            <ul>
                                <li>Product Name / اسم الصنف</li>
                                <li>SKU / كود الصنف / كود</li>
                                <li>Category / Brand (Optional)</li>
                                <li>Qty / الكمية (Optional - for Opening Stock)</li>
                                <li>Cost / Price (Optional)</li>
                                <li>Location / المخزن (Matches Location Name)</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary">Import Products</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</section>
<!-- /.content -->

@endsection
