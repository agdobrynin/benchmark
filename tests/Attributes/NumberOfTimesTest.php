<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\Attributes;

use Kaspi\Benchmark\Attributes\NumberOfTimes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(NumberOfTimes::class)]
class NumberOfTimesTest extends TestCase
{
    #[TestWith([0])]
    #[TestWith([-10])]
    #[TestWith([20])]
    public function testResultAlwaysGraterThenZero(int $n): void
    {
        $this->assertGreaterThanOrEqual(1, (new NumberOfTimes($n))->numberOfTimes);
    }
}
