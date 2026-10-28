<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One review per completed visit. The first name is kept here so the public page does not read the booking. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('comment', 500)->nullable();
            $table->string('author_first_name', 60);
            $table->timestamp('published_at');
            $table->timestamp('hidden_at')->nullable();
            $table->unsignedTinyInteger('featured_position')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'published_at']);
            // Several rows may have no position (NULL). 1, 2 and 3 are unique per barbershop.
            $table->unique(['tenant_id', 'featured_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
