<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeWarningsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_warnings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index();
            $table->integer('user_id')->index(); // employee who received warning
            $table->integer('issued_by')->index(); // user who issued warning
            $table->string('warning_type')->nullable(); // verbal, written, final
            $table->text('reason')->nullable();
            $table->date('warning_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_warnings');
    }
}
