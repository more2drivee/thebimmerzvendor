<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWatermarkImageToCheckcarServiceSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('checkcar_service_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('checkcar_service_settings', 'watermark_image')) {
                $table->string('watermark_image')->nullable()->after('value');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('checkcar_service_settings', function (Blueprint $table) {
            if (Schema::hasColumn('checkcar_service_settings', 'watermark_image')) {
                $table->dropColumn('watermark_image');
            }
        });
    }
}
