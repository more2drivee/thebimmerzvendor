<div class="modal-dialog modal-xl no-print" role="document" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      <h4 class="modal-title" id="modalTitle">
        @lang('treasury::lang.treasury_details') (<b>@lang('sale.invoice_no'):</b> {{ $transaction->invoice_no }})
      </h4>
    </div>
    <div class="modal-body">
      @if(!empty($repair))
      <style>
        .badge2 {
          display: inline-block;
          min-width: 10px;
          padding: 5px 7px;
          font-size: 12px;
          font-weight: 700;
          line-height: 1;
          color: #fff;
          text-align: center;
          white-space: nowrap;
          vertical-align: middle;
          border-radius: 4px;
        }

        [dir="rtl"] .pull-right {
          float: left !important;
        }

        [dir="rtl"] .pull-left {
          float: right !important;
        }

        [dir="rtl"] .text-right {
          text-align: left !important;
        }

        [dir="rtl"] .text-left {
          text-align: right !important;
        }

        [dir="rtl"] th, [dir="rtl"] td {
          text-align: right;
        }
      </style>

      <div class="row">
        <div class="col-xs-12">
          <div class="tw-p-4 tw-mb-4 tw-bg-gray-50 tw-rounded-lg tw-shadow-sm">
            <div class="row">
              <div class="col-sm-4">
                <b>{{ __('sale.invoice_no') }}:</b> #{{ $transaction->invoice_no ?? '' }}<br><br>
                <b>{{ __('repair::lang.jobsheet_number') }}:</b> #{{ $repair->job_sheet_no ?? '' }}<br>
                <b>{{ __('repair::lang.jobsheet_status') }}:</b>
                <span class="label" style="background-color: {{ $repair->status->color ?? 'transparent' }};">
                    {{ $repair->status->name ?? '' }}
                </span>
                <br><br>
                <b>{{ __('repair::lang.invoice_status') }}:</b>
                <span class="badge2"
                style="
                @if($transaction->status === 'under processing') background-color: #f39c12 !important;
                @elseif($transaction->status === 'final') background-color: #dc3545 !important;
                @else background-color: #6c757d !important;
                @endif">
                    {{ $transaction->status === 'under processing' ? 'قيد العمل' : ($transaction->status === 'final' ? 'منتهي' : ' ') }}
                </span><br>

                <b>{{ __('sale.payment_status') }}:</b>
                <span class="badge2"
                style="
                @if($transaction->payment_status === 'paid') background-color: #28a745 !important;
                @elseif($transaction->payment_status === 'due') background-color: #f39c12 !important;
                @elseif($transaction->payment_status === 'partial') background-color: #ffc107 !important;
                @else background-color: #6c757d !important;
                @endif">
                    {{ $transaction->payment_status === 'paid' ? 'مدفوع' : ($transaction->payment_status === 'due' ? 'مستحق' : ($transaction->payment_status === 'partial' ? 'مدفوع جزئياً' : ' ')) }}
                </span>
              </div>
              <div class="col-sm-4">
                <b>{{ __('sale.customer_name') }}:</b> {{ $repair->customer->name ?? '' }}<br>
                <b>{{ __('contact.mobile') }}:</b> {{ $repair->customer->mobile ?? '' }}<br>
              </div>
              <div class="col-sm-4">
                <strong>@lang('repair::lang.device'): </strong> {{ $repair->brand_name ?? $repair->Brand->name ?? '' }}<br>
                <strong>@lang('repair::lang.model'): </strong> {{ $repair->model_name ?? $repair->deviceModel->name ?? '' }}<br>

                <strong>@lang('repair::lang.manufacturing_year'):</strong> {{ $repair->manufacturing_year ?? '' }}<br>
                <strong>@lang('repair::lang.vin_number'):</strong> {{ $repair->chassis_number ?? '' }}<br>
                <strong>@lang('repair::lang.plate_number'):</strong> {{ $repair->plate_number ?? '' }}<br>
                <strong>@lang('repair::lang.color'):</strong> {{ $repair->color ?? '' }}<br>
                <strong>@lang('repair::lang.car_type'):</strong> {{ $repair->car_type ?? '' }}<br>
              </div>
            </div>
          </div>
        </div>
      </div>
      @endif

      @if(empty($repair) && ($transaction->sub_type !== 'repair' || empty($transaction->sub_type)))
      <style>
        .badge2 {
          display: inline-block;
          min-width: 10px;
          padding: 5px 7px;
          font-size: 12px;
          font-weight: 700;
          line-height: 1;
          color: #fff;
          text-align: center;
          white-space: nowrap;
          vertical-align: middle;
          border-radius: 4px;
        }

        [dir="rtl"] .pull-right {
          float: left !important;
        }

        [dir="rtl"] .pull-left {
          float: right !important;
        }

        [dir="rtl"] .text-right {
          text-align: left !important;
        }

        [dir="rtl"] .text-left {
          text-align: right !important;
        }

        [dir="rtl"] th, [dir="rtl"] td {
          text-align: right;
        }
      </style>

      <div class="row">
        <div class="col-xs-12">
          <div class="tw-p-4 tw-mb-4 tw-bg-gray-50 tw-rounded-lg tw-shadow-sm">
            <div class="row">
              <div class="col-sm-6">
                <b>{{ __('sale.invoice_no') }}:</b> #{{ $transaction->ref_no ?? $transaction->invoice_no ?? '' }}<br><br>

                <b>{{ __('repair::lang.invoice_status') }}:</b>
                <span class="badge2"
                style="
                @if($transaction->status === 'under processing') background-color: #f39c12 !important;
                @elseif($transaction->status === 'final') background-color: #dc3545 !important;
                @elseif($transaction->status === 'received') background-color: #28a745 !important;
                @elseif($transaction->status === 'pending') background-color: #ffc107 !important;
                @elseif($transaction->status === 'ordered') background-color: #17a2b8 !important;
                @elseif($transaction->status === 'draft') background-color: #6c757d !important;
                @else background-color: #6c757d !important;
                @endif">
                    @if($transaction->status === 'under processing')
                        قيد العمل
                    @elseif($transaction->status === 'final')
                        منتهي
                    @elseif($transaction->status === 'received')
                        مستلم
                    @elseif($transaction->status === 'pending')
                        معلق
                    @elseif($transaction->status === 'ordered')
                        مطلوب
                    @elseif($transaction->status === 'draft')
                        مسودة
                    @else
                        {{ ucfirst($transaction->status ?? 'غير محدد') }}
                    @endif
                </span><br><br>

                <b>{{ __('sale.payment_status') }}:</b>
                <span class="badge2"
                style="
                @if($transaction->payment_status === 'paid') background-color: #28a745 !important;
                @elseif($transaction->payment_status === 'due') background-color: #f39c12 !important;
                @elseif($transaction->payment_status === 'partial') background-color: #ffc107 !important;
                @else background-color: #6c757d !important;
                @endif">
                    {{ $transaction->payment_status === 'paid' ? 'مدفوع' : ($transaction->payment_status === 'due' ? 'مستحق' : ($transaction->payment_status === 'partial' ? 'مدفوع جزئياً' : ' ')) }}
                </span>
              </div>
              <div class="col-sm-6">
                <b>{{ __('sale.customer_name') }}:</b> {{ $transaction->contact->name ?? '' }}<br>
                <b>{{ __('contact.mobile') }}:</b> {{ $transaction->contact->mobile ?? '' }}<br>
              </div>
            </div>
          </div>
        </div>
      </div>
      @endif

      <div class="row">
        <div class="col-xs-12">
          <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($transaction->transaction_date) }}</p>
        </div>
      </div>

      <br>
      <div class="row">
        <div class="col-xs-12">
          <div class="table-responsive">
            <table class="table bg-gray">
              <thead>
                <tr class="bg-green">
                  <th>#</th>
                  <th>{{ __('sale.product') }}</th>
                  <th>{{ __('sale.qty') }}</th>
                  <th>{{ __('sale.unit_price') }}</th>
                  <th>{{ __('sale.discount') }}</th>
                  <th>{{ __('sale.tax') }}</th>
                  <th>{{ __('sale.price_inc_tax') }}</th>
                  <th>{{ __('sale.subtotal') }}</th>
                </tr>
              </thead>
              <tbody>
                @if(isset($transaction->sell_lines) && !empty($transaction->sell_lines))
                  @foreach($transaction->sell_lines as $key => $sell_line)
                    <tr>
                      <td>{{ $loop->iteration }}</td>
                      <td>
                        {{ $sell_line->product->name }}
                        @if($sell_line->product->type == 'variable')
                          - {{ $sell_line->variations->product_variation->name ?? '' }}
                          - {{ $sell_line->variations->name ?? '' }}
                        @endif
                      </td>
                      <td>{{ $sell_line->quantity }}</td>
                      <td>
                        <span class="display_currency" data-currency_symbol="true">
                          {{ $sell_line->unit_price }}
                        </span>
                      </td>
                      <td>
                        <span class="display_currency" data-currency_symbol="true">
                          {{ $sell_line->line_discount_amount }}
                        </span>
                        @if($sell_line->line_discount_type == 'percentage')
                          ({{ $sell_line->line_discount_amount }} %)
                        @endif
                      </td>
                      <td>
                        <span class="display_currency" data-currency_symbol="true">
                          {{ $sell_line->item_tax }}
                        </span>
                      </td>
                      <td>
                        <span class="display_currency" data-currency_symbol="true">
                          {{ $sell_line->unit_price_inc_tax }}
                        </span>
                      </td>
                      <td>
                        <span class="display_currency" data-currency_symbol="true">
                          {{ $sell_line->quantity * $sell_line->unit_price_inc_tax }}
                        </span>
                      </td>
                    </tr>
                  @endforeach
                @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-6 col-sm-offset-6">
          <table class="table">
            <tr>
              <th>{{ __('sale.total') }}: </th>
              <td>
                <span class="display_currency pull-right" data-currency_symbol="true">
                  {{ $transaction->final_total }}
                </span>
              </td>
            </tr>
            <tr>
              <th>{{ __('sale.discount') }}:</th>
              <td>
                <span class="display_currency pull-right" data-currency_symbol="true">
                  {{ $transaction->discount_amount }}
                </span>
                @if($transaction->discount_type == 'percentage')
                  ({{ $transaction->discount_amount }} %)
                @endif
              </td>
            </tr>
            <tr>
              <th>{{ __('sale.tax') }}:</th>
              <td>
                <span class="display_currency pull-right" data-currency_symbol="true">
                  {{ $transaction->tax_amount }}
                </span>
              </td>
            </tr>
            <tr>
              <th>{{ __('sale.payment_due') }}: </th>
              <td>
                <span class="display_currency pull-right" data-currency_symbol="true">
                  {{ $transaction->final_total - $transaction->payment_lines->sum('amount') }}
                </span>
              </td>
            </tr>
          </table>
        </div>
      </div>
      <div class="row">
        <div class="col-xs-12">
          <strong>{{ __('sale.payment_info') }}:</strong>
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
              @foreach($transaction->payment_lines as $payment_line)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ @format_date($payment_line->paid_on) }}</td>
                  <td>{{ $payment_line->payment_ref_no }}</td>
                  <td>
                    <span class="display_currency" data-currency_symbol="true">
                      {{ $payment_line->amount }}
                    </span>
                  </td>
                  <td>
                    {{ $payment_line->method }}
                  </td>
                  <td>
                    {{ $payment_line->note }}
                  </td>
                </tr>
              @endforeach
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <!-- <a href="{{ route('treasury.transaction.print', [$transaction->id]) }}" class="tw-dw-btn tw-dw-btn-primary tw-text-white" target="_blank"> -->
      <a href="{{ route('sell.printCleanInvoice', ['transaction_id' => $transaction->id]) }}" class="tw-dw-btn tw-dw-btn-primary tw-text-white" target="_blank">
        <i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")
      </a>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function(){
    var element = $('div.modal-xl');
    __currency_convert_recursively(element);
  });
</script>