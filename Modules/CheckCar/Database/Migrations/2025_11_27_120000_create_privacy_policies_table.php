<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->text('content')->nullable();
            $table->timestamps();
            
            $table->foreign('business_id')
                ->references('id')
                ->on('business')
                ->onDelete('cascade');
                
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_policies');
    }
};
