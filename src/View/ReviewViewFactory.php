<?php

declare(strict_types=1);

namespace App\View;

use App\Dto\ReviewStats;
use App\Entity\Review;

/**
 * Maps domain objects to the JSON shape the API exposes.
 *
 * Keeping this out of the controllers gives a single, stable contract and
 * avoids leaking entity internals into responses.
 */
final class ReviewViewFactory
{
    /**
     * @return array<string, mixed>
     */
    public function review(Review $review): array
    {
        return [
            'id' => $review->getId(),
            'platform' => $review->getPlatform()->value,
            'externalId' => $review->getExternalId(),
            'authorName' => $review->getAuthorName(),
            'rating' => $review->getRating(),
            'content' => $review->getContent(),
            'reviewedAt' => $review->getReviewedAt()->format(\DATE_ATOM),
            'createdAt' => $review->getCreatedAt()->format(\DATE_ATOM),
        ];
    }

    /**
     * @param iterable<Review> $reviews
     *
     * @return list<array<string, mixed>>
     */
    public function reviewList(iterable $reviews): array
    {
        $out = [];
        foreach ($reviews as $review) {
            $out[] = $this->review($review);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(ReviewStats $stats): array
    {
        return [
            'total' => $stats->total,
            'average' => $stats->average,
            'distribution' => $stats->distribution,
        ];
    }
}
