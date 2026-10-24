<?php

declare(strict_types=1);

namespace App\Observability;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * "I'm alive" signal for an external monitor (e.g. healthchecks.io). The scheduler dispatches it every 5 minutes and
 * the queue runs it, so one missing ping means the scheduler or the queue stopped: the monitor sends the alert.
 */
final class Heartbeat implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $tries = 1;

    public function handle(): void
    {
        $url = (string) config('services.heartbeat.url');
        if ($url === '') {
            return;
        }
        try {
            Http::timeout(10)->get($url);
        } catch (Throwable $error) {
            Log::warning('heartbeat_failed', ['error' => $error->getMessage()]);
        }
    }
}
