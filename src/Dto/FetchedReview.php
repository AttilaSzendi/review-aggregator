<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\Platform;

/**
 * A review as returned by an external provider, before it is persisted.
 */
final readonly class FetchedReview
{
    public function __construct(
        public Platform $platform,
        public string $externalId,
        public string $authorName,
        public int $rating,
        public string $content,
        public \DateTimeImmutable $reviewedAt,
    ) {
    }
}
