<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference_no')->unique();
            $table->unsignedBigInteger('device_id'); // car brand (device/category)
            $table->unsignedBigInteger('repair_device_model_id')->nullable(); // car model
            $table->unsignedSmallInteger('manufacturing_year')->nullable();
            $table->enum('side_type', ['front_half', 'rear_half', 'left_quarter', 'right_quarter', 'full_body', 'other'])->default('other');
            $table->decimal('price', 22, 4)->default(0);
            $table->boolean('has_parts_left')->default(true);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('device_id');
            $table->index('repair_device_model_id');
            $table->index('location_id');
            $table->index('side_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundles');
    }
};
