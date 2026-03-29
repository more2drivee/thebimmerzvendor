@extends('layouts.app')
@section('title', __('expense.add_expense'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('expense.add_expense')</h1>
</section>

<!-- Navbar -->
<section class="no-print">
    <nav class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#expense-navbar" aria-expanded="false" style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{ action([\App\Http\Controllers\ExpenseController::class, 'index']) }}"><i class="fa fa-money-bill"></i> {{ __('expense.expenses') }}</a>
            </div>
            <div class="collapse navbar-collapse" id="expense-navbar">
                <ul class="nav navbar-nav d-block" style="position: relative !important;">
                    <li @if(request()->segment(1) == 'expenses' && empty(request()->segment(2))) class="active" @endif>
                        <a href="{{ action([\App\Http\Controllers\ExpenseController::class, 'index']) }}">
                            <i class="fa fa-list"></i> {{ __('expense.all_expenses') }}
                        </a>
                    </li>
                    <li @if(request()->segment(1) == 'expense-categories') class="active" @endif>
                        <a href="{{ action([\App\Http\Controllers\ExpenseCategoryController::class, 'index']) }}">
                            <i class="fa fa-folder-open"></i> {{ __('expense.expense_categories') }}
                        </a>
                    </li>
                    @can('expense.add')
                    <li @if(request()->segment(1) == 'expenses' && request()->segment(2) == 'create') class="active" @endif>
                        <a href="{{ action([\App\Http\Controllers\ExpenseController::class, 'create']) }}">
                            <i class="fa fa-plus"></i> {{ __('messages.add') }}
                        </a>
                    </li>
                    @endcan
                </ul>
            </div>
        </div>
    </nav>
</section>

<!-- Main content -->
<section class="content">
	{!! Form::open(['url' => action([\App\Http\Controllers\ExpenseController::class, 'store']), 'method' => 'post', 'id' => 'add_expense_form', 'files' => true ]) !!}
	<div class="box box-solid">
		<div class="box-body">
			<div class="row">

				@if(count($business_locations) == 1)
					@php 
						$default_location = current(array_keys($business_locations->toArray())) 
					@endphp
				@else
					@php $default_location = null; @endphp
				@endif
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('location_id', __('purchase.business_location').':*') !!}
						{!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes); !!}
					</div>
				</div>

				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('expense_category_id', __('expense.expense_category').':') !!}
                        <div class="input-group">
                            {!! Form::select('expense_category_id', $expense_categories, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'expense_category_id']); !!}
							<span class="input-group-btn">
								<button type="button" class="btn btn-default bg-white btn-flat btn-modal" data-container=".expense_category_modal" data-href="{{ action([\App\Http\Controllers\ExpenseCategoryController::class, 'create']) }}" title="@lang('expense.add_expense_category')">
									<i class="fa fa-plus-circle text-primary fa-lg"></i>
								</button>
							</span>
						</div>
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('invoice_ref', __('sale.invoice_no') . ' / ' . __('repair::lang.job_sheet_no') . ':') !!}
						{!! Form::select('invoice_ref', [], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'invoice_ref']); !!}
					</div>
				</div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('expense_sub_category_id', __('product.sub_category') . ':') !!}
                          {!! Form::select('expense_sub_category_id', [],  null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'id' => 'expense_sub_category_id']); !!}
                      </div>
                </div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('ref_no', __('purchase.ref_no').':') !!}
						{!! Form::text('ref_no', null, ['class' => 'form-control']); !!}
						<p class="help-block">
			                @lang('lang_v1.leave_empty_to_autogenerate')
			            </p>
					</div>
				</div>
				<div class="clearfix"></div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required', 'id' => 'expense_transaction_date']); !!}
						</div>
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('expense_for', __('expense.expense_for').':') !!} @show_tooltip(__('tooltip.expense_for'))
						{!! Form::select('expense_for', $users, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('contact_id', __('lang_v1.expense_for_contact').':') !!} 
						{!! Form::select('contact_id', $contacts, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
					</div>
				</div>
				<div class="clearfix"></div>
				<div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                        {!! Form::file('document', ['id' => 'upload_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                        <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                        @includeIf('components.document_help_text')</p></small>
                    </div>
                </div>
				<div class="col-md-4">
			    	<div class="form-group">
			            {!! Form::label('tax_id', __('product.applicable_tax') . ':' ) !!}
			            <div class="input-group">
			                <span class="input-group-addon">
			                    <i class="fa fa-info"></i>
			                </span>
			                {!! Form::select('tax_id', $taxes['tax_rates'], null, ['class' => 'form-control'], $taxes['attributes']); !!}

							<input type="hidden" name="tax_calculation_amount" id="tax_calculation_amount" 
							value="0">
			            </div>
			        </div>
			    </div>
			    <div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('final_total', __('sale.total_amount') . ':*') !!}
						{!! Form::text('final_total', null, ['class' => 'form-control input_number', 'placeholder' => __('sale.total_amount'), 'required']); !!}
					</div>
				</div>
				<div class="clearfix"></div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('additional_notes', __('expense.expense_note') . ':') !!}
								{!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]); !!}
					</div>
				</div>
				<div class="col-md-4 col-sm-6">
					<br>
					<label>
		              {!! Form::checkbox('is_refund', 1, false, ['class' => 'input-icheck', 'id' => 'is_refund']); !!} @lang('lang_v1.is_refund')?
		            </label>@show_tooltip(__('lang_v1.is_refund_help'))
				</div>
			</div>
		</div>
	</div> <!--box end-->
	@include('expense.recur_expense_form_part')
	@component('components.widget', ['class' => 'box-solid', 'id' => "payment_rows_div", 'title' => __('purchase.add_payment')])
	<div class="payment_row">
		@include('sale_pos.partials.payment_row_form', ['row_index' => 0, 'show_date' => true])
		<hr>
		<div class="row">
			<div class="col-sm-12">
				<div class="pull-right">
					<strong>@lang('purchase.payment_due'):</strong>
					<span id="payment_due">{{@num_format(0)}}</span>
				</div>
			</div>
		</div>
	</div>
	@endcomponent
	<div class="col-sm-12 text-center">
		<button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">@lang('messages.save')</button>
	</div>
{!! Form::close() !!}
</section>

<!-- Modal for adding expense category -->
<div class="modal fade expense_category_modal" tabindex="-1" role="dialog"
	aria-labelledby="gridSystemModalLabel">
</div>

@endsection
@section('javascript')
<script type="text/javascript">
	$(document).ready( function(){
		$('.paid_on').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });

        $('#invoice_ref').select2({
            ajax: {
                url: '{{ action([\App\Http\Controllers\ExpenseController::class, "searchRelatedTransactions"]) }}',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return data; },
                cache: true
            },
            minimumInputLength: 0,
            placeholder: '{{ __('messages.please_select') }}',
            allowClear: true
        });

        $('#expense_category_id').select2({
            ajax: {
                url: '{{ action([\App\Http\Controllers\ExpenseCategoryController::class, "searchParents"]) }}',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return data; },
                cache: true
            },
            minimumInputLength: 0,
            placeholder: '{{ __('messages.please_select') }}',
            allowClear: true,
            language: { noResults: function(){ return '{{ __("messages.no_data_found") }}'; } }
        });

        $(document).on('change', '#expense_category_id', function() {
            if (typeof get_expense_sub_categories === 'function') { get_expense_sub_categories(); }
        });
    });
    
    __page_leave_confirmation('#add_expense_form');
    $(document).on('change', 'input#final_total, input.payment-amount', function() {
        calculateExpensePaymentDue();
    });

	function calculateExpensePaymentDue() {
		var final_total = __read_number($('input#final_total'));
		var payment_amount = __read_number($('input.payment-amount'));
		var payment_due = final_total - payment_amount;
		$('#payment_due').text(__currency_trans_from_en(payment_due, true, false));
	}

	$(document).on('change', '#recur_interval_type', function() {
	    if ($(this).val() == 'months') {
	        $('.recur_repeat_on_div').removeClass('hide');
	    } else {
	        $('.recur_repeat_on_div').addClass('hide');
	    }
	});

	$('#is_refund').on('ifChecked', function(event){
		$('#recur_expense_div').addClass('hide');
	});
	$('#is_refund').on('ifUnchecked', function(event){
		$('#recur_expense_div').removeClass('hide');
	});

	$(document).on('change', '.payment_types_dropdown, #location_id', function(e) {
	    var default_accounts = $('select#location_id').length ? 
	                $('select#location_id')
	                .find(':selected')
	                .data('default_payment_accounts') : [];
	    var payment_types_dropdown = $('.payment_types_dropdown');
	    var payment_type = payment_types_dropdown.val();
	    if (payment_type) {
	        var default_account = default_accounts && default_accounts[payment_type]['account'] ? 
	            default_accounts[payment_type]['account'] : '';
	        var payment_row = payment_types_dropdown.closest('.payment_row');
	        var row_index = payment_row.find('.payment_row_index').val();

	        var account_dropdown = payment_row.find('select#account_' + row_index);
	        if (account_dropdown.length && default_accounts) {
	            account_dropdown.val(default_account);
	            account_dropdown.change();
	        }
	    }
	});
</script>
@endsection
