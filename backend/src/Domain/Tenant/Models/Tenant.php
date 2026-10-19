<?php

declare(strict_types=1);

namespace Domain\Tenant\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A customer of the platform (a barbershop). Every business row belongs to one tenant.
 */
final class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }
}
