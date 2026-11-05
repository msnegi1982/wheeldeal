<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['brand_id', 'slug']);
            $table->index(['category_id', 'is_active']);
        });

        Schema::create('vehicle_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fuel_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('transmission', 20)->default('manual')->index();
            $table->unsignedInteger('engine_cc')->nullable();
            $table->decimal('power_bhp', 6, 2)->nullable();
            $table->unsignedInteger('torque_nm')->nullable();
            $table->decimal('mileage_kmpl', 5, 2)->nullable();
            $table->unsignedTinyInteger('seats')->nullable();
            $table->unsignedBigInteger('price_min')->nullable();
            $table->unsignedBigInteger('price_max')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['vehicle_model_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_variants');
        Schema::dropIfExists('vehicle_models');
    }
};
