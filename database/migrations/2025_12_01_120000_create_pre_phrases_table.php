<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timer_pre_phrases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
        
            $table->enum('reason_type', ['record_reason', 'finishtimer', 'ignore'])->nullable();

            $table->text('body')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'type']);
            $table->index(['location_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timer_pre_phrases');
    }
};
