<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmVehiclesTable extends Migration
{
    public function up()
    {
        Schema::create('cm_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('seller_contact_id')->index();
            $table->unsignedBigInteger('created_by')->nullable();

            // Vehicle identity
            $table->string('vin_number', 50)->nullable();
            $table->string('plate_number', 30)->nullable();

            // Vehicle specs
            $table->string('make', 100);
            $table->string('model', 100);
            $table->integer('year');
            $table->string('trim_level', 100)->nullable();
            $table->enum('body_type', ['sedan', 'suv', 'coupe', 'hatchback', 'truck', 'van', 'convertible', 'wagon', 'pickup', 'other'])->default('sedan');
            $table->string('color', 50)->nullable();
            $table->integer('mileage_km')->nullable();
            $table->integer('engine_capacity_cc')->nullable();
            $table->integer('cylinder_count')->nullable();
            $table->enum('fuel_type', ['gas', 'diesel', 'electric', 'hybrid', 'natural_gas'])->default('gas');
            $table->enum('transmission', ['automatic', 'manual'])->default('automatic');

            // Condition
            $table->enum('condition', ['new', 'used'])->default('used');
            $table->boolean('factory_paint')->default(false);
            $table->boolean('imported_specs')->default(false);
            $table->enum('license_type', ['seller_owned', 'private', 'commercial'])->default('seller_owned');
            $table->text('condition_notes')->nullable();

            // Pricing
            $table->decimal('listing_price', 15, 2);
            $table->decimal('min_price', 15, 2)->nullable();
            $table->string('currency', 10)->default('EGP');

            // Ownership costs
            $table->decimal('license_3year_cost', 12, 2)->nullable();
            $table->decimal('insurance_annual_cost', 12, 2)->nullable();
            $table->decimal('insurance_rate_pct', 5, 2)->nullable();

            // Location
            $table->string('location_city', 100)->nullable();
            $table->string('location_area', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Listing management
            $table->enum('listing_status', ['draft', 'pending', 'active', 'sold', 'reserved', 'expired', 'rejected'])->default('draft');
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->text('description')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->unsignedBigInteger('buyer_contact_id')->nullable();
            $table->decimal('sold_price', 15, 2)->nullable();
            $table->string('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('seller_contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('buyer_contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['make', 'model', 'year']);
            $table->index(['listing_status', 'business_id']);
            $table->index(['condition', 'listing_price']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cm_vehicles');
    }
}
