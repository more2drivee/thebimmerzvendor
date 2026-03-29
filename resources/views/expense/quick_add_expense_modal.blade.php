<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\App\Http\Controllers\ExpenseController::class, 'store']), 'method' => 'post', 'id' => 'quick_add_expense_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('expense.quick_add_expense')</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                @if(count($business_locations) == 1)
                    @php $default_location = current(array_keys($business_locations->toArray())); @endphp
                @else
                    @php $default_location = request()->input('location_id'); @endphp
                @endif
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('quick_expense_location_id', __('purchase.business_location').':*') !!}
                        {!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'quick_expense_location_id'], $bl_attributes); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('quick_expense_category_id', __('expense.expense_category').':') !!}
                        <div class="input-group">
                            {!! Form::select('expense_category_id', $expense_categories, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'quick_expense_category_id']); !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat btn-modal" data-container=".expense_category_modal" data-href="{{ action([\App\Http\Controllers\ExpenseCategoryController::class, 'create']) }}" title="@lang('expense.add_expense_category')">
                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('invoice_ref', __('sale.invoice_no') . ' / ' . __('repair::lang.job_sheet_no') . ':') !!}
                        {!! Form::select('invoice_ref', [], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'invoice_ref']); !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('quick_expense_sub_category_id', __('product.sub_category').':') !!}
                        {!! Form::select('expense_sub_category_id', [], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'quick_expense_sub_category_id']); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('quick_expense_transaction_date', __('messages.date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required', 'id' => 'quick_expense_transaction_date']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('quick_expense_for', __('expense.expense_for').':') !!}
                        {!! Form::select('expense_for', $users, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'quick_expense_for']); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('quick_expense_final_total', __('sale.total_amount') . ':*') !!}
                        {!! Form::text('final_total', null, ['class' => 'form-control input_number', 'placeholder' => __('sale.total_amount'), 'required', 'id' => 'quick_expense_final_total']); !!}
                    </div>
                </div>
                <!-- <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('quick_expense_tax_id', __('product.applicable_tax') . ':') !!}
                        {!! Form::select('tax_id', $taxes['tax_rates'], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'quick_expense_tax_id'], $taxes['attributes']); !!}
                    </div>
                </div> -->
                <div class="clearfix"></div>
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('quick_expense_notes', __('expense.expense_note') . ':') !!}
                        {!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3, 'id' => 'quick_expense_notes']); !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
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
    });
</script>
