<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
   

        Schema::create('job_estimator', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estimate_no')->nullable()->unique();
            $table->integer('contact_id')->unsigned();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->integer('device_id')->unsigned();
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->integer('location_id')->unsigned();
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->integer('created_by')->unsigned();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->integer('service_type_id')->unsigned()->nullable();
            $table->enum('estimator_status', ['pending', 'sent', 'approved', 'rejected', 'converted_to_order'])->default('pending');
            $table->text('vehicle_details')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->boolean('send_sms')->default(false);
            $table->dateTime('sent_to_customer_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            
            $table->index(['business_id', 'location_id']);
            $table->index(['contact_id', 'estimator_status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_estimator');
    }
};
