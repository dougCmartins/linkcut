<?php

declare(strict_types=1);

namespace App\Providers;

use App\Livewire\Pulse\TenantUsage;
use App\Observability\AddTenantToContext;
use App\Sms\LogSmsSender;
use App\Sms\SmsSender;
use Domain\Notification\Support\PushSender;
use Domain\Notification\Support\WebPushSender;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Context\TenantClock;
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
        $this->app->scoped(TenantClock::class);

        $this->app->bind(SmsSender::class, fn () => match (config('sms.driver')) {
            'log' => new LogSmsSender,
            default => throw new RuntimeException('Unknown SMS driver: '.config('sms.driver')),
        });
        $this->app->bind(PushSender::class, WebPushSender::class);
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
        // Public pages by slug (bio link, portfolio, booking options) and image uploads.
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        // Client area: codes by e-mail and sign-in (per e-mail and per visitor).
        // Per e-mail too (not only per visitor): changing IP does not give more codes or guesses for one account.
        RateLimiter::for('client-codes', fn (Request $request) => [
            Limit::perMinute(3)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perHour(20)->by($request->ip()),
            Limit::perHour(6)->by('client-codes-email:'.mb_strtolower((string) $request->input('email'))),
        ]);
        // Google's redirect has no e-mail yet. Without this, every visitor would share one hourly bucket.
        RateLimiter::for('client-login', function (Request $request) {
            $email = mb_strtolower((string) ($request->input('email') ?? $request->user('client')?->email ?? ''));
            $limits = [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
            if ($email !== '') {
                $limits[] = Limit::perHour(20)->by('client-login-email:'.$email);
            }

            return $limits;
        });
        // Barber sign-up: codes per e-mail and visitor; finishing and checking links per visitor.
        RateLimiter::for('sign-up-codes', fn (Request $request) => [
            Limit::perMinute(3)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perHour(20)->by($request->ip()),
            Limit::perHour(6)->by('sign-up-codes-email:'.mb_strtolower((string) $request->input('email'))),
        ]);
        RateLimiter::for('sign-up', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perHour(30)->by($request->ip()),
        ]);
        // Public booking requests: a few per minute per visitor, so nobody fills a barber's agenda.
        RateLimiter::for('booking', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(20)->by($request->ip()),
        ]);
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(30)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        // One client rating their visits. The limit follows the account, not the IP.
        RateLimiter::for('client-reviews', fn (Request $request) => Limit::perMinute(10)->by(
            'client-reviews:'.($request->user('client')?->getAuthIdentifier() ?? $request->ip()),
        ));
    }

    /** The SMS "log" driver writes codes to a file: never allowed in production while SMS is on. */
    private function refuseUnsafeDriversInProduction(): void
    {
        if ($this->app->isProduction() && config('sms.enabled') && config('sms.driver') === 'log') {
            throw new RuntimeException('SMS_DRIVER=log is not allowed in production.');
        }
    }
}
