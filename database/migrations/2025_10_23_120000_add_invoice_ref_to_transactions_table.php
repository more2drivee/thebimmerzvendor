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
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'invoice_ref')) {
                $table->unsignedInteger('invoice_ref')
                    ->nullable()
                    ->after('ref_no')
                    ->comment('References a related repair transaction');
                $table->index('invoice_ref');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'invoice_ref')) {
                $table->dropIndex('transactions_invoice_ref_index');
                $table->dropColumn('invoice_ref');
            }
        });
    }
};
