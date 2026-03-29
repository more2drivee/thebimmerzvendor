<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_note', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('title')->nullable();
            $table->text('content')->nullable();
            $table->unsignedInteger('job_sheet_id')->nullable();
            $table->unsignedInteger('job_estimator_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('device_id')->nullable();
            $table->enum('category_status', ['note', 'comment', 'purchase_req'])->default('note');
            $table->enum('status', ['awaiting_reply', 'approved'])->default('awaiting_reply');
            $table->timestamps();

            $table->foreign('title')
                ->references('id')->on('repair_statuses')
                ->onDelete('set null');
            $table->foreign('job_sheet_id')
                ->references('id')->on('repair_job_sheets')
                ->onDelete('cascade');
            $table->foreign('job_estimator_id')
                ->references('id')->on('job_estimator')
                ->onDelete('cascade');
            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onDelete('set null');
            $table->foreign('device_id')
                ->references('id')->on('contact_device')
                ->onDelete('set null');

            $table->index('category_status');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_note');
    }
};
