<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnonymousUserSupportToFcmTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->string('anonymous_user_id')->nullable()->after('user_id')->index();
            $table->json('subscribed_topics')->nullable()->after('device_info');
            $table->string('auth_type')->default('authenticated')->after('subscribed_topics')->comment('authenticated, anonymous');
            
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->nullable()->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['anonymous_user_id', 'is_active']);
            $table->index(['auth_type', 'is_active']);
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
            $table->dropIndex(['anonymous_user_id', 'is_active']);
            $table->dropIndex(['auth_type', 'is_active']);
            $table->dropColumn(['anonymous_user_id', 'subscribed_topics', 'auth_type']);
            
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->constrained('users')->onDelete('cascade');
        });
    }
}
