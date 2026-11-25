<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBrand\Controllers;

use Domain\Orchestrator\PublicBrand\Actions\ShowPublicBrand;
use Domain\Orchestrator\PublicBrand\Data\PublicBrandData;

/** Public brand page: active units of an organization for the unit select. */
final class PublicBrandController
{
    public function show(string $slug, ShowPublicBrand $action): PublicBrandData
    {
        return $action->handle($slug);
    }
}
