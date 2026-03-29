<!-- filepath: d:\pos-main\pos\Modules\Repair\Resources\views\repair\show.blade.php -->
<div class="modal-dialog modal-xl no-print" role="document" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      <h4 class="modal-title" id="modalTitle">
        @lang('repair::lang.repair_details') (<b>@lang('sale.invoice_no'):</b> {{ $sell->invoice_no }})
      </h4>
    </div>
    <div class="modal-body">
      <div class="row">
        <div class="col-xs-12">
          <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($sell->transaction_date) }}</p>
        </div>
      </div>

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
  <div class="col-sm-4">
      <b>{{ __('sale.invoice_no') }}:</b> #{{ $sell->invoice_no ?? ' ' }}
      @if(!empty($sell->return_parent))
          <small class="label bg-red label-round" title="{{ __('repair::lang.has_sell_return') }}"><i class="fas fa-undo"></i> {{ __('repair::lang.sell_return') }}</small>
      @endif
      <br><br>
      <b>{{ __('repair::lang.jobsheet_number') }}:</b> #{{ $sell->job_sheet_no ?? ' ' }}<br>
      <b>{{ __('repair::lang.jobsheet_status') }}:</b>
      <span class="label" style="background-color: {{ $sell->repair_status_color ?? 'transparent' }};">
          {{ $sell->repair_status ?? ' ' }}
      </span>
      <br><br>
      <b>{{ __('repair::lang.invoice_status') }}:</b>
      <span class="badge2"
      style="
      @if($sell->status === 'under processing') background-color: #f39c12 !important;
      @elseif($sell->status === 'final') background-color: #dc3545 !important;
      @else background-color: #6c757d !important;
      @endif">
          {{ $sell->status === 'under processing' ? 'قيد العمل' : ($sell->status === 'final' ? 'منتهي' : ' ') }}
      </span><br>

      <b>{{ __('sale.payment_status') }}:</b>
      <span class="badge2"
      style="
      @if($sell->payment_status === 'paid') background-color: #28a745 !important;
      @elseif($sell->payment_status === 'due') background-color: #f39c12 !important;
      @elseif($sell->payment_status === 'partial') background-color: #ffc107 !important;
      @else background-color: #6c757d !important;
      @endif">
          {{ $sell->payment_status === 'paid' ? 'مدفوع' : ($sell->payment_status === 'due' ? 'مستحق' : ($sell->payment_status === 'partial' ? 'مدفوع جزئياً' : ' ')) }}
      </span>

  </div>
  <div class="col-sm-4">
      <b>{{ __('sale.customer_name') }}:</b> {{ $sell->contact->name ?? ' ' }}<br>
      <b>{{ __('contact.mobile') }}:</b> {{ $sell->contact->mobile ?? ' ' }}<br>
  </div>
  <div class="col-sm-4">
      <strong>@lang('repair::lang.device'): </strong> {{ $sell->brand ?? ' ' }}<br>
      <strong>@lang('repair::lang.model'): </strong> {{ $sell->repair_model ?? ' ' }}<br>

      <strong>@lang('repair::lang.manufacturing_year'):</strong> {{ $sell->manufacturing_year ?? ' ' }}<br>
      <strong>@lang('repair::lang.vin_number'):</strong> {{ $sell->chassis_number ?? ' ' }}<br>
      <strong>@lang('repair::lang.plate_number'):</strong> {{ $sell->plate_number ?? ' ' }}<br>
      <strong>@lang('repair::lang.color'):</strong> {{ $sell->color ?? ' ' }}<br>
      <strong>@lang('repair::lang.car_type'):</strong> {{ $sell->car_type ?? ' ' }}<br>
      <strong>@lang('repair::lang.device_km'):</strong> {{ $sell->km ?? ' ' }}<br>
      @if(!empty($sell->motor_cc))
      <strong>@lang('car.motor_cc'):</strong> {{ $sell->motor_cc }}<br>
      @endif

      @if(!empty($warranty_expires_in))
          <strong>@lang('repair::lang.warranty'): </strong> {{ $sell->warranty_name ?? ' ' }}
          <small class="help-block">( @lang('repair::lang.expires_in') {{ $warranty_expires_in ?? ' ' }} )</small>
      @endif
  </div>
</div>

      <br>
      <div class="row">
        <div class="col-sm-12 col-xs-12">
          <h4>{{ __('repair::lang.products') }}:</h4>
        </div>
        <div class="col-sm-12 col-xs-12">
          <div class="table-responsive">
            @include('sale_pos.partials.sale_line_details')
          </div>
        </div>
      </div>

      <div class="row">

        <div class="clearfix"></div>

        <div class="col-sm-12">
            <div class="box box-solid">
                <div class="box-header with-border" style="cursor: pointer;">
                    <h3 class="box-title">{{ __('repair::lang.payment_info') }}:</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tr class="bg-gray">
                                <th>#</th>
                                <th>{{ __('repair::lang.payment_date') }}</th>
                                <th>{{ __('repair::lang.reference_number') }}</th>
                                <th>{{ __('repair::lang.amount') }}</th>
                                <th>{{ __('repair::lang.payment_method') }}</th>
                                <th>{{ __('repair::lang.payment_note') }}</th>
                            </tr>
                            @php
                              $total_paid = 0;

                            @endphp
                            @foreach($sell->payment_lines as $payment_line)
                                @php
                                  if($payment_line->is_return == 1){
                                    $total_paid -= $payment_line->amount;
                                  } else {
                                    $total_paid += $payment_line->amount;
                                  }
                                @endphp
                                <tr>
                                  <td>{{ $loop->iteration }}</td>
                                  <td>{{ @format_date($payment_line->paid_on) }}</td>
                                  <td>{{ $payment_line->payment_ref_no }}</td>
                                  <td><span class="display_currency" data-currency_symbol="true">{{ $payment_line->amount }}</span></td>
                                  <td>
                                    {{ $payment_types[$payment_line->method]}}
                                    @if($payment_line->is_return == 1)
                                      <br/>
                                      ( {{ __('lang_v1.change_return') }} )
                                    @endif
                                  </td>
                                  <td>@if($payment_line->note)
                                    {{ ucfirst($payment_line->note) }}
                                    @else
                                    --
                                    @endif
                                  </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            @php
                                // Calculate total before discount: invoice total + discount amount
                                $invoice_total = (float) ($sell->final_total ?? 0);

                                // Transaction discount: convert percentage to fixed
                                $transaction_discount = (float) ($sell->discount_amount ?? 0);
                                $transaction_discount_amount = 0;
                                if ($transaction_discount > 0) {
                                    if (($sell->discount_type ?? 'fixed') === 'percentage') {
                                        $transaction_discount_amount = ($transaction_discount / 100) * (float) ($sell->total_before_tax ?? 0);
                                    } else {
                                        $transaction_discount_amount = $transaction_discount;
                                    }
                                }

                                // Line discounts: convert percentage to fixed; fixed is per-unit unless fixed_line_discount provided
                                $line_discount = 0;
                                foreach ($sell->sell_lines as $line) {
                                    $quantity = $line->quantity ?? 1;
                                    $disc_value = (float) ($line->fixed_line_discount ?? $line->line_discount_amount ?? $line->discount_amount ?? 0);
                                    $disc_type = $line->line_discount_type ?? $line->discount_type ?? 'fixed';

                                    if ($disc_value > 0) {
                                        if ($disc_type === 'percentage') {
                                            $unit_base = $line->unit_price_before_discount ?? $line->unit_price ?? 0;
                                            $line_discount += ($disc_value / 100) * $unit_base * $quantity;
                                        } else {
                                            // fixed stored as per-unit in this context unless fixed_line_discount was provided
                                            if (!is_null($line->fixed_line_discount ?? null)) {
                                                $line_discount += $disc_value;
                                            } else {
                                                $line_discount += $disc_value * $quantity;
                                            }
                                        }
                                    }
                                }

                                $discount_total = $transaction_discount_amount + $line_discount;
                                $total_before_discount = $invoice_total + $discount_total;
                                $subtotal_after_discount = $invoice_total;
                                $total_tax = !empty($order_taxes) ? array_sum((array) $order_taxes) : 0;
                            @endphp
                            <tr>
                                <th>{{ __('repair::lang.subtotal') }} ({{ __('sale.before_discount') }}): </th>
                                <td></td>
                                <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_before_discount }}</span></td>
                            </tr>
                            <tr>
                                <th>{{ __('repair::lang.discount') }}:</th>
                                <td><b>(-)</b></td>
                                <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $discount_total }}</span></td>
                            </tr>
                         
                            @if(session('business.enable_rp') == 1 && !empty($sell->rp_redeemed) )
                                <tr>
                                  <th>{{session('business.rp_name')}}:</th>
                                  <td><b>(-)</b></td>
                                  <td> <span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->rp_redeemed_amount }}</span></td>
                                </tr>
                            @endif
                            <tr>
                                <th>{{ __('repair::lang.tax') }}:</th>
                                <td><b>(+)</b></td>
                                <td class="text-right">
                                  @if(!empty($order_taxes))
                                    @foreach($order_taxes as $k => $v)
                                      <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
                                    @endforeach
                                  @else
                                    0.00
                                  @endif
                                </td>
                            </tr>
                            {{-- <tr>
                                <th>{{ __('repair::lang.shipping') }}: @if($sell->shipping_details)({{$sell->shipping_details}}) @endif</th>
                                <td><b>(+)</b></td>
                                <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->shipping_charges }}</span></td>
                            </tr> --}}
                            <tr>
                                <th>{{ __('repair::lang.total') }}: </th>
                                <td></td>
                                <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->final_total }}</span></td>
                            </tr>
                            @if(!empty($sell->return_parent))
                            @php
                                $sell_return_amount = (float) ($sell->return_parent->final_total ?? 0);
                            @endphp
                            <tr class="text-danger">
                                <th><i class="fas fa-undo"></i> {{ __('repair::lang.sell_return') }}: </th>
                                <td><b>(-)</b></td>
                                <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell_return_amount }}</span></td>
                            </tr>
                            <tr>
                                <th>{{ __('repair::lang.net_after_return') }}: </th>
                                <td></td>
                                <td><span class="display_currency pull-right" data-currency_symbol="true"><strong>{{ $sell->final_total - $sell_return_amount }}</strong></span></td>
                            </tr>
                            @endif
                            @php
                                $effective_total = !empty($sell->return_parent) ? ($sell->final_total - ($sell->return_parent->final_total ?? 0)) : $sell->final_total;
                            @endphp
                            <tr>
                                <th>{{ __('repair::lang.paid') }}:</th>
                                <td></td>
                                <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $total_paid }}</span></td>
                            </tr>
                            <tr>
                                <th>{{ __('repair::lang.due') }}:</th>
                                <td></td>
                                <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $effective_total - $total_paid }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
      </div>
      @if(!empty($activities))
        <div class="row">
          <div class="col-sm-12">
            <strong>{{ __('lang_v1.activities') }}:</strong><br>
            @includeIf('activity_log.activities', ['activity_type' => 'sell'])
          </div>
        </div>
      @endif
    </div>
    <div class="modal-footer">
      <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-primary tw-text-white" data-href="{{route('repair.customerCopy', [$sell->id])}}">
        <i class="fa fa-print" aria-hidden="true"></i>
        @lang("repair::lang.print_customer_copy")
      </a>
      <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-primary tw-text-white" data-href="{{route('sell.printInvoice', [$sell->id])}}">
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

    @if(!empty($sell->repair_security_pattern))
      var security_pattern =  new PatternLock("#security_pattern_container", {
        enableSetPattern: true
      });
      security_pattern.setPattern("{{$sell->repair_security_pattern}}");
    @endif
  });
</script>
