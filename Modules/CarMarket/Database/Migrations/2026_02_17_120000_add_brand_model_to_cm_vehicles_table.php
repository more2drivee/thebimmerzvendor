<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cm_vehicles', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_category_id')->nullable()->after('plate_number');
            $table->unsignedBigInteger('repair_device_model_id')->nullable()->after('brand_category_id');
            
            $table->foreign('brand_category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('repair_device_model_id')->references('id')->on('repair_device_models')->onDelete('set null');
            
            // Index for faster queries
            $table->index(['brand_category_id', 'repair_device_model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cm_vehicles', function (Blueprint $table) {
            $table->dropForeign(['brand_category_id']);
            $table->dropForeign(['repair_device_model_id']);
            $table->dropColumn(['brand_category_id', 'repair_device_model_id']);
        });
    }
};
