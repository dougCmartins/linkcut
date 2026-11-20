<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The team of each unit (docs/decisoes/0001 P3): its barbers, what each one may see and the e-mail invitations.
 * Every unit already has one barber: its owner, created here for the barbershops that exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name', 60);
            $table->string('email', 120);
            $table->foreignId('avatar_image_id')->nullable()->constrained('images')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'active']);
            $table->unique(['tenant_id', 'email']);
        });

        Schema::create('staff_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->unique()->constrained('staff')->cascadeOnDelete();
            $table->boolean('see_prices')->default(false);
            $table->boolean('see_own_commission')->default(false);
            $table->boolean('see_unit_agenda')->default(false);
            $table->timestamps();
        });

        Schema::create('staff_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        $this->createTheOwnerOfEachUnit();
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_invitations');
        Schema::dropIfExists('staff_permissions');
        Schema::dropIfExists('staff');
    }

    private function createTheOwnerOfEachUnit(): void
    {
        DB::table('users')->where('role', 'owner')->orderBy('id')->lazyById()->each(function (object $owner): void {
            $now = now();
            $staffId = DB::table('staff')->insertGetId([
                'tenant_id' => $owner->tenant_id,
                'user_id' => $owner->id,
                'name' => mb_substr((string) $owner->name, 0, 60),
                'email' => $owner->email,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('staff_permissions')->insert(['tenant_id' => $owner->tenant_id, 'staff_id' => $staffId, 'created_at' => $now, 'updated_at' => $now]);
        });
    }
};
