<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEssentialsEmployeeBonusesDeductionsTable extends Migration
{
    public function up()
    {
        // Recurring bonuses that affect payroll
        Schema::create('essentials_employee_bonuses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->string('description');
            $table->decimal('amount', 22, 4)->default(0);
            $table->enum('amount_type', ['fixed', 'percent'])->default('fixed');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('apply_on', ['next_payroll', 'after_next', 'every_payroll'])->default('next_payroll');
            $table->enum('status', ['active', 'applied', 'cancelled'])->default('active');
            $table->integer('created_by')->unsigned()->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Recurring deductions that affect payroll
        Schema::create('essentials_employee_deductions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->string('description');
            $table->decimal('amount', 22, 4)->default(0);
            $table->enum('amount_type', ['fixed', 'percent'])->default('fixed');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('apply_on', ['next_payroll', 'after_next', 'every_payroll'])->default('next_payroll');
            $table->enum('status', ['active', 'applied', 'cancelled'])->default('active');
            $table->integer('created_by')->unsigned()->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('essentials_employee_deductions');
        Schema::dropIfExists('essentials_employee_bonuses');
    }
}
