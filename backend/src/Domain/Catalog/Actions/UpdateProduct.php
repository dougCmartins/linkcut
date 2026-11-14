<?php

declare(strict_types=1);

namespace Domain\Catalog\Actions;

use Domain\Catalog\Data\ProductData;
use Domain\Catalog\Data\ProductIdData;
use Domain\Catalog\Data\ProductInputData;
use Domain\Catalog\Exceptions\ProductNotFoundException;
use Domain\Catalog\Models\Product;
use Domain\Catalog\Support\ProductMapper;
use Illuminate\Support\Facades\Log;

/** Replaces the text, the price, the photo and the active flag. The order stays. */
final class UpdateProduct
{
    public function handle(ProductIdData $id, ProductInputData $data): ProductData
    {
        $product = Product::query()->find($id->id);
        if (! $product) {
            throw new ProductNotFoundException;
        }

        $product->update([
            'name' => $data->name,
            'price_in_cents' => $data->price_in_cents,
            'description' => $data->description,
            'image_id' => $data->image_id,
            'is_active' => $data->is_active,
        ]);

        Log::info('catalog_product_updated', ['product_id' => $product->id]);

        return ProductMapper::toData($product->refresh());
    }
}
