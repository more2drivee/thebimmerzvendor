<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="repairEditContactLabel">{{ __('contact.edit_contact') }}</h4>
        </div>
        <div class="modal-body">
            {!! Form::open(['url' => route('repair.contacts.update_basic', [$contact->id]), 'method' => 'post', 'id' => 'repair_edit_contact_form']) !!}
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('first_name', __('contact.first_name') . ':') !!}
                            {!! Form::text('first_name', $contact->first_name, ['class' => 'form-control', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('middle_name', __('contact.middle_name') . ':') !!}
                            {!! Form::text('middle_name', $contact->middle_name, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('last_name', __('contact.last_name') . ':') !!}
                            {!! Form::text('last_name', $contact->last_name, ['class' => 'form-control', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('mobile', __('contact.mobile') . ':') !!}
                            {!! Form::text('mobile', $contact->mobile, ['class' => 'form-control', 'id' => 'mobile_input', 'data-contact-id' => $contact->id]); !!}
                            <div id="mobile_validation_message" class="help-block" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('assigned_users[]', __('lang_v1.assigned_to') . ':') !!}
                            <select name="assigned_users[]" id="assigned_users" class="form-control select2" multiple="multiple" style="width: 100%;">
                                @foreach($assigned_users ?? [] as $user_id => $user_name)
                                    <option value="{{ $user_id }}" {{ in_array($user_id, $contact_assigned_users ?? []) ? 'selected' : '' }}>{{ $user_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            {!! Form::close() !!}
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.cancel') }}</button>
            <button type="submit" form="repair_edit_contact_form" class="btn btn-primary" id="save_contact_btn">{{ __('messages.save') }}</button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var mobileCheckTimeout;
    var isMobileValid = true;
    var duplicateContactData = null;

    // Initialize select2 for assigned users dropdown
    $('#assigned_users').select2({
        dropdownParent: $('#repair_edit_contact_modal'),
        placeholder: '{{ __("messages.please_select") }}'
    });

    $('#mobile_input').on('input', function() {
        var mobile = $(this).val();
        var contactId = $(this).data('contact-id');
        var $message = $('#mobile_validation_message');

        clearTimeout(mobileCheckTimeout);

        if (mobile.length === 0) {
            $message.hide();
            $(this).removeClass('is-invalid is-valid');
            isMobileValid = true;
            duplicateContactData = null;
            return;
        }

        mobileCheckTimeout = setTimeout(function() {
            $.ajax({
                method: 'POST',
                url: '{{ route("repair.contacts.check_mobile") }}',
                data: {
                    mobile: mobile,
                    contact_id: contactId,
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.valid === false && response.duplicate_mobile) {
                        var currentContactName = $('#mobile_input').closest('.modal').find('input[name="first_name"]').val() + ' ' + $('#mobile_input').closest('.modal').find('input[name="last_name"]').val();
                        $message.html(
                            '<div class="alert alert-warning" style="margin-bottom: 0;">' +
                                '<strong>{{ __("contact.mobile_already_exists") }}</strong><br>' +
                                '<small>{{ __("contact.other_contact") }}: ' + response.duplicate_contact_name + '</small><br>' +
                                '<button type="button" class="btn btn-sm btn-warning mt-2" id="show_merge_options">' +
                                    '{{ __("contact.choose_merge_option") }}' +
                                '</button>' +
                            '</div>'
                        );
                        $message.show();
                        $('#mobile_input').removeClass('is-valid').addClass('is-invalid');
                        isMobileValid = false;
                        duplicateContactData = response;

                        $('#show_merge_options').off('click').on('click', function() {
                            var currentContactId = $('#mobile_input').data('contact-id');
                            var duplicateContactId = response.duplicate_contact_id;

                            $('#merge_mobile').text(mobile);
                            $('#current_contact_name').text(currentContactName.trim());
                            $('#duplicate_contact_name').text(response.duplicate_contact_name);
                            $('#contact_merge_modal').data('current-contact-id', currentContactId);
                            $('#contact_merge_modal').data('duplicate-contact-id', duplicateContactId);
                            $('#contact_merge_modal').modal('show');
                        });
                    } else {
                        $message.hide();
                        $('#mobile_input').removeClass('is-invalid').addClass('is-valid');
                        isMobileValid = true;
                        duplicateContactData = null;
                    }
                },
                error: function() {
                    $message.hide();
                    $('#mobile_input').removeClass('is-invalid is-valid');
                    isMobileValid = true;
                    duplicateContactData = null;
                }
            });
        }, 500);
    });
});
</script>