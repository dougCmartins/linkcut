<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Address of the barbershop (shown to clients when they book) and its point on the map. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_settings', function (Blueprint $table): void {
            $table->string('address_zip_code', 8)->default('');
            $table->string('address_street', 120)->default('');
            $table->string('address_number', 10)->default('');
            $table->string('address_complement', 60)->nullable();
            $table->string('address_district', 60)->default('');
            $table->string('address_city', 60)->default('');
            $table->string('address_state', 2)->default('');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('profile_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'address_zip_code', 'address_street', 'address_number', 'address_complement',
                'address_district', 'address_city', 'address_state', 'latitude', 'longitude',
            ]);
        });
    }
};
