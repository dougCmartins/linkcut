<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Client;

abstract class TestCase extends BaseTestCase
{
    /** No test ever calls a real web service (e.g. the address lookup): use Http::fake(). */
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    /** Passport needs keys and a personal access client to issue access tokens. */
    protected function preparePassport(): void
    {
        if (! file_exists(storage_path('oauth-private.key'))) {
            Artisan::call('passport:keys', ['--force' => true]);
        }
        // Bound to their provider, like passport:ensure-personal-client does.
        Client::factory()->asPersonalAccessTokenClient()->create(['provider' => 'users']);
        Client::factory()->asPersonalAccessTokenClient()->create(['provider' => 'clients']);
    }
}
