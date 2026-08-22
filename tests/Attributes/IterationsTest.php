<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\Attributes;

use Kaspi\Benchmark\Attributes\Iterations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Iterations::class)]
class IterationsTest extends TestCase
{
    #[TestWith([0])]
    #[TestWith([-10])]
    #[TestWith([20])]
    public function testResultAlwaysGraterThenZero(int $n): void
    {
        $this->assertGreaterThanOrEqual(1, (new Iterations($n))->iterations);
    }
}
