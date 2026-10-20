<?php

declare(strict_types=1);

use Domain\Settings\Actions\PruneUnusedImages;
use Domain\Settings\Data\PruneImagesData;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Laravel\Passport\ClientRepository;
use Minishlink\WebPush\VAPID;

// Idempotent: the Docker entrypoint runs it on every start.
Artisan::command('passport:ensure-personal-client', function (ClientRepository $clients): void {
    try {
        $clients->personalAccessClient('users');
        $this->info('Personal access client already exists.');
    } catch (RuntimeException) {
        $clients->createPersonalAccessGrantClient('linkcut', 'users');
        $this->info('Personal access client created.');
    }
})->purpose('Create the Passport personal access client used after 2FA, if missing');

// Uploaded images that no settings use anymore (scheduled daily; see the "scheduler" service).
Artisan::command('settings:prune-images', function (PruneUnusedImages $prune): void {
    $this->info('Pruned images: '.$prune->handle(new PruneImagesData));
})->purpose('Delete uploaded images no settings reference anymore');

Schedule::command('settings:prune-images')->dailyAt('03:30');

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
