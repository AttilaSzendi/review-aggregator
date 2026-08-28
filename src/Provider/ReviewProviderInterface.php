<?php

declare(strict_types=1);

namespace App\Provider;

use App\Dto\FetchedReview;
use App\Enum\Platform;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Source of reviews for a single external platform.
 *
 * New platforms are added by implementing this interface — autoconfiguration
 * tags it and the importer picks it up automatically, unchanged
 * (Open/Closed + Dependency Inversion).
 */
#[AutoconfigureTag('app.review_provider')]
interface ReviewProviderInterface
{
    public function platform(): Platform;

    /**
     * @return iterable<FetchedReview>
     */
    public function fetch(): iterable;
}
