<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Review source platforms Trustindex-style aggregation supports.
 *
 * Backed enum so it maps cleanly to a Doctrine column and (de)serializes
 * to a stable string in the API.
 */
enum Platform: string
{
    case Google = 'google';
    case Facebook = 'facebook';
    case Trustpilot = 'trustpilot';
    case Yelp = 'yelp';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::Facebook => 'Facebook',
            self::Trustpilot => 'Trustpilot',
            self::Yelp => 'Yelp',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $p): string => $p->value, self::cases());
    }
}
