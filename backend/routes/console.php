<?php

declare(strict_types=1);

use App\Observability\Heartbeat;
use Domain\Client\Models\ClientEmailCode;
use Domain\Client\Models\ClientRefreshToken;
use Domain\Identity\Models\RefreshToken;
use Domain\Identity\Models\SignUpCode;
use Domain\Identity\Models\TwoFactorChallenge;
use Domain\Orchestrator\ClientEmails\Actions\InviteClientsToRegister;
use Domain\Orchestrator\ClientEmails\Actions\SendBookingReminders;
use Domain\Settings\Actions\PruneUnusedImages;
use Domain\Settings\Data\PruneImagesData;
use Domain\Tenant\Actions\ListTenantsForAdmin;
use Domain\Tenant\Actions\SetTenantActive;
use Domain\Tenant\Data\TenantActivationData;
use Domain\Tenant\Data\TenantData;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Minishlink\WebPush\VAPID;

// Idempotent: the Docker entrypoint runs it on every start.
// One per provider (barbers and clients), so a token of one never authenticates on the guard of the other.
Artisan::command('passport:ensure-personal-client', function (ClientRepository $clients): void {
    foreach (['users', 'clients'] as $provider) {
        $exists = Passport::client()->newQuery()->where('revoked', false)->where('provider', $provider)->get()
            ->contains(fn ($client): bool => $client->hasGrantType('personal_access'));
        if ($exists) {
            $this->info("Personal access client for {$provider} already exists.");

            continue;
        }
        $clients->createPersonalAccessGrantClient("linkcut-{$provider}", $provider);
        $this->info("Personal access client for {$provider} created.");
    }
})->purpose('Create the Passport personal access clients (barbers and clients), if missing');

// Uploaded images that no settings use anymore (scheduled daily; see the "scheduler" service).
Artisan::command('settings:prune-images', function (PruneUnusedImages $prune): void {
    $this->info('Pruned images: '.$prune->handle(new PruneImagesData));
})->purpose('Delete uploaded images no settings reference anymore');

Schedule::command('settings:prune-images')->dailyAt('03:30');

// E-mails to clients: the day-before reminder and the single sign-up invitation.
Artisan::command('schedule:send-reminders', function (SendBookingReminders $reminders): void {
    $this->info('Reminders sent: '.$reminders->handle());
})->purpose('E-mail clients the day before a confirmed appointment');

Artisan::command('client:invite-registration', function (InviteClientsToRegister $invites): void {
    $this->info('Invitations sent: '.$invites->handle());
})->purpose('E-mail clients who booked but did not complete the sign-up (once)');

Schedule::command('schedule:send-reminders')->hourly()->withoutOverlapping();
Schedule::command('client:invite-registration')->hourly()->withoutOverlapping();

// Keys of the push notifications. Idempotent: writes them to .env only when missing (the Docker entrypoint runs it).
Artisan::command('notifications:vapid-keys {--show : Print new keys instead of writing .env}', function (): void {
    $env = base_path('.env');
    $current = file_exists($env) ? (string) file_get_contents($env) : '';
    if (! $this->option('show') && preg_match('/^VAPID_PRIVATE_KEY=.+$/m', $current)) {
        $this->info('VAPID keys already set.');

        return;
    }
    $keys = VAPID::createVapidKeys();
    $lines = "VAPID_PUBLIC_KEY={$keys['publicKey']}\nVAPID_PRIVATE_KEY={$keys['privateKey']}";
    if ($this->option('show')) {
        $this->line($lines);

        return;
    }
    $cleaned = (string) preg_replace('/^VAPID_(PUBLIC|PRIVATE)_KEY=.*\n?/m', '', $current);
    file_put_contents($env, rtrim($cleaned)."\n\n{$lines}\n");
    $this->info('VAPID keys written to .env.');
})->purpose('Create the Web Push (VAPID) keys, if missing');

// ---------- Housekeeping (LGPD: data is kept only while it is useful) ----------
// Expired codes, 2FA challenges and refresh tokens; expired Passport tokens; old failed jobs.
Schedule::command('model:prune', ['--model' => [
    ClientEmailCode::class, ClientRefreshToken::class, RefreshToken::class, SignUpCode::class, TwoFactorChallenge::class,
]])->dailyAt('03:10');
Schedule::command('passport:purge --expired')->dailyAt('03:20');
Schedule::command('queue:prune-failed', ['--hours' => 720])->dailyAt('03:40');
Schedule::command('queue:prune-batches', ['--hours' => 720])->dailyAt('03:45');

// The scheduler and the queue are alive: an external monitor alerts when the ping stops (HEARTBEAT_URL).
Schedule::job(new Heartbeat)->everyFiveMinutes();

// ---------- Minimal admin (until the admin panel) ----------
Artisan::command('tenant:list', function (ListTenantsForAdmin $list): void {
    $rows = collect($list->handle()->all())->map(fn (TenantData $tenant): array => [
        $tenant->id, $tenant->slug, $tenant->name, $tenant->active ? 'sim' : 'NÃO', $tenant->onboarded ? 'sim' : 'não',
    ]);
    $this->table(['id', 'slug', 'nome', 'ativa', 'onboarding'], $rows->all());
})->purpose('List every barbershop (active and onboarding)');

Artisan::command('tenant:deactivate {slug}', function (SetTenantActive $setActive, string $slug): void {
    $tenant = $setActive->handle(new TenantActivationData(slug: $slug, active: false));
    $this->info("Barbearia {$tenant->slug} desativada: página pública em 404 e login bloqueado.");
})->purpose('Deactivate a barbershop (public page 404, sign-in refused)');

Artisan::command('tenant:activate {slug}', function (SetTenantActive $setActive, string $slug): void {
    $tenant = $setActive->handle(new TenantActivationData(slug: $slug, active: true));
    $this->info("Barbearia {$tenant->slug} ativada.");
})->purpose('Activate a barbershop again');
