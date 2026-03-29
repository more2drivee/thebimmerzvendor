<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vin_numbers', function (Blueprint $table) {
            // Year: numeric, allow null initially
            $table->unsignedSmallInteger('year')->nullable()->after('color');
            // Manufacturer: text
            $table->string('manufacturer')->nullable()->after('year');
            // Car Type: enum of supported options
            $table->enum('car_type', [
                'Sedan','SUV','Truck','Hatchback','Coupe','Convertible','Van','Other'
            ])->nullable()->after('manufacturer');
            // Transmission: enum of supported options
            $table->enum('transmission', [
                'Automatic','Manual','CVT','Dual-Clutch','Other'
            ])->nullable()->after('car_type');

            // Helpful indexes for filtering/sorting
            $table->index(['year']);
            $table->index(['manufacturer']);
            $table->index(['car_type']);
            $table->index(['transmission']);
        });
    }

    public function down(): void
    {
        Schema::table('vin_numbers', function (Blueprint $table) {
            $table->dropIndex(['year']);
            $table->dropIndex(['manufacturer']);
            $table->dropIndex(['car_type']);
            $table->dropIndex(['transmission']);

            $table->dropColumn(['year','manufacturer','car_type','transmission']);
        });
    }
};