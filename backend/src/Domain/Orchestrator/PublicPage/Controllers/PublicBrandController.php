<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicPage\Controllers;

use Domain\Orchestrator\PublicPage\Actions\ShowPublicBrand;
use Domain\Orchestrator\PublicPage\Data\PublicBrandData;
use Domain\Tenant\Data\TenantSlugData;

final class PublicBrandController
{
    public function show(string $slug, ShowPublicBrand $action): PublicBrandData
    {
        return $action->handle(new TenantSlugData(slug: $slug));
    }
}
