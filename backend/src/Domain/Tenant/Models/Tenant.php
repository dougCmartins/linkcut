<?php

declare(strict_types=1);

namespace Domain\Tenant\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer of the platform (a barbershop). Every business row belongs to one tenant.
 * A tenant is one unit (the main one or a branch) of an organization (docs/adr/0002).
 */
final class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /** The columns TenantData needs (lists select only these). */
    public const DATA_COLUMNS = ['id', 'name', 'slug', 'active', 'timezone', 'onboarded_at', 'organization_id', 'is_main'];

    protected $fillable = ['name', 'slug', 'active', 'timezone', 'onboarded_at', 'organization_id', 'is_main'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'is_main' => 'boolean', 'onboarded_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }
}
