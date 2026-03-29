<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('survey_settings', function (Blueprint $table) {
            $table->id();
            $table->string('active_theme')->default('light');
            $table->boolean('enable_intelligent')->default(false);
            $table->unsignedInteger('rating_threshold')->default(80);
            $table->text('facebook_url')->nullable();
            $table->text('instagram_url')->nullable();
            $table->text('google_review_url')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('survey_settings');
    }
};
