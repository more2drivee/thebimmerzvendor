<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('labour_by_vehicle_products', function (Blueprint $table) {
            $table->decimal('price', 22, 4)->default(0)->after('product_id');
        });
    }

    public function down()
    {
        Schema::table('labour_by_vehicle_products', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
