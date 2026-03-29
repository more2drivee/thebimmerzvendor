<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timer_stop_reasons', function (Blueprint $table) {
            $table->unsignedBigInteger('resumed_timer_id')->nullable()->after('timer_id');

            $table->foreign('resumed_timer_id')
                ->references('id')
                ->on('timer_tracking')
                ->onDelete('set null');

            $table->index('resumed_timer_id');
        });
    }

    public function down(): void
    {
        Schema::table('timer_stop_reasons', function (Blueprint $table) {
            $table->dropForeign(['resumed_timer_id']);
            $table->dropIndex(['resumed_timer_id']);
            $table->dropColumn('resumed_timer_id');
        });
    }
};
