@extends('layouts.app')
@section('title', __('repair::lang.recycle_bin'))

@section('content')
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('repair::lang.recycle_bin') (Sales)
        </h1>
    </section>

    <section class="content no-print">
        @component('components.widget', ['class' => 'box-primary'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="sell_recycle_bin_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('business.location')</th>
                            <th>@lang('lang_v1.deleted_at')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent
    </section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            var sell_recycle_bin_table = $('#sell_recycle_bin_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/sells/recycle-bin',
                columnDefs: [
                    {
                        targets: [0],
                        orderable: false,
                        searchable: false,
                    },
                ],
                columns: [
                    { data: 'action', name: 'action' },
                    { data: 'invoice_no', name: 'transactions.invoice_no' },
                    { data: 'customer', name: 'contacts.name' },
                    { data: 'location', name: 'bl.name' },
                    { data: 'deleted_at', name: 'transactions.deleted_at' },
                ],
            });

            $(document).on('click', '.delete_sale_permanent', function(e) {
                e.preventDefault();
                var url = $(this).data('href');
                if (!url) {
                    return;
                }

                swal({
                    title: "{{ __('repair::lang.hard_delete_confirm_title') }}",
                    text: "{{ __('repair::lang.hard_delete_confirm_text') }}",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                    content: {
                        element: 'input',
                        attributes: {
                            type: 'password',
                            placeholder: "{{ __('repair::lang.password_placeholder') }}",
                        }
                    }
                }).then(function(password) {
                    if (password) {
                        $.ajax({
                            method: 'DELETE',
                            url: url,
                            dataType: 'json',
                            data: {
                                force_delete: true,
                                password: password,
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(result) {
                                if (result.success) {
                                    toastr.success("{{ __('repair::lang.hard_delete_success') }}");
                                    sell_recycle_bin_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
