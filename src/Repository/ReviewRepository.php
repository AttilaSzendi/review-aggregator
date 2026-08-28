<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\ReviewFilter;
use App\Entity\Review;
use App\Enum\Platform;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function save(Review $review, bool $flush = true): void
    {
        $this->getEntityManager()->persist($review);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to keep imports idempotent.
     */
    public function findOneBySource(Platform $platform, string $externalId): ?Review
    {
        return $this->findOneBy(['platform' => $platform, 'externalId' => $externalId]);
    }

    /**
     * @return array{items: list<Review>, total: int}
     */
    public function findPaginated(ReviewFilter $filter): array
    {
        $qb = $this->createFilteredQueryBuilder($filter)
            ->orderBy('r.reviewedAt', 'DESC')
            ->setFirstResult($filter->offset())
            ->setMaxResults($filter->perPage);

        $paginator = new Paginator($qb, fetchJoinCollection: false);

        return [
            'items' => iterator_to_array($paginator),
            'total' => \count($paginator),
        ];
    }

    /**
     * Ratings matching the filter, for statistics aggregation.
     *
     * Returns raw ratings (not hydrated entities) so the pure
     * {@see \App\Service\ReviewStatsCalculator} can stay free of persistence.
     *
     * @return list<int>
     */
    public function findRatings(ReviewFilter $filter): array
    {
        /** @var list<array{rating: int}> $rows */
        $rows = $this->createFilteredQueryBuilder($filter)
            ->select('r.rating')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => $row['rating'], $rows);
    }

    private function createFilteredQueryBuilder(ReviewFilter $filter): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r');

        if (null !== $filter->platform) {
            $qb->andWhere('r.platform = :platform')
                ->setParameter('platform', $filter->platform);
        }

        if (null !== $filter->minRating) {
            $qb->andWhere('r.rating >= :minRating')
                ->setParameter('minRating', $filter->minRating);
        }

        return $qb;
    }
}
