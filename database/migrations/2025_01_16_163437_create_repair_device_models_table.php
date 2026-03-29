<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('repair_device_models', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->unsignedInteger('contact_id')->nullable(); // contact_id index, unsigned integer, nullable
            $table->unsignedInteger('models_id')->nullable(); // models_id index, unsigned integer, nullable
            $table->string('color', 255)->nullable(); // color, varchar with a length of 255 characters, nullable
            $table->string('chassis_number', 255)->nullable(); // chassis_number, varchar with a length of 255 characters, nullable
            $table->string('plate_number', 255)->nullable(); // plate_number, varchar with a length of 255 characters, nullable
            $table->year('manufacturing_year'); // manufacturing_year, year field, not nullable

            // Indexes for contact_id and models_id
            $table->index('contact_id');
            $table->index('models_id');

            // Timestamps (optional but typically included)
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
        Schema::dropIfExists('repair_device_models');
    }
};
