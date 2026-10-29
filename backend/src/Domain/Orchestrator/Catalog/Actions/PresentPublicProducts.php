<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Catalog\Actions;

use Domain\Catalog\Actions\ListActiveProducts;
use Domain\Catalog\Data\PublicProductData;
use Domain\Settings\Actions\ListImageUrls;
use Domain\Settings\Data\ImageIdData;
use Domain\Settings\Data\ImageIdsData;
use Spatie\LaravelData\DataCollection;

/** Active products with a photo url, for a client who already booked here. The urls are resolved in one query. */
final class PresentPublicProducts
{
    public function __construct(
        private readonly ListActiveProducts $listActiveProducts,
        private readonly ListImageUrls $listImageUrls,
    ) {}

    /** @return DataCollection<int, PublicProductData> */
    public function handle(): DataCollection
    {
        $products = $this->listActiveProducts->handle();
        $urls = [];
        $ids = [];
        foreach ($products as $product) {
            if ($product->image_id !== null) {
                $ids[] = new ImageIdData(id: $product->image_id);
            }
        }
        foreach ($this->listImageUrls->handle(new ImageIdsData(ids: new DataCollection(ImageIdData::class, $ids))) as $image) {
            $urls[$image->id] = $image->url;
        }

        $public = [];
        foreach ($products as $product) {
            $public[] = new PublicProductData(
                id: $product->id,
                name: $product->name,
                price_in_cents: $product->price_in_cents,
                description: $product->description,
                image_url: $product->image_id !== null ? ($urls[$product->image_id] ?? null) : null,
            );
        }

        return new DataCollection(PublicProductData::class, $public);
    }
}
