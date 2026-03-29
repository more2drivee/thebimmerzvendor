<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_job_sheets', function (Blueprint $table) {
            if (!Schema::hasColumn('repair_job_sheets', 'workshop_id')) {
                $table->json('workshop_id')->nullable()->after('workshop_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repair_job_sheets', function (Blueprint $table) {
            if (Schema::hasColumn('repair_job_sheets', 'workshop_id')) {
                $table->dropColumn('workshop_id');
            }
        });
    }
};
