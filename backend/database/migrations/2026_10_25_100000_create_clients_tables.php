<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clients of the barbershops. Global (no tenant_id): one account sees the appointments of every barbershop
 * where it booked. A client exists from the first booking; the password comes when it completes the sign-up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 60);
            $table->string('email', 120)->unique();
            $table->string('phone', 13);
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            // The single "complete your sign-up" e-mail was sent.
            $table->timestamp('registration_invited_at')->nullable();
            // The client asked not to receive reminders and invitations.
            $table->timestamp('emails_opted_out_at')->nullable();
            $table->timestamps();
        });

        // Same rotation as the barbers' refresh tokens (Identity), for the client sessions.
        Schema::create('client_refresh_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->uuid('family_id')->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        // Codes sent by e-mail to complete the sign-up or reset the password (only the HMAC is stored).
        Schema::create('client_email_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 16);
            $table->string('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['client_id', 'purpose']);
        });

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_email', 120)->default('');
            // The day-before reminder e-mail was sent.
            $table->timestamp('reminded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['client_email', 'reminded_at']);
        });
        Schema::dropIfExists('client_email_codes');
        Schema::dropIfExists('client_refresh_tokens');
        Schema::dropIfExists('clients');
    }
};
