<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\Modules\Repair\Http\Controllers\DeviceModelController::class, 'store']), 'method' => 'post', 'id' => 'device_model' ]) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title" id="myModalLabel">
                @lang('repair::lang.add_device_model')
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                   <div class="form-group">
                        {!! Form::label('name', __('repair::lang.model_name') . ':*' )!!}
                        {!! Form::text('name', null, ['class' => 'form-control', 'required' ]) !!}
                   </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                   <div class="form-group">
                        {!! Form::label('device_id', __('product.brand') .':') !!}
                        {!! Form::select('device_id', $devices, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;', 'id' => 'model_device_id']) !!}
                   </div>
                </div>
            </div>
            <!-- Add VIN Model Code field -->
            <div class="row">
                <div class="col-md-12">
                   <div class="form-group">
                        {!! Form::label('vin_model_code', __('messages.vin_model_code') . ':') !!}
                        <i class="fa fa-info-circle text-info" data-toggle="tooltip" title="Vehicle Identification Number model code (typically positions 4-8 in VIN)"></i>
                        {!! Form::text('vin_model_code', null, ['class' => 'form-control', 'placeholder' => 'e.g. WVWAA7']) !!}
                   </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('repair_checklist', __('repair::lang.repair_checklist') . ':') !!} @show_tooltip(__('repair::lang.repair_checklist_tooltip'))
                        {!! Form::textarea('repair_checklist', null, ['class' => 'form-control ', 'id' => 'repair_checklist', 'rows' => '3']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">
                @lang('messages.close')
            </button>
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                @lang('messages.save')
            </button>
        </div>
        {!! Form::close() !!}
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Initialize select2 with improved search
        $('#model_device_id').select2({
            dropdownParent: $('#device_model').closest('.modal'),
            width: '100%',
            placeholder: "{{ __('messages.please_select') }}",
            allowClear: true,
            minimumInputLength: 0,
            language: {
                noResults: function() {
                    return "{{ __('messages.no_results_found') }}";
                },
                searching: function() {
                    return "{{ __('messages.searching') }}...";
                }
            }
        });

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Initialize repair checklist editor if needed
        if (typeof CKEDITOR !== 'undefined' && $('#repair_checklist').length) {
            CKEDITOR.replace('repair_checklist');
        }
    });
</script>