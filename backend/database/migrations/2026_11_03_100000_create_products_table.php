<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The barbershop's product showcase. No stock and no payment: a photo, a price and a short description. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->unsignedInteger('price_in_cents');
            $table->string('description', 160)->nullable();
            $table->foreignId('image_id')->nullable()->constrained('images')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index(['tenant_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
