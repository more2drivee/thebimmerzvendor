<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEssentialsSalaryAdvancesTable extends Migration
{
    public function up()
    {
        Schema::create('essentials_salary_advances', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->decimal('amount', 22, 4)->default(0);
            $table->text('reason')->nullable();
            $table->date('request_date');
            $table->enum('status', ['pending', 'approved', 'rejected', 'deducted'])->default('pending');
            $table->integer('approved_by')->unsigned()->nullable();
            $table->date('approved_date')->nullable();
            $table->string('deduct_from_payroll')->nullable()->comment('month to deduct: YYYY-MM');
            $table->integer('created_by')->unsigned()->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('essentials_salary_advances');
    }
}
