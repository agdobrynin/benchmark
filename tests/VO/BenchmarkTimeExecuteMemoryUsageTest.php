<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\VO;

use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use Kaspi\Benchmark\VO\BenchmarkTimeExecuteMemoryUsage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkTimeExecuteMemoryUsage::class)]
#[UsesClass(TimeExecuteMemoryUsageInIteration::class)]
class BenchmarkTimeExecuteMemoryUsageTest extends TestCase
{
    public function testInputDataIsEmpty(): void
    {
        $b = new BenchmarkTimeExecuteMemoryUsage([]);

        self::assertEquals(0, $b->bytesLeaking);
        self::assertEquals(0, $b->bytesPeakUsage);
        self::assertEquals(0, $b->bytesUsage);
        self::assertEquals(0.0, $b->time);
        self::assertEquals(0, $b->numberOfTimes);
        self::assertEquals(0, $b->iterations);
    }

    #[TestWith([
        [
            new TimeExecuteMemoryUsageInIteration(100, 120, 120, 1, 1, 2),
            new TimeExecuteMemoryUsageInIteration(120, 150, 159, 1, 1, 2),
            new TimeExecuteMemoryUsageInIteration(150, 106, 160, 1, 1, 2),
        ],
        0,
    ])]
    #[TestWith([
        [
            new TimeExecuteMemoryUsageInIteration(100, 120, 120, 1, 1, 2),
            new TimeExecuteMemoryUsageInIteration(120, 150, 150, 1, 1, 2),
            new TimeExecuteMemoryUsageInIteration(150, 152, 180, 1, 1, 2),
        ],
        32,
    ])]
    public function testMemoryLeaking(array $data, int $expectMemoryLeaking): void
    {
        $b = new BenchmarkTimeExecuteMemoryUsage($data);

        self::assertEquals($expectMemoryLeaking, $b->bytesLeaking);
    }
}
