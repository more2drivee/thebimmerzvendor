<div class="payment_details_div hide" data-type="card" >
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::hidden("card_number", 'Card Payment', ['class' => 'form-control']); !!}
		</div>
	</div>
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::hidden("card_holder_name", 'Card Payment', ['class' => 'form-control']); !!}
		</div>
	</div>
	<div class="col-md-4">
		<div class="form-group">
			{!! Form::hidden("card_transaction_number", 'Card Payment', ['class' => 'form-control']); !!}
		</div>
	</div>
	<div class="clearfix"></div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::hidden("card_type", 'credit', ['class' => 'form-control']); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::hidden("card_month", date('m'), ['class' => 'form-control']); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::hidden("card_year", date('Y'), ['class' => 'form-control']); !!}
		</div>
	</div>
	<div class="col-md-3">
		<div class="form-group">
			{!! Form::hidden("card_security", '000', ['class' => 'form-control']); !!}
		</div>
	</div>
	<div class="clearfix"></div>
</div>
<div class="payment_details_div @if( $payment_line->method !== 'cheque' ) {{ 'hide' }} @endif" data-type="cheque" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("cheque_number",__('lang_v1.cheque_no')) !!}
			{!! Form::text("cheque_number", $payment_line->cheque_number, ['class' => 'form-control', 'placeholder' => __('lang_v1.cheque_no')]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line->method !== 'bank_transfer' ) {{ 'hide' }} @endif" data-type="bank_transfer" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("bank_account_number",__('lang_v1.bank_account_number')) !!}
			{!! Form::text( "bank_account_number", $payment_line->bank_account_number, ['class' => 'form-control', 'placeholder' => __('lang_v1.bank_account_number')]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line->method !== 'custom_pay_1' ) {{ 'hide' }} @endif" data-type="custom_pay_1" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_1", __('lang_v1.transaction_no')) !!}
			{!! Form::text("transaction_no_1", $payment_line->transaction_no, ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no')]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line->method !== 'custom_pay_2' ) {{ 'hide' }} @endif" data-type="custom_pay_2" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_2", __('lang_v1.transaction_no')) !!}
			{!! Form::text("transaction_no_2", $payment_line->transaction_no, ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no')]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line->method !== 'custom_pay_3' ) {{ 'hide' }} @endif" data-type="custom_pay_3" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_3", __('lang_v1.transaction_no')) !!}
			{!! Form::text("transaction_no_3", $payment_line->transaction_no, ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no')]); !!}
		</div>
	</div>
</div>