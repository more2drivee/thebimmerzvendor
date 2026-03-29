<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('types_of_services', function (Blueprint $table) {
            if (!Schema::hasColumn('types_of_services', 'is_inspection_service')) {
                $table->boolean('is_inspection_service')->default(false)->after('enable_custom_fields');
            }
        });
    }

    public function down(): void
    {
        Schema::table('types_of_services', function (Blueprint $table) {
            if (Schema::hasColumn('types_of_services', 'is_inspection_service')) {
                $table->dropColumn('is_inspection_service');
            }
        });
    }
};
