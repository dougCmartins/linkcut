<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\Identity\Enums\UserRole;
use Domain\Identity\Models\User;
use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\SocialLink;
use Domain\Tenant\Context\CurrentTenant;
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
            $rafael = $this->tenantWithOwner('Rafael Barber', 'rafael', 'Rafael Costa', 'rafael@linkcut.app', '+5511988887777');
            $navalha = $this->tenantWithOwner('Navalha Norte', 'navalha-norte', 'Bruno Lima', 'bruno@navalhanorte.app', null);

            $this->settings($rafael, 'Rafael Costa', [
                'handle' => '@rafa.barber',
                'bio' => 'Cortes clássicos e degradês precisos. Atendimento exclusivo com hora marcada.',
                'whatsapp_number' => '11988887777',
            ], ['instagram' => 'https://instagram.com/rafa.barber']);
            $this->settings($navalha, 'Navalha Norte', ['whatsapp_number' => '21977776666'], []);

            // Demo addresses with a fixed point on the map (no web lookup while seeding).
            $this->address($rafael, ['01304001', 'Rua Augusta', '1500', 'Consolação', 'São Paulo', 'SP', -23.5578, -46.6609]);
            $this->address($navalha, ['22011000', 'Rua Barata Ribeiro', '200', 'Copacabana', 'Rio de Janeiro', 'RJ', -22.9661, -43.1832]);

            // Only the demo account gets a history, expenses and booking requests; Navalha Norte starts empty.
            app(FinanceDemoSeeder::class)->forTenant($rafael);
            app(ScheduleDemoSeeder::class)->forTenant($rafael);
        });
    }

    /**
     * Default settings plus a few demo values (only on the first run, so edits made in the app survive a re-seed).
     *
     * @param  array<string, string>  $profile
     * @param  array<string, string>  $socials  network => url
     */
    private function settings(Tenant $tenant, string $name, array $profile, array $socials): void
    {
        app(CurrentTenant::class)->run($tenant->id, function () use ($name, $profile, $socials): void {
            if (ProfileSettings::query()->exists()) {
                return;
            }
            app(CreateDefaultSettings::class)->handle(new DefaultSettingsData(name: $name));
            ProfileSettings::query()->firstOrFail()->update($profile);
            foreach ($socials as $network => $url) {
                SocialLink::query()->where('network', $network)->update(['url' => $url]);
            }
        });
    }

    /**
     * Only when the barbershop has no address yet (also fills databases seeded before the address existed).
     *
     * @param  array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: float, 7: float}  $address
     */
    private function address(Tenant $tenant, array $address): void
    {
        app(CurrentTenant::class)->run($tenant->id, function () use ($address): void {
            $profile = ProfileSettings::query()->firstOrFail();
            if ($profile->hasAddress()) {
                return;
            }
            [$zipCode, $street, $number, $district, $city, $state, $latitude, $longitude] = $address;
            $profile->update([
                'address_zip_code' => $zipCode,
                'address_street' => $street,
                'address_number' => $number,
                'address_district' => $district,
                'address_city' => $city,
                'address_state' => $state,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        });
    }

    private function tenantWithOwner(string $tenantName, string $slug, string $name, string $email, ?string $phone): Tenant
    {
        $tenant = Tenant::query()->updateOrCreate(['slug' => $slug], ['name' => $tenantName, 'active' => true, 'onboarded_at' => now()]);

        User::query()->updateOrCreate(['email' => $email], [
            'tenant_id' => $tenant->id,
            'role' => UserRole::Owner,
            'name' => $name,
            'phone' => $phone,
            'password' => 'linkcut123',
        ]);

        return $tenant;
    }
}
