<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateReviewInput;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use Symfony\Component\Uid\Ulid;

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

        // Manual/API entries carry no platform-assigned id; generate a unique one
        // so the (platform, externalId) uniqueness still holds.
        $externalId = '' !== (string) $input->externalId
            ? (string) $input->externalId
            : 'manual-'.(new Ulid())->toBase58();

        $review = new Review(
            $input->platform,
            $externalId,
            $input->authorName,
            $input->rating,
            $input->content,
            new \DateTimeImmutable(),
        );
        $this->reviews->save($review);

        return $review;
    }
}
