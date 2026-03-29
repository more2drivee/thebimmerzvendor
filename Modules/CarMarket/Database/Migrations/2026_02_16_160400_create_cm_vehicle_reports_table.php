<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmVehicleReportsTable extends Migration
{
    public function up()
    {
        Schema::create('cm_vehicle_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('reported_by_contact_id');
            $table->enum('reason', ['fake_listing', 'wrong_price', 'wrong_info', 'fraud', 'duplicate', 'other'])->default('other');
            $table->text('details')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'resolved', 'dismissed'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('cm_vehicles')->onDelete('cascade');
            $table->foreign('reported_by_contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cm_vehicle_reports');
    }
}
