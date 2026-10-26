<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\ShareCardQueryData;
use Domain\Settings\Models\Image;
use Domain\Settings\Support\ImageProcessor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The JPEG of a shared page's photo (link previews). Made once from the stored WebP and kept next to it, so it is
 * deleted with the barbershop's images. Null when the page has no photo.
 */
final class ShowShareImage
{
    public function __construct(private readonly ShowPublicPage $showPublicPage) {}

    public function handle(ShareCardQueryData $data): ?string
    {
        $url = ShowShareCard::imageOf($this->showPublicPage->handle(), $data->page);
        if (! $url) {
            return null;
        }

        $disk = Storage::disk(Image::DISK);
        $path = Str::after((string) parse_url($url, PHP_URL_PATH), '/storage/');
        $jpegPath = preg_replace('/\.\w+$/', '', $path).'.preview.jpg';
        if ($disk->exists($jpegPath)) {
            return $disk->get($jpegPath);
        }
        if (! $disk->exists($path)) {
            return null;
        }

        $jpeg = ImageProcessor::jpegOf((string) $disk->get($path));
        if ($jpeg !== null) {
            $disk->put($jpegPath, $jpeg);
        }

        return $jpeg;
    }
}
