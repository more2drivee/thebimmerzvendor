@extends('layouts.app')

@section('title', __('survey::lang.all'))

@section('content')
   @include('survey::layouts.nav')

    <section class="content-header no-print">

        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('survey::lang.surveys')</h1>


        @component('components.widget', ['class' => 'box-primary', 'title' => __('survey::lang.all')])
            @can('direct_sell.access')
                @slot('tool')
                    <div class="box-tools">

                    </div>
                @endslot
            @endcan

            @if (auth()->user()->can('survey.view') || auth()->user()->can('survey.update') || auth()->user()->can('survey.delete'))
                <table class="table table-bordered table-striped ajax_view" id="data_table">
                    <thead>
                        <tr>
                            <th>@lang('survey::lang.username')</th>
                            <th>@lang('survey::lang.title')</th>
                            <th>@lang('survey::lang.typeform')</th>
                            <th>@lang('survey::lang.seen')</th>
                            <th>@lang('survey::lang.fill')</th>
                            <th>@lang('survey::lang.url')</th>
                            <th>@lang('survey::lang.action')</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            @endif
        @endcomponent
        
    </section>
@endsection

@section('javascript')


    <script type="text/javascript">
        $(document).ready(function() {
            $('#data_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                ajax: {
                    url: "{{ route('survey.data.sent') }}",
                    type: "GET"
                },
                scrollY: "75vh",
                scrollCollapse: true,
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'title',
                        name: 'title'
                    },

                    {
                        data: 'type_form',
                        name: 'type_form'
                    },
                    {
                        data: 'seen',
                        name: 'seen',
                        render: function(data, type, row) {
                            if (data == 1) {
                                return 'Yes';
                            } else {
                                return 'No';
                            }
                        }
                    },
                    {
                        data: 'fill',
                        name: 'fill',
                        render: function(data, type, row) {
                            if (data == 1) {
                                return `<a href="{{ url('survey/show') }}/${row.user_id}/${row.survey_id}" class="tw-text-blue-500 tw-font-bold">Yes</a>`;
                            } else {
                                return 'No';
                            }
                        }
                    },
                    {
                        data: 'user_url',
                        name: 'user_url',
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,

                    }
                ]
            });
        });

        function copyToClipboard(element) {
            var url = element.getAttribute("data-url");
            var tempInput = document.createElement("input");
            document.body.appendChild(tempInput);
            tempInput.value = url;
            tempInput.select();
            document.execCommand("copy");
            document.body.removeChild(tempInput);
        }
    </script>
@endsection
