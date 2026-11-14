<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Catalog\Actions;

use Domain\Catalog\Actions\ListProducts;
use Domain\Orchestrator\Catalog\Data\CatalogProductData;
use Spatie\LaravelData\DataCollection;

/** The panel list: every product, with the photo url. */
final class ListCatalogProducts
{
    public function __construct(
        private readonly ListProducts $listProducts,
        private readonly PresentCatalogProducts $present,
    ) {}

    /** @return DataCollection<int, CatalogProductData> */
    public function handle(): DataCollection
    {
        return $this->present->handle($this->listProducts->handle());
    }
}
