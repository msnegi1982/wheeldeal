<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->nullableMorphs('verifiable');
            $table->string('phone', 20);
            $table->string('channel', 20);
            $table->string('purpose', 40)->default('vehicle_request');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['phone', 'channel', 'purpose']);
        });

        Schema::create('otp_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('otp_verification_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 40);
            $table->string('channel', 20);
            $table->string('status', 20)->default('queued')->index();
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_delivery_logs');
        Schema::dropIfExists('otp_verifications');
    }
};
