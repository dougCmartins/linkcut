<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Review\Controllers;

use Domain\Orchestrator\Review\Actions\FeatureBarberReviews;
use Domain\Orchestrator\Review\Actions\HideBarberReview;
use Domain\Review\Actions\ListBarberReviews;
use Domain\Review\Data\BarberReviewData;
use Domain\Review\Data\FeatureReviewsData;
use Domain\Review\Data\ReviewIdData;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\DataCollection;

final class BarberReviewsController
{
    /** @return DataCollection<int, BarberReviewData> */
    public function index(ListBarberReviews $action): DataCollection
    {
        return $action->handle();
    }

    public function hide(int $review, HideBarberReview $action): JsonResponse
    {
        return response()->json($action->handle(new ReviewIdData(review_id: $review)));
    }

    /** @return DataCollection<int, BarberReviewData> */
    public function feature(FeatureReviewsData $data, FeatureBarberReviews $action): DataCollection
    {
        return $action->handle($data);
    }
}
