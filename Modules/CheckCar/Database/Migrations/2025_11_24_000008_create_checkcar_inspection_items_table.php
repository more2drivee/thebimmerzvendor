<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkcar_inspection_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // Link to inspection
            $table->unsignedBigInteger('inspection_id');
            
            // Link to element (the question/check item)
            $table->unsignedBigInteger('element_id');

            // Optional custom title/label for this item instance
            $table->string('title')->nullable();

            // Raw option IDs (submitted payload) - option details retrieved from checkcar_element_options
            $table->json('option_ids')->nullable();

            // Inspector's note for this specific item
            $table->text('note')->nullable();

            // Stored media references (array of file metadata)
            $table->json('images')->nullable();
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('inspection_id')
                ->references('id')
                ->on('checkcar_inspections')
                ->onDelete('cascade');

            $table->foreign('element_id')
                ->references('id')
                ->on('checkcar_elements')
                ->onDelete('cascade');

            // Indexes
            $table->index(['inspection_id', 'element_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkcar_inspection_items');
    }
};
