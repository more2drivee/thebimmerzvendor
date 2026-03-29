<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabourByVehicleProductsTable extends Migration
{
    public function up()
    {
        Schema::create('labour_by_vehicle_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('labour_by_vehicle_id');
            $table->unsignedInteger('product_id');
            $table->decimal('price', 22, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('labour_by_vehicle_id')->references('id')->on('labour_by_vehicle')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            $table->unique(['labour_by_vehicle_id', 'product_id'], 'labour_vehicle_product_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('labour_by_vehicle_products');
    }
}
