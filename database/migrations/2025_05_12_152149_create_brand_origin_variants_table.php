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
        Schema::create('brand_origin_variants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vin_category_code', 18)->nullable();
            $table->unsignedInteger('parent_id');
            $table->string('country_of_origin', 50)->nullable();
            $table->timestamps();
            
            // Add foreign key constraint
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brand_origin_variants');
    }
};
