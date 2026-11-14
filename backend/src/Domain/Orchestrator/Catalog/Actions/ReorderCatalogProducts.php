<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Catalog\Actions;

use Domain\Catalog\Actions\ReorderProducts;
use Domain\Catalog\Data\ProductOrderData;
use Domain\Orchestrator\Catalog\Data\CatalogProductData;
use Domain\Settings\Support\PublicSettingsCache;
use Spatie\LaravelData\DataCollection;

/** Saves the new order and drops the public cache. */
final class ReorderCatalogProducts
{
    public function __construct(
        private readonly ReorderProducts $reorderProducts,
        private readonly PresentCatalogProducts $present,
        private readonly PublicSettingsCache $cache,
    ) {}

    /** @return DataCollection<int, CatalogProductData> */
    public function handle(ProductOrderData $data): DataCollection
    {
        $products = $this->reorderProducts->handle($data);
        $this->cache->forget();

        return $this->present->handle($products);
    }
}
