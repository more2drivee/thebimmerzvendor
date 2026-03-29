@extends('layouts.guest')
@section('title', __('treasury::lang.payment_transactions'))

@section('content')
<div class="container">
    <div class="spacer"></div>
    <div class="row">
        <div class="col-md-12 text-right mb-12">
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print tw-dw-btn-sm" id="print_payments">
                <i class="fas fa-print"></i> @lang('messages.print')
            </button>
            @auth
                <a href="{{ route('treasury.payments.index') }}" class="tw-dw-btn tw-dw-btn-success tw-text-white no-print tw-dw-btn-sm">
                    <i class="fas fa-backward"></i>
                </a>
            @endauth
        </div>
    </div>
    <div class="row">
        <div class="col-md-12" id="payments_report_content">
            <div style="direction: rtl; font-family: 'Cairo', 'Arial', sans-serif; text-align: right; max-width: 100%; margin: 0 auto; margin-bottom: 10px;">
                <div style="overflow: hidden;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 2; text-align: right;">
                            <div style="font-weight: bold; font-size: 20px; color: #333;">
                                {{ $business_details->name ?? config('app.name') }}
                            </div>
                            @if(!empty($business_details->business_address))
                                <div style="font-size: 12px; color: #555;">
                                    {!! $business_details->business_address !!}
                                </div>
                            @endif
                            @php
                                $business_contact = $business_details->mobile
                                    ?? ($business_details->alternate_number ?? ($business_details->landline ?? null));
                            @endphp
                            @if(!empty($business_contact))
                                <div style="font-size: 12px; color: #555; direction: ltr; text-align: left; unicode-bidi: embed; float: right;">
                                    {{ $business_contact }}
                                </div>
                            @endif
                        </div>
                        <div style="flex: 1; text-align: left;">
                            @php
                                $logo_url = null;
                                if (!empty($business_details->logo)) {
                                    $logo_url = asset('uploads/business_logos/' . $business_details->logo);
                                } elseif (session()->has('business.logo') && !empty(session('business.logo'))) {
                                    $logo_url = asset('uploads/business_logos/' . session('business.logo'));
                                }
                            @endphp
                            @if(!empty($logo_url))
                                <img style="max-height: 130px; max-width: 170px; float: left;" src="{{ $logo_url }}" class="img" onerror="this.style.display='none';">
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="text-center">@lang('treasury::lang.payment_transactions')</h3>

            @php
                $hasFilters = !empty($filters['start_date']) 
                    || !empty($filters['end_date'])
                    || !empty($filters['method'])
                    || !empty($filters['amount_min'])
                    || !empty($filters['amount_max'])
                    || !empty($filters['transaction_type']);
            @endphp

            @if($hasFilters)
                <div style="font-size: 13px; margin-bottom: 10px; text-align: right; background-color: #f8f9fa; padding: 8px; border-radius: 4px; border: 1px solid #dee2e6;">
                    <strong style="color: #495057;">{{ __('treasury::lang.filter') }}:</strong>

                    @if(!empty($filters['start_date']) || !empty($filters['end_date']))
                        <span style="margin-right: 15px; color: #6c757d;">
                            <strong>{{ __('treasury::lang.payments_date_range') }}:</strong>
                            @if(!empty($filters['start_date']))
                                <span style="color: #007bff;">{{ \Carbon\Carbon::parse($filters['start_date'])->format('Y-m-d') }}</span>
                            @endif
                            @if(!empty($filters['start_date']) && !empty($filters['end_date']))
                                <span> - </span>
                            @endif
                            @if(!empty($filters['end_date']))
                                <span style="color: #007bff;">{{ \Carbon\Carbon::parse($filters['end_date'])->format('Y-m-d') }}</span>
                            @endif
                        </span>
                    @endif

                    @if(!empty($filters['transaction_type']))
                        <span style="margin-right: 15px; color: #6c757d;">
                            <strong>{{ __('treasury::lang.transaction_type') }}:</strong>
                            <span style="color: #007bff;">{{ $filters['transaction_type'] }}</span>
                        </span>
                    @endif

                    @if(!empty($filters['method']))
                        <span style="margin-right: 15px; color: #6c757d;">
                            <strong>{{ __('treasury::lang.payment_method') }}:</strong>
                            <span style="color: #007bff;">{{ $payment_methods[$filters['method']] ?? $filters['method'] }}</span>
                        </span>
                    @endif

                    @if(!empty($filters['amount_min']))
                        <span style="margin-right: 15px; color: #6c757d;">
                            <strong>{{ __('treasury::lang.amount_min') }}:</strong>
                            <span style="color: #007bff;">{{ $filters['amount_min'] }}</span>
                        </span>
                    @endif

                    @if(!empty($filters['amount_max']))
                        <span style="margin-right: 15px; color: #6c757d;">
                            <strong>{{ __('treasury::lang.amount_max') }}:</strong>
                            <span style="color: #007bff;">{{ $filters['amount_max'] }}</span>
                        </span>
                    @endif
                </div>
            @else
                <div style="font-size: 13px; margin-bottom: 10px; text-align: right; color: #6c757d; font-style: italic;">
                    {{ __('treasury::lang.showing_all_transactions') }}
                </div>
            @endif

            <style>
                .page-break { page-break-after: always; }
            </style>

            @php
                $rows_per_page = 25;
                $chunks = $all_payments->chunk($rows_per_page);
            @endphp

            @foreach($chunks as $chunk_index => $page_payments)
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>@lang('treasury::lang.transaction_date')</th>
                            <th>@lang('treasury::lang.reference_no')</th>
                            <th>@lang('treasury::lang.invoice_no')</th>
                            <th>@lang('treasury::lang.invoice_no')</th>
                            <th>@lang('treasury::lang.payment_method')</th>
                            <th>@lang('treasury::lang.transaction_type')</th>
                            <th>@lang('treasury::lang.income')</th>
                            <th>@lang('treasury::lang.expense')</th>
                            <th>@lang('treasury::lang.contact')</th>
                            <th>@lang('treasury::lang.contact')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($page_payments as $row)
                            @php
                                $method_label = $row->method && isset($payment_methods[$row->method])
                                    ? $payment_methods[$row->method]
                                    : ($row->method ?: __('treasury::lang.payment_method'));
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->paid_on)->format('Y-m-d H:i') }}</td>
                                <td>{{ $row->payment_ref_no }}</td>
                                <td>{{ $row->invoice_no ?: $row->ref_no }}</td>
                                <td>{{ $row->invoice_no ?: $row->ref_no }}</td>
                                <td>{{ $method_label }}</td>
                                <td>{{ $row->transaction_type }}</td>
                                <td>{{ $row->income_amount }}</td>
                                <td>{{ $row->outcome_amount }}</td>
                                <td>
                                    @if(!empty($row->supplier_business_name))
                                        {{ $row->supplier_business_name }}<br>{{ $row->contact_name }}
                                    @else
                                        {{ $row->contact_name }}
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($row->supplier_business_name))
                                        {{ $row->supplier_business_name }}
                                    @else
                                        {{ $row->contact_name }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">@lang('messages.no_data_found')</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-right">@lang('treasury::lang.page_total'):</th>
                            <th>{{ $page_payments->sum('income_amount') }}</th>
                            <th>{{ $page_payments->sum('outcome_amount') }}</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>

                @if(!$loop->last)
                    <div class="page-break"></div>
                @endif
            @endforeach

     

            @if(isset($internal_transfers) && $internal_transfers->count())
                <div class="page-break"></div>

                <h3 class="text-center">@lang('treasury::lang.internal_transfers')</h3>

                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>@lang('treasury::lang.transaction_date')</th>
                    
                            <th>@lang('treasury::lang.invoice_no')</th>
                            <th>@lang('treasury::lang.from_method')</th>
                            <th>@lang('treasury::lang.to_method')</th>
                            <th>@lang('business.location')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('treasury::lang.notes')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($internal_transfers as $transfer)
                            @php
                                // Map from payment method code to label
                                $from_method_label = $transfer->payment_method && isset($payment_methods[$transfer->payment_method])
                                    ? $payment_methods[$transfer->payment_method]
                                    : ($transfer->payment_method ?: __('treasury::lang.payment_method'));

                                $raw_notes = $transfer->notes ?? '';
                                $to_label = $raw_notes;
                                $extra_notes = '';

                                // Split main "Internal transfer to ..." from extra user notes
                                if (strpos($raw_notes, '. ') !== false) {
                                    [$main_part, $extra_notes] = explode('. ', $raw_notes, 2);
                                    $to_label = $main_part;
                                }

                                // Remove the leading phrase to keep only destination info
                                $to_label = trim(preg_replace('/^Internal transfer to /', '', $to_label));
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($transfer->transaction_date)->format('Y-m-d H:i') }}</td>
                                <td>{{ $transfer->invoice_no }}</td>
                             
                                <td>{{ $from_method_label }}</td>
                                <td>{{ $to_label }}</td>
                                <td>{{ $transfer->from_location_name ?: '-' }}</td>
                                <td>{{ $transfer->amount }}</td>
                        
                                <td>{{ $extra_notes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">@lang('treasury::lang.total'):</th>
                            <th>{{ $internal_transfers->sum('amount') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            @endif

            @if(isset($opening_balances) && $opening_balances->count())
                <div class="page-break"></div>

                <h3 class="text-center">@lang('treasury::lang.opening_balance')</h3>

                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>@lang('treasury::lang.transaction_date')</th>
                            <th>@lang('treasury::lang.reference_no')</th>
                            <th>@lang('treasury::lang.invoice_no')</th>
                            <th>@lang('treasury::lang.branch')</th>
                            <th>@lang('treasury::lang.payment_method')</th>
                            <th>@lang('treasury::lang.amount')</th>
                            <th>@lang('treasury::lang.notes')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($opening_balances as $ob)
                            @php
                                $ob_method_label = $ob->payment_method && isset($payment_methods[$ob->payment_method])
                                    ? $payment_methods[$ob->payment_method]
                                    : ($ob->payment_method ?: __('treasury::lang.payment_method'));
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($ob->transaction_date)->format('Y-m-d H:i') }}</td>
                                <td>{{ $ob->ref_no }}</td>
                                <td>{{ $ob->invoice_no }}</td>
                                <td>{{ $ob->location_name ?: '-' }}</td>
                                <td>{{ $ob_method_label }}</td>
                                <td>{{ $ob->amount }}</td>
                                <td>{{ $ob->notes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">@lang('treasury::lang.total'):</th>
                            <th>{{ $opening_balances->sum('amount') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>
               <!-- Summary of All Sections -->
            @if(isset($all_payments) || (isset($internal_transfers) && $internal_transfers->count()) || (isset($opening_balances) && $opening_balances->count()))
                <div class="page-break"></div>
                
                <h3 class="text-center" style="font-weight: bold; color: #2c3e50;">@lang('treasury::lang.summary')</h3>
                
                <table class="table table-bordered" style="background-color: #f8f9fa;">
                    <thead>
                        <tr style="background-color: #e9ecef;">
                            <th style="width: 70%;">@lang('treasury::lang.section')</th>
                            <th style="width: 15%;">@lang('treasury::lang.income')</th>
                            <th style="width: 15%;">@lang('treasury::lang.expense')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($all_payments) && $all_payments->count())
                            <tr>
                                <td><strong>@lang('treasury::lang.payment_transactions')</strong></td>
                                <td>{{ $total_income }}</td>
                                <td>{{ $total_outcome }}</td>
                            </tr>
                        @endif
                        
                        @if(isset($internal_transfers) && $internal_transfers->count())
                            <tr>
                                <td><strong>@lang('treasury::lang.internal_transfers')</strong></td>
                                <td>{{ $internal_transfers->sum('amount') }}</td>
                                <td>{{ $internal_transfers->sum('amount') }}</td>
                            </tr>
                        @endif
                        
                        @if(isset($opening_balances) && $opening_balances->count())
                            <tr>
                                <td><strong>@lang('treasury::lang.opening_balance')</strong></td>
                                <td>{{ $opening_balances->sum('amount') }}</td>
                                <td>0</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #dee2e6; font-weight: bold;">
                            <th>@lang('treasury::lang.grand_total')</th>
                            <th>
                                {{ 
                                    (isset($total_income) ? $total_income : 0) + 
                                    (isset($internal_transfers) ? $internal_transfers->sum('amount') : 0) + 
                                    (isset($opening_balances) ? $opening_balances->sum('amount') : 0) 
                                }}
                            </th>
                            <th>
                                {{ 
                                    (isset($total_outcome) ? $total_outcome : 0) + 
                                    (isset($internal_transfers) ? $internal_transfers->sum('amount') : 0) 
                                }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            @endif
    </div>
    <div class="spacer"></div>
</div>
@endsection


@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        $(document).on('click', '#print_payments', function(){
            $('#payments_report_content').printThis();
        });
    });
</script>
@endsection
