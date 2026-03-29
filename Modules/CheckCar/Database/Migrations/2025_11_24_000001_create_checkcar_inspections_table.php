<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkcar_inspections', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Location and ownership
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            // Linked to booking and job sheet
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('job_sheet_id')->nullable();

            // Linked contacts & vehicle
            $table->unsignedBigInteger('buyer_contact_id')->nullable();
            $table->unsignedBigInteger('seller_contact_id')->nullable();
            $table->unsignedInteger('contact_device_id')->nullable();

            // Inspection team (inspectors / technicians)
            $table->json('inspection_team')->nullable();

            // Legacy: Structured sections JSON (kept for backward compatibility/quick summary)
            // New approach: Use checkcar_inspection_items table for detailed element responses
            $table->json('sections')->nullable();

            // Final report
            $table->text('final_summary')->nullable();
            $table->unsignedTinyInteger('overall_rating')->nullable();
            $table->string('status', 50)->default('draft');
            $table->string('share_token')->nullable()->unique();

            $table->timestamps();

            $table->index('location_id');
            $table->index('created_by');
            $table->index('booking_id');
            $table->index('job_sheet_id');
            $table->index('buyer_contact_id');
            $table->index('seller_contact_id');
            $table->index('contact_device_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkcar_inspections');
    }
};
