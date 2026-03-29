<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('action', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('survey_id');
            $table->time('timesend');
            $table->string('type_form');
            $table->boolean('seen')->default(0);
            $table->boolean('fill')->default(0);
            $table->string('user_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('action');
    }
};
