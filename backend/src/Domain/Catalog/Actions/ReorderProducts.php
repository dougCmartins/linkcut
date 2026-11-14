<?php

declare(strict_types=1);

namespace Domain\Catalog\Actions;

use Domain\Catalog\Data\ProductData;
use Domain\Catalog\Data\ProductOrderData;
use Domain\Catalog\Exceptions\ProductOrderInvalidException;
use Domain\Catalog\Models\Product;
use Domain\Catalog\Support\ProductMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\DataCollection;

/** Writes positions from the given id list. The list must be exactly this barbershop's products, once each. */
final class ReorderProducts
{
    /** @return DataCollection<int, ProductData> */
    public function handle(ProductOrderData $data): DataCollection
    {
        $ids = array_map(intval(...), $data->ids);
        if (count($ids) !== count(array_unique($ids))) {
            throw new ProductOrderInvalidException;
        }

        $existing = Product::query()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        $sortedIds = $ids;
        $sortedExisting = $existing;
        sort($sortedIds);
        sort($sortedExisting);
        if ($sortedIds !== $sortedExisting) {
            throw new ProductOrderInvalidException;
        }

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $index => $id) {
                Product::query()->whereKey($id)->update(['position' => $index + 1]);
            }
        });

        Log::info('catalog_products_reordered', ['count' => count($ids)]);

        $products = Product::query()->orderBy('position')->orderBy('id')->get()
            ->map(fn (Product $product): ProductData => ProductMapper::toData($product))
            ->all();

        return new DataCollection(ProductData::class, $products);
    }
}
