<?php

declare(strict_types=1);

namespace App\Provider;

use App\Dto\FetchedReview;
use App\Enum\Platform;

/**
 * Stand-in for the Google Business Profile API.
 *
 * Returns a deterministic, hand-written sample set so the import flow can be
 * demonstrated end-to-end without external credentials. Swapping this for a
 * real HTTP-backed provider requires no change elsewhere.
 */
final class MockGoogleReviewProvider implements ReviewProviderInterface
{
    public function platform(): Platform
    {
        return Platform::Google;
    }

    public function fetch(): iterable
    {
        $samples = [
            ['g-1001', 'Kovács Anna', 5, 'Fantastic service, the widget was live in minutes.'],
            ['g-1002', 'John Miller', 4, 'Solid product, support answered quickly.'],
            ['g-1003', 'María García', 5, 'Exactly what our shop needed to show reviews.'],
            ['g-1004', 'Tamás Nagy', 3, 'Works well but I missed a dark theme option.'],
            ['g-1005', 'Sofia Rossi', 5, 'Beautiful widgets and easy setup.'],
        ];

        foreach ($samples as $i => [$externalId, $author, $rating, $content]) {
            yield new FetchedReview(
                platform: Platform::Google,
                externalId: $externalId,
                authorName: $author,
                rating: $rating,
                content: $content,
                reviewedAt: new \DateTimeImmutable(sprintf('-%d days', $i + 1)),
            );
        }
    }
}
