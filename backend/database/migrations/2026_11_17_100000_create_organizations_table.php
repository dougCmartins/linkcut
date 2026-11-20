<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The brand above the barbershops (docs/adr/0002, docs/decisoes/0001 P1): every tenant is a unit of one organization.
 * Each existing barbershop becomes an "individual" organization with itself as the main unit: no slug changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('individual');
            $table->string('name');
            $table->string('slug', 60)->unique();
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_main')->default(true);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
        });

        $this->createOneOrganizationPerTenant();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
        });
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn('is_main');
        });
        Schema::dropIfExists('organizations');
    }

    private function createOneOrganizationPerTenant(): void
    {
        DB::table('tenants')->whereNull('organization_id')->orderBy('id')->lazyById()->each(function (object $tenant): void {
            $ownerId = DB::table('users')->where('tenant_id', $tenant->id)->where('role', 'owner')->min('id');
            $organizationId = DB::table('organizations')->insertGetId([
                'owner_user_id' => $ownerId,
                'type' => 'individual',
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('tenants')->where('id', $tenant->id)->update(['organization_id' => $organizationId, 'is_main' => true]);
            DB::table('users')->where('tenant_id', $tenant->id)->update(['organization_id' => $organizationId]);
        });
    }
};
