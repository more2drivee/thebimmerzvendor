<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('response_general_group', function (Blueprint $table) {
            $table->decimal('score', 8, 2)->nullable()->after('answer');
        });
    }

    public function down()
    {
        Schema::table('response_general_group', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }
};
