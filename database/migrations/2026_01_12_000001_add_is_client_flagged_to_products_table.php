<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->tinyInteger('is_client_flagged')->default(0)->after('virtual_product')->comment('1 if client flagged product with enable_stock=0 and price=0');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_client_flagged');
        });
    }
};
