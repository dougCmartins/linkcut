<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Catalog\Actions;

use Domain\Catalog\Actions\DeleteProduct;
use Domain\Catalog\Data\ProductIdData;
use Domain\Settings\Support\PublicSettingsCache;

/** Removes the product and drops the public cache. */
final class DeleteCatalogProduct
{
    public function __construct(
        private readonly DeleteProduct $deleteProduct,
        private readonly PublicSettingsCache $cache,
    ) {}

    public function handle(ProductIdData $data): void
    {
        $this->deleteProduct->handle($data);
        $this->cache->forget();
    }
}
