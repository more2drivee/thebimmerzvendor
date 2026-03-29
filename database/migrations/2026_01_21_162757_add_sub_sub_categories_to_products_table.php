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
            $table->unsignedBigInteger('sub_sub_category_id')->nullable()->after('sub_category_id');
            $table->unsignedBigInteger('sub_sub_sub_category_id')->nullable()->after('sub_sub_category_id');
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
            $table->dropColumn(['sub_sub_category_id', 'sub_sub_sub_category_id']);
        });
    }
};
