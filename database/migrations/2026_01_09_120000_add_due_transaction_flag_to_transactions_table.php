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
        Schema::table('transactions', function (Blueprint $table) {
            $table->dateTime('general_due_date')->nullable()->after('repair_due_date')->comment('General due date for any transaction type');
            $table->boolean('is_due_transaction')->default(0)->after('general_due_date')->comment('Flag to mark transaction as due');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['general_due_date', 'is_due_transaction']);
        });
    }
};
