<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Client;

abstract class TestCase extends BaseTestCase
{
    /** Passport needs keys and a personal access client to issue access tokens. */
    protected function preparePassport(): void
    {
        if (! file_exists(storage_path('oauth-private.key'))) {
            Artisan::call('passport:keys', ['--force' => true]);
        }
        Client::factory()->asPersonalAccessTokenClient()->create();
    }
}
