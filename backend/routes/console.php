<?php

declare(strict_types=1);

use Domain\Settings\Actions\PruneUnusedImages;
use Domain\Settings\Data\PruneImagesData;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Laravel\Passport\ClientRepository;

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
