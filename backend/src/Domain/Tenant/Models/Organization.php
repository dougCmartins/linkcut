<?php

declare(strict_types=1);

namespace Domain\Tenant\Models;

use Database\Factories\OrganizationFactory;
use Domain\Tenant\Enums\OrganizationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A brand that groups one or more units (tenants). An individual barber is an organization with one unit.
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
