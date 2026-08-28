<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateReviewInput;
use App\Entity\Review;
use App\Repository\ReviewRepository;

/**
 * Single place that turns a validated {@see CreateReviewInput} into a persisted
 * {@see Review}. Both the API and the admin form funnel through here, so the
 * create logic lives in exactly one spot.
 */
final class ReviewCreator
{
    public function __construct(private readonly ReviewRepository $reviews)
    {
    }

    /**
     * The input is expected to be already validated (platform and rating set).
     */
    public function create(CreateReviewInput $input): Review
    {
        \assert(null !== $input->platform && null !== $input->rating);

        $review = new Review(
            $input->platform,
            $input->externalId,
            $input->authorName,
            $input->rating,
            $input->content,
            new \DateTimeImmutable(),
        );
        $this->reviews->save($review);

        return $review;
    }
}
