<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_note', function (Blueprint $table) {
            if (! Schema::hasColumn('maintenance_note', 'job_estimator_id')) {
                $table->unsignedInteger('job_estimator_id')->nullable()->after('job_sheet_id');

                $table->foreign('job_estimator_id')
                    ->references('id')->on('job_estimator')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_note', function (Blueprint $table) {
            if (Schema::hasColumn('maintenance_note', 'job_estimator_id')) {
                $table->dropForeign(['job_estimator_id']);
                $table->dropColumn('job_estimator_id');
            }
        });
    }
};
