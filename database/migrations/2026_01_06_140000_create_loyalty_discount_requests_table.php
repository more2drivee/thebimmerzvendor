<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loyalty_discount_requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->integer('contact_id')->unsigned();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->integer('points_requested')->unsigned();
            $table->decimal('amount_discounted', 22, 4)->unsigned();
            $table->decimal('order_total', 22, 4)->unsigned();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->integer('transaction_id')->unsigned()->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->integer('approved_by')->unsigned()->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
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
        Schema::dropIfExists('loyalty_discount_requests');
    }
};
