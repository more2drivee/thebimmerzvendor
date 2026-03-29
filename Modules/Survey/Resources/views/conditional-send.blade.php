@extends('layouts.app')

@section('title', __('survey::lang.conditional-send'))

@section('content')
    @include('survey::layouts.nav')

    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('survey::lang.conditional-send')</h1>
    </section>

    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <form method="POST" action="{{ route('survey.conditional-send') }}" id="conditional_send_form">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('survey::lang.action-type') }}</label>
                                        <select class="form-control" id="action_type_select" name="action_type" required>
                                            <option value="">{{ __('survey::lang.select-action-type') }}</option>
                                            <option value="direct_sale">{{ __('survey::lang.direct-sale') }}</option>
                                            <option value="repair_transaction">{{ __('survey::lang.repair-transaction') }}</option>
                                            <option value="crm_follow_up">{{ __('survey::lang.crm-follow-up') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('survey::lang.action-status') }}</label>
                                        <select class="form-control" id="action_status_select" name="action_status" required>
                                            <option value="">{{ __('survey::lang.select-action-status') }}</option>
                                            <option value="final" class="status-option direct-sale-status repair-status">{{ __('survey::lang.final') }}</option>
                                            <option value="scheduled" class="status-option crm-status">{{ __('crm::lang.scheduled') }}</option>
                                            <option value="completed" class="status-option crm-status">{{ __('crm::lang.completed') }}</option>
                                            <option value="cancelled" class="status-option crm-status">{{ __('crm::lang.cancelled') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('survey::lang.communication-channel') }}</label>
                                        <select class="form-control" id="channel_select" name="channel" required>
                                            <option value="sms">{{ __('survey::lang.sms') }}</option>
                                            <!-- <option value="whatsapp">{{ __('survey::lang.whatsapp') }}</option> -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('survey::lang.start-date') }}</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('survey::lang.end-date') }}</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('survey::lang.category') }}</label>
                                        <select class="form-control" id="category_select" name="category_id">
                                            <option value="">{{ __('survey::lang.select-category') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('survey::lang.select-survey') }}</label>
                                        <select class="form-control" id="survey_selectz" name="survey_id" required>
                                            <option value="">{{ __('survey::lang.select-survey') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-info" id="preview_contacts">{{ __('survey::lang.preview-contacts') }}</button>
                                    <button type="submit" class="btn btn-primary">{{ __('survey::lang.send') }}</button>
                                </div>
                            </div>
                        </form>

                        <div class="row" id="contacts_preview" style="display: none; margin-top: 20px;">
                            <div class="col-md-12">
                                <div class="box box-info">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">{{ __('survey::lang.affected-contacts') }}</h3>
                                        <div class="pull-right">
                                            <button type="button" class="btn btn-danger btn-sm" id="remove_selected_contacts" style="display: none;">
                                                <i class="fa fa-trash"></i> {{ __('survey::lang.remove-contact') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="box-body">
                                        <div class="alert alert-info">
                                            <strong>{{ __('survey::lang.total-contacts') }}:</strong> <span id="contacts_count">0</span> |
                                            <strong>{{ __('survey::lang.selected-contacts') }}:</strong> <span id="selected_contacts_count">0</span>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 15px;">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="add_contact_mobile" placeholder="{{ __('survey::lang.mobile') }}">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-success" id="add_contact_btn">
                                                        <i class="fa fa-plus"></i> {{ __('survey::lang.add-contact') }}
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        <table class="table table-bordered table-striped" id="contacts_table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50px;">
                                                        <input type="checkbox" id="select_all_contacts">
                                                    </th>
                                                    <th>{{ __('survey::lang.name') }}</th>
                                                    <th>{{ __('survey::lang.mobile') }}</th>
                                                    <th>{{ __('survey::lang.action-date') }}</th>
                                                    <th style="width: 80px;">{{ __('survey::lang.action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="contacts_list"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
console.log('Script loaded');

$(document).ready(function() {
    console.log('Script loaded');

    // Handle action type change to update status options
    $('#action_type_select').on('change', function() {
        var actionType = $(this).val();
        console.log('Action type changed to:', actionType);
        updateStatusOptions(actionType);
    });

    // Form submission handler
    $('#conditional_send_form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Form submission response:', response);
                if (response.success) {
                    // Show toaster notification
                    toastr.success(response.message);
                    // Clear form
                    $('#conditional_send_form')[0].reset();
                    $('#contacts_list').empty();
                    $('#contacts_count').text('0');
                    $('#selected_contacts_count').text('0');
                    $('#contacts_preview').hide();
                } else {
                    toastr.error(response.message || 'Error sending survey');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error submitting form:', error);
                console.error('Response:', xhr.responseText);
                toastr.error('Error sending survey');
            }
        });
    });

    // Load categories on page load
    loadCategories();

    // Handle category change
    $('#category_select').on('change', function() {
        var categoryId = $(this).val();
        console.log('Category changed to:', categoryId);
        if (categoryId) {
            loadSurveys(categoryId);
        } else {
            $('#survey_selectz').empty().append('<option value="">{{ __('survey::lang.select-survey') }}</option>');
        }
    });

    // Preview contacts button handler
    $('#preview_contacts').on('click', function() {
        console.log('Preview contacts clicked');
        var actionType = $('#action_type_select').val();
        var actionStatus = $('#action_status_select').val();
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        var surveyId = $('#survey_selectz').val();

        console.log('Form values:', {actionType, actionStatus, startDate, endDate, surveyId});

        if (!actionType || !actionStatus || !startDate || !endDate || !surveyId) {
            alert('Please fill all required fields');
            return;
        }

        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        console.log('CSRF Token:', csrfToken);

        $.ajax({
            url: '{{ route('survey.conditional-contacts') }}',
            method: 'POST',
            data: {
                _token: csrfToken,
                action_type: actionType,
                action_status: actionStatus,
                start_date: startDate,
                end_date: endDate
            },
            dataType: 'json',
            success: function(response) {
                console.log('Contacts response:', response);
                $('#contacts_count').text(response.count);
                var contactsList = $('#contacts_list');
                contactsList.empty();
                window.allContacts = response.contacts || [];

                if (response.contacts && response.contacts.length > 0) {
                    $.each(response.contacts, function(index, contact) {
                        contactsList.append(
                            '<tr data-contact-id="' + contact.id + '">' +
                                '<td><input type="checkbox" class="contact-checkbox" value="' + contact.id + '"></td>' +
                                '<td>' + contact.first_name + '</td>' +
                                '<td>' + contact.mobile + '</td>' +
                                '<td>' + contact.action_date + '</td>' +
                                '<td><button type="button" class="btn btn-danger btn-xs remove-single-contact" data-contact-id="' + contact.id + '"><i class="fa fa-trash"></i></button></td>' +
                            '</tr>'
                        );
                    });
                } else {
                    contactsList.append('<tr><td colspan="5">No contacts found</td></tr>');
                }

                $('#contacts_preview').show();
                updateSelectedCount();
            },
            error: function(xhr, status, error) {
                console.error('Error loading contacts:', error);
                console.error('Response:', xhr.responseText);
                alert('Error loading contacts');
            }
        });
    });

    // Select all contacts checkbox
    $('#select_all_contacts').on('change', function() {
        $('.contact-checkbox').prop('checked', $(this).prop('checked'));
        updateSelectedCount();
    });

    // Individual contact checkboxes
    $(document).on('change', '.contact-checkbox', function() {
        updateSelectedCount();
    });

    // Remove single contact
    $(document).on('click', '.remove-single-contact', function() {
        var contactId = $(this).data('contact-id');
        $('tr[data-contact-id="' + contactId + '"]').remove();
        updateSelectedCount();
    });

    // Remove selected contacts button
    $('#remove_selected_contacts').on('click', function() {
        $('.contact-checkbox:checked').each(function() {
            var contactId = $(this).val();
            $('tr[data-contact-id="' + contactId + '"]').remove();
        });
        $('#select_all_contacts').prop('checked', false);
        updateSelectedCount();
    });

    // Add contact by mobile number
    $('#add_contact_btn').on('click', function() {
        var mobile = $('#add_contact_mobile').val().trim();
        if (!mobile) {
            alert('Please enter a mobile number');
            return;
        }

        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: '{{ route('survey.search-contact-by-mobile') }}',
            method: 'POST',
            data: {
                _token: csrfToken,
                mobile: mobile
            },
            dataType: 'json',
            success: function(response) {
                console.log('Contact search response:', response);
                if (response.contact) {
                    var contact = response.contact;
                    $('#contacts_list').append(
                        '<tr data-contact-id="' + contact.id + '">' +
                            '<td><input type="checkbox" class="contact-checkbox" value="' + contact.id + '" checked></td>' +
                            '<td>' + contact.first_name + ' ' + (contact.last_name || '') + '</td>' +
                            '<td>' + contact.mobile + '</td>' +
                            '<td>-</td>' +
                            '<td><button type="button" class="btn btn-danger btn-xs remove-single-contact" data-contact-id="' + contact.id + '"><i class="fa fa-trash"></i></button></td>' +
                        '</tr>'
                    );
                    $('#add_contact_mobile').val('');
                    updateSelectedCount();
                } else {
                    alert('Contact not found');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error searching contact:', error);
                console.error('Response:', xhr.responseText);
                alert('Error searching contact');
            }
        });
    });

    function updateSelectedCount() {
        var selectedCount = $('.contact-checkbox:checked').length;
        $('#selected_contacts_count').text(selectedCount);

        if (selectedCount > 0) {
            $('#remove_selected_contacts').show();
        } else {
            $('#remove_selected_contacts').hide();
        }
    }

    function loadCategories() {
        console.log('Loading categories...');
        $.ajax({
            url: '{{ route('survey.categories.active') }}',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Categories response:', response);
                var categorySelect = $('#category_select');
                categorySelect.empty().append('<option value="">{{ __('survey::lang.select-category') }}</option>');

                if (response.categories && response.categories.length > 0) {
                    console.log('Found ' + response.categories.length + ' categories');
                    $.each(response.categories, function(index, category) {
                        categorySelect.append('<option value="' + category.id + '">' + category.name + '</option>');
                    });
                } else {
                    console.log('No categories found');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading categories:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    }

    function loadSurveys(categoryId) {
        console.log('Loading surveys for category:', categoryId);
        $.ajax({
            url: '{{ route('survey.active-surveys') }}',
            method: 'GET',
            data: { category_id: categoryId },
            dataType: 'json',
            success: function(response) {
                console.log('Surveys response:', response);
                var surveySelect = $('#survey_selectz');
                surveySelect.empty().append('<option value="">{{ __('survey::lang.select-survey') }}</option>');

                if (response.surveys && response.surveys.length > 0) {
                    console.log('Found ' + response.surveys.length + ' surveys');
                    $.each(response.surveys, function(index, survey) {
                        surveySelect.append('<option value="' + survey.id + '">' + survey.title + '</option>');
                    });
                } else {
                    console.log('No surveys found');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading surveys:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    }

    function updateStatusOptions(actionType) {
        console.log('Updating status options for:', actionType);
        var statusSelect = $('#action_status_select');

        // hide all first
        statusSelect.find('.direct-sale-status').hide();
        statusSelect.find('.repair-status').hide();
        statusSelect.find('.crm-status').hide();

        if (actionType === 'direct_sale') {
            statusSelect.find('.direct-sale-status').show();
        } else if (actionType === 'repair_transaction') {
            statusSelect.find('.repair-status').show();
        } else if (actionType === 'crm_follow_up') {
            statusSelect.find('.crm-status').show();
        }

        statusSelect.val('');
    }
});
</script>
@endsection
