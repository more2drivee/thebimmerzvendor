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
        // Check if the table exists first
        if (!Schema::hasTable('product_compatibility')) {
            Schema::create('product_compatibility', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('product_id')->unsigned();
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->string('make')->nullable();
                $table->string('model')->nullable();
                $table->integer('from_year')->nullable();
                $table->integer('to_year')->nullable();
                $table->timestamps();
            });
        }

        // Add model_id column if it doesn't exist
        if (!Schema::hasColumn('product_compatibility', 'model_id')) {
            Schema::table('product_compatibility', function (Blueprint $table) {
                $table->integer('model_id')->unsigned()->nullable()->after('product_id');
                $table->foreign('model_id')->references('id')->on('repair_device_models')->onDelete('set null');
            });
        }

        // We'll keep the make and model columns for now to ensure backward compatibility
        // We'll migrate the data in a separate migration after testing
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('product_compatibility', 'model_id')) {
            Schema::table('product_compatibility', function (Blueprint $table) {
                $table->dropForeign(['model_id']);
                $table->dropColumn('model_id');
            });
        }

        // Since we're keeping the make and model columns, we don't need to add them back in the down method
    }
};
