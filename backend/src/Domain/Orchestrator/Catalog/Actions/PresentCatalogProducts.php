<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Catalog\Actions;

use Domain\Catalog\Data\ProductData;
use Domain\Orchestrator\Catalog\Data\CatalogProductData;
use Domain\Settings\Actions\ListImageUrls;
use Domain\Settings\Data\ImageData;
use Domain\Settings\Data\ImageIdData;
use Domain\Settings\Data\ImageIdsData;
use Spatie\LaravelData\DataCollection;

/** Adds the photo url to each product. One query for the whole list. */
final class PresentCatalogProducts
{
    public function __construct(private readonly ListImageUrls $listImageUrls) {}

    /** @param DataCollection<int, ProductData> $products
     * @return DataCollection<int, CatalogProductData>
     */
    public function handle(DataCollection $products): DataCollection
    {
        $urls = $this->urls($products);
        $presented = [];
        foreach ($products as $product) {
            $presented[] = new CatalogProductData(
                id: $product->id,
                name: $product->name,
                price_in_cents: $product->price_in_cents,
                description: $product->description,
                image: $product->image_id !== null ? ($urls[$product->image_id] ?? null) : null,
                is_active: $product->is_active,
                position: $product->position,
            );
        }

        return new DataCollection(CatalogProductData::class, $presented);
    }

    /**
     * @param  DataCollection<int, ProductData>  $products
     * @return array<int, ImageData>
     */
    private function urls(DataCollection $products): array
    {
        $ids = [];
        foreach ($products as $product) {
            if ($product->image_id !== null) {
                $ids[] = new ImageIdData(id: $product->image_id);
            }
        }

        $byId = [];
        foreach ($this->listImageUrls->handle(new ImageIdsData(ids: new DataCollection(ImageIdData::class, $ids))) as $image) {
            $byId[$image->id] = $image;
        }

        return $byId;
    }
}
