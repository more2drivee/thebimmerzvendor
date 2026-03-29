<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicePredictionsTable extends Migration
{
    public function up()
    {
        Schema::create('service_predictions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('device_id')->nullable()->comment('contact_device.id (car)');
            $table->unsignedBigInteger('service_category_id')->nullable()->comment('product category id');
            $table->unsignedBigInteger('service_product_id')->nullable()->comment('specific product id');
            $table->integer('total_services_count')->default(0);
            $table->integer('avg_interval_months')->default(6);
            $table->integer('window_size_used')->default(3);
            $table->date('last_service_date')->nullable();
            $table->bigInteger('last_km')->nullable();
            $table->bigInteger('avg_km_interval')->nullable();
            $table->date('next_expected_date')->nullable();
            $table->bigInteger('next_expected_km')->nullable();
            $table->enum('status', ['on_time', 'due', 'overdue'])->default('on_time');
            $table->integer('overdue_months')->default(0);
            $table->enum('behavior_trend', ['stable', 'increasing', 'decreasing'])->default('stable');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->integer('reminder_level')->default(0)->comment('0=none,1=gentle,2=strong,3=discount,4=call');
            $table->timestamps();

            $table->index(['business_id', 'status'], 'idx_business_status');
            $table->index('contact_id', 'idx_contact');
            $table->index('next_expected_date', 'idx_next_date');
            $table->index('device_id', 'idx_device');
            $table->unique(['business_id', 'contact_id', 'device_id', 'service_category_id'], 'uq_prediction');

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_predictions');
    }
}
