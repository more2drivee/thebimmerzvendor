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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('sub_sub_category_id')->unsigned()->nullable()->after('sub_category_id');
            $table->integer('sub_sub_sub_category_id')->unsigned()->nullable()->after('sub_sub_category_id');

            $table->foreign('sub_sub_category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('sub_sub_sub_category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['sub_sub_category_id']);
            $table->dropForeign(['sub_sub_sub_category_id']);
            $table->dropColumn(['sub_sub_category_id', 'sub_sub_sub_category_id']);
        });
    }
};
