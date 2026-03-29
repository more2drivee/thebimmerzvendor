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
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->longText('message_template');
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->json('roles')->nullable(); // store allowed roles as JSON (array of role IDs)
            $table->timestamps();

            $table->index('status');
            $table->index('created_by');
        });

     Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sms_message_id'); // matches sms_messages.id (BIGINT)
            
            // FIXED: Match INT UNSIGNED IDs in other tables
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('transaction_id')->nullable();
            $table->unsignedInteger('job_sheet_id')->nullable();

            $table->string('mobile')->nullable();
            $table->longText('message_content');
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('sms_message_id')
                ->references('id')->on('sms_messages')
                ->onDelete('cascade');

            $table->foreign('contact_id')
                ->references('id')->on('contacts')
                ->onDelete('set null');

            $table->foreign('transaction_id')
                ->references('id')->on('transactions')
                ->onDelete('set null');

            $table->foreign('job_sheet_id')
                ->references('id')->on('repair_job_sheets')
                ->onDelete('set null');

            // Indexes
            $table->index('contact_id');
            $table->index('transaction_id');
            $table->index('job_sheet_id');
            $table->index(['sms_message_id', 'status']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_messages');
    }
};
