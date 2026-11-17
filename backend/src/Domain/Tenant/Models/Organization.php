<?php

declare(strict_types=1);

namespace Domain\Tenant\Models;

use Database\Factories\OrganizationFactory;
use Domain\Tenant\Enums\OrganizationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The brand (docs/adr/0002): groups the main unit and the branches, each one a tenant.
 * A barber on their own is an organization with one unit. Not tenant-scoped: it sits above the tenants.
 */
final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected $fillable = ['owner_user_id', 'type', 'name', 'slug'];

    protected function casts(): array
    {
        return ['type' => OrganizationType::class];
    }

    /** @return HasMany<Tenant, $this> */
    public function units(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }
}
