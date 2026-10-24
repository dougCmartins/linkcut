<?php

declare(strict_types=1);

namespace Domain\Orchestrator\BarbershopAccount\Actions;

use Domain\Finance\Actions\ListFinanceRecords;
use Domain\Identity\Actions\ShowAuthenticatedUser;
use Domain\Schedule\Actions\ListAllBookings;
use Domain\Settings\Actions\ShowSettings;
use Domain\Tenant\Actions\ShowCurrentTenant;
use Illuminate\Support\Facades\Log;

/**
 * "Baixar meus dados" (LGPD portability): everything of the barbershop in one JSON file: account, settings,
 * every booking and every finance record. Images are listed by their address (the files stay on the page).
 */
final class ExportBarbershopData
{
    public function __construct(
        private readonly ShowCurrentTenant $showTenant,
        private readonly ShowAuthenticatedUser $showUser,
        private readonly ShowSettings $showSettings,
        private readonly ListAllBookings $listBookings,
        private readonly ListFinanceRecords $listFinance,
    ) {}

    /** @return array<string, mixed> */
    public function handle(): array
    {
        $tenant = $this->showTenant->handle();
        Log::info('barbershop_data_exported', ['tenant_id' => $tenant->id]);

        return [
            'exported_at' => now()->toIso8601String(),
            'barbershop' => $tenant->toArray(),
            'account' => $this->showUser->handle()->toArray(),
            'settings' => $this->showSettings->handle()->toArray(),
            'bookings' => $this->listBookings->handle()->toArray(),
            'finance' => $this->listFinance->handle()->toArray(),
        ];
    }
}
