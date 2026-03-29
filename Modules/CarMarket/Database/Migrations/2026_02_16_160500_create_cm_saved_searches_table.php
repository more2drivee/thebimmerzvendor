<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmSavedSearchesTable extends Migration
{
    public function up()
    {
        Schema::create('cm_saved_searches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->string('name', 100)->nullable();
            $table->json('filters');
            $table->boolean('notify_new_matches')->default(false);
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cm_saved_searches');
    }
}
