<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTimerTrackingTimeAllocateToDecimal extends Migration
{
    public function up()
    {
        Schema::table('timer_tracking', function (Blueprint $table) {
            $table->decimal('time_allocate', 10, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('timer_tracking', function (Blueprint $table) {
            $table->decimal('time_allocate', 10, 0)->nullable()->change();
        });
    }
}
