<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateFlatRateServicesDropUnique extends Migration
{
    public function up()
    {
        // Try dropping by known index name; if not present, try by columns
        try {
            Schema::table('flat_rate_services', function (Blueprint $table) {
                $table->dropUnique('flat_rate_services_business_location_id_is_active_unique');
            });
        } catch (\Throwable $e) {
            try {
                Schema::table('flat_rate_services', function (Blueprint $table) {
                    $table->dropUnique(['business_location_id', 'is_active']);
                });
            } catch (\Throwable $e2) {
                // ignore if unable to drop (index may not exist)
            }
        }

        // Optional: add non-unique index for query performance
        try {
            Schema::table('flat_rate_services', function (Blueprint $table) {
                $table->index('business_location_id');
            });
        } catch (\Throwable $e) {
            // Index may already exist; ignore
        }
    }

    public function down()
    {
        Schema::table('flat_rate_services', function (Blueprint $table) {
            try {
                $table->unique(['business_location_id', 'is_active']);
            } catch (\Exception $e) {
                // Ignore if cannot re-create
            }
        });
    }
}