<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimerTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timer_tracking', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')
                    ->references('id')->on('business')
                    ->onDelete('cascade');

            $table->integer('job_sheet_id')->unsigned();
            $table->foreign('job_sheet_id')
                    ->references('id')->on('repair_job_sheets')
                    ->onDelete('cascade');

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('cascade');

            $table->enum('status', ['active', 'paused', 'completed'])->default('active');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->integer('total_paused_duration')->unsigned()->default(0); // seconds paused across the timer

            $table->text('notes')->nullable();

            $table->timestamps();

            // Index for performance
            $table->index(['business_id', 'job_sheet_id', 'user_id']);
            $table->index(['status', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('timer_tracking');
    }
}
