<?php

declare(strict_types=1);

namespace Domain\Catalog\Support;

use Domain\Catalog\Data\ProductData;
use Domain\Catalog\Models\Product;

/** The Catalog action reads the row and builds the Data. The Data class does not know the model. */
final class ProductMapper
{
    public static function toData(Product $product): ProductData
    {
        return new ProductData(
            id: $product->id,
            name: $product->name,
            price_in_cents: $product->price_in_cents,
            description: $product->description,
            image_id: $product->image_id !== null ? (int) $product->image_id : null,
            is_active: $product->is_active,
            position: $product->position,
        );
    }
}
