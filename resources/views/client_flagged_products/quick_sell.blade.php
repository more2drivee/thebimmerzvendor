@extends('layouts.app')

@section('title', __('client_flagged_products.quick_sell'))

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>{{ __('client_flagged_products.quick_sell') }}</h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('client_flagged_products.sell_product') }}</h3>
                    </div>
                    <div class="box-body">
                        <form id="quick_sell_form" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="product_id">{{ __('client_flagged_products.select_product') }} <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="product_id" name="product_id" required>
                                            <option value="">{{ __('lang_v1.select') }}</option>
                                            @foreach($products as $id => $name)
                                                <option value="{{ $id }}" @if($product && $product->id == $id) selected @endif>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quantity">{{ __('sale.quantity') }} <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="0.01" step="0.01" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="price">{{ __('sale.unit_price') }} <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="price" name="price" value="0" min="0" step="0.01" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contact_id">{{ __('contact.customer') }}</label>
                                        <select class="form-control select2" id="contact_id" name="contact_id">
                                            <option value="">{{ __('lang_v1.walk_in_customer') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">{{ __('sale.status') }} <span class="text-danger">*</span></label>
                                        <select class="form-control" id="status" name="status" required>
                                            @foreach($statuses as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="transaction_date">{{ __('sale.transaction_date') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control datetimepicker" id="transaction_date" name="transaction_date" value="{{ $default_datetime }}" required>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="additional_notes">{{ __('sale.additional_notes') }}</label>
                                        <textarea class="form-control" id="additional_notes" name="additional_notes" rows="3"></textarea>
                                    </div>
                                </div>

                                <div class="col-md-12 text-center">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-shopping-cart"></i> {{ __('sale.save') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.datetimepicker').datetimepicker({
        format: 'YYYY-MM-DD HH:mm',
        icons: {
            time: 'far fa-clock',
            date: 'far fa-calendar',
            up: 'fas fa-arrow-up',
            down: 'fas fa-arrow-down',
            previous: 'fas fa-chevron-left',
            next: 'fas fa-chevron-right',
            today: 'far fa-calendar-check',
            clear: 'far fa-trash-alt',
            close: 'far fa-times-circle'
        }
    });

    $(document).on('submit', '#quick_sell_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(form[0]);
        var url = '{{ route("client_flagged_products.quick_sell.store") }}';

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg);
                    window.location.href = '{{ route("transactions.show", ["id" => ""]) }}' + response.data.transaction_id;
                } else {
                    toastr.error(response.msg);
                    if (response.errors) {
                        $.each(response.errors, function(key, value) {
                            toastr.error(value);
                        });
                    }
                }
            },
            error: function(xhr) {
                toastr.error('{{ __("messages.something_went_wrong") }}');
            }
        });
    });
});
</script>
@endpush
