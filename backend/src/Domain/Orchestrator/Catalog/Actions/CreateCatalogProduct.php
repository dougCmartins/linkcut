<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Catalog\Actions;

use Domain\Catalog\Actions\CreateProduct;
use Domain\Catalog\Data\ProductData;
use Domain\Catalog\Data\ProductInputData;
use Domain\Orchestrator\Catalog\Data\CatalogProductData;
use Domain\Settings\Actions\EnsureImagesExist;
use Domain\Settings\Data\ImageIdData;
use Domain\Settings\Data\ImageIdsData;
use Domain\Settings\Support\PublicSettingsCache;
use Spatie\LaravelData\DataCollection;

/** Checks the photo, stores the product, and drops the public cache. */
final class CreateCatalogProduct
{
    public function __construct(
        private readonly EnsureImagesExist $ensureImages,
        private readonly CleanProductInput $clean,
        private readonly CreateProduct $createProduct,
        private readonly PresentCatalogProducts $present,
        private readonly PublicSettingsCache $cache,
    ) {}

    public function handle(ProductInputData $data): CatalogProductData
    {
        $this->ensureImages->handle($this->imageIds($data->image_id));
        $product = $this->createProduct->handle($this->clean->handle($data));
        $this->cache->forget();

        return $this->present->handle(new DataCollection(ProductData::class, [$product]))->all()[0];
    }

    private function imageIds(?int $imageId): ImageIdsData
    {
        $ids = $imageId !== null ? [new ImageIdData(id: $imageId)] : [];

        return new ImageIdsData(ids: new DataCollection(ImageIdData::class, $ids));
    }
}
