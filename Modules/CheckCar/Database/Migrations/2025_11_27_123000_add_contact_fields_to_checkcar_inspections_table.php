<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('checkcar_inspections')) {
            Schema::table('checkcar_inspections', function (Blueprint $table) {
                // Drop old verbose buyer/seller/car columns if they still exist
                if (Schema::hasColumn('checkcar_inspections', 'buyer_full_name')) {
                    $table->dropColumn('buyer_full_name');
                }
                if (Schema::hasColumn('checkcar_inspections', 'buyer_phone')) {
                    $table->dropColumn('buyer_phone');
                }
                if (Schema::hasColumn('checkcar_inspections', 'buyer_id_number')) {
                    $table->dropColumn('buyer_id_number');
                }
                if (Schema::hasColumn('checkcar_inspections', 'seller_full_name')) {
                    $table->dropColumn('seller_full_name');
                }
                if (Schema::hasColumn('checkcar_inspections', 'seller_phone')) {
                    $table->dropColumn('seller_phone');
                }
                if (Schema::hasColumn('checkcar_inspections', 'seller_id_number')) {
                    $table->dropColumn('seller_id_number');
                }
                if (Schema::hasColumn('checkcar_inspections', 'car_brand')) {
                    $table->dropColumn('car_brand');
                }
                if (Schema::hasColumn('checkcar_inspections', 'car_model')) {
                    $table->dropColumn('car_model');
                }
                if (Schema::hasColumn('checkcar_inspections', 'car_color')) {
                    $table->dropColumn('car_color');
                }
                if (Schema::hasColumn('checkcar_inspections', 'car_year')) {
                    $table->dropColumn('car_year');
                }
                if (Schema::hasColumn('checkcar_inspections', 'car_chassis_number')) {
                    $table->dropColumn('car_chassis_number');
                }
                if (Schema::hasColumn('checkcar_inspections', 'car_plate_number')) {
                    $table->dropColumn('car_plate_number');
                }
                if (Schema::hasColumn('checkcar_inspections', 'car_kilometers')) {
                    $table->dropColumn('car_kilometers');
                }

                if (!Schema::hasColumn('checkcar_inspections', 'buyer_contact_id')) {
                    $table->unsignedBigInteger('buyer_contact_id')->nullable()->after('created_by');
                    $table->index('buyer_contact_id');
                }

                if (!Schema::hasColumn('checkcar_inspections', 'seller_contact_id')) {
                    $table->unsignedBigInteger('seller_contact_id')->nullable()->after('buyer_contact_id');
                    $table->index('seller_contact_id');
                }

                if (!Schema::hasColumn('checkcar_inspections', 'contact_device_id')) {
                    $table->unsignedInteger('contact_device_id')->nullable()->after('seller_contact_id');
                    $table->index('contact_device_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('checkcar_inspections')) {
            Schema::table('checkcar_inspections', function (Blueprint $table) {
                if (Schema::hasColumn('checkcar_inspections', 'buyer_contact_id')) {
                    $table->dropIndex(['buyer_contact_id']);
                    $table->dropColumn('buyer_contact_id');
                }

                if (Schema::hasColumn('checkcar_inspections', 'seller_contact_id')) {
                    $table->dropIndex(['seller_contact_id']);
                    $table->dropColumn('seller_contact_id');
                }

                if (Schema::hasColumn('checkcar_inspections', 'contact_device_id')) {
                    $table->dropIndex(['contact_device_id']);
                    $table->dropColumn('contact_device_id');
                }
            });
        }
    }
};
