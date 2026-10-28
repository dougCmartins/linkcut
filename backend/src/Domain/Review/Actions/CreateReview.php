<?php

declare(strict_types=1);

namespace Domain\Review\Actions;

use Domain\Review\Data\CreateReviewData;
use Domain\Review\Exceptions\ReviewAlreadyExistsException;
use Domain\Review\Models\Review;
use Illuminate\Database\UniqueConstraintViolationException;

/** Saves the review of the current barbershop. One per visit. */
final class CreateReview
{
    public function handle(CreateReviewData $data): Review
    {
        if (Review::query()->where('booking_request_id', $data->booking_request_id)->exists()) {
            throw new ReviewAlreadyExistsException;
        }

        try {
            return Review::query()->create([
                'client_id' => $data->client_id,
                'booking_request_id' => $data->booking_request_id,
                'rating' => $data->rating,
                'comment' => $data->comment,
                'author_first_name' => $data->author_first_name,
                'published_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new ReviewAlreadyExistsException;
        }
    }
}
