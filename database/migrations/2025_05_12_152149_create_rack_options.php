<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRackOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rack_options', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('business_id');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
        });

        // Add rack_option_id to product_racks table
        Schema::table('product_racks', function (Blueprint $table) {
            $table->unsignedInteger('rack_option_id')->nullable()->after('rack');
            $table->foreign('rack_option_id')->references('id')->on('rack_options')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_racks', function (Blueprint $table) {
            $table->dropForeign(['rack_option_id']);
            $table->dropColumn('rack_option_id');
        });

        Schema::dropIfExists('rack_options');
    }
}
