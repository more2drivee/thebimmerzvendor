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
        if (Schema::hasTable('media') && Schema::hasColumn('media', 'image_tag_id')) {
            Schema::table('media', function (Blueprint $table) {
                try {
                    $table->dropIndex(['image_tag_id']);
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->dropColumn('image_tag_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('media') && !Schema::hasColumn('media', 'image_tag_id')) {
            Schema::table('media', function (Blueprint $table) {
                $table->unsignedInteger('image_tag_id')->nullable()->after('model_type');
                $table->index('image_tag_id');
            });
        }
    }
};
