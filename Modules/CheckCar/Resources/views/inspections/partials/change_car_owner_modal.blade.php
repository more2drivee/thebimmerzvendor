<div class="modal fade" id="change_car_owner_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning" style="border-bottom: 1px solid #f0ad4e;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <i class="fa fa-random"></i>
                    @lang('checkcar::lang.change_car_owner')
                </h4>
            </div>
            <div class="modal-body">
                <form id="change_car_owner_form" method="POST" action="{{ route('checkcar.inspections.change_car_owner', ['inspection' => $inspection->id]) }}">
                    @csrf

                    <div id="change_car_owner_errors" class="alert alert-danger" style="display:none;">
                        <ul class="mb-0"></ul>
                    </div>

                    <div class="alert alert-warning">
                        <p style="margin-bottom: 5px;">
                            @lang('checkcar::lang.change_car_owner_help')
                        </p>
                        <p style="margin-bottom: 0;">
                            @lang('checkcar::lang.change_car_owner_affects_transaction')
                        </p>
                    </div>

                    <div class="form-group">
                        <label>@lang('checkcar::lang.current_owner'):</label>
                        @if($currentOwner === 'buyer' && $inspection->buyerContact)
                            <span class="label label-info">@lang('checkcar::lang.buyer')</span>
                            <strong>
                                {{ $inspection->buyerContact->name ?? trim(($inspection->buyerContact->first_name ?? '') . ' ' . ($inspection->buyerContact->last_name ?? '')) }}
                            </strong>
                        @elseif($currentOwner === 'seller' && $inspection->sellerContact)
                            <span class="label label-info">@lang('checkcar::lang.seller')</span>
                            <strong>
                                {{ $inspection->sellerContact->name ?? trim(($inspection->sellerContact->first_name ?? '') . ' ' . ($inspection->sellerContact->last_name ?? '')) }}
                            </strong>
                        @else
                            <span class="text-muted">@lang('checkcar::lang.no_owner_detected')</span>
                        @endif
                    </div>

                    <hr>

                    <div class="form-group">
                        <label>@lang('checkcar::lang.select_new_owner'):</label>

                        <div class="radio">
                            <label>
                                <input type="radio" name="new_owner" value="buyer" {{ $currentOwner === 'buyer' ? 'checked' : '' }} {{ empty($inspection->buyer_contact_id) ? 'disabled' : '' }}>
                                <strong>@lang('checkcar::lang.buyer')</strong>
                                @if($inspection->buyerContact)
                                    <span class="text-muted">
                                        - {{ $inspection->buyerContact->name ?? trim(($inspection->buyerContact->first_name ?? '') . ' ' . ($inspection->buyerContact->last_name ?? '')) }}
                                    </span>
                                @endif
                                @if(empty($inspection->buyer_contact_id))
                                    <span class="text-danger">(@lang('checkcar::lang.not_available'))</span>
                                @endif
                            </label>
                        </div>

                        <div class="radio">
                            <label>
                                <input type="radio" name="new_owner" value="seller" {{ $currentOwner === 'seller' ? 'checked' : '' }} {{ empty($inspection->seller_contact_id) ? 'disabled' : '' }}>
                                <strong>@lang('checkcar::lang.seller')</strong>
                                @if($inspection->sellerContact)
                                    <span class="text-muted">
                                        - {{ $inspection->sellerContact->name ?? trim(($inspection->sellerContact->first_name ?? '') . ' ' . ($inspection->sellerContact->last_name ?? '')) }}
                                    </span>
                                @endif
                                @if(empty($inspection->seller_contact_id))
                                    <span class="text-danger">(@lang('checkcar::lang.not_available'))</span>
                                @endif
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="btn btn-primary" id="change_car_owner_save_btn">@lang('messages.save')</button>
            </div>
        </div>
    </div>
</div>
