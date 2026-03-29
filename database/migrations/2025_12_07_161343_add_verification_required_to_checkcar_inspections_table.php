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
        Schema::table('checkcar_inspections', function (Blueprint $table) {
            $table->boolean('verification_required')->default(true)->after('contact_device_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('checkcar_inspections', function (Blueprint $table) {
            $table->dropColumn('verification_required');
        });
    }
};
