<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Image;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** Multipart upload: JPEG, PNG or WebP up to 5 MB (content checked, not only the extension). */
final class UploadImageData extends Data
{
    public function __construct(
        #[Required, Image, Mimes('jpeg', 'png', 'webp'), Max(5120)]
        public readonly UploadedFile $file,
    ) {}
}
