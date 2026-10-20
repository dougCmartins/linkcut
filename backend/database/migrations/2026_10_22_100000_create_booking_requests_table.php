<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('client_name', 60);
            $table->string('client_whatsapp', 13);
            // Services are only soft deleted, so the link always resolves.
            $table->foreignId('service_id')->constrained('services');
            // Copies at request time: the request keeps its name and length if the service changes later.
            $table->string('service_name', 30);
            $table->unsignedSmallInteger('duration_minutes');
            $table->date('date');
            $table->string('starts_at', 5);
            $table->string('ends_at', 5);
            $table->string('status', 16);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
    }
};
