<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkcar_element_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('element_id');
            $table->string('label');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('element_id')
                ->references('id')
                ->on('checkcar_elements')
                ->onDelete('cascade');
                
            $table->index('element_id');
            $table->index('sort_order');
            $table->index(['element_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkcar_element_options');
    }
};
