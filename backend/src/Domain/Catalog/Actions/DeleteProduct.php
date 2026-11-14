<?php

declare(strict_types=1);

namespace Domain\Catalog\Actions;

use Domain\Catalog\Data\ProductIdData;
use Domain\Catalog\Exceptions\ProductNotFoundException;
use Domain\Catalog\Models\Product;
use Illuminate\Support\Facades\Log;

/** Removes a product of the current barbershop. */
final class DeleteProduct
{
    public function handle(ProductIdData $data): void
    {
        $product = Product::query()->find($data->id);
        if (! $product) {
            throw new ProductNotFoundException;
        }

        $product->delete();

        Log::info('catalog_product_deleted', ['product_id' => $data->id]);
    }
}
