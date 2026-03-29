<div class="modal fade" id="internal_transfer_modal" tabindex="-1" role="dialog" aria-labelledby="internalTransferModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="internalTransferModalLabel">@lang('treasury::lang.internal_transfer')</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Transfer Type Selection -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5>@lang('treasury::lang.transfer_type')</h5>
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-outline-primary active" id="payment_transfer_tab">
                                <input type="radio" name="transfer_type" value="payment_transfer" checked> 
                                <i class="fas fa-exchange-alt"></i> @lang('treasury::lang.payment_method_transfer')
                            </label>
                            @php
                                $business_locations = \App\BusinessLocation::forDropdown(request()->session()->get('user.business_id'), false, false, true, true);
                            @endphp
                            @if(count($business_locations) > 1)
                            <label class="btn btn-outline-success" id="branch_transfer_tab">
                                <input type="radio" name="transfer_type" value="branch_transfer"> 
                                <i class="fas fa-building"></i> @lang('treasury::lang.branch_transfer')
                            </label>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Payment Method Cards -->
                <div class="row mb-4" id="payment_method_cards_container" style="display: none;">
                    <div class="col-md-12">
                        <h5>@lang('treasury::lang.available_balances')</h5>
                        <div class="row" id="payment_method_cards">
                            <!-- Cards will be loaded here dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Payment Method Transfer Form -->
                <div id="payment_transfer_form_container">
                    <form id="payment_transfer_form">
                        @if(count($business_locations) >= 1)
                        <!-- Branch Selection for Payment Transfer (show even when there's exactly 1 branch) -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{ __('treasury::lang.branch') }} <small class="text-muted">({{ __('messages.optional') }})</small></label>
                                    <select class="form-control" name="location_id" id="payment_transfer_location_id">
                                        <option value="">{{ __('messages.please_select') }}</option>
                                        
                                        @foreach($business_locations as $location_id => $location_name)
                                            <option value="{{ $location_id }}">{{ $location_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('treasury::lang.from_payment_method')</label>
                                    <select class="form-control" name="from_payment_method" id="from_payment_method" required onchange="validatePaymentMethodSelection(this)">
                                        <option value="">@lang('treasury::lang.select_payment_method')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('treasury::lang.to_payment_method')</label>
                                    <select class="form-control" name="to_payment_method" id="to_payment_method" required onchange="validatePaymentMethodSelection(this)">
                                        <option value="">@lang('treasury::lang.select_payment_method')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('treasury::lang.amount')</label>
                                    <input type="text" class="form-control input_number" name="amount" id="payment_transfer_amount" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('treasury::lang.transfer_date')</label>
                                    <input type="text" class="form-control" name="transfer_date" id="payment_transfer_date" value="{{ @format_date('now') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('treasury::lang.notes')</label>
                                    <textarea class="form-control" name="notes" id="payment_transfer_notes" rows="3" placeholder="@lang('treasury::lang.transfer_notes_placeholder')"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                @if(count($business_locations) > 1)
                <!-- Branch Transfer Form (only when more than 1 branch exists) -->
                <div id="branch_transfer_form_container" style="display: none;">
                    <form id="branch_transfer_form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('treasury::lang.from_branch') }} <span class="text-danger">*</span></label>
                                    <select class="form-control" name="from_location_id" id="from_location_id" required onchange="validateBranchSelection(this)">
                                        <option value="">{{ __('messages.please_select') }}</option>
                                        @foreach($business_locations as $location_id => $location_name)
                                            <option value="{{ $location_id }}">{{ $location_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('treasury::lang.to_branch') }} <span class="text-danger">*</span></label>
                                    <select class="form-control" name="to_location_id" id="to_location_id" required onchange="validateBranchSelection(this)">
                                        <option value="">{{ __('messages.please_select') }}</option>
                                        @foreach($business_locations as $location_id => $location_name)
                                            <option value="{{ $location_id }}">{{ $location_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('treasury::lang.payment_method') <span class="text-danger">*</span></label>
                                    <select class="form-control" name="payment_method" id="branch_payment_method" required>
                                        <option value="">@lang('treasury::lang.select_payment_method')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('treasury::lang.amount')</label>
                                    <input type="text" class="form-control input_number" name="amount" id="branch_transfer_amount" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('treasury::lang.transfer_date')</label>
                                    <input type="text" class="form-control" name="transfer_date" id="branch_transfer_date" value="{{ @format_date('now') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('treasury::lang.notes')</label>
                                    <textarea class="form-control" name="notes" id="branch_transfer_notes" rows="3" placeholder="@lang('treasury::lang.transfer_notes_placeholder')"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="btn btn-primary" id="submit_transfer">@lang('treasury::lang.submit_transfer')</button>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script type="text/javascript">
    // Validate branch selection to prevent same branch selection
    function validateBranchSelection(element) {
        var fromBranch = $('#from_location_id').val();
        var toBranch = $('#to_location_id').val();
        
        if (fromBranch && toBranch && fromBranch === toBranch) {
            toastr.warning({!! json_encode(__('treasury::lang.please_select_different_branches')) !!});
            $(element).val('');
            $(element).focus();
            
            // Hide branch balances if displayed
            if ($('#branch_balances_container').length) {
                $('#branch_balances_container').hide();
            }
        }
    }
    
    // Validate payment method selection for payment transfers
    function validatePaymentMethodSelection(element) {
        var fromMethod = $('#from_payment_method').val();
        var toMethod = $('#to_payment_method').val();
        
        if (fromMethod && toMethod && fromMethod === toMethod) {
            toastr.warning({!! json_encode(__('treasury::lang.please_select_different_payment_methods')) !!});
            $(element).val('');
            $(element).focus();
        }
    }
</script>

    <script type="text/javascript">
        // If there's exactly one branch in the payment-transfer branch dropdown, auto-select it
        // and ensure the balances loader runs. We first try to trigger the change event;
        // if the handler isn't yet bound, call the loader directly when available.
        $(document).ready(function() {
            $('#internal_transfer_modal').on('shown.bs.modal', function() {
                var $loc = $('#payment_transfer_location_id');
                if ($loc.length) {
                    // Count only actual options that have a non-empty value
                    var validOptions = $loc.find('option').filter(function() { return $(this).val() !== ''; });
                    if (validOptions.length === 1) {
                        var val = validOptions.first().val();
                        // Auto-select the single branch
                        $loc.val(val);

                        // Preferred: trigger a change event so existing handlers run
                        // Give a small timeout to ensure other scripts attached their handlers
                        setTimeout(function() {
                            $loc.trigger('change');

                            // Fallback: if the named loader function exists, call it directly
                            if (typeof loadBranchSpecificBalances === 'function') {
                                loadBranchSpecificBalances(val);
                            }
                        }, 80);
                    }
                }
            });
        });
    </script>
