<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Review;
use App\Enum\Platform;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Test/fixture factory for {@see Review} — the Symfony ecosystem's equivalent
 * of a Laravel model factory (zenstruck/foundry).
 *
 * @extends PersistentObjectFactory<Review>
 */
final class ReviewFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Review::class;
    }

    /**
     * Foundry maps these to the entity's constructor arguments by name.
     */
    protected function defaults(): array
    {
        return [
            'platform' => self::faker()->randomElement(Platform::cases()),
            'externalId' => self::faker()->unique()->bothify('rev-#####'),
            'authorName' => self::faker()->name(),
            'rating' => self::faker()->numberBetween(1, 5),
            'content' => self::faker()->sentence(),
            'reviewedAt' => \DateTimeImmutable::createFromInterface(
                self::faker()->dateTimeBetween('-1 year'),
            ),
        ];
    }

    public function onPlatform(Platform $platform): static
    {
        return $this->with(['platform' => $platform]);
    }

    public function withRating(int $rating): static
    {
        return $this->with(['rating' => $rating]);
    }

    public function reviewedAt(string $modifier): static
    {
        return $this->with(['reviewedAt' => new \DateTimeImmutable($modifier)]);
    }
}
