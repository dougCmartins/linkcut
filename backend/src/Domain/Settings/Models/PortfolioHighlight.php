<?php

declare(strict_types=1);

namespace Domain\Settings\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** A short fact on the portfolio (e.g. "10 anos" de experiência). */
final class PortfolioHighlight extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'label', 'value', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }
}
