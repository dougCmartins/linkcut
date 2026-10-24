<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAccount\Actions;

use Domain\Client\Actions\ShowAuthenticatedClient;
use Domain\Orchestrator\ClientAppointments\Actions\ListClientAppointments;
use Illuminate\Support\Facades\Log;

/** "Baixar meus dados" of the client (LGPD portability): the account and every appointment, in one JSON file. */
final class ExportClientData
{
    public function __construct(
        private readonly ShowAuthenticatedClient $showClient,
        private readonly ListClientAppointments $listAppointments,
    ) {}

    /** @return array<string, mixed> */
    public function handle(): array
    {
        $client = $this->showClient->handle();
        Log::info('client_data_exported', ['client_id' => $client->id]);

        return [
            'exported_at' => now()->toIso8601String(),
            'account' => $client->toArray(),
            'appointments' => $this->listAppointments->handle()->toArray(),
        ];
    }
}
