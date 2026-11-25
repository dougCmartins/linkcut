<?php

declare(strict_types=1);

use Domain\Team\Support\BackfillOwnerStaff;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'active']);
            $table->unique(['tenant_id', 'user_id']);
        });

        Schema::create('staff_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('staff_id')->unique()->constrained('staff')->cascadeOnDelete();
            $table->boolean('see_prices')->default(false);
            $table->boolean('see_own_commission')->default(false);
            $table->boolean('see_unit_agenda')->default(false);
            $table->timestamps();
        });

        Schema::create('staff_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('email');
            $table->string('token_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('token_hash');
            $table->index(['staff_id', 'accepted_at']);
        });

        BackfillOwnerStaff::run();
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_invites');
        Schema::dropIfExists('staff_permissions');
        Schema::dropIfExists('staff');
    }
};
