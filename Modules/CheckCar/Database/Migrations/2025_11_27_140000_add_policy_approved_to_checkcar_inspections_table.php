<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkcar_inspections', function (Blueprint $table) {
            // Policy approved flag for privacy/terms acceptance
            $table->boolean('policy_approved')->default(false)->after('share_token');
        });
    }

    public function down(): void
    {
        Schema::table('checkcar_inspections', function (Blueprint $table) {
            $table->dropColumn('policy_approved');
        });
    }
};
