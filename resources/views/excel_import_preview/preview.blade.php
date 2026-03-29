@extends('layouts.app')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>Excel Import Preview</h1>
    </section>

    <section class="content">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Preview - {{ $total_rows }} Total Rows (Showing first 100)</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('excel_import_preview.index') }}" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="box-body">
                <div class="alert alert-info">
                    <strong>Column Mapping Found:</strong>
                    <ul style="margin-bottom: 0;">
                        @if($idx_name !== false)<li>Name: Column {{ $idx_name + 1 }}</li>@endif
                        @if($idx_sku !== false)<li>SKU: Column {{ $idx_sku + 1 }}</li>@endif
                        @if($idx_cat !== false)<li>Category: Column {{ $idx_cat + 1 }}</li>@endif
                        @if($idx_sub !== false)<li>Sub-Category: Column {{ $idx_sub + 1 }}</li>@endif
                        @if($idx_brand !== false)<li>Brand: Column {{ $idx_brand + 1 }}</li>@endif
                        @if($idx_qty !== false)<li>Quantity: Column {{ $idx_qty + 1 }}</li>@endif
                        @if($idx_cost !== false)<li>Cost: Column {{ $idx_cost + 1 }}</li>@endif
                        @if($idx_price !== false)<li>Price: Column {{ $idx_price + 1 }}</li>@endif
                        @if($idx_loc !== false)<li>Location: Column {{ $idx_loc + 1 }}</li>@endif
                    </ul>
                </div>

                <form action="{{ route('excel_import_preview.generate_sql') }}" method="POST" id="generateSqlForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Business ID</label>
                                <input type="number" name="business_id" class="form-control" value="1" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>User ID</label>
                                <input type="number" name="user_id" class="form-control" value="1" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Unit ID</label>
                                <input type="number" name="unit_id" class="form-control" value="1" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Location ID</label>
                                <input type="number" name="location_id" class="form-control" value="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa fa-download"></i> Generate SQL
                                    </button>
                                    <button type="button" class="btn btn-primary" onclick="selectAll()">
                                        <i class="fa fa-check-square"></i> Select All
                                    </button>
                                    <button type="button" class="btn btn-warning" onclick="deselectAll()">
                                        <i class="fa fa-square-o"></i> Deselect All
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="products" id="productsData">

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">
                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)">
                                    </th>
                                    <th>#</th>
                                    <th>SKU</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Sub-Category</th>
                                    <th>Brand</th>
                                    <th>Quantity</th>
                                    <th>Cost</th>
                                    <th>Price</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $index => $product)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="product-checkbox" value="{{ $index }}" checked>
                                        </td>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $product['sku'] }}</td>
                                        <td>{{ $product['name'] }}</td>
                                        <td>{{ $product['category'] }}</td>
                                        <td>{{ $product['sub_category'] }}</td>
                                        <td>{{ $product['brand'] }}</td>
                                        <td>{{ $product['quantity'] }}</td>
                                        <td>{{ number_format($product['cost'], 2) }}</td>
                                        <td>{{ number_format($product['price'], 2) }}</td>
                                        <td>{{ $product['location'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
    var productsData = @json($products);

    function toggleAll(checkbox) {
        var checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(function(cb) {
            cb.checked = checkbox.checked;
        });
    }

    function selectAll() {
        var checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(function(cb) {
            cb.checked = true;
        });
        document.getElementById('selectAllCheckbox').checked = true;
    }

    function deselectAll() {
        var checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(function(cb) {
            cb.checked = false;
        });
        document.getElementById('selectAllCheckbox').checked = false;
    }

    document.getElementById('generateSqlForm').addEventListener('submit', function(e) {
        var selectedProducts = [];
        var checkboxes = document.querySelectorAll('.product-checkbox:checked');

        checkboxes.forEach(function(checkbox) {
            var index = parseInt(checkbox.value);
            selectedProducts.push(productsData[index]);
        });

        if (selectedProducts.length === 0) {
            e.preventDefault();
            alert('Please select at least one product to generate SQL.');
            return;
        }

        document.getElementById('productsData').value = JSON.stringify(selectedProducts);
    });
</script>
@endsection

