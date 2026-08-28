<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ReviewStats;

/**
 * Computes rating aggregates from a plain list of ratings.
 *
 * Deliberately free of Doctrine and HTTP: a pure transformation that is trivial
 * to unit test and reusable anywhere ratings come from.
 */
final class ReviewStatsCalculator
{
    private const MIN_RATING = 1;
    private const MAX_RATING = 5;

    /**
     * @param iterable<int> $ratings
     */
    public function calculate(iterable $ratings): ReviewStats
    {
        $distribution = array_fill_keys(range(self::MIN_RATING, self::MAX_RATING), 0);
        $sum = 0;
        $total = 0;

        foreach ($ratings as $rating) {
            if ($rating < self::MIN_RATING || $rating > self::MAX_RATING) {
                throw new \InvalidArgumentException(sprintf('Rating %d is out of the 1-5 range.', $rating));
            }

            ++$distribution[$rating];
            $sum += $rating;
            ++$total;
        }

        $average = 0 === $total ? 0.0 : round($sum / $total, 2);

        return new ReviewStats($total, $average, $distribution);
    }
}
