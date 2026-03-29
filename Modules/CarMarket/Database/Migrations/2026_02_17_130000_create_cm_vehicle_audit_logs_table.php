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
        Schema::create('cm_vehicle_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->unsignedBigInteger('changed_by_contact_id')->nullable();
            $table->string('change_source', 30)->default('seller_api');
            $table->string('action', 80)->default('seller_updated_listing');
            $table->json('changed_fields')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'vehicle_id']);
            $table->index('vehicle_id');
            $table->index('change_source');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cm_vehicle_audit_logs');
    }
};
