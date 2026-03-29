<div class="modal-dialog modal-xl" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      <h4 class="modal-title" id="modalTitle">
        @lang('expense.expenses') Details (<b>@lang('purchase.ref_no'):</b> #{{ $expense->ref_no }})
      </h4>
    </div>
    <div class="modal-body">
      <div class="row">
        <div class="col-sm-12">
          <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($expense->transaction_date) }}</p>
        </div>
      </div>
      <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
          <b>@lang('purchase.ref_no'):</b> #{{ $expense->ref_no }}<br>
          <b>@lang('business.location'):</b> {{ $expense->location->name }}<br>
          <b>@lang('sale.status'):</b> {{ ucfirst($expense->status) }}<br>
          <b>@lang('sale.payment_status'):</b> {{ ucfirst($expense->payment_status) }}<br>
        </div>
        <div class="col-sm-4 invoice-col">
          @if(!empty($expense->contact))
            <b>@lang('contact.contact'):</b> {{ $expense->contact->name }}<br>
            @if(!empty($expense->contact->supplier_business_name))
              <b>@lang('business.business'):</b> {{ $expense->contact->supplier_business_name }}<br>
            @endif
            @if(!empty($expense->contact->mobile))
              <b>@lang('contact.mobile'):</b> {{ $expense->contact->mobile }}<br>
            @endif
            @if(!empty($expense->contact->email))
              <b>@lang('business.email'):</b> {{ $expense->contact->email }}<br>
            @endif
          @endif
        </div>
        <div class="col-sm-4 invoice-col">
          @if(!empty($expense->expense_for))
            <b>@lang('expense.expense_for'):</b> {{ $expense->transaction_for->first_name ?? '' }} {{ $expense->transaction_for->last_name ?? '' }}<br>
          @endif
          @if(!empty($expense->expense_category) && !empty($expense->expense_category->name))
            <b>@lang('expense.expense_category'):</b> {{ $expense->expense_category->name }}<br>
          @endif
        </div>
      </div>

      <br>
      <div class="row">
        <div class="col-sm-6 col-sm-offset-6">
          <table class="table">
            <tr>
              <th>@lang('sale.total_before_tax'): </th>
              <td>
                <span class="display_currency pull-right" data-currency_symbol="true">
                  {{ $expense->total_before_tax }}
                </span>
              </td>
            </tr>
            @if(!empty($expense->tax_amount))
            <tr>
              <th>@lang('sale.tax'):</th>
              <td>
                <span class="display_currency pull-right" data-currency_symbol="true">
                  {{ $expense->tax_amount }}
                </span>
                @if(!empty($expense->tax))
                  ({{ $expense->tax->name }})
                @endif
              </td>
            </tr>
            @endif
            @if(!empty($expense->discount_amount))
            <tr>
              <th>@lang('sale.discount'):</th>
              <td>
                <span class="display_currency pull-right" data-currency_symbol="true">
                  {{ $expense->discount_amount }}
                </span>
              </td>
            </tr>
            @endif
            <tr>
              <th>@lang('sale.total'): </th>
              <td>
                <span class="display_currency pull-right" data-currency_symbol="true">
                  {{ $expense->final_total }}
                </span>
              </td>
            </tr>
          </table>
        </div>
      </div>

      @if(!empty($expense->additional_notes))
      <div class="row">
        <div class="col-sm-12">
          <strong>@lang('lang_v1.additional_notes'):</strong>
          <p class="well well-sm no-shadow bg-gray" style="border-radius: 0px;">
            {{ $expense->additional_notes }}
          </p>
        </div>
      </div>
      @endif

      <div class="row">
        <div class="col-sm-12">
          <strong>@lang('sale.payment_info'):</strong>
        </div>
        <div class="col-md-12">
          <div class="table-responsive">
            <table class="table">
              <tr class="bg-green">
                <th>#</th>
                <th>{{ __('messages.date') }}</th>
                <th>{{ __('purchase.ref_no') }}</th>
                <th>{{ __('sale.amount') }}</th>
                <th>{{ __('sale.payment_mode') }}</th>
                <th>{{ __('sale.payment_note') }}</th>
              </tr>
              @php
                $total_paid = 0;
              @endphp
              @forelse($expense->payment_lines as $payment_line)
                @php
                  $total_paid += $payment_line->amount;
                @endphp
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ @format_date($payment_line->paid_on) }}</td>
                  <td>{{ $payment_line->payment_ref_no }}</td>
                  <td>
                    <span class="display_currency" data-currency_symbol="true">
                      {{ $payment_line->amount }}
                    </span>
                  </td>
                  <td>{{ $payment_methods[$payment_line->method] ?? $payment_line->method }}</td>
                  <td>{{ $payment_line->note }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center">@lang('purchase.no_payments_found')</td>
                </tr>
              @endforelse
            </table>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-sm-6 col-sm-offset-6">
          <table class="table">
            <tr>
              <th>@lang('purchase.total_paid'): </th>
              <td>
                <span class="display_currency pull-right" data-currency_symbol="true">
                  {{ $total_paid }}
                </span>
              </td>
            </tr>
            <tr>
              <th>@lang('purchase.payment_due'): </th>
              <td>
                <span class="display_currency pull-right" data-currency_symbol="true">
                  {{ $expense->final_total - $total_paid }}
                </span>
              </td>
            </tr>
          </table>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" aria-label="Print"
      onclick="$(this).closest('div.modal-content').printThis();">
        <i class="fa fa-print"></i> @lang( 'messages.print' )
      </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">
        @lang( 'messages.close' )
      </button>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function(){
    var element = $('div.modal-xl');
    __currency_convert_recursively(element);
  });
</script>
