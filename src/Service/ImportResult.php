<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Outcome of an import run, aggregated across every provider.
 */
final class ImportResult
{
    private int $created = 0;
    private int $updated = 0;

    public function recordCreated(): void
    {
        ++$this->created;
    }

    public function recordUpdated(): void
    {
        ++$this->updated;
    }

    public function created(): int
    {
        return $this->created;
    }

    public function updated(): int
    {
        return $this->updated;
    }

    public function total(): int
    {
        return $this->created + $this->updated;
    }
}
