<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('generic_spare_parts', function (Blueprint $table) {
            $table->enum('type', ['virtual', 'client_flagged'])->default('virtual')->after('description');
        });
    }

    public function down()
    {
        Schema::table('generic_spare_parts', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
