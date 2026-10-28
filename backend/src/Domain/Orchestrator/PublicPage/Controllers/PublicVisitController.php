<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicPage\Controllers;

use Domain\Orchestrator\PublicPage\Actions\ShowPublicVisit;
use Domain\Orchestrator\PublicPage\Data\PublicVisitData;

/** Public page of the barbershop resolved by slug, with the rating and the comments. */
final class PublicVisitController
{
    public function show(ShowPublicVisit $action): PublicVisitData
    {
        return $action->handle();
    }
}
