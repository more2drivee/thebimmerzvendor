<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'buyer_contact_id')) {
                $table->unsignedInteger('buyer_contact_id')->nullable()->after('contact_id');
                $table->foreign('buyer_contact_id')
                    ->references('id')
                    ->on('contacts')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'buyer_contact_id')) {
                $table->dropForeign(['buyer_contact_id']);
                $table->dropColumn('buyer_contact_id');
            }
        });
    }
};
