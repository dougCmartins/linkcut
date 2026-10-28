<?php

declare(strict_types=1);

namespace Domain\Review\Actions;

use Domain\Review\Data\ClientReviewsQueryData;
use Domain\Review\Models\Review;

/** Removes a client's reviews and reports which barbershops must refresh their public page. */
final class DeleteClientReviews
{
    /** @return list<int> */
    public function handle(ClientReviewsQueryData $data): array
    {
        $tenantIds = Review::withoutTenantScope()
            ->where('client_id', $data->client_id)
            ->distinct()
            ->pluck('tenant_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        Review::withoutTenantScope()->where('client_id', $data->client_id)->delete();

        return $tenantIds;
    }
}
