@extends('layouts.app')
@section('title', __('treasury::lang.add_treasury_transaction'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('treasury::lang.add_treasury_transaction') }}</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action([\Modules\Treasury\Http\Controllers\TreasuryController::class, 'store']), 'method' => 'post', 'id' => 'treasury_transaction_form']) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('transaction_date', __('treasury::lang.transaction_date') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required', 'id' => 'transaction_date']) !!}
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('transaction_type', __('treasury::lang.transaction_type') . ':*') !!}
                            {!! Form::select('transaction_type', ['income' => __('treasury::lang.income'), 'expense' => __('treasury::lang.expense')], null, ['class' => 'form-control select2', 'required', 'id' => 'transaction_type', 'placeholder' => __('messages.please_select')]) !!}
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('amount', __('treasury::lang.amount') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-money-bill-alt"></i>
                                </span>
                                {!! Form::text('amount', null, ['class' => 'form-control input_number', 'required', 'placeholder' => __('treasury::lang.amount')]) !!}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('reference_no', __('treasury::lang.reference_no') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-link"></i>
                                </span>
                                {!! Form::text('reference_no', null, ['class' => 'form-control', 'placeholder' => __('treasury::lang.reference_no')]) !!}
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('contact_id', __('treasury::lang.contact') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                {!! Form::select('contact_id', [], null, ['class' => 'form-control select2', 'id' => 'contact_id', 'placeholder' => __('messages.please_select')]) !!}
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('payment_status', __('treasury::lang.payment_status') . ':*') !!}
                            {!! Form::select('payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' => __('lang_v1.partial')], 'paid', ['class' => 'form-control select2', 'required', 'id' => 'payment_status']) !!}
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('note', __('lang_v1.note') . ':') !!}
                            {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('lang_v1.note')]) !!}
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize date picker
        $('#transaction_date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd'
        });
        
        // Initialize select2
        $('.select2').select2();
        
        // Load contacts based on transaction type
        $('#transaction_type').change(function() {
            var type = $(this).val();
            if (type) {
                $.ajax({
                    url: '/contacts/get-dropdown',
                    data: {
                        type: type === 'income' ? 'customer' : 'supplier'
                    },
                    dataType: 'json',
                    success: function(result) {
                        $('#contact_id').empty().append('<option value="">Please Select</option>');
                        if (result.data) {
                            $.each(result.data, function(key, value) {
                                $('#contact_id').append('<option value="' + key + '">' + value + '</option>');
                            });
                        }
                    }
                });
            } else {
                $('#contact_id').empty().append('<option value="">Please Select</option>');
            }
        });
    });
</script>
@endsection
