<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Models\ProfileSettings;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Volume for performance checks only (never in production): 200 barbershops, 100k clients, 500k booking
 * requests and 700k revenue entries, inserted with INSERT ... SELECT (seconds, not hours). MySQL only.
 *
 *   php artisan db:seed --class=PerformanceSeeder --force
 */
final class PerformanceSeeder extends Seeder
{
    private const TENANTS = 200;

    private const CLIENTS = 100_000;

    private const BOOKINGS = 500_000;

    private const CLIENT_SPREAD = 99_991;

    private const REVENUE_ENTRIES = 700_000;

    public function run(): void
    {
        if (app()->isProduction() || DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('PerformanceSeeder runs only on a MySQL database outside production.');
        }

        $this->numbers();
        $this->tenants();
        $this->clients();
        $this->bookings();
        $this->revenue();
        DB::statement('DROP TABLE perf_numbers');
        DB::statement('DROP TABLE perf_tenants');
        foreach (['tenants', 'clients', 'booking_requests', 'revenue_entries'] as $table) {
            DB::statement("ANALYZE TABLE {$table}");
        }
    }

    /** 0..999999, to generate rows with INSERT ... SELECT. */
    private function numbers(): void
    {
        DB::statement('DROP TABLE IF EXISTS perf_numbers');
        DB::statement('CREATE TABLE perf_numbers (n INT UNSIGNED PRIMARY KEY)');
        DB::statement('INSERT INTO perf_numbers (n) SELECT a.d + b.d * 10 + c.d * 100 + d.d * 1000 + e.d * 10000 + f.d * 100000
            FROM (SELECT 0 d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
            CROSS JOIN (SELECT 0 d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
            CROSS JOIN (SELECT 0 d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c
            CROSS JOIN (SELECT 0 d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d
            CROSS JOIN (SELECT 0 d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) e
            CROSS JOIN (SELECT 0 d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) f');
    }

    /** Barbershops with their default settings and an address; perf_tenants maps 0..199 to tenant and service. */
    private function tenants(): void
    {
        DB::statement('DROP TABLE IF EXISTS perf_tenants');
        DB::statement('CREATE TABLE perf_tenants (idx INT UNSIGNED PRIMARY KEY, tenant_id BIGINT UNSIGNED, service_id BIGINT UNSIGNED)');
        for ($index = 0; $index < self::TENANTS; $index++) {
            $tenant = Tenant::query()->create(['name' => "Barbearia {$index}", 'slug' => "perf-{$index}", 'active' => true, 'onboarded_at' => now()]);
            app(CurrentTenant::class)->run($tenant->id, function () use ($tenant, $index): void {
                app(CreateDefaultSettings::class)->handle(new DefaultSettingsData);
                ProfileSettings::query()->firstOrFail()->update([
                    'name' => "Barbearia {$index}", 'whatsapp_number' => '11988887777', 'address_zip_code' => '01304001',
                    'address_street' => 'Rua Augusta', 'address_number' => (string) (100 + $index), 'address_district' => 'Consolação',
                    'address_city' => 'São Paulo', 'address_state' => 'SP', 'latitude' => -23.5578, 'longitude' => -46.6609,
                ]);
                DB::table('perf_tenants')->insert([
                    'idx' => $index,
                    'tenant_id' => $tenant->id,
                    'service_id' => DB::table('services')->where('tenant_id', $tenant->id)->min('id'),
                ]);
            });
        }
    }

    /** A third already invited, a fifth registered, a few opted out; created over the last 300 days. */
    private function clients(): void
    {
        DB::statement('INSERT INTO clients (name, email, phone, password, registration_invited_at, emails_opted_out_at, created_at, updated_at)
            SELECT CONCAT("Cliente ", n), CONCAT("cliente", n, "@perf.test"), "11900000000",
                IF(n % 5 = 0, "x", NULL),
                IF(n % 3 = 0, NOW() - INTERVAL (n % 300) DAY, NULL),
                IF(n % 50 = 0, NOW(), NULL),
                NOW() - INTERVAL (n % 300) DAY, NOW()
            FROM perf_numbers WHERE n < ?', [self::CLIENTS]);
    }

    /**
     * 2,500 per barbershop, from a year ago to 30 days ahead: past ones completed or rejected, future ones pending or
     * confirmed. Each client books at ~5 different barbershops (CLIENT_SPREAD is not a multiple of the 200 tenants).
     */
    private function bookings(): void
    {
        $firstClient = (int) DB::table('clients')->min('id');
        DB::statement('INSERT INTO booking_requests (tenant_id, client_name, client_whatsapp, service_id, service_name, duration_minutes, date,
                starts_at, ends_at, status, client_email, client_id, created_at, updated_at)
            SELECT t.tenant_id, "Cliente", "11900000000", t.service_id, "Corte", 45, b.day,
                CONCAT(LPAD(8 + (b.n DIV 200) % 11, 2, "0"), IF(b.n % 2 = 0, ":00", ":30")),
                CONCAT(LPAD(8 + (b.n DIV 200) % 11, 2, "0"), IF(b.n % 2 = 0, ":45", ":15")),
                CASE WHEN b.day < CURDATE() THEN IF(b.n % 10 = 0, "rejected", "completed")
                     WHEN b.n % 3 = 0 THEN "pending" ELSE "confirmed" END,
                CONCAT("cliente", b.n % ?, "@perf.test"), ? + b.n % ?, NOW(), NOW()
            FROM (SELECT n, CURDATE() - INTERVAL 365 DAY + INTERVAL ((n DIV 200) % 396) DAY AS day FROM perf_numbers WHERE n < ?) b
            JOIN perf_tenants t ON t.idx = b.n % ?', [self::CLIENT_SPREAD, $firstClient, self::CLIENT_SPREAD, self::BOOKINGS, self::TENANTS]);
    }

    /** 3,500 per barbershop over the last year. */
    private function revenue(): void
    {
        DB::statement('INSERT INTO revenue_entries (tenant_id, date, time, client_name, service_name, price_in_cents, cost_in_cents, created_at, updated_at)
            SELECT t.tenant_id, CURDATE() - INTERVAL ((r.n DIV 200) % 365) DAY, CONCAT(LPAD(8 + r.n % 11, 2, "0"), ":00"),
                "Cliente", "Corte", 4000 + (r.n % 5) * 1000, 800, NOW(), NOW()
            FROM (SELECT n FROM perf_numbers WHERE n < ?) r
            JOIN perf_tenants t ON t.idx = r.n % ?', [self::REVENUE_ENTRIES, self::TENANTS]);
    }
}
