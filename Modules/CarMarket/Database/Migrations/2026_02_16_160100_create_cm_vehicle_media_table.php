<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmVehicleMediaTable extends Migration
{
    public function up()
    {
        Schema::create('cm_vehicle_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->enum('media_type', ['exterior', 'interior', 'engine', 'documents', 'other'])->default('exterior');
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->integer('file_size_kb')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('cm_vehicles')->onDelete('cascade');
            $table->index(['vehicle_id', 'display_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cm_vehicle_media');
    }
}
