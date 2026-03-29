<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFlatRateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flat_rate_services', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id');
            $table->integer('business_location_id');
            $table->string('name');
            $table->decimal('price_per_hour', 8, 2); // Flat rate hours
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('cascade');

            // Ensure only one active flat rate per location
            $table->unique(['business_location_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flat_rate_services');
    }
}
