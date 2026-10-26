<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Devices of clients that asked to be told when the barbershop answers. Separate from the barbers' devices: the
 * same browser can be both (one row in each table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint', 500)->unique();
            $table->string('public_key', 255);
            $table->string('auth_token', 255);
            $table->string('content_encoding', 16)->default('aes128gcm');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_push_subscriptions');
    }
};
