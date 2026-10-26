<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Free links the barber orders on the public page, besides the fixed social networks. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title', 60);
            $table->string('url', 300);
            $table->string('icon', 20)->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->index(['tenant_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_links');
    }
};
