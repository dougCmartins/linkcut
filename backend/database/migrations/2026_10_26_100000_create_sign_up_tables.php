<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Open sign-up of barbers: e-mail codes, verified e-mail and the end of the onboarding wizard. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sign_up_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 120)->unique();
            $table->string('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('sent_at');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            // Null while the barber has not finished the wizard (WhatsApp, address, services).
            $table->timestamp('onboarded_at')->nullable()->after('timezone');
        });
        // Barbershops that already exist are set up.
        DB::table('tenants')->update(['onboarded_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('tenants', fn (Blueprint $table) => $table->dropColumn('onboarded_at'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('email_verified_at'));
        Schema::dropIfExists('sign_up_codes');
    }
};
