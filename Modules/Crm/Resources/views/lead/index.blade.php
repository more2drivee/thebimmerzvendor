@extends('layouts.app')

@section('title', __('crm::lang.lead'))

@section('css')
<style>
    /* Fix for contact vehicles column */
    #leads_table td {
        vertical-align: top !important;
    }

    /* Fix for the contact vehicles column width */
    #leads_table th:nth-child(11),
    #leads_table td:nth-child(11) {
        width: 150px !important;
        max-width: 150px !important;
        min-width: 150px !important;
    }

    /* Make sure the button doesn't overflow */
    .contact_devices_btn {
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
@endsection

@section('content')
@include('crm::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header no-print">
   <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('crm::lang.leads')</h1>
</section>

<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('source', __('crm::lang.source') . ':') !!}
                    {!! Form::select('source', $sources, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'source', 'placeholder' => __('messages.all')]) !!}
                </div>
            </div>
            @if($lead_view != 'kanban')
                <div class="col-md-4">
                    <div class="form-group">
                         {!! Form::label('life_stage', __('crm::lang.life_stage') . ':') !!}
                        {!! Form::select('life_stage', $life_stages, null, ['class' => 'form-control select2', 'id' => 'life_stage', 'style' => 'width:100%', 'placeholder' => __('messages.all')]) !!}
                    </div>
                </div>
            @endif
            @if(count($users) > 0)
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('user_id', __('lang_v1.assigned_to') . ':') !!}
                    {!! Form::select('user_id', $users, null, ['class' => 'form-control select2', 'id' => 'user_id', 'style' => 'width:100%', 'placeholder' => __('messages.all')]) !!}
                </div>
            </div>
            @endif
        </div>
    @endcomponent
	@component('components.widget', ['class' => 'box-primary', 'title' => __('crm::lang.all_leads')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm btn-add-lead pull-right m-5" data-href="{{action([\Modules\Crm\Http\Controllers\LeadController::class, 'create'])}}" data-toggle="modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>

                <div class="btn-group btn-group-toggle pull-right m-5" data-toggle="buttons">
                    <label class="btn btn-info btn-sm active list">
                        <input type="radio" name="lead_view" value="list_view" class="lead_view" data-href="{{action([\Modules\Crm\Http\Controllers\LeadController::class, 'index']).'?lead_view=list_view'}}">
                        @lang('crm::lang.list_view')
                    </label>
                    <label class="btn btn-info btn-sm kanban">
                        <input type="radio" name="lead_view" value="kanban" class="lead_view" data-href="{{action([\Modules\Crm\Http\Controllers\LeadController::class, 'index']).'?lead_view=kanban'}}">
                        @lang('crm::lang.kanban_board')
                    </label>
                </div>
            </div>
        @endslot
        @if($lead_view == 'list_view')
        	<table class="table table-bordered table-striped" id="leads_table">
		        <thead>
		            <tr>
		                <th> @lang('messages.action')</th>
		                <th>@lang('lang_v1.contact_id')</th>
		                <th>@lang('contact.name')</th>
                        <th>@lang('contact.mobile')</th>
                        <th>@lang('crm::lang.source')</th>
                        <th style="width: 200px !important">
                            @lang('crm::lang.last_follow_up')
                        </th>
                        <th style="width: 200px !important">
                            @lang('crm::lang.upcoming_follow_up')
                        </th>
                        <th>@lang('crm::lang.contact_devices')</th>
                        <th>@lang('crm::lang.jobsheets')</th>
                        <th>@lang('crm::lang.transactions')</th>
                        <th>@lang('crm::lang.estimators')</th>
                        <th>@lang('lang_v1.added_on')</th>

		            </tr>
		        </thead>
                <tfoot>
                    <!-- Code commented temporarily as no relevant codes found -->
                    <!-- <tr class="bg-gray font-17 text-center footer-total">
                        <td colspan="23" class="text-left">
                            <button type="button" class="btn btn-xs btn-success update_contact_location" data-type="add">@lang('lang_v1.add_to_location')</button>
                                &nbsp;
                                <button type="button" class="btn btn-xs bg-navy update_contact_location" data-type="remove">@lang('lang_v1.remove_from_location')</button>
                        </td>
                    </tr> -->
                </tfoot>
		    </table>
        @endif
        @if($lead_view == 'kanban')
            <div class="lead-kanban-board">
                <div class="page">
                    <div class="main">
                        <div class="meta-tasks-wrapper">
                            <div id="myKanban" class="meta-tasks">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcomponent
    <div class="modal fade contact_modal" tabindex="-1" role="dialog"
    	aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade schedule" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade followup_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade transaction_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade jobsheet_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade contact_devices_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade edit_contact_device_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade estimator_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
@endsection
@section('javascript')
<!-- <script src="{{ asset('modules/crm/js/crm.js?v=' . $asset_v) }}"></script> -->
    <script type="text/javascript">
        // Add CSRF token to all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {
            var lead_view = urlSearchParam('lead_view');

            //if lead view is empty, set default to list_view
            if (_.isEmpty(lead_view)) {
                lead_view = 'list_view';
            }

            if (lead_view == 'kanban') {
                $('.kanban').addClass('active');
                $('.list').removeClass('active');
                initializeLeadKanbanBoard();
            } else if(lead_view == 'list_view') {
                initializeLeadDatatable();
            }

            // Initialize modal functionality for transaction and jobsheet links
            $(document).on('click', '.btn-modal', function(e) {
                e.preventDefault();
                var container = $(this).data('container');
                var href = $(this).data('href');

                // For estimator modal, capture source URL and extract estimator id
                var estId = null;
                if (container === '.estimator_modal') {
                    var idMatch = href.match(/job[_-]estimator\/(\d+)/);
                    if (idMatch && idMatch[1]) {
                        estId = idMatch[1];
                    }
                    $(container).attr('data-source-url', href).attr('data-estimator-id', estId || '');
                }

                $.ajax({
                    url: href,
                    dataType: 'html',
                    success: function(result) {
                        $(container).html(result).modal('show');

                        // After showing estimator modal, load lines section
                        if (container === '.estimator_modal' && estId) {
                            loadEstimatorLines(estId);
                        }
                    }
                });
            });

            // Fetch estimator details + lines and render a basic table inside the modal
            function loadEstimatorLines(estimatorId) {
                $.ajax({
                    url: '/crm/estimator/' + estimatorId + '/details',
                    type: 'GET',
                    dataType: 'json',
                    success: function(resp) {
                        if (!resp || resp.success !== true) { return; }
                        var $modal = $('.estimator_modal');
                        var $boxBody = $modal.find('.box-body');
                        if ($boxBody.length === 0) { return; }

                        // Build section container
                        var sectionHtml = '' +
                            '<div class="row tw-mt-4">' +
                              '<div class="col-sm-12">' +
                                '<h4>Estimator Lines</h4>' +
                                '<div class="table-responsive">' +
                                  '<table class="table table-striped table-condensed js-estimator-lines-table">' +
                                    '<thead>' +
                                      '<tr>' +
                                        '<th>Product</th>' +
                                        '<th>SKU</th>' +
                                        '<th>Qty</th>' +
                                        '<th>Unit</th>' +
                                        '<th>Price</th>' +
                                        '<th>Supplier</th>' +
                                        '<th>Approval</th>' +
                                        '<th>Notes</th>' +
                                      '</tr>' +
                                    '</thead>' +
                                    '<tbody></tbody>' +
                                  '</table>' +
                                '</div>' +
                              '</div>' +
                            '</div>';

                        // Avoid duplicating section on repeated opens
                        if ($modal.find('.js-estimator-lines-table').length === 0) {
                            $boxBody.append(sectionHtml);
                        }

                        var $tbody = $modal.find('.js-estimator-lines-table tbody');
                        $tbody.empty();
                        (resp.lines || []).forEach(function(line) {
                            var approval = (parseInt(line.client_approval, 10) === 1) ? 'Approved' : 'Pending';
                            var tr = '<tr>' +
                                '<td>' + (line.product_name || '') + '</td>' +
                                '<td>' + (line.sku || '') + '</td>' +
                                '<td>' + (line.quantity || 0) + '</td>' +
                                '<td>' + (line.unit || '') + '</td>' +
                                '<td>' + (line.price != null ? line.price : '') + '</td>' +
                                '<td>' + (line.supplier_name || '') + '</td>' +
                                '<td>' + approval + '</td>' +
                                '<td>' + (line.notes || '') + '</td>' +
                              '</tr>';
                            $tbody.append(tr);
                        });
                    },
                    error: function(xhr) {
                        console.error('Failed to load estimator details', xhr);
                    }
                });
            }

            // Handle contact devices button click
            $(document).on('click', '.contact_devices_btn', function() {
                var contact_id = $(this).data('contact_id');
                $.ajax({
                    url: '/crm/contact-devices/' + contact_id,
                    type: 'GET',
                    dataType: 'html',
                    success: function(result) {
                        $('.contact_devices_modal').html(result).modal('show');
                    }
                });
            });

            // Event delegation for the brand dropdown in the lead modal
            $(document).on('change', '#gehad_category_id', function() {
                var brandId = $(this).val(); // Get the selected brand ID

                if (!brandId) {
                    // Clear model dropdown if no brand is selected
                    $('#gehad_model_id').empty().append('<option value="">Select Model</option>');
                    return;
                }

                // Fetch models for the selected brand
                $.ajax({
                    url: "/bookings/get-models/" + brandId,
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        // Clear and rebuild the dropdown
                        var $dropdown = $('#gehad_model_id');
                        $dropdown.empty();
                        $dropdown.append('<option value="">Select Model</option>');

                        // Add all models from the response
                        if (response.length) {
                            $.each(response, function(index, model) {
                                $dropdown.append('<option value="' + model.id + '">' + model.name + '</option>');
                            });
                        } else {
                            // If no models are returned, show a "No models available" option
                            $dropdown.append('<option value="">No models available</option>');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching models for brand ' + brandId + ':', xhr);
                        toastr.error('Error loading models. Please try again.');
                    }
                });
            });

            // Handle follow-up button click to open in modal instead of redirecting
            $(document).on('click', 'a.follow-up-btn', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');

                // Load the follow-up form in a modal
                $.ajax({
                    url: url,
                    dataType: 'html',
                    success: function(result) {
                        $('.followup_modal').html(result).modal('show');

                        // Initialize form elements
                        $('.followup_modal .select2').select2({
                            dropdownParent: $('.followup_modal')
                        });

                        $('.followup_modal .datetimepicker').datetimepicker({
                            ignoreReadonly: true,
                            format: moment_date_format + ' ' + moment_time_format
                        });

                        // Set default values
                        // 1. Set status to first option if exists
                        var statusDropdown = $('.followup_modal select[name="status"]');
                        if (statusDropdown.length && statusDropdown.find('option').length > 1) {
                            statusDropdown.find('option:eq(1)').prop('selected', true).trigger('change');
                        }

                        // 2. Set schedule type to first option if exists
                        var scheduleTypeDropdown = $('.followup_modal select[name="schedule_type"]');
                        if (scheduleTypeDropdown.length && scheduleTypeDropdown.find('option').length > 1) {
                            scheduleTypeDropdown.find('option:eq(1)').prop('selected', true).trigger('change');
                        }

                        // 3. Set followup category to first option if exists
                        var followupCategoryDropdown = $('.followup_modal select[name="followup_category_id"]');
                        if (followupCategoryDropdown.length && followupCategoryDropdown.find('option').length > 1) {
                            followupCategoryDropdown.find('option:eq(1)').prop('selected', true).trigger('change');
                        }

                        // 4. Set start and end datetime to current date/time
                        var currentDateTime = moment().format(moment_date_format + ' ' + moment_time_format);
                        $('.followup_modal input[name="start_datetime"]').val(currentDateTime);
                        $('.followup_modal input[name="end_datetime"]').val(currentDateTime);

                        // 5. Set assigned to dropdown to first user if exists
                        var assignedToDropdown = $('.followup_modal select[name="user_id[]"]');
                        if (assignedToDropdown.length && assignedToDropdown.find('option').length > 1) {
                            // Clear any existing selections
                            assignedToDropdown.val(null).trigger('change');
                            // Select the first user (option index 1, after the placeholder)
                            var firstUserId = assignedToDropdown.find('option:eq(1)').val();
                            if (firstUserId) {
                                assignedToDropdown.val(firstUserId).trigger('change');
                            }
                        }

                        // Initialize editor for description
                        tinymce.init({
                            selector: '.followup_modal textarea#description'
                        });
                    }
                });
            });

            // Handle save button click in follow-up modal
            $(document).on('click', '.followup_modal .btn-primary:contains("Save")', function(e) {
                // Check if notification is allowed and show the elements before saving
                if ($('.followup_modal #allow_notification').is(':checked')) {
                    $('.followup_modal .allow_notification_elements').removeClass('hide');
                }
            });

            // Clean up when the modal is closed
            $(document).on('hidden.bs.modal', '.followup_modal', function() {
                if (typeof tinymce !== 'undefined') {
                    tinymce.remove('.followup_modal textarea#description');
                }
            });

            // Handle view follow-up modal button click
            $(document).on('click', '.view-followup-modal', function(e) {
                e.preventDefault();
                var lead_id = $(this).data('lead_id');
                // You may need to adjust the URL to your actual follow-up log endpoint
                $.ajax({
                    url: '/crm/lead/' + lead_id + '/followup-log',
                    dataType: 'html',
                    success: function(result) {
                        $('.followup_modal').html(result).modal('show');
                    },
                    error: function() {
                        toastr.error('Could not load follow up data.');
                    }
                });
            });

        });
    </script>
    <script>
        $(document).ready(function(){
	/**
	 * CRM MODULE
	 * contact login related code
	 */
	all_contact_login_datatable = $("#all_contact_login_table").DataTable({
            processing: true,
            serverSide: true,
            'ajax': {
                url: "/crm/contact-login",
                data: function (d) {
                    if ($("#contact_id").length > 0) {
                    	d.crm_contact_id = $("#contact_id").val();
                    }
                }
            },
            columns: [
                { data: 'action', name: 'action', searchable: false, sortable: false },
                { data: 'contact', name: 'contact', searchable: false, sortable: false },
                { data: 'username', name: 'username' },
                { data: 'name', name: 'name', searchable: false, sortable: false },
                { data: 'email', name: 'email' },
                { data: 'crm_department', name: 'crm_department' },
                { data: 'crm_designation', name: 'crm_designation' }
            ],
	});

	contact_login_datatable = $("#contact_login_table").DataTable({
            processing: true,
            serverSide: true,
            'ajax': {
                url: "/crm/contact-login",
                data: function (d) {
                    d.contact_id = $('input#contact_id_for_login').val();
                }
            },
            columns: [
					{ data: 'action', name: 'action', searchable: false, sortable: false },
	                { data: 'username', name: 'username' },
	                { data: 'name', name: 'name', searchable: false, sortable: false },
	                { data: 'email', name: 'email' },
	                { data: 'crm_department', name: 'crm_department' },
	            	{ data: 'crm_designation', name: 'crm_designation' }
	            ]
	});

	$(document).on('change', '#contact_id', function() {
		all_contact_login_datatable.ajax.reload();
	});

	$(document).on('click', '.contact-login-add', function () {
	    var url = $(this).data('href');
	    var data = {
	    			contact_id : $('input#contact_id_for_login').val(),
	    			crud_type: $("input#login_view_type").val()
	    		};
	    $.ajax({
	        method: 'GET',
	        url: url,
	        dataType: 'html',
	        data: data,
	        success: function(result) {
	            $('.contact_login_modal').html(result).modal('show');
	        }
	    });
	});

	$('.contact_login_modal').on('shown.bs.modal', function (e) {
	    $('.input-icheck').iCheck({
	        checkboxClass: 'icheckbox_square-blue'
	    });

	    if ($('form#contact_login_add').length > 0) {
	        $("form#contact_login_add").validate({
	            rules: {
	                first_name: {
	                    required: true,
	                },
	                email: {
	                    email: true,
	                    remote: {
	                        url: "/business/register/check-email",
	                        type: "post",
	                        data: {
	                            email: function() {
	                                return $( "#email" ).val();
	                            }
	                        }
	                    }
	                },
	                password: {
	                    required: true,
	                    minlength: 5
	                },
	                confirm_password: {
	                    equalTo: "#password"
	                },
	                username: {
	                    minlength: 5,
	                    remote: {
	                        url: "/business/register/check-username",
	                        type: "post",
	                        data: {
	                            username: function() {
	                                return $( "#username" ).val();
	                            }
	                        }
	                    }
	                }
	            },
	            messages: {
	                password: {
	                    minlength: 'Password should be minimum 5 characters',
	                },
	                confirm_password: {
	                    equalTo: 'Should be same as password'
	                },
	                username: {
	                    remote: 'Invalid username or User already exist'
	                },
	                email: {
	                    remote: 'Email already exists'
	                }
	            }
	        });
	    }

	    if ($('form#contact_login_edit').length > 0) {
	        $("form#contact_login_edit").validate({
	            rules: {
	                first_name: {
	                    required: true,
	                },
	                email: {
	                    email: true,
	                    remote: {
	                        url: "/business/register/check-email",
	                        type: "post",
	                        data: {
	                            email: function() {
	                                return $( "#email" ).val();
	                            },
	                            user_id: $('input#user_id').val()
	                        }
	                    }
	                },
	                password: {
	                    minlength: 5
	                },
	                confirm_password: {
	                    equalTo: "#password"
	                }
	            },
	            messages: {
	                password: {
	                    minlength: 'Password should be minimum 5 characters',
	                },
	                confirm_password: {
	                    equalTo: 'Should be same as password'
	                },
	                email: {
	                    remote: '{{ __("validation.unique", ["attribute" => __("business.email")]) }}'
	                }
	            }
	        });
	    }
	});

	$(document).on('submit', 'form#contact_login_add', function(e) {
	    e.preventDefault();
	    var data = $('form#contact_login_add').serialize();
	    var url = $('form#contact_login_add').attr('action');
	    $.ajax({
	        method: 'POST',
	        url: url,
	        dataType: 'json',
	        data: data,
	        success: function(result) {
	            if (result.success) {
	                $('.contact_login_modal').modal('hide');
	                toastr.success(result.msg);
	                all_contact_login_datatable.ajax.reload();
	                contact_login_datatable.ajax.reload();
	            } else {
	                toastr.error(result.msg);
	            }
	        }
	    });
	});

	$(document).on('click', '#delete_contact_login', function(e) {
	    e.preventDefault();
	    var url = $(this).data('href');

	    swal({
	        title: LANG.sure,
	        icon: "warning",
	        buttons: true,
	        dangerMode: true,
	    }).then((confirmed) => {
	        if (confirmed) {
	            $.ajax({
	                method: 'DELETE',
	                url: url,
	                dataType: 'json',
	                success: function(result) {
	                    if (result.success) {
	                        toastr.success(result.msg);
	                        all_contact_login_datatable.ajax.reload();
	                        contact_login_datatable.ajax.reload();
	                    } else {
	                        toastr.error(result.msg);
	                    }
	                }
	            });
	        }
	    });
	});

	$(document).on('click', '.edit_contact_login', function() {
	    var url = $(this).data('href');
	    var data = {
	    			crud_type: $("input#login_view_type").val()
	    		};
	    $.ajax({
	        method: 'GET',
	        url: url,
	        dataType: 'html',
	        data: data,
	        success: function(result) {
	            $('.contact_login_modal').html(result).modal('show');
	        }
	    });
	});

	$(document).on('submit', 'form#contact_login_edit', function(e) {
	    e.preventDefault();
	    var data = $('form#contact_login_edit').serialize();
	    var url = $('form#contact_login_edit').attr('action');
	    $.ajax({
	        method: 'PUT',
	        url: url,
	        dataType: 'json',
	        data: data,
	        success: function(result) {
	            if (result.success) {
	                $('.contact_login_modal').modal('hide');
	                toastr.success(result.msg);
	                all_contact_login_datatable.ajax.reload();
	                contact_login_datatable.ajax.reload();
	            } else {
	                toastr.error(result.msg);
	            }
	        }
	    });
	});

	/**
	* Crm Ledger
	* related code
	*/

	$('#ledger_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#ledger_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        }
    );

    $('#ledger_date_range').change( function(){
        getLedger();
    });

	$(document).on('click', '#create_ledger_pdf', function() {
	    var start_date = $('#ledger_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
	    var end_date = $('#ledger_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');

	    var url = $(this).data('href') + '&start_date=' + start_date + '&end_date=' + end_date;
	    window.location = url;
	});

	/**
	* Crm Profile
	* edit/update related
	* code
	*/
	$('form#update_password').validate({
		errorPlacement: function(error, element) {
            if (element.parent('.input-group').length) {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element);
            }
        },
        rules: {
            current_password: {
                required: true,
                minlength: 5,
            },
            new_password: {
                required: true,
                minlength: 5,
            },
            confirm_password: {
                equalTo: '#new_password',
            },
        },
    });

    $("form#edit_contact_profile").validate({
    	errorPlacement: function(error, element) {
            if (element.parent('.input-group').length) {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element);
            }
        },
        rules: {
            first_name: {
                required: true,
            },
            email: {
                email: true,
                remote: {
                    url: "/business/register/check-email",
                    type: "post",
                    data: {
                        email: function() {
                            return $( "#email" ).val();
                        },
                        user_id: $('input#user_id').val()
                    }
                }
            }
        },
        messages: {
            email: {
                remote: '{{ __("validation.unique", ["attribute" => __("business.email")]) }}'
            }
        }
    });

	/**
	* Crm Purchase
	* related code
	*/
	$('#date_range_filter').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
           contact_purchase_datatable.ajax.reload();
        }
    );

	$('#date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#date_range_filter').val('');
        contact_purchase_datatable.ajax.reload();
    });

	contact_purchase_datatable = $("#contact_purchase_table").DataTable({
		processing: true,
		serverSide: true,
		ajax: {
        url: '/contact/contact-purchases',
        data: function(d) {
            if ($('#payment_status_filter').length) {
                d.payment_status = $('#payment_status_filter').val();
            }
            if ($('#status_filter').length) {
                d.status = $('#status_filter').val();
            }

            var start = '';
            var end = '';
            if ($('#date_range_filter').val()) {
                start = $('input#date_range_filter')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');
                end = $('input#date_range_filter')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');
            }
            d.start_date = start;
            d.end_date = end;
            },
        },
        aaSorting: [[1, 'desc']],
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'status', name: 'status' },
            { data: 'payment_status', name: 'payment_status' },
            { data: 'final_total', name: 'final_total' },
            { data: 'payment_due', name: 'payment_due', orderable: false, searchable: false },
            { data: 'added_by', name: 'u.first_name' },
        ],
        fnDrawCallback: function(oSettings) {
            var total_purchase = sum_table_col($('#contact_purchase_table'), 'final_total');
            $('#footer_purchase_total').text(total_purchase);

            var total_due = sum_table_col($('#contact_purchase_table'), 'payment_due');
            $('#footer_total_due').text(total_due);

            var total_purchase_return_due = sum_table_col($('#contact_purchase_table'), 'purchase_return');
            $('#footer_total_purchase_return_due').text(total_purchase_return_due);

            $('#footer_status_count').html(__sum_status_html($('#contact_purchase_table'), 'status-label'));

            $('#footer_payment_status_count').html(
                __sum_status_html($('#contact_purchase_table'), 'payment-status-label')
            );

            __currency_convert_recursively($('#contact_purchase_table'));
        },
        createdRow: function(row, data, dataIndex) {
            $(row)
                .find('td:eq(3)')
                .attr('class', 'clickable_td');
        },

	});

	$(document).on('change', '#status_filter, #payment_status_filter', function() {
        contact_purchase_datatable.ajax.reload();
    });

	/**
	* Crm schedule
	* related code
	*/
	$(document).on('click', '.btn-add-schedule', function() {
	    load_schedule_modal();
	});

	function load_schedule_modal() {
		var url = $("#schedule_create_url").val();
		$.ajax({
	        method: 'GET',
	        url: url,
	        async: false,
	        dataType: 'html',
	        success: function(result) {
	            $('.schedule').html(result).modal('show');
	        }
	    });
	}

	$('.schedule').on('show.bs.modal', function (event) {
		$('form#add_schedule').validate();

		$('form#add_schedule .datetimepicker').datetimepicker({
	        ignoreReadonly: true,
	        format: moment_date_format + ' ' + moment_time_format
	    });

	    $(".select2").select2({
			dropdownParent: $(".schedule")
	  	});

	    $('input[type="checkbox"].input-icheck').iCheck({
	        checkboxClass: 'icheckbox_square-blue',
	    });

	    //initialize editor
	    tinymce.init({
	        selector: 'textarea#description',
	    });

	    $(document).on('ifChecked', '#allow_notification', function() {
	    	$("div").find('.allow_notification_elements').removeClass('hide');
	    });

	    $(document).on('ifUnchecked', '#allow_notification', function() {
	       $("div").find('.allow_notification_elements').addClass('hide');
	    });
	});

	$('.schedule').on('hidden.bs.modal', function(){
	    tinymce.remove("textarea#description");
	});

	$(document).on('submit', 'form#add_schedule', function(e){
	    e.preventDefault();
	    var url = $('form#add_schedule').attr('action');
	    var method = $('form#add_schedule').attr('method');
	    var data = $('form#add_schedule').serialize();
	    $.ajax({
	        method: method,
	        dataType: "json",
	        url: url,
	        data:data,
	        success: function(result){
	            if (result.success) {
	                $('.schedule').modal("hide");
	                toastr.success(result.msg);
	                if (result.schedule_for == 'lead') {
	                	initializeLeadScheduleDatatable();
	                }

	                if (typeof(follow_up_datatable) != 'undefined') {
					    follow_up_datatable.ajax.reload();
					}

	                if (typeof(leads_datatable) != 'undefined') {
					    leads_datatable.ajax.reload();
					}
	            } else {
	                toastr.error(result.msg);
	            }
	        }
	    });
	});

	$(document).on('click', '.schedule_delete', function(e) {
		e.preventDefault();
		var url = $(this).data('href');
		var view_type = $("#view_type").val();
		var data = {'view_type' : view_type};
		swal({
	        title: LANG.sure,
	        icon: "warning",
	        buttons: true,
	        dangerMode: true,
	    }).then((confirmed) => {
	        if (confirmed) {
	            $.ajax({
	                method: 'DELETE',
	                url: url,
	                dataType: 'json',
	                data: data,
	                success: function(result) {
	                    if (result.success) {
	                        toastr.success(result.msg);
	                        if (result.view_type == 'lead_info') {
	                        	initializeLeadScheduleDatatable();
	                        } else if (result.view_type == 'schedule_info') {
	                        	setTimeout(() => {
	                        		location.replace(result.action);
								}, 6000);
                            }

	                        if (typeof(follow_up_datatable) != 'undefined') {
							    follow_up_datatable.ajax.reload();
							}

							if (typeof(recursive_follow_up_table) != 'undefined') {
							    recursive_follow_up_table.ajax.reload();
							}
	                    } else {
	                        toastr.error(result.msg);
	                    }
	                }
	            });
	        }
	    });
	});

	$(document).on('click', '.schedule_edit', function() {
		var url = $(this).data('href');
		var schedule_for = $("#schedule_for").val();
		var data = {'schedule_for' : schedule_for};
	    $.ajax({
	        method: 'GET',
	        url: url,
	        dataType: 'html',
	        data: data,
	        success: function(result) {
	            $('.edit_schedule').html(result).modal('show');
	        }
	    });
	});

	$('.edit_schedule').on('show.bs.modal', function (event) {
		$('form#edit_schedule').validate();

		$('form#edit_schedule .datetimepicker').datetimepicker({
	        ignoreReadonly: true,
	        format: moment_date_format + ' ' + moment_time_format
	    });

	    $(".select2").select2();

	    $('input[type="checkbox"].input-icheck').iCheck({
	        checkboxClass: 'icheckbox_square-blue',
	    });

	    //initialize editor
	    tinymce.init({
	        selector: 'textarea#schedule_description',
	    });

	});

	$('.edit_schedule').on('hidden.bs.modal', function(){
	    tinymce.remove("textarea#schedule_description");
	});

	$(document).on('submit', 'form#edit_schedule', function(e){
	    e.preventDefault();
	    var url = $('form#edit_schedule').attr('action');
	    var method = $('form#edit_schedule').attr('method');
	    var data = $('form#edit_schedule').serialize();
	    $.ajax({
	        method: method,
	        dataType: "json",
	        url: url,
	        data:data,
	        success: function(result){
	            if (result.success) {
	                $('.edit_schedule').modal("hide");
	                toastr.success(result.msg);
	                if (result.schedule_for == 'lead') {
	                	initializeLeadScheduleDatatable();
	                } else if(result.schedule_for == 'schedule_info'){
	                	location.reload();
	                }

	                if (typeof(follow_up_datatable) != 'undefined') {
					    follow_up_datatable.ajax.reload();
					}
	            } else {
	                toastr.error(result.msg);
	            }
	        }
	    });
	});


	/**
	* Crm schedule log
	* related code
	*/
	$(document).on('click', '.schedule_log_add, .add-schedule-log', function(e) {
		e.preventDefault();
		var url = $(this).data('href');
		if (typeof url == 'undefined') {
			url = $(this).attr('href');
		}
	    $.ajax({
	        method: 'GET',
	        url: url,
	        dataType: 'html',
	        success: function(result) {
	            $('.schedule_log_modal').html(result).modal('show');
	        }
	    });
	});

	$(document).on('click', '.edit_schedule_log', function() {
		var url = $(this).data('href');
	    $.ajax({
	        method: 'GET',
	        url: url,
	        dataType: 'html',
	        success: function(result) {
	            $('.schedule_log_modal').html(result).modal('show');
	        }
	    });
	});

	$('.schedule_log_modal').on('show.bs.modal', function (event) {
		$('form#schedule_log_form').validate();

		$('form#schedule_log_form .datetimepicker').datetimepicker({
	        ignoreReadonly: true,
	        format: moment_date_format + ' ' + moment_time_format
	    });

	    $(".select2").select2();

	    //initialize editor
	    tinymce.init({
	        selector: 'textarea#description',
	    });
	});

	$('.schedule_log_modal').on('hide.bs.modal', function(){
	    tinymce.remove("textarea#description");
	});

	$(document).on('submit', 'form#schedule_log_form', function(e){
	    e.preventDefault();
	    var url = $('form#schedule_log_form').attr('action');
	    var method = $('form#schedule_log_form').attr('method');
	    var data = $('form#schedule_log_form').serialize();
	    $.ajax({
	        method: method,
	        dataType: "json",
	        url: url,
	        data:data,
	        success: function(result){
	            if (result.success) {
	                $('.schedule_log_modal').modal("hide");
	                toastr.success(result.msg);
	                getScheduleLog($("input#schedule_id").val());
	            } else {
	                toastr.error(result.msg);
	            }
	        }
	    });
	});

	$(document).on('click', '.delete_schedule_log', function(e) {
		e.preventDefault();
		var url = $(this).data('href');
		swal({
	        title: LANG.sure,
	        icon: "warning",
	        buttons: true,
	        dangerMode: true,
	    }).then((confirmed) => {
	        if (confirmed) {
	            $.ajax({
	                method: 'DELETE',
	                url: url,
	                dataType: 'json',
	                success: function(result) {
	                    if (result.success) {
	                        toastr.success(result.msg);
	                        getScheduleLog($("input#schedule_id").val());
	                    } else {
	                        toastr.error(result.msg);
	                    }
	                }
	            });
	        }
	    });
	});

	$(document).on('click', '.view_a_schedule_log', function() {
		var url = $(this).data('href');
	    $.ajax({
	        method: 'GET',
	        url: url,
	        dataType: 'html',
	        success: function(result) {
	            $('.view_modal').html(result).modal('show');
	        }
	    });
	});

	$(document).on('click', '.load_more_log', function() {
	    var url = $(this).data('href');
	    var data = {schedule_id : $("input#schedule_id").val()};
	    $.ajax({
	        method:'GET',
	        dataType: 'json',
	        url: url,
	        data:data,
	        success: function(result){
	            if (result.success) {
	                $('.load_more_log').hide();
	                $(".timeline").append(result.log);
	            } else {
	                toastr.error(result.msg);
	            }
	        }
	    });
	});

	/**
	* Crm lead
	* related code
	*/
	$(document).on('click', '.btn-add-lead', function() {
		var url = $(this).data('href');
		$.ajax({
	        method: 'GET',
	        url: url,
	        dataType: 'html',
	        success: function(result) {
	            $('.contact_modal').html(result).modal('show');

	            // Initialize vehicle dropdown toggle button
	            $("#toggleVehicleBtn").off('click').on("click", function() {
	                $("#vehicle_div").slideToggle();
	                $(this).find("i").toggleClass("fa-chevron-down fa-chevron-up");
	            });

	            // Initialize more info toggle button
	            $("#toggleMoreInfo").off('click').on("click", function() {
	                $("#more_div").slideToggle();
	                $(this).find("i").toggleClass("fa-chevron-down fa-chevron-up");
	            });
	        }
	    });
	});

	$(document).on('click', '.delete_a_lead', function(e) {
		e.preventDefault();
		var url = $(this).data('href');
		swal({
	        title: LANG.sure,
	        icon: "warning",
	        buttons: true,
	        dangerMode: true,
	    }).then((confirmed) => {
	        if (confirmed) {
	            $.ajax({
	                method: 'DELETE',
	                url: url,
	                dataType: 'json',
	                success: function(result) {
	                    if (result.success) {
	                        toastr.success(result.msg);

	                        var lead_view = urlSearchParam('lead_view');

							if (lead_view == 'kanban') {
							    initializeLeadKanbanBoard();
							} else if(lead_view == 'list_view') {
							    leads_datatable.ajax.reload();
							}

	                    } else {
	                        toastr.error(result.msg);
	                    }
	                }
	            });
	        }
	    });
	});

	$(document).on('click', '.convert_to_customer', function() {
    	var url = $(this).data('href');
		$.ajax({
	        method: 'GET',
	        url: url,
	        dataType: 'json',
	        success: function(result) {
	            if (result.success) {
                    toastr.success(result.msg);
                    leads_datatable.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
	        }
	    });
    });

    $(document).on('click', '.edit_lead', function() {
        var url = $(this).data('href');
        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html',
            success: function(result) {
                $('.contact_modal').html(result).modal('show');

                // Initialize vehicle dropdown toggle button
                $("#toggleVehicleBtn").off('click').on("click", function() {
                    $("#vehicle_div").slideToggle();
                    $(this).find("i").toggleClass("fa-chevron-down fa-chevron-up");
                });

                // Initialize more info toggle button
                $("#toggleMoreInfo").off('click').on("click", function() {
                    $("#more_div").slideToggle();
                    $(this).find("i").toggleClass("fa-chevron-down fa-chevron-up");
                });
            }
        });
    });

    $(document).on('change', "#life_stage, #source, #user_id", function(){
	   var lead_view = urlSearchParam('lead_view');
		if (lead_view == 'kanban') {
		    initializeLeadKanbanBoard();
		} else if(lead_view == 'list_view') {
		    leads_datatable.ajax.reload();
		}
	});

    $(document).on('change', '.lead_view', function() {
	    window.location.href = $(this).data('href');
	});

	KanbanBoard.prototype.initLeadKanban = function (boards, jKanbanElemSelector) {
	    var _this = this;
	    var kanban = new jKanban({
        element: jKanbanElemSelector,
        gutter: '5px',
        widthBoard: '320px',
        dragBoards: false,
        click: function (el) {
            //TODO: implement card clickable
            // _this.listApi.
            // getCard(el.dataset.eid).
            // then(_this.openCardModal.bind(_this));
        },
        dragEl: function (el, source) {
            $(el).addClass('dragging');
            isDraggingCard = true;
        },
        dragendEl: function (el) {
            $(el).removeClass('dragging');
            isDraggingCard = false;
        },
        dropEl: function (el, target, source, sibling) {
            var $el = $(el);

            $el.closest('.kanban-drag')[0]._ps.update();

            var $newParentLifeStage = $(target).parent('div.kanban-board').data('id');
            var $lifeStage = $(el).attr('data-parentid');

            //CRM MODULE:update life stage of lead in db
            if (!$('div.lead-kanban-board').hasClass('hide')) {
                if ($newParentLifeStage !== $lifeStage) {
                    var data = {
                        crm_life_stage : $newParentLifeStage,
                        lead_id: $(el).data('eid')
                    };

                    updateLeadLifeStageForKanban(data, $el);
                }
            }
        },

        addItemButton: false,
        boards: boards
	    });

	    initializeAutoScrollOnKanbanWhileCardDragging(kanban);

	    return kanban;
	};

	function updateLeadLifeStageForKanban(data, el) {
		$.ajax({
	        method: 'GET',
	        dataType: 'json',
	        url: '/crm/lead/' + data.lead_id + '/post-life-stage',
	        data:data,
	        success: function(result){
	            if (result.success) {
	                $(el).attr('data-parentid', data.crm_life_stage);
	                toastr.success(result.msg);
	            } else {
	                toastr.error(result.msg);
	            }
	        }
	    });
	}

	/**
	 * CRM MODULE
	 * campaign related code
	 */
	$(document).on('click', '.delete_a_campaign', function(e) {
	    e.preventDefault();
	    var url = $(this).data('href');
	    swal({
	        title: LANG.sure,
	        icon: "warning",
	        buttons: true,
	        dangerMode: true,
	    }).then((confirmed) => {
	        if (confirmed) {
	            $.ajax({
	                method: 'DELETE',
	                url: url,
	                dataType: 'json',
	                success: function(result) {
	                    if (result.success) {
	                        toastr.success(result.msg);
	                        campaigns_datatable.ajax.reload();
	                    } else {
	                        toastr.error(result.msg);
	                    }
	                }
	            });
	        }
	    });
	});

	$(document).on('change', '#campaign_type_filter', function() {
	    campaigns_datatable.ajax.reload();
	});

	$('.campaign_modal').on('hidden.bs.modal', function(){
	    tinymce.remove("textarea#email_body");
	});

	if ($('form#campaign_form').length) {
		$('form#campaign_form').validate({
			rules: {
				'contact_id[]': {
					required: true
				},
				'lead_id[]': {
					required: true
				}
			},
			submitHandler: function(form) {
				if ($(form).valid()) {
					form.submit();
					$(".submit-button").prop( "disabled", true );
				}
			}
		});
		$(".select2").select2();

	    tinymce.init({
	        selector: 'textarea#email_body'
	    });

	    if ($('select#campaign_type').val() == 'sms') {
            $('div.email_div').hide();
            $('div.sms_div').show();
        } else if ($('select#campaign_type').val() == 'email') {
            $('div.email_div').show();
            $('div.sms_div').hide();
        }

        $('select#campaign_type').change(function() {
            var campaign_type = $(this).val();
            if (campaign_type == 'sms') {
                $('div.sms_div').show();
                $('div.email_div').hide();
            } else if (campaign_type == 'email') {
                $('div.email_div').show();
                $('div.sms_div').hide();
            }
        });
	}

	$(document).on('click', '.send_campaign_notification', function() {
		var url = $(this).data('href');
		$.ajax({
	        method: 'GET',
	        url: url,
	        dataType: 'json',
	        success: function(result) {
	            if (result.success) {
	                toastr.success(result.msg);
	                campaigns_datatable.ajax.reload();
	            } else {
	                toastr.error(result.msg);
	            }
	        }
	    });
	});

	$(document).on('click', '.view_a_campaign', function() {
		var url = $(this).data('href');
	    $.ajax({
	        method: 'GET',
	        url: url,
	        dataType: 'html',
	        success: function(result) {
	            $('.campaign_view_modal').html(result).modal('show');
	        }
	    });
	});
});

/**
 * CRM MODULE
 * Code after Document ready
 */

function get_todays_schedule() {
	$.ajax({
        method: 'GET',
        dataType: "json",
        url: '/crm/todays-follow-ups',
        success: function(result){
            if (result.success) {
                $(".todays_schedule_table").html(result.todays_schedule);
            } else {
                toastr.error(result.msg);
            }
        }
    });
}

function initializeLeadScheduleDatatable() {
	if((typeof lead_schedule_datatable == 'undefined')) {
		lead_schedule_datatable = $("#lead_schedule_table").DataTable({
				processing: true,
		        serverSide: true,
		        ajax: {
		            url: "/crm/lead-follow-ups",
		            data:function(d) {
		            	d.lead_id = $("#lead_id").val();
		            }
		        },
		        columnDefs: [
		            {
		                targets: [0, 6],
		                orderable: false,
		                searchable: false,
		            },
		        ],
		        aaSorting: [[1, 'desc']],
		        columns: [
		            { data: 'action', name: 'action' },
		            { data: 'title', name: 'title' },
		            { data: 'status', name: 'status' },
		            { data: 'schedule_type', name: 'schedule_type' },
		            { data: 'start_datetime', name: 'start_datetime' },
		            { data: 'end_datetime', name: 'end_datetime' },
		            { data: 'users', name: 'users' },
		        ],
		        "fnDrawCallback": function( oSettings ) {
		        	$('a.view_schedule_log').click(function(){
		        		getScheduleLog($(this).data('schedule_id'), true);
		        	})
			    },
			});
	} else {
        lead_schedule_datatable.ajax.reload();
    }
}

function initializeCampaignDatatable() {
	if((typeof campaigns_datatable == 'undefined')) {
		campaigns_datatable = $("#campaigns_table").DataTable({
			processing: true,
		        serverSide: true,
		        ajax: {
		            url: "/crm/campaigns",
		            data:function(d) {
		            	d.campaign_type = $("#campaign_type_filter").val();
		            }
		        },
		        columnDefs: [
		            {
		                targets: [0, 3],
		                orderable: false,
		                searchable: false,
		            },
		        ],
		        aaSorting: [[4, 'desc']],
		        columns: [
		            { data: 'action', name: 'action' },
		            { data: 'name', name: 'name' },
		            { data: 'campaign_type', name: 'campaign_type' },
		            { data: 'createdBy', name: 'createdBy' },
		            { data: 'created_at', name: 'created_at' },
		        ]
		});
	} else {
    	campaigns_datatable.ajax.reload();
	}
}

function getLedger() {

    var start_date = '';
    var end_date = '';

    if($('#ledger_date_range').val()) {
        start_date = $('#ledger_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
        end_date = $('#ledger_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
    }
    $.ajax({
        url: '/contact/contact-get-ledger?start_date=' + start_date +'&end_date=' + end_date,
        dataType: 'html',
        success: function(result) {
            $('#contact_ledger_div')
                .html(result);
            __currency_convert_recursively($('#contact_ledger_div'));

            $('#ledger_table').DataTable({
                searching: false,
                ordering:false,
                paging:false,
                dom: 't'
            });
        },
    });
}

function getScheduleLog(schedule_id, modal_content = false) {
	var data = {schedule_id : schedule_id, modal_content: modal_content};
	$.ajax({
        method: 'GET',
        url: '/crm/follow-up-log',
        dataType: 'json',
        data:data,
        success: function(result) {
            if (result.success) {

            	if(modal_content){
            		$('div.schedule_log_modal').html(result.log).modal('show');
            	}else{
            		$(".followup_timeline").html(result.log);
            	}
            } else {
                toastr.error(result.msg);
            }
        }
    });
}

function initializeLeadDatatable() {
	if((typeof leads_datatable == 'undefined')) {

		leads_datatable = $("#leads_table").DataTable({
				processing: true,
		        serverSide: true,
		        scrollY: "75vh",
		        scrollX: true,
		        scrollCollapse: true,
                columnDefs: [
                    {
                        width: "150px",
                        targets: [7, 10], // Contact Vehicles & Estimators columns
                        className: "text-center"
                    }
                ],
		        ajax: {
		            url: "/crm/leads",
		            data:function(d) {
		            	d.source = $("#source").val();
		            	d.life_stage = $("#life_stage").val();
		            	d.user_id = $("#user_id").val();
		            	d.lead_view = urlSearchParam('lead_view');
		            }
		        },
		        columnDefs: [
		            {
		                targets: [0, 4, 7, 8, 9, 10],
		                orderable: false,
		                searchable: false,
		            },
		        ],
		        aaSorting: [[6, 'desc']],
		        columns: [
		            { data: 'action', name: 'action' },
		            { data: 'contact_id', name: 'contact_id' },
		            { data: 'name', name: 'name' },
		            { data: 'mobile', name: 'mobile' },
		            { data: 'crm_source', name: 'crm_source' },
		            { data: 'last_follow_up', name: 'last_follow_up', searchable: false},
		            { data: 'upcoming_follow_up', name: 'upcoming_follow_up', searchable: false},
		            { data: 'contact_devices', name: 'contact_devices', searchable: false, orderable: false },
		            { data: 'jobsheets', name: 'jobsheets', searchable: false, orderable: false },
		            { data: 'transactions', name: 'transactions', searchable: false, orderable: false },
		            { data: 'estimators', name: 'estimators', searchable: false, orderable: false },
		            { data: 'created_at', name: 'created_at' },

		        ]
			});
	} else {
        leads_datatable.ajax.reload();
    }
}

function initializeLeadKanbanBoard() {
	//before creating kanban, set div to empty.
    $('div#myKanban').html('');
    lists = getLeadListForKanban();

    KanbanBoard.prototype.run = function () {
        var _this = this;
        _this.lists = lists;
        var boards = lists.
        map(function (l) {return _this.listToKanbanBoard(l);}).
        map(function (b) {return _this.processBoard(b);});
        var kanbanTest = _this.initLeadKanban(boards, '#myKanban');
        $('.meta-tasks').each(function (i, el) {
            return new PerfectScrollbar(el, { useBothWheelAxes: true });
        });

        // _this.setupUI(kanbanTest);
    };

    new KanbanBoard().run();
}

function getLeadListForKanban() {
	var lead_view = urlSearchParam('lead_view');
    var data = {
        source : $("#source").val(),
        lead_view : lead_view
    };

    var kanbanDataSet = [];
    $.ajax({
        method: 'GET',
        dataType: 'json',
        async: false,
        url: '/crm/leads',
        data: data,
        success: function(result) {
            if (result.success) {
                kanbanDataSet = result.leads_html;
            } else {
                toastr.error(result.msg);
            }
        }
    });

    return kanbanDataSet;
}


    </script>
@endsection
