<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkcar_elements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('type', 50)->default('text'); // single, multiple, text
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('required')->default(false);
            $table->unsignedInteger('max_options')->default(0); // 0 = unlimited
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')
                ->on('checkcar_question_categories')
                ->onDelete('cascade');

            $table->foreign('subcategory_id')
                ->references('id')
                ->on('checkcar_question_subcategories')
                ->onDelete('cascade');
                
            $table->index('sort_order');
            $table->index(['category_id', 'sort_order', 'active']);
            $table->index(['subcategory_id', 'sort_order', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkcar_elements');
    }
};
