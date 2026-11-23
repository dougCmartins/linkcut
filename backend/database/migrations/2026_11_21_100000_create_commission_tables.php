<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->cascadeOnDelete();
            // Tenths of a percent: 50% = 500, 4% = 40.
            $table->unsignedInteger('percent');
            $table->date('starts_on');
            $table->timestamps();

            $table->index(['tenant_id', 'service_id', 'staff_id', 'starts_on'], 'commission_rules_lookup_index');
        });

        Schema::create('commission_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('stage', 10); // before | after
            $table->string('kind', 10); // percent | fixed
            $table->string('applies_to', 20); // price | commission | appointment
            $table->unsignedInteger('value')->default(0);
            $table->boolean('active')->default(true);
            $table->date('starts_on');
            $table->string('system_key', 40)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'system_key']);
            $table->index(['tenant_id', 'staff_id', 'active'], 'commission_adjustments_active_index');
        });

        Schema::create('commission_closings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('from');
            $table->date('to');
            $table->timestamp('closed_at');
            $table->foreignId('closed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'from', 'to'], 'commission_closings_period_index');
        });

        Schema::create('commission_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('booking_request_id');
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('base_in_cents');
            // Effective percent in tenths (weighted when several services).
            $table->unsignedInteger('percent');
            $table->json('adjustments_json');
            $table->unsignedInteger('amount_in_cents');
            $table->foreignId('closing_id')->nullable()->constrained('commission_closings')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'booking_request_id']);
            $table->index(['tenant_id', 'staff_id', 'date'], 'commission_entries_staff_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_entries');
        Schema::dropIfExists('commission_closings');
        Schema::dropIfExists('commission_adjustments');
        Schema::dropIfExists('commission_rules');
    }
};
