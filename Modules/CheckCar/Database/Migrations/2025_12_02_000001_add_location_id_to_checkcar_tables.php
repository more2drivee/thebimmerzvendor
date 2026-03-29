<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CheckCar master data tables
        Schema::table('checkcar_question_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('checkcar_question_categories', 'location_id')) {
                $table->unsignedInteger('location_id')->nullable()->after('id');
                $table->index('location_id');
            }
        });

        Schema::table('checkcar_question_subcategories', function (Blueprint $table) {
            if (!Schema::hasColumn('checkcar_question_subcategories', 'location_id')) {
                $table->unsignedInteger('location_id')->nullable()->after('id');
                $table->index(['category_id', 'location_id']);
            }
        });

        Schema::table('checkcar_elements', function (Blueprint $table) {
            if (!Schema::hasColumn('checkcar_elements', 'location_id')) {
                $table->unsignedInteger('location_id')->nullable()->after('id');
                $table->index('location_id');
            }
        });

        Schema::table('checkcar_element_options', function (Blueprint $table) {
            if (!Schema::hasColumn('checkcar_element_options', 'location_id')) {
                $table->unsignedInteger('location_id')->nullable()->after('id');
                $table->index('location_id');
            }
        });

        Schema::table('checkcar_phrase_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('checkcar_phrase_templates', 'location_id')) {
                $table->unsignedInteger('location_id')->nullable()->after('id');
                $table->index('location_id');
            }
        });

        // Detail tables that belong to a specific inspection/location
        Schema::table('checkcar_inspection_items', function (Blueprint $table) {
            if (!Schema::hasColumn('checkcar_inspection_items', 'location_id')) {
                $table->unsignedInteger('location_id')->nullable()->after('id');
                $table->index(['location_id', 'inspection_id']);
            }
        });

        Schema::table('checkcar_inspection_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('checkcar_inspection_documents', 'location_id')) {
                $table->unsignedInteger('location_id')->nullable()->after('id');
                $table->index(['location_id', 'inspection_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('checkcar_question_categories', function (Blueprint $table) {
            if (Schema::hasColumn('checkcar_question_categories', 'location_id')) {
                $table->dropIndex(['location_id']);
                $table->dropColumn('location_id');
            }
        });

        Schema::table('checkcar_question_subcategories', function (Blueprint $table) {
            if (Schema::hasColumn('checkcar_question_subcategories', 'location_id')) {
                $table->dropIndex(['category_id', 'location_id']);
                $table->dropColumn('location_id');
            }
        });

        Schema::table('checkcar_elements', function (Blueprint $table) {
            if (Schema::hasColumn('checkcar_elements', 'location_id')) {
                $table->dropIndex(['location_id']);
                $table->dropColumn('location_id');
            }
        });

        Schema::table('checkcar_element_options', function (Blueprint $table) {
            if (Schema::hasColumn('checkcar_element_options', 'location_id')) {
                $table->dropIndex(['location_id']);
                $table->dropColumn('location_id');
            }
        });

        Schema::table('checkcar_phrase_templates', function (Blueprint $table) {
            if (Schema::hasColumn('checkcar_phrase_templates', 'location_id')) {
                $table->dropIndex(['location_id']);
                $table->dropColumn('location_id');
            }
        });

        Schema::table('checkcar_inspection_items', function (Blueprint $table) {
            if (Schema::hasColumn('checkcar_inspection_items', 'location_id')) {
                $table->dropIndex(['location_id', 'inspection_id']);
                $table->dropColumn('location_id');
            }
        });

        Schema::table('checkcar_inspection_documents', function (Blueprint $table) {
            if (Schema::hasColumn('checkcar_inspection_documents', 'location_id')) {
                $table->dropIndex(['location_id', 'inspection_id']);
                $table->dropColumn('location_id');
            }
        });
    }
};
