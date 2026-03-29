<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('products', 'serviceHours')) {
            // Use raw SQL to avoid requiring doctrine/dbal
            DB::statement('ALTER TABLE `products` MODIFY `serviceHours` DECIMAL(8,2) NULL');
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('serviceHours', 8, 2)->nullable()->after('selling_price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'serviceHours')) {
            // Revert to DOUBLE to avoid precision loss while dropping decimal specifics
            DB::statement('ALTER TABLE `products` MODIFY `serviceHours` DOUBLE NULL');
        }
    }
};