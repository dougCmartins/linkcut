<?php

declare(strict_types=1);

namespace Domain\Review\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** The client's rating of one completed visit. Hidden reviews stay stored and leave the public page. */
final class Review extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'client_id', 'booking_request_id', 'rating', 'comment', 'author_first_name',
        'published_at', 'hidden_at', 'featured_position',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'published_at' => 'immutable_datetime',
            'hidden_at' => 'immutable_datetime',
            'featured_position' => 'integer',
        ];
    }
}
