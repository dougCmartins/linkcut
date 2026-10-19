<?php

declare(strict_types=1);

namespace Domain\Tenant\Models\Concerns;

use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** Adds "where tenant_id = current tenant". Without a tenant it throws instead of returning every row. */
final class TenantScope implements Scope
{
    public const NAME = 'tenant';

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->qualifyColumn('tenant_id'), app(CurrentTenant::class)->id());
    }
}
