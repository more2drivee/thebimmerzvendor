<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\Modules\Essentials\Http\Controllers\EssentialsLeaveController::class, 'store']), 'method' => 'post', 'id' => 'add_leave_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'essentials::lang.add_leave' )</h4>
    </div>

    <div class="modal-body">
    	<div class="row">
    		@can('essentials.crud_all_leave')
    		<div class="form-group col-md-12">
		        {!! Form::label('employees', __('essentials::lang.select_employee') . ':') !!}
		        {!! Form::select('employees[]', $employees, null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'employees', 'multiple', 'required' ]); !!}
    		</div>
    		@endcan
    		<div class="form-group col-md-12">
	        	{!! Form::label('essentials_leave_type_id', __( 'essentials::lang.leave_type' ) . ':*') !!}
	          	{!! Form::select('essentials_leave_type_id', $leave_types, null, ['class' => 'form-control select2', 'required', 'placeholder' => __( 'messages.please_select' ) ]); !!}
	      	</div>

	      	<div class="form-group col-md-6">
	        	{!! Form::label('start_date', __( 'essentials::lang.start_date' ) . ':*') !!}
	        	<div class="input-group data">
	        		{!! Form::text('start_date', null, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.start_date' ), 'readonly' ]); !!}
	        		<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
	        	</div>
	      	</div>

	      	<div class="form-group col-md-6">
	        	{!! Form::label('end_date', __( 'essentials::lang.end_date' ) . ':*') !!}
		        	<div class="input-group data">
		          	{!! Form::text('end_date', null, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.end_date' ), 'readonly', 'required' ]); !!}
		          	<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
	        	</div>
	      	</div>

	      	<div class="form-group col-md-12">
	        	{!! Form::label('reason', __( 'essentials::lang.reason' ) . ':') !!}
	          	{!! Form::textarea('reason', null, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.reason' ), 'rows' => 4, 'required' ]); !!}
	      	</div>

	      	<!-- Salary & Deduction Section -->
	      	<div class="col-md-12">
	      		<h4>@lang('essentials::lang.salary_details')</h4>
	      	</div>

	      	<div class="form-group col-md-6">
	        	{!! Form::label('employee_salary', __( 'essentials::lang.employee_salary' ) . ':') !!}
	        	{!! Form::number('employee_salary', null, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.employee_salary' ), 'step' => '0.01', 'id' => 'employee_salary']); !!}
	      	</div>

	      	<div class="form-group col-md-6">
	        	{!! Form::label('per_day_salary', __( 'essentials::lang.per_day_salary' ) . ':') !!}
	        	{!! Form::number('per_day_salary', null, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.per_day_salary' ), 'step' => '0.01', 'id' => 'per_day_salary']); !!}
	      	</div>

	      	<div class="form-group col-md-6">
	        	{!! Form::label('deduct_from_salary', __( 'essentials::lang.deduct_from_salary' ) . ':') !!}
	        	{!! Form::select('deduct_from_salary', ['no' => __('lang_v1.no'), 'yes' => __('lang_v1.yes')], 'no', ['class' => 'form-control select2', 'id' => 'deduct_from_salary']); !!}
	      	</div>

	      	<div class="form-group col-md-6">
	        	{!! Form::label('leave_days_count', __( 'essentials::lang.leave_days_count' ) . ':') !!}
	        	{!! Form::number('leave_days_count', 1, ['class' => 'form-control', 'min' => '1', 'id' => 'leave_days_count']); !!}
	      	</div>

	      	<div class="form-group col-md-12">
	        	{!! Form::label('deduction_amount', __( 'essentials::lang.deduction_amount' ) . ':') !!}
	        	{!! Form::number('deduction_amount', 0, ['class' => 'form-control', 'step' => '0.01', 'readonly' => 'readonly', 'id' => 'deduction_amount']); !!}
	      	</div>

	      	<!-- Warning Section -->
	      	<div class="col-md-12">
	      		<h4>@lang('essentials::lang.warning')</h4>
	      	</div>

	      	<div class="form-group col-md-6">
	        	{!! Form::label('give_warning', __( 'essentials::lang.give_warning' ) . ':') !!}
	        	{!! Form::select('give_warning', ['no' => __('lang_v1.no'), 'yes' => __('lang_v1.yes')], 'no', ['class' => 'form-control select2', 'id' => 'give_warning']); !!}
	      	</div>

	      	<div class="form-group col-md-6">
	        	{!! Form::label('warning_type', __( 'essentials::lang.warning_type' ) . ':') !!}
	        	{!! Form::select('warning_type', ['verbal' => __('essentials::lang.warning_verbal'), 'written' => __('essentials::lang.warning_written'), 'final' => __('essentials::lang.warning_final')], null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
	      	</div>

	      	<div class="form-group col-md-12">
	        	{!! Form::label('warning_note', __( 'essentials::lang.warning_note' ) . ':') !!}
	        	{!! Form::textarea('warning_note', null, ['class' => 'form-control', 'rows' => 2]); !!}
	      	</div>

	      	<hr>
	      	<div class="col-md-12">
    			{!! $instructions !!}
    		</div>
    	</div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white ladda-button add-leave-btn" data-style="expand-right">
      	<span class="ladda-label">@lang( 'messages.save' )</span>
      </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
$(document).ready(function() {
    // Function to calculate per day salary
    function calculatePerDaySalary(monthlySalary, payPeriod) {
        if (payPeriod === 'month') {
            return monthlySalary / 30;
        } else if (payPeriod === 'week') {
            return monthlySalary / 7;
        } else if (payPeriod === 'day') {
            return monthlySalary;
        }
        return monthlySalary / 30;
    }

    // Function to calculate deduction
    function calculateDeduction() {
        var deductFromSalary = $('#deduct_from_salary').val();
        var perDaySalary = parseFloat($('#per_day_salary').val()) || 0;
        var leaveDays = parseInt($('#leave_days_count').val()) || 1;
        
        if (deductFromSalary === 'yes' && perDaySalary > 0) {
            var deduction = perDaySalary * leaveDays;
            $('#deduction_amount').val(deduction.toFixed(2));
        } else {
            $('#deduction_amount').val('0');
        }
    }

    // Function to fetch and update salary for selected employee
    function updateSalaryFields() {
        var selectedEmployees = $('#employees').val();
        
        if (selectedEmployees && selectedEmployees.length === 1) {
            var employeeId = selectedEmployees[0];
            
            // Fetch salary data via AJAX
            $.ajax({
                url: '/hrm/employee-salary/' + employeeId,
                method: 'GET',
                success: function(response) {
                    if (response.salary > 0) {
                        $('#employee_salary').val(response.salary);
                        var perDay = calculatePerDaySalary(response.salary, response.pay_period);
                        $('#per_day_salary').val(perDay.toFixed(2));
                    } else {
                        $('#employee_salary').val('');
                        $('#per_day_salary').val('');
                    }
                    calculateDeduction();
                },
                error: function() {
                    $('#employee_salary').val('');
                    $('#per_day_salary').val('');
                    calculateDeduction();
                }
            });
        } else {
            // Multiple or no employees selected - clear salary fields
            $('#employee_salary').val('');
            $('#per_day_salary').val('');
            calculateDeduction();
        }
    }

    // On employee selection change
    $('#employees').on('change', function() {
        updateSalaryFields();
    });

    // On deduct_from_salary change
    $('#deduct_from_salary').change(function() {
        calculateDeduction();
    });

    // On leave_days_count change
    $('#leave_days_count').change(function() {
        calculateDeduction();
    });

    // On per_day_salary manual change
    $('#per_day_salary').change(function() {
        calculateDeduction();
    });

    // On start/end date change - calculate leave days
    $('#start_date, #end_date').change(function() {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        
        if (startDate && endDate) {
            var start = new Date(startDate);
            var end = new Date(endDate);
            var diffTime = Math.abs(end - start);
            var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            if (diffDays > 0) {
                $('#leave_days_count').val(diffDays);
                calculateDeduction();
            }
        }
    });

    // Initialize on modal open
    $(document).on('shown.bs.modal', '.leave_modal, .modal', function() {
        updateSalaryFields();
    });
});
</script>