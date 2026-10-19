<?php

declare(strict_types=1);

namespace App\Livewire\Pulse;

use App\Pulse\TenantRequests;
use Domain\Tenant\Models\Tenant;
use Illuminate\Contracts\View\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

/** Pulse card: the tenants with most requests and their server errors in the period. */
#[Lazy]
final class TenantUsage extends Card
{
    public function render(): View
    {
        $requests = $this->aggregate(TenantRequests::REQUESTS, ['count'], limit: 10);
        $errors = $this->aggregate(TenantRequests::ERRORS, ['count'])->pluck('count', 'key');
        $names = Tenant::query()->whereIn('id', $requests->pluck('key'))->pluck('name', 'id');

        return view('livewire.pulse.tenant-usage', [
            'tenants' => $requests->map(fn (object $row): array => [
                'tenant' => $names[(int) $row->key] ?? '#'.$row->key,
                'requests' => (int) $row->count,
                'errors' => (int) ($errors[$row->key] ?? 0),
            ]),
        ]);
    }
}
