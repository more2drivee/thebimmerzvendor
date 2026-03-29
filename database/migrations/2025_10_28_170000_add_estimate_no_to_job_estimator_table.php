<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_estimator', function (Blueprint $table) {
            if (! Schema::hasColumn('job_estimator', 'estimate_no')) {
                $table->string('estimate_no')->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_estimator', function (Blueprint $table) {
            if (Schema::hasColumn('job_estimator', 'estimate_no')) {
                $table->dropUnique(['estimate_no']);
                $table->dropColumn('estimate_no');
            }
        });
    }
};
