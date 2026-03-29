<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_category_id')->nullable()->after('type');
            $table->foreign('survey_category_id')->references('id')->on('survey_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropForeign(['survey_category_id']);
            $table->dropColumn('survey_category_id');
        });
    }
};
