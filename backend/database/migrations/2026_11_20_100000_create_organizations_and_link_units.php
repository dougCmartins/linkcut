<?php

declare(strict_types=1);

use Domain\Tenant\Support\BackfillOrganizations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);
            $table->string('name');
            $table->string('slug', 60)->unique();
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->boolean('is_main')->default(true)->after('organization_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('tenant_id')->constrained()->restrictOnDelete();
        });

        BackfillOrganizations::run();

        $this->requireOrganizationIds();
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

    /** Enforce NOT NULL after backfill (SQLite recreates the table; MySQL alters in place). */
    private function requireOrganizationIds(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable(false)->change();
        });
    }
};
