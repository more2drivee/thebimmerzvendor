<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('generic_spare_parts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
                        $table->enum('type', ['virtual', 'client_flagged'])->default('virtual')->after('description');

            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index('business_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('generic_spare_parts');
    }
};
