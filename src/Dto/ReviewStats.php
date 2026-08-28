<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Aggregated rating statistics — the numbers a Trustindex-style widget renders.
 */
final readonly class ReviewStats
{
    /**
     * @param array<int, int> $distribution rating (1-5) => count, always keyed 1..5
     */
    public function __construct(
        public int $total,
        public float $average,
        public array $distribution,
    ) {
    }
}
