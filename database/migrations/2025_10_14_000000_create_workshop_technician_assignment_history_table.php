<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_assignments', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->foreignId('workshop_id')->constrained('workshops')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('job_sheet_id')->nullable(); // For workshop-to-job assignments
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->enum('assignment_type', ['workshop', 'job_sheet'])->default('workshop');
            $table->json('metadata')->nullable(); // For storing additional context
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['workshop_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['job_sheet_id', 'status']);
            $table->index(['assignment_type', 'status']);
            $table->index(['status', 'created_at']);

            // Ensure either user_id or job_sheet_id is present
            $table->unique(['workshop_id', 'user_id', 'assignment_type', 'status']); // For workshop assignments
            $table->unique(['workshop_id', 'job_sheet_id', 'assignment_type', 'status']); // For job sheet assignments
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_assignments');
    }
};
