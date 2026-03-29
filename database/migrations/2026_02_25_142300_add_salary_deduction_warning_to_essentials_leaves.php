<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalaryDeductionWarningToEssentialsLeaves extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('essentials_leaves', function (Blueprint $table) {
            $table->decimal('employee_salary', 12, 2)->nullable()->after('reason');
            $table->decimal('per_day_salary', 12, 2)->nullable()->after('employee_salary');
            $table->enum('deduct_from_salary', ['yes', 'no'])->default('no')->nullable()->after('per_day_salary');
            $table->integer('leave_days_count')->default(0)->nullable()->after('deduct_from_salary');
            $table->decimal('deduction_amount', 12, 2)->default(0)->nullable()->after('leave_days_count');
            $table->enum('give_warning', ['yes', 'no'])->default('no')->nullable()->after('deduction_amount');
            $table->text('warning_note')->nullable()->after('give_warning');
            $table->string('warning_type')->nullable()->after('warning_note'); // verbal, written, final
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('essentials_leaves', function (Blueprint $table) {
            $table->dropColumn([
                'employee_salary',
                'per_day_salary',
                'deduct_from_salary',
                'leave_days_count',
                'deduction_amount',
                'give_warning',
                'warning_note',
                'warning_type',
            ]);
        });
    }
}
