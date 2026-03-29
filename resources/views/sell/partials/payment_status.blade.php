@if($payment_status == 'due' && isset($for_purchase) && $for_purchase)
    {{-- For purchases, check if final_total is 0 --}}
    @if(isset($final_total) && $final_total == 0)
        @php $empty_status = true; @endphp
    @endif
@endif

@if(!isset($empty_status) || !$empty_status)
    <a href="{{ action([\App\Http\Controllers\TransactionPaymentController::class, 'show'], [$id])}}" class="view_payment_modal payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="{{__('lang_v1.' . $payment_status)}}"><span class="label @payment_status($payment_status)">{{__('lang_v1.' . $payment_status)}}</span>
                        </a>
@endif