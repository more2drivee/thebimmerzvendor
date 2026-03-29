<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmVehicleInquiriesTable extends Migration
{
    public function up()
    {
        Schema::create('cm_vehicle_inquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('buyer_contact_id');
            $table->enum('inquiry_type', ['whatsapp', 'call', 'email', 'in_app', 'other'])->default('in_app');
            $table->enum('status', ['new', 'contacted', 'negotiating', 'closed_won', 'closed_lost'])->default('new');
            $table->text('message')->nullable();
            $table->text('seller_reply')->nullable();
            $table->decimal('offered_price', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('id')->on('cm_vehicles')->onDelete('cascade');
            $table->foreign('buyer_contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->index(['vehicle_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cm_vehicle_inquiries');
    }
}
