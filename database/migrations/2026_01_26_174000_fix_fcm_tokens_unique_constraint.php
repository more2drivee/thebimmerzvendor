<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixFcmTokensUniqueConstraint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            // Drop the existing unique constraint on token only
            $table->dropUnique('fcm_tokens_token_unique');
            
            // Add a composite unique constraint on user_id and token
            // This allows the same token for different users but prevents duplicates for the same user
            $table->unique(['user_id', 'token'], 'fcm_tokens_user_token_unique');
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
            // Drop the composite unique constraint
            $table->dropUnique('fcm_tokens_user_token_unique');
            
            // Restore the original unique constraint on token only
            $table->unique('token', 'fcm_tokens_token_unique');
        });
    }
}