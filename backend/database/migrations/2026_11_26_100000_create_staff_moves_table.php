<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A barber moved to another unit of the same brand (docs/decisoes/0003): a transfer, or a loan until a date.
 * Staff rows are never deleted (their commission entries cascade): a move turns one off and the other on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_moves', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('to_staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('status', 16);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_moves');
    }
};
