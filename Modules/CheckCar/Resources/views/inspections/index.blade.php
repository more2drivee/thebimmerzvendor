@extends('layouts.app')

@section('title', __('checkcar::lang.menu_check_car'))

@section('javascript')
<script>
// Configuration from server
window.inspectionsConfig = {
    ajaxUrl: "{{ route('checkcar.inspections.datatables') }}",
    pageLength: {{ $default_datatable_page_entries ?? 20 }}
};

$(document).ready(function() {
    var inspectionsTable = $('#inspections-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: window.inspectionsConfig.ajaxUrl,
        columns: [
            { data: 'id', name: 'id', orderable: true, searchable: false },
            { data: 'car_info', name: 'car_info', orderable: false, searchable: true },
            { data: 'buyer', name: 'buyer', orderable: false, searchable: true },
            { data: 'seller', name: 'seller', orderable: false, searchable: true },
            { data: 'location', name: 'location', orderable: false, searchable: true },
            { data: 'rating', name: 'rating', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: true, searchable: false },
            { data: 'created_at', name: 'created_at', orderable: true, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/' + (app_locale === 'ar' ? 'ar' : 'en') + '.json'
        },
        pageLength: window.inspectionsConfig.pageLength,
        order: [[0, 'desc']]
    });

    // Send SMS notifications (buyer & seller)
    $(document).on('click', '.js-send-sms', function() {
        var url = $(this).data('url');
        if (!url) return;

        var $btn = $(this);
        $btn.prop('disabled', true).addClass('disabled');

        $.post(url, { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res && res.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(res.message || '@lang('messages.success')');
                    }
                    inspectionsTable.ajax.reload(null, false);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error((res && res.message) || '@lang('messages.something_went_wrong')');
                    }
                }
            })
            .fail(function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || '@lang('messages.something_went_wrong')';
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                }
            })
            .always(function() {
                $btn.prop('disabled', false).removeClass('disabled');
            });
    });

    // Open change car owner modal
    $(document).on('click', '.js-change-car-owner', function() {
        var url = $(this).data('url');

        $.get(url, function(html) {
            $('#change_car_owner_modal').remove();
            $('body').append(html);
            $('#change_car_owner_modal').modal('show');
        });
    });

    // Save change car owner
    $(document).on('click', '#change_car_owner_save_btn', function() {
        var $form = $('#change_car_owner_form');
        if ($form.length === 0) {
            return;
        }

        var url = $form.attr('action');
        var data = $form.serialize();
        var $errorBox = $('#change_car_owner_errors');
        var $list = $errorBox.find('ul');
        $list.empty();
        $errorBox.hide();

        $.post(url, data)
            .done(function(response) {
                if (response.success) {
                    $('#change_car_owner_modal').modal('hide');
                    inspectionsTable.ajax.reload(null, false);
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || '@lang('messages.success')');
                    }
                } else if (response.message) {
                    $list.append('<li>' + response.message + '</li>');
                    $errorBox.show();
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message);
                    }
                }
            })
            .fail(function(xhr) {
                var errors = (xhr.responseJSON && xhr.responseJSON.errors) ? xhr.responseJSON.errors : {};
                var hasError = false;

                $.each(errors, function(field, messages) {
                    if ($.isArray(messages)) {
                        $.each(messages, function(_, msg) {
                            $list.append('<li>' + msg + '</li>');
                            if (typeof toastr !== 'undefined') {
                                toastr.error(msg);
                            }
                        });
                    } else if (messages) {
                        $list.append('<li>' + messages + '</li>');
                        if (typeof toastr !== 'undefined') {
                            toastr.error(messages);
                        }
                    }
                    hasError = true;
                });

                if (!hasError) {
                    var genericMsg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : '@lang('messages.something_went_wrong')';
                    $list.append('<li>' + genericMsg + '</li>');
                    if (typeof toastr !== 'undefined') {
                        toastr.error(genericMsg);
                    }
                }

                $errorBox.show();
            });
    });
});
</script>
@endsection

@section('content')
@include('checkcar::layouts.nav')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('checkcar::lang.menu_check_car')
    </h1>
    <p class="tw-text-gray-700 tw-mt-1">
        @lang('checkcar::lang.module_subtitle')
    </p>
</section>

<section class="content no-print">
    <div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-p-4 md:tw-p-6">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
            <h3 class="tw-font-semibold tw-text-lg">
                @lang('checkcar::lang.inspections_list')
            </h3>
       
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover" id="inspections-table">
                <thead>
                    <tr>
                        <th width="60">{{ __('messages.id') }}</th>
                        <th>{{ __('checkcar::lang.car_info') }}</th>
                        <th>{{ __('checkcar::lang.buyer') }}</th>
                        <th>{{ __('checkcar::lang.seller') }}</th>
                        <th>{{ __('business.location') }}</th>
                        <th width="100">{{ __('checkcar::lang.rating') }}</th>
                        <th width="100">{{ __('messages.status') }}</th>
                        <th width="120">{{ __('messages.date') }}</th>
                        <th width="150">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</section>
@endsection
