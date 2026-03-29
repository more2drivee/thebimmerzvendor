<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToLabourByVehicleProductsTable extends Migration
{
    public function up()
    {
        Schema::table('labour_by_vehicle_products', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('product_id');
        });
    }

    public function down()
    {
        Schema::table('labour_by_vehicle_products', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}
