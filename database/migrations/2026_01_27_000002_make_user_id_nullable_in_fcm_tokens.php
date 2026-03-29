<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeUserIdNullableInFcmTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            // Make user_id nullable to support anonymous users
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            // Revert user_id to not nullable
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
}
