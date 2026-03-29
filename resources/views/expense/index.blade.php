@extends('layouts.app')
@section('title', __('expense.expenses'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('expense.expenses')</h1>
</section>

<!-- Main content -->
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
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                @if(auth()->user()->can('all_expense.access'))
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('expense_for', __('expense.expense_for').':') !!}
                            {!! Form::select('expense_for', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('expense_contact_filter',  __('contact.contact') . ':') !!}
                            {!! Form::select('expense_contact_filter', $contacts, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                @endif
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('expense_category_id',__('expense.expense_category').':') !!}
                        <div class="input-group">
                            {!! Form::select('expense_category_id', $categories, null, ['placeholder' => __('report.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'expense_category_id']); !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat btn-modal" data-container=".expense_category_modal" data-href="{{ action([\App\Http\Controllers\ExpenseCategoryController::class, 'create']) }}" title="@lang('expense.add_expense_category')">
                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('expense_sub_category_id_filter',__('product.sub_category').':') !!}
                        {!! Form::select('expense_sub_category_id_filter', $sub_categories, null, ['placeholder' =>
                        __('report.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'expense_sub_category_id_filter']); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('expense_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'expense_date_range', 'readonly']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('expense_payment_status',  __('purchase.payment_status') . ':') !!}
                        {!! Form::select('expense_payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' => __('lang_v1.partial')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('expense.all_expenses')])
                @can('expense.add')
                    @slot('tool')
                        <div class="box-tools tw-flex tw-gap-2">
                            <button type="button"
                                class="tw-dw-btn tw-bg-gradient-to-r tw-from-emerald-500 tw-to-teal-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full quick_add_expense_btn btn-modal"
                                data-href="{{ action([\App\Http\Controllers\ExpenseController::class, 'create']) }}?quick_add=1"
                                data-container=".quick_add_expense_modal" data-toggle="tooltip"
                                title="@lang('expense.quick_add_expense')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-flash">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M7 2l10 0l-2 10h4l-10 12l2 -10h-4z" />
                                </svg> @lang('expense.quick_add')
                            </button>
                            <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full"
                                href="{{action([\App\Http\Controllers\ExpenseController::class, 'create'])}}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg> @lang('messages.add')
                            </a>
                        </div>
                    @endslot
                @endcan
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="expense_table">
                        <thead>
                            <tr>
                                <th>@lang('messages.action')</th>
                                <th>@lang('messages.date')</th>
                                <th>@lang('purchase.ref_no')</th>
                                <th>@lang('lang_v1.recur_details')</th>
                                <th>@lang('expense.expense_category')</th>
                                <th>@lang('product.sub_category')</th>
                                <th>@lang('business.location')</th>
                                <th>@lang('sale.payment_status')</th>
                                <th>@lang('product.tax')</th>
                                <th>@lang('sale.total_amount')</th>
                                <th>@lang('purchase.payment_due')
                                <th>@lang('expense.expense_for')</th>
                                <th>@lang('contact.contact')</th>
                                <th>Job Sheet</th>
                                <th>@lang('expense.expense_note')</th>
                                <th>@lang('lang_v1.added_by')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 text-center footer-total">
                                <td colspan="7"><strong>@lang('sale.total'):</strong></td>
                                <td class="footer_payment_status_count"></td>
                                <td></td>
                                <td class="footer_expense_total"></td>
                                <td class="footer_total_due"></td>
                                <td colspan="4"></td>
                            </tr>
                            <tr class="bg-gray font-14 text-center">
                                <td colspan="15" class="footer_payment_method_totals"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->
<!-- /.content -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade contains_select2 quick_add_expense_modal" tabindex="-1" role="dialog"
    aria-labelledby="quickAddExpenseLabel">
</div>

<div class="modal fade expense_category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@stop
@section('javascript')
 <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
 <script>
    (function($){
        'use strict';

        const quickExpensePlaceholder = "{{ addslashes(__('messages.please_select')) }}";
        const searchParentsUrl = "{{ addslashes(action([\App\Http\Controllers\ExpenseCategoryController::class, 'searchParents'])) }}";
        const getSubCategoriesUrl = "{{ addslashes(action([\App\Http\Controllers\ExpenseCategoryController::class, 'getSubCategories'])) }}";
        const searchRelatedTransactionsUrl = "{{ addslashes(action([\App\Http\Controllers\ExpenseController::class, 'searchRelatedTransactions'])) }}";

        function initQuickExpenseModal($modal) {
            const $form = $modal.find('#quick_add_expense_form');
            if (!$form.length) {
                return;
            }

            const $category = $form.find('#quick_expense_category_id');
            const $subCategory = $form.find('#quick_expense_sub_category_id');
            const $invoiceRef = $form.find('#invoice_ref');

            if ($category.length) {
                if ($category.data('select2')) {
                    $category.select2('destroy');
                }

                $category.select2({
                    dropdownParent: $modal,
                    placeholder: quickExpensePlaceholder,
                    allowClear: true,
                    ajax: {
                        url: searchParentsUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { q: params.term };
                        },
                        processResults: function(data) {
                            return data;
                        },
                        cache: true
                    }
                });
            }

            if ($subCategory.length) {
                if ($subCategory.data('select2')) {
                    $subCategory.select2('destroy');
                }

                $subCategory.select2({
                    dropdownParent: $modal,
                    placeholder: quickExpensePlaceholder,
                    allowClear: true
                });
            }

            if ($invoiceRef.length) {
                if ($invoiceRef.data('select2')) {
                    $invoiceRef.select2('destroy');
                }

                $invoiceRef.select2({
                    dropdownParent: $modal,
                    ajax: {
                        url: searchRelatedTransactionsUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { q: params.term };
                        },
                        processResults: function(data) {
                            return data;
                        },
                        cache: true
                    },
                    minimumInputLength: 0,
                    placeholder: quickExpensePlaceholder,
                    allowClear: true
                });
            }

            $modal.off('change.quickExpenseCategory', '#quick_expense_category_id')
                .on('change.quickExpenseCategory', '#quick_expense_category_id', function () {
                    const categoryId = $(this).val();

                    if (!categoryId) {
                        $subCategory.html('<option value="">' + quickExpensePlaceholder + '</option>');
                        $subCategory.val(null).trigger('change');
                        return;
                    }

                    $.ajax({
                        method: 'POST',
                        url: getSubCategoriesUrl,
                        dataType: 'html',
                        data: { cat_id: categoryId },
                        success: function(result) {
                            const options = result && result.trim() !== ''
                                ? result
                                : '<option value="">' + quickExpensePlaceholder + '</option>';
                            $subCategory.html(options);
                            $subCategory.val(null).trigger('change');
                        }
                    });
                });
        }

        $(document).on('shown.bs.modal', '.quick_add_expense_modal', function () {
            initQuickExpenseModal($(this));
        });

    })(jQuery);
 </script>
@endsection
