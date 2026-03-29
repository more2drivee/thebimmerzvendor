<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkcar_inspection_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('inspection_id');
            $table->string('party', 20); // buyer or seller
            $table->string('document_type', 50); // e.g. license, id, signature, license_front, etc.
            $table->string('file_path');
            $table->string('mime_type', 50)->nullable();
            $table->timestamps();

            $table->foreign('inspection_id')
                ->references('id')
                ->on('checkcar_inspections')
                ->onDelete('cascade');

            $table->index(['inspection_id', 'party', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkcar_inspection_documents');
    }
};
