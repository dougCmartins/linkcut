<?php

declare(strict_types=1);

namespace Domain\Catalog\Actions;

use Domain\Catalog\Data\ProductData;
use Domain\Catalog\Models\Product;
use Domain\Catalog\Support\ProductMapper;
use Spatie\LaravelData\DataCollection;

/** Every product of the current barbershop, in showcase order. Inactive ones stay in the list. */
final class ListProducts
{
    /** @return DataCollection<int, ProductData> */
    public function handle(): DataCollection
    {
        $products = Product::query()->orderBy('position')->orderBy('id')->get()
            ->map(fn (Product $product): ProductData => ProductMapper::toData($product))
            ->all();

        return new DataCollection(ProductData::class, $products);
    }
}
