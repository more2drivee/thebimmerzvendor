<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabourByVehicleTable extends Migration
{
    public function up()
    {
        Schema::create('labour_by_vehicle', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('device_id')->nullable()->index();
            $table->integer('from')->nullable();
            $table->integer('to')->nullable();
            $table->unsignedInteger('repair_device_model_id')->nullable()->index();
            $table->timestamps();
            
            $table->foreign('device_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('repair_device_model_id')->references('id')->on('repair_device_models')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('labour_by_vehicle');
    }
}
