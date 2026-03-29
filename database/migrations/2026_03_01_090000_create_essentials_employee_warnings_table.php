<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEssentialsEmployeeWarningsTable extends Migration
{
    public function up()
    {
        Schema::create('essentials_employee_warnings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('issued_by')->unsigned();
            $table->enum('warning_type', ['verbal', 'written', 'final'])->default('verbal');
            $table->text('warning_note')->nullable();
            $table->date('warning_date');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('issued_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('essentials_employee_warnings');
    }
}
