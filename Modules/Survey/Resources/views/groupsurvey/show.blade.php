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
                        <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right"
                            href="{{ action([\Modules\Survey\Http\Controllers\CreateGroupController::class, 'index']) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg> @lang('messages.add')
                        </a>
                    </div>
                @endslot
            @endcan

            @if (auth()->user()->can('survey.view') || auth()->user()->can('survey.update') || auth()->user()->can('survey.delete'))
                <table class="table table-bordered table-striped ajax_view" id="group_table">
                    <thead>
                        <tr>
                            <th>@lang('survey::lang.action')</th>
                            <th>@lang('survey::lang.name')</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            @endif
        @endcomponent
    </section>
@stop

@section('javascript')
    
    <script type="text/javascript">
        $(document).ready(function() {
            $('#group_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false, 
                aaSorting: [
                    [1, 'desc']
                ],
                ajax: {
                    url: "{{ route('group.data') }}",
                    type: "GET",
                    error: function(xhr, error, code) {
                        console.log("Error: ", error);
                        console.log("Code: ", code);
                        console.log("Response: ", xhr.responseText);
                    }
                },
                scrollY: "75vh", 
                scrollCollapse: true,
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    }
                ]
            });
        });
    </script>
@endsection