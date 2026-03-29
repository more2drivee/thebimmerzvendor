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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'login_medium')) {
                $table->string('login_medium')->nullable()->after('language');
            }
            if (!Schema::hasColumn('users', 'social_id')) {
                $table->string('social_id')->nullable()->after('login_medium');
            }
            if (!Schema::hasColumn('users', 'temporary_token')) {
                $table->string('temporary_token')->nullable()->after('social_id');
            }
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
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
        Schema::table('users', function (Blueprint $table) {
            $columns = ['login_medium', 'social_id', 'temporary_token', 'email_verified_at'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
