<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('pickup_latitude', 10, 8)->nullable()->after('job_estimator_id');
            $table->decimal('pickup_longitude', 11, 8)->nullable()->after('pickup_latitude');
            $table->text('pickup_address')->nullable()->after('pickup_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['pickup_latitude', 'pickup_longitude', 'pickup_address']);
        });
    }
};
