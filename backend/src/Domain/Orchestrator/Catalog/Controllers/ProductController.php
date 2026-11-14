<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Catalog\Controllers;

use Domain\Catalog\Data\ProductIdData;
use Domain\Catalog\Data\ProductInputData;
use Domain\Catalog\Data\ProductOrderData;
use Domain\Orchestrator\Catalog\Actions\CreateCatalogProduct;
use Domain\Orchestrator\Catalog\Actions\DeleteCatalogProduct;
use Domain\Orchestrator\Catalog\Actions\ListCatalogProducts;
use Domain\Orchestrator\Catalog\Actions\ReorderCatalogProducts;
use Domain\Orchestrator\Catalog\Actions\UpdateCatalogProduct;
use Domain\Orchestrator\Catalog\Data\CatalogProductData;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;

final class ProductController
{
    /** @return DataCollection<int, CatalogProductData> */
    public function index(ListCatalogProducts $action): DataCollection
    {
        return $action->handle();
    }

    public function store(ProductInputData $data, CreateCatalogProduct $action): CatalogProductData
    {
        return $action->handle($data);
    }

    public function update(int $id, ProductInputData $data, UpdateCatalogProduct $action): CatalogProductData
    {
        return $action->handle(self::id($id), $data);
    }

    public function destroy(int $id, DeleteCatalogProduct $action): Response
    {
        $action->handle(self::id($id));

        return response()->noContent();
    }

    /** @return DataCollection<int, CatalogProductData> */
    public function reorder(ProductOrderData $data, ReorderCatalogProducts $action): DataCollection
    {
        return $action->handle($data);
    }

    private static function id(int $id): ProductIdData
    {
        return ProductIdData::from(ProductIdData::validate(['id' => $id]));
    }
}
