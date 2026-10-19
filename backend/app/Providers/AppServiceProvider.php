<?php

declare(strict_types=1);

namespace App\Providers;

use App\Livewire\Pulse\TenantUsage;
use App\Observability\AddTenantToContext;
use App\Sms\LogSmsSender;
use App\Sms\SmsSender;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Events\TenantResolved;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use RuntimeException;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Only personal access tokens are used (issued after 2FA); the OAuth server routes stay off.
        Passport::ignoreRoutes();

        // One tenant context per request (reset between requests and queued jobs).
        $this->app->scoped(CurrentTenant::class);

        $this->app->bind(SmsSender::class, fn () => match (config('sms.driver')) {
            'log' => new LogSmsSender,
            default => throw new RuntimeException('Unknown SMS driver: '.config('sms.driver')),
        });
    }

    public function boot(): void
    {
        $this->refuseUnsafeDriversInProduction();

        Passport::personalAccessTokensExpireIn(now()->addMinutes((int) config('identity.session.access_token_ttl_minutes')));

        Event::listen(TenantResolved::class, AddTenantToContext::class);
        Livewire::component('pulse.tenant-usage', TenantUsage::class);

        $this->configureRateLimiting();
    }

    /** Brute-force protection for the public auth endpoints (429 with the error envelope). */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);
        RateLimiter::for('two-factor', fn (Request $request) => [
            Limit::perMinute(10)->by((string) $request->input('challenge_id').'|'.$request->ip()),
            Limit::perMinute(30)->by($request->ip()),
        ]);
        RateLimiter::for('refresh', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }

    /** The SMS "log" driver writes codes to a file: never allowed in production. */
    private function refuseUnsafeDriversInProduction(): void
    {
        if ($this->app->isProduction() && config('sms.driver') === 'log') {
            throw new RuntimeException('SMS_DRIVER=log is not allowed in production.');
        }
    }
}
