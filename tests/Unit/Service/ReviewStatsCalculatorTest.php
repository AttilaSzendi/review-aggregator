<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ReviewStatsCalculator;
use PHPUnit\Framework\TestCase;

final class ReviewStatsCalculatorTest extends TestCase
{
    private ReviewStatsCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ReviewStatsCalculator();
    }

    public function testEmptyInputYieldsZeros(): void
    {
        $stats = $this->calculator->calculate([]);

        self::assertSame(0, $stats->total);
        self::assertSame(0.0, $stats->average);
        self::assertSame([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0], $stats->distribution);
    }

    public function testComputesAverageAndDistribution(): void
    {
        $stats = $this->calculator->calculate([5, 5, 4, 3, 1]);

        self::assertSame(5, $stats->total);
        self::assertSame(3.6, $stats->average);
        self::assertSame([1 => 1, 2 => 0, 3 => 1, 4 => 1, 5 => 2], $stats->distribution);
    }

    public function testAverageIsRoundedToTwoDecimals(): void
    {
        // 4 + 5 + 5 = 14 / 3 = 4.666...
        $stats = $this->calculator->calculate([4, 5, 5]);

        self::assertSame(4.67, $stats->average);
    }

    public function testAcceptsAnyTraversable(): void
    {
        $gen = (static function (): \Generator {
            yield 2;
            yield 4;
        })();

        $stats = $this->calculator->calculate($gen);

        self::assertSame(2, $stats->total);
        self::assertSame(3.0, $stats->average);
    }

    /**
     * @dataProvider outOfRangeRatings
     */
    public function testRejectsOutOfRangeRatings(int $invalid): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->calculate([5, $invalid]);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function outOfRangeRatings(): iterable
    {
        yield 'zero' => [0];
        yield 'six' => [6];
        yield 'negative' => [-3];
    }
}
