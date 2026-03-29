@extends('layouts.app')

@section('title', __('essentials::lang.employee_warnings'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('essentials::lang.employee_warnings')</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget')
                @slot('title')
                    {{ __('essentials::lang.employee_warnings') }}
                @endslot
                
                @slot('tool')
                    <div class="box-tools">
                        <button type="button" class="btn btn-primary btn-modal" 
                            data-href="{{ action('\App\Http\Controllers\EmployeeWarningController@create') }}" 
                            data-container=".warning_modal">
                            <i class="fa fa-plus"></i> @lang('essentials::lang.add_warning')
                        </button>
                    </div>
                @endslot

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="employee_warnings_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>@lang('essentials::lang.employee')</th>
                                <th>@lang('essentials::lang.warning_type')</th>
                                <th>@lang('essentials::lang.reason')</th>
                                <th>@lang('essentials::lang.warning_date')</th>
                                <th>@lang('essentials::lang.warning_issued_by')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

<!-- Warning Modal -->
<div class="modal fade warning_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        // Employee warnings table
        var warnings_table = $('#employee_warnings_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ action('\App\Http\Controllers\EmployeeWarningController@index') }}',
                data: function(d) {
                    d.employee_id = $('#employee_id_filter').val();
                }
            },
            columns: [
                { data: 'employee_name', name: 'employee_name' },
                { data: 'warning_type', name: 'warning_type' },
                { data: 'reason', name: 'reason' },
                { data: 'warning_date', name: 'warning_date' },
                { data: 'issued_by_name', name: 'issued_by_name' },
            ]
        });

        // Open create warning modal
        $(document).on('click', '.btn-modal', function(e) {
            e.preventDefault();
            var container = $(this).data('container');
            $.ajax({
                url: $(this).data('href'),
                success: function(response) {
                    $(container).html(response).modal('show');
                }
            });
        });
    });
</script>
@endsection
