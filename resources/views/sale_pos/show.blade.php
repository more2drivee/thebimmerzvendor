<div class="modal-dialog modal-xl no-print" role="document">
  <div class="modal-content">
    <div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modalTitle"> @lang('sale.sell_details') (<b>@if($sell->type == 'sales_order') @lang('restaurant.order_no') @else @lang('sale.invoice_no') @endif :</b> {{ $sell->invoice_no }})
    </h4>
</div>
<div class="modal-body">
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
    </style>

    @if(!empty($sell->return_parent))
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-triangle"></i>
      <strong>@lang('lang_v1.sell_return')</strong> -
      @lang('lang_v1.sell_return_amount'): <span class="display_currency" data-currency_symbol="true">{{ $sell->return_parent->final_total }}</span>
    </div>
    @endif

    @if($sell->status == 'draft')
    <div class="alert alert-warning">
      <i class="fas fa-pencil-alt"></i>
      <strong>@lang('sale.draft')</strong>
    </div>
    @endif

    <div class="row">
      <div class="col-xs-12">
          <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($sell->transaction_date) }}</p>
      </div>
    </div>

    @if(!empty($repair))
    <div class="row">
      <div class="col-xs-12">
        <div class="tw-p-4 tw-mb-4 tw-bg-gray-50 tw-rounded-lg tw-shadow-sm">
          <div class="row">
            <div class="col-sm-4">
              <b>{{ __('sale.invoice_no') }}:</b> #{{ $sell->invoice_no ?? '' }}<br><br>
              <b>{{ __('repair::lang.jobsheet_number') }}:</b> #{{ $repair->job_sheet_no ?? '' }}<br>
              <b>{{ __('repair::lang.jobsheet_status') }}:</b>
              <span class="label" style="background-color: {{ $repair->status_color ?? 'transparent' }};">
                  {{ $repair->status_name ?? '' }}
              </span>
              <br><br>
              <b>{{ __('repair::lang.invoice_status') }}:</b>
              <span class="badge2"
              style="
              @if($sell->status === 'under processing') background-color: #f39c12 !important;
              @elseif($sell->status === 'final') background-color: #dc3545 !important;
              @else background-color: #6c757d !important;
              @endif">
                  {{ $sell->status === 'under processing' ? 'قيد العمل' : ($sell->status === 'final' ? 'منتهي' : ($statuses[$sell->status] ?? __('sale.' . $sell->status))) }}
              </span><br>

              <b>{{ __('sale.payment_status') }}:</b>
              <span class="badge2"
              style="
              @if($sell->payment_status === 'paid') background-color: #28a745 !important;
              @elseif($sell->payment_status === 'due') background-color: #f39c12 !important;
              @elseif($sell->payment_status === 'partial') background-color: #ffc107 !important;
              @else background-color: #6c757d !important;
              @endif">
                  {{ $sell->payment_status === 'paid' ? 'مدفوع' : ($sell->payment_status === 'due' ? 'مستحق' : ($sell->payment_status === 'partial' ? 'مدفوع جزئياً' : '')) }}
              </span>
            </div>
            <div class="col-sm-4">
              <b>{{ __('sale.customer_name') }}:</b> {{ $sell->contact->name ?? '' }}<br>
              <b>{{ __('contact.mobile') }}:</b> {{ $sell->contact->mobile ?? '' }}<br>
            </div>
            <div class="col-sm-4">
              <strong>@lang('repair::lang.device'): </strong> {{ $repair->brand_name ?? '' }}<br>
              <strong>@lang('repair::lang.model'): </strong> {{ $repair->model_name ?? '' }}<br>

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
    @else
    <div class="row">
      @php
        $custom_labels = json_decode(session('business.custom_labels'), true);
        $export_custom_fields = [];
        if (!empty($sell->is_export) && !empty($sell->export_custom_fields_info)) {
            $export_custom_fields = $sell->export_custom_fields_info;
        }
      @endphp
      <div class="@if(!empty($export_custom_fields)) col-sm-3 @else col-sm-4 @endif">
        <b>@if($sell->type == 'sales_order') {{ __('restaurant.order_no') }} @else {{ __('sale.invoice_no') }} @endif:</b> #{{ $sell->invoice_no }}<br>
        <b>{{ __('sale.status') }}:</b>
          @if($sell->status == 'draft' && $sell->is_quotation == 1)
            {{ __('lang_v1.quotation') }}
          @else
            {{ $statuses[$sell->status] ?? __('sale.' . $sell->status) }}
          @endif
        <br>
        @if($sell->type != 'sales_order')
          <b>{{ __('sale.payment_status') }}:</b> @if(!empty($sell->payment_status)){{ __('lang_v1.' . $sell->payment_status) }}
          @endif
        @endif
      </div>
      <div class="@if(!empty($export_custom_fields)) col-sm-3 @else col-sm-4 @endif">
        @if(!empty($sell->contact->supplier_business_name))
          {{ $sell->contact->supplier_business_name }}<br>
        @endif
        <b>{{ __('sale.customer_name') }}:</b> {{ $sell->contact->name }}<br>
        <b>{{ __('business.address') }}:</b><br>
        @if(!empty($sell->billing_address()))
          {{$sell->billing_address()}}
        @else
          {!! $sell->contact->contact_address !!}
          @if($sell->contact->mobile)
          <br>
              {{__('contact.mobile')}}: {{ $sell->contact->mobile }}
          @endif
        @endif
      </div>
    </div>
    @endif
    <br>
    <div class="row">
      <div class="col-sm-12 col-xs-12">
        <h4>{{ __('sale.products') }}:</h4>
      </div>

      <div class="col-sm-12 col-xs-12">
        <div class="table-responsive">
          @include('sale_pos.partials.sale_line_details')
        </div>
      </div>
    </div>
    <div class="row">
      @php
        $total_paid = 0;
      @endphp
      @if($sell->type != 'sales_order')
      <div class="col-sm-12 col-xs-12">
        <h4>{{ __('sale.payment_info') }}:</h4>
      </div>
      <div class="col-md-6 col-sm-12 col-xs-12">
        <div class="table-responsive">
          <table class="table bg-gray">
            <tr class="bg-green">
              <th>#</th>
              <th>{{ __('messages.date') }}</th>
              <th>{{ __('purchase.ref_no') }}</th>
              <th>{{ __('sale.amount') }}</th>
              <th>{{ __('sale.payment_mode') }}</th>
              <th>{{ __('sale.payment_note') }}</th>
            </tr>
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
                  {{ $payment_types[$payment_line->method] ?? $payment_line->method }}
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
      </div>
      @endif
      <div class="col-md-6 col-sm-12 col-xs-12 @if($sell->type == 'sales_order') col-md-offset-6 @endif">
        <div class="table-responsive">
          <table class="table bg-gray">
            <tr>
              <th>{{ __('sale.total') }}: </th>
              <td></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->total_before_tax }}</span></td>
            </tr>
            <tr>
              <th>{{ __('sale.discount') }}:</th>
              <td><b>(-)</b></td>
              <td><div class="pull-right"><span class="display_currency" @if( $sell->discount_type == 'fixed') data-currency_symbol="true" @endif>{{ $sell->discount_amount }}</span> @if( $sell->discount_type == 'percentage') {{ '%'}} @endif</span></div></td>
            </tr>
            @if(in_array('types_of_service' ,$enabled_modules) && !empty($sell->packing_charge))
              <tr>
                <th>{{ __('lang_v1.packing_charge') }}:</th>
                <td><b>(+)</b></td>
                <td><div class="pull-right"><span class="display_currency" @if( $sell->packing_charge_type == 'fixed') data-currency_symbol="true" @endif>{{ $sell->packing_charge }}</span> @if( $sell->packing_charge_type == 'percent') {{ '%'}} @endif </div></td>
              </tr>
            @endif
            @if(session('business.enable_rp') == 1 && !empty($sell->rp_redeemed) )
              <tr>
                <th>{{session('business.rp_name')}}:</th>
                <td><b>(-)</b></td>
                <td> <span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->rp_redeemed_amount }}</span></td>
              </tr>
            @endif
            <tr>
              <th>{{ __('sale.order_tax') }}:</th>
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
            @if(!empty($line_taxes))
            <tr>
              <th>{{ __('lang_v1.line_taxes') }}:</th>
              <td></td>
              <td class="text-right">
                @if(!empty($line_taxes))
                  @foreach($line_taxes as $k => $v)
                    <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
                  @endforeach
                @else
                0.00
                @endif
              </td>
            </tr>
            @endif
            <tr>
              <th>{{ __('sale.shipping') }}: @if($sell->shipping_details)({{$sell->shipping_details}}) @endif</th>
              <td><b>(+)</b></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->shipping_charges }}</span></td>
            </tr>

            @if( !empty( $sell->additional_expense_value_1 )  && !empty( $sell->additional_expense_key_1 ))
              <tr>
                <th>{{ $sell->additional_expense_key_1 }}:</th>
                <td><b>(+)</b></td>
                <td><span class="display_currency pull-right" >{{ $sell->additional_expense_value_1 }}</span></td>
              </tr>
            @endif
            @if( !empty( $sell->additional_expense_value_2 )  && !empty( $sell->additional_expense_key_2 ))
              <tr>
                <th>{{ $sell->additional_expense_key_2 }}:</th>
                <td><b>(+)</b></td>
                <td><span class="display_currency pull-right" >{{ $sell->additional_expense_value_2 }}</span></td>
              </tr>
            @endif
            @if( !empty( $sell->additional_expense_value_3 )  && !empty( $sell->additional_expense_key_3 ))
              <tr>
                <th>{{ $sell->additional_expense_key_3 }}:</th>
                <td><b>(+)</b></td>
                <td><span class="display_currency pull-right" >{{ $sell->additional_expense_value_3 }}</span></td>
              </tr>
            @endif
            @if( !empty( $sell->additional_expense_value_4 ) && !empty( $sell->additional_expense_key_4 ))
              <tr>
                <th>{{ $sell->additional_expense_key_4 }}:</th>
                <td><b>(+)</b></td>
                <td><span class="display_currency pull-right" >{{ $sell->additional_expense_value_4 }}</span></td>
              </tr>
            @endif
            <tr>
              <th>{{ __('lang_v1.round_off') }}: </th>
              <td></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->round_off_amount }}</span></td>
            </tr>
            <tr>
              <th>{{ __('sale.total_payable') }}: </th>
              <td></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->final_total }}</span></td>
            </tr>
            @if(!empty($sell->return_parent))
            <tr class="bg-danger">
              <th>{{ __('lang_v1.sell_return') }}: </th>
              <td><b>(-)</b></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->return_parent->final_total }}</span></td>
            </tr>
            <tr>
              <th>{{ __('lang_v1.net_payable') }}: </th>
              <td></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->final_total - $sell->return_parent->final_total }}</span></td>
            </tr>
            @endif
            @if($sell->type != 'sales_order')
            <tr>
              <th>{{ __('sale.total_paid') }}:</th>
              <td></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $total_paid }}</span></td>
            </tr>
            <tr>
              <th>{{ __('sale.total_remaining') }}:</th>
              <td></td>
              <td>
                @php
                  $total_paid = (string) $total_paid;
                  $sell_return_amount = !empty($sell->return_parent) ? (float)$sell->return_parent->final_total : 0;
                  $remaining = $sell->final_total - $sell_return_amount - $total_paid;
                @endphp
                <span class="display_currency pull-right" data-currency_symbol="true" >{{ $remaining }}</span></td>
            </tr>
            @endif
          </table>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <strong>{{ __( 'sale.sell_note')}}:</strong><br>
        <p class="well well-sm no-shadow bg-gray">
          @if($sell->additional_notes)
            {!! nl2br($sell->additional_notes) !!}
          @else
            --
          @endif
        </p>
      </div>
      <div class="col-sm-6">
        <strong>{{ __( 'sale.staff_note')}}:</strong><br>
        <p class="well well-sm no-shadow bg-gray">
          @if($sell->staff_note)
            {!! nl2br($sell->staff_note) !!}
          @else
            --
          @endif
        </p>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
            <strong>{{ __('lang_v1.activities') }}:</strong><br>
            @includeIf('activity_log.activities', ['activity_type' => 'sell'])
        </div>
    </div>
  </div>
  <div class="modal-footer">
    @if($sell->type != 'sales_order')
    <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-success tw-text-white" data-href="{{route('sell.printInvoice', [$sell->id])}}?package_slip=true"><i class="fas fa-file-alt" aria-hidden="true"></i> @lang("lang_v1.packing_slip")</a>
    @endif
    @can('print_invoice')
      <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-primary tw-text-white" data-href="{{route('sell.printInvoice', [$sell->id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("lang_v1.print_invoice")</a>
    @endcan
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
