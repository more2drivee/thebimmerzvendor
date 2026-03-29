<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->integer('bundle_id')->unsigned()->nullable()->after('variation_id');
            $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropForeign(['bundle_id']);
            $table->dropColumn('bundle_id');
        });
    }
};
