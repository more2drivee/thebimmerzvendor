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
        Schema::table('action', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
        });

        Schema::table('old_surveys', function (Blueprint $table) {
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('old_survey_id')->references('id')->on('surveys')->onDelete('cascade');
        });

        Schema::table('old_questions', function (Blueprint $table) {
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
        });

        Schema::table('responses', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
            $table->foreign('old_survey_id')->references('id')->on('surveys')->onDelete('cascade');
        });

        Schema::table('user_group', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });

        Schema::table('general_group', function (Blueprint $table) {
            $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
        });

        Schema::table('response_general_group', function (Blueprint $table) {
            $table->foreign('number_of_fill')->references('number_of_fill')->on('general_group')->onDelete('cascade');
            $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('action', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['survey_id']);
        });

        Schema::table('old_surveys', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['old_survey_id']);
        });

        Schema::table('old_questions', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['survey_id']);
        });

        Schema::table('responses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['survey_id']);
            $table->dropForeign(['question_id']);
            $table->dropForeign(['old_survey_id']);
        });

        Schema::table('user_group', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['group_id']);
        });

        Schema::table('general_group', function (Blueprint $table) {
            $table->dropForeign(['survey_id']);
        });

        Schema::table('response_general_group', function (Blueprint $table) {
            $table->dropForeign(['number_of_fill']);
            $table->dropForeign(['survey_id']);
            $table->dropForeign(['question_id']);
        });
    }
};
