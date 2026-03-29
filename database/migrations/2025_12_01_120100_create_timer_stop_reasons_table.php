<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timer_stop_reasons', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('timer_id')->nullable();
            $table->unsignedBigInteger('phrase_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->enum('reason_type', ['record_reason', 'finishtimer', 'ignore'])->nullable();

            $table->text('body')->nullable();
            $table->timestamp('pause_start')->nullable();
            $table->timestamp('pause_end')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('timer_id')->references('id')->on('timer_tracking')->onDelete('set null');
            $table->foreign('phrase_id')->references('id')->on('timer_pre_phrases')->onDelete('set null');
            $table->index('is_active');
            $table->index('timer_id');
            $table->index('phrase_id');
            $table->index(['location_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timer_stop_reasons');
    }
};
