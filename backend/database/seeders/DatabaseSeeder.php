<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data (idempotent). Two tenants, so tenant isolation can be checked by hand.
 * Demo login: rafael@linkcut.app / linkcut123
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->tenantWithOwner('Rafael Barber', 'rafael', 'Rafael Costa', 'rafael@linkcut.app', '+5511988887777');
            $this->tenantWithOwner('Navalha Norte', 'navalha-norte', 'Bruno Lima', 'bruno@navalhanorte.app', null);
        });
    }

    private function tenantWithOwner(string $tenantName, string $slug, string $name, string $email, ?string $phone): void
    {
        $tenant = Tenant::query()->updateOrCreate(['slug' => $slug], ['name' => $tenantName, 'active' => true]);

        User::query()->updateOrCreate(['email' => $email], [
            'tenant_id' => $tenant->id,
            'role' => UserRole::Owner,
            'name' => $name,
            'phone' => $phone,
            'password' => 'linkcut123',
        ]);
    }
}
