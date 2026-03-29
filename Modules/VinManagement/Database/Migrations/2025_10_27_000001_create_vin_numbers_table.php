<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVinNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vin_numbers', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Foreign keys
            $table->unsignedInteger('car_brand');
            $table->foreign('car_brand')
                ->references('id')->on('categories');

            $table->unsignedInteger('car_model');
            $table->foreign('car_model')
                ->references('id')->on('repair_device_models');

            // VIN details
            $table->string('color')->nullable();
            $table->string('vin_number');

            $table->timestamps();

            // Indexes
            $table->index('car_brand');
            $table->index('car_model');
            $table->unique('vin_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vin_numbers');
    }
}