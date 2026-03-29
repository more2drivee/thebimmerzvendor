@extends('layouts.app')
@section('title', __('repair::lang.recycle_bin'))

@section('content')
@include('repair::layouts.nav')
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('repair::lang.recycle_bin')</h1>
</section>

<section class="content no-print">
    @component('components.widget', ['class' => 'box-primary'])
        <!-- Filter buttons -->
        <div class="row" style="margin-bottom: 15px;">
            <div class="col-md-12">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-default filter-btn" data-type="all">
                        <i class="fas fa-list"></i> @lang('repair::lang.all_items')
                    </button>
                    <button type="button" class="btn btn-default filter-btn" data-type="job_sheet">
                        <i class="fas fa-clipboard"></i> @lang('repair::lang.job_sheets')
                    </button>
                    <button type="button" class="btn btn-default filter-btn" data-type="transaction">
                        <i class="fas fa-file-invoice"></i> @lang('repair::lang.repair_invoices')
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="unified_recycle_bin_table">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        {{-- High-level item kind: Job Sheet vs Transaction --}}
                        <th>@lang('repair::lang.type')</th>
                        {{-- Underlying transaction type: sell, purchase, opening_balance, etc. --}}
                        <th>@lang('account.transaction_type')</th>
                        <th class="reference-header">@lang('repair::lang.job_sheet_no') / @lang('sale.invoice_no')</th>
                        <th>@lang('receipt.date')</th>
                        <th class="party-header">@lang('role.customer')</th>
                        <th>@lang('business.location')</th>
                        <th>@lang('lang_v1.deleted_at')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var currentType = 'all';
        
        // Initialize DataTable
        var unified_recycle_bin_table = $('#unified_recycle_bin_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/repair/recycle-bin',
                data: function(d) {
                    d.type = currentType;
                }
            },
            columnDefs: [
                {
                    targets: [0],
                    orderable: false,
                    searchable: false,
                },
            ],
            columns: [
                { data: 'action', name: 'action' },
                // Job Sheet / Transaction
                { data: 'type_display', name: 'type_display' },
                // Underlying transaction type (sell, purchase, opening_balance, etc.)
                { data: 'transaction_type_display', name: 'transaction_type' },
                { 
                    data: null, 
                    name: 'reference',
                    render: function(data, type, row) {
                        if (row.type === 'job_sheet') {
                            return row.job_sheet_no || '';
                        } else if (row.type === 'transaction') {
                            // Different reference based on transaction type
                            if (row.transaction_type === 'purchase' || row.transaction_type === 'purchase_return') {
                                return row.invoice_no || '';
                            } else if (row.transaction_type === 'expense' || row.transaction_type === 'payroll') {
                                return row.ref_no || '';
                            } else {
                                return row.invoice_no || '';
                            }
                        }
                        return '';
                    }
                },
                { 
                    data: null,
                    name: 'date',
                    render: function(data, type, row) {
                        if (row.type === 'job_sheet') {
                            return row.created_at || '';
                        } else if (row.type === 'transaction') {
                            return row.transaction_date || '';
                        }
                        return '';
                    }
                },
                { 
                    data: null, 
                    name: 'party',
                    render: function(data, type, row) {
                        if (row.type === 'job_sheet') {
                            return row.customer || '';
                        } else if (row.type === 'transaction') {
                            // Different party name based on transaction type
                            if (row.transaction_type === 'purchase' || row.transaction_type === 'purchase_return') {
                                return row.supplier || '';
                            } else {
                                return row.customer || '';
                            }
                        }
                        return '';
                    }
                },
                { data: 'location', name: 'location' },
                { data: 'deleted_at', name: 'deleted_at' },
            ],
        });

        // Filter button clicks
        $('.filter-btn').on('click', function() {
            $('.filter-btn').removeClass('btn-primary').addClass('btn-default');
            $(this).removeClass('btn-default').addClass('btn-primary');
            
            currentType = $(this).data('type');
            unified_recycle_bin_table.ajax.reload();
        });

        // Set initial active button
        $('.filter-btn[data-type="all"]').removeClass('btn-default').addClass('btn-primary');

        // Restore item - Show preview modal for transactions, direct restore for job sheets
        $(document).on('click', '.restore_item', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            var itemType = $(this).data('item-type');

            // Check if this is a transaction restore
            if (url.indexOf('restore-transaction') !== -1) {
                // Extract transaction ID from URL
                var transactionId = url.split('/').pop();

                // Show loading
                var loader = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i></div>';

                // Open modal with loader
                $.ajax({
                    method: 'GET',
                    url: '/repair/recycle-bin/preview/' + transactionId,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            // Replace the entire modal content with the preview HTML
                            $('.view_modal').html(result.html);
                            $('.view_modal').modal('show');
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON ? xhr.responseJSON.msg : "{{__('messages.something_went_wrong')}}";
                        toastr.error(errorMsg);
                    }
                });
            } else {
                // Direct restore for job sheets
                $.ajax({
                    method: 'POST',
                    url: url,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            unified_recycle_bin_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });

        // Permanent delete with SweetAlert
        $(document).on('click', '.delete_permanent', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            var self = this;

            // SweetAlert confirmation dialog
            swal({
                title: "{{__('lang_v1.confirmation')}}",
                text: "{{__('repair::lang.hard_delete_confirm_text')}}",
                icon: 'warning',
                buttons: true,
                dangerMode: true,
                buttons: ["{{__('messages.cancel')}}", "{{__('messages.yes_delete_it')}}"]
            }).then(function(willDelete) {
                if (willDelete) {
                    // Ask for admin password with SweetAlert
                    swal({
                        title: "{{__('repair::lang.enter_admin_password')}}",
                        text: "{{__('repair::lang.password_placeholder')}}",
                        content: {
                            element: "input",
                            attributes: {
                                placeholder: "{{__('repair::lang.password_placeholder')}}",
                                type: "password",
                            }
                        },
                        buttons: ["{{__('messages.cancel')}}", "{{__('messages.delete_permanently')}}"]
                    }).then(function(password) {
                        if (password) {
                            $.ajax({
                                method: 'DELETE',
                                url: url,
                                data: {
                                    password: password
                                },
                                dataType: 'json',
                                success: function(result) {
                                    if (result.success) {
                                        swal(
                                            "{{__('messages.deleted')}}",
                                            result.msg,
                                            "success"
                                        );
                                        unified_recycle_bin_table.ajax.reload();
                                    } else {
                                        swal(
                                            "{{__('messages.error')}}",
                                            result.msg,
                                            "error"
                                        );
                                    }
                                },
                                error: function(xhr) {
                                    var errorMsg = xhr.responseJSON ? xhr.responseJSON.msg : "{{__('messages.something_went_wrong')}}";
                                    swal(
                                        "{{__('messages.error')}}",
                                        errorMsg,
                                        "error"
                                    );
                                }
                            });
                        } else if (password === null) {
                            swal(
                                "{{__('messages.error')}}",
                                "{{__('repair::lang.password_required')}}",
                                "error"
                            );
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
