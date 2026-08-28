<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Review;
use App\Provider\ReviewProviderInterface;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * Pulls reviews from every registered provider and persists them idempotently.
 *
 * Depends only on the {@see ReviewProviderInterface} abstraction, so it works
 * with any current or future provider without modification.
 */
final class ReviewImporter
{
    private readonly LoggerInterface $logger;

    /**
     * @param iterable<ReviewProviderInterface> $providers
     */
    public function __construct(
        #[TaggedIterator('app.review_provider')]
        private readonly iterable $providers,
        private readonly ReviewRepository $reviews,
        private readonly EntityManagerInterface $entityManager,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function import(): ImportResult
    {
        $result = new ImportResult();

        foreach ($this->providers as $provider) {
            $this->importFrom($provider, $result);
        }

        $this->entityManager->flush();

        $this->logger->info('Review import finished', [
            'created' => $result->created(),
            'updated' => $result->updated(),
        ]);

        return $result;
    }

    private function importFrom(ReviewProviderInterface $provider, ImportResult $result): void
    {
        foreach ($provider->fetch() as $fetched) {
            $existing = $this->reviews->findOneBySource($fetched->platform, $fetched->externalId);

            if (null === $existing) {
                $review = new Review(
                    $fetched->platform,
                    $fetched->externalId,
                    $fetched->authorName,
                    $fetched->rating,
                    $fetched->content,
                    $fetched->reviewedAt,
                );
                $this->reviews->save($review, flush: false);
                $result->recordCreated();

                continue;
            }

            $existing->updateFromSource(
                $fetched->authorName,
                $fetched->rating,
                $fetched->content,
                $fetched->reviewedAt,
            );
            $result->recordUpdated();
        }
    }
}
