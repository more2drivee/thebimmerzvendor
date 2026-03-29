<div class="modal fade" id="opening_balance_modal" tabindex="-1" role="dialog" aria-labelledby="openingBalanceModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="openingBalanceModalLabel">
                    <i class="fas fa-plus-circle"></i> @lang('treasury::lang.add_opening_balance')
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Current Balances Display -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="mb-3">
                            <i class="fas fa-wallet"></i> @lang('treasury::lang.current_payment_method_balances')
                        </h5>
                        <div class="row" id="current_balances_container">
                            <!-- Payment method balance cards will be loaded here -->
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-spinner fa-spin"></i> @lang('messages.loading')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Opening Balance Form -->
                <form id="opening_balance_form">
                    @csrf
                    
                    <!-- Branch Selection -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>@lang('treasury::lang.branch') <small class="text-muted">(@lang('messages.optional'))</small></label>
                                <select class="form-control" name="location_id" id="opening_balance_location_id">
                                    <option value="">@lang('messages.please_select')</option>
                                    @php
                                        $business_locations = \App\BusinessLocation::where('is_active', 1)->pluck('name', 'id');
                                    @endphp
                                    @foreach($business_locations as $location_id => $location_name)
                                        <option value="{{ $location_id }}">{{ $location_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>@lang('treasury::lang.payment_method') <span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_method" id="opening_balance_payment_method" required>
                                    <option value="">@lang('treasury::lang.select_payment_method')</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Amount -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>@lang('treasury::lang.amount') <span class="text-danger">*</span></label>
                                <input type="text" class="form-control input_number" name="amount" id="opening_balance_amount" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>@lang('treasury::lang.transaction_date') <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="transaction_date" id="opening_balance_date" value="{{ @format_date('now') }}" required>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Reference (Optional) -->
                    <!-- <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>@lang('treasury::lang.payment_reference') <small class="text-muted">(@lang('messages.optional'))</small></label>
                                <input type="text" class="form-control" name="payment_ref_no" id="opening_balance_ref_no" placeholder="e.g., Check #, Transfer ID">
                            </div>
                        </div>
                    </div> -->

                    <!-- Notes -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>@lang('treasury::lang.notes') <small class="text-muted">(@lang('messages.optional'))</small></label>
                                <textarea class="form-control" name="notes" id="opening_balance_notes" rows="3" placeholder="@lang('treasury::lang.opening_balance_notes_placeholder')"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="btn btn-primary" id="submit_opening_balance">
                    <i class="fas fa-save"></i> @lang('treasury::lang.add_opening_balance')
                </button>
            </div>
        </div>
    </div>
</div>
