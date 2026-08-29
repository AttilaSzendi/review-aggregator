<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\Platform;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command payload for creating a review — the single source of both the
 * validation rules and the create contract.
 *
 * Used by two entry points without duplication:
 *  - the JSON API, bound via #[MapRequestPayload] (deserialised + validated);
 *  - the admin form, bound as its data_class (the same constraints drive the
 *    form errors).
 *
 * A mutable form-model (public properties, sensible defaults) rather than an
 * immutable value object, because both the Form component and the request
 * deserialiser need to populate it field by field.
 */
final class CreateReviewInput
{
    #[Assert\NotNull]
    public ?Platform $platform = null;

    /**
     * Identifier on the source platform. Supplied by importers to keep imports
     * idempotent; left null for manual/API entries, where {@see \App\Service\ReviewCreator}
     * generates one.
     */
    #[Assert\Length(max: 128)]
    public ?string $externalId = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    public string $authorName = '';

    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 5)]
    public ?int $rating = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 5000)]
    public string $content = '';
}
