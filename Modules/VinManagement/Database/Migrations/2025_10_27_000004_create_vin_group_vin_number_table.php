<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vin_group_vin_number', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('vin_group_id');
            $table->unsignedBigInteger('vin_number_id');
            $table->timestamps();

            $table->unique(['vin_group_id', 'vin_number_id']);
            $table->index('vin_group_id');
            $table->index('vin_number_id');

            $table->foreign('vin_group_id')
                ->references('id')->on('vin_groups')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('vin_number_id')
                ->references('id')->on('vin_numbers')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vin_group_vin_number');
    }
};