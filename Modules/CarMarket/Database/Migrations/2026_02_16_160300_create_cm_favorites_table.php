<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmFavoritesTable extends Migration
{
    public function up()
    {
        Schema::create('cm_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->boolean('notify_price_change')->default(false);
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('id')->on('cm_vehicles')->onDelete('cascade');
            $table->unique(['contact_id', 'vehicle_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cm_favorites');
    }
}
