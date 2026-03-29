<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkcar_inspections', function (Blueprint $table) {
            // Add booking_id and job_sheet_id if they don't exist
            if (!Schema::hasColumn('checkcar_inspections', 'booking_id')) {
                $table->unsignedBigInteger('booking_id')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('checkcar_inspections', 'job_sheet_id')) {
                $table->unsignedBigInteger('job_sheet_id')->nullable()->after('booking_id');
            }
            
            // Add indexes if they don't exist
            if (!Schema::hasColumn('checkcar_inspections', 'booking_id')) {
                $table->index('booking_id');
            }
            if (!Schema::hasColumn('checkcar_inspections', 'job_sheet_id')) {
                $table->index('job_sheet_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checkcar_inspections', function (Blueprint $table) {
            $table->dropIndexIfExists(['booking_id']);
            $table->dropIndexIfExists(['job_sheet_id']);
            $table->dropColumn(['booking_id', 'job_sheet_id']);
        });
    }
};
