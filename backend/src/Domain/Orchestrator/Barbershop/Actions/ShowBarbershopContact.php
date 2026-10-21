<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Barbershop\Actions;

use Domain\Orchestrator\Barbershop\Data\BarbershopContactData;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Data\TenantIdsData;

/** Name, address, map, WhatsApp and page of the current barbershop. */
final class ShowBarbershopContact
{
    public function __construct(
        private readonly ListBarbershopContacts $listContacts,
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(): BarbershopContactData
    {
        $tenantId = $this->currentTenant->id();

        return $this->listContacts->handle(new TenantIdsData(ids: [$tenantId]))[$tenantId];
    }
}
