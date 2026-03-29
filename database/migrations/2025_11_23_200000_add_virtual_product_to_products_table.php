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
        if (! Schema::hasColumn('products', 'virtual_product')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('virtual_product')->default(false)->after('enable_stock');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('products', 'virtual_product')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('virtual_product');
            });
        }
    }
};
