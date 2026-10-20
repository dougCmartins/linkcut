<?php

declare(strict_types=1);

use Domain\Orchestrator\ClientEmails\Actions\InviteClientsToRegister;
use Domain\Orchestrator\ClientEmails\Actions\SendBookingReminders;
use Domain\Settings\Actions\PruneUnusedImages;
use Domain\Settings\Data\PruneImagesData;
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
