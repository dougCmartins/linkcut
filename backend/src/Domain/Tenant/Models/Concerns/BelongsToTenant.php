<?php

declare(strict_types=1);

namespace Domain\Tenant\Models\Concerns;

use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Exceptions\TenantMismatchException;
use Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For every model owned by a tenant (services, appointments, expenses…):
 * - queries are filtered by the current tenant automatically (fail closed);
 * - new rows get the current tenant_id;
 * - writing a row of another tenant is refused.
 * Platform-wide reads opt out explicitly with Model::withoutTenantScope().
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(TenantScope::NAME, new TenantScope);

        static::creating(function (self $model): void {
            $currentTenantId = app(CurrentTenant::class)->id();
            if (empty($model->tenant_id)) {
                $model->tenant_id = $currentTenantId;

                return;
            }
            if ((int) $model->tenant_id !== $currentTenantId) {
                throw new TenantMismatchException;
            }
        });
    }

    /** @return Builder<static> */
    public static function withoutTenantScope(): Builder
    {
        return static::query()->withoutGlobalScope(TenantScope::NAME);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
