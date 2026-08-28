<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\Platform;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request payload for creating a review through the API.
 *
 * Bound via #[MapRequestPayload]: Symfony deserializes the JSON body into this
 * object and runs the validator before the controller is invoked.
 */
final class CreateReviewInput
{
    public function __construct(
        public readonly Platform $platform,

        #[Assert\NotBlank]
        #[Assert\Length(max: 128)]
        public readonly string $externalId,

        #[Assert\NotBlank]
        #[Assert\Length(max: 180)]
        public readonly string $authorName,

        #[Assert\Range(min: 1, max: 5)]
        public readonly int $rating,

        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        public readonly string $content,
    ) {
    }
}
