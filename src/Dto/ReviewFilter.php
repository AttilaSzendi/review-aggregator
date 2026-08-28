<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\Platform;

/**
 * Read-model for the review listing query (filtering + pagination).
 *
 * Built from request query parameters; validated/normalised at construction so
 * the repository can trust its values.
 */
final readonly class ReviewFilter
{
    public const DEFAULT_PER_PAGE = 20;
    public const MAX_PER_PAGE = 100;

    public function __construct(
        public ?Platform $platform = null,
        public ?int $minRating = null,
        public int $page = 1,
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function fromQuery(array $query): self
    {
        $platform = null;
        if (isset($query['platform']) && '' !== $query['platform']) {
            // Unknown platform values are ignored rather than fatal: a filter is a hint.
            $platform = Platform::tryFrom((string) $query['platform']);
        }

        $minRating = isset($query['minRating']) ? (int) $query['minRating'] : null;
        if (null !== $minRating) {
            $minRating = max(1, min(5, $minRating));
        }

        $page = max(1, (int) ($query['page'] ?? 1));

        $perPage = (int) ($query['perPage'] ?? self::DEFAULT_PER_PAGE);
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));

        return new self($platform, $minRating, $page, $perPage);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
