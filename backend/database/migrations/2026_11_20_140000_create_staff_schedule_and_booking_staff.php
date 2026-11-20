<?php

declare(strict_types=1);

use Domain\Team\Support\BackfillStaffSchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['staff_id', 'service_id']);
        });

        Schema::create('staff_business_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->boolean('is_open')->default(true);
            $table->string('opens_at', 5);
            $table->string('closes_at', 5);
            $table->timestamps();

            $table->unique(['staff_id', 'weekday']);
            $table->index(['tenant_id', 'staff_id']);
        });

        Schema::create('staff_time_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('recurrence', 16)->nullable();
            $table->json('weekdays')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'staff_id', 'starts_at']);
        });

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->foreignId('staff_id')->nullable()->after('tenant_id')->constrained('staff')->restrictOnDelete();
        });

        BackfillStaffSchedule::run();

        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->foreignId('staff_id')->nullable(false)->change();
            $table->index(['tenant_id', 'staff_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'staff_id', 'date']);
            $table->dropConstrainedForeignId('staff_id');
        });
        Schema::dropIfExists('staff_time_blocks');
        Schema::dropIfExists('staff_business_hours');
        Schema::dropIfExists('staff_services');
    }
};
