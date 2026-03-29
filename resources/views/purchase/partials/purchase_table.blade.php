<table class="table table-bordered table-striped ajax_view" id="purchase_table" style="width: 100%;">
    <thead>
        <tr>
            <th>@lang('messages.action')</th>
            <th>@lang('messages.date')</th>
            <th>@lang('purchase.ref_no')</th>
            <th>Job Sheet No</th>
            <th>@lang('purchase.location')</th>
            <th>@lang('purchase.supplier')</th>
            <th>@lang('purchase.purchase_status')</th>
            <th>@lang('purchase.payment_status')</th>
            <th>@lang('purchase.grand_total')</th>
            <th>@lang('purchase.asked_qty')</th>
            <th>@lang('purchase.payment_due')</th>
            <th>@lang('lang_v1.added_by')</th>
        </tr>
    </thead>
    <tfoot>
        <tr class="bg-gray font-17 text-center footer-total">
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>@lang('sale.total'):</strong></td>
            <td class="footer_status_count"></td>
            <td class="footer_payment_status_count"></td>
            <td class="footer_purchase_total"></td>
            <td></td>
            <td class="text-left"><small>@lang('report.purchase_due') - <span class="footer_total_due"></span><br>
            @lang('lang_v1.purchase_return') - <span class="footer_total_purchase_return_due"></span>
            </small></td>
            <td></td>
        </tr>
    </tfoot>
</table>