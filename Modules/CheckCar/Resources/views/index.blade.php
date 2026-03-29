@extends('layouts.app')

@section('title', __('checkcar::lang.car_inspections'))

@section('content')
@include('checkcar::layouts.nav')
<section class="content-header">
    <h1>
        <i class="fa fa-car"></i> {{ __('checkcar::lang.car_inspections') }}
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
           
                
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="inspectionsTable">
                            <thead>
                                <tr>
                                    <th width="60">{{ __('messages.id') }}</th>
                                    <th>{{ __('checkcar::lang.car_info') }}</th>
                                    <th>{{ __('checkcar::lang.buyer') }}</th>
                                    <th>{{ __('checkcar::lang.seller') }}</th>
                                    <th>{{ __('business.location') }}</th>
                                    <th width="120">{{ __('checkcar::lang.job_sheet_no') }}</th>
                                    <th width="120">{{ __('messages.date') }}</th>
                                    <th width="150">{{ __('messages.actions') }}</th>
                                </tr>
                                
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Documents Modal -->
<div class="modal fade" id="documentsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-file"></i> {{ __('checkcar::lang.inspection_documents') }}
                </h4>
            </div>
            <div class="modal-body">
                <div id="documentsContainer">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">{{ __('messages.loading') }}...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SMS Recipients Modal -->
<div class="modal fade" id="smsRecipientsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-paper-plane"></i> {{ __('checkcar::lang.select_sms_recipients') }}
                </h4>
            </div>
            <div class="modal-body">
                <form id="smsRecipientsForm">
                    <input type="hidden" id="smsInspectionId" name="inspection_id">
                    <div class="form-group">
                        <label class="control-label">{{ __('checkcar::lang.send_to') }}:</label>
                        <div class="radio">
                            <label>
                                <input type="radio" name="recipient" value="buyer" checked>
                                {{ __('checkcar::lang.buyer') }}
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="recipient" value="seller">
                                {{ __('checkcar::lang.seller') }}
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="recipient" value="both">
                                {{ __('checkcar::lang.both') }}
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('messages.cancel') }}
                </button>
                <button type="button" class="btn btn-primary" id="sendSmsBtn">
                    <i class="fa fa-paper-plane"></i> {{ __('checkcar::lang.send_messages') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
// Translation strings from Laravel - extracted to avoid lint issues
var translations = {
    loading: "{{ __('messages.loading') }}",
    somethingWentWrong: "{{ __('messages.something_went_wrong') }}",
    view: "{{ __('messages.view') }}",
    download: "{{ __('messages.download') }}",
    inspectionDocuments: "{{ __('checkcar::lang.inspection_documents') }}",
    noDocumentsFound: "{{ __('checkcar::lang.no_documents_found') }}",
    documents: "{{ __('checkcar::lang.documents') }}",
    id: "{{ __('messages.id') }}",
    carInfo: "{{ __('checkcar::lang.car_info') }}",
    buyer: "{{ __('checkcar::lang.buyer') }}",
    seller: "{{ __('checkcar::lang.seller') }}",
    location: "{{ __('business.location') }}",
    jobSheetNo: "{{ __('checkcar::lang.job_sheet_no') }}",
    date: "{{ __('messages.date') }}",
    actions: "{{ __('messages.actions') }}",
    viewDocuments: "{{ __('checkcar::lang.view_documents') }}",
    publicView: "{{ __('checkcar::lang.public_view') }}",
    noInpectionsYet: "{{ __('checkcar::lang.no_inspections_yet') }}",
    createFirstInspection: "{{ __('checkcar::lang.create_first_inspection') }}",
    show: "{{ __('messages.show') }}",
    entries: "{{ __('messages.entries') }}",
    showing: "{{ __('messages.showing') }}",
    to: "{{ __('messages.to') }}",
    of: "{{ __('messages.of') }}",
    first: "{{ __('messages.first') }}",
    last: "{{ __('messages.last') }}",
    next: "{{ __('messages.next') }}",
    previous: "{{ __('messages.previous') }}",
    search: "{{ __('messages.search') }}",
    send: "{{ __('messages.send') }}",
    cancel: "{{ __('messages.cancel') }}",
    selectSmsRecipients: "{{ __('checkcar::lang.select_sms_recipients') }}",
    sendTo: "{{ __('checkcar::lang.send_to') }}",
    both: "{{ __('checkcar::lang.both') }}"
};

// Base URL for sending SMS (avoid calling route() without required params)
var sendSmsBaseUrl = "{{ url('checkcar/inspections') }}";

$(document).ready(function() {
    // Initialize DataTable
    var inspectionsTable = $('#inspectionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('checkcar.inspections.datatables') }}",
            type: "GET",
            data: function (d) {
                d._token = "{{ csrf_token() }}";
            }
        },
        columns: [
            {
                data: 'id',
                name: 'id',
                width: '60px',
                className: 'text-center',
                render: function(data) {
                    return '<strong>#' + data + '</strong>';
                }
            },
            {
                data: 'car_info',
                name: 'car_info',
                render: function(data) {
                    return data;
                }
            },
            {
                data: 'buyer',
                name: 'buyer',
                render: function(data) {
                    return data;
                }
            },
            {
                data: 'seller',
                name: 'seller',
                render: function(data) {
                    return data;
                }
            },
            {
                data: 'location',
                name: 'location',
                render: function(data) {
                    return data;
                }
            },
            {
                data: 'job_sheet_no',
                name: 'job_sheet_no',
                width: '120px',
                className: 'text-center',
                render: function(data) {
                    return data || '<span class="text-muted">-</span>';
                }
            },
            {
                data: 'created_at',
                name: 'created_at',
                width: '120px',
                className: 'text-center',
                render: function(data) {
                    return data || '';
                }
            },
            {
                data: 'actions',
                name: 'actions',
                width: '150px',
                className: 'text-center',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return data;
                }
            }
        ],
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-2x"></i>',
           
            lengthMenu: translations.show + ' _MENU_ ' + translations.entries,
            info: translations.showing + ' _START_ ' + translations.to + ' _END_ ' + translations.of + ' _TOTAL_ ' + translations.entries,
            paginate: {
                first: translations.first,
                last: translations.last,
                next: translations.next,
                previous: translations.previous
            },
            search: translations.search,
            aria: {
                sortAscending: ': activate to sort column ascending',
                sortDescending: ': activate to sort column descending'
            }
        },
        pageLength: 25,

        dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip'
    });
    // Open SMS recipients modal
    $(document).on('click', '.js-send-sms', function() {
        var inspectionId = $(this).data('inspection-id');
        $('#smsInspectionId').val(inspectionId);
        $('#smsRecipientsModal').modal('show');
    });

    // Send SMS with selected recipients
    $('#sendSmsBtn').click(function() {
        var inspectionId = $('#smsInspectionId').val();
        var recipient = $('input[name="recipient"]:checked').val();
        
        if (!inspectionId) {
            toastr.error(translations.somethingWentWrong);
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + translations.send + '...');

        $.ajax({
            url: sendSmsBaseUrl + '/' + inspectionId + '/send-sms',
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                recipient: recipient
            },
            success: function(response) {
                $('#smsRecipientsModal').modal('hide');
                if (response.success) {
                    toastr.success(response.message || translations.success || 'Success');
                } else {
                    toastr.error(response.message || translations.somethingWentWrong);
                }
            },
            error: function(xhr) {
                var message = translations.somethingWentWrong;
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                toastr.error(message);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> ' + translations.send);
            }
        });
    });

    // Copy share link functionality
    $(document).on('click', '.js-copy-share-link', function() {
        var url = $(this).data('url');
        navigator.clipboard.writeText(url).then(function() {
            toastr.success('{{ __("checkcar::lang.link_copied") }}');
        }).catch(function() {
            // Fallback for older browsers
            var input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            toastr.success('{{ __("checkcar::lang.link_copied") }}');
        });
    });

    // View documents functionality - delegate for DataTable dynamic content
    $(document).on('click', '.js-view-documents', function() {
        var documentsCount = $(this).data('inspection-documents');
        var documentsUrl = $(this).data('documents-url');
        
        // Update modal title with document count
        $('#documentsModal .modal-title').html(
            '<i class="fa fa-file"></i> ' + translations.inspectionDocuments + ' (' + documentsCount + ')'
        );
        
        // Show loading state
        $('#documentsContainer').html(
            '<div class="text-center">' +
            '<i class="fa fa-spinner fa-spin fa-2x"></i>' +
            '<p class="mt-2">' + translations.loading + '...</p>' +
            '</div>'
        );
        
        // Show modal
        $('#documentsModal').modal('show');
        
        // Fetch documents using route-generated URL
        fetch(documentsUrl)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    renderDocuments(data.documents);
                } else {
                    showError(data.message || translations.somethingWentWrong);
                }
            })
            .catch(function(error) {
                console.error('Error fetching documents:', error);
                showError(translations.somethingWentWrong);
            });
    });

    function renderDocuments(documents) {
        var $container = $('#documentsContainer');
        
        if (!documents || documents.length === 0) {
            $container.html(
                '<div class="text-center text-muted py-5">' +
                '<i class="fa fa-file-o fa-3x mb-3"></i>' +
                '<p>' + translations.noDocumentsFound + '</p>' +
                '</div>'
            );
            return;
        }

        // Group documents by party
        var documentsByParty = {};
        $.each(documents, function(i, doc) {
            if (!documentsByParty[doc.party]) {
                documentsByParty[doc.party] = [];
            }
            documentsByParty[doc.party].push(doc);
        });

        var html = '';
        
        // Render documents for each party
        $.each(Object.keys(documentsByParty), function(i, party) {
            html += 
                '<div class="panel panel-default mb-3">' +
                '<div class="panel-heading">' +
                '<h4 class="panel-title">' +
                '<i class="fa fa-user"></i> ' +
                party.charAt(0).toUpperCase() + party.slice(1) + ' ' + translations.documents +
                '<span class="badge pull-right">' + documentsByParty[party].length + '</span>' +
                '</h4>' +
                '</div>' +
                '<div class="panel-body">' +
                '<div class="row">';
            
            $.each(documentsByParty[party], function(j, doc) {
                var isImage = doc.mime_type && doc.mime_type.indexOf('image') !== -1;
                var fileIcon = getFileIcon(doc.mime_type);
                var fileUrl = doc.url || (doc.file_path ? (doc.file_path.startsWith('http') ? doc.file_path : '/' + doc.file_path) : '#');
                
                html += 
                    '<div class="col-md-6 col-sm-12 mb-3">' +
                    '<div class="media">' +
                    '<div class="media-left">';

                if (isImage && fileUrl !== '#') {
                    html += '<img src="' + fileUrl + '" alt="document" style="max-width: 80px; max-height: 80px; border: 1px solid #ddd; border-radius: 3px; object-fit: cover;">';
                } else {
                    html += '<i class="fa ' + fileIcon + ' fa-2x text-muted"></i>';
                }

                html += '</div>' +
                    '<div class="media-body">' +
                    '<h5 class="media-heading">' + (doc.document_type || translations.documents) + '</h5>' +
                    '<small class="text-muted">' + (doc.mime_type || '') + '</small>';

                if (!isImage || fileUrl === '#') {
                    html += '<br>' +
                        '<a href="' + fileUrl + '" target="_blank" class="btn btn-xs btn-primary">' +
                        '<i class="fa fa-eye"></i> ' + translations.view +
                        '</a>' +
                        '<a href="' + fileUrl + '" download class="btn btn-xs btn-default">' +
                        '<i class="fa fa-download"></i> ' + translations.download +
                        '</a>';
                }

                html += '</div>' +
                    '</div>' +
                    '</div>';
            });
            
            html += 
                '</div>' +
                '</div>' +
                '</div>';
        });
        
        $container.html(html);
    }

    function getFileIcon(mimeType) {
        if (!mimeType) return 'fa-file-o';
        
        if (mimeType.includes('pdf')) return 'fa-file-pdf-o';
        if (mimeType.includes('image')) return 'fa-file-image-o';
        if (mimeType.includes('word') || mimeType.includes('document')) return 'fa-file-word-o';
        if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'fa-file-excel-o';
        if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return 'fa-file-powerpoint-o';
        if (mimeType.includes('text')) return 'fa-file-text-o';
        if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('7z')) return 'fa-file-archive-o';
        
        return 'fa-file-o';
    }

    function showError(message) {
        $('#documentsContainer').html(
            '<div class="alert alert-danger">' +
            '<i class="fa fa-exclamation-triangle"></i> ' + message +
            '</div>'
        );
    }

    // Open change car owner modal (orange button in actions column)
    $(document).on('click', '.js-change-car-owner', function() {
        var url = $(this).data('url');
        if (!url) {
            return;
        }

        $.get(url, function(html) {
            $('#change_car_owner_modal').remove();
            $('body').append(html);
            $('#change_car_owner_modal').modal('show');
        }).fail(function() {
            if (typeof toastr !== 'undefined') {
                toastr.error(translations.somethingWentWrong);
            }
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
                    if (inspectionsTable) {
                        inspectionsTable.ajax.reload(null, false);
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || translations.success || 'Success');
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
                        : translations.somethingWentWrong;
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
