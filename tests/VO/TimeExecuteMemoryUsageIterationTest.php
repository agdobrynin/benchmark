<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\VO;

use Kaspi\Benchmark\VO\TimeExecuteMemoryUsageIteration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function round;

/**
 * @internal
 */
#[CoversClass(TimeExecuteMemoryUsageIteration::class)]
class TimeExecuteMemoryUsageIterationTest extends TestCase
{
    public function testTimeExecuteMemoryUsageIteration(): void
    {
        $vo = new TimeExecuteMemoryUsageIteration(
            10,
            20,
            30,
            32,
            200.3999,
            202.9999,
            2,
        );

        $this->assertIsInt($vo->memoryUsage());
        $this->assertEquals(10, $vo->memoryUsage());

        $this->assertIsInt($vo->memoryPeakUsage());
        $this->assertEquals(2, $vo->memoryPeakUsage());

        $this->assertIsFloat($vo->hrTime());
        $this->assertEquals(1.3, round($vo->hrTime(), 4));
    }
}
