<?php

declare(strict_types=1);

namespace Domain\Catalog\Actions;

use Domain\Catalog\Data\ProductData;
use Domain\Catalog\Data\ProductInputData;
use Domain\Catalog\Exceptions\ProductsLimitReachedException;
use Domain\Catalog\Models\Product;
use Domain\Catalog\Support\ProductLimits;
use Domain\Catalog\Support\ProductMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Stores a product at the end of the showcase. Refuses the 51st. */
final class CreateProduct
{
    public function handle(ProductInputData $data): ProductData
    {
        $product = DB::transaction(function () use ($data): Product {
            $count = Product::query()->lockForUpdate()->count();
            if ($count >= ProductLimits::PRODUCTS_LIMIT) {
                throw new ProductsLimitReachedException;
            }

            $position = ((int) Product::query()->max('position')) + 1;

            return Product::query()->create([
                'name' => $data->name,
                'price_in_cents' => $data->price_in_cents,
                'description' => $data->description,
                'image_id' => $data->image_id,
                'is_active' => $data->is_active,
                'position' => $position,
            ]);
        });

        Log::info('catalog_product_created', ['product_id' => $product->id]);

        return ProductMapper::toData($product);
    }
}
