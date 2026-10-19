<?php

declare(strict_types=1);

namespace Domain\Settings\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** An uploaded image (WebP, at most 1600 px), stored on the public disk under tenants/{id}/. */
final class Image extends Model
{
    use BelongsToTenant;

    public const DISK = 'public';

    protected $fillable = ['tenant_id', 'path', 'mime', 'size', 'width', 'height'];

    protected function casts(): array
    {
        return ['size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    /**
     * Ids from the list that are not images of the current tenant (tenant scope applies).
     *
     * @param  list<int>  $ids
     * @return list<int>
     */
    public static function missingIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if (empty($ids)) {
            return [];
        }
        $found = self::query()->whereKey($ids)->pluck('id')->all();

        return array_values(array_diff($ids, $found));
    }

    public function url(): string
    {
        return Storage::disk(self::DISK)->url($this->path);
    }
}
