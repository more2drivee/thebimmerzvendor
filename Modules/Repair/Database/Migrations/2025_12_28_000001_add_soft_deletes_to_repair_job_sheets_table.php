<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_job_sheets', function (Blueprint $table) {
            if (!Schema::hasColumn('repair_job_sheets', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('repair_job_sheets', function (Blueprint $table) {
            if (Schema::hasColumn('repair_job_sheets', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
