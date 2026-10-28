<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Review\Actions;

use Domain\Client\Models\Client;
use Domain\Notification\Support\PlainText;
use Domain\Review\Actions\CreateReview;
use Domain\Review\Data\CreateReviewData;
use Domain\Review\Data\SubmitReviewInputData;
use Domain\Review\Data\SubmittedReviewData;
use Domain\Review\Exceptions\ReviewNotAllowedException;
use Domain\Review\Support\AuthorName;
use Domain\Schedule\Actions\ShowClientBooking;
use Domain\Schedule\Data\ClientBookingIdData;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Settings\Support\PublicSettingsCache;
use Domain\Tenant\Context\CurrentTenant;

/** The signed-in client rates one completed visit of their own, once. */
final class SubmitReview
{
    public function __construct(
        private readonly ShowClientBooking $showBooking,
        private readonly CreateReview $createReview,
        private readonly CurrentTenant $tenant,
        private readonly PublicSettingsCache $cache,
    ) {}

    public function handle(int $bookingRequestId, SubmitReviewInputData $input): SubmittedReviewData
    {
        /** @var Client $client */
        $client = auth('client')->user();
        $booking = $this->showBooking->handle(new ClientBookingIdData(booking_id: $bookingRequestId, client_id: $client->id));
        if ($booking->status !== BookingStatus::Completed) {
            throw new ReviewNotAllowedException;
        }

        $review = $this->tenant->run($booking->tenant_id, function () use ($booking, $client, $input) {
            $review = $this->createReview->handle(new CreateReviewData(
                client_id: $client->id,
                booking_request_id: $booking->id,
                rating: $input->rating,
                comment: PlainText::text($input->comment),
                author_first_name: AuthorName::first($booking->client_name),
            ));
            $this->cache->forget();

            return $review;
        });

        return new SubmittedReviewData(id: $review->id, rating: $review->rating);
    }
}
