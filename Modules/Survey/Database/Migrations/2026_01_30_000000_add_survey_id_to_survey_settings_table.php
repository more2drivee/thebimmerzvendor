<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('survey_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_id')->nullable()->after('id');
            $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('survey_settings', function (Blueprint $table) {
            $table->dropForeign(['survey_id']);
            $table->dropColumn('survey_id');
        });
    }
};
