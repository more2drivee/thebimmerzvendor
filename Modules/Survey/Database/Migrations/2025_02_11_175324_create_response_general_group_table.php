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
        Schema::create('response_general_group', function (Blueprint $table) {
            $table->id();

            $table->integer('number_of_fill');
            $table->integer('survey_id');
            $table->integer('question_id');
            $table->text('answer');

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
        Schema::dropIfExists('response_general_group');
    }
};
