<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
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
