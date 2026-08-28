<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Platform;
use App\Repository\ReviewRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single customer review pulled from an external platform.
 *
 * A unique (platform, externalId) pair keeps imports idempotent: re-importing
 * the same source review updates the existing row instead of duplicating it.
 */
#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'review')]
#[ORM\UniqueConstraint(name: 'uniq_platform_external', columns: ['platform', 'external_id'])]
#[ORM\Index(name: 'idx_platform', columns: ['platform'])]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, enumType: Platform::class)]
    private Platform $platform;

    /**
     * Identifier of the review on the source platform (used for idempotent import).
     */
    #[ORM\Column(name: 'external_id', length: 128)]
    private string $externalId;

    #[ORM\Column(length: 180)]
    private string $authorName;

    #[ORM\Column(type: 'smallint')]
    private int $rating;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column]
    private \DateTimeImmutable $reviewedAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Platform $platform,
        string $externalId,
        string $authorName,
        int $rating,
        string $content,
        \DateTimeImmutable $reviewedAt,
    ) {
        $this->platform = $platform;
        $this->externalId = $externalId;
        $this->authorName = $authorName;
        $this->rating = $rating;
        $this->content = $content;
        $this->reviewedAt = $reviewedAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getReviewedAt(): \DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Update the mutable parts of a review from a freshly fetched source copy.
     */
    public function updateFromSource(string $authorName, int $rating, string $content, \DateTimeImmutable $reviewedAt): void
    {
        $this->authorName = $authorName;
        $this->rating = $rating;
        $this->content = $content;
        $this->reviewedAt = $reviewedAt;
    }
}
