<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('business_location_id')->unsigned();
            $table->string('status', 50);
            $table->integer('business_id')->unsigned();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};
