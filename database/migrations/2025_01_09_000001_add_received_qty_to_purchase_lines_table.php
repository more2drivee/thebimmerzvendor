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
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->decimal('received_qty', 22, 4)->default(0)->after('quantity')->comment('Quantity actually received from purchase');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('received_qty');
        });
    }
};
