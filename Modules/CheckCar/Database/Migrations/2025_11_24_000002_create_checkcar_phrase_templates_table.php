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
        Schema::create('checkcar_phrase_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('section_key', 50)->index();
            $table->text('phrase');
            $table->unsignedBigInteger('element_id')->nullable()->index();
            $table->string('preset_key')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('element_id')
                ->references('id')
                ->on('checkcar_elements')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkcar_phrase_templates');
    }
};
