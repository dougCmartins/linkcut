<?php

declare(strict_types=1);

namespace Domain\Orchestrator\ClientAppointments\Controllers;

use Domain\Catalog\Data\PublicProductData;
use Domain\Orchestrator\ClientAppointments\Actions\ListClientBarbershopProducts;
use Domain\Tenant\Data\TenantSlugData;
use Spatie\LaravelData\DataCollection;

final class ClientBarbershopProductsController
{
    /** @return DataCollection<int, PublicProductData> */
    public function index(string $slug, ListClientBarbershopProducts $action): DataCollection
    {
        return $action->handle(TenantSlugData::from(TenantSlugData::validate(['slug' => $slug])));
    }
}
