<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Optional break (e.g. lunch) inside a barber's working day (decision 0002, P3). Existing rows get none. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_business_hours', function (Blueprint $table): void {
            $table->string('break_starts_at', 5)->nullable()->after('closes_at');
            $table->string('break_ends_at', 5)->nullable()->after('break_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('staff_business_hours', function (Blueprint $table): void {
            $table->dropColumn(['break_starts_at', 'break_ends_at']);
        });
    }
};
