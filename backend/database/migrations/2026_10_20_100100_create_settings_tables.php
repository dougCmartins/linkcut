<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Settings domain: how the barbershop presents itself and the rules it sets for booking. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('mime', 30);
            $table->unsignedInteger('size');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('profile_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->string('handle', 30)->default('');
            $table->string('bio', 160)->default('');
            $table->foreignId('avatar_image_id')->nullable()->constrained('images')->nullOnDelete();
            $table->foreignId('background_image_id')->nullable()->constrained('images')->nullOnDelete();
            $table->string('whatsapp_number', 20)->default('');
            $table->string('whatsapp_message', 200)->default('');
            $table->timestamps();
        });

        Schema::create('social_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('network', 20);
            $table->string('url')->nullable();
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['tenant_id', 'network']);
        });

        Schema::create('portfolio_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('cover_image_id')->nullable()->constrained('images')->nullOnDelete();
            $table->string('quote', 200)->default('');
            $table->text('story');
            $table->timestamps();
        });

        Schema::create('portfolio_highlights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('label', 16)->default('');
            $table->string('value', 24)->default('');
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->index(['tenant_id', 'position']);
        });

        Schema::create('portfolio_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('image_id')->constrained('images')->cascadeOnDelete();
            $table->string('alt', 80);
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->index(['tenant_id', 'position']);
        });

        Schema::create('theme_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('palette', 20);
            $table->string('mode', 10);
            $table->string('custom_accent', 7)->nullable();
            $table->string('button_shape', 10);
            $table->string('button_style', 20);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30);
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedInteger('price_in_cents');
            // Private to the barber: never returned by public endpoints.
            $table->unsignedInteger('cost_in_cents')->default(0);
            $table->unsignedTinyInteger('position');
            $table->timestamps();
            // Kept (soft deleted) so past booking requests still point to it.
            $table->softDeletes();

            $table->index(['tenant_id', 'deleted_at', 'position']);
        });

        Schema::create('business_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // 0 = Sunday … 6 = Saturday
            $table->unsignedTinyInteger('weekday');
            $table->boolean('is_open');
            $table->string('opens_at', 5);
            $table->string('closes_at', 5);
            $table->timestamps();

            $table->unique(['tenant_id', 'weekday']);
        });

        Schema::create('booking_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_interval_minutes');
            $table->unsignedTinyInteger('booking_window_days');
            $table->boolean('vacation_enabled')->default(false);
            $table->date('vacation_starts_on')->nullable();
            $table->date('vacation_ends_on')->nullable();
            $table->string('vacation_message', 160)->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['booking_settings', 'business_hours', 'services', 'theme_settings', 'portfolio_photos',
            'portfolio_highlights', 'portfolio_settings', 'social_links', 'profile_settings', 'images'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
