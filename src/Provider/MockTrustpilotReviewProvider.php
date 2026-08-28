<?php

declare(strict_types=1);

namespace App\Provider;

use App\Dto\FetchedReview;
use App\Enum\Platform;

/**
 * Stand-in for the Trustpilot Business API. See {@see MockGoogleReviewProvider}.
 */
final class MockTrustpilotReviewProvider implements ReviewProviderInterface
{
    public function platform(): Platform
    {
        return Platform::Trustpilot;
    }

    public function fetch(): iterable
    {
        $samples = [
            ['tp-2001', 'Peter Schmidt', 5, 'Best review tool we have tried so far.'],
            ['tp-2002', 'Aisha Khan', 4, 'Great value, integrates nicely with our store.'],
            ['tp-2003', 'Lucas Silva', 2, 'Had a billing hiccup but it got resolved.'],
        ];

        foreach ($samples as $i => [$externalId, $author, $rating, $content]) {
            yield new FetchedReview(
                platform: Platform::Trustpilot,
                externalId: $externalId,
                authorName: $author,
                rating: $rating,
                content: $content,
                reviewedAt: new \DateTimeImmutable(sprintf('-%d days', $i + 2)),
            );
        }
    }
}
