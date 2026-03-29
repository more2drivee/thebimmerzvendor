@extends('layouts.app')
@section('title', __('product.import_products'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('product.import_products')
    </h1>
</section>

<!-- Main content -->
<section class="content">

    @if (session('notification') || !empty($notification))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    @if(!empty($notification['msg']))
                        {{$notification['msg']}}
                    @elseif(session('notification.msg'))
                        {{ session('notification.msg') }}
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if (session('status'))
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    {{ session('status.msg') }}
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary'])
            {!! Form::open(['url' => action([\App\Http\Controllers\ImportProductsController::class, 'store']), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('name', __( 'product.file_to_import' ) . ':') !!}
                            {!! Form::file('products_csv', ['accept'=> '.xls, .xlsx, .csv', 'required' => 'required']); !!}
                        </div>
                    </div>

                    <div class="col-md-4 text-right">
                        <button type="submit" class="btn btn-primary" style="margin-top: 25px;">@lang('messages.submit')</button>
                    </div>
                </div>
            {!! Form::close() !!}
            <br><br>
            <div class="row">
                <div class="col-sm-4">
                    <a href="{{ asset('files/import_products_csv_template.xls') }}" class="btn btn-success" download><i class="fa fa-download"></i> @lang('lang_v1.download_template_file')</a>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="sleep_seconds">Sleep Time Between Requests (seconds):</label>
                        <input type="number" id="sleep_seconds" class="form-control" value="10" min="3" max="30">
                        <small class="text-muted">Higher values reduce API errors but take longer to process</small>
                    </div>
                    <div class="form-group">
                        <label for="start_row">Start From Row:</label>
                        <input type="number" id="start_row" class="form-control" value="1" min="1">
                        <small class="text-muted">Row number to start processing from (1-based)</small>
                    </div>
                    <div class="form-group">
                        <label for="max_rows">Maximum Rows to Process:</label>
                        <input type="number" id="max_rows" class="form-control" min="1">
                        <small class="text-muted">Leave empty to process all rows</small>
                    </div>
                </div>
                <div class="col-sm-4 text-right">
                    <button type="button" id="process_excel_btn" class="btn btn-primary" style="margin-top: 25px;"><i class="fa fa-cogs"></i> Process Excel with AI (Add Compatibility)</button>
                    <button type="button" id="stop_process_btn" class="btn btn-danger" style="margin-top: 25px; margin-left: 10px; display: none;"><i class="fa fa-stop"></i> Stop Process</button>
                    <div id="process_status" style="margin-top: 10px; display: none;">
                        <div class="progress">
                            <div id="process_progress" class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">
                                <span id="progress_text">Initializing...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.instructions')])
                <strong>@lang('lang_v1.instruction_line1')</strong><br>
                    @lang('lang_v1.instruction_line2')
                    <br><br>
                <table class="table table-striped">
                    <tr>
                        <th>@lang('lang_v1.col_no')</th>
                        <th>@lang('lang_v1.col_name')</th>
                        <th>@lang('lang_v1.instruction')</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>@lang('product.product_name') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('lang_v1.name_ins')</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>@lang('product.brand') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.brand_ins') <br><small class="text-muted">(@lang('lang_v1.brand_ins2'))</small></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>@lang('product.unit') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('lang_v1.unit_ins')</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>@lang('product.category') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.category_ins') <br><small class="text-muted">(@lang('lang_v1.category_ins2'))</small></td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>@lang('product.sub_category') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.sub_category_ins') <br><small class="text-muted">({!! __('lang_v1.sub_category_ins2') !!})</small></td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>@lang('product.sku') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.sku_ins')</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>@lang('product.barcode_type') <small class="text-muted">(@lang('lang_v1.optional'), @lang('lang_v1.default'): C128)</small></td>
                        <td>@lang('lang_v1.barcode_type_ins') <br>
                            <strong>@lang('lang_v1.barcode_type_ins2'): C128, C39, EAN-13, EAN-8, UPC-A, UPC-E, ITF-14</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>@lang('product.manage_stock') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('lang_v1.manage_stock_ins')<br>
                            <strong>1 = @lang('messages.yes')<br>
                            0 = @lang('messages.no')</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>@lang('product.alert_quantity') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('product.alert_quantity')</td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td>@lang('product.expires_in') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.expires_in_ins')</td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td>@lang('lang_v1.expire_period_unit') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.expire_period_unit_ins')<br>
                            <strong>@lang('lang_v1.available_options'): days, months</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>12</td>
                        <td>@lang('product.applicable_tax') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.applicable_tax_ins') {!! __('lang_v1.applicable_tax_help') !!}</td>
                    </tr>
                    <tr>
                        <td>13</td>
                        <td>@lang('product.selling_price_tax_type') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('product.selling_price_tax_type') <br>
                            <strong>@lang('lang_v1.available_options'): inclusive, exclusive</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>14</td>
                        <td>@lang('product.product_type') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('product.product_type') <br>
                            <strong>@lang('lang_v1.available_options'): single, variable</strong></td>
                    </tr>
                    <tr>
                        <td>15</td>
                        <td>@lang('product.variation_name') <small class="text-muted">(@lang('lang_v1.variation_name_ins'))</small></td>
                        <td>@lang('lang_v1.variation_name_ins2')</td>
                    </tr>
                    <tr>
                        <td>16</td>
                        <td>@lang('product.variation_values') <small class="text-muted">(@lang('lang_v1.variation_values_ins'))</small></td>
                        <td>{!! __('lang_v1.variation_values_ins2') !!}</td>
                    </tr>
                    <tr>
                        <td>17</td>
                        <td>@lang('lang_v1.variation_sku') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.variation_sku_ins') !!}</td>
                    </tr>
                    <tr>
                        <td>18</td>
                        <td> @lang('lang_v1.purchase_price_inc_tax')<br><small class="text-muted">(@lang('lang_v1.purchase_price_inc_tax_ins1'))</small></td>
                        <td>{!! __('lang_v1.purchase_price_inc_tax_ins2') !!}</td>
                    </tr>
                    <tr>
                        <td>19</td>
                        <td>@lang('lang_v1.purchase_price_exc_tax')  <br><small class="text-muted">(@lang('lang_v1.purchase_price_exc_tax_ins1'))</small></td>
                        <td>{!! __('lang_v1.purchase_price_exc_tax_ins2') !!}</td>
                    </tr>
                    <tr>
                        <td>20</td>
                        <td>@lang('lang_v1.profit_margin') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.profit_margin_ins')<br>
                            <small class="text-muted">{!! __('lang_v1.profit_margin_ins1') !!}</small></td>
                    </tr>
                    <tr>
                        <td>21</td>
                        <td>@lang('lang_v1.selling_price') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.selling_price_ins')<br>
                         <small class="text-muted">{!! __('lang_v1.selling_price_ins1') !!}</small></td>
                    </tr>
                    <tr>
                        <td>22</td>
                        <td>@lang('lang_v1.opening_stock') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.opening_stock_ins') {!! __('lang_v1.opening_stock_help_text') !!}<br>
                        </td>
                    </tr>
                    <tr>
                        <td>23</td>
                        <td>@lang('lang_v1.opening_stock_location') <small class="text-muted">(@lang('lang_v1.optional')) <br>@lang('lang_v1.location_ins')</small></td>
                        <td>@lang('lang_v1.location_ins1')<br>
                        </td>
                    </tr>
                    <tr>
                        <td>24</td>
                        <td>@lang('lang_v1.expiry_date') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.expiry_date_ins') !!}<br>
                        </td>
                    </tr>
                    <tr>
                        <td>25</td>
                        <td>@lang('lang_v1.enable_imei_or_sr_no') <small class="text-muted">(@lang('lang_v1.optional'), @lang('lang_v1.default'): 0)</small></td>
                        <td><strong>1 = @lang('messages.yes')<br>
                            0 = @lang('messages.no')</strong><br>
                        </td>
                    </tr>
                    <tr>
                        <td>26</td>
                        <td>@lang('lang_v1.weight') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.optional')<br>
                        </td>
                    </tr>
                    <tr>
                        <td>27</td>
                        <td>@lang('lang_v1.rack') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.rack_help_text') !!}</td>
                    </tr>
                    <tr>
                        <td>28</td>
                        <td>@lang('lang_v1.row') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.row_help_text') !!}</td>
                    </tr>
                    <tr>
                        <td>29</td>
                        <td>@lang('lang_v1.position') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.position_help_text') !!}</td>
                    </tr>
                    <tr>
                        <td>30</td>
                        <td>@lang('lang_v1.image') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.image_help_text', ['path' => 'public/uploads/'.config('constants.product_img_path')]) !!} <br><br>
                            {{__('lang_v1.img_url_help_text')}}
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>31</td>
                        <td>@lang('lang_v1.product_description') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>32</td>
                        <td>@lang('lang_v1.product_custom_field1') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>33</td>
                        <td>@lang('lang_v1.product_custom_field2') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                    </tr>
                    <tr>
                        <td>34</td>
                        <td>@lang('lang_v1.product_custom_field3') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>35</td>
                        <td>@lang('lang_v1.product_custom_field4') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                    </tr>
                    <tr>
                        <td>36</td>
                        <td>@lang('lang_v1.not_for_selling') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td><strong>1 = @lang('messages.yes')<br>
                            0 = @lang('messages.no')</strong><br>
                        </td>
                    </tr>
                    <tr>
                        <td>37</td>
                        <td>@lang('lang_v1.product_locations') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.product_locations_ins')
                        </td>
                    </tr>


                </table>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>

    $(document).ready(function() {
        // Variable to store the AJAX request
        var currentAjaxRequest = null;
        var processingStartTime = null;
        var progressUpdateInterval = null;
        var totalProductsToProcess = 0;
        var currentProductIndex = 0;

        // Function to reset the UI after processing is complete or stopped
        function resetProcessingUI() {
            // Hide stop button and progress bar
            $('#stop_process_btn').hide();
            $('#process_status').hide();

            // Reset progress bar
            $('#process_progress').css('width', '0%');
            $('#progress_text').text('Initializing...');

            // Enable process button
            $('#process_excel_btn').prop('disabled', false);
            $('#process_excel_btn').html('<i class="fa fa-cogs"></i> Process Excel with AI (Add Compatibility)');

            // Clear interval if it exists
            if (progressUpdateInterval) {
                clearInterval(progressUpdateInterval);
                progressUpdateInterval = null;
            }
        }

        // Function to update progress based on elapsed time
        function updateProgressBasedOnTime() {
            if (!processingStartTime || !totalProductsToProcess) return;

            var elapsedSeconds = Math.floor((new Date() - processingStartTime) / 1000);
            var sleepSeconds = parseInt($('#sleep_seconds').val()) || 10;

            // Estimate progress based on sleep time and elapsed time
            // Each product should take approximately sleepSeconds to process
            var estimatedProductsProcessed = Math.floor(elapsedSeconds / sleepSeconds);

            // Cap at the total number of products
            estimatedProductsProcessed = Math.min(estimatedProductsProcessed, totalProductsToProcess);

            // Calculate percentage
            var percentage = Math.min(Math.floor((estimatedProductsProcessed / totalProductsToProcess) * 100), 99);

            // Update progress bar
            $('#process_progress').css('width', percentage + '%');
            $('#progress_text').text('Processing: ' + estimatedProductsProcessed + ' of ' + totalProductsToProcess + ' (' + percentage + '%)');

            // If we've reached the end, clear the interval
            if (estimatedProductsProcessed >= totalProductsToProcess) {
                clearInterval(progressUpdateInterval);
            }
        }

        // Handle the stop process button click
        $('#stop_process_btn').click(function() {
            if (currentAjaxRequest) {
                // Abort the AJAX request
                currentAjaxRequest.abort();
                currentAjaxRequest = null;

                // Show message
                toastr.warning('Process stopped by user');

                // Reset UI
                resetProcessingUI();
            }
        });

        // Handle the process Excel button click
        $('#process_excel_btn').click(function() {
            // Check if a file is selected
            var fileInput = $('input[name="products_csv"]');
            if (fileInput.val() === '') {
                toastr.error('Please select an Excel file first');
                return;
            }

            // Show loading indicator
            $(this).prop('disabled', true);
            $(this).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

            // Show stop button and progress bar
            $('#stop_process_btn').show();
            $('#process_status').show();

            // Reset progress
            $('#process_progress').css('width', '0%');
            $('#progress_text').text('Initializing...');

            // Get parameters from input fields
            var sleepSeconds = parseInt($('#sleep_seconds').val()) || 10;
            var startRow = parseInt($('#start_row').val()) || 1;
            var maxRows = parseInt($('#max_rows').val()) || '';

            // Validate sleep time (3-30 seconds)
            if (sleepSeconds < 3) sleepSeconds = 3;
            if (sleepSeconds > 30) sleepSeconds = 30;

            // Validate start row (minimum 1)
            if (startRow < 1) startRow = 1;

            // Create a FormData object to handle file upload
            var formData = new FormData();
            formData.append('products_csv', fileInput[0].files[0]);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            formData.append('sleep_seconds', sleepSeconds);
            formData.append('start_row', startRow);

            // Only append max_rows if it has a value
            if (maxRows) {
                formData.append('max_rows', maxRows);
            }

            // Set processing start time
            processingStartTime = new Date();

            // Send directly to AIProductController to process the Excel file
            currentAjaxRequest = $.ajax({
                url: "{{ route('product.process.excel') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Clear the current request
                    currentAjaxRequest = null;

                    if (response.success) {
                        // Show processing results
                        showProcessingResults(response, sleepSeconds);
                    } else {
                        toastr.error(response.error || 'Failed to process Excel file');
                        resetProcessingUI();
                    }
                },
                error: function(xhr, status, error) {
                    // Clear the current request
                    currentAjaxRequest = null;

                    // Only show error if not aborted by user
                    if (status !== 'abort') {
                        toastr.error('Error processing file: ' + (xhr.responseJSON ? xhr.responseJSON.error : error));
                    }

                    resetProcessingUI();
                }
            });

            // Start progress update interval
            if (progressUpdateInterval) {
                clearInterval(progressUpdateInterval);
            }

            // Estimate total products to process based on max_rows or all rows
            // This is just an initial estimate, will be updated when we get the response
            totalProductsToProcess = maxRows || 100; // Default to 100 if we don't know

            progressUpdateInterval = setInterval(updateProgressBasedOnTime, 1000);
        });

        // Function to show processing results
        function showProcessingResults(response, sleepSeconds) {
            // Reset the processing UI
            resetProcessingUI();

            // Update progress to 100%
            $('#process_progress').css('width', '100%');
            $('#progress_text').text('Complete!');

            // Show processing modal
            var modal = $('<div class="modal fade" id="processing_modal" tabindex="-1" role="dialog">' +
                '<div class="modal-dialog" role="document">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h4 class="modal-title">Processing Results</h4>' +
                '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                '</div>' +
                '<div class="modal-body">' +
                '<div class="alert alert-success">' +
                'Processing complete! ' + response.successful_products + ' of ' + response.products_in_database + ' products processed successfully.' +
                '<br><small>Sleep time between requests: ' + sleepSeconds + ' seconds</small>' +
                '</div>' +
                '<div class="alert alert-info">' +
                '<strong>Summary:</strong>' +
                '<ul>' +
                '<li>Total products in Excel: ' + response.total_products + '</li>' +
                '<li>Products found in database: ' + response.products_in_database + '</li>' +
                '<li>Products not in database (skipped): ' + response.products_not_in_database + '</li>' +
                '<li>Successfully processed: ' + response.successful_products + '</li>' +
                '<li>Failed to process: ' + response.failed_products + '</li>' +
                '</ul>' +
                '</div>' +
                (response.row_range ?
                '<div class="alert alert-info">' +
                '<strong>Row Range Information:</strong>' +
                '<ul>' +
                '<li>Starting from row: ' + response.row_range.start_row + '</li>' +
                '<li>Maximum rows to process: ' + (response.row_range.max_rows || 'All') + '</li>' +
                '<li>Actual rows processed: ' + response.row_range.actual_rows_processed + '</li>' +
                '<li>Total rows in file (including header): ' + response.row_range.total_rows_in_file + '</li>' +
                '</ul>' +
                '</div>' : '') +
                (response.brand_summary && response.brand_summary.length > 0 ?
                '<div class="alert alert-warning">' +
                '<strong>Skipped Products by Brand:</strong>' +
                '<div style="max-height: 150px; overflow-y: auto;">' +
                '<table class="table table-condensed">' +
                '<thead><tr><th>Brand</th><th>Count</th></tr></thead>' +
                '<tbody>' +
                (function() {
                    var html = '';
                    for (var i = 0; i < response.brand_summary.length; i++) {
                        var brand = response.brand_summary[i];
                        html += '<tr><td>' + brand.brand + '</td><td>' + brand.count + '</td></tr>';
                    }
                    return html;
                })() +
                '</tbody></table>' +
                '</div>' +
                '</div>' : '') +
                '<div class="form-group">' +
                '<label for="result_filter">Filter Results:</label>' +
                '<select id="result_filter" class="form-control">' +
                '<option value="all">All Results</option>' +
                '<option value="success">Successful Only</option>' +
                '<option value="failed">Failed Only</option>' +
                '<option value="skipped">Skipped Only</option>' +
                '</select>' +
                '</div>' +
                '<div id="processing_results" style="max-height: 400px; overflow-y: auto;"></div>' +
                '</div>' +
                '<div class="modal-footer">' +
                '<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>');

            $('body').append(modal);

            // Store results for filtering
            var allResults = response.results;

            // Set up modal events
            $('#processing_modal').on('hidden.bs.modal', function() {
                // Remove the modal from DOM when hidden to prevent ID conflicts
                $(this).remove();
            });

            // Show the modal
            $('#processing_modal').modal('show');

            // Remove any existing event handlers to prevent duplicates
            $(document).off('change', '#result_filter');

            // Handle filter change
            $(document).on('change', '#result_filter', function() {
                var filterValue = $(this).val();
                var filteredResults = [];

                if (filterValue === 'all') {
                    filteredResults = allResults;
                } else if (filterValue === 'success') {
                    filteredResults = allResults.filter(function(item) {
                        return item.success === true;
                    });
                } else if (filterValue === 'failed') {
                    filteredResults = allResults.filter(function(item) {
                        return item.success === false && item.status !== 'skipped';
                    });
                } else if (filterValue === 'skipped') {
                    filteredResults = allResults.filter(function(item) {
                        return item.status === 'skipped';
                    });
                }

                // Rebuild the results table with filtered data
                updateResultsTable(filteredResults);
            });

            // Function to update results table
            function updateResultsTable(results) {
                var resultsHtml = '<table class="table table-striped">' +
                    '<thead><tr>' +
                    '<th>SKU</th>' +
                    '<th>Product Name</th>' +
                    '<th>Status</th>' +
                    '<th>Details</th>' +
                    '<th>Compatibility Count</th>' +
                    '</tr></thead><tbody>';

                for (var i = 0; i < results.length; i++) {
                    var result = results[i];
                    var statusClass, statusText;

                    if (result.status === 'skipped') {
                        statusClass = 'warning';
                        statusText = 'Skipped';
                    } else if (result.success) {
                        statusClass = 'success';
                        statusText = 'Success';
                    } else {
                        statusClass = 'danger';
                        statusText = 'Failed';
                    }

                    var details = result.success ?
                        (result.product_status === 'existing' ? 'Added compatibility data to existing product' : 'Retrieved compatibility data for new product') :
                        result.error;

                    resultsHtml += '<tr class="' + statusClass + '">' +
                        '<td>' + (result.sku || '') + '</td>' +
                        '<td>' + (result.product_name || '') + '</td>' +
                        '<td>' + statusText + '</td>' +
                        '<td>' + details + '</td>' +
                        '<td>' + (result.compatibility_count || 0) + '</td>' +
                        '</tr>';
                }

                resultsHtml += '</tbody></table>';
                $('#processing_results').html(resultsHtml);
            }

            // Initial table build
            updateResultsTable(allResults);

            // Update the total products processed for future reference
            if (response.products_in_database) {
                totalProductsToProcess = response.products_in_database;
            }

            // Show 100% completion in the progress bar
            $('#process_progress').css('width', '100%');
            $('#progress_text').text('Complete: ' + response.successful_products + ' of ' + totalProductsToProcess + ' (100%)');

            // Keep the progress bar visible for a moment to show completion
            setTimeout(function() {
                $('#process_status').fadeOut(1000);
            }, 3000);
        }
    });
</script>

<style>
    .ai-label {
        font-weight: 600;
        margin-bottom: 12px;
        color: #333;
    }

    .ai-toggle-container {
        display: flex;
        justify-content: center;
        margin-top: 8px;
    }

    .ai-icon {
        cursor: pointer;
        font-size: 22px;
        color: #888;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #f5f5f5;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .ai-icon:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .ai-icon.active {
        color: white;
        background-color: #4F959D;
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(79,149,157,0.4);
    }

    .ai-icon.active i {
        animation: pulse 1.5s infinite;
    }

    .ai-status {
        font-size: 14px;
        font-weight: bold;
        margin-top: 5px;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
</style>
@endsection
