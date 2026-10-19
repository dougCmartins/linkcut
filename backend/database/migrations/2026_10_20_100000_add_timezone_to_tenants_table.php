<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            // "Today" and "now" of the barbershop (agenda, month-to-date) are computed in this timezone.
            $table->string('timezone', 64)->default('America/Sao_Paulo')->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('timezone');
        });
    }
};
