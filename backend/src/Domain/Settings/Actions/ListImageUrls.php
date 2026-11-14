<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\ImageData;
use Domain\Settings\Data\ImageIdData;
use Domain\Settings\Data\ImageIdsData;
use Domain\Settings\Models\Image;
use Spatie\LaravelData\DataCollection;

/** The public url of each image, in one query. Ids of another barbershop are simply absent. */
final class ListImageUrls
{
    /** @return DataCollection<int, ImageData> */
    public function handle(ImageIdsData $data): DataCollection
    {
        $ids = array_values(array_unique(array_map(fn (ImageIdData $item): int => $item->id, $data->ids->all())));
        if (empty($ids)) {
            return new DataCollection(ImageData::class, []);
        }

        $images = Image::query()->whereIn('id', $ids)->get()
            ->map(fn (Image $image): ImageData => new ImageData(id: $image->id, url: $image->url()))
            ->all();

        return new DataCollection(ImageData::class, $images);
    }
}
