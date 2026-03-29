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
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('is_callback')->default(false)->after('booking_note');
            $table->string('call_back_ref')->nullable()->after('is_callback');
            $table->integer('device_id')->nullable()->after('call_back_ref');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['is_callback', 'call_back_ref', 'device_id']);
        });
    }
};
