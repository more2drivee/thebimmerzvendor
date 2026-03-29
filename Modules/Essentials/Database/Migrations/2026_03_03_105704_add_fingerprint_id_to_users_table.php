<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFingerprintIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fingerprint_id', 100)
                ->nullable()
                ->after('id_proof_number')
                ->comment('Biometric / fingerprint device ID for attendance import lookup');

            $table->index('fingerprint_id', 'users_fingerprint_id_index');
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
            $table->dropIndex('users_fingerprint_id_index');
            $table->dropColumn('fingerprint_id');
        });
    }
}
