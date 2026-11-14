<?php

declare(strict_types=1);

namespace Domain\Catalog\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** A product on the barbershop showcase. Inactive products stay stored and leave the client list. */
final class Product extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'price_in_cents', 'description', 'image_id', 'is_active', 'position',
    ];

    protected function casts(): array
    {
        return [
            'price_in_cents' => 'integer',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }
}
