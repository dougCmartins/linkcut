<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\ImageIdData;
use Domain\Settings\Data\ImageIdsData;
use Domain\Settings\Exceptions\ImageNotFoundException;
use Domain\Settings\Models\Image;

/** Refuses an image id that is not an upload of the current barbershop. */
final class EnsureImagesExist
{
    public function handle(ImageIdsData $data): void
    {
        $ids = array_map(fn (ImageIdData $item): int => $item->id, $data->ids->all());
        if (Image::missingIds($ids)) {
            throw new ImageNotFoundException;
        }
    }
}
