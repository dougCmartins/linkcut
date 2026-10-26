<?php

declare(strict_types=1);

namespace Domain\Settings\Support;

use Domain\Settings\Exceptions\InvalidImageException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Re-encodes an upload as WebP, at most 1600 px on the longer side (never upscaled).
 * Re-encoding also drops whatever else the file carried (metadata, scripts in disguise).
 */
final class ImageProcessor
{
    public const MAX_DIMENSION = 1600;

    public const WEBP_QUALITY = 85;

    /** 40 megapixels (e.g. 8000 × 5000): above that, decoding alone would exhaust the PHP memory. */
    public const MAX_PIXELS = 40_000_000;

    /** @return array{path: string, mime: string, size: int, width: int, height: int} */
    public static function storeAsWebp(UploadedFile $file, int $tenantId, string $disk): array
    {
        $size = @getimagesize($file->getRealPath());
        if (! $size || $size[0] * $size[1] > self::MAX_PIXELS) {
            throw new InvalidImageException;
        }
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $source) {
            throw new InvalidImageException;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_DIMENSION / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagewebp($target, null, self::WEBP_QUALITY);
        $bytes = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        $path = "tenants/{$tenantId}/images/".Str::ulid()->toBase32().'.webp';
        Storage::disk($disk)->put($path, $bytes);

        return ['path' => $path, 'mime' => 'image/webp', 'size' => strlen($bytes), 'width' => $targetWidth, 'height' => $targetHeight];
    }

    /** Max side of the JPEG copy used by link previews (WhatsApp and Facebook read JPEG best). */
    public const PREVIEW_MAX_DIMENSION = 1200;

    /**
     * A JPEG copy of a stored image, for link previews: some apps ignore WebP there. Transparent parts become white.
     * Null when the bytes are not an image.
     */
    public static function jpegOf(string $bytes): ?string
    {
        $source = @imagecreatefromstring($bytes);
        if (! $source) {
            return null;
        }
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::PREVIEW_MAX_DIMENSION / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($target, 0, 0, (int) imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($target, null, 82);
        $jpeg = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        return $jpeg;
    }
}
