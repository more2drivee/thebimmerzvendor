<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCheckcarServiceSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checkcar_service_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            // This actually references products table, so name it product_id
            $table->unsignedInteger('product_id')->nullable()->comment('Selected product for checkcar');
            $table->string('type', 50)->default('service')->comment('Context/type of service setting');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');

            // Ensure only one setting per business & type
            $table->unique(['business_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('checkcar_service_settings');
    }
}
