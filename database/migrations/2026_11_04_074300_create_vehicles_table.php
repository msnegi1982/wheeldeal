<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('slug');
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_model_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('fuel_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->unsignedBigInteger('price')->index();
            $table->unsignedBigInteger('original_price')->nullable();
            $table->unsignedInteger('km_driven')->default(0)->index();
            $table->unsignedTinyInteger('owner_count')->default(1)->index();
            $table->string('transmission', 20)->default('manual')->index();
            $table->unsignedTinyInteger('seats')->default(5)->index();
            $table->string('colour', 50)->nullable()->index();
            $table->string('registration_state', 80)->nullable();
            $table->unsignedSmallInteger('registration_year')->nullable();
            $table->unsignedInteger('engine_cc')->nullable();
            $table->decimal('power_bhp', 6, 2)->nullable();
            $table->unsignedInteger('torque_nm')->nullable();
            $table->decimal('mileage_kmpl', 5, 2)->nullable();
            $table->longText('description')->nullable();
            $table->string('condition', 30)->default('good');
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_popular')->default(false)->index();
            $table->unsignedBigInteger('popularity_score')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['vehicle_model_id', 'slug']);
            $table->index(['status', 'published_at']);
            $table->index(['status', 'price']);
            $table->index(['status', 'year']);
            $table->index(['status', 'km_driven']);
            $table->index(['brand_id', 'status']);
            $table->index(['vehicle_model_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index(['fuel_type_id', 'status']);
            $table->index(['city_id', 'status']);
            $table->index(['transmission', 'status']);
            $table->index(['owner_count', 'status']);
            $table->index(['seats', 'status']);
            $table->index(['colour', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index(['popularity_score', 'status']);
        });

        Schema::create('feature_vehicle', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->primary(['vehicle_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_vehicle');
        Schema::dropIfExists('vehicles');
    }
};
