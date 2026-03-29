<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_joborder', function (Blueprint $table) {
            $table->boolean('inventory_delivery')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('product_joborder', function (Blueprint $table) {
            $table->dropColumn('inventory_delivery');
        });
    }
};
