<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Review\Controllers;

use Domain\Orchestrator\Review\Actions\SubmitReview;
use Domain\Review\Data\SubmitReviewInputData;
use Domain\Review\Data\SubmittedReviewData;

final class ClientReviewController
{
    public function store(int $appointment, SubmitReviewInputData $input, SubmitReview $action): SubmittedReviewData
    {
        return $action->handle($appointment, $input);
    }
}
