<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Both start off: the barber chooses whether the public page shows the average and the comments. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_settings', function (Blueprint $table): void {
            $table->boolean('show_rating')->default(false);
            $table->boolean('show_reviews')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_settings', function (Blueprint $table): void {
            $table->dropColumn(['show_rating', 'show_reviews']);
        });
    }
};
