<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The history: one row per concluded appointment, with the price and cost of that moment.
        Schema::create('revenue_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // Set when the entry comes from a booking request (stage 3); concluding twice never counts twice.
            $table->unsignedBigInteger('booking_request_id')->nullable();
            $table->date('date');
            $table->string('time', 5);
            $table->string('client_name', 80);
            $table->string('service_name', 60);
            $table->unsignedInteger('price_in_cents');
            $table->unsignedInteger('cost_in_cents')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'booking_request_id']);
            $table->index(['tenant_id', 'date', 'time']);
        });

        Schema::create('fixed_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->unsignedInteger('amount_in_cents');
            $table->unsignedTinyInteger('due_day');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'deleted_at']);
        });

        Schema::create('one_off_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('description', 40);
            $table->unsignedInteger('amount_in_cents');
            $table->date('date');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'deleted_at', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('one_off_expenses');
        Schema::dropIfExists('fixed_expenses');
        Schema::dropIfExists('revenue_entries');
    }
};
