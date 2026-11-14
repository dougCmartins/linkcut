<?php

declare(strict_types=1);

namespace Domain\Catalog\Actions;

use Domain\Catalog\Data\ProductData;
use Domain\Catalog\Models\Product;
use Domain\Catalog\Support\ProductMapper;
use Spatie\LaravelData\DataCollection;

/** Active products, in showcase order. This is what a client with a booking sees. */
final class ListActiveProducts
{
    /** @return DataCollection<int, ProductData> */
    public function handle(): DataCollection
    {
        $products = Product::query()->where('is_active', true)->orderBy('position')->orderBy('id')->get()
            ->map(fn (Product $product): ProductData => ProductMapper::toData($product))
            ->all();

        return new DataCollection(ProductData::class, $products);
    }
}
